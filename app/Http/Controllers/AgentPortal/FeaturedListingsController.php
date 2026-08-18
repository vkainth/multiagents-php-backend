<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\Listings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FeaturedListingsController extends Controller
{
    public function index()
    {
        $agent    = Auth::guard('agent')->user()->load('settings', 'mls_ids');
        $settings = $agent->settings;

        $featuredIds  = $settings ? ($settings->featured_listing_ids ?? []) : [];
        $featured     = collect();

        if (!empty($featuredIds)) {
            try {
                $featured = Listings::whereIn('ml_num', $featuredIds)
                    ->get()
                    ->keyBy('ml_num')
                    ->sortBy(fn($l) => array_search($l->ml_num, $featuredIds))
                    ->values();
            } catch (\Throwable $e) {
                $featured = collect();
            }
        }

        return view('agent-portal.featured-listings', compact('agent', 'settings', 'featured', 'featuredIds'));
    }

    public function search(Request $request)
    {
        $agent  = Auth::guard('agent')->user()->load('mls_ids');
        $query  = trim($request->get('q', ''));
        $mlsIds = $agent->mls_ids->pluck('mls_id')->toArray();

        if (empty($query) || empty($mlsIds)) {
            return response()->json([]);
        }

        try {
            $listings = Listings::where('status', 'Active')
                ->whereIn('la_code', $mlsIds)
                ->where(function ($q) use ($query) {
                    $q->where('ml_num', 'like', "%{$query}%")
                      ->orWhere('addr', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['ml_num', 'addr', 'municipality', 'lp_dol', 'type_own1_out', 'br', 'bath_tot']);

            return response()->json($listings);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    public function save(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|max:6',
            'ids.*' => 'required|string|max:20',
        ]);

        $agent    = Auth::guard('agent')->user()->load('mls_ids', 'settings');
        $mlsIds   = $agent->mls_ids->pluck('mls_id')->toArray();
        $settings = $agent->settings()->firstOrCreate(['agent_id' => $agent->id]);

        $ids = collect($request->input('ids'))->slice(0, 6)->values()->toArray();

        // Verify all ids belong to agent's MLS listings
        if (!empty($mlsIds)) {
            try {
                $valid = Listings::whereIn('ml_num', $ids)
                    ->whereIn('la_code', $mlsIds)
                    ->pluck('ml_num')
                    ->toArray();
                $ids = array_values(array_intersect($ids, $valid));
            } catch (\Throwable $e) {
                // skip validation if MLS DB unavailable
            }
        }

        $settings->update(['featured_listing_ids' => $ids]);

        Cache::forget("agent_featured_{$agent->id}");

        return response()->json(['success' => true, 'ids' => $ids]);
    }
}
