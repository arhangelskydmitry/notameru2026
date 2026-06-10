<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use App\Models\PostSeo;
use App\Services\SeoGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SeoAnalysisController extends Controller
{
    protected array $criteria = [
        'title' => ['min_length' => 30, 'max_length' => 60, 'weight' => 20],
        'description' => ['min_length' => 120, 'max_length' => 160, 'weight' => 20],
        'keywords' => ['min_count' => 3, 'max_count' => 10, 'weight' => 15],
        'focus_keyword' => ['weight' => 25],
        'og_data' => ['weight' => 10],
        'twitter_data' => ['weight' => 10],
    ];

    /**
     * Главная страница SEO-анализа
     */
    public function index()
    {
        // Статистика
        $stats = Cache::remember('seo_analysis_stats', 300, function () {
            $totalPosts = Post::where('post_status', 'publish')
                ->where('post_type', 'post')
                ->count();
            
            $withSeo = PostSeo::count();
            
            $goodSeo = PostSeo::where('seo_score', '>=', 70)->count();
            $badSeo = PostSeo::where(function($q) {
                $q->whereNull('seo_score')
                  ->orWhere('seo_score', '<', 70);
            })->count();
            
            return [
                'total_posts' => $totalPosts,
                'with_seo' => $withSeo,
                'good_seo' => $goodSeo,
                'bad_seo' => $badSeo,
                'percent_good' => $totalPosts > 0 ? round($goodSeo / $totalPosts * 100, 1) : 0,
            ];
        });
        
        // Проверяем доступность AI провайдеров
        $providers = [];
        try {
            $seoService = new SeoGeneratorService();
            $providers = $seoService->getAvailableProviders();
        } catch (\Exception $e) {
            Log::warning('Failed to get AI providers: ' . $e->getMessage());
        }
        
        return view('admin.seo-analysis.index', compact('stats', 'providers'));
    }
    
    /**
     * Анализ статей с пагинацией
     */
    public function analyze(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $minScore = $request->get('min_score', 70);
        $filter = $request->get('filter', 'all'); // all, bad, good, empty
        
        $query = Post::where('post_status', 'publish')
            ->where('post_type', 'post')
            ->with('seo')
            ->orderBy('ID', 'desc');
        
        // Фильтрация
        if ($filter === 'bad') {
            $query->whereHas('seo', function($q) use ($minScore) {
                $q->where(function($q2) use ($minScore) {
                    $q2->whereNull('seo_score')
                       ->orWhere('seo_score', '<', $minScore);
                });
            });
        } elseif ($filter === 'good') {
            $query->whereHas('seo', function($q) use ($minScore) {
                $q->where('seo_score', '>=', $minScore);
            });
        } elseif ($filter === 'empty') {
            $query->whereDoesntHave('seo');
        }
        
        $posts = $query->paginate($perPage);
        
        // Анализируем каждый пост
        $analyzed = [];
        foreach ($posts as $post) {
            $analyzed[] = $this->analyzePost($post, $minScore);
        }
        
        if ($request->ajax()) {
            return response()->json([
                'posts' => $analyzed,
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                ],
            ]);
        }
        
        return view('admin.seo-analysis.analyze', compact('posts', 'analyzed', 'minScore', 'filter'));
    }
    
    /**
     * Детальный анализ одной статьи
     */
    public function show(int $postId)
    {
        $post = Post::with('seo')->findOrFail($postId);
        $analysis = $this->analyzePost($post, 70);
        
        return response()->json([
            'post' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'url' => url('/' . $post->post_name),
            ],
            'seo' => $post->seo ? $post->seo->toArray() : null,
            'analysis' => $analysis,
        ]);
    }
    
    /**
     * Генерация нового SEO через AI (предпросмотр)
     */
    public function preview(Request $request)
    {
        $postId = $request->get('post_id');
        $provider = $request->get('provider', 'auto');
        
        $post = Post::findOrFail($postId);
        
        try {
            $seoService = new SeoGeneratorService();
            $newSeo = $seoService->generateSeoData(
                $post->post_title,
                $post->post_excerpt,
                $post->post_content,
                $provider === 'auto' ? null : $provider
            );
            
            // Убеждаемся что все критичные поля заполнены
            $newSeo = $this->ensureAllFieldsFilled($newSeo, $post);
            
            // Получаем текущее SEO для сравнения
            $currentSeo = $post->seo;
            
            return response()->json([
                'success' => true,
                'post_id' => $postId,
                'current' => $currentSeo ? [
                    'seo_title' => $currentSeo->seo_title,
                    'seo_description' => $currentSeo->seo_description,
                    'seo_keywords' => $currentSeo->getKeywordsString(),
                    'focus_keyword' => is_array($currentSeo->focus_keywords) 
                        ? implode(', ', $currentSeo->focus_keywords) 
                        : $currentSeo->focus_keywords,
                    'og_title' => $currentSeo->og_title,
                    'og_description' => $currentSeo->og_description,
                    'twitter_title' => $currentSeo->twitter_title,
                    'twitter_description' => $currentSeo->twitter_description,
                ] : null,
                'new' => $newSeo,
            ]);
        } catch (\Exception $e) {
            Log::error('SEO preview failed for post ' . $postId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ошибка генерации: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Убеждаемся что все необходимые SEO поля заполнены И ОПТИМИЗИРОВАНЫ
     */
    protected function ensureAllFieldsFilled(array $seoData, Post $post): array
    {
        // 1. Определяем Focus Keyword в первую очередь
        if (empty($seoData['focus_keyword'])) {
            $seoData['focus_keyword'] = $this->extractMainKeyword($post->post_title);
        }
        $focusKeyword = is_array($seoData['focus_keyword']) 
            ? ($seoData['focus_keyword'][0] ?? '') 
            : $seoData['focus_keyword'];
        $focusKeyword = mb_strtolower(trim($focusKeyword));
        
        // 2. SEO Title - делаем уникальным и добавляем focus keyword
        if (empty($seoData['seo_title'])) {
            $seoData['seo_title'] = $this->optimizeSeoTitle($post->post_title, $focusKeyword);
        } else {
            // Если title уже есть, проверяем наличие суффикса " | НотаМиру"
            $siteSuffix = ' | НотаМиру';
            if (mb_strpos($seoData['seo_title'], $siteSuffix) === false) {
                // Суффикса нет - добавляем
                $maxLength = 60 - mb_strlen($siteSuffix);
                $currentTitle = $seoData['seo_title'];
                
                // Проверяем наличие focus keyword в title
                if (!empty($focusKeyword) && mb_strpos(mb_strtolower($currentTitle), $focusKeyword) === false) {
                    $currentTitle = $this->addFocusToTitle($currentTitle, $focusKeyword);
                } else {
                    // Focus уже есть, просто обрезаем и добавляем суффикс
                    if (mb_strlen($currentTitle) > $maxLength) {
                        $currentTitle = mb_substr($currentTitle, 0, $maxLength - 3) . '...';
                    }
                    $currentTitle = $currentTitle . $siteSuffix;
                }
                
                $seoData['seo_title'] = $currentTitle;
            } else {
                // Суффикс уже есть - проверяем только focus keyword
                if (!empty($focusKeyword) && mb_strpos(mb_strtolower($seoData['seo_title']), $focusKeyword) === false) {
                    // Убираем суффикс, добавляем focus, возвращаем суффикс
                    $titleWithoutSuffix = str_replace($siteSuffix, '', $seoData['seo_title']);
                    $seoData['seo_title'] = $this->addFocusToTitle($titleWithoutSuffix, $focusKeyword);
                }
            }
        }
        
        // 3. SEO Description - обязательно с focus keyword
        if (empty($seoData['seo_description'])) {
            $excerpt = strip_tags($post->post_excerpt ?: $post->post_content);
            $seoData['seo_description'] = $this->optimizeSeoDescription($excerpt, $focusKeyword);
        } else {
            // Проверяем наличие focus keyword в description
            if (!empty($focusKeyword) && mb_strpos(mb_strtolower($seoData['seo_description']), $focusKeyword) === false) {
                $seoData['seo_description'] = $this->addFocusToDescription($seoData['seo_description'], $focusKeyword);
            }
        }
        
        // 4. SEO Keywords - обязательно минимум 3, включая focus
        if (empty($seoData['seo_keywords']) || (is_array($seoData['seo_keywords']) && count($seoData['seo_keywords']) < 3)) {
            $seoData['seo_keywords'] = $this->extractKeywordsFromTitle($post->post_title, $focusKeyword);
        }
        
        // 5. OG Title - если пусто, берём SEO Title
        if (empty($seoData['og_title'])) {
            $seoData['og_title'] = $seoData['seo_title'];
        }
        
        // 6. OG Description - если пусто, берём SEO Description
        if (empty($seoData['og_description'])) {
            $seoData['og_description'] = $seoData['seo_description'];
        }
        
        // 7. Twitter Title - если пусто, берём SEO Title
        if (empty($seoData['twitter_title'])) {
            $seoData['twitter_title'] = $seoData['seo_title'];
        }
        
        // 8. Twitter Description - если пусто, берём SEO Description
        if (empty($seoData['twitter_description'])) {
            $seoData['twitter_description'] = $seoData['seo_description'];
        }
        
        // 9. Извлекаем первое изображение если есть
        if (empty($seoData['og_image'])) {
            $seoData['og_image'] = $this->extractFirstImage($post->post_content);
        }
        
        if (empty($seoData['twitter_image'])) {
            $seoData['twitter_image'] = $seoData['og_image'];
        }
        
        return $seoData;
    }
    
    /**
     * Оптимизирует SEO Title - делает его отличным от заголовка и добавляет focus keyword
     * Всегда добавляет " | НотаМиру" в конец для уникальности
     */
    protected function optimizeSeoTitle(string $originalTitle, string $focusKeyword): string
    {
        $title = trim($originalTitle);
        $focusLower = mb_strtolower($focusKeyword);
        $siteSuffix = ' | НотаМиру'; // 11 символов
        $maxLength = 60 - mb_strlen($siteSuffix); // Оставляем место для суффикса (60 - 11 = 49)
        
        // Проверяем, есть ли уже focus keyword в заголовке
        $hasFocus = !empty($focusKeyword) && mb_strpos(mb_strtolower($title), $focusLower) !== false;
        
        if (!$hasFocus && !empty($focusKeyword)) {
            // Focus нет - добавляем в начало
            $focusPrefix = ucfirst($focusKeyword) . ': ';
            $maxTitleLen = $maxLength - mb_strlen($focusPrefix);
            
            if (mb_strlen($title) > $maxTitleLen) {
                $title = mb_substr($title, 0, $maxTitleLen - 3) . '...';
            }
            $title = $focusPrefix . $title;
        } else {
            // Focus уже есть или не задан - просто обрезаем если нужно
            if (mb_strlen($title) > $maxLength) {
                $title = mb_substr($title, 0, $maxLength - 3) . '...';
            }
        }
        
        // Добавляем суффикс сайта для уникальности
        return $title . $siteSuffix;
    }
    
    /**
     * Добавляет focus keyword в существующий title
     * Всегда добавляет " | НотаМиру" в конец
     */
    protected function addFocusToTitle(string $title, string $focusKeyword): string
    {
        $title = trim($title);
        $focusLower = mb_strtolower($focusKeyword);
        $siteSuffix = ' | НотаМиру';
        $maxLength = 60 - mb_strlen($siteSuffix);
        
        // Если focus keyword уже есть - просто добавляем суффикс сайта
        if (mb_strpos(mb_strtolower($title), $focusLower) !== false) {
            if (mb_strlen($title) > $maxLength) {
                $title = mb_substr($title, 0, $maxLength - 3) . '...';
            }
            return $title . $siteSuffix;
        }
        
        // Добавляем focus keyword в начало
        $focusPrefix = ucfirst($focusKeyword) . ' – ';
        $maxTitleLen = $maxLength - mb_strlen($focusPrefix);
        
        if (mb_strlen($title) > $maxTitleLen) {
            $title = mb_substr($title, 0, $maxTitleLen - 3) . '...';
        }
        
        return $focusPrefix . $title . $siteSuffix;
    }
    
    /**
     * Оптимизирует SEO Description - добавляет focus keyword
     */
    protected function optimizeSeoDescription(string $text, string $focusKeyword): string
    {
        $desc = trim($text);
        $focusLower = mb_strtolower($focusKeyword);
        
        // Проверяем, есть ли уже focus keyword
        if (!empty($focusKeyword) && mb_strpos(mb_strtolower($desc), $focusLower) === false) {
            // Добавляем focus keyword в начало
            $desc = ucfirst($focusKeyword) . ' — ' . $desc;
        }
        
        // Обрезаем до 160 символов
        if (mb_strlen($desc) > 160) {
            $desc = mb_substr($desc, 0, 157) . '...';
        }
        
        return $desc;
    }
    
    /**
     * Добавляет focus keyword в существующий description
     */
    protected function addFocusToDescription(string $description, string $focusKeyword): string
    {
        $desc = trim($description);
        $focusLower = mb_strtolower($focusKeyword);
        
        // Если focus keyword уже есть - возвращаем как есть
        if (mb_strpos(mb_strtolower($desc), $focusLower) !== false) {
            return $desc;
        }
        
        // Добавляем в начало
        $newDesc = ucfirst($focusKeyword) . ' — ' . $desc;
        
        // Обрезаем если нужно
        if (mb_strlen($newDesc) > 160) {
            // Пробуем обрезать оригинальный текст
            $maxLen = 157 - mb_strlen($focusKeyword);
            if ($maxLen > 20) {
                $desc = mb_substr($desc, 0, $maxLen) . '...';
                $newDesc = ucfirst($focusKeyword) . ' — ' . $desc;
            } else {
                // Если не хватает места, просто обрезаем
                $newDesc = mb_substr($newDesc, 0, 157) . '...';
            }
        }
        
        return $newDesc;
    }
    
    /**
     * Извлекаем главное ключевое слово из заголовка
     */
    protected function extractMainKeyword(string $title): string
    {
        $words = preg_split('/[\s,\.\-:;!?]+/u', mb_strtolower($title));
        $words = array_filter($words, function($word) {
            $stopWords = ['и', 'в', 'на', 'с', 'для', 'от', 'по', 'из', 'что', 'как'];
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return !empty($words) ? array_values($words)[0] : '';
    }
    
    /**
     * Извлекаем ключевые слова из заголовка (включая focus keyword)
     */
    protected function extractKeywordsFromTitle(string $title, string $focusKeyword = ''): string
    {
        $words = preg_split('/[\s,\.\-:;!?]+/u', mb_strtolower($title));
        $words = array_filter($words, function($word) {
            $stopWords = ['и', 'в', 'на', 'с', 'для', 'от', 'по', 'из', 'что', 'как', 'это', 'то', 'не'];
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        $keywords = array_slice(array_values($words), 0, 5);
        
        // Добавляем focus keyword в начало если его нет
        if (!empty($focusKeyword)) {
            $focusLower = mb_strtolower($focusKeyword);
            if (!in_array($focusLower, $keywords)) {
                array_unshift($keywords, $focusLower);
                // Ограничиваем 5 ключевыми словами
                $keywords = array_slice($keywords, 0, 5);
            }
        }
        
        return implode(', ', $keywords);
    }
    
    /**
     * Извлекаем первое изображение из контента
     */
    protected function extractFirstImage(?string $content): ?string
    {
        if (!$content) return null;
        
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Применение нового SEO (после подтверждения)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'post_id' => 'required|integer',
            'seo_data' => 'required|array',
        ]);
        
        $postId = $request->get('post_id');
        $seoData = $request->get('seo_data');
        
        // Логируем полученные данные для отладки
        Log::info('SEO apply request for post ' . $postId, [
            'received_data' => $seoData,
            'user' => auth()->id()
        ]);
        
        $post = Post::findOrFail($postId);
        
        try {
            DB::beginTransaction();
            
            // Подготавливаем данные для сохранения
            $dataToSave = [
                'seo_title' => $seoData['seo_title'] ?? null,
                'seo_description' => $seoData['seo_description'] ?? null,
                'seo_keywords' => $this->parseKeywords($seoData['seo_keywords'] ?? ''),
                'focus_keywords' => $this->parseFocusKeyword($seoData['focus_keyword'] ?? ''),
                'og_title' => $seoData['og_title'] ?? null,
                'og_description' => $seoData['og_description'] ?? null,
                'og_image' => $seoData['og_image'] ?? null,
                'twitter_title' => $seoData['twitter_title'] ?? null,
                'twitter_description' => $seoData['twitter_description'] ?? null,
                'twitter_image' => $seoData['twitter_image'] ?? null,
                'seo_score' => 100,
            ];
            
            // Логируем что будем сохранять
            Log::info('SEO data to save for post ' . $postId, [
                'data' => $dataToSave
            ]);
            
            $seo = PostSeo::updateOrCreate(
                ['post_id' => $postId],
                $dataToSave
            );
            
            // Логируем сохранённые данные
            Log::info('SEO saved for post ' . $postId, [
                'seo_id' => $seo->id,
                'saved_data' => $seo->toArray()
            ]);
            
            DB::commit();
            
            // Очищаем кеш статистики
            Cache::forget('seo_analysis_stats');
            
            return response()->json([
                'success' => true,
                'message' => 'SEO данные успешно применены',
                'seo_id' => $seo->id,
                'saved_data' => $seo->fresh()->toArray(), // Возвращаем сохранённые данные
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SEO apply failed for post ' . $postId . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Ошибка сохранения: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Пакетная генерация (предпросмотр нескольких)
     */
    public function batchPreview(Request $request)
    {
        $postIds = $request->get('post_ids', []);
        $provider = $request->get('provider', 'auto');
        
        if (count($postIds) > 10) {
            return response()->json([
                'success' => false,
                'error' => 'Максимум 10 статей за раз для предпросмотра',
            ], 400);
        }
        
        $results = [];
        $seoService = new SeoGeneratorService();
        
        foreach ($postIds as $postId) {
            try {
                $post = Post::with('seo')->find($postId);
                if (!$post) continue;
                
                $newSeo = $seoService->generateSeoData(
                    $post->post_title,
                    $post->post_excerpt,
                    $post->post_content,
                    $provider === 'auto' ? null : $provider
                );
                
                $results[] = [
                    'post_id' => $postId,
                    'title' => $post->post_title,
                    'success' => true,
                    'current' => $post->seo ? [
                        'seo_title' => $post->seo->seo_title,
                        'seo_description' => $post->seo->seo_description,
                    ] : null,
                    'new' => $newSeo,
                ];
                
                // Задержка между запросами к AI
                usleep(500000); // 0.5 сек
                
            } catch (\Exception $e) {
                $results[] = [
                    'post_id' => $postId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
    
    /**
     * Пакетное применение
     */
    public function batchApply(Request $request)
    {
        $items = $request->get('items', []);
        
        if (count($items) > 50) {
            return response()->json([
                'success' => false,
                'error' => 'Максимум 50 статей за раз',
            ], 400);
        }
        
        $applied = 0;
        $failed = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($items as $item) {
                $postId = $item['post_id'] ?? null;
                $seoData = $item['seo_data'] ?? null;
                
                if (!$postId || !$seoData) {
                    $failed++;
                    continue;
                }
                
                try {
                    PostSeo::updateOrCreate(
                        ['post_id' => $postId],
                        [
                            'seo_title' => $seoData['seo_title'] ?? null,
                            'seo_description' => $seoData['seo_description'] ?? null,
                            'seo_keywords' => $this->parseKeywords($seoData['seo_keywords'] ?? ''),
                            'focus_keywords' => $this->parseFocusKeyword($seoData['focus_keyword'] ?? ''),
                            'og_title' => $seoData['og_title'] ?? null,
                            'og_description' => $seoData['og_description'] ?? null,
                            'og_image' => $seoData['og_image'] ?? null,
                            'twitter_title' => $seoData['twitter_title'] ?? null,
                            'twitter_description' => $seoData['twitter_description'] ?? null,
                            'twitter_image' => $seoData['twitter_image'] ?? null,
                            'seo_score' => 100,
                        ]
                    );
                    $applied++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Batch apply failed for post ' . $postId . ': ' . $e->getMessage());
                }
            }
            
            DB::commit();
            Cache::forget('seo_analysis_stats');
            
            return response()->json([
                'success' => true,
                'applied' => $applied,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Экспорт в SQL
     */
    public function exportSql(Request $request)
    {
        $items = $request->get('items', []);
        
        $sql = "-- SEO Updates Generated from Admin Panel\n";
        $sql .= "-- Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- User: " . (auth()->user()->name ?? 'Unknown') . "\n\n";
        $sql .= "SET NAMES utf8mb4;\n\n";
        
        foreach ($items as $item) {
            $postId = $item['post_id'] ?? null;
            $seoData = $item['seo_data'] ?? null;
            
            if (!$postId || !$seoData) continue;
            
            $existingSeo = PostSeo::where('post_id', $postId)->first();
            
            if ($existingSeo) {
                $sql .= $this->buildUpdateSql($existingSeo->id, $postId, $seoData);
            } else {
                $sql .= $this->buildInsertSql($postId, $seoData);
            }
            $sql .= "\n\n";
        }
        
        return response($sql, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="seo_updates_' . date('Y-m-d_His') . '.sql"',
        ]);
    }
    
    // ==================== PRIVATE METHODS ====================
    
    /**
     * Анализирует один пост
     */
    protected function analyzePost(Post $post, int $minScore): array
    {
        $seo = $post->seo;
        $issues = [];
        $score = 0;
        
        if (!$seo) {
            return [
                'post_id' => $post->ID,
                'title' => $post->post_title,
                'post_date' => $post->post_date,
                'score' => 0,
                'status' => 'empty',
                'issues' => ['❌ SEO данные отсутствуют'],
                'needs_fix' => true,
            ];
        }
        
        $totalWeight = 0;
        $earnedScore = 0;
        
        // 1. SEO Title
        $titleResult = $this->analyzeSeoTitle($seo, $post);
        $issues = array_merge($issues, $titleResult['issues']);
        $totalWeight += $this->criteria['title']['weight'];
        $earnedScore += $titleResult['score'] * $this->criteria['title']['weight'] / 100;
        
        // 2. SEO Description
        $descResult = $this->analyzeSeoDescription($seo, $post);
        $issues = array_merge($issues, $descResult['issues']);
        $totalWeight += $this->criteria['description']['weight'];
        $earnedScore += $descResult['score'] * $this->criteria['description']['weight'] / 100;
        
        // 3. Keywords
        $keywordsResult = $this->analyzeKeywords($seo);
        $issues = array_merge($issues, $keywordsResult['issues']);
        $totalWeight += $this->criteria['keywords']['weight'];
        $earnedScore += $keywordsResult['score'] * $this->criteria['keywords']['weight'] / 100;
        
        // 4. Focus Keyword
        $focusResult = $this->analyzeFocusKeyword($seo, $post);
        $issues = array_merge($issues, $focusResult['issues']);
        $totalWeight += $this->criteria['focus_keyword']['weight'];
        $earnedScore += $focusResult['score'] * $this->criteria['focus_keyword']['weight'] / 100;
        
        // 5. OG
        $ogResult = $this->analyzeOg($seo);
        $issues = array_merge($issues, $ogResult['issues']);
        $totalWeight += $this->criteria['og_data']['weight'];
        $earnedScore += $ogResult['score'] * $this->criteria['og_data']['weight'] / 100;
        
        // 6. Twitter
        $twitterResult = $this->analyzeTwitter($seo);
        $issues = array_merge($issues, $twitterResult['issues']);
        $totalWeight += $this->criteria['twitter_data']['weight'];
        $earnedScore += $twitterResult['score'] * $this->criteria['twitter_data']['weight'] / 100;
        
        $score = $totalWeight > 0 ? round($earnedScore / $totalWeight * 100) : 0;
        
        return [
            'post_id' => $post->ID,
            'title' => $post->post_title,
            'post_date' => $post->post_date,
            'score' => $score,
            'status' => $score >= 90 ? 'excellent' : ($score >= 70 ? 'good' : ($score >= 50 ? 'fair' : 'bad')),
            'issues' => $issues,
            'needs_fix' => $score < $minScore,
            'seo_id' => $seo->id,
        ];
    }
    
    protected function analyzeSeoTitle(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        $title = $seo->seo_title ?? '';
        
        if (empty($title)) {
            return ['issues' => ['⚠️ SEO Title пустой'], 'score' => 0];
        }
        
        $length = mb_strlen($title);
        if ($length < 30) {
            $issues[] = "⚠️ Title короткий ({$length} симв.)";
            $score -= 30;
        }
        if ($length > 60) {
            $issues[] = "⚠️ Title длинный ({$length} симв.)";
            $score -= 20;
        }
        
        // Проверка на дублирование
        $similarity = similar_text(mb_strtolower($title), mb_strtolower($post->post_title)) / max(mb_strlen($title), 1) * 100;
        if ($similarity > 90) {
            $issues[] = '⚠️ Title = заголовок (нужен рерайт)';
            $score -= 25;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function analyzeSeoDescription(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        $desc = $seo->seo_description ?? '';
        
        if (empty($desc)) {
            return ['issues' => ['⚠️ Description пустой'], 'score' => 0];
        }
        
        $length = mb_strlen($desc);
        if ($length < 120) {
            $issues[] = "⚠️ Description короткий ({$length} симв.)";
            $score -= 30;
        }
        if ($length > 160) {
            $issues[] = "⚠️ Description длинный ({$length} симв.)";
            $score -= 20;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function analyzeKeywords(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        $keywords = $seo->seo_keywords;
        
        if (empty($keywords)) {
            return ['issues' => ['⚠️ Keywords пустые'], 'score' => 0];
        }
        
        $count = is_array($keywords) ? count($keywords) : count(explode(',', $keywords));
        if ($count < 3) {
            $issues[] = "⚠️ Мало keywords ({$count})";
            $score -= 40;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function analyzeFocusKeyword(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        $focus = $seo->focus_keywords;
        
        if (empty($focus)) {
            return ['issues' => ['❌ Focus Keyword пуст'], 'score' => 0];
        }
        
        $focusWord = is_array($focus) ? ($focus[0] ?? '') : $focus;
        $focusWord = mb_strtolower(trim($focusWord));
        
        if (mb_strpos(mb_strtolower($seo->seo_title ?? ''), $focusWord) === false) {
            $issues[] = '⚠️ Focus не в Title';
            $score -= 30;
        }
        
        if (mb_strpos(mb_strtolower($seo->seo_description ?? ''), $focusWord) === false) {
            $issues[] = '⚠️ Focus не в Description';
            $score -= 30;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function analyzeOg(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        
        if (empty($seo->og_title)) {
            $issues[] = '⚠️ OG Title пуст';
            $score -= 50;
        }
        if (empty($seo->og_description)) {
            $issues[] = '⚠️ OG Description пуст';
            $score -= 50;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function analyzeTwitter(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        
        if (empty($seo->twitter_title)) {
            $issues[] = '⚠️ Twitter Title пуст';
            $score -= 50;
        }
        if (empty($seo->twitter_description)) {
            $issues[] = '⚠️ Twitter Description пуст';
            $score -= 50;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    protected function parseKeywords($keywords): ?array
    {
        if (is_array($keywords)) return $keywords;
        if (empty($keywords)) return null;
        
        return array_filter(array_map('trim', explode(',', $keywords)));
    }
    
    protected function parseFocusKeyword($focus): ?array
    {
        if (is_array($focus)) return $focus;
        if (empty($focus)) return null;
        
        return [$focus];
    }
    
    protected function buildUpdateSql(int $seoId, int $postId, array $data): string
    {
        $now = now()->format('Y-m-d H:i:s');
        $sql = "-- Post ID: {$postId}\n";
        $sql .= "UPDATE post_seo SET\n";
        $sql .= "    seo_title = " . $this->escape($data['seo_title'] ?? '') . ",\n";
        $sql .= "    seo_description = " . $this->escape($data['seo_description'] ?? '') . ",\n";
        $sql .= "    seo_keywords = " . $this->escape(json_encode($this->parseKeywords($data['seo_keywords'] ?? ''), JSON_UNESCAPED_UNICODE)) . ",\n";
        $sql .= "    focus_keywords = " . $this->escape(json_encode($this->parseFocusKeyword($data['focus_keyword'] ?? ''), JSON_UNESCAPED_UNICODE)) . ",\n";
        $sql .= "    og_title = " . $this->escape($data['og_title'] ?? '') . ",\n";
        $sql .= "    og_description = " . $this->escape($data['og_description'] ?? '') . ",\n";
        $sql .= "    twitter_title = " . $this->escape($data['twitter_title'] ?? '') . ",\n";
        $sql .= "    twitter_description = " . $this->escape($data['twitter_description'] ?? '') . ",\n";
        $sql .= "    seo_score = 100,\n";
        $sql .= "    updated_at = '{$now}'\n";
        $sql .= "WHERE id = {$seoId};";
        return $sql;
    }
    
    protected function buildInsertSql(int $postId, array $data): string
    {
        $now = now()->format('Y-m-d H:i:s');
        $sql = "-- Post ID: {$postId} (NEW)\n";
        $sql .= "INSERT INTO post_seo (post_id, seo_title, seo_description, seo_keywords, focus_keywords, og_title, og_description, twitter_title, twitter_description, seo_score, created_at, updated_at) VALUES (\n";
        $sql .= "    {$postId},\n";
        $sql .= "    " . $this->escape($data['seo_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escape($data['seo_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escape(json_encode($this->parseKeywords($data['seo_keywords'] ?? ''), JSON_UNESCAPED_UNICODE)) . ",\n";
        $sql .= "    " . $this->escape(json_encode($this->parseFocusKeyword($data['focus_keyword'] ?? ''), JSON_UNESCAPED_UNICODE)) . ",\n";
        $sql .= "    " . $this->escape($data['og_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escape($data['og_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escape($data['twitter_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escape($data['twitter_description'] ?? '') . ",\n";
        $sql .= "    100,\n";
        $sql .= "    '{$now}',\n";
        $sql .= "    '{$now}'\n";
        $sql .= ");";
        return $sql;
    }
    
    protected function escape(?string $value): string
    {
        if (empty($value)) return 'NULL';
        return "'" . str_replace(["'", "\\"], ["''", "\\\\"], $value) . "'";
    }
}
