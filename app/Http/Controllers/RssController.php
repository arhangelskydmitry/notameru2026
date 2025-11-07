<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use Illuminate\Http\Response;

class RssController extends Controller
{
    /**
     * RSS лента для Яндекс.Дзен
     */
    public function yandexZen()
    {
        // Получаем последние 50 опубликованных статей
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('post_date', 'desc')
            ->limit(50)
            ->get();
        
        $xml = $this->generateYandexZenXml($posts);
        
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
    
    /**
     * RSS лента для Яндекс.Новости
     */
    public function yandexNews()
    {
        // Получаем статьи за последние 8 дней
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_date', '>=', now()->subDays(8))
            ->orderBy('post_date', 'desc')
            ->get();
        
        $xml = $this->generateYandexNewsXml($posts);
        
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
    
    /**
     * RSS лента для Яндекс.Турбо
     */
    public function yandexTurbo()
    {
        // Получаем статьи за последние 8 дней
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_date', '>=', now()->subDays(8))
            ->orderBy('post_date', 'desc')
            ->get();
        
        $xml = $this->generateYandexTurboXml($posts);
        
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
    
    /**
     * Генерация XML для Яндекс.Дзен
     */
    private function generateYandexZenXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Информация о канале (из настроек WordPress)
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru</link>' . "\n";
        $xml .= '<description>Новости индустрии шоу-бизнеса, культуры, искусства, здоровья и спорта</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        $xml .= '<atom:link href="https://notame.ru/feed/zen1/" rel="self" type="application/rss+xml" />' . "\n";
        
        // Добавляем статьи
        foreach ($posts as $post) {
            $xml .= $this->generatePostItem($post);
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';
        
        return $xml;
    }
    
    /**
     * Генерация элемента для одной статьи
     */
    private function generatePostItem($post)
    {
        $xml = '<item>' . "\n";
        
        // Заголовок
        $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
        
        // Ссылка
        $xml .= '<link>' . route('post', $post->post_name) . '</link>' . "\n";
        
        // GUID
        $xml .= '<guid isPermaLink="true">' . route('post', $post->post_name) . '</guid>' . "\n";
        
        // Дата публикации
        $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
        
        // Автор
        $xml .= '<dc:creator>' . $this->escapeXml($post->author->display_name ?? 'Unknown') . '</dc:creator>' . "\n";
        
        // Категории из WordPress
        foreach ($post->categories as $category) {
            $xml .= '<category>' . $this->escapeXml($category->term->name) . '</category>' . "\n";
        }
        
        // Дефолтная тематика для Яндекс.Дзен - "Знаменитости"
        $xml .= '<category>Знаменитости</category>' . "\n";
        
        // Описание (excerpt или начало контента)
        $description = $post->post_excerpt ?: $this->getExcerpt($post->post_content);
        $xml .= '<description>' . $this->escapeXml($description) . '</description>' . "\n";
        
        // Полный контент
        $content = \App\Helpers\ContentHelper::getContent($post);
        $xml .= '<content:encoded><![CDATA[' . $content . ']]></content:encoded>' . "\n";
        
        // Изображение
        $thumbnail = $this->getFeaturedImage($post);
        if ($thumbnail) {
            $xml .= '<enclosure url="' . $thumbnail . '" type="image/webp" />' . "\n";
            $xml .= '<media:content url="' . $thumbnail . '" medium="image" />' . "\n";
        }
        
        $xml .= '</item>' . "\n";
        
        return $xml;
    }
    
    /**
     * Получить excerpt из контента
     */
    private function getExcerpt($content, $length = 200)
    {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '...';
        }
        
        return $text;
    }
    
    /**
     * Получить featured image
     */
    private function getFeaturedImage($post)
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        
        if ($thumbnailId) {
            $attachment = Post::find($thumbnailId);
            if ($attachment && $attachment->guid) {
                // Конвертируем путь к WebP если нужно
                $path = $attachment->guid;
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $path)) {
                    $filename = basename($path);
                    $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                    return url('/imgnews/' . $filename);
                }
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Экранирование XML
     */
    private function escapeXml($string)
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Генерация XML для Яндекс.Новости
     */
    private function generateYandexNewsXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Информация о канале
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru</link>' . "\n";
        $xml .= '<description>Новости индустрии шоу-бизнеса, культуры, искусства, здоровья и спорта</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        
        // Добавляем статьи
        foreach ($posts as $post) {
            $xml .= '<item>' . "\n";
            
            // Заголовок
            $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
            
            // Ссылка
            $xml .= '<link>' . route('post', $post->post_name) . '</link>' . "\n";
            
            // Дата публикации
            $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
            
            // Категории
            foreach ($post->categories as $category) {
                $xml .= '<category>' . $this->escapeXml($category->term->name) . '</category>' . "\n";
            }
            
            // Описание
            $description = $post->post_excerpt ?: $this->getExcerpt($post->post_content);
            $xml .= '<description>' . $this->escapeXml($description) . '</description>' . "\n";
            
            // Полный контент
            $content = \App\Helpers\ContentHelper::getContent($post);
            $xml .= '<yandex:full-text>' . $this->escapeXml($content) . '</yandex:full-text>' . "\n";
            
            // Изображение
            $thumbnail = $this->getFeaturedImage($post);
            if ($thumbnail) {
                $xml .= '<enclosure url="' . $thumbnail . '" type="image/webp" />' . "\n";
            }
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';
        
        return $xml;
    }
    
    /**
     * Генерация XML для Яндекс.Турбо
     */
    private function generateYandexTurboXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" xmlns:turbo="http://turbo.yandex.ru">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Информация о канале
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru</link>' . "\n";
        $xml .= '<description>Новости индустрии шоу-бизнеса, культуры, искусства, здоровья и спорта</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        
        // Добавляем статьи
        foreach ($posts as $post) {
            $xml .= '<item turbo="true">' . "\n";
            
            // Заголовок
            $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
            
            // Ссылка
            $xml .= '<link>' . route('post', $post->post_name) . '</link>' . "\n";
            
            // Дата публикации
            $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
            
            // Автор
            $xml .= '<author>' . $this->escapeXml($post->author->display_name ?? 'Unknown') . '</author>' . "\n";
            
            // Категории
            foreach ($post->categories as $category) {
                $xml .= '<category>' . $this->escapeXml($category->term->name) . '</category>' . "\n";
            }
            
            // Турбо-контент
            $xml .= '<turbo:content><![CDATA[';
            $xml .= '<header>';
            $xml .= '<h1>' . $this->escapeXml($post->post_title) . '</h1>';
            
            // Изображение в header
            $thumbnail = $this->getFeaturedImage($post);
            if ($thumbnail) {
                $xml .= '<figure><img src="' . $thumbnail . '"/></figure>';
            }
            
            $xml .= '</header>';
            
            // Основной контент
            $content = \App\Helpers\ContentHelper::getContent($post);
            $xml .= $content;
            
            $xml .= ']]></turbo:content>' . "\n";
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';
        
        return $xml;
    }
}

