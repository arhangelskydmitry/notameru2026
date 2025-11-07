<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем существование таблицы wp_posts
        if (!Schema::hasTable('wp_posts')) {
            return; // Таблица не существует, пропускаем миграцию
        }
        
        // Временно отключаем strict mode только для MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
        DB::statement('SET SESSION sql_mode = ""');
        }
        
        Schema::table('wp_posts', function (Blueprint $table) {
            // SEO Meta поля
            if (!Schema::hasColumn('wp_posts', 'seo_title')) {
            $table->string('seo_title', 255)->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'seo_description')) {
            $table->text('seo_description')->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'seo_keywords')) {
            $table->text('seo_keywords')->nullable();
            }
            
            // Open Graph
            if (!Schema::hasColumn('wp_posts', 'og_title')) {
            $table->string('og_title', 255)->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'og_description')) {
            $table->text('og_description')->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'og_image')) {
            $table->string('og_image', 500)->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'og_type')) {
            $table->string('og_type', 50)->default('article');
            }
            
            // Twitter Card
            if (!Schema::hasColumn('wp_posts', 'twitter_card')) {
            $table->string('twitter_card', 50)->default('summary_large_image');
            }
            if (!Schema::hasColumn('wp_posts', 'twitter_title')) {
            $table->string('twitter_title', 255)->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'twitter_description')) {
            $table->text('twitter_description')->nullable();
            }
            if (!Schema::hasColumn('wp_posts', 'twitter_image')) {
            $table->string('twitter_image', 500)->nullable();
            }
            
            // Canonical URL
            if (!Schema::hasColumn('wp_posts', 'canonical_url')) {
            $table->string('canonical_url', 500)->nullable();
            }
            
            // Meta Robots
            if (!Schema::hasColumn('wp_posts', 'meta_robots')) {
            $table->string('meta_robots', 100)->default('index, follow');
            }
            
            // Focus Keyword (для анализа контента)
            if (!Schema::hasColumn('wp_posts', 'focus_keyword')) {
            $table->string('focus_keyword', 255)->nullable();
            }
        });
            
        // Добавляем индексы только если колонки существуют
        if (Schema::hasColumn('wp_posts', 'seo_title') && 
            !$this->hasIndex('wp_posts', 'wp_posts_seo_title_index')) {
            Schema::table('wp_posts', function (Blueprint $table) {
            $table->index('seo_title');
            });
        }
        if (Schema::hasColumn('wp_posts', 'focus_keyword') && 
            !$this->hasIndex('wp_posts', 'wp_posts_focus_keyword_index')) {
            Schema::table('wp_posts', function (Blueprint $table) {
            $table->index('focus_keyword');
        });
        }
    }
    
    /**
     * Проверка существования индекса
     */
    private function hasIndex($table, $index)
    {
        try {
            $conn = Schema::getConnection();
            $dbSchemaManager = $conn->getDoctrineSchemaManager();
            $doctrineTable = $dbSchemaManager->introspectTable($table);
            return $doctrineTable->hasIndex($index);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wp_posts', function (Blueprint $table) {
            $table->dropIndex(['seo_title']);
            $table->dropIndex(['focus_keyword']);
            
            $table->dropColumn([
                'seo_title',
                'seo_description',
                'seo_keywords',
                'og_title',
                'og_description',
                'og_image',
                'og_type',
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'canonical_url',
                'meta_robots',
                'focus_keyword',
            ]);
        });
    }
};
