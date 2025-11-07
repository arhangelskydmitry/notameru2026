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
        // Таблица баннеров
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название баннера
            $table->enum('type', ['image', 'html', 'js'])->default('image'); // Тип баннера
            $table->text('content')->nullable(); // URL изображения или HTML/JS код
            $table->string('link_url', 500)->nullable(); // Ссылка при клике
            $table->string('zone', 50); // Зона размещения (header, sidebar, content)
            $table->integer('priority')->default(5); // Приоритет (1-10, больше = выше)
            $table->date('start_date')->nullable(); // Дата начала показа
            $table->date('end_date')->nullable(); // Дата окончания показа
            $table->enum('status', ['active', 'paused', 'expired'])->default('active');
            $table->boolean('target_blank')->default(true); // Открывать в новом окне
            $table->integer('width')->nullable(); // Ширина баннера (пиксели)
            $table->integer('height')->nullable(); // Высота баннера (пиксели)
            $table->timestamps();
            
            // Индексы
            $table->index('zone');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
        
        // Таблица статистики баннеров
        Schema::create('banner_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Дата статистики
            $table->integer('impressions')->default(0); // Показы
            $table->integer('clicks')->default(0); // Клики
            $table->integer('unique_impressions')->default(0); // Уникальные показы
            $table->integer('unique_clicks')->default(0); // Уникальные клики
            $table->decimal('ctr', 5, 2)->default(0); // CTR (%)
            $table->timestamps();
            
            // Индексы
            $table->unique(['banner_id', 'date']);
            $table->index('date');
        });
        
        // Таблица зон баннеров
        Schema::create('banner_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // Название зоны (header, sidebar-top)
            $table->string('display_name', 100); // Отображаемое название
            $table->text('description')->nullable(); // Описание зоны
            $table->integer('max_banners')->default(1); // Максимум баннеров для ротации
            $table->integer('recommended_width')->nullable(); // Рекомендуемая ширина
            $table->integer('recommended_height')->nullable(); // Рекомендуемая высота
            $table->timestamps();
        });
        
        // Таблица для отслеживания показов (для уникальности)
        Schema::create('banner_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45); // IP адрес посетителя
            $table->string('user_agent', 255)->nullable(); // User agent
            $table->enum('action', ['impression', 'click']); // Действие
            $table->timestamp('created_at');
            
            // Индексы
            $table->index(['banner_id', 'ip_address', 'created_at']);
            $table->index('created_at');
        });
        
        // Вставляем стандартные зоны
        DB::table('banner_zones')->insert([
            [
                'name' => 'header',
                'display_name' => 'Шапка сайта',
                'description' => 'Баннер в шапке сайта, отображается на всех страницах',
                'max_banners' => 1,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sidebar-top',
                'display_name' => 'Сайдбар (верх)',
                'description' => 'Баннер в верхней части сайдбара',
                'max_banners' => 1,
                'recommended_width' => 300,
                'recommended_height' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sidebar-middle',
                'display_name' => 'Сайдбар (середина)',
                'description' => 'Баннер в середине сайдбара',
                'max_banners' => 1,
                'recommended_width' => 300,
                'recommended_height' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'content-top',
                'display_name' => 'Контент (верх)',
                'description' => 'Баннер над контентом статьи',
                'max_banners' => 1,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'content-middle',
                'display_name' => 'Контент (середина)',
                'description' => 'Баннер в середине контента статьи',
                'max_banners' => 1,
                'recommended_width' => 336,
                'recommended_height' => 280,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'footer',
                'display_name' => 'Подвал сайта',
                'description' => 'Баннер в подвале сайта',
                'max_banners' => 1,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_views');
        Schema::dropIfExists('banner_stats');
        Schema::dropIfExists('banner_zones');
        Schema::dropIfExists('banners');
    }
};
