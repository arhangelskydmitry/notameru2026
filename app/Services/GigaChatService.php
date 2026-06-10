<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с GigaChat API (Сбер)
 * Документация: https://developers.sber.ru/docs/ru/gigachat/api/overview
 */
class GigaChatService
{
    protected string $authUrl = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
    protected string $apiUrl = 'https://gigachat.devices.sberbank.ru/api/v1';
    protected string $tokenCacheStore = 'file';
    
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $scope;
    
    public function __construct()
    {
        $this->clientId = \App\Models\Setting::get('gigachat_client_id') ?? config('services.gigachat.client_id');
        $this->clientSecret = \App\Models\Setting::get('gigachat_client_secret') ?? config('services.gigachat.client_secret');
        $this->scope = \App\Models\Setting::get('gigachat_scope') ?? config('services.gigachat.scope', 'GIGACHAT_API_PERS');
    }
    
    /**
     * Проверяет, настроен ли сервис
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
    
    /**
     * Получает access token через OAuth2
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'gigachat_access_token';
        $cache = Cache::store($this->tokenCacheStore);
        
        // Проверяем кеш
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }
        
        try {
            // Формируем credentials для Basic Auth
            $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);
            
            $response = Http::withOptions([
                'verify' => false, // GigaChat использует самоподписанный сертификат
            ])
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'RqUID' => $this->generateRqUID(),
                'Authorization' => 'Basic ' . $credentials,
            ])
            ->asForm()
            ->post($this->authUrl, [
                'scope' => $this->scope,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $expiresIn = $this->resolveTokenTtl($data);
                
                if ($token) {
                    $cache->put($cacheKey, $token, max(60, $expiresIn));
                    return $token;
                }
            }
            
            Log::error('GigaChat OAuth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('GigaChat OAuth exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function resolveTokenTtl(array $data): int
    {
        $expiresAt = $data['expires_at'] ?? null;

        if (is_numeric($expiresAt)) {
            $expiresAt = (int) $expiresAt;

            // Некоторые ответы GigaChat возвращают timestamp в миллисекундах.
            if ($expiresAt > 9999999999) {
                $expiresAt = (int) floor($expiresAt / 1000);
            }

            return max(60, $expiresAt - time() - 60);
        }

        $expiresIn = $data['expires_in'] ?? null;
        if (is_numeric($expiresIn)) {
            return max(60, (int) $expiresIn - 60);
        }

        return 1740;
    }
    
    /**
     * Генерирует уникальный идентификатор запроса
     */
    protected function generateRqUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Отправляет запрос в чат GigaChat
     */
    public function chat(array $messages, string $model = 'GigaChat', float $temperature = 0.7, int $maxTokens = 1024): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            throw new \Exception('Не удалось получить токен доступа GigaChat');
        }
        
        try {
            $response = Http::withOptions([
                'verify' => false,
            ])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'stream' => false,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('GigaChat API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            throw new \Exception('Ошибка GigaChat API: ' . $response->status());
            
        } catch (\Exception $e) {
            Log::error('GigaChat chat exception: ' . $e->getMessage());
            throw $e;
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
            $response = $this->chat([
                [
                    'role' => 'system',
                    'content' => 'Ты — профессиональный SEO-эксперт для новостного сайта на русском языке. Твоя задача — создавать качественные и оптимизированные SEO-метаданные. Отвечай только в формате JSON без дополнительного текста.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ], 'GigaChat', 0.7, 1500);
            
            $responseContent = $response['choices'][0]['message']['content'] ?? null;
            
            if (!$responseContent) {
                throw new \Exception('Пустой ответ от GigaChat');
            }
            
            return $this->parseSeoResponse($responseContent, $title, $cleanExcerpt ?: $cleanContent, $firstImage);
            
        } catch (\Exception $e) {
            Log::error('GigaChat SEO generation failed: ' . $e->getMessage());
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
1. **seo_title** — ОБЯЗАТЕЛЬНО сделай РЕРАЙТ (перефразирование) оригинального названия. НЕ копируй и НЕ обрезай оригинал! Создай НОВОЕ название (50-60 символов), которое передаёт ту же суть, но другими словами. Используй синонимы, меняй порядок слов, переформулируй, но сохраняй смысл.

   ❌ НЕПРАВИЛЬНО: просто обрезать \"{$title}\"
   ✅ ПРАВИЛЬНО: перефразировать, например: \"Успешное тестирование нейтронных сетей: история достижения\" (если оригинал был про тест-драйв)

2. **seo_description** — создай НОВОЕ описание на основе excerpt и названия. НЕ копируй excerpt дословно! Переформулируй, сделай более привлекательным и информативным (150-160 символов).

3. **focus_keyword** — ОБЯЗАТЕЛЬНО укажи одно главное ключевое слово (1-3 слова), которое лучше всего описывает статью.

4. **seo_keywords** — ОБЯЗАТЕЛЬНО извлеки 5-7 релевантных ключевых слов/фраз из контекста. Это должны быть слова, которые люди реально ищут в Яндексе/Google.

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
            Log::warning('Failed to parse GigaChat SEO response', ['response' => $response]);
            return $this->generateFallbackSeo($title, $description, $image);
        }
        
        // Извлекаем ключевые слова, если они не были сгенерированы
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
        
        // Проверяем, что SEO-заголовок не является просто обрезанным оригиналом
        $seoTitle = $data['seo_title'] ?? mb_substr($title, 0, 60);
        $originalStart = mb_substr(mb_strtolower($title), 0, 50);
        $seoTitleStart = mb_substr(mb_strtolower($seoTitle), 0, 50);
        
        // Если SEO-заголовок слишком похож на оригинал (более 80% совпадения), пытаемся улучшить
        similar_text($originalStart, $seoTitleStart, $similarity);
        if ($similarity > 80 && mb_strlen($seoTitle) >= 50) {
            Log::warning('SEO title too similar to original', [
                'original' => $title,
                'seo_title' => $seoTitle,
                'similarity' => $similarity
            ]);
            // Можно попробовать перефразировать, но пока оставляем как есть
        }
        
        return [
            'seo_title' => $seoTitle,
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
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Генерация произвольного текста через GigaChat
     */
    public function generateText(string $prompt, float $temperature = 0.9, int $maxTokens = 2000): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $response = $this->chat($messages, 'GigaChat', $temperature, $maxTokens);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                return $response['choices'][0]['message']['content'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('GigaChat generateText error: ' . $e->getMessage());
            return null;
        }
    }
}