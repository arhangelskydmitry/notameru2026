<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\Post;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'category_id',
        'page_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'category_id' => 'integer',
        'page_id' => 'integer',
    ];

    /**
     * Bootstrap модели
     */
    protected static function boot()
    {
        parent::boot();

        // Автоматически заполняем slug при создании
        static::creating(function ($item) {
            if (!$item->slug) {
                $item->slug = self::generateSlug($item);
            }
        });

        // Автоматически обновляем slug при изменении типа или ID
        static::updating(function ($item) {
            if ($item->isDirty(['type', 'category_id', 'page_id'])) {
                $item->slug = self::generateSlug($item);
            }
        });
    }

    /**
     * Генерация slug на основе типа и связанного контента
     */
    protected static function generateSlug($item): string
    {
        if ($item->type === 'category' && $item->category_id) {
            $category = TermTaxonomy::with('term')->find($item->category_id);
            if ($category && $category->term) {
                return $category->term->slug;
            }
        } elseif ($item->type === 'page' && $item->page_id) {
            $page = Post::find($item->page_id);
            if ($page) {
                return $page->post_name;
            }
        } elseif ($item->type === 'url' && $item->slug) {
            return $item->slug; // Для URL используем введенное значение
        }
        
        // Fallback: если ничего не подошло, генерируем из title
        return $item->slug ?? \Illuminate\Support\Str::slug($item->title ?? 'menu-item');
    }

    /**
     * Связь с категорией WordPress
     */
    public function category()
    {
        return $this->belongsTo(TermTaxonomy::class, 'category_id', 'term_taxonomy_id');
    }
    
    /**
     * Связь со страницей WordPress
     */
    public function page()
    {
        return $this->belongsTo(Post::class, 'page_id', 'ID');
    }

    /**
     * Scope для активных пунктов меню
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки по порядку
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Получить URL пункта меню
     */
    public function getUrlAttribute()
    {
        if ($this->type === 'category') {
            // Если есть привязанная категория, используем её slug
            if ($this->category_id) {
                if (!$this->relationLoaded('category')) {
                    $this->load('category.term');
                }
                
                if ($this->category && $this->category->term && $this->category->term->slug) {
                    return route('category', $this->category->term->slug);
                }
            }
            
            // Fallback: используем slug из меню
            return route('category', $this->slug);
            
        } elseif ($this->type === 'page') {
            // Если есть привязанная страница, используем её slug
            if ($this->page_id) {
                if (!$this->relationLoaded('page')) {
                    $this->load('page');
                }
                
                if ($this->page && $this->page->post_name) {
                    return route('post', $this->page->post_name);
                }
            }
            
            // Fallback: используем slug из меню
            return route('post', $this->slug);
            
        } else {
            // Прямой URL
            return $this->slug;
        }
    }
}
