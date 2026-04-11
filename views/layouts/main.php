<?php
/**
 * ARCHIVO: layout_main.php (Estructura principal REFACTORIZADA)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!defined('BASE_URL')) define('BASE_URL', '/');

$esAdmin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');

if (!function_exists('isActiveAdmin')) {
    function isActiveAdmin($ruta) {
        $current = $_GET['url'] ?? 'admin/dashboard';
        return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
    }
}

$categoriasMenu = $listaCategorias ?? [];

// FIX GLOBAL: Categorías
if (empty($categoriasMenu) && isset($this->db)) {
    try {
        $stmt = $this->db->query("SELECT DISTINCT categoria AS nombre FROM productos WHERE activo = 1 AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
        $categoriasMenu = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) { $categoriasMenu = []; }
}

// 1. INCLUSIÓN DE LA CABECERA Y EL NAVBAR
include __DIR__ . '/header.php';
?>

<div class="d-flex" id="wrapper">

    <?php if ($esAdmin) include __DIR__ . '/sidebar.php'; ?>

    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">

        <div class="sticky-top w-100 bg-white shadow-sm" style="z-index: 1040; top: 0;">
            <?php include __DIR__ . '/navbar.php'; ?>
        </div>

        <main class="container-fluid px-0 flex-grow-1 pt-4 mt-2">
            <?php
            if (isset($content)) {
                echo $content;
            } else {
                echo "<div class='container py-5 text-center'><i class='bi bi-exclamation-circle fs-1 text-muted'></i><p class='mt-3'>No se ha seleccionado contenido para mostrar.</p></div>";
            }
            ?>
        </main>

        <?php include __DIR__ . '/footer.php'; ?>
    </div>
</div>

<?php 
// Cargamos los menús laterales (Categorías, Carrito, Sidebar Admin)
include __DIR__ . '/../componentes/offcanvas.php'; 

// Cargamos los modales (Login, Registro, Checkout, Accesibilidad, etc.)
include __DIR__ . '/../componentes/modales.php'; 

// Cargamos los scripts globales y librerías
include __DIR__ . '/../componentes/scripts_globales.php'; 
?>

</body>
</html>