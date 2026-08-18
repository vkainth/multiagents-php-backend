<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_settings')) {
            return;
        }

        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->string('custom_domain')->nullable()->index();
            $table->string('notification_email')->nullable();
            $table->string('notification_phone', 30)->nullable();
            $table->json('featured_listing_ids')->nullable();
            $table->json('social_links')->nullable();
            $table->string('ga4_id', 30)->nullable();
            $table->string('fb_pixel_id', 30)->nullable();
            $table->boolean('fub_enabled')->default(false);
            $table->text('fub_api_key')->nullable();
            $table->json('lead_routing')->nullable();
            $table->string('intro_video_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_settings');
    }
};
