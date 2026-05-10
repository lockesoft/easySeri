<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../../core/plants/PlantService.php';

require_login();

function camaras_admin_redirect(string $path): void
{
    header('Location: /easyseri/camaras-ubicacion' . $path);
    exit;
}

$cameraId = (int)($_GET['id'] ?? 0);

if ($cameraId <= 0) {
    flash('error', 'Cámara no válida');
    camaras_admin_redirect('/camaras');
}

$db = camaras_db();
$userId = current_user_id();

$stmt = db_query($db, 'SELECT id, name, code, plant_code, priority, entry_row, entry_col, notes FROM cameras WHERE id=?', 'i', [$cameraId]);
$cam = $stmt->get_result()->fetch_assoc();

if (!$cam) {
    flash('error', 'Cámara no encontrada');
    camaras_admin_redirect('/camaras');
}

if (!$userId || !PlantService::userCanAccessPlant((int)$userId, (string)$cam['plant_code'])) {
    http_response_code(403);
    ob_start();
    echo '<h1>403</h1><p>No tienes acceso a la planta de esta cámara.</p>';
    echo '<p><a href="/easyseri/camaras-ubicacion/camaras">Volver</a></p>';
    $content = ob_get_clean();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cells = $_POST['cell'] ?? [];

    try {
        if ($action === 'paint') {
            $type = $_POST['type'] ?? '';

            if (!in_array($type, ['almacenaje', 'pasillo', 'bloqueada'], true)) {
                throw new Exception('Tipo inválido');
            }

            if (!$cells) {
                throw new Exception('Selecciona al menos una celda');
            }

            $st = $db->prepare('UPDATE camera_positions SET type=? WHERE camera_id=? AND row_idx=? AND col_idx=?');
            if (!$st) {
                throw new Exception($db->error);
            }

            foreach ($cells as $rc) {
                [$r, $c] = array_map('intval', explode('-', $rc));
                $st->bind_param('siii', $type, $cameraId, $r, $c);
                if (!$st->execute()) {
                    throw new Exception($st->error);
                }
            }

            flash('ok', 'Pintado aplicado: ' . $type);
            camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
        }

        if ($action === 'set_entry') {
            if (count($cells) !== 1) {
                throw new Exception('Selecciona exactamente una celda como punto de entrada');
            }

            [$r, $c] = array_map('intval', explode('-', $cells[0]));
            db_query($db, 'UPDATE cameras SET entry_row=?, entry_col=? WHERE id=?', 'iii', [$r, $c, $cameraId]);

            flash('ok', "Punto de entrada: F{$r}-C{$c}");
            camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
        }

        if ($action === 'set_levels') {
            $levels = max(1, (int)($_POST['levels'] ?? 1));

            if (!$cells) {
                throw new Exception('Selecciona al menos una celda');
            }

            $st = $db->prepare('UPDATE camera_positions SET max_levels=? WHERE camera_id=? AND row_idx=? AND col_idx=?');
            if (!$st) {
                throw new Exception($db->error);
            }

            foreach ($cells as $rc) {
                [$r, $c] = array_map('intval', explode('-', $rc));
                $st->bind_param('iiii', $levels, $cameraId, $r, $c);
                if (!$st->execute()) {
                    throw new Exception($st->error);
                }
            }

            flash('ok', 'Niveles aplicados: ' . $levels);
            camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
        }

        if ($action === 'create_row_group') {
            if (!$cells && !empty($_POST['cells_csv'])) {
                $cells = array_filter(array_map('trim', explode(',', $_POST['cells_csv'])));
            }

            $label = trim($_POST['rg_label'] ?? '');
            $order = (int)($_POST['rg_order'] ?? 0);
            $orient = $_POST['rg_orient'] ?? 'vertical';

            if ($label === '' || $order <= 0) {
                throw new Exception('Etiqueta y orden son obligatorios');
            }

            if (!in_array($orient, ['vertical', 'horizontal', 'mixed'], true)) {
                throw new Exception('Orientación inválida');
            }

            if (!$cells) {
                throw new Exception('Selecciona celdas para crear la fila real');
            }

            db_query(
                $db,
                'INSERT INTO camera_row_groups (camera_id, label, order_index, orientation) VALUES (?, ?, ?, ?)',
                'isis',
                [$cameraId, $label, $order, $orient]
            );

            $groupId = (int)$db->insert_id;

            $stPos = $db->prepare('SELECT id FROM camera_positions WHERE camera_id=? AND row_idx=? AND col_idx=?');
            $stIns = $db->prepare('INSERT INTO camera_row_cells (row_group_id, position_id) VALUES (?, ?)');

            if (!$stPos || !$stIns) {
                throw new Exception($db->error);
            }

            foreach ($cells as $rc) {
                [$r, $c] = array_map('intval', explode('-', $rc));

                $stPos->bind_param('iii', $cameraId, $r, $c);
                if (!$stPos->execute()) {
                    throw new Exception($stPos->error);
                }

                $stPos->store_result();
                $stPos->bind_result($positionId);
                $found = $stPos->fetch();
                $stPos->free_result();

                if ($found) {
                    $stIns->bind_param('ii', $groupId, $positionId);
                    if (!$stIns->execute()) {
                        throw new Exception($stIns->error);
                    }
                }
            }

            flash('ok', 'Fila real creada: ' . $label);
            camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
        }

        if ($action === 'delete_row_group') {
            $groupId = (int)($_POST['rg_id'] ?? 0);

            if ($groupId <= 0) {
                throw new Exception('Grupo no válido');
            }

            db_query($db, 'DELETE FROM camera_row_groups WHERE id=? AND camera_id=?', 'ii', [$groupId, $cameraId]);
            flash('ok', 'Fila real eliminada');
            camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        camaras_admin_redirect('/camaras/plano?id=' . $cameraId);
    }
}

$stmt = db_query($db, 'SELECT row_idx, col_idx, max_levels, type, id FROM camera_positions WHERE camera_id=? ORDER BY row_idx, col_idx', 'i', [$cameraId]);
$res = $stmt->get_result();

$cells = [];
$maxR = 0;
$maxC = 0;
$stats = [
    'total' => 0,
    'almacenaje' => 0,
    'pasillo' => 0,
    'bloqueada' => 0,
    'capacity' => 0,
];

while ($row = $res->fetch_assoc()) {
    $r = (int)$row['row_idx'];
    $c = (int)$row['col_idx'];
    $type = (string)$row['type'];
    $levels = (int)$row['max_levels'];

    $cells[$r][$c] = $row;
    $maxR = max($maxR, $r);
    $maxC = max($maxC, $c);

    $stats['total']++;
    if (isset($stats[$type])) {
        $stats[$type]++;
    }
    if ($type === 'almacenaje') {
        $stats['capacity'] += $levels;
    }
}

$rowGroups = [];
$groupCells = [];
$assignedCells = [];

try {
    $stmt = db_query(
        $db,
        'SELECT id, label, order_index, orientation FROM camera_row_groups WHERE camera_id=? ORDER BY order_index ASC, id ASC',
        'i',
        [$cameraId]
    );
    $rowGroups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($rowGroups) {
        $groupIds = array_map('intval', array_column($rowGroups, 'id'));
        $in = implode(',', array_fill(0, count($groupIds), '?'));
        $types = str_repeat('i', count($groupIds));

        $sql = "
            SELECT crc.row_group_id, cp.row_idx, cp.col_idx
            FROM camera_row_cells crc
            JOIN camera_positions cp ON cp.id = crc.position_id
            WHERE crc.row_group_id IN ($in)
        ";
        $st2 = $db->prepare($sql);
        $st2->bind_param($types, ...$groupIds);
        $st2->execute();
        $res2 = $st2->get_result();

        while ($r = $res2->fetch_assoc()) {
            $rc = $r['row_idx'] . '-' . $r['col_idx'];
            $groupCells[(int)$r['row_group_id']][] = $rc;
            $assignedCells[$rc] = true;
        }
    }
} catch (Throwable $e) {
    $rowGroups = [];
    $groupCells = [];
    $assignedCells = [];
}

$assignedCount = count($assignedCells);
$hasEntry = !empty($cam['entry_row']) && !empty($cam['entry_col']);
$warnings = [];

if ($stats['almacenaje'] === 0) {
    $warnings[] = 'La cámara no tiene posiciones de almacenaje.';
}
if (!$rowGroups) {
    $warnings[] = 'Todavía no hay filas reales/grupos. El escaneo necesita filas reales para trabajar de forma cómoda.';
}
if (!$hasEntry) {
    $warnings[] = 'No hay punto de entrada definido.';
}

ob_start();
?>

<style>
:root {
    --cell-size: 54px;
    --alm-bg: #e7f7ee;
    --alm-border: #9fd5b4;
    --pas-bg: #eef1f4;
    --pas-border: #c8ced6;
    --blo-bg: #f8d7da;
    --blo-border: #e79aa3;
    --brand: #f47a20;
}
.camera-editor-shell { display:grid; grid-template-columns: 320px minmax(0, 1fr); gap:16px; align-items:start; }
.camera-topbar { background:linear-gradient(135deg, #17202a, #2d3436); color:#fff; border-radius:18px; padding:18px 20px; box-shadow:0 12px 28px rgba(0,0,0,.12); }
.camera-title { font-size:1.35rem; font-weight:800; margin:0; }
.camera-subtitle { opacity:.85; margin-top:4px; }
.camera-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:999px; font-weight:700; background:rgba(255,255,255,.14); color:#fff; }
.camera-panel { background:#fff; border:1px solid #e8ecef; border-radius:18px; box-shadow:0 10px 22px rgba(0,0,0,.06); overflow:hidden; }
.camera-panel-header { padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e8ecef; font-weight:800; }
.camera-panel-body { padding:14px 16px; }
.stat-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
.stat-card { border:1px solid #edf0f2; border-radius:14px; padding:12px; background:#fff; }
.stat-value { font-size:1.35rem; font-weight:900; line-height:1; }
.stat-label { font-size:.78rem; color:#6c757d; margin-top:5px; }
.tool-button { width:100%; border:1px solid #dfe4e8; background:#fff; padding:10px 12px; border-radius:12px; text-align:left; font-weight:700; transition:.12s ease; }
.tool-button:hover { transform:translateY(-1px); box-shadow:0 6px 14px rgba(0,0,0,.08); }
.tool-button.active { border-color:var(--brand); box-shadow:0 0 0 3px rgba(244,122,32,.18); }
.tool-dot { display:inline-block; width:14px; height:14px; border-radius:4px; margin-right:8px; vertical-align:-2px; border:1px solid rgba(0,0,0,.15); }
.dot-alm { background:var(--alm-bg); }
.dot-pas { background:var(--pas-bg); }
.dot-blo { background:var(--blo-bg); }
.editor-grid-card { background:#fff; border:1px solid #e8ecef; border-radius:18px; box-shadow:0 10px 22px rgba(0,0,0,.06); overflow:hidden; }
.editor-grid-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid #e8ecef; background:#f8fafc; }
.grid-scroll { max-height:72vh; overflow:auto; background:#fbfcfd; padding:14px; }
.grid-table { border-collapse:separate; border-spacing:4px; }
.grid-table td { width:var(--cell-size); height:var(--cell-size); padding:0 !important; border:0 !important; background:transparent !important; }
.grid-head { min-width:var(--cell-size); height:34px; text-align:center; font-weight:800; font-size:.8rem; color:#495057; background:#fff; border:1px solid #e4e8ec; border-radius:10px; cursor:pointer; position:sticky; top:0; z-index:2; }
.grid-head-col { position:sticky; left:0; z-index:3; }
.cell-square { width:100%; height:100%; display:inline-flex; align-items:center; justify-content:center; position:relative; cursor:pointer; user-select:none; border-radius:12px; border:2px solid transparent; transition:.08s ease; font-weight:800; }
.cell-square:hover { transform:scale(1.04); box-shadow:0 8px 18px rgba(0,0,0,.13); z-index:3; }
.cell-alm { background:var(--alm-bg); border-color:var(--alm-border); }
.cell-pas { background:var(--pas-bg); border-color:var(--pas-border); }
.cell-blo { background:var(--blo-bg); border-color:var(--blo-border); }
.cell-symbol { font-size:1.25rem; line-height:1; opacity:.72; }
.cell-label { position:absolute; bottom:3px; right:5px; font-size:.62rem; background:rgba(255,255,255,.72); padding:0 .25rem; border-radius:.25rem; color:#495057; }
.labels-hidden .cell-label { display:none; }
.cell-level { position:absolute; bottom:3px; left:5px; font-size:.62rem; padding:0 .28rem; border-radius:.25rem; background:rgba(0,0,0,.08); color:#212529; }
.cell-entry { outline:4px solid #0d6efd; outline-offset:-4px; }
.cell-entry::after { content:"➜"; position:absolute; top:2px; right:5px; font-size:1rem; color:#0d6efd; }
.cell-selected { box-shadow:0 0 0 4px #ffc107 inset; }
.cell-highlight { outline:4px solid #ffc107; outline-offset:-4px; }
.cell-assigned::before { content:""; position:absolute; top:4px; left:4px; width:8px; height:8px; border-radius:50%; background:var(--brand); }
.cell-square input[type="checkbox"] { position:absolute; inset:0; opacity:0; pointer-events:none; }
.group-list { display:flex; flex-direction:column; gap:8px; max-height:260px; overflow:auto; }
.group-item { border:1px solid #edf0f2; border-radius:12px; padding:9px; background:#fff; }
.group-label { font-weight:800; cursor:pointer; }
.warning-list { display:flex; flex-direction:column; gap:7px; }
.warning-item { padding:9px 11px; border-radius:12px; background:#fff8e5; border:1px solid #ffe0a3; font-size:.9rem; }
@media (max-width: 1100px) { .camera-editor-shell { grid-template-columns:1fr; } .grid-scroll { max-height:65vh; } }
</style>

<div class="camera-topbar mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="camera-title">Editor visual de cámara</h1>
            <div class="camera-subtitle">
                <?= e($cam['name']) ?>
                <?= $cam['code'] ? ' · Código ' . e($cam['code']) : '' ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="camera-pill">🏭 Planta <?= e($cam['plant_code'] ?? '—') ?></span>
            <span class="camera-pill">📐 <?= (int)$maxR ?> × <?= (int)$maxC ?></span>
            <?php if ($hasEntry): ?>
                <span class="camera-pill">➡ Entrada F<?= (int)$cam['entry_row'] ?>-C<?= (int)$cam['entry_col'] ?></span>
            <?php else: ?>
                <span class="camera-pill">⚠ Sin entrada</span>
            <?php endif; ?>
            <a class="btn btn-light btn-sm" href="/easyseri/camaras-ubicacion/camaras">Volver</a>
        </div>
    </div>
</div>

<?php if ($msg = flash('ok')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="camera-editor-shell">
    <aside>
        <div class="camera-panel mb-3">
            <div class="camera-panel-header">Resumen operativo</div>
            <div class="camera-panel-body">
                <div class="stat-grid">
                    <div class="stat-card"><div class="stat-value"><?= (int)$stats['capacity'] ?></div><div class="stat-label">Capacidad total</div></div>
                    <div class="stat-card"><div class="stat-value"><?= count($rowGroups) ?></div><div class="stat-label">Filas reales</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int)$stats['almacenaje'] ?></div><div class="stat-label">Almacenaje</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int)$assignedCount ?></div><div class="stat-label">Celdas en grupos</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int)$stats['pasillo'] ?></div><div class="stat-label">Pasillos</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int)$stats['bloqueada'] ?></div><div class="stat-label">Bloqueadas</div></div>
                </div>
            </div>
        </div>

        <?php if ($warnings): ?>
            <div class="camera-panel mb-3">
                <div class="camera-panel-header">Validaciones pendientes</div>
                <div class="camera-panel-body warning-list">
                    <?php foreach ($warnings as $warning): ?>
                        <div class="warning-item">⚠ <?= e($warning) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="camera-panel mb-3" id="editorForm">
            <div class="camera-panel-header">Herramientas rápidas</div>
            <div class="camera-panel-body">
                <input type="hidden" name="type" id="typeHidden" value="almacenaje">
                <input type="hidden" name="action" id="actionHidden" value="paint">

                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="tool-button active" id="brush-almacenaje" onclick="setBrush('almacenaje')"><span class="tool-dot dot-alm"></span>Pincel almacenaje</button>
                    <button type="button" class="tool-button" id="brush-pasillo" onclick="setBrush('pasillo')"><span class="tool-dot dot-pas"></span>Pincel pasillo</button>
                    <button type="button" class="tool-button" id="brush-bloqueada" onclick="setBrush('bloqueada')"><span class="tool-dot dot-blo"></span>Pincel bloqueada</button>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-2" onclick="document.getElementById('actionHidden').value='paint'">Aplicar pincel a selección</button>

                <div class="input-group mb-2">
                    <span class="input-group-text">Niveles</span>
                    <input type="number" name="levels" class="form-control" value="1" min="1">
                    <button type="submit" class="btn btn-outline-primary" onclick="document.getElementById('actionHidden').value='set_levels'">Aplicar</button>
                </div>

                <button type="submit" class="btn btn-outline-primary w-100 mb-2" onclick="document.getElementById('actionHidden').value='set_entry'">Marcar punto de entrada</button>

                <div class="d-grid gap-2 mt-3">
                    <button type="button" class="btn btn-outline-dark" onclick="selectAll()">Seleccionar todo</button>
                    <button type="button" class="btn btn-outline-dark" onclick="clearSel()">Quitar selección</button>
                </div>

                <div class="small text-muted mt-3">
                    Selecciona celdas en el plano y aplica una acción. También puedes pulsar sobre cabeceras F/C para seleccionar filas o columnas completas.
                </div>
            </div>
        </form>

        <div class="camera-panel mb-3">
            <div class="camera-panel-header">Crear fila real</div>
            <div class="camera-panel-body">
                <form method="post" onsubmit="return injectSelectedCells(this);">
                    <input type="hidden" name="action" value="create_row_group">
                    <input type="hidden" name="cells_csv" value="">

                    <label class="form-label">Etiqueta visible</label>
                    <input name="rg_label" class="form-control mb-2" placeholder="Fila 1" required>

                    <label class="form-label">Orden</label>
                    <input name="rg_order" type="number" min="1" class="form-control mb-2" placeholder="1" required>

                    <label class="form-label">Orientación</label>
                    <select name="rg_orient" class="form-select mb-3">
                        <option value="vertical">Vertical</option>
                        <option value="horizontal">Horizontal</option>
                        <option value="mixed">Mixta</option>
                    </select>

                    <button class="btn btn-success w-100">Crear fila con selección</button>
                </form>
            </div>
        </div>

        <div class="camera-panel">
            <div class="camera-panel-header">Filas reales existentes</div>
            <div class="camera-panel-body">
                <?php if ($rowGroups): ?>
                    <div class="group-list">
                        <?php foreach ($rowGroups as $rg): ?>
                            <div class="group-item">
                                <div class="d-flex justify-content-between gap-2 align-items-start">
                                    <div>
                                        <div class="group-label" onclick="highlightGroup(<?= (int)$rg['id'] ?>)">
                                            <?= e($rg['order_index']) ?> · <?= e($rg['label']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= e($rg['orientation']) ?> · <?= count($groupCells[(int)$rg['id']] ?? []) ?> celdas
                                        </div>
                                    </div>
                                    <form method="post">
                                        <input type="hidden" name="rg_id" value="<?= (int)$rg['id'] ?>">
                                        <button name="action" value="delete_row_group" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta fila real?')">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">No hay filas reales creadas.</div>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <section class="editor-grid-card">
        <div class="editor-grid-toolbar">
            <div>
                <strong>Plano físico</strong>
                <span class="text-muted ms-2" id="selectedCounter">0 celdas seleccionadas</span>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0">Zoom</label>
                    <input type="range" min="36" max="84" step="4" id="zoomRange" class="form-range" style="width:170px;">
                    <span class="small text-muted" id="zoomVal"></span>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="labelsSwitch" checked>
                    <label class="form-check-label" for="labelsSwitch">Etiquetas</label>
                </div>
            </div>
        </div>

        <div class="grid-scroll labels-on" id="gridWrap">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th class="grid-head grid-head-col"></th>
                        <?php for ($c = 1; $c <= $maxC; $c++): ?>
                            <th class="grid-head" onclick="toggleCol(<?= $c ?>)">C<?= $c ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                <?php for ($r = 1; $r <= $maxR; $r++): ?>
                    <tr>
                        <th class="grid-head grid-head-col" onclick="toggleRow(<?= $r ?>)">F<?= $r ?></th>
                        <?php for ($c = 1; $c <= $maxC; $c++): ?>
                            <?php
                                $cell = $cells[$r][$c] ?? null;
                                $type = $cell['type'] ?? 'almacenaje';
                                $class = $type === 'pasillo' ? 'cell-pas' : ($type === 'bloqueada' ? 'cell-blo' : 'cell-alm');
                                $isEntry = ((int)$cam['entry_row'] === $r && (int)$cam['entry_col'] === $c);
                                $entryClass = $isEntry ? ' cell-entry' : '';
                                $levels = (int)($cell['max_levels'] ?? 1);
                                $symbol = $type === 'pasillo' ? '⛶' : ($type === 'bloqueada' ? '✖' : '▦');
                                $rc = $r . '-' . $c;
                                $assignedClass = isset($assignedCells[$rc]) ? ' cell-assigned' : '';
                            ?>
                            <td>
                                <div class="cell-square <?= $class . $entryClass . $assignedClass ?>" data-level="<?= $levels ?>" data-r="<?= $r ?>" data-c="<?= $c ?>" data-rc="<?= $rc ?>" onclick="cellClick(this, event)">
                                    <input type="checkbox" name="cell[]" value="<?= $rc ?>" form="editorForm">
                                    <div class="cell-symbol"><?= $symbol ?></div>
                                    <span class="cell-label">F<?= $r ?>-C<?= $c ?></span>
                                    <?php if ($levels > 1): ?><span class="cell-level">×<?= $levels ?></span><?php endif; ?>
                                </div>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
const zoomRange = document.getElementById('zoomRange');
const zoomVal = document.getElementById('zoomVal');
const selectedCounter = document.getElementById('selectedCounter');

function applyZoom(px){
    document.documentElement.style.setProperty('--cell-size', px + 'px');
    zoomVal.textContent = px + 'px';
}
zoomRange.addEventListener('input', e => applyZoom(e.target.value));
zoomRange.value = 54;
applyZoom(54);

document.getElementById('labelsSwitch').addEventListener('change', () => {
    document.getElementById('gridWrap').classList.toggle('labels-hidden', !document.getElementById('labelsSwitch').checked);
});

function selectedInputs(){ return Array.from(document.querySelectorAll('input[name="cell[]"]')); }
function checkedInputs(){ return selectedInputs().filter(el => el.checked); }
function updateCounter(){ selectedCounter.textContent = checkedInputs().length + ' celdas seleccionadas'; }
function selectAll(){ selectedInputs().forEach(el => el.checked = true); syncSelStyles(); }
function clearSel(){ selectedInputs().forEach(el => el.checked = false); syncSelStyles(); }
function toggleRow(r){ document.querySelectorAll(`.cell-square[data-r="${r}"] input[type="checkbox"]`).forEach(el => el.checked = !el.checked); syncSelStyles(); }
function toggleCol(c){ document.querySelectorAll(`.cell-square[data-c="${c}"] input[type="checkbox"]`).forEach(el => el.checked = !el.checked); syncSelStyles(); }
function syncSelStyles(){ document.querySelectorAll('.cell-square').forEach(el => el.classList.toggle('cell-selected', el.querySelector('input[type="checkbox"]').checked)); updateCounter(); }

function setBrush(t){
    document.getElementById('typeHidden').value = t;
    document.getElementById('actionHidden').value = 'paint';
    document.querySelectorAll('.tool-button').forEach(btn => btn.classList.remove('active'));
    const btn = document.getElementById('brush-' + t);
    if (btn) btn.classList.add('active');
}

function cellClick(el, ev){
    const cb = el.querySelector('input[type="checkbox"]');
    cb.checked = !cb.checked;
    el.classList.toggle('cell-selected', cb.checked);
    updateCounter();
}

const GROUP_CELLS = <?= json_encode($groupCells, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
function highlightGroup(groupId){
    document.querySelectorAll('.cell-square').forEach(el => el.classList.remove('cell-highlight'));
    const list = GROUP_CELLS[String(groupId)] || [];
    list.forEach(rc => {
        const el = document.querySelector(`.cell-square[data-rc="${rc}"]`);
        if (el) el.classList.add('cell-highlight');
    });
}

function getSelectedCells(){ return checkedInputs().map(cb => cb.value); }

function injectSelectedCells(form){
    const vals = getSelectedCells();
    if (vals.length === 0) {
        alert('Selecciona celdas en el plano');
        return false;
    }
    form.querySelector('input[name="cells_csv"]').value = vals.join(',');
    return true;
}

updateCounter();
</script>

<?php
$content = ob_get_clean();
