<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ConvertImagesToWebP extends Command
{
    protected $signature = 'images:convert-to-webp {--analyze : Только анализ без конвертации}';
    protected $description = 'Конвертирует изображения из notame.ru в WebP и обновляет базу данных';

    public function handle()
    {
        $analyzeOnly = $this->option('analyze');
        
        $this->info('🔍 Анализ изображений в постах...');
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->get();
        
        $this->info("Найдено постов: {$posts->count()}");
        
        $imageUrls = [];
        $postImages = []; // Массив для хранения соответствий пост => изображения
        
        // Собираем все URL изображений
        foreach ($posts as $post) {
            $postImageUrls = [];
            
            // Ищем изображения в контенте
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false || strpos($imgUrl, 'http') === 0) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
            // Также проверяем post_excerpt
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_excerpt, $matchesExcerpt);
            if (!empty($matchesExcerpt[1])) {
                foreach ($matchesExcerpt[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false || strpos($imgUrl, 'http') === 0) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
            if (!empty($postImageUrls)) {
                $postImages[$post->ID] = $postImageUrls;
            }
        }
        
        $imageUrls = array_unique($imageUrls);
        $this->info("Найдено уникальных изображений: " . count($imageUrls));
        
        if ($analyzeOnly) {
            $this->info("\n📊 Примеры найденных изображений:");
            foreach (array_slice($imageUrls, 0, 10) as $url) {
                $this->line("  - " . $url);
            }
            $this->info("\nЗапустите без флага --analyze для конвертации");
            return 0;
        }
        
        // Конвертация изображений
        $this->info("\n🔄 Начинаем конвертацию...");
        
        $sourceDir = '/Users/mac/Sites/notame.ru';
        $targetDir = public_path('imgnews');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $converted = 0;
        $skipped = 0;
        $errors = 0;
        
        $bar = $this->output->createProgressBar(count($imageUrls));
        $bar->start();
        
        foreach ($imageUrls as $url) {
            // Извлекаем путь к файлу из URL
            $path = parse_url($url, PHP_URL_PATH);
            
            if (!$path) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Определяем исходный файл
            $sourceFile = $sourceDir . $path;
            
            if (!file_exists($sourceFile)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Генерируем имя для WebP файла
            $pathInfo = pathinfo($path);
            $newFilename = $pathInfo['filename'] . '.webp';
            $targetFile = $targetDir . '/' . $newFilename;
            
            try {
                // Конвертируем в WebP
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sourceFile);
                
                // Масштабируем, если изображение слишком большое
                if ($image->width() > 1200 || $image->height() > 800) {
                    $image->scale(width: 1200);
                }
                
                // Сохраняем в WebP с качеством 85%
                $image->toWebp(85)->save($targetFile);
                
                $converted++;
            } catch (\Exception $e) {
                $this->error("\nОшибка при конвертации $sourceFile: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Конвертация завершена!");
        $this->info("Конвертировано: $converted");
        $this->info("Пропущено: $skipped");
        $this->info("Ошибок: $errors");
        
        // Обновление базы данных
        $this->info("\n🔄 Обновление базы данных...");
        
        $updated = 0;
        foreach ($postImages as $postId => $urls) {
            $post = Post::find($postId);
            if (!$post) continue;
            
            $content = $post->post_content;
            $excerpt = $post->post_excerpt;
            
            foreach ($urls as $oldUrl) {
                $path = parse_url($oldUrl, PHP_URL_PATH);
                if (!$path) continue;
                
                $pathInfo = pathinfo($path);
                $newFilename = $pathInfo['filename'] . '.webp';
                $newUrl = '/imgnews/' . $newFilename;
                
                // Заменяем в контенте
                $content = str_replace($oldUrl, $newUrl, $content);
                $excerpt = str_replace($oldUrl, $newUrl, $excerpt);
            }
            
            if ($content !== $post->post_content || $excerpt !== $post->post_excerpt) {
                $post->post_content = $content;
                $post->post_excerpt = $excerpt;
                $post->save();
                $updated++;
            }
        }
        
        $this->info("Обновлено постов в БД: $updated");
        $this->info("\n🎉 Готово! Изображения конвертированы и база данных обновлена.");
        
        return 0;
    }
}

