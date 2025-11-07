<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ConvertRemainingImages extends Command
{
    protected $signature = 'images:convert-remaining {--limit=200 : Количество изображений за раз}';
    protected $description = 'Конвертирует оставшиеся изображения с notame.ru';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info('🔍 Поиск постов с изображениями notame.ru...');
        
        // Находим посты, которые ещё содержат ссылки на notame.ru
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_excerpt', 'LIKE', '%notame.ru%');
            })
            ->limit(100) // Берём 100 постов за раз
            ->get();
        
        $this->info("Найдено постов с notame.ru: {$posts->count()}");
        
        if ($posts->count() === 0) {
            $this->info('✅ Все изображения уже сконвертированы!');
            return 0;
        }
        
        $sourceDir = '/Users/mac/Sites/notame.ru';
        $targetDir = public_path('imgnews');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $imageUrls = [];
        $postImages = [];
        
        // Собираем изображения
        foreach ($posts as $post) {
            $postImageUrls = [];
            
            // Ищем в контенте
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
            // Ищем в excerpt
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_excerpt, $matchesExcerpt);
            if (!empty($matchesExcerpt[1])) {
                foreach ($matchesExcerpt[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
            if (!empty($postImageUrls)) {
                $postImages[$post->ID] = [
                    'urls' => array_unique($postImageUrls),
                    'post' => $post
                ];
            }
        }
        
        $imageUrls = array_unique($imageUrls);
        $imageUrls = array_slice($imageUrls, 0, $limit);
        
        $this->info("Изображений для конвертации: " . count($imageUrls));
        
        $converted = 0;
        $skipped = 0;
        $errors = 0;
        
        $bar = $this->output->createProgressBar(count($imageUrls));
        $bar->start();
        
        foreach ($imageUrls as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            
            if (!$path) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Исходный файл
            $sourceFile = $sourceDir . $path;
            
            if (!file_exists($sourceFile)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Имя WebP файла
            $pathInfo = pathinfo($path);
            $newFilename = $pathInfo['filename'] . '.webp';
            $targetFile = $targetDir . '/' . $newFilename;
            
            // Пропускаем, если уже существует
            if (file_exists($targetFile)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sourceFile);
                
                if ($image->width() > 1200 || $image->height() > 800) {
                    $image->scale(width: 1200);
                }
                
                $image->toWebp(85)->save($targetFile);
                $converted++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nОшибка: {$sourceFile} - {$e->getMessage()}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Конвертация завершена!");
        $this->info("Конвертировано: $converted");
        $this->info("Пропущено: $skipped");
        $this->info("Ошибок: $errors");
        
        // Обновление БД
        $this->info("\n🔄 Обновление базы данных...");
        $updated = 0;
        
        foreach ($postImages as $data) {
            $post = $data['post'];
            $urls = $data['urls'];
            
            $content = $post->post_content;
            $excerpt = $post->post_excerpt;
            $changed = false;
            
            foreach ($urls as $oldUrl) {
                $path = parse_url($oldUrl, PHP_URL_PATH);
                if (!$path) continue;
                
                $pathInfo = pathinfo($path);
                $newFilename = $pathInfo['filename'] . '.webp';
                $newUrl = '/imgnews/' . $newFilename;
                
                // Проверяем, что файл создан
                if (file_exists(public_path('imgnews/' . $newFilename))) {
                    $content = str_replace($oldUrl, $newUrl, $content);
                    $excerpt = str_replace($oldUrl, $newUrl, $excerpt);
                    $changed = true;
                }
            }
            
            if ($changed) {
                $post->post_content = $content;
                $post->post_excerpt = $excerpt;
                $post->save();
                $updated++;
            }
        }
        
        $this->info("Обновлено постов в БД: $updated");
        
        // Проверяем, сколько ещё осталось
        $remaining = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_excerpt', 'LIKE', '%notame.ru%');
            })
            ->count();
        
        $this->info("\n📊 Осталось постов с notame.ru: $remaining");
        
        if ($remaining > 0) {
            $this->info("💡 Запустите команду снова для продолжения конвертации");
        } else {
            $this->info("🎉 Все изображения успешно сконвертированы!");
        }
        
        return 0;
    }
}




