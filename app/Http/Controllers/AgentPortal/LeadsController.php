<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\AgentLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::guard('agent')->user();

        $query = AgentLead::where('agent_id', $agent->id)->orderByDesc('created_at');

        if ($type = $request->get('type')) {
            $query->where('form_type', $type);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $leads = $query->paginate(25)->withQueryString();

        return view('agent-portal.leads', compact('agent', 'leads'));
    }

    public function markContacted(Request $request, AgentLead $lead)
    {
        $agent = Auth::guard('agent')->user();

        if ($lead->agent_id !== $agent->id) {
            abort(403);
        }

        $lead->update(['contacted_at' => now()]);

        return response()->json([
            'success'      => true,
            'contacted_at' => $lead->contacted_at->diffForHumans(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $agent = Auth::guard('agent')->user();

        $query = AgentLead::where('agent_id', $agent->id)->orderByDesc('created_at');

        if ($type = $request->get('type')) {
            $query->where('form_type', $type);
        }
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $leads = $query->get();

        $filename = 'leads_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Phone', 'Email', 'Type', 'Page', 'Listing', 'Message', 'Contacted', 'Date']);

            foreach ($leads as $lead) {
                fputcsv($out, [
                    trim($lead->first_name . ' ' . $lead->last_name),
                    $lead->phone ?? '',
                    $lead->email ?? '',
                    $lead->formTypeLabel(),
                    $lead->source_url ?? '',
                    $lead->listing_id ?? '',
                    $lead->message ?? '',
                    $lead->contacted_at ? $lead->contacted_at->format('Y-m-d H:i') : '',
                    $lead->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
