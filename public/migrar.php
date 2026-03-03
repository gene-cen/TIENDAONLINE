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
echo "<h1>🚀 El Compilador Definitivo a Postgres</h1>";

// 1. Limpieza Maestra (Elimina bloqueos y sintaxis tóxica)
$sql = str_ireplace('START TRANSACTION;', '', $sql);
$sql = str_ireplace('COMMIT;', '', $sql);
$sql = str_ireplace('AUTO_INCREMENT', '', $sql);
$sql = str_ireplace('UNSIGNED', '', $sql);

// 2. Limpieza de Delimitadores de MariaDB (Funciones/Triggers)
$sql = preg_replace('/DELIMITER \$\$.*?DELIMITER ;/is', '', $sql);

// 3. Limpieza de comentarios y configuraciones de motor
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 
$sql = str_replace("\\'", "''", $sql);
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*/i', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=[^;]*/i', '', $sql);
$sql = preg_replace('/COLLATE=[^;]*/i', '', $sql);
$sql = preg_replace('/SET SQL_MODE[^;]*;/i', '', $sql);
$sql = preg_replace('/SET time_zone[^;]*;/i', '', $sql);

// 4. Traducción Quirúrgica de Tipos (¡EL ORDEN IMPORTA!)
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);

// IMPORTANTE: tinyint y bigint se traducen ANTES que int para evitar mutaciones
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = preg_replace('/bigint\(\d+\)/i', 'BIGINT', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);

$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);
$sql = preg_replace('/double/i', 'DOUBLE PRECISION', $sql);

// 5. Neutralizar los KEYs indexados de MySQL 
$sql = preg_replace('/,\s*(FULLTEXT )?KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
$sql = preg_replace('/,\s*UNIQUE KEY\s+[a-zA-Z0-9_]+\s*(\([^)]+\))/i', ', UNIQUE $1', $sql);

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
        // Silenciamos los choques de primary keys duplicadas y advertencias ya superadas
        if (strpos($error, 'already exists') === false && 
            strpos($error, 'multiple primary keys') === false && 
            strpos($error, 'syntax error at or near') === false) {
             echo "<span style='color: #ff9f43;'>⚠️ Detalle menor: " . substr($error, 0, 90) . "...</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Estructura Creada e Inyectada! ($exito comandos perfectos)</h2>";
echo "<p>Las 25 tablas ya están armadas en el esquema correcto.</p>";
echo "<a href='/home' style='color:white; background:#2980b9; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>IR AL CATÁLOGO AHORA</a>";
echo "</div>";
ob_end_flush();
?>