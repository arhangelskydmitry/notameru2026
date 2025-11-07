<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateViewStatistics extends Command
{
    protected $signature = 'migrate:views {--reset : Reset existing view statistics}';
    protected $description = 'Migrate post view statistics from WordPress to Laravel';

    public function handle()
    {
        $this->info('🔄 Migrating post view statistics...');
        
        // Используем явное подключение к MySQL
        $connection = DB::connection('mysql');
        
        if ($this->option('reset')) {
            $this->warn('⚠️  Resetting existing view statistics...');
            $connection->table('post_views')->truncate();
        }
        
        // Получаем все посты с их просмотрами из wp_post_views
        // type = 1 означает посты (не страницы и другие типы)
        $this->info('📊 Reading statistics from wp_post_views...');
        
        $posts = $connection->table('wp_post_views as pv')
            ->join('wp_posts as p', 'pv.id', '=', 'p.ID')
            ->where('pv.type', 1)
            ->where('p.post_type', 'post')
            ->where('p.post_status', 'publish')
            ->select(
                'p.ID',
                'p.post_title',
                'p.post_date',
                $connection->raw('SUM(pv.count) as views')
            )
            ->groupBy('p.ID', 'p.post_title', 'p.post_date')
            ->orderBy('views', 'desc')
            ->get();
        
        if ($posts->isEmpty()) {
            $this->warn('❌ No view statistics found in WordPress meta');
            return;
        }
        
        $this->info("📊 Found {$posts->count()} posts with view statistics");
        
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        $totalViews = 0;
        
        foreach ($posts as $post) {
            $views = (int) $post->views;
            
            if ($views > 0) {
                // Создаем синтетические записи просмотров
                // Распределяем их равномерно от даты публикации до сегодня
                $startDate = \Carbon\Carbon::parse($post->post_date);
                $endDate = now();
                $daysSpan = $startDate->diffInDays($endDate);
                
                if ($daysSpan > 0) {
                    // Создаем несколько записей для имитации распределения просмотров
                    $viewsToCreate = min($views, 1000); // Ограничиваем для производительности
                    $viewsPerDay = $viewsToCreate / max($daysSpan, 1);
                    
                    for ($i = 0; $i < $viewsToCreate; $i++) {
                        $randomDay = rand(0, $daysSpan);
                        $viewDate = $startDate->copy()->addDays($randomDay);
                        
                        try {
                            $connection->table('post_views')->insert([
                                'post_id' => $post->ID,
                                'ip_address' => $this->generateRandomIp(),
                                'user_agent' => 'Migrated from WordPress',
                                'viewed_at' => $viewDate->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                            ]);
                        } catch (\Exception $e) {
                            // Игнорируем дубликаты
                        }
                    }
                }
                
                $totalViews += $views;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Migration completed!");
        $this->info("📊 Statistics:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Posts processed', number_format($posts->count())],
                ['Total views', number_format($totalViews)],
                ['Average views per post', number_format($totalViews / $posts->count(), 2)],
                ['Top post', $posts->first()->post_title . ' (' . number_format($posts->first()->views) . ' views)'],
            ]
        );
    }
    
    private function generateRandomIp()
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }
}

