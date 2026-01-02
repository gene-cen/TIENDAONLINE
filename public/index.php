<?php

define('BASE_URL', 'http://localhost/tienda-online/');
// Desactivar temporalmente el cache de errores para ver cambios reales
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Cargar el autoloader (aquí es donde VS Code se queja, pero PHP debería funcionar)
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

echo "<h1>Estado del Sistema</h1>";

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    if($connection) {
        echo "<p style='color:green;'>✅ Autoload de Composer: Funcionando.</p>";
        echo "<p style='color:green;'>✅ Conexión a MariaDB: Exitosa.</p>";
        
        // Probamos si existe la tabla de cupones que creamos
        $query = $connection->query("SHOW TABLES LIKE 'cupones'");
        if($query->rowCount() > 0) {
            echo "<p style='color:blue;'>ℹ️ Tabla de cupones detectada correctamente.</p>";
        }
    }
} catch (\Exception $e) {
    echo "<p style='color:red;'>❌ Error crítico: " . $e->getMessage() . "</p>";
}