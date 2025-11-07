<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerZone extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'max_banners',
        'recommended_width',
        'recommended_height',
    ];
    
    protected $casts = [
        'max_banners' => 'integer',
        'recommended_width' => 'integer',
        'recommended_height' => 'integer',
    ];
    
    /**
     * Баннеры в этой зоне
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'zone', 'name');
    }
    
    /**
     * Активные баннеры в зоне
     */
    public function activeBanners(): HasMany
    {
        return $this->banners()
            ->active()
            ->byPriority();
    }
}
