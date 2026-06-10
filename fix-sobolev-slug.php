<?php
/**
 * Исправление slug статьи с символом №
 * URL: https://notame.ru/andrej-sobolev-pereizdal-svoi-luchshie-pesni-albom-№1
 */

// Безопасность
define('SECURITY_KEY', 'notaadmin2025');
if (!isset($_GET['key']) || $_GET['key'] !== SECURITY_KEY) {
    die('❌ Доступ запрещен! Используйте: ?key=notaadmin2025');
}

$action = $_GET['action'] ?? 'preview';

// Подключение к Laravel
require_once __DIR__ . '/bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Исправление slug со знаком №</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .alert { padding: 15px; border-radius: 4px; margin: 15px 0; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #0c5460; color: #0c5460; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .post-box { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #667eea; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .code { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Исправление slug статьи</h1>
        
        <?php
        
        if ($action === 'preview') {
            // Ищем статью со знаком № в slug
            $posts = Post::where('post_name', 'like', '%№%')
                ->where('post_type', 'post')
                ->get();
            
            if ($posts->isEmpty()) {
                // Поиск по заголовку
                $posts = Post::where('post_title', 'like', '%Андрей Соболев%')
                    ->orWhere('post_title', 'like', '%андрей соболев%')
                    ->orWhere('post_name', 'like', '%sobolev%')
                    ->where('post_type', 'post')
                    ->where('post_status', 'publish')
                    ->get();
            }
            
            if ($posts->isEmpty()) {
                // Поиск по всем статьям с проблемными символами
                $posts = Post::where('post_name', 'REGEXP', '[^a-z0-9\-]')
                    ->where('post_type', 'post')
                    ->where('post_status', 'publish')
                    ->limit(20)
                    ->get();
                
                echo '<div class="alert alert-info">';
                echo '<strong>ℹ️ Статья "Андрей Соболев" не найдена напрямую.</strong><br>';
                echo 'Показаны все статьи с недопустимыми символами в slug (найдено: ' . $posts->count() . ')';
                echo '</div>';
            }
            
            if ($posts->isEmpty()) {
                echo '<div class="alert alert-danger">';
                echo '<strong>❌ Статьи не найдены!</strong>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Найдено статей с проблемными slug: ' . $posts->count() . '</strong>';
                echo '</div>';
                
                foreach ($posts as $post) {
                    $old_slug = $post->post_name;
                    
                    // Создаем правильный slug
                    $new_slug = $old_slug;
                    
                    // Удаляем знак №
                    $new_slug = str_replace('№', '-', $new_slug);
                    
                    // Удаляем другие недопустимые символы
                    $new_slug = preg_replace('/[^a-z0-9\-]/', '', $new_slug);
                    
                    // Убираем повторяющиеся дефисы
                    $new_slug = preg_replace('/-+/', '-', $new_slug);
                    
                    // Убираем дефисы в начале и конце
                    $new_slug = trim($new_slug, '-');
                    
                    $has_problem = ($old_slug !== $new_slug);
                    
                    if (!$has_problem) continue;
                    
                    echo '<div class="post-box">';
                    echo '<strong>📝 Статья ID: ' . $post->ID . '</strong><br>';
                    echo '<strong>Заголовок:</strong> ' . htmlspecialchars($post->post_title) . '<br>';
                    echo '<strong>Дата:</strong> ' . $post->post_date->format('d.m.Y H:i') . '<br>';
                    echo '<br>';
                    
                    echo '<strong>❌ Старый slug:</strong><br>';
                    echo '<pre>' . htmlspecialchars($old_slug) . '</pre>';
                    
                    echo '<strong>✅ Новый slug:</strong><br>';
                    echo '<pre>' . htmlspecialchars($new_slug) . '</pre>';
                    
                    echo '<strong>❌ Старый URL:</strong><br>';
                    echo '<div class="code">https://notame.ru/' . htmlspecialchars($old_slug) . '</div>';
                    
                    echo '<strong>✅ Новый URL:</strong><br>';
                    echo '<div class="code">https://notame.ru/' . htmlspecialchars($new_slug) . '</div>';
                    
                    echo '<form method="get" style="margin-top: 15px;">';
                    echo '<input type="hidden" name="key" value="' . SECURITY_KEY . '">';
                    echo '<input type="hidden" name="action" value="fix">';
                    echo '<input type="hidden" name="post_id" value="' . $post->ID . '">';
                    echo '<button type="submit" class="btn btn-success">✅ Исправить этот slug</button>';
                    echo '</form>';
                    
                    echo '</div>';
                }
                
                // Массовое исправление
                $problem_count = $posts->filter(function($p) {
                    $old = $p->post_name;
                    $new = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9\-]/', '', str_replace('№', '-', $old))), '-');
                    return $old !== $new;
                })->count();
                
                if ($problem_count > 0) {
                    echo '<div class="alert alert-info" style="margin-top: 20px;">';
                    echo '<strong>🔧 Массовое исправление</strong><br>';
                    echo 'Найдено статей с проблемами: ' . $problem_count . '<br><br>';
                    echo '<form method="get">';
                    echo '<input type="hidden" name="key" value="' . SECURITY_KEY . '">';
                    echo '<input type="hidden" name="action" value="fix_all">';
                    echo '<button type="submit" class="btn btn-danger">⚠️ Исправить все (' . $problem_count . ' шт)</button>';
                    echo '</form>';
                    echo '</div>';
                }
            }
        }
        
        elseif ($action === 'fix') {
            $post_id = $_GET['post_id'] ?? 0;
            $post = Post::find($post_id);
            
            if (!$post) {
                echo '<div class="alert alert-danger">❌ Статья не найдена!</div>';
            } else {
                $old_slug = $post->post_name;
                
                // Создаем правильный slug
                $new_slug = str_replace('№', '-', $old_slug);
                $new_slug = preg_replace('/[^a-z0-9\-]/', '', $new_slug);
                $new_slug = preg_replace('/-+/', '-', $new_slug);
                $new_slug = trim($new_slug, '-');
                
                if ($old_slug === $new_slug) {
                    echo '<div class="alert alert-info">ℹ️ Slug уже корректный, изменений не требуется.</div>';
                } else {
                    // Проверяем уникальность
                    $exists = Post::where('post_name', $new_slug)
                        ->where('ID', '!=', $post->ID)
                        ->where('post_type', 'post')
                        ->exists();
                    
                    if ($exists) {
                        $new_slug .= '-' . $post->ID;
                    }
                    
                    // Обновляем
                    $post->post_name = $new_slug;
                    $post->save();
                    
                    echo '<div class="alert alert-success">';
                    echo '<strong>✅ Slug успешно исправлен!</strong><br><br>';
                    echo '<strong>Статья:</strong> ' . htmlspecialchars($post->post_title) . '<br>';
                    echo '<strong>Старый slug:</strong> <code>' . htmlspecialchars($old_slug) . '</code><br>';
                    echo '<strong>Новый slug:</strong> <code>' . htmlspecialchars($new_slug) . '</code><br><br>';
                    echo '<strong>Новый URL:</strong><br>';
                    echo '<a href="https://notame.ru/' . htmlspecialchars($new_slug) . '" target="_blank">';
                    echo 'https://notame.ru/' . htmlspecialchars($new_slug);
                    echo '</a>';
                    echo '</div>';
                    
                    echo '<div class="alert alert-info">';
                    echo '<strong>📋 Что делать дальше:</strong><br>';
                    echo '1. Проверьте что статья открывается по новой ссылке<br>';
                    echo '2. Добавьте 301 редирект со старого URL на новый (если нужно)<br>';
                    echo '3. Обновите sitemap.xml';
                    echo '</div>';
                }
                
                echo '<br><a href="?key=' . SECURITY_KEY . '&action=preview" class="btn">◀️ Назад к списку</a>';
            }
        }
        
        elseif ($action === 'fix_all') {
            // Массовое исправление
            $posts = Post::where('post_name', 'like', '%№%')
                ->orWhere('post_name', 'REGEXP', '[^a-z0-9\-]')
                ->where('post_type', 'post')
                ->where('post_status', 'publish')
                ->get();
            
            $fixed = 0;
            $skipped = 0;
            $results = [];
            
            foreach ($posts as $post) {
                $old_slug = $post->post_name;
                
                $new_slug = str_replace('№', '-', $old_slug);
                $new_slug = preg_replace('/[^a-z0-9\-]/', '', $new_slug);
                $new_slug = preg_replace('/-+/', '-', $new_slug);
                $new_slug = trim($new_slug, '-');
                
                if ($old_slug === $new_slug) {
                    $skipped++;
                    continue;
                }
                
                // Проверяем уникальность
                $exists = Post::where('post_name', $new_slug)
                    ->where('ID', '!=', $post->ID)
                    ->where('post_type', 'post')
                    ->exists();
                
                if ($exists) {
                    $new_slug .= '-' . $post->ID;
                }
                
                $post->post_name = $new_slug;
                $post->save();
                
                $fixed++;
                $results[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'old' => $old_slug,
                    'new' => $new_slug,
                ];
            }
            
            echo '<div class="alert alert-success">';
            echo '<strong>✅ Массовое исправление завершено!</strong><br><br>';
            echo 'Исправлено: ' . $fixed . ' статей<br>';
            echo 'Пропущено: ' . $skipped . ' статей (slug уже корректный)';
            echo '</div>';
            
            if (!empty($results)) {
                echo '<h3>📋 Исправленные статьи:</h3>';
                foreach ($results as $r) {
                    echo '<div class="post-box">';
                    echo '<strong>ID ' . $r['id'] . ':</strong> ' . htmlspecialchars($r['title']) . '<br>';
                    echo '<strong>Старый:</strong> <code>' . htmlspecialchars($r['old']) . '</code><br>';
                    echo '<strong>Новый:</strong> <code>' . htmlspecialchars($r['new']) . '</code><br>';
                    echo '<a href="https://notame.ru/' . htmlspecialchars($r['new']) . '" target="_blank">Открыть статью</a>';
                    echo '</div>';
                }
            }
            
            echo '<br><a href="?key=' . SECURITY_KEY . '&action=preview" class="btn">◀️ Вернуться</a>';
        }
        
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>💡 Подсказка:</strong> После исправления slug удалите этот файл!
        </div>
    </div>
</body>
</html>
