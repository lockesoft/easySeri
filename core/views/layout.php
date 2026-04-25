<?php
/** @var array $menu */
/** @var array|null $user */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>easySeri</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .header {
            background: #1f2937;
            color: white;
            padding: 15px 20px;
        }

        .wrapper {
            display: flex;
            min-height: calc(100vh - 54px);
        }

        .sidebar {
            width: 240px;
            background: #111827;
            color: white;
            padding: 20px 0;
        }

        .sidebar h3 {
            margin: 0 20px 20px 20px;
            font-size: 18px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
        }

        .sidebar a:hover {
            background: #1f2937;
        }

        .content {
            flex: 1;
            padding: 30px;
            background: white;
        }

        .user-box {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="header">
    <strong>easySeri</strong>
    <div class="user-box">
        Usuario: <?= htmlspecialchars($user['name'] ?? 'Invitado') ?>
        <a href="<?= htmlspecialchars($basePath . '/logout') ?>" style="color:white;">
    Cerrar sesión
</a>
    </div>
    
</div>

<div class="wrapper">
    <div class="sidebar">
        <h3>Módulos</h3>

<?php foreach ($menu as $item): 
    $isActive = ($currentPath === $basePath . $item['route']);
?>
<a href="<?= htmlspecialchars($basePath . $item['route']) ?>"
   style="<?= $isActive ? 'background:#1f2937;font-weight:bold;' : '' ?>">
    <?= htmlspecialchars($item['title']) ?>
</a>
<?php endforeach; ?>
    </div>

    <div class="content">
        <p style="color:#666;font-size:14px;">
    Ruta actual: <?= htmlspecialchars($currentPath) ?>
</p>
        <?= $content ?>
    </div>
</div>

</body>
</html>