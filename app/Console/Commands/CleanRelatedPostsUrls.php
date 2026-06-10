<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WordPress\Post;

class CleanRelatedPostsUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:clean-relatedposts-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка URL от параметров relatedposts в контенте статей';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Поиск статей с параметрами relatedposts в URL...');
        
        // Используем явно MySQL подключение
        DB::setDefaultConnection('mysql');
        
        // Паттерн для поиска URL с параметрами relatedposts
        $pattern = '%?relatedposts_hit=%&relatedposts_origin=%&relatedposts_position=%';
        
        // Находим все посты с такими URL
        $posts = Post::on('mysql')
            ->where('post_type', 'post')
            ->where(function($query) use ($pattern) {
                $query->where('post_content', 'LIKE', $pattern)
                      ->orWhere('post_content', 'LIKE', '%\?relatedposts%')
                      ->orWhere('post_content', 'LIKE', '%&relatedposts%');
            })
            ->get();
        
        if ($posts->isEmpty()) {
            $this->info('✅ URL с параметрами relatedposts не найдены');
            return 0;
        }
        
        $this->info("Найдено статей с параметрами: {$posts->count()}");
        $this->newLine();
        
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        $updatedCount = 0;
        $totalReplacements = 0;
        
        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $updatedContent = $originalContent;
            
            // Удаляем параметры relatedposts из URL
            // Паттерн 1: ?relatedposts_hit=1&relatedposts_origin=14414&relatedposts_position=0
            $updatedContent = preg_replace(
                '/\?relatedposts_hit=\d+&relatedposts_origin=\d+&relatedposts_position=\d+/',
                '',
                $updatedContent,
                -1,
                $count1
            );
            
            // Паттерн 2: &relatedposts_hit=1&relatedposts_origin=14414&relatedposts_position=0
            $updatedContent = preg_replace(
                '/&relatedposts_hit=\d+&relatedposts_origin=\d+&relatedposts_position=\d+/',
                '',
                $updatedContent,
                -1,
                $count2
            );
            
            // Паттерн 3: любые другие варианты с relatedposts
            $updatedContent = preg_replace(
                '/[\?&]relatedposts_[^"\'\s&]+=[^"\'\s&]+/',
                '',
                $updatedContent,
                -1,
                $count3
            );
            
            $replacements = $count1 + $count2 + $count3;
            
            if ($updatedContent !== $originalContent) {
                $post->post_content = $updatedContent;
                $post->save();
                $updatedCount++;
                $totalReplacements += $replacements;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Обработка завершена!");
        $this->info("   Обновлено статей: {$updatedCount}");
        $this->info("   Всего замен: {$totalReplacements}");
        
        // Показываем примеры изменений
        if ($updatedCount > 0) {
            $this->newLine();
            $this->info('📝 Примеры очищенных URL:');
            $this->line('   До:  /zakrytie-festivalya-teatralnyh-shkol-stran-briks?relatedposts_hit=1&relatedposts_origin=14414&relatedposts_position=0');
            $this->line('   После: /zakrytie-festivalya-teatralnyh-shkol-stran-briks');
        }
        
        return 0;
    }
}

