<?php

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../module-manager/ModuleMenuBuilder.php';

function renderLayout(string $content): void
{
    $user = Auth::user();
    $menuBuilder = new ModuleMenuBuilder();
    $menu = $menuBuilder->getMenuForCurrentUser();

    // detectar ruta actual
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    require __DIR__ . '/layout.php';
}