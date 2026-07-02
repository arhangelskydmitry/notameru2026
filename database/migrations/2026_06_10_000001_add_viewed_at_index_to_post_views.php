<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Покрывающий индекс для запроса «популярные посты за период»:
 * WHERE viewed_at >= ? GROUP BY post_id — без него полный скан ~1.9 млн строк (~1.1 c).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->index(['viewed_at', 'post_id'], 'post_views_viewed_at_post_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex('post_views_viewed_at_post_id_index');
        });
    }
};
