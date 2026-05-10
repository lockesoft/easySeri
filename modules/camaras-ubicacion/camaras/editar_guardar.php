<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

$db = camaras_db();
$userId = current_user_id();

$cameraId = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$plantCode = strtoupper(trim($_POST['plant_code'] ?? ''));
$priority = (int)($_POST['priority'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($cameraId <= 0) {
    flash('error', 'Cámara no válida');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

if ($name === '') {
    flash('error', 'Nombre requerido');
    header('Location: /easyseri/camaras-ubicacion/camaras/editar?id=' . $cameraId);
    exit;
}

if ($plantCode === '' || !$userId || !PlantService::userCanAccessPlant((int)$userId, $plantCode)) {
    flash('error', 'Planta no válida o sin acceso para tu usuario');
    header('Location: /easyseri/camaras-ubicacion/camaras/editar?id=' . $cameraId);
    exit;
}

try {
    $stmt = db_query(
        $db,
        'SELECT id, plant_code FROM cameras WHERE id=?',
        'i',
        [$cameraId]
    );
    $camera = $stmt->get_result()->fetch_assoc();

    if (!$camera) {
        throw new Exception('Cámara no encontrada');
    }

    if (!PlantService::userCanAccessPlant((int)$userId, (string)$camera['plant_code'])) {
        throw new Exception('No tienes acceso a la planta actual de esta cámara');
    }

    $codeValue = $code !== '' ? $code : null;
    $notesValue = $notes !== '' ? $notes : null;

    $stmt = $db->prepare('
        UPDATE cameras
        SET name = ?, code = ?, plant_code = ?, priority = ?, notes = ?
        WHERE id = ?
    ');

    if (!$stmt) {
        throw new Exception('Error prepare: ' . $db->error);
    }

    $stmt->bind_param('sssisi', $name, $codeValue, $plantCode, $priority, $notesValue, $cameraId);

    if (!$stmt->execute()) {
        throw new Exception('Error guardando cámara: ' . $stmt->error);
    }

    flash('ok', 'Cámara actualizada correctamente');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
} catch (Throwable $e) {
    flash('error', 'Error al guardar cámara: ' . $e->getMessage());
    header('Location: /easyseri/camaras-ubicacion/camaras/editar?id=' . $cameraId);
    exit;
}
