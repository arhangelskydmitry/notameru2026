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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название пункта меню
            $table->string('slug'); // Slug категории или URL
            $table->string('type')->default('category'); // category, page, url
            $table->integer('order')->default(0); // Порядок отображения
            $table->boolean('is_active')->default(true); // Активен/неактивен
            $table->timestamps();
            
            $table->index('order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
