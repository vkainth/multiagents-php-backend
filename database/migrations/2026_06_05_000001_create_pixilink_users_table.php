<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('email_verification_token', 64)->nullable()->index();
                $table->string('google_id')->nullable()->index();
                $table->string('password');
                $table->string('phone', 30)->nullable();
                $table->string('phone_country_code', 10)->nullable()->default('+1');
                $table->timestamp('phone_verified_at')->nullable();
                $table->timestamp('terms_agreed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
            return;
        }

        // Table already exists (production may have a different base schema).
        // Add only the columns our app needs, without ->after() so column order
        // doesn't matter regardless of how the existing table was created.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verification_token')) {
                $table->string('email_verification_token', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable();
            }
            if (!Schema::hasColumn('users', 'phone_country_code')) {
                $table->string('phone_country_code', 10)->nullable()->default('+1');
            }
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'terms_agreed_at')) {
                $table->timestamp('terms_agreed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
