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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = "Todos los campos son obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no tiene un formato válido";
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM core_users WHERE email = ?");
        $stmtCheck->execute([$email]);

        if ($stmtCheck->fetch()) {
            $error = "Ya existe un usuario con ese email";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO core_users (name, email, password_hash, is_active)
                VALUES (?, ?, ?, 1)
            ");

            $stmt->execute([$name, $email, $hash]);

            header('Location: /easyseri/admin-usuarios?msg=created');
            exit;
        }
    }
}

ob_start();
?>

<h1>Crear usuario</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"><br><br>

    <label>Email</label><br>
    <input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"><br><br>

    <label>Password</label><br>
    <input type="password" name="password"><br><br>

    <button type="submit">Guardar</button>
    <a href="/easyseri/admin-usuarios">Cancelar</a>
</form>

<?php
$content = ob_get_clean();