<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_password_reset_tokens')) {
            return;
        }

        Schema::create('user_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token', 64);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_password_reset_tokens');
    }
};
