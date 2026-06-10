<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeDiskImages extends Command
{
    protected $signature = 'images:optimize-disk 
                            {--dry-run : Показать что будет конвертировано} 
                            {--quality=80 : Качество WebP (0-100)}
                            {--delete-original : Удалить оригиналы после конвертации}
                            {--limit= : Ограничить количество изображений}
                            {--force : Пропустить подтверждение}';

    protected $description = 'Массовая конвертация JPG/PNG файлов на диске в WebP формат';

    private $converted = 0;
    private $failed = 0;
    private $skipped = 0;
    private $savedSpace = 0;

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $quality = (int) $this->option('quality');
        $deleteOriginal = $this->option('delete-original');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $force = $this->option('force');
        
        // Проверяем наличие GD библиотеки с поддержкой WebP
        if (!function_exists('imagewebp')) {
            $this->error('❌ GD библиотека не поддерживает WebP');
            $this->error('Установите PHP с поддержкой WebP или используйте расширение imagick');
            return 1;
        }
        
        $this->info('🖼️  Оптимизация изображений на диске...');
        $this->newLine();
        
        if ($dryRun) {
            $this->warn('🔍 РЕЖИМ ТЕСТИРОВАНИЯ - изображения не будут конвертированы');
            $this->newLine();
        }
        
        $imagesPath = public_path('imgnews');
        
        if (!File::exists($imagesPath)) {
            $this->error('Директория imgnews не найдена');
            return 1;
        }
        
        // Находим все JPG и PNG рекурсивно
        $this->info('⏳ Сканирование директории...');
        $images = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($imagesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    // Проверяем, что WebP версия еще не существует
                    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file->getPathname());
                    if (!File::exists($webpPath)) {
                        $images[] = $file->getPathname();
                    }
                }
            }
        }
        
        // Ограничиваем количество если указано
        if ($limit) {
            $images = array_slice($images, 0, $limit);
        }
        
        $totalImages = count($images);
        $totalSize = 0;
        
        foreach ($images as $image) {
            $totalSize += filesize($image);
        }
        
        $this->info("📊 Найдено изображений для конвертации: " . number_format($totalImages));
        $this->info("📦 Общий размер: " . $this->formatBytes($totalSize));
        $this->info("🎨 Качество WebP: {$quality}%");
        $this->newLine();
        
        if ($totalImages === 0) {
            $this->info('✅ Все изображения уже в формате WebP или не найдены');
            return 0;
        }
        
        if ($dryRun) {
            $estimatedSavings = $totalSize * 0.7; // ~70% экономии
            $this->info("💡 Ожидаемая экономия: ~" . $this->formatBytes($estimatedSavings));
            $this->newLine();
            
            // Показываем примеры
            $this->info('📝 Примеры файлов для конвертации:');
            foreach (array_slice($images, 0, 5) as $img) {
                $size = $this->formatBytes(filesize($img));
                $relativePath = str_replace(public_path(), '', $img);
                $this->line("   {$relativePath} ({$size})");
            }
            if ($totalImages > 5) {
                $this->line("   ... и еще " . ($totalImages - 5) . " файлов");
            }
            
            $this->newLine();
            $this->info('Для конвертации запустите без --dry-run');
            return 0;
        }
        
        if (!$force && !$this->confirm("Начать конвертацию {$totalImages} изображений?")) {
            $this->info('Операция отменена');
            return 0;
        }
        
        $this->newLine();
        $bar = $this->output->createProgressBar($totalImages);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('Начинаем...');
        $bar->start();
        
        foreach ($images as $imagePath) {
            $relativePath = basename($imagePath);
            $bar->setMessage($relativePath);
            $this->convertToWebP($imagePath, $quality, $deleteOriginal);
            $bar->advance();
        }
        
        $bar->setMessage('Завершено!');
        $bar->finish();
        $this->newLine(2);
        
        $this->info('✅ Конвертация завершена!');
        $this->newLine();
        $this->info("📊 Статистика:");
        $this->line("   ✅ Конвертировано: " . number_format($this->converted));
        $this->line("   ⏭️  Пропущено: " . number_format($this->skipped));
        $this->line("   ❌ Ошибок: " . number_format($this->failed));
        $this->line("   💾 Сэкономлено места: " . $this->formatBytes($this->savedSpace));
        
        if ($this->savedSpace > 0 && $totalSize > 0) {
            $savingsPercent = round(($this->savedSpace / $totalSize) * 100, 1);
            $this->line("   📉 Экономия: {$savingsPercent}%");
        }
        
        return 0;
    }

    private function convertToWebP(string $imagePath, int $quality, bool $deleteOriginal): void
    {
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imagePath);
        
        // Пропускаем если уже существует
        if (File::exists($webpPath)) {
            $this->skipped++;
            return;
        }
        
        try {
            $originalSize = filesize($imagePath);
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            
            // Создаем изображение из источника
            $image = match($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($imagePath),
                'png' => @imagecreatefrompng($imagePath),
                default => false
            };
            
            if ($image === false) {
                $this->failed++;
                return;
            }
            
            // Сохраняем прозрачность для PNG
            if ($extension === 'png') {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            
            // Конвертируем в WebP
            if (!imagewebp($image, $webpPath, $quality)) {
                $this->failed++;
                imagedestroy($image);
                return;
            }
            
            imagedestroy($image);
            
            $webpSize = filesize($webpPath);
            $this->savedSpace += ($originalSize - $webpSize);
            $this->converted++;
            
            // Удаляем оригинал если нужно
            if ($deleteOriginal) {
                File::delete($imagePath);
            }
            
        } catch (\Exception $e) {
            $this->failed++;
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

