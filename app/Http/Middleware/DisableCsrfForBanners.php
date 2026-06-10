<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Отключает CSRF проверку для banner tracking API
 */
class DisableCsrfForBanners
{
    /**
     * Маршруты, которые не требуют CSRF токена
     */
    protected $except = [
        'api/banner/*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                // Пропускаем CSRF проверку для этих маршрутов
                return $next($request);
            }
        }

        return $next($request);
    }
}
