<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaravelCategory extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'order' => 'integer',
    ];

    /**
     * Посты категории
     */
    public function posts()
    {
        return $this->belongsToMany(LaravelPost::class, 'post_category', 'category_id', 'post_id');
    }

    /**
     * Родительская категория
     */
    public function parent()
    {
        return $this->belongsTo(LaravelCategory::class, 'parent_id');
    }

    /**
     * Дочерние категории
     */
    public function children()
    {
        return $this->hasMany(LaravelCategory::class, 'parent_id');
    }

    /**
     * Количество постов
     */
    public function getPostsCountAttribute()
    {
        return $this->posts()->where('status', 'published')->count();
    }

    /**
     * URL категории
     */
    public function getUrlAttribute()
    {
        return route('category', $this->slug);
    }
}

