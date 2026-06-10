<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WordPress\Post;

class ConvertHttpToHttps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:convert-http-to-https {--dry-run : Только показать, что будет изменено без реального изменения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Замена всех http:// на https:// в базе данных';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 РЕЖИМ ТЕСТИРОВАНИЯ - изменения не будут сохранены');
            $this->newLine();
        } else {
            $this->info('🔄 Замена http:// на https:// в базе данных...');
            $this->newLine();
        }
        
        // Используем явно MySQL подключение
        DB::setDefaultConnection('mysql');
        
        $totalUpdated = 0;
        
        // 1. Обновляем wp_posts (post_content и guid)
        $this->info('📝 Обработка таблицы wp_posts...');
        $postsUpdated = $this->updatePosts($dryRun);
        $totalUpdated += $postsUpdated;
        $this->info("   Обновлено записей: {$postsUpdated}");
        $this->newLine();
        
        // 2. Обновляем wp_postmeta
        $this->info('🔖 Обработка таблицы wp_postmeta...');
        $metaUpdated = $this->updatePostMeta($dryRun);
        $totalUpdated += $metaUpdated;
        $this->info("   Обновлено записей: {$metaUpdated}");
        $this->newLine();
        
        // 3. Обновляем wp_options
        $this->info('⚙️  Обработка таблицы wp_options...');
        $optionsUpdated = $this->updateOptions($dryRun);
        $totalUpdated += $optionsUpdated;
        $this->info("   Обновлено записей: {$optionsUpdated}");
        $this->newLine();
        
        if ($dryRun) {
            $this->warn("📊 Всего будет обновлено записей: {$totalUpdated}");
            $this->info('');
            $this->info('Для реального обновления запустите команду без флага --dry-run:');
            $this->comment('php artisan db:convert-http-to-https');
        } else {
            $this->info("✅ Замена завершена! Всего обновлено записей: {$totalUpdated}");
        }
        
        return 0;
    }
    
    /**
     * Обновление таблицы wp_posts
     */
    protected function updatePosts($dryRun = false): int
    {
        $posts = Post::on('mysql')
            ->where(function($query) {
                $query->where('post_content', 'LIKE', '%http://%')
                      ->orWhere('guid', 'LIKE', 'http://%')
                      ->orWhere('post_excerpt', 'LIKE', '%http://%');
            })
            ->get();
        
        $count = 0;
        
        foreach ($posts as $post) {
            $updated = false;
            
            // Обновляем post_content
            if ($post->post_content && str_contains($post->post_content, 'http://')) {
                $post->post_content = str_replace('http://', 'https://', $post->post_content);
                $updated = true;
            }
            
            // Обновляем guid
            if ($post->guid && str_contains($post->guid, 'http://')) {
                $post->guid = str_replace('http://', 'https://', $post->guid);
                $updated = true;
            }
            
            // Обновляем post_excerpt
            if ($post->post_excerpt && str_contains($post->post_excerpt, 'http://')) {
                $post->post_excerpt = str_replace('http://', 'https://', $post->post_excerpt);
                $updated = true;
            }
            
            if ($updated) {
                if (!$dryRun) {
                    $post->save();
                }
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Обновление таблицы wp_postmeta
     */
    protected function updatePostMeta($dryRun = false): int
    {
        $meta = DB::connection('mysql')
            ->table('wp_postmeta')
            ->where('meta_value', 'LIKE', '%http://%')
            ->get();
        
        $count = 0;
        
        foreach ($meta as $m) {
            if (str_contains($m->meta_value, 'http://')) {
                $newValue = str_replace('http://', 'https://', $m->meta_value);
                
                if (!$dryRun) {
                    DB::connection('mysql')
                        ->table('wp_postmeta')
                        ->where('meta_id', $m->meta_id)
                        ->update(['meta_value' => $newValue]);
                }
                
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Обновление таблицы wp_options
     */
    protected function updateOptions($dryRun = false): int
    {
        $options = DB::connection('mysql')
            ->table('wp_options')
            ->where('option_value', 'LIKE', '%http://%')
            ->get();
        
        $count = 0;
        
        foreach ($options as $option) {
            if (str_contains($option->option_value, 'http://')) {
                $newValue = str_replace('http://', 'https://', $option->option_value);
                
                if (!$dryRun) {
                    DB::connection('mysql')
                        ->table('wp_options')
                        ->where('option_id', $option->option_id)
                        ->update(['option_value' => $newValue]);
                }
                
                $count++;
            }
        }
        
        return $count;
    }
}

