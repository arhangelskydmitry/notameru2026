<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для принудительного редиректа HTTP → HTTPS
 * 
 * Особенности:
 * - Не редиректит на localhost (для разработки)
 * - Не редиректит CLI запросы
 * - Использует 301 редирект (постоянный)
 * - Устанавливает HSTS header
 */
class ForceHttps
{
    /**
     * Домены, которые НЕ нужно редиректить (для разработки)
     */
    protected array $excludedHosts = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем CLI
        if (app()->runningInConsole()) {
            return $next($request);
        }
        
        // Пропускаем localhost
        $host = $request->getHost();
        if (in_array($host, $this->excludedHosts) || str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
            return $next($request);
        }
        
        // Проверяем, что запрос не через HTTPS
        if (!$request->secure()) {
            // Редирект на HTTPS (301 - постоянный)
            return redirect()->secure($request->getRequestUri(), 301);
        }
        
        // Продолжаем обработку
        $response = $next($request);
        
        // Добавляем HSTS header (Strict-Transport-Security)
        // Заставляет браузер всегда использовать HTTPS в течение 1 года
        if ($response instanceof Response) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
        
        return $response;
    }
}
