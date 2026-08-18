<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province', 2)->default('BC');
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('school_type', ['Elementary', 'Middle', 'Secondary', 'Other'])->default('Elementary');
            $table->string('district_name')->nullable();
            $table->unsignedSmallInteger('district_id')->nullable()->index();
            $table->string('facility_type')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['district_id', 'school_type']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
