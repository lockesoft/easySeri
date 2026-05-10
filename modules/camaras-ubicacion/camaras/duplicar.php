<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';
require_login();

$db = camaras_db();
$userId = current_user_id();
$sourceId = (int)($_GET['id'] ?? 0);

if ($sourceId <= 0) {
    flash('error', 'Cámara origen no válida');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

$stmt = db_query($db, 'SELECT id, name, code, plant_code, priority, entry_row, entry_col, notes FROM cameras WHERE id=?', 'i', [$sourceId]);
$source = $stmt->get_result()->fetch_assoc();

if (!$source) {
    flash('error', 'Cámara origen no encontrada');
    header('Location: /easyseri/camaras-ubicacion/camaras');
    exit;
}

if (!$userId || !PlantService::userCanAccessPlant((int)$userId, (string)$source['plant_code'])) {
    http_response_code(403);
    ob_start();
    echo '<h1>403</h1><p>No tienes acceso a la planta de la cámara origen.</p>';
    echo '<p><a href="/easyseri/camaras-ubicacion/camaras">Volver</a></p>';
    $content = ob_get_clean();
    return;
}

$plants = PlantService::getPlantsForUser((int)$userId);
$activePlant = PlantService::getActivePlantForCurrentUser();

// Conteos informativos de la estructura que se copiará.
$positionsCount = 0;
$rowGroupsCount = 0;
try {
    $st = db_query($db, 'SELECT COUNT(*) AS total FROM camera_positions WHERE camera_id=?', 'i', [$sourceId]);
    $positionsCount = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);

    $st = db_query($db, 'SELECT COUNT(*) AS total FROM camera_row_groups WHERE camera_id=?', 'i', [$sourceId]);
    $rowGroupsCount = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);
} catch (Throwable $e) {
    // Solo informativo. No bloqueamos el formulario.
}

ob_start();
?>

<h1>Duplicar cámara</h1>

<p>
    <a href="/easyseri/camaras-ubicacion/camaras">← Volver a cámaras</a>
</p>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Vas a duplicar la estructura de <strong><?= e($source['name']) ?></strong>
    <?= $source['code'] ? '(' . e($source['code']) . ')' : '' ?>.
    Se copiarán posiciones, tipos de celda, niveles, punto de entrada y filas reales.
    <strong>No se copiarán ubicaciones ni ocupación actual.</strong>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="h4 mb-0"><?= (int)$positionsCount ?></div>
                <div class="text-muted">posiciones a copiar</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="h4 mb-0"><?= (int)$rowGroupsCount ?></div>
                <div class="text-muted">filas reales a copiar</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="h4 mb-0"><?= e($source['plant_code']) ?></div>
                <div class="text-muted">planta origen</div>
            </div>
        </div>
    </div>
</div>

<?php if (!$plants): ?>
    <div class="alert alert-danger">
        Tu usuario no tiene plantas asignadas. Antes de duplicar cámaras, asigna plantas desde Admin → Plantas.
    </div>
<?php else: ?>
    <form method="post" action="/easyseri/camaras-ubicacion/camaras/duplicar/guardar" class="card shadow-sm">
        <input type="hidden" name="source_id" value="<?= (int)$sourceId ?>">

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Planta / almacén destino</label>
                    <select name="plant_code" class="form-select" required>
                        <?php foreach ($plants as $plant): ?>
                            <?php
                                $selected = false;
                                if ($activePlant && $activePlant['code'] === $plant['code']) {
                                    $selected = true;
                                } elseif (!$activePlant && $source['plant_code'] === $plant['code']) {
                                    $selected = true;
                                }
                            ?>
                            <option value="<?= e($plant['code']) ?>" <?= $selected ? 'selected' : '' ?>>
                                <?= e($plant['code']) ?> - <?= e($plant['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Nombre nueva cámara</label>
                    <input name="name" class="form-control" required value="Copia de <?= e($source['name']) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Código nuevo</label>
                    <input name="code" class="form-control" placeholder="Código único">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prioridad</label>
                    <input name="priority" type="number" class="form-control" value="<?= (int)$source['priority'] ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label">Notas</label>
                    <input name="notes" class="form-control" value="<?= e($source['notes'] ?? '') ?>">
                </div>
            </div>

            <div class="alert alert-warning mt-3 mb-0">
                Revisa bien el código nuevo: si lo dejas vacío se permitirá crear la cámara sin código, pero si introduces uno debe ser único.
            </div>

            <div class="mt-3 text-end">
                <a class="btn btn-outline-secondary" href="/easyseri/camaras-ubicacion/camaras">Cancelar</a>
                <button class="btn btn-primary">Duplicar estructura y abrir plano</button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php
$content = ob_get_clean();
