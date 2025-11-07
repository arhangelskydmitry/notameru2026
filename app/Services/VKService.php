<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VKService
{
    private $accessToken;
    private $groupId;
    private $apiVersion = '5.131';
    
    public function __construct()
    {
        $this->accessToken = config('services.vk.access_token');
        $this->groupId = config('services.vk.group_id');
    }
    
    /**
     * Отправить статью в группу VK
     */
    public function sendPost($post)
    {
        if (!$this->accessToken || !$this->groupId) {
            Log::warning('VK credentials not configured');
            return false;
        }
        
        try {
            $message = $this->formatMessage($post);
            $imageUrl = $this->getFeaturedImage($post);
            
            $attachments = [];
            
            // Загружаем изображение если есть
            if ($imageUrl) {
                $photoAttachment = $this->uploadPhoto($imageUrl);
                if ($photoAttachment) {
                    $attachments[] = $photoAttachment;
                }
            }
            
            // Публикуем пост
            return $this->wallPost($message, $attachments);
            
        } catch (\Exception $e) {
            Log::error('VK send error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Форматирование сообщения
     */
    private function formatMessage($post)
    {
        $title = $post->post_title;
        $url = route('post', $post->post_name);
        
        // Получаем краткое описание
        $excerpt = $post->post_excerpt ?: $this->getExcerpt($post->post_content, 400);
        
        $message = "{$title}\n\n";
        $message .= "{$excerpt}\n\n";
        $message .= "Читать полностью: {$url}";
        
        // Добавляем хештеги
        $tags = [];
        foreach ($post->categories as $category) {
            $tag = '#' . str_replace([' ', '-'], '_', transliterator_transliterate(
                'Any-Latin; Latin-ASCII', 
                $category->term->name
            ));
            $tags[] = $tag;
        }
        
        if (!empty($tags)) {
            $message .= "\n\n" . implode(' ', $tags);
        }
        
        return $message;
    }
    
    /**
     * Загрузить фото на сервер VK
     */
    private function uploadPhoto($imageUrl)
    {
        try {
            // 1. Получаем URL для загрузки
            $uploadServer = $this->getWallUploadServer();
            if (!$uploadServer) {
                return null;
            }
            
            // 2. Загружаем изображение
            $uploadResult = $this->uploadImageToServer($uploadServer, $imageUrl);
            if (!$uploadResult) {
                return null;
            }
            
            // 3. Сохраняем фото
            $savedPhoto = $this->saveWallPhoto($uploadResult);
            if (!$savedPhoto) {
                return null;
            }
            
            return "photo{$savedPhoto['owner_id']}_{$savedPhoto['id']}";
            
        } catch (\Exception $e) {
            Log::error('VK photo upload error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получить сервер для загрузки
     */
    private function getWallUploadServer()
    {
        $response = Http::get('https://api.vk.com/method/photos.getWallUploadServer', [
            'group_id' => $this->groupId,
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
        ]);
        
        $data = $response->json();
        
        if (isset($data['response']['upload_url'])) {
            return $data['response']['upload_url'];
        }
        
        return null;
    }
    
    /**
     * Загрузить изображение на сервер VK
     */
    private function uploadImageToServer($uploadUrl, $imageUrl)
    {
        // Скачиваем изображение
        $imageContent = file_get_contents($imageUrl);
        if (!$imageContent) {
            return null;
        }
        
        // Загружаем на сервер VK
        $response = Http::attach(
            'photo', $imageContent, 'photo.jpg'
        )->post($uploadUrl);
        
        return $response->json();
    }
    
    /**
     * Сохранить фото на стене
     */
    private function saveWallPhoto($uploadResult)
    {
        $response = Http::post('https://api.vk.com/method/photos.saveWallPhoto', [
            'group_id' => $this->groupId,
            'photo' => $uploadResult['photo'],
            'server' => $uploadResult['server'],
            'hash' => $uploadResult['hash'],
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
        ]);
        
        $data = $response->json();
        
        if (isset($data['response'][0])) {
            return $data['response'][0];
        }
        
        return null;
    }
    
    /**
     * Опубликовать пост на стене
     */
    private function wallPost($message, $attachments = [])
    {
        $params = [
            'owner_id' => '-' . $this->groupId,
            'from_group' => 1,
            'message' => $message,
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
        ];
        
        if (!empty($attachments)) {
            $params['attachments'] = implode(',', $attachments);
        }
        
        $response = Http::post('https://api.vk.com/method/wall.post', $params);
        
        $data = $response->json();
        
        return isset($data['response']['post_id']);
    }
    
    /**
     * Получить excerpt из контента
     */
    private function getExcerpt($content, $length = 400)
    {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '...';
        }
        
        return $text;
    }
    
    /**
     * Получить featured image
     */
    private function getFeaturedImage($post)
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        
        if ($thumbnailId) {
            $attachment = \App\Models\WordPress\Post::find($thumbnailId);
            if ($attachment && $attachment->guid) {
                $path = $attachment->guid;
                // Конвертируем путь к WebP
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $path)) {
                    $filename = basename($path);
                    $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                    return url('/imgnews/' . $filename);
                }
                return $path;
            }
        }
        
        return null;
    }
}

