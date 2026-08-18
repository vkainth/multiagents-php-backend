<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            if (!Schema::hasColumn('saved_searches', 'confirmed')) {
                $table->tinyInteger('confirmed')->default(1)->after('last_update_sent');
            }
            if (!Schema::hasColumn('saved_searches', 'active')) {
                $table->tinyInteger('active')->default(1)->after('confirmed');
            }
            if (!Schema::hasColumn('saved_searches', 'confirmation_token')) {
                $table->string('confirmation_token', 100)->nullable()->after('active');
            }
            if (!Schema::hasColumn('saved_searches', 'manage_token')) {
                $table->string('manage_token', 100)->nullable()->after('confirmation_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropColumn(['confirmed', 'active', 'confirmation_token', 'manage_token']);
        });
    }
};
