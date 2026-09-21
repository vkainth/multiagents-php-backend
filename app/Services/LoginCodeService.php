<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Email second factor, shared by the admin login and the agent portal.
 *
 * Threat model this is built against: a leaked or guessed password. The code turns that
 * from "attacker is in" into "attacker also needs the mailbox".
 *
 * Deliberate choices:
 *   - codes are HASHED at rest, so a database dump cannot be replayed into a login
 *   - codes are single-use and short-lived
 *   - a wrong guess is counted PER CODE; at the cap the code is destroyed, so an
 *     attacker gets 5 tries at a 6-digit number rather than unlimited ones
 *   - issuing is rate limited separately from verifying, so an attacker cannot use the
 *     login form to mailbomb somebody
 *   - errors never say whether the account exists
 */
class LoginCodeService
{
    private const CODE_TTL_MINUTES   = 10;
    private const MAX_ATTEMPTS       = 5;
    private const MAX_ISSUES_PER_HOUR = 6;
    private const DEVICE_TTL_DAYS    = 30;
    public  const DEVICE_COOKIE      = 'pxl_td';

    /**
     * Issue a code and email it. Returns false only when issuing is rate limited — the
     * caller must NOT tell the user which, since that distinction leaks whether an
     * address is in use.
     */
    public function issue(string $subjectType, int $subjectId, string $email, ?string $ip = null): bool
    {
        $ipHash = $ip ? hash('sha256', $ip) : null;

        $recent = DB::table('login_codes')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= self::MAX_ISSUES_PER_HOUR) {
            Log::warning('Login code issuing rate limited', ['type' => $subjectType, 'id' => $subjectId]);
            return false;
        }

        // Any earlier unused code is invalidated. Without this, every code issued in the
        // last 10 minutes stays valid at once, which multiplies an attacker's guesses.
        DB::table('login_codes')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        // random_int is the CSPRNG. rand()/mt_rand() are predictable from prior output
        // and have no place generating an authentication secret.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('login_codes')->insert([
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'email'        => $email,
            'code_hash'    => Hash::make($code),
            'expires_at'   => now()->addMinutes(self::CODE_TTL_MINUTES),
            'ip_hash'      => $ipHash,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->send($email, $code);

        return true;
    }

    private function send(string $email, string $code): void
    {
        $minutes = self::CODE_TTL_MINUTES;
        $body = "Your sign-in code is:\n\n    {$code}\n\n"
            . "It expires in {$minutes} minutes and can only be used once.\n\n"
            . "If you did not try to sign in, you can ignore this email — but if it keeps\n"
            . "happening, someone may know your password and you should change it.\n";

        try {
            Mail::raw($body, function ($m) use ($email, $code) {
                $m->to($email)
                  // From the DKIM-authenticated pixilink.com domain. A login code that
                  // lands in spam is a lockout, so alignment matters more here than
                  // anywhere else in the app.
                  ->from(config('mail.lead_from.address'), config('invoicing.company_name'))
                  ->subject($code . ' is your sign-in code');
            });
        } catch (\Throwable $e) {
            // Logged, not thrown: the caller has already committed to the challenge, and
            // leaking a mail failure back to the form would confirm the account exists.
            Log::error('Login code email failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a submitted code. Consumes it on success, and burns it once the attempt cap
     * is reached so a wrong-guess loop cannot continue against the same code.
     */
    public function verify(string $subjectType, int $subjectId, string $code): bool
    {
        $row = DB::table('login_codes')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return false;
        }

        if ($row->attempts >= self::MAX_ATTEMPTS) {
            DB::table('login_codes')->where('id', $row->id)->update(['consumed_at' => now()]);
            return false;
        }

        if (! Hash::check(trim($code), $row->code_hash)) {
            DB::table('login_codes')->where('id', $row->id)->increment('attempts');
            return false;
        }

        DB::table('login_codes')->where('id', $row->id)->update([
            'consumed_at' => now(),
            'updated_at'  => now(),
        ]);

        return true;
    }

    // ── Trusted devices ─────────────────────────────────────────────────────

    /** Mint a device token. The RAW value goes in the cookie; only its hash is stored. */
    public function trustDevice(string $subjectType, int $subjectId, ?string $label = null, ?string $ip = null): string
    {
        $raw = Str::random(64);

        DB::table('trusted_devices')->insert([
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'token_hash'   => hash('sha256', $raw),
            'label'        => $label ? Str::limit($label, 115) : null,
            'last_ip_hash' => $ip ? hash('sha256', $ip) : null,
            'expires_at'   => now()->addDays(self::DEVICE_TTL_DAYS),
            'last_used_at' => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $raw;
    }

    /** True when this cookie value is a live trust record for this exact account. */
    public function deviceIsTrusted(string $subjectType, int $subjectId, ?string $rawToken): bool
    {
        if (! $rawToken) {
            return false;
        }

        $row = DB::table('trusted_devices')
            ->where('token_hash', hash('sha256', $rawToken))
            // Scoped to the subject as well as the token: a token for one account must
            // never satisfy the second factor for another.
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            return false;
        }

        DB::table('trusted_devices')->where('id', $row->id)->update(['last_used_at' => now()]);

        return true;
    }

    public function forgetDevice(?string $rawToken): void
    {
        if ($rawToken) {
            DB::table('trusted_devices')->where('token_hash', hash('sha256', $rawToken))->delete();
        }
    }

    public function deviceTtlDays(): int
    {
        return self::DEVICE_TTL_DAYS;
    }

    /** Housekeeping — expired rows have no value and codes are sensitive by nature. */
    public function prune(): int
    {
        $codes = DB::table('login_codes')->where('expires_at', '<', now()->subDay())->delete();
        $devs  = DB::table('trusted_devices')->where('expires_at', '<', now())->delete();

        return $codes + $devs;
    }
}
