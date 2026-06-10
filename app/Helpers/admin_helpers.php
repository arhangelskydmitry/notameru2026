<?php

if (!function_exists('admin_user')) {
    /**
     * Получить текущего авторизованного пользователя админки
     */
    function admin_user(): ?\App\Models\WordPress\User
    {
        $userId = session('admin_user_id');
        
        if (!$userId) {
            return null;
        }
        
        return \App\Models\WordPress\User::with(['userRole.role', 'statistics'])->find($userId);
    }
}

if (!function_exists('admin_check')) {
    /**
     * Проверить, авторизован ли пользователь в админке
     */
    function admin_check(): bool
    {
        return session()->has('admin_user_id');
    }
}

if (!function_exists('admin_can')) {
    /**
     * Проверить, имеет ли текущий пользователь право
     */
    function admin_can(string $permission): bool
    {
        $user = admin_user();
        
        if (!$user) {
            return false;
        }
        
        return $user->hasPermission($permission);
    }
}

if (!function_exists('admin_is')) {
    /**
     * Проверить, имеет ли текущий пользователь роль
     */
    function admin_is(string $role): bool
    {
        $user = admin_user();
        
        if (!$user) {
            return false;
        }
        
        return $user->hasRole($role);
    }
}

if (!function_exists('admin_log')) {
    /**
     * Логировать действие пользователя
     */
    function admin_log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $properties = null
    ): ?\App\Models\ActivityLog {
        if (!admin_check()) {
            return null;
        }
        
        return \App\Models\ActivityLog::log($action, $modelType, $modelId, $description, $properties);
    }
}

if (!function_exists('admin_is_impersonating')) {
    /**
     * Проверить, находится ли текущая сессия в режиме импровизации
     */
    function admin_is_impersonating(): bool
    {
        return session()->has('impersonator_id');
    }
}

if (!function_exists('admin_impersonator_name')) {
    /**
     * Получить имя исходного пользователя (если включена имперсонация)
     */
    function admin_impersonator_name(): ?string
    {
        return session('impersonator_name');
    }
}

if (!function_exists('mac_app_user')) {
    function mac_app_user(?\Illuminate\Http\Request $request = null): ?\App\Models\WordPress\User
    {
        $request ??= request();

        return $request?->attributes->get('mac_app_user');
    }
}

