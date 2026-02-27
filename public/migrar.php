<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

// Leemos el archivo SQL que subiste
$sqlFile = __DIR__ . '/../ecommerce_db (24).sql';

if (!file_exists($sqlFile)) {
    die("Error: No se encuentra el archivo ecommerce_db (24).sql en la raíz.");
}

$sql = file_get_contents($sqlFile);

try {
    // Ejecutamos el SQL directamente
    $db->exec($sql);
    echo "¡Éxito! Las tablas se crearon correctamente en Render.";
} catch (PDOException $e) {
    echo "Error al migrar: " . $e->getMessage();
}