#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🖼️  ОБНОВЛЕНИЕ ССЫЛОК НА ИЗОБРАЖЕНИЯ В БД                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "🔍 Поиск постов с изображениями JPG/JPEG/PNG...\n\n";

$postsToUpdate = Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->where(function($q) {
        $q->where('post_content', 'like', '%.jpg%')
          ->orWhere('post_content', 'like', '%.jpeg%')
          ->orWhere('post_content', 'like', '%.png%')
          ->orWhere('post_content', 'like', '%.JPG%')
          ->orWhere('post_content', 'like', '%.JPEG%')
          ->orWhere('post_content', 'like', '%.PNG%');
    })
    ->count();

echo "📊 Найдено постов: $postsToUpdate\n\n";

if ($postsToUpdate == 0) {
    echo "✅ Все ссылки уже обновлены!\n";
    exit(0);
}

echo "🔄 Обновляем ссылки на изображения...\n";

$updated = 0;
$errors = [];

Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->where(function($q) {
        $q->where('post_content', 'like', '%.jpg%')
          ->orWhere('post_content', 'like', '%.jpeg%')
          ->orWhere('post_content', 'like', '%.png%')
          ->orWhere('post_content', 'like', '%.JPG%')
          ->orWhere('post_content', 'like', '%.JPEG%')
          ->orWhere('post_content', 'like', '%.PNG%');
    })
    ->chunk(100, function($posts) use (&$updated, &$errors) {
        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $newContent = $originalContent;
            
            // Заменяем расширения на webp
            $newContent = preg_replace('/\.jpg(\?|"|\'|\s|>)/i', '.webp$1', $newContent);
            $newContent = preg_replace('/\.jpeg(\?|"|\'|\s|>)/i', '.webp$1', $newContent);
            $newContent = preg_replace('/\.png(\?|"|\'|\s|>)/i', '.webp$1', $newContent);
            
            // Заменяем пути wp-content/uploads на /imgnews/
            $newContent = preg_replace(
                '/https?:\/\/[^\/]+\/wp-content\/uploads\/([^"\'>\s]+)/',
                '/imgnews/$1',
                $newContent
            );
            
            if ($originalContent !== $newContent) {
                try {
                    $post->post_content = $newContent;
                    $post->save();
                    $updated++;
                    echo ".";
                    
                    if ($updated % 50 == 0) {
                        echo " [$updated]\n";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Post ID {$post->ID}: " . $e->getMessage();
                }
            }
        }
    });

echo "\n\n";
echo "✅ Обновлено постов: $updated\n";

if (!empty($errors)) {
    echo "\n⚠️  Ошибки при обновлении:\n";
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "  • $error\n";
    }
    if (count($errors) > 10) {
        echo "  • ... и ещё " . (count($errors) - 10) . " ошибок\n";
    }
}

echo "\n🎉 Обновление завершено!\n";

