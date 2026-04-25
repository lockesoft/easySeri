<?php

require_once __DIR__ . '/../../core/database/connection.php';
require_once __DIR__ . '/../../core/module-manager/ModuleManager.php';

$manager = new ModuleManager();
$visibleModules = $manager->getVisibleModulesForUser();

if (!in_array('admin-usuarios', $visibleModules)) {
    http_response_code(403);
    echo "<h1>403</h1><p>No tienes acceso</p>";
    return;
}

$pdo = db();
$error = null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM core_roles WHERE id = ?");
$stmt->execute([$id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    ob_start();
    echo "<h1>Rol no encontrado</h1>";
    $content = ob_get_clean();
    return;
}

$permissions = $pdo->query("
    SELECT id, code, description, module_name
    FROM core_permissions
    ORDER BY module_name ASC, code ASC
")->fetchAll(PDO::FETCH_ASSOC);

$stmtRolePermissions = $pdo->prepare("
    SELECT permission_id
    FROM core_role_permissions
    WHERE role_id = ?
");
$stmtRolePermissions->execute([$id]);
$rolePermissionIds = $stmtRolePermissions->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selectedPermissions = $_POST['permissions'] ?? [];

    if ($name === '') {
        $error = "El nombre del rol es obligatorio";
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM core_roles WHERE name = ? AND id <> ?");
        $stmtCheck->execute([$name, $id]);

        if ($stmtCheck->fetch()) {
            $error = "Ya existe otro rol con ese nombre";
        } else {
            $stmt = $pdo->prepare("
                UPDATE core_roles
                SET name = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $id]);

            $stmt = $pdo->prepare("DELETE FROM core_role_permissions WHERE role_id = ?");
            $stmt->execute([$id]);

            if (!empty($selectedPermissions)) {
                $stmt = $pdo->prepare("
                    INSERT INTO core_role_permissions (role_id, permission_id)
                    VALUES (?, ?)
                ");

                foreach ($selectedPermissions as $permissionId) {
                    $stmt->execute([$id, (int)$permissionId]);
                }
            }

            header('Location: /easyseri/admin-roles?msg=updated');
            exit;
        }
    }
}

ob_start();
?>

<h1>Editar rol</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre del rol</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $role['name']) ?>"><br><br>

    <label>Descripción</label><br>
    <input type="text" name="description" value="<?= htmlspecialchars($_POST['description'] ?? ($role['description'] ?? '')) ?>"><br><br>

    <h3>Permisos / módulos</h3>

    <?php foreach ($permissions as $p): ?>
        <label>
            <input
                type="checkbox"
                name="permissions[]"
                value="<?= (int)$p['id'] ?>"
                <?= in_array($p['id'], $rolePermissionIds) ? 'checked' : '' ?>
            >
            <?= htmlspecialchars($p['code']) ?>
            <small style="color:#666;">
                <?= htmlspecialchars($p['description'] ?? '') ?>
            </small>
        </label>
        <br>
    <?php endforeach; ?>

    <br>

    <button type="submit">Guardar cambios</button>
    <a href="/easyseri/admin-roles">Cancelar</a>
</form>

<?php
$content = ob_get_clean();