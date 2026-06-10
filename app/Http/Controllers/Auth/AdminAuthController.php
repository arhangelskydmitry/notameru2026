<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WordPress\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Показать форму входа
     */
    public function showLoginForm()
    {
        // Если уже авторизован, редирект на dashboard
        if (session('admin_user_id')) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('auth.admin-login');
    }

    /**
     * Обработка входа
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with(['userRole.role'])->where('user_email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Пользователь с таким email не найден.',
            ])->withInput();
        }

        // Проверяем наличие роли
        $role = $user->getRole();
        if (!$role) {
            return back()->withErrors([
                'email' => 'У вас нет доступа к админ-панели.',
            ])->withInput();
        }

        // Проверка пароля
        $passwordValid = false;
        
        // Сначала проверяем admin_password (Laravel bcrypt)
        if ($user->admin_password) {
            try {
                $passwordValid = Hash::check($request->password, $user->admin_password);
            } catch (\Exception $e) {
                // Если bcrypt не подходит, попробуем WordPress
                $passwordValid = false;
            }
        }
        
        // Если admin_password не подошел, пробуем user_pass (WordPress)
        if (!$passwordValid && $user->user_pass) {
            $passwordValid = $this->checkWordPressPassword($request->password, $user->user_pass);
        }
        
        if (!$passwordValid) {
            return back()->withErrors([
                'password' => 'Неверный пароль.',
            ])->withInput();
        }

        // На всякий случай очищаем режим имперсонации
        session()->forget(['impersonator_id', 'impersonator_name', 'impersonator_role']);

        // Сохраняем ID пользователя в сессии
        session([
            'admin_user_id' => $user->ID,
            'admin_user_name' => $user->display_name,
            'admin_user_role' => $role->name,
        ]);

        // Логируем вход
        ActivityLog::log(
            ActivityLog::ACTION_LOGIN,
            null,
            null,
            "Пользователь {$user->display_name} вошел в систему"
        );

        return redirect()->route('admin.dashboard')
            ->with('success', "Добро пожаловать, {$user->display_name}!");
    }

    /**
     * Выход из системы
     */
    public function logout(Request $request)
    {
        $userId = session('admin_user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Логируем выход
                ActivityLog::create([
                    'user_id' => $userId,
                    'action' => ActivityLog::ACTION_LOGOUT,
                    'description' => "Пользователь {$user->display_name} вышел из системы",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        }

        session()->forget([
            'admin_user_id',
            'admin_user_name',
            'admin_user_role',
            'impersonator_id',
            'impersonator_name',
            'impersonator_role',
        ]);
        session()->flush();

        return redirect()->route('admin.login')
            ->with('success', 'Вы успешно вышли из системы.');
    }

    /**
     * Проверка пароля WordPress
     * WordPress может использовать разные форматы хешей
     */
    private function checkWordPressPassword($password, $hash)
    {
        // Если пустой хеш, не разрешаем вход
        if (empty($hash)) {
            return false;
        }
        
        // 1. Новый формат WordPress: $wp$ + bcrypt ($2y$)
        // Пример: $wp$2y$10$nAixEFw2HGWNAwnPith5POto...
        if (substr($hash, 0, 4) === '$wp$') {
            // Убираем префикс $wp$ и проверяем как обычный bcrypt
            $bcryptHash = substr($hash, 4);
            return password_verify($password, $bcryptHash);
        }
        
        // 2. Старый phpass формат: $P$ или $H$
        if (strlen($hash) === 34 && (substr($hash, 0, 3) === '$P$' || substr($hash, 0, 3) === '$H$')) {
            return $this->checkPhpassPassword($password, $hash);
        }
        
        // 3. Прямой bcrypt без префикса $wp$
        if (substr($hash, 0, 4) === '$2y$' || substr($hash, 0, 4) === '$2a$' || substr($hash, 0, 4) === '$2b$') {
            return password_verify($password, $hash);
        }
        
        // Для остальных случаев пытаемся через phpass
        return $this->checkPhpassPassword($password, $hash);
    }

    /**
     * Проверка пароля phpass (WordPress)
     */
    private function checkPhpassPassword($password, $hash)
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        
        $output = '*0';
        if (substr($hash, 0, 2) === $output) {
            $output = '*1';
        }

        $id = substr($hash, 0, 3);
        if ($id !== '$P$' && $id !== '$H$') {
            return false;
        }

        $count_log2 = strpos($itoa64, $hash[3]);
        if ($count_log2 < 7 || $count_log2 > 30) {
            return false;
        }

        $count = 1 << $count_log2;
        $salt = substr($hash, 4, 8);
        
        if (strlen($salt) !== 8) {
            return false;
        }

        $hash_result = md5($salt . $password, true);
        do {
            $hash_result = md5($hash_result . $password, true);
        } while (--$count);

        $output = substr($hash, 0, 12);
        $output .= $this->encode64($hash_result, 16, $itoa64);

        return $output === $hash;
    }

    /**
     * Кодирование для phpass
     */
    private function encode64($input, $count, $itoa64)
    {
        $output = '';
        $i = 0;
        
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            
            $output .= $itoa64[($value >> 6) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            
            $output .= $itoa64[($value >> 12) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
