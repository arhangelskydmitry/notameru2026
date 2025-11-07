<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    protected $table = 'wp_usermeta';
    protected $primaryKey = 'umeta_id';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value',
    ];
    
    // Пользователь
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }
}
