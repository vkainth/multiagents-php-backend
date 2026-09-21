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

        // A DATE, not a month start. Each site resolves its own period from this using
        // its anchor day, so the run must be told "when is it", not "which month".
        // Passing the 1st would make an agent anchored on the 21st resolve to the
        // PREVIOUS cycle and bill them for a period they have already paid.
        // --period=YYYY-MM uses that month's end so the anchor lands inside it.
        $runFor = $this->option('period')
            ? Carbon::createFromFormat('Y-m', $this->option('period'))->endOfMonth()
            : Carbon::now();

        $this->info('Billing run as at ' . $runFor->toDateString() . ($dry ? '  [DRY RUN — nothing will be written]' : ''));

        if (! $this->option('retries-only')) {
            $this->issueForPeriod($invoices, $stripe, $pdf, $runFor, $dry);
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

    private function issueForPeriod(InvoiceService $invoices, StripeBilling $stripe, InvoicePdf $pdf, Carbon $runFor, bool $dry): void
    {
        foreach ($this->billableAgents() as $agent) {
            $settings = $agent->settings;

            // Each site bills on its OWN anchor day, not the 1st of the month. Sharene's
            // cycle runs from the 21st, so billing her on the 1st would invoice her for a
            // period she is already paid up on and shift every future period by ten days.
            $period = $this->periodStartFor((int) ($settings->billing_anchor_day ?: 1), $runFor);

            // Never retro-bill a site for periods before it went live.
            if ($settings->billing_starts_on && $period->lt(Carbon::parse($settings->billing_starts_on)->startOfDay())) {
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
            // An invoice that was never SENT cannot be overdue. Nobody has been asked to
            // pay it, so retrying the card against it and eventually withholding their
            // leads would be penalising a customer for our own inaction — and the clock
            // would have started on the issue date, which may be weeks earlier.
            if (! $invoice->sent_at) {
                continue;
            }

            // Nor can an invoice be overdue BEFORE ITS DUE DATE. October's invoices were
            // sent early, on 21 September, so ageing from sent_at alone would have
            // charged a client's card on 24 September for a period that had not started
            // — and chased them for it. Sending an invoice early is a courtesy; billing
            // early is not.
            if ($invoice->due_date && Carbon::now()->startOfDay()->lt($invoice->due_date->startOfDay())) {
                continue;
            }

            // The dunning clock starts when the invoice becomes DUE, or when it was sent
            // if that is later — never from whichever happened first.
            //
            // Ageing from sent_at alone meant October's invoices, sent early on 21
            // September, were already 10 days "overdue" on 1 October: past the 7-day
            // grace, so they would have skipped both retries and gone straight to
            // restricting the site on the very day payment first became due.
            $clockStart = $invoice->due_date && $invoice->due_date->gt($invoice->sent_at)
                ? $invoice->due_date->copy()->startOfDay()
                : Carbon::parse($invoice->sent_at)->startOfDay();

            $age = (int) $clockStart->diffInDays(Carbon::now()->startOfDay());

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

                // Deliberate, dated follow-up — the only reminders anyone receives.
                // Sent after the retry so a card that has since been added results in a
                // receipt rather than a chase.
                if (! $invoice->refresh()->isPaid()) {
                    $this->sendReminder($invoice, $stripe, $pdf, $age);
                }
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
            $this->alertOperator($invoice, 'Charge not completed: ' . ($intent['status'] ?? 'unknown'));
        } catch (StripeBillingException $e) {
            $this->error('    charge failed: ' . $e->getMessage());
            Log::warning('Invoice ' . $invoice->invoice_number . ' charge failed: ' . $e->getMessage());

            // A DECLINE must not be silent. Previously only 'no card' produced an email,
            // so a declined or expired card meant the customer heard nothing, we heard
            // nothing, and the invoice simply sat there — the worst failure mode a
            // billing system has, because everyone believes it worked.
            $this->alertOperator($invoice, $e->getMessage());

            // Every failure now tells the customer something actionable, not just the
            // "no card" case. A card link is included whenever they have no usable card —
            // which is also true after a decline if the stored card has been removed.
            $payUrl = null;
            try {
                $payer = $invoice->billTo()->with('settings')->first();
                if ($payer && ! $stripe->hasCard($payer)) {
                    $payUrl = $stripe->cardLinkFor($payer);
                }
            } catch (\Throwable $linkErr) {
                Log::warning('Could not build card link for ' . $invoice->invoice_number . ': ' . $linkErr->getMessage());
            }

            // Only email if they have NOT already been told about this invoice. The run
            // is daily now, and it re-attempts every open invoice each morning — without
            // this guard Randy and Nav would receive the same "no card on file" email
            // every single day until they act, which trains people to ignore us.
            // Deliberate follow-ups happen on the retry days, via sendReminder().
            if (! $invoice->sent_at) {
                $this->emailInvoice(
                    $invoice,
                    $pdf,
                    paid: false,
                    payUrl: $payUrl,
                    problem: in_array($e->stripeCode, ['no_card', 'no_customer'], true) ? 'no_card' : 'declined',
                );
            }
        }
    }

    /** Email the invoice PDF: a receipt when paid, a request when not. */
    private function emailInvoice(Invoice $invoice, InvoicePdf $pdf, bool $paid, ?string $payUrl = null, ?string $problem = null): void
    {
        $to = $invoice->bill_to_email;
        if (! $to) {
            $this->warn('    no billing email — not sent');
            return;
        }

        try {
            $bytes   = $pdf->render($invoice, $payUrl, hasCard: $paid);
            $company = $invoice->company_name ?: config('invoicing.company_name');

            $body = $paid
                ? "Thank you — your payment has been received.\n\n"
                  . "Invoice {$invoice->invoice_number}\n"
                  . 'Amount: ' . Invoice::formatMoney($invoice->total_cents) . " (includes {$invoice->tax_label})\n"
                  . ($invoice->period_start ? 'Period: ' . $invoice->period_start->format('M j') . ' – ' . $invoice->period_end->format('M j, Y') . "\n" : '')
                  . "\nYour invoice is attached as a PDF.\n\n{$company}\n"
                : "Your invoice {$invoice->invoice_number} is attached.\n\n"
                  . 'Amount due: ' . Invoice::formatMoney($invoice->balanceCents()) . " (includes {$invoice->tax_label})\n"
                  . ($invoice->period_start ? 'Period: ' . $invoice->period_start->format('M j') . ' – ' . $invoice->period_end->format('M j, Y') . "\n" : '')
                  . $this->problemText($problem, $payUrl)
                  . "\n{$company}\n";

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
    /**
     * Start of the billing period that CONTAINS $ref, for a site anchored on $anchorDay.
     *
     * Clamped to the month length so a site anchored on the 31st still bills in
     * February rather than silently skipping it, and subMonthNoOverflow avoids the
     * classic March-31 minus one month lands on March-3 bug.
     */
    /**
     * A payment reminder on a retry day.
     *
     * Separate from emailInvoice so a reminder reads like a reminder rather than the
     * original invoice arriving again, and so it states what happens next — people
     * respond to a specific consequence with a date on it, not to a repeated attachment.
     */
    private function sendReminder(Invoice $invoice, StripeBilling $stripe, InvoicePdf $pdf, int $age): void
    {
        $to = $invoice->bill_to_email;
        if (! $to) {
            return;
        }

        $grace = (int) config('invoicing.grace_period_days', 7);

        try {
            $payer   = $invoice->billTo()->with('settings')->first();
            $hasCard = $payer ? $stripe->hasCard($payer) : false;
            $payUrl  = (! $hasCard && $payer) ? $stripe->cardLinkFor($payer) : null;

            $daysLeft = max(0, $grace - $age);
            $company  = $invoice->company_name ?: config('invoicing.company_name');

            $body = "A reminder that invoice {$invoice->invoice_number} is still outstanding.\n\n"
                . 'Amount due: ' . Invoice::formatMoney($invoice->balanceCents()) . " (includes {$invoice->tax_label})\n"
                . 'Sent: ' . $invoice->sent_at->format('M j, Y') . "\n"
                . ($payUrl
                    ? "\nThere is no card on file. Add one here and it will be settled automatically:\n\n{$payUrl}\n"
                    : "\nWe will try the card on file again shortly.\n")
                . ($daysLeft > 0
                    ? "\nIf it is still unpaid in {$daysLeft} day" . ($daysLeft === 1 ? '' : 's') . ", new enquiries from your\n"
                      . "site will be held rather than delivered until payment clears. They are never lost.\n"
                    : '')
                . "\n{$company}\n";

            Mail::raw($body, function ($m) use ($to, $invoice, $pdf, $payUrl, $hasCard) {
                $m->to($to)
                  ->from(config('mail.lead_from.address'), config('invoicing.company_name'))
                  ->subject('Reminder: invoice ' . $invoice->invoice_number . ' is outstanding')
                  ->attachData($pdf->render($invoice, $payUrl, $hasCard), $pdf->filename($invoice), ['mime' => 'application/pdf']);
            });

            $this->info('    reminder sent to ' . $to);
        } catch (\Throwable $e) {
            Log::warning('Reminder failed for ' . $invoice->invoice_number . ': ' . $e->getMessage());
        }
    }

    /**
     * The explanation a customer actually needs, per failure mode.
     *
     * A decline and "we never had a card" are different problems with different fixes,
     * and telling someone their card was declined when we never asked for one is the
     * kind of message that generates a phone call rather than a payment.
     */
    private function problemText(?string $problem, ?string $payUrl): string
    {
        if ($problem === 'declined') {
            return "\nWe tried to charge the card on file and it was declined. That is usually an\n"
                 . "expired card or a bank block rather than anything wrong with the account.\n"
                 . ($payUrl
                     ? "\nYou can add a different card here:\n\n{$payUrl}\n\nThis link is valid for 45 days.\n"
                     : "\nPlease reply to this email and we will send a secure link to update it.\n");
        }

        if ($payUrl) {
            return "\nThere is no card on file for this account. Add one here and the invoice will be\n"
                 . "charged automatically — it takes about a minute, and the page is hosted by Stripe:\n\n"
                 . "{$payUrl}\n\nThis link is valid for 45 days.\n";
        }

        return "\nThis will be charged automatically to the card on file.\n";
    }

    /**
     * Tell the operator when a charge fails.
     *
     * Without this a decline exists only in a log nobody reads, and the first sign of
     * trouble is noticing months later that someone stopped paying. Failure to collect
     * money should be noisy.
     */
    private function alertOperator(Invoice $invoice, string $reason): void
    {
        $to = config('invoicing.alert_email') ?: config('invoicing.company_email');
        if (! $to) {
            return;
        }

        try {
            Mail::raw(
                "Billing problem on invoice {$invoice->invoice_number}.\n\n"
                . 'Site:    ' . ($invoice->agent?->slug ?? '?') . "\n"
                . "Bill to: {$invoice->bill_to_name} <{$invoice->bill_to_email}>\n"
                . 'Amount:  ' . Invoice::formatMoney($invoice->balanceCents()) . "\n"
                . "Reason:  {$reason}\n\n"
                . "The customer has been emailed. Nothing has been charged.\n",
                fn ($m) => $m->to($to)
                    ->from(config('mail.lead_from.address'), config('invoicing.company_name'))
                    ->subject('Billing: ' . $invoice->invoice_number . ' — ' . $reason)
            );
        } catch (\Throwable $e) {
            Log::warning('Operator billing alert failed: ' . $e->getMessage());
        }
    }

    private function periodStartFor(int $anchorDay, Carbon $ref): Carbon
    {
        $ref = $ref->copy()->startOfDay();
        $anchorDay = max(1, min(31, $anchorDay));

        $thisMonth = $ref->copy()->day(min($anchorDay, $ref->daysInMonth));

        if ($ref->gte($thisMonth)) {
            return $thisMonth;
        }

        $prev = $ref->copy()->subMonthNoOverflow()->startOfMonth();

        return $prev->day(min($anchorDay, $prev->daysInMonth));
    }
}
