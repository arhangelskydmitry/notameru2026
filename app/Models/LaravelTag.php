<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaravelTag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Посты тега
     */
    public function posts()
    {
        return $this->belongsToMany(LaravelPost::class, 'post_tag', 'tag_id', 'post_id');
    }

    /**
     * Количество постов
     */
    public function getPostsCountAttribute()
    {
        return $this->posts()->where('status', 'published')->count();
    }

    /**
     * URL тега
     */
    public function getUrlAttribute()
    {
        return route('tag', $this->slug);
    }
}

