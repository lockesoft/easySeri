<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /easyseri/camaras-ubicacion/camaras/crear');
    exit;
}

$db = camaras_db();
$userId = current_user_id();

$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$plantCode = strtoupper(trim($_POST['plant_code'] ?? ''));
$priority = (int)($_POST['priority'] ?? 0);
$rows = max(1, min(200, (int)($_POST['rows'] ?? 1)));
$cols = max(1, min(200, (int)($_POST['cols'] ?? 1)));
$levels = max(1, min(20, (int)($_POST['levels'] ?? 1)));
$notes = trim($_POST['notes'] ?? '');

if ($name === '') {
    flash('error', 'Nombre requerido');
    header('Location: /easyseri/camaras-ubicacion/camaras/crear');
    exit;
}

if ($plantCode === '' || !$userId || !PlantService::userCanAccessPlant((int)$userId, $plantCode)) {
    flash('error', 'Planta no válida o sin acceso para tu usuario');
    header('Location: /easyseri/camaras-ubicacion/camaras/crear');
    exit;
}

$db->begin_transaction();

try {
    $stmt = $db->prepare('
        INSERT INTO cameras (name, code, plant_code, priority, notes)
        VALUES (?, ?, ?, ?, ?)
    ');

    if (!$stmt) {
        throw new Exception('Error prepare camera: ' . $db->error);
    }

    $codeValue = $code !== '' ? $code : null;
    $notesValue = $notes !== '' ? $notes : null;

    $stmt->bind_param('sssis', $name, $codeValue, $plantCode, $priority, $notesValue);

    if (!$stmt->execute()) {
        throw new Exception('Error insert camera: ' . $stmt->error);
    }

    $cameraId = (int)$db->insert_id;
    $stmt->close();

    $stmtPos = $db->prepare('
        INSERT INTO camera_positions (camera_id, row_idx, col_idx, max_levels, type)
        VALUES (?, ?, ?, ?, \'almacenaje\')
    ');

    if (!$stmtPos) {
        throw new Exception('Error prepare positions: ' . $db->error);
    }

    for ($r = 1; $r <= $rows; $r++) {
        for ($c = 1; $c <= $cols; $c++) {
            $stmtPos->bind_param('iiii', $cameraId, $r, $c, $levels);
            if (!$stmtPos->execute()) {
                throw new Exception('Error insert position: ' . $stmtPos->error);
            }
        }
    }

    $stmtPos->close();

    $db->commit();
    flash('ok', 'Cámara creada correctamente. Ahora configura su plano.');
    header('Location: /easyseri/camaras-ubicacion/camaras/plano?id=' . $cameraId);
    exit;
} catch (Throwable $e) {
    $db->rollback();
    flash('error', 'Error al crear cámara: ' . $e->getMessage());
    header('Location: /easyseri/camaras-ubicacion/camaras/crear');
    exit;
}
