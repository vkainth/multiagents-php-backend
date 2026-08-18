<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_lead_sms_log')) {
            return;
        }

        Schema::create('agent_lead_sms_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_lead_id')->constrained('agent_leads')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('to_phone', 30);
            $table->text('message');
            $table->string('status', 20)->default('sent'); // sent, failed
            $table->string('twilio_sid')->nullable();
            $table->timestamps();
            $table->index('agent_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_lead_sms_log');
    }
};
