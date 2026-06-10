<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    /**
     * Роли с этим правом
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Группы прав
     */
    const GROUP_GENERAL = 'general';
    const GROUP_POSTS = 'posts';
    const GROUP_PAGES = 'pages';
    const GROUP_USERS = 'users';
    const GROUP_CATEGORIES = 'categories';
    const GROUP_MENU = 'menu';
    const GROUP_BANNERS = 'banners';
    const GROUP_ANALYTICS = 'analytics';
    const GROUP_SETTINGS = 'settings';
}
