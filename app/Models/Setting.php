<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Получить значение настройки
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Установить значение настройки
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        
        // Очищаем кеш для этой настройки
        Cache::forget("setting_{$key}");
    }

    /**
     * Получить несколько настроек
     */
    public static function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::get($key);
        }
        return $result;
    }

    /**
     * Установить несколько настроек
     */
    public static function setMultiple(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value);
        }
    }
}





