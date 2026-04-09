<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

final class Bootstrap
{
    public static function init(string $basePath): void
    {
        if (class_exists(Dotenv::class) && file_exists($basePath . '/.env')) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }

        $debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
        }
    }
}
