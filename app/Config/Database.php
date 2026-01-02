<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    // Configuración para XAMPP local
    private $host = "localhost";
    private $db_name = "ecommerce_db";
    private $username = "root";
    private $password = ""; // En XAMPP por defecto es vacío
    private $charset = "utf8mb4";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Construcción del DSN (Data Source Name)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza errores si hay fallos
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,       // Devuelve los datos como objetos
                PDO::ATTR_EMULATE_PREPARES   => false,                // Mayor seguridad contra SQL Injection
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            // Si hay error, te lo mostrará en pantalla
            die("Error de conexión: " . $exception->getMessage());
        }

        return $this->conn;
    }
}