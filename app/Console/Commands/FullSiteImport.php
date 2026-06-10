<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FullSiteImport extends Command
{
    protected $signature = 'site:import 
                            {archive : Путь к архиву бэкапа}
                            {--dry-run : Тестовый запуск без изменений}
                            {--skip-images : Пропустить импорт изображений}
                            {--skip-config : Не импортировать конфигурации}
                            {--skip-db : Пропустить импорт базы данных}
                            {--force : Перезаписать существующие данные}';

    protected $description = 'Полный импорт сайта из архива бэкапа';

    protected $importDir;
    protected $dryRun = false;
    protected $stats = [
        'tables_imported' => 0,
        'rows_imported' => 0,
        'images_imported' => 0,
        'configs_imported' => 0,
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         📥 ПОЛНЫЙ ИМПОРТ САЙТА NOTAME.RU                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        
        if ($this->dryRun) {
            $this->warn('⚠️  ТЕСТОВЫЙ РЕЖИМ - изменения НЕ будут сохранены');
        }
        $this->newLine();
        
        $archivePath = $this->argument('archive');
        
        // Проверяем архив
        if (!file_exists($archivePath)) {
            $this->error("❌ Архив не найден: {$archivePath}");
            return 1;
        }
        
        // Распаковываем
        $this->importDir = $this->extractArchive($archivePath);
        if (!$this->importDir) {
            return 1;
        }
        
        // Проверяем манифест
        if (!$this->validateManifest()) {
            $this->cleanupImportDir();
            return 1;
        }
        
        // Подтверждение
        if (!$this->dryRun && !$this->option('force')) {
            if (!$this->confirm('⚠️  Это перезапишет данные на локальном сервере. Продолжить?')) {
                $this->cleanupImportDir();
                return 0;
            }
        }
        
        try {
            // 1. Импорт базы данных
            if (!$this->option('skip-db')) {
                $this->importDatabase();
            }
            
            // 2. Импорт конфигураций
            if (!$this->option('skip-config')) {
                $this->importConfigurations();
            }
            
            // 3. Импорт изображений
            if (!$this->option('skip-images')) {
                $this->importImages();
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка импорта: " . $e->getMessage());
            $this->cleanupImportDir();
            return 1;
        }
        
        // Очистка
        $this->cleanupImportDir();
        
        // Итоги
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    ✅ ИМПОРТ ЗАВЕРШЁН!                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Таблиц импортировано', $this->stats['tables_imported']],
                ['Записей в БД', number_format($this->stats['rows_imported'])],
                ['Изображений', number_format($this->stats['images_imported'])],
                ['Конфигураций', $this->stats['configs_imported']],
            ]
        );
        
        if (!$this->dryRun) {
            $this->newLine();
            $this->info('💡 Рекомендуемые действия после импорта:');
            $this->line('   1. Проверьте .env файл и настройте подключение к БД');
            $this->line('   2. php artisan config:clear');
            $this->line('   3. php artisan cache:clear');
            $this->line('   4. php artisan route:clear');
            $this->line('   5. Проверьте права на папки storage/ и public/');
        }
        
        return 0;
    }

    protected function extractArchive($archivePath)
    {
        $this->info('📦 Распаковка архива...');
        
        $importDir = storage_path('imports/import_' . time());
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }
        
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
        
        $this->info("   ✅ Распаковано");
        return $importDir;
    }

    protected function validateManifest()
    {
        $manifestPath = $this->importDir . '/manifest.json';
        
        if (!file_exists($manifestPath)) {
            $this->error('❌ Манифест не найден. Это не валидный архив.');
            return false;
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        $this->newLine();
        $this->line('📋 Информация об архиве:');
        $this->line("   Версия: {$manifest['version']}");
        $this->line("   Тип: " . ($manifest['type'] ?? 'backup'));
        $this->line("   Создан: {$manifest['created_at']}");
        $this->line("   Источник: {$manifest['source_url']}");
        
        if (isset($manifest['stats'])) {
            $this->line("   Таблиц: " . number_format($manifest['stats']['tables'] ?? 0));
            $this->line("   Записей: " . number_format($manifest['stats']['rows'] ?? 0));
            $this->line("   Изображений: " . number_format($manifest['stats']['images'] ?? 0));
        }
        
        $this->newLine();
        
        return true;
    }

    protected function importDatabase()
    {
        $this->info('🗄️  Импорт базы данных...');
        
        $dbDir = $this->importDir . '/database';
        
        // Приоритет: SQL дамп, затем JSON (новый формат с таблицами), затем старый JSON
        $sqlFile = $dbDir . '/database.sql';
        $tablesDir = $dbDir . '/tables';
        $jsonFile = $dbDir . '/full_database.json';
        
        if (file_exists($sqlFile) && filesize($sqlFile) > 100) {
            $this->importFromSql($sqlFile);
        } elseif (is_dir($tablesDir)) {
            $this->importFromTablesDir($tablesDir);
        } elseif (file_exists($jsonFile)) {
            $this->importFromJson($jsonFile);
        } else {
            $this->warn('   ⚠️  Файлы базы данных не найдены');
            return;
        }
        
        $this->info("   ✅ БД импортирована ({$this->stats['rows_imported']} записей)");
    }

    protected function importFromSql($sqlFile)
    {
        $this->line('   Импорт из SQL дампа...');
        
        if ($this->dryRun) {
            $this->line('   [dry-run] SQL дамп будет импортирован');
            return;
        }
        
        $config = config('database.connections.mysql');
        
        // Используем mysql для импорта
        $mysqlPath = $this->findMysql();
        
        if ($mysqlPath) {
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
                $mysqlPath,
                escapeshellarg($config['host']),
                escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($sqlFile)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                // Подсчитываем записи
                $tables = DB::select('SHOW TABLES');
                $this->stats['tables_imported'] = count($tables);
                
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    $count = DB::table($tableName)->count();
                    $this->stats['rows_imported'] += $count;
                }
                
                return;
            }
        }
        
        // Если mysql не доступен, используем JSON
        $jsonFile = dirname($sqlFile) . '/full_database.json';
        if (file_exists($jsonFile)) {
            $this->importFromJson($jsonFile);
        }
    }

    protected function importFromJson($jsonFile)
    {
        $this->line('   Импорт из JSON...');
        
        $data = json_decode(file_get_contents($jsonFile), true);
        
        if (!$data) {
            $this->warn('   ⚠️  Ошибка чтения JSON');
            return;
        }
        
        $bar = $this->output->createProgressBar(count($data));
        $bar->start();
        
        foreach ($data as $tableName => $tableData) {
            if ($this->dryRun) {
                $this->stats['tables_imported']++;
                $this->stats['rows_imported'] += $tableData['count'] ?? 0;
                $bar->advance();
                continue;
            }
            
            // Пересоздаём таблицу
            if (!empty($tableData['structure'])) {
                try {
                    DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                    DB::statement($tableData['structure']);
                } catch (\Exception $e) {
                    // Таблица уже существует - очищаем
                    DB::table($tableName)->truncate();
                }
            }
            
            // Вставляем данные
            if (!empty($tableData['data'])) {
                // Вставляем порциями по 500 записей
                $chunks = array_chunk($tableData['data'], 500);
                foreach ($chunks as $chunk) {
                    try {
                        DB::table($tableName)->insert($chunk);
                    } catch (\Exception $e) {
                        // Пробуем по одной записи
                        foreach ($chunk as $row) {
                            try {
                                DB::table($tableName)->insert((array)$row);
                            } catch (\Exception $e) {
                                // Пропускаем проблемную запись
                            }
                        }
                    }
                }
                $this->stats['rows_imported'] += count($tableData['data']);
            }
            
            $this->stats['tables_imported']++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }

    protected function importFromTablesDir($tablesDir)
    {
        $this->line('   Импорт из JSON таблиц (новый формат)...');
        
        $files = glob($tablesDir . '/*.json');
        
        if (empty($files)) {
            $this->warn('   ⚠️  Файлы таблиц не найдены');
            return;
        }
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        foreach ($files as $file) {
            $tableName = basename($file, '.json');
            $tableData = json_decode(file_get_contents($file), true);
            
            if (!$tableData) {
                $bar->advance();
                continue;
            }
            
            if ($this->dryRun) {
                $this->stats['tables_imported']++;
                $this->stats['rows_imported'] += $tableData['count'] ?? 0;
                $bar->advance();
                continue;
            }
            
            // Пересоздаём таблицу
            if (!empty($tableData['structure'])) {
                try {
                    DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                    DB::statement($tableData['structure']);
                } catch (\Exception $e) {
                    // Таблица уже существует - очищаем
                    try {
                        DB::table($tableName)->truncate();
                    } catch (\Exception $e) {}
                }
            }
            
            // Вставляем данные порциями
            if (!empty($tableData['data'])) {
                $chunks = array_chunk($tableData['data'], 500);
                foreach ($chunks as $chunk) {
                    try {
                        DB::table($tableName)->insert($chunk);
                    } catch (\Exception $e) {
                        // Пробуем по одной записи
                        foreach ($chunk as $row) {
                            try {
                                DB::table($tableName)->insert($row);
                            } catch (\Exception $e) {}
                        }
                    }
                }
                $this->stats['rows_imported'] += count($tableData['data']);
            }
            
            $this->stats['tables_imported']++;
            
            // Освобождаем память
            unset($tableData);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }

    protected function findMysql(): ?string
    {
        $paths = [
            'mysql',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            '/opt/homebrew/bin/mysql',
            '/usr/local/mysql/bin/mysql',
            '/Applications/MAMP/Library/bin/mysql',
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

    protected function importConfigurations()
    {
        $this->newLine();
        $this->info('⚙️  Импорт конфигураций...');
        
        $configDir = $this->importDir . '/config';
        
        if (!is_dir($configDir)) {
            $this->warn('   ⚠️  Директория config не найдена');
            return;
        }
        
        // Импортируем .env (создаём .env.imported для безопасности)
        $envSource = $configDir . '/.env';
        if (file_exists($envSource)) {
            if ($this->dryRun) {
                $this->line('   [dry-run] .env будет скопирован как .env.imported');
            } else {
                copy($envSource, base_path('.env.imported'));
                $this->stats['configs_imported']++;
                $this->line('   ✅ .env сохранён как .env.imported');
                $this->warn('      Проверьте и переименуйте в .env при необходимости');
            }
        }
        
        // Импортируем SEO настройки
        $seoSource = $configDir . '/seo-settings.json';
        if (file_exists($seoSource)) {
            if (!$this->dryRun) {
                copy($seoSource, base_path('seo-settings.json'));
            }
            $this->stats['configs_imported']++;
            $this->line('   ✅ seo-settings.json');
        }
        
        // Остальные конфиги копируем в config/
        $configFiles = glob($configDir . '/config/*.php');
        foreach ($configFiles as $file) {
            $filename = basename($file);
            if (!$this->dryRun) {
                copy($file, config_path($filename));
            }
            $this->stats['configs_imported']++;
        }
        
        $this->info("   ✅ Конфигураций импортировано: {$this->stats['configs_imported']}");
    }

    protected function importImages()
    {
        $this->newLine();
        $this->info('🖼️  Импорт изображений...');
        
        $imagesDir = $this->importDir . '/images';
        
        if (!is_dir($imagesDir)) {
            $this->warn('   ⚠️  Директория images не найдена');
            return;
        }
        
        // Сканируем все файлы
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($imagesDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file;
            }
        }
        
        if (empty($files)) {
            $this->warn('   ⚠️  Изображения не найдены');
            return;
        }
        
        $this->line("   Найдено файлов: " . count($files));
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        foreach ($files as $file) {
            $relativePath = str_replace($imagesDir . '/', '', $file->getPathname());
            $targetPath = public_path($relativePath);
            $targetDir = dirname($targetPath);
            
            if (!$this->dryRun) {
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                if (!file_exists($targetPath) || $this->option('force')) {
                    copy($file->getPathname(), $targetPath);
                    $this->stats['images_imported']++;
                }
            } else {
                $this->stats['images_imported']++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("   ✅ Изображений импортировано: {$this->stats['images_imported']}");
    }

    protected function cleanupImportDir()
    {
        if ($this->importDir && is_dir($this->importDir)) {
            $this->deleteDirectory($this->importDir);
        }
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
}
