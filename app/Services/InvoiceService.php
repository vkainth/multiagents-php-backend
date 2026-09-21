<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BillingAddon;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds and finalises first-party invoices.
 *
 * Invariants this class exists to hold:
 *   - money is integer cents end to end; nothing is ever a float
 *   - tax is computed once over the taxable subtotal, not per line, so the printed
 *     lines always add up to the printed total
 *   - invoice numbers are sequential with no duplicates, under concurrency
 *   - an invoice cannot be finalised without the issuer's GST number on it
 *   - building the same period twice does not produce two invoices
 */
class InvoiceService
{
    /**
     * Next sequential invoice number, e.g. PXL-2026-0001.
     *
     * Wrapped in a transaction with a row lock on the year's highest number. The unique
     * index on invoice_number is the real backstop — under a race the loser's INSERT
     * fails rather than silently issuing a duplicate number, which is the one outcome
     * an audit cannot tolerate.
     */
    public function nextInvoiceNumber(?int $year = null): string
    {
        $year   = $year ?: (int) now()->format('Y');
        $prefix = config('invoicing.number_prefix', 'PXL') . '-' . $year . '-';
        $pad    = (int) config('invoicing.number_pad', 4);

        return DB::transaction(function () use ($prefix, $pad) {
            $last = DB::table('invoices')
                ->where('invoice_number', 'like', $prefix . '%')
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->value('invoice_number');

            $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix . str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Recompute subtotal / tax / total from the invoice's own lines.
     *
     * Tax is calculated on the summed taxable amount and rounded ONCE. Rounding per line
     * and summing would drift: three $99.99 lines taxed individually round to a different
     * total than the same $299.97 taxed once, and the invoice would not foot.
     */
    public function recalculate(Invoice $invoice): Invoice
    {
        $lines = $invoice->lines()->get();

        $subtotal      = 0;
        $taxableAmount = 0;

        foreach ($lines as $line) {
            $subtotal += (int) $line->amount_cents;
            if ($line->taxable) {
                $taxableAmount += (int) $line->amount_cents;
            }
        }

        $rate = (float) $invoice->tax_rate_percent;
        $tax  = (int) round($taxableAmount * $rate / 100);

        $invoice->subtotal_cents = $subtotal;
        $invoice->tax_cents      = $tax;
        $invoice->total_cents    = $subtotal + $tax;
        $invoice->save();

        return $invoice->refresh();
    }

    /**
     * Create a DRAFT invoice for one site for one service period.
     *
     * Returns the existing invoice if this site/period already has one that is not void,
     * so re-running the monthly command is idempotent rather than duplicating a charge.
     */
    public function buildForPeriod(Agent $agent, CarbonInterface $periodStart, array $opts = []): Invoice
    {
        $settings    = $agent->settings;
        $periodStart = Carbon::instance($periodStart->toDateTime())->startOfDay();
        $periodEnd   = $periodStart->copy()->addMonthNoOverflow()->subDay();

        $existing = Invoice::where('agent_id', $agent->id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->where('status', '!=', 'void')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Who receives and pays this invoice. On a shared site both agents use the one
        // site, but a single invoice goes to the primary payer.
        $billToId = $settings?->billing_primary_agent_id ?: $agent->id;
        $billTo   = Agent::with('settings')->find($billToId) ?: $agent;

        $invoice = new Invoice([
            'invoice_number'   => $opts['invoice_number'] ?? $this->nextInvoiceNumber((int) $periodStart->format('Y')),
            'agent_id'         => $agent->id,
            'bill_to_agent_id' => $billTo->id,
            'status'           => 'draft',
            'currency'         => 'CAD',
            'period_start'     => $periodStart->toDateString(),
            'period_end'       => $periodEnd->toDateString(),
            'issue_date'       => ($opts['issue_date'] ?? $periodStart)->toDateString(),
            'tax_rate_percent' => config('invoicing.tax_rate_percent', 5.00),
            'tax_label'        => config('invoicing.tax_label', 'GST'),
            'gst_number'       => config('invoicing.gst_number') ?: null,
            'company_name'     => config('invoicing.company_name'),
            'company_address'  => config('invoicing.company_address') ?: null,
            'bill_to_name'     => $billTo->name,
            'bill_to_email'    => $billTo->settings?->notification_email ?: $billTo->email,
        ]);

        $dueDays = (int) config('invoicing.due_days', 0);
        $invoice->due_date = Carbon::parse($invoice->issue_date)->addDays($dueDays)->toDateString();
        $invoice->save();

        $sort = 0;

        // Recurring site fee.
        $monthly = (int) ($opts['monthly_cents'] ?? $settings?->billing_monthly_cents ?? 0);
        if ($monthly > 0) {
            $this->addLine($invoice, [
                'description'       => $opts['monthly_description']
                    ?? ('Website hosting & management — ' . $periodStart->format('F Y')),
                'quantity'          => 1,
                'unit_amount_cents' => $monthly,
                'amount_cents'      => $monthly,
                'taxable'           => true,
                'kind'              => 'subscription',
                'period_start'      => $periodStart->toDateString(),
                'period_end'        => $periodEnd->toDateString(),
                'sort_order'        => $sort++,
            ]);
        }

        // Add-ons configured in admin — one-time and recurring alike land here as their
        // own lines, so the agent gets one invoice and one card charge for the month.
        foreach (BillingAddon::where('agent_id', $agent->id)->where('active', true)->get() as $addon) {
            if (! $addon->appliesToPeriod($periodStart)) {
                continue;
            }

            $this->addLine($invoice, [
                'description'       => $addon->description,
                'quantity'          => 1,
                'unit_amount_cents' => (int) $addon->amount_cents,
                'amount_cents'      => (int) $addon->amount_cents,
                'taxable'           => (bool) $addon->taxable,
                'kind'              => $addon->kind === 'one_time' ? 'addon_one_time' : 'addon_recurring',
                'billing_addon_id'  => $addon->id,
                'period_start'      => $addon->kind === 'recurring' ? $periodStart->toDateString() : null,
                'period_end'        => $addon->kind === 'recurring' ? $periodEnd->toDateString() : null,
                'sort_order'        => $sort++,
            ]);

            // Stamp immediately so a crash later in the run cannot re-bill this add-on.
            if ($addon->kind === 'one_time') {
                $addon->billed_at = now();
            } else {
                $addon->last_billed_period = $periodStart->toDateString();
            }
            $addon->save();
        }

        return $this->recalculate($invoice);
    }

    public function addLine(Invoice $invoice, array $attrs): InvoiceLine
    {
        $attrs['invoice_id'] = $invoice->id;

        if (! isset($attrs['amount_cents'])) {
            $attrs['amount_cents'] = (int) ($attrs['quantity'] ?? 1) * (int) ($attrs['unit_amount_cents'] ?? 0);
        }

        return InvoiceLine::create($attrs);
    }

    /**
     * Move a draft to 'open' — the point at which it becomes a real document.
     *
     * Refuses without a GST number: an invoice that charges GST without the supplier's
     * registration number on it is not a valid tax document, and the customer cannot
     * claim the credit. Better to block issuing than to send 30 invoices that have to be
     * reissued later.
     */
    public function finalise(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            return $invoice;
        }

        if ((int) $invoice->tax_cents > 0 && blank($invoice->gst_number)) {
            throw new \RuntimeException(
                'Cannot issue invoice ' . $invoice->invoice_number . ': no GST registration number configured. '
                . 'Set INVOICE_GST_NUMBER before issuing invoices that charge GST.'
            );
        }

        $this->recalculate($invoice);

        $invoice->status = 'open';
        $invoice->save();

        return $invoice->refresh();
    }

    /**
     * Void an invoice. Never delete — the number must stay in the sequence so a gap in
     * the numbering can always be explained.
     */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        if ($invoice->isPaid()) {
            throw new \RuntimeException('Cannot void a paid invoice; issue a credit instead.');
        }

        $invoice->status      = 'void';
        $invoice->voided_at   = now();
        $invoice->void_reason = $reason;
        $invoice->save();

        return $invoice->refresh();
    }

    /** Record payment against an invoice, whether it came through Stripe or not. */
    public function markPaid(Invoice $invoice, array $opts = []): Invoice
    {
        $invoice->amount_paid_cents       = $opts['amount_cents'] ?? $invoice->total_cents;
        $invoice->status                  = 'paid';
        $invoice->paid_at                 = $opts['paid_at'] ?? now();
        $invoice->payment_method          = $opts['payment_method'] ?? 'card';
        $invoice->stripe_payment_intent_id = $opts['payment_intent'] ?? $invoice->stripe_payment_intent_id;
        $invoice->stripe_charge_id        = $opts['charge'] ?? $invoice->stripe_charge_id;
        $invoice->save();

        return $invoice->refresh();
    }
}
