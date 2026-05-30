<?php

namespace App\Models;

class DB
{
    private static $connection;

    public static function getConnection()
    {
        if (!self::$connection) {

            $host   = getenv('DB_HOST') ?: 'db';
            $dbname = getenv('DB_NAME') ?: '';
            $user   = getenv('DB_USER') ?: '';
            $pass   = getenv('DB_PASS') ?: '';

            try {
                self::$connection = new \PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
                self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                self::$connection->exec("SET time_zone = '-03:00';");
            } catch (\PDOException $e) {
                throw new \Exception("Error de conexión: " . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
