<?php

require_once __DIR__ . '/../../core/auth/Auth.php';

$user = Auth::user();
$name = $user ? $user['name'] : 'usuario';

ob_start();
?>

<h1>Bienvenido <?= htmlspecialchars($name) ?></h1>
<p>El core de easySeri está funcionando correctamente.</p>
<p>Este módulo sirve como prueba de carga dinámica, permisos y menú.</p>

<?php
$content = ob_get_clean();