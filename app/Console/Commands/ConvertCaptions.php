<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Illuminate\Support\Facades\DB;

class ConvertCaptions extends Command
{
    protected $signature = 'captions:convert {--dry-run : Preview changes without saving}';
    protected $description = 'Convert WordPress [caption] shortcodes to clean HTML figure tags';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - изменения не будут сохранены');
        }
        
        $this->info('🔍 Поиск постов с [caption] shortcodes...');
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_content', 'LIKE', '%[caption%')
            ->get();
        
        $totalPosts = $posts->count();
        $this->info("📊 Найдено постов: {$totalPosts}");
        
        if ($totalPosts === 0) {
            $this->info('✅ Нет постов для обработки');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($totalPosts);
        $bar->start();
        
        $convertedPosts = 0;
        $convertedCaptions = 0;
        
        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $newContent = $this->convertCaptionsToHtml($originalContent);
            
            if ($originalContent !== $newContent) {
                $convertedPosts++;
                $captionCount = substr_count($originalContent, '[caption');
                $convertedCaptions += $captionCount;
                
                if (!$dryRun) {
                    DB::table('wp_posts')
                        ->where('ID', $post->ID)
                        ->update(['post_content' => $newContent]);
                }
                
                // Показываем первые 3 примера
                if ($convertedPosts <= 3) {
                    $this->newLine(2);
                    $this->info("📄 Пост #{$post->ID}: {$post->post_title}");
                    $this->line("   Найдено caption: {$captionCount}");
                    
                    // Показываем пример преобразования
                    if (preg_match('/\[caption[^\]]*\].*?\[\/caption\]/s', $originalContent, $matches)) {
                        $this->line("   Было: " . substr($matches[0], 0, 100) . '...');
                        
                        if (preg_match('/<figure class="wp-caption[^>]*>.*?<\/figure>/s', $newContent, $newMatches)) {
                            $this->line("   Стало: " . substr($newMatches[0], 0, 100) . '...');
                        }
                    }
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('📊 РЕЗУЛЬТАТЫ:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Обработано постов', $totalPosts],
                ['Изменено постов', $convertedPosts],
                ['Конвертировано caption', $convertedCaptions],
                ['Статус', $dryRun ? '🔍 Тест (не сохранено)' : '✅ Сохранено в БД'],
            ]
        );
        
        if ($dryRun) {
            $this->warn('⚠️  Это был тест. Запустите без --dry-run для сохранения изменений:');
            $this->line('   php artisan captions:convert');
        } else {
            $this->info('✅ Все caption shortcodes успешно преобразованы!');
        }
        
        return 0;
    }
    
    /**
     * Convert WordPress caption shortcodes to HTML figure tags
     */
    private function convertCaptionsToHtml(string $content): string
    {
        // Паттерн для поиска [caption] shortcodes
        $pattern = '/\[caption[^\]]*\](.*?)\[\/caption\]/s';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $captionContent = $matches[1];
            
            // Извлекаем align из shortcode
            $align = 'alignnone';
            if (preg_match('/align="([^"]+)"/', $matches[0], $alignMatch)) {
                $align = $alignMatch[1];
            }
            
            // Извлекаем width из shortcode
            $width = '';
            if (preg_match('/width="([^"]+)"/', $matches[0], $widthMatch)) {
                $width = $widthMatch[1];
            }
            
            // Извлекаем img tag
            $imgTag = '';
            if (preg_match('/<img[^>]+>/i', $captionContent, $imgMatch)) {
                $imgTag = $imgMatch[0];
            }
            
            // Извлекаем текст подписи (всё после img тега)
            $captionText = trim(preg_replace('/<img[^>]+>/i', '', $captionContent));
            
            // Формируем красивый HTML
            $html = '<figure class="wp-caption ' . $align . '"';
            if ($width) {
                $html .= ' style="max-width: ' . $width . 'px"';
            }
            $html .= '>';
            $html .= "\n  " . $imgTag;
            
            if ($captionText) {
                $html .= "\n  " . '<figcaption class="wp-caption-text">' . $captionText . '</figcaption>';
            }
            
            $html .= "\n" . '</figure>';
            
            return $html;
        }, $content);
        
        return $content;
    }
}














