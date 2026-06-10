<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Размеры изображений
     */
    const SIZE_LARGE = 1920;  // Оригинал (большой)
    const SIZE_MEDIUM = 800;  // Средний
    const SIZE_SMALL = 400;   // Маленький

    /**
     * Загрузка изображения с созданием трех размеров в WebP формате
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,gif,webp|max:51200', // 50MB
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('file');
            
            // Получаем alt и title из запроса
            $alt = $request->input('alt', '');
            $title = $request->input('title', '');
            
            // Если alt не указан, используем имя файла
            if (empty($alt)) {
                $alt = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
            
            // Создаем уникальное имя файла
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = Str::slug($filename) . '-' . time();
            
            // Папка для текущего месяца (YYYY/MM)
            $folder = date('Y/m');
            $fullPath = "imgnews/{$folder}";
            
            // Создаем папку, если не существует
            if (!file_exists(public_path($fullPath))) {
                mkdir(public_path($fullPath), 0755, true);
            }
            
            // Инициализируем Intervention Image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            
            // Получаем оригинальные размеры
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Массив для хранения путей к файлам
            $files = [];
            
            // 1. БОЛЬШОЙ (оригинал с ограничением) - ВСЕГДА WebP
            $largeImage = clone $image;
            if ($originalWidth > self::SIZE_LARGE) {
                $largeImage->scale(width: self::SIZE_LARGE);
            }
            $largePath = "{$fullPath}/{$filename}-large.webp";
            $largeImage->toWebp(quality: 90)->save(public_path($largePath));
            $files['large'] = [
                'url' => url($largePath),
                'path' => $largePath,
                'width' => $largeImage->width(),
                'height' => $largeImage->height(),
            ];
            
            // 2. СРЕДНИЙ - ВСЕГДА WebP
            $mediumImage = clone $image;
            $mediumImage->scale(width: self::SIZE_MEDIUM);
            $mediumPath = "{$fullPath}/{$filename}-medium.webp";
            $mediumImage->toWebp(quality: 85)->save(public_path($mediumPath));
            $files['medium'] = [
                'url' => url($mediumPath),
                'path' => $mediumPath,
                'width' => $mediumImage->width(),
                'height' => $mediumImage->height(),
            ];
            
            // 3. МАЛЕНЬКИЙ (миниатюра) - ВСЕГДА WebP
            $smallImage = clone $image;
            $smallImage->scale(width: self::SIZE_SMALL);
            $smallPath = "{$fullPath}/{$filename}-small.webp";
            $smallImage->toWebp(quality: 80)->save(public_path($smallPath));
            $files['small'] = [
                'url' => url($smallPath),
                'path' => $smallPath,
                'width' => $smallImage->width(),
                'height' => $smallImage->height(),
            ];
            
            // Возвращаем JSON с информацией о всех трех размерах
            return response()->json([
                'success' => true,
                'message' => 'Изображение загружено в WebP формате',
                'files' => $files,
                'location' => $files['large']['url'], // По умолчанию возвращаем большое
                'alt' => $alt,
                'title' => $title,
                'original' => [
                    'width' => $originalWidth,
                    'height' => $originalHeight,
                ],
                'format' => 'webp'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Загрузка для TinyMCE (с выбором размера)
     */
    public function uploadForTinyMCE(Request $request)
    {
        $result = $this->upload($request);
        $data = $result->getData(true);
        
        if (!$data['success']) {
            return $result;
        }
        
        // Определяем размер по параметру (по умолчанию large)
        $size = $request->input('size', 'large');
        
        if (!isset($data['files'][$size])) {
            $size = 'large';
        }
        
        return response()->json([
            'location' => $data['files'][$size]['url'],
            'sizes' => $data['files'], // Отправляем все размеры
        ]);
    }
}

