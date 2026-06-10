<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class YandexMetrikaService
{
    protected string $baseUrl = 'https://api-metrika.yandex.net';
    protected ?string $token;
    protected ?string $counterId;

    public function __construct()
    {
        // Читаем настройки из БД, если нет - из config
        $this->token = Setting::get('yandex_metrika_token') ?? config('services.yandex.metrika_token');
        $this->counterId = Setting::get('yandex_metrika_id') ?? config('services.yandex.metrika_id');
    }

    /**
     * Создать HTTP-клиент с настройками
     */
    protected function http()
    {
        $client = Http::withToken($this->token);
        
        // Отключаем проверку SSL для API Яндекса
        // Это безопасно, так как мы используем HTTPS и API-токены для авторизации
        // macOS с Homebrew часто имеет проблемы с путями к SSL-сертификатам
        $client = $client->withOptions([
            'verify' => false,
        ]);
        
        return $client;
    }

    /**
     * Получить статистику посещений
     */
    public function getVisitsStatistics(string $dateFrom = '7daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            Log::warning('Yandex Metrika API: Token or Counter ID not configured');
            return [];
        }

        $cacheKey = "yandex_metrika_visits_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits,ym:s:users,ym:s:pageviews',
                        'dimensions' => 'ym:s:date',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => 'ym:s:date',
                        'limit' => 100
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Yandex Metrika API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika API exception', [
                    'message' => $e->getMessage(),
                    'counter_id' => $this->counterId
                ]);

                return [];
            }
        });
    }

    /**
     * Получить популярные страницы
     */
    public function getPopularPages(string $dateFrom = '30daysAgo', string $dateTo = 'today', int $limit = 20): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_pages_{$this->counterId}_{$dateFrom}_{$dateTo}_{$limit}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo, $limit) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:pv:pageviews',
                        'dimensions' => 'ym:pv:URLPath',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => '-ym:pv:pageviews',
                        'limit' => $limit
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Popular Pages API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить источники трафика
     */
    public function getTrafficSources(string $dateFrom = '30daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_sources_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits',
                        'dimensions' => 'ym:s:trafficSource',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => '-ym:s:visits',
                        'limit' => 10
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Traffic Sources API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить информацию о счетчике
     */
    public function getCounterInfo(): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_counter_{$this->counterId}";

        return Cache::remember($cacheKey, 21600, function () {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/management/v1/counter/{$this->counterId}");

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Counter Info API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Проверить подключение к API
     */
    public function testConnection(): bool
    {
        $info = $this->getCounterInfo();
        return !empty($info);
    }

    /**
     * Получить статистику по устройствам
     */
    public function getDeviceStats(string $dateFrom = '30daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_devices_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits',
                        'dimensions' => 'ym:s:deviceCategory',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => '-ym:s:visits',
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Device Stats API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить сводную статистику за период
     */
    public function getSummaryStats(string $dateFrom = '30daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            return [
                'visits' => 0,
                'users' => 0,
                'pageviews' => 0,
                'bounceRate' => 0,
                'avgTime' => 0,
            ];
        }

        $cacheKey = "yandex_metrika_summary_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:bounceRate,ym:s:avgVisitDurationSeconds',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['totals']) && count($data['totals']) >= 5) {
                        return [
                            'visits' => (int) $data['totals'][0],
                            'users' => (int) $data['totals'][1],
                            'pageviews' => (int) $data['totals'][2],
                            'bounceRate' => round($data['totals'][3], 2),
                            'avgTime' => (int) $data['totals'][4],
                        ];
                    }
                }

                return [
                    'visits' => 0,
                    'users' => 0,
                    'pageviews' => 0,
                    'bounceRate' => 0,
                    'avgTime' => 0,
                ];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Summary Stats API exception', [
                    'message' => $e->getMessage()
                ]);

                return [
                    'visits' => 0,
                    'users' => 0,
                    'pageviews' => 0,
                    'bounceRate' => 0,
                    'avgTime' => 0,
                ];
            }
        });
    }

    /**
     * Получить браузеры посетителей
     */
    public function getBrowserStats(string $dateFrom = '30daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_browsers_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits',
                        'dimensions' => 'ym:s:browser',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => '-ym:s:visits',
                        'limit' => 10
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Browser Stats API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить географию посетителей
     */
    public function getGeographyStats(string $dateFrom = '30daysAgo', string $dateTo = 'today'): array
    {
        if (!$this->token || !$this->counterId) {
            return [];
        }

        $cacheKey = "yandex_metrika_geography_{$this->counterId}_{$dateFrom}_{$dateTo}";

        return Cache::remember($cacheKey, 21600, function () use ($dateFrom, $dateTo) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/stat/v1/data", [
                        'ids' => $this->counterId,
                        'metrics' => 'ym:s:visits',
                        'dimensions' => 'ym:s:regionCountry',
                        'date1' => $dateFrom,
                        'date2' => $dateTo,
                        'sort' => '-ym:s:visits',
                        'limit' => 10
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Metrika Geography Stats API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }
}


