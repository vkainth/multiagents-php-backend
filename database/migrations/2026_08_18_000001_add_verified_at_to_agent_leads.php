<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * agent_leads.phone_verified_at / email_verified_at are referenced by App\Models\AgentLead
 * ($casts, and the booted() hook that dispatches AgentLeadVerifiedJob) and are defined in
 * 2026_06_03_000011_create_agent_leads_table — but they are absent from the live table.
 *
 * Cause: two migrations share the name create_agent_leads_table (000010 and 000011, both
 * batch 1002). 000010 created the table first, so 000011's Schema::create was skipped and
 * its extra columns never landed. Consequently the hook has never been able to fire and
 * AgentLeadVerifiedJob has never run in production.
 *
 * This adds only the two missing columns, guarded, rather than re-running 000011.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_leads')) {
            return;
        }

        Schema::table('agent_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_leads', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
            if (! Schema::hasColumn('agent_leads', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_leads')) {
            return;
        }

        Schema::table('agent_leads', function (Blueprint $table) {
            foreach (['phone_verified_at', 'email_verified_at'] as $col) {
                if (Schema::hasColumn('agent_leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
