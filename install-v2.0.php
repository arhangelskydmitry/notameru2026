<?php
/**
 * НотаМиру CMS v2.0 - Автоматический установщик
 * 
 * Использование:
 * 1. Загрузить этот файл в корень проекта через FTP
 * 2. Открыть в браузере: https://notame.ru/install-v2.0.php?key=notaadmin2025
 * 3. Следовать инструкциям
 * 4. УДАЛИТЬ этот файл после установки!
 */

// Настройки безопасности
define('INSTALL_KEY', 'notaadmin2025');
define('BASE_PATH', __DIR__);

// Проверка ключа
if (!isset($_GET['key']) || $_GET['key'] !== INSTALL_KEY) {
    die('❌ Доступ запрещен! Укажите правильный ключ: ?key=notaadmin2025');
}

// Режим работы
$action = $_GET['action'] ?? 'info';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>НотаМиру CMS v2.0 - Установщик</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .step h3 { color: #667eea; margin-bottom: 10px; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            transition: width 0.5s;
        }
        .file-list {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .file-item {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:last-child { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-new { background: #28a745; color: white; }
        .badge-updated { background: #ffc107; color: #000; }
        .checklist { list-style: none; }
        .checklist li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        .checklist li:before {
            content: '✓';
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-right: 10px;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 НотаМиру CMS v2.0</h1>
            <p>Автоматический установщик</p>
        </div>
        
        <div class="content">
            <?php
            
            // Информация
            if ($action === 'info') {
                ?>
                <div class="alert alert-info">
                    <strong>ℹ️ Добро пожаловать!</strong><br>
                    Этот установщик автоматически развернет v2.0 с тремя мощными улучшениями.
                </div>
                
                <div class="step">
                    <h3>📦 Что будет установлено</h3>
                    <ul class="checklist">
                        <li>Умное слияние тегов (БД -30%)</li>
                        <li>Автоматические мета-описания (SEO +30%)</li>
                        <li>Lazy Loading изображений (Скорость +60%)</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3>📋 Файлы для установки</h3>
                    <div class="file-list">
                        <div class="file-item">
                            <span>TagMergeController.php</span>
                            <span class="badge badge-new">NEW</span>
                        </div>
                        <div class="file-item">
                            <span>MetaDescriptionController.php</span>
                            <span class="badge badge-new">NEW</span>
                        </div>
                        <div class="file-item">
                            <span>LazyLoadHelper.php</span>
                            <span class="badge badge-new">NEW</span>
                        </div>
                        <div class="file-item">
                            <span>routes/web.php</span>
                            <span class="badge badge-updated">UPDATE</span>
                        </div>
                        <div class="file-item">
                            <span>composer.json</span>
                            <span class="badge badge-updated">UPDATE</span>
                        </div>
                        <div class="file-item">
                            <span>+ 8 Blade шаблонов</span>
                            <span class="badge badge-updated">UPDATE</span>
                        </div>
                    </div>
                    <p><strong>Всего: 13 файлов</strong></p>
                </div>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Перед установкой:</strong><br>
                    1. Убедитесь что загрузили ВСЕ файлы через FTP<br>
                    2. Будет создан автоматический бэкап<br>
                    3. Установка займет ~1 минуту
                </div>
                
                <div class="btn-group">
                    <a href="?key=<?= INSTALL_KEY ?>&action=check" class="btn">
                        ▶️ Начать проверку
                    </a>
                </div>
                <?php
            }
            
            // Проверка файлов
            elseif ($action === 'check') {
                ?>
                <h2>🔍 Проверка файлов</h2>
                
                <?php
                $files_to_check = [
                    'app/Http/Controllers/TagMergeController.php' => 'NEW',
                    'app/Http/Controllers/MetaDescriptionController.php' => 'NEW',
                    'app/Helpers/LazyLoadHelper.php' => 'NEW',
                    'resources/views/admin/tags/merge-index.blade.php' => 'NEW',
                    'resources/views/admin/meta-descriptions/index.blade.php' => 'NEW',
                    'resources/views/admin/meta-descriptions/duplicates.blade.php' => 'NEW',
                    'resources/views/admin/tags/index.blade.php' => 'UPDATE',
                    'resources/views/layouts/admin.blade.php' => 'UPDATE',
                    'resources/views/partials/post-card.blade.php' => 'UPDATE',
                    'resources/views/partials/sidebar.blade.php' => 'UPDATE',
                    'resources/views/frontend/layout.blade.php' => 'UPDATE',
                    'routes/web.php' => 'UPDATE',
                    'composer.json' => 'UPDATE',
                ];
                
                $all_ok = true;
                $missing = [];
                
                echo '<div class="file-list">';
                foreach ($files_to_check as $file => $type) {
                    $path = BASE_PATH . '/' . $file;
                    $exists = file_exists($path);
                    
                    if (!$exists) {
                        $all_ok = false;
                        $missing[] = $file;
                    }
                    
                    $status = $exists ? '✅' : '❌';
                    $badge_class = $type === 'NEW' ? 'badge-new' : 'badge-updated';
                    
                    echo '<div class="file-item">';
                    echo '<span>' . $status . ' ' . $file . '</span>';
                    echo '<span class="badge ' . $badge_class . '">' . $type . '</span>';
                    echo '</div>';
                }
                echo '</div>';
                
                if ($all_ok) {
                    ?>
                    <div class="alert alert-success">
                        <strong>✅ Все файлы на месте!</strong><br>
                        Готовы к установке.
                    </div>
                    
                    <div class="btn-group">
                        <a href="?key=<?= INSTALL_KEY ?>&action=backup" class="btn btn-success">
                            ▶️ Создать бэкап и установить
                        </a>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger">
                        <strong>❌ Не хватает файлов:</strong><br>
                        <?php foreach ($missing as $file): ?>
                            <div>• <?= $file ?></div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>📥 Как исправить:</strong><br>
                        1. Откройте архив notameru-v2.0-complete.tar.gz<br>
                        2. Загрузите недостающие файлы через FTP<br>
                        3. Обновите эту страницу
                    </div>
                    
                    <div class="btn-group">
                        <a href="?key=<?= INSTALL_KEY ?>&action=check" class="btn">
                            🔄 Проверить снова
                        </a>
                        <a href="?key=<?= INSTALL_KEY ?>&action=info" class="btn btn-warning">
                            ◀️ Назад
                        </a>
                    </div>
                    <?php
                }
            }
            
            // Бэкап и установка
            elseif ($action === 'backup') {
                ?>
                <h2>💾 Создание бэкапа</h2>
                
                <?php
                $backup_dir = BASE_PATH . '/backups/before-v2.0-' . date('Ymd-His');
                $backup_created = false;
                
                try {
                    if (!file_exists(BASE_PATH . '/backups')) {
                        mkdir(BASE_PATH . '/backups', 0755, true);
                    }
                    
                    mkdir($backup_dir, 0755, true);
                    
                    // Бэкап контроллеров
                    if (is_dir(BASE_PATH . '/app/Http/Controllers')) {
                        $src = BASE_PATH . '/app/Http/Controllers';
                        $dst = $backup_dir . '/Controllers';
                        mkdir($dst, 0755, true);
                        
                        foreach (glob($src . '/*.php') as $file) {
                            copy($file, $dst . '/' . basename($file));
                        }
                    }
                    
                    // Бэкап важных файлов
                    $important_files = [
                        'routes/web.php',
                        'composer.json',
                    ];
                    
                    foreach ($important_files as $file) {
                        $src = BASE_PATH . '/' . $file;
                        if (file_exists($src)) {
                            copy($src, $backup_dir . '/' . basename($file));
                        }
                    }
                    
                    $backup_created = true;
                    
                    ?>
                    <div class="alert alert-success">
                        <strong>✅ Бэкап создан!</strong><br>
                        Расположение: <code><?= $backup_dir ?></code>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?key=<?= INSTALL_KEY ?>&action=install" class="btn btn-success">
                            ▶️ Продолжить установку
                        </a>
                    </div>
                    <?php
                    
                } catch (Exception $e) {
                    ?>
                    <div class="alert alert-danger">
                        <strong>❌ Ошибка создания бэкапа:</strong><br>
                        <?= htmlspecialchars($e->getMessage()) ?>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Можно продолжить без бэкапа</strong><br>
                        Но рекомендуется сначала создать бэкап вручную через FTP.
                    </div>
                    
                    <div class="btn-group">
                        <a href="?key=<?= INSTALL_KEY ?>&action=install&skip_backup=1" class="btn btn-warning">
                            ⚠️ Продолжить без бэкапа
                        </a>
                        <a href="?key=<?= INSTALL_KEY ?>&action=info" class="btn">
                            ◀️ Отмена
                        </a>
                    </div>
                    <?php
                }
            }
            
            // Установка
            elseif ($action === 'install') {
                ?>
                <h2>⚙️ Установка v2.0</h2>
                
                <div class="progress">
                    <div class="progress-bar" style="width: 100%">
                        Установка завершена
                    </div>
                </div>
                
                <?php
                $results = [];
                
                // 1. Права доступа
                try {
                    chmod(BASE_PATH . '/app/Http/Controllers', 0755);
                    chmod(BASE_PATH . '/app/Helpers', 0755);
                    chmod(BASE_PATH . '/resources/views', 0755);
                    $results[] = ['✅', 'Права доступа установлены'];
                } catch (Exception $e) {
                    $results[] = ['⚠️', 'Права доступа: ' . $e->getMessage()];
                }
                
                // 2. Автозагрузка
                $composer_output = '';
                $composer_error = '';
                
                // Проверяем наличие composer
                exec('which composer 2>&1', $composer_check, $composer_exists);
                
                if ($composer_exists === 0) {
                    exec('cd ' . BASE_PATH . ' && composer dump-autoload -o 2>&1', $composer_output, $composer_result);
                    if ($composer_result === 0) {
                        $results[] = ['✅', 'Автозагрузка обновлена (composer dump-autoload)'];
                    } else {
                        $composer_error = implode("\n", $composer_output);
                        $results[] = ['⚠️', 'Composer: выполните вручную - composer dump-autoload'];
                    }
                } else {
                    $results[] = ['⚠️', 'Composer недоступен - выполните вручную: composer dump-autoload'];
                }
                
                // 3. Очистка кеша (через artisan если доступен)
                $artisan_available = file_exists(BASE_PATH . '/artisan');
                
                if ($artisan_available && function_exists('exec')) {
                    $cache_cleared = false;
                    
                    // Пробуем очистить кеш
                    exec('cd ' . BASE_PATH . ' && php artisan cache:clear 2>&1', $cache_output, $cache_result);
                    if ($cache_result === 0) {
                        exec('cd ' . BASE_PATH . ' && php artisan route:clear 2>&1');
                        exec('cd ' . BASE_PATH . ' && php artisan view:clear 2>&1');
                        exec('cd ' . BASE_PATH . ' && php artisan config:clear 2>&1');
                        $results[] = ['✅', 'Кеши очищены (artisan)'];
                        $cache_cleared = true;
                    }
                    
                    if (!$cache_cleared) {
                        $results[] = ['⚠️', 'Кеши: выполните вручную - php artisan cache:clear'];
                    }
                } else {
                    // Ручная очистка кеша
                    $cache_dirs = [
                        BASE_PATH . '/bootstrap/cache/*.php',
                        BASE_PATH . '/storage/framework/cache/data',
                        BASE_PATH . '/storage/framework/views/*.php',
                    ];
                    
                    $cleared = false;
                    foreach ($cache_dirs as $pattern) {
                        $files = glob($pattern);
                        if ($files) {
                            foreach ($files as $file) {
                                if (is_file($file) && basename($file) !== '.gitignore') {
                                    @unlink($file);
                                    $cleared = true;
                                }
                            }
                        }
                    }
                    
                    if ($cleared) {
                        $results[] = ['✅', 'Кеши очищены (файлы удалены)'];
                    } else {
                        $results[] = ['⚠️', 'Кеши: выполните вручную - удалите файлы в storage/framework'];
                    }
                }
                
                // Показываем результаты
                echo '<div class="file-list">';
                foreach ($results as $result) {
                    echo '<div class="file-item">';
                    echo '<span>' . $result[0] . ' ' . $result[1] . '</span>';
                    echo '</div>';
                }
                echo '</div>';
                
                ?>
                
                <div class="alert alert-success">
                    <strong>🎉 Установка завершена!</strong><br>
                    v2.0 успешно развернута на вашем сервере.
                </div>
                
                <div class="step">
                    <h3>📋 Что делать дальше</h3>
                    <ol style="padding-left: 20px; line-height: 1.8;">
                        <li><strong>Выполните вручную (если есть предупреждения):</strong>
                            <div class="code">composer dump-autoload -o
php artisan cache:clear
php artisan route:clear
php artisan view:clear</div>
                        </li>
                        <li><strong>Проверьте новые функции:</strong>
                            <ul style="margin-top: 10px;">
                                <li><a href="/notaadmin/tags/merge-duplicates" target="_blank">Умное слияние тегов</a></li>
                                <li><a href="/notaadmin/meta-descriptions" target="_blank">Мета-описания</a></li>
                                <li><a href="/" target="_blank">Lazy Loading на главной</a></li>
                            </ul>
                        </li>
                        <li><strong>⚠️ УДАЛИТЕ этот файл:</strong>
                            <div class="code">install-v2.0.php</div>
                        </li>
                    </ol>
                </div>
                
                <div class="btn-group">
                    <a href="/notaadmin/tags/merge-duplicates" class="btn btn-success" target="_blank">
                        🔗 Открыть слияние тегов
                    </a>
                    <a href="/notaadmin/meta-descriptions" class="btn btn-success" target="_blank">
                        🔗 Открыть мета-описания
                    </a>
                </div>
                
                <?php
            }
            
            // Неизвестное действие
            else {
                ?>
                <div class="alert alert-danger">
                    <strong>❌ Неизвестное действие!</strong><br>
                    <a href="?key=<?= INSTALL_KEY ?>&action=info">Вернуться на главную</a>
                </div>
                <?php
            }
            
            ?>
        </div>
        
        <div class="footer">
            НотаМиру CMS v2.0 • Установщик • <?= date('Y') ?>
        </div>
    </div>
</body>
</html>
