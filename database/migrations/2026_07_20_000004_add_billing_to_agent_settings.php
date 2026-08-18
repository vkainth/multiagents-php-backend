<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'stripe_customer_id')) {
                $table->string('stripe_customer_id', 64)->nullable();
            }
            if (!Schema::hasColumn('agent_settings', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id', 64)->nullable();
            }
            if (!Schema::hasColumn('agent_settings', 'billing_tier')) {
                $table->string('billing_tier', 16)->nullable()->comment('hub|personal');
            }
            if (!Schema::hasColumn('agent_settings', 'billing_status')) {
                $table->string('billing_status', 16)->nullable()->default('none')
                    ->comment('none|active|past_due|suspended|cancelling|canceled');
            }
            // next_billing_date = end of current paid period (= next invoice date)
            if (!Schema::hasColumn('agent_settings', 'next_billing_date')) {
                $table->timestamp('next_billing_date')->nullable();
            }
            if (!Schema::hasColumn('agent_settings', 'last_payment_at')) {
                $table->timestamp('last_payment_at')->nullable();
            }
            // Tracks when the FIRST payment failure occurred so we can apply a
            // configurable grace period (BILLING_GRACE_PERIOD_DAYS) before suspending.
            if (!Schema::hasColumn('agent_settings', 'billing_failed_at')) {
                $table->timestamp('billing_failed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'billing_tier',
                'billing_status',
                'next_billing_date',
                'last_payment_at',
                'billing_failed_at',
            ]);
        });
    }
};
