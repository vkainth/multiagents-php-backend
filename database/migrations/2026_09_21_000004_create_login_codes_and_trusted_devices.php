<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email second-factor for admin and agent logins.
 *
 * Both tables store only HASHES. A login code sitting in plaintext in the database is a
 * second password: anyone with read access to the DB — a backup, a dump, a SQL injection
 * elsewhere — could log in as an admin during the code's lifetime without knowing the
 * password. Same reasoning for device tokens, which are longer-lived and therefore worse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();

            // 'admin' | 'agent' — the two guards. Kept as a string rather than a
            // polymorphic relation because these rows are short-lived and never joined.
            $table->string('subject_type', 16);
            $table->unsignedBigInteger('subject_id');

            // Snapshot of where it was sent, so an audit does not depend on the account
            // still having the same address.
            $table->string('email', 160);

            $table->string('code_hash');

            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();

            // Wrong guesses against THIS code. At the cap the code is burned, so an
            // attacker gets a handful of tries at a 6-digit number, not a million.
            $table->unsignedTinyInteger('attempts')->default(0);

            // Hashed, not raw: an IP is personal data and this table has no need to
            // identify anyone, only to spot repetition.
            $table->string('ip_hash', 64)->nullable();

            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('expires_at');
        });

        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();

            $table->string('subject_type', 16);
            $table->unsignedBigInteger('subject_id');

            // SHA-256 of the cookie value. Indexed because every login looks it up, and
            // unique because a collision would mean two devices sharing a trust record.
            $table->string('token_hash', 64)->unique();

            // Shown in a "your devices" list so a stolen laptop can be recognised.
            $table->string('label', 120)->nullable();
            $table->string('last_ip_hash', 64)->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes');
        Schema::dropIfExists('trusted_devices');
    }
};
