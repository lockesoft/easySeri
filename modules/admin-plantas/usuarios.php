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

try {
    $plants = $pdo->query('
        SELECT id, code, name, is_active
        FROM core_plants
        ORDER BY code ASC
    ')->fetchAll(PDO::FETCH_ASSOC);

    $users = $pdo->query('
        SELECT id, name, email, is_active, default_plant_id
        FROM core_users
        ORDER BY name ASC, id ASC
    ')->fetchAll(PDO::FETCH_ASSOC);

    $userPlantsRows = $pdo->query('
        SELECT user_id, plant_id
        FROM core_user_plants
    ')->fetchAll(PDO::FETCH_ASSOC);

    $userPlants = [];
    foreach ($userPlantsRows as $row) {
        $userPlants[(int)$row['user_id']][] = (int)$row['plant_id'];
    }
} catch (Throwable $e) {
    $plants = [];
    $users = [];
    $userPlants = [];
    $error = 'No se pudo cargar la asignación de plantas. Revisa si las tablas core_plants, core_user_plants y la columna default_plant_id ya existen.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $selectedPlants = $_POST['plants'] ?? [];
    $defaultPlantId = (int)($_POST['default_plant_id'] ?? 0);

    if ($userId <= 0) {
        $error = 'Usuario inválido';
    } elseif (!is_array($selectedPlants)) {
        $error = 'Selección de plantas inválida';
    } else {
        $selectedPlants = array_values(array_unique(array_map('intval', $selectedPlants)));
        $selectedPlants = array_filter($selectedPlants, fn($id) => $id > 0);

        if ($defaultPlantId > 0 && !in_array($defaultPlantId, $selectedPlants, true)) {
            $error = 'La planta por defecto debe estar entre las plantas asignadas al usuario';
        } else {
            $pdo->beginTransaction();

            $del = $pdo->prepare('DELETE FROM core_user_plants WHERE user_id = ?');
            $del->execute([$userId]);

            if ($selectedPlants) {
                $ins = $pdo->prepare('INSERT INTO core_user_plants (user_id, plant_id) VALUES (?, ?)');

                foreach ($selectedPlants as $plantId) {
                    $ins->execute([$userId, $plantId]);
                }
            }

            $upd = $pdo->prepare('UPDATE core_users SET default_plant_id = ? WHERE id = ?');
            $upd->execute([$defaultPlantId > 0 ? $defaultPlantId : null, $userId]);

            $pdo->commit();

            header('Location: /easyseri/admin-plantas/usuarios?msg=updated');
            exit;
        }
    }
}

ob_start();
?>

<h1>Asignar plantas a usuarios</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'updated'): ?>
    <p style="color:green;">Asignación actualizada correctamente.</p>
<?php endif; ?>

<p>
    <a href="/easyseri/admin-plantas">Volver a plantas</a>
</p>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>Usuario</th>
        <th>Email</th>
        <th>Activo</th>
        <th>Plantas asignadas</th>
        <th>Planta por defecto</th>
        <th>Acción</th>
    </tr>

    <?php foreach ($users as $user): ?>
        <?php
            $uid = (int)$user['id'];
            $assigned = $userPlants[$uid] ?? [];
        ?>
        <tr>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $uid ?>">

                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= ((int)$user['is_active'] === 1) ? 'Sí' : 'No' ?></td>
                <td>
                    <?php foreach ($plants as $plant): ?>
                        <label style="display:block;">
                            <input
                                type="checkbox"
                                name="plants[]"
                                value="<?= (int)$plant['id'] ?>"
                                <?= in_array((int)$plant['id'], $assigned, true) ? 'checked' : '' ?>
                            >
                            <?= htmlspecialchars($plant['code']) ?> - <?= htmlspecialchars($plant['name']) ?>
                            <?= ((int)$plant['is_active'] === 1) ? '' : '(inactiva)' ?>
                        </label>
                    <?php endforeach; ?>
                </td>
                <td>
                    <select name="default_plant_id">
                        <option value="0">Sin planta por defecto</option>
                        <?php foreach ($plants as $plant): ?>
                            <option
                                value="<?= (int)$plant['id'] ?>"
                                <?= ((int)($user['default_plant_id'] ?? 0) === (int)$plant['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($plant['code']) ?> - <?= htmlspecialchars($plant['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <button type="submit">Guardar</button>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>

    <?php if (!$users): ?>
        <tr>
            <td colspan="6">No hay usuarios o falta ejecutar la migración.</td>
        </tr>
    <?php endif; ?>
</table>

<?php
$content = ob_get_clean();
