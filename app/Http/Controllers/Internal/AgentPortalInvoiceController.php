<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Http\JsonResponse;

class AgentPortalInvoiceController extends Controller
{
    public function __construct(private readonly InvoicePdf $pdf) {}

    /**
     * Invoices this agent is entitled to see.
     *
     * Scoped to invoices where they are either the SITE or the PAYER. On a shared site
     * one invoice is issued to the primary payer, and the other agent must not be able
     * to pull the payer's billing documents — so this is an OR over two explicit
     * columns, never a lookup by site alone.
     *
     * Drafts are excluded: a draft is our working copy and may still change. An agent
     * should only ever see a document that was actually issued to them.
     */
    public function index(int $agentId): JsonResponse
    {
        $invoices = Invoice::where('status', '!=', 'draft')
            ->where(function ($q) use ($agentId) {
                $q->where('agent_id', $agentId)->orWhere('bill_to_agent_id', $agentId);
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        return response()->json($invoices->map(fn (Invoice $i) => [
            'id'             => $i->id,
            'invoice_number' => $i->invoice_number,
            'status'         => $i->status,
            'issue_date'     => $i->issue_date?->toDateString(),
            'due_date'       => $i->due_date?->toDateString(),
            'period_start'   => $i->period_start?->toDateString(),
            'period_end'     => $i->period_end?->toDateString(),
            'currency'       => $i->currency,
            'subtotal'       => round($i->subtotal_cents / 100, 2),
            'tax'            => round($i->tax_cents / 100, 2),
            'tax_label'      => $i->tax_label,
            'total'          => round($i->total_cents / 100, 2),
            'balance'        => round($i->balanceCents() / 100, 2),
            'paid_at'        => $i->paid_at?->toDateTimeString(),
            'pdf_url'        => "/api-internal/agent-portal/{$agentId}/invoices/{$i->id}/pdf",
        ]));
    }

    /** Stream one invoice as a PDF, with the same entitlement check as the list. */
    public function pdf(int $agentId, int $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('status', '!=', 'draft')
            ->where(function ($q) use ($agentId) {
                $q->where('agent_id', $agentId)->orWhere('bill_to_agent_id', $agentId);
            })
            ->first();

        if (! $invoice) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response($this->pdf->render($invoice), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->pdf->filename($invoice) . '"',
        ]);
    }
}
