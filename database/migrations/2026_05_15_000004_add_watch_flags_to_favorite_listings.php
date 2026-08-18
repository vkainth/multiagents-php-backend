<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorite_listings', function (Blueprint $table) {
            $table->boolean('watch_price_drop')->default(false)->after('deleted');
            $table->boolean('watch_sold')->default(false)->after('watch_price_drop');
        });
    }

    public function down(): void
    {
        Schema::table('favorite_listings', function (Blueprint $table) {
            $table->dropColumn(['watch_price_drop', 'watch_sold']);
        });
    }
};
