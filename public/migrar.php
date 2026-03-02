<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

// Apuntamos al archivo de respaldo actualizado
$sqlFile = __DIR__ . '/../ecommerce_db (25).sql';

if (!file_exists($sqlFile)) {
    die("❌ Error: No se encontró el archivo ecommerce_db (25).sql en la raíz.");
}

// Leemos todo el archivo de MariaDB
$sql = file_get_contents($sqlFile);

// =========================================================================
// 🚀 TRADUCTOR AUTOMÁTICO: MARIADB -> POSTGRESQL
// =========================================================================

// 1. Quitamos las comillas invertidas (Postgres no las soporta)
$sql = str_replace('`', '', $sql);

// 2. Limpiamos configuraciones exclusivas de MySQL (ENGINE, CHARSET, etc.)
$sql = preg_replace('/ENGINE=InnoDB[^;]*;/i', ';', $sql);
$sql = preg_replace('/SET SQL_MODE.*?;/i', '', $sql);
$sql = preg_replace('/SET time_zone.*?;/i', '', $sql);
$sql = preg_replace('/SET NAMES.*?;/i', '', $sql);

// 3. Traducimos los tipos de datos principales
$sql = preg_replace('/int\(\d+\)\s+NOT\s+NULL\s+AUTO_INCREMENT/i', 'SERIAL NOT NULL', $sql);
$sql = str_ireplace('AUTO_INCREMENT', '', $sql); // Limpieza residual
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

// Separamos el archivo por cada consulta individual
$consultas = preg_split("/;+(?=(?:(?:[^']*'){2})*[^']*$)/", $sql);
echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Iniciando Migración Espejo a PostgreSQL... 🚀</h1>";

$exito = true;

// Ejecutamos línea por línea
foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    
    // Ignoramos comentarios y líneas vacías
    if (empty($consulta) || strpos($consulta, '/*') === 0 || strpos($consulta, '--') === 0) {
        continue; 
    }

    try {
        $db->exec($consulta);
    } catch (PDOException $e) {
        // Si PostgreSQL detecta un comando que no logramos traducir, lo atrapamos aquí
        echo "<div style='background: #ffebee; padding: 10px; margin-bottom: 10px; border-left: 4px solid #f44336;'>";
        echo "<p><b>⚠️ Detalle de sintaxis en:</b> <br><code>" . htmlspecialchars(substr($consulta, 0, 150)) . "...</code></p>";
        echo "<p><b>Error de Postgres:</b> " . $e->getMessage() . "</p>";
        echo "</div>";
        $exito = false;
    }
}

if ($exito) {
    echo "<h2 style='color: #4CAF50;'>¡Migración 100% completada! Tu base en Render es un espejo exacto.</h2>";
    echo "<a href='" . BASE_URL . "home' style='padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Ir al Catálogo</a>";
} else {
    echo "<h2>Proceso terminado, pero con algunos detalles de traducción.</h2>";
    echo "<p>Pásame los errores del recuadro rojo y te doy la solución exacta para esos campos.</p>";
}
echo "</div>";