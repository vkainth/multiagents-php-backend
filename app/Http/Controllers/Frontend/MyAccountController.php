<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedSearches;
use App\Models\BuildingFollow;
use App\Models\FavoriteListings;
use App\Models\AlertHistory;

class MyAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login?redirect=' . urlencode(request()->fullUrl()));
        }

        $tab = $request->get('tab', 'alerts');

        $savedSearches   = SavedSearches::where('userid', $user->id)->orderBy('id', 'desc')->get();
        $buildingFollows = BuildingFollow::where('userid', $user->id)->orderBy('id', 'desc')->get();
        $alertHistory    = AlertHistory::where('userid', $user->id)->orderBy('id', 'desc')->paginate(25);

        $favorites = FavoriteListings::where('userid', $user->id)
            ->with(['listing', 'listing.photos' => function ($q) {
                $q->limit(1);
            }])
            ->orderBy('id', 'desc')
            ->get();

        return view('frontend.user.my_account', compact(
            'user', 'tab', 'savedSearches', 'buildingFollows', 'favorites', 'alertHistory'
        ));
    }

    public function alertHistory(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not Authorized'], 401);
        }

        $history = AlertHistory::where('userid', $user->id)->orderBy('id', 'desc')->paginate(25);

        return response()->json(['success' => true, 'data' => $history]);
    }

    public function reactivateAlert(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not Authorized'], 401);
        }

        $type = $request->input('type');
        $id   = $request->input('id');

        if ($type === 'search') {
            $record = SavedSearches::where('id', $id)->where('userid', $user->id)->first();
        } else {
            $record = BuildingFollow::where('id', $id)->where('userid', $user->id)->first();
        }

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $record->active           = 1;
        $record->last_update_sent = null;
        $record->save();

        \App\Services\AlertWebhookService::dispatch('subscription.reactivated', $type, $record->toArray());

        return response()->json(['success' => true]);
    }

    public function linkGuestAlerts(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->email) {
            return response()->json(['success' => false, 'message' => 'Not Authorized'], 401);
        }

        $email = $user->email;

        $searches = SavedSearches::where('email', $email)
            ->whereNull('userid')
            ->where('confirmed', 1)
            ->get();
        foreach ($searches as $s) {
            $s->userid = $user->id;
            $s->save();
        }

        $follows = BuildingFollow::where('email', $email)
            ->whereNull('userid')
            ->where('confirmed', 1)
            ->get();
        foreach ($follows as $f) {
            $f->userid = $user->id;
            $f->save();
        }

        return response()->json(['success' => true, 'linked_searches' => $searches->count(), 'linked_follows' => $follows->count()]);
    }
}
