<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Fallback for legacy MoonShine references without needing composer dump
$moonshineProvider = __DIR__ . '/../app/MoonShine/Laravel/Providers/MoonShineServiceProvider.php';
if (file_exists($moonshineProvider)) {
    require_once $moonshineProvider;
}

return Application::configure(dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Глобальный middleware для подавления ошибок "Broken pipe" 
        // Применяется ко ВСЕМ запросам (web, api, console)
        $middleware->use([
            \App\Http\Middleware\SuppressOutputErrors::class,
            \App\Http\Middleware\OptimizeDbConnections::class,
        ]);
        
        // Добавляем HTTPS редирект и заголовки безопасности для web-запросов
        $middleware->web(append: [
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\Log404Errors::class,
        ]);
        
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
        
        // Регистрация алиасов middleware для админки
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'editor' => \App\Http\Middleware\EnsureEditorOrAbove::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Автоматическая генерация новостей каждые 2 часа
        $schedule->command('app:auto-generate-news')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/news-generation.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
