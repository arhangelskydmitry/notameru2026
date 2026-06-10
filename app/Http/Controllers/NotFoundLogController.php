<?php

namespace App\Http\Controllers;

use App\Models\NotFoundLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotFoundLogController extends Controller
{
    /**
     * Главная страница статистики 404
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '7'); // дней
        $dateFrom = now()->subDays((int)$period);
        
        // Общая статистика
        $totalHits = NotFoundLog::where('created_at', '>=', $dateFrom)->count();
        $uniqueUrls = NotFoundLog::where('created_at', '>=', $dateFrom)
            ->distinct('url')
            ->count('url');
        
        // Топ URL
        $topUrls = NotFoundLog::selectRaw('url, COUNT(*) as hits, MAX(created_at) as last_hit')
            ->where('created_at', '>=', $dateFrom)
            ->groupBy('url')
            ->orderByDesc('hits')
            ->limit(50)
            ->get();
        
        // Топ рефереров
        $topReferers = NotFoundLog::selectRaw('referer, COUNT(*) as hits')
            ->where('created_at', '>=', $dateFrom)
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->groupBy('referer')
            ->orderByDesc('hits')
            ->limit(30)
            ->get();
        
        // Последние 404
        $recentLogs = NotFoundLog::where('created_at', '>=', $dateFrom)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
        
        // График по дням
        $dailyStats = NotFoundLog::selectRaw('DATE(created_at) as date, COUNT(*) as hits')
            ->where('created_at', '>=', $dateFrom)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        return view('admin.not-found-logs.index', compact(
            'totalHits',
            'uniqueUrls',
            'topUrls',
            'topReferers',
            'recentLogs',
            'dailyStats',
            'period'
        ));
    }

    /**
     * Детали конкретного URL
     */
    public function details(Request $request)
    {
        $url = $request->get('url');
        
        if (!$url) {
            return redirect()->route('admin.404-logs.index');
        }
        
        $period = $request->get('period', '30');
        $dateFrom = now()->subDays((int)$period);
        
        // Все записи для этого URL
        $logs = NotFoundLog::where('url', $url)
            ->where('created_at', '>=', $dateFrom)
            ->orderByDesc('created_at')
            ->paginate(50);
        
        // Статистика
        $totalHits = NotFoundLog::where('url', $url)
            ->where('created_at', '>=', $dateFrom)
            ->count();
        
        $uniqueReferers = NotFoundLog::where('url', $url)
            ->where('created_at', '>=', $dateFrom)
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->distinct('referer')
            ->count('referer');
        
        $topReferers = NotFoundLog::selectRaw('referer, COUNT(*) as hits')
            ->where('url', $url)
            ->where('created_at', '>=', $dateFrom)
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->groupBy('referer')
            ->orderByDesc('hits')
            ->limit(20)
            ->get();
        
        return view('admin.not-found-logs.details', compact(
            'url',
            'logs',
            'totalHits',
            'uniqueReferers',
            'topReferers',
            'period'
        ));
    }

    /**
     * Очистка старых логов
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);
        
        $deleted = NotFoundLog::cleanOldLogs($validated['days']);
        
        return back()->with('success', "Удалено {$deleted} записей старше {$validated['days']} дней");
    }

    /**
     * Экспорт в CSV
     */
    public function export(Request $request)
    {
        $period = $request->get('period', '30');
        $dateFrom = now()->subDays((int)$period);
        
        $logs = NotFoundLog::where('created_at', '>=', $dateFrom)
            ->orderByDesc('created_at')
            ->get();
        
        $filename = 'notame_404_logs_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // BOM для корректного отображения в Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Заголовки
            fputcsv($file, ['Дата/Время', 'URL', 'Откуда', 'IP', 'User Agent'], ';');
            
            // Данные
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->url,
                    $log->referer ?? 'Прямой переход',
                    $log->ip_address,
                    $log->user_agent,
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
