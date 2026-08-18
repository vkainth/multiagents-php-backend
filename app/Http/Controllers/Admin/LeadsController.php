<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentLead;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        $agents   = Agent::orderBy('name')->get(['id', 'name']);
        $agentId  = $request->input('agent_id');
        $formType = $request->input('form_type');
        $from     = $request->input('from', now()->startOfMonth()->toDateString());
        $to       = $request->input('to', now()->toDateString());

        $query = AgentLead::with('agent:id,name')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('created_at');

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        if ($formType) {
            $query->where('form_type', $formType);
        }

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        $leads = $query->paginate(50)->withQueryString();

        $totalsByAgent = AgentLead::selectRaw('agent_id, count(*) as total')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->get()
            ->sortByDesc('total');

        return view('admin.leads.index', compact(
            'leads', 'agents', 'totalsByAgent',
            'agentId', 'formType', 'from', 'to'
        ));
    }

    private function exportCsv($leads)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Agent', 'Name', 'Email', 'Phone', 'Type', 'Source', 'Date', 'Contacted']);
            foreach ($leads as $lead) {
                fputcsv($fh, [
                    $lead->agent->name ?? '—',
                    trim($lead->first_name . ' ' . $lead->last_name),
                    $lead->email ?? '',
                    $lead->phone ?? '',
                    $lead->form_type,
                    $lead->source_url ?? '',
                    $lead->created_at->format('Y-m-d H:i'),
                    $lead->contacted_at ? $lead->contacted_at->format('Y-m-d') : '',
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
