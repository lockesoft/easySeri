<?php

require_once __DIR__ . '/../../core/auth/Auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($email, $password)) {
        header('Location: /easyseri/');
        exit;
    } else {
        $error = "Credenciales incorrectas";
    }
}

ob_start();
?>

<h1>Login</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Email</label><br>
    <input type="text" name="email"><br><br>

    <label>Password</label><br>
    <input type="password" name="password"><br><br>

    <button type="submit">Entrar</button>
</form>

<?php
$content = ob_get_clean();