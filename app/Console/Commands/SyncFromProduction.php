<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncFromProduction extends Command
{
    protected $signature = 'sync:production 
                            {--url= : URL сервера для скачивания архива}
                            {--key= : Секретный ключ для доступа}
                            {--since= : Скачать статьи с даты}
                            {--with-images : Включить изображения}
                            {--auto-import : Автоматически импортировать после скачивания}';

    protected $description = 'Синхронизация с production сервера (скачивание и импорт)';

    public function handle()
    {
        $this->info('🔄 СИНХРОНИЗАЦИЯ С PRODUCTION');
        $this->newLine();
        
        // Параметры
        $url = $this->option('url') ?: $this->ask('URL сервера (например, https://notame.ru)');
        $key = $this->option('key') ?: $this->secret('Секретный ключ');
        
        if (!$url || !$key) {
            $this->error('❌ URL и ключ обязательны');
            return 1;
        }
        
        // Формируем URL для скачивания
        $params = [
            'key' => $key,
            'action' => 'export',
        ];
        
        if ($this->option('since')) {
            $params['since'] = $this->option('since');
        }
        
        if ($this->option('with-images')) {
            $params['with_images'] = '1';
        }
        
        $exportUrl = rtrim($url, '/') . '/sync-export.php?' . http_build_query($params);
        
        $this->line("📡 Подключение к: {$url}");
        
        // Скачиваем архив
        $this->info('⏳ Скачивание архива...');
        
        $downloadDir = storage_path('downloads');
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }
        
        $archivePath = $downloadDir . '/sync_' . date('Y-m-d_His') . '.tar.gz';
        
        // Используем curl для скачивания
        $ch = curl_init($exportUrl);
        $fp = fopen($archivePath, 'w');
        
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'NotameSyncClient/1.0',
        ]);
        
        // Прогресс-бар
        $bar = $this->output->createProgressBar(100);
        $bar->start();
        
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($ch, $downloadSize, $downloaded) use ($bar) {
            if ($downloadSize > 0) {
                $bar->setProgress((int)(($downloaded / $downloadSize) * 100));
            }
        });
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fp);
        
        $bar->finish();
        $this->newLine();
        
        if (!$result || $httpCode !== 200) {
            $this->error("❌ Ошибка скачивания. HTTP код: {$httpCode}");
            if ($error) {
                $this->error("   Ошибка: {$error}");
            }
            unlink($archivePath);
            return 1;
        }
        
        // Проверяем размер файла
        $fileSize = filesize($archivePath);
        if ($fileSize < 100) {
            $content = file_get_contents($archivePath);
            $this->error("❌ Ошибка: {$content}");
            unlink($archivePath);
            return 1;
        }
        
        $this->info("✅ Архив скачан: " . $this->formatBytes($fileSize));
        $this->line("   Путь: {$archivePath}");
        
        // Автоматический импорт
        if ($this->option('auto-import') || $this->confirm('Импортировать архив?', true)) {
            $this->newLine();
            $this->call('articles:import', [
                'archive' => $archivePath,
            ]);
        } else {
            $this->newLine();
            $this->info('💡 Для импорта выполните:');
            $this->line("   php artisan articles:import {$archivePath}");
        }
        
        return 0;
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
