#!/bin/bash

# 🔍 Быстрая проверка SEO-модуля
# Запуск: ./check-seo-status.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 ПРОВЕРКА SEO-МОДУЛЯ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 1. Статистика записей
echo "📊 Статистика SEO-данных:"
php artisan tinker --execute="
\$total = \App\Models\PostSeo::count();
\$titles = \App\Models\PostSeo::whereNotNull('seo_title')->count();
\$descriptions = \App\Models\PostSeo::whereNotNull('seo_description')->count();
\$images = \App\Models\PostSeo::whereNotNull('og_image')->count();

echo '  ✅ Всего SEO записей: ' . \$total . PHP_EOL;
echo '  📝 С SEO Title: ' . \$titles . ' (' . round(\$titles/\$total*100, 1) . '%)' . PHP_EOL;
echo '  📄 С SEO Description: ' . \$descriptions . ' (' . round(\$descriptions/\$total*100, 1) . '%)' . PHP_EOL;
echo '  🖼️  С OG Images: ' . \$images . ' (' . round(\$images/\$total*100, 1) . '%)' . PHP_EOL;
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎯 Пример SEO-данных (случайный пост):"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

php artisan tinker --execute="
\$post = \App\Models\WordPress\Post::with('seo')
    ->where('post_type', 'post')
    ->where('post_status', 'publish')
    ->inRandomOrder()
    ->first();

if (\$post && \$post->seo) {
    \$seoService = app(\App\Services\SeoService::class);
    \$seo = \$seoService->getPageSeo(\$post);
    \$analysis = \$seoService->analyzeSeoScore(\$post);
    
    echo '📰 Пост: ' . mb_substr(\$post->post_title, 0, 70) . PHP_EOL . PHP_EOL;
    
    echo '🌐 Frontend SEO:' . PHP_EOL;
    echo '  Title: ' . mb_substr(\$seo['title'], 0, 80) . PHP_EOL;
    echo '  Description: ' . mb_substr(\$seo['description'], 0, 100) . '...' . PHP_EOL;
    echo '  Canonical: ' . \$seo['canonical'] . PHP_EOL . PHP_EOL;
    
    echo '📱 Social Media:' . PHP_EOL;
    echo '  OG Title: ' . mb_substr(\$seo['og']['title'], 0, 60) . PHP_EOL;
    echo '  OG Image: ' . (\$seo['og']['image'] ? '✅ есть' : '❌ нет') . PHP_EOL;
    echo '  Twitter Card: ' . \$seo['twitter']['card'] . PHP_EOL . PHP_EOL;
    
    echo '📊 SEO Score:' . PHP_EOL;
    echo '  Оценка: ' . \$analysis['score'] . '/100' . PHP_EOL;
    echo '  Статус: ';
    
    switch(\$analysis['status']) {
        case 'excellent':
            echo '🟢 Отлично' . PHP_EOL;
            break;
        case 'good':
            echo '🔵 Хорошо' . PHP_EOL;
            break;
        case 'fair':
            echo '🟡 Удовлетворительно' . PHP_EOL;
            break;
        default:
            echo '🔴 Требует улучшения' . PHP_EOL;
    }
    
    if (count(\$analysis['issues']) > 0) {
        echo PHP_EOL . '  ⚠️  Проблемы:' . PHP_EOL;
        foreach (\$analysis['issues'] as \$issue) {
            echo '    • ' . \$issue . PHP_EOL;
        }
    }
    
    if (count(\$analysis['recommendations']) > 0) {
        echo PHP_EOL . '  💡 Рекомендации:' . PHP_EOL;
        foreach (\$analysis['recommendations'] as \$rec) {
            echo '    • ' . \$rec . PHP_EOL;
        }
    }
}
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📈 Распределение по SEO Score:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

php artisan tinker --execute="
\$posts = \App\Models\WordPress\Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->limit(50)
    ->get();

\$seoService = app(\App\Services\SeoService::class);

\$excellent = 0;
\$good = 0;
\$fair = 0;
\$poor = 0;

foreach (\$posts as \$post) {
    \$analysis = \$seoService->analyzeSeoScore(\$post);
    switch(\$analysis['status']) {
        case 'excellent': \$excellent++; break;
        case 'good': \$good++; break;
        case 'fair': \$fair++; break;
        case 'poor': \$poor++; break;
    }
}

echo '  🟢 Отлично (80+): ' . \$excellent . ' постов' . PHP_EOL;
echo '  🔵 Хорошо (60-79): ' . \$good . ' постов' . PHP_EOL;
echo '  🟡 Удовл. (40-59): ' . \$fair . ' постов' . PHP_EOL;
echo '  🔴 Плохо (<40): ' . \$poor . ' постов' . PHP_EOL;
echo PHP_EOL;
echo '  (проверено первых 50 постов)' . PHP_EOL;
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Проверка завершена!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "💡 Команды для управления SEO:"
echo "  • php artisan migrate:seo --force  - Перемигрировать данные"
echo "  • php artisan route:list | grep post  - Проверить роуты"
echo "  • php artisan optimize:clear  - Очистить кэш"
echo ""



