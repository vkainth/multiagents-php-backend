<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_ab_logs', function (Blueprint $table) {
            $table->id();
            $table->string('variant', 1);
            $table->string('event', 20);
            $table->string('listing_id', 50)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_ab_logs');
    }
};
