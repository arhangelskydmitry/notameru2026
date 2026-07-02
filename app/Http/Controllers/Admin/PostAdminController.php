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

class PostAdminController extends Controller
{

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
            'future' => Post::where('post_type', 'post')->where('post_status', 'future')->count(),
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
            'post_status' => 'required|in:publish,draft,pending,future',
            'post_author' => 'required|integer|exists:wp_users,ID',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
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
        
        // Автор может публиковать только от своего имени; смена автора — редактор и выше
        $currentUser = admin_user();
        if ($currentUser && !$currentUser->isSuperAdmin() && !$currentUser->isEditor()) {
            $validated['post_author'] = $currentUser->ID;
        }

        // Преобразуем дату из локального времени
        $postDate = \Carbon\Carbon::parse($validated['post_date']);
        
        $resolvedStatus = $this->resolvePostPublicationStatus($validated['post_status'], $postDate);

        $post = Post::create([
            'post_author' => $validated['post_author'],
            'post_date' => $postDate,
            'post_date_gmt' => $postDate->copy()->timezone('UTC'),
            'post_content' => $validated['post_content'],
            'post_title' => $validated['post_title'],
            'post_excerpt' => $validated['post_excerpt'] ?? '',
            'post_status' => $resolvedStatus,
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
        $message = $this->buildPostStatusMessage($resolvedStatus, true) . ' ';
        $message .= isset($seoGenerated) && $seoGenerated
            ? 'SEO-данные сгенерированы автоматически. ' 
            : 'SEO-данные созданы по умолчанию. ';
        
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
            'post_status' => 'required|in:publish,draft,pending,future',
            'post_author' => 'required|integer|exists:wp_users,ID',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'post_date' => 'required|date',
        ]);
        
        // Автор не может переписать материал на другого пользователя
        if (!$currentUser->isSuperAdmin() && !$currentUser->isEditor()) {
            $validated['post_author'] = $post->post_author;
        }

        // Преобразуем дату из локального времени
        $postDate = \Carbon\Carbon::parse($validated['post_date']);
        $resolvedStatus = $this->resolvePostPublicationStatus($validated['post_status'], $postDate);
        
        $post->update([
            'post_title' => $validated['post_title'],
            'post_content' => $validated['post_content'],
            'post_excerpt' => $validated['post_excerpt'] ?? '',
            'post_status' => $resolvedStatus,
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
        
        return redirect()->route('admin.posts')->with('success', $this->buildPostStatusMessage($resolvedStatus, false));
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

        $currentUser = admin_user();
        if (!$currentUser || !$currentUser->canEditPost($post)) {
            abort(403, 'У вас нет прав для удаления этой статьи');
        }

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


    protected function resolvePostPublicationStatus(string $requestedStatus, \Carbon\CarbonInterface $postDate): string
    {
        if ($requestedStatus === 'draft' || $requestedStatus === 'pending') {
            return $requestedStatus;
        }

        if ($requestedStatus === 'future') {
            return $postDate->isFuture() ? 'future' : 'publish';
        }

        if ($requestedStatus === 'publish') {
            return $postDate->isFuture() ? 'future' : 'publish';
        }

        return $requestedStatus;
    }


    protected function buildPostStatusMessage(string $status, bool $created): string
    {
        $prefix = $created ? 'Статья создана.' : 'Статья обновлена.';

        return match ($status) {
            'publish' => $prefix . ' Материал опубликован.',
            'future' => $prefix . ' Материал поставлен в отложенную публикацию.',
            'pending' => $prefix . ' Материал ожидает проверки.',
            default => $prefix . ' Материал сохранен как черновик.',
        };
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
