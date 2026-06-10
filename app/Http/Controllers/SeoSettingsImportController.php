<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SeoSettingsImportController extends Controller
{
    /**
     * Показать страницу импорта настроек
     */
    public function showImportForm()
    {
        return view('admin.seo-settings-import');
    }

    /**
     * Обработать импорт настроек из JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json,txt|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('settings_file');
            $json = file_get_contents($file->getRealPath());
            $settings = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Ошибка: Неверный формат JSON - ' . json_last_error_msg());
            }

            $imported = 0;
            $skipped = 0;
            $openaiKey = null;

            foreach ($settings as $key => $value) {
                if (empty($value)) {
                    $skipped++;
                    continue;
                }

                // OpenAI API ключ нужно добавить в .env, а не в БД
                if ($key === 'openai_api_key') {
                    $openaiKey = $value;
                    continue;
                }

                try {
                    // Используем INSERT ... ON DUPLICATE KEY UPDATE
                    DB::table('settings')->insertOrIgnore([
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Обновляем, если запись уже существует
                    DB::table('settings')
                        ->where('key', $key)
                        ->update([
                            'value' => $value,
                            'updated_at' => now(),
                        ]);

                    $imported++;
                } catch (\Exception $e) {
                    \Log::error("Ошибка при импорте {$key}: " . $e->getMessage());
                }
            }

            // Очищаем кеш токена GigaChat
            Cache::store('file')->forget('gigachat_access_token');
            Cache::flush();

            $message = "✅ Импортировано: {$imported} настроек";
            if ($skipped > 0) {
                $message .= ", пропущено: {$skipped}";
            }
            if ($openaiKey) {
                $message .= "\n\n⚠️ OpenAI API ключ нужно добавить в .env файл:\nOPENAI_API_KEY=" . substr($openaiKey, 0, 20) . "...";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Ошибка импорта настроек: ' . $e->getMessage());
            return back()->with('error', 'Ошибка импорта: ' . $e->getMessage());
        }
    }
}
