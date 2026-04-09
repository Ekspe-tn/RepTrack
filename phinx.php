<?php

declare(strict_types=1);

$basePath = __DIR__;

if (file_exists($basePath . '/vendor/autoload.php')) {
    require $basePath . '/vendor/autoload.php';
}

if (class_exists(Dotenv\Dotenv::class) && file_exists($basePath . '/.env')) {
    Dotenv\Dotenv::createImmutable($basePath)->safeLoad();
}

require_once $basePath . '/config/env.php';

return [
    'paths' => [
        'migrations' => 'migrations',
        'seeds' => 'seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => env_value('DB_HOST', '127.0.0.1'),
            'name' => env_value('DB_NAME', 'reptrack'),
            'user' => env_value('DB_USER', 'root'),
            'pass' => env_value('DB_PASS', ''),
            'port' => (int) env_value('DB_PORT', 3306),
            'charset' => 'utf8mb4',
        ],
    ],
];
