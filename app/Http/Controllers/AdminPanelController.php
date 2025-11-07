<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminPanelController extends Controller
{
    /**
     * Главная страница админ панели
     */
    public function dashboard()
    {
        // Базовая статистика
        $stats = [
            'posts' => Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->count(),
            'pages' => Post::where('post_type', 'page')
                ->where('post_status', 'publish')
                ->count(),
            'categories' => TermTaxonomy::where('taxonomy', 'category')->count(),
            'comments' => DB::table('wp_comments')->count(),
        ];
        
        // Статистика посетителей
        $visitorStats = \App\Models\SiteVisitor::getTotalStatistics();
        
        // Топ статей за неделю
        $topWeekPosts = \App\Models\PostView::getTopPosts('week', 10);
        
        // Топ статей за год
        $topYearPosts = \App\Models\PostView::getTopPosts('year', 10);
        
        // Статистика просмотров за последние 30 дней
        $viewStatistics = \App\Models\PostView::getViewStatistics(
            now()->subDays(30),
            now()
        );
        
        // Статистика посетителей за последние 30 дней
        $dailyStatistics = \App\Models\SiteVisitor::getDailyStatistics(
            now()->subDays(30)->toDateString(),
            now()->toDateString()
        );
        
        return view('admin.dashboard', compact(
            'stats',
            'visitorStats',
            'topWeekPosts',
            'topYearPosts',
            'viewStatistics',
            'dailyStatistics'
        ));
    }

    /**
     * Список постов
     */
    public function posts()
    {
        $posts = Post::where('post_type', 'post')
            ->orderBy('post_date', 'desc')
            ->paginate(50); // Изменено с 20 на 50
        
        $stats = [
            'total' => Post::where('post_type', 'post')->count(),
            'published' => Post::where('post_type', 'post')->where('post_status', 'publish')->count(),
            'draft' => Post::where('post_type', 'post')->where('post_status', 'draft')->count(),
        ];
        
        return view('admin.posts', compact('posts', 'stats'));
    }

    /**
     * Форма редактирования поста
     */
    public function editPost($id)
    {
        $post = Post::findOrFail($id);
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        
        return view('admin.post-edit', compact('post', 'categories'));
    }

    /**
     * Обновление поста
     */
    public function updatePost(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        
        $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_excerpt' => 'nullable|string',
            'post_status' => 'required|in:publish,draft,pending',
            'category_ids' => 'nullable|array',
        ]);
        
        $post->update([
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
            'og_type' => $request->input('og_type', 'article'),
            // Twitter Card
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
        ]);
        
        // Обновляем категории
        if (isset($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }
        
        return redirect()->route('admin.posts')->with('success', 'Пост успешно обновлен!');
    }

    /**
     * Удаление поста
     */
    public function deletePost($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();
        
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
        $page = Post::where('post_type', 'page')->findOrFail($id);
        
        return view('admin.page-edit', compact('page'));
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
        ]);
        
        return redirect()->route('admin.pages')->with('success', 'Страница успешно обновлена!');
    }

    /**
     * Удаление страницы
     */
    public function deletePage($id)
    {
        $page = Post::where('post_type', 'page')->findOrFail($id);
        $page->delete();
        
        return redirect()->route('admin.pages')->with('success', 'Страница удалена!');
    }
}

