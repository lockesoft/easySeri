<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';
require_login();

$userId = current_user_id();
$plants = $userId ? PlantService::getPlantsForUser((int)$userId) : [];
$activePlant = PlantService::getActivePlantForCurrentUser();

ob_start();
?>

<h1>Nueva cámara</h1>

<p>
    <a href="/easyseri/camaras-ubicacion/camaras">← Volver a cámaras</a>
</p>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (!$plants): ?>
    <div class="alert alert-danger">
        Tu usuario no tiene plantas asignadas. Antes de crear cámaras, asigna plantas desde Admin → Plantas.
    </div>
<?php else: ?>
    <form method="post" action="/easyseri/camaras-ubicacion/camaras/guardar" class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Planta / almacén</label>
                    <select name="plant_code" class="form-select" required>
                        <?php foreach ($plants as $plant): ?>
                            <option value="<?= e($plant['code']) ?>" <?= ($activePlant && $activePlant['code'] === $plant['code']) ? 'selected' : '' ?>>
                                <?= e($plant['code']) ?> - <?= e($plant['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">La cámara quedará asociada a esta planta/almacén.</div>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Nombre visible</label>
                    <input name="name" class="form-control" required placeholder="Cámara 4 descarga">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input name="code" class="form-control" placeholder="Descarga4">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prioridad</label>
                    <input name="priority" type="number" class="form-control" value="0">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Filas del plano</label>
                    <input name="rows" type="number" class="form-control" value="5" min="1" max="200" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Columnas del plano</label>
                    <input name="cols" type="number" class="form-control" value="10" min="1" max="200" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Niveles por defecto</label>
                    <input name="levels" type="number" class="form-control" value="1" min="1" max="20" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <input name="notes" class="form-control" placeholder="Observaciones opcionales">
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                Al crear la cámara se generará automáticamente la matriz completa en <strong>camera_positions</strong>.
                Después podrás pintar pasillos, bloqueadas, niveles y filas reales desde el plano.
            </div>

            <div class="mt-3 text-end">
                <a class="btn btn-outline-secondary" href="/easyseri/camaras-ubicacion/camaras">Cancelar</a>
                <button class="btn btn-primary">Crear cámara y abrir plano</button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php
$content = ob_get_clean();
