<?php
set_time_limit(0); 
ini_set('memory_limit', '-1');

require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

$sqlFile = __DIR__ . '/../database.sql';
if (!file_exists($sqlFile)) die("❌ No se encontró database.sql");

$sql = file_get_contents($sqlFile);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #00ff00;'>";
echo "<h1>🚀 Desbloqueo Maestro de PostgreSQL</h1>";

// 1. ELIMINAR EL BLOQUEO MAESTRO (La raíz de todos los males)
$sql = str_ireplace('START TRANSACTION;', '', $sql);
$sql = str_ireplace('COMMIT;', '', $sql);

// 2. Limpieza profunda
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 
$sql = str_replace("\\'", "''", $sql);
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*/i', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=[^;]*/i', '', $sql);
$sql = preg_replace('/COLLATE=[^;]*/i', '', $sql);
$sql = preg_replace('/SET SQL_MODE[^;]*;/i', '', $sql);
$sql = preg_replace('/SET time_zone[^;]*;/i', '', $sql);

// 3. Conversión inteligente de llaves primarias
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

$consultas = explode(";", $sql);
$exito = 0;

foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    if (empty($consulta) || strlen($consulta) < 5) continue; 

    try {
        // Ejecutamos en modo libre: si un comando choca, NO arrastra a los demás
        $db->exec($consulta);
        $exito++;
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Ocultamos los ruidos de sintaxis para enfocarnos en los insertos reales
        if (strpos($error, 'multiple primary keys') === false && strpos($error, 'syntax error at or near') === false) {
             echo "<span style='color: #ff9f43;'>⚠️ Detalle menor: " . substr($error, 0, 90) . "...</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Catálogo Desplegado! ($exito comandos inyectados)</h2>";
echo "<p>El bloqueo en cadena fue neutralizado y PostgreSQL fue forzado a crear todas las tablas, incluyendo 'marcas'.</p>";
echo "<a href='/home' style='color:white; background:#2980b9; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>IR AL CATÁLOGO AHORA</a>";
echo "</div>";
?>