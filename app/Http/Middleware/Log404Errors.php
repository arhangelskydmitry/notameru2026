<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\NotFoundLog;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Log404Errors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Если ответ - 404, логируем
        if ($response->status() === 404) {
            // Не логируем запросы к админке и статическим файлам
            $path = $request->path();
            
            $excludePatterns = [
                'notaadmin',
                'wp-admin',
                'wp-includes',
                'wp-content',
                'favicon.ico',
                'robots.txt',
                'sitemap',
                '.xml',
                '.txt',
                '.json',
                'apple-touch-icon',
            ];
            
            $shouldLog = true;
            foreach ($excludePatterns as $pattern) {
                if (str_contains($path, $pattern)) {
                    $shouldLog = false;
                    break;
                }
            }
            
            if ($shouldLog) {
                NotFoundLog::logNotFound($request);
            }
        }
        
        return $response;
    }
}
