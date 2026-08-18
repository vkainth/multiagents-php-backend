<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid')->nullable()->index();
            $table->string('email', 191)->nullable()->index();
            $table->enum('type', ['search', 'building']);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('listing_ids')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
    }
};
