<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    /**
     * Отображение списка тегов
     */
    public function index(Request $request)
    {
        $query = TermTaxonomy::tags()->with('term');

        // Поиск
        if ($search = $request->get('search')) {
            $query->whereHas('term', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Фильтр по использованию
        $filter = $request->get('filter');
        if ($filter === 'unused') {
            $query->where('count', 0);
        } elseif ($filter === 'single') {
            $query->where('count', 1);
        } elseif ($filter === 'active') {
            $query->where('count', '>', 0);
        }

        // Сортировка
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        if ($sort === 'name') {
            $query->join('wp_terms', 'wp_term_taxonomy.term_id', '=', 'wp_terms.term_id')
                  ->orderBy('wp_terms.name', $direction)
                  ->select('wp_term_taxonomy.*');
        } elseif ($sort === 'count') {
            $query->orderBy('count', $direction);
        } elseif ($sort === 'id') {
            $query->orderBy('term_taxonomy_id', $direction);
        }

        $tags = $query->paginate(50)->withQueryString();

        // Статистика
        $stats = [
            'total' => TermTaxonomy::tags()->count(),
            'active' => TermTaxonomy::tags()->where('count', '>', 0)->count(),
            'unused' => TermTaxonomy::tags()->where('count', 0)->count(),
            'single' => TermTaxonomy::tags()->where('count', 1)->count(),
        ];

        return view('admin.tags.index', compact('tags', 'stats'));
    }

    /**
     * Форма создания тега
     */
    public function create()
    {
        return view('admin.tags.create');
    }

    /**
     * Сохранение нового тега
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:wp_terms,slug',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Генерация slug если не указан
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            // Создаем термин
            $term = Term::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
            ]);

            // Создаем таксономию
            TermTaxonomy::create([
                'term_id' => $term->term_id,
                'taxonomy' => 'post_tag',
                'description' => $validated['description'] ?? '',
                'parent' => 0,
                'count' => 0,
            ]);

            DB::commit();

            return redirect()->route('admin.tags.index')
                ->with('success', 'Тег успешно создан!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка создания тега: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ошибка создания тега: ' . $e->getMessage());
        }
    }

    /**
     * Форма редактирования тега
     */
    public function edit($id)
    {
        $taxonomy = TermTaxonomy::tags()->with('term')->findOrFail($id);
        
        // Получаем список статей с этим тегом
        $posts = $taxonomy->posts()
            ->select('ID', 'post_title', 'post_date', 'post_status')
            ->orderBy('post_date', 'desc')
            ->limit(100)
            ->get();

        return view('admin.tags.edit', compact('taxonomy', 'posts'));
    }

    /**
     * Обновление тега
     */
    public function update(Request $request, $id)
    {
        $taxonomy = TermTaxonomy::tags()->with('term')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:200|unique:wp_terms,slug,' . $taxonomy->term_id . ',term_id',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Обновляем термин
            $taxonomy->term->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
            ]);

            // Обновляем таксономию
            $taxonomy->update([
                'description' => $validated['description'] ?? '',
            ]);

            DB::commit();

            return redirect()->route('admin.tags.index')
                ->with('success', 'Тег успешно обновлен!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обновления тега: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ошибка обновления тега: ' . $e->getMessage());
        }
    }

    /**
     * Удаление тега
     */
    public function destroy($id)
    {
        $taxonomy = TermTaxonomy::tags()->with('term')->findOrFail($id);

        try {
            DB::beginTransaction();

            // Удаляем связи с постами
            DB::table('wp_term_relationships')
                ->where('term_taxonomy_id', $taxonomy->term_taxonomy_id)
                ->delete();

            // Удаляем таксономию
            $taxonomy->delete();

            // Удаляем термин (если он не используется в других таксономиях)
            $otherTaxonomies = TermTaxonomy::where('term_id', $taxonomy->term_id)->count();
            if ($otherTaxonomies == 0) {
                $taxonomy->term->delete();
            }

            DB::commit();

            return redirect()->route('admin.tags.index')
                ->with('success', 'Тег успешно удален!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка удаления тега: ' . $e->getMessage());
            return back()->with('error', 'Ошибка удаления тега: ' . $e->getMessage());
        }
    }

    /**
     * Страница статистики тегов
     */
    public function statistics()
    {
        // Топ-20 популярных тегов
        $topTags = TermTaxonomy::tags()
            ->with('term')
            ->where('count', '>', 0)
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        // Неиспользуемые теги
        $unusedTags = TermTaxonomy::tags()
            ->with('term')
            ->where('count', 0)
            ->orderBy('term_id', 'desc')
            ->get();

        // Теги с одной статьей
        $singleUseTags = TermTaxonomy::tags()
            ->with('term')
            ->where('count', 1)
            ->orderBy('term_id', 'desc')
            ->get();

        // Общая статистика
        $stats = [
            'total' => TermTaxonomy::tags()->count(),
            'active' => TermTaxonomy::tags()->where('count', '>', 0)->count(),
            'unused' => count($unusedTags),
            'single' => count($singleUseTags),
            'top_count' => $topTags->first()->count ?? 0,
        ];

        return view('admin.tags.statistics', compact('topTags', 'unusedTags', 'singleUseTags', 'stats'));
    }

    /**
     * Форма объединения тегов
     */
    public function mergeForm()
    {
        $tags = TermTaxonomy::tags()
            ->with('term')
            ->where('count', '>', 0)
            ->orderBy('count', 'desc')
            ->get();

        return view('admin.tags.merge', compact('tags'));
    }

    /**
     * Выполнение объединения тегов
     */
    public function merge(Request $request)
    {
        $validated = $request->validate([
            'source_tags' => 'required|array|min:1',
            'source_tags.*' => 'exists:wp_term_taxonomy,term_taxonomy_id',
            'target_tag' => 'required|exists:wp_term_taxonomy,term_taxonomy_id',
        ]);

        $sourceIds = $validated['source_tags'];
        $targetId = $validated['target_tag'];

        // Проверяем что целевой тег не в списке источников
        if (in_array($targetId, $sourceIds)) {
            return back()->with('error', 'Целевой тег не может быть в списке исходных тегов!');
        }

        try {
            DB::beginTransaction();

            $targetTaxonomy = TermTaxonomy::findOrFail($targetId);
            $mergedCount = 0;

            foreach ($sourceIds as $sourceId) {
                $sourceTaxonomy = TermTaxonomy::findOrFail($sourceId);

                // Получаем все связи источника
                $relationships = DB::table('wp_term_relationships')
                    ->where('term_taxonomy_id', $sourceId)
                    ->get();

                foreach ($relationships as $rel) {
                    // Проверяем, нет ли уже связи с целевым тегом
                    $exists = DB::table('wp_term_relationships')
                        ->where('object_id', $rel->object_id)
                        ->where('term_taxonomy_id', $targetId)
                        ->exists();

                    if (!$exists) {
                        // Переназначаем на целевой тег
                        DB::table('wp_term_relationships')
                            ->where('object_id', $rel->object_id)
                            ->where('term_taxonomy_id', $sourceId)
                            ->update(['term_taxonomy_id' => $targetId]);
                        
                        $mergedCount++;
                    } else {
                        // Просто удаляем дубликат
                        DB::table('wp_term_relationships')
                            ->where('object_id', $rel->object_id)
                            ->where('term_taxonomy_id', $sourceId)
                            ->delete();
                    }
                }

                // Удаляем исходную таксономию
                $sourceTaxonomy->delete();

                // Удаляем термин если он не используется
                $otherTaxonomies = TermTaxonomy::where('term_id', $sourceTaxonomy->term_id)->count();
                if ($otherTaxonomies == 0) {
                    Term::where('term_id', $sourceTaxonomy->term_id)->delete();
                }
            }

            // Обновляем счетчик целевого тега
            $newCount = DB::table('wp_term_relationships')
                ->where('term_taxonomy_id', $targetId)
                ->count();
            
            $targetTaxonomy->update(['count' => $newCount]);

            DB::commit();

            return redirect()->route('admin.tags.index')
                ->with('success', "Теги успешно объединены! Переназначено связей: {$mergedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка объединения тегов: ' . $e->getMessage());
            return back()->with('error', 'Ошибка объединения тегов: ' . $e->getMessage());
        }
    }

    /**
     * Массовое удаление тегов
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:wp_term_taxonomy,term_taxonomy_id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            foreach ($validated['tag_ids'] as $id) {
                $taxonomy = TermTaxonomy::tags()->with('term')->find($id);
                if ($taxonomy) {
                    // Удаляем связи
                    DB::table('wp_term_relationships')
                        ->where('term_taxonomy_id', $id)
                        ->delete();

                    // Удаляем таксономию
                    $taxonomy->delete();

                    // Удаляем термин если не используется
                    $otherTaxonomies = TermTaxonomy::where('term_id', $taxonomy->term_id)->count();
                    if ($otherTaxonomies == 0) {
                        $taxonomy->term->delete();
                    }

                    $deletedCount++;
                }
            }

            DB::commit();

            return redirect()->route('admin.tags.index')
                ->with('success', "Успешно удалено тегов: {$deletedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка массового удаления тегов: ' . $e->getMessage());
            return back()->with('error', 'Ошибка массового удаления тегов: ' . $e->getMessage());
        }
    }

    /**
     * Анализ контента и предложение новых тегов
     */
    public function suggestTags(Request $request)
    {
        $limit = $request->get('limit', 200); // Сколько статей анализировать
        $minFrequency = $request->get('min_frequency', 10); // Минимальная частота слова
        $topResults = $request->get('top', 100); // Топ результатов

        // Список стоп-слов
        $stopWords = $this->getStopWords();

        // Получаем существующие теги для исключения
        $existingTags = TermTaxonomy::tags()
            ->with('term')
            ->get()
            ->pluck('term.name')
            ->map(fn($name) => mb_strtolower($name))
            ->toArray();

        // Получаем статьи
        $posts = DB::table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->select('post_title', 'post_content', 'post_excerpt')
            ->orderBy('post_date', 'desc')
            ->limit($limit)
            ->get();

        $wordFrequency = [];

        foreach ($posts as $post) {
            // Объединяем весь текст
            $text = $post->post_title . ' ' . $post->post_content . ' ' . ($post->post_excerpt ?? '');
            
            // Удаляем HTML теги
            $text = strip_tags($text);
            
            // Удаляем спецсимволы, оставляем только буквы и пробелы
            $text = preg_replace('/[^а-яёА-ЯЁa-zA-Z\s]/u', ' ', $text);
            
            // Разбиваем на слова
            $words = preg_split('/\s+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
            
            foreach ($words as $word) {
                // Пропускаем короткие слова (< 4 символов)
                if (mb_strlen($word) < 4) continue;
                
                // Пропускаем стоп-слова
                if (in_array($word, $stopWords)) continue;
                
                // Пропускаем существующие теги
                if (in_array($word, $existingTags)) continue;
                
                // Считаем частоту
                if (!isset($wordFrequency[$word])) {
                    $wordFrequency[$word] = 0;
                }
                $wordFrequency[$word]++;
            }
        }

        // Фильтруем по минимальной частоте
        $wordFrequency = array_filter($wordFrequency, fn($freq) => $freq >= $minFrequency);

        // Сортируем по частоте
        arsort($wordFrequency);

        // Берем топ
        $wordFrequency = array_slice($wordFrequency, 0, $topResults, true);

        $stats = [
            'analyzed_posts' => $posts->count(),
            'total_unique_words' => count($wordFrequency),
            'existing_tags' => count($existingTags),
            'stop_words' => count($stopWords),
        ];

        return view('admin.tags.suggest', compact('wordFrequency', 'stats', 'limit', 'minFrequency', 'topResults'));
    }

    /**
     * Массовое создание тегов из предложений
     */
    public function bulkCreateFromSuggestions(Request $request)
    {
        $validated = $request->validate([
            'words' => 'required|array|min:1',
            'words.*' => 'required|string|max:200',
            'auto_assign' => 'boolean',
        ]);

        $autoAssign = $request->has('auto_assign');

        try {
            DB::beginTransaction();

            $createdCount = 0;
            $skippedCount = 0;
            $assignedCount = 0;
            $createdTags = [];

            foreach ($validated['words'] as $word) {
                $word = trim($word);
                
                // Проверяем, не существует ли уже такой тег
                $existingTerm = Term::whereHas('taxonomies', function($q) {
                    $q->where('taxonomy', 'post_tag');
                })->where('name', $word)->first();

                if ($existingTerm) {
                    $skippedCount++;
                    
                    // Если тег уже есть и включено авто-тегирование, используем существующий
                    if ($autoAssign) {
                        $taxonomy = $existingTerm->taxonomies()->where('taxonomy', 'post_tag')->first();
                        if ($taxonomy) {
                            $createdTags[] = [
                                'word' => $word,
                                'term_id' => $existingTerm->term_id,
                                'taxonomy_id' => $taxonomy->term_taxonomy_id,
                                'is_new' => false,
                            ];
                        }
                    }
                    continue;
                }

                // Создаем slug
                $slug = Str::slug($word);

                // Создаем термин
                $term = Term::create([
                    'name' => ucfirst($word),
                    'slug' => $slug,
                ]);

                // Создаем таксономию
                $taxonomy = TermTaxonomy::create([
                    'term_id' => $term->term_id,
                    'taxonomy' => 'post_tag',
                    'description' => '',
                    'parent' => 0,
                    'count' => 0,
                ]);

                $createdCount++;
                
                if ($autoAssign) {
                    $createdTags[] = [
                        'word' => $word,
                        'term_id' => $term->term_id,
                        'taxonomy_id' => $taxonomy->term_taxonomy_id,
                        'is_new' => true,
                    ];
                }
            }

            // Автоматическое назначение тегов
            if ($autoAssign && !empty($createdTags)) {
                foreach ($createdTags as $tagInfo) {
                    $assigned = $this->autoAssignTagToPosts($tagInfo['word'], $tagInfo['taxonomy_id']);
                    $assignedCount += $assigned;
                    
                    // Обновляем счетчик тега
                    TermTaxonomy::where('term_taxonomy_id', $tagInfo['taxonomy_id'])
                        ->update(['count' => $assigned]);
                }
            }

            DB::commit();

            $message = "Создано тегов: {$createdCount}";
            if ($skippedCount > 0) {
                $message .= ", пропущено (уже существуют): {$skippedCount}";
            }
            if ($autoAssign && $assignedCount > 0) {
                $message .= ", автоматически назначено: {$assignedCount} связей";
            }

            return redirect()->route('admin.tags.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка массового создания тегов: ' . $e->getMessage());
            return back()->with('error', 'Ошибка создания тегов: ' . $e->getMessage());
        }
    }

    /**
     * Автоматическое назначение тега статьям
     */
    private function autoAssignTagToPosts($word, $taxonomyId)
    {
        $word = mb_strtolower($word);
        $assignedCount = 0;

        // Ищем статьи, где встречается это слово
        $posts = DB::table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) use ($word) {
                $query->where('post_title', 'LIKE', "%{$word}%")
                      ->orWhere('post_content', 'LIKE', "%{$word}%")
                      ->orWhere('post_excerpt', 'LIKE', "%{$word}%");
            })
            ->select('ID')
            ->get();

        foreach ($posts as $post) {
            // Проверяем, нет ли уже этого тега у поста
            $exists = DB::table('wp_term_relationships')
                ->where('object_id', $post->ID)
                ->where('term_taxonomy_id', $taxonomyId)
                ->exists();

            if (!$exists) {
                // Добавляем связь
                DB::table('wp_term_relationships')->insert([
                    'object_id' => $post->ID,
                    'term_taxonomy_id' => $taxonomyId,
                    'term_order' => 0,
                ]);
                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Предпросмотр автоматического тегирования
     */
    public function previewAutoTagging(Request $request)
    {
        $validated = $request->validate([
            'words' => 'required|array|min:1',
            'words.*' => 'required|string|max:200',
        ]);

        $preview = [];

        foreach ($validated['words'] as $word) {
            $word = trim($word);
            $wordLower = mb_strtolower($word);

            // Проверяем существование тега
            $existingTag = Term::whereHas('taxonomies', function($q) {
                $q->where('taxonomy', 'post_tag');
            })->where('name', $word)->exists();

            // Считаем потенциальные статьи
            $postsCount = DB::table('wp_posts')
                ->where('post_type', 'post')
                ->where('post_status', 'publish')
                ->where(function($query) use ($wordLower) {
                    $query->where(DB::raw('LOWER(post_title)'), 'LIKE', "%{$wordLower}%")
                          ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', "%{$wordLower}%")
                          ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', "%{$wordLower}%");
                })
                ->count();

            // Пример статей
            $examplePosts = DB::table('wp_posts')
                ->where('post_type', 'post')
                ->where('post_status', 'publish')
                ->where(function($query) use ($wordLower) {
                    $query->where(DB::raw('LOWER(post_title)'), 'LIKE', "%{$wordLower}%")
                          ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', "%{$wordLower}%")
                          ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', "%{$wordLower}%");
                })
                ->select('ID', 'post_title', 'post_date')
                ->orderBy('post_date', 'desc')
                ->limit(5)
                ->get();

            $preview[] = [
                'word' => ucfirst($word),
                'exists' => $existingTag,
                'posts_count' => $postsCount,
                'example_posts' => $examplePosts,
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_posts' => array_sum(array_column($preview, 'posts_count')),
            'total_tags' => count($preview),
        ]);
    }

    /**
     * Страница массового автотегирования
     */
    public function massAutoTagging()
    {
        // Получаем ВСЕ теги (включая неиспользуемые)
        $tags = TermTaxonomy::tags()
            ->with('term')
            ->orderBy('count', 'desc')
            ->orderBy('term_id', 'desc')
            ->get();

        return view('admin.tags.mass-auto-tagging', compact('tags'));
    }

    /**
     * Выполнение автотегирования для выбранного тега
     */
    public function executeAutoTagging(Request $request, $id)
    {
        $validated = $request->validate([
            'search_mode' => 'required|in:word,exact',
        ]);

        $taxonomy = TermTaxonomy::tags()->with('term')->findOrFail($id);
        $word = $taxonomy->term->name;
        $searchMode = $validated['search_mode'];

        try {
            DB::beginTransaction();

            $wordLower = mb_strtolower($word);
            $assignedCount = 0;
            $skippedCount = 0;

            // Ищем статьи в зависимости от режима
            $query = DB::table('wp_posts')
                ->where('post_type', 'post')
                ->where('post_status', 'publish');

            if ($searchMode === 'word') {
                // Поиск по вхождению слова
                $query->where(function($q) use ($wordLower) {
                    $q->where(DB::raw('LOWER(post_title)'), 'LIKE', "%{$wordLower}%")
                      ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', "%{$wordLower}%")
                      ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', "%{$wordLower}%");
                });
            } else {
                // Точный поиск (как отдельное слово)
                $pattern = '%\b' . $wordLower . '\b%';
                $query->where(function($q) use ($pattern) {
                    $q->where(DB::raw('LOWER(post_title)'), 'LIKE', $pattern)
                      ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', $pattern)
                      ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', $pattern);
                });
            }

            $posts = $query->select('ID')->get();

            foreach ($posts as $post) {
                // Проверяем, нет ли уже этого тега
                $exists = DB::table('wp_term_relationships')
                    ->where('object_id', $post->ID)
                    ->where('term_taxonomy_id', $taxonomy->term_taxonomy_id)
                    ->exists();

                if (!$exists) {
                    DB::table('wp_term_relationships')->insert([
                        'object_id' => $post->ID,
                        'term_taxonomy_id' => $taxonomy->term_taxonomy_id,
                        'term_order' => 0,
                    ]);
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Обновляем счетчик
            $newCount = DB::table('wp_term_relationships')
                ->where('term_taxonomy_id', $taxonomy->term_taxonomy_id)
                ->count();

            $taxonomy->update(['count' => $newCount]);

            DB::commit();

            $message = "Тег «{$word}» применен к {$assignedCount} статье(ям)";
            if ($skippedCount > 0) {
                $message .= ". Пропущено: {$skippedCount} (тег уже был)";
            }
            $message .= ". Всего статей с этим тегом: {$newCount}";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка автотегирования: ' . $e->getMessage());
            return back()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Предпросмотр автотегирования для одного тега
     */
    public function previewSingleTagging($id, Request $request)
    {
        $validated = $request->validate([
            'search_mode' => 'required|in:word,exact',
        ]);

        $taxonomy = TermTaxonomy::tags()->with('term')->findOrFail($id);
        $word = $taxonomy->term->name;
        $wordLower = mb_strtolower($word);
        $searchMode = $validated['search_mode'];

        // Ищем статьи
        $query = DB::table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish');

        if ($searchMode === 'word') {
            $query->where(function($q) use ($wordLower) {
                $q->where(DB::raw('LOWER(post_title)'), 'LIKE', "%{$wordLower}%")
                  ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', "%{$wordLower}%")
                  ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', "%{$wordLower}%");
            });
        } else {
            $pattern = '%\b' . $wordLower . '\b%';
            $query->where(function($q) use ($pattern) {
                $q->where(DB::raw('LOWER(post_title)'), 'LIKE', $pattern)
                  ->orWhere(DB::raw('LOWER(post_content)'), 'LIKE', $pattern)
                  ->orWhere(DB::raw('LOWER(post_excerpt)'), 'LIKE', $pattern);
            });
        }

        $foundPosts = $query->select('ID', 'post_title', 'post_date')->get();

        // Проверяем у каких уже есть этот тег
        $postsWithTag = DB::table('wp_term_relationships')
            ->where('term_taxonomy_id', $taxonomy->term_taxonomy_id)
            ->pluck('object_id')
            ->toArray();

        $toAdd = $foundPosts->whereNotIn('ID', $postsWithTag);
        $alreadyHave = $foundPosts->whereIn('ID', $postsWithTag);

        return response()->json([
            'success' => true,
            'tag_name' => $word,
            'current_count' => $taxonomy->count,
            'found_total' => $foundPosts->count(),
            'to_add' => $toAdd->count(),
            'already_have' => $alreadyHave->count(),
            'new_total' => $taxonomy->count + $toAdd->count(),
            'examples_to_add' => $toAdd->take(10)->values(),
            'examples_already_have' => $alreadyHave->take(5)->values(),
        ]);
    }

    /**
     * Список стоп-слов (общеупотребительные слова, которые не должны быть тегами)
     */
    private function getStopWords()
    {
        return [
            // Предлоги
            'без', 'безо', 'близ', 'более', 'больше', 'вблизи', 'ввиду', 'вглубь', 'вдоль', 
            'вместо', 'вне', 'внизу', 'внутри', 'внутрь', 'вокруг', 'вопреки', 'впереди', 
            'вплоть', 'впоследствии', 'время', 'вроде', 'вслед', 'вследствие', 'вторая', 
            'второй', 'выше', 'далее', 'для', 'днем', 'другая', 'другие', 'других', 'другой', 
            'едва', 'если', 'еще', 'жаль', 'здесь', 'или', 'иметь', 'иногда', 'именно',
            'итак', 'как', 'какая', 'какой', 'кем', 'когда', 'кого', 'ком', 'кому', 'конечно',
            'которая', 'которого', 'которой', 'которую', 'которые', 'который', 'которых',
            'кроме', 'кто', 'куда', 'лучше', 'между', 'меля', 'менее', 'меньше', 'меня',
            'мимо', 'мной', 'много', 'мог', 'могут', 'мож', 'может', 'можно', 'моя', 'мы',
            'на', 'над', 'надо', 'наиболее', 'наконец', 'нам', 'нами', 'наоборот', 'нас',
            'насчет', 'наш', 'наша', 'наше', 'нашего', 'нашей', 'наши', 'наших', 'не', 'него',
            'нее', 'ней', 'немного', 'нему', 'непрерывно', 'нередко', 'несколько', 'нет',
            'нею', 'ни', 'нибудь', 'ниже', 'низко', 'никак', 'никогда', 'никто', 'ним',
            'ними', 'них', 'ничего', 'ничто', 'но', 'ну', 'о', 'об', 'оба', 'обе', 'один',
            'одна', 'одни', 'одним', 'одних', 'одно', 'однако', 'одной', 'около', 'он', 'она',
            'они', 'оно', 'опять', 'особенно', 'остаться', 'от', 'ото', 'отовсюду', 'отсюда',
            'очень', 'по', 'под', 'поди', 'подо', 'пожалуйста', 'позже', 'пока', 'пор', 'пора',
            'после', 'посреди', 'потом', 'потому', 'почему', 'почти', 'правда', 'при', 'про',
            'просто', 'против', 'пятая', 'пятый', 'раз', 'разве', 'рано', 'раньше', 'рядом',
            'с', 'сам', 'сама', 'сами', 'самим', 'самими', 'самих', 'само', 'самого', 'самой',
            'самом', 'самому', 'саму', 'самый', 'свое', 'своего', 'своей', 'свои', 'своих',
            'свою', 'сделать', 'сеаоя', 'себе', 'себя', 'сегодня', 'седьмая', 'седьмой',
            'сейчас', 'семь', 'серыя', 'серый', 'си', 'сих', 'сказал', 'сказала', 'сказать',
            'сколько', 'слишком', 'сначала', 'снова', 'со', 'собой', 'собою', 'совсем',
            'спасибо', 'стал', 'стала', 'стали', 'стать', 'суть', 'та', 'так', 'такая',
            'также', 'таки', 'такие', 'таким', 'такими', 'таких', 'такого', 'такое', 'такой',
            'такую', 'там', 'твой', 'твоя', 'твоё', 'твои', 'те', 'тебе', 'тебя', 'тем', 'теми',
            'теперь', 'тех', 'то', 'тоаз', 'тобой', 'тобою', 'тогда', 'того', 'тоже', 'тоже',
            'только', 'том', 'тому', 'тот', 'тою', 'третий', 'третья', 'три', 'тринадцатая',
            'тринадцатый', 'ту', 'туда', 'тут', 'ты', 'тысяч', 'у', 'уж', 'уже', 'уметь',
            'хорошо', 'хотел', 'хотела', 'хотеть', 'хоть', 'хотя', 'хочешь', 'цел', 'цела',
            'целые', 'целый', 'цельа', 'часто', 'чаще', 'чего', 'человек', 'чем', 'чему',
            'через', 'четвертая', 'четвертый', 'четыре', 'четырнадцатая', 'четырнадцатый',
            'что', 'чтоб', 'чтобы', 'чуть', 'шестая', 'шестнадцатая', 'шестнадцатый', 'шестой',
            'шесть', 'эта', 'эти', 'этим', 'этими', 'этих', 'это', 'этого', 'этой', 'этом',
            'этому', 'этот', 'этою', 'эту', 'я', 'являюсь', 'январь',
            // Дополнительные общие слова
            'будет', 'была', 'были', 'было', 'быть', 'весь', 'вся', 'всё', 'все', 'всего',
            'всей', 'всем', 'всему', 'всех', 'всею', 'всю', 'вся', 'где', 'год', 'года', 'году',
            'день', 'дня', 'есть', 'была', 'были', 'было', 'быть', 'сказал', 'сказала',
            'говорит', 'говорил', 'два', 'две', 'двух', 'этого', 'тому', 'таких', 'том',
            'того', 'тех', 'ещё', 'более', 'всех', 'были', 'была', 'было', 'будет', 'буду',
            'будут', 'будем', 'будете', 'есть', 'был', 'была', 'было', 'были',
            // Английские
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was',
            'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new',
            'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put',
            'say', 'she', 'too', 'use', 'this', 'that', 'with', 'have', 'from', 'they',
            'been', 'have', 'were', 'said', 'what', 'when', 'your', 'will', 'more', 'than',
            'some', 'time', 'very', 'upon', 'about', 'after', 'again', 'could', 'first',
            'other', 'their', 'there', 'these', 'would', 'which', 'before', 'should',
        ];
    }
}
