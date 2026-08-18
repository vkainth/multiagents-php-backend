<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SavedSearches;
use App\Models\BuildingFollow;
use App\Models\FavoriteListings;
use App\Models\AlertHistory;

class AlertSubscriptionApiController extends Controller
{
    /**
     * Cursor format: "s{last_search_id}:b{last_follow_id}:w{last_watch_id}"
     * Any component may be omitted when that type is exhausted.
     */
    public function index(Request $request)
    {
        $isTest = $request->attributes->get('alert_api_test_mode', false);
        $limit  = min((int) $request->get('limit', 200), 500);
        $cursor = $request->get('cursor', '');

        if ($isTest) {
            return $this->testSubscriptions($cursor, $limit);
        }

        // Parse typed cursors from composite string "s{id}:b{id}:w{id}"
        $searchCursor = null;
        $followCursor = null;
        $watchCursor  = null;
        if ($cursor) {
            if (preg_match('/s(\d+)/', $cursor, $m)) {
                $searchCursor = (int) $m[1];
            }
            if (preg_match('/b(\d+)/', $cursor, $m)) {
                $followCursor = (int) $m[1];
            }
            if (preg_match('/w(\d+)/', $cursor, $m)) {
                $watchCursor = (int) $m[1];
            }
        }

        // Fetch up to limit+1 from each type independently so the combined
        // page can fill up to the full requested limit regardless of type mix.
        $searches = SavedSearches::where('daily_email', 1)
            ->where('confirmed', 1)
            ->where('active', 1)
            ->when($searchCursor, fn ($q) => $q->where('id', '>', $searchCursor))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $follows = BuildingFollow::where('confirmed', 1)
            ->where('active', 1)
            ->when($followCursor, fn ($q) => $q->where('id', '>', $followCursor))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        // Listing watches: active rows in favorite_listings with at least one watch flag set.
        $watches = FavoriteListings::where('deleted', 0)
            ->where(function ($q) {
                $q->where('watch_price_drop', 1)->orWhere('watch_sold', 1);
            })
            ->when($watchCursor, fn ($q) => $q->where('id', '>', $watchCursor))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $searchHasMore = $searches->count() > $limit;
        $followHasMore = $follows->count() > $limit;
        $watchHasMore  = $watches->count() > $limit;

        $searchPage = $searches->take($limit);
        $followPage = $follows->take($limit);
        $watchPage  = $watches->take($limit);

        // Pre-aggregate user emails and last-active timestamps in batch to avoid N+1 queries.
        $allUserIds = $searchPage->pluck('userid')
            ->merge($followPage->pluck('userid'))
            ->merge($watchPage->pluck('userid'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $userEmails     = $this->batchEmailsForUserIds($allUserIds);
        $userLastActive = $this->batchUserLastActiveAt($allUserIds);

        // Build formatted item arrays from each stream, then interleave up to $limit total.
        $searchItems = [];
        foreach ($searchPage as $s) {
            $searchItems[] = [
                'id'                  => 's_' . $s->id,
                'record_id'           => $s->id,
                'type'                => 'search',
                'email'               => $s->email ?? ($userEmails[$s->userid] ?? null),
                'userid'              => $s->userid,
                'search_name'         => $s->search_name,
                'criteria'            => $this->parseCriteria($s->data ?? '{}'),
                'active'              => (bool) $s->active,
                'last_update_sent'    => $s->last_update_sent,
                'user_last_active_at' => $userLastActive[$s->userid] ?? null,
                'created_at'          => $s->created_at,
            ];
        }

        $followItems = [];
        foreach ($followPage as $f) {
            $followItems[] = [
                'id'                  => 'b_' . $f->id,
                'record_id'           => $f->id,
                'type'                => 'building',
                'email'               => $f->email ?? ($userEmails[$f->userid] ?? null),
                'userid'              => $f->userid,
                'building_slug'       => $f->building_slug,
                'building_name'       => $f->building_name,
                'street_no'           => $f->street_no,
                'street_name'         => $f->street_name,
                'city'                => $f->city,
                'strata_no'           => $f->strata_no,
                'active'              => (bool) $f->active,
                'last_update_sent'    => $f->last_update_sent,
                'user_last_active_at' => $userLastActive[$f->userid] ?? null,
                'created_at'          => $f->created_at,
            ];
        }

        $watchItems = [];
        foreach ($watchPage as $w) {
            $watchItems[] = [
                'id'                  => 'w_' . $w->id,
                'record_id'           => $w->id,
                'type'                => 'watch',
                'email'               => $userEmails[$w->userid] ?? null,
                'userid'              => $w->userid,
                'listingid'           => $w->listingid,
                'watch_price_drop'    => (bool) ($w->watch_price_drop ?? false),
                'watch_sold'          => (bool) ($w->watch_sold ?? false),
                'active'              => (bool) (!$w->deleted),
                'last_update_sent'    => $w->last_update_sent,
                'user_last_active_at' => $userLastActive[$w->userid] ?? null,
                'created_at'          => $w->created_at,
            ];
        }

        // Round-robin interleave all three streams, filling to $limit total.
        $items  = collect();
        $si     = 0; $fi = 0; $wi = 0;
        $sTotal = count($searchItems);
        $fTotal = count($followItems);
        $wTotal = count($watchItems);
        while ($items->count() < $limit) {
            $hasSrc = $si < $sTotal;
            $hasFol = $fi < $fTotal;
            $hasWch = $wi < $wTotal;
            if (!$hasSrc && !$hasFol && !$hasWch) {
                break;
            }
            if ($hasSrc && $items->count() < $limit) {
                $items->push($searchItems[$si++]);
            }
            if ($hasFol && $items->count() < $limit) {
                $items->push($followItems[$fi++]);
            }
            if ($hasWch && $items->count() < $limit) {
                $items->push($watchItems[$wi++]);
            }
        }

        // Cursor encodes last consumed position in each stream.
        $nextCursor = null;
        $searchExhausted = !$searchHasMore && $si >= $sTotal;
        $followExhausted = !$followHasMore && $fi >= $fTotal;
        $watchExhausted  = !$watchHasMore  && $wi >= $wTotal;

        if (!$searchExhausted || !$followExhausted || !$watchExhausted) {
            $lastSearchId = $si > 0 ? $searchPage->values()[$si - 1]->id : $searchCursor;
            $lastFollowId = $fi > 0 ? $followPage->values()[$fi - 1]->id : $followCursor;
            $lastWatchId  = $wi > 0 ? $watchPage->values()[$wi - 1]->id  : $watchCursor;
            $parts = [];
            if ($lastSearchId && !$searchExhausted) {
                $parts[] = 's' . $lastSearchId;
            }
            if ($lastFollowId && !$followExhausted) {
                $parts[] = 'b' . $lastFollowId;
            }
            if ($lastWatchId && !$watchExhausted) {
                $parts[] = 'w' . $lastWatchId;
            }
            $nextCursor = implode(':', $parts) ?: null;
        }

        return response()->json([
            'success'     => true,
            'data'        => $items->values(),
            'next_cursor' => $nextCursor,
        ]);
    }

    public function alertSent(Request $request)
    {
        $isTest  = $request->attributes->get('alert_api_test_mode', false);
        $type     = $request->input('type');
        $recordId = $request->input('record_id');
        $sentAt   = $request->input('sent_at');
        $listingIds = $request->input('listing_ids', []);

        if (!$type || !$recordId) {
            return response()->json(['success' => false, 'message' => 'type and record_id are required'], 422);
        }

        if ($isTest) {
            return response()->json(['success' => true, 'test_mode' => true]);
        }

        if ($type === 'search') {
            SavedSearches::where('id', $recordId)->update(['last_update_sent' => $sentAt ?? now()]);
            $record = SavedSearches::find($recordId);
        } elseif ($type === 'watch') {
            FavoriteListings::where('id', $recordId)->update(['last_update_sent' => $sentAt ?? now()]);
            $record = FavoriteListings::find($recordId);
        } else {
            BuildingFollow::where('id', $recordId)->update(['last_update_sent' => $sentAt ?? now()]);
            $record = BuildingFollow::find($recordId);
        }

        if ($record) {
            AlertHistory::create([
                'userid'     => $record->userid ?? null,
                'email'      => $record->email ?? ($record->userid ? $this->emailForUserId($record->userid) : null),
                'type'       => $type,
                'record_id'  => $recordId,
                'listing_ids' => is_array($listingIds) ? $listingIds : [],
                'sent_at'    => $sentAt ?? now(),
                'created_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function deactivateAlert(Request $request)
    {
        $isTest   = $request->attributes->get('alert_api_test_mode', false);
        $type     = $request->input('type');
        $recordId = $request->input('record_id');

        if (!$type || !$recordId) {
            return response()->json(['success' => false, 'message' => 'type and record_id are required'], 422);
        }

        if ($isTest) {
            return response()->json(['success' => true, 'test_mode' => true]);
        }

        if ($type === 'search') {
            SavedSearches::where('id', $recordId)->update(['active' => 0]);
        } elseif ($type === 'watch') {
            // Clear both watch flags — the favorite row itself is preserved
            FavoriteListings::where('id', $recordId)->update([
                'watch_price_drop' => 0,
                'watch_sold'       => 0,
            ]);
        } else {
            BuildingFollow::where('id', $recordId)->update(['active' => 0]);
        }

        return response()->json(['success' => true]);
    }

    private function parseCriteria(string $json): array
    {
        $data = json_decode($json, true) ?: [];
        $fields = [
            'cities', 'subareas', 'areas', 'type', 'status',
            'min_price', 'max_price', 'min_beds', 'max_beds',
            'min_baths', 'max_baths', 'min_sqft', 'max_sqft',
            'year_built_min', 'year_built_max', 'strata_fee_max',
            'lot_size_min', 'lot_size_max', 'views', 'features',
        ];
        $out = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $out[$f] = $data[$f];
            }
        }
        return $out;
    }

    /** @param int[] $userIds @return array<int,string> */
    private function batchEmailsForUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        return DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('email', 'id')
            ->all();
    }

    /** @param int[] $userIds @return array<int,string|null> */
    private function batchUserLastActiveAt(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $tables = ['user_property_views', 'user_searches', 'user_building_views'];
        $result = array_fill_keys($userIds, null);

        foreach ($tables as $table) {
            $rows = DB::table($table)
                ->whereIn('userid', $userIds)
                ->select('userid', DB::raw('MAX(created_at) as last_at'))
                ->groupBy('userid')
                ->pluck('last_at', 'userid')
                ->all();

            foreach ($rows as $uid => $lastAt) {
                if ($lastAt && ($result[$uid] === null || $lastAt > $result[$uid])) {
                    $result[$uid] = $lastAt;
                }
            }
        }

        return $result;
    }

    /** @deprecated Use batchEmailsForUserIds for bulk lookups */
    private function emailForUserId(?int $userId): ?string
    {
        if (!$userId) return null;
        return DB::table('users')->where('id', $userId)->value('email');
    }

    private function testSubscriptions(?string $cursor, int $limit): \Illuminate\Http\JsonResponse
    {
        $items = [
            [
                'id'               => 's_test_1',
                'record_id'        => 1,
                'type'             => 'search',
                'email'            => 'test@example.com',
                'userid'           => null,
                'search_name'      => 'Test 2BR Condos Vancouver',
                'criteria'         => ['cities' => 'Vancouver', 'type' => 'Apartment', 'min_beds' => 2, 'status' => 'Active'],
                'active'           => true,
                'last_update_sent' => null,
                'user_last_active_at' => null,
                'created_at'       => now()->toDateTimeString(),
            ],
            [
                'id'               => 'b_test_1',
                'record_id'        => 1,
                'type'             => 'building',
                'email'            => 'test@example.com',
                'userid'           => null,
                'building_slug'    => 'test-building-vancouver',
                'building_name'    => 'Test Building',
                'city'             => 'Vancouver',
                'strata_no'        => 'EPS1234',
                'active'           => true,
                'last_update_sent' => null,
                'user_last_active_at' => null,
                'created_at'       => now()->toDateTimeString(),
            ],
        ];

        return response()->json([
            'success'     => true,
            'test_mode'   => true,
            'data'        => $items,
            'next_cursor' => null,
        ]);
    }
}
