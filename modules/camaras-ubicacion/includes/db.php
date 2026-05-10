<?php

require_once __DIR__ . '/config.php';

/**
 * Devuelve una conexión mysqli a la base de datos del módulo cámaras.
 *
 * Importante:
 * easySeri carga los módulos dentro de closures del router. Si creamos
 * $mysqli como variable normal al hacer require dentro de una closure, esa
 * variable queda en el ámbito local de la closure y luego global $mysqli puede
 * devolver null. Por eso guardamos la conexión explícitamente en $GLOBALS.
 */
if (!function_exists('camaras_db')) {
    function camaras_db(): mysqli
    {
        if (isset($GLOBALS['camaras_mysqli']) && $GLOBALS['camaras_mysqli'] instanceof mysqli) {
            return $GLOBALS['camaras_mysqli'];
        }

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($mysqli->connect_errno) {
            http_response_code(500);
            die('Error de conexión a la base de datos cámaras');
        }

        $mysqli->set_charset('utf8mb4');

        $GLOBALS['camaras_mysqli'] = $mysqli;

        return $GLOBALS['camaras_mysqli'];
    }
}

// Función helper para consultas preparadas rápidas
if (!function_exists('db_query')) {
    function db_query(mysqli $mysqli, string $sql, string $types = '', array $params = [])
    {
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            throw new Exception('Error prepare: ' . $mysqli->error);
        }

        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception('Error execute: ' . $stmt->error);
        }

        return $stmt;
    }
}

// Abrimos la conexión una vez al cargar el archivo para mantener compatibilidad
// con código legacy que pudiera esperar que la conexión ya exista.
camaras_db();
