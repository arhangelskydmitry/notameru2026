<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaxAutoPoster
{
    /**
     * Запускаем MAX-автопостинг через web-триггер как fallback на хостингах,
     * где cron/schedule может быть не настроен.
     */
    public function postLatestIfDue(): void
    {
        if (! config('max.enabled', false)) {
            return;
        }

        $cache = Cache::store('file');
        $throttleKey = 'max:auto_post_latest:throttle';

        if (! $cache->add($throttleKey, now()->timestamp, now()->addMinutes(5))) {
            return;
        }

        try {
            Artisan::call('max:post-latest', [
                '--limit' => 5,
            ]);

            $output = trim(Artisan::output());
            if ($output !== '') {
                Log::info('MAX auto poster output', ['output' => $output]);
            }
        } catch (Throwable $e) {
            Log::warning('MAX auto poster failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
