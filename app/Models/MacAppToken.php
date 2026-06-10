<?php

namespace App\Models;

use App\Models\WordPress\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MacAppToken extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    public static function issue(int $userId, ?string $deviceName = null, int $daysValid = 90): array
    {
        $plain = 'nm_mac_' . Str::random(48);
        $token = static::create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'device_name' => $deviceName,
            'expires_at' => now()->addDays($daysValid),
        ]);

        return ['token' => $plain, 'model' => $token];
    }

    public static function findByPlainToken(string $plain): ?self
    {
        if ($plain === '') {
            return null;
        }

        return static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
