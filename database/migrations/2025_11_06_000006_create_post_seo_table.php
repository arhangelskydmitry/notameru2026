<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_seo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->unique();
            
            // Основные SEO поля
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 100)->default('index, follow');
            
            // Open Graph
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('og_type', 50)->default('article');
            $table->text('og_article_section')->nullable();
            $table->text('og_article_tags')->nullable();
            
            // Twitter Card
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 500)->nullable();
            
            // Дополнительные поля
            $table->text('focus_keywords')->nullable();
            $table->integer('readability_score')->nullable();
            $table->integer('seo_score')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->foreign('post_id')->references('ID')->on('wp_posts')->onDelete('cascade');
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_seo');
    }
};




