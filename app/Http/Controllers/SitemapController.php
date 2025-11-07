<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\WordPress\Post;
use App\Models\WordPress\TermTaxonomy;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * Генерация sitemap.xml (улучшенная версия)
     */
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $sitemap .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        
        // Главная страница
        $sitemap .= $this->addUrl(
            config('app.url'),
            now()->toW3cString(),
            '1.0',
            'daily'
        );
        
        // Опубликованные посты (с изображениями)
        $posts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('post_modified', 'desc')
            ->get();
        
        foreach ($posts as $post) {
            $url = config('app.url') . '/' . $post->post_name;
            $lastmod = $post->post_modified->toW3cString();
            
            // Приоритет зависит от давности публикации
            $daysOld = $post->post_modified->diffInDays(now());
            $priority = $this->calculatePriority($daysOld, 'post');
            
            // Частота обновления зависит от давности
            $changefreq = $this->calculateChangefreq($daysOld);
            
            // Получаем изображение
            $image = $this->getPostImage($post);
            
            $sitemap .= $this->addUrl($url, $lastmod, $priority, $changefreq, $image);
        }
        
        // Опубликованные страницы
        $pages = Post::where('post_type', 'page')
            ->where('post_status', 'publish')
            ->whereNotNull('post_name')
            ->orderBy('post_modified', 'desc')
            ->get();
        
        foreach ($pages as $page) {
            $url = config('app.url') . '/' . $page->post_name;
            $lastmod = $page->post_modified->toW3cString();
            $sitemap .= $this->addUrl($url, $lastmod, '0.7', 'monthly');
        }
        
        // Категории
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->where('count', '>', 0)
            ->with('term')
            ->get();
        
        foreach ($categories as $category) {
            if ($category->term) {
                $url = config('app.url') . '/category/' . $category->term->slug;
                $sitemap .= $this->addUrl($url, now()->toW3cString(), '0.6', 'weekly');
            }
        }
        
        // Теги (топ-20)
        $tags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->where('count', '>', 0)
            ->orderBy('count', 'desc')
            ->limit(20)
            ->with('term')
            ->get();
        
        foreach ($tags as $tag) {
            if ($tag->term) {
                $url = config('app.url') . '/tag/' . $tag->term->slug;
                $sitemap .= $this->addUrl($url, now()->toW3cString(), '0.5', 'weekly');
            }
        }
        
        $sitemap .= '</urlset>';
        
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
    
    /**
     * Добавление URL в sitemap
     */
    private function addUrl($loc, $lastmod, $priority, $changefreq, $image = null)
    {
        $url = '<url>';
        $url .= '<loc>' . htmlspecialchars($loc) . '</loc>';
        $url .= '<lastmod>' . $lastmod . '</lastmod>';
        $url .= '<priority>' . $priority . '</priority>';
        $url .= '<changefreq>' . $changefreq . '</changefreq>';
        
        // Добавляем изображение если есть
        if ($image) {
            $url .= '<image:image>';
            $url .= '<image:loc>' . htmlspecialchars($image['url']) . '</image:loc>';
            if ($image['title']) {
                $url .= '<image:title>' . htmlspecialchars($image['title']) . '</image:title>';
            }
            $url .= '</image:image>';
        }
        
        $url .= '</url>';
        
        return $url;
    }
    
    /**
     * Получить изображение поста для sitemap
     */
    private function getPostImage($post)
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        if (!$thumbnailId) {
            return null;
        }
        
        $attachment = Post::find($thumbnailId);
        if (!$attachment) {
            return null;
        }
        
        return [
            'url' => $attachment->guid,
            'title' => $post->post_title,
        ];
    }
    
    /**
     * Рассчитать приоритет на основе давности публикации
     */
    private function calculatePriority($daysOld, $type = 'post')
    {
        if ($type === 'post') {
            if ($daysOld <= 7) {
                return '0.9'; // Свежие статьи (неделя)
            } elseif ($daysOld <= 30) {
                return '0.8'; // Месяц
            } elseif ($daysOld <= 90) {
                return '0.7'; // Квартал
            } else {
                return '0.6'; // Старые
            }
        }
        
        return '0.7';
    }
    
    /**
     * Рассчитать частоту изменений
     */
    private function calculateChangefreq($daysOld)
    {
        if ($daysOld <= 1) {
            return 'hourly'; // Сегодня
        } elseif ($daysOld <= 7) {
            return 'daily'; // Неделя
        } elseif ($daysOld <= 30) {
            return 'weekly'; // Месяц
        } else {
            return 'monthly'; // Старше месяца
        }
    }
    
    /**
     * Robots.txt (улучшенный)
     */
    public function robots()
    {
        $robots = "# Robots.txt for " . config('app.name') . "\n\n";
        
        // Основные правила
        $robots .= "User-agent: *\n";
        $robots .= "Allow: /\n\n";
        
        // Запреты
        $robots .= "# Админ-панель\n";
        $robots .= "Disallow: /notaadmin/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /wp-admin/\n\n";
        
        $robots .= "# WordPress технические директории\n";
        $robots .= "Disallow: /wp-content/plugins/\n";
        $robots .= "Disallow: /wp-content/cache/\n";
        $robots .= "Disallow: /wp-content/themes/\n";
        $robots .= "Disallow: /wp-includes/\n\n";
        
        $robots .= "# API\n";
        $robots .= "Disallow: /api/\n\n";
        
        $robots .= "# Разрешаем изображения\n";
        $robots .= "Allow: /imgnews/\n";
        $robots .= "Allow: /wp-content/uploads/\n\n";
        
        // Специальные агенты
        $robots .= "# Яндекс\n";
        $robots .= "User-agent: Yandex\n";
        $robots .= "Allow: /\n";
        $robots .= "Crawl-delay: 1\n\n";
        
        $robots .= "# Google\n";
        $robots .= "User-agent: Googlebot\n";
        $robots .= "Allow: /\n\n";
        
        $robots .= "User-agent: Googlebot-Image\n";
        $robots .= "Allow: /imgnews/\n";
        $robots .= "Allow: /wp-content/uploads/\n\n";
        
        // Sitemap
        $robots .= "# Sitemap\n";
        $robots .= "Sitemap: " . config('app.url') . "/sitemap.xml\n";
        
        return response($robots, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
    
    /**
     * Статистика sitemap (для админки)
     */
    public function stats()
    {
        $stats = [
            'posts' => Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->count(),
            'pages' => Post::where('post_type', 'page')
                ->where('post_status', 'publish')
                ->whereNotNull('post_name')
                ->count(),
            'categories' => TermTaxonomy::where('taxonomy', 'category')
                ->where('count', '>', 0)
                ->count(),
            'tags' => TermTaxonomy::where('taxonomy', 'post_tag')
                ->where('count', '>', 0)
                ->count(),
        ];
        
        $stats['total'] = $stats['posts'] + $stats['pages'] + $stats['categories'] + $stats['tags'] + 1; // +1 главная
        
        return $stats;
    }
}
