<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/core/module-manager/ModuleManager.php';
require_once __DIR__ . '/core/router/Router.php';
require_once __DIR__ . '/core/views/render.php';
require_once __DIR__ . '/core/auth/Auth.php';

$manager = new ModuleManager();
$manager->syncWithDatabase();

$basePath = '/easyseri';

$router = new Router($basePath);

$publicRoutes = [
    '/login',
];

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($basePath !== '' && strpos($currentPath, $basePath) === 0) {
    $currentPath = substr($currentPath, strlen($basePath));
}

$currentPath = '/' . trim($currentPath, '/');
if ($currentPath === '//') {
    $currentPath = '/';
}

if (!in_array($currentPath, $publicRoutes) && !Auth::check()) {
    header('Location: ' . $basePath . '/login');
    exit;
}

$router->get('/login', function () {
    require __DIR__ . '/modules/auth-login/index.php';
    renderLayout($content);
});

$router->post('/login', function () {
    require __DIR__ . '/modules/auth-login/index.php';
    renderLayout($content);
});
$router->get('/', function () use ($basePath) {
    header('Location: ' . $basePath . '/welcome');
    exit;
});
$router->get('/logout', function () use ($basePath) {
    Auth::logout();
    header('Location: ' . $basePath . '/login');
    exit;
});
///////////////////////modulos/////////////////
$router->get('/welcome', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('welcome', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso a este módulo.</p>');
        return;
    }

    require __DIR__ . '/modules/welcome/index.php';
    renderLayout($content);
});

$router->get('/admin-usuarios', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('admin-usuarios', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/admin-usuarios/index.php';
    renderLayout($content);
});
$router->get('/admin-usuarios/crear', function () {
    require __DIR__ . '/modules/admin-usuarios/crear.php';
    renderLayout($content);
});

$router->post('/admin-usuarios/crear', function () {
    require __DIR__ . '/modules/admin-usuarios/crear.php';
    renderLayout($content);
});
$router->get('/admin-usuarios/editar', function () {
    require __DIR__ . '/modules/admin-usuarios/editar.php';
    renderLayout($content);
});

$router->post('/admin-usuarios/editar', function () {
    require __DIR__ . '/modules/admin-usuarios/editar.php';
    renderLayout($content);
});
$router->get('/admin-roles', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('admin-roles', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/admin-roles/index.php';
    renderLayout($content);
});

$router->get('/admin-roles/crear', function () {
    require __DIR__ . '/modules/admin-roles/crear.php';
    renderLayout($content);
});

$router->post('/admin-roles/crear', function () {
    require __DIR__ . '/modules/admin-roles/crear.php';
    renderLayout($content);
});

$router->get('/admin-roles/editar', function () {
    require __DIR__ . '/modules/admin-roles/editar.php';
    renderLayout($content);
});

$router->post('/admin-roles/editar', function () {
    require __DIR__ . '/modules/admin-roles/editar.php';
    renderLayout($content);
});
$router->get('/admin-modulos', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('admin-modulos', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/admin-modulos/index.php';
    renderLayout($content);
});
$router->get('/admin-plantas', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('admin-plantas', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/admin-plantas/index.php';
    renderLayout($content);
});
$router->post('/admin-plantas', function () {
    require __DIR__ . '/modules/admin-plantas/index.php';
    renderLayout($content);
});
$router->get('/admin-plantas/crear', function () {
    require __DIR__ . '/modules/admin-plantas/crear.php';
    renderLayout($content);
});
$router->post('/admin-plantas/crear', function () {
    require __DIR__ . '/modules/admin-plantas/crear.php';
    renderLayout($content);
});
$router->get('/admin-plantas/editar', function () {
    require __DIR__ . '/modules/admin-plantas/editar.php';
    renderLayout($content);
});
$router->post('/admin-plantas/editar', function () {
    require __DIR__ . '/modules/admin-plantas/editar.php';
    renderLayout($content);
});
$router->get('/admin-plantas/usuarios', function () {
    require __DIR__ . '/modules/admin-plantas/usuarios.php';
    renderLayout($content);
});
$router->post('/admin-plantas/usuarios', function () {
    require __DIR__ . '/modules/admin-plantas/usuarios.php';
    renderLayout($content);
});
$router->get('/admin-plantas/seleccionar', function () {
    require __DIR__ . '/modules/admin-plantas/seleccionar.php';
    renderLayout($content);
});
$router->post('/admin-plantas/seleccionar', function () {
    require __DIR__ . '/modules/admin-plantas/seleccionar.php';
    renderLayout($content);
});
$router->get('/camaras-ubicacion', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('camaras-ubicacion', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/camaras-ubicacion/index.php';
    renderLayout($content);
});
$router->get('/camaras-ubicacion/camaras', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('camaras-ubicacion', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/camaras-ubicacion/camaras/index.php';
    renderLayout($content);
});
$router->get('/camaras-ubicacion/camaras/crear', function () {
    require __DIR__ . '/modules/camaras-ubicacion/camaras/crear.php';
    renderLayout($content);
});
$router->post('/camaras-ubicacion/camaras/guardar', function () {
    require __DIR__ . '/modules/camaras-ubicacion/camaras/guardar.php';
});
$router->get('/camaras-ubicacion/camaras/plano', function () {
    require __DIR__ . '/modules/camaras-ubicacion/camaras/plano_v2.php';
    renderLayout($content);
});
$router->post('/camaras-ubicacion/camaras/plano', function () {
    require __DIR__ . '/modules/camaras-ubicacion/camaras/plano_v2.php';
    renderLayout($content);
});
$router->get('/camaras-ubicacion/scan', function () {
    $manager = new ModuleManager();
    $visibleModules = $manager->getVisibleModulesForUser();

    if (!in_array('camaras-ubicacion', $visibleModules)) {
        http_response_code(403);
        renderLayout('<h1>403</h1><p>No tienes acceso</p>');
        return;
    }

    require __DIR__ . '/modules/camaras-ubicacion/scan.php';
    renderLayout($content);
});




$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);