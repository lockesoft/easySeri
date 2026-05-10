<?php

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../auth/Auth.php';

class PlantService
{
    public static function getPlantsForUser(int $userId): array
    {
        $pdo = db();

        $stmt = $pdo->prepare('
            SELECT
                p.id,
                p.code,
                p.name,
                p.is_active
            FROM core_user_plants up
            JOIN core_plants p ON p.id = up.plant_id
            WHERE up.user_id = ?
              AND p.is_active = 1
            ORDER BY p.code ASC
        ');

        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function userCanAccessPlant(int $userId, string $plantCode): bool
    {
        $pdo = db();

        $stmt = $pdo->prepare('
            SELECT 1
            FROM core_user_plants up
            JOIN core_plants p ON p.id = up.plant_id
            WHERE up.user_id = ?
              AND p.code = ?
              AND p.is_active = 1
            LIMIT 1
        ');

        $stmt->execute([$userId, $plantCode]);
        return (bool)$stmt->fetchColumn();
    }

    public static function getDefaultPlantForUser(int $userId): ?array
    {
        $pdo = db();

        $stmt = $pdo->prepare('
            SELECT
                p.id,
                p.code,
                p.name,
                p.is_active
            FROM core_users u
            JOIN core_plants p ON p.id = u.default_plant_id
            WHERE u.id = ?
              AND p.is_active = 1
            LIMIT 1
        ');

        $stmt->execute([$userId]);
        $plant = $stmt->fetch(PDO::FETCH_ASSOC);

        return $plant ?: null;
    }

    public static function getActivePlantForCurrentUser(): ?array
    {
        Auth::start();

        $userId = Auth::userId();
        if (!$userId) {
            return null;
        }

        $sessionPlantCode = $_SESSION['active_plant_code'] ?? null;

        if ($sessionPlantCode && self::userCanAccessPlant((int)$userId, (string)$sessionPlantCode)) {
            return self::getPlantByCode((string)$sessionPlantCode);
        }

        $defaultPlant = self::getDefaultPlantForUser((int)$userId);

        if ($defaultPlant && self::userCanAccessPlant((int)$userId, (string)$defaultPlant['code'])) {
            $_SESSION['active_plant_code'] = $defaultPlant['code'];
            return $defaultPlant;
        }

        $plants = self::getPlantsForUser((int)$userId);

        if (!empty($plants)) {
            $_SESSION['active_plant_code'] = $plants[0]['code'];
            return $plants[0];
        }

        return null;
    }

    public static function setActivePlantForCurrentUser(string $plantCode): bool
    {
        Auth::start();

        $userId = Auth::userId();
        if (!$userId) {
            return false;
        }

        $plantCode = strtoupper(trim($plantCode));

        if ($plantCode === '') {
            return false;
        }

        if (!self::userCanAccessPlant((int)$userId, $plantCode)) {
            return false;
        }

        $_SESSION['active_plant_code'] = $plantCode;
        return true;
    }

    public static function getPlantByCode(string $plantCode): ?array
    {
        $pdo = db();

        $stmt = $pdo->prepare('
            SELECT id, code, name, is_active
            FROM core_plants
            WHERE code = ?
              AND is_active = 1
            LIMIT 1
        ');

        $stmt->execute([$plantCode]);
        $plant = $stmt->fetch(PDO::FETCH_ASSOC);

        return $plant ?: null;
    }
}
