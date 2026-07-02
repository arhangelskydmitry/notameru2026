#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;
use Illuminate\Support\Facades\File;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 ПРОВЕРКА ДОСТУПНОСТИ ИЗОБРАЖЕНИЙ                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$stats = [
    'checked' => 0,
    'found' => 0,
    'missing' => 0,
    'converted' => 0,
    'errors' => []
];

$imagesDir = public_path('imgnews');

// Функция для извлечения всех изображений из контента
function extractImages($content) {
    $images = [];
    
    // Ищем все изображения в тегах img
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
    if (!empty($matches[1])) {
        $images = array_merge($images, $matches[1]);
    }
    
    // Ищем изображения в markdown
    preg_match_all('/!\[([^\]]*)\]\(([^)]+)\)/', $content, $matches);
    if (!empty($matches[2])) {
        $images = array_merge($images, $matches[2]);
    }
    
    return array_unique($images);
}

// Функция для конвертации изображения в WebP
function convertToWebp($sourcePath, $destPath) {
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    
    try {
        switch ($mimeType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Конвертируем в WebP
        $result = @imagewebp($image, $destPath, 85);
        imagedestroy($image);
        
        return $result;
    } catch (\Exception $e) {
        return false;
    }
}

echo "🔍 Сканирование постов...\n\n";

Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->chunk(100, function($posts) use (&$stats, $imagesDir) {
        foreach ($posts as $post) {
            $images = extractImages($post->post_content);
            
            foreach ($images as $imageUrl) {
                $stats['checked']++;
                
                // Убираем домен и параметры
                $imagePath = preg_replace('/^https?:\/\/[^\/]+/', '', $imageUrl);
                $imagePath = preg_replace('/\?.*$/', '', $imagePath);
                
                // Убираем /imgnews/ из начала для получения относительного пути
                $relativePath = preg_replace('/^\/imgnews\//', '', $imagePath);
                
                // Полный путь к файлу
                $fullPath = $imagesDir . '/' . $relativePath;
                
                // Проверяем существует ли файл
                if (file_exists($fullPath)) {
                    $stats['found']++;
                    echo ".";
                } else {
                    $stats['missing']++;
                    
                    // Пытаемся найти файл в другом формате
                    $pathInfo = pathinfo($fullPath);
                    $dirname = $pathInfo['dirname'];
                    $filename = $pathInfo['filename'];
                    
                    $alternativeFormats = ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF'];
                    $foundAlternative = false;
                    
                    foreach ($alternativeFormats as $ext) {
                        $alternativePath = $dirname . '/' . $filename . '.' . $ext;
                        
                        if (file_exists($alternativePath)) {
                            echo "\n📸 Найден альтернативный формат: " . basename($alternativePath);
                            
                            // Конвертируем в WebP
                            $webpPath = $dirname . '/' . $filename . '.webp';
                            
                            if (convertToWebp($alternativePath, $webpPath)) {
                                echo " → ✅ Сконвертирован в WebP\n";
                                $stats['converted']++;
                                $foundAlternative = true;
                                
                                // Обновляем контент поста
                                $oldUrl = str_replace('.webp', '.' . $ext, $imageUrl);
                                $newContent = str_replace($oldUrl, $imageUrl, $post->post_content);
                                
                                if ($newContent !== $post->post_content) {
                                    $post->post_content = $newContent;
                                    $post->save();
                                }
                                
                                break;
                            } else {
                                echo " → ❌ Ошибка конвертации\n";
                                $stats['errors'][] = "Не удалось конвертировать: $alternativePath";
                            }
                        }
                    }
                    
                    if (!$foundAlternative) {
                        echo "\n⚠️  Изображение не найдено: $relativePath\n";
                        $stats['errors'][] = "Не найдено: $relativePath (пост ID: {$post->ID})";
                    }
                }
                
                if ($stats['checked'] % 100 == 0) {
                    echo " [{$stats['checked']}]\n";
                }
            }
        }
    });

echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  📊 РЕЗУЛЬТАТЫ ПРОВЕРКИ                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Статистика:\n";
echo "  • Проверено изображений: " . $stats['checked'] . "\n";
echo "  • Найдено: " . $stats['found'] . " (" . round($stats['found']/$stats['checked']*100, 1) . "%)\n";
echo "  • Не найдено: " . $stats['missing'] . " (" . round($stats['missing']/$stats['checked']*100, 1) . "%)\n";
echo "  • Сконвертировано: " . $stats['converted'] . "\n\n";

if (!empty($stats['errors'])) {
    echo "⚠️  Проблемы:\n";
    $errorCount = min(20, count($stats['errors']));
    for ($i = 0; $i < $errorCount; $i++) {
        echo "  • " . $stats['errors'][$i] . "\n";
    }
    if (count($stats['errors']) > 20) {
        echo "  ... и ещё " . (count($stats['errors']) - 20) . " проблем\n";
    }
    echo "\n";
    
    // Сохраняем полный список ошибок в файл
    file_put_contents(__DIR__ . '/missing_images.log', implode("\n", $stats['errors']));
    echo "📄 Полный список сохранён в: missing_images.log\n\n";
}

if ($stats['converted'] > 0) {
    echo "✅ Сконвертировано изображений: " . $stats['converted'] . "\n";
}

echo "\n🎉 Проверка завершена!\n";

