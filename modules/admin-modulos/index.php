<?php

require_once __DIR__ . '/../../core/database/connection.php';
require_once __DIR__ . '/../../core/module-manager/ModuleManager.php';

$pdo = db();
$manager = new ModuleManager();

// sincroniza por si hay nuevos módulos
$manager->syncWithDatabase();

// activar / desactivar
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE core_modules
        SET enabled = NOT enabled
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    header('Location: /easyseri/admin-modulos');
    exit;
}

$stmt = $pdo->query("
    SELECT id, name, title, version, enabled
    FROM core_modules
    ORDER BY name ASC
");

$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>Gestión de módulos</h1>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Título</th>
        <th>Versión</th>
        <th>Estado</th>
        <th>Acción</th>
    </tr>

    <?php foreach ($modules as $m): ?>
        <tr>
            <td><?= (int)$m['id'] ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['title']) ?></td>
            <td><?= htmlspecialchars($m['version'] ?? '-') ?></td>
            <td><?= $m['enabled'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
                <a href="/easyseri/admin-modulos?toggle=<?= (int)$m['id'] ?>">
                    <?= $m['enabled'] ? 'Desactivar' : 'Activar' ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
$content = ob_get_clean();