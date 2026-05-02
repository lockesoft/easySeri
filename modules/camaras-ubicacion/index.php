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

<hr>

<h2>Estado del módulo</h2>

<ul>
    <li>Integración base: <strong>OK</strong></li>
    <li>App antigua de cámaras: <strong>pendiente de migrar</strong></li>
    <li>APIs de escaneo: <strong>pendiente</strong></li>
    <li>Sync SAP: <strong>externo, pendiente de validación física</strong></li>
</ul>

<?php
$content = ob_get_clean();