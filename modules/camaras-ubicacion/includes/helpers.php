<?php
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function flash($key, $value = null) {
    if (!isset($_SESSION['_flash'])) $_SESSION['_flash'] = [];
    if ($value === null) {
        if (!isset($_SESSION['_flash'][$key])) return null;
        $val = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
    $_SESSION['_flash'][$key] = $value;
    return null;
}

// NUEVO: helper para componer URLs dentro de BASE_URL
function url(string $path = '/'): string {
    $path = '/' . ltrim($path, '/');
    return rtrim(BASE_URL, '/') . $path;
}

// Arreglado: redirección relativa a BASE_URL
function redirect(string $path = '/'): void {
    header('Location: ' . url($path));
    exit;
}
if (!function_exists('normalize_pallet_code')) {
  function normalize_pallet_code(string $raw, bool $strip3=true): string {
    $s = trim($raw);
    if ($strip3 && strlen($s) > 3 && preg_match('/^\d{3}/', $s)) {
      $s = substr($s, 3);
    }
    return $s;
  }
}

