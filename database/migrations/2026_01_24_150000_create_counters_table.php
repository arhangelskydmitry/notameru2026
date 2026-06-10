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
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название счетчика (для админки)');
            $table->text('code')->comment('HTML код счетчика');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_active')->default(true)->comment('Активен ли счетчик');
            $table->string('position')->default('sidebar')->comment('Позиция: sidebar, footer, header');
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
