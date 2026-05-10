<?php
// /public/api/cameras.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../../../core/plants/PlantService.php';

require_login();

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

function out($ok, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function table_has_column(mysqli $db, string $table, string $column): bool
{
    $sql = "
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ";

    $st = $db->prepare($sql);

    if (!$st) {
        return false;
    }

    $st->bind_param('ss', $table, $column);
    $st->execute();
    $st->store_result();

    $ok = $st->num_rows > 0;

    $st->free_result();
    $st->close();

    return $ok;
}

try {
    $db = camaras_db();

    if (!table_has_column($db, 'cameras', 'plant_code')) {
        out(false, [
            'error' => 'Falta la columna cameras.plant_code. Ejecuta la migración multi-planta antes de usar cámaras.'
        ], 500);
    }

    $activePlant = PlantService::getActivePlantForCurrentUser();

    if (!$activePlant || empty($activePlant['code'])) {
        out(false, [
            'error' => 'Tu usuario no tiene planta activa. Selecciona una planta antes de usar cámaras.'
        ], 403);
    }

    $plantCode = (string)$activePlant['code'];

    $st = $db->prepare("
        SELECT id, name, code, plant_code, priority
        FROM cameras
        WHERE plant_code = ?
        ORDER BY priority DESC, id ASC
    ");

    if (!$st) {
        out(false, ['error' => 'DB prepare cameras: ' . $db->error], 500);
    }

    $st->bind_param('s', $plantCode);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    out(true, [
        'active_plant' => [
            'code' => $plantCode,
            'name' => $activePlant['name'] ?? $plantCode,
        ],
        'cameras' => $rows,
    ]);
} catch (Throwable $e) {
    out(false, ['error' => $e->getMessage()], 500);
}
