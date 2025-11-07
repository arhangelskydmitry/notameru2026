<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'post_author',
        'post_date',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'post_name',
        'post_type',
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
}
