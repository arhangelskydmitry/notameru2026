#!/usr/bin/env php
<?php
/**
 * Скрипт проверки готовности к развертыванию
 * 
 * Использование:
 * php check-deployment-readiness.php
 */

echo "🔍 ПРОВЕРКА ГОТОВНОСТИ К РАЗВЕРТЫВАНИЮ v2.0\n";
echo str_repeat("=", 60) . "\n\n";

$errors = 0;
$warnings = 0;
$success = 0;

// Проверка необходимых файлов
$requiredFiles = [
    'app/Http/Controllers/FrontendController.php' => 'FrontendController',
    'app/Http/Controllers/TagController.php' => 'TagController',
    'app/Http/Middleware/SecurityHeaders.php' => 'SecurityHeaders',
    'resources/views/errors/404.blade.php' => '404 страница',
    'resources/views/partials/sidebar.blade.php' => 'Сайдбар с тегами',
    'routes/api.php' => 'API маршруты',
    'composer.json' => 'Composer config',
    'app/Helpers/TextHelper.php' => 'TextHelper',
];

echo "📁 Проверка критичных файлов:\n";
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "  ✅ $description: $file\n";
        $success++;
    } else {
        echo "  ❌ $description: $file - ОТСУТСТВУЕТ!\n";
        $errors++;
    }
}

echo "\n";

// Проверка временных скриптов
$tempFiles = [
    'test-404-debug.php' => 'Диагностика 404',
    'test-category-query.php' => 'Проверка категорий',
    'assign-default-category-simple.php' => 'Назначение категорий',
];

echo "🔧 Проверка временных скриптов:\n";
foreach ($tempFiles as $file => $description) {
    if (file_exists($file)) {
        echo "  ✅ $description: $file - готов к использованию\n";
        $success++;
    } else {
        echo "  ⚠️  $description: $file - не найден\n";
        $warnings++;
    }
}

echo "\n";

// Проверка старых файлов (должны быть удалены)
$oldFiles = [
    'resources/views/frontend/404.blade.php' => 'Старая 404 страница',
    'test-auto-tagging.php' => 'Старый скрипт тегирования',
    'clean-all-symbols.php' => 'Скрипт очистки символов',
];

echo "🗑️  Проверка старых файлов (должны быть удалены):\n";
foreach ($oldFiles as $file => $description) {
    if (!file_exists($file)) {
        echo "  ✅ $description: удален\n";
        $success++;
    } else {
        echo "  ⚠️  $description: $file - еще существует\n";
        $warnings++;
    }
}

echo "\n";

// Проверка содержимого файлов
echo "🔍 Проверка содержимого ключевых файлов:\n";

// Проверка composer.json
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    if (isset($composer['autoload']['files']) && 
        in_array('app/Helpers/TextHelper.php', $composer['autoload']['files'])) {
        echo "  ✅ composer.json содержит TextHelper в autoload\n";
        $success++;
    } else {
        echo "  ⚠️  composer.json не содержит TextHelper в autoload\n";
        $warnings++;
    }
}

// Проверка SecurityHeaders.php на наличие mc.yandex.com
if (file_exists('app/Http/Middleware/SecurityHeaders.php')) {
    $content = file_get_contents('app/Http/Middleware/SecurityHeaders.php');
    if (strpos($content, 'mc.yandex.com') !== false) {
        echo "  ✅ SecurityHeaders.php содержит mc.yandex.com\n";
        $success++;
    } else {
        echo "  ❌ SecurityHeaders.php НЕ содержит mc.yandex.com!\n";
        $errors++;
    }
}

// Проверка sidebar.blade.php на наличие облака тегов
if (file_exists('resources/views/partials/sidebar.blade.php')) {
    $content = file_get_contents('resources/views/partials/sidebar.blade.php');
    if (strpos($content, 'tags-cloud') !== false) {
        echo "  ✅ sidebar.blade.php содержит облако тегов\n";
        $success++;
    } else {
        echo "  ❌ sidebar.blade.php НЕ содержит облако тегов!\n";
        $errors++;
    }
}

// Проверка 404.blade.php на наличие бесконечной прокрутки
if (file_exists('resources/views/errors/404.blade.php')) {
    $content = file_get_contents('resources/views/errors/404.blade.php');
    if (strpos($content, 'load-more-posts') !== false || 
        strpos($content, 'IntersectionObserver') !== false) {
        echo "  ✅ 404.blade.php содержит бесконечную прокрутку\n";
        $success++;
    } else {
        echo "  ❌ 404.blade.php НЕ содержит бесконечную прокрутку!\n";
        $errors++;
    }
}

echo "\n";
echo str_repeat("=", 60) . "\n";

// Итоговая статистика
echo "\n📊 ИТОГИ ПРОВЕРКИ:\n\n";
echo "  ✅ Успешно: $success\n";
echo "  ⚠️  Предупреждения: $warnings\n";
echo "  ❌ Ошибки: $errors\n\n";

if ($errors > 0) {
    echo "❌ ВНИМАНИЕ! Обнаружены критичные проблемы!\n";
    echo "   Перед развертыванием необходимо исправить ошибки.\n\n";
    exit(1);
} elseif ($warnings > 0) {
    echo "⚠️  ВНИМАНИЕ! Есть предупреждения.\n";
    echo "   Развертывание возможно, но рекомендуется проверить предупреждения.\n\n";
    exit(0);
} else {
    echo "✅ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "   Система готова к развертыванию на production.\n\n";
    exit(0);
}
