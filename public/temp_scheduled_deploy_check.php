<?php

declare(strict_types=1);

use App\Http\Middleware\PublishScheduledPosts;
use App\Models\WordPress\Post;
use App\Models\WordPress\User;
use App\Models\PostSeo;
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

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    if ($action === 'classcheck') {
        echo json_encode([
            'service_class_exists' => class_exists(\App\Services\ScheduledPostPublisher::class),
            'middleware_class_exists' => class_exists(\App\Http\Middleware\PublishScheduledPosts::class),
            'post_edit_has_future_option' => str_contains(
                (string) file_get_contents(resource_path('views/admin/post-edit.blade.php')),
                'value="future"'
            ),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

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
                'before' => $before,
                'after_first_get' => $after,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } finally {
            Post::whereIn('post_name', [$pastSlug, $futureDraftSlug, $futurePublishSlug])->delete();
            Cache::store('file')->forget('scheduled_posts:publish_due:throttle');
        }

        exit;
    }

    if ($action === 'socialcovers') {
        $mode = $_GET['mode'] ?? 'preview';
        $limit = max(1, min((int) ($_GET['limit'] ?? 500), 5000));

        $coverVariants = [
            url('/images/social/share-news-1.png'),
            url('/images/social/share-news-2.png'),
            url('/images/social/share-news-3.png'),
            url('/images/social/share-news-4.png'),
        ];

        $posts = Post::query()
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'future', 'draft', 'pending'])
            ->whereNotNull('post_name')
            ->where('post_name', '!=', '')
            ->whereNotIn('ID', function ($sub) {
                $sub->select('post_id')
                    ->from('wp_postmeta')
                    ->whereIn('meta_key', ['_thumbnail_id', '_thumbnail_url'])
                    ->whereNotNull('meta_value')
                    ->where('meta_value', '!=', '');
            })
            ->orderByDesc('post_date')
            ->limit($limit)
            ->get(['ID', 'post_title', 'post_name', 'post_status', 'post_date']);

        $prepared = [];
        $updatedCount = 0;

        foreach ($posts as $index => $post) {
            $selectedCover = $coverVariants[$index % count($coverVariants)];

            $prepared[] = [
                'id' => $post->ID,
                'slug' => $post->post_name,
                'status' => $post->post_status,
                'title' => $post->post_title,
                'post_date' => (string) $post->post_date,
                'cover' => $selectedCover,
            ];

            if ($mode !== 'apply') {
                continue;
            }

            $post->setMeta('_thumbnail_url', $selectedCover);

            $seo = PostSeo::firstOrNew(['post_id' => $post->ID]);
            if (! $seo->exists) {
                $seo->robots = 'index, follow';
                $seo->twitter_card = 'summary_large_image';
                $seo->og_type = 'article';
            }

            if (empty($seo->og_image)) {
                $seo->og_image = $selectedCover;
            }
            if (empty($seo->twitter_image)) {
                $seo->twitter_image = $selectedCover;
            }

            $seo->save();
            $updatedCount++;
        }

        echo json_encode([
            'ok' => true,
            'mode' => $mode,
            'limit' => $limit,
            'found_without_cover' => $posts->count(),
            'updated' => $updatedCount,
            'sample' => array_slice($prepared, 0, 20),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($action === 'setcoverbyslug') {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $cover = trim((string) ($_GET['cover'] ?? url('/images/social/share-news-4.png')));

        if ($slug === '') {
            echo json_encode([
                'ok' => false,
                'error' => 'Missing slug',
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        $post = Post::query()
            ->where('post_type', 'post')
            ->where('post_name', $slug)
            ->first();

        if (! $post) {
            echo json_encode([
                'ok' => false,
                'error' => 'Post not found',
                'slug' => $slug,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        $post->setMeta('_thumbnail_url', $cover);

        $seo = PostSeo::firstOrNew(['post_id' => $post->ID]);
        if (! $seo->exists) {
            $seo->robots = 'index, follow';
            $seo->twitter_card = 'summary_large_image';
            $seo->og_type = 'article';
        }
        $seo->og_image = $cover;
        $seo->twitter_image = $cover;
        $seo->save();

        echo json_encode([
            'ok' => true,
            'id' => $post->ID,
            'slug' => $slug,
            'cover' => $cover,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($action === 'maxpost') {
        $limit = max(1, min((int) ($_GET['limit'] ?? 20), 100));
        $force = (($_GET['force'] ?? '0') === '1');
        $ascending = (($_GET['ascending'] ?? '0') === '1');

        $command = 'max:post-latest --limit=' . $limit;
        if ($force) {
            $command .= ' --force';
        }
        if ($ascending) {
            $command .= ' --ascending';
        }

        Artisan::call($command);

        echo json_encode([
            'ok' => true,
            'command' => $command,
            'output' => Artisan::output(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
