<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ConvertWithTranslit extends Command
{
    protected $signature = 'images:convert-translit {--limit=300 : Количество изображений}';
    protected $description = 'Конвертирует изображения с транслитерацией имён файлов';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info('🔍 Поиск постов с изображениями notame.ru...');
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_excerpt', 'LIKE', '%notame.ru%');
            })
            ->limit(100)
            ->get();
        
        $this->info("Найдено постов: {$posts->count()}");
        
        if ($posts->count() === 0) {
            $this->info('✅ Все изображения обработаны!');
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
            
            preg_match_all('/<img[^>]+src=["\']([^"\'>]+)["\']/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $imgUrl) {
                    if (strpos($imgUrl, 'notame.ru') !== false) {
                        $imageUrls[] = $imgUrl;
                        $postImageUrls[] = $imgUrl;
                    }
                }
            }
            
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
        
        $this->info("Изображений для обработки: " . count($imageUrls));
        
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
            
            $sourceFile = $sourceDir . $path;
            
            if (!file_exists($sourceFile)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Транслитерируем имя файла
            $pathInfo = pathinfo($path);
            $originalName = $pathInfo['filename'];
            $translitName = $this->transliterate($originalName);
            $newFilename = $translitName . '.webp';
            $targetFile = $targetDir . '/' . $newFilename;
            
            // Пропускаем если существует
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
                $originalName = $pathInfo['filename'];
                $translitName = $this->transliterate($originalName);
                $newFilename = $translitName . '.webp';
                $newUrl = '/imgnews/' . $newFilename;
                
                // Проверяем что файл создан
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
        
        $this->info("Обновлено постов: $updated");
        
        $remaining = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_excerpt', 'LIKE', '%notame.ru%');
            })
            ->count();
        
        $this->info("\n📊 Осталось постов с notame.ru: $remaining");
        
        if ($remaining > 0) {
            $this->info("💡 Запустите команду снова");
        } else {
            $this->info("🎉 Все изображения сконвертированы!");
        }
        
        return 0;
    }
    
    /**
     * Транслитерация кириллицы
     */
    private function transliterate($string)
    {
        $cyrillic = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];
        
        $latin = [
            'a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
            'A','B','V','G','D','E','Yo','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sch','','Y','','E','Yu','Ya'
        ];
        
        $result = str_replace($cyrillic, $latin, $string);
        
        // Очищаем от недопустимых символов
        $result = preg_replace('/[^a-zA-Z0-9_-]/', '-', $result);
        $result = preg_replace('/-+/', '-', $result);
        $result = trim($result, '-');
        
        return $result;
    }
}














