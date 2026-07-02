<?php

namespace App\Http\Controllers;

use App\Models\NotFoundLog;
use App\Models\PostView;
use App\Models\SiteVisitor;
use App\Models\WordPress\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Полноэкранная TV-заставка с дашбордами (для Mac mini в режиме киоска).
 * Доступ по секретному ключу: /wallboard?key=...
 */
class WallboardController extends Controller
{
    private function authorize(Request $request): void
    {
        $key = (string) config('app.wallboard_key', env('WALLBOARD_KEY', ''));
        if ($key === '' || !hash_equals($key, (string) $request->query('key'))) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->authorize($request);
        return view('wallboard', ['key' => $request->query('key')]);
    }

    public function data(Request $request)
    {
        $this->authorize($request);

        $data = Cache::remember('wallboard_data', 60, function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();

            // Один сломанный показатель не должен валить всю заставку
            $safe = function (callable $fn, $fallback = 0) {
                try {
                    return $fn();
                } catch (\Throwable $e) {
                    \Log::warning('Wallboard metric failed: ' . $e->getMessage());
                    return $fallback;
                }
            };

            // Посетители (схема site_visitors: first_visit_at / last_visit_at)
            $visitorsToday = $safe(fn() => (int) SiteVisitor::where('last_visit_at', '>=', $today)->count());
            $visitorsYesterday = $safe(fn() => (int) SiteVisitor::where('last_visit_at', '>=', $yesterday)
                ->where('last_visit_at', '<', $today)
                ->count());
            $pageViewsToday = $safe(fn() => (int) SiteVisitor::where('last_visit_at', '>=', $today)->sum('page_views'));

            // Просмотры статей
            $postViewsToday = $safe(fn() => (int) PostView::where('viewed_at', '>=', $today)->count());
            $postViewsWeek = $safe(fn() => (int) PostView::where('viewed_at', '>=', now()->subWeek())->count());

            // Публикации
            $publishedToday = $safe(fn() => (int) Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->where('post_date', '>=', $today)
                ->count());
            $publishedTotal = $safe(fn() => (int) Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->count());
            $pendingCount = $safe(fn() => (int) Post::where('post_type', 'post')
                ->whereIn('post_status', ['pending', 'draft'])
                ->count());

            // Топ статей за неделю
            $top = $safe(fn() => PostView::getTopPosts('week', 6)->map(function ($item) {
                return [
                    'title' => $item->post?->post_title ?? '—',
                    'views' => (int) $item->view_count,
                ];
            })->values(), collect());

            // Последние публикации
            $latest = $safe(fn() => Post::publiclyVisible()
                ->orderByDesc('post_date')
                ->limit(6)
                ->get(['ID', 'post_title', 'post_date'])
                ->map(fn($p) => [
                    'title' => $p->post_title,
                    'time' => $p->post_date->format('d.m H:i'),
                ]), collect());

            // Проблемы
            $notFoundToday = $safe(fn() => (int) NotFoundLog::where('created_at', '>=', $today)->count());

            // Клики по баннерам сегодня
            $bannerClicksToday = $safe(fn() => (int) DB::table('banner_views')
                ->where('action', 'click')
                ->where('created_at', '>=', $today)
                ->count());

            return [
                'visitors_today' => $visitorsToday,
                'visitors_yesterday' => $visitorsYesterday,
                'page_views_today' => $pageViewsToday,
                'post_views_today' => $postViewsToday,
                'post_views_week' => $postViewsWeek,
                'published_today' => $publishedToday,
                'published_total' => $publishedTotal,
                'pending_count' => $pendingCount,
                'top_week' => $top,
                'latest' => $latest,
                'not_found_today' => $notFoundToday,
                'banner_clicks_today' => $bannerClicksToday,
                'generated_at' => now()->format('H:i:s'),
            ];
        });

        return response()->json($data);
    }
}
