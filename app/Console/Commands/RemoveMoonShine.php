<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RemoveMoonShine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moonshine:remove {--dry-run : Только показать что будет удалено}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Полное удаление MoonShine из проекта';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 РЕЖИМ ТЕСТИРОВАНИЯ - ничего не будет удалено');
        } else {
            $this->warn('⚠️  ВНИМАНИЕ! Эта операция удалит MoonShine из проекта!');
            if (!$this->confirm('Вы уверены?')) {
                $this->info('Операция отменена');
                return 0;
            }
        }
        
        $this->newLine();
        $this->info('🗑️  Удаление MoonShine из проекта...');
        $this->newLine();
        
        DB::setDefaultConnection('mysql');
        
        // 1. Удаление таблиц
        $this->info('1️⃣  Удаление таблиц базы данных...');
        $this->removeTables($dryRun);
        
        // 2. Удаление публичных файлов
        $this->info('2️⃣  Удаление публичных файлов...');
        $this->removePublicFiles($dryRun);
        
        // 3. Удаление конфигурации
        $this->info('3️⃣  Удаление конфигурационных файлов...');
        $this->removeConfig($dryRun);
        
        // 4. Удаление из composer.json
        $this->info('4️⃣  Обновление composer.json...');
        $this->updateComposer($dryRun);
        
        $this->newLine();
        
        if ($dryRun) {
            $this->warn('Это был тестовый режим. Ничего не было удалено.');
            $this->info('Для реального удаления запустите: php artisan moonshine:remove');
        } else {
            $this->info('✅ MoonShine успешно удален!');
            $this->newLine();
            $this->warn('📝 Следующие шаги:');
            $this->line('1. Запустите: composer update');
            $this->line('2. Очистите кэш: php artisan cache:clear');
            $this->line('3. Проверьте работу админки: /notaadmin');
        }
        
        return 0;
    }
    
    /**
     * Удаление таблиц MoonShine
     */
    protected function removeTables($dryRun)
    {
        $tables = [
            'moonshine_users',
            'moonshine_user_roles',
        ];
        
        foreach ($tables as $table) {
            if (Schema::connection('mysql')->hasTable($table)) {
                $count = DB::connection('mysql')->table($table)->count();
                $this->line("   📋 {$table} ({$count} записей)");
                
                if (!$dryRun) {
                    Schema::connection('mysql')->dropIfExists($table);
                    $this->line("      ✅ Удалена");
                }
            } else {
                $this->line("   ⚠️  {$table} - не найдена");
            }
        }
        
        $this->newLine();
    }
    
    /**
     * Удаление публичных файлов
     */
    protected function removePublicFiles($dryRun)
    {
        $directories = [
            public_path('vendor/moonshine'),
            public_path('vendor/moonshine-tinymce'),
        ];
        
        $totalSize = 0;
        $filesCount = 0;
        
        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                $size = $this->getDirectorySize($dir);
                $files = $this->countFilesInDirectory($dir);
                $totalSize += $size;
                $filesCount += $files;
                
                $this->line("   📁 " . basename(dirname($dir)) . '/' . basename($dir));
                $this->line("      Файлов: {$files}, Размер: " . $this->formatBytes($size));
                
                if (!$dryRun) {
                    File::deleteDirectory($dir);
                    $this->line("      ✅ Удалена");
                }
            } else {
                $this->line("   ⚠️  " . basename($dir) . " - не найдена");
            }
        }
        
        $this->line("   📊 Всего будет освобождено: " . $this->formatBytes($totalSize));
        $this->newLine();
    }
    
    /**
     * Удаление конфигурационных файлов
     */
    protected function removeConfig($dryRun)
    {
        $configFiles = [
            config_path('moonshine.php'),
        ];
        
        foreach ($configFiles as $file) {
            if (File::exists($file)) {
                $this->line("   📄 " . basename($file));
                
                if (!$dryRun) {
                    File::delete($file);
                    $this->line("      ✅ Удален");
                }
            } else {
                $this->line("   ⚠️  " . basename($file) . " - не найден");
            }
        }
        
        $this->newLine();
    }
    
    /**
     * Обновление composer.json
     */
    protected function updateComposer($dryRun)
    {
        $composerPath = base_path('composer.json');
        $composerData = json_decode(File::get($composerPath), true);
        
        $packagesToRemove = [
            'moonshine/moonshine',
            'moonshine/tinymce',
        ];
        
        $removed = [];
        
        foreach ($packagesToRemove as $package) {
            if (isset($composerData['require'][$package])) {
                $version = $composerData['require'][$package];
                $this->line("   📦 {$package} ({$version})");
                $removed[] = $package;
                
                if (!$dryRun) {
                    unset($composerData['require'][$package]);
                }
            }
        }
        
        if (!empty($removed)) {
            if (!$dryRun) {
                File::put(
                    $composerPath,
                    json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                );
                $this->line("      ✅ composer.json обновлен");
            } else {
                $this->line("      📝 Будут удалены из composer.json");
            }
        } else {
            $this->line("   ⚠️  Пакеты MoonShine не найдены в composer.json");
        }
        
        $this->newLine();
    }
    
    /**
     * Получить размер директории
     */
    protected function getDirectorySize($path): int
    {
        $size = 0;
        
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Подсчитать файлы в директории
     */
    protected function countFilesInDirectory($path): int
    {
        if (!File::isDirectory($path)) {
            return 0;
        }
        
        return count(File::allFiles($path));
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
}

