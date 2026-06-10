<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorDbConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor {action=status : status|kill|optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Мониторинг и управление подключениями к базе данных';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showStatus();
                break;
            case 'kill':
                $this->killIdleConnections();
                break;
            case 'optimize':
                $this->optimizeTables();
                break;
            default:
                $this->error("Неизвестное действие: {$action}");
                $this->info("Доступные действия: status, kill, optimize");
        }

        return Command::SUCCESS;
    }

    /**
     * Показать статус подключений
     */
    protected function showStatus()
    {
        try {
            $this->info('=== Статус подключений к БД ===');
            
            // Получаем информацию о процессах
            $processes = DB::select('SHOW PROCESSLIST');
            
            $this->table(
                ['ID', 'User', 'Host', 'DB', 'Command', 'Time', 'State', 'Info'],
                array_map(function ($process) {
                    return [
                        $process->Id,
                        $process->User,
                        $process->Host,
                        $process->db ?? 'NULL',
                        $process->Command,
                        $process->Time,
                        $process->State ?? '',
                        substr($process->Info ?? '', 0, 50),
                    ];
                }, $processes)
            );

            $this->newLine();
            $this->info("Всего активных подключений: " . count($processes));

            // Получаем лимиты
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            $maxUserConnections = DB::select("SHOW VARIABLES LIKE 'max_user_connections'");
            
            if (!empty($maxConnections)) {
                $this->info("Лимит max_connections: " . $maxConnections[0]->Value);
            }
            if (!empty($maxUserConnections)) {
                $this->info("Лимит max_user_connections: " . $maxUserConnections[0]->Value);
            }

            // Статистика по типам команд
            $commandStats = [];
            foreach ($processes as $process) {
                $cmd = $process->Command;
                $commandStats[$cmd] = ($commandStats[$cmd] ?? 0) + 1;
            }

            $this->newLine();
            $this->info('=== Статистика по типам команд ===');
            foreach ($commandStats as $cmd => $count) {
                $this->line("  {$cmd}: {$count}");
            }

            // Долгие запросы (> 10 секунд)
            $slowQueries = array_filter($processes, fn($p) => $p->Time > 10);
            if (!empty($slowQueries)) {
                $this->newLine();
                $this->warn('⚠️  Обнаружены медленные запросы (> 10 сек):');
                foreach ($slowQueries as $query) {
                    $this->line("  ID {$query->Id}: {$query->Time} сек - " . substr($query->Info ?? '', 0, 80));
                }
            }

        } catch (\Exception $e) {
            $this->error("Ошибка при получении статуса: " . $e->getMessage());
        }
    }

    /**
     * Убить простаивающие подключения
     */
    protected function killIdleConnections()
    {
        try {
            $this->info('=== Поиск простаивающих подключений ===');
            
            $processes = DB::select('SHOW PROCESSLIST');
            $killed = 0;

            foreach ($processes as $process) {
                // Убиваем только Sleep процессы старше 60 секунд
                if ($process->Command === 'Sleep' && $process->Time > 60) {
                    if ($this->confirm("Убить процесс {$process->Id} (Sleep {$process->Time} сек)?", true)) {
                        try {
                            DB::statement("KILL {$process->Id}");
                            $this->info("✓ Процесс {$process->Id} убит");
                            $killed++;
                        } catch (\Exception $e) {
                            $this->warn("✗ Не удалось убить процесс {$process->Id}: " . $e->getMessage());
                        }
                    }
                }
            }

            $this->newLine();
            if ($killed > 0) {
                $this->info("Убито подключений: {$killed}");
            } else {
                $this->info("Простаивающих подключений не найдено");
            }

        } catch (\Exception $e) {
            $this->error("Ошибка при закрытии подключений: " . $e->getMessage());
        }
    }

    /**
     * Оптимизировать таблицы
     */
    protected function optimizeTables()
    {
        try {
            $this->info('=== Оптимизация таблиц ===');
            
            $tables = ['wp_posts', 'wp_postmeta', 'wp_term_relationships', 'wp_terms', 'wp_term_taxonomy'];
            
            foreach ($tables as $table) {
                if ($this->confirm("Оптимизировать таблицу {$table}?", true)) {
                    $this->line("Оптимизация {$table}...");
                    
                    try {
                        DB::statement("OPTIMIZE TABLE {$table}");
                        $this->info("✓ Таблица {$table} оптимизирована");
                    } catch (\Exception $e) {
                        $this->warn("✗ Ошибка оптимизации {$table}: " . $e->getMessage());
                    }
                }
            }

            $this->newLine();
            $this->info("Оптимизация завершена");

        } catch (\Exception $e) {
            $this->error("Ошибка при оптимизации: " . $e->getMessage());
        }
    }
}
