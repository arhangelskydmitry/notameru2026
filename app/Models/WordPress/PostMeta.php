<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMeta extends Model
{
    protected $table = 'wp_postmeta';
    protected $primaryKey = 'meta_id';
    public $timestamps = false;
    
    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];
    
    // Пост
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'ID');
    }
}
