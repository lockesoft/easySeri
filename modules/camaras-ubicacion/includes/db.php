<?php
require_once __DIR__ . '/config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Error de conexión a la base de datos');
}
$mysqli->set_charset('utf8mb4');

// Función helper para consultas preparadas rápidas
function db_query($mysqli, $sql, $types = '', $params = []) {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error prepare: ' . $mysqli->error);
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception('Error execute: ' . $stmt->error);
    }
    return $stmt;
}
if (!function_exists('db')) {
  function db(): mysqli {
    global $mysqli;
    return $mysqli;
  }
    if (!function_exists('camaras_db')) {
  function camaras_db(): mysqli {
    global $mysqli;
    return $mysqli;
  }
}



}
