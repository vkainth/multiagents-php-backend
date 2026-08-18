<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_mls_ids')) {
            return;
        }

        Schema::create('agent_mls_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('mls_id', 50);
            $table->index('agent_id');
            $table->index('mls_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_mls_ids');
    }
};
