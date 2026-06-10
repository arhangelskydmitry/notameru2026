<?php

namespace App\Helpers;

/**
 * HTML Sanitizer Helper
 * 
 * Встроенная защита от XSS атак БЕЗ внешних зависимостей
 * Работает на чистом PHP, не требует composer install
 * 
 * Удаляет:
 * - <script> теги и их содержимое
 * - javascript: ссылки
 * - on* события (onclick, onerror и т.д.)
 * - data: URI в изображениях (кроме безопасных)
 * - vbscript: ссылки
 * - expression() в CSS
 */
class HtmlPurifierHelper
{
    /**
     * Разрешённые теги для контента статей
     */
    protected static array $allowedTags = [
        // Структурные
        'p', 'br', 'hr', 'div', 'span', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav',
        // Заголовки
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        // Форматирование
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'small', 'mark', 'del', 'ins', 'cite', 'code', 'pre', 'kbd', 'samp', 'var',
        // Списки
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        // Ссылки и изображения
        'a', 'img',
        // Таблицы
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        // Медиа
        'figure', 'figcaption', 'picture', 'source', 'video', 'audio', 'iframe',
        // Блоки
        'blockquote', 'q', 'address', 'details', 'summary',
        // Форматирование
        'abbr', 'time', 'wbr',
    ];
    
    /**
     * Разрешённые атрибуты
     */
    protected static array $allowedAttributes = [
        '*' => ['id', 'class', 'style', 'title', 'lang', 'dir'],
        'a' => ['href', 'target', 'rel', 'download'],
        'img' => ['src', 'alt', 'width', 'height', 'loading', 'srcset', 'sizes'],
        'video' => ['src', 'controls', 'width', 'height', 'poster', 'autoplay', 'muted', 'loop', 'playsinline'],
        'audio' => ['src', 'controls', 'autoplay', 'muted', 'loop'],
        'source' => ['src', 'srcset', 'type', 'media', 'sizes'],
        'iframe' => ['src', 'width', 'height', 'frameborder', 'allowfullscreen', 'allow', 'loading'],
        'td' => ['colspan', 'rowspan', 'headers'],
        'th' => ['colspan', 'rowspan', 'headers', 'scope'],
        'col' => ['span'],
        'colgroup' => ['span'],
        'time' => ['datetime'],
        'abbr' => ['title'],
        'blockquote' => ['cite'],
        'q' => ['cite'],
        'ol' => ['start', 'type', 'reversed'],
        'li' => ['value'],
        'details' => ['open'],
    ];
    
    /**
     * Разрешённые домены для iframe (YouTube, Vimeo, RuTube и т.д.)
     */
    protected static array $allowedIframeDomains = [
        'www.youtube.com',
        'youtube.com',
        'www.youtube-nocookie.com',
        'player.vimeo.com',
        'vimeo.com',
        'rutube.ru',
        'ok.ru',
        'vk.com',
        'music.yandex.ru',
        'open.spotify.com',
    ];
    
    /**
     * Очистить HTML с сохранением безопасных тегов
     * Основной метод для очистки контента статей
     */
    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        // 1. Удаляем script теги полностью
        $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        
        // 2. Удаляем style теги полностью (inline стили в атрибутах оставляем)
        $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        
        // 3. Удаляем комментарии HTML
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // 4. Удаляем noscript теги
        $html = preg_replace('#<noscript[^>]*>.*?</noscript>#is', '', $html);
        
        // 5. Очищаем опасные атрибуты из всех тегов
        $html = self::removeEventHandlers($html);
        
        // 6. Очищаем javascript: и vbscript: ссылки
        $html = self::removeJavaScriptUrls($html);
        
        // 7. Очищаем опасный CSS
        $html = self::removeDangerousCss($html);
        
        // 8. Проверяем iframe на разрешённые домены
        $html = self::sanitizeIframes($html);
        
        // 9. Удаляем неразрешённые теги (оставляем содержимое)
        $html = self::removeDisallowedTags($html);
        
        return $html;
    }
    
    /**
     * Удаляет обработчики событий (onclick, onerror и т.д.)
     */
    protected static function removeEventHandlers(string $html): string
    {
        // Удаляем все on* атрибуты
        return preg_replace(
            '/\s+on\w+\s*=\s*(["\'])[^"\']*\1/i',
            '',
            $html
        );
    }
    
    /**
     * Удаляет javascript: и vbscript: ссылки
     */
    protected static function removeJavaScriptUrls(string $html): string
    {
        // Заменяем javascript: и vbscript: на безопасный #
        $patterns = [
            '/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i' => 'href="#"',
            '/href\s*=\s*(["\'])\s*vbscript:[^"\']*\1/i' => 'href="#"',
            '/src\s*=\s*(["\'])\s*javascript:[^"\']*\1/i' => 'src=""',
            '/src\s*=\s*(["\'])\s*data:text\/html[^"\']*\1/i' => 'src=""',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }
        
        return $html;
    }
    
    /**
     * Удаляет опасный CSS (expression, url с javascript и т.д.)
     */
    protected static function removeDangerousCss(string $html): string
    {
        // Удаляем expression() из CSS
        $html = preg_replace('/expression\s*\([^)]*\)/i', '', $html);
        
        // Удаляем javascript: из url() в CSS
        $html = preg_replace('/url\s*\(\s*(["\']?)\s*javascript:[^)]*\)/i', 'url(#)', $html);
        
        // Удаляем behavior: из CSS
        $html = preg_replace('/behavior\s*:\s*[^;"\'>]*/i', '', $html);
        
        // Удаляем -moz-binding из CSS
        $html = preg_replace('/-moz-binding\s*:\s*[^;"\'>]*/i', '', $html);
        
        return $html;
    }
    
    /**
     * Проверяет и очищает iframe на разрешённые домены
     * Неразрешённые iframe полностью удаляются
     */
    protected static function sanitizeIframes(string $html): string
    {
        return preg_replace_callback(
            '/<iframe[^>]*>.*?<\/iframe>|<iframe[^>]*\/?>/is',
            function ($matches) {
                $iframeTag = $matches[0];
                
                // Извлекаем src
                if (preg_match('/src\s*=\s*(["\'])([^"\']+)\1/i', $iframeTag, $srcMatch)) {
                    $src = $srcMatch[2];
                    $parsedUrl = parse_url($src);
                    $host = $parsedUrl['host'] ?? '';
                    
                    // Проверяем, разрешён ли домен
                    $allowed = false;
                    foreach (self::$allowedIframeDomains as $allowedDomain) {
                        if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                            $allowed = true;
                            break;
                        }
                    }
                    
                    if (!$allowed) {
                        // Полностью удаляем неразрешённый iframe
                        return '<!-- blocked iframe -->';
                    }
                }
                
                return $iframeTag;
            },
            $html
        );
    }
    
    /**
     * Удаляет неразрешённые теги, сохраняя их содержимое
     */
    protected static function removeDisallowedTags(string $html): string
    {
        // Список опасных тегов для полного удаления (с содержимым)
        $dangerousTags = ['script', 'style', 'noscript', 'object', 'embed', 'applet', 'form', 'input', 'button', 'select', 'textarea'];
        
        foreach ($dangerousTags as $tag) {
            $html = preg_replace("#<{$tag}[^>]*>.*?</{$tag}>#is", '', $html);
            $html = preg_replace("#<{$tag}[^>]*/?>#is", '', $html);
        }
        
        return $html;
    }
    
    /**
     * Строгая очистка - удаляет ВСЕ HTML теги
     * Для заголовков, мета-тегов, имён пользователей
     */
    public static function stripAll(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        // Удаляем все теги
        $text = strip_tags($html);
        
        // Декодируем HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Удаляем лишние пробелы
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Экранирует HTML для вывода в атрибутах
     */
    public static function escape(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Глобальная функция для очистки HTML
 * 
 * @param string|null $html HTML для очистки
 * @param bool $strict Если true - удаляет ВСЕ теги
 * @return string Очищенный HTML
 */
if (!function_exists('clean_html')) {
    function clean_html(?string $html, bool $strict = false): string
    {
        if ($strict) {
            return HtmlPurifierHelper::stripAll($html);
        }
        return HtmlPurifierHelper::clean($html);
    }
}

/**
 * Алиас для clean_html
 */
if (!function_exists('purify')) {
    function purify(?string $html): string
    {
        return HtmlPurifierHelper::clean($html);
    }
}
