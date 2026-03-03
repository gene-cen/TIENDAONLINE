<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$db = (new Database())->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS log_accesos (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER,
    ip_address VARCHAR(100),
    user_agent TEXT,
    exito SMALLINT DEFAULT 0,
    fecha_acceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($sql);
    echo "<h2 style='color: green;'>¡Tabla log_accesos creada con éxito! Ya puedes iniciar sesión.</h2>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>