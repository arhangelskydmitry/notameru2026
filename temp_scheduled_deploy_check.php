<?php

declare(strict_types=1);

use App\Http\Middleware\PublishScheduledPosts;
use App\Models\WordPress\Post;
use App\Models\WordPress\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? 'classcheck';

if ($key !== 'notaadmin2026-scheduled-check') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/vendor/autoload.php';

if ($action === 'classcheck') {
    echo json_encode([
        'service_class_exists' => class_exists(\App\Services\ScheduledPostPublisher::class),
        'middleware_class_exists' => class_exists(\App\Http\Middleware\PublishScheduledPosts::class),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    if ($action === 'clear') {
        Artisan::call('optimize:clear');

        echo json_encode([
            'ok' => true,
            'artisan_output' => Artisan::output(),
            'post_edit_has_future_option' => str_contains(
                (string) file_get_contents(resource_path('views/admin/post-edit.blade.php')),
                'value="future"'
            ),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($action === 'scheduledtest') {
        $suffix = Str::lower(Str::random(8));
        $authorId = User::query()->value('ID') ?? 1;
        $now = now();

        $pastSlug = "prod-test-scheduled-past-{$suffix}";
        $futureDraftSlug = "prod-test-scheduled-future-draft-{$suffix}";
        $futurePublishSlug = "prod-test-scheduled-future-publish-{$suffix}";

        $basePayload = [
            'post_author' => $authorId,
            'post_content' => 'Production scheduled test content',
            'post_excerpt' => '',
            'post_type' => 'post',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => '',
        ];

        try {
            $past = Post::create($basePayload + [
                'post_date' => $now->copy()->subMinutes(10),
                'post_date_gmt' => $now->copy()->subMinutes(10)->utc(),
                'post_modified' => $now->copy()->subMinutes(10),
                'post_modified_gmt' => $now->copy()->subMinutes(10)->utc(),
                'post_title' => "Production Scheduled Past {$suffix}",
                'post_status' => 'future',
                'post_name' => $pastSlug,
            ]);

            $futureDraft = Post::create($basePayload + [
                'post_date' => $now->copy()->addHours(2),
                'post_date_gmt' => $now->copy()->addHours(2)->utc(),
                'post_modified' => $now,
                'post_modified_gmt' => $now->copy()->utc(),
                'post_title' => "Production Scheduled Future Draft {$suffix}",
                'post_status' => 'future',
                'post_name' => $futureDraftSlug,
            ]);

            $futurePublish = Post::create($basePayload + [
                'post_date' => $now->copy()->addHours(2),
                'post_date_gmt' => $now->copy()->addHours(2)->utc(),
                'post_modified' => $now,
                'post_modified_gmt' => $now->copy()->utc(),
                'post_title' => "Production Scheduled Future Publish {$suffix}",
                'post_status' => 'publish',
                'post_name' => $futurePublishSlug,
            ]);

            $before = [
                'past_status' => $past->post_status,
                'past_visible' => Post::publiclyVisible()->where('post_name', $pastSlug)->exists(),
                'future_draft_status' => $futureDraft->post_status,
                'future_draft_visible' => Post::publiclyVisible()->where('post_name', $futureDraftSlug)->exists(),
                'future_publish_status' => $futurePublish->post_status,
                'future_publish_visible' => Post::publiclyVisible()->where('post_name', $futurePublishSlug)->exists(),
            ];

            Cache::store('file')->forget('scheduled_posts:publish_due:throttle');
            app(PublishScheduledPosts::class)->handle(Request::create('/', 'GET'), static fn () => response('ok'));

            $past->refresh();
            $futureDraft->refresh();
            $futurePublish->refresh();

            $after = [
                'past_status' => $past->post_status,
                'past_visible' => Post::publiclyVisible()->where('post_name', $pastSlug)->exists(),
                'future_draft_status' => $futureDraft->post_status,
                'future_draft_visible' => Post::publiclyVisible()->where('post_name', $futureDraftSlug)->exists(),
                'future_publish_status' => $futurePublish->post_status,
                'future_publish_visible' => Post::publiclyVisible()->where('post_name', $futurePublishSlug)->exists(),
            ];

            echo json_encode([
                'ok' => true,
                'post_edit_has_future_option' => str_contains(
                    (string) file_get_contents(resource_path('views/admin/post-edit.blade.php')),
                    'value="future"'
                ),
                'before' => $before,
                'after_first_get' => $after,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } finally {
            Post::whereIn('post_name', [$pastSlug, $futureDraftSlug, $futurePublishSlug])->delete();
            Cache::store('file')->forget('scheduled_posts:publish_due:throttle');
        }

        exit;
    }

    echo json_encode([
        'ok' => false,
        'error' => 'Unknown action',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
