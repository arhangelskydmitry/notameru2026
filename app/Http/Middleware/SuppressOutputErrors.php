<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware для подавления ошибок вывода
 * (заглушка - ничего не делает, просто пропускает запрос)
 */
class SuppressOutputErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
