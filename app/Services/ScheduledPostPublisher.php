<?php

namespace App\Services;

use App\Models\WordPress\Post;
use Illuminate\Support\Facades\Cache;

class ScheduledPostPublisher
{
    /**
     * Публикуем просроченные draft/pending посты не чаще одного раза в 15 секунд,
     * чтобы не бить лишний раз по БД на каждом запросе.
     */
    public function publishDuePosts(): int
    {
        $cache = Cache::store('file');
        $throttleKey = 'scheduled_posts:publish_due:throttle';

        if (! $cache->add($throttleKey, now()->timestamp, now()->addSeconds(15))) {
            return 0;
        }

        $now = now();

        return Post::where('post_type', 'post')
            ->whereIn('post_status', ['draft', 'pending', 'future'])
            ->where('post_date', '<=', $now)
            ->update([
                'post_status' => 'publish',
                'post_modified' => $now,
                'post_modified_gmt' => $now->copy()->utc(),
            ]);
    }
}
