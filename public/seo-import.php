<?php
/**
 * Веб-интерфейс для импорта настроек SEO AI
 * 
 * Использование:
 * 1. Загрузите этот файл на сервер в папку public/
 * 2. Откройте в браузере: https://notame.ru/seo-import.php
 * 3. Загрузите файл seo-settings.json
 * 
 * ВАЖНО: После использования удалите этот файл с сервера!
 */

// Защита от прямого доступа (можно добавить проверку авторизации)
// Раскомментируйте, если нужна авторизация:
/*
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    die('Доступ запрещен. Необходима авторизация.');
}
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

// Подключаемся к базе данных
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['settings_file'])) {
    if (!$database || !$username) {
        $error = 'Учетные данные базы данных не найдены в .env';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $file = $_FILES['settings_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Ошибка загрузки файла');
            }

            $json = file_get_contents($file['tmp_name']);
            $settings = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Неверный формат JSON: ' . json_last_error_msg());
            }

            $imported = 0;
            $skipped = 0;
            $openaiKey = null;

            foreach ($settings as $key => $value) {
                if (empty($value)) {
                    $skipped++;
                    continue;
                }

                if ($key === 'openai_api_key') {
                    $openaiKey = $value;
                    continue;
                }

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) 
                        VALUES (?, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE 
                            `value` = VALUES(`value`),
                            `updated_at` = NOW()
                    ");
                    $stmt->execute([$key, $value]);
                    $imported++;
                } catch (PDOException $e) {
                    error_log("Ошибка при импорте {$key}: " . $e->getMessage());
                }
            }

            $success = "✅ Импортировано: {$imported} настроек";
            if ($skipped > 0) {
                $success .= ", пропущено: {$skipped}";
            }
            if ($openaiKey) {
                $success .= "\n\n⚠️ OpenAI API ключ нужно добавить в .env файл:\nOPENAI_API_KEY=" . substr($openaiKey, 0, 20) . "...";
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт настроек SEO AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-upload"></i> Импорт настроек SEO AI</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> 
                                <pre style="white-space: pre-wrap; margin: 0;"><?= htmlspecialchars($success) ?></pre>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="settings_file" class="form-label fw-bold">
                                    <i class="fas fa-file-code"></i> JSON файл с настройками
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="settings_file" 
                                       name="settings_file" 
                                       accept=".json,.txt"
                                       required>
                                <small class="form-text text-muted">
                                    Загрузите файл seo-settings.json, полученный после экспорта настроек
                                </small>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Инструкция:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>На локальном сервере выполните: <code>php scripts/export-seo-settings.php > seo-settings.json</code></li>
                                    <li>Загрузите полученный файл через форму выше</li>
                                    <li>После импорта добавьте OPENAI_API_KEY в .env файл на сервере</li>
                                    <li>Очистите кеш: <code>php artisan config:clear && php artisan cache:clear</code></li>
                                </ol>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Импортировать настройки
                            </button>
                        </form>

                        <div class="card mt-4">
                            <div class="card-header">
                                <i class="fas fa-info-circle"></i> Что будет импортировано
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li><strong>seo_ai_provider</strong> - Предпочтительный провайдер</li>
                                    <li><strong>gigachat_client_id</strong> - Client ID для GigaChat</li>
                                    <li><strong>gigachat_client_secret</strong> - Client Secret для GigaChat</li>
                                    <li><strong>gigachat_scope</strong> - Scope для GigaChat</li>
                                    <li><strong>chatinfo_api_key</strong> - API ключ для ChatInfo</li>
                                    <li><strong>openai_api_key</strong> - API ключ для OpenAI (нужно добавить в .env)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Безопасность:</strong> После использования удалите этот файл (seo-import.php) с сервера!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
