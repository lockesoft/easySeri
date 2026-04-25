<?php

require_once __DIR__ . '/../database/connection.php';

class Auth
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user()
    {
        self::start();

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $pdo = db();

        $stmt = $pdo->prepare("SELECT * FROM core_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function userId()
    {
        $user = self::user();
        return $user ? $user['id'] : null;
    }

    public static function attempt($email, $password)
    {
        self::start();

        $pdo = db();

        $stmt = $pdo->prepare("SELECT * FROM core_users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // temporal: comparación simple (luego bcrypt)
        if ($user['password_hash'] !== $password) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];

        return true;
    }

    public static function logout()
    {
        self::start();
        session_destroy();
    }

    public static function check()
    {
        self::start();
        return isset($_SESSION['user_id']);
    }
}