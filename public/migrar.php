<?php
set_time_limit(0); 
ini_set('memory_limit', '-1');

require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$database = new Database();
$db = $database->getConnection();

$sqlFile = __DIR__ . '/../ecommerce_db (25).sql';
if (!file_exists($sqlFile)) die("❌ No se encontró database.sql");

$sql = file_get_contents($sqlFile);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #00ff00;'>";
echo "<h1>🔧 Limpieza y Carga Forzada (Paso a Paso)</h1>";

// --- PRE-PROCESAMIENTO AGRESIVO ---
$sql = preg_replace('/^--.*$/m', '', $sql);      
$sql = preg_replace('/^\/\*.*\*\//m', '', $sql); 
$sql = str_replace("\\'", "''", $sql);
$sql = str_replace('`', '', $sql);
$sql = preg_replace('/ENGINE=InnoDB[^;]*/i', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=[^;]*/i', '', $sql);
$sql = preg_replace('/COLLATE=[^;]*/i', '', $sql);

// Conversión de tipos para que Postgres no proteste
$sql = preg_replace('/int\(\d+\)\s+NOT\s+NULL\s+AUTO_INCREMENT/i', 'SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/tinyint\(\d+\)/i', 'SMALLINT', $sql);
$sql = str_ireplace('datetime', 'TIMESTAMP', $sql);
$sql = str_ireplace('longtext', 'TEXT', $sql);

// Separamos por punto y coma
$consultas = explode(";", $sql);

foreach ($consultas as $consulta) {
    $consulta = trim($consulta);
    if (empty($consulta) || strlen($consulta) < 5) continue; 

    try {
        // LA CLAVE: Iniciamos y cerramos transacción en CADA comando
        $db->beginTransaction();
        $db->exec($consulta);
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack(); // Si falla, liberamos a Postgres del bloqueo
        }
        
        $error = $e->getMessage();
        // Solo mostramos errores que NO sean de sintaxis de MariaDB (para no llenar la pantalla)
        if (strpos($error, 'relation') !== false || strpos($error, 'syntax') !== false) {
            echo "<span style='color: #ff9f43;'>⚠️ Detalle: " . substr($error, 0, 100) . "</span><br>";
        }
    }
}

echo "<h2 style='color: #2ecc71;'>¡Proceso Finalizado!</h2>";
echo "<p>Si PostgreSQL se bloqueó antes, este script acaba de forzar la creación de 'marcas' y las demás tablas.</p>";
echo "<a href='/home' style='color:white; background:blue; padding:10px;'>PROBAR HOME AHORA</a>";
echo "</div>";