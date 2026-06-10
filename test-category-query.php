<?php
/**
 * Тестовый скрипт для проверки SQL-запросов категорий
 * Этот файл можно удалить после проверки
 */

// Настройки БД
$dbConfig = [
    'host' => 'localhost',
    'database' => 'iq210692_notamerurework',
    'username' => 'iq210692_new2026-1',
    'password' => 'jZ7mG2qM6e',
    'port' => '3306',
];

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
    
    echo "<h1>Проверка SQL-запросов для категорий</h1>";
    echo "<hr>";
    
    // 1. Всего опубликованных статей
    $totalPosts = $pdo->query("
        SELECT COUNT(*) as count 
        FROM wp_posts 
        WHERE post_type = 'post' 
        AND post_status = 'publish'
    ")->fetch()->count;
    
    echo "<h2>1. Всего опубликованных статей: <strong>$totalPosts</strong></h2>";
    
    // 2. Статьи с категориями (OLD метод - с LEFT JOIN)
    $postsWithCategoriesOLD = $pdo->query("
        SELECT COUNT(DISTINCT p.ID) as count
        FROM wp_posts p
        LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
        LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'category'
        WHERE p.post_type = 'post' 
        AND p.post_status = 'publish'
        AND tt.term_taxonomy_id IS NOT NULL
    ")->fetch()->count;
    
    echo "<h2>2. Статьи с категориями (OLD метод): <strong>$postsWithCategoriesOLD</strong></h2>";
    
    // 3. Статьи с категориями (NEW метод - с INNER JOIN)
    $postsWithCategoriesNEW = $pdo->query("
        SELECT COUNT(DISTINCT p.ID) as count
        FROM wp_posts p
        INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
        INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE p.post_type = 'post' 
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'category'
    ")->fetch()->count;
    
    echo "<h2>3. Статьи с категориями (NEW метод): <strong>$postsWithCategoriesNEW</strong></h2>";
    
    // 4. Статьи БЕЗ категорий (OLD метод)
    $postsWithoutCategoriesOLD = $pdo->query("
        SELECT COUNT(DISTINCT p.ID) as count
        FROM wp_posts p
        LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
        LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'category'
        WHERE p.post_type = 'post' 
        AND p.post_status = 'publish'
        AND tt.term_taxonomy_id IS NULL
    ")->fetch()->count;
    
    echo "<h2>4. Статьи БЕЗ категорий (OLD метод): <strong style='color: red;'>$postsWithoutCategoriesOLD</strong></h2>";
    
    // 5. Статьи БЕЗ категорий (NEW метод - с NOT EXISTS)
    $postsWithoutCategoriesNEW = $pdo->query("
        SELECT COUNT(*) as count
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
    ")->fetch()->count;
    
    echo "<h2>5. Статьи БЕЗ категорий (NEW метод): <strong style='color: green;'>$postsWithoutCategoriesNEW</strong></h2>";
    
    echo "<hr>";
    echo "<h2>Проверка суммы:</h2>";
    echo "<p><strong>Статьи с категориями + Статьи без категорий = Всего статей?</strong></p>";
    echo "<p>OLD метод: $postsWithCategoriesOLD + $postsWithoutCategoriesOLD = " . ($postsWithCategoriesOLD + $postsWithoutCategoriesOLD) . 
         " (должно быть $totalPosts) " . 
         ($postsWithCategoriesOLD + $postsWithoutCategoriesOLD == $totalPosts ? '✅' : '❌') . "</p>";
    echo "<p>NEW метод: $postsWithCategoriesNEW + $postsWithoutCategoriesNEW = " . ($postsWithCategoriesNEW + $postsWithoutCategoriesNEW) . 
         " (должно быть $totalPosts) " . 
         ($postsWithCategoriesNEW + $postsWithoutCategoriesNEW == $totalPosts ? '✅' : '❌') . "</p>";
    
    echo "<hr>";
    echo "<h2>Примеры статей без категорий (NEW метод):</h2>";
    
    // Показываем несколько примеров
    $examples = $pdo->query("
        SELECT p.ID, p.post_title
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
        LIMIT 10
    ")->fetchAll();
    
    echo "<ul>";
    foreach ($examples as $post) {
        echo "<li><strong>ID {$post->ID}:</strong> {$post->post_title}</li>";
    }
    echo "</ul>";
    
    // Проверим конкретную статью "ИИ в музыке"
    echo "<hr>";
    echo "<h2>Проверка статьи 'ИИ в музыке: не угроза, а дверь в творчество':</h2>";
    
    $aiPost = $pdo->query("
        SELECT p.ID, p.post_title
        FROM wp_posts p
        WHERE p.post_title LIKE '%ИИ в музыке%'
        LIMIT 1
    ")->fetch();
    
    if ($aiPost) {
        echo "<p><strong>ID:</strong> {$aiPost->ID}</p>";
        echo "<p><strong>Название:</strong> {$aiPost->post_title}</p>";
        
        // Проверяем категории этой статьи
        $categories = $pdo->prepare("
            SELECT t.name, tt.taxonomy
            FROM wp_term_relationships tr
            INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN wp_terms t ON tt.term_id = t.term_id
            WHERE tr.object_id = ?
            AND tt.taxonomy = 'category'
        ");
        $categories->execute([$aiPost->ID]);
        $cats = $categories->fetchAll();
        
        echo "<p><strong>Категории:</strong></p>";
        if (count($cats) > 0) {
            echo "<ul>";
            foreach ($cats as $cat) {
                echo "<li>{$cat->name}</li>";
            }
            echo "</ul>";
            echo "<p style='color: green;'>✅ У статьи ЕСТЬ категории - не должна попадать в список 'без категорий'</p>";
        } else {
            echo "<p style='color: red;'>❌ У статьи НЕТ категорий</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h1 style='color: red;'>Ошибка: " . $e->getMessage() . "</h1>";
}
