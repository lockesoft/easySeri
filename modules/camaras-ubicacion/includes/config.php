<?php

/*
|--------------------------------------------------------------------------
| Configuración legacy del módulo camaras-ubicacion
|--------------------------------------------------------------------------
| Las credenciales NO deben estar en el repositorio.
| Se leen desde el .env principal de easySeri.
*/

function camaras_load_env_once(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;

    $envPath = __DIR__ . '/../../../.env';

    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $_ENV[trim($key)] = trim($value);
    }
}

function camaras_env(string $key, $default = null)
{
    camaras_load_env_once();

    return $_ENV[$key] ?? $default;
}

define('DB_HOST', camaras_env('CAMARAS_DB_HOST', 'localhost'));
define('DB_USER', camaras_env('CAMARAS_DB_USER', 'root'));
define('DB_PASS', camaras_env('CAMARAS_DB_PASS', ''));
define('DB_NAME', camaras_env('CAMARAS_DB_NAME', 'ubicacion'));

define('APP_NAME', 'Cámaras Cítricos');
define('USE_SUPERVISOR_PIN', false);
define('BASE_URL', '/easyseri/modules/camaras-ubicacion/legacy');