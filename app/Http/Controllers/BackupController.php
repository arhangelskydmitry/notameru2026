<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Показать список бекапов
     */
    public function index()
    {
        $backups = Backup::latest()->paginate(20);
        
        // Статистика
        $stats = [
            'total_backups' => Backup::count(),
            'completed_backups' => Backup::completed()->count(),
            'failed_backups' => Backup::failed()->count(),
            'total_size' => Backup::completed()->sum('size'),
            'last_backup' => Backup::completed()->latest()->first(),
        ];

        // Проверка доступного места
        $backupPath = storage_path('app/backups');
        $freeSpace = disk_free_space($backupPath);
        $totalSpace = disk_total_space($backupPath);

        $diskInfo = [
            'free' => $freeSpace,
            'total' => $totalSpace,
            'used' => $totalSpace - $freeSpace,
            'used_percent' => round(($totalSpace - $freeSpace) / $totalSpace * 100, 2),
        ];

        return view('admin.backups.index', compact('backups', 'stats', 'diskInfo'));
    }

    /**
     * Создать бекап вручную
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:full,database,files',
        ]);

        try {
            $backup = $this->backupService->create(
                $validated['type'],
                'user_' . auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Бекап успешно создан!',
                'backup' => [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'type' => $backup->type_name,
                    'size' => $backup->human_size,
                    'created_at' => $backup->created_at->format('d.m.Y H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка создания бекапа через веб-интерфейс: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания бекапа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Скачать бекап
     */
    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            abort(404, 'Бекап не завершен или недоступен');
        }

        $filePath = $backup->getFullPath();

        if (!file_exists($filePath)) {
            abort(404, 'Файл бекапа не найден');
        }

        Log::info('Скачивание бекапа', [
            'backup_id' => $backup->id,
            'filename' => $backup->filename,
            'user_id' => auth()->id(),
        ]);

        return Response::download($filePath, $backup->filename, [
            'Content-Type' => 'application/x-gzip',
        ]);
    }

    /**
     * Удалить бекап
     */
    public function destroy(Backup $backup)
    {
        try {
            $backup->deleteFile();
            $backup->delete();

            Log::info('Бекап удален', [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Бекап успешно удален',
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка удаления бекапа: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления бекапа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Показать страницу восстановления
     */
    public function restoreForm(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            abort(404, 'Бекап не завершен или недоступен');
        }

        return view('admin.backups.restore', compact('backup'));
    }

    /**
     * Восстановить из бекапа
     */
    public function restore(Request $request, Backup $backup)
    {
        // TODO: Реализовать восстановление из бекапа
        // Это сложная операция, требует дополнительной логики
        
        return response()->json([
            'success' => false,
            'message' => 'Функция восстановления находится в разработке',
        ], 501);
    }

    /**
     * Проверить статус создания бекапа
     */
    public function status(Backup $backup)
    {
        return response()->json([
            'id' => $backup->id,
            'status' => $backup->status,
            'status_name' => $backup->status_name,
            'progress' => $backup->status === 'in_progress' ? 50 : 100,
            'error' => $backup->error_message,
        ]);
    }

    /**
     * Запустить очистку вручную
     */
    public function cleanup()
    {
        try {
            $deleted = $this->backupService->applyRetentionPolicy();

            Log::info('Очистка бекапов выполнена', [
                'deleted_count' => $deleted,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Удалено бекапов: {$deleted}",
                'deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка очистки бекапов: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки: ' . $e->getMessage(),
            ], 500);
        }
    }
}
