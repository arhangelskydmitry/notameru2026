<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitor extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'ip_address',
        'page_views',
        'first_visit_at',
        'last_visit_at',
    ];
    
    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];
    
    /**
     * Записать визит
     */
    public static function recordVisit($request)
    {
        $ipAddress = $request->ip();
        
        // Ищем посетителя по IP
        $visitor = self::where('ip_address', $ipAddress)->first();
        
        if ($visitor) {
            // Обновляем существующую запись
            $visitor->page_views++;
            $visitor->last_visit_at = now();
            $visitor->save();
        } else {
            // Создаем новую запись
            $visitor = self::create([
                'ip_address' => $ipAddress,
                'page_views' => 1,
                'first_visit_at' => now(),
                'last_visit_at' => now(),
            ]);
        }
        
        return $visitor;
    }
    
    /**
     * Получить уникальных посетителей за период
     */
    public static function getUniqueVisitors($startDate, $endDate)
    {
        return self::whereBetween('first_visit_at', [$startDate, $endDate])
            ->distinct('ip_address')
            ->count('ip_address');
    }
    
    /**
     * Получить статистику по дням
     */
    public static function getDailyStatistics($startDate, $endDate)
    {
        return self::selectRaw('DATE(first_visit_at) as date, COUNT(DISTINCT ip_address) as unique_visitors, SUM(page_views) as total_views')
            ->whereBetween('first_visit_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
    
    /**
     * Получить общую статистику
     */
    public static function getTotalStatistics()
    {
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        
        return [
            'total_unique_visitors' => self::distinct('ip_address')->count('ip_address'),
            'total_page_views' => self::sum('page_views'),
            'today_unique_visitors' => self::whereBetween('last_visit_at', [$today, $todayEnd])->count(),
            'today_page_views' => self::whereBetween('last_visit_at', [$today, $todayEnd])->sum('page_views'),
        ];
    }
}

