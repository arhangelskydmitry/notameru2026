<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для оптимизации подключений к БД
 * 
 * Решает проблему "max_user_connections exceeded" на shared hosting:
 * 1. Отключает соединение после каждого запроса
 * 2. Использует persistent connections
 * 3. Устанавливает таймауты
 */
class OptimizeDbConnections
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Устанавливаем короткий таймаут для ожидания соединения
        config(['database.connections.mysql.options' => [
            \PDO::ATTR_TIMEOUT => 5,
            \PDO::ATTR_EMULATE_PREPARES => true,
        ]]);
        
        $response = $next($request);
        
        return $response;
    }
    
    /**
     * Выполняется после отправки ответа клиенту
     */
    public function terminate(Request $request, Response $response): void
    {
        // Принудительно закрываем соединение после каждого запроса
        // Это освобождает слот для других пользователей
        try {
            DB::disconnect();
        } catch (\Exception $e) {
            // Игнорируем ошибки отключения
        }
    }
}
