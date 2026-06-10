<?php

namespace App\Http\Controllers\Api\Mac;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MacAppToken;
use App\Models\WordPress\User;
use App\Services\AdminPasswordValidator;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, AdminPasswordValidator $passwords)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        $user = User::with(['userRole.role'])->where('user_email', $validated['email'])->first();
        if (!$user || !$user->getRole()) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        if (!$passwords->validate($user, $validated['password'])) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        if ($user->admin_account_active === false) {
            return response()->json(['message' => 'Аккаунт отключён'], 403);
        }

        $issued = MacAppToken::issue($user->ID, $validated['device_name'] ?? 'Nota Miru Mac');

        ActivityLog::log(ActivityLog::ACTION_LOGIN, null, null, "Mac App: {$user->display_name} вошёл в систему");

        return response()->json([
            'token' => $issued['token'],
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload(mac_app_user($request)),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('mac_app_token');
        $token?->delete();

        return response()->json(['ok' => true]);
    }

    private function userPayload(User $user): array
    {
        $role = $user->getRole();

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'position' => $user->getPosition(),
            'role' => $role?->name,
            'role_label' => $role?->display_name,
            'is_super_admin' => $user->isSuperAdmin(),
            'is_editor' => $user->isEditor(),
        ];
    }
}
