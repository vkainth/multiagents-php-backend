<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Pulls PAID Stripe invoices into our ledger.
 *
 * Money that was collected before this system existed is still income: without it the
 * tax report understates revenue and GST, and the agent's portal shows nothing for a
 * period they demonstrably paid for.
 *
 * Two deliberate choices:
 *
 *  - The Stripe invoice NUMBER is kept as our invoice_number. The customer holds a PDF
 *    numbered 9F0B253-0001; if our ledger called it PXWEB-2026-0005 the two records
 *    would disagree about the same payment. It also keeps the PXWEB sequence meaning
 *    exactly "issued by this system", with no borrowed entries.
 *
 *  - Idempotent on stripe_invoice_id, so re-running cannot double-count revenue.
 */
class BillingBackfillStripe extends Command
{
    protected $signature = 'billing:backfill-stripe
        {--dry-run : Show what would be imported without writing anything}
        {--period= : Service period for a single-line setup/hosting invoice, YYYY-MM}
        {--invoice=* : Import ONLY these Stripe invoice numbers (repeatable)}
        {--agent= : Agent slug the --invoice values belong to}
        {--customer=* : Extra Stripe customer ids to search (for records not linked by email)}';

    protected $description = 'Import paid Stripe invoices into the invoice ledger';

    public function handle(InvoiceService $invoices): int
    {
        $dry = (bool) $this->option('dry-run');
        $key = env('STRIPE_SECRET_KEY');

        if (! $key) {
            $this->error('STRIPE_SECRET_KEY not configured.');
            return self::FAILURE;
        }

        $imported = 0;
        $skipped  = 0;

        foreach (DB::table('agent_settings')->get() as $settings) {
            $agent = DB::table('agents')->where('id', $settings->agent_id)->first();
            if (! $agent) continue;

            // Look across EVERY Stripe customer record for this agent, not just the id in
            // agent_settings. Randy and Nav each have two: an old one going back to 2016
            // that their setup invoice was actually paid on, and a newer one created by
            // the subscription flow that has never been charged. Scanning only the
            // configured id silently missed $5,250 of collected revenue — the failure
            // mode is under-reporting income, which is the wrong way to be wrong.
            // Explicit mode widens the search by email so a payment on a duplicate
            // customer record can be found. Automatic mode does NOT: this Stripe
            // account also bills photography, feature sheets and other Pixilink
            // products to the same people, and sweeping every invoice for an email
            // would import that unrelated revenue as website income — inflating both
            // the ledger and the GST return.
            $wanted = (array) $this->option('invoice');
            $agentFilter = $this->option('agent');

            if ($agentFilter && $agent->slug !== $agentFilter) { continue; }

            $customerIds = $wanted
                ? array_unique(array_merge(
                    $this->customerIdsFor($key, $settings, $agent),
                    // Nav's setup invoice sits on a customer under nav@suburbia.ca
                    // while our record holds hello@suburbia.ca, so email lookup alone
                    // cannot reach it. Naming the customer explicitly is safer than
                    // widening the email search and hoovering up other product lines.
                    (array) $this->option('customer'),
                  ))
                : array_filter([$settings->stripe_customer_id ?? null]);

            if (! $customerIds) continue;

            $stripeInvoices = [];
            foreach ($customerIds as $cid) {
                $res = Http::withToken($key)->timeout(20)->get('https://api.stripe.com/v1/invoices', [
                    'customer' => $cid,
                    'status'   => 'paid',
                    'limit'    => 100,
                ]);
                foreach (($res->json()['data'] ?? []) as $si) {
                    $stripeInvoices[$si['id']] = $si;
                }
            }

            foreach ($stripeInvoices as $si) {
                $number = $si['number'] ?? null;
                $total  = (int) ($si['total'] ?? 0);

                if ($wanted && ! in_array($number, $wanted, true) && ! in_array($si['id'], $wanted, true)) {
                    continue;
                }

                // Zero-amount invoices are subscription prorations and credit notes, not
                // revenue. Importing them would put £0 rows in the tax report.
                if ($total === 0 || ! $number) {
                    $skipped++;
                    continue;
                }

                if (Invoice::where('stripe_invoice_id', $si['id'])->orWhere('invoice_number', $number)->exists()) {
                    $this->line("  {$agent->slug}: {$number} already in ledger");
                    $skipped++;
                    continue;
                }

                $paidAt = isset($si['status_transitions']['paid_at'])
                    ? Carbon::createFromTimestamp($si['status_transitions']['paid_at'])
                    : Carbon::createFromTimestamp($si['created']);

                $period = $this->option('period')
                    ? Carbon::createFromFormat('Y-m', $this->option('period'))->startOfMonth()
                    : null;

                $this->line(sprintf(
                    '  %s: %s  %s  total %s  paid %s%s',
                    $agent->slug, $number, date('Y-m-d', $si['created']),
                    number_format($total / 100, 2), $paidAt->toDateString(),
                    $dry ? '   [DRY RUN]' : ''
                ));

                if ($dry) { continue; }

                DB::transaction(function () use ($si, $number, $agent, $settings, $paidAt, $period, $invoices, &$imported) {
                    $subtotal = (int) ($si['subtotal'] ?? 0);
                    $tax      = (int) ($si['tax'] ?? 0);
                    $total    = (int) ($si['total'] ?? 0);

                    // Rate derived from the amounts actually charged, not from current
                    // config: a historical invoice must record the rate it was issued
                    // under, even if the rate changes later.
                    $rate = $subtotal > 0 ? round($tax / $subtotal * 100, 2) : 0;

                    $invoice = Invoice::create([
                        'invoice_number'    => $number,
                        'agent_id'          => $agent->id,
                        'bill_to_agent_id'  => $settings->billing_primary_agent_id ?: $agent->id,
                        'status'            => 'paid',
                        'currency'          => strtoupper($si['currency'] ?? 'cad'),
                        'period_start'      => $period?->toDateString(),
                        'period_end'        => $period?->copy()->endOfMonth()->toDateString(),
                        'issue_date'        => date('Y-m-d', $si['created']),
                        'due_date'          => date('Y-m-d', $si['created']),
                        'subtotal_cents'    => $subtotal,
                        'tax_cents'         => $tax,
                        'total_cents'       => $total,
                        'amount_paid_cents' => (int) ($si['amount_paid'] ?? $total),
                        'tax_rate_percent'  => $rate,
                        'tax_label'         => config('invoicing.tax_label'),
                        'gst_number'        => config('invoicing.gst_number') ?: null,
                        'company_name'      => config('invoicing.company_name'),
                        'company_address'   => config('invoicing.company_address') ?: null,
                        'bill_to_name'      => $settings->billing_contact_name ?: $agent->name,
                        'bill_to_email'     => $settings->billing_contact_email ?: $agent->email,
                        'stripe_invoice_id' => $si['id'],
                        'paid_at'           => $paidAt,
                        // Marked as sent: Stripe delivered this one, and a record showing
                        // "never sent" for an invoice the customer paid would be wrong.
                        'sent_at'           => $paidAt,
                        'payment_method'    => 'card',
                        'notes'             => 'Imported from Stripe invoice ' . $number,
                    ]);

                    $sort = 0;
                    foreach (($si['lines']['data'] ?? []) as $line) {
                        $invoices->addLine($invoice, [
                            'description'       => $line['description'] ?? 'Services',
                            'quantity'          => $line['quantity'] ?? 1,
                            'unit_amount_cents' => (int) ($line['amount'] ?? 0),
                            'amount_cents'      => (int) ($line['amount'] ?? 0),
                            'taxable'           => $tax > 0,
                            'kind'              => 'subscription',
                            'sort_order'        => $sort++,
                        ]);
                    }

                    $imported++;
                });
            }
        }

        $this->newLine();
        $this->info("Imported {$imported}, skipped {$skipped}.");

        return self::SUCCESS;
    }
    /**
     * Every Stripe customer id plausibly belonging to this agent.
     *
     * The configured id, plus any customer matching the agent email or the billing
     * contact email. Duplicate customer records are normal in an account this old —
     * one created by hand years ago, another by a later automated flow — and the
     * payment can sit on either.
     */
    private function customerIdsFor(string $key, object $settings, object $agent): array
    {
        $ids = [];

        if (! empty($settings->stripe_customer_id)) {
            $ids[] = $settings->stripe_customer_id;
        }

        $emails = array_filter([
            $agent->email ?? null,
            $settings->billing_contact_email ?? null,
            $settings->notification_email ?? null,
        ]);

        foreach (array_unique($emails) as $email) {
            $res = Http::withToken($key)->timeout(20)
                ->get("https://api.stripe.com/v1/customers", ["email" => $email, "limit" => 10]);
            foreach (($res->json()["data"] ?? []) as $c) {
                $ids[] = $c["id"];
            }
        }

        return array_values(array_unique($ids));
    }
}
