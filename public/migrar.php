<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

$sql_postgres = "
-- 1. Analytics
CREATE TABLE IF NOT EXISTS analytics_visitas (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_id INT DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics_eventos (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_id INT DEFAULT NULL,
    tipo_evento VARCHAR(50) NOT NULL,
    etiqueta VARCHAR(255),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255),
    telefono VARCHAR(20),
    rut VARCHAR(20),
    rol VARCHAR(20) DEFAULT 'cliente',
    verify_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255),
    reset_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    auth_provider VARCHAR(50) DEFAULT 'local',
    google_id VARCHAR(255),
    avatar_url VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Categorías
CREATE TABLE IF NOT EXISTS categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    imagen_url VARCHAR(255),
    activa BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Productos
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2),
    stock INT NOT NULL DEFAULT 0,
    imagen_url VARCHAR(255),
    categoria_id INT,
    marca VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    destacado BOOLEAN DEFAULT FALSE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Banners
CREATE TABLE IF NOT EXISTS banners (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(100),
    imagen_url VARCHAR(255) NOT NULL,
    enlace VARCHAR(255),
    orden INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Marcas Destacadas
CREATE TABLE IF NOT EXISTS marcas_destacadas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen_url VARCHAR(255) NOT NULL,
    enlace VARCHAR(255),
    orden INT DEFAULT 0,
    activa BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $db->exec($sql_postgres);
    echo "<h1>¡Migración Exitosa, Gene! 🚀</h1>";
    echo "<p>Las tablas principales (Analytics, Usuarios, Productos, Categorías, Banners y Marcas) han sido creadas en PostgreSQL.</p>";
    echo "<p>El Home de tu tienda ya debería cargar sin problemas de base de datos.</p>";
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}