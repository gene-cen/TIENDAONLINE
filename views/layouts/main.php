<?php

/**
 * ARCHIVO: layout_main.php (Estructura principal REFACTORIZADA V2)
 * Descripción: Maneja la identidad visual, seguridad por ID, accesibilidad global y Venta Asistida.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) define('BASE_URL', '/');

// 🔥 1. SEGURIDAD: Identificamos si es Administrador por ID (1=Super, 2=Sucursal)
$esAdmin = (isset($_SESSION['rol_id']) && in_array((int)$_SESSION['rol_id'], [1, 2]));

// Helper para clases activas en sidebar
if (!function_exists('isActiveAdmin')) {
    function isActiveAdmin($ruta)
    {
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
        } catch (\Exception $e) {
            $categorias = [];
        }
    }
}
$categoriasMenu = $categorias; // Sincronizamos ambas variables

// 3. CABECERA (Contiene <!DOCTYPE>, <head> y el inicio del <body>)
include __DIR__ . '/header.php';
?>



<div id="wrapper" class="d-flex">
    <?php
    // Sidebar de Admin (Solo si el controlador lo solicita y es admin)
    // 💡 NOTA: Ocultamos el sidebar de admin si estamos en Venta Asistida para dar más espacio al catálogo
    if ($esAdmin && isset($mostrarSidebarAdmin) && $mostrarSidebarAdmin && !isset($_SESSION['modo_venta_asistida'])) {
        include __DIR__ . '/sidebar.php';
    }
    ?>

    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">

        <?php include __DIR__ . '/navbar.php'; ?>

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

        <?php
        // 🔥 Detectamos si es una ruta de administración para ocultar el footer
        $urlActualMain = $_GET['url'] ?? 'home';
        $esRutaAdminMain = (strpos($urlActualMain, 'admin/') === 0 || strpos($urlActualMain, 'empleos/rrhh_') === 0);

        // El footer solo se muestra si NO estamos en el panel de control
        if (!$esRutaAdminMain) {
            include __DIR__ . '/footer.php';
        }
        ?>

    </div>
</div>

<?php
// Menús laterales (Categorías, Carrito, etc.)
include __DIR__ . '/../componentes/offcanvas.php';
include __DIR__ . '/../componentes/modales.php'; // Aquí vive tu botón de accesibilidad
include __DIR__ . '/../componentes/scripts_globales.php';
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Leemos la URL de forma segura
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');

        // 2. Si hay mensaje, lo mostramos
        if (msg) {
            if (msg === 'login_exito') {
                console.log("¡Bienvenido al Centro de Operaciones!");
            }

            // 🔥 ALERTAS DE VENTA ASISTIDA
            if (msg === 'venta_asistida_on') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Modo Caja Activado',
                    text: 'Iniciando venta presencial.',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
            if (msg === 'venta_asistida_off') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Caja Cerrada',
                    text: 'Has salido del modo venta asistida correctamente.',
                    showConfirmButton: false,
                    timer: 3000
                });
            }

            // 3. Limpiamos la URL silenciosamente para que no moleste más
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url);
        }
    });
</script>
</body>

</html>