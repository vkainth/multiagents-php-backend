<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'google_place_id')) {
                $table->string('google_place_id')->nullable()->after('intro_video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            $table->dropColumn('google_place_id');
        });
    }
};
