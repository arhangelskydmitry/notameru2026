<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('restore-pdo-20260602', $key)) { http_response_code(403); exit('Forbidden'); }
set_time_limit(0);
ini_set('memory_limit', '1024M');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function qi(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }
function pdo_dump_connection(string $connection, string $dir, string $basename): array {
    $pdo = DB::connection($connection)->getPdo();
    $database = DB::connection($connection)->getDatabaseName();
    $gzPath = $dir . '/' . $basename . '.sql.gz';
    @unlink($gzPath);
    $gz = gzopen($gzPath, 'wb9');
    if (!$gz) { throw new RuntimeException('Cannot open gzip output: ' . $gzPath); }
    $write = function(string $s) use ($gz) { gzwrite($gz, $s); };
    $write("-- Notame restore SQL export\n-- connection: {$connection}\n-- database: {$database}\n-- created_at: " . date('c') . "\n\n");
    $write("SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET NAMES utf8mb4;\n\n");

    $tables = [];
    $stmt = $pdo->query('SHOW FULL TABLES');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (($row[1] ?? '') === 'BASE TABLE') { $tables[] = $row[0]; }
    }

    $totalRows = 0;
    foreach ($tables as $table) {
        $create = DB::connection($connection)->select('SHOW CREATE TABLE ' . qi($table));
        $createSql = (array) $create[0];
        $createStatement = $createSql['Create Table'] ?? array_values($createSql)[1] ?? '';
        $write("\n-- Table {$table}\nDROP TABLE IF EXISTS " . qi($table) . ";\n{$createStatement};\n\n");

        $columnsStmt = $pdo->query('SHOW COLUMNS FROM ' . qi($table));
        $columns = [];
        while ($col = $columnsStmt->fetch(PDO::FETCH_ASSOC)) { $columns[] = $col['Field']; }
        if (!$columns) { continue; }

        $select = $pdo->query('SELECT * FROM ' . qi($table));
        $batch = [];
        $batchSize = 100;
        while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($columns as $col) {
                $value = $row[$col] ?? null;
                $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
            }
            $batch[] = '(' . implode(',', $values) . ')';
            $totalRows++;
            if (count($batch) >= $batchSize) {
                $write('INSERT INTO ' . qi($table) . ' (' . implode(',', array_map('qi', $columns)) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) {
            $write('INSERT INTO ' . qi($table) . ' (' . implode(',', array_map('qi', $columns)) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }
    $write("\nSET FOREIGN_KEY_CHECKS=1;\n");
    gzclose($gz);
    return ['connection'=>$connection,'database'=>$database,'path'=>$gzPath,'size'=>filesize($gzPath),'tables'=>count($tables),'rows'=>$totalRows,'ok'=>is_file($gzPath) && filesize($gzPath) > 1000];
}

$latest = collect(glob(storage_path('app/restore-points/stable-*'), GLOB_ONLYDIR))->sort()->last();
if (!$latest) { throw new RuntimeException('No restore point dir'); }
$mysql = pdo_dump_connection('mysql', $latest, 'database-pdo');
$wp = pdo_dump_connection('wordpress', $latest, 'wordpress-pdo');
$manifestPath = $latest . '/manifest.json';
$manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
$manifest['database_pdo'] = $mysql;
$manifest['wordpress_database_pdo'] = $wp;
$manifest['restore_point_status'] = [
    'code_archive_ok'=>is_file($latest . '/code.tar.gz') && filesize($latest . '/code.tar.gz') > 0,
    'media_archive_ok'=>is_file($latest . '/media.tar.gz') && filesize($latest . '/media.tar.gz') > 0,
    'db_archive_ok'=>$mysql['ok'],
    'wordpress_db_archive_ok'=>$wp['ok'],
    'complete'=>(is_file($latest . '/code.tar.gz') && filesize($latest . '/code.tar.gz') > 0 && is_file($latest . '/media.tar.gz') && filesize($latest . '/media.tar.gz') > 0 && $mysql['ok'] && $wp['ok']),
];
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>$manifest['restore_point_status']['complete'],'dir'=>$latest,'database_pdo'=>$mysql,'wordpress_database_pdo'=>$wp,'restore_point_status'=>$manifest['restore_point_status']], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
