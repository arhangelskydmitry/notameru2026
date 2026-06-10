<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagMergeController extends Controller
{
    /**
     * Показать страницу анализа похожих тегов
     */
    public function index(Request $request)
    {
        $similarityThreshold = $request->input('threshold', 80); // Процент похожести
        $minCount = $request->input('min_count', 0); // Минимальное кол-во статей
        
        // Получаем все теги
        $tags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->with('term')
            ->when($minCount > 0, function($query) use ($minCount) {
                return $query->where('count', '>=', $minCount);
            })
            ->get();
        
        // Группируем похожие теги
        $similarGroups = $this->findSimilarTags($tags, $similarityThreshold);
        
        // Статистика
        $stats = [
            'total_tags' => $tags->count(),
            'total_groups' => count($similarGroups),
            'potential_duplicates' => $this->countPotentialDuplicates($similarGroups),
            'potential_cleanup' => $this->countTagsToRemove($similarGroups),
        ];
        
        return view('admin.tags.merge-index', compact('similarGroups', 'stats', 'similarityThreshold', 'minCount'));
    }
    
    /**
     * Найти похожие теги
     */
    private function findSimilarTags($tags, $threshold)
    {
        $groups = [];
        $processed = [];
        
        foreach ($tags as $tag1) {
            $name1 = mb_strtolower($tag1->term->name);
            
            // Пропускаем уже обработанные
            if (in_array($tag1->term_id, $processed)) {
                continue;
            }
            
            $similarTags = [$tag1];
            
            foreach ($tags as $tag2) {
                if ($tag1->term_id === $tag2->term_id) {
                    continue;
                }
                
                if (in_array($tag2->term_id, $processed)) {
                    continue;
                }
                
                $name2 = mb_strtolower($tag2->term->name);
                
                // Проверяем различные типы похожести
                if ($this->areSimilar($name1, $name2, $threshold)) {
                    $similarTags[] = $tag2;
                    $processed[] = $tag2->term_id;
                }
            }
            
            // Если нашли похожие теги, добавляем группу
            if (count($similarTags) > 1) {
                // Сортируем по количеству статей (убывание)
                usort($similarTags, function($a, $b) {
                    return $b->count <=> $a->count;
                });
                
                $groups[] = [
                    'tags' => $similarTags,
                    'suggested_primary' => $similarTags[0], // Тег с наибольшим count
                    'total_articles' => array_sum(array_column($similarTags, 'count')),
                ];
                
                $processed[] = $tag1->term_id;
            }
        }
        
        // Сортируем группы по количеству статей
        usort($groups, function($a, $b) {
            return $b['total_articles'] <=> $a['total_articles'];
        });
        
        return $groups;
    }
    
    /**
     * Проверить похожесть двух строк
     */
    private function areSimilar($str1, $str2, $threshold)
    {
        // 1. Точное совпадение (разный регистр)
        if ($str1 === $str2) {
            return true;
        }
        
        // 2. Один содержит другой
        if (mb_strlen($str1) > 3 && mb_strlen($str2) > 3) {
            if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
                return true;
            }
        }
        
        // 3. Разница только в дефисах/пробелах
        $normalized1 = str_replace(['-', '_', ' '], '', $str1);
        $normalized2 = str_replace(['-', '_', ' '], '', $str2);
        if ($normalized1 === $normalized2) {
            return true;
        }
        
        // 4. Единственное/множественное число (простая проверка)
        if ($this->isSingularPlural($str1, $str2)) {
            return true;
        }
        
        // 5. Расстояние Левенштейна
        $maxLen = max(mb_strlen($str1), mb_strlen($str2));
        if ($maxLen > 0) {
            $distance = levenshtein($str1, $str2);
            $similarity = (1 - $distance / $maxLen) * 100;
            
            if ($similarity >= $threshold) {
                return true;
            }
        }
        
        // 6. similar_text для русского языка
        similar_text($str1, $str2, $percent);
        if ($percent >= $threshold) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверить единственное/множественное число
     */
    private function isSingularPlural($str1, $str2)
    {
        // Простые русские окончания
        $endings = ['ы', 'и', 'а', 'я', 'ов', 'ев', 'ий'];
        
        foreach ($endings as $ending) {
            if ($str1 . $ending === $str2 || $str2 . $ending === $str1) {
                return true;
            }
            if (rtrim($str1, $ending) === rtrim($str2, $ending)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Подсчитать потенциальные дубликаты
     */
    private function countPotentialDuplicates($groups)
    {
        $count = 0;
        foreach ($groups as $group) {
            $count += count($group['tags']);
        }
        return $count;
    }
    
    /**
     * Подсчитать теги, которые можно удалить
     */
    private function countTagsToRemove($groups)
    {
        $count = 0;
        foreach ($groups as $group) {
            $count += (count($group['tags']) - 1); // Оставляем один основной
        }
        return $count;
    }
    
    /**
     * Предпросмотр слияния
     */
    public function previewMerge(Request $request)
    {
        $primaryId = $request->input('primary_id');
        $mergeIds = $request->input('merge_ids', []);
        
        if (!$primaryId || empty($mergeIds)) {
            return response()->json(['error' => 'Не выбраны теги для слияния'], 400);
        }
        
        // Получаем основной тег
        $primaryTag = TermTaxonomy::with('term')
            ->where('taxonomy', 'post_tag')
            ->where('term_id', $primaryId)
            ->first();
        
        if (!$primaryTag) {
            return response()->json(['error' => 'Основной тег не найден'], 404);
        }
        
        // Получаем теги для слияния
        $mergeTags = TermTaxonomy::with('term')
            ->where('taxonomy', 'post_tag')
            ->whereIn('term_id', $mergeIds)
            ->get();
        
        // Подсчитываем статистику
        $currentCount = $primaryTag->count;
        $mergeCount = $mergeTags->sum('count');
        
        // Проверяем пересечения (статьи, у которых уже есть оба тега)
        $overlappingArticles = DB::table('wp_term_relationships as tr1')
            ->join('wp_term_relationships as tr2', 'tr1.object_id', '=', 'tr2.object_id')
            ->where('tr1.term_taxonomy_id', $primaryTag->term_taxonomy_id)
            ->whereIn('tr2.term_taxonomy_id', $mergeTags->pluck('term_taxonomy_id'))
            ->distinct('tr1.object_id')
            ->count('tr1.object_id');
        
        $newCount = $currentCount + $mergeCount - $overlappingArticles;
        
        // Получаем примеры статей, которые получат новый тег
        $exampleArticles = DB::table('wp_posts as p')
            ->join('wp_term_relationships as tr', 'p.ID', '=', 'tr.object_id')
            ->whereIn('tr.term_taxonomy_id', $mergeTags->pluck('term_taxonomy_id'))
            ->whereNotExists(function($query) use ($primaryTag) {
                $query->select(DB::raw(1))
                    ->from('wp_term_relationships as tr2')
                    ->whereRaw('tr2.object_id = p.ID')
                    ->where('tr2.term_taxonomy_id', $primaryTag->term_taxonomy_id);
            })
            ->select('p.ID', 'p.post_title', 'p.post_date')
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'primary_tag' => [
                'id' => $primaryTag->term_id,
                'name' => $primaryTag->term->name,
                'current_count' => $currentCount,
            ],
            'merge_tags' => $mergeTags->map(fn($t) => [
                'id' => $t->term_id,
                'name' => $t->term->name,
                'count' => $t->count,
            ]),
            'statistics' => [
                'current_count' => $currentCount,
                'adding_count' => $mergeCount - $overlappingArticles,
                'overlapping_count' => $overlappingArticles,
                'new_total' => $newCount,
                'tags_to_remove' => count($mergeIds),
            ],
            'example_articles' => $exampleArticles,
        ]);
    }
    
    /**
     * Выполнить слияние тегов
     */
    public function executeMerge(Request $request)
    {
        $primaryId = $request->input('primary_id');
        $mergeIds = $request->input('merge_ids', []);
        
        if (!$primaryId || empty($mergeIds)) {
            return response()->json(['error' => 'Не выбраны теги для слияния'], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Получаем основной тег
            $primaryTag = TermTaxonomy::with('term')
                ->where('taxonomy', 'post_tag')
                ->where('term_id', $primaryId)
                ->lockForUpdate()
                ->first();
            
            if (!$primaryTag) {
                throw new \Exception('Основной тег не найден');
            }
            
            // Получаем теги для слияния
            $mergeTags = TermTaxonomy::with('term')
                ->where('taxonomy', 'post_tag')
                ->whereIn('term_id', $mergeIds)
                ->lockForUpdate()
                ->get();
            
            $mergedCount = 0;
            $updatedArticles = 0;
            
            foreach ($mergeTags as $mergeTag) {
                // Получаем все связи с этим тегом
                $relationships = DB::table('wp_term_relationships')
                    ->where('term_taxonomy_id', $mergeTag->term_taxonomy_id)
                    ->get();
                
                foreach ($relationships as $rel) {
                    // Проверяем, есть ли уже связь с основным тегом
                    $exists = DB::table('wp_term_relationships')
                        ->where('object_id', $rel->object_id)
                        ->where('term_taxonomy_id', $primaryTag->term_taxonomy_id)
                        ->exists();
                    
                    if (!$exists) {
                        // Создаем новую связь с основным тегом
                        DB::table('wp_term_relationships')->insert([
                            'object_id' => $rel->object_id,
                            'term_taxonomy_id' => $primaryTag->term_taxonomy_id,
                            'term_order' => 0,
                        ]);
                        $updatedArticles++;
                    }
                    
                    // Удаляем старую связь
                    DB::table('wp_term_relationships')
                        ->where('object_id', $rel->object_id)
                        ->where('term_taxonomy_id', $mergeTag->term_taxonomy_id)
                        ->delete();
                }
                
                $mergedCount += $mergeTag->count;
                
                // Удаляем старый тег из term_taxonomy
                DB::table('wp_term_taxonomy')
                    ->where('term_taxonomy_id', $mergeTag->term_taxonomy_id)
                    ->delete();
                
                // Удаляем старый тег из terms (если больше не используется)
                $stillUsed = DB::table('wp_term_taxonomy')
                    ->where('term_id', $mergeTag->term_id)
                    ->exists();
                
                if (!$stillUsed) {
                    DB::table('wp_terms')
                        ->where('term_id', $mergeTag->term_id)
                        ->delete();
                }
            }
            
            // Обновляем счетчик основного тега
            $newCount = DB::table('wp_term_relationships')
                ->where('term_taxonomy_id', $primaryTag->term_taxonomy_id)
                ->distinct('object_id')
                ->count('object_id');
            
            DB::table('wp_term_taxonomy')
                ->where('term_taxonomy_id', $primaryTag->term_taxonomy_id)
                ->update(['count' => $newCount]);
            
            DB::commit();
            
            Log::info("Теги объединены", [
                'primary_tag' => $primaryTag->term->name,
                'merged_tags' => $mergeTags->pluck('term.name'),
                'updated_articles' => $updatedArticles,
                'new_count' => $newCount,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Теги успешно объединены',
                'statistics' => [
                    'merged_tags_count' => count($mergeIds),
                    'updated_articles' => $updatedArticles,
                    'new_total' => $newCount,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка при слиянии тегов: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка при слиянии: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Массовое слияние (по группам из анализа)
     */
    public function bulkMerge(Request $request)
    {
        $groups = $request->input('groups', []);
        
        if (empty($groups)) {
            return response()->json(['error' => 'Нет групп для слияния'], 400);
        }
        
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($groups as $group) {
            try {
                $response = $this->executeMerge(new Request([
                    'primary_id' => $group['primary_id'],
                    'merge_ids' => $group['merge_ids'],
                ]));
                
                $data = json_decode($response->getContent(), true);
                
                if ($data['success'] ?? false) {
                    $successCount++;
                    $results[] = [
                        'success' => true,
                        'primary_id' => $group['primary_id'],
                        'merged_count' => count($group['merge_ids']),
                    ];
                } else {
                    $errorCount++;
                    $results[] = [
                        'success' => false,
                        'primary_id' => $group['primary_id'],
                        'error' => $data['error'] ?? 'Неизвестная ошибка',
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $results[] = [
                    'success' => false,
                    'primary_id' => $group['primary_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'success' => $errorCount === 0,
            'summary' => [
                'total' => count($groups),
                'success' => $successCount,
                'errors' => $errorCount,
            ],
            'results' => $results,
        ]);
    }
}
