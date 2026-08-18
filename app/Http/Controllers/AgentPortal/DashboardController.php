<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\AgentLead;
use App\Models\AgentPageView;
use App\Models\Listings;
use App\Models\OpenHouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $agent    = Auth::guard('agent')->user();
        $settings = $agent->settings;

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $leadsThisMonth = AgentLead::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $pageViewsThisMonth = AgentPageView::where('agent_id', $agent->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('count');

        $activeListingsCount = 0;
        $openHouseCount      = 0;

        try {
            $mlsIds = $agent->mls_ids->pluck('mls_id')->toArray();
            if (!empty($mlsIds)) {
                $activeListingsCount = Listings::whereIn('la_code', $mlsIds)
                    ->where('status', 'Active')
                    ->count();

                $openHouseCount = OpenHouse::whereHas('listing', function ($q) use ($mlsIds) {
                    $q->whereIn('la_code', $mlsIds);
                })->where('oh_start_dt', '>=', now())->count();
            }
        } catch (\Throwable $e) {
            // graceful degradation if MLS DB unavailable
        }

        $recentLeads = AgentLead::where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('agent-portal.dashboard', compact(
            'agent', 'settings',
            'leadsThisMonth', 'pageViewsThisMonth',
            'activeListingsCount', 'openHouseCount',
            'recentLeads'
        ));
    }
}
