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

while ($row = $res->fetch_assoc()) {
    $r = (int)$row['row_idx'];
    $c = (int)$row['col_idx'];
    $cells[$r][$c] = $row;
    $maxR = max($maxR, $r);
    $maxC = max($maxC, $c);
}

$rowGroups = [];
$groupCells = [];

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
            $groupCells[(int)$r['row_group_id']][] = $r['row_idx'] . '-' . $r['col_idx'];
        }
    }
} catch (Throwable $e) {
    $rowGroups = [];
    $groupCells = [];
}

ob_start();
?>

<style>
:root { --cell-size: 52px; }
.grid-table td { width: var(--cell-size); height: var(--cell-size); padding: 0 !important; }
.cell-square { width: 100%; height: 100%; display:inline-flex; align-items:center; justify-content:center; position:relative; cursor:pointer; user-select:none; }
.cell-alm { background-color:#e7f7ee; }
.cell-pas { background-color:#e9ecef; }
.cell-blo { background-color:#f8d7da; }
.cell-symbol { font-size:1.2rem; line-height:1; opacity:.75; }
.cell-label { position:absolute; bottom:2px; right:4px; font-size:.65rem; background:rgba(255,255,255,.6); padding:0 .25rem; border-radius:.2rem; }
.labels-hidden .cell-label { display:none; }
.cell-level { position:absolute; bottom:2px; left:4px; font-size:.65rem; padding:0 .25rem; border-radius:.25rem; background:rgba(0,0,0,.06); }
.cell-entry { outline:3px solid #0d6efd; outline-offset:-3px; }
.cell-entry::after { content:"⮕"; position:absolute; top:2px; right:4px; font-size:.95rem; color:#0d6efd; }
.cell-selected { box-shadow:0 0 0 3px #ffc107 inset; }
.cell-highlight { outline:3px solid #ffc107; outline-offset:-3px; }
.grid-head { text-align:center; font-weight:600; background:#f8f9fa; cursor:pointer; position:sticky; top:0; z-index:2; }
.grid-head-col { position:sticky; left:0; z-index:1; background:#f8f9fa; }
.zoom-display { min-width:3ch; display:inline-block; text-align:right; }
.cell-square input[type="checkbox"] { position:absolute; inset:0; opacity:0; pointer-events:none; }
.entry-badge { display:inline-block; background:#0d6efd; color:#fff; border-radius:.35rem; padding:.25rem .5rem; font-size:.85rem; }
.grid-legend { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
</style>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h1 class="h4 mb-1">
            Plano: <?= e($cam['name']) ?>
            <?= $cam['code'] ? '(' . e($cam['code']) . ')' : '' ?>
            <span class="badge text-bg-info">Planta <?= e($cam['plant_code'] ?? '—') ?></span>
        </h1>
        <?php if (!empty($cam['entry_row']) && !empty($cam['entry_col'])): ?>
            <span class="entry-badge">Entrada → F<?= (int)$cam['entry_row'] ?>-C<?= (int)$cam['entry_col'] ?></span>
        <?php else: ?>
            <span class="text-muted">Punto de entrada no definido</span>
        <?php endif; ?>
    </div>
    <a class="btn btn-outline-secondary" href="/easyseri/camaras-ubicacion/camaras">Volver</a>
</div>

<?php if ($msg = flash('ok')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-xl-7">
                <div class="grid-legend">
                    <span class="badge text-bg-light">Leyenda:</span>
                    <span class="badge" style="background:#e7f7ee; border:1px solid #bfe6cf;">Almacenaje</span>
                    <span class="badge" style="background:#e9ecef; border:1px solid #cfd4da;">Pasillo</span>
                    <span class="badge" style="background:#f8d7da; border:1px solid #f1aeb5;">Bloqueada</span>
                    <span class="badge text-bg-primary">Punto entrada</span>
                    <span class="badge text-bg-warning">Resaltado</span>
                </div>
            </div>
            <div class="col-xl-5 text-xl-end d-flex flex-wrap gap-3 justify-content-end">
                <div>
                    <label class="form-label mb-0">Zoom</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" min="36" max="80" step="4" id="zoomRange" class="form-range" style="width:220px;">
                        <span class="zoom-display" id="zoomVal"></span>
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="labelsSwitch" checked>
                    <label class="form-check-label" for="labelsSwitch">Etiquetas F/C</label>
                </div>
            </div>
        </div>

        <hr>

        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div class="me-auto">
                <label class="form-label mb-1">Filas reales / grupos</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($rowGroups): ?>
                        <?php foreach ($rowGroups as $rg): ?>
                            <form method="post" class="d-inline-flex align-items-center gap-2">
                                <input type="hidden" name="rg_id" value="<?= (int)$rg['id'] ?>">
                                <span class="badge text-bg-secondary" onclick="highlightGroup(<?= (int)$rg['id'] ?>)" style="cursor:pointer">
                                    <?= e($rg['order_index']) ?> · <?= e($rg['label']) ?> (<?= e(strtoupper($rg['orientation'][0])) ?>)
                                </span>
                                <button name="action" value="delete_row_group" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar grupo?')">Eliminar</button>
                            </form>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">No hay grupos creados</span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="post" class="d-flex flex-wrap gap-2 align-items-end" onsubmit="return injectSelectedCells(this);">
                <input type="hidden" name="action" value="create_row_group">
                <input type="hidden" name="cells_csv" value="">
                <div>
                    <label class="form-label mb-1">Etiqueta</label>
                    <input name="rg_label" class="form-control form-control-sm" placeholder="Fila 1" required>
                </div>
                <div>
                    <label class="form-label mb-1">Orden</label>
                    <input name="rg_order" type="number" min="1" class="form-control form-control-sm" placeholder="1" required>
                </div>
                <div>
                    <label class="form-label mb-1">Orientación</label>
                    <select name="rg_orient" class="form-select form-select-sm">
                        <option value="vertical">Vertical</option>
                        <option value="horizontal">Horizontal</option>
                        <option value="mixed">Mixta</option>
                    </select>
                </div>
                <button class="btn btn-sm btn-primary">➕ Crear fila real</button>
            </form>
        </div>
    </div>
</div>

<form method="post" class="card shadow-sm" id="editorForm">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-success" onclick="setBrush('almacenaje')">Pincel: Almacenaje</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setBrush('pasillo')">Pincel: Pasillo</button>
                    <button type="button" class="btn btn-outline-danger" onclick="setBrush('bloqueada')">Pincel: Bloqueada</button>
                </div>
                <input type="hidden" name="type" id="typeHidden" value="almacenaje">
                <input type="hidden" name="action" id="actionHidden" value="paint">

                <button type="submit" class="btn btn-primary">Aplicar pincel</button>

                <div class="input-group" style="max-width: 280px;">
                    <span class="input-group-text">Niveles</span>
                    <input type="number" name="levels" class="form-control" value="1" min="1">
                    <button name="action" value="set_levels" type="submit" class="btn btn-outline-primary" onclick="document.getElementById('actionHidden').value='set_levels'">Aplicar</button>
                </div>

                <button name="action" value="set_entry" type="submit" class="btn btn-outline-primary" onclick="document.getElementById('actionHidden').value='set_entry'">Marcar punto entrada</button>
                <button type="button" class="btn btn-outline-dark" onclick="selectAll()">Seleccionar todo</button>
                <button type="button" class="btn btn-outline-dark" onclick="clearSel()">Quitar selección</button>
            </div>
        </div>

        <div class="table-responsive labels-on" id="gridWrap">
            <table class="table table-bordered align-middle mb-0 grid-table">
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
                        <th class="grid-head-col" onclick="toggleRow(<?= $r ?>)">F<?= $r ?></th>
                        <?php for ($c = 1; $c <= $maxC; $c++): ?>
                            <?php
                                $cell = $cells[$r][$c] ?? null;
                                $type = $cell['type'] ?? 'almacenaje';
                                $class = $type === 'pasillo' ? 'cell-pas' : ($type === 'bloqueada' ? 'cell-blo' : 'cell-alm');
                                $isEntry = ((int)$cam['entry_row'] === $r && (int)$cam['entry_col'] === $c);
                                $entryClass = $isEntry ? ' cell-entry' : '';
                                $levels = (int)($cell['max_levels'] ?? 1);
                                $symbol = $type === 'pasillo' ? '⛶' : ($type === 'bloqueada' ? '✖' : '▦');
                            ?>
                            <td>
                                <div class="cell-square <?= $class . $entryClass ?>" data-level="<?= $levels ?>" data-r="<?= $r ?>" data-c="<?= $c ?>" data-rc="<?= $r . '-' . $c ?>" onclick="cellClick(this, event)">
                                    <input type="checkbox" name="cell[]" value="<?= $r . '-' . $c ?>">
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
    </div>
</form>

<script>
const zoomRange = document.getElementById('zoomRange');
const zoomVal = document.getElementById('zoomVal');
function applyZoom(px){ document.documentElement.style.setProperty('--cell-size', px + 'px'); zoomVal.textContent = px + 'px'; }
zoomRange.addEventListener('input', e => applyZoom(e.target.value));
applyZoom(52);

document.getElementById('labelsSwitch').addEventListener('change', () => {
    document.getElementById('gridWrap').classList.toggle('labels-hidden', !document.getElementById('labelsSwitch').checked);
});

function selectAll(){ document.querySelectorAll('input[name="cell[]"]').forEach(el => el.checked = true); syncSelStyles(); }
function clearSel(){ document.querySelectorAll('input[name="cell[]"]').forEach(el => el.checked = false); syncSelStyles(); }
function toggleRow(r){ document.querySelectorAll(`.cell-square[data-r="${r}"] input[type="checkbox"]`).forEach(el => el.checked = !el.checked); syncSelStyles(); }
function toggleCol(c){ document.querySelectorAll(`.cell-square[data-c="${c}"] input[type="checkbox"]`).forEach(el => el.checked = !el.checked); syncSelStyles(); }
function syncSelStyles(){ document.querySelectorAll('.cell-square').forEach(el => el.classList.toggle('cell-selected', el.querySelector('input[type="checkbox"]').checked)); }

let brush = 'almacenaje';
function setBrush(t){ brush = t; document.getElementById('typeHidden').value = t; document.getElementById('actionHidden').value = 'paint'; }

function cellClick(el, ev){
    const cb = el.querySelector('input[type="checkbox"]');
    cb.checked = !cb.checked;
    el.classList.toggle('cell-selected', cb.checked);
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

function getSelectedCells(){
    const vals = [];
    document.querySelectorAll('input[name="cell[]"]:checked').forEach(cb => vals.push(cb.value));
    return vals;
}

function injectSelectedCells(form){
    const vals = getSelectedCells();
    if (vals.length === 0) {
        alert('Selecciona celdas en el plano');
        return false;
    }
    form.querySelector('input[name="cells_csv"]').value = vals.join(',');
    return true;
}
</script>

<?php
$content = ob_get_clean();
