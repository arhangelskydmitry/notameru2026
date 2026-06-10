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
        // Таблица ролей
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, editor, author
            $table->string('display_name'); // Суперадминистратор, Главный редактор, Автор
            $table->text('description')->nullable();
            $table->integer('level')->default(0); // Уровень иерархии (чем выше, тем больше прав)
            $table->timestamps();
        });

        // Таблица прав доступа
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // edit_posts, manage_users, view_analytics
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // general, posts, users, settings
            $table->timestamps();
        });

        // Связь ролей и прав (многие ко многим)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // Связь пользователей и ролей (добавляем к существующей таблице wp_users)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID из wp_users
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('position')->nullable(); // Должность (Директор, Журналист, и т.д.)
            $table->json('custom_permissions')->nullable(); // Дополнительные права для конкретного пользователя
            $table->json('allowed_categories')->nullable(); // Разрешенные категории для редактирования
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
        });

        // Таблица истории действий (audit log)
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // created, updated, deleted, login, logout
            $table->string('model_type')->nullable(); // App\Models\WordPress\Post
            $table->unsignedBigInteger('model_id')->nullable(); // ID записи
            $table->text('description')->nullable();
            $table->json('properties')->nullable(); // Что изменилось
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        // Таблица статистики авторов
        Schema::create('author_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('total_posts')->default(0);
            $table->integer('published_posts')->default(0);
            $table->integer('draft_posts')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('total_comments')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('this_month_posts')->default(0);
            $table->integer('this_week_posts')->default(0);
            $table->date('last_post_date')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_statistics');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
