<?php
/**
 * Анализ специальных символов и следов ИИ в статьях
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 АНАЛИЗ СПЕЦИАЛЬНЫХ СИМВОЛОВ В СТАТЬЯХ\n";
echo "=========================================\n\n";

$posts = \App\Models\WordPress\Post::where('post_type', 'post')
    ->where('post_status', 'publish')
    ->get();

$symbols = [
    // Тире и дефисы
    '—' => 'Длинное тире (em dash)',
    '–' => 'Среднее тире (en dash)',
    '&mdash;' => 'HTML длинное тире',
    '&ndash;' => 'HTML среднее тире',
    '−' => 'Минус',
    
    // Кавычки
    '«' => 'Кавычки ёлочки открывающие',
    '»' => 'Кавычки ёлочки закрывающие',
    '„' => 'Немецкие кавычки нижние',
    '"' => 'Английские кавычки открывающие',
    '"' => 'Английские кавычки закрывающие',
    '&laquo;' => 'HTML кавычки ёлочки открывающие',
    '&raquo;' => 'HTML кавычки ёлочки закрывающие',
    '&ldquo;' => 'HTML английские открывающие',
    '&rdquo;' => 'HTML английские закрывающие',
    '"' => 'Прямые кавычки (обычные)',
    
    // Многоточия
    '…' => 'Многоточие (символ)',
    '&hellip;' => 'HTML многоточие',
    '...' => 'Три точки подряд',
    
    // Пробелы
    '&nbsp;' => 'Неразрывный пробел',
    '  ' => 'Двойной пробел',
];

$stats = [];

foreach ($symbols as $symbol => $name) {
    $count = 0;
    $postsCount = 0;
    
    foreach ($posts as $post) {
        $inTitle = substr_count($post->post_title, $symbol);
        $inContent = substr_count($post->post_content, $symbol);
        $inExcerpt = substr_count($post->post_excerpt ?? '', $symbol);
        
        $total = $inTitle + $inContent + $inExcerpt;
        
        if ($total > 0) {
            $postsCount++;
            $count += $total;
        }
    }
    
    if ($count > 0) {
        $stats[$name] = ['count' => $count, 'posts' => $postsCount, 'symbol' => $symbol];
    }
}

// Сортируем по количеству
uasort($stats, function($a, $b) {
    return $b['count'] - $a['count'];
});

echo "📊 НАЙДЕННЫЕ СПЕЦИАЛЬНЫЕ СИМВОЛЫ:\n";
echo str_repeat('-', 80) . "\n";
printf("%-50s | %10s | %10s\n", 'Символ', 'Всего', 'Статей');
echo str_repeat('-', 80) . "\n";

foreach ($stats as $name => $data) {
    printf("%-50s | %10d | %10d\n", $name, $data['count'], $data['posts']);
}

echo str_repeat('-', 80) . "\n\n";

// Анализ ИИ-паттернов
echo "🤖 АНАЛИЗ СЛЕДОВ ИИ:\n";
echo str_repeat('-', 80) . "\n";

$aiPatterns = [
    'В заключение' => 0,
    'в заключение' => 0,
    'Таким образом' => 0,
    'таким образом' => 0,
    'Подводя итог' => 0,
    'подводя итог' => 0,
    'Важно отметить' => 0,
    'важно отметить' => 0,
    'Стоит отметить' => 0,
    'стоит отметить' => 0,
    'Следует отметить' => 0,
    'следует отметить' => 0,
    'В целом' => 0,
    'в целом' => 0,
    'В итоге' => 0,
    'в итоге' => 0,
    'Резюмируя' => 0,
    'резюмируя' => 0,
];

$aiStats = [];

foreach ($aiPatterns as $pattern => $count) {
    $postsCount = 0;
    $totalCount = 0;
    
    foreach ($posts as $post) {
        $inContent = substr_count($post->post_content, $pattern);
        
        if ($inContent > 0) {
            $postsCount++;
            $totalCount += $inContent;
        }
    }
    
    if ($totalCount > 0) {
        $aiStats[$pattern] = ['count' => $totalCount, 'posts' => $postsCount];
    }
}

// Сортируем
uasort($aiStats, function($a, $b) {
    return $b['posts'] - $a['posts'];
});

printf("%-40s | %10s | %10s\n", 'Фраза', 'Всего', 'Статей');
echo str_repeat('-', 80) . "\n";

foreach ($aiStats as $pattern => $data) {
    printf("%-40s | %10d | %10d\n", $pattern, $data['count'], $data['posts']);
}

echo str_repeat('-', 80) . "\n";
echo "\n✅ Анализ завершен!\n";
