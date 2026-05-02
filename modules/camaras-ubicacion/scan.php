<?php

ob_start();
?>

<h1>Escaneo de palets</h1>

<iframe 
    src="/easyseri/modules/camaras-ubicacion/legacy/scan.php" 
    allow="camera; microphone; fullscreen"
    style="width:100%; height:80vh; border:none;">
</iframe>

<?php
$content = ob_get_clean();