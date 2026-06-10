<?php
/**
 * Скрипт для экспорта настроек SEO AI из базы данных
 * 
 * Использование:
 * php scripts/export-seo-settings.php > seo-settings.json
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
    echo json_encode(['error' => 'Database credentials not found in .env'], JSON_PRETTY_PRINT);
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
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()], JSON_PRETTY_PRINT);
    exit(1);
}

$settings = [
    'seo_ai_provider',
    'gigachat_client_id',
    'gigachat_client_secret',
    'gigachat_scope',
    'chatinfo_api_key',
];

$export = [];

// Получаем настройки из базы данных
$placeholders = str_repeat('?,', count($settings) - 1) . '?';
$stmt = $pdo->prepare("SELECT `key`, `value` FROM `settings` WHERE `key` IN ({$placeholders})");
$stmt->execute($settings);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['value'])) {
        $export[$row['key']] = $row['value'];
    }
}

// Также экспортируем OpenAI API ключ из .env (если есть)
$openaiKey = getenv('OPENAI_API_KEY');
if ($openaiKey) {
    $export['openai_api_key'] = $openaiKey;
}

echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
