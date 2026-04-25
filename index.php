<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/core/module-manager/ModuleManager.php';
require_once __DIR__ . '/core/router/Router.php';
require_once __DIR__ . '/core/views/render.php';

$manager = new ModuleManager();
$manager->syncWithDatabase();

/*
|--------------------------------------------------------------------------
| BASE PATH
|--------------------------------------------------------------------------
| URL real:
| https://lockesoft.ovh/easyseri/
*/
$basePath = '/easyseri';

$router = new Router($basePath);


$router->get('/login', function () {
    require __DIR__ . '/modules/auth-login/index.php';
    renderLayout($content);
});

$router->get('/', function () use ($basePath) {
    header('Location: ' . $basePath . '/welcome');
    exit;
});

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

require_once __DIR__ . '/core/auth/Auth.php';

if (!Auth::check()) {
    header('Location: /easyseri/login');
    exit;
}

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);