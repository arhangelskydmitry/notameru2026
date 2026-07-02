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
        // Таблица для отслеживания просмотров постов
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('viewed_at');
            
            $table->foreign('post_id')->references('ID')->on('wp_posts')->onDelete('cascade');
            $table->index(['post_id', 'viewed_at']);
            $table->index('ip_address');
        });
        
        // Таблица для уникальных посетителей
        Schema::create('site_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->date('visit_date');
            $table->integer('page_views')->default(1);
            $table->timestamp('first_visit');
            $table->timestamp('last_visit');
            
            $table->unique(['ip_address', 'visit_date']);
            $table->index('visit_date');
        });
        
        // Таблица для общей статистики сайта
        Schema::create('site_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('posts_published')->default(0);
            $table->json('popular_posts')->nullable();
            
            $table->unique('date');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_views');
        Schema::dropIfExists('site_visitors');
        Schema::dropIfExists('site_statistics');
    }
};

