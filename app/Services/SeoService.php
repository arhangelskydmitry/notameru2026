<?php

namespace App\Services;

use App\Models\WordPress\Post;
use App\Models\PostSeo;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Получить SEO данные для страницы
     */
    public function getPageSeo(Post $post): array
    {
        // Загружаем SEO данные если они еще не загружены
        if (!$post->relationLoaded('seo')) {
            $post->load('seo');
        }
        
        return [
            'title' => $this->getTitle($post),
            'description' => $this->getDescription($post),
            'keywords' => $this->getKeywords($post),
            'canonical' => $this->getCanonical($post),
            'robots' => $this->getRobots($post),
            'og' => $this->getOpenGraph($post),
            'twitter' => $this->getTwitterCard($post),
            'schema' => $this->getStructuredData($post),
        ];
    }
    
    /**
     * Получить title страницы
     */
    public function getTitle(Post $post): string
    {
        // Приоритет: PostSeo -> Post title
        $seo = $post->seo;
        $title = $seo && $seo->seo_title ? $seo->getTitle() : $post->post_title;
        
        // Добавляем название сайта
        $siteName = config('app.name', 'Нота Миру');
        
        return $title . ' - ' . $siteName;
    }
    
    /**
     * Получить description
     */
    public function getDescription(Post $post): string
    {
        // Используем PostSeo модель с её умными методами
        $seo = $post->seo;
        
        if ($seo) {
            return $seo->getDescription();
        }
        
        // Fallback: Post excerpt -> первые 160 символов контента
        if ($post->post_excerpt) {
            return Str::limit(strip_tags($post->post_excerpt), 160);
        }
        
        return Str::limit(strip_tags($post->post_content), 160);
    }
    
    /**
     * Получить keywords
     */
    public function getKeywords(Post $post): ?string
    {
        $seo = $post->seo;
        
        if ($seo && $seo->seo_keywords) {
            return $seo->getKeywordsString();
        }
        
        // Автоматически из тегов
        if ($post->tags && $post->tags->isNotEmpty()) {
            return $post->tags->pluck('term.name')->implode(', ');
        }
        
        return null;
    }
    
    /**
     * Получить canonical URL
     */
    public function getCanonical(Post $post): string
    {
        $seo = $post->seo;
        return $seo && $seo->canonical_url ? $seo->canonical_url : route('post', $post->post_name);
    }
    
    /**
     * Получить meta robots
     */
    public function getRobots(Post $post): string
    {
        $seo = $post->seo;
        return $seo && $seo->robots ? $seo->robots : 'index, follow';
    }
    
    /**
     * Получить Open Graph данные
     */
    public function getOpenGraph(Post $post): array
    {
        $seo = $post->seo;
        $thumbnail = $this->getThumbnailUrl($post);
        
        return [
            'title' => $seo ? $seo->getOgTitle() : $post->post_title,
            'description' => $seo ? $seo->getOgDescription() : $this->getDescription($post),
            'image' => $seo && $seo->og_image ? $seo->og_image : $thumbnail,
            'type' => $seo && $seo->og_type ? $seo->og_type : 'article',
            'url' => $this->getCanonical($post),
            'site_name' => config('app.name', 'Нота Миру'),
            'locale' => 'ru_RU',
        ];
    }
    
    /**
     * Получить Twitter Card данные
     */
    public function getTwitterCard(Post $post): array
    {
        $seo = $post->seo;
        $thumbnail = $this->getThumbnailUrl($post);
        
        return [
            'card' => $seo && $seo->twitter_card ? $seo->twitter_card : 'summary_large_image',
            'title' => $seo ? $seo->getTwitterTitle() : $post->post_title,
            'description' => $seo ? $seo->getTwitterDescription() : $this->getDescription($post),
            'image' => $seo && $seo->twitter_image ? $seo->twitter_image : $thumbnail,
        ];
    }
    
    /**
     * Получить Structured Data (Schema.org) для статьи
     */
    public function getStructuredData(Post $post): array
    {
        $thumbnail = $this->getThumbnailUrl($post);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->post_title,
            'description' => $this->getDescription($post),
            'datePublished' => $post->post_date->toIso8601String(),
            'dateModified' => $post->post_modified->toIso8601String(),
            'url' => $this->getCanonical($post),
        ];
        
        // Автор
        if ($post->author) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $post->author->display_name,
            ];
        }
        
        // Изображение
        if ($thumbnail) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $thumbnail,
            ];
        }
        
        // Издатель
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name' => config('app.name', 'Нота Миру'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/logo.png'),
            ],
        ];
        
        // Категория
        if ($post->categories && $post->categories->isNotEmpty()) {
            $schema['articleSection'] = $post->categories->first()->term->name;
        }
        
        // Теги как keywords
        if ($post->tags && $post->tags->isNotEmpty()) {
            $schema['keywords'] = $post->tags->pluck('term.name')->implode(', ');
        }
        
        return $schema;
    }
    
    /**
     * Получить URL миниатюры
     */
    protected function getThumbnailUrl(Post $post): ?string
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        if (!$thumbnailId) {
            return null;
        }
        
        $attachment = Post::find($thumbnailId);
        if (!$attachment) {
            return null;
        }
        
        // Полный URL
        $url = $attachment->guid;
        
        // Если относительный путь, делаем абсолютным
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = url($url);
        }
        
        return $url;
    }
    
    /**
     * Импорт SEO данных из WordPress Yoast SEO
     */
    public function importYoastSeo(Post $post): bool
    {
        // Yoast хранит данные в postmeta
        $yoastTitle = $post->getMeta('_yoast_wpseo_title');
        $yoastDesc = $post->getMeta('_yoast_wpseo_metadesc');
        $yoastKeywords = $post->getMeta('_yoast_wpseo_focuskw');
        $yoastCanonical = $post->getMeta('_yoast_wpseo_canonical');
        $yoastOgTitle = $post->getMeta('_yoast_wpseo_opengraph-title');
        $yoastOgDesc = $post->getMeta('_yoast_wpseo_opengraph-description');
        $yoastOgImage = $post->getMeta('_yoast_wpseo_opengraph-image');
        $yoastTwitterTitle = $post->getMeta('_yoast_wpseo_twitter-title');
        $yoastTwitterDesc = $post->getMeta('_yoast_wpseo_twitter-description');
        $yoastTwitterImage = $post->getMeta('_yoast_wpseo_twitter-image');
        $yoastMetaRobots = $post->getMeta('_yoast_wpseo_meta-robots-noindex');
        
        // Обновляем поля
        $updated = false;
        
        if ($yoastTitle) {
            $post->seo_title = $this->cleanYoastVariable($yoastTitle, $post);
            $updated = true;
        }
        
        if ($yoastDesc) {
            $post->seo_description = $yoastDesc;
            $updated = true;
        }
        
        if ($yoastKeywords) {
            $post->focus_keyword = $yoastKeywords;
            $updated = true;
        }
        
        if ($yoastCanonical) {
            $post->canonical_url = $yoastCanonical;
            $updated = true;
        }
        
        if ($yoastOgTitle) {
            $post->og_title = $this->cleanYoastVariable($yoastOgTitle, $post);
            $updated = true;
        }
        
        if ($yoastOgDesc) {
            $post->og_description = $yoastOgDesc;
            $updated = true;
        }
        
        if ($yoastOgImage) {
            $post->og_image = $yoastOgImage;
            $updated = true;
        }
        
        if ($yoastTwitterTitle) {
            $post->twitter_title = $this->cleanYoastVariable($yoastTwitterTitle, $post);
            $updated = true;
        }
        
        if ($yoastTwitterDesc) {
            $post->twitter_description = $yoastTwitterDesc;
            $updated = true;
        }
        
        if ($yoastTwitterImage) {
            $post->twitter_image = $yoastTwitterImage;
            $updated = true;
        }
        
        if ($yoastMetaRobots === '1') {
            $post->meta_robots = 'noindex, follow';
            $updated = true;
        }
        
        if ($updated) {
            $post->save();
        }
        
        return $updated;
    }
    
    /**
     * Очистить Yoast переменные (%%title%%, %%sitename%% и т.д.)
     */
    protected function cleanYoastVariable(string $text, Post $post): string
    {
        $replacements = [
            '%%title%%' => $post->post_title,
            '%%sitename%%' => config('app.name', 'Нота Миру'),
            '%%sep%%' => '-',
            '%%excerpt%%' => $post->post_excerpt ?: '',
            '%%category%%' => $post->categories->first()->term->name ?? '',
            '%%date%%' => $post->post_date->format('d.m.Y'),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Генерировать SEO-friendly описание
     */
    public function generateDescription(Post $post, int $length = 160): string
    {
        $content = strip_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content); // Убираем лишние пробелы
        $content = trim($content);
        
        return Str::limit($content, $length);
    }
    
    /**
     * Анализ SEO качества статьи
     */
    public function analyzeSeoScore(Post $post): array
    {
        $score = 100;
        $issues = [];
        $recommendations = [];
        
        // Проверка title
        if (!$post->seo_title && !$post->post_title) {
            $score -= 20;
            $issues[] = 'Отсутствует заголовок';
        } elseif (mb_strlen($post->seo_title ?: $post->post_title) < 30) {
            $score -= 10;
            $recommendations[] = 'Заголовок слишком короткий (рекомендуется 50-60 символов)';
        } elseif (mb_strlen($post->seo_title ?: $post->post_title) > 70) {
            $score -= 5;
            $recommendations[] = 'Заголовок слишком длинный (может обрезаться в поиске)';
        }
        
        // Проверка description
        if (!$post->seo_description && !$post->post_excerpt) {
            $score -= 15;
            $issues[] = 'Отсутствует описание';
        } elseif (mb_strlen($this->getDescription($post)) < 120) {
            $score -= 5;
            $recommendations[] = 'Описание слишком короткое (рекомендуется 150-160 символов)';
        }
        
        // Проверка focus keyword
        if (!$post->focus_keyword) {
            $score -= 10;
            $recommendations[] = 'Не указано ключевое слово';
        }
        
        // Проверка изображения
        if (!$this->getThumbnailUrl($post)) {
            $score -= 10;
            $issues[] = 'Отсутствует изображение';
        }
        
        // Проверка длины контента
        $wordCount = str_word_count(strip_tags($post->post_content));
        if ($wordCount < 300) {
            $score -= 15;
            $issues[] = 'Контент слишком короткий (менее 300 слов)';
        }
        
        // Проверка canonical
        if (!$post->canonical_url) {
            $score -= 5;
            $recommendations[] = 'Не указан canonical URL';
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->getSeoStatus($score),
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
    
    /**
     * Получить статус SEO (хороший/средний/плохой)
     */
    protected function getSeoStatus(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
}

