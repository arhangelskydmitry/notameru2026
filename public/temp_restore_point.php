<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('restore-point-20260602', $key)) { http_response_code(403); exit('Forbidden'); }
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$action = $_GET['action'] ?? 'probe';
$base = storage_path('app/restore-points');
if (!is_dir($base)) { mkdir($base, 0755, true); }
function run_cmd($cmd) { $out=[]; $code=0; exec($cmd . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
function dir_size($dir) { if (!is_dir($dir)) return 0; $s=0; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)); foreach($it as $f){ if($f->isFile()) $s += $f->getSize(); } return $s; }
function fmt($b){ $u=['B','KB','MB','GB']; $i=0; while($b>1024 && $i<count($u)-1){$b/=1024;$i++;} return round($b,2).' '.$u[$i]; }

if ($action === 'probe') {
    [$mysqldumpCode,$mysqldumpPath]=run_cmd('which mysqldump');
    [$tarCode,$tarPath]=run_cmd('which tar');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'=>true,
        'base'=>$base,
        'base_writable'=>is_writable($base),
        'mysqldump'=>['code'=>$mysqldumpCode,'path'=>$mysqldumpPath],
        'tar'=>['code'=>$tarCode,'path'=>$tarPath],
        'sizes'=>[
            'app'=>fmt(dir_size(base_path('app'))),
            'config'=>fmt(dir_size(base_path('config'))),
            'routes'=>fmt(dir_size(base_path('routes'))),
            'resources'=>fmt(dir_size(base_path('resources'))),
            'public_images'=>fmt(dir_size(public_path('images'))),
            'public_imgnews'=>fmt(dir_size(public_path('imgnews'))),
            'public_uploads'=>fmt(dir_size(public_path('uploads'))),
            'public_wp_content'=>fmt(dir_size(public_path('wp-content'))),
        ],
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'create') {
    $stamp = date('Ymd-His');
    $dir = $base . '/stable-' . $stamp;
    mkdir($dir, 0755, true);
    $manifest = [
        'created_at'=>date('c'),
        'app_url'=>config('app.url'),
        'php'=>PHP_VERSION,
        'laravel'=>app()->version(),
        'paths'=>[],
    ];

    $db = config('database.connections.mysql');
    $dump = $dir . '/database.sql.gz';
    $cmd = sprintf('mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip -9 > %s', escapeshellarg($db['host']), escapeshellarg($db['port'] ?? 3306), escapeshellarg($db['username']), escapeshellarg($db['password']), escapeshellarg($db['database']), escapeshellarg($dump));
    [$code,$out]=run_cmd($cmd);
    $manifest['database']=['path'=>$dump,'ok'=>$code===0 && is_file($dump) && filesize($dump)>0,'size'=>is_file($dump)?filesize($dump):0,'command_code'=>$code,'output'=>$out];
    if (!$manifest['database']['ok']) { throw new RuntimeException('DB dump failed: '.$out); }

    $codeArchive = $dir . '/code.tar.gz';
    $exclude = "--exclude='.git' --exclude='vendor' --exclude='node_modules' --exclude='storage/app/restore-points' --exclude='storage/logs/*.log' --exclude='storage/framework/cache/*' --exclude='storage/framework/views/*'";
    $cmd = 'cd ' . escapeshellarg(base_path()) . ' && tar ' . $exclude . ' -czf ' . escapeshellarg($codeArchive) . ' app bootstrap config database lang public/.htaccess public/robots.txt public/llms.txt public/llms-full.txt resources routes composer.json composer.lock artisan .env.example 2>&1';
    [$code,$out]=run_cmd($cmd);
    $manifest['code']=['path'=>$codeArchive,'ok'=>$code===0 && is_file($codeArchive) && filesize($codeArchive)>0,'size'=>is_file($codeArchive)?filesize($codeArchive):0,'command_code'=>$code,'output'=>$out];
    if (!$manifest['code']['ok']) { throw new RuntimeException('Code archive failed: '.$out); }

    $mediaArchive = $dir . '/media.tar.gz';
    $cmd = 'cd ' . escapeshellarg(public_path()) . ' && tar -czf ' . escapeshellarg($mediaArchive) . ' images imgnews uploads wp-content 2>&1';
    [$code,$out]=run_cmd($cmd);
    $manifest['media']=['path'=>$mediaArchive,'ok'=>$code===0 && is_file($mediaArchive) && filesize($mediaArchive)>0,'size'=>is_file($mediaArchive)?filesize($mediaArchive):0,'command_code'=>$code,'output'=>$out];

    file_put_contents($dir . '/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true,'dir'=>$dir,'manifest'=>$manifest], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

http_response_code(400); echo 'Unknown action';
