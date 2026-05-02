<?php
// /public/api/pallet_status.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_login();

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

if (!function_exists('db')) {
  function db(): mysqli {
    global $mysqli;
    if (!$mysqli instanceof mysqli) throw new RuntimeException('DB no inicializada');
    return $mysqli;
  }
}
function out($ok, $data=[], $code=200){
  http_response_code($code);
  echo json_encode(['ok'=>$ok] + $data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

function norm_str(string $s): string {
  $s = trim($s);
  return preg_replace('/[\x00-\x1F\x7F]+/u','',$s) ?? $s;
}

/**
 * Genera candidatos de búsqueda a partir del código leído.
 */
function build_candidates(string $raw): array {
  $raw = norm_str($raw);
  $c = [];
  if ($raw!=='') $c[] = $raw;
  // strip3 (campo)
  if (preg_match('/^\d{3}\d+$/', $raw)) $c[] = substr($raw,3);
  // strip5 (plegado)
  if (preg_match('/^\d{5}\d+$/', $raw)) $c[] = substr($raw,5);
  // rightmost 8 (plegados)
  $digits = preg_replace('/\D+/', '', $raw) ?? $raw;
  if (strlen($digits) >= 8) $c[] = substr($digits, -8);

  // normalizador global de la app
  if (function_exists('normalize_pallet_code')) {
    try { $n = normalize_pallet_code($raw, true); if ($n!=='') $c[]=$n; } catch (\Throwable $e) {}
  }

  // únicos y no vacíos
  $seen=[]; $out=[];
  foreach ($c as $x){ if ($x!=='' && !isset($seen[$x])) { $seen[$x]=1; $out[]=$x; } }
  return $out;
}

/**
 * Enriquecer datos de colocación con info de fila (row_group):
 * - Busca el position_id de la celda (camera,row_idx,col_idx)
 * - Determina el row_group y su etiqueta
 * - Cuenta palets activos en esa fila
 */
function load_row_info(mysqli $db, int $camera_id, int $row_idx, int $col_idx): ?array {
  // position_id
  $qPos = $db->prepare("SELECT id FROM camera_positions WHERE camera_id=? AND row_idx=? AND col_idx=? LIMIT 1");
  if (!$qPos) return null;
  $qPos->bind_param('iii', $camera_id, $row_idx, $col_idx);
  $qPos->execute();
  $qPos->bind_result($pos_id);
  $hasPos = $qPos->fetch();
  $qPos->close();
  if (!$hasPos) return null;

  // row_group + label
  $qRG = $db->prepare("SELECT crg.id, crg.label
                       FROM camera_row_cells crc
                       JOIN camera_row_groups crg ON crg.id=crc.row_group_id
                       WHERE crc.position_id=? LIMIT 1");
  if (!$qRG) return null;
  $qRG->bind_param('i', $pos_id);
  $qRG->execute();
  $qRG->bind_result($row_group_id, $label);
  $hasRG = $qRG->fetch();
  $qRG->close();
  if (!$hasRG) return null;

  // contar palets activos en esa fila (cámara + row_group)
  $qCnt = $db->prepare("
    SELECT COUNT(*) 
    FROM placements pl
    JOIN camera_positions cp 
         ON cp.camera_id = pl.camera_id 
        AND cp.row_idx   = pl.row_idx 
        AND cp.col_idx   = pl.col_idx
    JOIN camera_row_cells crc 
         ON crc.position_id = cp.id
    WHERE pl.removed_at IS NULL
      AND pl.camera_id  = ?
      AND crc.row_group_id = ?
  ");
  if (!$qCnt) return null;
  $qCnt->bind_param('ii', $camera_id, $row_group_id);
  $qCnt->execute();
  $qCnt->bind_result($count);
  $qCnt->fetch();
  $qCnt->close();

  return [
    'camera_id'    => (int)$camera_id,
    'row_group_id' => (int)$row_group_id,
    'label'        => (string)$label,
    'count'        => (int)$count,
  ];
}

try {
  $code = (string)($_GET['code'] ?? '');
  $code = norm_str($code);
  if ($code==='') out(false, ['error'=>'Falta parámetro code'], 400);

  $db = db(); $db->set_charset('utf8mb4');
  $cands = build_candidates($code);
  if (empty($cands)) out(false, ['error'=>'Código inválido'], 200);

  // placeholders dinámicos
  $ph = implode(',', array_fill(0, count($cands), '?'));
  $types = str_repeat('s', count($cands));

  // 1) ¿Existe en CAMPO?
  $stC = $db->prepare("SELECT pallet_num, entrada_num FROM erp_palets_mirror WHERE pallet_num IN ($ph) LIMIT 1");
  if (!$stC) out(false, ['error'=>'DB prepare campo: '.$db->error], 500);
  $stC->bind_param($types, ...$cands);
  $stC->execute();
  $rsC = $stC->get_result();
  $hitCampo = $rsC->fetch_assoc();
  $stC->close();

  // 2) ¿Existe en PLEGADOS?
  $stP = $db->prepare("SELECT pallet_num, tipo, variedad, calibres1, kg_reales, cajones, fecha, almacen, comentario, numero_volcador
                       FROM erp_plegados_mirror WHERE pallet_num IN ($ph) LIMIT 1");
  if (!$stP) out(false, ['error'=>'DB prepare plegado: '.$db->error], 500);
  $stP->bind_param($types, ...$cands);
  $stP->execute();
  $rsP = $stP->get_result();
  $hitPleg = $rsP->fetch_assoc();
  $stP->close();

  $ambiguous = $hitCampo && $hitPleg;
  if (!$hitCampo && !$hitPleg) out(false, ['error'=>'Palet no encontrado en espejos'], 200);

  // === Estado de placement actual (activo)
  $pallet_num_lookup = $hitCampo ? $hitCampo['pallet_num'] : $hitPleg['pallet_num'];
  $stPl = $db->prepare("SELECT camera_id,row_idx,col_idx,level_idx,placed_at FROM placements WHERE pallet_num=? AND removed_at IS NULL LIMIT 1");
  if (!$stPl) out(false, ['error'=>'DB prepare placement: '.$db->error], 500);
  $stPl->bind_param('s', $pallet_num_lookup);
  $stPl->execute();
  $place = $stPl->get_result()->fetch_assoc();
  $stPl->close();
  $placed = !!$place;

  // Si está colocado, intentar enriquecer con row_info
  $row_info = null;
  if ($placed) {
    $row_info = load_row_info($db, (int)$place['camera_id'], (int)$place['row_idx'], (int)$place['col_idx']);
  }

  if ($hitCampo) {
    // === MODO CAMPO ===
    $pallet_num = $hitCampo['pallet_num'];
    $entrada    = $hitCampo['entrada_num'];

    // Variedad mixta/única desde espejo de palets
    $variedad = null;
    if ($q = $db->prepare("SELECT COUNT(DISTINCT COALESCE(variedad,'')) c, MIN(variedad) v1 FROM erp_palets_mirror WHERE entrada_num=?")) {
      $q->bind_param('s',$entrada); $q->execute();
      $r = $q->get_result()->fetch_assoc(); $q->close();
      if ($r) $variedad = ((int)$r['c']>1) ? 'Mixta' : ($r['v1'] ?: null);
    }

    // Datos de la entrada si existen en el espejo de entradas
    $ent = null;
    if ($q = $db->prepare("SELECT propietario, matricula, fecha_boleto, almacen_nombre FROM erp_entradas_mirror WHERE entrada_num=? LIMIT 1")) {
      $q->bind_param('s',$entrada); $q->execute(); $ent = $q->get_result()->fetch_assoc(); $q->close();
    }

    // Listado de palets de la misma entrada (opcional)
    $rows = [];
    if ($q = $db->prepare("SELECT p.pallet_num,
                                  CASE WHEN pl.pallet_num IS NULL THEN 0 ELSE 1 END AS has_place
                           FROM erp_palets_mirror p
                           LEFT JOIN placements pl ON pl.pallet_num=p.pallet_num AND pl.removed_at IS NULL
                           WHERE p.entrada_num=? ORDER BY p.pallet_num")) {
      $q->bind_param('s',$entrada); $q->execute();
      $res = $q->get_result(); while($x=$res->fetch_assoc()) $rows[]=$x; $q->close();
    }

    out(true, [
      'mode'        => 'campo',
      'source'      => 'campo',
      'ambiguous'   => $ambiguous,
      'pallet_num'  => $pallet_num,
      'entrada_num' => $entrada,
      'placed'      => $placed,
      'movible'     => true,
      'place'       => $place ?: null,
      'row_info'    => $row_info,     // <<--- NUEVO
      'variedad'    => $variedad,
      'entrada'     => [
        'propietario' => $ent['propietario'] ?? null,
        'matricula'   => $ent['matricula'] ?? null,
        'fecha'       => $ent['fecha_boleto'] ?? null,
        'almacen'     => $ent['almacen_nombre'] ?? null,
      ],
      'siblings'    => $rows,
    ]);
  }

  // === MODO PLEGADO ===
  $movible = is_null($hitPleg['numero_volcador']) || $hitPleg['numero_volcador']==='';
  out(true, [
    'mode'        => 'plegado',
    'source'      => 'plegado',
    'ambiguous'   => $ambiguous,
    'pallet_num'  => $hitPleg['pallet_num'],
    'placed'      => $placed,
    'movible'     => $movible,
    'place'       => $place ?: null,
    'row_info'    => $row_info,   // <<--- NUEVO
    'plegado'     => [
      'tipo'       => $hitPleg['tipo']       ?? null,
      'variedad'   => $hitPleg['variedad']   ?? null,
      'calibres1'  => $hitPleg['calibres1']  ?? null,
      'kg_reales'  => $hitPleg['kg_reales']  ?? null,
      'cajones'    => $hitPleg['cajones']    ?? null,
      'fecha'      => $hitPleg['fecha']      ?? null,
      'almacen'    => $hitPleg['almacen']    ?? null,
      'comentario' => $hitPleg['comentario'] ?? null,
    ],
  ]);

} catch (Throwable $e) {
  out(false, ['error'=>$e->getMessage()], 500);
}
