<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Services\SeoService;

class ImportYoastSeo extends Command
{
    protected $signature = 'seo:import-yoast {--type=all : Type of content to import (all, posts, pages)}';
    protected $description = 'Import Yoast SEO data from WordPress postmeta';

    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        parent::__construct();
        $this->seoService = $seoService;
    }

    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  ИМПОРТ YOAST SEO ДАННЫХ');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $type = $this->option('type');
        
        $query = Post::where('post_status', 'publish');
        
        if ($type === 'posts') {
            $query->where('post_type', 'post');
        } elseif ($type === 'pages') {
            $query->where('post_type', 'page');
        } else {
            $query->whereIn('post_type', ['post', 'page']);
        }
        
        $posts = $query->with('meta')->get();
        
        $this->info("Найдено элементов для импорта: " . $posts->count());
        $this->newLine();
        
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($posts as $post) {
            if ($this->seoService->importYoastSeo($post)) {
                $imported++;
            } else {
                $skipped++;
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  РЕЗУЛЬТАТЫ');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->line("✅ Импортировано: <fg=green>{$imported}</>");
        $this->line("⏭️  Пропущено (нет данных): <fg=yellow>{$skipped}</>");
        $this->line("📊 Всего обработано: " . $posts->count());
        
        $this->newLine();
        $this->info('Импорт завершен успешно! 🎉');
        $this->newLine();
        
        // Показываем примеры импортированных данных
        $example = Post::where('post_status', 'publish')
            ->where('post_type', 'post')
            ->whereNotNull('seo_title')
            ->first();
            
        if ($example) {
            $this->info('Пример импортированной записи:');
            $this->newLine();
            $this->line("Заголовок: {$example->post_title}");
            $this->line("SEO Title: {$example->seo_title}");
            $this->line("SEO Description: " . ($example->seo_description ? substr($example->seo_description, 0, 100) . '...' : 'н/д'));
            $this->line("Focus Keyword: " . ($example->focus_keyword ?: 'н/д'));
            $this->newLine();
        }
        
        return 0;
    }
}
