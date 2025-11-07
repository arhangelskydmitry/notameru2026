<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WordPress\Post;
use App\Models\WordPress\TermTaxonomy;

class FrontendController extends Controller
{
    /**
     * Главная страница с ленивой загрузкой
     */
    public function index()
    {
        // Получаем общее количество постов
        $totalPosts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();
        
        // Загружаем первые 9 постов для начальной страницы (5 для слайдера + 4 в сетке)
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories.term', 'tags.term'])
            ->orderBy('post_date', 'desc')
            ->limit(9)
            ->get();
        
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->where('count', '>', 0)
            ->with('term')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return view('frontend.index', compact('posts', 'categories', 'totalPosts'));
    }
    
    /**
     * AJAX загрузка дополнительных постов
     */
    public function loadMorePosts(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 6);
        
        // Базовый запрос
        $query = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories.term', 'tags.term']);
        
        // Фильтр по категории
        if ($categoryId = $request->input('category')) {
            $query->whereHas('categories', function($q) use ($categoryId) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $categoryId);
            });
        }
        
        // Фильтр по тегу
        if ($tagId = $request->input('tag')) {
            $query->whereHas('tags', function($q) use ($tagId) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $tagId);
            });
        }
        
        // Фильтр по автору
        if ($authorId = $request->input('author')) {
            $query->where('post_author', $authorId);
        }
        
        // Фильтр по поиску
        if ($searchQuery = $request->input('search')) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('post_title', 'like', '%' . $searchQuery . '%')
                  ->orWhere('post_content', 'like', '%' . $searchQuery . '%');
            });
        }
        
        // Получаем общее количество для текущего фильтра
        $totalPosts = $query->count();
        
        // Получаем посты
        $posts = $query->orderBy('post_date', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
        
        // Генерируем HTML для каждого поста
        $html = '';
        foreach ($posts as $post) {
            $html .= view('partials.post-card', compact('post'))->render();
        }
        
        return response()->json([
            'html' => $html,
            'hasMore' => ($offset + $limit) < $totalPosts
        ]);
    }
    
    /**
     * Страница одного поста
     */
    public function post(string $slug)
    {
        $post = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_name', $slug)
            ->with(['author', 'categories.term', 'tags.term'])
            ->firstOrFail();
        
        // Записываем просмотр
        \App\Models\PostView::recordView($post, request());
        \App\Models\SiteVisitor::recordVisit(request());
        
        // Похожие посты (по первой категории)
        $relatedPosts = [];
        if ($post->categories->isNotEmpty()) {
            $firstCategory = $post->categories->first();
            $relatedPosts = Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->where('ID', '!=', $post->ID)
                ->whereHas('categories', function($q) use ($firstCategory) {
                    $q->where('wp_term_taxonomy.term_taxonomy_id', $firstCategory->term_taxonomy_id);
                })
                ->orderBy('post_date', 'desc')
                ->limit(5)
                ->get();
        }
        
        return view('frontend.post', compact('post', 'relatedPosts'));
    }
    
    /**
     * Архив категории
     */
    public function category(string $slug)
    {
        $category = TermTaxonomy::where('taxonomy', 'category')
            ->whereHas('term', function($q) use ($slug) {
                $q->where('slug', $slug);
            })
            ->with('term')
            ->firstOrFail();
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->whereHas('categories', function($q) use ($category) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $category->term_taxonomy_id);
            })
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        return view('frontend.category', compact('category', 'posts'));
    }
    
    /**
     * Архив тега
     */
    public function tag(string $slug)
    {
        $tag = TermTaxonomy::where('taxonomy', 'post_tag')
            ->whereHas('term', function($q) use ($slug) {
                $q->where('slug', $slug);
            })
            ->with('term')
            ->firstOrFail();
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->whereHas('tags', function($q) use ($tag) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $tag->term_taxonomy_id);
            })
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        return view('frontend.tag', compact('tag', 'posts'));
    }
    
    /**
     * Поиск
     */
    public function search(Request $request)
    {
        $query = $request->get('s', '');
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($q) use ($query) {
                $q->where('post_title', 'like', '%' . $query . '%')
                  ->orWhere('post_content', 'like', '%' . $query . '%');
            })
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        return view('frontend.search', compact('posts', 'query'));
    }
    
    /**
     * Страница автора
     */
    public function author($id)
    {
        $author = \App\Models\WordPress\User::findOrFail($id);
        
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_author', $id)
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        return view('frontend.author', compact('author', 'posts'));
    }
    
    /**
     * Страница WordPress
     */
    public function page(string $slug)
    {
        $page = Post::where('post_type', 'page')
            ->where('post_status', 'publish')
            ->where('post_name', $slug)
            ->with(['author'])
            ->firstOrFail();
        
        return view('frontend.page', compact('page'));
    }
    
    /**
     * API: Умные подсказки для поиска
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        // Ищем посты по заголовку
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where(function($q) use ($query) {
                $q->where('post_title', 'LIKE', "%{$query}%")
                  ->orWhere('post_content', 'LIKE', "%{$query}%");
            })
            ->orderBy('post_date', 'desc')
            ->limit(8)
            ->get();
        
        $suggestions = $posts->map(function($post) {
            // Получаем изображение
            $thumbnailId = $post->getMeta('_thumbnail_id');
            $thumbnail = null;
            
            if ($thumbnailId) {
                $attachment = Post::find($thumbnailId);
                if ($attachment && $attachment->guid) {
                    $path = $attachment->guid;
                    // Конвертируем путь к WebP
                    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $path)) {
                        $filename = basename($path);
                        $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                        $thumbnail = url('/imgnews/' . $filename);
                    } else {
                        $thumbnail = $path;
                    }
                }
            }
            
            return [
                'title' => $post->post_title,
                'url' => route('post', $post->post_name),
                'image' => $thumbnail,
                'date' => $post->post_date->format('d.m.Y'),
                'views' => $post->getMeta('post_views_count', 0),
            ];
        });
        
        return response()->json($suggestions);
    }
}

