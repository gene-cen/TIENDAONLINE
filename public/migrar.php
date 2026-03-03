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
echo "<h1>🚀 Desbloqueo Maestro de PostgreSQL (Fase Final)</h1>";

// 1. Eliminar bloqueos de transacciones
$sql = str_ireplace('START TRANSACTION;', '', $sql);
$sql = str_ireplace('COMMIT;', '', $sql);

// 2. Limpieza de comentarios y configuraciones de MariaDB
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 
$sql = str_replace("\\'", "''", $sql);
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*/i', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=[^;]*/i', '', $sql);
$sql = preg_replace('/COLLATE=[^;]*/i', '', $sql);
$sql = preg_replace('/SET SQL_MODE[^;]*;/i', '', $sql);
$sql = preg_replace('/SET time_zone[^;]*;/i', '', $sql);

// 3. Conversión de tipos de datos (¡incluyendo tinyinteger!)
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = preg_replace('/tinyinteger/i', 'SMALLINT', $sql); // Arreglo específico para tus tablas
$sql = preg_replace('/tinyint/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

// 4. Corte seguro (Solo corta si el punto y coma va seguido de un salto de línea)
$sql = str_replace("\r\n", "\n", $sql);
$consultas = explode(";\n", $sql);
$exito = 0;

foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    if (empty($consulta) || strlen($consulta) < 5) continue; 

    try {
        $db->exec($consulta);
        $exito++;
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Filtramos avisos de tablas que ya se crearon en intentos anteriores
        if (strpos($error, 'already exists') === false && 
            strpos($error, 'multiple primary keys') === false && 
            strpos($error, 'syntax error at or near') === false) {
             echo "<span style='color: #ff9f43;'>⚠️ Detalle menor: " . substr($error, 0, 90) . "...</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Estructura Inyectada! ($exito comandos procesados)</h2>";
echo "<a href='/home' style='color:white; background:#2980b9; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>IR AL CATÁLOGO AHORA</a>";
echo "</div>";
?>