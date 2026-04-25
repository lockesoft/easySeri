<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();
$error = null;

$permissions = $pdo->query("
    SELECT id, code, description, module_name
    FROM core_permissions
    ORDER BY module_name ASC, code ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selectedPermissions = $_POST['permissions'] ?? [];

    if ($name === '') {
        $error = "El nombre del rol es obligatorio";
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM core_roles WHERE name = ?");
        $stmtCheck->execute([$name]);

        if ($stmtCheck->fetch()) {
            $error = "Ya existe un rol con ese nombre";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO core_roles (name, description)
                VALUES (?, ?)
            ");
            $stmt->execute([$name, $description]);

            $roleId = $pdo->lastInsertId();

            if (!empty($selectedPermissions)) {
                $stmt = $pdo->prepare("
                    INSERT INTO core_role_permissions (role_id, permission_id)
                    VALUES (?, ?)
                ");

                foreach ($selectedPermissions as $permissionId) {
                    $stmt->execute([$roleId, (int)$permissionId]);
                }
            }

            header('Location: /easyseri/admin-roles?msg=created');
            exit;
        }
    }
}

ob_start();
?>

<h1>Crear rol</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre del rol</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"><br><br>

    <label>Descripción</label><br>
    <input type="text" name="description" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>"><br><br>

    <h3>Permisos / módulos</h3>

    <?php foreach ($permissions as $p): ?>
        <label>
            <input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>">
            <?= htmlspecialchars($p['code']) ?>
            <small style="color:#666;">
                <?= htmlspecialchars($p['description'] ?? '') ?>
            </small>
        </label>
        <br>
    <?php endforeach; ?>

    <br>

    <button type="submit">Guardar</button>
    <a href="/easyseri/admin-roles">Cancelar</a>
</form>

<?php
$content = ob_get_clean();