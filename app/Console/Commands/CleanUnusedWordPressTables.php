<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanUnusedWordPressTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-unused-wp-tables {--dry-run : Только показать список без удаления} {--force : Подтверждение удаления}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаление неиспользуемых WordPress таблиц (плагинов)';

    /**
     * Таблицы, которые НЕЛЬЗЯ удалять (критически важные)
     */
    protected $criticalTables = [
        'wp_posts',
        'wp_postmeta',
        'wp_users',
        'wp_usermeta',
        'wp_terms',
        'wp_term_taxonomy',
        'wp_term_relationships',
        'wp_comments',
        'wp_commentmeta',
        'wp_options',
        'wp_links',
        'wp_termmeta',
    ];

    /**
     * Таблицы плагинов, которые можно безопасно удалить
     */
    protected $pluginTables = [
        // Action Scheduler (WooCommerce plugin)
        'wp_actionscheduler_actions',
        'wp_actionscheduler_claims',
        'wp_actionscheduler_groups',
        'wp_actionscheduler_logs',
        
        // All-in-One SEO
        'wp_aioseo_cache',
        'wp_aioseo_crawl_cleanup_blocked_args',
        'wp_aioseo_crawl_cleanup_logs',
        'wp_aioseo_notifications',
        'wp_aioseo_posts',
        'wp_aioseo_seo_analyzer_results',
        'wp_aioseo_writing_assistant_keywords',
        'wp_aioseo_writing_assistant_posts',
        
        // Ajax Load More
        'wp_alm',
        
        // Poll Plugin
        'wp_ayspoll_answers',
        'wp_ayspoll_categories',
        'wp_ayspoll_polls',
        'wp_ayspoll_reports',
        'wp_ayspoll_settings',
        
        // Elementor
        'wp_e_events',
        
        // FireBox
        'wp_firebox_logs',
        'wp_firebox_logs_details',
        
        // Jetpack
        'wp_jetpack_sync_queue',
        'wp_jetpack_waf_blocklog',
        
        // miniOrange OAuth
        'wp_mo_openid_linked_user',
        
        // WordPress Popular Posts (заменен на собственную систему)
        'wp_popularpostsdata',
        'wp_popularpostssummary',
        'wp_popularpoststransients',
        
        // Post Views (старая система, заменена на post_views Laravel)
        'wp_post_views',
        
        // Radio Player
        'wp_radio_player_players',
        'wp_radio_player_statistics',
        
        // SiteGuard
        'wp_siteguard_history',
        'wp_siteguard_login',
        
        // Yoast SEO (SEO данные мигрированы в post_seo)
        'wp_yoast_indexable',
        'wp_yoast_indexable_hierarchy',
        'wp_yoast_migrations',
        'wp_yoast_primary_term',
        'wp_yoast_seo_links',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        DB::setDefaultConnection('mysql');
        
        if ($dryRun) {
            $this->warn('🔍 РЕЖИМ ТЕСТИРОВАНИЯ - таблицы не будут удалены');
            $this->newLine();
        } else if (!$force) {
            $this->error('⚠️  ВНИМАНИЕ! Эта операция удалит таблицы из базы данных!');
            $this->warn('Для выполнения используйте флаг --force');
            $this->info('Для просмотра списка используйте --dry-run');
            return 1;
        }
        
        $this->info('📋 Анализ WordPress таблиц...');
        $this->newLine();
        
        // Получаем все существующие таблицы
        $existingTables = $this->getExistingTables();
        
        // Фильтруем только те, которые действительно существуют
        $tablesToDelete = array_intersect($this->pluginTables, $existingTables);
        
        if (empty($tablesToDelete)) {
            $this->info('✅ Нет таблиц для удаления');
            return 0;
        }
        
        $this->info('🗑️  Таблицы для удаления (' . count($tablesToDelete) . '):');
        $this->newLine();
        
        $totalSize = 0;
        $totalRecords = 0;
        
        $tableData = [];
        
        foreach ($tablesToDelete as $table) {
            $count = DB::connection('mysql')->table($table)->count();
            $size = $this->getTableSize($table);
            $totalRecords += $count;
            $totalSize += $size;
            
            $tableData[] = [
                'table' => $table,
                'records' => number_format($count),
                'size' => $this->formatBytes($size),
                'plugin' => $this->getPluginName($table),
            ];
        }
        
        // Отображаем таблицу
        $this->table(
            ['Таблица', 'Записей', 'Размер', 'Плагин'],
            $tableData
        );
        
        $this->newLine();
        $this->info("📊 Итого:");
        $this->line("   Таблиц: " . count($tablesToDelete));
        $this->line("   Записей: " . number_format($totalRecords));
        $this->line("   Размер: " . $this->formatBytes($totalSize));
        $this->newLine();
        
        if ($dryRun) {
            $this->warn('Это тестовый режим. Таблицы не будут удалены.');
            $this->info('Для удаления запустите: php artisan db:clean-unused-wp-tables --force');
            return 0;
        }
        
        // Удаление таблиц
        $this->warn('⚠️  Начинаю удаление таблиц...');
        $bar = $this->output->createProgressBar(count($tablesToDelete));
        $bar->start();
        
        $deleted = 0;
        foreach ($tablesToDelete as $table) {
            try {
                Schema::connection('mysql')->dropIfExists($table);
                $deleted++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Ошибка при удалении {$table}: " . $e->getMessage());
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Удаление завершено!");
        $this->line("   Удалено таблиц: {$deleted}");
        $this->line("   Освобождено места: " . $this->formatBytes($totalSize));
        
        return 0;
    }
    
    /**
     * Получить все существующие wp_ таблицы
     */
    protected function getExistingTables(): array
    {
        $tables = DB::connection('mysql')->select('SHOW TABLES');
        $wpTables = [];
        
        foreach ($tables as $t) {
            $tableName = array_values((array)$t)[0];
            if (strpos($tableName, 'wp_') === 0) {
                $wpTables[] = $tableName;
            }
        }
        
        return $wpTables;
    }
    
    /**
     * Получить размер таблицы в байтах
     */
    protected function getTableSize($table): int
    {
        $result = DB::connection('mysql')->select("
            SELECT 
                (data_length + index_length) as size
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = ?
        ", [$table]);
        
        return $result[0]->size ?? 0;
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
    
    /**
     * Определить название плагина по таблице
     */
    protected function getPluginName($table): string
    {
        $plugins = [
            'actionscheduler' => 'Action Scheduler',
            'aioseo' => 'All-in-One SEO',
            'alm' => 'Ajax Load More',
            'ayspoll' => 'Poll Plugin',
            'e_events' => 'Elementor',
            'firebox' => 'FireBox',
            'jetpack' => 'Jetpack',
            'mo_openid' => 'miniOrange OAuth',
            'popularpost' => 'WordPress Popular Posts',
            'post_views' => 'Post Views Counter',
            'radio_player' => 'Radio Player',
            'siteguard' => 'SiteGuard',
            'yoast' => 'Yoast SEO',
        ];
        
        foreach ($plugins as $key => $name) {
            if (strpos($table, $key) !== false) {
                return $name;
            }
        }
        
        return 'Unknown';
    }
}

