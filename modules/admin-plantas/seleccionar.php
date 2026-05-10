<?php

require_once __DIR__ . '/../../core/auth/Auth.php';
require_once __DIR__ . '/../../core/plants/PlantService.php';

Auth::start();

$userId = Auth::userId();

if (!$userId) {
    header('Location: /easyseri/login');
    exit;
}

$error = null;
$plants = PlantService::getPlantsForUser((int)$userId);
$activePlant = PlantService::getActivePlantForCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plantCode = trim($_POST['plant_code'] ?? '');

    if (PlantService::setActivePlantForCurrentUser($plantCode)) {
        header('Location: /easyseri/admin-plantas/seleccionar?msg=updated');
        exit;
    }

    $error = 'No tienes acceso a esa planta o la planta no está activa.';
}

ob_start();
?>

<h1>Seleccionar planta activa</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'updated'): ?>
    <p style="color:green;">Planta activa actualizada correctamente.</p>
<?php endif; ?>

<p>
    Planta activa actual:
    <strong><?= $activePlant ? htmlspecialchars($activePlant['code'] . ' - ' . $activePlant['name']) : 'Sin planta activa' ?></strong>
</p>

<?php if (!$plants): ?>
    <p style="color:red;">Tu usuario no tiene plantas asignadas.</p>
<?php else: ?>
    <form method="POST">
        <label>Planta</label><br>
        <select name="plant_code">
            <?php foreach ($plants as $plant): ?>
                <option
                    value="<?= htmlspecialchars($plant['code']) ?>"
                    <?= ($activePlant && $activePlant['code'] === $plant['code']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($plant['code']) ?> - <?= htmlspecialchars($plant['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <button type="submit">Cambiar planta activa</button>
        <a href="/easyseri/admin-plantas">Volver</a>
    </form>
<?php endif; ?>

<?php
$content = ob_get_clean();
