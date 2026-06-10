<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'type',
        'content',
        'link_url',
        'zone',
        'priority',
        'start_date',
        'end_date',
        'status',
        'show_on_home',
        'show_on_category',
        'show_on_post',
        'show_on_other',
        'target_blank',
        'width',
        'height',
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_blank' => 'boolean',
        'show_on_home' => 'boolean',
        'show_on_category' => 'boolean',
        'show_on_post' => 'boolean',
        'show_on_other' => 'boolean',
        'priority' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];
    
    /**
     * Статистика баннера
     */
    public function stats(): HasMany
    {
        return $this->hasMany(BannerStats::class);
    }
    
    /**
     * Зона баннера
     */
    public function bannerZone(): BelongsTo
    {
        return $this->belongsTo(BannerZone::class, 'zone', 'name');
    }
    
    /**
     * Scope: Активные баннеры
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }
    
    /**
     * Scope: По зоне
     */
    public function scopeInZone($query, $zone)
    {
        return $query->where('zone', $zone);
    }
    
    /**
     * Scope: С приоритетом
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
    
    /**
     * Проверка активности
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        
        $now = Carbon::now();
        
        if ($this->start_date && $this->start_date > $now) {
            return false;
        }
        
        if ($this->end_date && $this->end_date < $now) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка, можно ли показывать на текущем типе страницы
     */
    public function canShowOnCurrentPage(): bool
    {
        $routeName = request()->route() ? request()->route()->getName() : '';
        
        // Определяем тип страницы
        if ($routeName === 'home' || request()->is('/')) {
            return $this->show_on_home;
        }
        
        if ($routeName === 'category' || request()->is('category/*')) {
            return $this->show_on_category;
        }
        
        if ($routeName === 'post' || preg_match('#^[a-z0-9\-]+$#i', request()->path())) {
            return $this->show_on_post;
        }
        
        return $this->show_on_other;
    }
    
    /**
     * Scope: Для текущего типа страницы
     */
    public function scopeForCurrentPage($query)
    {
        $routeName = request()->route() ? request()->route()->getName() : '';
        
        // Определяем тип страницы
        if ($routeName === 'home' || request()->is('/')) {
            return $query->where('show_on_home', true);
        }
        
        if ($routeName === 'category' || request()->is('category/*')) {
            return $query->where('show_on_category', true);
        }
        
        if ($routeName === 'post' || preg_match('#^[a-z0-9\-]+$#i', request()->path())) {
            return $query->where('show_on_post', true);
        }
        
        return $query->where('show_on_other', true);
    }
    
    /**
     * Получить HTML код баннера
     */
    public function getHtml(): string
    {
        if ($this->type === 'image') {
            // Убираем домен из URL, если это полный путь
            $imgSrc = $this->content;
            if (strpos($imgSrc, 'notame.ru') !== false) {
                $imgSrc = preg_replace('#^https?://[^/]+/#', '/', $imgSrc);
            }
            
            $img = '<img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($this->title) . '"';
            
            if ($this->width) {
                $img .= ' width="' . $this->width . '"';
            }
            if ($this->height) {
                $img .= ' height="' . $this->height . '"';
            }
            
            $img .= ' style="max-width: 100%; height: auto;">';
            
            if ($this->link_url) {
                // Используем прямой PHP файл для redirect (обходим проблемы с роутингом)
                $redirectUrl = '/banner-redirect.php?id=' . $this->id;
                $target = $this->target_blank ? ' target="_blank" rel="noopener"' : '';
                return '<a href="' . $redirectUrl . '"' . $target . '>' . $img . '</a>';
            }
            
            return $img;
        }
        
        if ($this->type === 'html') {
            return $this->content;
        }
        
        if ($this->type === 'js') {
            return '<script>' . $this->content . '</script>';
        }
        
        return '';
    }
    
    /**
     * Записать показ
     */
    public function recordImpression(string $ipAddress, ?string $userAgent = null): void
    {
        // Записываем в banner_views
        \DB::table('banner_views')->insert([
            'banner_id' => $this->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'action' => 'impression',
            'created_at' => now(),
        ]);
        
        // Обновляем статистику за сегодня
        $this->updateTodayStats();
    }
    
    /**
     * Записать клик
     */
    public function recordClick(string $ipAddress, ?string $userAgent = null): void
    {
        // Записываем в banner_views
        \DB::table('banner_views')->insert([
            'banner_id' => $this->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'action' => 'click',
            'created_at' => now(),
        ]);
        
        // Обновляем статистику за сегодня
        $this->updateTodayStats();
    }
    
    /**
     * Обновить статистику за сегодня
     */
    protected function updateTodayStats(): void
    {
        $today = now()->toDateString();
        
        // Подсчитываем показы и клики за сегодня
        $impressions = \DB::table('banner_views')
            ->where('banner_id', $this->id)
            ->whereDate('created_at', $today)
            ->where('action', 'impression')
            ->count();
        
        $clicks = \DB::table('banner_views')
            ->where('banner_id', $this->id)
            ->whereDate('created_at', $today)
            ->where('action', 'click')
            ->count();
        
        $uniqueImpressions = \DB::table('banner_views')
            ->where('banner_id', $this->id)
            ->whereDate('created_at', $today)
            ->where('action', 'impression')
            ->distinct('ip_address')
            ->count('ip_address');
        
        $uniqueClicks = \DB::table('banner_views')
            ->where('banner_id', $this->id)
            ->whereDate('created_at', $today)
            ->where('action', 'click')
            ->distinct('ip_address')
            ->count('ip_address');
        
        $ctr = $impressions > 0 ? ($clicks / $impressions * 100) : 0;
        
        // Обновляем или создаем запись статистики
        BannerStats::updateOrCreate(
            [
                'banner_id' => $this->id,
                'date' => $today,
            ],
            [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'unique_impressions' => $uniqueImpressions,
                'unique_clicks' => $uniqueClicks,
                'ctr' => round($ctr, 2),
            ]
        );
    }
    
    /**
     * Получить общую статистику
     */
    public function getTotalStats(): array
    {
        $stats = $this->stats()
            ->selectRaw('
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(unique_impressions) as total_unique_impressions,
                SUM(unique_clicks) as total_unique_clicks
            ')
            ->first();
        
        $totalImpressions = $stats->total_impressions ?? 0;
        $totalClicks = $stats->total_clicks ?? 0;
        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions * 100) : 0;
        
        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'unique_impressions' => $stats->total_unique_impressions ?? 0,
            'unique_clicks' => $stats->total_unique_clicks ?? 0,
            'ctr' => round($ctr, 2),
        ];
    }
}
