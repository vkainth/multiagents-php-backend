<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_page_views')) {
            return;
        }

        Schema::create('agent_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
            $table->unique(['agent_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_page_views');
    }
};
