<?php
/**
 * ARCHIVO: layout_main.php (Estructura principal REFACTORIZADA V2)
 * Descripción: Maneja la identidad visual, seguridad por ID y accesibilidad global.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!defined('BASE_URL')) define('BASE_URL', '/');

// 🔥 1. SEGURIDAD: Identificamos si es Administrador por ID (1=Super, 2=Sucursal)
$esAdmin = (isset($_SESSION['rol_id']) && in_array((int)$_SESSION['rol_id'], [1, 2]));

// Helper para clases activas en sidebar
if (!function_exists('isActiveAdmin')) {
    function isActiveAdmin($ruta) {
        $current = $_GET['url'] ?? 'admin/dashboard';
        return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
    }
}

// 🔥 2. CATEGORÍAS: Unificamos la fuente de datos para Navbar y Catálogo
if (!isset($categorias) || empty($categorias)) {
    if (isset($this->db)) {
        try {
            $stmt = $this->db->query("SELECT DISTINCT categoria AS nombre FROM productos WHERE activo = 1 AND categoria IS NOT NULL ORDER BY categoria ASC");
            $categorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) { $categorias = []; }
    }
}
$categoriasMenu = $categorias; // Sincronizamos ambas variables

// 3. CABECERA (Contiene <!DOCTYPE>, <head> y el inicio del <body>)
include __DIR__ . '/header.php';
?>

<div id="wrapper" class="d-flex">

    <?php 
    // Sidebar de Admin (Solo si el controlador lo solicita y es admin)
    if ($esAdmin && isset($mostrarSidebarAdmin) && $mostrarSidebarAdmin) {
        include __DIR__ . '/sidebar.php'; 
    }
    ?>

    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">

        <div class="sticky-top w-100 bg-white shadow-sm" style="z-index: 1040; top: 0;">
            <?php include __DIR__ . '/navbar.php'; ?>
        </div>

        <main class="container-fluid px-0 flex-grow-1 pt-4 mt-2">
            <?php
            if (isset($content)) {
                echo $content;
            } else {
                echo "<div class='container py-5 text-center'>
                        <i class='bi bi-exclamation-circle fs-1 text-muted'></i>
                        <p class='mt-3 fw-bold text-muted'>No se ha seleccionado contenido para mostrar.</p>
                      </div>";
            }
            ?>
        </main>

        <?php include __DIR__ . '/footer.php'; ?>
        
    </div> </div> <?php 
// Menús laterales (Categorías, Carrito, etc.)
include __DIR__ . '/../componentes/offcanvas.php'; 

// Todos los Modales (Login, Accesibilidad, etc.)
include __DIR__ . '/../componentes/modales.php'; 
?>

<svg style="display:none" aria-hidden="true">
    <defs>
        <filter id="protanopia">
            <feColorMatrix type="matrix" values="0.567,0.433,0,0,0 0.558,0.442,0,0,0 0,0.242,0.758,0,0 0,0,0,1,0"/>
        </filter>
        <filter id="deuteranopia">
            <feColorMatrix type="matrix" values="0.625,0.375,0,0,0 0.7,0.3,0,0,0 0,0.3,0.7,0,0 0,0,0,1,0"/>
        </filter>
        <filter id="tritanopia">
            <feColorMatrix type="matrix" values="0.95,0.05,0,0,0 0,0.433,0.567,0,0 0,0.475,0.525,0,0 0,0,0,1,0"/>
        </filter>
    </defs>
</svg>

<?php include __DIR__ . '/../componentes/scripts_globales.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.AccessManager) {
            window.AccessManager.init();
        }
    });
</script>

</body>
</html>