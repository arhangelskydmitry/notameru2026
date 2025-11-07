<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WordPress\PostMeta;
use Illuminate\Support\Facades\DB;

echo "=== Проверка SEO-данных в WordPress ===\n\n";

// Проверяем популярные SEO плагины
$seoKeys = PostMeta::whereIn('meta_key', [
    '_yoast_wpseo_title',
    '_yoast_wpseo_metadesc',
    '_yoast_wpseo_focuskw',
    '_yoast_wpseo_canonical',
    '_aioseop_title',
    '_aioseop_description',
    '_aioseop_keywords',
    'seo_title',
    'seo_description',
    'seo_keywords'
])
->select('meta_key', DB::raw('COUNT(*) as count'))
->groupBy('meta_key')
->get();

echo "1. SEO meta keys найдено:\n";
foreach($seoKeys as $key) {
    echo "   - {$key->meta_key}: {$key->count} записей\n";
}

if ($seoKeys->isEmpty()) {
    echo "   Специфичные SEO мета-ключи не найдены.\n";
}

echo "\n2. Проверяем все мета-ключи, содержащие 'seo' или 'yoast':\n";
$allSeoKeys = PostMeta::where(function($query) {
    $query->where('meta_key', 'like', '%seo%')
          ->orWhere('meta_key', 'like', '%yoast%')
          ->orWhere('meta_key', 'like', '%aioseop%');
})
->select('meta_key', DB::raw('COUNT(*) as count'))
->groupBy('meta_key')
->orderBy('count', 'desc')
->limit(20)
->get();

foreach($allSeoKeys as $key) {
    echo "   - {$key->meta_key}: {$key->count} записей\n";
}

if ($allSeoKeys->isEmpty()) {
    echo "   SEO мета-ключи не найдены.\n";
}

// Проверяем пример поста с SEO данными
echo "\n3. Пример SEO-данных из первого поста:\n";
$post = \App\Models\WordPress\Post::with('meta')->first();
if ($post) {
    echo "   Пост: {$post->post_title}\n";
    echo "   Все мета-поля:\n";
    $seoRelated = $post->meta->filter(function($meta) {
        return stripos($meta->meta_key, 'seo') !== false || 
               stripos($meta->meta_key, 'yoast') !== false ||
               stripos($meta->meta_key, 'description') !== false ||
               stripos($meta->meta_key, 'keyword') !== false;
    });
    
    if ($seoRelated->count() > 0) {
        foreach($seoRelated as $meta) {
            $value = strlen($meta->meta_value) > 100 ? substr($meta->meta_value, 0, 100) . '...' : $meta->meta_value;
            echo "   - {$meta->meta_key}: {$value}\n";
        }
    } else {
        echo "   SEO мета-поля не найдены у этого поста.\n";
    }
}

echo "\n4. Статистика по всем мета-ключам (топ 30):\n";
$topMetaKeys = PostMeta::select('meta_key', DB::raw('COUNT(*) as count'))
->groupBy('meta_key')
->orderBy('count', 'desc')
->limit(30)
->get();

foreach($topMetaKeys as $key) {
    echo "   - {$key->meta_key}: {$key->count} записей\n";
}

echo "\n=== Проверка завершена ===\n";

