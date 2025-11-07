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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('type');
            $table->unsignedBigInteger('page_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('custom_page_id')->nullable()->after('page_id');
            
            // Индексы для внешних ключей
            $table->index('category_id');
            $table->index('page_id');
            $table->index('custom_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['page_id']);
            $table->dropIndex(['custom_page_id']);
            $table->dropColumn(['category_id', 'page_id', 'custom_page_id']);
        });
    }
};
