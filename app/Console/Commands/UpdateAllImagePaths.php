<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Illuminate\Support\Facades\DB;

class UpdateAllImagePaths extends Command
{
    protected $signature = 'images:update-all-paths {--dry-run : Показать изменения без сохранения}';
    protected $description = 'Обновляет все пути к изображениям с notame.ru на локальные WebP';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  РЕЖИМ СИМУЛЯЦИИ - изменения не будут сохранены');
        }
        
        $this->info('🔍 Поиск постов с изображениями notame.ru...');
        
        // Находим все посты с изображениями notame.ru
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_excerpt', 'LIKE', '%notame.ru%');
            })
            ->get();
        
        $this->info("Найдено постов: {$posts->count()}");
        
        if ($posts->count() === 0) {
            $this->info('✅ Нет постов для обновления!');
            return 0;
        }
        
        $updated = 0;
        $failed = 0;
        $totalReplacements = 0;
        
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $originalExcerpt = $post->post_excerpt;
            
            $newContent = $originalContent;
            $newExcerpt = $originalExcerpt;
            $replacements = 0;
            
            // Находим все URL изображений
            preg_match_all('/(https?:\/\/notame\.ru\/wp-content\/uploads\/[^"\'\s>]+\.(jpg|jpeg|png|gif|webp))/i', $originalContent, $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $oldUrl) {
                    $path = parse_url($oldUrl, PHP_URL_PATH);
                    if ($path) {
                        $pathInfo = pathinfo($path);
                        $newFilename = $pathInfo['filename'] . '.webp';
                        $newUrl = '/imgnews/' . $newFilename;
                        
                        // Заменяем старый URL на новый
                        $newContent = str_replace($oldUrl, $newUrl, $newContent);
                        $replacements++;
                    }
                }
            }
            
            // Обрабатываем excerpt
            preg_match_all('/(https?:\/\/notame\.ru\/wp-content\/uploads\/[^"\'\s>]+\.(jpg|jpeg|png|gif|webp))/i', $originalExcerpt, $matchesExcerpt);
            
            if (!empty($matchesExcerpt[0])) {
                foreach ($matchesExcerpt[0] as $oldUrl) {
                    $path = parse_url($oldUrl, PHP_URL_PATH);
                    if ($path) {
                        $pathInfo = pathinfo($path);
                        $newFilename = $pathInfo['filename'] . '.webp';
                        $newUrl = '/imgnews/' . $newFilename;
                        
                        $newExcerpt = str_replace($oldUrl, $newUrl, $newExcerpt);
                        $replacements++;
                    }
                }
            }
            
            // Сохраняем, если были изменения
            if ($newContent !== $originalContent || $newExcerpt !== $originalExcerpt) {
                if (!$dryRun) {
                    try {
                        $post->post_content = $newContent;
                        $post->post_excerpt = $newExcerpt;
                        $post->save();
                        $updated++;
                        $totalReplacements += $replacements;
                    } catch (\Exception $e) {
                        $failed++;
                        $this->error("\nОшибка при обновлении поста {$post->ID}: {$e->getMessage()}");
                    }
                } else {
                    $updated++;
                    $totalReplacements += $replacements;
                    
                    if ($updated <= 5) {
                        $this->line("\n\nПример изменений в посте {$post->ID} ({$post->post_title}):");
                        $this->line("  Замен: $replacements");
                    }
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Обновление завершено!");
        $this->info("Обновлено постов: $updated");
        $this->info("Всего замен: $totalReplacements");
        
        if ($failed > 0) {
            $this->warn("Ошибок: $failed");
        }
        
        if ($dryRun) {
            $this->warn("\n⚠️  Это была симуляция. Запустите без --dry-run для реального обновления:");
            $this->info("php artisan images:update-all-paths");
        } else {
            // Проверяем результат
            $remaining = Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->where(function($query) {
                    $query->where('post_content', 'LIKE', '%notame.ru/wp-content/uploads%')
                          ->orWhere('post_excerpt', 'LIKE', '%notame.ru/wp-content/uploads%');
                })
                ->count();
            
            $this->info("\n📊 Осталось постов с notame.ru: $remaining");
            
            if ($remaining === 0) {
                $this->info("🎉 Все пути успешно обновлены!");
            } else {
                $this->warn("💡 Некоторые посты всё ещё содержат ссылки на notame.ru");
                $this->info("Возможно, это изображения с других доменов или специальные случаи");
            }
        }
        
        return 0;
    }
}




