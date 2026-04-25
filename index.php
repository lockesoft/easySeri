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
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);