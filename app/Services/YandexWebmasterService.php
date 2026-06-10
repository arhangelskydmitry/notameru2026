<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class YandexWebmasterService
{
    protected string $baseUrl = 'https://api.webmaster.yandex.net/v4';
    protected ?string $token;
    protected ?string $hostId;
    protected ?int $userId = null;

    public function __construct()
    {
        // Читаем настройки из БД, если нет - из config
        $this->token = Setting::get('yandex_webmaster_token') ?? config('services.yandex.webmaster_token');
        $this->hostId = Setting::get('yandex_webmaster_host_id') ?? config('services.yandex.webmaster_host_id');
    }

    /**
     * Создать HTTP-клиент с настройками
     */
    protected function http()
    {
        $headers = [
            'Authorization' => 'OAuth ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return Http::withHeaders($headers)
            ->withOptions(['verify' => false]);
    }

    protected function getUserId(): ?int
    {
        if (!$this->token) {
            return null;
        }

        if ($this->userId !== null) {
            return $this->userId;
        }

        return $this->userId = Cache::remember('yandex_webmaster_user_id', 3600, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/user");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['user_id'] ?? null;
                }

                Log::error('Yandex Webmaster getUser API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                Log::error('Yandex Webmaster getUser API exception', [
                    'message' => $e->getMessage()
                ]);
            }

            return null;
        });
    }

    /**
     * Получить информацию о хосте
     */
    public function getHostInfo(): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$this->hostId || !$userId) {
            Log::warning('Yandex Webmaster API: Token or Host ID not configured');
            return [];
        }

        $cacheKey = "yandex_webmaster_host_{$this->hostId}";

        return Cache::remember($cacheKey, 1800, function () use ($userId) { // 30 минут
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/user/{$userId}/hosts/{$this->hostId}");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Yandex Webmaster API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];

            } catch (\Exception $e) {
                Log::error('Yandex Webmaster API exception', [
                    'message' => $e->getMessage(),
                    'host_id' => $this->hostId
                ]);

                return [];
            }
        });
    }

    /**
     * Получить статистику индексации
     */
    public function getIndexingStats(): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$this->hostId || !$userId) {
            return [];
        }

        $cacheKey = "yandex_webmaster_indexing_{$this->hostId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/user/{$userId}/hosts/{$this->hostId}/summary");

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Webmaster Indexing Stats API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularQueries(string $dateFrom = '7daysAgo', string $dateTo = 'today', int $limit = 20, string $indicator = 'TOTAL_CLICKS'): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$this->hostId || !$userId) {
            return [];
        }

        return $this->fetchPopularQueries($userId, $indicator, $dateFrom, $dateTo, $limit);
    }

    /**
     * Подробные данные по запросам (показы, клики, CTR)
     */
    public function getPopularQueriesDetailed(string $dateFrom = '30daysAgo', string $dateTo = 'today', int $limit = 20): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$this->hostId || !$userId) {
            return [];
        }

        $showsResponse = $this->fetchPopularQueries($userId, 'TOTAL_SHOWS', $dateFrom, $dateTo, $limit);
        $clicksResponse = $this->fetchPopularQueries($userId, 'TOTAL_CLICKS', $dateFrom, $dateTo, $limit);

        $items = [];

        foreach (($showsResponse['queries'] ?? []) as $item) {
            $query = $item['query_text'] ?? '';
            $shows = (int) ($item['totals'][0] ?? 0);
            $items[$query] = [
                'query_text' => $query,
                'shows' => $shows,
                'clicks' => 0,
                'avg_position' => $item['avg_click_position'] ?? null,
            ];
        }

        foreach (($clicksResponse['queries'] ?? []) as $item) {
            $query = $item['query_text'] ?? '';
            $clicks = (int) ($item['totals'][0] ?? 0);
            if (!isset($items[$query])) {
                $items[$query] = [
                    'query_text' => $query,
                    'shows' => 0,
                    'clicks' => $clicks,
                    'avg_position' => $item['avg_click_position'] ?? null,
                ];
            } else {
                $items[$query]['clicks'] = $clicks;
                $items[$query]['avg_position'] = $items[$query]['avg_position'] ?? ($item['avg_click_position'] ?? null);
            }
        }

        foreach ($items as &$item) {
            $shows = max(1, $item['shows']);
            $item['ctr'] = round(($item['clicks'] / $shows) * 100, 2);
        }
        unset($item);

        // Сортируем по количеству показов, далее по кликам
        $items = collect($items)
            ->sortByDesc(fn ($item) => [$item['shows'], $item['clicks']])
            ->take($limit)
            ->values()
            ->toArray();

        return $items;
    }

    /**
     * Запрос популярных запросов с кешированием
     */
    protected function fetchPopularQueries(int $userId, string $indicator, string $dateFrom, string $dateTo, int $limit): array
    {
        $cacheKey = "yandex_webmaster_queries_{$this->hostId}_{$indicator}_{$dateFrom}_{$dateTo}_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($indicator, $dateFrom, $dateTo, $limit, $userId) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/user/{$userId}/hosts/{$this->hostId}/search-queries/popular", [
                        'query_indicator' => $indicator,
                        'order_by' => $indicator,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'limit' => $limit,
                    ]);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Webmaster Popular Queries API exception', [
                    'message' => $e->getMessage(),
                    'indicator' => $indicator,
                ]);

                return [];
            }
        });
    }

    /**
     * Получить позиции в поиске
     */
    public function getSearchPositions(string $query = '', int $limit = 20): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$this->hostId || !$userId) {
            return [];
        }

        $cacheKey = "yandex_webmaster_positions_{$this->hostId}_" . md5($query) . "_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit, $userId) {
            try {
                $params = [
                    'limit' => $limit,
                    'order_by' => 'POSITION',
                ];

                if ($query) {
                    $params['query'] = $query;
                }

                $response = $this->http()
                    ->get("{$this->baseUrl}/user/{$userId}/hosts/{$this->hostId}/search-queries", $params);

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Webmaster Search Positions API exception', [
                    'message' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Получить список всех хостов пользователя
     */
    public function getHostsList(): array
    {
        $userId = $this->getUserId();

        if (!$this->token || !$userId) {
            return [];
        }

        $cacheKey = "yandex_webmaster_hosts_list_{$userId}";

        return Cache::remember($cacheKey, 1800, function () use ($userId) {
            try {
                $response = $this->http()
                    ->get("{$this->baseUrl}/user/{$userId}/hosts");

                return $response->successful() ? $response->json() : [];

            } catch (\Exception $e) {
                Log::error('Yandex Webmaster Hosts List API exception', [
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
        $hosts = $this->getHostsList();
        return !empty($hosts);
    }
}



