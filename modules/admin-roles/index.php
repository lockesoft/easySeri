<?php

require_once __DIR__ . '/../../core/database/connection.php';

$pdo = db();

$stmt = $pdo->query("
    SELECT 
        r.id,
        r.name,
        r.description,
        GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') AS permissions
    FROM core_roles r
    LEFT JOIN core_role_permissions rp ON r.id = rp.role_id
    LEFT JOIN core_permissions p ON rp.permission_id = p.id
    GROUP BY r.id, r.name, r.description
    ORDER BY r.name ASC
");

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>Gestión de roles</h1>

<a href="/easyseri/admin-roles/crear">Crear rol</a>
<br><br>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Rol</th>
        <th>Descripción</th>
        <th>Permisos</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($roles as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['permissions'] ?? 'Sin permisos') ?></td>
            <td>
                <a href="/easyseri/admin-roles/editar?id=<?= (int)$r['id'] ?>">Editar</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
$content = ob_get_clean();