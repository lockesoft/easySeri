<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';
require_login();

$db = camaras_db();
$userId = current_user_id();
$cameraId = (int)($_GET['id'] ?? 0);

if ($cameraId <= 0) {
    flash('error', 'Cámara no válida');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

$stmt = db_query($db, 'SELECT id, name, code, plant_code, priority, entry_row, entry_col, notes FROM cameras WHERE id=?', 'i', [$cameraId]);
$camera = $stmt->get_result()->fetch_assoc();

if (!$camera) {
    flash('error', 'Cámara no encontrada');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

if (!$userId || !PlantService::userCanAccessPlant((int)$userId, (string)$camera['plant_code'])) {
    http_response_code(403);
    ob_start();
    echo '<h1>403</h1><p>No tienes acceso a la planta actual de esta cámara.</p>';
    echo '<p><a href="/easyseri/camaras-ubicacion/camaras">Volver</a></p>';
    $content = ob_get_clean();
    return;
}

$plants = PlantService::getPlantsForUser((int)$userId);

ob_start();
?>

<h1>Editar cámara</h1>

<p>
    <a href="/easyseri/camaras-ubicacion/camaras">← Volver a cámaras</a>
</p>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Esta pantalla solo modifica datos generales. El plano físico, posiciones, niveles y filas reales se editan desde <strong>Plano</strong>.
</div>

<form method="post" action="/easyseri/camaras-ubicacion/camaras/editar/guardar" class="card shadow-sm">
    <input type="hidden" name="id" value="<?= (int)$camera['id'] ?>">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Planta / almacén</label>
                <select name="plant_code" class="form-select" required>
                    <?php foreach ($plants as $plant): ?>
                        <option value="<?= e($plant['code']) ?>" <?= $camera['plant_code'] === $plant['code'] ? 'selected' : '' ?>>
                            <?= e($plant['code']) ?> - <?= e($plant['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Cambiar la planta afecta a qué usuarios/planta verán esta cámara en el escaneo.
                </div>
            </div>

            <div class="col-md-5">
                <label class="form-label">Nombre visible</label>
                <input name="name" class="form-control" required value="<?= e($camera['name']) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input name="code" class="form-control" value="<?= e($camera['code'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Prioridad</label>
                <input name="priority" type="number" class="form-control" value="<?= (int)$camera['priority'] ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Punto entrada actual</label>
                <input class="form-control" disabled value="<?= (!empty($camera['entry_row']) && !empty($camera['entry_col'])) ? 'F' . (int)$camera['entry_row'] . '-C' . (int)$camera['entry_col'] : 'Sin definir' ?>">
                <div class="form-text">Se cambia desde el plano.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Notas</label>
                <input name="notes" class="form-control" value="<?= e($camera['notes'] ?? '') ?>">
            </div>
        </div>

        <div class="alert alert-warning mt-3 mb-0">
            Si cambias la planta de una cámara con ubicaciones reales, esas ubicaciones pasarán a quedar asociadas indirectamente a la nueva planta. Hazlo solo si estás seguro.
        </div>

        <div class="mt-3 text-end">
            <a class="btn btn-outline-secondary" href="/easyseri/camaras-ubicacion/camaras">Cancelar</a>
            <a class="btn btn-outline-primary" href="/easyseri/camaras-ubicacion/camaras/plano?id=<?= (int)$camera['id'] ?>">Abrir plano</a>
            <button class="btn btn-primary">Guardar cambios</button>
        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
