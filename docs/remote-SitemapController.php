<?php

namespace App\Http\Controllers;

use App\Models\WordPress\Post;
use App\Models\WordPress\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Генерация и отдача sitemap.xml
     */
    public function index()
    {
        $sitemap = Cache::remember('sitemap_xml', 3600, function () {
            return $this->generateSitemap();
        });
        
        return response($sitemap)
            ->header('Content-Type', 'application/xml');
    }
    
    /**
     * Просмотр sitemap в админке
     */
    public function admin()
    {
        $sitemap = $this->generateSitemap();
        Cache::put('sitemap_xml', $sitemap, 3600);
        $stats = $this->getSitemapStats($sitemap);
        
        return view('admin.sitemap', compact('stats', 'sitemap'));
    }
    
    /**
     * Регенерация sitemap (очистка кеша)
     */
    public function regenerate(Request $request)
    {
        Cache::forget('sitemap_xml');
        
        // Логируем только если пользователь авторизован
        if (auth()->check()) {
            try {
                \App\Models\ActivityLog::log(
                    'sitemap_regenerated',
                    null,
                    null,
                    'Sitemap был перегенерирован'
                );
            } catch (\Exception $e) {
                // Игнорируем ошибки логирования
                \Log::warning('Failed to log sitemap regeneration: ' . $e->getMessage());
            }
        }
        
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sitemap успешно обновлен',
                'stats' => $this->getSitemapStats()
            ]);
        }
        
        return redirect()->route('admin.sitemap')->with('success', 'Sitemap успешно обновлен');
    }
    
    /**
     * Генерация XML содержимого sitemap
     */
    protected function generateSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Главная страница
        $xml .= $this->addUrl(url('/'), now(), '1.0', 'daily');
        
        // Статьи
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('post_modified', 'desc')
            ->get();
        
        foreach ($posts as $post) {
            $xml .= $this->addUrl(
                route('post', $post->post_name),
                $post->post_modified,
                '0.8',
                'weekly'
            );
        }
        
        // Страницы
        $pages = Post::where('post_type', 'page')
            ->where('post_status', 'publish')
            ->orderBy('post_modified', 'desc')
            ->get();
        
        foreach ($pages as $page) {
            $xml .= $this->addUrl(
                route('post', $page->post_name),
                $page->post_modified,
                '0.6',
                'monthly'
            );
        }
        
        // Категории
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->whereHas('term')
            ->with('term')
            ->get();
        
        foreach ($categories as $category) {
            $xml .= $this->addUrl(
                route('category', $category->term->slug),
                now(),
                '0.7',
                'weekly'
            );
        }
        
        // Архивы по годам (последние 3 года)
        $years = range(date('Y'), date('Y') - 2);
        foreach ($years as $year) {
            $xml .= $this->addUrl(
                route('posts.by-year', $year),
                now(),
                '0.5',
                'monthly'
            );
        }
        
        // HTML Sitemap
        $xml .= $this->addUrl(
            route('sitemap.html'),
            now(),
            '0.5',
            'weekly'
        );
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Добавление URL в sitemap
     */
    protected function addUrl(string $loc, $lastmod, string $priority, string $changefreq): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "  </url>\n";
        
        return $xml;
    }
    
    /**
     * Получение статистики sitemap
     */
    public function getSitemapStats(?string $sitemapContent = null): array
    {
        if (!$sitemapContent) {
            $sitemapContent = Cache::get('sitemap_xml');
        }
        
        if (!$sitemapContent) {
            $sitemapContent = $this->generateSitemap();
            Cache::put('sitemap_xml', $sitemapContent, 3600);
        }
        
        return [
            'total' => 1 + // Главная
                Post::where('post_type', 'post')->where('post_status', 'publish')->count() +
                Post::where('post_type', 'page')->where('post_status', 'publish')->count() +
                TermTaxonomy::where('taxonomy', 'category')->whereHas('term')->count() +
                3 + // Года
                1, // HTML Sitemap
            'posts' => Post::where('post_type', 'post')->where('post_status', 'publish')->count(),
            'pages' => Post::where('post_type', 'page')->where('post_status', 'publish')->count(),
            'categories' => TermTaxonomy::where('taxonomy', 'category')->whereHas('term')->count(),
            'last_updated' => Cache::has('sitemap_xml') ? 'В кеше' : 'Новый',
            'file_size' => strlen($sitemapContent),
        ];
    }
    
    /**
     * Публичный метод для получения статистики (алиас для использования в views)
     */
    public function stats(): array
    {
        return $this->getSitemapStats();
    }
    
    /**
     * Генерация robots.txt
     */
    public function robots()
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /notaadmin/\n";
        $content .= "Disallow: /vendor/\n";
        $content .= "\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
        
        return response($content)
            ->header('Content-Type', 'text/plain');
    }
}
