<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordPress\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Получить список категорий
     */
    public function index(Request $request): JsonResponse
    {
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->orderBy('count', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories->map(function($cat) {
                return [
                    'id' => $cat->term_taxonomy_id,
                    'name' => $cat->term->name ?? '',
                    'slug' => $cat->term->slug ?? '',
                    'description' => $cat->description,
                    'count' => $cat->count,
                    'url' => config('app.url') . '/category/' . ($cat->term->slug ?? ''),
                ];
            })
        ]);
    }
    
    /**
     * Получить одну категорию по ID или slug
     */
    public function show(string $id): JsonResponse
    {
        $category = TermTaxonomy::where('taxonomy', 'category')
            ->where(function($query) use ($id) {
                $query->where('term_taxonomy_id', $id)
                      ->orWhereHas('term', function($q) use ($id) {
                          $q->where('slug', $id);
                      });
            })
            ->with('term')
            ->first();
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->term_taxonomy_id,
                'name' => $category->term->name ?? '',
                'slug' => $category->term->slug ?? '',
                'description' => $category->description,
                'count' => $category->count,
                'parent' => $category->parent,
                'url' => config('app.url') . '/category/' . ($category->term->slug ?? ''),
            ]
        ]);
    }
}
