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

class PageAdminController extends Controller
{

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
        $page = Post::with(['seo'])->where('post_type', 'page')->findOrFail($id);
        
        // Получаем featured image
        $featuredImage = \App\Helpers\ContentHelper::getFeaturedImage($page);
        
        return view('admin.page-edit', compact('page', 'featuredImage'));
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
            'og_type' => $request->input('og_type', 'website'),
            // Twitter Card
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
        ]);
        
        // Логируем изменение
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            Post::class,
            $page->ID,
            "Обновлена страница: {$page->post_title}"
        );
        
        return redirect()->route('admin.pages')->with('success', 'Страница успешно обновлена!');
    }


    /**
     * Удаление страницы
     */
    public function deletePage($id)
    {
        $page = Post::where('post_type', 'page')->findOrFail($id);
        $page->delete();
        
        // Логируем удаление
        admin_log(
            \App\Models\ActivityLog::ACTION_DELETED,
            Post::class,
            $id,
            "Удалена страница: {$page->post_title}"
        );
        
        return redirect()->route('admin.pages')->with('success', 'Страница удалена!');
    }
}
