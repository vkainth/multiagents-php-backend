<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BillingAddon;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use App\Services\InvoiceService;
use App\Services\StripeBilling;
use App\Services\TaxReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoicePdf $pdf,
        private readonly StripeBilling $stripe,
        private readonly TaxReport $tax,
    ) {}

    public function index(Request $req)
    {
        $query = Invoice::with(['agent', 'billTo'])->orderByDesc('issue_date')->orderByDesc('id');

        if ($status = $req->query('status')) {
            $query->where('status', $status);
        }
        if ($agentId = $req->query('agent')) {
            $query->where('agent_id', $agentId);
        }

        return view('admin.invoices.index', [
            'invoices'   => $query->paginate(50)->withQueryString(),
            'agents'     => Agent::orderBy('name')->get(['id', 'name', 'slug']),
            'status'     => $status,
            'agentId'    => $agentId,
            'receivables'=> $this->tax->receivables(),
            'gstReady'   => (bool) config('invoicing.gst_number'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['lines', 'agent.settings', 'billTo']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'cards'   => $invoice->billTo ? $this->stripe->listCards($invoice->billTo) : [],
        ]);
    }

    /** Stream the PDF. Inline so it previews in the browser rather than forcing a save. */
    public function pdf(Invoice $invoice)
    {
        return response($this->pdf->render($invoice), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->pdf->filename($invoice) . '"',
        ]);
    }

    public function finalise(Invoice $invoice)
    {
        try {
            $this->invoices->finalise($invoice);
            return back()->with('success', $invoice->invoice_number . ' issued.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function void(Request $req, Invoice $invoice)
    {
        $req->validate(['reason' => 'required|string|max:500']);

        try {
            $this->invoices->void($invoice, $req->input('reason'));
            return back()->with('success', $invoice->invoice_number . ' voided.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record a payment that did not come through Stripe — e-transfer, cheque, or a card
     * taken over the phone. Without this the tax report misses income that was genuinely
     * received, which is the whole point of the report.
     */
    public function markPaid(Request $req, Invoice $invoice)
    {
        $data = $req->validate([
            'payment_method' => 'required|in:card,etransfer,cheque,other',
            'paid_at'        => 'nullable|date',
        ]);

        $this->invoices->markPaid($invoice, [
            'payment_method' => $data['payment_method'],
            'paid_at'        => $data['paid_at'] ? Carbon::parse($data['paid_at']) : now(),
        ]);

        return back()->with('success', $invoice->invoice_number . ' marked paid.');
    }

    /** Charge the card on file for an already-issued invoice. */
    public function charge(Invoice $invoice)
    {
        if ($invoice->status !== 'open') {
            return back()->with('error', 'Only an open invoice can be charged.');
        }

        try {
            $intent = $this->stripe->chargeInvoice($invoice);

            if (($intent['status'] ?? null) === 'succeeded') {
                $this->invoices->markPaid($invoice, [
                    'payment_intent' => $intent['id'] ?? null,
                    'charge'         => $intent['latest_charge'] ?? null,
                    'payment_method' => 'card',
                ]);
                return back()->with('success', 'Charged ' . Invoice::formatMoney($invoice->total_cents) . '.');
            }

            return back()->with('error', 'Charge not completed: ' . ($intent['status'] ?? 'unknown'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Add-ons ─────────────────────────────────────────────────────────────

    public function addons(Agent $agent)
    {
        return view('admin.invoices.addons', [
            'agent'  => $agent->load('settings'),
            'addons' => BillingAddon::where('agent_id', $agent->id)->orderByDesc('id')->get(),
        ]);
    }

    public function storeAddon(Request $req, Agent $agent)
    {
        $data = $req->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
            'kind'        => 'required|in:one_time,recurring',
            'taxable'     => 'nullable|boolean',
            'starts_on'   => 'nullable|date',
            'ends_on'     => 'nullable|date|after_or_equal:starts_on',
        ]);

        BillingAddon::create([
            'agent_id'    => $agent->id,
            'description' => $data['description'],
            // Round to the cent explicitly. (int)($x * 100) truncates: 45.15 * 100 is
            // 4514.9999… in binary floating point and would silently bill a cent less.
            'amount_cents'=> (int) round($data['amount'] * 100),
            'kind'        => $data['kind'],
            'taxable'     => (bool) ($data['taxable'] ?? true),
            'starts_on'   => $data['starts_on'] ?? null,
            'ends_on'     => $data['ends_on'] ?? null,
            'active'      => true,
        ]);

        return back()->with('success', 'Add-on saved. It will appear on the next invoice.');
    }

    public function destroyAddon(BillingAddon $addon)
    {
        // Deactivate rather than delete: a billed add-on is referenced by an invoice line
        // and its history has to stay resolvable.
        $addon->active = false;
        $addon->save();

        return back()->with('success', 'Add-on deactivated.');
    }

    // ── Card capture ────────────────────────────────────────────────────────

    /**
     * Generate a Stripe-hosted link the agent uses to add their card. Card details go
     * straight to Stripe; this server never sees them.
     */
    public function cardLink(Agent $agent)
    {
        try {
            $url = $this->stripe->cardCaptureUrl($agent, route('admin.invoices.index'));
            return back()->with('card_link', $url)->with('card_link_agent', $agent->name);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Tax report ──────────────────────────────────────────────────────────

    public function taxReport(Request $req)
    {
        [$from, $to] = $this->range($req);

        return view('admin.invoices.tax', [
            'from'        => $from,
            'to'          => $to,
            'summary'     => $this->tax->summary($from, $to),
            'receivables' => $this->tax->receivables(),
        ]);
    }

    public function taxExport(Request $req)
    {
        [$from, $to] = $this->range($req);

        $csv = $this->tax->exportCsv($from, $to);
        $name = 'income-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    private function range(Request $req): array
    {
        $from = $req->query('from')
            ? Carbon::parse($req->query('from'))
            : Carbon::now()->startOfYear();

        $to = $req->query('to')
            ? Carbon::parse($req->query('to'))
            : Carbon::now()->endOfDay();

        return [$from, $to];
    }
}
