<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup
                            {--dry-run : Показать что будет удалено без удаления}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить старые бекапы согласно политике ротации';

    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('backup.enabled') || !config('backup.cleanup.auto_cleanup_enabled')) {
            $this->warn('⚠️  Автоматическая очистка отключена в конфигурации.');
            return self::SUCCESS;
        }

        $this->info('🧹 Начало очистки старых бекапов...');
        $this->newLine();

        try {
            $isDryRun = $this->option('dry-run');

            if ($isDryRun) {
                $this->warn('⚠️  Режим DRY-RUN: файлы не будут удалены');
                $this->newLine();
            }

            $deleted = $isDryRun ? 0 : $this->backupService->applyRetentionPolicy();

            if ($deleted > 0) {
                $this->info("✅ Удалено бекапов: {$deleted}");
            } else {
                $this->info('✅ Нет бекапов для удаления');
            }

            $this->newLine();
            
            // Показываем текущую политику ротации
            $retention = config('backup.retention');
            $this->info('📋 Текущая политика ротации:');
            $this->table(
                ['Период', 'Количество'],
                [
                    ['Последние бекапы', $retention['keep_last']],
                    ['Ежедневные', $retention['keep_daily']],
                    ['Еженедельные', $retention['keep_weekly']],
                    ['Ежемесячные', $retention['keep_monthly']],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка очистки бекапов:');
            $this->error($e->getMessage());
            $this->newLine();
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
