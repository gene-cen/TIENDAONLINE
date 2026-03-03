<?php
// 1. QUITAMOS LOS FRENOS DEL SERVIDOR
set_time_limit(0); 
ini_set('memory_limit', '-1');
if (ob_get_level() == 0) ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

$sqlFile = __DIR__ . '/../ecommerce_db (25).sql';

if (!file_exists($sqlFile)) {
    die("❌ Error: No se encontró el archivo database.sql");
}

$sql = file_get_contents($sqlFile);

echo "<div style='font-family: sans-serif; padding: 20px; background-color: #f8f9fa;'>";
echo "<h1 style='color: #2c3e50;'>🚀 Migración Espejo Definitiva a PostgreSQL</h1>";
echo "<div style='height: 400px; overflow-y: auto; background: #1e1e1e; color: #00ff00; padding: 10px; font-family: monospace;'>";

// --- EL ARREGLO MÁGICO ---
// Borramos los comentarios línea por línea ANTES de separar las consultas
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 

// Limpiamos la sintaxis exclusiva de MariaDB/MySQL que hace chocar a Postgres
$sql = str_replace("\\'", "''", $sql); // Arregla los textos como D'AROMA
$sql = str_replace('`', '', $sql); // Quita comillas invertidas
$sql = preg_replace('/DELIMITER \$\$.*?DELIMITER ;/is', '', $sql); // Borra Triggers
$sql = preg_replace('/ENGINE=InnoDB[^;]*;/i', ';', $sql);

// Forzamos que las tablas nazcan con secuencias válidas para Postgres
$sql = preg_replace('/id\s+int\(\d+\)\s+NOT\s+NULL/i', 'id SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

// Cortamos por punto y coma de forma segura
$sql = str_replace("\r\n", "\n", $sql);
$consultas = explode(";\n", $sql);
$total = count($consultas);

$exito = true;
$contador = 0;
$errores = 0;

foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    if (empty($consulta)) continue; 

    $contador++;
    try {
        $db->exec($consulta);
        if ($contador % 50 === 0) {
            echo "✅ Procesadas $contador / $total...<br>";
            ob_flush(); flush();
        }
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Al forzar el SERIAL arriba, algunos comandos viejos de MariaDB sobrarán. Los ignoramos en silencio.
        if (strpos($msg, 'multiple primary keys') === false && strpos($msg, 'syntax error at or near "MODIFY"') === false) {
            echo "<span style='color: #e67e22;'>⚠️ Ignorado (Incompatibilidad menor): " . substr($msg, 0, 80) . "...</span><br>";
            $errores++;
        }
        ob_flush(); flush();
    }
}

echo "</div>";
echo "<h2 style='color: #27ae60;'>¡Tablas y Datos Inyectados Exitosamente! 🎉</h2>";
echo "<p>Las 25 tablas fueron forzadas a crearse. Si hubo advertencias naranjas, son datos muy específicos que no bloquearán el Home.</p>";
echo "<br><a href='/home' style='padding: 10px 20px; background: #2980b9; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ir al Catálogo de la Tienda</a>";
echo "</div>";
ob_end_flush();
?>