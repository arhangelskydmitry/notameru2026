<?php

namespace App\Models\WordPress;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Term extends BaseModel
{
    protected $table = 'wp_terms';
    protected $primaryKey = 'term_id';
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'slug',
    ];
    
    // Связь с таксономией (taxonomy)
    public function taxonomy()
    {
        return $this->hasOne(TermTaxonomy::class, 'term_id', 'term_id');
    }
    
    // Все таксономии этого термина
    public function taxonomies()
    {
        return $this->hasMany(TermTaxonomy::class, 'term_id', 'term_id');
    }
    
    // Посты этого термина
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'wp_term_relationships',
            'term_taxonomy_id',
            'object_id',
            'term_id',
            'ID'
        );
    }
    
    // Только категории
    public function scopeCategories($query)
    {
        return $query->whereHas('taxonomies', function($q) {
            $q->where('taxonomy', 'category');
        });
    }
    
    // Только теги
    public function scopeTags($query)
    {
        return $query->whereHas('taxonomies', function($q) {
            $q->where('taxonomy', 'post_tag');
        });
    }
}
