<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $table = 'not_found_logs';
    
    public $timestamps = false;
    
    protected $fillable = [
        'url',
        'referer',
        'ip_address',
        'user_agent',
        'method',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Логировать 404 ошибку
     */
    public static function logNotFound($request)
    {
        try {
            self::create([
                'url' => $request->fullUrl(),
                'referer' => $request->header('referer'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка логирования 404: ' . $e->getMessage());
        }
    }

    /**
     * Получить статистику по URL
     */
    public static function getUrlStats($limit = 100)
    {
        return self::selectRaw('url, COUNT(*) as count, MAX(created_at) as last_hit')
            ->groupBy('url')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику по рефереру
     */
    public static function getRefererStats($limit = 50)
    {
        return self::selectRaw('referer, COUNT(*) as count')
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->groupBy('referer')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить последние 404
     */
    public static function getRecent($limit = 100)
    {
        return self::orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Очистить старые логи
     */
    public static function cleanOldLogs($days = 90)
    {
        $date = now()->subDays($days);
        return self::where('created_at', '<', $date)->delete();
    }
}
