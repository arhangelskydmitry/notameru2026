<?php
/**
 * Скрипт для назначения категории "Новости" статьям без категорий
 * 
 * URL: https://notame.ru/assign-default-category.php?key=notaadmin2025&action=preview
 * URL: https://notame.ru/assign-default-category.php?key=notaadmin2025&action=execute
 * 
 * @author НотаМиру CMS
 * @version 1.0
 * @date 2026-01-25
 */

// Включаем отображение всех ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
// КОНФИГУРАЦИЯ
// ============================================================================

try {
    define('BASE_PATH', __DIR__);
    
    // Проверяем наличие файлов
    if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
        die('❌ Файл vendor/autoload.php не найден. Путь: ' . BASE_PATH . '/vendor/autoload.php');
    }
    
    if (!file_exists(BASE_PATH . '/bootstrap/app.php')) {
        die('❌ Файл bootstrap/app.php не найден. Путь: ' . BASE_PATH . '/bootstrap/app.php');
    }
    
    require BASE_PATH . '/vendor/autoload.php';
    
    $app = require_once BASE_PATH . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
} catch (Exception $e) {
    die('❌ Ошибка загрузки Laravel: ' . $e->getMessage() . '<br><br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine());
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ============================================================================
// НАСТРОЙКИ
// ============================================================================

// Название категории, которую нужно назначить
$DEFAULT_CATEGORY_NAME = 'Новости';

// ID категории (будет определен автоматически)
$defaultCategoryId = null;

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
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
            h1 {
                color: #667eea;
                margin-bottom: 10px;
                font-size: 28px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 14px;
            }
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
            .stat-value {
                font-size: 36px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .stat-label {
                font-size: 14px;
                opacity: 0.9;
            }
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
            .btn-primary {
                background: #667eea;
                color: white;
            }
            .btn-primary:hover {
                background: #5568d3;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            .btn-success {
                background: #4CAF50;
                color: white;
            }
            .btn-success:hover {
                background: #45a049;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
            }
            .btn-danger {
                background: #f44336;
                color: white;
            }
            .btn-danger:hover {
                background: #da190b;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
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
                align-items: center;
            }
            .post-item:last-child {
                border-bottom: none;
            }
            .post-title {
                font-weight: 500;
                color: #333;
                flex: 1;
            }
            .post-meta {
                color: #999;
                font-size: 12px;
                margin-left: 15px;
            }
            code {
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 14px;
            }
            ul {
                margin-left: 20px;
                margin-top: 10px;
            }
            li {
                margin: 5px 0;
            }
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
// ФУНКЦИИ
// ============================================================================

/**
 * Найти ID категории по названию
 */
function findCategoryId($categoryName) {
    $term = DB::table('wp_terms')
        ->join('wp_term_taxonomy', 'wp_terms.term_id', '=', 'wp_term_taxonomy.term_id')
        ->where('wp_term_taxonomy.taxonomy', 'category')
        ->where('wp_terms.name', $categoryName)
        ->select('wp_term_taxonomy.term_taxonomy_id', 'wp_terms.term_id', 'wp_terms.name')
        ->first();
    
    return $term;
}

/**
 * Найти статьи без категорий
 */
function findPostsWithoutCategories() {
    // Статьи, которые не имеют ни одной связи с категориями
    $postsWithoutCategories = DB::table('wp_posts as p')
        ->leftJoin('wp_term_relationships as tr', 'p.ID', '=', 'tr.object_id')
        ->leftJoin('wp_term_taxonomy as tt', function($join) {
            $join->on('tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
                 ->where('tt.taxonomy', '=', 'category');
        })
        ->where('p.post_type', 'post')
        ->where('p.post_status', 'publish')
        ->whereNull('tt.term_taxonomy_id')
        ->select('p.ID', 'p.post_title', 'p.post_date')
        ->orderBy('p.post_date', 'desc')
        ->get();
    
    return $postsWithoutCategories;
}

/**
 * Назначить категорию статье
 */
function assignCategoryToPost($postId, $categoryTaxonomyId) {
    // Проверяем, нет ли уже этой связи
    $exists = DB::table('wp_term_relationships')
        ->where('object_id', $postId)
        ->where('term_taxonomy_id', $categoryTaxonomyId)
        ->exists();
    
    if ($exists) {
        return false; // Уже существует
    }
    
    // Создаем связь
    DB::table('wp_term_relationships')->insert([
        'object_id' => $postId,
        'term_taxonomy_id' => $categoryTaxonomyId,
        'term_order' => 0,
    ]);
    
    return true;
}

/**
 * Обновить счетчик категории
 */
function updateCategoryCount($categoryTaxonomyId) {
    $count = DB::table('wp_term_relationships')
        ->where('term_taxonomy_id', $categoryTaxonomyId)
        ->count();
    
    DB::table('wp_term_taxonomy')
        ->where('term_taxonomy_id', $categoryTaxonomyId)
        ->update(['count' => $count]);
    
    return $count;
}

// ============================================================================
// ОБРАБОТКА ДЕЙСТВИЙ
// ============================================================================

$action = $_GET['action'] ?? 'preview';

try {
    // Найти категорию "Новости"
    $category = findCategoryId($DEFAULT_CATEGORY_NAME);
    
    if (!$category) {
        renderPage('❌ Ошибка', '<div class="error-box">
            <strong>Категория "' . htmlspecialchars($DEFAULT_CATEGORY_NAME) . '" не найдена!</strong><br><br>
            Пожалуйста, создайте эту категорию в админке WordPress или измените название в скрипте.
        </div>');
        exit;
    }
    
    // Найти статьи без категорий
    $postsWithoutCategories = findPostsWithoutCategories();
    
    // РЕЖИМ: ПРЕДПРОСМОТР
    if ($action === 'preview') {
        ob_start();
        ?>
        
        <div class="info-box">
            <strong>ℹ️ Режим предпросмотра</strong><br>
            Этот скрипт найдет все статьи без категорий и назначит им категорию <code><?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?></code>
        </div>
        
        <div class="stats">
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
                <strong>Что произойдет при выполнении:</strong>
                <ul>
                    <li>Всем <?= count($postsWithoutCategories) ?> статьям будет назначена категория "<?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?>"</li>
                    <li>Будут созданы связи в таблице <code>wp_term_relationships</code></li>
                    <li>Счетчик категории будет обновлен</li>
                    <li>Статьи станут видны в рубрике "<?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?>"</li>
                </ul>
            </div>
            
            <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=execute" class="btn btn-success" 
               onclick="return confirm('Назначить категорию <?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?> всем <?= count($postsWithoutCategories) ?> статьям?')">
                ✅ Выполнить назначение
            </a>
            
        <?php else: ?>
            <div class="success-box">
                <strong>✅ Все хорошо!</strong><br><br>
                Все статьи уже имеют категории. Действий не требуется.
            </div>
        <?php endif; ?>
        
        <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=preview" class="btn btn-secondary">
            🔄 Обновить
        </a>
        
        <?php
        $content = ob_get_clean();
        renderPage('📋 Назначение категорий - Предпросмотр', $content);
    }
    
    // РЕЖИМ: ВЫПОЛНЕНИЕ
    elseif ($action === 'execute') {
        if (count($postsWithoutCategories) === 0) {
            renderPage('✅ Готово', '<div class="success-box">
                <strong>Статей без категорий не найдено.</strong><br><br>
                Действий не требуется.
            </div>
            <a href="?key=' . urlencode($SECURITY_KEY) . '&action=preview" class="btn btn-primary">← Назад</a>');
            exit;
        }
        
        DB::beginTransaction();
        
        try {
            $assignedCount = 0;
            $skippedCount = 0;
            $processedPosts = [];
            
            foreach ($postsWithoutCategories as $post) {
                $assigned = assignCategoryToPost($post->ID, $category->term_taxonomy_id);
                
                if ($assigned) {
                    $assignedCount++;
                    $processedPosts[] = $post;
                } else {
                    $skippedCount++;
                }
            }
            
            // Обновляем счетчик категории
            $newCategoryCount = updateCategoryCount($category->term_taxonomy_id);
            
            DB::commit();
            
            // Логирование
            Log::info("Массовое назначение категорий: назначено {$assignedCount}, пропущено {$skippedCount}");
            
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
            
            <?php if ($assignedCount > 0): ?>
                <div class="info-box">
                    <strong>📊 Обработано статей: <?= $assignedCount ?></strong><br><br>
                    Категория <code><?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?></code> назначена следующим статьям:
                </div>
                
                <div class="posts-list">
                    <?php foreach (array_slice($processedPosts, 0, 100) as $post): ?>
                        <div class="post-item">
                            <div class="post-title">
                                ✅ <?= htmlspecialchars($post->post_title) ?>
                            </div>
                            <div class="post-meta">
                                ID: <?= $post->ID ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($processedPosts) > 100): ?>
                        <div class="post-item">
                            <div class="post-title">
                                <em>... и еще <?= count($processedPosts) - 100 ?> статей</em>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="success-box">
                <strong>✅ Результат:</strong><br><br>
                Всем статьям без категорий присвоена категория "<?= htmlspecialchars($DEFAULT_CATEGORY_NAME) ?>".
                Статьи теперь отображаются в соответствующей рубрике на сайте.
            </div>
            
            <a href="?key=<?= urlencode($SECURITY_KEY) ?>&action=preview" class="btn btn-primary">
                ← Назад к предпросмотру
            </a>
            
            <?php
            $content = ob_get_clean();
            renderPage('✅ Категории назначены', $content);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    else {
        renderPage('❌ Ошибка', '<div class="error-box">
            <strong>Неизвестное действие</strong><br><br>
            Используйте: <code>action=preview</code> или <code>action=execute</code>
        </div>');
    }
    
} catch (Exception $e) {
    Log::error('Ошибка в скрипте назначения категорий: ' . $e->getMessage());
    
    renderPage('❌ Ошибка', '<div class="error-box">
        <strong>Произошла ошибка:</strong><br><br>
        ' . htmlspecialchars($e->getMessage()) . '<br><br>
        <small>Проверьте логи Laravel для подробностей.</small>
    </div>
    <a href="?key=' . htmlspecialchars($SECURITY_KEY) . '&action=preview" class="btn btn-secondary">← Назад</a>');
}
