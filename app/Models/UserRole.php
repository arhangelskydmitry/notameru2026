<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'position',
        'custom_permissions',
        'allowed_categories',
    ];

    protected $casts = [
        'custom_permissions' => 'array',
        'allowed_categories' => 'array',
    ];

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WordPress\User::class, 'user_id', 'ID');
    }

    /**
     * Роль
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
