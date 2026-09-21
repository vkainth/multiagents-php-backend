<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * First-party invoicing: invoices, their lines, and admin-configurable add-ons.
 *
 * Money is stored as INTEGER CENTS everywhere. Floats cannot represent 0.05 exactly, and
 * a cent of drift per line becomes a reconciliation problem the moment these numbers go
 * on a tax return. Cents also match what the Stripe API takes and returns, so no
 * conversion happens in the middle of a payment.
 *
 * No native JSON columns anywhere here (see CLAUDE.md): the DB replicates MySQL 5.7 to
 * MariaDB, where JSON is only an alias for LONGTEXT, and a JSON column's binary row
 * events stop the replica with error 1677.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Human-facing sequential number. Unique and never reused — an accountant
            // reading a gap in the sequence must be able to find a VOID invoice there,
            // never a deleted one. That is also why there is no hard delete on this table.
            $table->string('invoice_number', 32)->unique();

            // The SITE being billed.
            $table->unsignedBigInteger('agent_id')->index();

            // The agent who actually pays. On a site shared by two agents (Randy/Neb,
            // Nav/Reza) one invoice is issued to the primary payer, so this can differ
            // from agent_id. Snapshotted onto the invoice rather than resolved at render
            // time, so reassigning a site later never rewrites historical invoices.
            $table->unsignedBigInteger('bill_to_agent_id')->index();

            $table->string('status', 16)->default('draft')
                ->comment('draft|open|paid|void|uncollectible');

            $table->char('currency', 3)->default('CAD');

            // The service period this invoice covers, distinct from when it was issued.
            // Saeed's first payment was taken on 2026-09-16 for the month of October, so
            // issue date and service period genuinely disagree and both must be recorded.
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            $table->integer('subtotal_cents')->default(0);
            $table->integer('tax_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->integer('amount_paid_cents')->default(0);

            // Rate stored PER INVOICE, not read from config at render time. If GST ever
            // changes, every historical invoice must still show the rate it was issued
            // under; a config lookup would silently restate old invoices.
            $table->decimal('tax_rate_percent', 5, 2)->default(5.00);
            $table->string('tax_label', 24)->default('GST');

            // Issuer identity, snapshotted for the same reason: an invoice is a fixed
            // record of what was sent, not a live view of current company details.
            $table->string('gst_number', 32)->nullable();
            $table->string('company_name', 160)->nullable();
            $table->text('company_address')->nullable();

            // Bill-to identity, snapshotted at issue time.
            $table->string('bill_to_name', 160)->nullable();
            $table->string('bill_to_email', 160)->nullable();

            $table->string('stripe_payment_intent_id', 64)->nullable()->index();
            $table->string('stripe_charge_id', 64)->nullable();
            // Set when an invoice corresponds to one raised in Stripe before this system
            // existed, so the backfilled history can be traced back.
            $table->string('stripe_invoice_id', 64)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // How payment arrived: card|etransfer|cheque|other. Not every payment goes
            // through Stripe, and the tax report has to include the ones that did not.
            $table->string('payment_method', 24)->nullable();

            $table->text('notes')->nullable();
            $table->text('void_reason')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index('issue_date');
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();

            $table->string('description', 255);

            $table->integer('quantity')->default(1);
            $table->integer('unit_amount_cents')->default(0);
            $table->integer('amount_cents')->default(0);

            // Taxability is per line: GST applies to the service fees, but a line such as
            // a credit or a disbursement may not be taxable, and that must not be decided
            // globally at the invoice level.
            $table->boolean('taxable')->default(true);

            $table->string('kind', 24)->default('subscription')
                ->comment('subscription|setup|addon_one_time|addon_recurring|credit|discount');

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Present when the line came from a configured add-on, so admin can trace it.
            $table->unsignedBigInteger('billing_addon_id')->nullable()->index();

            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('billing_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->index();

            $table->string('description', 255);
            $table->integer('amount_cents');
            $table->boolean('taxable')->default(true);

            $table->string('kind', 16)->default('one_time')->comment('one_time|recurring');

            // A one-time add-on is consumed once: billed_at is stamped when it lands on an
            // invoice, and a stamped one-time add-on is never picked up again. This is the
            // guard against an add-on being charged twice if the monthly run is re-run.
            $table->timestamp('billed_at')->nullable();

            // Recurring add-ons run between these dates (ends_on null = open-ended), and
            // last_billed_period records the period already charged, so a re-run of the
            // same month is a no-op rather than a double charge.
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->date('last_billed_period')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['agent_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('billing_addons');
        Schema::dropIfExists('invoices');
    }
};
