<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('restore-wordpress-20260602', $key)) { http_response_code(403); exit('Forbidden'); }
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
function run_cmd($cmd) { $out=[]; $code=0; exec($cmd . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
$latest = collect(glob(storage_path('app/restore-points/stable-*'), GLOB_ONLYDIR))->sort()->last();
if (!$latest) { throw new RuntimeException('No restore point dir'); }
$db = config('database.connections.wordpress');
$dump = $latest . '/wordpress.sql.gz';
$cmd = sprintf('mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip -9 > %s', escapeshellarg($db['host']), escapeshellarg($db['port'] ?? 3306), escapeshellarg($db['username']), escapeshellarg($db['password']), escapeshellarg($db['database']), escapeshellarg($dump));
[$code,$out]=run_cmd($cmd);
$tables = DB::connection('wordpress')->select('SHOW TABLES');
$posts = DB::connection('wordpress')->table('wp_posts')->count();
$users = DB::connection('wordpress')->table('wp_users')->count();
$manifestPath = $latest . '/manifest.json';
$manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
$manifest['wordpress_database'] = ['path'=>$dump,'ok'=>$code===0 && is_file($dump) && filesize($dump)>0,'size'=>is_file($dump)?filesize($dump):0,'command_code'=>$code,'output'=>$out,'tables'=>count($tables),'wp_posts'=>$posts,'wp_users'=>$users];
$manifest['media']['ok_with_warning'] = is_file($latest . '/media.tar.gz') && filesize($latest . '/media.tar.gz') > 0;
$manifest['media']['note'] = 'tar warning was caused by missing empty public/uploads and public/wp-content directories; public/imgnews and public/images archive exists.';
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'dir'=>$latest,'wordpress_database'=>$manifest['wordpress_database'],'manifest_path'=>$manifestPath,'media'=>$manifest['media']], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
