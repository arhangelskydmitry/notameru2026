<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WordPress\Post;

class ExportArticles extends Command
{
    protected $signature = 'articles:export 
                            {--output= : Директория для сохранения архива}
                            {--with-images : Включить изображения в архив}
                            {--since= : Экспортировать только статьи с указанной даты (YYYY-MM-DD)}
                            {--limit= : Ограничить количество статей}
                            {--tables-only : Только БД без изображений}';

    protected $description = 'Экспорт статей, метаданных и изображений в архив';

    protected $exportDir;
    protected $stats = [
        'posts' => 0,
        'meta' => 0,
        'seo' => 0,
        'categories' => 0,
        'tags' => 0,
        'images' => 0,
        'images_size' => 0,
    ];

    public function handle()
    {
        $this->info('📦 ЭКСПОРТ АРХИВА СТАТЕЙ');
        $this->newLine();
        
        // Создаём временную директорию
        $timestamp = date('Y-m-d_His');
        $outputDir = $this->option('output') ?: storage_path('exports');
        $this->exportDir = $outputDir . '/export_' . $timestamp;
        
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
        
        $this->line("📁 Директория экспорта: {$this->exportDir}");
        $this->newLine();
        
        // 1. Экспорт БД
        $this->exportDatabase();
        
        // 2. Экспорт изображений (если не --tables-only)
        if (!$this->option('tables-only')) {
            if ($this->option('with-images')) {
                $this->exportImages();
            } else {
                $this->warn('⚠️  Изображения не включены. Добавьте --with-images для полного экспорта');
            }
        }
        
        // 3. Создаём манифест
        $this->createManifest();
        
        // 4. Архивируем
        $archivePath = $this->createArchive($outputDir, $timestamp);
        
        // 5. Очищаем временные файлы
        $this->cleanupExportDir();
        
        // Итоги
        $this->newLine();
        $this->info('✅ ЭКСПОРТ ЗАВЕРШЁН!');
        $this->newLine();
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Статей', number_format($this->stats['posts'])],
                ['Мета-записей', number_format($this->stats['meta'])],
                ['SEO данных', number_format($this->stats['seo'])],
                ['Категорий', number_format($this->stats['categories'])],
                ['Тегов', number_format($this->stats['tags'])],
                ['Изображений', number_format($this->stats['images'])],
                ['Размер изображений', $this->formatBytes($this->stats['images_size'])],
                ['Архив', basename($archivePath)],
                ['Размер архива', $this->formatBytes(filesize($archivePath))],
            ]
        );
        
        $this->newLine();
        $this->line("📦 Архив: {$archivePath}");
        
        return 0;
    }

    protected function exportDatabase()
    {
        $this->info('🗄️  Экспорт базы данных...');
        
        // Получаем статьи
        $query = Post::where('post_type', 'post')
                    ->where('post_status', 'publish');
        
        if ($since = $this->option('since')) {
            $query->where('post_date', '>=', $since);
            $this->line("   Фильтр: статьи с {$since}");
        }
        
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
            $this->line("   Лимит: {$limit} статей");
        }
        
        $posts = $query->get();
        $this->stats['posts'] = $posts->count();
        $this->line("   Найдено статей: {$this->stats['posts']}");
        
        // Получаем ID постов для связанных данных
        $postIds = $posts->pluck('ID')->toArray();
        
        // Экспортируем посты
        $postsData = $posts->toArray();
        file_put_contents(
            $this->exportDir . '/posts.json',
            json_encode($postsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        // Экспортируем мета-данные
        $meta = DB::table('wp_postmeta')
            ->whereIn('post_id', $postIds)
            ->get();
        $this->stats['meta'] = $meta->count();
        file_put_contents(
            $this->exportDir . '/postmeta.json',
            json_encode($meta->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $this->line("   Мета-записей: {$this->stats['meta']}");
        
        // Экспортируем SEO данные
        $seo = DB::table('post_seo')
            ->whereIn('post_id', $postIds)
            ->get();
        $this->stats['seo'] = $seo->count();
        file_put_contents(
            $this->exportDir . '/post_seo.json',
            json_encode($seo->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $this->line("   SEO записей: {$this->stats['seo']}");
        
        // Экспортируем категории и связи
        $termRelationships = DB::table('wp_term_relationships')
            ->whereIn('object_id', $postIds)
            ->get();
        file_put_contents(
            $this->exportDir . '/term_relationships.json',
            json_encode($termRelationships->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        // Получаем уникальные term_taxonomy_id
        $taxonomyIds = $termRelationships->pluck('term_taxonomy_id')->unique()->toArray();
        
        // Экспортируем term_taxonomy
        $termTaxonomy = DB::table('wp_term_taxonomy')
            ->whereIn('term_taxonomy_id', $taxonomyIds)
            ->get();
        file_put_contents(
            $this->exportDir . '/term_taxonomy.json',
            json_encode($termTaxonomy->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        // Подсчитываем категории и теги
        $this->stats['categories'] = $termTaxonomy->where('taxonomy', 'category')->count();
        $this->stats['tags'] = $termTaxonomy->where('taxonomy', 'post_tag')->count();
        
        // Получаем уникальные term_id
        $termIds = $termTaxonomy->pluck('term_id')->unique()->toArray();
        
        // Экспортируем terms
        $terms = DB::table('wp_terms')
            ->whereIn('term_id', $termIds)
            ->get();
        file_put_contents(
            $this->exportDir . '/terms.json',
            json_encode($terms->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        $this->line("   Категорий: {$this->stats['categories']}");
        $this->line("   Тегов: {$this->stats['tags']}");
        
        // Экспортируем авторов (анонимизированно)
        $authorIds = $posts->pluck('post_author')->unique()->toArray();
        $authors = DB::table('wp_users')
            ->whereIn('ID', $authorIds)
            ->select('ID', 'display_name', 'user_login')
            ->get();
        file_put_contents(
            $this->exportDir . '/authors.json',
            json_encode($authors->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $this->line("   Авторов: " . count($authorIds));
        
        $this->info('   ✅ База данных экспортирована');
    }

    protected function exportImages()
    {
        $this->newLine();
        $this->info('🖼️  Экспорт изображений...');
        
        // Создаём директорию для изображений
        $imagesDir = $this->exportDir . '/images';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }
        
        // Читаем посты и мета-данные
        $posts = json_decode(file_get_contents($this->exportDir . '/posts.json'), true);
        $meta = json_decode(file_get_contents($this->exportDir . '/postmeta.json'), true);
        
        // Собираем все пути к изображениям
        $imagePaths = [];
        
        // Из контента статей
        foreach ($posts as $post) {
            preg_match_all('/src=["\']([^"\']*\.(jpg|jpeg|png|webp|gif))["\']/', $post['post_content'], $matches);
            foreach ($matches[1] as $src) {
                $imagePaths[] = $this->normalizeImagePath($src);
            }
        }
        
        // Из мета-данных (_thumbnail_url)
        foreach ($meta as $m) {
            if (in_array($m['meta_key'], ['_thumbnail_url', '_wp_attached_file'])) {
                $imagePaths[] = $this->normalizeImagePath($m['meta_value']);
            }
        }
        
        // Уникальные пути
        $imagePaths = array_unique(array_filter($imagePaths));
        $this->line("   Найдено уникальных изображений: " . count($imagePaths));
        
        // Копируем изображения
        $bar = $this->output->createProgressBar(count($imagePaths));
        $bar->start();
        
        $copiedImages = [];
        foreach ($imagePaths as $imagePath) {
            $sourcePath = public_path($imagePath);
            
            if (file_exists($sourcePath)) {
                // Сохраняем структуру папок
                $relativePath = ltrim($imagePath, '/');
                $targetPath = $imagesDir . '/' . $relativePath;
                $targetDir = dirname($targetPath);
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                if (copy($sourcePath, $targetPath)) {
                    $this->stats['images']++;
                    $this->stats['images_size'] += filesize($sourcePath);
                    $copiedImages[] = $relativePath;
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        // Сохраняем список изображений
        file_put_contents(
            $this->exportDir . '/images_list.json',
            json_encode($copiedImages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        $this->info("   ✅ Скопировано изображений: {$this->stats['images']}");
        $this->line("   📊 Общий размер: " . $this->formatBytes($this->stats['images_size']));
    }

    protected function normalizeImagePath($url)
    {
        // Убираем домен
        $url = preg_replace('/^https?:\/\/[^\/]+/', '', $url);
        // Убираем wp-content/uploads если есть
        $url = preg_replace('/^\/wp-content\/uploads/', '/uploads', $url);
        return $url;
    }

    protected function createManifest()
    {
        $manifest = [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'source_url' => config('app.url'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'stats' => $this->stats,
            'options' => [
                'since' => $this->option('since'),
                'limit' => $this->option('limit'),
                'with_images' => $this->option('with-images'),
            ],
            'files' => [
                'posts.json' => 'Статьи (wp_posts)',
                'postmeta.json' => 'Метаданные статей',
                'post_seo.json' => 'SEO данные',
                'terms.json' => 'Термины (категории, теги)',
                'term_taxonomy.json' => 'Таксономия терминов',
                'term_relationships.json' => 'Связи статей с терминами',
                'authors.json' => 'Авторы',
                'images_list.json' => 'Список изображений',
            ],
        ];
        
        file_put_contents(
            $this->exportDir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function createArchive($outputDir, $timestamp)
    {
        $this->newLine();
        $this->info('📦 Создание архива...');
        
        $archiveName = "articles_export_{$timestamp}.tar.gz";
        $archivePath = $outputDir . '/' . $archiveName;
        
        // Используем tar для создания архива
        $command = sprintf(
            'cd %s && tar -czf %s -C %s .',
            escapeshellarg($outputDir),
            escapeshellarg($archiveName),
            escapeshellarg($this->exportDir)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->error('❌ Ошибка создания архива');
            return $this->exportDir;
        }
        
        $this->info("   ✅ Архив создан: {$archiveName}");
        return $archivePath;
    }

    protected function cleanupExportDir()
    {
        // Удаляем временную директорию
        $this->deleteDirectory($this->exportDir);
    }

    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
