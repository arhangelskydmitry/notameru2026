<?php

namespace App\Services;

use App\Helpers\ContentHelper;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoMetaService
{
    /**
     * Сформировать SEO-данные для страницы категории.
     */
    public function forCategory(TermTaxonomy $category, $posts, ?string $description = null): array
    {
        $siteName = config('app.name', 'Нота Миру');
        $title = sprintf(
            'Категория «%s» — свежие новости, статьи и интервью | %s',
            $category->term->name,
            $siteName
        );

        $fallbackDescription = $this->fallbackCategoryDescription($category);

        return $this->buildListingMeta([
            'title' => $title,
            'description' => $description ?: $fallbackDescription,
            'keywords' => $this->buildKeywords($category->term->name),
            'og_title' => sprintf('Все материалы категории «%s»', $category->term->name),
        ], $posts);
    }

    /**
     * Сформировать SEO-данные для страницы автора.
     */
    public function forAuthor(User $author, $posts): array
    {
        $siteName = config('app.name', 'Нота Миру');
        $count = max($this->postsCount($posts), 1);

        $title = sprintf('Автор %s — публикации и материалы | %s', $author->display_name, $siteName);

        $description = $author->description
            ? strip_tags($author->description)
            : sprintf(
                'Читайте %s статей автора %s на портале %s: эксклюзивы, интервью и аналитика.',
                $count,
                $author->display_name,
                $siteName
            );

        return $this->buildListingMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $this->buildKeywords($author->display_name),
            'og_title' => sprintf('Материалы автора %s', $author->display_name),
        ], $posts);
    }

    /**
     * SEO-данные для страницы поиска.
     */
    public function forSearch(string $query, $posts, ?int $total = null): array
    {
        $siteName = config('app.name', 'Нота Миру');
        $safeQuery = trim($query) !== '' ? $query : 'поиск';
        $count = $total ?? $this->postsCount($posts);

        $title = sprintf('Поиск: %s — %s', $safeQuery, $siteName);

        $description = sprintf(
            'Результаты поиска по запросу «%s». Найдено %s материалов с новостями, обзорами и интервью.',
            $safeQuery,
            $count > 0 ? $count : 'новые'
        );

        return $this->buildListingMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => implode(', ', [$safeQuery, 'поиск новостей', $siteName]),
            'og_title' => sprintf('Результаты поиска: %s', $safeQuery),
        ], $posts);
    }

    /**
     * SEO для архивов по дате/месяцу/году.
     */
    public function forDateArchive(string $label, $posts, ?string $description = null): array
    {
        $siteName = config('app.name', 'Нота Миру');
        $count = max($this->postsCount($posts), 1);

        $title = sprintf('Публикации за %s — %s', $label, $siteName);
        $fallbackDescription = sprintf(
            'Архив новостей и статей за %s: %s материалов про музыку, культуру и шоу-бизнес.',
            $label,
            $count
        );

        return $this->buildListingMeta([
            'title' => $title,
            'description' => $description ?: $fallbackDescription,
            'keywords' => implode(', ', [
                "новости {$label}",
                "архив {$label}",
                $siteName,
            ]),
            'og_title' => sprintf('Все публикации за %s', $label),
        ], $posts);
    }

    /**
     * Сформировать SEO-данные для страницы тега.
     */
    public function forTag(TermTaxonomy $tag, $posts, ?string $description = null): array
    {
        $siteName = config('app.name', 'Нота Миру');
        $title = sprintf(
            'Тег «%s» — подборка материалов и новостей | %s',
            $tag->term->name,
            $siteName
        );

        $fallbackDescription = $this->fallbackTagDescription($tag);

        return $this->buildListingMeta([
            'title' => $title,
            'description' => $description ?: $fallbackDescription,
            'keywords' => $this->buildKeywords($tag->term->name),
            'og_title' => sprintf('Публикации по тегу «%s»', $tag->term->name),
        ], $posts);
    }

    /**
     * Унифицированная сборка данных для листинговых страниц.
     */
    protected function buildListingMeta(array $data, $posts): array
    {
        $page = $this->currentPage();

        $description = $this->truncateDescription($data['description'] ?? '');
        $description = $this->applyPageSuffix($description, $page, true);

        $title = $this->applyPageSuffix($data['title'] ?? config('app.name', 'Нота Миру'), $page);
        $ogTitle = $this->applyPageSuffix($data['og_title'] ?? $title, $page);

        $canonical = $this->buildCanonical($page);
        $image = $this->resolveListingImage($posts);

        $route = request()->route();
        $isSearchPage = method_exists($route, 'getName') && $route->getName() === 'search';

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $data['keywords'] ?? null,
            'canonical' => $canonical,
            'robots' => $isSearchPage ? 'noindex, follow' : 'index, follow',
            'og' => [
                'type' => $data['og_type'] ?? 'website',
                'title' => $ogTitle,
                'description' => $description,
                'image' => $image,
                'url' => $canonical,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $ogTitle,
                'description' => $description,
                'image' => $image,
            ],
        ];
    }

    /**
     * Текущая страница пагинации.
     */
    protected function currentPage(): int
    {
        return max(1, (int) request()->get('page', 1));
    }

    /**
     * Добавить информацию о странице к заголовку/описанию.
     */
    protected function applyPageSuffix(string $text, int $page, bool $short = false): string
    {
        if ($page <= 1 || $text === '') {
            return $text;
        }

        $suffix = $short ? " (страница {$page})" : " — страница {$page}";

        return $text . $suffix;
    }

    /**
     * Канонический URL с учетом пагинации.
     */
    protected function buildCanonical(int $page): string
    {
        $baseUrl = request()->url();
        $query = request()->query();

        if ($page <= 1) {
            unset($query['page']);
        } else {
            $query['page'] = $page;
        }

        if (empty($query)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($query);
    }

    /**
     * Выбрать изображение для OG/Twitter карточек.
     */
    protected function resolveListingImage($posts): string
    {
        $collection = $this->collectPosts($posts);

        $image = $collection->map(function ($post) {
            if (!$post) {
                return null;
            }

            $featured = ContentHelper::getFeaturedImage($post);

            if (!$featured) {
                return null;
            }

            if (Str::contains($featured, 'placeholder')) {
                return null;
            }

            return $featured;
        })->filter()->first();

        if (!$image) {
            $image = asset('favicon.svg');
        }

        return $this->absoluteUrl($image);
    }

    /**
     * Преобразовать относительный путь в абсолютный URL.
     */
    protected function absoluteUrl(?string $path): string
    {
        if (!$path) {
            return asset('favicon.svg');
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : url($path);
    }

    /**
     * Очистить и ограничить длину description.
     */
    protected function truncateDescription(string $text): string
    {
        $clean = strip_tags($text);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        return Str::limit($clean, 180);
    }

    /**
     * Сформировать список ключевых слов.
     */
    protected function buildKeywords(string $base): string
    {
        $normalized = Str::lower($base);

        $keywords = array_unique(array_filter([
            $base,
            "новости {$normalized}",
            "статьи {$normalized}",
            "интервью {$normalized}",
            config('app.name', 'Нота Миру'),
        ]));

        return implode(', ', $keywords);
    }

    /**
     * Резервное описание для категорий.
     */
    protected function fallbackCategoryDescription(TermTaxonomy $category): string
    {
        $name = $category->term->name;
        $count = max((int) $category->count, 1);
        $siteName = config('app.name', 'Нота Миру');

        return "Читайте {$count} материалов категории «{$name}» на портале {$siteName}. "
            . "Свежие новости, эксклюзивы и экспертные комментарии по теме {$name}.";
    }

    /**
     * Резервное описание для тегов.
     */
    protected function fallbackTagDescription(TermTaxonomy $tag): string
    {
        $name = $tag->term->name;
        $count = max((int) $tag->count, 1);
        $siteName = config('app.name', 'Нота Миру');

        return "Подборка из {$count} публикаций по тегу «{$name}» на {$siteName}: "
            . "новости, интервью и аналитика по интересующей теме.";
    }

    /**
     * Приведение коллекций постов к удобному виду.
     */
    protected function collectPosts($posts): Collection
    {
        if ($posts instanceof Collection) {
            return $posts;
        }

        if ($posts instanceof LengthAwarePaginator) {
            return collect($posts->items());
        }

        if ($posts instanceof PaginatorContract) {
            return collect($posts->items());
        }

        if (is_array($posts)) {
            return collect($posts);
        }

        if ($posts instanceof \Traversable) {
            return collect(iterator_to_array($posts));
        }

        return collect($posts ? [$posts] : []);
    }

    /**
     * Подсчет количества постов для описаний.
     */
    protected function postsCount($posts): int
    {
        if ($posts instanceof LengthAwarePaginator) {
            return (int) $posts->total();
        }

        if ($posts instanceof PaginatorContract) {
            return (int) $posts->count();
        }

        if ($posts instanceof Collection) {
            return (int) $posts->count();
        }

        if (is_array($posts)) {
            return count($posts);
        }

        return 0;
    }
}


