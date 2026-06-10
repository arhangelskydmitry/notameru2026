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
        if (!app()->environment(['local', 'development', 'testing'])) {
            return;
        }

        // Диагностические хуки нужны только локально, а не на production.
        try {
            DB::connection()->enableQueryLog();

            DB::listen(function ($query) {
                if ($query->time > 1000) { // Запросы > 1 секунды
                    \Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        } catch (\Exception $e) {
            \Log::warning('Database connection not available during boot: ' . $e->getMessage());
        }
    }
}
