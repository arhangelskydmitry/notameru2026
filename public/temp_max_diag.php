<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('max-diag-20260602', $key)) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WordPress\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

$action = $_GET['action'] ?? 'diag';

function mask_value($value) {
    $value = (string) $value;
    if ($value === '') return '';
    if (strlen($value) <= 8) return str_repeat('*', strlen($value));
    return substr($value, 0, 4) . '...' . substr($value, -4);
}

function latest_posts_state($limit = 8) {
    $now = Carbon::now();
    return Post::query()
        ->publiclyVisible()
        ->where('post_date', '<=', $now)
        ->orderByDesc('post_date')
        ->limit($limit)
        ->get()
        ->map(function ($post) {
            return [
                'id' => $post->ID,
                'slug' => $post->post_name,
                'date' => optional($post->post_date)->toDateTimeString(),
                'title' => $post->post_title,
                'max_posted_at' => (string) $post->getMeta('_max_posted_at', ''),
                'max_message_id' => (string) $post->getMeta('_max_posted_message_id', ''),
            ];
        })->values()->all();
}

if ($action === 'clear') {
    Artisan::call('optimize:clear');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'output' => Artisan::output()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'dry-run') {
    Artisan::call('max:post-latest', [
        '--limit' => (int) ($_GET['limit'] ?? 8),
        '--dry-run' => true,
    ]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'output' => Artisan::output()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'send-one') {
    Artisan::call('max:post-latest', [
        '--limit' => (int) ($_GET['limit'] ?? 1),
    ]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'output' => Artisan::output(), 'posts_after' => latest_posts_state(3)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$logPath = storage_path('logs/max-posting.log');
$logTail = '';
if (is_file($logPath)) {
    $content = file_get_contents($logPath);
    $logTail = substr($content, -6000);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'now' => Carbon::now()->toDateTimeString(),
    'app_url' => config('app.url'),
    'max_config' => [
        'enabled' => config('max.enabled'),
        'bot_token_present' => trim((string) config('max.bot_token')) !== '',
        'bot_token_masked' => mask_value(config('max.bot_token')),
        'chat_id' => config('max.chat_id'),
        'api_base' => config('max.api_base'),
        'message_format' => config('max.message_format'),
    ],
    'env' => [
        'MAX_ENABLED' => env('MAX_ENABLED'),
        'MAX_CHAT_ID' => env('MAX_CHAT_ID'),
        'MAX_BOT_TOKEN_present' => trim((string) env('MAX_BOT_TOKEN')) !== '',
    ],
    'latest_posts' => latest_posts_state(),
    'log_tail' => $logTail,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
