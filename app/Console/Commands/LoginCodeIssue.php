<?php

namespace App\Console\Commands;

use App\Services\LoginCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Break-glass: mint a sign-in code and print it to the terminal.
 *
 * Email second factors have one catastrophic failure mode — the mail does not arrive and
 * nobody can get in. SendGrid outage, a spam filter change, a DNS mistake, an expired
 * DKIM key: all of those lock the owner out of their own admin, usually at the worst
 * moment.
 *
 * Anyone who can run this already has shell access to the server, and therefore already
 * has the database and the .env. So this grants no privilege that is not already held —
 * it just avoids a lockout.
 *
 *   php artisan login:code varinder@pixilink.com
 *   php artisan login:code someone@agency.com --agent
 */
class LoginCodeIssue extends Command
{
    protected $signature = 'login:code {email} {--agent : Look the address up as an agent rather than an admin}';

    protected $description = 'Print a valid sign-in code (use when email delivery is down)';

    public function handle(LoginCodeService $codes): int
    {
        $email = trim($this->argument('email'));
        $type  = $this->option('agent') ? 'agent' : 'admin';

        $row = $type === 'agent'
            ? DB::table('agents')->where('email', $email)->first()
            : DB::table('admins')->where('email', $email)->first();

        if (! $row) {
            $this->error("No {$type} found with email {$email}");
            return self::FAILURE;
        }

        // Generated the same way as a normal code, then written directly so the value can
        // be shown here. Going through issue() would only ever email it, which is exactly
        // the channel assumed broken.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('login_codes')
            ->where('subject_type', $type)->where('subject_id', $row->id)
            ->whereNull('consumed_at')->update(['consumed_at' => now()]);

        DB::table('login_codes')->insert([
            'subject_type' => $type,
            'subject_id'   => $row->id,
            'email'        => $email,
            'code_hash'    => Hash::make($code),
            'expires_at'   => now()->addMinutes(10),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->newLine();
        $this->info("Sign-in code for {$email} ({$type}): {$code}");
        $this->line('Valid for 10 minutes, single use. It was NOT emailed.');
        $this->newLine();

        return self::SUCCESS;
    }
}
