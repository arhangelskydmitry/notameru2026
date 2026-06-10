<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WordPress\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaDescriptionController extends Controller
{
    /**
     * Главная страница анализа мета-описаний
     */
    public function index(Request $request)
    {
        // Получаем статистику
        $stats = $this->getStatistics();
        
        // Получаем проблемные статьи
        $filter = $request->input('filter', 'all');
        $search = $request->input('search', '');
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->when($search, function($query) use ($search) {
                return $query->where('post_title', 'like', "%{$search}%");
            });
        
        // Применяем фильтры
        switch ($filter) {
            case 'no_description':
                $posts = $this->filterNoDescription($posts);
                break;
            case 'short':
                $posts = $this->filterShortDescription($posts);
                break;
            case 'long':
                $posts = $this->filterLongDescription($posts);
                break;
            case 'duplicates':
                return $this->showDuplicates($request);
            case 'good':
                $posts = $this->filterGoodDescription($posts);
                break;
        }
        
        $posts = $posts->orderBy('post_date', 'desc')
            ->paginate(50);
        
        return view('admin.meta-descriptions.index', compact('stats', 'posts', 'filter', 'search'));
    }
    
    /**
     * Получить статистику
     */
    private function getStatistics()
    {
        $seoService = app(\App\Services\SeoService::class);
        
        $total = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();
        
        // Получаем все статьи с их SEO данными
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with('seo')
            ->get();
        
        $noDescription = 0;
        $short = 0;
        $long = 0;
        $good = 0;
        $descriptions = [];
        
        foreach ($posts as $post) {
            $description = $seoService->getDescription($post);
            $length = mb_strlen($description);
            
            // Проверяем является ли description сохраненным или автогенерированным
            $hasSavedDescription = $post->seo && !empty($post->seo->seo_description);
            
            if (!$hasSavedDescription) {
                $noDescription++;
            } elseif ($length < 100) {
                $short++;
            } elseif ($length > 160) {
                $long++;
            } else {
                $good++;
            }
            
            // Собираем descriptions для поиска дубликатов (только сохраненные)
            if ($hasSavedDescription) {
                $descriptions[] = $description;
            }
        }
        
        // Дубликаты (только среди сохраненных descriptions)
        $duplicates = count($descriptions) - count(array_unique($descriptions));
        
        return [
            'total' => $total,
            'no_description' => $noDescription,
            'short' => $short,
            'long' => $long,
            'duplicates' => $duplicates,
            'good' => $good,
            'needs_work' => $noDescription + $short + $long,
        ];
    }
    
    /**
     * Фильтр: статьи без description
     */
    private function filterNoDescription($query)
    {
        return $query->whereDoesntHave('seo', function($q) {
            $q->where('seo_description', '!=', '')
                ->whereNotNull('seo_description');
        });
    }
    
    /**
     * Фильтр: короткие description
     */
    private function filterShortDescription($query)
    {
        return $query->whereHas('seo', function($q) {
            $q->where('seo_description', '!=', '')
                ->whereNotNull('seo_description')
                ->whereRaw('CHAR_LENGTH(seo_description) < 100');
        });
    }
    
    /**
     * Фильтр: длинные description
     */
    private function filterLongDescription($query)
    {
        return $query->whereHas('seo', function($q) {
            $q->where('seo_description', '!=', '')
                ->whereNotNull('seo_description')
                ->whereRaw('CHAR_LENGTH(seo_description) > 160');
        });
    }
    
    /**
     * Фильтр: хорошие description
     */
    private function filterGoodDescription($query)
    {
        return $query->whereHas('seo', function($q) {
            $q->where('seo_description', '!=', '')
                ->whereNotNull('seo_description')
                ->whereRaw('CHAR_LENGTH(seo_description) BETWEEN 100 AND 160');
        });
    }
    
    /**
     * Показать дубликаты
     */
    private function showDuplicates(Request $request)
    {
        $search = $request->input('search', '');
        
        // Получаем дубликаты из post_seo
        $duplicates = DB::table('post_seo as ps1')
            ->select('ps1.seo_description as meta_value', DB::raw('COUNT(*) as count'))
            ->join('wp_posts as p', 'ps1.post_id', '=', 'p.ID')
            ->where('ps1.seo_description', '!=', '')
            ->whereNotNull('ps1.seo_description')
            ->where('p.post_type', 'post')
            ->where('p.post_status', 'publish')
            ->when($search, function($query) use ($search) {
                return $query->where('ps1.seo_description', 'like', "%{$search}%");
            })
            ->groupBy('ps1.seo_description')
            ->having('count', '>', 1)
            ->orderBy('count', 'desc')
            ->paginate(20);
        
        // Для каждого дубликата получаем статьи
        foreach ($duplicates as $duplicate) {
            $duplicate->posts = Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->whereHas('seo', function($q) use ($duplicate) {
                    $q->where('seo_description', $duplicate->meta_value);
                })
                ->limit(10)
                ->get();
        }
        
        $stats = $this->getStatistics();
        $filter = 'duplicates';
        $posts = collect();
        
        return view('admin.meta-descriptions.duplicates', compact('stats', 'duplicates', 'filter', 'search'));
    }
    
    /**
     * Предпросмотр сгенерированного description
     */
    public function preview(Request $request)
    {
        $postId = $request->input('post_id');
        
        $post = Post::with('seo')->where('ID', $postId)
            ->where('post_type', 'post')
            ->first();
        
        if (!$post) {
            return response()->json(['error' => 'Статья не найдена'], 404);
        }
        
        $seoService = app(\App\Services\SeoService::class);
        
        // Текущее description (из post_seo или автогенерированное)
        $current = $seoService->getDescription($post);
        $hasSaved = $post->seo && !empty($post->seo->seo_description);
        
        // Генерируем новое
        $generated = $this->generateDescription($post);
        
        return response()->json([
            'success' => true,
            'post' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $post->post_date->format('d.m.Y'),
            ],
            'current' => $current,
            'current_length' => mb_strlen($current),
            'has_saved' => $hasSaved,
            'generated' => $generated,
            'generated_length' => mb_strlen($generated),
            'is_good_length' => mb_strlen($generated) >= 100 && mb_strlen($generated) <= 160,
        ]);
    }
    
    /**
     * Применить сгенерированное description
     */
    public function apply(Request $request)
    {
        $postId = $request->input('post_id');
        $description = $request->input('description');
        
        if (!$description) {
            return response()->json(['error' => 'Description не может быть пустым'], 400);
        }
        
        $post = Post::find($postId);
        
        if (!$post) {
            return response()->json(['error' => 'Статья не найдена'], 404);
        }
        
        // Обновляем или создаем запись в post_seo
        \App\Models\PostSeo::updateOrCreate(
            ['post_id' => $postId],
            ['seo_description' => $description]
        );
        
        Log::info("Meta description обновлен", [
            'post_id' => $postId,
            'title' => $post->post_title,
            'length' => mb_strlen($description),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Description успешно обновлен',
        ]);
    }
    
    /**
     * Массовая генерация descriptions
     */
    public function bulkGenerate(Request $request)
    {
        $postIds = $request->input('post_ids', []);
        $overwrite = $request->input('overwrite', false); // Перезаписывать существующие
        
        if (empty($postIds)) {
            return response()->json(['error' => 'Не выбраны статьи'], 400);
        }
        
        $results = [
            'success' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];
        
        foreach ($postIds as $postId) {
            try {
                $post = Post::with('seo')->find($postId);
                
                if (!$post) {
                    $results['errors']++;
                    continue;
                }
                
                // Проверяем есть ли уже сохраненное description
                $existing = $post->seo && !empty($post->seo->seo_description);
                
                if ($existing && !$overwrite) {
                    $results['skipped']++;
                    continue;
                }
                
                // Генерируем
                $description = $this->generateDescription($post);
                
                // Сохраняем в post_seo
                \App\Models\PostSeo::updateOrCreate(
                    ['post_id' => $postId],
                    ['seo_description' => $description]
                );
                
                $results['success']++;
                $results['details'][] = [
                    'id' => $postId,
                    'title' => $post->post_title,
                    'description' => Str::limit($description, 50),
                ];
                
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error("Ошибка генерации description для поста {$postId}: " . $e->getMessage());
            }
        }
        
        Log::info("Массовая генерация descriptions", $results);
        
        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
    
    /**
     * Генерация description для статьи
     */
    private function generateDescription($post)
    {
        // 1. Получаем excerpt или первый абзац
        $text = '';
        
        if (!empty($post->post_excerpt)) {
            $text = $post->post_excerpt;
        } else {
            // Извлекаем первый абзац из контента
            $content = strip_tags($post->post_content);
            $content = preg_replace('/\s+/', ' ', $content); // Убираем лишние пробелы
            $content = trim($content);
            
            // Берем первое предложение или до 200 символов
            $firstSentence = preg_split('/[.!?]/', $content, 2);
            $text = $firstSentence[0] ?? $content;
        }
        
        // 2. Очищаем текст
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // 3. Получаем теги и категории для контекста
        $tags = $post->tags()->limit(3)->pluck('name')->toArray();
        $categories = $post->categories()->limit(2)->pluck('name')->toArray();
        
        // 4. Формируем description
        $description = $text;
        
        // Обрезаем до нужной длины (120-150 символов оптимально)
        $targetLength = 150;
        
        if (mb_strlen($description) > $targetLength) {
            // Обрезаем по слову
            $description = mb_substr($description, 0, $targetLength);
            $lastSpace = mb_strrpos($description, ' ');
            if ($lastSpace) {
                $description = mb_substr($description, 0, $lastSpace);
            }
        }
        
        // Убираем незавершенные предложения
        $description = rtrim($description, '.,!?;:');
        
        // Добавляем точку если нет знака препинания в конце
        if (!preg_match('/[.!?]$/', $description)) {
            $description .= '.';
        }
        
        // 5. Добавляем бренд в конец (если осталось место)
        $brand = ' | Нота Миру';
        if (mb_strlen($description) + mb_strlen($brand) <= 160) {
            $description .= $brand;
        }
        
        // 6. Если слишком коротко, добавляем теги
        if (mb_strlen($description) < 100 && !empty($tags)) {
            $tagsText = ' ' . implode(', ', array_slice($tags, 0, 2)) . '.';
            if (mb_strlen($description) + mb_strlen($tagsText) <= 160) {
                $description = rtrim($description, '.') . '.' . $tagsText;
            }
        }
        
        return $description;
    }
    
    /**
     * Экспорт в CSV
     */
    public function export(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $seoService = app(\App\Services\SeoService::class);
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with('seo');
        
        // Применяем фильтр
        switch ($filter) {
            case 'no_description':
                $posts = $this->filterNoDescription($posts);
                break;
            case 'short':
                $posts = $this->filterShortDescription($posts);
                break;
            case 'long':
                $posts = $this->filterLongDescription($posts);
                break;
        }
        
        $posts = $posts->orderBy('post_date', 'desc')->get();
        
        $filename = 'meta-descriptions-' . $filter . '-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($posts, $seoService) {
            $file = fopen('php://output', 'w');
            
            // BOM для UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Заголовки
            fputcsv($file, ['ID', 'Заголовок', 'Дата', 'Description', 'Длина', 'Сохранен', 'URL']);
            
            foreach ($posts as $post) {
                $description = $seoService->getDescription($post);
                $hasSaved = $post->seo && !empty($post->seo->seo_description);
                
                fputcsv($file, [
                    $post->ID,
                    $post->post_title,
                    $post->post_date->format('d.m.Y'),
                    $description,
                    mb_strlen($description),
                    $hasSaved ? 'Да' : 'Автогенерация',
                    route('post', $post->post_name),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
