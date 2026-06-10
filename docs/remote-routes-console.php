<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Планировщик задач для автоматической генерации новостей
Schedule::command('news:auto-generate')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/news-generation.log'))
    ->emailOutputOnFailure(config('mail.from.address'));

// Автоматические бекапы
if (config('backup.enabled') && config('backup.schedule.enabled')) {
    $backupSchedule = Schedule::command('backup:create --type=full')
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/backup.log'));

    // Настраиваем расписание в зависимости от частоты
    match (config('backup.schedule.frequency')) {
        'daily' => $backupSchedule->dailyAt(config('backup.schedule.time')),
        'weekly' => $backupSchedule->weeklyOn(config('backup.schedule.day_of_week'), config('backup.schedule.time')),
        'monthly' => $backupSchedule->monthlyOn(config('backup.schedule.day_of_month'), config('backup.schedule.time')),
        default => $backupSchedule->dailyAt('03:00'),
    };
}

// Автоматическая очистка старых бекапов
if (config('backup.enabled') && config('backup.cleanup.auto_cleanup_enabled')) {
    Schedule::command('backup:cleanup')
        ->daily()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/backup-cleanup.log'));
}

// Альтернативные варианты расписания (закомментированы):
// ->hourly() - каждый час
// ->everyThirtyMinutes() - каждые 30 минут
// ->daily() - раз в день
// ->dailyAt('02:00') - каждый день в 2:00
// ->cron('0 */2 * * *') - каждые 2 часа (cron формат)
