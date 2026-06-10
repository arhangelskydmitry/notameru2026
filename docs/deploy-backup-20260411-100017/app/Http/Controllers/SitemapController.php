<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SitemapController extends Controller
{
    protected string $sitemapCacheStore = 'file';
    protected string $sitemapPublicPath = 'sitemap.xml';
    protected string $sitemapDataConnection = 'sitemap';

    /**
     * Генерация и отдача sitemap.xml
     */
    public function index()
    {
        if ($this->hasStaticSitemap()) {
            return response(File::get($this->getStaticSitemapPath()))
                ->header('Content-Type', 'application/xml');
        }

        $cache = Cache::store($this->sitemapCacheStore);
        $sitemap = $cache->remember('sitemap_xml', 3600, function () {
            return $this->generateAndStoreSitemap();
        });
        
        return response($sitemap)
            ->header('Content-Type', 'application/xml');
    }
    
    /**
     * Просмотр sitemap в админке
     */
    public function admin()
    {
        $sitemap = $this->generateAndStoreSitemap();
        Cache::store($this->sitemapCacheStore)->put('sitemap_xml', $sitemap, 3600);
        $stats = $this->getSitemapStats($sitemap);
        
        return view('admin.sitemap', compact('stats', 'sitemap'));
    }
    
    /**
     * Регенерация sitemap (очистка кеша)
     */
    public function regenerate(Request $request)
    {
        Cache::store($this->sitemapCacheStore)->forget('sitemap_xml');
        $sitemap = $this->generateAndStoreSitemap();
        Cache::store($this->sitemapCacheStore)->put('sitemap_xml', $sitemap, 3600);
        
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
                'stats' => $this->getSitemapStats($sitemap)
            ]);
        }
        
        return redirect()->route('admin.sitemap')->with('success', 'Sitemap успешно обновлен');
    }

    /**
     * Сгенерировать sitemap и сохранить готовый XML в public.
     */
    public function generateAndStoreSitemap(): string
    {
        $sitemap = $this->generateSitemap();
        File::put($this->getStaticSitemapPath(), $sitemap);

        return $sitemap;
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
        $posts = $this->sitemapDb()->table('wp_posts')
            ->select('post_name', 'post_modified')
            ->where('post_status', 'publish')
            ->where('post_type', 'post')
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
        $pages = $this->sitemapDb()->table('wp_posts')
            ->select('post_name', 'post_modified')
            ->where('post_status', 'publish')
            ->where('post_type', 'page')
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
        $categories = $this->sitemapDb()->table('wp_term_taxonomy as taxonomy')
            ->join('wp_terms as terms', 'terms.term_id', '=', 'taxonomy.term_id')
            ->select('terms.slug')
            ->where('taxonomy.taxonomy', 'category')
            ->get();
        
        foreach ($categories as $category) {
            $xml .= $this->addUrl(
                route('category', $category->slug),
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
        
        // Статические служебные страницы
        foreach ($this->getStaticSitemapRoutes() as $page) {
            $xml .= $this->addUrl(
                $page['loc'],
                $page['lastmod'] ?? now(),
                $page['priority'] ?? '0.5',
                $page['changefreq'] ?? 'monthly'
            );
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Добавление URL в sitemap
     */
    protected function addUrl(string $loc, $lastmod, string $priority, string $changefreq): string
    {
        $timestamp = is_numeric($lastmod)
            ? (int) $lastmod
            : strtotime((string) $lastmod);

        if ($timestamp === false || $timestamp <= 0) {
            $timestamp = now()->timestamp;
        }

        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d', $timestamp) . "</lastmod>\n";
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
        $cache = Cache::store($this->sitemapCacheStore);

        if (!$sitemapContent) {
            if ($this->hasStaticSitemap()) {
                $sitemapContent = File::get($this->getStaticSitemapPath());
            } else {
                $sitemapContent = $cache->get('sitemap_xml');
            }
        }
        
        if (!$sitemapContent) {
            $sitemapContent = $this->generateAndStoreSitemap();
            $cache->put('sitemap_xml', $sitemapContent, 3600);
        }
        
        $posts = $this->sitemapDb()->table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();
        $pages = $this->sitemapDb()->table('wp_posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->count();
        $categories = $this->sitemapDb()->table('wp_term_taxonomy as taxonomy')
            ->join('wp_terms as terms', 'terms.term_id', '=', 'taxonomy.term_id')
            ->where('taxonomy.taxonomy', 'category')
            ->count();
        $total = 1 + $posts + $pages + $categories + 3 + count($this->getStaticSitemapRoutes());

        return [
            'total' => $total,
            'total_urls' => $total,
            'posts' => $posts,
            'pages' => $pages,
            'categories' => $categories,
            'last_updated' => $this->hasStaticSitemap() ? date('Y-m-d H:i:s', File::lastModified($this->getStaticSitemapPath())) : ($cache->has('sitemap_xml') ? 'В кеше' : 'Новый'),
            'file_size' => strlen($sitemapContent),
        ];
    }

    protected function getStaticSitemapRoutes(): array
    {
        return [
            [
                'loc' => route('sitemap.html'),
                'priority' => '0.5',
                'changefreq' => 'weekly',
            ],
            [
                'loc' => route('editorial'),
                'priority' => '0.4',
                'changefreq' => 'monthly',
            ],
            [
                'loc' => route('advertising'),
                'priority' => '0.4',
                'changefreq' => 'monthly',
            ],
            [
                'loc' => route('privacy'),
                'priority' => '0.3',
                'changefreq' => 'yearly',
            ],
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

    protected function getStaticSitemapPath(): string
    {
        return public_path($this->sitemapPublicPath);
    }

    protected function hasStaticSitemap(): bool
    {
        return File::exists($this->getStaticSitemapPath());
    }

    protected function sitemapDb()
    {
        return DB::connection($this->sitemapDataConnection);
    }
}
