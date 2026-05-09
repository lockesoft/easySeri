<?php

require_once __DIR__ . '/../../core/database/connection.php';
require_once __DIR__ . '/../../core/module-manager/ModuleManager.php';

$manager = new ModuleManager();
$visibleModules = $manager->getVisibleModulesForUser();

if (!in_array('admin-plantas', $visibleModules)) {
    http_response_code(403);
    echo '<h1>403</h1><p>No tienes acceso</p>';
    return;
}

$pdo = db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || $name === '') {
        $error = 'Código y nombre son obligatorios';
    } elseif (!preg_match('/^[A-Z0-9_-]{1,20}$/', $code)) {
        $error = 'El código solo puede contener letras, números, guion y guion bajo. Máximo 20 caracteres.';
    } else {
        $stmtCheck = $pdo->prepare('SELECT id FROM core_plants WHERE code = ?');
        $stmtCheck->execute([$code]);

        if ($stmtCheck->fetch()) {
            $error = 'Ya existe una planta con ese código';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO core_plants (code, name, is_active)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$code, $name, $isActive]);

            header('Location: /easyseri/admin-plantas?msg=created');
            exit;
        }
    }
}

ob_start();
?>

<h1>Crear planta</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Código</label><br>
    <input type="text" name="code" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>" placeholder="A1"><br>
    <small>Ejemplo: A1, A2, CAMPO, ALMACEN1</small>
    <br><br>

    <label>Nombre</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Planta A1"><br><br>

    <label>
        <input type="checkbox" name="is_active" <?= (!isset($_POST['code']) || isset($_POST['is_active'])) ? 'checked' : '' ?>>
        Planta activa
    </label>

    <br><br>

    <button type="submit">Guardar</button>
    <a href="/easyseri/admin-plantas">Cancelar</a>
</form>

<?php
$content = ob_get_clean();
