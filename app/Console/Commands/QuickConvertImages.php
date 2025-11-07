<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class QuickConvertImages extends Command
{
    protected $signature = 'images:quick-convert {--limit=100 : Количество изображений для конвертации}';
    protected $description = 'Быстрая конвертация изображений (пакетами)';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("🔄 Конвертация $limit изображений...");
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->limit(50) // Берём первые 50 постов
            ->get();
        
        $sourceDir = '/Users/mac/Sites/notame.ru';
        $targetDir = public_path('imgnews');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $imageUrls = [];
        $postImages = [];
        
        // Собираем изображения из первых 50 постов
        foreach ($posts as $post) {
            $postImageUrls = [];
            
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
            if (!empty($postImageUrls)) {
                $postImages[$post->ID] = [
                    'urls' => $postImageUrls,
                    'post' => $post
                ];
            }
        }
        
        $imageUrls = array_unique($imageUrls);
        $imageUrls = array_slice($imageUrls, 0, $limit);
        
        $this->info("Найдено изображений для конвертации: " . count($imageUrls));
        
        $converted = 0;
        $skipped = 0;
        
        $bar = $this->output->createProgressBar(count($imageUrls));
        $bar->start();
        
        foreach ($imageUrls as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            
            if (!$path) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            $sourceFile = $sourceDir . $path;
            
            if (!file_exists($sourceFile)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            $pathInfo = pathinfo($path);
            $newFilename = $pathInfo['filename'] . '.webp';
            $targetFile = $targetDir . '/' . $newFilename;
            
            // Пропускаем, если файл уже существует
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
                $skipped++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Завершено!");
        $this->info("Конвертировано: $converted");
        $this->info("Пропущено: $skipped");
        
        // Обновление БД для обработанных постов
        $this->info("\n🔄 Обновление базы данных...");
        $updated = 0;
        
        foreach ($postImages as $data) {
            $post = $data['post'];
            $urls = $data['urls'];
            
            $content = $post->post_content;
            $changed = false;
            
            foreach ($urls as $oldUrl) {
                $path = parse_url($oldUrl, PHP_URL_PATH);
                if (!$path) continue;
                
                $pathInfo = pathinfo($path);
                $newFilename = $pathInfo['filename'] . '.webp';
                $newUrl = '/imgnews/' . $newFilename;
                
                // Проверяем, что файл был создан
                if (file_exists(public_path('imgnews/' . $newFilename))) {
                    $content = str_replace($oldUrl, $newUrl, $content);
                    $changed = true;
                }
            }
            
            if ($changed) {
                $post->post_content = $content;
                $post->save();
                $updated++;
            }
        }
        
        $this->info("Обновлено постов: $updated");
        $this->info("\n🎉 Готово!");
        
        return 0;
    }
}

