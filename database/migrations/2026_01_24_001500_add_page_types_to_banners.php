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
        Schema::table('banners', function (Blueprint $table) {
            // Добавляем настройки отображения на разных типах страниц
            $table->boolean('show_on_home')->default(true)->after('status'); // Показывать на главной
            $table->boolean('show_on_category')->default(true)->after('show_on_home'); // Показывать на страницах категорий
            $table->boolean('show_on_post')->default(true)->after('show_on_category'); // Показывать на страницах статей
            $table->boolean('show_on_other')->default(true)->after('show_on_post'); // Показывать на остальных страницах
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['show_on_home', 'show_on_category', 'show_on_post', 'show_on_other']);
        });
    }
};
