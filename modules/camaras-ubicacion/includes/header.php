<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS desde CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('/assets/css/custom.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= e(url('/dashboard.php')) ?>"><?= e(APP_NAME) ?></a>
    <div class="d-flex">
      <?php if (current_user()): ?>
        <?php $u = current_user(); ?>
<span class="navbar-text text-white me-3">
  👤 <?= e($u['name'] ?? $u['email'] ?? 'usuario') ?>
</span>
        <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout.php')) ?>">Salir</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container py-3">
