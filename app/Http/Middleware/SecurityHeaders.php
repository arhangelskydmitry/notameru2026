<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для добавления заголовков безопасности
 * 
 * Добавляет:
 * - Content-Security-Policy (CSP) - защита от XSS
 * - X-Frame-Options - защита от clickjacking
 * - X-Content-Type-Options - защита от MIME sniffing
 * - X-XSS-Protection - дополнительная защита от XSS
 * - Referrer-Policy - контроль передачи referrer
 * - Permissions-Policy - контроль разрешений браузера
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Не добавляем заголовки для админки (могут мешать редактору)
        if ($request->is('notaadmin/*') || $request->is('notaadmin')) {
            return $this->addBasicSecurityHeaders($response);
        }
        
        // Полные заголовки безопасности для фронтенда
        return $this->addFullSecurityHeaders($response);
    }
    
    /**
     * Базовые заголовки безопасности (для админки)
     */
    protected function addBasicSecurityHeaders(Response $response): Response
    {
        // X-Frame-Options - защита от clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // X-Content-Type-Options - защита от MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // X-XSS-Protection - дополнительная защита XSS (для старых браузеров)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer-Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }
    
    /**
     * Полные заголовки безопасности (для фронтенда)
     */
    protected function addFullSecurityHeaders(Response $response): Response
    {
        // Базовые заголовки
        $response = $this->addBasicSecurityHeaders($response);
        
        // Content-Security-Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Permissions-Policy (бывший Feature-Policy)
        $response->headers->set('Permissions-Policy', implode(', ', [
            'accelerometer=()',
            'camera=()',
            'geolocation=()',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=()',
            'usb=()',
        ]));
        
        return $response;
    }
    
    /**
     * Построение Content-Security-Policy
     */
    protected function buildContentSecurityPolicy(): string
    {
        $policies = [
            // Источники по умолчанию
            "default-src 'self'",
            
            // Скрипты
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " . implode(' ', [
                'https://mc.yandex.ru',            // Яндекс.Метрика (старый домен)
                'https://mc.yandex.com',           // Яндекс.Метрика (новый домен)
                'https://yastatic.net',            // Яндекс статика
                'https://metrika.yandex.ru',       // Яндекс.Метрика статика
                'https://code.jquery.com',         // jQuery CDN
                'https://cdn.jsdelivr.net',        // jsDelivr CDN
                'https://cdnjs.cloudflare.com',    // Cloudflare CDN
                'https://www.googletagmanager.com', // Google Tag Manager
                'https://www.google-analytics.com', // Google Analytics
            ]),
            
            // Стили
            "style-src 'self' 'unsafe-inline' " . implode(' ', [
                'https://fonts.googleapis.com',    // Google Fonts
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
            ]),
            
            // Изображения
            "img-src 'self' data: blob: " . implode(' ', [
                'https://*.yandex.ru',
                'https://*.yandex.net',
                'https://*.yandex.com',            // Яндекс.Метрика домены
                'https://mc.yandex.ru',
                'https://mc.yandex.com',           // Яндекс.Метрика
                'https://www.google-analytics.com',
                'https://www.googletagmanager.com',
                'https://*.googleapis.com',
                'https://i.ytimg.com',             // YouTube thumbnails
                'https://img.youtube.com',
            ]),
            
            // Шрифты
            "font-src 'self' data: " . implode(' ', [
                'https://fonts.gstatic.com',
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
            ]),
            
            // Фреймы (для YouTube, Vimeo и т.д.)
            "frame-src 'self' " . implode(' ', [
                'https://www.youtube.com',
                'https://www.youtube-nocookie.com',
                'https://player.vimeo.com',
                'https://rutube.ru',
                'https://ok.ru',
                'https://vk.com',
                'https://music.yandex.ru',
                'https://open.spotify.com',
            ]),
            
            // AJAX/Fetch запросы
            "connect-src 'self' " . implode(' ', [
                'https://mc.yandex.ru',            // Яндекс.Метрика (старый)
                'https://mc.yandex.com',           // Яндекс.Метрика (новый)
                'https://*.yandex.ru',
                'https://*.yandex.com',
                'https://yandex.ru',
                'https://yandex.com',
                'https://www.google-analytics.com',
                'https://www.googletagmanager.com',
            ]),
            
            // Медиа (video, audio)
            "media-src 'self' blob: " . implode(' ', [
                'https://*.youtube.com',
                'https://*.vimeo.com',
            ]),
            
            // Object (flash и т.д.) - запрещаем
            "object-src 'none'",
            
            // Base URI
            "base-uri 'self'",
            
            // Form action
            "form-action 'self'",
            
            // Frame ancestors (защита от clickjacking)
            "frame-ancestors 'self'",
        ];
        
        return implode('; ', $policies);
    }
}
