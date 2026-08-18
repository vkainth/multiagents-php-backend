<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_leads')) {
            return;
        }

        Schema::create('agent_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('form_type', 30)->default('w1'); // w1=showing, w2=home_eval, w3=prequal
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source_url')->nullable();
            $table->string('listing_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->index('agent_id');
            $table->index(['agent_id', 'form_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_leads');
    }
};
