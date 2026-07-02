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

class TaxonomyAdminController extends Controller
{

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
}
