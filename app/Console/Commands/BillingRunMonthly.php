<?php

namespace App\Console\Commands;

use App\Exceptions\StripeBillingException;
use App\Models\Agent;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use App\Services\InvoiceService;
use App\Services\StripeBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Monthly billing run: build → issue → charge → email.
 *
 * Intended cron (mirrors how articles:generate is scheduled — plain crontab, this app
 * does not use Laravel's scheduler):
 *   0 6 1 * * /opt/cpanel/ea-php83/root/usr/bin/php /home/websitemanager/bcchv2/artisan billing:run-monthly >> storage/logs/billing.log 2>&1
 *   0 6 * * * … artisan billing:run-monthly --retries-only
 *
 * Safe to re-run. Invoice creation is idempotent per site+period, and the Stripe charge
 * carries an idempotency key derived from the invoice number, so a double-fired cron
 * cannot double-bill.
 */
class BillingRunMonthly extends Command
{
    protected $signature = 'billing:run-monthly
        {--period= : Service period to bill, YYYY-MM (default: the current month)}
        {--agent= : Restrict to one agent slug}
        {--dry-run : Show what would happen without writing or charging anything}
        {--no-charge : Build and issue invoices but do not touch the card}
        {--retries-only : Skip new invoices; only retry unpaid ones}';

    protected $description = 'Build, issue, charge and email monthly invoices';

    public function handle(InvoiceService $invoices, StripeBilling $stripe, InvoicePdf $pdf): int
    {
        $dry = (bool) $this->option('dry-run');

        $period = $this->option('period')
            ? Carbon::createFromFormat('Y-m', $this->option('period'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $this->info('Billing run for ' . $period->format('F Y') . ($dry ? '  [DRY RUN — nothing will be written]' : ''));

        if (! $this->option('retries-only')) {
            $this->issueForPeriod($invoices, $stripe, $pdf, $period, $dry);
        }

        $this->retryUnpaid($stripe, $pdf, $dry);

        return self::SUCCESS;
    }

    private function billableAgents(): \Illuminate\Support\Collection
    {
        $query = Agent::with('settings')
            ->whereHas('settings', fn ($q) => $q->where('billing_monthly_cents', '>', 0));

        if ($slug = $this->option('agent')) {
            $query->where('slug', $slug);
        }

        return $query->get();
    }

    private function issueForPeriod(InvoiceService $invoices, StripeBilling $stripe, InvoicePdf $pdf, Carbon $period, bool $dry): void
    {
        foreach ($this->billableAgents() as $agent) {
            $settings = $agent->settings;

            // Never retro-bill a site for months before it went live.
            if ($settings->billing_starts_on && $period->lt(Carbon::parse($settings->billing_starts_on)->startOfMonth())) {
                $this->line("  {$agent->slug}: skipped — billing starts {$settings->billing_starts_on}");
                continue;
            }

            if ($dry) {
                $existing = Invoice::where('agent_id', $agent->id)
                    ->whereDate('period_start', $period->toDateString())
                    ->where('status', '!=', 'void')->first();

                $this->line("  {$agent->slug}: " . ($existing
                    ? "already invoiced ({$existing->invoice_number}, {$existing->status})"
                    : 'would invoice $' . number_format($settings->billing_monthly_cents / 100, 2) . ' + GST'));
                continue;
            }

            try {
                $invoice = $invoices->buildForPeriod($agent, $period);

                if ($invoice->status === 'draft') {
                    $invoice = $invoices->finalise($invoice);
                }

                if ($invoice->isPaid()) {
                    $this->line("  {$agent->slug}: {$invoice->invoice_number} already paid");
                    continue;
                }

                $this->line("  {$agent->slug}: {$invoice->invoice_number} " . Invoice::formatMoney($invoice->total_cents));

                if (! $this->option('no-charge')) {
                    $this->attemptCharge($invoice, $stripe, $pdf);
                }
            } catch (\Throwable $e) {
                // One site's failure must never abort the run for the others.
                $this->error("  {$agent->slug}: {$e->getMessage()}");
                Log::error('billing:run-monthly failed for ' . $agent->slug . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Retry open invoices on the configured retry days, then restrict the site.
     *
     * Restriction withholds leads and drops admin to read-only; it does NOT take the
     * public site down and does NOT stop lead capture. A captured lead can be released
     * the moment payment clears, whereas a prospect turned away by a dead form is gone
     * for good — the commercial pressure is the same and only one of them is reversible.
     */
    private function retryUnpaid(StripeBilling $stripe, InvoicePdf $pdf, bool $dry): void
    {
        $open = Invoice::where('status', 'open')->with(['agent.settings', 'billTo.settings'])->get();

        if ($open->isEmpty()) {
            return;
        }

        $retryDays = (array) config('invoicing.retry_days', [3, 7]);
        $grace     = (int) config('invoicing.grace_period_days', 7);

        foreach ($open as $invoice) {
            $age = (int) Carbon::parse($invoice->issue_date)->startOfDay()->diffInDays(Carbon::now()->startOfDay());

            if ($age > $grace) {
                $settings = $invoice->agent?->settings;
                if ($settings && ! $settings->billing_restricted_at) {
                    $this->warn("  {$invoice->invoice_number}: unpaid {$age}d — restricting site (leads withheld, admin read-only)");
                    if (! $dry) {
                        $settings->billing_restricted_at = now();
                        $settings->billing_status = 'past_due';
                        $settings->save();
                    }
                }
                continue;
            }

            if (! in_array($age, $retryDays, true)) {
                continue;
            }

            $this->line("  {$invoice->invoice_number}: retry (day {$age})");

            if (! $dry) {
                $this->attemptCharge($invoice, $stripe, $pdf);
            }
        }
    }

    private function attemptCharge(Invoice $invoice, StripeBilling $stripe, InvoicePdf $pdf): void
    {
        $service = app(InvoiceService::class);

        try {
            $intent = $stripe->chargeInvoice($invoice);

            if (($intent['status'] ?? null) === 'succeeded') {
                $service->markPaid($invoice, [
                    'payment_intent' => $intent['id'] ?? null,
                    'charge'         => $intent['latest_charge'] ?? null,
                    'payment_method' => 'card',
                ]);

                $this->info('    charged OK — ' . ($intent['id'] ?? ''));

                // Clear any restriction now that the account is current.
                $settings = $invoice->agent?->settings;
                if ($settings && $settings->billing_restricted_at) {
                    $settings->billing_restricted_at = null;
                    $settings->billing_status = 'active';
                    $settings->save();
                    $this->info('    restriction lifted');
                }

                $this->emailInvoice($invoice->refresh(), $pdf, paid: true);
                return;
            }

            $this->warn('    charge not completed: ' . ($intent['status'] ?? 'unknown'));
        } catch (StripeBillingException $e) {
            $this->error('    charge failed: ' . $e->getMessage());
            Log::warning('Invoice ' . $invoice->invoice_number . ' charge failed: ' . $e->getMessage());

            // No card on file is not a decline — it means we never asked for one. Email
            // the invoice so the agent can act rather than silently failing every month.
            if (in_array($e->stripeCode, ['no_card', 'no_customer'], true)) {
                $this->emailInvoice($invoice, $pdf, paid: false);
            }
        }
    }

    /** Email the invoice PDF: a receipt when paid, a request when not. */
    private function emailInvoice(Invoice $invoice, InvoicePdf $pdf, bool $paid): void
    {
        $to = $invoice->bill_to_email;
        if (! $to) {
            $this->warn('    no billing email — not sent');
            return;
        }

        try {
            $bytes   = $pdf->render($invoice);
            $company = $invoice->company_name ?: config('invoicing.company_name');

            $body = $paid
                ? "Thank you — your payment has been received.\n\n"
                  . "Invoice {$invoice->invoice_number}\n"
                  . 'Amount: ' . Invoice::formatMoney($invoice->total_cents) . " (includes {$invoice->tax_label})\n"
                  . ($invoice->period_start ? 'Period: ' . $invoice->period_start->format('M j') . ' – ' . $invoice->period_end->format('M j, Y') . "\n" : '')
                  . "\nYour invoice is attached as a PDF.\n\n{$company}\n"
                : "Your invoice {$invoice->invoice_number} is attached.\n\n"
                  . 'Amount due: ' . Invoice::formatMoney($invoice->balanceCents()) . " (includes {$invoice->tax_label})\n"
                  . "\nWe could not charge a card on file. Please reply to this email and we will send a secure link to add one.\n\n{$company}\n";

            Mail::raw($body, function ($m) use ($to, $invoice, $bytes, $paid, $pdf) {
                $m->to($to)
                  ->from(config('mail.lead_from.address'), config('invoicing.company_name'))
                  ->subject(($paid ? 'Receipt' : 'Invoice') . ' ' . $invoice->invoice_number . ' — ' . config('invoicing.company_name'))
                  ->attachData($bytes, $pdf->filename($invoice), ['mime' => 'application/pdf']);
            });

            $invoice->sent_at = now();
            $invoice->save();

            $this->info('    emailed ' . $to);
        } catch (\Throwable $e) {
            $this->error('    email failed: ' . $e->getMessage());
            Log::warning('Invoice email failed for ' . $invoice->invoice_number . ': ' . $e->getMessage());
        }
    }
}
