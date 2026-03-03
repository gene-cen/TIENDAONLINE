<?php
set_time_limit(0); 
ini_set('memory_limit', '-1');
if (ob_get_level() == 0) ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

$sqlFile = __DIR__ . '/../database.sql';
if (!file_exists($sqlFile)) die("❌ No se encontró database.sql");

$sql = file_get_contents($sqlFile);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #00ff00;'>";
echo "<h1>🚀 Despliegue de Base de Datos Postgres</h1>";

// 1. Limpieza base
$sql = str_ireplace('START TRANSACTION;', '', $sql);
$sql = str_ireplace('COMMIT;', '', $sql);
$sql = str_ireplace('AUTO_INCREMENT', '', $sql);
$sql = str_ireplace('UNSIGNED', '', $sql);
$sql = preg_replace('/DELIMITER \$\$.*?DELIMITER ;/is', '', $sql);
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 
$sql = str_replace("\\'", "''", $sql);
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*/i', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=[^;]*/i', '', $sql);
$sql = preg_replace('/COLLATE=[^;]*/i', '', $sql);
$sql = preg_replace('/SET SQL_MODE[^;]*;/i', '', $sql);
$sql = preg_replace('/SET time_zone[^;]*;/i', '', $sql);

// 2. Transformación de Tipos
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = preg_replace('/tinyinteger/i', 'SMALLINT', $sql); 
$sql = preg_replace('/tinyint/i', 'SMALLINT', $sql);
$sql = preg_replace('/bigint\(\d+\)/i', 'BIGINT', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);
$sql = preg_replace('/double/i', 'DOUBLE PRECISION', $sql);

// 3. LA MAGIA: Borrar las llaves redundantes y sintaxis de MariaDB
// Quitamos la PRIMARY KEY redundante del final (porque ya forzamos SERIAL PRIMARY KEY arriba)
$sql = preg_replace('/,\s*PRIMARY KEY\s*\([^)]+\)/i', '', $sql);
// Quitamos los KEY e INDEX simples que confunden a Postgres
$sql = preg_replace('/,\s*(UNIQUE )?(KEY|INDEX)\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
// Quitamos los CONSTRAINT redundantes dentro de CREATE TABLE
$sql = preg_replace('/,\s*CONSTRAINT\s+[a-zA-Z0-9_]+\s+FOREIGN KEY\s*\([^)]+\)\s*REFERENCES\s+[a-zA-Z0-9_]+\s*\([^)]+\)(\s*ON DELETE CASCADE)?/i', '', $sql);

// 4. Ejecución segura
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
        // Ahora SÍ, como la tabla se creó, solo silenciamos alertas si intentamos crearla de nuevo
        if (strpos($error, 'already exists') === false) {
             echo "<span style='color: #ff9f43;'>⚠️ Detalle: " . substr($error, 0, 110) . "...</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Estructura Creada y Datos Inyectados! ($exito comandos)</h2>";
echo "<a href='/home' style='color:white; background:#2980b9; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>IR AL CATÁLOGO AHORA</a>";
echo "</div>";
ob_end_flush();
?>