<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;

class FixImagePathsTranslit extends Command
{
    protected $signature = 'images:fix-paths';
    protected $description = 'Исправляет пути к изображениям с транслитерацией';

    public function handle()
    {
        $this->info('🔧 Исправление путей к изображениям...');
        
        // Найти все посты с изображениями (включая уже частично обработанные)
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%notame.ru%')
                      ->orWhere('post_content', 'LIKE', '%/imgnews/%');
            })
            ->get();
        
        $this->info("Найдено постов: {$posts->count()}");
        
        $updated = 0;
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        foreach ($posts as $post) {
            $content = $post->post_content;
            $excerpt = $post->post_excerpt;
            $changed = false;
            
            // Находим все изображения notame.ru
            preg_match_all('/(https?:\/\/notame\.ru\/wp-content\/uploads\/[^"\'\s>]+\.(jpg|jpeg|png|gif))/i', $content, $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $oldUrl) {
                    $path = parse_url($oldUrl, PHP_URL_PATH);
                    if ($path) {
                        $pathInfo = pathinfo($path);
                        $originalName = $pathInfo['filename'];
                        $translitName = $this->transliterate($originalName);
                        $newUrl = '/imgnews/' . $translitName . '.webp';
                        
                        $content = str_replace($oldUrl, $newUrl, $content);
                        $changed = true;
                    }
                }
            }
            
            // Также обрабатываем уже созданные /imgnews/ с кириллицей
            preg_match_all('/src="\/imgnews\/([^"]+)"/', $content, $imgNewsMatches);
            
            if (!empty($imgNewsMatches[1])) {
                foreach ($imgNewsMatches[1] as $filename) {
                    // Если в имени файла есть не-ASCII символы
                    if (preg_match('/[^\x20-\x7E]/', $filename)) {
                        $pathInfo = pathinfo($filename);
                        $translitName = $this->transliterate($pathInfo['filename']);
                        $newFilename = $translitName . '.webp';
                        
                        $content = str_replace('/imgnews/' . $filename, '/imgnews/' . $newFilename, $content);
                        $changed = true;
                    }
                }
            }
            
            // Обрабатываем excerpt
            if ($excerpt) {
                preg_match_all('/(https?:\/\/notame\.ru\/wp-content\/uploads\/[^"\'\s>]+\.(jpg|jpeg|png|gif))/i', $excerpt, $matchesExcerpt);
                
                if (!empty($matchesExcerpt[0])) {
                    foreach ($matchesExcerpt[0] as $oldUrl) {
                        $path = parse_url($oldUrl, PHP_URL_PATH);
                        if ($path) {
                            $pathInfo = pathinfo($path);
                            $originalName = $pathInfo['filename'];
                            $translitName = $this->transliterate($originalName);
                            $newUrl = '/imgnews/' . $translitName . '.webp';
                            
                            $excerpt = str_replace($oldUrl, $newUrl, $excerpt);
                            $changed = true;
                        }
                    }
                }
            }
            
            if ($changed) {
                try {
                    $post->post_content = $content;
                    $post->post_excerpt = $excerpt;
                    $post->save();
                    $updated++;
                } catch (\Exception $e) {
                    // Игнорируем ошибки
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Обработано постов: $updated");
        
        // Проверяем результат
        $remaining = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_content', 'LIKE', '%notame.ru/wp-content/uploads%')
            ->count();
        
        $this->info("📊 Осталось постов с notame.ru: $remaining");
        
        if ($remaining === 0) {
            $this->info("🎉 Все пути успешно обновлены!");
        }
        
        return 0;
    }
    
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
        $result = preg_replace('/[^a-zA-Z0-9_-]/', '-', $result);
        $result = preg_replace('/-+/', '-', $result);
        $result = trim($result, '-');
        
        return $result;
    }
}














