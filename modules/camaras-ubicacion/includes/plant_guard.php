<?php

require_once __DIR__ . '/../../../core/plants/PlantService.php';

if (!function_exists('camera_belongs_to_active_plant')) {
    function camera_belongs_to_active_plant(mysqli $db, int $cameraId): bool
    {
        if ($cameraId <= 0) {
            return false;
        }

        $activePlant = PlantService::getActivePlantForCurrentUser();

        if (!$activePlant || empty($activePlant['code'])) {
            return false;
        }

        $plantCode = (string)$activePlant['code'];

        $stmt = $db->prepare('
            SELECT 1
            FROM cameras
            WHERE id = ?
              AND plant_code = ?
            LIMIT 1
        ');

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('is', $cameraId, $plantCode);
        $stmt->execute();
        $stmt->store_result();

        $ok = $stmt->num_rows > 0;

        $stmt->free_result();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('row_group_belongs_to_camera')) {
    function row_group_belongs_to_camera(mysqli $db, int $rowGroupId, int $cameraId): bool
    {
        if ($rowGroupId <= 0 || $cameraId <= 0) {
            return false;
        }

        $stmt = $db->prepare('
            SELECT 1
            FROM camera_row_groups
            WHERE id = ?
              AND camera_id = ?
            LIMIT 1
        ');

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $rowGroupId, $cameraId);
        $stmt->execute();
        $stmt->store_result();

        $ok = $stmt->num_rows > 0;

        $stmt->free_result();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('active_plant_guard_error')) {
    function active_plant_guard_error(): string
    {
        $activePlant = PlantService::getActivePlantForCurrentUser();

        if (!$activePlant || empty($activePlant['code'])) {
            return 'Tu usuario no tiene planta activa. Selecciona una planta antes de operar.';
        }

        return 'La cámara destino no pertenece a tu planta activa (' . $activePlant['code'] . ').';
    }
}
