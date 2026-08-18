<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_features')) {
            return;
        }

        Schema::create('agent_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('feature_key', 60);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['agent_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_features');
    }
};
