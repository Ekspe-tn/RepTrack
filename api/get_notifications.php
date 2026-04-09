<?php

declare(strict_types=1);

require __DIR__ . '/../config/auth.php';

require_login();
header('Content-Type: application/json');

$response = [
    'unread' => 0,
    'items' => [],
];

echo json_encode($response);
