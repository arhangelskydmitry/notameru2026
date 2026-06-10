<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WordPress\Post;

class CleanWordPressRevisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp:clean-revisions {--dry-run : Показать что будет удалено}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаление WordPress ревизий и автосохранений';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        DB::setDefaultConnection('mysql');
        
        if ($dryRun) {
            $this->warn('🔍 РЕЖИМ ТЕСТИРОВАНИЯ');
            $this->newLine();
        } else {
            $this->info('🗑️  Очистка WordPress ревизий и автосохранений...');
            $this->newLine();
        }
        
        // 1. Подсчет ревизий
        $revisions = Post::on('mysql')
            ->where('post_type', 'revision')
            ->get();
        
        $this->info("1️⃣  Ревизии постов:");
        $this->line("   Найдено: " . $revisions->count());
        
        // 2. Подсчет автосохранений
        $autosaves = Post::on('mysql')
            ->where('post_name', 'LIKE', '%-autosave-%')
            ->get();
        
        $this->info("2️⃣  Автосохранения:");
        $this->line("   Найдено: " . $autosaves->count());
        
        // 3. Orphaned метаданные (без постов)
        $orphanedMeta = DB::connection('mysql')->select("
            SELECT COUNT(*) as count
            FROM wp_postmeta pm
            LEFT JOIN wp_posts p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        $this->info("3️⃣  Orphaned метаданные:");
        $this->line("   Найдено: " . $orphanedMeta[0]->count);
        
        $this->newLine();
        
        $totalToDelete = $revisions->count() + $autosaves->count() + $orphanedMeta[0]->count;
        
        if ($totalToDelete === 0) {
            $this->info('✅ Нет данных для удаления');
            return 0;
        }
        
        $this->warn("📊 Всего будет удалено: {$totalToDelete} записей");
        $this->newLine();
        
        if ($dryRun) {
            $this->info('Это тестовый режим. Для удаления запустите без --dry-run');
            return 0;
        }
        
        // Удаление
        $bar = $this->output->createProgressBar(3);
        $bar->start();
        
        $deleted = 0;
        
        // Удаляем ревизии
        foreach ($revisions as $revision) {
            $revision->delete();
            $deleted++;
        }
        $bar->advance();
        
        // Удаляем автосохранения
        foreach ($autosaves as $autosave) {
            $autosave->delete();
            $deleted++;
        }
        $bar->advance();
        
        // Удаляем orphaned метаданные
        DB::connection('mysql')->delete("
            DELETE pm FROM wp_postmeta pm
            LEFT JOIN wp_posts p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        $deleted += $orphanedMeta[0]->count;
        $bar->advance();
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Очистка завершена!");
        $this->line("   Удалено записей: " . number_format($deleted));
        
        // Оптимизация таблиц
        $this->newLine();
        $this->info("🔧 Оптимизация таблиц...");
        DB::connection('mysql')->statement('OPTIMIZE TABLE wp_posts');
        DB::connection('mysql')->statement('OPTIMIZE TABLE wp_postmeta');
        $this->info("✅ Таблицы оптимизированы");
        
        return 0;
    }
}

