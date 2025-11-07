#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;
use App\Models\PostSeo;
use Illuminate\Support\Str;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🤖 ГЕНЕРАЦИЯ SEO-ДАННЫХ ДЛЯ ПОСТОВ БЕЗ SEO                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Проверяем посты без SEO
$postsWithoutSeo = Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->whereDoesntHave('seo', function($query) {
        $query->whereNotNull('seo_title')
              ->orWhereNotNull('seo_description');
    })
    ->count();

echo "📊 Найдено постов без SEO: " . $postsWithoutSeo . "\n\n";

if ($postsWithoutSeo == 0) {
    echo "✅ Все посты уже имеют SEO данные!\n";
    exit(0);
}

echo "🔄 Генерируем SEO данные...\n";

$processed = 0;
$posts = Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->with('seo')
    ->chunk(100, function($posts) use (&$processed) {
        foreach ($posts as $post) {
            $seo = $post->seo;
            $updated = false;
            
            if (!$seo) {
                $seo = new PostSeo();
                $seo->post_id = $post->ID;
            }
            
            // Генерируем SEO title если нет
            if (empty($seo->seo_title)) {
                $seo->seo_title = $post->post_title;
                $updated = true;
            }
            
            // Генерируем SEO description если нет
            if (empty($seo->seo_description)) {
                if ($post->post_excerpt) {
                    $seo->seo_description = Str::limit(strip_tags($post->post_excerpt), 160);
                } else {
                    // Берём первые 160 символов из контента
                    $content = strip_tags($post->post_content);
                    $content = preg_replace('/\s+/', ' ', $content);
                    $seo->seo_description = Str::limit(trim($content), 160);
                }
                $updated = true;
            }
            
            // Генерируем OG title если нет
            if (empty($seo->og_title)) {
                $seo->og_title = $post->post_title;
                $updated = true;
            }
            
            // Генерируем OG description если нет
            if (empty($seo->og_description)) {
                $seo->og_description = $seo->seo_description;
                $updated = true;
            }
            
            // Устанавливаем OG image из миниатюры
            if (empty($seo->og_image)) {
                $thumbnailId = $post->getMeta('_thumbnail_id');
                if ($thumbnailId) {
                    $attachment = Post::find($thumbnailId);
                    if ($attachment) {
                        $file = $attachment->getMeta('_wp_attached_file');
                        if ($file) {
                            $seo->og_image = 'http://localhost:8001/wp-content/uploads/' . $file;
                            $updated = true;
                        }
                    }
                }
            }
            
            // Устанавливаем canonical URL если нет
            if (empty($seo->canonical_url) && $post->post_name) {
                $seo->canonical_url = route('post', $post->post_name);
                $updated = true;
            }
            
            if ($updated) {
                $seo->save();
                $processed++;
                echo ".";
                if ($processed % 50 == 0) {
                    echo " [$processed]\n";
                }
            }
        }
    });

echo "\n\n✅ Обработано постов: $processed\n";
echo "🎉 SEO данные сгенерированы!\n";

