<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BillingAddon;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use App\Services\InvoiceService;
use App\Services\StripeBilling;
use App\Services\TaxReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Invoicing for the NEXT.JS admin at {agent-domain}/admin.
 *
 * There are two admin surfaces on this platform: the Laravel/Blade one at
 * website.pixilink.com/admin, and the Next.js one served on each agent domain. The
 * invoicing screens were first built only in the Blade admin — which is not the one
 * actually used day to day, so from the operator's point of view the feature did not
 * exist. This exposes the same operations as JSON so the Next admin can present them.
 *
 * Guarded by VerifyAdminSecret on the route group, the same as every other admin
 * endpoint here.
 */
class AdminInvoiceApiController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoicePdf $pdf,
        private readonly StripeBilling $stripe,
        private readonly TaxReport $tax,
    ) {}

    public function index(Request $req): JsonResponse
    {
        $q = Invoice::with(['agent', 'billTo'])->orderByDesc('issue_date')->orderByDesc('id');

        if ($s = $req->query('status')) $q->where('status', $s);
        if ($a = $req->query('agent_id')) $q->where('agent_id', $a);

        $rows = $q->limit(200)->get();

        return response()->json([
            'invoices'    => $rows->map(fn (Invoice $i) => $this->shape($i)),
            'outstanding' => round($this->tax->receivables()['total_cents'] / 100, 2),
            'gst_ready'   => (bool) config('invoicing.gst_number'),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $inv = Invoice::with(['lines', 'agent.settings', 'billTo.settings'])->find($id);
        if (! $inv) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'invoice' => $this->shape($inv) + [
                'lines' => $inv->lines->map(fn ($l) => [
                    'id'          => $l->id,
                    'description' => $l->description,
                    'kind'        => $l->kind,
                    'quantity'    => $l->quantity,
                    'unit_amount' => round($l->unit_amount_cents / 100, 2),
                    'amount'      => round($l->amount_cents / 100, 2),
                    'taxable'     => (bool) $l->taxable,
                ]),
                'company_name'  => $inv->company_name,
                'gst_number'    => $inv->gst_number,
                'void_reason'   => $inv->void_reason,
                'notes'         => $inv->notes,
            ],
            'cards' => $inv->billTo ? $this->stripe->listCards($inv->billTo) : [],
        ]);
    }

    private function shape(Invoice $i): array
    {
        return [
            'id'             => $i->id,
            'invoice_number' => $i->invoice_number,
            'status'         => $i->status,
            'agent_id'       => $i->agent_id,
            'agent_slug'     => $i->agent?->slug,
            'bill_to_name'   => $i->bill_to_name,
            'bill_to_email'  => $i->bill_to_email,
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
            'sent_at'        => $i->sent_at?->toDateTimeString(),
            'paid_at'        => $i->paid_at?->toDateTimeString(),
            'payment_method' => $i->payment_method,
        ];
    }

    public function pdf(int $id)
    {
        $inv = Invoice::with('lines')->find($id);
        if (! $inv) return response()->json(['error' => 'Not found'], 404);

        $payer   = $inv->billTo;
        $hasCard = $payer ? $this->stripe->hasCard($payer) : false;
        $payUrl  = (! $hasCard && $payer) ? $this->stripe->cardLinkFor($payer) : null;

        return response($this->pdf->render($inv, $payUrl, $hasCard), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->pdf->filename($inv) . '"',
        ]);
    }

    public function action(Request $req, int $id): JsonResponse
    {
        $inv = Invoice::with(['lines', 'billTo.settings', 'agent.settings'])->find($id);
        if (! $inv) return response()->json(['error' => 'Not found'], 404);

        try {
            switch ($req->input('action')) {
                case 'finalise':
                    $this->invoices->finalise($inv);
                    break;

                case 'void':
                    $this->invoices->void($inv, (string) $req->input('reason', 'Voided from admin'));
                    break;

                case 'mark_paid':
                    $this->invoices->markPaid($inv, [
                        'payment_method' => $req->input('payment_method', 'other'),
                        'paid_at'        => $req->input('paid_at') ? Carbon::parse($req->input('paid_at')) : now(),
                    ]);
                    break;

                case 'charge':
                    if ($inv->status !== 'open') {
                        return response()->json(['error' => 'Only an open invoice can be charged'], 422);
                    }
                    $intent = $this->stripe->chargeInvoice($inv);
                    if (($intent['status'] ?? null) !== 'succeeded') {
                        return response()->json(['error' => 'Charge not completed: ' . ($intent['status'] ?? 'unknown')], 422);
                    }
                    $this->invoices->markPaid($inv, [
                        'payment_intent' => $intent['id'] ?? null,
                        'charge'         => $intent['latest_charge'] ?? null,
                        'payment_method' => 'card',
                    ]);
                    break;

                case 'send':
                    return $this->sendInvoice($inv);

                default:
                    return response()->json(['error' => 'Unknown action'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['invoice' => $this->shape($inv->refresh())]);
    }

    /** Email the invoice PDF to the bill-to address, with a card link if none is on file. */
    private function sendInvoice(Invoice $inv): JsonResponse
    {
        if (! $inv->bill_to_email) {
            return response()->json(['error' => 'No billing email on this invoice'], 422);
        }
        if ($inv->status === 'draft') {
            return response()->json(['error' => 'Issue the invoice before sending it'], 422);
        }

        $payer   = $inv->billTo;
        $hasCard = $payer ? $this->stripe->hasCard($payer) : false;
        $payUrl  = (! $hasCard && $payer) ? $this->stripe->cardLinkFor($payer) : null;

        $company = $inv->company_name ?: config('invoicing.company_name');
        $body = "Your invoice {$inv->invoice_number} is attached.\n\n"
            . 'Amount due: ' . Invoice::formatMoney($inv->balanceCents()) . " (includes {$inv->tax_label})\n"
            . ($inv->period_start ? 'Period: ' . $inv->period_start->format('M j') . ' – ' . $inv->period_end->format('M j, Y') . "\n" : '')
            . ($payUrl
                ? "\nThere is no card on file for this account. Add one here and the invoice will be\n"
                  . "charged automatically — the page is hosted by Stripe:\n\n{$payUrl}\n\nThis link is valid for 45 days.\n"
                : "\nThis will be charged automatically to the card on file.\n")
            . "\n{$company}\n";

        try {
            $bytes = $this->pdf->render($inv, $payUrl, $hasCard);

            \Illuminate\Support\Facades\Mail::raw($body, function ($m) use ($inv, $bytes) {
                $m->to($inv->bill_to_email)
                  ->from(config('mail.lead_from.address'), config('invoicing.company_name'))
                  ->subject('Invoice ' . $inv->invoice_number . ' — ' . config('invoicing.company_name'))
                  ->attachData($bytes, $inv->invoice_number . '.pdf', ['mime' => 'application/pdf']);
            });

            $inv->sent_at = now();
            $inv->save();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Send failed: ' . $e->getMessage()], 502);
        }

        return response()->json(['invoice' => $this->shape($inv->refresh()), 'sent_to' => $inv->bill_to_email]);
    }

    // ── Add-ons ─────────────────────────────────────────────────────────────

    public function addons(int $agentId): JsonResponse
    {
        return response()->json(BillingAddon::where('agent_id', $agentId)->orderByDesc('id')->get()->map(fn ($a) => [
            'id'          => $a->id,
            'description' => $a->description,
            'amount'      => round($a->amount_cents / 100, 2),
            'kind'        => $a->kind,
            'taxable'     => (bool) $a->taxable,
            'active'      => (bool) $a->active,
            'billed_at'   => $a->billed_at?->toDateString(),
            'last_billed_period' => $a->last_billed_period?->toDateString(),
        ]));
    }

    public function addonCreate(Request $req, int $agentId): JsonResponse
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
            'agent_id'     => $agentId,
            'description'  => $data['description'],
            // round, not (int) cast: 45.15 * 100 is 4514.9999… in binary floating point
            // and truncation would quietly bill a cent short.
            'amount_cents' => (int) round($data['amount'] * 100),
            'kind'         => $data['kind'],
            'taxable'      => (bool) ($data['taxable'] ?? true),
            'starts_on'    => $data['starts_on'] ?? null,
            'ends_on'      => $data['ends_on'] ?? null,
            'active'       => true,
        ]);

        return response()->json(['ok' => true]);
    }

    public function addonDelete(int $addonId): JsonResponse
    {
        // Deactivated, never deleted: a billed add-on is referenced by an invoice line
        // and that history has to stay resolvable.
        BillingAddon::where('id', $addonId)->update(['active' => false]);

        return response()->json(['ok' => true]);
    }

    // ── Card link + tax ─────────────────────────────────────────────────────

    public function cardLink(int $agentId): JsonResponse
    {
        $agent = Agent::with('settings')->find($agentId);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);

        try {
            return response()->json([
                'url'      => $this->stripe->cardLinkFor($agent),
                'has_card' => $this->stripe->hasCard($agent),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function taxReport(Request $req): JsonResponse
    {
        $from = $req->query('from') ? Carbon::parse($req->query('from')) : Carbon::now()->startOfYear();
        $to   = $req->query('to')   ? Carbon::parse($req->query('to'))   : Carbon::now()->endOfDay();

        $summary = $this->tax->summary($from, $to);

        return response()->json([
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'months' => $summary['months'],
            'totals' => [
                'invoice_count' => $summary['totals']['invoice_count'],
                'subtotal'      => round($summary['totals']['subtotal_cents'] / 100, 2),
                'tax'           => round($summary['totals']['tax_cents'] / 100, 2),
                'total'         => round($summary['totals']['total_cents'] / 100, 2),
            ],
            'receivables' => [
                'total' => round($this->tax->receivables()['total_cents'] / 100, 2),
                'rows'  => $this->tax->receivables()['rows'],
            ],
            'tax_label' => config('invoicing.tax_label'),
        ]);
    }

    public function taxExport(Request $req)
    {
        $from = $req->query('from') ? Carbon::parse($req->query('from')) : Carbon::now()->startOfYear();
        $to   = $req->query('to')   ? Carbon::parse($req->query('to'))   : Carbon::now()->endOfDay();

        return response($this->tax->exportCsv($from, $to), 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="income-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.csv"',
        ]);
    }
}
