<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('restore-robust-20260602', $key)) { http_response_code(403); exit('Forbidden'); }
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
function run_cmd($cmd) { $out=[]; $code=0; exec($cmd . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
function dump_connection($conn, $dir, $name) {
    $db = config('database.connections.' . $conn);
    $sql = $dir . '/' . $name . '.sql';
    $gz = $sql . '.gz';
    $err = $dir . '/' . $name . '.stderr.txt';
    @unlink($sql); @unlink($gz); @unlink($err);
    $cmd = sprintf('mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --default-character-set=utf8mb4 %s > %s 2> %s', escapeshellarg($db['host']), escapeshellarg($db['port'] ?? 3306), escapeshellarg($db['username']), escapeshellarg($db['password']), escapeshellarg($db['database']), escapeshellarg($sql), escapeshellarg($err));
    [$code,$out]=run_cmd($cmd);
    $sqlSize = is_file($sql) ? filesize($sql) : 0;
    $stderr = is_file($err) ? trim(file_get_contents($err)) : '';
    $gzipCode = null; $gzipOut = '';
    if ($code === 0 && $sqlSize > 1000) {
        [$gzipCode,$gzipOut]=run_cmd('gzip -9 ' . escapeshellarg($sql));
    }
    return [
        'connection'=>$conn,
        'database'=>$db['database'] ?? null,
        'sql_path'=>$sql,
        'gz_path'=>$gz,
        'mysqldump_code'=>$code,
        'mysqldump_output'=>$out,
        'stderr'=>$stderr,
        'sql_size'=>$sqlSize,
        'gzip_code'=>$gzipCode,
        'gzip_output'=>$gzipOut,
        'gz_size'=>is_file($gz) ? filesize($gz) : 0,
        'ok'=>is_file($gz) && filesize($gz) > 1000,
    ];
}
$latest = collect(glob(storage_path('app/restore-points/stable-*'), GLOB_ONLYDIR))->sort()->last();
if (!$latest) { throw new RuntimeException('No restore point dir'); }
$default = dump_connection('mysql', $latest, 'database-verified');
$wp = dump_connection('wordpress', $latest, 'wordpress-verified');
$manifestPath = $latest . '/manifest.json';
$manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
$manifest['database_verified'] = $default;
$manifest['wordpress_database_verified'] = $wp;
$manifest['verification'] = [
    'wordpress_tables'=>count(DB::connection('wordpress')->select('SHOW TABLES')),
    'wordpress_posts'=>DB::connection('wordpress')->table('wp_posts')->count(),
    'wordpress_users'=>DB::connection('wordpress')->table('wp_users')->count(),
    'default_tables'=>count(DB::connection('mysql')->select('SHOW TABLES')),
];
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>($default['ok'] && $wp['ok']),'dir'=>$latest,'database_verified'=>$default,'wordpress_database_verified'=>$wp,'verification'=>$manifest['verification']], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
