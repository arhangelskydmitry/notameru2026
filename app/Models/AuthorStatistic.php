<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorStatistic extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'total_posts',
        'published_posts',
        'draft_posts',
        'total_views',
        'total_comments',
        'average_rating',
        'this_month_posts',
        'this_week_posts',
        'last_post_date',
    ];

    protected $casts = [
        'total_posts' => 'integer',
        'published_posts' => 'integer',
        'draft_posts' => 'integer',
        'total_views' => 'integer',
        'total_comments' => 'integer',
        'average_rating' => 'decimal:2',
        'this_month_posts' => 'integer',
        'this_week_posts' => 'integer',
        'last_post_date' => 'date',
    ];

    /**
     * Автор
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WordPress\User::class, 'user_id', 'ID');
    }

    /**
     * Обновление статистики для автора
     */
    public static function updateForUser(int $userId): void
    {
        $stats = \App\Models\WordPress\Post::where('post_author', $userId)
            ->where('post_type', 'post')
            ->selectRaw('
                COUNT(*) as total_posts,
                SUM(CASE WHEN post_status = "publish" THEN 1 ELSE 0 END) as published_posts,
                SUM(CASE WHEN post_status = "draft" THEN 1 ELSE 0 END) as draft_posts,
                MAX(post_date) as last_post_date
            ')
            ->first();

        $thisMonth = \App\Models\WordPress\Post::where('post_author', $userId)
            ->where('post_type', 'post')
            ->where('post_date', '>=', now()->startOfMonth())
            ->count();

        $thisWeek = \App\Models\WordPress\Post::where('post_author', $userId)
            ->where('post_type', 'post')
            ->where('post_date', '>=', now()->startOfWeek())
            ->count();

        // Получаем просмотры из wp_postmeta (post_views_count)
        $postIds = \App\Models\WordPress\Post::where('post_author', $userId)
            ->where('post_type', 'post')
            ->pluck('ID');
        
        $totalViews = 0;
        if ($postIds->isNotEmpty()) {
            $totalViews = \DB::table('wp_postmeta')
                ->whereIn('post_id', $postIds)
                ->where('meta_key', 'post_views_count')
                ->sum(\DB::raw('CAST(meta_value AS UNSIGNED)'));
        }

        // Получаем комментарии
        $totalComments = \DB::table('wp_comments')
            ->join('wp_posts', 'wp_comments.comment_post_ID', '=', 'wp_posts.ID')
            ->where('wp_posts.post_author', $userId)
            ->where('wp_comments.comment_approved', '1')
            ->count();

        self::updateOrCreate(
            ['user_id' => $userId],
            [
                'total_posts' => $stats->total_posts ?? 0,
                'published_posts' => $stats->published_posts ?? 0,
                'draft_posts' => $stats->draft_posts ?? 0,
                'this_month_posts' => $thisMonth,
                'this_week_posts' => $thisWeek,
                'total_views' => $totalViews,
                'total_comments' => $totalComments,
                'last_post_date' => $stats->last_post_date,
            ]
        );
    }
}
