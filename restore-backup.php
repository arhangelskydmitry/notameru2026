<?php
/**
 * Скрипт Автоматического Восстановления из Бекапа
 * 
 * Использование:
 * https://notame.ru/restore-backup.php?backup=FILENAME&key=SECRET&mode=MODE
 * 
 * Параметры:
 * - backup: Имя файла бекапа (например: backup_full_2026-01-24_03-00-00.tar.gz)
 * - key: Секретный ключ (установите свой)
 * - mode: preview|database|files|full
 * 
 * Режимы:
 * - preview: Показать что будет восстановлено (безопасно)
 * - database: Восстановить только БД
 * - files: Восстановить только файлы
 * - full: Полное восстановление (БД + файлы)
 */

// ============================================
// НАСТРОЙКИ (ОБЯЗАТЕЛЬНО ИЗМЕНИТЕ!)
// ============================================

// Секретный ключ доступа (ИЗМЕНИТЕ!)
define('RESTORE_SECRET_KEY', 'change_this_to_random_string_2026');

// Путь к Laravel
define('LARAVEL_ROOT', __DIR__);

// Путь к бекапам
define('BACKUP_PATH', LARAVEL_ROOT . '/storage/app/backups');

// Путь для временных файлов
define('TEMP_PATH', LARAVEL_ROOT . '/storage/app/temp_restore');

// База данных (из .env)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'iq210692_notamerurework');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'iq210692_notamerurework');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// ============================================
// ФУНКЦИИ
// ============================================

function logMessage($message, $type = 'info') {
    $time = date('Y-m-d H:i:s');
    $log = "[$time] [$type] $message\n";
    file_put_contents(LARAVEL_ROOT . '/storage/logs/restore.log', $log, FILE_APPEND);
    
    if ($type === 'error') {
        echo "<div class='alert alert-danger'>❌ $message</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ $message</div>";
    }
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function checkPermissions() {
    if (!is_writable(LARAVEL_ROOT . '/storage')) {
        die('❌ Нет прав на запись в /storage/');
    }
    
    if (!is_writable(LARAVEL_ROOT . '/public/images')) {
        die('❌ Нет прав на запись в /public/images/');
    }
}

function extractBackup($backupFile) {
    logMessage("Распаковка бекапа: $backupFile");
    
    // Создаем временную директорию
    if (!file_exists(TEMP_PATH)) {
        mkdir(TEMP_PATH, 0755, true);
    }
    
    // Распаковываем
    $fullPath = BACKUP_PATH . '/' . $backupFile;
    $command = "tar -xzf " . escapeshellarg($fullPath) . " -C " . escapeshellarg(TEMP_PATH);
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        logMessage("Ошибка распаковки бекапа. Код: $returnCode", 'error');
        return false;
    }
    
    // Находим распакованную директорию
    $dirs = glob(TEMP_PATH . '/*', GLOB_ONLYDIR);
    if (empty($dirs)) {
        logMessage("Не найдена распакованная директория", 'error');
        return false;
    }
    
    logMessage("Бекап распакован: " . basename($dirs[0]));
    return $dirs[0];
}

function previewBackup($extractedPath) {
    $manifest = json_decode(file_get_contents($extractedPath . '/manifest.json'), true);
    
    echo "<h3>📦 Содержимое Бекапа</h3>";
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Параметр</th><th>Значение</th></tr>";
    echo "<tr><td>Дата создания</td><td>{$manifest['created_at']}</td></tr>";
    echo "<tr><td>Тип</td><td>{$manifest['type']}</td></tr>";
    echo "<tr><td>Версия Laravel</td><td>{$manifest['laravel_version']}</td></tr>";
    
    if (isset($manifest['database'])) {
        echo "<tr><td>База данных</td><td>";
        echo "Таблиц: {$manifest['database']['tables_count']}<br>";
        echo "Размер: " . formatBytes($manifest['database']['size']);
        echo "</td></tr>";
    }
    
    if (isset($manifest['files'])) {
        echo "<tr><td>Файлы</td><td>";
        echo "Всего: {$manifest['files']['total_files']}<br>";
        echo "Размер: " . formatBytes($manifest['files']['total_size']);
        echo "</td></tr>";
    }
    
    echo "</table>";
    
    // Проверяем что будет перезаписано
    echo "<h3>⚠️ Что будет перезаписано</h3>";
    echo "<ul>";
    if (file_exists($extractedPath . '/database')) {
        echo "<li><strong>База данных:</strong> Все таблицы будут заменены</li>";
    }
    if (file_exists($extractedPath . '/files')) {
        echo "<li><strong>Файлы:</strong> Изображения будут заменены</li>";
    }
    echo "</ul>";
    
    echo "<div class='alert alert-warning'>";
    echo "<strong>🔐 РЕКОМЕНДАЦИЯ:</strong> Создайте бекап текущего состояния перед восстановлением!";
    echo "</div>";
}

function restoreDatabase($extractedPath) {
    logMessage("Начало восстановления БД...");
    
    $dbPath = $extractedPath . '/database';
    if (!file_exists($dbPath)) {
        logMessage("База данных не найдена в бекапе", 'error');
        return false;
    }
    
    // Ищем SQL файл
    $sqlFile = null;
    if (file_exists($dbPath . '/database.sql.gz')) {
        // Разархивируем
        logMessage("Разархивация database.sql.gz...");
        exec("gunzip " . escapeshellarg($dbPath . '/database.sql.gz'));
        $sqlFile = $dbPath . '/database.sql';
    } elseif (file_exists($dbPath . '/database.sql')) {
        $sqlFile = $dbPath . '/database.sql';
    }
    
    if (!$sqlFile || !file_exists($sqlFile)) {
        logMessage("SQL файл не найден", 'error');
        return false;
    }
    
    logMessage("Импорт БД... (может занять несколько минут)");
    
    // Создаем команду импорта
    $command = sprintf(
        'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USERNAME),
        escapeshellarg(DB_PASSWORD),
        escapeshellarg(DB_DATABASE),
        escapeshellarg($sqlFile)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        logMessage("Ошибка импорта БД: " . implode("\n", $output), 'error');
        return false;
    }
    
    logMessage("✅ База данных восстановлена успешно!");
    return true;
}

function restoreFiles($extractedPath) {
    logMessage("Начало восстановления файлов...");
    
    $filesPath = $extractedPath . '/files/public/images';
    if (!file_exists($filesPath)) {
        logMessage("Файлы не найдены в бекапе", 'error');
        return false;
    }
    
    $targetPath = LARAVEL_ROOT . '/public/images';
    
    // Копируем файлы
    $command = "cp -R " . escapeshellarg($filesPath) . "/* " . escapeshellarg($targetPath) . "/ 2>&1";
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        logMessage("Ошибка копирования файлов: " . implode("\n", $output), 'error');
        return false;
    }
    
    // Устанавливаем права
    exec("chmod -R 755 " . escapeshellarg($targetPath));
    
    logMessage("✅ Файлы восстановлены успешно!");
    return true;
}

function cleanup() {
    if (file_exists(TEMP_PATH)) {
        exec("rm -rf " . escapeshellarg(TEMP_PATH));
        logMessage("Временные файлы удалены");
    }
}

// ============================================
// ОСНОВНАЯ ЛОГИКА
// ============================================

// Проверка ключа
if (!isset($_GET['key']) || $_GET['key'] !== RESTORE_SECRET_KEY) {
    die('❌ Доступ запрещен. Неверный ключ.');
}

// Получение параметров
$backupFile = $_GET['backup'] ?? null;
$mode = $_GET['mode'] ?? 'preview';

if (!$backupFile) {
    die('❌ Не указан файл бекапа. Используйте: ?backup=FILENAME&key=KEY&mode=MODE');
}

// HTML заголовок
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление из Бекапа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert { margin: 10px 0; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔄 Восстановление из Бекапа</h1>
    <p><strong>Бекап:</strong> <code><?= htmlspecialchars($backupFile) ?></code></p>
    <p><strong>Режим:</strong> <span class="badge bg-primary"><?= htmlspecialchars($mode) ?></span></p>
    <hr>
    
<?php

// Проверка существования бекапа
$backupFullPath = BACKUP_PATH . '/' . $backupFile;
if (!file_exists($backupFullPath)) {
    die("<div class='alert alert-danger'>❌ Бекап не найден: $backupFile</div>");
}

logMessage("Размер бекапа: " . formatBytes(filesize($backupFullPath)));

// Проверка прав
checkPermissions();

// Распаковка
$extractedPath = extractBackup($backupFile);
if (!$extractedPath) {
    die("<div class='alert alert-danger'>❌ Не удалось распаковать бекап</div>");
}

// Выполнение в зависимости от режима
switch ($mode) {
    case 'preview':
        previewBackup($extractedPath);
        echo "<h3>🚀 Готовы восстановить?</h3>";
        echo "<p>Выберите режим:</p>";
        echo "<a href='?backup=$backupFile&key=" . RESTORE_SECRET_KEY . "&mode=database' class='btn btn-warning'>Только БД</a> ";
        echo "<a href='?backup=$backupFile&key=" . RESTORE_SECRET_KEY . "&mode=files' class='btn btn-info'>Только Файлы</a> ";
        echo "<a href='?backup=$backupFile&key=" . RESTORE_SECRET_KEY . "&mode=full' class='btn btn-danger'>Полное Восстановление</a>";
        break;
        
    case 'database':
        echo "<h3>🗄️ Восстановление Базы Данных</h3>";
        if (restoreDatabase($extractedPath)) {
            echo "<div class='alert alert-success'>✅ База данных успешно восстановлена!</div>";
            echo "<p>Рекомендуется очистить кеш:</p>";
            echo "<code>DELETE FROM cache; DELETE FROM cache_locks;</code>";
        }
        break;
        
    case 'files':
        echo "<h3>📁 Восстановление Файлов</h3>";
        if (restoreFiles($extractedPath)) {
            echo "<div class='alert alert-success'>✅ Файлы успешно восстановлены!</div>";
        }
        break;
        
    case 'full':
        echo "<h3>🔄 Полное Восстановление</h3>";
        $dbSuccess = restoreDatabase($extractedPath);
        $filesSuccess = restoreFiles($extractedPath);
        
        if ($dbSuccess && $filesSuccess) {
            echo "<div class='alert alert-success'><h4>✅ Восстановление завершено успешно!</h4>";
            echo "<p>База данных и файлы восстановлены.</p>";
            echo "<p><strong>Следующие шаги:</strong></p>";
            echo "<ol>";
            echo "<li>Очистите кеш приложения</li>";
            echo "<li>Проверьте главную страницу: <a href='/'>notame.ru</a></li>";
            echo "<li>Проверьте админку: <a href='/notaadmin/'>Админка</a></li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Восстановление завершено с ошибками. Проверьте логи.</div>";
        }
        break;
        
    default:
        die("<div class='alert alert-danger'>❌ Неизвестный режим: $mode</div>");
}

// Очистка
cleanup();

?>
    
    <hr>
    <p class="text-muted"><small>Логи сохранены в: storage/logs/restore.log</small></p>
    <p><a href="/notaadmin/" class="btn btn-secondary">← Вернуться в Админку</a></p>
</div>
</body>
</html>
