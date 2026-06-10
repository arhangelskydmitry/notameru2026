<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест 404 страницы</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #c80000; }
        h2 { color: #333; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .post-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .post-title { font-weight: bold; color: #333; }
        .post-date { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Тест 404 страницы - Диагностика</h1>
        
        <?php
        // Подключаем Laravel
        require __DIR__.'/vendor/autoload.php';
        $app = require_once __DIR__.'/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        echo "<h2>1. Проверка подключения к БД</h2>";
        try {
            $connection = DB::connection()->getPdo();
            echo "<p class='success'>✅ Подключение к БД успешно</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Ошибка подключения: " . $e->getMessage() . "</p>";
            exit;
        }
        
        echo "<h2>2. Проверка модели Post</h2>";
        try {
            $totalPosts = \App\Models\WordPress\Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->count();
            echo "<p class='success'>✅ Всего опубликованных статей: <strong>$totalPosts</strong></p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Ошибка запроса: " . $e->getMessage() . "</p>";
            exit;
        }
        
        echo "<h2>3. Загрузка первых 12 постов</h2>";
        try {
            $posts = \App\Models\WordPress\Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->with(['author', 'categories.term'])
                ->orderBy('post_date', 'desc')
                ->limit(12)
                ->get();
            
            echo "<p class='success'>✅ Загружено постов: <strong>" . $posts->count() . "</strong></p>";
            
            if ($posts->count() > 0) {
                echo "<div class='info'>";
                echo "<h3>Примеры загруженных постов:</h3>";
                
                foreach ($posts->take(5) as $index => $post) {
                    $num = $index + 1;
                    echo "<div class='post-card'>";
                    echo "<div class='post-title'>{$num}. {$post->post_title}</div>";
                    echo "<div class='post-date'>ID: {$post->ID} | Дата: {$post->post_date->format('d.m.Y H:i')}</div>";
                    
                    // Категории
                    if ($post->categories && $post->categories->count() > 0) {
                        $cats = $post->categories->pluck('term.name')->join(', ');
                        echo "<div class='post-date'>Категории: {$cats}</div>";
                    }
                    
                    echo "</div>";
                }
                
                if ($posts->count() > 5) {
                    echo "<p><em>... и еще " . ($posts->count() - 5) . " постов</em></p>";
                }
                
                echo "</div>";
            } else {
                echo "<p class='error'>❌ Посты не найдены!</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Ошибка загрузки постов: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
        echo "<h2>4. Проверка view</h2>";
        $viewPath = resource_path('views/errors/404.blade.php');
        if (file_exists($viewPath)) {
            echo "<p class='success'>✅ Файл view существует: $viewPath</p>";
            echo "<p class='info'>Размер файла: " . number_format(filesize($viewPath)) . " байт</p>";
        } else {
            echo "<p class='error'>❌ Файл view НЕ найден: $viewPath</p>";
        }
        
        echo "<h2>5. Проверка partials/post-card.blade.php</h2>";
        $partialPath = resource_path('views/partials/post-card.blade.php');
        if (file_exists($partialPath)) {
            echo "<p class='success'>✅ Файл partial существует: $partialPath</p>";
        } else {
            echo "<p class='error'>❌ Файл partial НЕ найден: $partialPath</p>";
        }
        
        echo "<h2>6. Тест рендеринга post-card</h2>";
        if ($posts->count() > 0 && file_exists($partialPath)) {
            try {
                $firstPost = $posts->first();
                $rendered = view('partials.post-card', ['post' => $firstPost])->render();
                echo "<p class='success'>✅ Рендеринг успешен</p>";
                echo "<details><summary>Посмотреть HTML</summary><pre>" . htmlspecialchars($rendered) . "</pre></details>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Ошибка рендеринга: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h2>✅ Диагностика завершена</h2>";
        echo "<div class='info'>";
        echo "<strong>Рекомендации:</strong><br>";
        echo "1. Загрузите файл <code>resources/views/errors/404.blade.php</code> на production<br>";
        echo "2. Убедитесь, что файл <code>resources/views/partials/post-card.blade.php</code> существует<br>";
        echo "3. Выполните <code>php artisan view:clear</code> на production<br>";
        echo "4. Откройте любую несуществующую страницу: <code>https://notame.ru/test-404-page</code>";
        echo "</div>";
        ?>
    </div>
</body>
</html>
