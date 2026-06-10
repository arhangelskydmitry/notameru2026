<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Получить активные счетчики для указанной позиции
     */
    public static function getActiveForPosition(string $position = 'sidebar')
    {
        return static::where('is_active', true)
            ->where('position', $position)
            ->orderBy('sort_order')
            ->get();
    }
}
