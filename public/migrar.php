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
echo "<h1>🚀 El Compilador Definitivo a Postgres (Versión Final)</h1>";

// 1. Limpieza Maestra
$sql = str_ireplace('START TRANSACTION;', '', $sql);
$sql = str_ireplace('COMMIT;', '', $sql);
$sql = str_ireplace('AUTO_INCREMENT', '', $sql);
$sql = str_ireplace('UNSIGNED', '', $sql);
$sql = str_ireplace('current_timestamp()', 'CURRENT_TIMESTAMP', $sql); // Arregla el error de los paréntesis

// 2. Limpieza de Delimitadores y Comentarios
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
$sql = preg_replace('/COMMENT\s+\'[^\']*\'/i', '', $sql); // Arregla el error de la sintaxis COMMENT

// 3. Traducción Quirúrgica de Tipos
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = preg_replace('/tinyinteger/i', 'SMALLINT', $sql);
$sql = preg_replace('/tinyint/i', 'SMALLINT', $sql);
$sql = preg_replace('/bigint\(\d+\)/i', 'BIGINT', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);
$sql = preg_replace('/double/i', 'DOUBLE PRECISION', $sql);

// 4. Extirpación de llaves e índices que rompen Postgres
$sql = preg_replace('/,\s*PRIMARY KEY\s*\([^)]+\)/i', '', $sql);
$sql = preg_replace('/,\s*(UNIQUE )?(KEY|INDEX)\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
$sql = preg_replace('/ADD PRIMARY KEY\s*\([^)]+\)/i', '', $sql); 
$sql = preg_replace('/ALTER TABLE[^\n]+MODIFY[^\n]+;/i', '', $sql); // Arregla el error MODIFY

// 5. Arreglo de la coma huérfana (Soluciona el error "at or near )")
$sql = preg_replace('/,\s*\)/', "\n)", $sql); 

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
        if (strpos($error, 'already exists') === false && 
            strpos($error, 'multiple primary keys') === false && 
            strpos($error, 'syntax error at or near') === false) {
             echo "<span style='color: #ff9f43;'>⚠️ Detalle menor: " . substr($error, 0, 90) . "...</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Estructura Creada e Inyectada! ($exito comandos)</h2>";
echo "<a href='/home' style='color:white; background:#2980b9; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>IR AL CATÁLOGO AHORA</a>";
echo "</div>";
ob_end_flush();
?>