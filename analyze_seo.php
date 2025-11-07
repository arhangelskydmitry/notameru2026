<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WordPress\Post;
use App\Models\WordPress\PostMeta;

echo "=== Детальный анализ AIOSEO данных ===\n\n";

// Найдем пост с заполненными SEO данными
$postWithSeo = Post::whereHas('meta', function($query) {
    $query->where('meta_key', '_aioseo_title')
          ->where('meta_value', '!=', '');
})->first();

if ($postWithSeo) {
    echo "Найден пост с SEO данными:\n";
    echo "ID: {$postWithSeo->ID}\n";
    echo "Заголовок: {$postWithSeo->post_title}\n";
    echo "Дата: {$postWithSeo->post_date}\n\n";
    
    echo "SEO поля:\n";
    $seoFields = [
        '_aioseo_title' => 'SEO Title',
        '_aioseo_description' => 'SEO Description',
        '_aioseo_keywords' => 'SEO Keywords',
        '_aioseo_og_title' => 'OG Title',
        '_aioseo_og_description' => 'OG Description',
        '_aioseo_og_article_section' => 'OG Section',
        '_aioseo_og_article_tags' => 'OG Tags',
        '_aioseo_twitter_title' => 'Twitter Title',
        '_aioseo_twitter_description' => 'Twitter Description',
    ];
    
    foreach ($seoFields as $key => $label) {
        $meta = $postWithSeo->meta->where('meta_key', $key)->first();
        $value = $meta ? $meta->meta_value : '(пусто)';
        echo "  - {$label} ({$key}):\n    {$value}\n\n";
    }
} else {
    echo "Не найдено постов с заполненными SEO данными.\n";
    echo "Проверим любой недавний пост:\n\n";
    
    $recentPost = Post::where('post_type', 'post')
        ->where('post_status', 'publish')
        ->orderBy('post_date', 'desc')
        ->first();
    
    if ($recentPost) {
        echo "Последний пост:\n";
        echo "ID: {$recentPost->ID}\n";
        echo "Заголовок: {$recentPost->post_title}\n\n";
        
        echo "SEO поля:\n";
        $seoFields = [
            '_aioseo_title' => 'SEO Title',
            '_aioseo_description' => 'SEO Description',
            '_aioseo_keywords' => 'SEO Keywords',
            '_aioseo_og_title' => 'OG Title',
            '_aioseo_og_description' => 'OG Description',
        ];
        
        foreach ($seoFields as $key => $label) {
            $meta = $recentPost->getMeta($key);
            $value = $meta ?: '(пусто)';
            echo "  - {$label}: {$value}\n";
        }
    }
}

echo "\n=== Статистика заполненности SEO полей ===\n";
$stats = [];
$seoKeys = [
    '_aioseo_title',
    '_aioseo_description',
    '_aioseo_keywords',
    '_aioseo_og_title',
    '_aioseo_og_description',
];

foreach ($seoKeys as $key) {
    $filled = PostMeta::where('meta_key', $key)
        ->where('meta_value', '!=', '')
        ->count();
    $total = PostMeta::where('meta_key', $key)->count();
    $percent = $total > 0 ? round(($filled / $total) * 100, 2) : 0;
    echo "{$key}: {$filled}/{$total} заполнено ({$percent}%)\n";
}

echo "\n=== Анализ завершен ===\n";




