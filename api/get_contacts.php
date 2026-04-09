<?php

declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!function_exists('is_logged_in') || !is_logged_in()) {
    http_response_code(401);
    echo json_encode(['items' => []]);
    exit;
}

$governorateId = (int) ($_GET['governorate_id'] ?? 0);
$cityId = (int) ($_GET['city_id'] ?? 0);
if ($governorateId <= 0 || $cityId <= 0) {
    echo json_encode(['items' => []]);
    exit;
}

try {
    $stmt = db()->prepare('SELECT id, name, type FROM contacts WHERE governorate_id = ? AND city_id = ? AND active = 1 ORDER BY name');
    $stmt->execute([$governorateId, $cityId]);
    $items = $stmt->fetchAll();
} catch (Throwable $e) {
    $items = [];
}

echo json_encode(['items' => $items]);
