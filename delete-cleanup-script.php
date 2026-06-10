<?php
/**
 * Скрипт для удаления clean-all-symbols.php с production
 * Используется для безопасного удаления утилит после применения
 */

$key = $_GET['key'] ?? '';
$correctKey = 'delete_cleanup_2026';

if ($key !== $correctKey) {
    die('❌ Доступ запрещён. Неверный ключ.');
}

$scriptPath = __DIR__ . '/clean-all-symbols.php';

if (!file_exists($scriptPath)) {
    die('✅ Скрипт уже удалён или не найден.');
}

if (unlink($scriptPath)) {
    echo '✅ Скрипт clean-all-symbols.php успешно удалён!';
    echo '<br><br>';
    echo '🔒 Система в безопасности.';
    echo '<br><br>';
    echo '⚠️ Не забудьте удалить этот скрипт (delete-cleanup-script.php) вручную!';
} else {
    die('❌ Ошибка удаления. Удалите файл вручную через FTP.');
}
