<?php

require_once __DIR__ . '/../../core/auth/Auth.php';

$user = Auth::user();
$name = $user ? $user['name'] : 'usuario';

ob_start();
?>

<h1>Cámaras - Ubicación</h1>

<p>
    Módulo base cargado correctamente dentro de easySeri.
</p>

<p>
    Usuario actual:
    <strong><?= htmlspecialchars($name) ?></strong>
</p>

<p>
    <a href="/easyseri/camaras-ubicacion/scan" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">
        📷 Ir a escaneo de palets
    </a>
</p>

<hr>

<h2>Estado del módulo</h2>

<ul>
    <li>Integración base: <strong>OK</strong></li>
    <li>Pantalla de escaneo legacy en iframe: <strong>OK</strong></li>
    <li>Cámara de escaneo: <strong>OK en carga inicial</strong></li>
    <li>APIs legacy de escaneo: <strong>copiadas parcialmente</strong></li>
    <li>Sync SAP: <strong>externo, pendiente de validación física</strong></li>
</ul>

<?php
$content = ob_get_clean();