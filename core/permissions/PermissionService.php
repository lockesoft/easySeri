<?php

require_once __DIR__ . '/../database/connection.php';

class PermissionService
{
    public static function userHas($userId, $permissionCode)
    {
        $pdo = db();

        $sql = "
            SELECT 1
            FROM core_user_roles ur
            JOIN core_role_permissions rp ON ur.role_id = rp.role_id
            JOIN core_permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ?
              AND p.code = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $permissionCode]);

        return (bool) $stmt->fetch();
    }
}