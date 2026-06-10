<?php
/**
 * Очистка кешей Laravel через веб-интерфейс
 * Использовать ТОЛЬКО если автоустановщик не смог очистить кеши
 * 
 * URL: https://notame.ru/clear-cache.php?key=notaadmin2025
 * УДАЛИТЬ после использования!
 */

define('SECURITY_KEY', 'notaadmin2025');

if (!isset($_GET['key']) || $_GET['key'] !== SECURITY_KEY) {
    die('❌ Доступ запрещен!');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Очистка кешей Laravel</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #667eea; }
        .result { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #667eea; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .btn { padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Очистка кешей Laravel</h1>
        
        <?php
        $results = [];
        
        // 1. Очистка кеша конфигурации
        $config_cache = __DIR__ . '/bootstrap/cache/config.php';
        if (file_exists($config_cache)) {
            if (@unlink($config_cache)) {
                $results[] = ['success', 'Config cache очищен'];
            } else {
                $results[] = ['error', 'Не удалось удалить config cache'];
            }
        } else {
            $results[] = ['success', 'Config cache не найден (это нормально)'];
        }
        
        // 2. Очистка кеша маршрутов
        $routes_cache = __DIR__ . '/bootstrap/cache/routes-v7.php';
        if (file_exists($routes_cache)) {
            if (@unlink($routes_cache)) {
                $results[] = ['success', 'Routes cache очищен'];
            } else {
                $results[] = ['error', 'Не удалось удалить routes cache'];
            }
        } else {
            $results[] = ['success', 'Routes cache не найден (это нормально)'];
        }
        
        // 3. Очистка compiled views
        $views_path = __DIR__ . '/storage/framework/views/';
        if (is_dir($views_path)) {
            $cleared = 0;
            $files = glob($views_path . '*.php');
            foreach ($files as $file) {
                if (basename($file) !== '.gitignore' && is_file($file)) {
                    if (@unlink($file)) {
                        $cleared++;
                    }
                }
            }
            $results[] = ['success', "Compiled views очищены ($cleared файлов)"];
        }
        
        // 4. Очистка application cache
        $cache_path = __DIR__ . '/storage/framework/cache/data/';
        if (is_dir($cache_path)) {
            $cleared = 0;
            $dirs = glob($cache_path . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (@unlink($file)) {
                            $cleared++;
                        }
                    }
                }
            }
            $results[] = ['success', "Application cache очищен ($cleared файлов)"];
        }
        
        // 5. Очистка session files (опционально)
        $sessions_path = __DIR__ . '/storage/framework/sessions/';
        if (is_dir($sessions_path)) {
            $cleared = 0;
            $files = glob($sessions_path . '*');
            foreach ($files as $file) {
                if (basename($file) !== '.gitignore' && is_file($file)) {
                    // Удаляем только старые сессии (>1 час)
                    if (time() - filemtime($file) > 3600) {
                        if (@unlink($file)) {
                            $cleared++;
                        }
                    }
                }
            }
            if ($cleared > 0) {
                $results[] = ['success', "Старые сессии очищены ($cleared файлов)"];
            }
        }
        
        // Показываем результаты
        foreach ($results as $result) {
            $class = $result[0];
            $message = $result[1];
            echo '<div class="result ' . $class . '">';
            echo ($class === 'success' ? '✅ ' : '❌ ') . $message;
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #d1ecf1; border-radius: 4px;">
            <strong>✅ Кеши очищены!</strong><br><br>
            
            <strong>Что делать дальше:</strong>
            <ol>
                <li>Обновите страницу админки (F5)</li>
                <li>Проверьте новые функции:
                    <ul>
                        <li><a href="/notaadmin/tags/merge-duplicates" target="_blank">Умное слияние</a></li>
                        <li><a href="/notaadmin/meta-descriptions" target="_blank">Мета-описания</a></li>
                    </ul>
                </li>
                <li><strong>УДАЛИТЕ этот файл: clear-cache.php</strong></li>
            </ol>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="/" class="btn">← На главную</a>
            <a href="/notaadmin" class="btn">← В админку</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>💡 Важно:</strong> После успешной очистки удалите этот файл через FTP!
        </div>
    </div>
</body>
</html>
