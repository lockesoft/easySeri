<?php

require_once __DIR__ . '/core/module-manager/ModuleManager.php';

$manager = new ModuleManager();

$manager->syncWithDatabase();

$modules = $manager->getVisibleModulesForUser();

if (in_array('welcome', $modules)) {
    require __DIR__ . '/modules/welcome/index.php';
} else {
    echo "No tienes acceso";
}