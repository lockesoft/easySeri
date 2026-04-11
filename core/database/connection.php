<?php

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $db   = 'easyseri';
        $user = 'root';
        $pass = 'Polete.2019';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    return $pdo;
}