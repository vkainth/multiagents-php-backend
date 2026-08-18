<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_catchments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->enum('level', ['Elementary', 'Secondary', 'Middle'])->index();
            $table->unsignedSmallInteger('district_id')->nullable()->index();
            $table->string('catchment_name')->nullable();
            $table->longText('polygon_geojson')->nullable();
            $table->longText('polygon_wkt')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE school_catchments ADD COLUMN polygon_geom MULTIPOLYGON NULL AFTER polygon_wkt');
            // SPATIAL INDEX requires NOT NULL in MySQL; controller uses polygon_geojson text column instead.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_catchments');
    }
};
