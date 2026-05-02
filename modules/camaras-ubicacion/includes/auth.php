<?php
require_once __DIR__ . '/config.php';
session_name(SESSION_NAME);
session_start();

function require_login() {
    if (empty($_SESSION['user'])) {
        redirect('/index.php'); // ← ahora apunta a BASE_URL/index.php
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
function is_admin(): bool {
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function current_user_id(): ?int {
    if (isset($_SESSION['user']['id'])) {
        return (int) $_SESSION['user']['id'];
    }

    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    return null;
}
