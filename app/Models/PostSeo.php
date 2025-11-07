<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\WordPress\Post;

class PostSeo extends Model
{
    use HasFactory;

    protected $table = 'post_seo';

    protected $fillable = [
        'post_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'og_article_section',
        'og_article_tags',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'focus_keywords',
        'readability_score',
        'seo_score',
    ];

    protected $casts = [
        'og_article_tags' => 'array',
        'seo_keywords' => 'array',
        'focus_keywords' => 'array',
        'readability_score' => 'integer',
        'seo_score' => 'integer',
    ];

    /**
     * Связь с постом
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'ID');
    }

    /**
     * Получить SEO title или использовать заголовок поста
     */
    public function getTitle(): string
    {
        return $this->seo_title ?: $this->post->post_title;
    }

    /**
     * Получить SEO description или использовать excerpt поста
     */
    public function getDescription(): string
    {
        if ($this->seo_description) {
            return $this->seo_description;
        }
        
        if ($this->post->post_excerpt) {
            return $this->post->post_excerpt;
        }
        
        // Создаем excerpt из контента
        $content = strip_tags($this->post->post_content);
        return mb_substr($content, 0, 160) . '...';
    }

    /**
     * Получить OG title
     */
    public function getOgTitle(): string
    {
        return $this->og_title ?: $this->getTitle();
    }

    /**
     * Получить OG description
     */
    public function getOgDescription(): string
    {
        return $this->og_description ?: $this->getDescription();
    }

    /**
     * Получить Twitter title
     */
    public function getTwitterTitle(): string
    {
        return $this->twitter_title ?: $this->getTitle();
    }

    /**
     * Получить Twitter description
     */
    public function getTwitterDescription(): string
    {
        return $this->twitter_description ?: $this->getDescription();
    }

    /**
     * Получить ключевые слова как строку
     */
    public function getKeywordsString(): string
    {
        if (is_array($this->seo_keywords)) {
            return implode(', ', $this->seo_keywords);
        }
        return $this->seo_keywords ?: '';
    }
}




