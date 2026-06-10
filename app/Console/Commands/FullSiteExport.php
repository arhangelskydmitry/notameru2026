<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FullSiteExport extends Command
{
    protected $signature = 'site:export 
                            {--output= : Директория для сохранения архива}
                            {--skip-images : Пропустить изображения (только БД)}
                            {--skip-env : Не включать .env файл}';

    protected $description = 'Полный экспорт сайта: БД, изображения, настройки';

    protected $exportDir;
    protected $stats = [
        'tables' => 0,
        'rows' => 0,
        'images' => 0,
        'images_size' => 0,
        'configs' => 0,
    ];

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         📦 ПОЛНЫЙ ЭКСПОРТ САЙТА NOTAME.RU                    ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        // Создаём временную директорию
        $timestamp = date('Y-m-d_His');
        $outputDir = $this->option('output') ?: storage_path('exports');
        $this->exportDir = $outputDir . '/full_export_' . $timestamp;
        
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
        
        $this->line("📁 Директория: {$this->exportDir}");
        $this->newLine();
        
        // 1. Полный дамп базы данных
        $this->exportFullDatabase();
        
        // 2. Экспорт конфигураций
        if (!$this->option('skip-env')) {
            $this->exportConfigurations();
        }
        
        // 3. Экспорт изображений
        if (!$this->option('skip-images')) {
            $this->exportAllImages();
        } else {
            $this->warn('⚠️  Изображения пропущены (--skip-images)');
        }
        
        // 4. Создаём манифест
        $this->createManifest();
        
        // 5. Архивируем
        $archivePath = $this->createArchive($outputDir, $timestamp);
        
        // 6. Очищаем временные файлы
        $this->cleanupExportDir();
        
        // Итоги
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    ✅ ЭКСПОРТ ЗАВЕРШЁН!                       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Таблиц', number_format($this->stats['tables'])],
                ['Записей в БД', number_format($this->stats['rows'])],
                ['Изображений', number_format($this->stats['images'])],
                ['Размер изображений', $this->formatBytes($this->stats['images_size'])],
                ['Конфигураций', $this->stats['configs']],
                ['Архив', basename($archivePath)],
                ['Размер архива', $this->formatBytes(filesize($archivePath))],
            ]
        );
        
        $this->newLine();
        $this->line("📦 Путь к архиву:");
        $this->info("   {$archivePath}");
        $this->newLine();
        $this->line("💡 Для импорта на локальном сервере:");
        $this->info("   php artisan site:import {$archivePath}");
        
        return 0;
    }

    protected function exportFullDatabase()
    {
        $this->info('🗄️  Экспорт полной базы данных...');
        
        $dbDir = $this->exportDir . '/database';
        mkdir($dbDir, 0755, true);
        
        // Сначала пробуем mysqldump (оптимальный вариант)
        if ($this->createSqlDump($dbDir)) {
            $this->info("   ✅ SQL дамп создан успешно");
            
            // Подсчитываем статистику
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database');
            $tableKey = "Tables_in_{$dbName}";
            $this->stats['tables'] = count($tables);
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                try {
                    $count = DB::table($tableName)->count();
                    $this->stats['rows'] += $count;
                } catch (\Exception $e) {}
            }
            
            $this->info("   ✅ БД экспортирована ({$this->stats['rows']} записей)");
            return;
        }
        
        // Fallback: JSON экспорт по частям
        $this->warn("   ⚠️  mysqldump недоступен, используем JSON экспорт...");
        
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$dbName}";
        
        $this->stats['tables'] = count($tables);
        $this->line("   Таблиц найдено: {$this->stats['tables']}");
        
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();
        
        // Экспортируем каждую таблицу в отдельный файл
        $tablesDir = $dbDir . '/tables';
        mkdir($tablesDir, 0755, true);
        
        $tablesList = [];
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Получаем структуру таблицы
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $structure = $createTable[0]->{'Create Table'} ?? '';
            
            // Получаем данные порциями (чанками) для экономии памяти
            $tableData = [
                'structure' => $structure,
                'data' => [],
                'count' => 0,
            ];
            
            // Читаем по 1000 записей
            DB::table($tableName)->orderBy(DB::raw('1'))->chunk(1000, function ($rows) use (&$tableData) {
                foreach ($rows as $row) {
                    $tableData['data'][] = (array)$row;
                    $tableData['count']++;
                }
            });
            
            $this->stats['rows'] += $tableData['count'];
            
            // Сохраняем таблицу в отдельный файл
            file_put_contents(
                $tablesDir . '/' . $tableName . '.json',
                json_encode($tableData, JSON_UNESCAPED_UNICODE)
            );
            
            $tablesList[$tableName] = $tableData['count'];
            
            // Освобождаем память
            unset($tableData);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        // Сохраняем индекс таблиц
        file_put_contents(
            $dbDir . '/tables_index.json',
            json_encode($tablesList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        $this->info("   ✅ БД экспортирована ({$this->stats['rows']} записей)");
    }

    protected function createSqlDump($dbDir): bool
    {
        $config = config('database.connections.mysql');
        $mysqldumpPath = $this->findMysqldump();
        
        if (!$mysqldumpPath) {
            return false;
        }
        
        $dumpFile = $dbDir . '/database.sql';
        
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            $mysqldumpPath,
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($dumpFile)
        );
        
        exec($command, $output, $returnVar);
        
        return $returnVar === 0 && file_exists($dumpFile) && filesize($dumpFile) > 100;
    }

    protected function findMysqldump(): ?string
    {
        $paths = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/MAMP/Library/bin/mysqldump',
        ];
        
        foreach ($paths as $path) {
            exec("which {$path} 2>/dev/null", $output, $returnVar);
            if ($returnVar === 0 && !empty($output)) {
                return trim($output[0]);
            }
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    protected function exportConfigurations()
    {
        $this->newLine();
        $this->info('⚙️  Экспорт конфигураций...');
        
        $configDir = $this->exportDir . '/config';
        mkdir($configDir, 0755, true);
        
        // Список файлов для экспорта
        $configFiles = [
            '.env' => base_path('.env'),
            'config/app.php' => config_path('app.php'),
            'config/database.php' => config_path('database.php'),
            'config/filesystems.php' => config_path('filesystems.php'),
            'config/services.php' => config_path('services.php'),
            'config/logging.php' => config_path('logging.php'),
            'config/cache.php' => config_path('cache.php'),
            'config/session.php' => config_path('session.php'),
        ];
        
        foreach ($configFiles as $name => $path) {
            if (file_exists($path) && is_readable($path)) {
                try {
                    $targetDir = $configDir . '/' . dirname($name);
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    if (@copy($path, $configDir . '/' . $name)) {
                        $this->stats['configs']++;
                    }
                } catch (\Exception $e) {
                    $this->warn("   ⚠️  Не удалось скопировать: {$name}");
                }
            }
        }
        
        // Экспорт SEO настроек
        $seoSettingsFile = base_path('seo-settings.json');
        if (file_exists($seoSettingsFile)) {
            copy($seoSettingsFile, $configDir . '/seo-settings.json');
            $this->stats['configs']++;
        }
        
        $this->info("   ✅ Конфигураций экспортировано: {$this->stats['configs']}");
    }

    protected function exportAllImages()
    {
        $this->newLine();
        $this->info('🖼️  Экспорт всех изображений...');
        
        $imagesDir = $this->exportDir . '/images';
        mkdir($imagesDir, 0755, true);
        
        // Директории с изображениями
        $sourceDirs = [
            'uploads' => public_path('uploads'),
            'wp-content/uploads' => public_path('wp-content/uploads'),
            'images' => public_path('images'),
        ];
        
        foreach ($sourceDirs as $name => $sourceDir) {
            if (!is_dir($sourceDir)) {
                continue;
            }
            
            $this->line("   Сканирование: {$name}");
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            $files = [];
            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|webp|gif|svg|ico)$/i', $file->getFilename())) {
                    $files[] = $file;
                }
            }
            
            if (empty($files)) {
                continue;
            }
            
            $bar = $this->output->createProgressBar(count($files));
            $bar->start();
            
            foreach ($files as $file) {
                $relativePath = str_replace($sourceDir, '', $file->getPathname());
                $targetPath = $imagesDir . '/' . $name . $relativePath;
                $targetDir = dirname($targetPath);
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                if (copy($file->getPathname(), $targetPath)) {
                    $this->stats['images']++;
                    $this->stats['images_size'] += $file->getSize();
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
        }
        
        $this->info("   ✅ Изображений: {$this->stats['images']} (" . $this->formatBytes($this->stats['images_size']) . ")");
    }

    protected function createManifest()
    {
        $manifest = [
            'version' => '2.0',
            'type' => 'full_site_backup',
            'created_at' => date('Y-m-d H:i:s'),
            'source_url' => config('app.url'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'stats' => $this->stats,
            'structure' => [
                'database/' => 'Полный дамп базы данных',
                'database/full_database.json' => 'JSON формат всех таблиц',
                'database/database.sql' => 'SQL дамп (если доступен)',
                'config/' => 'Конфигурации и .env',
                'images/' => 'Все изображения сайта',
            ],
            'import_command' => 'php artisan site:import <archive.tar.gz>',
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
        
        $archiveName = "notame_full_backup_{$timestamp}.tar.gz";
        $archivePath = $outputDir . '/' . $archiveName;
        
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
        
        $this->info("   ✅ Архив создан");
        return $archivePath;
    }

    protected function cleanupExportDir()
    {
        $this->deleteDirectory($this->exportDir);
    }

    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) return;
        
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
