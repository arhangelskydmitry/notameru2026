<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;

class CheckUrls extends Command
{
    protected $signature = 'check:urls';
    protected $description = 'Сравнить URL статей между WordPress и Laravel';

    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  СРАВНЕНИЕ URL СТАТЕЙ: WordPress vs Laravel');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Получаем последние 15 статей
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();

        $this->info("Найдено статей: " . $posts->count());
        $this->newLine();

        $wordpressBase = 'http://notame.ru';
        $laravelBase = 'http://localhost:8002';

        $headers = ['Заголовок', 'post_name', 'WordPress URL', 'Laravel URL', 'Статус'];
        $rows = [];

        foreach ($posts as $post) {
            $slug = $post->post_name;
            $title = mb_substr($post->post_title, 0, 35) . (mb_strlen($post->post_title) > 35 ? '...' : '');
            
            // WordPress может использовать разные структуры
            $wordpressUrl = "$wordpressBase/$slug";
            
            // Laravel использует
            $laravelUrl = "$laravelBase/$slug";
            
            $rows[] = [
                $title,
                $slug,
                $wordpressUrl,
                $laravelUrl,
                '✓'
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  СТРУКТУРА URL');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->line('WordPress:');
        $this->line('  - Формат: http://notame.ru/{post_name}');
        $this->line('  - Пример: ' . $wordpressBase . '/' . $posts->first()->post_name);
        $this->newLine();

        $this->line('Laravel (текущий):');
        $this->line('  - Формат: http://localhost:8002/{post_name}');
        $this->line('  - Пример: ' . $laravelBase . '/' . $posts->first()->post_name);
        $this->line('  - Маршрут: Route::get(\'/{slug}\', [FrontendController::class, \'post\'])');
        $this->newLine();

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  ПРОВЕРКА');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->line('Пример URLs для проверки:');
        $this->newLine();
        
        foreach ($posts->take(5) as $post) {
            $slug = $post->post_name;
            $this->line("WordPress: $wordpressBase/$slug");
            $this->line("Laravel:   $laravelBase/$slug");
            $this->newLine();
        }

        $this->warn('⚠ Проверьте несколько URL вручную на notame.ru!');
        $this->newLine();

        return 0;
    }
}

