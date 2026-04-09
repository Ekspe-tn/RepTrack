<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => env_value('MAIL_HOST', 'smtp.example.com'),
    'port' => (int) env_value('MAIL_PORT', 587),
    'user' => env_value('MAIL_USER', 'mailer@example.com'),
    'pass' => env_value('MAIL_PASS', 'change-me'),
    'from' => env_value('MAIL_FROM', 'mailer@example.com'),
    'from_name' => env_value('MAIL_FROM_NAME', 'RepTrack'),
];
