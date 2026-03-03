<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

try {
    $db = (new Database())->getConnection();
    // Consultamos directamente al motor de Postgres qué tablas existen
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<div style='font-family: sans-serif; padding: 20px;'>";
    echo "<h2 style='color: #2c3e50;'>Radiografía de Base de Datos en Render</h2>";
    echo "<p>Total de tablas existentes: <strong>" . count($tablas) . " / 25</strong></p>";
    
    echo "<ul style='background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 5px solid #3498db; list-style-type: none;'>";
    foreach ($tablas as $i => $tabla) {
        echo "<li style='margin-bottom: 5px;'>✅ " . ($i+1) . ". <b>$tabla</b></li>";
    }
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>