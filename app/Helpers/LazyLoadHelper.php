<?php

namespace App\Helpers;

class LazyLoadHelper
{
    /**
     * Добавить loading="lazy" к img тегу
     *
     * @param string $html HTML с img тегами
     * @param array $options Опции (skip_first, add_dimensions, etc.)
     * @return string
     */
    public static function addLazyLoading($html, $options = [])
    {
        $skipFirst = $options['skip_first'] ?? false;
        $addDimensions = $options['add_dimensions'] ?? true;
        $count = 0;
        
        // Паттерн для поиска img тегов
        $pattern = '/<img([^>]*)>/i';
        
        $html = preg_replace_callback($pattern, function($matches) use (&$count, $skipFirst, $addDimensions) {
            $count++;
            $imgTag = $matches[0];
            $attributes = $matches[1];
            
            // Пропускаем первое изображение если нужно (LCP оптимизация)
            if ($skipFirst && $count === 1) {
                // Добавляем fetchpriority="high" для первого изображения
                if (!str_contains($attributes, 'fetchpriority')) {
                    $imgTag = str_replace('<img', '<img fetchpriority="high"', $imgTag);
                }
                return $imgTag;
            }
            
            // Если уже есть loading атрибут - пропускаем
            if (str_contains($attributes, 'loading=')) {
                return $imgTag;
            }
            
            // Добавляем loading="lazy"
            $imgTag = str_replace('<img', '<img loading="lazy"', $imgTag);
            
            // Опционально добавляем width/height если их нет (CLS оптимизация)
            if ($addDimensions && !str_contains($attributes, 'width=') && !str_contains($attributes, 'height=')) {
                // Можно добавить логику определения размеров
                // Но лучше делать это на стороне контента при сохранении
            }
            
            return $imgTag;
        }, $html);
        
        return $html;
    }
    
    /**
     * Обработать контент статьи
     *
     * @param string $content
     * @param bool $skipFirst Пропустить первое изображение (для featured image)
     * @return string
     */
    public static function processPostContent($content, $skipFirst = true)
    {
        return self::addLazyLoading($content, [
            'skip_first' => $skipFirst,
            'add_dimensions' => false, // Не добавляем размеры автоматически
        ]);
    }
    
    /**
     * Получить атрибуты для img тега
     *
     * @param bool $isFirst Это первое изображение на странице?
     * @param string $alt Alt текст
     * @param array $dimensions ['width' => 800, 'height' => 600]
     * @return string
     */
    public static function getImageAttributes($isFirst = false, $alt = '', $dimensions = [])
    {
        $attributes = [];
        
        // Alt всегда нужен для SEO
        $attributes[] = 'alt="' . htmlspecialchars($alt) . '"';
        
        // Lazy loading или high priority
        if ($isFirst) {
            $attributes[] = 'fetchpriority="high"';
        } else {
            $attributes[] = 'loading="lazy"';
        }
        
        // Размеры для предотвращения CLS
        if (!empty($dimensions['width'])) {
            $attributes[] = 'width="' . (int)$dimensions['width'] . '"';
        }
        if (!empty($dimensions['height'])) {
            $attributes[] = 'height="' . (int)$dimensions['height'] . '"';
        }
        
        return implode(' ', $attributes);
    }
    
    /**
     * Проверить поддержку браузером
     * (для использования в JS polyfill)
     *
     * @return string JavaScript код для проверки
     */
    public static function getPolyfillScript()
    {
        return <<<'JS'
<script>
// Lazy Loading Polyfill для старых браузеров
(function() {
    // Проверяем поддержку loading="lazy"
    if ('loading' in HTMLImageElement.prototype) {
        return; // Браузер поддерживает нативно
    }
    
    // Polyfill для старых браузеров
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        // Используем Intersection Observer
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    img.removeAttribute('loading');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px' // Загружаем за 50px до появления
        });
        
        images.forEach(img => {
            // Переносим src в data-src
            if (img.src && !img.dataset.src) {
                img.dataset.src = img.src;
                img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            }
            imageObserver.observe(img);
        });
    } else {
        // Fallback: загружаем все сразу
        images.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
            img.removeAttribute('loading');
        });
    }
})();
</script>
JS;
    }
    
    /**
     * Получить статистику ленивой загрузки на странице
     *
     * @param string $html
     * @return array
     */
    public static function getStats($html)
    {
        $totalImages = preg_match_all('/<img[^>]*>/i', $html);
        $lazyImages = preg_match_all('/<img[^>]*loading=["\']lazy["\']/i', $html);
        $eagerImages = preg_match_all('/<img[^>]*fetchpriority=["\']high["\']/i', $html);
        
        return [
            'total' => $totalImages,
            'lazy' => $lazyImages,
            'eager' => $eagerImages,
            'without' => $totalImages - $lazyImages - $eagerImages,
            'percentage' => $totalImages > 0 ? round(($lazyImages / $totalImages) * 100, 2) : 0,
        ];
    }
}
