<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    /**
     * Права доступа роли
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Пользователи с этой ролью
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Проверка наличия права
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Константы ролей
     */
    const SUPER_ADMIN = 'super_admin';
    const EDITOR = 'editor';
    const AUTHOR = 'author';
}
