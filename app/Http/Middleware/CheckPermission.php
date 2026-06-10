<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Получаем текущего пользователя (из сессии или другого источника)
        // Пока используем фейковый пользователь для демонстрации
        // TODO: Интегрировать с реальной системой аутентификации
        
        $userId = session('admin_user_id');
        
        if (!$userId) {
            return redirect()->route('admin.login')
                ->with('error', 'Необходимо войти в систему');
        }
        
        $user = \App\Models\WordPress\User::find($userId);
        
        if (!$user) {
            return redirect()->route('admin.login')
                ->with('error', 'Пользователь не найден');
        }
        
        // Проверяем право доступа
        if (!$user->hasPermission($permission)) {
            abort(403, 'У вас нет прав для выполнения этого действия');
        }
        
        // Сохраняем пользователя в request для дальнейшего использования
        $request->merge(['current_admin_user' => $user]);
        
        return $next($request);
    }
}
