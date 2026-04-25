<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = "Todos los campos son obligatorios";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

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
    <input type="text" name="name"><br><br>

    <label>Email</label><br>
    <input type="text" name="email"><br><br>

    <label>Password</label><br>
    <input type="password" name="password"><br><br>

    <button type="submit">Guardar</button>
</form>

<?php
$content = ob_get_clean();