<?php

namespace App\Models\WordPress;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\UserRole;
use App\Models\Role;
use App\Models\AuthorStatistic;
use App\Models\ActivityLog;

class User extends BaseModel
{
    protected $table = 'wp_users';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'user_login',
        'user_email',
        'user_nicename',
        'display_name',
        'admin_password',
        'admin_password_plain',
        'admin_account_active',
    ];
    
    protected $hidden = [
        'user_pass',
        'admin_password',
    ];
    
    protected $casts = [
        'admin_account_active' => 'boolean',
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
    
    /**
     * Связь с ролями
     */
    public function userRole(): HasOne
    {
        return $this->hasOne(UserRole::class, 'user_id', 'ID');
    }
    
    /**
     * Получить роль пользователя
     */
    public function getRole(): ?Role
    {
        return $this->userRole?->role;
    }
    
    /**
     * Получить должность пользователя
     */
    public function getPosition(): ?string
    {
        return $this->userRole?->position;
    }
    
    /**
     * Пресс-карты журналиста
     */
    public function pressCards(): HasMany
    {
        return $this->hasMany(\App\Models\PressCard::class, 'user_id', 'ID');
    }

    /**
     * Активная пресс-карта
     */
    public function activePressCard(): HasOne
    {
        return $this->hasOne(\App\Models\PressCard::class, 'user_id', 'ID')
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->latest('issued_at');
    }

    /**
     * Статистика автора
     */
    public function statistics(): HasOne
    {
        return $this->hasOne(AuthorStatistic::class, 'user_id', 'ID');
    }
    
    /**
     * История действий
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'ID');
    }
    
    /**
     * Проверка наличия права
     */
    public function hasPermission(string $permissionName): bool
    {
        $role = $this->getRole();
        
        if (!$role) {
            return false;
        }
        
        // Супер-админ имеет все права
        if ($role->name === Role::SUPER_ADMIN) {
            return true;
        }
        
        // Проверяем кастомные права пользователя
        $customPermissions = $this->userRole?->custom_permissions ?? [];
        if (in_array($permissionName, $customPermissions)) {
            return true;
        }
        
        // Проверяем права роли
        return $role->hasPermission($permissionName);
    }
    
    /**
     * Проверка роли
     */
    public function hasRole(string $roleName): bool
    {
        return $this->getRole()?->name === $roleName;
    }
    
    /**
     * Проверка, является ли пользователь супер-админом
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }
    
    /**
     * Проверка, является ли пользователь главным редактором
     */
    public function isEditor(): bool
    {
        return $this->hasRole(Role::EDITOR);
    }
    
    /**
     * Проверка, является ли пользователь автором
     */
    public function isAuthor(): bool
    {
        return $this->hasRole(Role::AUTHOR);
    }
    
    /**
     * Проверка, может ли пользователь редактировать категорию
     */
    public function canEditCategory(int $categoryId): bool
    {
        // Супер-админ и главный редактор могут редактировать все категории
        if ($this->isSuperAdmin() || $this->isEditor()) {
            return true;
        }
        
        // Проверяем разрешенные категории
        $allowedCategories = $this->userRole?->allowed_categories ?? [];
        
        // Если массив пустой, автор может редактировать все категории
        if (empty($allowedCategories)) {
            return true;
        }
        
        return in_array($categoryId, $allowedCategories);
    }
    
    /**
     * Проверка, может ли пользователь редактировать пост
     */
    public function canEditPost(Post $post): bool
    {
        // Супер-админ может редактировать все
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Главный редактор может редактировать все статьи
        if ($this->isEditor() && $this->hasPermission('edit_all_posts')) {
            return true;
        }
        
        // Автор может редактировать только свои статьи
        if ($this->isAuthor() && $post->post_author == $this->ID) {
            return true;
        }
        
        return false;
    }
}

