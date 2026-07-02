<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPress\Post;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Главная страница админ панели
     */
    public function dashboard()
    {
        $cache = Cache::store('file');
        $rememberMetric = function ($cacheKey, $callback, $fallback, $label, $ttl = null) use ($cache) {
            $ttl ??= now()->addHours(6);

            try {
                $value = $callback();
                $cache->put($cacheKey, $value, $ttl);

                return $value;
            } catch (\Throwable $e) {
                \Log::warning("Admin dashboard metric skipped ({$label}): " . $e->getMessage());

                return $cache->get($cacheKey, $fallback);
            }
        };

        $safeMetric = function (callable $callback, $fallback, string $label) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                \Log::warning("Admin dashboard metric skipped ({$label}): " . $e->getMessage());

                return $fallback;
            }
        };

        // Кешируем статистику на 5 минут для снижения нагрузки на БД
        $stats = $cache->remember('admin_dashboard_stats', now()->addMinutes(5), function() use ($safeMetric, $rememberMetric) {
            return [
                'posts' => $safeMetric(
                    fn () => Post::where('post_type', 'post')
                        ->where('post_status', 'publish')
                        ->count(),
                    0,
                    'posts_count'
                ),
                'pages' => $safeMetric(
                    fn () => Post::where('post_type', 'page')
                        ->where('post_status', 'publish')
                        ->count(),
                    0,
                    'pages_count'
                ),
                'categories' => $safeMetric(
                    fn () => TermTaxonomy::where('taxonomy', 'category')->count(),
                    0,
                    'categories_count'
                ),
                'comments' => $rememberMetric(
                    'admin_dashboard_comments_count:last_success',
                    fn () => DB::connection('wordpress')->table('wp_comments')->count(),
                    0,
                    'comments_count'
                ),
            ];
        });
        
        // Статистика посетителей (кеш 5 мин)
        $visitorStats = $cache->remember('admin_visitor_stats', now()->addMinutes(5), function() {
            return \App\Models\SiteVisitor::getTotalStatistics();
        });
        
        // Топ статей за неделю (кеш 10 мин)
        $topWeekPosts = $cache->remember('admin_top_week_posts', now()->addMinutes(10), function() {
            return \App\Models\PostView::getTopPosts('week', 10);
        });
        
        // Статистика просмотров за последние 30 дней (кеш 1 час)
        $viewStatistics = $cache->remember('admin_view_statistics_30d', now()->addHour(), function() {
            return \App\Models\PostView::getViewStatistics(
                now()->subDays(30),
                now()
            );
        });
        
        // Статистика посетителей за последние 30 дней (кеш 1 час)
        $dailyStatistics = $cache->remember('admin_daily_statistics_30d', now()->addHour(), function() {
            return \App\Models\SiteVisitor::getDailyStatistics(
                now()->subDays(30)->toDateString(),
                now()->toDateString()
            );
        });
        
        // Последние действия (кеш 2 мин — не грузим в шаблоне)
        $recentActivities = $cache->remember('admin_recent_activities', now()->addMinutes(2), function () {
            return \App\Models\ActivityLog::with('user')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        });

        // Сводка SEO-качества (тяжёлый расчёт — кеш 6 ч, чанками по 150 постов)
        $seoQuality = $cache->remember('admin_seo_quality_counts', now()->addHours(6), function () {
            try {
                return $this->computeSeoQualityCounts();
            } catch (\Throwable $e) {
                \Log::warning('Admin dashboard SEO quality counts failed: ' . $e->getMessage());
                return ['excellent' => 0, 'good' => 0, 'needs_work' => 0, 'total' => 0];
            }
        });
        
        return view('admin.dashboard', compact(
            'stats',
            'visitorStats',
            'topWeekPosts',
            'viewStatistics',
            'dailyStatistics',
            'recentActivities',
            'seoQuality',
        ));
    }

    /**
     * Подсчёт SEO-качества без загрузки всех постов в память разом.
     */
    private function computeSeoQualityCounts(): array
    {
        $seoService = app(\App\Services\SeoService::class);
        $excellent = $good = $needsWork = 0;

        Post::query()
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->select(['ID', 'post_title', 'post_excerpt', 'post_content'])
            ->with('seo')
            ->with(['meta' => fn ($q) => $q->where('meta_key', '_thumbnail_id')])
            ->orderBy('ID')
            ->chunkById(150, function ($posts) use ($seoService, &$excellent, &$good, &$needsWork) {
                foreach ($posts as $post) {
                    $status = $seoService->analyzeSeoScore($post)['status'];
                    if ($status === 'excellent') {
                        $excellent++;
                    } elseif ($status === 'good') {
                        $good++;
                    } else {
                        $needsWork++;
                    }
                }
            }, 'ID');

        return [
            'excellent' => $excellent,
            'good' => $good,
            'needs_work' => $needsWork,
            'total' => $excellent + $good + $needsWork,
        ];
    }

    
    /**
     * Content Quality Dashboard - анализ качества контента
     */
    public function contentQuality(Request $request)
    {
        $query = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['categories.term']);
        
        // Фильтры
        $filter = $request->get('filter', 'all');
        $sortBy = $request->get('sort', 'quality');
        
        // Получаем все посты для анализа
        $allPosts = $query->get();
        
        $seoService = app(\App\Services\SeoService::class);
        $postsAnalysis = [];
        
        foreach ($allPosts as $post) {
            // Анализ изображений
            $thumbnailId = $post->getMeta('_thumbnail_id');
            $hasFeaturedImage = false;
            $isPlaceholder = false;
            
            if ($thumbnailId) {
                $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post);
                $hasFeaturedImage = true;
                $isPlaceholder = (strpos($thumbnail, 'placeholder') !== false);
            }
            
            // Подсчет изображений в контенте
            preg_match_all('/<img[^>]+>/i', $post->post_content, $imagesInContent);
            $contentImagesCount = count($imagesInContent[0]);
            
            // Проверка placeholder в контенте
            $placeholdersInContent = substr_count($post->post_content, 'placeholder.svg');
            
            // SEO анализ
            $seoScore = $seoService->analyzeSeoScore($post);
            
            // Длина контента (без HTML тегов)
            $contentLength = mb_strlen(strip_tags($post->post_content));
            
            // Проверка основных полей
            $issues = [];
            if ($isPlaceholder) {
                $issues[] = 'Отсутствует миниатюра';
            }
            if ($placeholdersInContent > 0) {
                $issues[] = "Placeholder в контенте ($placeholdersInContent)";
            }
            if ($contentLength < 300) {
                $issues[] = 'Слишком короткий текст';
            }
            if (!$post->post_excerpt) {
                $issues[] = 'Нет краткого описания';
            }
            if ($post->categories->isEmpty()) {
                $issues[] = 'Нет категорий';
            }
            
            // Объединяем с SEO issues
            $allIssues = array_merge($issues, $seoScore['issues']);
            
            // Рассчитываем общий балл качества (0-100)
            $qualityScore = 100;
            $qualityScore -= $isPlaceholder ? 20 : 0;
            $qualityScore -= $placeholdersInContent * 5;
            $qualityScore -= ($contentLength < 300) ? 15 : 0;
            $qualityScore -= (!$post->post_excerpt) ? 10 : 0;
            $qualityScore -= ($post->categories->isEmpty()) ? 10 : 0;
            $qualityScore = max(0, min(100, $qualityScore - (100 - $seoScore['score'])));
            
            $analysis = [
                'post' => $post,
                'has_featured_image' => $hasFeaturedImage,
                'is_placeholder' => $isPlaceholder,
                'content_images' => $contentImagesCount,
                'placeholders_in_content' => $placeholdersInContent,
                'seo_score' => $seoScore['score'],
                'seo_status' => $seoScore['status'],
                'content_length' => $contentLength,
                'quality_score' => round($qualityScore),
                'issues' => $allIssues,
                'recommendations' => $seoScore['recommendations'],
            ];
            
            $postsAnalysis[] = $analysis;
        }
        
        // Применяем фильтры
        if ($filter !== 'all') {
            $postsAnalysis = array_filter($postsAnalysis, function($analysis) use ($filter) {
                switch ($filter) {
                    case 'no-image':
                        return !$analysis['has_featured_image'] || $analysis['is_placeholder'];
                    case 'placeholder':
                        return $analysis['placeholders_in_content'] > 0;
                    case 'poor-seo':
                        return $analysis['seo_score'] < 60;
                    case 'low-quality':
                        return $analysis['quality_score'] < 60;
                    case 'no-categories':
                        return $analysis['post']->categories->isEmpty();
                    case 'short-content':
                        return $analysis['content_length'] < 500;
                    default:
                        return true;
                }
            });
        }
        
        // Сортировка
        usort($postsAnalysis, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'quality':
                    return $a['quality_score'] - $b['quality_score'];
                case 'seo':
                    return $a['seo_score'] - $b['seo_score'];
                case 'date':
                    return strtotime($b['post']->post_date) - strtotime($a['post']->post_date);
                case 'issues':
                    return count($b['issues']) - count($a['issues']);
                default:
                    return 0;
            }
        });
        
        // Статистика
        $stats = [
            'total' => count($allPosts),
            'no_image' => collect($postsAnalysis)->filter(fn($a) => !$a['has_featured_image'] || $a['is_placeholder'])->count(),
            'with_placeholders' => collect($postsAnalysis)->filter(fn($a) => $a['placeholders_in_content'] > 0)->count(),
            'poor_seo' => collect($postsAnalysis)->filter(fn($a) => $a['seo_score'] < 60)->count(),
            'low_quality' => collect($postsAnalysis)->filter(fn($a) => $a['quality_score'] < 60)->count(),
            'avg_quality' => round(collect($postsAnalysis)->avg('quality_score')),
            'avg_seo' => round(collect($postsAnalysis)->avg('seo_score')),
        ];
        
        return view('admin.content-quality', [
            'postsAnalysis' => $postsAnalysis,
            'stats' => $stats,
            'currentFilter' => $filter,
            'currentSort' => $sortBy,
        ]);
    }


    /**
     * История действий
     */
    public function activityLog(Request $request)
    {
        $query = \App\Models\ActivityLog::with('user')
            ->orderBy('created_at', 'desc');
        
        // Фильтрация по пользователю
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Фильтрация по действию
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        // Фильтрация по дате
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $logs = $query->paginate(50);
        
        $users = \App\Models\WordPress\User::with('userRole.role')
            ->orderBy('display_name', 'asc')
            ->get();
        
        return view('admin.activity-log', compact('logs', 'users'));
    }


    /**
     * Статистика авторов
     */
    public function authorStatistics()
    {
        // Обновляем статистику для всех авторов при загрузке страницы
        $userIds = \App\Models\UserRole::pluck('user_id');
        foreach ($userIds as $userId) {
            \App\Models\AuthorStatistic::updateForUser($userId);
        }
        
        $authors = \App\Models\WordPress\User::with(['userRole.role', 'statistics'])
            ->whereHas('userRole', function($q) {
                $q->whereHas('role');
            })
            ->get()
            ->sortByDesc(function($user) {
                return $user->statistics?->total_posts ?? 0;
            });
        
        return view('admin.author-statistics', compact('authors'));
    }

    
    /**
     * Обновить статистику авторов (AJAX)
     */
    public function updateAuthorStatistics()
    {
        $userIds = \App\Models\UserRole::pluck('user_id');
        $updated = 0;
        
        foreach ($userIds as $userId) {
            \App\Models\AuthorStatistic::updateForUser($userId);
            $updated++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "Статистика обновлена для {$updated} авторов",
            'updated_count' => $updated
        ]);
    }


    /**
     * Моя статистика (для авторов)
     */
    public function myStatistics()
    {
        $user = admin_user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        // Обновляем статистику
        \App\Models\AuthorStatistic::updateForUser($user->ID);
        
        $statistics = $user->statistics()->first();
        
        // Получаем последние посты
        $recentPosts = $user->posts()
            ->where('post_type', 'post')
            ->orderBy('post_date', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.my-statistics', compact('user', 'statistics', 'recentPosts'));
    }



    /**
     * Страница аналитики
     */
    public function analytics()
    {
        $metrikaService = app(\App\Services\YandexMetrikaService::class);
        
        // Получаем данные за последние 30 дней
        $dateFrom = '30daysAgo';
        $dateTo = 'today';
        
        // Проверяем подключение ПЕРВЫМ делом (с кешированием)
        $isConnected = false;
        try {
            $isConnected = $metrikaService->testConnection();
        } catch (\Exception $e) {
            \Log::warning('Yandex Metrika connection test failed: ' . $e->getMessage());
        }
        
        // Инициализируем пустые данные
        $summary = [
            'visits' => 0,
            'users' => 0,
            'pageviews' => 0,
            'bounceRate' => 0,
            'avgTime' => 0,
        ];
        $visitsData = ['data' => []];
        $popularPages = ['data' => []];
        $trafficSources = ['data' => []];
        $deviceStats = ['data' => []];
        $browserStats = ['data' => [], 'totals' => [0]];
        $geographyStats = ['data' => [], 'totals' => [0]];
        
        // Загружаем данные только если подключено
        if ($isConnected) {
            try {
                // Сводная статистика
                $summary = $metrikaService->getSummaryStats($dateFrom, $dateTo);
                
                // Статистика по дням для графика
                $visitsData = $metrikaService->getVisitsStatistics($dateFrom, $dateTo);
                
                // Популярные страницы
                $popularPages = $metrikaService->getPopularPages($dateFrom, $dateTo, 10);
                
                // Источники трафика
                $trafficSources = $metrikaService->getTrafficSources($dateFrom, $dateTo);
                
                // Статистика по устройствам
                $deviceStats = $metrikaService->getDeviceStats($dateFrom, $dateTo);
                
                // Браузеры
                $browserStats = $metrikaService->getBrowserStats($dateFrom, $dateTo);
                
                // География
                $geographyStats = $metrikaService->getGeographyStats($dateFrom, $dateTo);
                
            } catch (\Exception $e) {
                \Log::error('Failed to load Yandex Metrika analytics: ' . $e->getMessage());
                // Данные остаются пустыми, установленными выше
            }
        }
        
        $webmasterData = $this->buildWebmasterPanelData();

        return view('admin.analytics', compact(
            'summary',
            'visitsData',
            'popularPages',
            'trafficSources',
            'deviceStats',
            'browserStats',
            'geographyStats',
            'isConnected',
            'webmasterData'
        ));
    }



    /**
     * Страница настроек Яндекс сервисов
     */
    public function yandexServices(Request $request)
    {
        // Очистка кеша, если запрошено
        if ($request->has('clear_cache')) {
            \Illuminate\Support\Facades\Cache::flush();
            return redirect()->route('admin.yandex')->with('success', 'Кеш очищен! Обновите страницу или используйте "Тест API" для проверки.');
        }
        
        // Читаем настройки из БД, если нет - из config
        $settings = [
            'metrika_id' => \App\Models\Setting::get('yandex_metrika_id') ?? config('services.yandex.metrika_id'),
            'metrika_token' => \App\Models\Setting::get('yandex_metrika_token') ?? config('services.yandex.metrika_token'),
            'webmaster_verification' => \App\Models\Setting::get('yandex_webmaster_verification') ?? config('services.yandex.webmaster_verification'),
            'webmaster_token' => \App\Models\Setting::get('yandex_webmaster_token') ?? config('services.yandex.webmaster_token'),
            'webmaster_host_id' => \App\Models\Setting::get('yandex_webmaster_host_id') ?? config('services.yandex.webmaster_host_id'),
        ];

        // Упрощенная проверка доступности настроек
        $metrikaConfigured = !empty($settings['metrika_id']) && !empty($settings['metrika_token']);
        $webmasterConfigured = !empty($settings['webmaster_token']) && !empty($settings['webmaster_host_id']);

        $webmasterConnected = false;
        $webmasterHostInfo = [];
        $webmasterIndexingStats = [];
        $webmasterHosts = [];
        $webmasterPopularQueries = [];
        $webmasterError = null;

        if ($webmasterConfigured) {
            try {
                $webmasterService = app(\App\Services\YandexWebmasterService::class);
                $webmasterConnected = $webmasterService->testConnection();

                if ($webmasterConnected) {
                    $webmasterHosts = $webmasterService->getHostsList();
                    $webmasterHostInfo = $webmasterService->getHostInfo();
                    $webmasterIndexingStats = $webmasterService->getIndexingStats();
                    $webmasterPopularQueries = $webmasterService->getPopularQueriesDetailed('30daysAgo', 'today', 10);
                } else {
                    $webmasterError = 'Не удалось подключиться к API Яндекс Вебмастер. Проверьте права токена.';
                }
            } catch (\Exception $e) {
                $webmasterError = $e->getMessage();
                \Log::error('Yandex Webmaster connection error: ' . $e->getMessage());
            }
        }

        return view('admin.yandex-services', compact(
            'settings',
            'metrikaConfigured',
            'webmasterConfigured',
            'webmasterConnected',
            'webmasterHostInfo',
            'webmasterIndexingStats',
            'webmasterHosts',
            'webmasterPopularQueries',
            'webmasterError'
        ));
    }

    
    /**
     * Обновление настроек Яндекс сервисов
     */
    public function updateYandexServices(Request $request)
    {
        $validated = $request->validate([
            'metrika_id' => 'nullable|string|max:20',
            'metrika_token' => 'nullable|string|max:500',
            'webmaster_verification' => 'nullable|string|max:500',
            'webmaster_token' => 'nullable|string|max:500',
            'webmaster_host_id' => 'nullable|string|max:500',
        ]);

        try {
            // Сохраняем настройки в БД
            \App\Models\Setting::setMultiple([
                'yandex_metrika_id' => $validated['metrika_id'] ?? '',
                'yandex_metrika_token' => $validated['metrika_token'] ?? '',
                'yandex_webmaster_verification' => $validated['webmaster_verification'] ?? '',
                'yandex_webmaster_token' => $validated['webmaster_token'] ?? '',
                'yandex_webmaster_host_id' => $validated['webmaster_host_id'] ?? '',
            ]);
            
            // Очищаем кеш
            \Illuminate\Support\Facades\Cache::forget('yandex_metrika_counter_info');
            \Illuminate\Support\Facades\Cache::forget('yandex_webmaster_hosts');
            \Illuminate\Support\Facades\Cache::forget('yandex_webmaster_host_info');
            \Illuminate\Support\Facades\Cache::forget('yandex_webmaster_indexing_stats');
            
        } catch (\Exception $e) {
            \Log::error('Failed to update Yandex settings: ' . $e->getMessage());
            return redirect()->route('admin.yandex')
                ->with('error', 'Не удалось сохранить настройки. Ошибка: ' . $e->getMessage());
        }

        // Логируем действие
        try {
            \App\Models\ActivityLog::log(
                'yandex_settings_updated',
                null,
                null,
                'Настройки Яндекс сервисов обновлены'
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to log yandex settings update: ' . $e->getMessage());
        }

        return redirect()->route('admin.yandex')
            ->with('success', 'Настройки Яндекс сервисов успешно обновлены!');
    }

    
    /**
     * Тестирование подключения к Яндекс API
     */
    public function testYandexApi()
    {
        // Инициализируем результаты
        $metrikaConnected = false;
        $webmasterConnected = false;
        $metrikaData = null;
        $webmasterData = null;
        $metrikaError = null;
        $webmasterError = null;
        
        // Читаем настройки из БД
        $metrikaId = \App\Models\Setting::get('yandex_metrika_id');
        $metrikaToken = \App\Models\Setting::get('yandex_metrika_token');
        $webmasterToken = \App\Models\Setting::get('yandex_webmaster_token');
        
        // Тестируем Метрику
        if (!empty($metrikaToken) && !empty($metrikaId)) {
            try {
                $metrikaService = app(\App\Services\YandexMetrikaService::class);
                $metrikaConnected = $metrikaService->testConnection();
                
                if ($metrikaConnected) {
                    $metrikaData = $metrikaService->getCounterInfo();
                } else {
                    $metrikaError = 'Не удалось подключиться к API Яндекс Метрики';
                }
            } catch (\Exception $e) {
                $metrikaError = 'Ошибка при подключении к API: ' . $e->getMessage();
                \Log::error('Yandex Metrika test failed', ['error' => $e->getMessage()]);
            }
        } else {
            $metrikaError = 'Токен или ID счетчика не настроены';
        }
        
        // Тестируем Вебмастер
        if (!empty($webmasterToken)) {
            try {
                $webmasterService = app(\App\Services\YandexWebmasterService::class);
                $webmasterConnected = $webmasterService->testConnection();
                
                if ($webmasterConnected) {
                    $webmasterData = $webmasterService->getHostsList();
                } else {
                    $webmasterError = 'Не удалось подключиться к API Яндекс Вебмастер (возможно, нет прав доступа)';
                }
            } catch (\Exception $e) {
                $webmasterError = 'Ошибка при подключении к API: ' . $e->getMessage();
                \Log::error('Yandex Webmaster test failed', ['error' => $e->getMessage()]);
            }
        } else {
            $webmasterError = 'Токен не настроен';
        }

        $results = [
            'metrika' => [
                'configured' => !empty($metrikaToken) && !empty($metrikaId),
                'connected' => $metrikaConnected,
                'data' => $metrikaData,
                'error' => $metrikaError,
            ],
            'webmaster' => [
                'configured' => !empty($webmasterToken),
                'connected' => $webmasterConnected,
                'data' => $webmasterData,
                'error' => $webmasterError,
            ],
        ];

        return view('admin.yandex-api-test', compact('results'));
    }

    
    /**
     * Построение данных для панели Вебмастера
     */
    protected function buildWebmasterPanelData()
    {
        $data = [
            'configured' => false,
            'connected' => false,
            'hostInfo' => [],
            'indexingStats' => [],
            'popularQueries' => [],
            'lowCtrQueries' => [],
            'highCtrQueries' => [],
            'hosts' => [],
            'issues' => [],
            'recommendations' => [],
            'error' => null,
        ];
        
        $token = \App\Models\Setting::get('yandex_webmaster_token');
        $hostId = \App\Models\Setting::get('yandex_webmaster_host_id');
        
        $data['configured'] = !empty($token) && !empty($hostId);
        
        if (!$data['configured']) {
            return $data;
        }
        
        try {
            $service = app(\App\Services\YandexWebmasterService::class);
            $hostsList = $service->getHostsList();
            $data['connected'] = !empty($hostsList);
            
            if (!$data['connected']) {
                $data['error'] = 'Не удалось подключиться к API Яндекс Вебмастер. Проверьте права токена.';
                return $data;
            }
            
            $data['hosts'] = $hostsList['hosts'] ?? [];
            $data['hostInfo'] = $service->getHostInfo();
            $data['indexingStats'] = $service->getIndexingStats();
            $data['popularQueries'] = $service->getPopularQueriesDetailed('30daysAgo', 'today', 12);
            $data['lowCtrQueries'] = collect($data['popularQueries'])
                ->filter(fn ($item) => ($item['shows'] ?? 0) >= 50 && ($item['ctr'] ?? 0) < 1)
                ->take(5)
                ->values()
                ->toArray();
            $data['highCtrQueries'] = collect($data['popularQueries'])
                ->filter(fn ($item) => ($item['ctr'] ?? 0) >= 5)
                ->take(5)
                ->values()
                ->toArray();
            $data['issues'] = array_keys($data['indexingStats']['site_problems'] ?? []);
            $data['recommendations'] = $this->makeWebmasterRecommendations(
                $data['indexingStats'],
                $data['hostInfo'],
                $data['popularQueries']
            );
            
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            \Log::error('Analytics error: ' . $e->getMessage());
        }
        
        return $data;
    }

    
    /**
     * Рекомендации для вебмастера
     */
    protected function makeWebmasterRecommendations(array $indexingStats, array $hostInfo, array $popularQueries): array
    {
        $recs = [];
        
        $sqi = $indexingStats['sqi'] ?? null;
        $searchable = (int) ($indexingStats['searchable_pages_count'] ?? 0);
        $excluded = (int) ($indexingStats['excluded_pages_count'] ?? 0);
        
        if ($sqi !== null && $sqi < 20) {
            $recs[] = 'Повышайте SQI: улучшайте скорость сайта, исправляйте ошибки микроразметки и контента.';
        }
        
        if ($excluded > $searchable * 0.1) {
            $recs[] = sprintf('Много исключенных страниц (%d из %d). Проверьте robots.txt и мета-теги noindex.', $excluded, $searchable + $excluded);
        }
        
        if (empty($popularQueries)) {
            $recs[] = 'Нет данных о популярных запросах. Улучшайте SEO-оптимизацию контента.';
        }
        
        return $recs;
    }
}
