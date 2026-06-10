<?php
/**
 * 🔍 ТЕСТ ПОДКЛЮЧЕНИЯ К БД
 */

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'test123') {
    die('Добавьте ?key=test123');
}

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:2rem;font-family:monospace'>";
echo "🔍 ТЕСТ ПОДКЛЮЧЕНИЯ\n\n";

// 1. Читаем .env
echo "1. Читаю .env... ";
flush();

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ .env не найден");
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
echo "✅\n";
flush();

$host = isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost';
$port = isset($env['DB_PORT']) ? $env['DB_PORT'] : '3306';
$db = isset($env['DB_DATABASE']) ? $env['DB_DATABASE'] : '';
$user = isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : '';
$pass = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';

echo "\n2. Конфигурация:\n";
echo "   Host: $host:$port\n";
echo "   DB: $db\n";
echo "   User: $user\n\n";
flush();

// 2. Подключение
echo "3. Подключение... ";
flush();

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, array(
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ));
    echo "✅\n";
    flush();
} catch (PDOException $e) {
    die("❌ " . $e->getMessage());
}

// 3. Тест запроса
echo "4. Тест запроса... ";
flush();
$count = $pdo->query("SELECT COUNT(*) FROM wp_posts")->fetchColumn();
echo "✅ (wp_posts: $count записей)\n\n";
flush();

// 4. Список таблиц
echo "5. Таблицы:\n";
flush();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "   - $t: $c\n";
    flush();
}

$pdo = null;

echo "\n✅ ВСЁ РАБОТАЕТ!\n";
echo "</pre>";
