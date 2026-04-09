<?php

declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$governorateId = (int) ($_GET['governorate_id'] ?? 0);
if ($governorateId <= 0) {
    echo json_encode(['items' => []]);
    exit;
}

try {
    $stmt = db()->prepare('SELECT id, name_fr, postal_code FROM cities WHERE governorate_id = ? ORDER BY name_fr');
    $stmt->execute([$governorateId]);
    $items = $stmt->fetchAll();
} catch (Throwable $e) {
    $items = [];
}

echo json_encode(['items' => $items]);
