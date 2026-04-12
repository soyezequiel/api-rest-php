<?php

class DB
{
    private static $connection;

    public static function getConnection()
    {

        // Patrón Singleton: Si la conexión ya existe, no la vuelve a crear
        if (!self::$connection) {

            // Variables de configuración
            $host   = $_ENV['DB_HOST'] ?? 'db';
            $dbname = $_ENV['DB_NAME'] ?? '';
            $user   = $_ENV['DB_USER'] ?? '';
            $pass   = $_ENV['DB_PASS'] ?? '';

            try {
                // PDO es la interfaz de PHP para conectarse a bases de datos.
                // Aquí se construye el DSN (Data Source Name) y se pasan las credenciales.
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

                // Configuramos PDO para que lance excepciones en caso de errores de SQL
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Si la conexión falla, corta la ejecución y devuelve un JSON con el error
                throw new Exception("Error de conexión: " . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
