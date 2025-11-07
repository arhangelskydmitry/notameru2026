<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordPress\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * Получить список постов с пагинацией
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories.term', 'tags.term'])
            ->orderBy('post_date', 'desc');
        
        // Фильтрация по категории
        if ($request->has('category')) {
            $query->whereHas('categories.term', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        
        // Фильтрация по тегу
        if ($request->has('tag')) {
            $query->whereHas('tags.term', function($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }
        
        // Поиск
        if ($request->has('search')) {
            $query->where('post_title', 'like', '%' . $request->search . '%');
        }
        
        // Пагинация
        $perPage = min($request->get('per_page', 15), 100);
        $posts = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $posts->map(function($post) {
                return $this->formatPost($post);
            }),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
            'links' => [
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Получить один пост по ID или slug
     */
    public function show(string $id): JsonResponse
    {
        $post = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($query) use ($id) {
                $query->where('ID', $id)
                      ->orWhere('post_name', $id);
            })
            ->with(['author', 'categories.term', 'tags.term'])
            ->first();
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Пост не найден'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $this->formatPost($post, true)
        ]);
    }
    
    /**
     * Получить популярные посты
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories.term'])
            ->get()
            ->sortByDesc(function($post) {
                return (int) $post->getMeta('post_views_count', 0);
            })
            ->take($limit)
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $posts->map(function($post) {
                return $this->formatPost($post);
            })
        ]);
    }
    
    /**
     * Получить последние посты
     */
    public function latest(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $posts->map(function($post) {
                return $this->formatPost($post);
            })
        ]);
    }
    
    /**
     * Форматирование поста для API
     */
    private function formatPost(Post $post, bool $full = false): array
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        $thumbnail = null;
        
        if ($thumbnailId) {
            $attachment = Post::find($thumbnailId);
            if ($attachment) {
                $thumbnail = [
                    'id' => $attachment->ID,
                    'url' => $attachment->guid,
                    'title' => $attachment->post_title,
                ];
            }
        }
        
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'excerpt' => $post->post_excerpt,
            'date' => $post->post_date->toIso8601String(),
            'modified' => $post->post_modified->toIso8601String(),
            'author' => [
                'id' => $post->author?->ID,
                'name' => $post->author?->display_name,
                'url' => $post->author?->user_url,
            ],
            'categories' => $post->categories->map(function($cat) {
                return [
                    'id' => $cat->term_taxonomy_id,
                    'name' => $cat->term->name ?? '',
                    'slug' => $cat->term->slug ?? '',
                ];
            }),
            'tags' => $post->tags->map(function($tag) {
                return [
                    'id' => $tag->term_taxonomy_id,
                    'name' => $tag->term->name ?? '',
                    'slug' => $tag->term->slug ?? '',
                ];
            }),
            'thumbnail' => $thumbnail,
            'views' => (int) $post->getMeta('post_views_count', 0),
            'url' => config('app.url') . '/' . $post->post_name,
        ];
        
        if ($full) {
            $data['content'] = $post->post_content;
            $data['seo'] = [
                'title' => $post->getMeta('_yoast_wpseo_title'),
                'description' => $post->getMeta('_yoast_wpseo_metadesc'),
                'focus_keyword' => $post->getMeta('_yoast_wpseo_focuskw'),
            ];
        }
        
        return $data;
    }
}
