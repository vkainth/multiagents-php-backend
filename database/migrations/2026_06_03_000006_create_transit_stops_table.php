<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transit_stops', function (Blueprint $table) {
            $table->id();
            $table->string('stop_id')->index();
            $table->string('stop_name');
            $table->decimal('latitude',  10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('routes')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transit_stops ADD COLUMN location POINT NULL AFTER longitude');
            // SPATIAL INDEX requires NOT NULL in MySQL; lat/lng decimal columns used for queries instead.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transit_stops');
    }
};
