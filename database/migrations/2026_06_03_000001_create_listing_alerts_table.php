<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->index();
            $table->string('city', 100)->nullable()->index();
            $table->string('subarea', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_alerts');
    }
};
