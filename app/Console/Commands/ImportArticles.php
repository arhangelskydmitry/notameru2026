<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportArticles extends Command
{
    protected $signature = 'articles:import 
                            {archive : Путь к архиву экспорта}
                            {--dry-run : Тестовый запуск без изменений}
                            {--skip-images : Не импортировать изображения}
                            {--force : Перезаписывать существующие данные}';

    protected $description = 'Импорт статей из архива экспорта';

    protected $importDir;
    protected $dryRun = false;
    protected $stats = [
        'posts_imported' => 0,
        'posts_skipped' => 0,
        'posts_updated' => 0,
        'meta_imported' => 0,
        'seo_imported' => 0,
        'images_imported' => 0,
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        
        $this->info('📥 ИМПОРТ АРХИВА СТАТЕЙ');
        if ($this->dryRun) {
            $this->warn('⚠️  ТЕСТОВЫЙ РЕЖИМ - изменения не будут сохранены');
        }
        $this->newLine();
        
        $archivePath = $this->argument('archive');
        
        // Проверяем существование архива
        if (!file_exists($archivePath)) {
            $this->error("❌ Архив не найден: {$archivePath}");
            return 1;
        }
        
        // Распаковываем архив
        $this->importDir = $this->extractArchive($archivePath);
        if (!$this->importDir) {
            return 1;
        }
        
        // Проверяем манифест
        if (!$this->validateManifest()) {
            $this->cleanupImportDir();
            return 1;
        }
        
        // Импортируем данные
        DB::beginTransaction();
        
        try {
            // 1. Импорт терминов (категории, теги)
            $this->importTerms();
            
            // 2. Импорт статей
            $this->importPosts();
            
            // 3. Импорт мета-данных
            $this->importMeta();
            
            // 4. Импорт SEO
            $this->importSeo();
            
            // 5. Импорт связей (term_relationships)
            $this->importRelationships();
            
            // 6. Импорт изображений
            if (!$this->option('skip-images')) {
                $this->importImages();
            }
            
            if ($this->dryRun) {
                DB::rollBack();
                $this->warn('⚠️  Тестовый режим - транзакция отменена');
            } else {
                DB::commit();
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Ошибка импорта: " . $e->getMessage());
            $this->cleanupImportDir();
            return 1;
        }
        
        // Очищаем временные файлы
        $this->cleanupImportDir();
        
        // Итоги
        $this->newLine();
        $this->info('✅ ИМПОРТ ЗАВЕРШЁН!');
        $this->newLine();
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Статей импортировано', $this->stats['posts_imported']],
                ['Статей обновлено', $this->stats['posts_updated']],
                ['Статей пропущено', $this->stats['posts_skipped']],
                ['Мета-записей', $this->stats['meta_imported']],
                ['SEO записей', $this->stats['seo_imported']],
                ['Изображений', $this->stats['images_imported']],
            ]
        );
        
        return 0;
    }

    protected function extractArchive($archivePath)
    {
        $this->info('📦 Распаковка архива...');
        
        $importDir = storage_path('imports/import_' . time());
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }
        
        // Распаковываем tar.gz
        $command = sprintf(
            'tar -xzf %s -C %s',
            escapeshellarg($archivePath),
            escapeshellarg($importDir)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->error('❌ Ошибка распаковки архива');
            return null;
        }
        
        $this->info("   ✅ Распаковано в: {$importDir}");
        return $importDir;
    }

    protected function validateManifest()
    {
        $manifestPath = $this->importDir . '/manifest.json';
        
        if (!file_exists($manifestPath)) {
            $this->error('❌ Манифест не найден. Это не валидный архив экспорта.');
            return false;
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        $this->line('📋 Информация об архиве:');
        $this->line("   Версия: {$manifest['version']}");
        $this->line("   Создан: {$manifest['created_at']}");
        $this->line("   Источник: {$manifest['source_url']}");
        $this->line("   Статей: " . number_format($manifest['stats']['posts']));
        $this->line("   Изображений: " . number_format($manifest['stats']['images']));
        $this->newLine();
        
        // Проверяем наличие необходимых файлов
        $requiredFiles = ['posts.json', 'postmeta.json'];
        foreach ($requiredFiles as $file) {
            if (!file_exists($this->importDir . '/' . $file)) {
                $this->error("❌ Отсутствует необходимый файл: {$file}");
                return false;
            }
        }
        
        return true;
    }

    protected function importTerms()
    {
        $this->info('📂 Импорт категорий и тегов...');
        
        // Импорт terms
        $termsFile = $this->importDir . '/terms.json';
        if (file_exists($termsFile)) {
            $terms = json_decode(file_get_contents($termsFile), true);
            
            foreach ($terms as $term) {
                $existing = DB::table('wp_terms')->where('slug', $term['slug'])->first();
                
                if (!$existing && !$this->dryRun) {
                    DB::table('wp_terms')->insert([
                        'term_id' => $term['term_id'],
                        'name' => $term['name'],
                        'slug' => $term['slug'],
                        'term_group' => $term['term_group'] ?? 0,
                    ]);
                }
            }
            $this->line("   Терминов обработано: " . count($terms));
        }
        
        // Импорт term_taxonomy
        $taxonomyFile = $this->importDir . '/term_taxonomy.json';
        if (file_exists($taxonomyFile)) {
            $taxonomies = json_decode(file_get_contents($taxonomyFile), true);
            
            foreach ($taxonomies as $tax) {
                $existing = DB::table('wp_term_taxonomy')
                    ->where('term_id', $tax['term_id'])
                    ->where('taxonomy', $tax['taxonomy'])
                    ->first();
                
                if (!$existing && !$this->dryRun) {
                    DB::table('wp_term_taxonomy')->insert([
                        'term_taxonomy_id' => $tax['term_taxonomy_id'],
                        'term_id' => $tax['term_id'],
                        'taxonomy' => $tax['taxonomy'],
                        'description' => $tax['description'] ?? '',
                        'parent' => $tax['parent'] ?? 0,
                        'count' => $tax['count'] ?? 0,
                    ]);
                }
            }
            $this->line("   Таксономий обработано: " . count($taxonomies));
        }
        
        $this->info('   ✅ Категории и теги импортированы');
    }

    protected function importPosts()
    {
        $this->info('📝 Импорт статей...');
        
        $posts = json_decode(file_get_contents($this->importDir . '/posts.json'), true);
        $force = $this->option('force');
        
        $bar = $this->output->createProgressBar(count($posts));
        $bar->start();
        
        foreach ($posts as $post) {
            $existing = DB::table('wp_posts')->where('ID', $post['ID'])->first();
            
            if ($existing) {
                if ($force && !$this->dryRun) {
                    // Обновляем существующую статью
                    DB::table('wp_posts')->where('ID', $post['ID'])->update($post);
                    $this->stats['posts_updated']++;
                } else {
                    $this->stats['posts_skipped']++;
                }
            } else {
                if (!$this->dryRun) {
                    DB::table('wp_posts')->insert($post);
                }
                $this->stats['posts_imported']++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('   ✅ Статьи импортированы');
    }

    protected function importMeta()
    {
        $this->info('🔧 Импорт метаданных...');
        
        $meta = json_decode(file_get_contents($this->importDir . '/postmeta.json'), true);
        $force = $this->option('force');
        
        foreach ($meta as $m) {
            $existing = DB::table('wp_postmeta')
                ->where('post_id', $m['post_id'])
                ->where('meta_key', $m['meta_key'])
                ->first();
            
            if (!$existing && !$this->dryRun) {
                DB::table('wp_postmeta')->insert([
                    'meta_id' => $m['meta_id'] ?? null,
                    'post_id' => $m['post_id'],
                    'meta_key' => $m['meta_key'],
                    'meta_value' => $m['meta_value'],
                ]);
                $this->stats['meta_imported']++;
            } elseif ($existing && $force && !$this->dryRun) {
                DB::table('wp_postmeta')
                    ->where('meta_id', $existing->meta_id)
                    ->update(['meta_value' => $m['meta_value']]);
                $this->stats['meta_imported']++;
            }
        }
        
        $this->line("   Мета-записей: {$this->stats['meta_imported']}");
        $this->info('   ✅ Метаданные импортированы');
    }

    protected function importSeo()
    {
        $seoFile = $this->importDir . '/post_seo.json';
        if (!file_exists($seoFile)) {
            $this->warn('   ⚠️  SEO данные не найдены');
            return;
        }
        
        $this->info('🔍 Импорт SEO данных...');
        
        $seoData = json_decode(file_get_contents($seoFile), true);
        $force = $this->option('force');
        
        foreach ($seoData as $seo) {
            $existing = DB::table('post_seo')->where('post_id', $seo['post_id'])->first();
            
            if (!$existing && !$this->dryRun) {
                DB::table('post_seo')->insert($seo);
                $this->stats['seo_imported']++;
            } elseif ($existing && $force && !$this->dryRun) {
                DB::table('post_seo')->where('post_id', $seo['post_id'])->update($seo);
                $this->stats['seo_imported']++;
            }
        }
        
        $this->line("   SEO записей: {$this->stats['seo_imported']}");
        $this->info('   ✅ SEO данные импортированы');
    }

    protected function importRelationships()
    {
        $relFile = $this->importDir . '/term_relationships.json';
        if (!file_exists($relFile)) {
            return;
        }
        
        $this->info('🔗 Импорт связей статей с категориями...');
        
        $relationships = json_decode(file_get_contents($relFile), true);
        $imported = 0;
        
        foreach ($relationships as $rel) {
            $existing = DB::table('wp_term_relationships')
                ->where('object_id', $rel['object_id'])
                ->where('term_taxonomy_id', $rel['term_taxonomy_id'])
                ->first();
            
            if (!$existing && !$this->dryRun) {
                DB::table('wp_term_relationships')->insert([
                    'object_id' => $rel['object_id'],
                    'term_taxonomy_id' => $rel['term_taxonomy_id'],
                    'term_order' => $rel['term_order'] ?? 0,
                ]);
                $imported++;
            }
        }
        
        $this->line("   Связей импортировано: {$imported}");
        $this->info('   ✅ Связи импортированы');
    }

    protected function importImages()
    {
        $imagesDir = $this->importDir . '/images';
        if (!is_dir($imagesDir)) {
            $this->warn('   ⚠️  Изображения не найдены в архиве');
            return;
        }
        
        $this->info('🖼️  Импорт изображений...');
        
        // Получаем список изображений
        $imagesListFile = $this->importDir . '/images_list.json';
        if (!file_exists($imagesListFile)) {
            $this->warn('   ⚠️  Список изображений не найден');
            return;
        }
        
        $imagesList = json_decode(file_get_contents($imagesListFile), true);
        
        $bar = $this->output->createProgressBar(count($imagesList));
        $bar->start();
        
        foreach ($imagesList as $imagePath) {
            $sourcePath = $imagesDir . '/' . $imagePath;
            $targetPath = public_path($imagePath);
            
            if (file_exists($sourcePath)) {
                // Создаём директорию если нужно
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir) && !$this->dryRun) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Копируем файл
                if (!file_exists($targetPath) && !$this->dryRun) {
                    copy($sourcePath, $targetPath);
                    $this->stats['images_imported']++;
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->line("   Изображений импортировано: {$this->stats['images_imported']}");
        $this->info('   ✅ Изображения импортированы');
    }

    protected function cleanupImportDir()
    {
        if ($this->importDir && is_dir($this->importDir)) {
            $this->deleteDirectory($this->importDir);
        }
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
}
