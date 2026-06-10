<?php

namespace App\Models\WordPress;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends BaseModel
{
    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'post_name',
        'post_modified',
        'post_modified_gmt',
        'post_type',
        'comment_status',
        'ping_status',
        'to_ping',
        'pinged',
        'post_content_filtered',
        // SEO fields
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'meta_robots',
        'focus_keyword',
    ];
    
    protected $casts = [
        'post_date' => 'datetime',
        'post_modified' => 'datetime',
    ];
    
    // Только опубликованные посты
    public function scopePublished($query)
    {
        return $query->where('post_status', 'publish')
                    ->where('post_type', 'post');
    }

    /**
     * Публично видимые посты:
     * только опубликованные материалы, дата публикации которых уже наступила.
     */
    public function scopePubliclyVisible($query)
    {
        return $query->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->where('post_date', '<=', now());
    }

    public function getAdminStatusLabel(): string
    {
        return match ($this->post_status) {
            'publish' => 'Опубликовано',
            'draft' => 'Черновик',
            'pending' => 'Ожидает проверки',
            'future' => 'Отложенная публикация',
            default => (string) $this->post_status,
        };
    }

    public function getAdminStatusBadgeClass(): string
    {
        return match ($this->post_status) {
            'publish' => 'bg-success',
            'draft' => 'bg-warning',
            'pending' => 'bg-secondary',
            'future' => 'bg-info',
            default => 'bg-secondary',
        };
    }

    public function isPubliclyAccessible(): bool
    {
        return filled($this->post_name)
            && $this->post_status === 'publish'
            && $this->post_date !== null
            && $this->post_date->lte(now());
    }
    
    // Автор поста
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'post_author', 'ID');
    }
    
    // Метаданные поста
    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class, 'post_id', 'ID');
    }
    
    // Получить значение мета-поля
    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        return $meta ? $meta->meta_value : $default;
    }
    
    // Установить значение мета-поля
    public function setMeta(string $key, $value)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        
        if ($meta) {
            // Обновляем существующую запись
            $meta->update(['meta_value' => $value]);
        } else {
            // Создаем новую запись
            $this->meta()->create([
                'meta_key' => $key,
                'meta_value' => $value
            ]);
        }
        
        return $this;
    }
    
    /**
     * Удалить meta данные
     */
    public function deleteMeta(string $key)
    {
        $this->meta()->where('meta_key', $key)->delete();
        
        return $this;
    }
    
    // Категории (через term_relationships и term_taxonomy)
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            TermTaxonomy::class,
            'wp_term_relationships',
            'object_id',
            'term_taxonomy_id',
            'ID',
            'term_taxonomy_id'
        )->where('wp_term_taxonomy.taxonomy', 'category');
    }
    
    // Теги (через term_relationships и term_taxonomy)
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            TermTaxonomy::class,
            'wp_term_relationships',
            'object_id',
            'term_taxonomy_id',
            'ID',
            'term_taxonomy_id'
        )->where('wp_term_taxonomy.taxonomy', 'post_tag');
    }
    
    // Миниатюра поста
    public function thumbnail()
    {
        $thumbnailId = $this->getMeta('_thumbnail_id');
        if ($thumbnailId) {
            return self::find($thumbnailId);
        }
        return null;
    }
    
    // SEO данные
    public function seo()
    {
        return $this->hasOne(\App\Models\PostSeo::class, 'post_id', 'ID');
    }
    
    // Accessors для SEO полей
    public function getSeoTitleAttribute()
    {
        return $this->seo->seo_title ?? '';
    }
    
    public function getSeoDescriptionAttribute()
    {
        return $this->seo->seo_description ?? '';
    }
    
    public function getSeoKeywordsAttribute()
    {
        $keywords = $this->seo->seo_keywords ?? [];
        return is_array($keywords) ? implode(', ', $keywords) : $keywords;
    }
    
    public function getFocusKeywordAttribute()
    {
        return $this->seo->focus_keywords ?? '';
    }
    
    public function getCanonicalUrlAttribute()
    {
        return $this->seo->canonical_url ?? '';
    }
    
    public function getMetaRobotsAttribute()
    {
        return $this->seo->robots ?? 'index, follow';
    }
    
    public function getOgTitleAttribute()
    {
        return $this->seo->og_title ?? '';
    }
    
    public function getOgDescriptionAttribute()
    {
        return $this->seo->og_description ?? '';
    }
    
    public function getOgImageAttribute()
    {
        $image = $this->seo->og_image ?? '';
        return $this->normalizeImageUrl($image);
    }
    
    public function getOgTypeAttribute()
    {
        return $this->seo->og_type ?? 'article';
    }
    
    public function getTwitterCardAttribute()
    {
        return $this->seo->twitter_card ?? 'summary_large_image';
    }
    
    public function getTwitterTitleAttribute()
    {
        return $this->seo->twitter_title ?? '';
    }
    
    public function getTwitterDescriptionAttribute()
    {
        return $this->seo->twitter_description ?? '';
    }
    
    public function getTwitterImageAttribute()
    {
        $image = $this->seo->twitter_image ?? '';
        return $this->normalizeImageUrl($image);
    }
    
    /**
     * Нормализовать URL изображения (преобразовать относительный путь в полный)
     */
    protected function normalizeImageUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }
        
        // Если уже полный URL, возвращаем как есть
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }
        
        // Если относительный путь, преобразуем в полный URL
        if (str_starts_with($url, '/')) {
            return rtrim(config('app.url'), '/') . $url;
        }
        
        return $url;
    }
    
    /**
     * Получить featured image (главное изображение поста)
     */
    public function getFeaturedImageAttribute()
    {
        // Сначала проверяем SEO og_image
        if ($this->seo && $this->seo->og_image) {
            return $this->normalizeImageUrl($this->seo->og_image);
        }
        
        // Проверяем _thumbnail_url в метаданных
        $thumbnailUrl = $this->getMeta('_thumbnail_url');
        if ($thumbnailUrl) {
            return $this->normalizeImageUrl($thumbnailUrl);
        }
        
        // Проверяем _thumbnail_id в метаданных
        $thumbnailId = $this->getMeta('_thumbnail_id');
        if ($thumbnailId) {
            $attachment = self::where('ID', $thumbnailId)
                ->where('post_type', 'attachment')
                ->first();
            
            if ($attachment) {
                return $this->normalizeImageUrl($attachment->guid);
            }
        }
        
        // Если нет thumbnail_id, ищем первое изображение в контенте
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $this->post_content, $matches)) {
            return $this->normalizeImageUrl($matches[1]);
        }
        
        // Если ничего не найдено, возвращаем null
        return null;
    }
    
    /**
     * Получить URL изображения для социальных сетей
     */
    public function getSocialImageAttribute()
    {
        // Приоритет: OG Image > Featured Image > Первое изображение в контенте
        if ($this->og_image) {
            return $this->og_image;
        }
        
        return $this->featured_image;
    }
}
