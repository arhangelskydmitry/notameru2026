<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис генерации SEO-данных с использованием ИИ
 * Приоритет: GigaChat (Сбер) -> ChatInfo -> OpenAI
 */
class SeoGeneratorService
{
    protected ?GigaChatService $gigaChatService = null;
    protected ?ChatInfoService $chatInfoService = null;
    protected ?object $openAiClient = null;
    protected string $preferredProvider;

    public function __construct()
    {
        // Определяем предпочтительного провайдера из настроек
        $this->preferredProvider = \App\Models\Setting::get('seo_ai_provider') ?? 'gigachat';
        
        // Инициализируем GigaChat
        try {
            $this->gigaChatService = new GigaChatService();
        } catch (\Exception $e) {
            Log::warning('GigaChat service initialization failed: ' . $e->getMessage());
        }
        
        // Инициализируем ChatInfo
        try {
            $this->chatInfoService = new ChatInfoService();
        } catch (\Exception $e) {
            Log::warning('ChatInfo service initialization failed: ' . $e->getMessage());
        }
        
        // Инициализируем OpenAI как fallback
        $openAiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        if ($openAiKey) {
            try {
                $certPath = $this->findCertPath();
                
                $this->openAiClient = \OpenAI::factory()
                    ->withApiKey($openAiKey)
                    ->withHttpClient(new \GuzzleHttp\Client([
                        'verify' => $certPath, // Путь к сертификатам или true для системных
                        'timeout' => 30,
                        'curl' => [
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_SSL_VERIFYHOST => 2,
                        ],
                    ]))
                    ->make();
            } catch (\Exception $e) {
                Log::warning('OpenAI client initialization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Находит путь к SSL сертификатам безопасно
     */
    protected function findCertPath()
    {
        $paths = [
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/certs/ca-bundle.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/cert.pem',
        ];
        
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
        
        return true;
    }

    /**
     * Получить список доступных провайдеров
     */
    public function getAvailableProviders(): array
    {
        $providers = [];
        
        if ($this->gigaChatService && $this->gigaChatService->isConfigured()) {
            $providers['gigachat'] = [
                'name' => 'GigaChat (Сбер)',
                'configured' => true,
                'description' => 'Российский ИИ от Сбера - высокое качество для русского языка'
            ];
        } else {
            $providers['gigachat'] = [
                'name' => 'GigaChat (Сбер)',
                'configured' => false,
                'description' => 'Не настроен - добавьте Client ID и Secret'
            ];
        }
        
        // Проверяем ChatInfo
        if ($this->chatInfoService && $this->chatInfoService->isConfigured()) {
            $providers['chatinfo'] = [
                'name' => 'ChatInfo (GPT-4o)',
                'configured' => true,
                'description' => 'ChatInfo GPT-4o - российский сервис, оплата из России'
            ];
        } else {
            $chatinfoKey = config('services.chatinfo.api_key', env('CHATINFO_API_KEY'));
            $providers['chatinfo'] = [
                'name' => 'ChatInfo (GPT-4o)',
                'configured' => !empty($chatinfoKey),
                'description' => !empty($chatinfoKey) 
                    ? 'ChatInfo GPT-4o (ключ настроен)' 
                    : 'Не настроен - добавьте API ключ в .env (CHATINFO_API_KEY)'
            ];
        }
        
        // Проверяем наличие ключа в .env (даже если клиент не инициализирован из-за ошибок)
        $openAiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        if (!empty($openAiKey)) {
            $providers['openai'] = [
                'name' => 'OpenAI (GPT)',
                'configured' => true,
                'description' => $this->openAiClient ? 'OpenAI GPT-4o-mini' : 'OpenAI GPT-4o-mini (ключ настроен, но может потребоваться VPN)'
            ];
        } else {
            $providers['openai'] = [
                'name' => 'OpenAI (GPT)',
                'configured' => false,
                'description' => 'Не настроен - добавьте API ключ в .env (OPENAI_API_KEY)'
            ];
        }
        
        return $providers;
    }

    /**
     * Генерация SEO-данных на основе заголовка, excerpt и контента статьи
     */
    public function generateSeoData(string $title, ?string $excerpt = null, ?string $content = null, ?string $provider = null): array
    {
        $provider = $provider ?? $this->preferredProvider;
        
        // Извлекаем первое изображение из контента
        $firstImage = $content ? $this->extractFirstImage($content) : null;
        
        // Очищаем контент от HTML-тегов
        $cleanContent = $content ? strip_tags($content) : '';
        $cleanExcerpt = $excerpt ? strip_tags($excerpt) : '';
        $shortContent = mb_substr($cleanContent, 0, 2000);

        // Пробуем выбранного провайдера
        if ($provider === 'gigachat' && $this->gigaChatService && $this->gigaChatService->isConfigured()) {
            try {
                Log::info('Generating SEO with GigaChat');
                return $this->gigaChatService->generateSeoData($title, $cleanExcerpt, $content);
            } catch (\Exception $e) {
                Log::error('GigaChat SEO generation failed: ' . $e->getMessage());
                // Fallback на ChatInfo
                if ($this->chatInfoService && $this->chatInfoService->isConfigured()) {
                    Log::info('Falling back to ChatInfo');
                    return $this->chatInfoService->generateSeoData($title, $cleanExcerpt, $content);
                }
                // Fallback на OpenAI
                if ($this->openAiClient) {
                    Log::info('Falling back to OpenAI');
                    return $this->generateWithOpenAI($title, $cleanExcerpt, $shortContent, $firstImage);
                }
            }
        }
        
        // ChatInfo как основной или fallback
        if ($provider === 'chatinfo' && $this->chatInfoService && $this->chatInfoService->isConfigured()) {
            try {
                Log::info('Generating SEO with ChatInfo');
                return $this->chatInfoService->generateSeoData($title, $cleanExcerpt, $content);
            } catch (\Exception $e) {
                Log::error('ChatInfo SEO generation failed: ' . $e->getMessage());
                // Fallback на OpenAI
                if ($this->openAiClient) {
                    Log::info('Falling back to OpenAI');
                    return $this->generateWithOpenAI($title, $cleanExcerpt, $shortContent, $firstImage);
                }
            }
        }
        
        // OpenAI как основной или fallback
        if ($provider === 'openai' && $this->openAiClient) {
            try {
                Log::info('Generating SEO with OpenAI');
                return $this->generateWithOpenAI($title, $cleanExcerpt, $shortContent, $firstImage);
            } catch (\Exception $e) {
                Log::error('OpenAI SEO generation failed: ' . $e->getMessage());
            }
        }
        
        // Если провайдер не указан, пробуем по приоритету: GigaChat -> ChatInfo -> OpenAI
        if ($this->gigaChatService && $this->gigaChatService->isConfigured()) {
            try {
                Log::info('Generating SEO with GigaChat (auto)');
                return $this->gigaChatService->generateSeoData($title, $cleanExcerpt, $content);
            } catch (\Exception $e) {
                Log::error('GigaChat SEO generation failed: ' . $e->getMessage());
            }
        }
        
        if ($this->chatInfoService && $this->chatInfoService->isConfigured()) {
            try {
                Log::info('Generating SEO with ChatInfo (auto)');
                return $this->chatInfoService->generateSeoData($title, $cleanExcerpt, $content);
            } catch (\Exception $e) {
                Log::error('ChatInfo SEO generation failed: ' . $e->getMessage());
            }
        }
        
        if ($this->openAiClient) {
            try {
                Log::info('Generating SEO with OpenAI (auto)');
                return $this->generateWithOpenAI($title, $cleanExcerpt, $shortContent, $firstImage);
            } catch (\Exception $e) {
                Log::error('OpenAI SEO generation failed: ' . $e->getMessage());
            }
        }
        
        // Если ничего не доступно - возвращаем базовые данные
        Log::warning('No AI provider available, using fallback');
        return $this->generateFallbackSeo($title, $cleanExcerpt ?: $cleanContent, $firstImage);
    }

    /**
     * Генерация через OpenAI
     */
    protected function generateWithOpenAI(string $title, string $excerpt, string $content, ?string $image): array
    {
        $prompt = $this->buildPrompt($title, $excerpt, $content);

        try {
            $response = $this->openAiClient->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты - эксперт по SEO-оптимизации для новостного сайта. Твоя задача - создавать оптимизированные SEO-метаданные на русском языке. Отвечай только в формате JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            $result = $response->choices[0]->message->content;
            
            return $this->parseSeoResponse($result, $title, $excerpt ?: $content, $image);
            
        } catch (\Exception $e) {
            Log::error('OpenAI SEO Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Извлечение первого изображения из HTML контента
     */
    protected function extractFirstImage(string $content): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Построение промпта для генерации SEO
     */
    protected function buildPrompt(string $title, string $excerpt, string $content): string
    {
        $keywordsSource = trim($excerpt . ' ' . $title);
        if ($content) {
            $keywordsSource .= ' ' . mb_substr($content, 0, 1000);
        }
        
        return "Ты — профессиональный SEO-копирайтер. Создай SEO-метаданные для новостной статьи.

**ОРИГИНАЛЬНОЕ НАЗВАНИЕ СТАТЬИ:** {$title}

**КРАТКОЕ ОПИСАНИЕ (EXCERPT):**
{$excerpt}

**КОНТЕКСТ ДЛЯ КЛЮЧЕВЫХ СЛОВ:**
{$keywordsSource}

**КРИТИЧЕСКИ ВАЖНО:**
1. **seo_title** — ОБЯЗАТЕЛЬНО сделай РЕРАЙТ (перефразирование) оригинального названия. НЕ копируй и НЕ обрезай оригинал! Создай НОВОЕ название (50-60 символов), которое передаёт ту же суть, но другими словами.

2. **seo_description** — создай НОВОЕ описание на основе excerpt и названия. НЕ копируй excerpt дословно! Переформулируй (150-160 символов).

3. **focus_keyword** — ОБЯЗАТЕЛЬНО укажи одно главное ключевое слово (1-3 слова).

4. **seo_keywords** — ОБЯЗАТЕЛЬНО извлеки 5-7 релевантных ключевых слов/фраз из контекста.

**ФОРМАТ ОТВЕТА (строго JSON):**
{
  \"seo_title\": \"РЕРАЙТ названия (50-60 символов, НЕ копия)\",
  \"seo_description\": \"Новое описание (150-160 символов, НЕ копия excerpt)\",
  \"focus_keyword\": \"Главное ключевое слово (ОБЯЗАТЕЛЬНО)\",
  \"seo_keywords\": [\"слово 1\", \"слово 2\", \"слово 3\", \"слово 4\", \"слово 5\"],
  \"og_title\": \"Заголовок для соцсетей (до 60 символов)\",
  \"og_description\": \"Описание для соцсетей (до 160 символов)\",
  \"twitter_title\": \"Заголовок для Twitter/X (до 60 символов)\",
  \"twitter_description\": \"Описание для Twitter/X (до 160 символов)\"
}

**ПОМНИ:** focus_keyword и seo_keywords НЕ должны быть пустыми! Все тексты на русском языке.";
    }

    /**
     * Парсинг ответа от AI
     */
    protected function parseSeoResponse(string $response, string $title, string $description, ?string $image): array
    {
        // Извлекаем JSON из ответа (может быть обернут в markdown блок)
        $json = $response;
        
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $json = $matches[1];
        }
        
        $data = json_decode($json, true);
        
        if (!$data) {
            Log::warning('Failed to parse AI response, using fallback', ['response' => $response]);
            return $this->generateFallbackSeo($title, $description, $image);
        }

        return [
            'seo_title' => $data['seo_title'] ?? mb_substr($title, 0, 60),
            'seo_description' => $data['seo_description'] ?? mb_substr($description, 0, 160),
            'focus_keyword' => $data['focus_keyword'] ?? '',
            'seo_keywords' => is_array($data['seo_keywords'] ?? null) 
                ? implode(', ', $data['seo_keywords']) 
                : '',
            'og_title' => $data['og_title'] ?? $data['seo_title'] ?? mb_substr($title, 0, 60),
            'og_description' => $data['og_description'] ?? $data['seo_description'] ?? mb_substr($description, 0, 160),
            'og_image' => $image ?? '',
            'twitter_title' => $data['twitter_title'] ?? $data['seo_title'] ?? mb_substr($title, 0, 60),
            'twitter_description' => $data['twitter_description'] ?? $data['seo_description'] ?? mb_substr($description, 0, 160),
            'twitter_image' => $image ?? '',
        ];
    }

    /**
     * Генерация базовых SEO данных (fallback)
     */
    /**
     * Генерация базовых SEO данных (fallback)
     * Улучшенная версия с перефразированием заголовка и извлечением ключевых слов
     */
    protected function generateFallbackSeo(string $title, string $description, ?string $image): array
    {
        $desc = mb_substr($description, 0, 160);
        
        // Пытаемся перефразировать заголовок
        $seoTitle = $this->rephraseTitleFallback($title);
        
        // Извлекаем ключевые слова
        $keywords = $this->extractKeywordsFallback($title, $description);
        
        return [
            'seo_title' => $seoTitle,
            'seo_description' => $desc,
            'focus_keyword' => $keywords['focus'] ?? '',
            'seo_keywords' => $keywords['keywords'] ?? '',
            'og_title' => $seoTitle,
            'og_description' => $desc,
            'og_image' => $image ?? '',
            'twitter_title' => $seoTitle,
            'twitter_description' => $desc,
            'twitter_image' => $image ?? '',
        ];
    }
    
    /**
     * Перефразирует заголовок для SEO (fallback метод)
     */
    protected function rephraseTitleFallback(string $title): string
    {
        // Убираем лишние знаки препинания в конце
        $title = rtrim($title, '.,;:!?');
        
        // Простые правила перефразирования
        $replacements = [
            'Тест-драйв' => 'Тестирование',
            'пройден успешно' => 'завершён успешно',
            'пройден' => 'завершён',
            'История одного' => 'История',
        ];
        
        $rephrased = $title;
        foreach ($replacements as $search => $replace) {
            if (mb_stripos($rephrased, $search) !== false) {
                $rephrased = str_ireplace($search, $replace, $rephrased);
                break;
            }
        }
        
        // Если ничего не изменилось, пытаемся изменить порядок слов
        if ($rephrased === $title) {
            $words = preg_split('/[\s,\.\-:;!?]+/u', $title);
            $words = array_filter($words, function($w) { return mb_strlen($w) > 0; });
            $words = array_values($words);
            
            if (count($words) > 3) {
                $temp = $words[0];
                $words[0] = $words[1];
                $words[1] = $temp;
                $rephrased = implode(' ', $words);
            }
        }
        
        // Исправляем грамматику
        if (preg_match('/Тестирование.*пройден/ui', $rephrased)) {
            $rephrased = preg_replace('/пройден/ui', 'завершено', $rephrased);
        }
        
        return mb_substr($rephrased, 0, 60);
    }
    
    /**
     * Извлекает ключевые слова из заголовка и описания (fallback метод)
     */
    protected function extractKeywordsFallback(string $title, string $description): array
    {
        $text = mb_strtolower($title . ' ' . $description);
        $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);
        $words = preg_split('/\s+/u', $text);
        
        $stopWords = ['и', 'в', 'на', 'с', 'для', 'от', 'по', 'из', 'что', 'как', 'это', 'то', 'не', 'но', 'или', 'а', 'же', 'ли', 'бы', 'был', 'была', 'было', 'были', 'есть', 'быть', 'стать', 'стал', 'стала', 'стало', 'стали'];
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        $frequency = array_count_values($words);
        arsort($frequency);
        $topWords = array_slice(array_keys($frequency), 0, 5);
        
        return [
            'focus' => !empty($topWords) ? $topWords[0] : '',
            'keywords' => implode(', ', $topWords)
        ];
    }
    

    /**
     * Проверить доступность выбранного провайдера
     */
    public function testProvider(string $provider): array
    {
        if ($provider === 'gigachat') {
            if (!$this->gigaChatService || !$this->gigaChatService->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'GigaChat не настроен. Добавьте Client ID и Client Secret.'
                ];
            }
            
            try {
                $connected = $this->gigaChatService->testConnection();
                return [
                    'success' => $connected,
                    'message' => $connected ? 'GigaChat подключен успешно!' : 'Не удалось подключиться к GigaChat'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Ошибка: ' . $e->getMessage()
                ];
            }
        }
        
        if ($provider === 'chatinfo') {
            if (!$this->chatInfoService || !$this->chatInfoService->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'ChatInfo не настроен. Добавьте API ключ в .env файл (CHATINFO_API_KEY).'
                ];
            }
            
            try {
                $connected = $this->chatInfoService->testConnection();
                return [
                    'success' => $connected,
                    'message' => $connected ? 'ChatInfo подключен успешно!' : 'Не удалось подключиться к ChatInfo'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Ошибка: ' . $e->getMessage()
                ];
            }
        }
        
        if ($provider === 'openai') {
            // Проверяем наличие ключа в .env
            $openAiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
            if (empty($openAiKey)) {
                return [
                    'success' => false,
                    'message' => 'OpenAI не настроен. Добавьте API ключ в .env файл (OPENAI_API_KEY).'
                ];
            }
            
            // Если клиент не инициализирован, пытаемся инициализировать его сейчас
            if (!$this->openAiClient) {
                try {
                    $certPath = $this->findCertPath();
                    
                    $this->openAiClient = \OpenAI::factory()
                        ->withApiKey($openAiKey)
                        ->withHttpClient(new \GuzzleHttp\Client([
                            'verify' => $certPath,
                            'timeout' => 30,
                            'curl' => [
                                CURLOPT_SSL_VERIFYPEER => true,
                                CURLOPT_SSL_VERIFYHOST => 2,
                            ],
                        ]))
                        ->make();
                } catch (\Exception $e) {
                    Log::warning('OpenAI client re-initialization failed: ' . $e->getMessage());
                    return [
                        'success' => false,
                        'message' => 'Не удалось инициализировать OpenAI клиент: ' . $e->getMessage()
                    ];
                }
            }
            
            try {
                // Простой тест - получаем список моделей
                $models = $this->openAiClient->models()->list();
                return [
                    'success' => true,
                    'message' => 'OpenAI подключен успешно!'
                ];
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                
                // Проверяем, не связана ли ошибка с географическими ограничениями
                if (stripos($errorMsg, 'country') !== false || 
                    stripos($errorMsg, 'region') !== false || 
                    stripos($errorMsg, 'territory') !== false ||
                    stripos($errorMsg, 'not supported') !== false) {
                    return [
                        'success' => false,
                        'message' => 'OpenAI API недоступен из вашего региона. Ключ настроен правильно, но требуется VPN или прокси для доступа к API.'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Ошибка подключения: ' . $errorMsg
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Неизвестный провайдер: ' . $provider
        ];
    }
}
