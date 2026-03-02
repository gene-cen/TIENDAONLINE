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
    die("❌ Error: No se encontró el archivo ecommerce_db.sql");
}

$sql = file_get_contents($sqlFile);

// Limpieza general
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*;/i', ';', $sql);
$sql = preg_replace('/SET SQL_MODE.*?;/i', '', $sql);
$sql = preg_replace('/SET time_zone.*?;/i', '', $sql);
$sql = preg_replace('/SET NAMES.*?;/i', '', $sql);

// Traducción MariaDB -> Postgres
$sql = preg_replace('/int\(\d+\)\s+NOT\s+NULL\s+AUTO_INCREMENT/i', 'SERIAL NOT NULL', $sql);
$sql = str_ireplace('AUTO_INCREMENT', '', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

// CORTE INTELIGENTE Y LIGERO: Punto y coma + Salto de línea
$sql = str_replace("\r\n", "\n", $sql); // Normalizamos saltos de línea
$consultas = explode(";\n", $sql);
$total = count($consultas);

echo "<div style='font-family: sans-serif; padding: 20px; background-color: #f8f9fa;'>";
echo "<h1 style='color: #2c3e50;'>Procesando Base de Datos... ⏳</h1>";
echo "<p>Total de consultas a procesar: <b>$total</b></p>";
echo "<div style='height: 300px; overflow-y: auto; background: #1e1e1e; color: #00ff00; padding: 10px; font-family: monospace;'>";

$exito = true;
$contador = 0;

foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    if (empty($consulta) || strpos($consulta, '/*') === 0 || strpos($consulta, '--') === 0) {
        continue; 
    }

    $contador++;
    try {
        $db->exec($consulta);
        if ($contador % 20 === 0) {
            echo "✅ Procesadas $contador / $total...<br>";
            ob_flush(); flush();
        }
    } catch (PDOException $e) {
        echo "<span style='color: #ff5555;'>⚠️ Error en línea $contador: " . $e->getMessage() . "</span><br>";
        $exito = false;
        ob_flush(); flush();
    }
}

echo "</div>";

if ($exito) {
    echo "<h2 style='color: #27ae60;'>¡Estructura migrada con éxito! 🚀</h2>";
} else {
    echo "<h2 style='color: #e67e22;'>Migración completada con advertencias.</h2>";
    echo "<p>Las tablas ya deberían existir, los errores suelen ser datos específicos incompatibles.</p>";
}

// CORRECCIÓN DEL FATAL ERROR: Usamos un link relativo en lugar de BASE_URL
echo "<br><a href='/home' style='padding: 10px 20px; background: #2980b9; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ir al Catálogo de la Tienda</a>";
echo "</div>";
ob_end_flush();
?>