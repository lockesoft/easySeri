<?php
// /public/api/entry_counts.php
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

try{
  $entrada = trim((string)($_GET['entrada_num'] ?? ''));
  if ($entrada==='') out(false, ['error'=>'Falta entrada_num'], 400);

  // total en espejo ERP
  $db = camaras_db();

  $st = $db->prepare("SELECT COUNT(*) FROM erp_palets_mirror WHERE entrada_num=?");
  $st->bind_param('s', $entrada);
  $st->execute(); $st->bind_result($total); $st->fetch(); $st->close();
  $total = (int)$total;

  if ($total===0) out(false, ['error'=>'Entrada no encontrada o sin palets en espejo ERP'], 404);

  // ubicados activos
  $st2 = $db->prepare("SELECT COUNT(*) FROM placements WHERE entrada_num=? AND removed_at IS NULL");
  $st2->bind_param('s', $entrada);
  $st2->execute(); $st2->bind_result($placed); $st2->fetch(); $st2->close();
  $placed = (int)$placed;

  $pending = max(0, $total - $placed);

  out(true, ['total'=>$total, 'placed'=>$placed, 'pending'=>$pending]);
}catch(Throwable $e){
  out(false, ['error'=>$e->getMessage()], 500);
}
