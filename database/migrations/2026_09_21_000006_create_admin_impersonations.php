<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log and single-use handoff for "view this agent's portal as them".
 *
 * Doubles as both on purpose. The row IS the token: consuming it is what makes the
 * handoff one-time, and the same row is the permanent record of which admin opened which
 * agent's portal and when. An impersonation feature without an audit trail is the kind of
 * thing that is impossible to reason about after the fact — "did someone look at this
 * agent's leads in March?" needs an answer.
 *
 * expires_at is DATETIME, not TIMESTAMP: MySQL attaches ON UPDATE CURRENT_TIMESTAMP to
 * the first NOT NULL TIMESTAMP column on this server, which silently rewrites the expiry
 * on any update. That already broke the login-code tables once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_impersonations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('agent_id');

            // Only the hash is stored. The raw token travels in a URL, which means it can
            // land in browser history, a proxy log or a Referer header — so what sits in
            // the database must not be replayable even if the row is read.
            $table->string('token_hash', 64)->unique();

            $table->dateTime('expires_at');
            $table->timestamp('consumed_at')->nullable();

            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'created_at']);
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_impersonations');
    }
};
