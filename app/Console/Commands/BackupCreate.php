<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create
                            {--type=full : Тип бекапа (full, database, files)}
                            {--force : Игнорировать rate limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать бекап сайта';

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
        if (!config('backup.enabled')) {
            $this->error('❌ Модуль бекапов отключен в конфигурации.');
            return self::FAILURE;
        }

        $type = $this->option('type');
        
        if (!in_array($type, ['full', 'database', 'files'])) {
            $this->error('❌ Неверный тип бекапа. Допустимые: full, database, files');
            return self::FAILURE;
        }

        $this->info("🚀 Начало создания бекапа (тип: {$type})...");
        $this->newLine();

        try {
            $triggeredBy = $this->option('force') ? 'manual_forced' : 'auto';
            $backup = $this->backupService->create($type, $triggeredBy);

            $this->newLine();
            $this->info('✅ Бекап успешно создан!');
            $this->newLine();
            
            $this->table(
                ['Параметр', 'Значение'],
                [
                    ['Имя файла', $backup->filename],
                    ['Тип', $backup->type_name],
                    ['Размер', $backup->human_size],
                    ['Длительность', $backup->duration ?? 'N/A'],
                    ['Дата создания', $backup->created_at->format('d.m.Y H:i:s')],
                    ['Путь', $backup->getFullPath()],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка создания бекапа:');
            $this->error($e->getMessage());
            $this->newLine();
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
