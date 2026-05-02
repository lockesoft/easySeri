<?php

/*
|--------------------------------------------------------------------------
| Adaptador temporal de autenticación para legacy cámaras dentro de easySeri
|--------------------------------------------------------------------------
| La app antigua usaba:
|   - SESSION_NAME = cam_app_sid
|   - $_SESSION['user']
|
| easySeri usa:
|   - sesión PHP normal
|   - $_SESSION['user_id']
|   - core/auth/Auth.php
|
| Este archivo adapta la app vieja para que funcione con el login actual
| de easySeri sin tocar todavía todos los endpoints legacy.
*/

require_once __DIR__ . '/../../../core/auth/Auth.php';
require_once __DIR__ . '/../../../core/permissions/PermissionService.php';
require_once __DIR__ . '/helpers.php';

Auth::start();

function require_login(): void
{
    if (!Auth::check()) {
        header('Location: /easyseri/login');
        exit;
    }

    $userId = Auth::userId();

    if (!$userId || !PermissionService::userHas($userId, 'camaras-ubicacion.access')) {
        http_response_code(403);
        echo '403 - No tienes acceso al módulo Cámaras.';
        exit;
    }
}

function current_user()
{
    return Auth::user();
}

function current_user_id(): ?int
{
    $id = Auth::userId();
    return $id ? (int)$id : null;
}

function is_admin(): bool
{
    $userId = current_user_id();

    if (!$userId) {
        return false;
    }

    return PermissionService::userHas($userId, 'camaras-ubicacion.admin');
}