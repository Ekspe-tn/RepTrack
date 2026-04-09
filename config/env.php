<?php

declare(strict_types=1);

if (!function_exists('env_load_file')) {
    function env_load_file(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $data = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $data[$key] = $value;
        }

        return $data;
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, $default = null)
    {
        static $fileEnv = null;

        if ($fileEnv === null) {
            $basePath = dirname(__DIR__);
            $fileEnv = env_load_file($basePath . '/.env');
        }

        if (array_key_exists($key, $fileEnv)) {
            return $fileEnv[$key];
        }
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return $default;
    }
}
