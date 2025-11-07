<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermTaxonomy extends Model
{
    protected $table = 'wp_term_taxonomy';
    protected $primaryKey = 'term_taxonomy_id';
    public $timestamps = false;
    
    protected $fillable = [
        'term_id',
        'taxonomy',
        'description',
        'parent',
        'count',
    ];
    
    // Термин (категория/тег)
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }
    
    // Только категории
    public function scopeCategories($query)
    {
        return $query->where('taxonomy', 'category');
    }
    
    // Только теги
    public function scopeTags($query)
    {
        return $query->where('taxonomy', 'post_tag');
    }
}
