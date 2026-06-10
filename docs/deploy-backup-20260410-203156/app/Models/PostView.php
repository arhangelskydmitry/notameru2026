<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\WordPress\Post;

class PostView extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'post_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];
    
    protected $casts = [
        'viewed_at' => 'datetime',
    ];
    
    /**
     * Пост
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'ID');
    }
    
    /**
     * Записать просмотр поста
     */
    public static function recordView(Post $post, $request)
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        
        // Проверяем, не был ли этот пост просмотрен с этого IP за последний час
        $recentView = self::where('post_id', $post->ID)
            ->where('ip_address', $ipAddress)
            ->where('viewed_at', '>', now()->subHour())
            ->exists();
        
        if (!$recentView) {
            self::create([
                'post_id' => $post->ID,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);
            
            // Обновляем счетчик в meta
            $currentViews = (int) $post->getMeta('post_views_count', 0);
            $post->setMeta('post_views_count', $currentViews + 1);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Получить топ постов за период
     */
    public static function getTopPosts($period = 'week', $limit = 10)
    {
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };
        
        // Для года используем wp_postmeta, так как в post_views ограничение 1000 записей
        if ($period === 'year') {
            return \DB::table('wp_postmeta as pm')
                ->join('wp_posts as p', 'pm.post_id', '=', 'p.ID')
                ->where('pm.meta_key', 'post_views_count')
                ->where('p.post_type', 'post')
                ->where('p.post_status', 'publish')
                ->select('p.ID as post_id', \DB::raw('CAST(pm.meta_value AS UNSIGNED) as view_count'))
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($item) {
                    $item->post = Post::find($item->post_id);
                    return $item;
                });
        }
        
        // Для недели считаем записи в post_views
        return self::select('post_id', \DB::raw('COUNT(*) as view_count'))
            ->where('viewed_at', '>=', $startDate)
            ->groupBy('post_id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->with('post')
            ->get();
    }
    
    /**
     * Получить статистику просмотров за период
     */
    public static function getViewStatistics($startDate, $endDate)
    {
        return self::selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}

