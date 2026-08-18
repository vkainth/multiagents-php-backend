<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_tokens')) {
            return;
        }

        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            // Plain bigint without a DB-level foreign key constraint so this
            // works regardless of whether users.id is INT or BIGINT UNSIGNED.
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
    }
};
