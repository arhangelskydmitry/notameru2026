<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $connection = 'mysql';

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Пользователь, совершивший действие
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WordPress\User::class, 'user_id', 'ID');
    }

    /**
     * Логирование действия
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $properties = null
    ): ?self {
        // Получаем ID пользователя (WordPress User ID)
        $userId = null;
        if (auth()->check()) {
            // Если используется WordPress User model
            $user = auth()->user();
            $userId = $user->ID ?? $user->id ?? null;
        }

        // Fallback для админ-панели (использует отдельную сессию)
        if (!$userId && function_exists('admin_user')) {
            $adminUser = admin_user();
            if ($adminUser) {
                $userId = $adminUser->ID;
            }
        }

        // Если helper не доступен, попробуем взять ID напрямую из сессии
        if (!$userId && session()->has('admin_user_id')) {
            $userId = session('admin_user_id');
        }
        
        // Если пользователь не авторизован, не логируем
        if (!$userId) {
            return null;
        }
        
        try {
            return self::create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'description' => $description,
                'properties' => $properties,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Если таблица не существует или другая ошибка - игнорируем
            \Log::warning('ActivityLog failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Константы действий
     */
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_RESTORED = 'restored';
    const ACTION_VIEWED = 'viewed';

    /**
     * Получить текстовое описание действия
     */
    public function getActionText(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Создан',
            self::ACTION_UPDATED => 'Обновлен',
            self::ACTION_DELETED => 'Удален',
            self::ACTION_LOGIN => 'Вход в систему',
            self::ACTION_LOGOUT => 'Выход из системы',
            'sitemap_regenerated' => 'Sitemap перегенерирован',
            'password_reset' => 'Пароль сброшен',
            'user_created' => 'Пользователь создан',
            'user_updated' => 'Пользователь обновлен',
            'user_deleted' => 'Пользователь удален',
            'post_boost_views' => 'Просмотры статей накручены',
            'category_created' => 'Категория создана',
            'category_updated' => 'Категория обновлена',
            'category_deleted' => 'Категория удалена',
            'menu_updated' => 'Меню обновлено',
            'banner_created' => 'Баннер создан',
            'banner_updated' => 'Баннер обновлен',
            'banner_deleted' => 'Баннер удален',
            default => 'Действие: ' . $this->action
        };
    }
}
