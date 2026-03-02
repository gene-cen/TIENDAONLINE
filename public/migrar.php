<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

// Traducimos las tablas principales a formato PostgreSQL
$sql_postgres = "
CREATE TABLE IF NOT EXISTS analytics_visitas (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255),
    user_id INTEGER NULL,
    url VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    rol VARCHAR(20) DEFAULT 'cliente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agrega aquí las demás tablas que necesites para el testeo inicial
";

try {
    $db->exec($sql_postgres);
    echo "<h1>¡Éxito total, Gene!</h1>";
    echo "<p>Las tablas base se crearon. Ahora el error de 'analytics_visitas' debería desaparecer.</p>";
    echo "<a href='/home'>Ir a la Tienda</a>";
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}