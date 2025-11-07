<?php

namespace App\Observers;

use App\Models\WordPress\Post;
use App\Services\TelegramService;
use App\Services\VKService;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    private $telegramService;
    private $vkService;
    
    public function __construct(TelegramService $telegramService, VKService $vkService)
    {
        $this->telegramService = $telegramService;
        $this->vkService = $vkService;
    }
    
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post)
    {
        // Автопостинг только для опубликованных статей
        if ($post->post_type === 'post' && $post->post_status === 'publish') {
            $this->autoPost($post);
        }
    }
    
    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post)
    {
        // Проверяем, изменился ли статус на "publish"
        if ($post->post_type === 'post' && 
            $post->post_status === 'publish' && 
            $post->wasChanged('post_status')) {
            
            $this->autoPost($post);
        }
    }
    
    /**
     * Автопостинг в соцсети
     */
    private function autoPost(Post $post)
    {
        // Проверяем, не был ли уже отправлен (чтобы избежать дубликатов)
        $alreadyPosted = $post->getMeta('_auto_posted');
        if ($alreadyPosted) {
            return;
        }
        
        try {
            // Отправляем в Telegram
            if (config('services.telegram.bot_token')) {
                $telegramResult = $this->telegramService->sendPost($post);
                if ($telegramResult) {
                    Log::info("Post {$post->ID} sent to Telegram");
                }
            }
            
            // Отправляем в VK
            if (config('services.vk.access_token')) {
                $vkResult = $this->vkService->sendPost($post);
                if ($vkResult) {
                    Log::info("Post {$post->ID} sent to VK");
                }
            }
            
            // Помечаем пост как отправленный
            $post->setMeta('_auto_posted', time());
            
        } catch (\Exception $e) {
            Log::error("Auto-posting error for post {$post->ID}: " . $e->getMessage());
        }
    }
}

