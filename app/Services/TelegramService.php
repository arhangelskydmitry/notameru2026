<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private $botToken;
    private $channelId;
    
    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->channelId = config('services.telegram.channel_id');
    }
    
    /**
     * Отправить статью в Telegram канал
     */
    public function sendPost($post)
    {
        if (!$this->botToken || !$this->channelId) {
            Log::warning('Telegram credentials not configured');
            return false;
        }
        
        try {
            $message = $this->formatMessage($post);
            $imageUrl = $this->getFeaturedImage($post);
            
            if ($imageUrl) {
                // Отправка с изображением
                return $this->sendPhotoWithCaption($imageUrl, $message);
            } else {
                // Отправка только текста
                return $this->sendMessage($message);
            }
        } catch (\Exception $e) {
            Log::error('Telegram send error: ' . $e->getMessage());
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
        $excerpt = $post->post_excerpt ?: $this->getExcerpt($post->post_content, 300);
        
        // Форматируем сообщение с использованием HTML разметки Telegram
        $message = "<b>{$title}</b>\n\n";
        $message .= "{$excerpt}\n\n";
        $message .= "📖 <a href=\"{$url}\">Читать полностью</a>";
        
        // Добавляем категории как хештеги
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
     * Отправить сообщение с изображением
     */
    private function sendPhotoWithCaption($imageUrl, $caption)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";
        
        $response = Http::post($url, [
            'chat_id' => $this->channelId,
            'photo' => $imageUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ]);
        
        return $response->successful();
    }
    
    /**
     * Отправить текстовое сообщение
     */
    private function sendMessage($text)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $response = Http::post($url, [
            'chat_id' => $this->channelId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ]);
        
        return $response->successful();
    }
    
    /**
     * Получить excerpt из контента
     */
    private function getExcerpt($content, $length = 300)
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











