<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'wp_users';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'user_login',
        'user_email',
        'user_nicename',
        'display_name',
    ];
    
    protected $hidden = [
        'user_pass',
    ];
    
    // Посты пользователя
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'post_author', 'ID');
    }
    
    // Метаданные пользователя
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class, 'user_id', 'ID');
    }
    
    // Получить значение мета-поля
    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        return $meta ? $meta->meta_value : $default;
    }
}
