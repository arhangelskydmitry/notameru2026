<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitor extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'ip_address',
        'user_agent',
        'visit_date',
        'page_views',
        'first_visit',
        'last_visit',
    ];
    
    protected $casts = [
        'visit_date' => 'date',
        'first_visit' => 'datetime',
        'last_visit' => 'datetime',
    ];
    
    /**
     * Записать визит
     */
    public static function recordVisit($request)
    {
        try {
            $ipAddress = $request->ip();
            $visitDate = now()->toDateString();
            
            $visitor = self::where('ip_address', $ipAddress)
                ->where('visit_date', $visitDate)
                ->first();
            
            if ($visitor) {
                $visitor->page_views++;
                $visitor->last_visit = now();
                $visitor->save();
            } else {
                $visitor = self::create([
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                    'visit_date' => $visitDate,
                    'page_views' => 1,
                    'first_visit' => now(),
                    'last_visit' => now(),
                ]);
            }
            
            return $visitor;
        } catch (\Throwable $e) {
            \Log::warning('Site visitor recording skipped: ' . $e->getMessage());

            return null;
        }
    }
    
    /**
     * Получить уникальных посетителей за период
     */
    public static function getUniqueVisitors($startDate, $endDate)
    {
        return self::whereBetween('visit_date', [$startDate, $endDate])
            ->distinct('ip_address')
            ->count('ip_address');
    }
    
    /**
     * Получить статистику по дням
     */
    public static function getDailyStatistics($startDate, $endDate)
    {
        return self::selectRaw('visit_date as date, COUNT(DISTINCT ip_address) as unique_visitors, SUM(page_views) as total_views')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('visit_date')
            ->orderBy('visit_date')
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
            'today_unique_visitors' => self::whereBetween('visit_date', [$today->toDateString(), $todayEnd->toDateString()])->count(),
            'today_page_views' => self::whereBetween('visit_date', [$today->toDateString(), $todayEnd->toDateString()])->sum('page_views'),
        ];
    }
}

