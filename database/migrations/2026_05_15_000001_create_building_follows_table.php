<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid')->nullable()->index();
            $table->string('email', 191)->nullable()->index();
            $table->string('building_slug', 191)->nullable()->index();
            $table->string('building_name', 191)->nullable();
            $table->string('street_no', 20)->nullable();
            $table->string('street_name', 191)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('strata_no', 50)->nullable();
            $table->tinyInteger('confirmed')->default(0)->index();
            $table->tinyInteger('active')->default(1)->index();
            $table->string('confirmation_token', 100)->nullable()->unique();
            $table->string('manage_token', 100)->nullable()->index();
            $table->timestamp('last_update_sent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_follows');
    }
};
