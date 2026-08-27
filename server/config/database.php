<?php

require_once __DIR__ . '/../helpers/Env.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = Env::get('DB_HOST', 'localhost');
            $port     = Env::get('DB_PORT', '3306');
            $dbname   = Env::get('DB_NAME', 'eduadmin');
            $username = Env::get('DB_USER', 'root');
            $password = Env::get('DB_PASS', '');

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

            self::$instance = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
