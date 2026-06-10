<?php

namespace App\Http\Middleware;

use App\Models\MacAppToken;
use App\Models\WordPress\User;
use Closure;
use Illuminate\Http\Request;

class MacAppAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->bearerToken() ?? '';
        $record = MacAppToken::findByPlainToken($header);

        if (!$record) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::with(['userRole.role'])->find($record->user_id);
        if (!$user || !$user->getRole()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($user->admin_account_active === false) {
            return response()->json(['message' => 'Account disabled'], 403);
        }

        $record->update(['last_used_at' => now()]);
        $request->attributes->set('mac_app_user', $user);
        $request->attributes->set('mac_app_token', $record);

        return $next($request);
    }
}
