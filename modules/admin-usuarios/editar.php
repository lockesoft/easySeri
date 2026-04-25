<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();
$error = null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM core_users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// Cargar todos los roles disponibles
$stmtRoles = $pdo->query("SELECT id, name, description FROM core_roles ORDER BY name ASC");
$roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

// Cargar roles actuales del usuario
$stmtUserRoles = $pdo->prepare("SELECT role_id FROM core_user_roles WHERE user_id = ?");
$stmtUserRoles->execute([$id]);
$userRoleIds = $stmtUserRoles->fetchAll(PDO::FETCH_COLUMN);

if (!$user) {
    ob_start();
    echo "<h1>Usuario no encontrado</h1>";
    $content = ob_get_clean();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password'] ?? '');
    $selectedRoles = $_POST['roles'] ?? [];

    if ($name === '' || $email === '') {
        $error = "Nombre y email son obligatorios";
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE core_users
                SET name = ?, email = ?, is_active = ?, password_hash = ?
                WHERE id = ?
            ");

            $stmt->execute([$name, $email, $isActive, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE core_users
                SET name = ?, email = ?, is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([$name, $email, $isActive, $id]);
        }
        // Actualizar roles del usuario
        $stmt = $pdo->prepare("DELETE FROM core_user_roles WHERE user_id = ?");
        $stmt->execute([$id]);

        if (!empty($selectedRoles)) {
            $stmt = $pdo->prepare("INSERT INTO core_user_roles (user_id, role_id) VALUES (?, ?)");

            foreach ($selectedRoles as $roleId) {
                $stmt->execute([$id, (int)$roleId]);
            }
        }
        header('Location: /easyseri/admin-usuarios');
        exit;
    }
}

ob_start();
?>

<h1>Editar usuario</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"><br><br>

    <label>Email</label><br>
    <input type="text" name="email" value="<?= htmlspecialchars($user['email']) ?>"><br><br>

    <label>
        <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
        Usuario activo
    </label>

    <br><br>

    <label>Nueva contraseña</label><br>
    <input type="password" name="password">
    <p style="color:#666;font-size:13px;">
        Deja este campo vacío si no quieres cambiar la contraseña.
    </p>
    <h3>Roles</h3>

    <?php foreach ($roles as $role): ?>
        <label>
            <input
                type="checkbox"
                name="roles[]"
                value="<?= (int)$role['id'] ?>"
                <?= in_array($role['id'], $userRoleIds) ? 'checked' : '' ?>
            >
            <?= htmlspecialchars($role['name']) ?>
            <?php if (!empty($role['description'])): ?>
                <small style="color:#666;">
                    - <?= htmlspecialchars($role['description']) ?>
                </small>
            <?php endif; ?>
        </label>
        <br>
    <?php endforeach; ?>

    <br>
    <button type="submit">Guardar cambios</button>
    <a href="/easyseri/admin-usuarios">Cancelar</a>
</form>

<?php
$content = ob_get_clean();