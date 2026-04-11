<?php

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../permissions/PermissionService.php';

class ModuleManager
{
    private string $modulesPath;

    public function __construct()
    {
        $this->modulesPath = __DIR__ . '/../../modules/';
    }

    public function getInstalledModules(): array
    {
        $dirs = array_filter(glob($this->modulesPath . '*'), 'is_dir');
        $modules = [];

        foreach ($dirs as $dir) {
            $moduleFile = $dir . '/module.json';

            if (file_exists($moduleFile)) {
                $data = json_decode(file_get_contents($moduleFile), true);

                if (is_array($data) && isset($data['name'])) {
                    $modules[] = $data;
                }
            }
        }

        return $modules;
    }

    public function syncWithDatabase(): void
    {
        $pdo = db();
        $modules = $this->getInstalledModules();

        foreach ($modules as $module) {
            $stmt = $pdo->prepare("SELECT id FROM core_modules WHERE name = ?");
            $stmt->execute([$module['name']]);

            if (!$stmt->fetch()) {
                $insert = $pdo->prepare("
                    INSERT INTO core_modules (name, title, version, enabled)
                    VALUES (?, ?, ?, 0)
                ");

                $insert->execute([
                    $module['name'],
                    $module['title'] ?? $module['name'],
                    $module['version'] ?? '0.0.1'
                ]);
            }
        }
    }

    public function getVisibleModulesForUser(): array
    {
        $pdo = db();
        $userId = Auth::userId();

        $stmt = $pdo->query("SELECT name FROM core_modules WHERE enabled = 1");
        $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $visible = [];

        foreach ($modules as $moduleName) {
            $permission = $moduleName . '.access';

            if (PermissionService::userHas($userId, $permission)) {
                $visible[] = $moduleName;
            }
        }

        return $visible;
    }
}