<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * expires_at must be DATETIME, not TIMESTAMP.
 *
 * MySQL applies automatic initialisation to the FIRST NOT NULL TIMESTAMP column in a
 * table when explicit_defaults_for_timestamp is off — which it is on this server. Both
 * new tables therefore got:
 *
 *     `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *
 * so ANY update to the row silently rewrote the expiry to "now". Observed directly: a
 * single failed code guess moved expires_at from 19:58 to 12:48, and the code was then
 * invisible to its own lookup.
 *
 * Effects, both of which fail CLOSED — nothing was ever granted that should not have
 * been, but the feature did not work:
 *   - login_codes: the first wrong guess expired the code, so the correct code was then
 *     rejected and the attempt counter never advanced past 1.
 *   - trusted_devices: deviceIsTrusted() stamps last_used_at on every check, which reset
 *     the expiry to now — so a remembered device would have worked exactly once.
 *
 * DATETIME has no automatic-update behaviour and no session-timezone conversion, which
 * is what an absolute expiry wants.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `login_codes` MODIFY `expires_at` DATETIME NOT NULL');
        DB::statement('ALTER TABLE `trusted_devices` MODIFY `expires_at` DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `login_codes` MODIFY `expires_at` TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE `trusted_devices` MODIFY `expires_at` TIMESTAMP NOT NULL');
    }
};
