<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Daily per-site funnel report.
 *
 * Answers "are we getting people?", which is not the same question as "are we getting
 * leads". Someone who starts a form and leaves, or mistypes their number, is evidence of
 * real interest and was previously invisible.
 *
 * One email per site, to that site's owner, containing only their own numbers — an agent
 * must never see another agent's traffic.
 *
 * Reports a daily figure beside a 7-day total: at this volume a single day is mostly noise,
 * and a report that reads 0 every morning gets filtered to trash.
 */
class DailyFunnelReport extends Command
{
    protected $signature = 'report:daily-funnel
                            {--date= : Report on this Y-m-d instead of yesterday}
                            {--agent= : Only this agent slug}
                            {--to= : Override recipient(s), comma-separated}
                            {--dry-run : Print instead of emailing}';

    protected $description = 'Email each site owner a daily funnel report for their own site';

    /** Report days are local days. Readers are in BC; the app clock is UTC. */
    private const REPORT_TZ = 'America/Vancouver';

    public function handle(): int
    {
        // Timestamps are stored in UTC (config('app.timezone') is UTC, and Laravel's now()
        // is what writes them) — but MySQL's own NOW() on this box returns Pacific, 7 hours
        // behind. So boundaries are built explicitly rather than trusted from either side.
        //
        // A "day" is anchored to Pacific: a UTC day would run 5pm–5pm local and the totals
        // would never match what the reader saw happen that day.
        $tz  = self::REPORT_TZ;
        $day = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'), $tz)
            : \Carbon\Carbon::now($tz)->subDay();

        $start = $day->copy()->startOfDay()->utc();
        $end   = $day->copy()->endOfDay()->utc();
        $week  = \Carbon\Carbon::now($tz)->subDays(7)->startOfDay()->utc();

        $agents = \App\Models\Agent::query()
            ->when($this->option('agent'), fn ($q) => $q->where('slug', $this->option('agent')))
            ->orderBy('id')
            ->get();

        if ($agents->isEmpty()) {
            $this->error('No matching agents.');
            return self::FAILURE;
        }

        $sent = 0;
        foreach ($agents as $agent) {
            $data = $this->collect($agent, $start, $end, $week, $day);

            if ($this->option('dry-run')) {
                $this->line($data['text']);
                continue;
            }

            // Skip a site with nothing at all rather than mailing its owner a page of zeros
            // every morning. The 7-day check means a quiet single day still reports.
            if ($data['engaged'] === 0 && $data['captured'] === 0 && $data['engaged7'] === 0) {
                $this->line("  {$agent->slug}: nothing to report, skipped");
                continue;
            }

            $to = $this->option('to')
                ? array_map('trim', explode(',', $this->option('to')))
                : array_values(array_filter([$agent->settings?->notification_email ?: $agent->email]));

            if (empty($to)) {
                $this->warn("  {$agent->slug}: no recipient on file, skipped");
                continue;
            }

            try {
                Mail::send(['html' => 'emails.funnel_report'], $data, function ($m) use ($to, $day, $data) {
                    $m->to($to)->subject($data['siteLabel'] . ' — site funnel, ' . $day->format('D j M'));
                    // text/plain alternative: readable in stripped-down clients, and less
                    // likely to be graded as bulk mail.
                    $m->getSymfonyMessage()->text($data['text']);
                });
                $this->info("  {$agent->slug}: sent to " . implode(', ', $to));
                $sent++;
            } catch (\Throwable $e) {
                // One bad recipient must not abort the rest of the run.
                $this->error("  {$agent->slug}: send failed — " . $e->getMessage());
            }
        }

        if (! $this->option('dry-run')) {
            $this->info("Done — {$sent} report(s) sent.");
        }

        return self::SUCCESS;
    }

    /** Gather one site's numbers. Every query here is scoped to this agent. */
    private function collect($agent, $start, $end, $week, $day): array
    {
        $slug = $agent->slug;

        $ev = function ($type, $from, $to = null) use ($slug) {
            $q = DB::table('sold_gate_events')
                ->where('event_type', $type)
                ->where('agent_slug', $slug)          // scoped — never another site's traffic
                ->where('created_at', '>=', $from);
            if ($to) $q->where('created_at', '<=', $to);
            return (int) $q->count();
        };

        $starts      = $ev('form_start', $start, $end);
        $starts7     = $ev('form_start', $week);
        $abandons    = $ev('form_abandon', $start, $end);
        $abandons7   = $ev('form_abandon', $week);
        $otpFailed   = $ev('otp_failed', $start, $end);
        $otp7        = $ev('otp_failed', $week);
        $badPhone    = $ev('phone_invalid', $start, $end);
        $badPhone7   = $ev('phone_invalid', $week);
        $impressions = $ev('prompt_impression', $start, $end);
        $impr7       = $ev('prompt_impression', $week);
        $gateReg     = $ev('register', $start, $end);
        $gateReg7    = $ev('register', $week);
        $gateLogin   = $ev('login', $start, $end);
        $gateLogin7  = $ev('login', $week);

        $leadQ  = fn () => DB::table('agent_leads')->where('agent_id', $agent->id);
        $leads  = (int) $leadQ()->whereBetween('created_at', [$start, $end])->count();
        $leads7 = (int) $leadQ()->where('created_at', '>=', $week)->count();

        // Sign-ups for this site only. Legacy Firebase rows (uid set) belong to
        // bccondosandhomes.com — a different site — and would swamp these numbers.
        $userQ = fn () => DB::table('users')
            ->where('agent_id', $agent->id)
            ->where(function ($q) { $q->whereNull('uid')->orWhere('uid', ''); });
        $signups   = (int) $userQ()->whereBetween('created_at', [$start, $end])->count();
        $signups7  = (int) $userQ()->where('created_at', '>=', $week)->count();
        $verified  = (int) $userQ()->whereBetween('created_at', [$start, $end])->whereNotNull('phone_verified_at')->count();
        $verified7 = (int) $userQ()->where('created_at', '>=', $week)->whereNotNull('phone_verified_at')->count();

        $engaged  = $starts + $impressions;
        $engaged7 = $starts7 + $impr7;
        $captured = $leads + $signups;
        $rate     = $engaged > 0 ? round(($captured / $engaged) * 100, 1) : 0;

        $groups = [
            ['title' => 'Showing interest', 'class' => 's-interest', 'rows' => [
                ['label' => 'Started filling a form', 'day' => $starts,      'week' => $starts7],
                ['label' => 'Shown a sign-in prompt', 'day' => $impressions, 'week' => $impr7],
            ]],
            ['title' => 'Dropped out', 'class' => 's-dropped', 'rows' => [
                ['label' => 'Started a form, did not submit', 'day' => $abandons,  'week' => $abandons7],
                ['label' => 'Wrong or expired code',          'day' => $otpFailed, 'week' => $otp7],
                ['label' => 'Phone number rejected',          'day' => $badPhone,  'week' => $badPhone7],
            ]],
            ['title' => 'Converted', 'class' => 's-convert', 'rows' => [
                ['label' => 'Leads captured',             'day' => $leads,     'week' => $leads7],
                ['label' => 'Signed up',                  'day' => $signups,   'week' => $signups7],
                ['label' => 'Signed up + phone verified', 'day' => $verified,  'week' => $verified7],
                ['label' => 'Clicked register at gate',   'day' => $gateReg,   'week' => $gateReg7],
                ['label' => 'Clicked sign-in at gate',    'day' => $gateLogin, 'week' => $gateLogin7],
            ]],
        ];

        $siteLabel = $agent->name ?: $slug;

        // Plain-text twin, built from the same numbers so the two can never disagree.
        $text  = "{$siteLabel} — site funnel — {$day->format('D j M Y')}\n";
        $text .= str_repeat('=', 56) . "\n\n";
        $text .= "  {$engaged} people showed interest on this day ({$engaged7} over 7 days)\n\n";
        $text .= sprintf("  %-34s %6s   %6s\n", '', $day->format('D j'), '7 days');
        foreach ($groups as $g) {
            $text .= "\n" . strtoupper($g['title']) . "\n";
            foreach ($g['rows'] as $r) {
                $text .= sprintf("  %-34s %6s   %6s\n", $r['label'], $r['day'], $r['week']);
            }
        }
        $text .= "\n" . str_repeat('-', 56) . "\n";
        $text .= $engaged > 0
            ? "  Of {$engaged} people who engaged, {$captured} gave us their details ({$rate}%).\n"
            : "  No tracked engagement on this day.\n";
        $text .= "\nNotes:\n";
        $text .= "  - \"Showed interest\" = started a form or was shown a sign-in prompt.\n";
        $text .= "    Anonymous - no field values are recorded.\n";
        $text .= "  - Abandons are detected on page-hide, so a force-quit browser may not\n";
        $text .= "    report one. Treat abandons as a floor, not an exact figure.\n";
        $text .= "  - Days run midnight to midnight Pacific.\n";

        return [
            'dayLabel'  => $day->format('l j F Y'),
            'dayShort'  => $day->format('D j M'),
            'siteLabel' => $siteLabel,
            'engaged'   => $engaged,
            'engaged7'  => $engaged7,
            'captured'  => $captured,
            'rate'      => $rate,
            'groups'    => $groups,
            'text'      => $text,
        ];
    }
}
