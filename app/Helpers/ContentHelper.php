<?php

namespace App\Helpers;

class ContentHelper
{
    /**
     * Преобразование WordPress [caption] shortcode в HTML
     */
    public static function convertCaptionShortcode($content)
    {
        // Паттерн для [caption] shortcode
        $pattern = '/\[caption[^\]]*\](.*?)\[\/caption\]/is';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $fullMatch = $matches[0];
            $innerContent = $matches[1];
            
            // Извлекаем параметры из shortcode
            preg_match('/id="([^"]+)"/', $fullMatch, $id);
            preg_match('/align="([^"]+)"/', $fullMatch, $align);
            preg_match('/width="([^"]+)"/', $fullMatch, $width);
            
            // Извлекаем изображение и текст caption
            preg_match('/<img[^>]+>/i', $innerContent, $img);
            $captionText = trim(strip_tags($innerContent));
            
            // Определяем выравнивание
            $alignClass = '';
            $alignStyle = 'margin: 20px auto; text-align: center;';
            
            if (isset($align[1])) {
                switch ($align[1]) {
                    case 'alignleft':
                        $alignClass = 'alignleft';
                        $alignStyle = 'float: left; margin: 10px 20px 20px 0; text-align: left;';
                        break;
                    case 'alignright':
                        $alignClass = 'alignright';
                        $alignStyle = 'float: right; margin: 10px 0 20px 20px; text-align: right;';
                        break;
                    case 'aligncenter':
                        $alignClass = 'aligncenter';
                        $alignStyle = 'margin: 20px auto; text-align: center;';
                        break;
                }
            }
            
            // Определяем ширину
            $widthAttr = isset($width[1]) ? 'max-width: ' . $width[1] . 'px;' : 'max-width: 100%;';
            
            // Формируем HTML
            $html = '<figure class="wp-caption ' . $alignClass . '" style="' . $alignStyle . ' ' . $widthAttr . '">';
            
            if (isset($img[0])) {
                // Исправляем путь к изображению
                $imgTag = self::fixImageTag($img[0]);
                $html .= $imgTag;
            }
            
            if (!empty($captionText)) {
                $html .= '<figcaption class="wp-caption-text" style="margin-top: 10px; font-size: 14px; color: #666; font-style: italic; line-height: 1.5;">' . 
                         htmlspecialchars($captionText) . 
                         '</figcaption>';
            }
            
            $html .= '</figure>';
            
            return $html;
        }, $content);
        
        return $content;
    }
    
    /**
     * Исправление одного img тега
     */
    private static function fixImageTag($imgTag)
    {
        // Извлекаем src из img тега
        preg_match('/src=["\']([^"\']+)["\']/i', $imgTag, $srcMatch);
        
        if (!isset($srcMatch[1])) {
            return $imgTag;
        }
        
        $src = $srcMatch[1];
        $newSrc = $src;
        
        // Если путь содержит wp-content/uploads или старый домен
        if (strpos($src, 'wp-content/uploads') !== false || 
            strpos($src, 'notame.ru') !== false ||
            strpos($src, 'localhost:8001') !== false) {
            
            // Извлекаем имя файла
            $filename = basename($src);
            
            // Конвертируем расширение в .webp если это изображение
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
                $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
            }
            
            // Новый путь к изображению
            $newSrc = '/imgnews/' . $filename;
        }
        
        // Если путь уже правильный (/imgnews/), оставляем как есть
        if (strpos($src, '/imgnews/') !== false) {
            $newSrc = $src;
        }
        
        // Заменяем src в img теге и добавляем стили
        $imgTag = str_replace($src, $newSrc, $imgTag);
        $imgTag = str_replace('<img ', '<img style="width: 100%; height: auto; border-radius: 5px;" ', $imgTag);
        
        // Оборачиваем в ссылку для лайтбокса
        return '<a href="' . $newSrc . '" class="post-image-link" data-lightbox="post-images">' . $imgTag . '</a>';
    }
    
    /**
     * Очистка WordPress shortcodes из контента
     */
    public static function cleanShortcodes($content)
    {
        // Преобразуем [caption] в HTML вместо удаления
        $content = self::convertCaptionShortcode($content);
        
        // Удаляем другие популярные shortcodes
        $content = preg_replace('/\[gallery[^\]]*\]/i', '', $content);
        $content = preg_replace('/\[embed[^\]]*\](.*?)\[\/embed\]/is', '$1', $content);
        
        // Очистка лишних пробелов
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Извлечение excerpt с очисткой
     */
    public static function getExcerpt($post, $length = 150)
    {
        $excerpt = $post->post_excerpt ?: $post->post_content;
        $excerpt = self::cleanShortcodes($excerpt);
        $excerpt = strip_tags($excerpt);
        return \Str::limit($excerpt, $length);
    }
    
    /**
     * Очистка и подготовка полного контента
     */
    public static function getContent($post)
    {
        $content = $post->post_content;
        $content = self::cleanShortcodes($content);
        
        // Заменяем WordPress классы на современные (но для img оставляем без изменений)
        $content = preg_replace_callback('/<img([^>]*)class="([^"]*)"([^>]*)>/i', function($matches) {
            $before = $matches[1];
            $classes = $matches[2];
            $after = $matches[3];
            
            // Оставляем aligncenter как есть для CSS обработки
            return '<img' . $before . 'class="' . $classes . '"' . $after . '>';
        }, $content);
        
        // Исправляем пути к изображениям
        $content = self::fixImagePaths($content);
        
        return $content;
    }
    
    /**
     * Исправление путей к изображениям в контенте
     */
    public static function fixImagePaths($content)
    {
        // Паттерн для поиска всех img тегов
        $pattern = '/<img([^>]*)src=["\']([^"\']+)["\']([^>]*)>/i';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $beforeSrc = $matches[1];
            $src = $matches[2];
            $afterSrc = $matches[3];
            
            // Извлекаем классы из img тега
            $alignClass = '';
            if (preg_match('/class="([^"]*)"/i', $beforeSrc . $afterSrc, $classMatch)) {
                $classes = $classMatch[1];
                // Ищем align классы
                if (strpos($classes, 'aligncenter') !== false) {
                    $alignClass = 'aligncenter';
                } elseif (strpos($classes, 'alignleft') !== false) {
                    $alignClass = 'alignleft';
                } elseif (strpos($classes, 'alignright') !== false) {
                    $alignClass = 'alignright';
                }
            }
            
            // Если путь содержит wp-content/uploads или старый домен
            if (strpos($src, 'wp-content/uploads') !== false || 
                strpos($src, 'notame.ru') !== false ||
                strpos($src, 'localhost:8001') !== false) {
                
                // Извлекаем имя файла
                $filename = basename($src);
                
                // Конвертируем расширение в .webp если это изображение
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
                    $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                }
                
                // Новый путь к изображению
                $newSrc = '/imgnews/' . $filename;
                
                // Создаем img тег с кликабельной ссылкой для модального окна
                $linkClass = $alignClass ? ' class="post-image-link ' . $alignClass . '"' : ' class="post-image-link"';
                $imgTag = '<a href="' . $newSrc . '"' . $linkClass . ' data-lightbox="post-images">' .
                          '<img' . $beforeSrc . 'src="' . $newSrc . '"' . $afterSrc . '>' .
                          '</a>';
                
                return $imgTag;
            }
            
            // Если путь уже правильный (/imgnews/), тоже делаем кликабельным
            if (strpos($src, '/imgnews/') !== false) {
                $linkClass = $alignClass ? ' class="post-image-link ' . $alignClass . '"' : ' class="post-image-link"';
                return '<a href="' . $src . '"' . $linkClass . ' data-lightbox="post-images">' .
                       '<img' . $beforeSrc . 'src="' . $src . '"' . $afterSrc . '>' .
                       '</a>';
            }
            
            // Для других изображений возвращаем как есть
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Получить локальный путь к featured image
     */
    public static function getFeaturedImage($post)
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        
        if (!$thumbnailId) {
            return null;
        }
        
        $attachment = \App\Models\WordPress\Post::find($thumbnailId);
        
        if (!$attachment) {
            return null;
        }
        
        // Пытаемся получить локальный путь
        $attachedFile = $attachment->getMeta('_wp_attached_file');
        
        if ($attachedFile) {
            // Формируем локальный путь к WebP версии
            $filename = basename($attachedFile);
            $webpFilename = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filename);
            $localPath = '/imgnews/' . $webpFilename;
            
            // Проверяем существование файла
            if (file_exists(public_path($localPath))) {
                return $localPath;
            }
        }
        
        // Fallback: используем GUID (старый путь)
        return $attachment->guid;
    }
    
    /**
     * Склонение русских существительных
     * 
     * @param int $number Число
     * @param array $forms Массив форм: [1 => 'статья', 2 => 'статьи', 5 => 'статей']
     * @return string Правильная форма слова
     */
    public static function pluralize($number, $forms)
    {
        $number = abs($number) % 100;
        $n1 = $number % 10;
        
        if ($number > 10 && $number < 20) {
            return $forms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $forms[1];
        }
        if ($n1 == 1) {
            return $forms[0];
        }
        
        return $forms[2];
    }
}

