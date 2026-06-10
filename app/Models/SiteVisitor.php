<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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
        'first_visit_at',
        'last_visit_at',
    ];
    
    protected $casts = [
        'visit_date' => 'date',
        'first_visit' => 'datetime',
        'last_visit' => 'datetime',
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];

    protected static function getSchemaProfile(): array
    {
        return Cache::store('file')->remember('site_visitors_schema_profile_v1', now()->addMinutes(30), function () {
            $hasVisitDate = Schema::hasColumn('site_visitors', 'visit_date');
            $firstVisitColumn = Schema::hasColumn('site_visitors', 'first_visit')
                ? 'first_visit'
                : (Schema::hasColumn('site_visitors', 'first_visit_at') ? 'first_visit_at' : null);
            $lastVisitColumn = Schema::hasColumn('site_visitors', 'last_visit')
                ? 'last_visit'
                : (Schema::hasColumn('site_visitors', 'last_visit_at') ? 'last_visit_at' : null);

            return [
                'has_visit_date' => $hasVisitDate,
                'has_user_agent' => Schema::hasColumn('site_visitors', 'user_agent'),
                'first_visit_column' => $firstVisitColumn,
                'last_visit_column' => $lastVisitColumn,
            ];
        });
    }

    protected static function buildPayload(array $data, array $schema): array
    {
        $payload = [
            'ip_address' => $data['ip_address'] ?? null,
            'page_views' => $data['page_views'] ?? 1,
        ];

        if ($schema['has_user_agent'] ?? false) {
            $payload['user_agent'] = $data['user_agent'] ?? null;
        }

        if ($schema['has_visit_date'] ?? false) {
            $payload['visit_date'] = $data['visit_date'] ?? now()->toDateString();
        }

        if (!empty($schema['first_visit_column'])) {
            $payload[$schema['first_visit_column']] = $data['first_visit'] ?? now();
        }

        if (!empty($schema['last_visit_column'])) {
            $payload[$schema['last_visit_column']] = $data['last_visit'] ?? now();
        }

        return $payload;
    }
    
    /**
     * Записать визит
     */
    public static function recordVisit($request)
    {
        try {
            $ipAddress = $request->ip();
            $visitDate = now()->toDateString();
            $schema = self::getSchemaProfile();

            $query = self::where('ip_address', $ipAddress);

            if ($schema['has_visit_date']) {
                $query->where('visit_date', $visitDate);
            }

            $visitor = $query->first();
            
            if ($visitor) {
                $visitor->page_views++;
                if (!empty($schema['last_visit_column'])) {
                    $visitor->{$schema['last_visit_column']} = now();
                }
                $visitor->save();
            } else {
                $visitor = self::create(self::buildPayload([
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                    'visit_date' => $visitDate,
                    'page_views' => 1,
                    'first_visit' => now(),
                    'last_visit' => now(),
                ], $schema));
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
        try {
            $schema = self::getSchemaProfile();
            $dateColumn = $schema['has_visit_date'] ? 'visit_date' : ($schema['first_visit_column'] ?: $schema['last_visit_column']);

            if (!$dateColumn) {
                return 0;
            }

            return self::whereBetween($dateColumn, [$startDate, $endDate])
                ->distinct('ip_address')
                ->count('ip_address');
        } catch (\Throwable $e) {
            \Log::warning('Unique visitors query skipped: ' . $e->getMessage());

            return 0;
        }
    }
    
    /**
     * Получить статистику по дням
     */
    public static function getDailyStatistics($startDate, $endDate)
    {
        try {
            $schema = self::getSchemaProfile();
            $dateColumn = $schema['has_visit_date'] ? 'visit_date' : ($schema['first_visit_column'] ?: $schema['last_visit_column']);

            if (!$dateColumn) {
                return collect();
            }

            return self::selectRaw("DATE({$dateColumn}) as date, COUNT(DISTINCT ip_address) as unique_visitors, SUM(page_views) as total_views")
                ->whereBetween($dateColumn, [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Throwable $e) {
            \Log::warning('Daily visitor statistics skipped: ' . $e->getMessage());

            return collect();
        }
    }
    
    /**
     * Получить общую статистику
     */
    public static function getTotalStatistics()
    {
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        try {
            $schema = self::getSchemaProfile();
            $todayQueryColumn = $schema['has_visit_date'] ? 'visit_date' : ($schema['last_visit_column'] ?: $schema['first_visit_column']);

            $todayUniqueVisitors = 0;
            $todayPageViews = 0;

            if ($todayQueryColumn) {
                if ($schema['has_visit_date']) {
                    $todayUniqueVisitors = self::whereBetween($todayQueryColumn, [$today->toDateString(), $todayEnd->toDateString()])->count();
                    $todayPageViews = self::whereBetween($todayQueryColumn, [$today->toDateString(), $todayEnd->toDateString()])->sum('page_views');
                } else {
                    $todayUniqueVisitors = self::whereBetween($todayQueryColumn, [$today, $todayEnd])->count();
                    $todayPageViews = self::whereBetween($todayQueryColumn, [$today, $todayEnd])->sum('page_views');
                }
            }

            return [
                'total_unique_visitors' => self::distinct('ip_address')->count('ip_address'),
                'total_page_views' => self::sum('page_views'),
                'today_unique_visitors' => $todayUniqueVisitors,
                'today_page_views' => $todayPageViews,
            ];
        } catch (\Throwable $e) {
            \Log::warning('Total visitor statistics skipped: ' . $e->getMessage());

            return [
                'total_unique_visitors' => 0,
                'total_page_views' => 0,
                'today_unique_visitors' => 0,
                'today_page_views' => 0,
            ];
        }
    }
}

