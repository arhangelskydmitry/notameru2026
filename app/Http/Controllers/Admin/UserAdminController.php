<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPress\Post;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserAdminController extends Controller
{

    /**
     * Список пользователей
     */
    public function users()
    {
        $users = \App\Models\WordPress\User::with(['userRole.role', 'statistics'])
            ->withCount([
                'posts as total_posts' => function($query) {
                    $query->where('post_type', 'post')->where('post_status', 'publish');
                },
                'posts as draft_posts' => function($query) {
                    $query->where('post_type', 'post')->where('post_status', 'draft');
                }
            ])
            ->whereHas('userRole') // Только пользователи с ролями
            ->get()
            ->sortBy(function($user) {
                // Сортировка по иерархии ролей и активности
                $role = $user->getRole();
                
                if (!$role) {
                    return 9999; // В конец, если нет роли
                }
                
                // Порядок: super_admin (0) -> editor (1) -> author (2)
                $roleOrder = [
                    'super_admin' => 0,
                    'editor' => 100,
                    'author' => 200,
                ];
                
                $baseOrder = $roleOrder[$role->name] ?? 300;
                
                // Для авторов сортируем по количеству статей (больше статей = выше)
                if ($role->name === 'author') {
                    return $baseOrder - ($user->total_posts ?? 0);
                }
                
                // Для остальных ролей просто по уровню роли
                return $baseOrder;
            })
            ->take(50); // Ограничение до 50 пользователей
        
        return view('admin.users', compact('users'));
    }


    /**
     * Редактирование пользователя
     */
    public function editUser($id)
    {
        $user = \App\Models\WordPress\User::with(['userRole', 'statistics', 'pressCards'])->findOrFail($id);
        $roles = \App\Models\Role::orderBy('level', 'desc')->get();
        $categories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();
        
        return view('admin.user-edit', compact('user', 'roles', 'categories'));
    }


    /**
     * Обновление пользователя
     */
    public function updateUser(Request $request, $id)
    {
        $user = \App\Models\WordPress\User::findOrFail($id);
        
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'position' => 'nullable|string|max:255',
            'allowed_categories' => 'nullable|array',
        ]);
        
        // Обновляем пользователя
        $user->update([
            'display_name' => $validated['display_name'],
            'user_email' => $validated['user_email'],
        ]);
        
        // Обновляем роль
        \App\Models\UserRole::updateOrCreate(
            ['user_id' => $user->ID],
            [
                'role_id' => $validated['role_id'],
                'position' => $validated['position'],
                'allowed_categories' => $validated['allowed_categories'] ?? null,
            ]
        );
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Обновлен пользователь {$user->display_name}"
        );
        
        return redirect()->route('admin.users')->with('success', 'Пользователь успешно обновлен!');
    }


    /**
     * Профиль пользователя
     */
    public function profile()
    {
        $user = admin_user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        return view('admin.profile', compact('user'));
    }


    /**
     * Обновление профиля
     */
    public function updateProfile(Request $request)
    {
        $user = admin_user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        
        // Обновляем данные
        $user->update([
            'display_name' => $validated['display_name'],
            'user_email' => $validated['user_email'],
        ]);
        
        // Обновляем пароль admin_password (только hash — открытый текст в БД не храним)
        if ($request->filled('password')) {
            $user->update([
                'admin_password' => \Hash::make($validated['password']),
                'admin_password_plain' => null,
            ]);
        }
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Обновлен профиль"
        );
        
        return redirect()->route('admin.profile')->with('success', 'Профиль успешно обновлен!');
    }

    
    /**
     * Просмотр паролей всех пользователей (только для суперадмина)
     */
    public function viewPasswords()
    {
        $user = admin_user();
        
        // Проверяем права суперадмина
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        // Получаем всех пользователей с ролями (БЕЗ паролей в открытом виде!)
        $users = \App\Models\WordPress\User::whereHas('userRole')
            ->with(['userRole.role'])
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->ID,
                    'name' => $u->display_name,
                    'email' => $u->user_email,
                    'login' => $u->user_login,
                    'role' => $u->getRole()?->display_name,
                    'position' => $u->getPosition(),
                    'has_password' => !empty($u->admin_password),
                    'last_login' => $u->admin_last_login ? $u->admin_last_login->format('d.m.Y H:i') : 'Никогда',
                ];
            });
        
        return view('admin.passwords', compact('users'));
    }

    
    /**
     * Сброс пароля пользователя (только для суперадмина)
     * 
     * БЕЗОПАСНОСТЬ: Пароль показывается ОДИН раз и НЕ сохраняется в открытом виде!
     */
    public function resetPassword(Request $request, $userId)
    {
        $currentUser = admin_user();
        
        // Проверяем права суперадмина
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        $user = \App\Models\WordPress\User::findOrFail($userId);
        
        // Генерируем новый пароль
        $password = $this->generateSecurePassword();
        
        // БЕЗОПАСНО: Сохраняем только ХЕШ пароля, НЕ открытый текст!
        $user->update([
            'admin_password' => \Hash::make($password),
            // Удаляем plain пароль из БД если он был
            'admin_password_plain' => null,
        ]);
        
        // Логируем
        admin_log(
            \App\Models\ActivityLog::ACTION_UPDATED,
            \App\Models\WordPress\User::class,
            $user->ID,
            "Сброшен пароль пользователя {$user->display_name}"
        );
        
        // Показываем пароль ОДИН раз через flash-сообщение
        // После обновления страницы пароль будет недоступен
        return back()->with('new_password', [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'password' => $password,
        ]);
    }

    
    /**
     * Генерирует безопасный пароль
     */
    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%*-_+=';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Добавляем еще 5 случайных символов
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 5; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }



    /**
     * Войти под другим пользователем (только для суперадмина)
     */
    public function impersonateUser($id)
    {
        $currentUser = admin_user();

        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        $targetUser = User::with('userRole.role')->findOrFail($id);

        if ($targetUser->ID === $currentUser->ID) {
            return back()->with('error', 'Вы уже работаете под своим аккаунтом.');
        }

        // Сохраняем данные текущего пользователя, если режим имперсонации еще не активен
        if (!session()->has('impersonator_id')) {
            session([
                'impersonator_id' => $currentUser->ID,
                'impersonator_name' => $currentUser->display_name,
                'impersonator_role' => $currentUser->getRole()?->display_name,
            ]);
        }

        session([
            'admin_user_id' => $targetUser->ID,
            'admin_user_name' => $targetUser->display_name,
            'admin_user_role' => $targetUser->getRole()?->name,
        ]);

        admin_log(
            'impersonate_start',
            User::class,
            $targetUser->ID,
            "Суперадмин вошёл как {$targetUser->display_name}"
        );

        return redirect()->route('admin.dashboard')
            ->with('success', "Вы вошли как {$targetUser->display_name}. Нажмите «Выйти из режима» чтобы вернуться к своему аккаунту.");
    }


    /**
     * Остановить имперсонацию и вернуться к своему аккаунту
     */
    public function stopImpersonation()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Режим имперсонации не активен.');
        }

        $originalId = session('impersonator_id');
        $originalName = session('impersonator_name');
        $originalRole = session('impersonator_role');

        $targetUser = User::find(session('admin_user_id'));

        // Восстанавливаем сессию оригинального пользователя
        session([
            'admin_user_id' => $originalId,
            'admin_user_name' => $originalName,
            'admin_user_role' => $originalRole,
        ]);

        // Удаляем данные имперсонации
        session()->forget(['impersonator_id', 'impersonator_name', 'impersonator_role']);

        if ($targetUser) {
            admin_log(
                'impersonate_stop',
                User::class,
                $targetUser->ID,
                "Суперадмин вышел из режима имперсонации пользователя {$targetUser->display_name}"
            );
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Вы вернулись к своему аккаунту.');
    }
}
