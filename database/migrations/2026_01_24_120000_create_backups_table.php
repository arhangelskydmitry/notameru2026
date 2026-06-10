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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->comment('Имя файла бекапа');
            $table->enum('type', ['full', 'database', 'files'])->default('full')->comment('Тип бекапа');
            $table->unsignedBigInteger('size')->default(0)->comment('Размер в байтах');
            $table->enum('status', ['in_progress', 'completed', 'failed'])->default('in_progress')->comment('Статус');
            $table->enum('storage', ['local', 'remote'])->default('local')->comment('Тип хранилища');
            $table->string('storage_path', 500)->nullable()->comment('Путь в хранилище');
            $table->string('triggered_by', 50)->default('auto')->comment('Кто запустил: auto, manual, user_id');
            $table->json('manifest')->nullable()->comment('Метаданные: таблицы, файлы, версия');
            $table->text('error_message')->nullable()->comment('Сообщение об ошибке');
            $table->timestamp('started_at')->nullable()->comment('Начало создания');
            $table->timestamp('completed_at')->nullable()->comment('Завершение создания');
            $table->timestamps();
            
            // Индексы
            $table->index('status');
            $table->index('created_at');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
