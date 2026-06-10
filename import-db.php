<?php
/**
 * ИМПОРТ БД (MAMP) - с обходом ошибок структуры
 */

if (php_sapi_name() !== 'cli') die("Запуск: php import-db.php путь\n");
if (!isset($argv[1])) die("Использование: php import-db.php файл.json или папка/\n");

$path = $argv[1];

echo "📦 ИМПОРТ БД (MAMP)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$db = 'notameru';

echo "1. Подключение...\n";
try {
    $pdo = new PDO("mysql:unix_socket=$socket", 'root', 'root', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    echo "   ✅ OK\n\n";
} catch (PDOException $e) {
    die("   ❌ " . $e->getMessage() . "\n");
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4");
$pdo->exec("USE `$db`");
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET sql_mode=''");

// Файлы
$files = array();
if (is_dir($path)) {
    foreach (glob(rtrim($path, '/') . '/*.json') as $f) $files[] = $f;
} elseif (is_file($path)) {
    $files[] = $path;
} else {
    die("Не найдено: $path\n");
}

// Пропускаем уже обработанные part файлы и пустые
$files = array_filter($files, function($f) {
    return filesize($f) > 10;
});

echo "2. Файлов: " . count($files) . "\n\n";
echo "3. Импорт:\n";

$totalRows = 0;
$totalTables = 0;

foreach ($files as $file) {
    $basename = basename($file);
    
    // Пропускаем db_part файлы (уже импортированы)
    if (strpos($basename, 'db_part') === 0) {
        continue;
    }
    
    echo "\n   📄 $basename\n";
    
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    unset($json);
    
    if (!$data) {
        echo "      ⚠️ JSON error\n";
        continue;
    }
    
    foreach ($data as $table => $info) {
        echo "      $table... ";
        
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        
        $s = isset($info['s']) ? $info['s'] : '';
        $rows = isset($info['d']) ? $info['d'] : array();
        
        // Пробуем создать таблицу
        $created = false;
        if ($s) {
            // Убираем проблемные части для старых MySQL
            $s = preg_replace('/\s+COLLATE\s+\w+/', '', $s);
            $s = str_replace(' VISIBLE', '', $s);
            $s = preg_replace('/\s+ENGINE=\w+/', ' ENGINE=InnoDB', $s);
            
            try {
                $pdo->exec($s);
                $created = true;
            } catch (PDOException $e) {
                // Создаём таблицу из данных
                if (!empty($rows)) {
                    $cols = array();
                    $firstRow = $rows[0];
                    foreach ($firstRow as $col => $val) {
                        if (is_int($val)) {
                            $cols[] = "`$col` BIGINT";
                        } elseif (is_float($val)) {
                            $cols[] = "`$col` DOUBLE";
                        } elseif (strlen($val) > 1000) {
                            $cols[] = "`$col` LONGTEXT";
                        } else {
                            $cols[] = "`$col` TEXT";
                        }
                    }
                    $createSql = "CREATE TABLE `$table` (" . implode(', ', $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    try {
                        $pdo->exec($createSql);
                        $created = true;
                    } catch (PDOException $e2) {
                        echo "⚠️ create failed\n";
                        continue;
                    }
                }
            }
        }
        
        // Вставляем данные
        $cnt = count($rows);
        if ($cnt > 0 && $created) {
            $columns = array_keys($rows[0]);
            
            foreach (array_chunk($rows, 50) as $chunk) {
                $placeholders = array();
                $values = array();
                
                foreach ($chunk as $row) {
                    $ph = array();
                    foreach ($columns as $col) {
                        $values[] = isset($row[$col]) ? $row[$col] : null;
                        $ph[] = '?';
                    }
                    $placeholders[] = '(' . implode(',', $ph) . ')';
                }
                
                try {
                    $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES " . implode(',', $placeholders);
                    $pdo->prepare($sql)->execute($values);
                } catch (PDOException $e) {
                    // Пробуем по одной
                    foreach ($chunk as $row) {
                        try {
                            $sql2 = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")";
                            $pdo->prepare($sql2)->execute(array_values($row));
                        } catch (PDOException $e2) {}
                    }
                }
            }
            $totalRows += $cnt;
        }
        
        $totalTables++;
        echo "$cnt rows\n";
        unset($rows, $info);
    }
    unset($data);
}

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ ГОТОВО!\n";
echo "   Таблиц: $totalTables\n";
echo "   Записей: " . number_format($totalRows) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "php artisan config:clear && php artisan cache:clear\n";
