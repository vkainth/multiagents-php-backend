<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_boards';

    public function up(): void
    {
        Schema::connection('mysql_boards')->create('search_listings_seo_content', function (Blueprint $table) {
            $table->id();
            $table->string('city', 100)->default('');
            $table->string('subarea', 100)->default('');
            $table->string('type_slug', 50)->default('');
            $table->string('feature_slug', 50)->default('');
            $table->text('intro_text')->nullable();
            $table->text('local_facts')->nullable();
            $table->string('rental_estimate', 255)->nullable();
            $table->timestamps();

            $table->unique(['city', 'subarea', 'type_slug', 'feature_slug'], 'uq_city_subarea_type_feature');
            $table->index(['city', 'type_slug', 'feature_slug'], 'idx_city_type_feature');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_boards')->dropIfExists('search_listings_seo_content');
    }
};
