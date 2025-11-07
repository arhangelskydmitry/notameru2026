<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BannerZone;
use App\Models\BannerStats;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BannerController extends Controller
{
    /**
     * Список всех баннеров
     */
    public function index()
    {
        $banners = Banner::with(['bannerZone', 'stats'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Добавляем статистику к каждому баннеру
        $banners->each(function($banner) {
            $banner->total_stats = $banner->getTotalStats();
        });
        
        $zones = BannerZone::all();
        
        return view('admin.banners.index', compact('banners', 'zones'));
    }
    
    /**
     * Форма создания баннера
     */
    public function create()
    {
        $zones = BannerZone::all();
        return view('admin.banners.create', compact('zones'));
    }
    
    /**
     * Сохранение нового баннера
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:image,html,js',
            'content' => 'required|string',
            'link_url' => 'nullable|url|max:500',
            'zone' => 'required|exists:banner_zones,name',
            'priority' => 'required|integer|min:1|max:10',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,paused,expired',
            'target_blank' => 'boolean',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
        ]);
        
        $validated['target_blank'] = $request->has('target_blank');
        
        Banner::create($validated);
        
        return redirect()->route('admin.banners')
            ->with('success', 'Баннер успешно создан!');
    }
    
    /**
     * Форма редактирования баннера
     */
    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        $zones = BannerZone::all();
        $stats = $banner->getTotalStats();
        
        return view('admin.banners.edit', compact('banner', 'zones', 'stats'));
    }
    
    /**
     * Обновление баннера
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:image,html,js',
            'content' => 'required|string',
            'link_url' => 'nullable|url|max:500',
            'zone' => 'required|exists:banner_zones,name',
            'priority' => 'required|integer|min:1|max:10',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,paused,expired',
            'target_blank' => 'boolean',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
        ]);
        
        $validated['target_blank'] = $request->has('target_blank');
        
        $banner->update($validated);
        
        return redirect()->route('admin.banners')
            ->with('success', 'Баннер успешно обновлен!');
    }
    
    /**
     * Удаление баннера
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        
        return redirect()->route('admin.banners')
            ->with('success', 'Баннер удален!');
    }
    
    /**
     * Статистика баннера
     */
    public function statistics($id)
    {
        $banner = Banner::with('stats')->findOrFail($id);
        
        // Статистика за последние 30 дней
        $stats = BannerStats::where('banner_id', $id)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date', 'desc')
            ->get();
        
        // Общая статистика
        $totalStats = $banner->getTotalStats();
        
        // Данные для графика
        $chartData = [
            'dates' => $stats->pluck('date')->map(fn($d) => $d->format('d.m'))->reverse()->toArray(),
            'impressions' => $stats->pluck('impressions')->reverse()->toArray(),
            'clicks' => $stats->pluck('clicks')->reverse()->toArray(),
            'ctr' => $stats->pluck('ctr')->reverse()->toArray(),
        ];
        
        return view('admin.banners.statistics', compact('banner', 'stats', 'totalStats', 'chartData'));
    }
    
    /**
     * API: Записать показ (impression)
     */
    public function trackImpression(Request $request)
    {
        $bannerId = $request->input('banner_id');
        $banner = Banner::find($bannerId);
        
        if (!$banner) {
            return response()->json(['success' => false], 404);
        }
        
        $banner->recordImpression(
            $request->ip(),
            $request->userAgent()
        );
        
        return response()->json(['success' => true]);
    }
    
    /**
     * API: Записать клик
     */
    public function trackClick(Request $request)
    {
        $bannerId = $request->input('banner_id');
        $banner = Banner::find($bannerId);
        
        if (!$banner) {
            return response()->json(['success' => false], 404);
        }
        
        $banner->recordClick(
            $request->ip(),
            $request->userAgent()
        );
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Изменить статус баннера
     */
    public function toggleStatus($id)
    {
        $banner = Banner::findOrFail($id);
        
        if ($banner->status === 'active') {
            $banner->status = 'paused';
        } else {
            $banner->status = 'active';
        }
        
        $banner->save();
        
        return redirect()->back()
            ->with('success', 'Статус баннера изменен!');
    }
    
    /**
     * Предпросмотр баннера
     */
    public function preview($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.preview', compact('banner'));
    }
}
