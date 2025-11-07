<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Term extends Model
{
    protected $table = 'wp_terms';
    protected $primaryKey = 'term_id';
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'slug',
    ];
    
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
        return $query->whereHas('taxonomy', function($q) {
            $q->where('taxonomy', 'category');
        });
    }
}
