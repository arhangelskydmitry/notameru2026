#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🔧 ИСПРАВЛЕНИЕ ПУТЕЙ К ИЗОБРАЖЕНИЯМ                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📝 Проблема: изображения в /imgnews/ БЕЗ подпапок\n";
echo "   БД содержит: /imgnews/2025/06/image.webp\n";
echo "   Файл реально: /imgnews/image.webp\n\n";

echo "🔄 Удаляем подпапки из путей...\n\n";

$updated = 0;

Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->where(function($q) {
        $q->where('post_content', 'like', '%/imgnews/%/%/%')
          ->orWhere('post_content', 'like', '%wp-content/uploads/%/%/%');
    })
    ->chunk(100, function($posts) use (&$updated) {
        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $newContent = $originalContent;
            
            // Убираем подпапки из путей /imgnews/YYYY/MM/file.webp -> /imgnews/file.webp
            $newContent = preg_replace('#/imgnews/\d{4}/\d{2}/([^"\'>\s]+)#', '/imgnews/$1', $newContent);
            
            // Убираем wp-content/uploads/YYYY/MM/ и меняем на /imgnews/
            $newContent = preg_replace('#/wp-content/uploads/\d{4}/\d{2}/([^"\'>\s]+)#', '/imgnews/$1', $newContent);
            $newContent = preg_replace('#https?://[^/]+/wp-content/uploads/\d{4}/\d{2}/([^"\'>\s]+)#', '/imgnews/$1', $newContent);
            
            if ($originalContent !== $newContent) {
                $post->post_content = $newContent;
                $post->save();
                $updated++;
                echo ".";
                
                if ($updated % 50 == 0) {
                    echo " [$updated]\n";
                }
            }
        }
    });

echo "\n\n✅ Обновлено постов: $updated\n";
echo "🎉 Пути исправлены!\n\n";

echo "🔍 Теперь проверим несколько постов...\n";

$sample = Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->where('post_content', 'like', '%/imgnews/%')
    ->limit(3)
    ->get();

foreach ($sample as $post) {
    preg_match_all('#/imgnews/([^"\'>\s]+)#', $post->post_content, $matches);
    if (!empty($matches[1])) {
        echo "\nПост ID {$post->ID}:\n";
        foreach (array_slice($matches[1], 0, 3) as $img) {
            $fullPath = public_path('imgnews/' . $img);
            $exists = file_exists($fullPath) ? '✅' : '❌';
            echo "  $exists /imgnews/$img\n";
        }
    }
}

echo "\n🎉 Готово!\n";

