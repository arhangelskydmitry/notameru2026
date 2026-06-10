<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = admin_user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Доступ разрешен только суперадминистраторам.');
        }

        return $next($request);
    }
}





