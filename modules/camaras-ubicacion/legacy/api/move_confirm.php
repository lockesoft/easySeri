<?php
// /public/api/move_confirm.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_login();

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

function out($ok, $data = [], $code = 200): void
{
    http_response_code($code);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
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

function free_slots_of_group(mysqli $db, int $camera_id, int $row_group_id): array
{
    $st = $db->prepare("
        SELECT cp.row_idx r, cp.col_idx c, cp.max_levels, cp.type
        FROM camera_row_cells crc
        JOIN camera_positions cp ON cp.id = crc.position_id
        WHERE crc.row_group_id = ?
        ORDER BY cp.row_idx, cp.col_idx
    ");

    if (!$st) {
        throw new Exception('Prepare cells: ' . $db->error);
    }

    $st->bind_param('i', $row_group_id);
    $st->execute();
    $cells = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $slots = [];
    $cellsSet = [];

    foreach ($cells as $cell) {
        $cellsSet[$cell['r'] . '-' . $cell['c']] = true;

        if ($cell['type'] === 'almacenaje') {
            for ($lv = 1; $lv <= (int)$cell['max_levels']; $lv++) {
                $slots[] = [
                    'r' => (int)$cell['r'],
                    'c' => (int)$cell['c'],
                    'level' => $lv,
                ];
            }
        }
    }

    if (!$slots) {
        return [];
    }

    $st2 = $db->prepare("
        SELECT row_idx r, col_idx c, level_idx level
        FROM placements
        WHERE removed_at IS NULL
          AND camera_id = ?
    ");

    $st2->bind_param('i', $camera_id);
    $st2->execute();
    $res2 = $st2->get_result();

    $occ = [];

    while ($row = $res2->fetch_assoc()) {
        if (isset($cellsSet[$row['r'] . '-' . $row['c']])) {
            $occ[$row['r'] . '-' . $row['c'] . '-' . $row['level']] = true;
        }
    }

    $st2->close();

    $free = [];

    foreach ($slots as $s) {
        $k = $s['r'] . '-' . $s['c'] . '-' . $s['level'];

        if (empty($occ[$k])) {
            $free[] = $s;
        }
    }

    usort($free, function ($a, $b) {
        return ($a['r'] <=> $b['r'])
            ?: (($a['c'] <=> $b['c'])
            ?: ($a['level'] <=> $b['level']));
    });

    return $free;
}

function update_pending_summary(mysqli $db, string $entrada): void
{
    $qEnt = $db->prepare("
        SELECT fecha_boleto, almacen_nombre, propietario
        FROM erp_entradas_mirror
        WHERE entrada_num = ?
        LIMIT 1
    ");
    $qEnt->bind_param('s', $entrada);
    $qEnt->execute();
    $qEnt->bind_result($fecha, $almacen, $propietario);
    $qEnt->fetch();
    $qEnt->close();

    $qTot = $db->prepare("
        SELECT COUNT(*)
        FROM erp_palets_mirror
        WHERE entrada_num = ?
    ");
    $qTot->bind_param('s', $entrada);
    $qTot->execute();
    $qTot->bind_result($total);
    $qTot->fetch();
    $qTot->close();

    $qPl = $db->prepare("
        SELECT COUNT(*)
        FROM placements
        WHERE entrada_num = ?
          AND removed_at IS NULL
    ");
    $qPl->bind_param('s', $entrada);
    $qPl->execute();
    $qPl->bind_result($placed);
    $qPl->fetch();
    $qPl->close();

    $qVar = $db->prepare("
        SELECT COUNT(DISTINCT COALESCE(variedad, '')) c, MIN(variedad) v1
        FROM erp_palets_mirror
        WHERE entrada_num = ?
    ");
    $qVar->bind_param('s', $entrada);
    $qVar->execute();
    $res = $qVar->get_result()->fetch_assoc();
    $qVar->close();

    $variedad = null;

    if ($res) {
        $variedad = ((int)$res['c'] > 1) ? 'Mixta' : ($res['v1'] ?? null);
    }

    $total = (int)$total;
    $placed = (int)$placed;
    $remaining = max(0, $total - $placed);
    $status = ($remaining > 0) ? 'pending' : 'complete';

    $up = $db->prepare("
        INSERT INTO erp_entries_pending
            (entrada_num, fecha_boleto, almacen_nombre, propietario, variedad,
             total_palets_erp, placed_count, remaining_count, status,
             first_seen_at, last_seen_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            fecha_boleto = VALUES(fecha_boleto),
            almacen_nombre = VALUES(almacen_nombre),
            propietario = VALUES(propietario),
            variedad = VALUES(variedad),
            total_palets_erp = VALUES(total_palets_erp),
            placed_count = VALUES(placed_count),
            remaining_count = VALUES(remaining_count),
            status = VALUES(status),
            last_seen_at = NOW()
    ");

    $up->bind_param(
        'sssssiiss',
        $entrada,
        $fecha,
        $almacen,
        $propietario,
        $variedad,
        $total,
        $placed,
        $remaining,
        $status
    );

    $up->execute();
    $up->close();
}

try {
    $db = camaras_db();

    $in = json_input();

    $entrada = isset($in['entrada_num']) ? trim((string)$in['entrada_num']) : '';
    $scope = isset($in['scope']) ? (string)$in['scope'] : 'entry';
    $selected = (isset($in['selected_pallets']) && is_array($in['selected_pallets']))
        ? array_values($in['selected_pallets'])
        : [];

    $camera_id_dest = (int)($in['camera_id'] ?? 0);
    $row_group_dest = (int)($in['row_group_id'] ?? 0);
    $limit = isset($in['max_move']) ? max(0, (int)$in['max_move']) : null;

    $camera_id_src = (int)($in['src_camera_id'] ?? 0);
    $row_group_src = (int)($in['src_row_group_id'] ?? 0);

    $uid = current_user_id();

    if (!$uid) {
        out(false, ['error' => 'No se pudo identificar usuario actual'], 403);
    }

    if ($camera_id_dest <= 0 || $row_group_dest <= 0) {
        out(false, ['error' => 'camera_id y row_group_id son requeridos'], 400);
    }

    if ($scope === 'selected' && empty($selected)) {
        out(false, ['error' => 'Debes seleccionar palets'], 400);
    }

    if ($scope === 'row' && ($camera_id_src <= 0 || $row_group_src <= 0)) {
        out(false, ['error' => 'src_camera_id y src_row_group_id son requeridos para mover fila'], 400);
    }

    $candidates = [];

    if ($scope === 'entry') {
        if ($entrada === '') {
            out(false, ['error' => 'entrada_num requerido en scope=entry'], 400);
        }

        $sql = "
            SELECT p.pallet_num
            FROM erp_palets_mirror p
            JOIN placements pl
              ON pl.pallet_num = p.pallet_num
             AND pl.removed_at IS NULL
            WHERE p.entrada_num = ?
            ORDER BY p.pallet_num ASC
        ";

        $st = $db->prepare($sql);

        if (!$st) {
            out(false, ['error' => 'Prepare candidatos(entry): ' . $db->error], 500);
        }

        $st->bind_param('s', $entrada);
        $st->execute();
        $res = $st->get_result();

        while ($row = $res->fetch_assoc()) {
            $candidates[] = $row['pallet_num'];
        }

        $st->close();

    } elseif ($scope === 'selected') {
        $ph = implode(',', array_fill(0, count($selected), '?'));
        $types = str_repeat('s', count($selected));

        $sql = "
            SELECT pl.pallet_num
            FROM placements pl
            WHERE pl.removed_at IS NULL
              AND pl.pallet_num IN ($ph)
            ORDER BY pl.pallet_num
        ";

        $st = $db->prepare($sql);

        if (!$st) {
            out(false, ['error' => 'Prepare candidatos(selected): ' . $db->error], 500);
        }

        $st->bind_param($types, ...$selected);
        $st->execute();
        $res = $st->get_result();

        while ($row = $res->fetch_assoc()) {
            $candidates[] = $row['pallet_num'];
        }

        $st->close();

    } else {
        $stPos = $db->prepare("
            SELECT cp.row_idx, cp.col_idx
            FROM camera_row_cells crc
            JOIN camera_positions cp ON cp.id = crc.position_id
            WHERE crc.row_group_id = ?
              AND cp.camera_id = ?
        ");

        if (!$stPos) {
            out(false, ['error' => 'Prepare row positions: ' . $db->error], 500);
        }

        $stPos->bind_param('ii', $row_group_src, $camera_id_src);
        $stPos->execute();
        $pos = $stPos->get_result()->fetch_all(MYSQLI_ASSOC);
        $stPos->close();

        if (!$pos) {
            out(false, ['error' => 'La fila origen no tiene posiciones'], 409);
        }

        $posSet = [];

        foreach ($pos as $p) {
            $posSet[$p['row_idx'] . '-' . $p['col_idx']] = true;
        }

        $stPl = $db->prepare("
            SELECT pallet_num, row_idx, col_idx
            FROM placements
            WHERE removed_at IS NULL
              AND camera_id = ?
        ");

        $stPl->bind_param('i', $camera_id_src);
        $stPl->execute();
        $r = $stPl->get_result();

        while ($x = $r->fetch_assoc()) {
            if (isset($posSet[$x['row_idx'] . '-' . $x['col_idx']])) {
                if (!empty($x['pallet_num'])) {
                    $candidates[] = $x['pallet_num'];
                }
            }
        }

        $stPl->close();

        if (empty($candidates)) {
            out(false, ['error' => 'La fila origen no tiene palets ubicados'], 409);
        }
    }

    if (empty($candidates)) {
        out(false, ['error' => 'No hay palets con ubicación activa para mover'], 409);
    }

    $ph = implode(',', array_fill(0, count($candidates), '?'));
    $types = str_repeat('s', count($candidates));

    $stP = $db->prepare("
        SELECT pallet_num
        FROM erp_plegados_mirror
        WHERE pallet_num IN ($ph)
          AND (numero_volcador IS NOT NULL AND numero_volcador <> '')
    ");

    if ($stP) {
        $stP->bind_param($types, ...$candidates);
        $stP->execute();
        $blk = [];
        $r = $stP->get_result();

        while ($x = $r->fetch_assoc()) {
            $blk[$x['pallet_num']] = 1;
        }

        $stP->close();

        if ($blk) {
            $candidates = array_values(array_filter($candidates, function ($pn) use ($blk) {
                return empty($blk[$pn]);
            }));
        }
    }

    $requested = count($candidates);

    if ($requested === 0) {
        out(false, ['error' => 'Todos los candidatos están bloqueados (volcados)'], 409);
    }

    $free = free_slots_of_group($db, $camera_id_dest, $row_group_dest);
    $row_free = count($free);

    if ($row_free <= 0) {
        out(false, ['error' => 'La fila destino no tiene huecos libres'], 409);
    }

    $maxInsert = min($requested, $row_free);

    if ($limit !== null) {
        $maxInsert = min($maxInsert, $limit);
    }

    if ($maxInsert <= 0) {
        out(false, ['error' => 'Sin capacidad para mover'], 409);
    }

    $moving = array_slice($candidates, 0, $maxInsert);

    $typesMap = [];
    $entryByPN = [];

    if ($moving) {
        $phm = implode(',', array_fill(0, count($moving), '?'));
        $tm = str_repeat('s', count($moving));

        if ($qPle = $db->prepare("SELECT pallet_num FROM erp_plegados_mirror WHERE pallet_num IN ($phm)")) {
            $qPle->bind_param($tm, ...$moving);
            $qPle->execute();
            $r = $qPle->get_result();

            while ($x = $r->fetch_assoc()) {
                $typesMap[$x['pallet_num']] = 'plegado';
            }

            $qPle->close();
        }

        if ($qEnt = $db->prepare("SELECT pallet_num, entrada_num FROM erp_palets_mirror WHERE pallet_num IN ($phm)")) {
            $qEnt->bind_param($tm, ...$moving);
            $qEnt->execute();
            $r = $qEnt->get_result();

            while ($x = $r->fetch_assoc()) {
                $entryByPN[$x['pallet_num']] = (string)$x['entrada_num'];

                if (!isset($typesMap[$x['pallet_num']])) {
                    $typesMap[$x['pallet_num']] = 'entrada';
                }
            }

            $qEnt->close();
        }
    }

    $db->begin_transaction();

    $ph = implode(',', array_fill(0, count($moving), '?'));
    $types = str_repeat('s', count($moving));

    $sqlClose = "
        UPDATE placements
        SET removed_at = NOW(),
            removed_source = 'manual'
        WHERE removed_at IS NULL
          AND pallet_num IN ($ph)
    ";

    $stC = $db->prepare($sqlClose);

    if (!$stC) {
        $db->rollback();
        out(false, ['error' => 'Prepare close: ' . $db->error], 500);
    }

    $stC->bind_param($types, ...$moving);

    if (!$stC->execute()) {
        $db->rollback();
        out(false, ['error' => 'Exec close: ' . $stC->error], 500);
    }

    $stC->close();

    $hasPlacedAt = table_has_column($db, 'placements', 'placed_at');
    $hasUpdated = table_has_column($db, 'placements', 'updated_at');
    $hasSource = table_has_column($db, 'placements', 'source_type');

    $cols = [
        '`camera_id`',
        '`row_idx`',
        '`col_idx`',
        '`level_idx`',
        '`entrada_num`',
        '`pallet_num`',
        '`placed_by`',
    ];

    $vals = ['?', '?', '?', '?', '?', '?', '?'];

    $bind = 'iiiissi';

    if ($hasSource) {
        $cols[] = '`source_type`';
        $vals[] = '?';
        $bind .= 's';
    }

    if ($hasPlacedAt) {
        $cols[] = '`placed_at`';
        $vals[] = 'NOW()';
    }

    if ($hasUpdated) {
        $cols[] = '`updated_at`';
        $vals[] = 'NOW()';
    }

    $sqlIns = "INSERT INTO placements (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
    $ins = $db->prepare($sqlIns);

    if (!$ins) {
        $dbg = $db->error ?: 'unknown';
        $db->rollback();
        out(false, ['error' => "Prepare insert failed: $dbg", 'sql' => $sqlIns], 500);
    }

    $moved = 0;

    for ($i = 0; $i < $maxInsert; $i++) {
        $slot = $free[$i];
        $pn = $moving[$i];
        $stype = $typesMap[$pn] ?? 'entrada';

        if ($scope === 'entry') {
            $entref = ($stype === 'entrada') ? $entrada : '';
        } else {
            $entref = ($stype === 'entrada') ? ((string)($entryByPN[$pn] ?? '')) : '';
        }

        if ($hasSource) {
            if (!$ins->bind_param(
                $bind,
                $camera_id_dest,
                $slot['r'],
                $slot['c'],
                $slot['level'],
                $entref,
                $pn,
                $uid,
                $stype
            )) {
                $db->rollback();
                out(false, ['error' => 'bind_param insert failed: ' . $ins->error], 500);
            }
        } else {
            if (!$ins->bind_param(
                $bind,
                $camera_id_dest,
                $slot['r'],
                $slot['c'],
                $slot['level'],
                $entref,
                $pn,
                $uid
            )) {
                $db->rollback();
                out(false, ['error' => 'bind_param insert failed: ' . $ins->error], 500);
            }
        }

        if (!$ins->execute()) {
            $db->rollback();
            out(false, ['error' => 'Exec insert failed for ' . $pn . ': ' . $ins->error], 500);
        }

        $moved++;
    }

    $ins->close();

    // moves_log sí acepta move_row, move_entry y move_pallet.
    // Se deja logging solo para movimientos, no para scan_case.
    $logType = ($scope === 'row') ? 'move_row' : (($scope === 'selected') ? 'move_pallet' : 'move_entry');

    if ($log = $db->prepare("
        INSERT INTO moves_log
            (moved_at, moved_by, type, src_camera_id, src_row_group_id,
             dest_camera_id, dest_row_group_id, entrada_num, pallets_count)
        VALUES
            (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)
    ")) {
        $src_cam = ($scope === 'row') ? $camera_id_src : null;
        $src_rg = ($scope === 'row') ? $row_group_src : null;
        $entLog = ($scope === 'entry') ? $entrada : null;

        $log->bind_param(
            'isiiiisi',
            $uid,
            $logType,
            $src_cam,
            $src_rg,
            $camera_id_dest,
            $row_group_dest,
            $entLog,
            $moved
        );

        $log->execute();
        $log->close();
    }

    if (!empty($entryByPN)) {
        $uniq = [];

        foreach ($moving as $pn) {
            $en = $entryByPN[$pn] ?? '';

            if ($en !== '') {
                $uniq[(string)$en] = 1;
            }
        }

        foreach (array_keys($uniq) as $en) {
            update_pending_summary($db, (string)$en);
        }
    }

    $db->commit();

    out(true, [
        'moved' => $moved,
        'requested' => $requested,
        'row_free' => count($free),
        'skipped' => max(0, $requested - $moved),
        'scope' => $scope,
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        @$db->rollback();
    }

    out(false, ['error' => $e->getMessage()], 500);
}