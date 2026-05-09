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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM core_plants WHERE id = ?');
$stmt->execute([$id]);
$plant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plant) {
    ob_start();
    echo '<h1>Planta no encontrada</h1>';
    echo '<p><a href="/easyseri/admin-plantas">Volver</a></p>';
    $content = ob_get_clean();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || $name === '') {
        $error = 'Código y nombre son obligatorios';
    } elseif (!preg_match('/^[A-Z0-9_-]{1,20}$/', $code)) {
        $error = 'El código solo puede contener letras, números, guion y guion bajo. Máximo 20 caracteres.';
    } else {
        $stmtCheck = $pdo->prepare('SELECT id FROM core_plants WHERE code = ? AND id <> ?');
        $stmtCheck->execute([$code, $id]);

        if ($stmtCheck->fetch()) {
            $error = 'Ya existe otra planta con ese código';
        } else {
            $stmt = $pdo->prepare('
                UPDATE core_plants
                SET code = ?, name = ?, is_active = ?
                WHERE id = ?
            ');
            $stmt->execute([$code, $name, $isActive, $id]);

            header('Location: /easyseri/admin-plantas?msg=updated');
            exit;
        }
    }
}

ob_start();
?>

<h1>Editar planta</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Código</label><br>
    <input type="text" name="code" value="<?= htmlspecialchars($_POST['code'] ?? $plant['code']) ?>"><br>
    <small>Ejemplo: A1, A2, CAMPO, ALMACEN1</small>
    <br><br>

    <label>Nombre</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $plant['name']) ?>"><br><br>

    <label>
        <input type="checkbox" name="is_active" <?= (isset($_POST['is_active']) || (!$_POST && (int)$plant['is_active'] === 1)) ? 'checked' : '' ?>>
        Planta activa
    </label>

    <br><br>

    <button type="submit">Guardar cambios</button>
    <a href="/easyseri/admin-plantas">Cancelar</a>
</form>

<?php
$content = ob_get_clean();
