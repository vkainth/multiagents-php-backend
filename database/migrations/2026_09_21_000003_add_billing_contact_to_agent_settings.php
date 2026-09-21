<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explicit billing contact per site.
 *
 * The person who PAYS is not always an agent record. findfraservalleyhomes.com is billed
 * to Randy Dyck, but agent 1 is now Neb Yidegiligne — Randy has no agent row of his own
 * since the handover, and inventing one purely to hang an invoice off would put a
 * non-existent agent into every listing-credit and cross-agent query in the app.
 *
 * These two fields override the payer's name/email when an invoice snapshots its bill-to
 * details. Null falls back to the billing_primary_agent_id agent, then to the site's own
 * agent, so existing behaviour is unchanged where they are not set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'billing_contact_name')) {
                $table->string('billing_contact_name', 160)->nullable();
            }
            if (!Schema::hasColumn('agent_settings', 'billing_contact_email')) {
                $table->string('billing_contact_email', 160)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            foreach (['billing_contact_name', 'billing_contact_email'] as $col) {
                if (Schema::hasColumn('agent_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
