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

$sourceId = (int)($_POST['source_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$plantCode = strtoupper(trim($_POST['plant_code'] ?? ''));
$priority = (int)($_POST['priority'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($sourceId <= 0) {
    flash('error', 'Cámara origen no válida');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

if ($name === '') {
    flash('error', 'Nombre requerido');
    header('Location: /easyseri/camaras-ubicacion/camaras/duplicar?id=' . $sourceId);
    exit;
}

if ($plantCode === '' || !$userId || !PlantService::userCanAccessPlant((int)$userId, $plantCode)) {
    flash('error', 'Planta destino no válida o sin acceso para tu usuario');
    header('Location: /easyseri/camaras-ubicacion/camaras/duplicar?id=' . $sourceId);
    exit;
}

try {
    $stmt = db_query(
        $db,
        'SELECT id, name, code, plant_code, entry_row, entry_col FROM cameras WHERE id=?',
        'i',
        [$sourceId]
    );
    $source = $stmt->get_result()->fetch_assoc();

    if (!$source) {
        throw new Exception('Cámara origen no encontrada');
    }

    if (!PlantService::userCanAccessPlant((int)$userId, (string)$source['plant_code'])) {
        throw new Exception('No tienes acceso a la planta de la cámara origen');
    }

    $db->begin_transaction();

    $codeValue = $code !== '' ? $code : null;
    $notesValue = $notes !== '' ? $notes : null;
    $entryRow = $source['entry_row'] !== null ? (int)$source['entry_row'] : null;
    $entryCol = $source['entry_col'] !== null ? (int)$source['entry_col'] : null;

    $stmtCam = $db->prepare('
        INSERT INTO cameras (name, code, plant_code, priority, entry_row, entry_col, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    if (!$stmtCam) {
        throw new Exception('Error prepare cámara destino: ' . $db->error);
    }

    $stmtCam->bind_param('sssiiis', $name, $codeValue, $plantCode, $priority, $entryRow, $entryCol, $notesValue);
    if (!$stmtCam->execute()) {
        throw new Exception('Error insert cámara destino: ' . $stmtCam->error);
    }

    $newCameraId = (int)$db->insert_id;
    $stmtCam->close();

    // 1) Copiar posiciones y construir mapa posición vieja -> posición nueva.
    $oldToNewPosition = [];

    $stmtPositions = db_query(
        $db,
        'SELECT id, row_idx, col_idx, max_levels, type FROM camera_positions WHERE camera_id=? ORDER BY row_idx, col_idx',
        'i',
        [$sourceId]
    );
    $positions = $stmtPositions->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmtInsertPos = $db->prepare('
        INSERT INTO camera_positions (camera_id, row_idx, col_idx, max_levels, type)
        VALUES (?, ?, ?, ?, ?)
    ');
    if (!$stmtInsertPos) {
        throw new Exception('Error prepare posiciones: ' . $db->error);
    }

    foreach ($positions as $pos) {
        $oldId = (int)$pos['id'];
        $rowIdx = (int)$pos['row_idx'];
        $colIdx = (int)$pos['col_idx'];
        $maxLevels = (int)$pos['max_levels'];
        $type = (string)$pos['type'];

        $stmtInsertPos->bind_param('iiiis', $newCameraId, $rowIdx, $colIdx, $maxLevels, $type);
        if (!$stmtInsertPos->execute()) {
            throw new Exception('Error copiando posición: ' . $stmtInsertPos->error);
        }

        $oldToNewPosition[$oldId] = (int)$db->insert_id;
    }
    $stmtInsertPos->close();

    // 2) Copiar grupos y reconstruir sus celdas contra las nuevas posiciones.
    $stmtGroups = db_query(
        $db,
        'SELECT id, label, order_index, orientation FROM camera_row_groups WHERE camera_id=? ORDER BY order_index ASC, id ASC',
        'i',
        [$sourceId]
    );
    $groups = $stmtGroups->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmtInsertGroup = $db->prepare('
        INSERT INTO camera_row_groups (camera_id, label, order_index, orientation)
        VALUES (?, ?, ?, ?)
    ');
    if (!$stmtInsertGroup) {
        throw new Exception('Error prepare grupos: ' . $db->error);
    }

    $stmtCells = $db->prepare('SELECT position_id FROM camera_row_cells WHERE row_group_id=? ORDER BY id ASC');
    $stmtInsertCell = $db->prepare('INSERT INTO camera_row_cells (row_group_id, position_id) VALUES (?, ?)');

    if (!$stmtCells || !$stmtInsertCell) {
        throw new Exception('Error prepare celdas de grupo: ' . $db->error);
    }

    foreach ($groups as $group) {
        $oldGroupId = (int)$group['id'];
        $label = (string)$group['label'];
        $orderIndex = (int)$group['order_index'];
        $orientation = (string)$group['orientation'];

        $stmtInsertGroup->bind_param('isis', $newCameraId, $label, $orderIndex, $orientation);
        if (!$stmtInsertGroup->execute()) {
            throw new Exception('Error copiando grupo: ' . $stmtInsertGroup->error);
        }
        $newGroupId = (int)$db->insert_id;

        $stmtCells->bind_param('i', $oldGroupId);
        if (!$stmtCells->execute()) {
            throw new Exception('Error leyendo celdas grupo: ' . $stmtCells->error);
        }
        $stmtCells->store_result();
        $stmtCells->bind_result($oldPositionId);

        while ($stmtCells->fetch()) {
            $oldPositionId = (int)$oldPositionId;
            if (!isset($oldToNewPosition[$oldPositionId])) {
                continue;
            }
            $newPositionId = $oldToNewPosition[$oldPositionId];
            $stmtInsertCell->bind_param('ii', $newGroupId, $newPositionId);
            if (!$stmtInsertCell->execute()) {
                throw new Exception('Error copiando celda de grupo: ' . $stmtInsertCell->error);
            }
        }
        $stmtCells->free_result();
    }

    $stmtInsertGroup->close();
    $stmtCells->close();
    $stmtInsertCell->close();

    $db->commit();

    flash('ok', 'Cámara duplicada correctamente. Se ha copiado la estructura sin ubicaciones.');
    header('Location: /easyseri/camaras-ubicacion/camaras/plano?id=' . $newCameraId);
    exit;
} catch (Throwable $e) {
    if ($db instanceof mysqli) {
        try { $db->rollback(); } catch (Throwable $ignored) {}
    }

    flash('error', 'Error al duplicar cámara: ' . $e->getMessage());
    header('Location: /easyseri/camaras-ubicacion/camaras/duplicar?id=' . $sourceId);
    exit;
}
