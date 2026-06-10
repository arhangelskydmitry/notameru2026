<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MaxBotService
{
    public function isConfigured(): bool
    {
        return (bool) config('max.enabled', false)
            && $this->token() !== ''
            && $this->chatId() !== '';
    }

    public function chatId(): string
    {
        return trim((string) config('max.chat_id', ''));
    }

    public function sendMessageToConfiguredChat(string $text): array
    {
        return $this->sendMessage($this->chatId(), $text);
    }

    public function sendMessage(string $chatId, string $text): array
    {
        $token = $this->token();
        if ($token === '') {
            throw new RuntimeException('MAX_BOT_TOKEN is not configured.');
        }

        if (trim($chatId) === '') {
            throw new RuntimeException('MAX_CHAT_ID is not configured.');
        }

        $baseUrl = rtrim((string) config('max.api_base', 'https://platform-api.max.ru'), '/');
        $format = trim((string) config('max.message_format', 'markdown'));

        $endpoint = $baseUrl . '/messages?' . http_build_query([
            'chat_id' => $chatId,
        ]);

        $response = Http::timeout((int) config('max.http_timeout', 15))
            ->withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($endpoint, [
                'text' => $text,
                'format' => $format,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('MAX API error: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function token(): string
    {
        return trim((string) config('max.bot_token', ''));
    }
}
