<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentLead;
use App\Models\AgentPageView;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $sortBy  = $request->input('sort', 'views');
        $allowed = ['views', 'leads', 'name'];
        if (!in_array($sortBy, $allowed)) {
            $sortBy = 'views';
        }

        $from = now()->subDays(29)->toDateString();
        $to   = now()->toDateString();

        $viewTotals = AgentPageView::selectRaw('agent_id, sum(`count`) as total_views')
            ->whereBetween('date', [$from, $to])
            ->groupBy('agent_id')
            ->pluck('total_views', 'agent_id');

        $leadTotals = AgentLead::selectRaw('agent_id, count(*) as total_leads')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('agent_id')
            ->pluck('total_leads', 'agent_id');

        $agents = Agent::orderBy('name')->get(['id', 'name', 'brokerage', 'status'])->map(function ($agent) use ($viewTotals, $leadTotals) {
            $agent->total_views = $viewTotals[$agent->id] ?? 0;
            $agent->total_leads = $leadTotals[$agent->id] ?? 0;
            return $agent;
        });

        if ($sortBy === 'views') {
            $agents = $agents->sortByDesc('total_views');
        } elseif ($sortBy === 'leads') {
            $agents = $agents->sortByDesc('total_leads');
        } else {
            $agents = $agents->sortBy('name');
        }

        return view('admin.analytics.index', compact('agents', 'sortBy', 'from', 'to'));
    }
}
