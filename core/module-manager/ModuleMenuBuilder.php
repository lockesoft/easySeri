<?php

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../permissions/PermissionService.php';
require_once __DIR__ . '/../database/connection.php';

class ModuleMenuBuilder
{
    private string $modulesPath;

    public function __construct()
    {
        $this->modulesPath = __DIR__ . '/../../modules/';
    }

    public function getMenuForCurrentUser(): array
    {
        $pdo = db();
        $userId = Auth::userId();

        $stmt = $pdo->query("SELECT DISTINCT name FROM core_modules WHERE enabled = 1 ORDER BY name ASC");
        $activeModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $menu = [];

        foreach ($activeModules as $moduleName) {
            $permission = $moduleName . '.access';

            if (!PermissionService::userHas($userId, $permission)) {
                continue;
            }

            $menuFile = $this->modulesPath . $moduleName . '/menu.php';

            if (file_exists($menuFile)) {
                $menuItem = require $menuFile;

                if (is_array($menuItem)) {
                    $menu[] = $menuItem;
                }
            }
        }

        usort($menu, function ($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        return $menu;
    }
}