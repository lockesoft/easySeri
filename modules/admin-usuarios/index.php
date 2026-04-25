<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();

$stmt = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.is_active,
        GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles
    FROM core_users u
    LEFT JOIN core_user_roles ur ON u.id = ur.user_id
    LEFT JOIN core_roles r ON ur.role_id = r.id
    GROUP BY u.id, u.name, u.email, u.is_active
    ORDER BY u.id ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>Gestión de usuarios</h1>

<a href="/easyseri/admin-usuarios/crear">Crear usuario</a>
<br><br>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Activo</th>
        <th>Roles</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['is_active'] ? 'Sí' : 'No' ?></td>
            <td><?= htmlspecialchars($u['roles'] ?? 'Sin rol') ?></td>
            <td>
                <a href="/easyseri/admin-usuarios/editar?id=<?= (int)$u['id'] ?>">Editar</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
$content = ob_get_clean();