<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\WordPress\Post;
use App\Models\WordPress\TermTaxonomy;

class FrontendController extends Controller
{
    /**
     * Возвращает JPEG-версию изображения для соцсетей (Telegram/VK/MAX).
     */
    public function socialImageJpg(Request $request)
    {
        $src = trim((string) $request->query('src', ''));
        if ($src === '') {
            abort(404);
        }

        $path = parse_url($src, PHP_URL_PATH) ?: '';
        $host = parse_url($src, PHP_URL_HOST);

        if ($host && ! in_array(mb_strtolower($host), ['notame.ru', 'www.notame.ru'], true)) {
            abort(404);
        }

        if ($path === '' || ! str_starts_with($path, '/')) {
            abort(404);
        }

        if (str_contains($path, '..')) {
            abort(404);
        }

        if (! Str::startsWith($path, ['/imgnews/', '/images/'])) {
            abort(404);
        }

        $sourceFile = public_path(ltrim($path, '/'));
        if (! is_file($sourceFile)) {
            abort(404);
        }

        $sourceExt = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
        if (! in_array($sourceExt, ['webp', 'jpg', 'jpeg', 'png'], true)) {
            abort(404);
        }

        $cacheDir = storage_path('app/social-jpg-cache');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $cacheKey = md5($sourceFile . '|' . @filemtime($sourceFile) . '|q88');
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.jpg';

        if (! is_file($cacheFile)) {
            $image = match ($sourceExt) {
                'webp' => @imagecreatefromwebp($sourceFile),
                'png' => @imagecreatefrompng($sourceFile),
                default => @imagecreatefromjpeg($sourceFile),
            };

            if (! $image) {
                abort(404);
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $canvas = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
            imagejpeg($canvas, $cacheFile, 88);

            imagedestroy($image);
            imagedestroy($canvas);
        }

        $jpgContent = @file_get_contents($cacheFile);
        if ($jpgContent === false) {
            abort(404);
        }

        return response($jpgContent, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Главная страница с ленивой загрузкой
     */
    public function index()
    {
        // Получаем общее количество постов
        $totalPosts = Post::publiclyVisible()->count();
        
        // Загружаем первые 9 постов для начальной страницы (5 для слайдера + 4 в сетке)
        $posts = Post::publiclyVisible()
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
        $query = Post::publiclyVisible()
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
        // Asset-пути иногда проваливаются в catch-all роут и не должны идти в БД.
        if (str_contains($slug, '/') || preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico|css|js)$/i', $slug)) {
            abort(404);
        }

        // Сначала пробуем найти как пост
        $post = Post::publiclyVisible()
            ->where('post_name', $slug)
            ->with(['author', 'categories.term', 'tags.term'])
            ->first();
        
        // Если не нашли пост, ищем страницу
        if (!$post) {
            $page = Post::where('post_type', 'page')
                ->where('post_status', 'publish')
                ->where('post_name', $slug)
                ->with(['author'])
                ->first();
            
            if ($page) {
                return view('frontend.page', compact('page'));
            }
            
            abort(404);
        }
        
        // Записываем просмотр
        \App\Models\PostView::recordView($post, request());
        \App\Models\SiteVisitor::recordVisit(request());
        
        // Похожие посты (по первой категории)
        $relatedPosts = [];
        if ($post->categories->isNotEmpty()) {
            $firstCategory = $post->categories->first();
            $relatedPosts = Post::publiclyVisible()
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
     * Legacy gorod-magazine article URL:
     * /{category}/{year}/{month}/{day}/{slug}/
     */
    public function legacyPost(string $category, string $year, string $month, string $day, string $slug)
    {
        $post = Post::publiclyVisible()
            ->where('post_name', $slug)
            ->whereDate('post_date', sprintf('%s-%s-%s', $year, $month, $day))
            ->with(['categories.term'])
            ->first();

        if (! $post) {
            abort(404);
        }

        $matchesCategory = $post->categories->contains(function ($taxonomy) use ($category) {
            return optional($taxonomy->term)->slug === $category;
        });

        if (! $matchesCategory) {
            abort(404);
        }

        return $this->post($slug);
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
        
        $posts = Post::publiclyVisible()
            ->whereHas('categories', function($q) use ($category) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $category->term_taxonomy_id);
            })
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        // Генерируем уникальное описание для категории
        $description = $this->generateCategoryDescription($category, $posts->count());
        
        return view('frontend.category', compact('category', 'posts', 'description'));
    }
    
    /**
     * Генерация уникального описания для категории
     */
    protected function generateCategoryDescription($category, $postsCount): string
    {
        $categoryName = $category->term->name;
        
        // Используем description из базы, если есть И она достаточно длинная
        if ($category->description && strlen(trim($category->description)) >= 150) {
            return trim($category->description);
        }
        
        // Генерируем расширенное динамическое описание (150+ символов)
        $templates = [
            "Читайте свежие новости и статьи в категории «{$categoryName}» на портале Нота Миру. У нас собраны {$postsCount} актуальных публикаций с экспертными мнениями, репортажами и эксклюзивными материалами от профессиональных журналистов.",
            "Последние новости из раздела «{$categoryName}» - только проверенная информация и авторские материалы. Всего {$postsCount} статей о самых важных событиях, тенденциях и перспективах развития индустрии.",
            "Все материалы категории «{$categoryName}» на Нота Миру: {$postsCount} публикаций с глубокой аналитикой, интересными репортажами и интервью с известными личностями. Оставайтесь в курсе актуальных событий.",
            "Новости и статьи по теме «{$categoryName}» - {$postsCount} материалов с актуальной информацией, экспертными комментариями и подробным разбором важнейших событий в мире музыки, культуры и шоу-бизнеса.",
            "Актуальные публикации в категории «{$categoryName}»: {$postsCount} статей от наших опытных авторов и экспертов индустрии. Узнавайте первыми о главных событиях, трендах и интересных фактах."
        ];
        
        // Выбираем шаблон на основе ID категории (для стабильности)
        $index = $category->term_taxonomy_id % count($templates);
        return $templates[$index];
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
        
        $posts = Post::publiclyVisible()
            ->whereHas('tags', function($q) use ($tag) {
                $q->where('wp_term_taxonomy.term_taxonomy_id', $tag->term_taxonomy_id);
            })
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(15)
            ->get();
        
        // Генерируем уникальное описание для тега
        $description = $this->generateTagDescription($tag, $posts->count());
        
        return view('frontend.tag', compact('tag', 'posts', 'description'));
    }
    
    /**
     * Генерация уникального описания для тега
     */
    protected function generateTagDescription($tag, $postsCount): string
    {
        $tagName = $tag->term->name;
        
        // Генерируем расширенное динамическое описание (150+ символов)
        $templates = [
            "Все статьи по тегу «{$tagName}» на портале Нота Миру - {$postsCount} публикаций с подробной информацией, актуальными новостями и экспертными комментариями. Читайте свежие материалы от профессиональных журналистов.",
            "Материалы с тегом «{$tagName}»: {$postsCount} статей от наших авторов и признанных экспертов индустрии. Глубокая аналитика, интересные факты и самые важные события в мире музыки и культуры.",
            "Все публикации по теме «{$tagName}» - {$postsCount} качественных материалов с детальным анализом, экспертными мнениями и комментариями специалистов. Оставайтесь в курсе последних новостей и трендов.",
            "Читайте статьи с тегом «{$tagName}»: {$postsCount} публикаций на актуальные темы с профессиональным подходом к освещению событий. Узнавайте первыми о важных новостях в сфере музыки и шоу-бизнеса.",
            "Новости и статьи по теме «{$tagName}» - {$postsCount} материалов для вашего ознакомления с глубоким погружением в тему, интервью с экспертами и подробным разбором актуальных событий индустрии."
        ];
        
        // Выбираем шаблон на основе ID тега (для стабильности)
        $index = $tag->term_taxonomy_id % count($templates);
        return $templates[$index];
    }
    
    /**
     * Поиск
     */
    public function search(Request $request)
    {
        $query = trim((string) ($request->get('q', $request->get('s', ''))));

        if ($query === '') {
            return view('frontend.search', [
                'posts' => collect(),
                'query' => '',
            ]);
        }
        
        $posts = Post::publiclyVisible()
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
        
        $posts = Post::publiclyVisible()
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
     * Редакция и контакты
     */
    public function editorialContacts()
    {
        return view('frontend.editorial');
    }

    /**
     * Реклама и схема баннерных размещений
     */
    public function advertising()
    {
        $placements = [
            [
                'zone' => 'header',
                'title' => 'Шапка сайта',
                'size' => '728x90',
                'pages' => 'Главная, категории, статьи и служебные страницы',
                'note' => 'Максимальный общий охват и заметность при первом экране.',
            ],
            [
                'zone' => 'sidebar-top',
                'title' => 'Сайдбар, верхний блок',
                'size' => '300x250',
                'pages' => 'Главная, рубрики и публикации с сайдбаром',
                'note' => 'Подходит для промо-кампаний, афиш, релизов и событий.',
            ],
            [
                'zone' => 'sidebar-middle',
                'title' => 'Сайдбар, средний блок',
                'size' => '300x250',
                'pages' => 'Длинные материалы и внутренние страницы',
                'note' => 'Хороший формат для дополнительного касания и ремаркетинга.',
            ],
            [
                'zone' => 'content-top',
                'title' => 'Над контентом статьи',
                'size' => '728x90',
                'pages' => 'Страницы материалов',
                'note' => 'Нативная точка входа перед чтением основного текста.',
            ],
            [
                'zone' => 'content-middle',
                'title' => 'Внутри материала',
                'size' => '336x280',
                'pages' => 'Страницы статей',
                'note' => 'Лучший вариант для заметности в процессе чтения.',
            ],
            [
                'zone' => 'footer',
                'title' => 'Подвал сайта',
                'size' => '728x90',
                'pages' => 'Все страницы сайта',
                'note' => 'Подходит для постоянного имиджевого присутствия.',
            ],
        ];

        $targeting = [
            'Главная страница',
            'Страницы рубрик',
            'Страницы публикаций',
            'Прочие служебные страницы',
        ];

        $formats = [
            'Графический баннер с переходом по ссылке',
            'HTML-код рекламного блока',
            'JS-код сторонней рекламной системы',
            'Запуск по датам с ограничением периода размещения',
            'Открытие ссылки в новой вкладке и передача UTM-меток',
        ];

        return view('frontend.advertising', compact('placements', 'targeting', 'formats'));
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
        $posts = Post::publiclyVisible()
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
    
    /**
     * Посты по конкретной дате
     */
    public function postsByDate(string $date)
    {
        // Валидация формата даты
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404);
        }
        
        // Получаем посты за указанную дату
        $posts = Post::publiclyVisible()
            ->whereDate('post_date', $date)
            ->with(['author', 'categories.term', 'tags.term'])
            ->orderBy('post_date', 'desc')
            ->paginate(20);
        
        // Форматируем дату для отображения
        $formattedDate = \Carbon\Carbon::parse($date)->locale('ru')->isoFormat('D MMMM YYYY');
        
        return view('frontend.posts-by-date', [
            'posts' => $posts,
            'date' => $date,
            'formattedDate' => $formattedDate,
            'description' => $this->generateDateDescription($formattedDate, $posts->total()),
        ]);
    }
    
    /**
     * HTML карта сайта с иерархией год/месяц/день
     */
    public function htmlSitemap()
    {
        // Получаем годы с количеством постов
        $years = Post::publiclyVisible()
            ->selectRaw('YEAR(post_date) as year, COUNT(*) as count')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();
        
        return view('frontend.sitemap', compact('years'));
    }
    
    /**
     * API для получения месяцев года
     */
    public function getSitemapMonths($year)
    {
        $months = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->selectRaw('MONTH(post_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function($item) use ($year) {
                $monthNames = [
                    1 => 'Январь', 2 => 'Февраль', 3 => 'Март',
                    4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
                    7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
                    10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
                ];
                return [
                    'month' => $item->month,
                    'month_name' => $monthNames[$item->month],
                    'count' => $item->count,
                    'url' => route('posts.by-year-month', ['year' => $year, 'month' => sprintf('%02d', $item->month)])
                ];
            });
        
        return response()->json($months);
    }
    
    /**
     * API для получения дней месяца
     */
    public function getSitemapDays($year, $month)
    {
        $days = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->whereMonth('post_date', $month)
            ->selectRaw('DAY(post_date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->get()
            ->map(function($item) use ($year, $month) {
                return [
                    'day' => $item->day,
                    'count' => $item->count,
                    'url' => route('posts.by-date', ['date' => sprintf('%04d-%02d-%02d', $year, $month, $item->day)])
                ];
            });
        
        return response()->json($days);
    }
    
    /**
     * API для получения постов дня
     */
    public function getSitemapPosts($year, $month, $day)
    {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        
        $posts = Post::publiclyVisible()
            ->whereDate('post_date', $date)
            ->select('ID', 'post_title', 'post_name', 'post_date')
            ->orderBy('post_date', 'desc')
            ->get()
            ->map(function($post) {
                return [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => route('post', $post->post_name),
                    'time' => $post->post_date->format('H:i')
                ];
            });
        
        return response()->json($posts);
    }
    
    /**
     * Страница постов за год
     */
    public function postsByYear($year)
    {
        $posts = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->paginate(20);
        
        $postsCount = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->count();
        
        $formattedDate = $year . ' год';
        
        // Генерируем уникальное описание для архива года
        $description = $this->generateYearDescription($year, $postsCount);
        
        return view('frontend.posts-by-date', compact('posts', 'formattedDate', 'description'));
    }
    
    /**
     * Генерация уникального описания для архива года
     */
    protected function generateYearDescription($year, $postsCount): string
    {
        $templates = [
            "Архив новостей и статей за {$year} год на портале Нота Миру - {$postsCount} публикаций о самых важных и значимых событиях года в мире музыки, культуры и шоу-бизнеса. Полный обзор главных тенденций и трендов.",
            "Все материалы {$year} года: {$postsCount} статей с актуальными новостями, глубокой аналитикой и эксклюзивными репортажами. Изучайте историю событий, которые определили год в индустрии развлечений и культуры.",
            "Новости {$year} года на Нота Миру - {$postsCount} публикаций о главных событиях, трендах и персонах года. Комплексный обзор важнейших моментов в мире музыки, концертов и культурных событий.",
            "Публикации за {$year} год: {$postsCount} качественных материалов с новостями, интересными интервью и экспертными мнениями. Погрузитесь в атмосферу прошедшего года и узнайте о ключевых событиях индустрии.",
            "Архив статей {$year} года на Нота Миру - {$postsCount} публикаций о важнейших событиях и темах года в сфере музыки и шоу-бизнеса. Читайте обзоры, репортажи и аналитику от профессиональных журналистов."
        ];
        
        // Выбираем шаблон на основе года (для стабильности)
        $index = intval($year) % count($templates);
        return $templates[$index];
    }
    
    /**
     * Страница постов за год и месяц
     */
    public function postsByYearMonth($year, $month)
    {
        $monthNames = [
            '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
            '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
            '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
            '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
        ];
        
        $monthNamesGenitive = [
            '01' => 'января', '02' => 'февраля', '03' => 'марта',
            '04' => 'апреля', '05' => 'мая', '06' => 'июня',
            '07' => 'июля', '08' => 'августа', '09' => 'сентября',
            '10' => 'октября', '11' => 'ноября', '12' => 'декабря'
        ];
        
        $posts = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->whereMonth('post_date', $month)
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->paginate(20);
        
        $postsCount = Post::publiclyVisible()
            ->whereYear('post_date', $year)
            ->whereMonth('post_date', $month)
            ->count();
        
        $formattedDate = $monthNames[$month] . ' ' . $year;
        
        // Генерируем уникальное описание для архива месяца
        $description = $this->generateMonthDescription($year, $monthNamesGenitive[$month], $postsCount);
        
        return view('frontend.posts-by-date', compact('posts', 'formattedDate', 'description'));
    }
    
    /**
     * Генерация уникального описания для архива месяца
     */
    protected function generateMonthDescription($year, $monthName, $postsCount): string
    {
        $templates = [
            "Новости и статьи за {$monthName} {$year} года на Нота Миру - {$postsCount} публикаций о важных событиях месяца в мире музыки, культуры и шоу-бизнеса. Актуальные материалы, интервью и репортажи от наших журналистов.",
            "Архив материалов за {$monthName} {$year} года на портале Нота Миру: {$postsCount} статей с актуальными новостями, экспертными комментариями и глубоким анализом ключевых событий месяца в индустрии развлечений.",
            "Все публикации {$monthName} {$year} года - {$postsCount} материалов с эксклюзивными репортажами, глубокой аналитикой и интервью с известными личностями. Оставайтесь в курсе важнейших событий месяца.",
            "Статьи и новости за {$monthName} {$year} года: {$postsCount} публикаций о главных событиях месяца с профессиональным подходом к освещению. Узнайте о трендах, премьерах и значимых моментах индустрии.",
            "Материалы {$monthName} {$year} года на Нота Миру - {$postsCount} статей от наших авторов и корреспондентов с места событий. Полный обзор новостей месяца в сфере музыки, концертов и культурных мероприятий."
        ];
        
        // Выбираем шаблон на основе месяца и года (для стабильности)
        $index = (intval($year) + intval(date('m', strtotime($monthName)))) % count($templates);
        return $templates[$index];
    }

    /**
     * Генерация описания для архива конкретного дня
     */
    protected function generateDateDescription(string $label, int $postsCount): string
    {
        $siteName = config('app.name', 'Нота Миру');

        $templates = [
            "Все публикации за {$label} на {$siteName}: {$postsCount} материалов с новостями, интервью и аналитикой.",
            "Архив новостей за {$label}: {$postsCount} статей о ключевых событиях музыки, культуры и шоу-бизнеса.",
            "{$postsCount} публикаций за {$label} на портале {$siteName}. Читайте репортажи, обзоры и эксклюзивные комментарии.",
        ];

        $index = $postsCount % count($templates);

        return $templates[$index];
    }
    
    /**
     * Страница 404 с лентой всех материалов
     */
    public function notFound()
    {
        // Получаем первые 12 постов для отображения
        $posts = Post::publiclyVisible()
            ->with(['author', 'categories.term'])
            ->orderBy('post_date', 'desc')
            ->limit(12)
            ->get();
        
        // Общее количество постов для автозагрузки
        $totalPosts = Post::publiclyVisible()->count();
        
        return response()->view('errors.404', compact('posts', 'totalPosts'), 404);
    }
}


