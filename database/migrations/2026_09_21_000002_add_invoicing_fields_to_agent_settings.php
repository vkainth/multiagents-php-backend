<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-site billing configuration for first-party invoicing.
 *
 * The existing module derived price from a hardcoded TIERS map in BillingController
 * ('hub' => 2500, 'personal' => 150). That cannot express what is actually being
 * charged: all three live sites are on $2,500/mo regardless of tier, and a negotiated
 * price for one site would have meant editing a PHP constant. The amount now lives with
 * the site, in cents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'billing_monthly_cents')) {
                $table->integer('billing_monthly_cents')->nullable()
                    ->comment('Recurring fee in cents, pre-tax. 250000 = $2,500.00');
            }

            // On a site shared by two agents, ONE invoice is issued to this agent and
            // their card is charged. Null means bill the site's own agent.
            if (!Schema::hasColumn('agent_settings', 'billing_primary_agent_id')) {
                $table->unsignedBigInteger('billing_primary_agent_id')->nullable();
            }

            // Which saved Stripe card to charge. Stored explicitly rather than relying on
            // the customer's invoice_settings default, because Saeed has three cards on
            // file (two of them old) and "whichever Stripe considers default" is not a
            // decision worth leaving implicit on a $2,625 charge.
            if (!Schema::hasColumn('agent_settings', 'stripe_payment_method_id')) {
                $table->string('stripe_payment_method_id', 64)->nullable();
            }

            // Day of month to bill. 1 for everyone today; kept configurable because
            // Saeed's cycle starts from a mid-month first payment.
            if (!Schema::hasColumn('agent_settings', 'billing_anchor_day')) {
                $table->unsignedTinyInteger('billing_anchor_day')->default(1);
            }

            // First period this site is billable for. The monthly run never issues an
            // invoice for a period before this date, which is what stops a newly
            // configured site being retro-billed for months it was not live.
            if (!Schema::hasColumn('agent_settings', 'billing_starts_on')) {
                $table->date('billing_starts_on')->nullable();
            }

            // Set when unpaid past the grace period: leads are withheld (still captured)
            // and admin drops to read-only until payment clears.
            if (!Schema::hasColumn('agent_settings', 'billing_restricted_at')) {
                $table->timestamp('billing_restricted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            foreach ([
                'billing_monthly_cents',
                'billing_primary_agent_id',
                'stripe_payment_method_id',
                'billing_anchor_day',
                'billing_starts_on',
                'billing_restricted_at',
            ] as $col) {
                if (Schema::hasColumn('agent_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
