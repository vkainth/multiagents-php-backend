<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Daily funnel report.
 *
 * Built to answer "are we getting people?" - which is not the same question as "are we
 * getting leads". Someone who starts a form and leaves, or mistypes their number, is
 * evidence of real interest and was previously invisible.
 *
 * Counts, for the previous full day:
 *   - people who started a form and never submitted it
 *   - people whose phone/code failed verification
 *   - people who did sign up, and how many verified
 *
 * Deliberately reports both a daily figure and a 7-day total: at ~20 sign-ups a month a
 * single day is mostly noise, and a report that reads "0" every day gets ignored.
 */
class DailyFunnelReport extends Command
{
    protected $signature = 'report:daily-funnel
                            {--date= : Report on this Y-m-d instead of yesterday}
                            {--to= : Override recipient(s), comma-separated}
                            {--dry-run : Print the report instead of emailing it}';

    protected $description = 'Email a daily site funnel report (form starts, abandons, failed verifications, sign-ups)';

    /** Report days are local days. The reader is in BC; the app clock is UTC. */
    private const REPORT_TZ = 'America/Vancouver';

    public function handle(): int
    {
        // Timestamps are stored in UTC (config('app.timezone') is UTC, and Laravel's
        // now() is what writes them) - but MySQL's own NOW() on this box returns
        // Pacific, 7 hours behind. So boundaries must be built explicitly rather than
        // trusted from either side.
        //
        // A "day" is anchored to Pacific, not UTC: a UTC day would run 5pm-5pm local
        // and the totals would never match what the reader saw happen that day.
        $tz = self::REPORT_TZ;
        $day = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'), $tz)
            : \Carbon\Carbon::now($tz)->subDay();

        $start = $day->copy()->startOfDay()->utc();
        $end   = $day->copy()->endOfDay()->utc();
        $week  = \Carbon\Carbon::now($tz)->subDays(7)->startOfDay()->utc();

        $ev = function ($type, $from, $to = null) {
            $q = DB::table('sold_gate_events')->where('event_type', $type)->where('created_at', '>=', $from);
            if ($to) $q->where('created_at', '<=', $to);
            return (int) $q->count();
        };

        // Form engagement
        $starts     = $ev('form_start',   $start, $end);
        $abandons   = $ev('form_abandon', $start, $end);
        $otpFailed  = $ev('otp_failed',   $start, $end);
        $badPhone   = $ev('phone_invalid', $start, $end);

        // Gate prompts
        $impressions = $ev('prompt_impression', $start, $end);
        $gateRegister = $ev('register', $start, $end);
        $gateLogin    = $ev('login', $start, $end);

        // Leads actually captured
        $leads = (int) DB::table('agent_leads')
            ->whereBetween('created_at', [$start, $end])->count();

        // Sign-ups. Only the new flow: legacy Firebase rows (uid set) belong to
        // bccondosandhomes.com, a different site, and would swamp these numbers.
        $signupQ = DB::table('users')
            ->where(function ($q) { $q->whereNull('uid')->orWhere('uid', ''); });
        $signups  = (int) (clone $signupQ)->whereBetween('created_at', [$start, $end])->count();
        $verified = (int) (clone $signupQ)->whereBetween('created_at', [$start, $end])
            ->whereNotNull('phone_verified_at')->count();

        // 7-day context
        $starts7   = $ev('form_start', $week);
        $abandons7 = $ev('form_abandon', $week);
        $otp7      = $ev('otp_failed', $week);
        $leads7    = (int) DB::table('agent_leads')->where('created_at', '>=', $week)->count();
        $signups7  = (int) (clone $signupQ)->where('created_at', '>=', $week)->count();
        $verified7 = (int) (clone $signupQ)->where('created_at', '>=', $week)
            ->whereNotNull('phone_verified_at')->count();

        // Anyone who showed intent, whether or not we got their details. This is the
        // headline: it is the number that says people are turning up.
        $engaged  = $starts + $impressions;
        $engaged7 = $starts7 + $ev('prompt_impression', $week);

        $pad = fn($l, $a, $b) => sprintf("  %-34s %6s   %6s\n", $l, $a, $b);

        $body  = "Site funnel — {$day->format('D j M Y')}\n";
        $body .= str_repeat('=', 54) . "\n\n";
        $body .= sprintf("  %-34s %6s   %6s\n", '', 'day', '7 days');
        $body .= str_repeat('-', 54) . "\n";
        $body .= "\nPEOPLE SHOWING INTEREST\n";
        $body .= $pad('Engaged (form started or prompted)', $engaged, $engaged7);
        $body .= $pad('Started filling a form', $starts, $starts7);
        $body .= $pad('Saw a sign-in prompt', $impressions, $ev('prompt_impression', $week));
        $body .= "\nDROPPED OUT\n";
        $body .= $pad('Started a form, did not submit', $abandons, $abandons7);
        $body .= $pad('Wrong / expired code', $otpFailed, $otp7);
        $body .= $pad('Phone number rejected', $badPhone, $ev('phone_invalid', $week));
        $body .= "\nCONVERTED\n";
        $body .= $pad('Leads captured', $leads, $leads7);
        $body .= $pad('Signed up', $signups, $signups7);
        $body .= $pad('Signed up + phone verified', $verified, $verified7);
        $body .= $pad('Clicked register at the gate', $gateRegister, $ev('register', $week));
        $body .= $pad('Clicked sign-in at the gate', $gateLogin, $ev('login', $week));

        $body .= "\n" . str_repeat('-', 54) . "\n";
        if ($engaged > 0) {
            $rate = round((($leads + $signups) / max($engaged, 1)) * 100, 1);
            $body .= "  Of {$engaged} people who engaged, " . ($leads + $signups) . " gave us their details ({$rate}%).\n";
        } else {
            $body .= "  No tracked engagement on this day.\n";
        }
        $body .= "\nNotes:\n";
        $body .= "  - \"Engaged\" counts people who started a form or were shown a sign-in\n";
        $body .= "    prompt. It is anonymous - no field values are recorded.\n";
        $body .= "  - Sign-ups exclude bccondosandhomes.com (legacy) accounts.\n";
        $body .= "  - Abandons are detected on page-hide, so a browser that is force-quit\n";
        $body .= "    may not report one. Treat abandons as a floor, not an exact figure.\n";

        if ($this->option('dry-run')) {
            $this->line($body);
            return self::SUCCESS;
        }

        $to = $this->option('to')
            ? array_map('trim', explode(',', $this->option('to')))
            : [config('mail.funnel_report_to', 'varinder@pixilink.com')];

        try {
            Mail::raw($body, function ($m) use ($to, $day) {
                $m->to($to)->subject('Site funnel — ' . $day->format('D j M Y'));
            });
            $this->info('Sent to ' . implode(', ', $to));
        } catch (\Throwable $e) {
            $this->error('Send failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
