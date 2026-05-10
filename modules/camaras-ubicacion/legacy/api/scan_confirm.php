<?php
// /public/api/scan_confirm.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/plant_guard.php';

require_login();

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

function out($ok, $data = [], $code = 200): void
{
    http_response_code($code);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
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
        $kRC = $row['r'] . '-' . $row['c'];

        if (isset($cellsSet[$kRC])) {
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

    $raw = file_get_contents('php://input') ?: '{}';
    $js = json_decode($raw, true) ?: [];

    $case = (int)($js['case'] ?? 0);
    $entrada = trim((string)($js['entrada_num'] ?? ''));
    $camera_id = (int)($js['camera_id'] ?? 0);
    $uid = current_user_id();

    if (!$case || $entrada === '' || $camera_id <= 0) {
        out(false, ['error' => 'Parámetros inválidos'], 400);
    }

    if (!$uid) {
        out(false, ['error' => 'No se pudo identificar usuario actual'], 403);
    }

    if (!camera_belongs_to_active_plant($db, $camera_id)) {
        out(false, ['error' => active_plant_guard_error()], 403);
    }

    // CASE 1: Ubicar en UNA fila
    if ($case === 1) {
        $rg = (int)($js['row_group_id'] ?? 0);
        $limit = isset($js['limit']) ? max(0, (int)$js['limit']) : null;

        if ($rg <= 0) {
            out(false, ['error' => 'Falta row_group_id en case=1'], 400);
        }

        if (!row_group_belongs_to_camera($db, $rg, $camera_id)) {
            out(false, ['error' => 'La fila destino no pertenece a la cámara seleccionada'], 403);
        }

        $sqlPend = "
            SELECT p.pallet_num
            FROM erp_palets_mirror p
            LEFT JOIN placements pl
              ON pl.pallet_num = p.pallet_num
             AND pl.removed_at IS NULL
            WHERE p.entrada_num = ?
              AND pl.pallet_num IS NULL
            ORDER BY p.pallet_num ASC
        ";

        $stPend = $db->prepare($sqlPend);
        $stPend->bind_param('s', $entrada);
        $stPend->execute();
        $pend = $stPend->get_result()->fetch_all(MYSQLI_ASSOC);
        $stPend->close();

        if (!$pend) {
            out(false, ['error' => 'No hay palets pendientes para esta entrada'], 409);
        }

        $free = free_slots_of_group($db, $camera_id, $rg);

        if (!$free) {
            out(false, ['error' => 'La fila destino no tiene huecos libres'], 409);
        }

        $row_free = count($free);
        $pending = count($pend);
        $maxInsert = min($row_free, $pending);

        if ($limit !== null) {
            $maxInsert = min($maxInsert, $limit);
        }

        if ($maxInsert <= 0) {
            out(false, ['error' => 'No hay capacidad o no hay palets pendientes'], 409);
        }

        $db->begin_transaction();

        $ins = $db->prepare("
            INSERT INTO placements
                (camera_id, row_idx, col_idx, level_idx, entrada_num, pallet_num, placed_by)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$ins) {
            throw new Exception('Prepare INSERT placements: ' . $db->error);
        }

        for ($i = 0; $i < $maxInsert; $i++) {
            $slot = $free[$i];
            $code = $pend[$i]['pallet_num'];

            $ins->bind_param(
                'iiiissi',
                $camera_id,
                $slot['r'],
                $slot['c'],
                $slot['level'],
                $entrada,
                $code,
                $uid
            );

            if (!$ins->execute()) {
                throw new Exception('Insert placement: ' . $ins->error);
            }
        }

        $ins->close();

        update_pending_summary($db, $entrada);

        $db->commit();

        out(true, [
            'inserted' => $maxInsert,
            'pending_before' => $pending,
            'row_free' => $row_free,
        ]);
    }

    // CASE 2: Ubicar por códigos leídos
    if ($case === 2) {
        $rows = $js['rows'] ?? [];

        if (!is_array($rows) || !count($rows)) {
            out(false, ['error' => 'Falta rows en case=2'], 400);
        }

        foreach ($rows as $item) {
            $rgCheck = (int)($item['row_group_id'] ?? 0);
            if ($rgCheck <= 0 || !row_group_belongs_to_camera($db, $rgCheck, $camera_id)) {
                out(false, ['error' => 'Una fila destino no pertenece a la cámara seleccionada'], 403);
            }
        }

        $db->begin_transaction();

        $ins = $db->prepare("
            INSERT INTO placements
                (camera_id, row_idx, col_idx, level_idx, entrada_num, pallet_num, placed_by)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$ins) {
            throw new Exception('Prepare INSERT placements: ' . $db->error);
        }

        $checkBelongs = $db->prepare("
            SELECT 1
            FROM erp_palets_mirror
            WHERE pallet_num = ?
              AND entrada_num = ?
            LIMIT 1
        ");

        $checkActive = $db->prepare("
            SELECT 1
            FROM placements
            WHERE pallet_num = ?
              AND removed_at IS NULL
            LIMIT 1
        ");

        $inserted = 0;

        foreach ($rows as $item) {
            $rg = (int)($item['row_group_id'] ?? 0);
            $codes = $item['codes'] ?? [];

            if ($rg <= 0) {
                throw new Exception('row_group_id inválido');
            }

            if (!is_array($codes) || !count($codes)) {
                continue;
            }

            $free = free_slots_of_group($db, $camera_id, $rg);

            if (!$free) {
                throw new Exception('Sin hueco en fila destino');
            }

            $cursor = 0;

            foreach ($codes as $code) {
                $code = normalize_pallet_code(trim((string)$code), true);

                if ($code === '') {
                    continue;
                }

                $checkBelongs->bind_param('ss', $code, $entrada);
                $checkBelongs->execute();
                $checkBelongs->store_result();

                if ($checkBelongs->num_rows === 0) {
                    $checkBelongs->free_result();
                    throw new Exception("El palet $code no pertenece a la entrada $entrada");
                }

                $checkBelongs->free_result();

                $checkActive->bind_param('s', $code);
                $checkActive->execute();
                $checkActive->store_result();

                if ($checkActive->num_rows > 0) {
                    $checkActive->free_result();
                    continue;
                }

                $checkActive->free_result();

                if (!isset($free[$cursor])) {
                    throw new Exception('No hay hueco suficiente en fila destino');
                }

                $slot = $free[$cursor++];

                $ins->bind_param(
                    'iiiissi',
                    $camera_id,
                    $slot['r'],
                    $slot['c'],
                    $slot['level'],
                    $entrada,
                    $code,
                    $uid
                );

                if (!$ins->execute()) {
                    throw new Exception('Insert placement: ' . $ins->error);
                }

                $inserted++;
            }
        }

        $ins->close();
        $checkBelongs->close();
        $checkActive->close();

        update_pending_summary($db, $entrada);

        $db->commit();

        out(true, ['inserted' => $inserted]);
    }

    out(false, ['error' => 'Valor de case no soportado'], 400);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        @$db->rollback();
    }

    out(false, ['error' => $e->getMessage()], 500);
}
