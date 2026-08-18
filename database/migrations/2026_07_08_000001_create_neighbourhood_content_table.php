<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neighbourhood_content')) {
            if (!Schema::hasColumn('neighbourhood_content', 'pulse_body')) {
                Schema::table('neighbourhood_content', function (Blueprint $table) {
                    $table->longText('pulse_body')->nullable()->after('lifestyle_body');
                    $table->timestamp('pulse_generated_at')->nullable()->after('pulse_body');
                });
            }
            return;
        }

        Schema::create('neighbourhood_content', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->index();
            $table->string('subarea', 100)->index();
            $table->longText('lifestyle_body')->nullable();
            $table->timestamp('lifestyle_generated_at')->nullable();
            $table->longText('pulse_body')->nullable();
            $table->timestamp('pulse_generated_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_id', 'subarea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighbourhood_content');
    }
};
