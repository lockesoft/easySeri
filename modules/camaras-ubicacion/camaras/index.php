<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$db = camaras_db();
$error = null;
$cams = [];

try {
    $sql = "
        SELECT id, name, code, plant_code, priority, entry_row, entry_col, notes
        FROM cameras
        ORDER BY plant_code ASC, priority DESC, id ASC
    ";
    $stmt = $db->query($sql);
    $cams = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) {
    $error = $e->getMessage();
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1>Cámaras</h1>
        <p class="text-muted mb-0">Administración dinámica de cámaras, planos, posiciones y filas reales.</p>
    </div>
    <a href="/easyseri/camaras-ubicacion/camaras/crear" class="btn btn-success">➕ Nueva cámara</a>
</div>

<p>
    <a href="/easyseri/camaras-ubicacion">← Volver al módulo cámaras</a>
</p>

<?php if ($msg = flash('ok')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">Error cargando cámaras: <?= e($error) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Consejo: para crear cámaras de otra planta con la misma estructura, usa <strong>Duplicar</strong>.
    Se copiará el plano, niveles y filas reales, pero no las ubicaciones ni ocupación actual.
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Planta / almacén</th>
                    <th>Nombre visible</th>
                    <th>Código</th>
                    <th>Prioridad</th>
                    <th>P. entrada</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cams as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><strong><?= e($c['plant_code'] ?: '—') ?></strong></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['code']) ?></td>
                    <td><?= (int)$c['priority'] ?></td>
                    <td>
                        <?php if (!empty($c['entry_row']) && !empty($c['entry_col'])): ?>
                            F<?= (int)$c['entry_row'] ?>-C<?= (int)$c['entry_col'] ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($c['notes'] ?? '') ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/easyseri/camaras-ubicacion/camaras/plano?id=<?= (int)$c['id'] ?>">🧩 Plano</a>
                        <a class="btn btn-sm btn-outline-secondary" href="/easyseri/camaras-ubicacion/camaras/editar?id=<?= (int)$c['id'] ?>">✏️ Editar</a>
                        <a class="btn btn-sm btn-outline-success" href="/easyseri/camaras-ubicacion/camaras/duplicar?id=<?= (int)$c['id'] ?>">⧉ Duplicar</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$cams): ?>
                <tr>
                    <td colspan="8">No hay cámaras creadas.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
