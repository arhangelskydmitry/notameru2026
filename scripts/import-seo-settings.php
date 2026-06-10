<?php
/**
 * Скрипт для импорта настроек SEO AI в базу данных
 * 
 * Использование:
 * php scripts/import-seo-settings.php seo-settings.json
 */

// Загружаем переменные окружения
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Подключаемся к базе данных напрямую
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

if (!$database || !$username) {
    echo "Ошибка: Учетные данные базы данных не найдены в .env\n";
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "Ошибка подключения к базе данных: " . $e->getMessage() . "\n";
    exit(1);
}

if ($argc < 2) {
    echo "Использование: php scripts/import-seo-settings.php seo-settings.json\n";
    exit(1);
}

$jsonFile = $argv[1];

if (!file_exists($jsonFile)) {
    echo "Ошибка: Файл {$jsonFile} не найден\n";
    exit(1);
}

$json = file_get_contents($jsonFile);
$settings = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Ошибка: Неверный формат JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "Импорт настроек SEO AI...\n\n";

$imported = 0;
$skipped = 0;

foreach ($settings as $key => $value) {
    if (empty($value)) {
        echo "⏭️  Пропущено: {$key} (пустое значение)\n";
        $skipped++;
        continue;
    }
    
    // OpenAI API ключ нужно добавить в .env, а не в БД
    if ($key === 'openai_api_key') {
        echo "⚠️  OpenAI API ключ нужно добавить в .env файл вручную:\n";
        echo "   OPENAI_API_KEY={$value}\n\n";
        continue;
    }
    
    try {
        // Используем INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $pdo->prepare("
            INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) 
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                `value` = VALUES(`value`),
                `updated_at` = NOW()
        ");
        $stmt->execute([$key, $value]);
        
        $maskedValue = strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value;
        echo "✅ Импортировано: {$key} = {$maskedValue}\n";
        $imported++;
    } catch (PDOException $e) {
        echo "❌ Ошибка при импорте {$key}: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "✅ Импортировано: {$imported} настроек\n";
echo "⏭️  Пропущено: {$skipped} настроек\n";
echo "\n";
echo "⚠️  Не забудьте:\n";
echo "   1. Добавить OPENAI_API_KEY в .env файл на сервере\n";
echo "   2. Очистить кеш: php artisan config:clear && php artisan cache:clear\n";
