<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_testimonials')) {
            return;
        }

        Schema::create('agent_testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('source', 50)->default('google');
            $table->string('external_id')->nullable();
            $table->string('author_name');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body')->nullable();
            $table->date('date')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_testimonials');
    }
};
