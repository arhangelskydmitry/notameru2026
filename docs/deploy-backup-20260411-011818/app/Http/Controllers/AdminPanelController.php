<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminPanelController extends Controller
{
    /**
     * Главная страница админ панели
     */
    public function dashboard()
    {
        $cache = Cache::store('file');
        $safeMetric = function (callable $callback, $fallback, string $label) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                \Log::warning("Admin dashboard metric skipped ({$label}): " . $e->getMessage());

                return $fallback;
            }
        };

        // Кешируем статистику на 5 минут для снижения нагрузки на БД
        $stats = $cache->remember('admin_dashboard_stats', now()->addMinutes(5), function() use ($safeMetric) {
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
                'comments' => $safeMetric(
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
        
        return view('admin.dashboard', compact(
            'stats',
            'visitorStats',
            'topWeekPosts',
            'viewStatistics',
            'dailyStatistics'
        ));
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
     * Список постов
     */
    public function posts(Request $request)
    {
        $query = Post::where('post_type', 'post')->with('author');
        
        // Проверяем права доступа - автор видит только свои статьи
        $userId = session('admin_user_id');
        if (!$userId) {
            return redirect()->route('admin.login')->with('error', 'Необходимо авторизоваться');
        }
        
        $currentUser = \App\Models\WordPress\User::find($userId);
        if (!$currentUser) {
            return redirect()->route('admin.login')->with('error', 'Пользователь не найден');
        }
        
        if ($currentUser->isAuthor()) {
            $query->where('post_author', $currentUser->ID);
        }
        
        // Поиск по названию
        if ($request->has('search') && $request->search) {
            $query->where('post_title', 'LIKE', '%' . $request->search . '%');
        }
        
        // Фильтр по автору (только для админов и редакторов)
        if ($request->has('author') && $request->author && !$currentUser->isAuthor()) {
            $query->where('post_author', $request->author);
        }
        
        // Фильтр по статусу
        if ($request->has('status') && $request->status) {
            $query->where('post_status', $request->status);
        }
        
        $perPage = 30; // По 30 статей за раз для бесконечной подгрузки
        $posts = $query->orderBy('post_date', 'desc')->paginate($perPage);
        
        // Если это AJAX запрос, возвращаем JSON
        if ($request->ajax()) {
            return response()->json([
                'posts' => $posts->items(),
                'has_more' => $posts->hasMorePages(),
                'next_page' => $posts->currentPage() + 1,
                'html' => view('admin.partials.posts-list', ['posts' => $posts->items()])->render()
            ]);
        }
        
        $stats = [
            'total' => Post::where('post_type', 'post')->count(),
            'published' => Post::where('post_type', 'post')->where('post_status', 'publish')->count(),
            'draft' => Post::where('post_type', 'post')->where('post_status', 'draft')->count(),
        ];
        
        // Получаем список авторов для фильтра
        $authors = \App\Models\WordPress\User::whereHas('posts', function($q) {
                $q->where('post_type', 'post');
            })
            ->withCount(['posts as posts_count' => function($q) {
                $q->where('post_type', 'post')->where('post_status', 'publish');
            }])
            ->orderBy('display_name', 'asc')
            ->get();
        
        return view('admin.posts', compact('posts', 'stats', 'authors'));
    }

    /**
     * Форма создания нового поста
     */
    public function createPost()
    {
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        
        $tags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->with('term')
            ->orderBy('term_id')
            ->get();
        
        // Получаем список авторов
        $authors = \App\Models\WordPress\User::whereHas('userRole')->orderBy('display_name', 'asc')->get();
        
        return view('admin.post-create', compact('categories', 'tags', 'authors'));
    }

    /**
     * Сохранение нового поста
     */
    public function storePost(Request $request)
    {
        $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_excerpt' => 'nullable|string',
            'post_status' => 'required|in:publish,draft,pending',
            'post_author' => 'required|integer|exists:wp_users,ID',
            'category_ids' => 'nullable|array',
            'post_date' => 'required|date',
        ]);
        
        // Создаем slug из заголовка
        $slug = \Illuminate\Support\Str::slug($validated['post_title']);
        
        // Проверяем уникальность slug
        $originalSlug = $slug;
        $counter = 1;
        while (Post::where('post_name', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        // Преобразуем дату из локального времени
        $postDate = \Carbon\Carbon::parse($validated['post_date']);
        
        // Создаем пост со статусом draft (будет опубликован позже)
        $post = Post::create([
            'post_author' => $validated['post_author'],
            'post_date' => $postDate,
            'post_date_gmt' => $postDate->copy()->timezone('UTC'),
            'post_content' => $validated['post_content'],
            'post_title' => $validated['post_title'],
            'post_excerpt' => $validated['post_excerpt'] ?? '',
            'post_status' => 'draft', // Всегда создаем как черновик
            'post_name' => $slug,
            'post_modified' => now(),
            'post_modified_gmt' => now(),
            'post_type' => 'post',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => '',
        ]);
        
        if ($post) {
            // Сохраняем обложку если она была загружена
            if ($request->has('featured_image_url') && $request->input('featured_image_url')) {
                $post->setMeta('_thumbnail_url', $request->input('featured_image_url'));
            } else {
                // Если обложка не загружена - берем первое изображение из контента
                $firstImage = $this->extractFirstImageFromContent($validated['post_content']);
                if ($firstImage) {
                    $post->setMeta('_thumbnail_url', $firstImage);
                }
            }
            
            // Генерируем SEO данные через OpenAI
            try {
                $seoGenerator = new \App\Services\SeoGeneratorService();
                $seoData = $seoGenerator->generateSeoData(
                    $validated['post_title'],
                    $validated['post_content']
                );
                
                // Создаем SEO данные с AI-генерацией
                \App\Models\PostSeo::create([
                    'post_id' => $post->ID,
                    'seo_title' => $seoData['seo_title'],
                    'seo_description' => $seoData['seo_description'],
                    'focus_keyword' => $seoData['focus_keyword'],
                    'seo_keywords' => $seoData['seo_keywords'],
                    'og_title' => $seoData['og_title'],
                    'og_description' => $seoData['og_description'],
                    'og_image' => $seoData['og_image'] ?? '',
                    'twitter_title' => $seoData['twitter_title'],
                    'twitter_description' => $seoData['twitter_description'],
                    'twitter_image' => $seoData['twitter_image'] ?? '',
                ]);
                
                $seoGenerated = true;
            } catch (\Exception $e) {
                \Log::error('SEO Generation failed: ' . $e->getMessage());
                
                // Создаем базовые SEO данные в случае ошибки
                \App\Models\PostSeo::create([
                    'post_id' => $post->ID,
                    'seo_title' => $validated['post_title'],
                    'seo_description' => \Illuminate\Support\Str::limit(strip_tags($validated['post_content']), 155),
                ]);
                
                $seoGenerated = false;
            }
            
            // Привязываем категории
            if (!empty($validated['category_ids'])) {
                foreach ($validated['category_ids'] as $categoryId) {
                    \DB::table('wp_term_relationships')->insert([
                        'object_id' => $post->ID,
                        'term_taxonomy_id' => $categoryId,
                    ]);
                }
            }
            
            // Привязываем теги
            if ($request->has('tag_ids') && !empty($request->input('tag_ids'))) {
                foreach ($request->input('tag_ids') as $tagId) {
                    \DB::table('wp_term_relationships')->insert([
                        'object_id' => $post->ID,
                        'term_taxonomy_id' => $tagId,
                    ]);
                }
            }
            
            // Логируем
            admin_log(
                \App\Models\ActivityLog::ACTION_CREATED,
                Post::class,
                $post->ID,
                "Создан пост: {$post->post_title}"
            );
        }
        
        // Возвращаем JSON для AJAX-запроса
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'post_id' => $post->ID,
                'seo_generated' => $seoGenerated ?? false,
                'redirect_url' => route('admin.posts.edit', $post->ID)
            ]);
        }
        
        // Обычный редирект для обычных форм
        $message = 'Статья создана и сохранена в черновиках. ';
        $message .= isset($seoGenerated) && $seoGenerated 
            ? 'SEO-данные сгенерированы автоматически. ' 
            : 'SEO-данные созданы по умолчанию. ';
        $message .= 'Для публикации нажмите "Опубликовать".';
        
        return redirect()->route('admin.posts.edit', $post->ID)->with('success', $message);
    }
    
    /**
     * Увеличение просмотров поста
     */
    public function boostPostViews(Request $request)
    {
        $user = admin_user();
        
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        $validated = $request->validate([
            'max_current_views' => 'required|integer|min:0',
            'min_increment' => 'required|integer|min:1',
            'max_increment' => 'required|integer|min:1|gte:min_increment',
            'limit' => 'required|integer|min:1|max:200',
        ]);
        
        $posts = Post::query()
            ->select(
                'wp_posts.*',
                DB::raw('COALESCE(CAST(views_meta.meta_value AS UNSIGNED), 0) as current_views')
            )
            ->leftJoin('wp_postmeta as views_meta', function($join) {
                $join->on('wp_posts.ID', '=', 'views_meta.post_id')
                    ->where('views_meta.meta_key', 'post_views_count');
            })
            ->where('wp_posts.post_type', 'post')
            ->where('wp_posts.post_status', 'publish')
            ->having('current_views', '<', $validated['max_current_views'])
            ->orderBy('current_views', 'asc')
            ->limit($validated['limit'])
            ->get();
        
        if ($posts->isEmpty()) {
            return back()->with('info', 'Нет статей, подходящих под выбранные условия.');
        }
        
        $report = [];
        
        foreach ($posts as $post) {
            $current = (int) $post->getMeta('post_views_count', 0);
            $increment = random_int($validated['min_increment'], $validated['max_increment']);
            $newValue = $current + $increment;
            
            $post->setMeta('post_views_count', $newValue);
            
            $report[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'previous' => $current,
                'added' => $increment,
                'current' => $newValue,
            ];
        }
        
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            Post::class,
            null,
            sprintf(
                'Подкручены просмотры для %d статей (порог %d, +%d..%d, лимит %d)',
                count($report),
                $validated['max_current_views'],
                $validated['min_increment'],
                $validated['max_increment'],
                $validated['limit']
            )
        );
        
        return back()
            ->with('success', 'Просмотры обновлены для выбранных статей.')
            ->with('views_boost_report', $report);
    }
    

    /**
     * Форма редактирования поста
     */
    public function editPost($id)
    {
        $post = Post::with(['seo', 'categories.term', 'tags.term', 'author'])->findOrFail($id);
        
        // Проверяем права доступа
        $userId = session('admin_user_id');
        if (!$userId) {
            return redirect()->route('admin.login')->with('error', 'Необходимо авторизоваться');
        }
        
        $currentUser = \App\Models\WordPress\User::find($userId);
        if (!$currentUser) {
            return redirect()->route('admin.login')->with('error', 'Пользователь не найден');
        }
        
        if (!$currentUser->canEditPost($post)) {
            abort(403, 'У вас нет прав для редактирования этой статьи');
        }
        
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        
        $tags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->with('term')
            ->orderBy('term_id')
            ->get();
        
        // Получаем список авторов
        $authors = \App\Models\WordPress\User::orderBy('display_name', 'asc')->get();
        
        // Получаем featured image
        $featuredImage = \App\Helpers\ContentHelper::getFeaturedImage($post);
        
        // Получаем первое изображение из контента для подсказки
        $firstImageFromContent = $this->extractFirstImageFromContent($post->post_content);
        
        return view('admin.post-edit', compact('post', 'categories', 'tags', 'authors', 'featuredImage', 'firstImageFromContent'));
    }

    /**
     * Обновление поста
     */
    public function updatePost(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        
        // Проверяем права доступа
        $userId = session('admin_user_id');
        if (!$userId) {
            return redirect()->route('admin.login')->with('error', 'Необходимо авторизоваться');
        }
        
        $currentUser = \App\Models\WordPress\User::find($userId);
        if (!$currentUser) {
            return redirect()->route('admin.login')->with('error', 'Пользователь не найден');
        }
        
        if (!$currentUser->canEditPost($post)) {
            return back()->with('error', 'У вас нет прав для редактирования этой статьи');
        }
        
        $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_excerpt' => 'nullable|string',
            'post_status' => 'required|in:publish,draft,pending',
            'post_author' => 'required|integer|exists:wp_users,ID',
            'category_ids' => 'nullable|array',
            'post_date' => 'required|date',
        ]);
        
        // Преобразуем дату из локального времени
        $postDate = \Carbon\Carbon::parse($validated['post_date']);
        
        $post->update([
            'post_title' => $validated['post_title'],
            'post_content' => $validated['post_content'],
            'post_excerpt' => $validated['post_excerpt'] ?? '',
            'post_status' => $validated['post_status'],
            'post_author' => $validated['post_author'],
            'post_date' => $postDate,
            'post_date_gmt' => $postDate->copy()->timezone('UTC'),
            'post_modified' => now(),
            'post_modified_gmt' => now(),
        ]);
        
        // Обновляем/создаем SEO данные
        $seoData = [
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'seo_keywords' => $request->input('seo_keywords') ? explode(',', $request->input('seo_keywords')) : null,
            'focus_keywords' => $request->input('focus_keyword') ? explode(',', $request->input('focus_keyword')) : null,
            'canonical_url' => $request->input('canonical_url'),
            'robots' => $request->input('meta_robots', 'index, follow'),
            'og_title' => $request->input('og_title'),
            'og_description' => $request->input('og_description'),
            'og_image' => $request->input('og_image'),
            'og_type' => $request->input('og_type', 'article'),
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
        ];
        
        $post->seo()->updateOrCreate(
            ['post_id' => $post->ID],
            $seoData
        );
        
        // Обновляем категории (ручной sync для WordPress структуры)
        $categoryIds = array_map('intval', $validated['category_ids'] ?? []);
        $this->syncTermRelationships($post->ID, $categoryIds, 'category');
        
        // Обновляем теги
        $tagIds = array_map('intval', $request->input('tag_ids', []));
        $this->syncTermRelationships($post->ID, $tagIds, 'post_tag');
        
        // Обновляем обложку (Featured Image)
        if ($request->has('featured_image_url') && $request->input('featured_image_url')) {
            // Сохраняем URL обложки в post meta
            $post->setMeta('_thumbnail_url', $request->input('featured_image_url'));
        } elseif ($request->has('featured_image_id') && !$request->input('featured_image_id')) {
            // Удаляем обложку если поле пустое
            $post->deleteMeta('_thumbnail_id');
            $post->deleteMeta('_thumbnail_url');
        } elseif (!$post->getMeta('_thumbnail_url') && !$post->getMeta('_thumbnail_id')) {
            // Если обложки нет совсем - берем первое изображение из контента
            $firstImage = $this->extractFirstImageFromContent($post->post_content);
            if ($firstImage) {
                $post->setMeta('_thumbnail_url', $firstImage);
            }
        }
        
        // Логируем изменение
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            Post::class,
            $post->ID,
            "Обновлен пост: {$post->post_title}"
        );
        
        return redirect()->route('admin.posts')->with('success', 'Пост успешно обновлен!');
    }
    
    /**
     * Загрузка изображения через TinyMCE
     */
    public function uploadImage(Request $request)
    {
        // Используем новый ImageUploadController для загрузки с тремя размерами
        $imageController = new \App\Http\Controllers\ImageUploadController();
        return $imageController->uploadForTinyMCE($request);
    }

    /**
     * Удаление поста
     */
    public function deletePost($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();
        
        // Логируем удаление
        admin_log(
            \App\Models\ActivityLog::ACTION_DELETED,
            Post::class,
            $id,
            "Удален пост: {$post->post_title}"
        );
        
        return redirect()->route('admin.posts')->with('success', 'Пост удален!');
    }

    /**
     * Список категорий
     */
    public function categories()
    {
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->orderBy('term_id', 'desc')
            ->get();
        
        return view('admin.categories', compact('categories'));
    }

    /**
     * Обновление категории
     */
    public function updateCategory(Request $request, $id)
    {
        $taxonomy = TermTaxonomy::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:200',
            'description' => 'nullable|string',
        ]);
        
        $taxonomy->term->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);
        
        $taxonomy->update([
            'description' => $validated['description'] ?? '',
        ]);
        
        return redirect()->route('admin.categories')->with('success', 'Категория обновлена!');
    }

    /**
     * Управление меню
     */
    public function menu()
    {
        $menuItems = MenuItem::orderBy('order')->get();
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        $pages = Post::where('post_type', 'page')
            ->where('post_status', 'publish')
            ->get();
        
        return view('admin.menu', compact('menuItems', 'categories', 'pages'));
    }

    /**
     * Создание пункта меню
     */
    public function createMenuItem(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'type' => 'required|in:category,page,url',
            'category_id' => 'nullable|exists:wp_term_taxonomy,term_taxonomy_id',
            'page_id' => 'nullable|exists:wp_posts,ID',
            'slug' => 'nullable|string|max:100',
            'order' => 'required|integer',
        ]);
        
        // Обрабатываем is_active (чекбокс)
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Очищаем лишние поля в зависимости от типа
        if ($validated['type'] === 'category') {
            $validated['page_id'] = null;
            if (empty($validated['category_id'])) {
                $validated['category_id'] = null;
            }
        } elseif ($validated['type'] === 'page') {
            $validated['category_id'] = null;
            if (empty($validated['page_id'])) {
                $validated['page_id'] = null;
            }
        } else {
            // type === 'url'
            $validated['category_id'] = null;
            $validated['page_id'] = null;
        }
        
        \Log::info('CreateMenuItem - Validated data:', $validated);
        
        $menuItem = MenuItem::create($validated);
        
        \Log::info('CreateMenuItem - Created item:', $menuItem->toArray());
        
        return redirect()->route('admin.menu')->with('success', 'Пункт меню создан!');
    }

    /**
     * Обновление пункта меню
     */
    public function updateMenuItem(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'type' => 'required|in:category,page,url',
            'category_id' => 'nullable|exists:wp_term_taxonomy,term_taxonomy_id',
            'page_id' => 'nullable|exists:wp_posts,ID',
            'slug' => 'nullable|string|max:100',
            'order' => 'required|integer',
        ]);
        
        // Обрабатываем is_active (чекбокс)
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Очищаем лишние поля в зависимости от типа
        if ($validated['type'] === 'category') {
            $validated['page_id'] = null;
            if (empty($validated['category_id'])) {
                $validated['category_id'] = null;
            }
        } elseif ($validated['type'] === 'page') {
            $validated['category_id'] = null;
            if (empty($validated['page_id'])) {
                $validated['page_id'] = null;
            }
        } else {
            // type === 'url'
            $validated['category_id'] = null;
            $validated['page_id'] = null;
        }
        
        \Log::info('UpdateMenuItem - Validated data:', $validated);
        
        $menuItem->update($validated);
        
        \Log::info('UpdateMenuItem - Updated item:', $menuItem->fresh()->toArray());
        
        return redirect()->route('admin.menu')->with('success', 'Пункт меню обновлен!');
    }

    /**
     * Удаление пункта меню
     */
    public function deleteMenuItem($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();
        
        return redirect()->route('admin.menu')->with('success', 'Пункт меню удален!');
    }

    /**
     * Список страниц
     */
    public function pages()
    {
        $pages = Post::where('post_type', 'page')
            ->orderBy('post_date', 'desc')
            ->paginate(20);
        
        $stats = [
            'total' => Post::where('post_type', 'page')->count(),
            'published' => Post::where('post_type', 'page')->where('post_status', 'publish')->count(),
            'draft' => Post::where('post_type', 'page')->where('post_status', 'draft')->count(),
        ];
        
        return view('admin.pages', compact('pages', 'stats'));
    }

    /**
     * Форма редактирования страницы
     */
    public function editPage($id)
    {
        $page = Post::with(['seo'])->where('post_type', 'page')->findOrFail($id);
        
        // Получаем featured image
        $featuredImage = \App\Helpers\ContentHelper::getFeaturedImage($page);
        
        return view('admin.page-edit', compact('page', 'featuredImage'));
    }

    /**
     * Обновление страницы
     */
    public function updatePage(Request $request, $id)
    {
        $page = Post::where('post_type', 'page')->findOrFail($id);
        
        $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_excerpt' => 'nullable|string',
            'post_status' => 'required|in:publish,draft,pending',
        ]);
        
        $page->update([
            'post_title' => $validated['post_title'],
            'post_content' => $validated['post_content'],
            'post_excerpt' => $validated['post_excerpt'] ?? '',
            'post_status' => $validated['post_status'],
            'post_modified' => now(),
            // SEO fields
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'seo_keywords' => $request->input('seo_keywords'),
            'focus_keyword' => $request->input('focus_keyword'),
            'canonical_url' => $request->input('canonical_url'),
            'meta_robots' => $request->input('meta_robots', 'index, follow'),
            // Open Graph
            'og_title' => $request->input('og_title'),
            'og_description' => $request->input('og_description'),
            'og_image' => $request->input('og_image'),
            'og_type' => $request->input('og_type', 'website'),
            // Twitter Card
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
        ]);
        
        // Логируем изменение
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            Post::class,
            $page->ID,
            "Обновлена страница: {$page->post_title}"
        );
        
        return redirect()->route('admin.pages')->with('success', 'Страница успешно обновлена!');
    }

    /**
     * Удаление страницы
     */
    public function deletePage($id)
    {
        $page = Post::where('post_type', 'page')->findOrFail($id);
        $page->delete();
        
        // Логируем удаление
        admin_log(
            \App\Models\ActivityLog::ACTION_DELETED,
            Post::class,
            $id,
            "Удалена страница: {$page->post_title}"
        );
        
        return redirect()->route('admin.pages')->with('success', 'Страница удалена!');
    }

    /**
     * Список пользователей
     */
    public function users()
    {
        $users = \App\Models\WordPress\User::with(['userRole.role', 'statistics'])
            ->withCount([
                'posts as total_posts' => function($query) {
                    $query->where('post_type', 'post')->where('post_status', 'publish');
                },
                'posts as draft_posts' => function($query) {
                    $query->where('post_type', 'post')->where('post_status', 'draft');
                }
            ])
            ->whereHas('userRole') // Только пользователи с ролями
            ->get()
            ->sortBy(function($user) {
                // Сортировка по иерархии ролей и активности
                $role = $user->getRole();
                
                if (!$role) {
                    return 9999; // В конец, если нет роли
                }
                
                // Порядок: super_admin (0) -> editor (1) -> author (2)
                $roleOrder = [
                    'super_admin' => 0,
                    'editor' => 100,
                    'author' => 200,
                ];
                
                $baseOrder = $roleOrder[$role->name] ?? 300;
                
                // Для авторов сортируем по количеству статей (больше статей = выше)
                if ($role->name === 'author') {
                    return $baseOrder - ($user->total_posts ?? 0);
                }
                
                // Для остальных ролей просто по уровню роли
                return $baseOrder;
            })
            ->take(50); // Ограничение до 50 пользователей
        
        return view('admin.users', compact('users'));
    }

    /**
     * Редактирование пользователя
     */
    public function editUser($id)
    {
        $user = \App\Models\WordPress\User::with(['userRole', 'statistics'])->findOrFail($id);
        $roles = \App\Models\Role::orderBy('level', 'desc')->get();
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        
        return view('admin.user-edit', compact('user', 'roles', 'categories'));
    }

    /**
     * Обновление пользователя
     */
    public function updateUser(Request $request, $id)
    {
        $user = \App\Models\WordPress\User::findOrFail($id);
        
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'position' => 'nullable|string|max:255',
            'allowed_categories' => 'nullable|array',
        ]);
        
        // Обновляем пользователя
        $user->update([
            'display_name' => $validated['display_name'],
            'user_email' => $validated['user_email'],
        ]);
        
        // Обновляем роль
        \App\Models\UserRole::updateOrCreate(
            ['user_id' => $user->ID],
            [
                'role_id' => $validated['role_id'],
                'position' => $validated['position'],
                'allowed_categories' => $validated['allowed_categories'] ?? null,
            ]
        );
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Обновлен пользователь {$user->display_name}"
        );
        
        return redirect()->route('admin.users')->with('success', 'Пользователь успешно обновлен!');
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
     * Профиль пользователя
     */
    public function profile()
    {
        $user = admin_user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        return view('admin.profile', compact('user'));
    }

    /**
     * Обновление профиля
     */
    public function updateProfile(Request $request)
    {
        $user = admin_user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        
        // Обновляем данные
        $user->update([
            'display_name' => $validated['display_name'],
            'user_email' => $validated['user_email'],
        ]);
        
        // Обновляем пароль admin_password
        if ($request->filled('password')) {
            $user->update([
                'admin_password' => \Hash::make($validated['password']),
                'admin_password_plain' => $validated['password'], // Сохраняем для суперадмина
            ]);
        }
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Обновлен профиль"
        );
        
        return redirect()->route('admin.profile')->with('success', 'Профиль успешно обновлен!');
    }
    
    /**
     * Просмотр паролей всех пользователей (только для суперадмина)
     */
    public function viewPasswords()
    {
        $user = admin_user();
        
        // Проверяем права суперадмина
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        // Получаем всех пользователей с ролями (БЕЗ паролей в открытом виде!)
        $users = \App\Models\WordPress\User::whereHas('userRole')
            ->with(['userRole.role'])
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->ID,
                    'name' => $u->display_name,
                    'email' => $u->user_email,
                    'login' => $u->user_login,
                    'role' => $u->getRole()?->display_name,
                    'position' => $u->getPosition(),
                    'has_password' => !empty($u->admin_password),
                    'last_login' => $u->admin_last_login ? $u->admin_last_login->format('d.m.Y H:i') : 'Никогда',
                ];
            });
        
        return view('admin.passwords', compact('users'));
    }
    
    /**
     * Сброс пароля пользователя (только для суперадмина)
     * 
     * БЕЗОПАСНОСТЬ: Пароль показывается ОДИН раз и НЕ сохраняется в открытом виде!
     */
    public function resetPassword(Request $request, $userId)
    {
        $currentUser = admin_user();
        
        // Проверяем права суперадмина
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        $user = \App\Models\WordPress\User::findOrFail($userId);
        
        // Генерируем новый пароль
        $password = $this->generateSecurePassword();
        
        // БЕЗОПАСНО: Сохраняем только ХЕШ пароля, НЕ открытый текст!
        $user->update([
            'admin_password' => \Hash::make($password),
            // Удаляем plain пароль из БД если он был
            'admin_password_plain' => null,
        ]);
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Сброшен пароль пользователя {$user->display_name}"
        );
        
        // Показываем пароль ОДИН раз через flash-сообщение
        // После обновления страницы пароль будет недоступен
        return back()->with('new_password', [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'password' => $password,
        ]);
    }
    
    /**
     * Генерирует безопасный пароль
     */
    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%*-_+=';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Добавляем еще 5 случайных символов
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 5; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
    
    /**
     * Извлечь первое изображение из HTML контента
     */
    protected function extractFirstImageFromContent(string $content): ?string
    {
        // Ищем первый тег <img>
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Рерайт текста статьи с помощью ИИ
     */
    /**
     * Генерация SEO данных через ИИ
     */
    public function generateSeo(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'provider' => 'nullable|string|in:gigachat,openai,chatinfo',
        ]);
        
        try {
            $seoGenerator = new \App\Services\SeoGeneratorService();
            $seoData = $seoGenerator->generateSeoData(
                $validated['title'],
                $validated['excerpt'] ?? null,
                $validated['content'] ?? null,
                $validated['provider'] ?? null
            );
            
            \Log::info('Generated SEO data:', $seoData);
            
            return response()->json([
                'success' => true,
                'data' => $seoData,
                'message' => 'SEO-данные успешно сгенерированы!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('SEO Generation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации SEO: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Страница настроек SEO AI
     */
    public function seoSettings()
    {
        $seoGenerator = new \App\Services\SeoGeneratorService();
        $providers = $seoGenerator->getAvailableProviders();
        
        $settings = [
            'preferred_provider' => \App\Models\Setting::get('seo_ai_provider') ?? 'gigachat',
            'gigachat_client_id' => \App\Models\Setting::get('gigachat_client_id') ?? '',
            'gigachat_client_secret' => \App\Models\Setting::get('gigachat_client_secret') ?? '',
            'gigachat_scope' => \App\Models\Setting::get('gigachat_scope') ?? 'GIGACHAT_API_PERS',
            'openai_configured' => !empty(config('services.openai.api_key', env('OPENAI_API_KEY'))),
        ];
        
        return view('admin.seo-settings', compact('providers', 'settings'));
    }
    
    /**
     * Обновление настроек SEO AI
     */
    public function updateSeoSettings(Request $request)
    {
        $validated = $request->validate([
            'preferred_provider' => 'required|string|in:gigachat,openai,chatinfo',
            'gigachat_client_id' => 'nullable|string|max:500',
            'gigachat_client_secret' => 'nullable|string|max:500',
            'gigachat_scope' => 'nullable|string|in:GIGACHAT_API_PERS,GIGACHAT_API_CORP,GIGACHAT_API_B2B',
        ]);
        
        try {
            \App\Models\Setting::setMultiple([
                'seo_ai_provider' => $validated['preferred_provider'],
                'gigachat_client_id' => $validated['gigachat_client_id'] ?? '',
                'gigachat_client_secret' => $validated['gigachat_client_secret'] ?? '',
                'gigachat_scope' => $validated['gigachat_scope'] ?? 'GIGACHAT_API_PERS',
            ]);
            
            // Очищаем кеш токена GigaChat
            \Illuminate\Support\Facades\Cache::store('file')->forget('gigachat_access_token');
            
            admin_log(
                \App\Models\ActivityLog::ACTION_UPDATED,
                null,
                null,
                'Настройки SEO AI обновлены'
            );
            
            return redirect()->route('admin.seo-settings')
                ->with('success', 'Настройки SEO AI успешно сохранены!');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.seo-settings')
                ->with('error', 'Ошибка сохранения: ' . $e->getMessage());
        }
    }
    
    /**
     * Тестирование провайдера SEO AI
     */
    public function testSeoProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:gigachat,openai,chatinfo',
        ]);
        
        $seoGenerator = new \App\Services\SeoGeneratorService();
        $result = $seoGenerator->testProvider($validated['provider']);
        
        return response()->json($result);
    }
    
    
    public function rewriteContent(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:50',
            'title' => 'nullable|string|max:500',
        ]);
        
        try {
            $seoGenerator = new \App\Services\SeoGeneratorService();
            $provider = \App\Models\Setting::get('seo_ai_provider') ?? 'gigachat';
            
            // Формируем промпт для рерайта
            $title = $validated['title'] ?? '';
            $originalContent = $validated['content'];
            
            // Проверяем наличие HTML разметки
            $hasHtml = strip_tags($originalContent) !== $originalContent;
            
            // Для промпта используем текст без HTML, но в требованиях указываем сохранить HTML
            $contentText = strip_tags($originalContent);
            
            $prompt = "Перепиши следующий текст статьи, сохраняя смысл и основные факты, но изменив формулировки и структуру предложений. ";
            
            if ($title) {
                $prompt .= "Заголовок статьи: {$title}. ";
            }
            
            $prompt .= "Текст для переписывания:\n\n{$contentText}\n\n";
            $prompt .= "Требования:\n";
            $prompt .= "- Сохрани все факты и информацию\n";
            $prompt .= "- Измени формулировки и структуру предложений\n";
            if ($hasHtml) {
                $prompt .= "- ВАЖНО: Оригинальный текст содержал HTML разметку. Верни переписанный текст с HTML тегами (<p>, <strong>, <em>, <ul>, <ol>, <li>, <h2>, <h3> и т.д.)\n";
                $prompt .= "- Сохрани структуру абзацев и списков\n";
            } else {
                $prompt .= "- Верни текст в виде HTML с тегами <p> для абзацев\n";
            }
            $prompt .= "- Сделай текст более читаемым и интересным\n";
            $prompt .= "- Верни только переписанный текст без дополнительных комментариев";
            
            $rewrittenContent = null;
            
            // Пробуем использовать выбранный провайдер
            if ($provider === 'gigachat') {
                $gigaChat = new \App\Services\GigaChatService();
                if ($gigaChat->isConfigured()) {
                    $rewrittenContent = $gigaChat->generateText($prompt, 0.8, 4000);
                }
            } elseif ($provider === 'chatinfo') {
                $chatInfo = new \App\Services\ChatInfoService();
                if ($chatInfo->isConfigured()) {
                    $rewrittenContent = $chatInfo->generateText($prompt, 0.8, 4000);
                }
            }
            
            // Fallback на другие провайдеры
            if (!$rewrittenContent) {
                $gigaChat = new \App\Services\GigaChatService();
                if ($gigaChat->isConfigured()) {
                    $rewrittenContent = $gigaChat->generateText($prompt, 0.8, 4000);
                }
            }
            
            if (!$rewrittenContent) {
                $chatInfo = new \App\Services\ChatInfoService();
                if ($chatInfo->isConfigured()) {
                    $rewrittenContent = $chatInfo->generateText($prompt, 0.8, 4000);
                }
            }
            
            if (!$rewrittenContent) {
                throw new \Exception('Не удалось переписать текст. Проверьте настройки AI провайдеров в разделе "Настройки → SEO".');
            }
            
            \Log::info('Content rewritten', [
                'original_length' => mb_strlen($originalContent),
                'rewritten_length' => mb_strlen($rewrittenContent),
                'provider' => $provider,
            ]);
            
            return response()->json([
                'success' => true,
                'rewritten_content' => $rewrittenContent,
                'message' => 'Текст успешно переписан!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Content rewrite failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка рерайта: ' . $e->getMessage()
            ], 500);
        }
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
     * Войти под другим пользователем (только для суперадмина)
     */
    public function impersonateUser($id)
    {
        $currentUser = admin_user();

        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        $targetUser = User::with('userRole.role')->findOrFail($id);

        if ($targetUser->ID === $currentUser->ID) {
            return back()->with('error', 'Вы уже работаете под своим аккаунтом.');
        }

        // Сохраняем данные текущего пользователя, если режим имперсонации еще не активен
        if (!session()->has('impersonator_id')) {
            session([
                'impersonator_id' => $currentUser->ID,
                'impersonator_name' => $currentUser->display_name,
                'impersonator_role' => $currentUser->getRole()?->display_name,
            ]);
        }

        session([
            'admin_user_id' => $targetUser->ID,
            'admin_user_name' => $targetUser->display_name,
            'admin_user_role' => $targetUser->getRole()?->name,
        ]);

        admin_log(
            'impersonate_start',
            User::class,
            $targetUser->ID,
            "Суперадмин вошёл как {$targetUser->display_name}"
        );

        return redirect()->route('admin.dashboard')
            ->with('success', "Вы вошли как {$targetUser->display_name}. Нажмите «Выйти из режима» чтобы вернуться к своему аккаунту.");
    }

    /**
     * Остановить имперсонацию и вернуться к своему аккаунту
     */
    public function stopImpersonation()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Режим имперсонации не активен.');
        }

        $originalId = session('impersonator_id');
        $originalName = session('impersonator_name');
        $originalRole = session('impersonator_role');

        $targetUser = User::find(session('admin_user_id'));

        // Восстанавливаем сессию оригинального пользователя
        session([
            'admin_user_id' => $originalId,
            'admin_user_name' => $originalName,
            'admin_user_role' => $originalRole,
        ]);

        // Удаляем данные имперсонации
        session()->forget(['impersonator_id', 'impersonator_name', 'impersonator_role']);

        if ($targetUser) {
            admin_log(
                'impersonate_stop',
                User::class,
                $targetUser->ID,
                "Суперадмин вышел из режима имперсонации пользователя {$targetUser->display_name}"
            );
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Вы вернулись к своему аккаунту.');
    }

    /**
     * Синхронизация term relationships для WordPress
     * Корректно работает с категориями и тегами
     */
    protected function syncTermRelationships(int $postId, array $termTaxonomyIds, string $taxonomy): void
    {
        // Получаем текущие term_taxonomy_id для данной таксономии
        $currentIds = \DB::table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', $taxonomy)
            ->pluck('tr.term_taxonomy_id')
            ->toArray();
        
        $newIds = array_filter($termTaxonomyIds);
        
        // Удаляем старые связи для этой таксономии
        $toDelete = array_diff($currentIds, $newIds);
        if (!empty($toDelete)) {
            \DB::table('wp_term_relationships')
                ->where('object_id', $postId)
                ->whereIn('term_taxonomy_id', $toDelete)
                ->delete();
            
            // Уменьшаем счётчик
            \DB::table('wp_term_taxonomy')
                ->whereIn('term_taxonomy_id', $toDelete)
                ->decrement('count');
        }
        
        // Добавляем новые связи
        $toAdd = array_diff($newIds, $currentIds);
        foreach ($toAdd as $termTaxonomyId) {
            \DB::table('wp_term_relationships')->insert([
                'object_id' => $postId,
                'term_taxonomy_id' => $termTaxonomyId,
                'term_order' => 0,
            ]);
            
            // Увеличиваем счётчик
            \DB::table('wp_term_taxonomy')
                ->where('term_taxonomy_id', $termTaxonomyId)
                ->increment('count');
        }
    }

}

