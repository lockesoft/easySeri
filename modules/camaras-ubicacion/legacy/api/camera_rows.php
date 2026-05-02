<?php
// /public/api/camera_rows.php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

function out($ok, $data=[], $code=200){
  http_response_code($code);
  echo json_encode(['ok'=>$ok] + $data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

function free_slots_of_group(mysqli $db, int $camera_id, int $row_group_id): int {
  // 1) Celdas del grupo
  $st = $db->prepare("
    SELECT cp.row_idx r, cp.col_idx c, cp.max_levels, cp.type
    FROM camera_row_cells crc
    JOIN camera_positions cp ON cp.id = crc.position_id
    WHERE crc.row_group_id = ?
  ");
  $st->bind_param('i', $row_group_id);
  $st->execute();
  $cells = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();

  if (!$cells) return 0;

  // 2) Set de celdas y capacidad total
  $cellsSet = [];
  $capacity = 0;
  foreach ($cells as $cell){
    $k = $cell['r'].'-'.$cell['c'];
    $cellsSet[$k] = true;
    if ($cell['type']==='almacenaje'){
      $capacity += (int)$cell['max_levels'];
    }
  }
  if ($capacity===0) return 0;

  // 3) Ocupación activa
  $st2 = $db->prepare("SELECT row_idx r, col_idx c, level_idx level FROM placements WHERE removed_at IS NULL AND camera_id=?");
  $st2->bind_param('i', $camera_id);
  $st2->execute();
  $res2 = $st2->get_result();
  $occupied = 0;
  while ($row = $res2->fetch_assoc()){
    if (isset($cellsSet[$row['r'].'-'.$row['c']])){
      $occupied++;
    }
  }
  $st2->close();

  $free = $capacity - $occupied;
  return max(0, $free);
}

try{
  $camera_id = (int)($_GET['camera_id'] ?? 0);
  if ($camera_id<=0) out(false, ['error'=>'camera_id inválido'], 400);

  // Filas reales
  $db = camaras_db();

$st = $db->prepare("SELECT id, label FROM camera_row_groups WHERE camera_id=? ORDER BY order_index ASC, id ASC");
  $st->bind_param('i', $camera_id);
  $st->execute();
  $groups = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();

  $out = [];
  foreach ($groups as $g){
    $free = free_slots_of_group($db, $camera_id, (int)$g['id']);
    $out[] = [
      'row_group_id' => (int)$g['id'],
      'label'        => $g['label'],
      'free'         => $free
    ];
  }

  out(true, ['rows'=>$out]);

}catch(Throwable $e){
  out(false, ['error'=>$e->getMessage()], 500);
}
