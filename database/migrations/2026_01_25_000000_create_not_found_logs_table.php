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
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->index();
            $table->string('referer', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('method', 10)->default('GET');
            $table->timestamp('created_at');
            
            // Индексы для быстрого поиска
            $table->index('created_at');
            $table->index(['url', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
