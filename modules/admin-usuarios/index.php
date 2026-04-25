<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();

$stmt = $pdo->query("SELECT id, name, email, is_active FROM core_users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>Gestión de usuarios</h1>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Activo</th>
    </tr>

    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['is_active'] ? 'Sí' : 'No' ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
$content = ob_get_clean();