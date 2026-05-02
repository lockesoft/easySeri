<?php
// /public/api/cameras.php
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
 $rows = camaras_db()
    ->query("SELECT id, name, priority FROM cameras ORDER BY priority DESC, id ASC")
    ->fetch_all(MYSQLI_ASSOC);
  out(true, ['cameras'=>$rows]);
}catch(Throwable $e){
  out(false, ['error'=>$e->getMessage()], 500);
}
