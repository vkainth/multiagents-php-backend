<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->index();
            $table->string('form_type', 10)->index(); // w1, w2, w3
            $table->string('name', 120);
            $table->string('email', 180);
            $table->string('phone', 40)->nullable();
            $table->text('message')->nullable();
            $table->string('property_address', 300)->nullable();
            $table->string('property_type', 80)->nullable();
            $table->string('timeline', 80)->nullable();
            $table->string('budget', 80)->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('listing_slug', 200)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->boolean('sms_verified')->default(false);
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_leads');
    }
};
