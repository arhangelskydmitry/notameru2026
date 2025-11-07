<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaravelPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'author_id',
        'featured_image',
        'views',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'views' => 'integer',
    ];

    /**
     * Автор поста
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Категории поста
     */
    public function categories()
    {
        return $this->belongsToMany(LaravelCategory::class, 'post_category', 'post_id', 'category_id');
    }

    /**
     * Теги поста
     */
    public function tags()
    {
        return $this->belongsToMany(LaravelTag::class, 'post_tag', 'post_id', 'tag_id');
    }

    /**
     * Scope для опубликованных постов
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Инкремент просмотров
     */
    public function incrementViews()
    {
        $this->increment('views');
    }

    /**
     * URL поста
     */
    public function getUrlAttribute()
    {
        return route('post', $this->slug);
    }
}

