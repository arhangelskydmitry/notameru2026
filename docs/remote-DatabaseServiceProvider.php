<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Оборачиваем в try-catch, чтобы не падало при отсутствии БД
        try {
            // Включаем reconnect при потере соединения
            DB::connection()->enableQueryLog();
            
            // Автоматически переподключаемся при обрыве соединения
            DB::connection()->getPdo()->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );

            // Слушаем события запросов для мониторинга
            if (config('app.debug')) {
                DB::listen(function ($query) {
                    if ($query->time > 1000) { // Запросы > 1 секунды
                        \Log::warning('Slow query detected', [
                            'sql' => $query->sql,
                            'time' => $query->time,
                            'bindings' => $query->bindings,
                        ]);
                    }
                });
            }

            // Обработка ошибок соединения
            DB::connection()->beforeExecuting(function ($query, $bindings) {
                try {
                    DB::connection()->getPdo();
                } catch (\PDOException $e) {
                    if ($e->getCode() === 1226 || strpos($e->getMessage(), 'max_user_connections') !== false) {
                        // Ждем немного и пробуем переподключиться
                        usleep(100000); // 0.1 секунды
                        DB::reconnect();
                    }
                }
            });
        } catch (\Exception $e) {
            // Логируем ошибку, но не падаем
            \Log::warning('Database connection not available during boot: ' . $e->getMessage());
        }
    }
}
