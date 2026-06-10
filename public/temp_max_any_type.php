<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('max-any-type-20260602', $key)) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WordPress\Post;
use App\Services\MaxBotService;
use Illuminate\Support\Carbon;

$action = $_GET['action'] ?? 'preview';
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 5)));
$force = filter_var($_GET['force'] ?? false, FILTER_VALIDATE_BOOL);

$query = Post::query()
    ->where('post_status', 'publish')
    ->where('post_date', '<=', Carbon::now())
    ->whereNotNull('post_name')
    ->where('post_name', '!=', '')
    ->orderByDesc('post_date')
    ->limit($limit);

$items = $query->get();

$rows = [];
foreach ($items as $post) {
    $url = rtrim((string) config('app.url'), '/') . '/' . ltrim((string) $post->post_name, '/');
    $alreadySent = (string) $post->getMeta('_max_posted_at', '') !== ''
        || (string) $post->getMeta('_max_posted_message_id', '') !== '';

    $row = [
        'id' => $post->ID,
        'type' => $post->post_type,
        'status' => $post->post_status,
        'date' => optional($post->post_date)->toDateTimeString(),
        'slug' => $post->post_name,
        'title' => $post->post_title,
        'url' => $url,
        'already_sent_to_max' => $alreadySent,
        'max_posted_at' => (string) $post->getMeta('_max_posted_at', ''),
        'max_message_id' => (string) $post->getMeta('_max_posted_message_id', ''),
    ];

    if ($action === 'send') {
        if ($alreadySent && ! $force) {
            $row['send_status'] = 'skipped_already_sent';
        } else {
            try {
                $response = app(MaxBotService::class)->sendMessageToConfiguredChat("📰 {$post->post_title}\n\n{$url}");
                $messageId = (string) data_get($response, 'message.body.mid', data_get($response, 'message.mid', ''));
                $post->setMeta('_max_posted_at', Carbon::now()->toDateTimeString());
                if ($messageId !== '') {
                    $post->setMeta('_max_posted_message_id', $messageId);
                }
                $row['send_status'] = 'sent';
                $row['sent_message_id'] = $messageId;
            } catch (Throwable $e) {
                $row['send_status'] = 'failed';
                $row['error'] = $e->getMessage();
            }
        }
    }

    $rows[] = $row;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'action' => $action,
    'limit' => $limit,
    'force' => $force,
    'items' => $rows,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
