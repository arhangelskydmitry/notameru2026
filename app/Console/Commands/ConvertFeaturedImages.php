<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConvertFeaturedImages extends Command
{
    protected $signature = 'images:convert-featured {--limit=100 : Number of images to process}';
    protected $description = 'Convert featured images (thumbnails) to WebP format';

    private $converted = 0;
    private $skipped = 0;
    private $failed = 0;

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info('🔍 Поиск featured images для конвертации...');
        
        // Получаем все attachment (featured images)
        $attachments = Post::where('post_type', 'attachment')
            ->whereHas('meta', function($query) {
                $query->where('meta_key', '_wp_attached_file');
            })
            ->limit($limit)
            ->get();
        
        $total = $attachments->count();
        $this->info("📊 Найдено вложений: {$total}");
        
        if ($total === 0) {
            $this->info('✅ Нет изображений для обработки');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($attachments as $attachment) {
            $this->processAttachment($attachment);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('📊 РЕЗУЛЬТАТЫ:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Обработано', $total],
                ['✅ Сконвертировано', $this->converted],
                ['⏭️  Пропущено (уже WebP)', $this->skipped],
                ['❌ Ошибок', $this->failed],
            ]
        );
        
        return 0;
    }
    
    private function processAttachment($attachment)
    {
        try {
            $attachedFile = $attachment->getMeta('_wp_attached_file');
            
            if (!$attachedFile) {
                $this->failed++;
                return;
            }
            
            // Извлекаем путь из GUID если нужно
            $oldUrl = $attachment->guid;
            
            // Если это внешний URL, скачиваем
            if (str_starts_with($oldUrl, 'http')) {
                $this->downloadAndConvert($attachment, $oldUrl, $attachedFile);
            }
            
        } catch (\Exception $e) {
            $this->failed++;
            \Log::error('Failed to process attachment: ' . $attachment->ID, [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function downloadAndConvert($attachment, $url, $attachedFile)
    {
        try {
            // Проверяем, не сконвертировано ли уже
            $filename = basename($attachedFile);
            $webpFilename = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filename);
            $localPath = public_path('imgnews/' . $webpFilename);
            
            if (file_exists($localPath)) {
                $this->skipped++;
                return;
            }
            
            // Скачиваем изображение
            $imageContent = @file_get_contents($url);
            
            if (!$imageContent) {
                $this->failed++;
                return;
            }
            
            // Конвертируем в WebP
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContent);
            
            // Ресайз если нужно
            if ($image->width() > 1200 || $image->height() > 1200) {
                $image->scale(width: 1200);
            }
            
            // Сохраняем как WebP
            $image->toWebp(quality: 85)->save($localPath);
            
            $this->converted++;
            
        } catch (\Exception $e) {
            $this->failed++;
            \Log::error('Failed to download/convert: ' . $url, [
                'error' => $e->getMessage()
            ]);
        }
    }
}




