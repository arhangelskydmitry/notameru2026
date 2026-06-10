<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ConvertJpgToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:convert-to-webp {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert all JPG images in posts to WebP format';

    protected $manager;
    protected $convertedCount = 0;
    protected $skippedCount = 0;
    protected $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }
        
        $this->info('🚀 Starting JPG to WebP conversion...');
        $this->newLine();
        
        // Инициализируем Intervention Image
        $this->manager = new ImageManager(new Driver());
        
        // Получаем все посты
        $posts = Post::where('post_type', 'post')
            ->where('post_status', '!=', 'trash')
            ->get();
        
        $this->info("📊 Found {$posts->count()} posts to process");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($posts->count());
        $progressBar->start();
        
        foreach ($posts as $post) {
            $this->processPost($post, $dryRun);
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Итоговая статистика
        $this->info('✅ Conversion completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Converted', $this->convertedCount],
                ['⏭️  Skipped (already WebP)', $this->skippedCount],
                ['❌ Errors', $this->errorCount],
            ]
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN - no actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        }
        
        return Command::SUCCESS;
    }

    /**
     * Обработка одного поста
     */
    protected function processPost(Post $post, bool $dryRun)
    {
        $content = $post->post_content;
        $updated = false;
        
        // Находим все изображения с расширениями jpg, jpeg, png, gif
        preg_match_all('/<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png|gif))["\'][^>]*>/i', $content, $matches);
        
        if (empty($matches[1])) {
            return;
        }
        
        foreach ($matches[1] as $imageUrl) {
            $result = $this->convertImage($imageUrl, $dryRun);
            
            if ($result['converted']) {
                // Заменяем URL в контенте
                $webpUrl = $result['webp_url'];
                $content = str_replace($imageUrl, $webpUrl, $content);
                $updated = true;
                $this->convertedCount++;
            } elseif ($result['skipped']) {
                $this->skippedCount++;
            } elseif ($result['error']) {
                $this->errorCount++;
            }
        }
        
        // Обновляем пост если были изменения
        if ($updated && !$dryRun) {
            $post->post_content = $content;
            $post->save();
        }
    }

    /**
     * Конвертация одного изображения
     */
    protected function convertImage(string $imageUrl, bool $dryRun): array
    {
        // Извлекаем путь из URL
        $path = parse_url($imageUrl, PHP_URL_PATH);
        
        // Определяем абсолютный путь к файлу
        $absolutePath = public_path($path);
        
        // Проверяем существование исходного файла
        if (!file_exists($absolutePath)) {
            return ['converted' => false, 'skipped' => false, 'error' => true];
        }
        
        // Формируем путь к WebP файлу
        $pathInfo = pathinfo($absolutePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        $webpUrl = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        $webpUrl = str_replace(public_path(), '', $webpUrl);
        
        // Проверяем, существует ли уже WebP версия
        if (file_exists($webpPath)) {
            return [
                'converted' => true,
                'skipped' => false,
                'error' => false,
                'webp_url' => $webpUrl
            ];
        }
        
        // Если DRY RUN, просто возвращаем что файл будет конвертирован
        if ($dryRun) {
            return [
                'converted' => true,
                'skipped' => false,
                'error' => false,
                'webp_url' => $webpUrl
            ];
        }
        
        // Конвертируем в WebP
        try {
            $image = $this->manager->read($absolutePath);
            
            // Сохраняем как WebP с качеством 85%
            $image->toWebp(85)->save($webpPath);
            
            return [
                'converted' => true,
                'skipped' => false,
                'error' => false,
                'webp_url' => $webpUrl
            ];
            
        } catch (\Exception $e) {
            $this->error("Failed to convert: {$imageUrl}");
            $this->error("Error: " . $e->getMessage());
            
            return ['converted' => false, 'skipped' => false, 'error' => true];
        }
    }
}

