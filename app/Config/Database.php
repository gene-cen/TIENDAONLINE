<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        // 1. Intentamos obtener la URL de Render
        $databaseUrl = getenv('DATABASE_URL');

        try {
            if ($databaseUrl) {
                // --- CONFIGURACIÓN PARA RENDER (PostgreSQL) ---
                $dbopts = parse_url($databaseUrl);

                $host = $dbopts['host'];
                // Si 'port' no existe, usamos 5432 por defecto
                $port = $dbopts['port'] ?? '5432';
                $user = $dbopts['user'];
                $pass = $dbopts['pass'];
                $name = ltrim($dbopts['path'], '/');

                // DSN específico para PostgreSQL
                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                $username = $user;
                $password = $pass;
            } else {
                // --- TU CONFIGURACIÓN LOCAL (XAMPP / MySQL) ---
                $host = "localhost";
                $db_name = "ecommerce_db";
                $username = "root";
                $password = "";
                $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            die("Error de conexión: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
