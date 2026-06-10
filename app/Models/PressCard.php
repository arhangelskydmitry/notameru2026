<?php

namespace App\Models;

use App\Models\WordPress\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PressCard extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'card_number',
        'full_name',
        'position',
        'organization',
        'photo_path',
        'issued_at',
        'expires_at',
        'status',
        'issued_by',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function wpUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'ID');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture() || $this->expires_at->isToday();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => $this->isActive() ? 'Действует' : 'Истекла',
            'revoked' => 'Отозвана',
            'expired' => 'Истекла',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        if ($this->status === 'revoked') {
            return 'danger';
        }
        if ($this->isActive()) {
            return 'success';
        }

        return 'secondary';
    }

    public function verifyUrl(): string
    {
        return url('/press-verify/' . $this->card_number);
    }

    public function photoUrl(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public static function generateCardNumber(): string
    {
        $year = now()->format('Y');
        $last = static::query()
            ->where('card_number', 'like', "NM-{$year}-%")
            ->orderByDesc('id')
            ->value('card_number');

        $seq = 1;
        if ($last && preg_match('/NM-' . $year . '-(\d+)/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('NM-%s-%04d', $year, $seq);
    }

    public static function syncExpiredStatuses(): void
    {
        static::query()
            ->where('status', 'active')
            ->whereDate('expires_at', '<', Carbon::today())
            ->update(['status' => 'expired']);
    }
}
