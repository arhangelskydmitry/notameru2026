<?php
/**
 * BANNER REDIRECT - Простая реализация без Laravel маршрутов
 * 
 * URL: https://notame.ru/banner-redirect.php?id=1
 */

// Подключаем Laravel
$projectRoot = __DIR__ . '/..';
require $projectRoot . '/vendor/autoload.php';
$app = require_once $projectRoot . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Получаем ID баннера
$bannerId = $_GET['id'] ?? null;

if (!$bannerId || !is_numeric($bannerId)) {
    http_response_code(404);
    echo "Banner ID not specified";
    exit;
}

// Загружаем баннер
$banner = \App\Models\Banner::find($bannerId);

if (!$banner) {
    http_response_code(404);
    echo "Banner not found: ID = $bannerId";
    exit;
}

if (!$banner->link_url) {
    http_response_code(404);
    echo "Banner has no link URL";
    exit;
}

// Записываем клик
try {
    $banner->recordClick(
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? null
    );
    
    // Успешно записан
    error_log("Banner click recorded: ID={$bannerId}, URL={$banner->link_url}");
    
} catch (Exception $e) {
    // Ошибка записи, но всё равно делаем redirect
    error_log("Error recording banner click: " . $e->getMessage());
}

// Перенаправляем
header("Location: " . $banner->link_url, true, 302);
exit;
