<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'type',
        'size',
        'status',
        'storage',
        'storage_path',
        'triggered_by',
        'manifest',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'manifest' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'size' => 'integer',
    ];

    /**
     * Получить читаемый размер файла
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Получить длительность создания бекапа
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $seconds = $this->completed_at->diffInSeconds($this->started_at);
        
        if ($seconds < 60) {
            return $seconds . ' сек';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . ' мин ' . ($seconds % 60) . ' сек';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' ч ' . $minutes . ' мин';
        }
    }

    /**
     * Получить иконку типа бекапа
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'full' => '📦',
            'database' => '🗄️',
            'files' => '📁',
            default => '📄'
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'completed' => '✅',
            'in_progress' => '⏳',
            'failed' => '❌',
            default => '❓'
        };
    }

    /**
     * Получить перевод типа
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'full' => 'Полный бекап',
            'database' => 'Только БД',
            'files' => 'Только файлы',
            default => 'Неизвестно'
        };
    }

    /**
     * Получить перевод статуса
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'completed' => 'Завершен',
            'in_progress' => 'В процессе',
            'failed' => 'Ошибка',
            default => 'Неизвестно'
        };
    }

    /**
     * Проверить существование файла
     */
    public function fileExists(): bool
    {
        if ($this->storage === 'local') {
            $path = storage_path('app/backups/' . $this->filename);
            return file_exists($path);
        }
        
        return false; // Для remote проверка отдельно
    }

    /**
     * Получить полный путь к файлу
     */
    public function getFullPath(): string
    {
        if ($this->storage === 'local') {
            return storage_path('app/backups/' . $this->filename);
        }
        
        return $this->storage_path ?? '';
    }

    /**
     * Удалить файл бекапа
     */
    public function deleteFile(): bool
    {
        if ($this->storage === 'local') {
            $path = $this->getFullPath();
            if (file_exists($path)) {
                return unlink($path);
            }
        }
        
        return false;
    }

    /**
     * Scope: только завершенные бекапы
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: только ошибочные бекапы
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: сортировка по дате (свежие первые)
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
