<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use Illuminate\Http\Response;

class RssController extends Controller
{
    /**
     * Стандартная RSS 2.0 лента
     */
    public function standardRss()
    {
        // Получаем последние 50 опубликованных статей
        $posts = Post::publiclyVisible()
            ->orderBy('post_date', 'desc')
            ->limit(50)
            ->get();
        
        $xml = $this->generateStandardRssXml($posts);
        
        return response($xml, 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
    
    /**
     * RSS лента для Яндекс.Дзен
     */
    public function yandexZen()
    {
        // Получаем последние 50 опубликованных статей
        $posts = Post::publiclyVisible()
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
        $posts = Post::publiclyVisible()
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
        $posts = Post::publiclyVisible()
            ->where('post_date', '>=', now()->subDays(8))
            ->orderBy('post_date', 'desc')
            ->get();
        
        $xml = $this->generateYandexTurboXml($posts);
        
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * RSS лента для Rambler/Новости
     */
    public function ramblerNews()
    {
        $posts = Post::publiclyVisible()
            ->where('post_date', '>=', now()->subDays(8))
            ->orderBy('post_date', 'desc')
            ->limit(50)
            ->get();

        $xml = $this->generateRamblerNewsXml($posts);

        return response($xml, 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
    
    /**
     * Генерация стандартного RSS 2.0 XML
     */
    private function generateStandardRssXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Информация о канале
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru</link>' . "\n";
        $xml .= '<description>Новости индустрии шоу-бизнеса, культуры, искусства, здоровья и спорта</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        $xml .= '<lastBuildDate>' . now()->format('D, d M Y H:i:s O') . '</lastBuildDate>' . "\n";
        $xml .= '<atom:link href="https://notame.ru/feed" rel="self" type="application/rss+xml" />' . "\n";
        
        // Добавляем статьи
        foreach ($posts as $post) {
            $xml .= '<item>' . "\n";
            
            // Заголовок
            $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
            
            // Ссылка
            $xml .= '<link>' . route('post', $post->post_name) . '</link>' . "\n";
            
            // GUID
            $xml .= '<guid isPermaLink="true">' . route('post', $post->post_name) . '</guid>' . "\n";
            
            // Дата публикации
            $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
            
            // Автор
            if ($post->author) {
                $xml .= '<dc:creator>' . $this->escapeXml($post->author->display_name) . '</dc:creator>' . "\n";
            }
            
            // Категории
            foreach ($post->categories as $category) {
                $xml .= '<category>' . $this->escapeXml($category->term->name) . '</category>' . "\n";
            }
            
            // Описание (excerpt или начало контента)
            $description = $post->post_excerpt ?: $this->getExcerpt($post->post_content);
            $xml .= '<description>' . $this->escapeXml($description) . '</description>' . "\n";
            
            // Полный контент
            $content = $this->prepareFeedContent(\App\Helpers\ContentHelper::getContent($post));
            $xml .= '<content:encoded><![CDATA[' . $content . ']]></content:encoded>' . "\n";
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';
        
        return $xml;
    }

    /**
     * Генерация XML для Rambler/Новости
     */
    private function generateRamblerNewsXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:rambler="http://news.rambler.ru" version="2.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru/</link>' . "\n";
        $xml .= '<description>Новости музыки, культуры и шоу-бизнеса</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        $xml .= '<lastBuildDate>' . now()->format('D, d M Y H:i:s O') . '</lastBuildDate>' . "\n";

        foreach ($posts as $post) {
            $xml .= '<item>' . "\n";

            $postUrl = route('post', $post->post_name);
            $description = $post->post_excerpt ?: $this->getExcerpt($post->post_content, 220);
            $description = preg_replace('/\s+/', ' ', trim(strip_tags($description)));
            $authorName = $post->author->display_name ?? 'Редакция Нота Миру';
            $content = $this->prepareRamblerContent(\App\Helpers\ContentHelper::getContent($post));

            $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
            $xml .= '<link>' . $postUrl . '</link>' . "\n";
            $xml .= '<guid>' . $this->escapeXml($postUrl) . '</guid>' . "\n";
            $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
            $xml .= '<author>' . $this->escapeXml($authorName) . '</author>' . "\n";

            if ($post->categories && $post->categories->isNotEmpty()) {
                foreach ($post->categories as $category) {
                    $xml .= '<category>' . $this->escapeXml($category->term->name) . '</category>' . "\n";
                }
            }

            $xml .= '<description>' . $this->escapeXml($description) . '</description>' . "\n";
            $xml .= '<content><![CDATA[' . $content . ']]></content>' . "\n";

            $thumbnail = $this->getFeaturedImage($post);
            if ($thumbnail) {
                $mimeType = $this->guessImageMimeType($thumbnail);
                $xml .= '<enclosure url="' . $this->escapeXml($thumbnail) . '" type="' . $mimeType . '"/>' . "\n";
            }

            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
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
        $content = $this->prepareFeedContent(\App\Helpers\ContentHelper::getContent($post));
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
     * Получить featured image с fallback механизмами
     */
    private function getFeaturedImage($post)
    {
        // 1. Пробуем получить _thumbnail_id
        $thumbnailId = $post->getMeta('_thumbnail_id');
        
        if ($thumbnailId) {
            $attachment = Post::find($thumbnailId);
            if ($attachment && $attachment->guid) {
                $path = $attachment->guid;
                
                // Если это JPG/PNG/GIF - конвертируем в WebP
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $path)) {
                    $filename = basename($path);
                    $webpFilename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                    $webpUrl = url('/imgnews/' . $webpFilename);
                    
                    // Проверяем существование WebP файла
                    $webpPath = public_path('/imgnews/' . $webpFilename);
                    if (file_exists($webpPath)) {
                        return $webpUrl;
                    }
                    // Если WebP не существует, возвращаем оригинальный URL
                    return $this->normalizeImageUrl($path);
                }
                
                // Если уже WebP или другой формат
                return $this->normalizeImageUrl($path);
            }
        }
        
        // 2. Fallback: проверяем og_image из SEO
        $seo = $post->seo;
        if ($seo && $seo->og_image) {
            return $this->normalizeImageUrl($seo->og_image);
        }
        
        // 3. Fallback: ищем первое изображение в контенте
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches)) {
            return $this->normalizeImageUrl($matches[1]);
        }
        
        return null;
    }

    /**
     * Подготовить HTML контент под требования Rambler.
     */
    private function prepareRamblerContent($content)
    {
        if (!$content) {
            return '<p>Материал доступен на сайте по ссылке из новости.</p>';
        }

        $content = preg_replace('#<(script|style|iframe|form|button)[^>]*>.*?</\1>#is', '', $content);

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $html = '<div>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</div>';
        $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = $document->documentElement;
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '<p>' . $this->escapeXml($this->getExcerpt($content, 400)) . '</p>';
        }

        $sanitized = $this->sanitizeRamblerChildren($root);
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = preg_replace('/>\s+</', '><', $sanitized);
        $sanitized = trim($sanitized);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $sanitized !== ''
            ? $sanitized
            : '<p>' . $this->escapeXml($this->getExcerpt($content, 400)) . '</p>';
    }

    /**
     * Подготовить HTML контент для RSS/Zen/Turbo без фронтовой разметки.
     */
    private function prepareFeedContent($content)
    {
        if (!$content) {
            return '<p>Материал доступен на сайте по ссылке из новости.</p>';
        }

        $content = preg_replace('#<(script|style|iframe|form|button|input|textarea|select|video|audio)[^>]*>.*?</\1>#is', '', $content);

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $html = '<div>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</div>';
        $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = $document->documentElement;
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '<p>' . $this->escapeXml($this->getExcerpt($content, 400)) . '</p>';
        }

        $sanitized = $this->sanitizeFeedChildren($root);
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = preg_replace('/>\s+</', '><', $sanitized);
        $sanitized = trim($sanitized);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $sanitized !== ''
            ? $sanitized
            : '<p>' . $this->escapeXml($this->getExcerpt($content, 400)) . '</p>';
    }

    private function sanitizeFeedChildren(\DOMNode $node)
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $this->sanitizeFeedNode($child);
        }

        return $html;
    }

    private function sanitizeFeedNode(\DOMNode $node)
    {
        if ($node instanceof \DOMText) {
            $text = preg_replace('/\s+/u', ' ', $node->nodeValue ?? '');
            return $this->escapeXml($text);
        }

        if (!($node instanceof \DOMElement)) {
            return '';
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['h1', 'h2'], true)) {
            $tag = 'h2';
        } elseif (in_array($tag, ['h3', 'h4', 'h5', 'h6'], true)) {
            $tag = 'h3';
        }

        $allowed = ['p', 'a', 'h2', 'h3', 'blockquote', 'figure', 'img', 'figcaption', 'ul', 'ol', 'li', 'br', 'strong', 'em'];

        if (!in_array($tag, $allowed, true)) {
            return $this->sanitizeFeedChildren($node);
        }

        if ($tag === 'img') {
            $src = $this->normalizeImageUrl($node->getAttribute('src'));
            if (!$src) {
                return '';
            }

            $alt = trim($node->getAttribute('alt'));
            $attributes = ' src="' . $this->escapeXml($src) . '"';
            if ($alt !== '') {
                $attributes .= ' alt="' . $this->escapeXml($alt) . '"';
            }

            return '<img' . $attributes . '/>';
        }

        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $inner = trim($this->sanitizeFeedChildren($node));

            if ($inner === '') {
                return '';
            }

            // Не оставляем обертки-ссылки вокруг изображений в фидах.
            if (strpos($inner, '<img') !== false) {
                return $inner;
            }

            if ($href === '' || str_starts_with($href, '#')) {
                return $inner;
            }

            $href = $this->normalizeImageUrl($href);
            if (!$href) {
                return $inner;
            }

            return '<a href="' . $this->escapeXml($href) . '">' . $inner . '</a>';
        }

        if ($tag === 'br') {
            return '<br/>';
        }

        $inner = trim($this->sanitizeFeedChildren($node));

        if ($tag === 'figure') {
            $hasMedia = strpos($inner, '<img') !== false;
            return $hasMedia ? '<figure>' . $inner . '</figure>' : '';
        }

        if ($inner === '') {
            return '';
        }

        return '<' . $tag . '>' . $inner . '</' . $tag . '>';
    }

    /**
     * Очистить дочерние узлы под требования Rambler.
     */
    private function sanitizeRamblerChildren(\DOMNode $node)
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $this->sanitizeRamblerNode($child);
        }

        return $html;
    }

    /**
     * Оставить только разрешенные теги и безопасные атрибуты.
     */
    private function sanitizeRamblerNode(\DOMNode $node)
    {
        if ($node instanceof \DOMText) {
            $text = preg_replace('/\s+/u', ' ', $node->nodeValue ?? '');
            return $this->escapeXml($text);
        }

        if (!($node instanceof \DOMElement)) {
            return '';
        }

        $tag = strtolower($node->tagName);
        $allowed = ['p', 'a', 'h1', 'h2', 'blockquote', 'figure', 'img', 'figcaption'];

        if (!in_array($tag, $allowed, true)) {
            return $this->sanitizeRamblerChildren($node);
        }

        if ($tag === 'img') {
            $src = $this->normalizeImageUrl($node->getAttribute('src'));
            if (!$src) {
                return '';
            }

            $alt = trim($node->getAttribute('alt'));
            $attributes = ' src="' . $this->escapeXml($src) . '"';
            if ($alt !== '') {
                $attributes .= ' alt="' . $this->escapeXml($alt) . '"';
            }

            return '<img' . $attributes . '/>';
        }

        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            if ($href === '') {
                return $this->sanitizeRamblerChildren($node);
            }

            $href = str_starts_with($href, '#') ? '' : $this->normalizeImageUrl($href);
            $inner = trim($this->sanitizeRamblerChildren($node));

            if ($href === '' || $inner === '') {
                return $inner;
            }

            return '<a href="' . $this->escapeXml($href) . '">' . $inner . '</a>';
        }

        $inner = trim($this->sanitizeRamblerChildren($node));

        if ($tag === 'figure') {
            $hasMedia = strpos($inner, '<img') !== false;
            return $hasMedia ? '<figure>' . $inner . '</figure>' : '';
        }

        if ($inner === '') {
            return '';
        }

        return '<' . $tag . '>' . $inner . '</' . $tag . '>';
    }

    /**
     * Определить MIME-тип изображения по расширению.
     */
    private function guessImageMimeType($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'gif' => 'image/gif',
            'jpg', 'jpeg', 'pjpeg' => 'image/jpeg',
            default => 'image/jpeg',
        };
    }
    
    /**
     * Нормализация URL изображения (убираем localhost, приводим к production URL)
     */
    private function normalizeImageUrl($url)
    {
        if (empty($url)) {
            return null;
        }
        
        // Убираем localhost и порты из URL
        $url = preg_replace('#^https?://localhost(:\d+)?/#', 'https://notame.ru/', $url);
        $url = preg_replace('#^https?://127\.0\.0\.1(:\d+)?/#', 'https://notame.ru/', $url);
        
        // Если относительный путь - делаем абсолютным
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = 'https://notame.ru' . $url;
        }
        
        // Убираем двойные слеши (кроме https://)
        $url = preg_replace('#(?<!:)//+#', '/', $url);
        
        return $url;
    }
    
    /**
     * Экранирование XML
     */
    private function escapeXml($string)
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Генерация XML для Яндекс.Новости (по рекомендациям Яндекс Вебмастер)
     */
    private function generateYandexNewsXml($posts)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" version="2.0">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Информация о канале
        $xml .= '<title>Нота Миру</title>' . "\n";
        $xml .= '<link>https://notame.ru/</link>' . "\n";
        $xml .= '<description>Новости музыки, культуры и шоу-бизнеса</description>' . "\n";
        $xml .= '<language>ru</language>' . "\n";
        
        // Добавляем статьи
        foreach ($posts as $post) {
            $xml .= '<item>' . "\n";
            
            // Заголовок
            $xml .= '<title>' . $this->escapeXml($post->post_title) . '</title>' . "\n";
            
            // Ссылка на десктопную версию
            $xml .= '<link>' . route('post', $post->post_name) . '</link>' . "\n";
            
            // Ссылка на мобильную версию (опционально)
            $xml .= '<pdalink>' . route('post', $post->post_name) . '</pdalink>' . "\n";
            
            // Описание (краткое содержание)
            $description = $post->post_excerpt ?: $this->getExcerpt($post->post_content, 200);
            $xml .= '<description>' . $this->escapeXml($description) . '</description>' . "\n";
            
            // Автор
            $authorName = $post->author->display_name ?? 'Редакция Нота Миру';
            $xml .= '<author>' . $this->escapeXml($authorName) . '</author>' . "\n";
            
            // Категория (берем первую категорию)
            if ($post->categories && $post->categories->isNotEmpty()) {
                $xml .= '<category>' . $this->escapeXml($post->categories->first()->term->name) . '</category>' . "\n";
            }
            
            // Изображение и видео (media:group)
            $thumbnail = $this->getFeaturedImage($post);
            if ($thumbnail) {
                $xml .= '<media:group>' . "\n";
                $xml .= '<media:thumbnail url="' . $thumbnail . '"/>' . "\n";
                $xml .= '</media:group>' . "\n";
            }
            
            // Дата публикации
            $xml .= '<pubDate>' . $post->post_date->format('D, d M Y H:i:s O') . '</pubDate>' . "\n";
            
            // Жанр материала (message - новость/статья)
            $xml .= '<yandex:genre>message</yandex:genre>' . "\n";
            
            // Полный текст статьи
            $fullText = strip_tags(\App\Helpers\ContentHelper::getContent($post));
            $fullText = preg_replace('/\s+/', ' ', $fullText);
            $fullText = trim($fullText);
            $xml .= '<yandex:full-text>' . $this->escapeXml($fullText) . '</yandex:full-text>' . "\n";
            
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
            $content = $this->prepareFeedContent(\App\Helpers\ContentHelper::getContent($post));
            $xml .= $content;
            
            $xml .= ']]></turbo:content>' . "\n";
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';
        
        return $xml;
    }
}

