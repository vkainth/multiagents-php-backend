<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Http\JsonResponse;

class AgentPortalInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoicePdf $pdf,
        private readonly \App\Services\StripeBilling $stripe,
    ) {}

    /**
     * Billing summary for the portal.
     *
     * The portal has always called GET {id}/billing, but no such route existed — so it
     * silently received null and rendered an empty panel. It is answered from OUR ledger
     * rather than from a Stripe subscription: the subscriptions are retired, and both
     * hub sites' are dead (incomplete_expired / canceled) while the DB still called them
     * active. Outstanding balance and next invoice come from the invoices table, which
     * is the thing that is actually true.
     */
    public function billing(int $agentId): JsonResponse
    {
        $agent = \App\Models\Agent::with('settings')->find($agentId);
        if (! $agent) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $cards = $this->stripe->listCards($agent);
        $card  = $cards[0] ?? null;

        $outstanding = Invoice::whereIn('status', ['open', 'uncollectible'])
            ->where(function ($q) use ($agentId) {
                $q->where('agent_id', $agentId)->orWhere('bill_to_agent_id', $agentId);
            })
            ->get()
            ->sum(fn (Invoice $i) => $i->balanceCents());

        $settings = $agent->settings;

        return response()->json([
            'billing_status'       => $settings?->billing_status ?? 'none',
            'monthly_amount'       => $settings?->billing_monthly_cents
                ? round($settings->billing_monthly_cents / 100, 2) : null,
            'currency'             => 'CAD',
            'tax_label'            => config('invoicing.tax_label'),
            'tax_rate_percent'     => (float) config('invoicing.tax_rate_percent'),
            'outstanding'          => round($outstanding / 100, 2),
            'has_stripe_customer'  => (bool) $settings?->stripe_customer_id,
            'has_payment_method'   => $card !== null,
            'payment_method_brand' => $card['brand'] ?? null,
            'payment_method_last4' => $card['last4'] ?? null,
            'payment_method_exp'   => $card ? sprintf('%02d/%d', $card['exp_month'], $card['exp_year']) : null,
            // Restriction is surfaced so the portal can explain WHY things are limited
            // rather than appearing broken.
            'restricted'           => (bool) $settings?->billing_restricted_at,
        ]);
    }

    /**
     * Mint a fresh Stripe Checkout setup session so the agent can add or replace their
     * card from the portal.
     *
     * Created on demand rather than stored: these sessions expire after 24 hours, so a
     * cached URL would be a dead link for most of its life.
     */
    public function cardSession(int $agentId, \Illuminate\Http\Request $request): JsonResponse
    {
        $agent = \App\Models\Agent::with('settings')->find($agentId);
        if (! $agent) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $return = $request->input('return_url') ?: config('app.url');

        try {
            return response()->json(['url' => $this->stripe->cardCaptureUrl($agent, $return)]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Could not start card setup'], 502);
        }
    }

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
