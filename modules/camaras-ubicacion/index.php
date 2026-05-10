<?php

require_once __DIR__ . '/../../core/auth/Auth.php';

$user = Auth::user();
$name = $user ? $user['name'] : 'usuario';

ob_start();
?>

<h1>Cámaras - Ubicación</h1>

<p>
    Módulo de ubicación de palets en cámaras integrado dentro de easySeri.
</p>

<p>
    Usuario actual:
    <strong><?= htmlspecialchars($name) ?></strong>
</p>

<div style="display:flex; gap:12px; flex-wrap:wrap; margin:18px 0;">
    <a href="/easyseri/camaras-ubicacion/scan" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">
        📷 Ir a escaneo de palets
    </a>

    <a href="/easyseri/camaras-ubicacion/camaras" style="display:inline-block;padding:12px 18px;background:#198754;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">
        🧩 Administrar cámaras y planos
    </a>
</div>

<hr>

<h2>Estado del módulo</h2>

<ul>
    <li>Integración base: <strong>OK</strong></li>
    <li>Pantalla de escaneo legacy en iframe: <strong>OK</strong></li>
    <li>Gestión dinámica de cámaras: <strong>adaptada desde app legacy</strong></li>
    <li>Selector de planta activa: <strong>OK</strong></li>
    <li>Filtrado de cámaras por planta activa: <strong>pendiente de validación en planta</strong></li>
    <li>Sync SAP: <strong>externo, pendiente de validación física</strong></li>
</ul>

<?php
$content = ob_get_clean();