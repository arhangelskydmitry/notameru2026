<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('fix-kiselevav-20260602', $key)) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WordPress\User;

$email = 'aleks3453@mail.ru';
$login = 'kiselevav';
$user = User::where('user_email', $email)->first();
if (!$user) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'User not found', 'email' => $email], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$conflict = User::where('ID', '!=', $user->ID)
    ->where(function ($query) use ($login) {
        $query->where('user_login', $login)->orWhere('user_nicename', $login);
    })
    ->first();

if ($conflict) {
    http_response_code(409);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => 'Login or slug already used by another user',
        'conflict_id' => $conflict->ID,
        'conflict_email' => $conflict->user_email,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$before = [
    'ID' => $user->ID,
    'user_login' => $user->user_login,
    'user_nicename' => $user->user_nicename,
    'user_email' => $user->user_email,
    'display_name' => $user->display_name,
];

$user->user_login = $login;
$user->user_nicename = $login;
$user->save();
$user->refresh();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'before' => $before,
    'after' => [
        'ID' => $user->ID,
        'user_login' => $user->user_login,
        'user_nicename' => $user->user_nicename,
        'user_email' => $user->user_email,
        'display_name' => $user->display_name,
    ],
    'author_url' => url('/author/' . $user->ID),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
