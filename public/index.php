<?php

/**
 * CENCOCAL S.A. - Front Controller (Router)
 * Refactorizado para Controladores Especialistas
 */

session_start();
date_default_timezone_set('America/Santiago');

// 1. CONFIGURACIÓN DE ERRORES
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. URL BASE DINÁMICA
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false) {
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/');
} else {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', 'http://' . $host . '/tienda-online/public/');
}

// 3. CARGA DE LIBRERÍAS Y HELPERS
require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}
require_once __DIR__ . '/../app/helpers.php';

use App\Config\Database;

// 4. CONEXIÓN A BASE DE DATOS
$database = new Database();
$db = $database->getConnection();

// 5. ANALYTICS TRACKING (Global)
$url = $_GET['url'] ?? 'home';
$url = rtrim($url, '/');

if (strpos($url, 'admin') === false && strpos($url, 'api') === false) {
    require_once __DIR__ . '/../app/Models/Analytics.php';
    $analytics = new \App\Models\Analytics($db);
    $analytics->registrarVisita($url, $_SESSION['user_id'] ?? null);
}

// =========================================================
// 6. RUTAS DINÁMICAS (CAPTURA DE IDs)
// =========================================================

// --- SECCIÓN PEDIDOS ---
if (strpos($url, 'admin/pedido/ver/') === 0) {
    $id = end(explode('/', $url));
    require_once '../app/Controllers/AdminPedidoController.php';
    (new \App\Controllers\AdminPedidoController($db))->verDetalle($id);
    exit();
}

// --- SECCIÓN PRODUCTOS ---
if (strpos($url, 'admin/producto/editar/') === 0) {
    $id = end(explode('/', $url));
    require_once '../app/Controllers/AdminProductoController.php'; // Cambiado al nuevo especialista
    (new \App\Controllers\AdminProductoController($db))->index(); // O la función de edición si la separas
    exit();
}

if (strpos($url, 'admin/producto/eliminar/') === 0) {
    $id = end(explode('/', $url));
    require_once '../app/Controllers/AdminProductoController.php';
    (new \App\Controllers\AdminProductoController($db))->toggleEstadoAjax(); // Reutiliza la lógica de borrado/oculto
    exit();
}

// --- SECCIÓN BANNERS Y MARCAS (Se quedan en AdminController por ahora) ---
if (strpos($url, 'admin/banners/borrar/') === 0) {
    $id = end(explode('/', $url));
    (new \App\Controllers\AdminController($db))->borrarBanner($id);
    exit();
}

if (strpos($url, 'admin/marcas/borrar/') === 0) {
    $id = end(explode('/', $url));
    (new \App\Controllers\AdminController($db))->borrarMarca($id);
    exit();
}

// =========================================================
// 7. ENRUTADOR PRINCIPAL (SWITCH)
// =========================================================

switch ($url) {
    // ----------------------------------------------------
    // 🔐 AUTH & LOGIN
    // ----------------------------------------------------
    case 'auth/login':
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        $error = (new \App\Controllers\AuthController($db))->login();
        include __DIR__ . '/../views/auth/login.php';
        break;

    case 'auth/guestLogin':
        (new \App\Controllers\AuthController($db))->guestLogin();
        break;
    case 'auth/google':
        (new \App\Controllers\AuthController($db))->googleLogin();
        break;
    case 'auth/google-callback':
        (new \App\Controllers\AuthController($db))->googleCallback();
        break;
    case 'auth/logout':
        (new \App\Controllers\AuthController($db))->logout();
        break;
    case 'auth/register':
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        $error = null;
        $success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = (new \App\Controllers\AuthController($db))->register();
            if ($res === true) $success = true;
            else $error = $res;
        }
        include __DIR__ . '/../views/auth/register.php';
        break;

    case 'auth/forgot':
        (new \App\Controllers\AuthController($db))->forgot();
        break;
    case 'auth/reset':
        (new \App\Controllers\AuthController($db))->reset();
        break;

    // ----------------------------------------------------
    // 🏠 TIENDA PÚBLICA (HOME)
    // ----------------------------------------------------
    case 'home':
        (new \App\Controllers\HomeController($db))->index();
        break;
    case 'home/catalogo':
        (new \App\Controllers\HomeController($db))->catalogo();
        break;
    case 'home/producto':
        (new \App\Controllers\HomeController($db))->producto();
        break;
    case 'home/locales':
        (new \App\Controllers\HomeController($db))->locales();
        break;
    case 'home/terminos':
        (new \App\Controllers\HomeController($db))->terminos();
        break;
    case 'home/autocomplete':
        (new \App\Controllers\HomeController($db))->autocomplete();
        break;

    // ----------------------------------------------------
    // 🛒 CARRITO & CHECKOUT
    // ----------------------------------------------------
    case 'carrito/ver':
        (new \App\Controllers\CarritoController($db))->ver();
        break;
    case 'carrito/agregarAjax':
        (new \App\Controllers\CarritoController($db))->agregarAjax();
        break;
    case 'carrito/modificarAjax':
        (new \App\Controllers\CarritoController($db))->modificarAjax();
        break;
    case 'carrito/vaciar':
        (new \App\Controllers\CarritoController($db))->vaciar();
        break;
    case 'carrito/obtenerHtml':
        (new \App\Controllers\CarritoController($db))->obtenerHtml();
        break;

    case 'checkout':
        (new \App\Controllers\CheckoutController($db))->index();
        break;
    case 'checkout/procesar':
        (new \App\Controllers\CheckoutController($db))->procesar();
        break;

    // ----------------------------------------------------
    // 👤 PERFIL DE USUARIO
    // ----------------------------------------------------
    case 'perfil':
        (new \App\Controllers\PerfilController($db))->index();
        break;
    case 'perfil/guardar':
        (new \App\Controllers\PerfilController($db))->guardar();
        break;
    case 'perfil/agregarDireccionAjax':
        (new \App\Controllers\PerfilController($db))->agregarDireccionAjax();
        break;
    case 'perfil/eliminarDireccionAjax':
        (new \App\Controllers\PerfilController($db))->eliminarDireccionAjax();
        break;
    case 'perfil/hacerPrincipalAjax':
        (new \App\Controllers\PerfilController($db))->hacerPrincipalAjax();
        break;

    // 🔥 RUTAS CRÍTICAS PARA EL DETALLE Y TELÉFONOS
    case 'perfil/obtenerDetallePedido':
        (new \App\Controllers\PerfilController($db))->obtenerDetallePedido();
        break;
    case 'perfil/eliminarTelefono':
        (new \App\Controllers\PerfilController($db))->eliminarTelefono();
        break;
    case 'perfil/editarTelefono':
        (new \App\Controllers\PerfilController($db))->editarTelefono();
        break;
    case 'perfil/agregarTelefonoPerfil':
        (new \App\Controllers\PerfilController($db))->agregarTelefonoPerfil();
        break;
    // ----------------------------------------------------
    // 📊 ZONA ADMIN: DASHBOARD & ANALYTICS
    // ----------------------------------------------------
    case 'admin/dashboard':
        (new \App\Controllers\AdminController($db))->dashboard();
        break;
    case 'admin/analytics':
        (new \App\Controllers\AdminController($db))->analytics();
        break;
    // public/index.php
    case 'pedido/exito':
        require_once __DIR__ . '/../app/Controllers/PedidoController.php';
        (new \App\Controllers\PedidoController($db))->exito();
        break;
    case 'admin/buscar_cliente_venta_asistida':
        (new \App\Controllers\AdminController($db))->buscarClienteVentaAsistidaAjax();
        break;
    case 'admin/importar_erp':
        (new \App\Controllers\AdminController($db))->importarERP();
        break;
    case 'admin/usuarios':
        (new \App\Controllers\AdminController($db))->usuarios();
        break;
    case 'admin/usuarios/get':
        (new \App\Controllers\AdminController($db))->obtenerUsuarioAjax();
        break;
    case 'admin/usuarios/update':
        (new \App\Controllers\AdminController($db))->actualizarUsuarioAjax();
        break;


    // public/index.php

    // ... busca donde dice case 'empleos' y actualiza el bloque así:

    // ----------------------------------------------------
    // 💼 RRHH & EMPLEOS
    // ----------------------------------------------------
    case 'empleos':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->index();
        break;

    // 🔥 ESTA ES LA QUE FALTABA PARA POSTULAR
    case 'empleos/postulante':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->postulante();
        break;

    case 'empleos/dashboardRRHH':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->dashboardRRHH();
        break;

    // 🔥 ESTA ES LA QUE FALTABA PARA EL EXCEL
    case 'empleos/exportarExcelRRHH':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->exportarExcelRRHH();
        break;
    // ----------------------------------------------------
    // 📦 ZONA ADMIN: PRODUCTOS (Nuevo Especialista)
    // ----------------------------------------------------
    case 'admin/productos':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->index();
        break;

    case 'admin/productos/ajax':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->index(); // Filtros AJAX
        break;

    case 'admin/productos_nuevos':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->productosNuevos();
        break;

    case 'admin/stock_fantasma':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->stockFantasma();
        break;

    case 'admin/exportarProductosExcel':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->exportarExcel();
        break;

    case 'admin/producto/toggleAjax':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->toggleEstadoAjax();
        break;

    // ----------------------------------------------------
    // 🧾 ZONA ADMIN: PEDIDOS (Nuevo Especialista)
    // ----------------------------------------------------
    case 'admin/pedidos':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->index();
        break;

    case 'admin/pedido/cambiarEstado':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->actualizarEstadoManual();
        break;

    case 'admin/pedido/guardar_edicion':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->guardarEdicion();
        break;

    case 'admin/exportar_pedidos':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->exportar();
        break;

    case 'admin/subirComprobantePago':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->subirComprobante();
        break;

    case 'admin/pedido/capturar_pago':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->capturarPago();
        break;

    case 'admin/pedido/anular_reembolsar':
        require_once '../app/Controllers/AdminPedidoController.php';
        (new \App\Controllers\AdminPedidoController($db))->anularYReembolsar();
        break;

    // ----------------------------------------------------
    // 🖼️ ZONA ADMIN: BANNERS & MARCAS
    // ----------------------------------------------------
    case 'admin/banners':
        (new \App\Controllers\AdminController($db))->banners();
        break;
    case 'admin/banners/guardar':
        (new \App\Controllers\AdminController($db))->guardarBanner();
        break;
    case 'admin/banners/actualizar':
        (new \App\Controllers\AdminController($db))->actualizarBanner();
        break;
    case 'admin/banners/reordenarAjax':
        (new \App\Controllers\AdminController($db))->reordenarBannersAjax();
        break;
    case 'admin/banners/toggleAjax':
        (new \App\Controllers\AdminController($db))->toggleBannerAjax();
        break;

    case 'admin/marcas':
        (new \App\Controllers\AdminController($db))->marcas();
        break;
    case 'admin/marcas/guardar':
        (new \App\Controllers\AdminController($db))->guardarMarca();
        break;
    case 'admin/marcas/actualizar':
        (new \App\Controllers\AdminController($db))->actualizarMarca();
        break;

    // ----------------------------------------------------
    // 💼 RRHH & EMPLEOS
    // ----------------------------------------------------
    case 'empleos':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->index();
        break;
    case 'empleos/dashboardRRHH':
        require_once __DIR__ . '/../app/Controllers/EmpleosController.php';
        (new \App\Controllers\EmpleosController($db))->dashboardRRHH();
        break;

    // ----------------------------------------------------
    // 🚚 LOGÍSTICA & TRANSPORTE
    // ----------------------------------------------------
    case 'transporte/misEntregas':
        require_once __DIR__ . '/../app/Controllers/TransporteController.php';
        (new \App\Controllers\TransporteController($db))->misEntregas();
        break;
    case 'transporte/finalizarEntrega':
        require_once __DIR__ . '/../app/Controllers/TransporteController.php';
        (new \App\Controllers\TransporteController($db))->finalizarEntrega();
        break;

    // ----------------------------------------------------
    // 🗺️ GEOLOCALIZACIÓN
    // ----------------------------------------------------
    case 'location/actualizar':
        (new \App\Controllers\LocationController($db))->actualizar();
        break;

    case 'location/detectar':
        (new \App\Controllers\LocationController($db))->detectar();
        break;

    // 🔥 ESTA ES LA QUE FALTABA:
    case 'location/actualizar_por_nombre':
        (new \App\Controllers\LocationController($db))->actualizar_por_nombre();
        break;

    // ----------------------------------------------------
    // 💳 WEBPAY (TRANSBANK)
    // ----------------------------------------------------
    case 'webpay/iniciar':
        (new \App\Controllers\WebpayController($db))->iniciar($_GET['id'] ?? null);
        break;
    case 'webpay/confirmar':
        (new \App\Controllers\WebpayController($db))->confirmar();
        break;

    // ----------------------------------------------------
    // 🗺️ GEOLOCALIZACIÓN
    // ----------------------------------------------------
    case 'location/actualizar':
        (new \App\Controllers\LocationController($db))->actualizar();
        break;
    case 'location/detectar':
        (new \App\Controllers\LocationController($db))->detectar();
        break;

        // public/index.php

// ... dentro del switch, en la sección de banners ...

    case 'admin/banners':
        (new \App\Controllers\AdminController($db))->banners();
        break;

    // 🔥 ESTA ES LA QUE FALTA PARA EL BUSCADOR DE PRODUCTOS EN BANNERS
    case 'banners/buscarParaBannerAjax':
        (new \App\Controllers\AdminController($db))->buscarParaBannerAjax();
        break;

    case 'admin/banners/guardar':
        (new \App\Controllers\AdminController($db))->guardarBanner();
        break;

// 🔥 RUTA FALTANTE PARA BORRAR BANNERS CON AJAX
    case 'admin/banners/borrarAjax':
        (new \App\Controllers\AdminController($db))->borrarBannerAjax();
        break;
    // ----------------------------------------------------
    // 🖼️ ZONA ADMIN: BANNERS & MARCAS
    // ----------------------------------------------------
    case 'admin/banners':
        (new \App\Controllers\AdminController($db))->banners();
        break;

    // 🔥 ACTUALIZADO: Agregamos el prefijo 'admin/' para que coincida con banners.js
    case 'admin/banners/buscarParaBannerAjax':
        (new \App\Controllers\AdminController($db))->buscarParaBannerAjax();
        break;

    case 'admin/banners/guardar':
        (new \App\Controllers\AdminController($db))->guardarBanner();
        break;

    // ----------------------------------------------------
    // 🚨 404 NOT FOUND
    // ----------------------------------------------------
    default:
        http_response_code(404);
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
        echo "<h1 style='color:#e63946; font-size:50px;'>404</h1>";
        echo "<h2>Página no encontrada</h2><p>Ruta: " . htmlspecialchars($url) . "</p>";
        echo "<a href='" . BASE_URL . "home' style='color:#283593; font-weight:bold;'>Volver al inicio</a>";
        echo "</div>";
        break;
}

