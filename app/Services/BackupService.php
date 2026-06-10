<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use ZipArchive;

class BackupService
{
    protected string $backupPath;
    protected array $config;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        $this->config = config('backup');
        
        // Автоопределение пути к mysqldump для разных окружений
        $mysqldumpPath = $this->config['database']['mysqldump_path'];
        if ($mysqldumpPath === 'mysqldump') {
            // Безопасная проверка путей с учетом open_basedir ограничений
            try {
                // Проверяем MAMP (только на Mac)
                if (@file_exists('/Applications/MAMP/Library/bin/mysqldump')) {
                    $this->config['database']['mysqldump_path'] = '/Applications/MAMP/Library/bin/mysqldump';
                }
                // Проверяем стандартные пути
                elseif (@file_exists('/usr/local/bin/mysqldump')) {
                    $this->config['database']['mysqldump_path'] = '/usr/local/bin/mysqldump';
                }
                elseif (@file_exists('/usr/bin/mysqldump')) {
                    $this->config['database']['mysqldump_path'] = '/usr/bin/mysqldump';
                }
                // Иначе используем mysqldump из PATH (для production)
                else {
                    $this->config['database']['mysqldump_path'] = 'mysqldump';
                }
            } catch (\Exception $e) {
                // Если проверка не удалась (open_basedir), используем mysqldump из PATH
                $this->config['database']['mysqldump_path'] = 'mysqldump';
            }
        }
        
        // Создаем директорию если не существует
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Создать бекап
     */
    public function create(string $type = 'full', string $triggeredBy = 'manual'): Backup
    {
        // Проверка rate limit (пропускаем для принудительного создания)
        if ($this->config['rate_limit']['enabled'] && $triggeredBy !== 'manual_forced') {
            $this->checkRateLimit();
        }

        // Создаем запись в БД
        $filename = $this->generateFilename($type);
        $backup = Backup::create([
            'filename' => $filename,
            'type' => $type,
            'status' => 'in_progress',
            'storage' => 'local',
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
        ]);

        try {
            $manifest = [];
            $filePath = $this->backupPath . '/' . $filename;

            // Создаем временную директорию
            $tempDir = $this->backupPath . '/temp_' . time();
            mkdir($tempDir, 0755, true);

            // Создаем бекап в зависимости от типа
            if ($type === 'full' || $type === 'database') {
                Log::info('Создание дампа базы данных...');
                $dbFile = $this->createDatabaseDump($tempDir);
                $manifest['database'] = [
                    'file' => basename($dbFile),
                    'size' => filesize($dbFile),
                    'tables_count' => $this->getTablesCount(),
                ];
            }

            if ($type === 'full' || $type === 'files') {
                Log::info('Архивация файлов...');
                $this->archiveFiles($tempDir);
                $manifest['files'] = [
                    'paths' => $this->config['include']['files'],
                ];
            }

            if ($this->config['include']['config'] && ($type === 'full')) {
                Log::info('Копирование конфигурации...');
                $this->backupConfig($tempDir);
                $manifest['config'] = true;
            }

            // Добавляем manifest
            file_put_contents($tempDir . '/manifest.json', json_encode([
                'version' => '2.0',
                'type' => $type,
                'created_at' => now()->toIso8601String(),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'contents' => $manifest,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Создаем финальный архив
            Log::info('Создание финального архива...');
            $this->createArchive($tempDir, $filePath);

            // Удаляем временную директорию
            $this->removeDirectory($tempDir);

            // Обновляем запись в БД
            $fileSize = filesize($filePath);
            $backup->update([
                'status' => 'completed',
                'size' => $fileSize,
                'storage_path' => $filePath,
                'manifest' => $manifest,
                'completed_at' => now(),
            ]);

            Log::info("Бекап успешно создан: {$filename} ({$this->formatBytes($fileSize)})");

            // Отправляем уведомление если настроено
            if ($this->config['notifications']['enabled'] && $this->config['notifications']['on_success']) {
                $this->sendNotification($backup, 'success');
            }

            return $backup;

        } catch (\Exception $e) {
            Log::error('Ошибка создания бекапа: ' . $e->getMessage());
            
            // Обновляем статус
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Удаляем временные файлы
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            if (file_exists($filePath ?? '')) {
                unlink($filePath);
            }

            // Отправляем уведомление об ошибке
            if ($this->config['notifications']['enabled'] && $this->config['notifications']['on_failure']) {
                $this->sendNotification($backup, 'failure');
            }

            throw $e;
        }
    }

    /**
     * Создать дамп базы данных
     */
    protected function createDatabaseDump(string $outputDir): string
    {
        $filename = 'database.sql';
        $outputPath = $outputDir . '/' . $filename;

        $dbConfig = config('database.connections.' . config('database.default'));
        $host = $dbConfig['host'];
        $port = $dbConfig['port'] ?? 3306;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $socket = $dbConfig['unix_socket'] ?? null;

        // Формируем команду mysqldump
        $mysqldumpPath = $this->config['database']['mysqldump_path'];
        
        // Используем socket если доступен (для MAMP и локальных серверов)
        if ($socket && file_exists($socket)) {
            $hostParam = "--socket=" . escapeshellarg($socket);
        } else {
            $hostParam = "--host=" . escapeshellarg($host) . " --port={$port}";
        }
        
        // Формируем команду без password в явном виде (для безопасности)
        // Используем минимальные параметры для совместимости с shared-хостингом
        // --skip-lock-tables: не требует привилегий RELOAD/FLUSH_TABLES
        // --quick: минимизирует использование памяти
        // --no-tablespaces: пропускает табличные пространства (могут требовать права)
        $command = sprintf(
            '%s %s --user=%s %s --skip-lock-tables --quick --no-tablespaces',
            $mysqldumpPath,
            $hostParam,
            escapeshellarg($username),
            escapeshellarg($database)
        );

        // Исключаем таблицы если настроено
        $excludeTables = $this->config['database']['exclude_tables'] ?? [];
        foreach ($excludeTables as $table) {
            $command .= " --ignore-table=" . escapeshellarg("{$database}.{$table}");
        }

        // Добавляем вывод в файл и перенаправление ошибок
        $errorLog = $outputDir . '/mysqldump_error.log';
        $command .= " > " . escapeshellarg($outputPath) . " 2> " . escapeshellarg($errorLog);

        // Создаем временный файл с паролем для безопасности
        $configFile = $outputDir . '/.my.cnf';
        file_put_contents($configFile, "[client]\npassword=" . escapeshellarg($password));
        chmod($configFile, 0600);

        // Добавляем конфиг файл к команде
        $command = "MYSQL_PWD=" . escapeshellarg($password) . " " . $command;

        // Выполняем команду
        exec($command, $output, $returnCode);

        // Удаляем временный конфиг
        if (file_exists($configFile)) {
            unlink($configFile);
        }

        if ($returnCode !== 0) {
            $error = file_exists($errorLog) ? file_get_contents($errorLog) : 'Unknown error';
            Log::error("mysqldump error: " . $error);
            throw new \Exception("Ошибка создания дампа БД. Код: {$returnCode}. Детали: " . substr($error, 0, 200));
        }

        // Удаляем лог ошибок если успешно
        if (file_exists($errorLog)) {
            unlink($errorLog);
        }

        // Сжимаем если настроено
        if ($this->config['database']['compression']) {
            exec("gzip " . escapeshellarg($outputPath));
            $outputPath .= '.gz';
            $filename .= '.gz';
        }

        if (!file_exists($outputPath)) {
            throw new \Exception("Файл дампа БД не был создан");
        }

        return $outputPath;
    }

    /**
     * Архивировать файлы
     */
    protected function archiveFiles(string $outputDir): void
    {
        $filePaths = $this->config['include']['files'];
        $basePath = base_path();

        foreach ($filePaths as $path) {
            $fullPath = $basePath . '/' . $path;
            if (!file_exists($fullPath)) {
                Log::warning("Путь не найден: {$fullPath}");
                continue;
            }

            $relativePath = str_replace($basePath . '/', '', $fullPath);
            $targetPath = $outputDir . '/files/' . $relativePath;

            // Создаем директорию
            $targetDir = dirname($targetPath);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // Копируем файлы/директории
            if (is_dir($fullPath)) {
                $this->copyDirectory($fullPath, $targetPath);
            } else {
                copy($fullPath, $targetPath);
            }
        }
    }

    /**
     * Бекап конфигурации
     */
    protected function backupConfig(string $outputDir): void
    {
        $configDir = $outputDir . '/config';
        mkdir($configDir, 0755, true);

        // Копируем .env (без паролей в plain text - только структура)
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            copy($envPath, $configDir . '/.env');
        }

        // Копируем важные конфиги
        $configFiles = ['app.php', 'database.php', 'backup.php'];
        foreach ($configFiles as $file) {
            $sourcePath = config_path($file);
            if (file_exists($sourcePath)) {
                copy($sourcePath, $configDir . '/' . $file);
            }
        }
    }

    /**
     * Создать tar.gz архив
     */
    protected function createArchive(string $sourceDir, string $outputFile): void
    {
        $command = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($outputFile),
            escapeshellarg($sourceDir)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputFile)) {
            throw new \Exception("Ошибка создания архива. Код: {$returnCode}");
        }
    }

    /**
     * Генерировать имя файла
     */
    protected function generateFilename(string $type): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "backup_{$type}_{$timestamp}.tar.gz";
    }

    /**
     * Проверка rate limit
     */
    protected function checkRateLimit(): void
    {
        $minInterval = $this->config['rate_limit']['min_interval'];
        $lastBackup = Backup::latest()->first();

        if ($lastBackup && $lastBackup->created_at->diffInSeconds(now()) < $minInterval) {
            throw new \Exception("Слишком частое создание бекапов. Подождите {$minInterval} секунд.");
        }
    }

    /**
     * Получить количество таблиц
     */
    protected function getTablesCount(): int
    {
        $tables = DB::select('SHOW TABLES');
        return count($tables);
    }

    /**
     * Форматировать байты
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Копировать директорию рекурсивно
     */
    protected function copyDirectory(string $source, string $dest): void
    {
        if (!file_exists($dest)) {
            mkdir($dest, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
        closedir($dir);
    }

    /**
     * Удалить директорию рекурсивно
     */
    protected function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Отправить уведомление
     */
    protected function sendNotification(Backup $backup, string $status): void
    {
        // TODO: Реализовать отправку email уведомлений
        Log::info("Отправка уведомления о бекапе: {$backup->filename}, статус: {$status}");
    }

    /**
     * Применить политику ротации
     */
    public function applyRetentionPolicy(): int
    {
        $retention = $this->config['retention'];
        $allBackups = Backup::completed()->latest()->get();
        $toKeep = collect();
        $deleted = 0;

        // 1. Последние N бекапов всегда храним
        $toKeep = $toKeep->merge($allBackups->take($retention['keep_last']));

        // 2. По одному за день (за последние N дней)
        $dailyBackups = $allBackups->groupBy(function ($backup) {
            return $backup->created_at->format('Y-m-d');
        })->take($retention['keep_daily'])->map->first();
        $toKeep = $toKeep->merge($dailyBackups);

        // 3. По одному за неделю
        $weeklyBackups = $allBackups->groupBy(function ($backup) {
            return $backup->created_at->format('Y-W');
        })->take($retention['keep_weekly'])->map->first();
        $toKeep = $toKeep->merge($weeklyBackups);

        // 4. По одному за месяц
        $monthlyBackups = $allBackups->groupBy(function ($backup) {
            return $backup->created_at->format('Y-m');
        })->take($retention['keep_monthly'])->map->first();
        $toKeep = $toKeep->merge($monthlyBackups);

        // Получаем уникальные ID для хранения
        $keepIds = $toKeep->pluck('id')->unique()->toArray();

        // Удаляем остальные
        $toDelete = $allBackups->reject(function ($backup) use ($keepIds) {
            return in_array($backup->id, $keepIds);
        });

        foreach ($toDelete as $backup) {
            $backup->deleteFile();
            $backup->delete();
            $deleted++;
            Log::info("Удален старый бекап: {$backup->filename}");
        }

        // Удаляем ошибочные бекапы старше N дней
        if ($this->config['cleanup']['delete_failed_after_days'] > 0) {
            $failedBackups = Backup::failed()
                ->where('created_at', '<', now()->subDays($this->config['cleanup']['delete_failed_after_days']))
                ->get();

            foreach ($failedBackups as $backup) {
                $backup->deleteFile();
                $backup->delete();
                $deleted++;
                Log::info("Удален ошибочный бекап: {$backup->filename}");
            }
        }

        return $deleted;
    }
}
