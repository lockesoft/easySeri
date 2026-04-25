<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();
$error = null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM core_users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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

    <button type="submit">Guardar cambios</button>
    <a href="/easyseri/admin-usuarios">Cancelar</a>
</form>

<?php
$content = ob_get_clean();