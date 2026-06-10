<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с ChatInfo API
 * Документация: https://chatinfo.ru/api-docs
 * 
 * ChatInfo предоставляет совместимый с ChatGPT API эндпоинт
 * Использует модель GPT-4o
 */
class ChatInfoService
{
    protected string $apiUrl = 'https://chatinfo.ru/v1';
    protected ?string $apiKey;
    protected ?object $client = null;

    public function __construct()
    {
        $this->apiKey = \App\Models\Setting::get('chatinfo_api_key') ?? config('services.chatinfo.api_key');
    }

    /**
     * Находит путь к SSL сертификатам безопасно
     */
    protected function findCertPath()
    {
        // Стандартные пути (без Mac-специфичных)
        $paths = [
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/certs/ca-bundle.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/cert.pem',
        ];
        
        // Mac пути только если не на сервере
        if (PHP_OS === 'Darwin') {
            array_unshift($paths, '/opt/homebrew/etc/ca-certificates/cert.pem');
        }
        
        foreach ($paths as $path) {
            try {
                if (@file_exists($path)) {
                    return $path;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return true; // Системные сертификаты
    }

    /**
     * Проверяет, настроен ли сервис
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Инициализация клиента (совместимый с OpenAI API)
     */
    protected function getClient(): object
    {
        if ($this->client) {
            return $this->client;
        }

        if (!$this->isConfigured()) {
            throw new \Exception('ChatInfo API ключ не настроен');
        }

        try {
            // Определяем путь к сертификатам безопасно
            $certPath = $this->findCertPath();

            $this->client = \OpenAI::factory()
                ->withApiKey($this->apiKey)
                ->withBaseUri($this->apiUrl)
                ->withHttpClient(new \GuzzleHttp\Client([
                    'verify' => $certPath,
                    'timeout' => 60,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                    ],
                ]))
                ->make();

            return $this->client;
        } catch (\Exception $e) {
            Log::error('ChatInfo client initialization failed: ' . $e->getMessage());
            throw new \Exception('Не удалось инициализировать ChatInfo клиент: ' . $e->getMessage());
        }
    }

    /**
     * Генерация текста через ChatInfo
     */
    public function generateText(string $prompt, float $temperature = 0.7, int $maxTokens = 2000): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();
            
            $response = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            return $response->choices[0]->message->content ?? null;

        } catch (\Exception $e) {
            Log::error('ChatInfo generateText error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Генерирует SEO-данные для статьи
     */
    public function generateSeoData(string $title, ?string $excerpt = null, ?string $content = null): array
    {
        // Извлекаем первое изображение из контента
        $firstImage = $content ? $this->extractFirstImage($content) : null;
        
        // Очищаем excerpt и контент
        $cleanExcerpt = $excerpt ? strip_tags($excerpt) : '';
        $cleanContent = $content ? strip_tags($content) : '';
        
        // Для ключевых слов используем excerpt + title, плюс первые 1000 символов контента
        $keywordsSource = trim($cleanExcerpt . ' ' . $title);
        if ($cleanContent) {
            $keywordsSource .= ' ' . mb_substr($cleanContent, 0, 1000);
        }
        
        $prompt = $this->buildSeoPrompt($title, $cleanExcerpt, $keywordsSource);
        
        try {
            $client = $this->getClient();
            
            $response = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты — профессиональный SEO-эксперт для новостного сайта на русском языке. Твоя задача — создавать качественные и оптимизированные SEO-метаданные. Отвечай только в формате JSON без дополнительного текста.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1500,
            ]);
            
            $responseContent = $response->choices[0]->message->content ?? null;
            
            if (!$responseContent) {
                throw new \Exception('Пустой ответ от ChatInfo');
            }
            
            return $this->parseSeoResponse($responseContent, $title, $cleanExcerpt ?: $cleanContent, $firstImage);
            
        } catch (\Exception $e) {
            Log::error('ChatInfo SEO generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Формирует промпт для генерации SEO
     */
    protected function buildSeoPrompt(string $title, string $excerpt, string $keywordsSource): string
    {
        return "Ты — профессиональный SEO-копирайтер. Создай SEO-метаданные для новостной статьи.

**ОРИГИНАЛЬНОЕ НАЗВАНИЕ СТАТЬИ:** {$title}

**КРАТКОЕ ОПИСАНИЕ (EXCERPT):**
{$excerpt}

**КОНТЕКСТ ДЛЯ КЛЮЧЕВЫХ СЛОВ:**
{$keywordsSource}

**КРИТИЧЕСКИ ВАЖНО:**
1. **seo_title** — ОБЯЗАТЕЛЬНО сделай РЕРАЙТ (перефразирование) оригинального названия. НЕ копируй и НЕ обрезай оригинал! Создай НОВОЕ название (50-60 символов), которое передаёт ту же суть, но другими словами.

2. **seo_description** — создай НОВОЕ описание на основе excerpt и названия. НЕ копируй excerpt дословно! Переформулируй, сделай более привлекательным и информативным (150-160 символов).

3. **focus_keyword** — ОБЯЗАТЕЛЬНО укажи одно главное ключевое слово (1-3 слова), которое лучше всего описывает статью.

4. **seo_keywords** — ОБЯЗАТЕЛЬНО извлеки 5-7 релевантных ключевых слов/фраз из контекста.

5. **og_title** и **twitter_title** — могут быть такими же как seo_title или немного отличаться для соцсетей.

6. **og_description** и **twitter_description** — могут быть такими же как seo_description или более эмоциональными для соцсетей.

**ФОРМАТ ОТВЕТА (строго JSON, без дополнительного текста):**
{
  \"seo_title\": \"РЕРАЙТ названия статьи (50-60 символов, НЕ копия оригинала)\",
  \"seo_description\": \"Новое описание на основе excerpt (150-160 символов, НЕ копия excerpt)\",
  \"focus_keyword\": \"Главное ключевое слово (ОБЯЗАТЕЛЬНО заполнить)\",
  \"seo_keywords\": [\"ключевое слово 1\", \"ключевое слово 2\", \"ключевое слово 3\", \"ключевое слово 4\", \"ключевое слово 5\"],
  \"og_title\": \"Заголовок для соцсетей (до 60 символов)\",
  \"og_description\": \"Описание для соцсетей (до 160 символов)\",
  \"twitter_title\": \"Заголовок для Twitter/X (до 60 символов)\",
  \"twitter_description\": \"Описание для Twitter/X (до 160 символов)\"
}

**ПОМНИ:** focus_keyword и seo_keywords НЕ должны быть пустыми! Все тексты на русском языке.";
    }

    /**
     * Парсит ответ от ИИ
     */
    protected function parseSeoResponse(string $response, string $title, string $description, ?string $image): array
    {
        // Извлекаем JSON из ответа
        $json = $response;
        
        // Убираем markdown блоки если есть
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $json = $matches[1];
        }
        
        $data = json_decode($json, true);
        
        if (!$data) {
            Log::warning('Failed to parse ChatInfo SEO response', ['response' => $response]);
            return $this->generateFallbackSeo($title, $description, $image);
        }
        
        // Извлекаем ключевые слова
        $focusKeyword = $data['focus_keyword'] ?? '';
        $seoKeywords = is_array($data['seo_keywords'] ?? null) 
            ? implode(', ', $data['seo_keywords']) 
            : ($data['seo_keywords'] ?? '');
        
        // Если ключевые слова пустые, пытаемся извлечь из названия
        if (empty($focusKeyword) || empty($seoKeywords)) {
            $extracted = $this->extractKeywordsFromTitle($title);
            if (empty($focusKeyword)) {
                $focusKeyword = $extracted['focus'] ?? '';
            }
            if (empty($seoKeywords)) {
                $seoKeywords = $extracted['keywords'] ?? '';
            }
        }
        
        return [
            'seo_title' => $data['seo_title'] ?? mb_substr($title, 0, 60),
            'seo_description' => $data['seo_description'] ?? mb_substr($description, 0, 160),
            'focus_keyword' => $focusKeyword,
            'seo_keywords' => $seoKeywords,
            'og_title' => $data['og_title'] ?? $data['seo_title'] ?? mb_substr($title, 0, 60),
            'og_description' => $data['og_description'] ?? $data['seo_description'] ?? mb_substr($description, 0, 160),
            'og_image' => $image ?? '',
            'twitter_title' => $data['twitter_title'] ?? $data['seo_title'] ?? mb_substr($title, 0, 60),
            'twitter_description' => $data['twitter_description'] ?? $data['seo_description'] ?? mb_substr($description, 0, 160),
            'twitter_image' => $image ?? '',
        ];
    }

    /**
     * Генерирует базовые SEO-данные (fallback)
     */
    protected function generateFallbackSeo(string $title, string $description, ?string $image): array
    {
        $desc = mb_substr($description, 0, 160);
        
        return [
            'seo_title' => mb_substr($title, 0, 60),
            'seo_description' => $desc,
            'focus_keyword' => '',
            'seo_keywords' => '',
            'og_title' => mb_substr($title, 0, 60),
            'og_description' => $desc,
            'og_image' => $image ?? '',
            'twitter_title' => mb_substr($title, 0, 60),
            'twitter_description' => $desc,
            'twitter_image' => $image ?? '',
        ];
    }

    /**
     * Извлекает первое изображение из HTML
     */
    protected function extractFirstImage(string $content): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Извлекает ключевые слова из названия статьи (fallback)
     */
    protected function extractKeywordsFromTitle(string $title): array
    {
        // Убираем знаки препинания и разбиваем на слова
        $words = preg_split('/[\s,\.\-:;!?]+/u', mb_strtolower($title));
        $words = array_filter($words, function($word) {
            return mb_strlen($word) > 3; // Только слова длиннее 3 символов
        });
        
        // Берем первые 3-5 значимых слов
        $keywords = array_slice(array_values($words), 0, 5);
        
        return [
            'focus' => !empty($keywords) ? $keywords[0] : '',
            'keywords' => implode(', ', $keywords)
        ];
    }

    /**
     * Проверяет подключение к API
     */
    public function testConnection(): bool
    {
        try {
            $client = $this->getClient();
            $response = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Привет'
                    ]
                ],
                'max_tokens' => 10,
            ]);
            
            return !empty($response->choices[0]->message->content);
        } catch (\Exception $e) {
            Log::error('ChatInfo connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
