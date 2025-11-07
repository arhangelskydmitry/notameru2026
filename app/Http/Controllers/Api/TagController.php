<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordPress\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * Получить список тегов
     */
    public function index(Request $request): JsonResponse
    {
        $query = TermTaxonomy::where('taxonomy', 'post_tag')
            ->with('term')
            ->orderBy('count', 'desc');
        
        // Лимит
        $limit = min($request->get('limit', 100), 500);
        $tags = $query->limit($limit)->get();
        
        return response()->json([
            'success' => true,
            'data' => $tags->map(function($tag) {
                return [
                    'id' => $tag->term_taxonomy_id,
                    'name' => $tag->term->name ?? '',
                    'slug' => $tag->term->slug ?? '',
                    'description' => $tag->description,
                    'count' => $tag->count,
                    'url' => config('app.url') . '/tag/' . ($tag->term->slug ?? ''),
                ];
            })
        ]);
    }
    
    /**
     * Получить один тег по ID или slug
     */
    public function show(string $id): JsonResponse
    {
        $tag = TermTaxonomy::where('taxonomy', 'post_tag')
            ->where(function($query) use ($id) {
                $query->where('term_taxonomy_id', $id)
                      ->orWhereHas('term', function($q) use ($id) {
                          $q->where('slug', $id);
                      });
            })
            ->with('term')
            ->first();
        
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Тег не найден'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tag->term_taxonomy_id,
                'name' => $tag->term->name ?? '',
                'slug' => $tag->term->slug ?? '',
                'description' => $tag->description,
                'count' => $tag->count,
                'url' => config('app.url') . '/tag/' . ($tag->term->slug ?? ''),
            ]
        ]);
    }
    
    /**
     * Получить популярные теги
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 20), 100);
        
        $tags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->where('count', '>', 0)
            ->with('term')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $tags->map(function($tag) {
                return [
                    'id' => $tag->term_taxonomy_id,
                    'name' => $tag->term->name ?? '',
                    'slug' => $tag->term->slug ?? '',
                    'count' => $tag->count,
                ];
            })
        ]);
    }
}
