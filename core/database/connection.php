<?php

function loadEnv()
{
    $envPath = __DIR__ . '/../../.env';

    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

function envValue($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        loadEnv();

        $host = envValue('DB_HOST', '127.0.0.1');
        $db   = envValue('DB_DATABASE', 'easyseri');
        $user = envValue('DB_USERNAME', 'root');
        $pass = envValue('DB_PASSWORD', '');
        $port = envValue('DB_PORT', '3306');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    return $pdo;
}