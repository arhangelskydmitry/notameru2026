#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WordPress\Post;

$post = Post::where('post_name', 'raskryty-imena-zvyozd-so-slozhnym-harakterom')->first();

if (!$post) {
    echo "❌ Пост не найден\n";
    exit(1);
}

echo "✅ Пост найден: {$post->post_title}\n\n";

// Извлекаем все URL изображений
preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);

echo "📷 Найдено изображений в контенте: " . count($matches[1]) . "\n\n";

foreach ($matches[1] as $src) {
    echo "🔗 SRC: $src\n";
    
    // Проверяем существует ли файл
    if (strpos($src, '/imgnews/') === 0) {
        $path = public_path($src);
        if (file_exists($path)) {
            echo "   ✅ Файл существует\n";
        } else {
            echo "   ❌ Файл НЕ существует: $path\n";
            
            // Пробуем найти оригинал
            $filename = basename($src);
            echo "   🔍 Ищем: $filename\n";
        }
    }
    echo "\n";
}

// Проверяем миниатюру
$thumbnailId = $post->getMeta('_thumbnail_id');
if ($thumbnailId) {
    echo "\n📸 Миниатюра ID: $thumbnailId\n";
    $attachment = Post::find($thumbnailId);
    if ($attachment) {
        echo "   GUID: {$attachment->guid}\n";
        
        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post);
        echo "   Обработанный путь: $thumbnail\n";
        
        if (file_exists(public_path($thumbnail))) {
            echo "   ✅ Миниатюра существует\n";
        } else {
            echo "   ❌ Миниатюра НЕ существует\n";
        }
    }
}



