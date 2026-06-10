<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--filename= : Имя файла для сохранения (без расширения)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание резервной копии базы данных MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗄️  Создание резервной копии базы данных...');
        $this->newLine();
        
        // Получаем настройки подключения
        $config = config('database.connections.mysql');
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        
        // Определяем имя файла
        $filename = $this->option('filename') 
            ? $this->option('filename') 
            : $database . '_backup_' . date('Y-m-d_His');
        
        // Директория для бэкапов
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/' . $filename . '.sql';
        
        $this->line("📂 Директория: {$backupDir}");
        $this->line("📝 Файл: {$filename}.sql");
        $this->newLine();
        
        // Проверяем доступность mysqldump
        $mysqldumpPath = $this->findMysqldump();
        
        if (!$mysqldumpPath) {
            $this->error('❌ mysqldump не найден!');
            $this->warn('Убедитесь, что MySQL установлен и mysqldump доступен в PATH');
            return 1;
        }
        
        $this->info("🔍 Используется: {$mysqldumpPath}");
        
        // Формируем команду mysqldump
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            $mysqldumpPath,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );
        
        $this->info('⏳ Создание резервной копии (это может занять некоторое время)...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();
        
        // Выполняем команду
        exec($command, $output, $returnVar);
        
        $bar->finish();
        $this->newLine(2);
        
        if ($returnVar !== 0) {
            $this->error('❌ Ошибка при создании резервной копии!');
            if (!empty($output)) {
                $this->line(implode("\n", $output));
            }
            return 1;
        }
        
        // Проверяем размер файла
        if (!file_exists($backupFile)) {
            $this->error('❌ Файл резервной копии не был создан!');
            return 1;
        }
        
        $fileSize = filesize($backupFile);
        
        if ($fileSize === 0) {
            $this->error('❌ Файл резервной копии пустой!');
            unlink($backupFile);
            return 1;
        }
        
        // Сжимаем файл
        $this->info('🗜️  Сжатие файла...');
        
        $gzipFile = $backupFile . '.gz';
        $this->gzipFile($backupFile, $gzipFile);
        
        if (file_exists($gzipFile)) {
            unlink($backupFile); // Удаляем несжатый файл
            $compressedSize = filesize($gzipFile);
            
            $this->newLine();
            $this->info('✅ Резервная копия успешно создана!');
            $this->newLine();
            $this->line("📁 Файл: {$gzipFile}");
            $this->line("📊 Размер (исходный): " . $this->formatBytes($fileSize));
            $this->line("📦 Размер (сжатый): " . $this->formatBytes($compressedSize));
            $this->line("💾 Экономия: " . round((1 - $compressedSize / $fileSize) * 100, 1) . '%');
            
            // Статистика базы данных
            $this->newLine();
            $this->info('📊 Статистика базы данных:');
            $this->showDatabaseStats();
        } else {
            $this->newLine();
            $this->info('✅ Резервная копия успешно создана!');
            $this->line("📁 Файл: {$backupFile}");
            $this->line("📊 Размер: " . $this->formatBytes($fileSize));
        }
        
        return 0;
    }
    
    /**
     * Найти путь к mysqldump
     */
    protected function findMysqldump(): ?string
    {
        $possiblePaths = [
            'mysqldump', // В PATH
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump', // Homebrew на Apple Silicon
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/MAMP/Library/bin/mysqldump', // MAMP
        ];
        
        foreach ($possiblePaths as $path) {
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
    
    /**
     * Сжать файл в gzip
     */
    protected function gzipFile($source, $destination)
    {
        $bufferSize = 4096;
        $file = fopen($source, 'rb');
        $gzFile = gzopen($destination, 'wb9');
        
        while (!feof($file)) {
            gzwrite($gzFile, fread($file, $bufferSize));
        }
        
        fclose($file);
        gzclose($gzFile);
    }
    
    /**
     * Форматировать размер в человекочитаемый вид
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Показать статистику базы данных
     */
    protected function showDatabaseStats()
    {
        DB::setDefaultConnection('mysql');
        
        $tables = DB::select("
            SELECT 
                COUNT(*) as table_count,
                SUM(table_rows) as total_rows,
                SUM(data_length + index_length) as total_size
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
        ");
        
        if (!empty($tables)) {
            $stats = $tables[0];
            $this->line("   Таблиц: " . number_format($stats->table_count));
            $this->line("   Записей: " . number_format($stats->total_rows));
            $this->line("   Размер БД: " . $this->formatBytes($stats->total_size));
        }
    }
}

