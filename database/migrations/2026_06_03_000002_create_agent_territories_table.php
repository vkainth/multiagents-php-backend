<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_territories')) {
            return;
        }

        Schema::create('agent_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('city');
            $table->string('subarea')->nullable();
            $table->string('board')->nullable();
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_territories');
    }
};
