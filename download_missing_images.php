#!/usr/bin/env php
<?php

// Скрипт для поиска и скачивания недостающих изображений

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WordPress\Post;

echo "🔍 Поиск постов с изображениями...\n\n";

// Получаем все опубликованные посты
$posts = Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->get();

echo "📊 Всего постов: " . $posts->count() . "\n\n";

$missingImages = [];
$totalImages = 0;

foreach ($posts as $post) {
    // Извлекаем все URL изображений
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);
    
    foreach ($matches[1] as $src) {
        $totalImages++;
        
        // Пропускаем placeholders и внешние изображения
        if (strpos($src, 'placeholder') !== false) {
            continue;
        }
        
        // Проверяем изображения с /imgnews/ или notame.ru/imgnews/
        if (strpos($src, '/imgnews/') !== false) {
            // Убираем домен
            $relativeSrc = preg_replace('#^https?://[^/]+#', '', $src);
            
            // Извлекаем имя файла
            $filename = basename($relativeSrc);
            
            // Проверяем существование
            $localPath = public_path($relativeSrc);
            $alternativePath = public_path('/imgnews/' . $filename);
            
            if (!file_exists($localPath) && !file_exists($alternativePath)) {
                // Файл не найден
                if (!isset($missingImages[$filename])) {
                    $missingImages[$filename] = [
                        'url' => $src,
                        'post' => $post->post_name,
                        'title' => $post->post_title
                    ];
                }
            }
        }
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 СТАТИСТИКА:\n";
echo "   Всего изображений: $totalImages\n";
echo "   Недостающих: " . count($missingImages) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (empty($missingImages)) {
    echo "✅ Все изображения на месте!\n";
    exit(0);
}

echo "🔽 СКАЧИВАНИЕ НЕДОСТАЮЩИХ ИЗОБРАЖЕНИЙ:\n\n";

$downloaded = 0;
$errors = 0;

foreach ($missingImages as $filename => $info) {
    echo "📥 $filename\n";
    echo "   Пост: {$info['title']}\n";
    
    // Формируем правильный URL
    $url = $info['url'];
    
    // Если URL относительный, делаем абсолютным
    if (strpos($url, 'http') !== 0) {
        $url = 'https://notame.ru' . $url;
    }
    
    echo "   URL: $url\n";
    
    // Скачиваем изображение
    $imageData = @file_get_contents($url);
    
    if ($imageData === false) {
        echo "   ❌ Не удалось скачать\n\n";
        $errors++;
        continue;
    }
    
    // Сохраняем
    $savePath = public_path('/imgnews/' . $filename);
    file_put_contents($savePath, $imageData);
    
    $size = filesize($savePath);
    $sizeKb = round($size / 1024, 1);
    
    echo "   ✅ Скачано: {$sizeKb} KB\n\n";
    $downloaded++;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 ИТОГО:\n";
echo "   ✅ Скачано: $downloaded\n";
echo "   ❌ Ошибок: $errors\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

