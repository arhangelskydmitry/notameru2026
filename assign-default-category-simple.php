<?php
/**
 * Упрощенная версия скрипта для назначения категорий
 * Работает напрямую с БД без Laravel
 * 
 * URL: https://notame.ru/assign-default-category-simple.php?key=notaadmin2025&action=preview
 * 
 * @version 1.1 (простая версия)
 */

// Отображение ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ============================================================================
// БЕЗОПАСНОСТЬ
// ============================================================================

$SECURITY_KEY = 'notaadmin2025';

if (!isset($_GET['key']) || $_GET['key'] !== $SECURITY_KEY) {
    http_response_code(403);
    die('🔒 Доступ запрещен. Неверный ключ безопасности.');
}

// ============================================================================
// КОНФИГУРАЦИЯ БД (из .env файла Laravel)
// ============================================================================

// Загружаем конфигурацию из .env
$envFile = __DIR__ . '/.env';
$dbConfig = [
    'host' => 'localhost',
    'database' => 'iq210692_notamerurework',
    'username' => 'iq210692_new2026-1',
    'password' => 'jZ7mG2qM6e',
    'port' => '3306',
    'socket' => null,
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Пропускаем комментарии
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Ищем знак = и разделяем по нему
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Убираем кавычки из значения (одинарные или двойные)
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Присваиваем значения
            switch ($key) {
                case 'DB_HOST':
                    $dbConfig['host'] = $value;
                    break;
                case 'DB_DATABASE':
                    $dbConfig['database'] = $value;
                    break;
                case 'DB_USERNAME':
                    $dbConfig['username'] = $value;
                    break;
                case 'DB_PASSWORD':
                    $dbConfig['password'] = $value;
                    break;
                case 'DB_PORT':
                    $dbConfig['port'] = $value;
                    break;
                case 'DB_SOCKET':
                    $dbConfig['socket'] = $value;
                    break;
            }
        }
    }
}

// Название категории
$DEFAULT_CATEGORY_NAME = 'Новости';

// ============================================================================
// ПОДКЛЮЧЕНИЕ К БД
// ============================================================================

try {
    // Строка подключения
    if ($dbConfig['socket']) {
        // Подключение через Unix socket
        $dsn = "mysql:unix_socket={$dbConfig['socket']};dbname={$dbConfig['database']};charset=utf8mb4";
    } else {
        // Подключение через TCP
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
    }
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]
    );
} catch (PDOException $e) {
    // Показываем подробную информацию для отладки
    $errorDetails = '<div class="error-box">';
    $errorDetails .= '<strong>❌ Ошибка подключения к БД:</strong><br><br>';
    $errorDetails .= htmlspecialchars($e->getMessage());
    $errorDetails .= '<br><br><strong>Параметры подключения:</strong><br>';
    $errorDetails .= 'Host: ' . htmlspecialchars($dbConfig['host']) . '<br>';
    $errorDetails .= 'Database: ' . htmlspecialchars($dbConfig['database']) . '<br>';
    $errorDetails .= 'Username: ' . htmlspecialchars($dbConfig['username']) . '<br>';
    $errorDetails .= 'Password: ' . (empty($dbConfig['password']) ? '<em>пусто</em>' : '<em>***скрыт***</em>') . '<br>';
    $errorDetails .= 'Port: ' . htmlspecialchars($dbConfig['port']) . '<br>';
    if ($dbConfig['socket']) {
        $errorDetails .= 'Socket: ' . htmlspecialchars($dbConfig['socket']) . '<br>';
    }
    $errorDetails .= '<br><strong>Файл .env:</strong> ';
    $errorDetails .= file_exists($envFile) ? '✅ найден' : '❌ не найден';
    $errorDetails .= '<br><br><small>Проверьте параметры DB_* в файле .env</small>';
    $errorDetails .= '</div>';
    
    renderPage('❌ Ошибка подключения', $errorDetails);
    exit;
}

// ============================================================================
// ФУНКЦИИ
// ============================================================================

function findCategoryId($pdo, $categoryName) {
    $stmt = $pdo->prepare("
        SELECT tt.term_taxonomy_id, t.term_id, t.name
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'category' AND t.name = ?
        LIMIT 1
    ");
    $stmt->execute([$categoryName]);
    return $stmt->fetch();
}

function findPostsWithoutCategories($pdo) {
    $stmt = $pdo->query("
        SELECT p.ID, p.post_title, p.post_date
        FROM wp_posts p
        WHERE p.post_type = 'post' 
        AND p.post_status = 'publish'
        AND NOT EXISTS (
            SELECT 1
            FROM wp_term_relationships tr
            INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id = p.ID
            AND tt.taxonomy = 'category'
        )
        ORDER BY p.post_date DESC
    ");
    return $stmt->fetchAll();
}

function assignCategoryToPost($pdo, $postId, $categoryTaxonomyId) {
    // Проверяем существование
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM wp_term_relationships 
        WHERE object_id = ? AND term_taxonomy_id = ?
    ");
    $stmt->execute([$postId, $categoryTaxonomyId]);
    $exists = $stmt->fetch()->count > 0;
    
    if ($exists) {
        return false;
    }
    
    // Создаем связь
    $stmt = $pdo->prepare("
        INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
        VALUES (?, ?, 0)
    ");
    $stmt->execute([$postId, $categoryTaxonomyId]);
    return true;
}

function updateCategoryCount($pdo, $categoryTaxonomyId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM wp_term_relationships 
        WHERE term_taxonomy_id = ?
    ");
    $stmt->execute([$categoryTaxonomyId]);
    $count = $stmt->fetch()->count;
    
    $stmt = $pdo->prepare("
        UPDATE wp_term_taxonomy 
        SET count = ? 
        WHERE term_taxonomy_id = ?
    ");
    $stmt->execute([$count, $categoryTaxonomyId]);
    
    return $count;
}

// ============================================================================
// HTML ШАБЛОН
// ============================================================================

function renderPage($title, $content) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - НотаМиру CMS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 900px;
                width: 100%;
                padding: 40px;
            }
            h1 { color: #667eea; margin-bottom: 10px; font-size: 28px; }
            .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
            .info-box {
                background: #e7f3ff;
                border-left: 4px solid #2196F3;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .success-box {
                background: #e8f5e9;
                border-left: 4px solid #4CAF50;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .warning-box {
                background: #fff3e0;
                border-left: 4px solid #ff9800;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .error-box {
                background: #ffebee;
                border-left: 4px solid #f44336;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }
            .stat-value { font-size: 36px; font-weight: bold; margin-bottom: 5px; }
            .stat-label { font-size: 14px; opacity: 0.9; }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                border-radius: 5px;
                text-decoration: none;
                font-weight: 500;
                margin: 10px 10px 10px 0;
                transition: all 0.3s;
                border: none;
                cursor: pointer;
                font-size: 16px;
            }
            .btn-primary { background: #667eea; color: white; }
            .btn-primary:hover { background: #5568d3; transform: translateY(-2px); }
            .btn-success { background: #4CAF50; color: white; }
            .btn-success:hover { background: #45a049; transform: translateY(-2px); }
            .btn-secondary { background: #6c757d; color: white; }
            .posts-list {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                background: #f9f9f9;
            }
            .post-item {
                padding: 10px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
            }
            .post-item:last-child { border-bottom: none; }
            .post-title { font-weight: 500; color: #333; flex: 1; }
            .post-meta { color: #999; font-size: 12px; margin-left: 15px; }
            code {
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
            }
            ul { margin-left: 20px; margin-top: 10px; }
            li { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?= htmlspecialchars($title) ?></h1>
            <div class="subtitle">НотаМиру CMS v2.0 - Инструменты управления контентом</div>
            <?= $content ?>
        </div>
    </body>
    </html>
    <?php
}

// ============================================================================
// ОБРАБОТКА
// ============================================================================

$action = $_GET['action'] ?? 'preview';

try {
    // Найти категорию
    $category = findCategoryId($pdo, $DEFAULT_CATEGORY_NAME);
    
    if (!$category) {
        renderPage('❌ Ошибка', '<div class="error-box">
            <strong>Категория "' . htmlspecialchars($DEFAULT_CATEGORY_NAME) . '" не найдена!</strong><br><br>
            Пожалуйста, создайте эту категорию в админке WordPress.
        </div>');
        exit;
    }
    
    // Статистика по всем статьям
    $totalPublishedPosts = $pdo->query("
        SELECT COUNT(*) as count 
        FROM wp_posts 
        WHERE post_type = 'post' 
        AND post_status = 'publish'
    ")->fetch()->count;
    
    // Статьи с категориями
    $postsWithCategories = $pdo->query("
        SELECT COUNT(DISTINCT p.ID) as count
        FROM wp_posts p
        INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
        INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE p.post_type = 'post' 
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'category'
    ")->fetch()->count;
    
    // Найти статьи без категорий
    $postsWithoutCategories = findPostsWithoutCategories($pdo);
    
    // ПРЕДПРОСМОТР
    if ($action === 'preview') {
        ob_start();
        ?>
        
        <div class="info-box">
            <strong>ℹ️ Режим предпросмотра</strong><br>
            Этот скрипт найдет все статьи без категорий и назначит им категорию <code><?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?></code>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= $totalPublishedPosts ?></div>
                <div class="stat-label">Всего опубликованных статей</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $postsWithCategories ?></div>
                <div class="stat-label">Статей с категориями</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($postsWithoutCategories) ?></div>
                <div class="stat-label">Статей без категорий</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= htmlspecialchars($category->name) ?></div>
                <div class="stat-label">Будет назначена категория</div>
            </div>
        </div>
        
        <?php if (count($postsWithoutCategories) > 0): ?>
            <div class="warning-box">
                <strong>⚠️ Найдены статьи без категорий:</strong><br><br>
                Всего: <strong><?= count($postsWithoutCategories) ?></strong> статей
            </div>
            
            <div class="posts-list">
                <?php foreach ($postsWithoutCategories as $post): ?>
                    <div class="post-item">
                        <div class="post-title">
                            <?= htmlspecialchars($post->post_title) ?>
                        </div>
                        <div class="post-meta">
                            ID: <?= $post->ID ?> | <?= date('d.m.Y', strtotime($post->post_date)) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="info-box">
                <strong>Что произойдет:</strong>
                <ul>
                    <li><strong>✅ Безопасно:</strong> Существующие категории статей НЕ будут затронуты</li>
                    <li><strong>✅ Добавление:</strong> Категория "<?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?>" будет ДОБАВЛЕНА к статьям без категорий</li>
                    <li><strong>📊 Количество:</strong> Будет обработано <?= count($postsWithoutCategories) ?> статей</li>
                    <li><strong>🔒 Проверка:</strong> Категория не будет добавлена повторно, если уже существует</li>
                </ul>
            </div>
            
            <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=execute" class="btn btn-success" 
               onclick="return confirm('Назначить категорию <?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?> всем <?= count($postsWithoutCategories) ?> статьям?')">
                ✅ Выполнить назначение
            </a>
            
        <?php else: ?>
            <div class="success-box">
                <strong>✅ Все хорошо!</strong><br><br>
                Все статьи уже имеют категории.
            </div>
        <?php endif; ?>
        
        <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=preview" class="btn btn-secondary">
            🔄 Обновить
        </a>
        
        <?php
        $content = ob_get_clean();
        renderPage('📋 Назначение категорий - Предпросмотр', $content);
    }
    
    // ВЫПОЛНЕНИЕ
    elseif ($action === 'execute') {
        if (count($postsWithoutCategories) === 0) {
            renderPage('✅ Готово', '<div class="success-box">
                <strong>Статей без категорий не найдено.</strong>
            </div>
            <a href="?key=' . urlencode($SECURITY_KEY) . '&action=preview" class="btn btn-primary">← Назад</a>');
            exit;
        }
        
        $pdo->beginTransaction();
        
        try {
            $assignedCount = 0;
            $skippedCount = 0;
            
            foreach ($postsWithoutCategories as $post) {
                $assigned = assignCategoryToPost($pdo, $post->ID, $category->term_taxonomy_id);
                if ($assigned) {
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            }
            
            $newCategoryCount = updateCategoryCount($pdo, $category->term_taxonomy_id);
            
            $pdo->commit();
            
            ob_start();
            ?>
            
            <div class="success-box">
                <strong>✅ Операция выполнена успешно!</strong>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $assignedCount ?></div>
                    <div class="stat-label">Назначено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $skippedCount ?></div>
                    <div class="stat-label">Пропущено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $newCategoryCount ?></div>
                    <div class="stat-label">Статей в категории</div>
                </div>
            </div>
            
            <div class="success-box">
                <strong>✅ Результат:</strong><br><br>
                Категория "<?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?>" назначена всем статьям без категорий.
            </div>
            
            <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=preview" class="btn btn-primary">
                ← Назад к предпросмотру
            </a>
            
            <?php
            $content = ob_get_clean();
            renderPage('✅ Категории назначены', $content);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    renderPage('❌ Ошибка', '<div class="error-box">
        <strong>Произошла ошибка:</strong><br><br>
        ' . htmlspecialchars($e->getMessage()) . '
    </div>
    <a href="?key=' . htmlspecialchars($SECURITY_KEY) . '&action=preview" class="btn btn-secondary">← Назад</a>');
}
