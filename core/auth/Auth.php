<?php

require_once __DIR__ . '/../database/connection.php';

class Auth
{
    public static function user()
    {
        $pdo = db();

        // Simulación: siempre Pablo
        $stmt = $pdo->prepare("SELECT * FROM core_users WHERE email = ?");
        $stmt->execute(['pablo@test.com']);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function userId()
    {
        $user = self::user();
        return $user ? $user['id'] : null;
    }
}