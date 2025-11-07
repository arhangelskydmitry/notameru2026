<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerStats extends Model
{
    protected $fillable = [
        'banner_id',
        'date',
        'impressions',
        'clicks',
        'unique_impressions',
        'unique_clicks',
        'ctr',
    ];
    
    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'unique_impressions' => 'integer',
        'unique_clicks' => 'integer',
        'ctr' => 'decimal:2',
    ];
    
    /**
     * Баннер
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
