<?php

require_once __DIR__ . '/../../core/database/connection.php';
require_once __DIR__ . '/../../core/module-manager/ModuleManager.php';

$manager = new ModuleManager();
$visibleModules = $manager->getVisibleModulesForUser();

if (!in_array('admin-plantas', $visibleModules)) {
    http_response_code(403);
    echo '<h1>403</h1><p>No tienes acceso</p>';
    return;
}

$pdo = db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE core_plants SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);

        header('Location: /easyseri/admin-plantas?msg=toggled');
        exit;
    }

    $error = 'Planta inválida';
}

$plants = [];

try {
    $stmt = $pdo->query('
        SELECT
            p.id,
            p.code,
            p.name,
            p.is_active,
            COUNT(up.user_id) AS users_count
        FROM core_plants p
        LEFT JOIN core_user_plants up ON up.plant_id = p.id
        GROUP BY p.id, p.code, p.name, p.is_active
        ORDER BY p.code ASC
    ');

    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'No se pudo cargar el listado de plantas. Revisa si las tablas core_plants y core_user_plants ya existen.';
}

ob_start();
?>

<h1>Gestión de plantas</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'created'): ?>
    <p style="color:green;">Planta creada correctamente.</p>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'updated'): ?>
    <p style="color:green;">Planta actualizada correctamente.</p>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'toggled'): ?>
    <p style="color:green;">Estado de la planta actualizado correctamente.</p>
<?php endif; ?>

<p>
    <a href="/easyseri/admin-plantas/crear">Crear planta</a>
    |
    <a href="/easyseri/admin-plantas/usuarios">Asignar plantas a usuarios</a>
</p>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Nombre</th>
        <th>Activa</th>
        <th>Usuarios asignados</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($plants as $plant): ?>
        <tr>
            <td><?= (int)$plant['id'] ?></td>
            <td><?= htmlspecialchars($plant['code']) ?></td>
            <td><?= htmlspecialchars($plant['name']) ?></td>
            <td><?= ((int)$plant['is_active'] === 1) ? 'Sí' : 'No' ?></td>
            <td><?= (int)$plant['users_count'] ?></td>
            <td>
                <a href="/easyseri/admin-plantas/editar?id=<?= (int)$plant['id'] ?>">Editar</a>

                <form method="POST" style="display:inline; margin-left:8px;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$plant['id'] ?>">
                    <button type="submit">
                        <?= ((int)$plant['is_active'] === 1) ? 'Desactivar' : 'Activar' ?>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$plants): ?>
        <tr>
            <td colspan="6">No hay plantas creadas o falta ejecutar la migración.</td>
        </tr>
    <?php endif; ?>
</table>

<?php
$content = ob_get_clean();
