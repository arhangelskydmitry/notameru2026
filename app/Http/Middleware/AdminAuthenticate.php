<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\WordPress\User;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('admin_user_id');

        if (!$userId) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Необходимо войти в систему'
                ], 401);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Необходимо войти в систему');
        }

        $user = User::with(['userRole.role'])->find($userId);

        if (!$user) {
            session()->forget(['admin_user_id', 'admin_user_name', 'admin_user_role']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Пользователь не найден'
                ], 401);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Пользователь не найден');
        }

        // Проверяем, активен ли аккаунт
        if (!$user->admin_account_active) {
            session()->forget(['admin_user_id', 'admin_user_name', 'admin_user_role']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ваш аккаунт заблокирован'
                ], 403);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Ваш аккаунт заблокирован. Обратитесь к администратору.');
        }

        // Проверяем наличие роли
        if (!$user->getRole()) {
            session()->forget(['admin_user_id', 'admin_user_name', 'admin_user_role']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'У вас нет доступа к админ-панели'
                ], 403);
            }
            return redirect()->route('admin.login')
                ->with('error', 'У вас нет доступа к админ-панели');
        }

        // Добавляем пользователя в request для использования в контроллерах
        $request->merge(['current_admin_user' => $user]);
        
        // Делаем пользователя доступным через helper
        view()->share('currentAdminUser', $user);

        return $next($request);
    }
}
