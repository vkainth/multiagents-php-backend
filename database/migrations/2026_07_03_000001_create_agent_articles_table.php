<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_articles')) {
            return;
        }

        Schema::create('agent_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('category', 40)->default('market_update');
            $table->string('status', 20)->default('draft');
            $table->string('featured_image_url', 500)->nullable();
            $table->timestamp('ai_generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['agent_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_articles');
    }
};
