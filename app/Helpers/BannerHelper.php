<?php

namespace App\Helpers;

use App\Models\Banner;

class BannerHelper
{
    /**
     * Получить баннер для зоны
     * 
     * @param string $zone Название зоны (header, sidebar-top, etc.)
     * @param bool $track Записывать ли показ (по умолчанию true)
     * @return string HTML код баннера
     */
    public static function show(string $zone, bool $track = true): string
    {
        // Получаем активные баннеры для зоны
        $banners = Banner::active()
            ->inZone($zone)
            ->byPriority()
            ->get();
        
        if ($banners->isEmpty()) {
            return '';
        }
        
        // Выбираем случайный баннер с учетом приоритета
        $banner = self::selectBannerByPriority($banners);
        
        if (!$banner) {
            return '';
        }
        
        // Записываем показ
        if ($track) {
            $banner->recordImpression(
                request()->ip(),
                request()->userAgent()
            );
        }
        
        // Генерируем HTML с трекингом клика
        $html = '<div class="banner-container" data-zone="' . $zone . '" data-banner-id="' . $banner->id . '">';
        $html .= $banner->getHtml();
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Выбрать баннер с учетом приоритета
     * 
     * @param \Illuminate\Database\Eloquent\Collection $banners
     * @return \App\Models\Banner|null
     */
    protected static function selectBannerByPriority($banners)
    {
        // Создаем взвешенный массив
        $weighted = [];
        
        foreach ($banners as $banner) {
            // Добавляем баннер N раз, где N = priority
            for ($i = 0; $i < $banner->priority; $i++) {
                $weighted[] = $banner;
            }
        }
        
        if (empty($weighted)) {
            return null;
        }
        
        // Выбираем случайный элемент
        return $weighted[array_rand($weighted)];
    }
    
    /**
     * Получить все баннеры для зоны (без случайного выбора)
     * 
     * @param string $zone
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForZone(string $zone)
    {
        return Banner::active()
            ->inZone($zone)
            ->byPriority()
            ->get();
    }
    
    /**
     * Проверить, есть ли активные баннеры в зоне
     * 
     * @param string $zone
     * @return bool
     */
    public static function hasActiveBanners(string $zone): bool
    {
        return Banner::active()
            ->inZone($zone)
            ->exists();
    }
}




