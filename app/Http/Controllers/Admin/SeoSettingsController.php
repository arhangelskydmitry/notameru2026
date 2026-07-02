<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPress\Post;
use App\Models\WordPress\Term;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SeoSettingsController extends Controller
{
    /**
     * Страница настроек SEO AI
     */
    public function seoSettings()
    {
        $seoGenerator = new \App\Services\SeoGeneratorService();
        $providers = $seoGenerator->getAvailableProviders();
        
        $settings = [
            'preferred_provider' => \App\Models\Setting::get('seo_ai_provider') ?? 'gigachat',
            'gigachat_client_id' => \App\Models\Setting::get('gigachat_client_id') ?? '',
            'gigachat_client_secret' => \App\Models\Setting::get('gigachat_client_secret') ?? '',
            'gigachat_scope' => \App\Models\Setting::get('gigachat_scope') ?? 'GIGACHAT_API_PERS',
            'openai_configured' => !empty(config('services.openai.api_key', env('OPENAI_API_KEY'))),
        ];
        
        return view('admin.seo-settings', compact('providers', 'settings'));
    }

    
    /**
     * Обновление настроек SEO AI
     */
    public function updateSeoSettings(Request $request)
    {
        $validated = $request->validate([
            'preferred_provider' => 'required|string|in:gigachat,openai,chatinfo',
            'gigachat_client_id' => 'nullable|string|max:500',
            'gigachat_client_secret' => 'nullable|string|max:500',
            'gigachat_scope' => 'nullable|string|in:GIGACHAT_API_PERS,GIGACHAT_API_CORP,GIGACHAT_API_B2B',
        ]);
        
        try {
            \App\Models\Setting::setMultiple([
                'seo_ai_provider' => $validated['preferred_provider'],
                'gigachat_client_id' => $validated['gigachat_client_id'] ?? '',
                'gigachat_client_secret' => $validated['gigachat_client_secret'] ?? '',
                'gigachat_scope' => $validated['gigachat_scope'] ?? 'GIGACHAT_API_PERS',
            ]);
            
            // Очищаем кеш токена GigaChat
            \Illuminate\Support\Facades\Cache::store('file')->forget('gigachat_access_token');
            
            admin_log(
                \App\Models\ActivityLog::ACTION_UPDATED,
                null,
                null,
                'Настройки SEO AI обновлены'
            );
            
            return redirect()->route('admin.seo-settings')
                ->with('success', 'Настройки SEO AI успешно сохранены!');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.seo-settings')
                ->with('error', 'Ошибка сохранения: ' . $e->getMessage());
        }
    }

    
    /**
     * Тестирование провайдера SEO AI
     */
    public function testSeoProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:gigachat,openai,chatinfo',
        ]);
        
        $seoGenerator = new \App\Services\SeoGeneratorService();
        $result = $seoGenerator->testProvider($validated['provider']);
        
        return response()->json($result);
    }
}
