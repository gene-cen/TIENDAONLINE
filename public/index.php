<?php

/**
 * CENCOCAL S.A. - Front Controller (Router)
 * Versión Limpia, Consolidada y con Venta Asistida
 */

// =========================================================
// 🔥 FIX WEBPAY: CONFIGURACIÓN DE COOKIES SAMESITE
// =========================================================
$esSeguro = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);

if ($esSeguro) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None'
    ]);
}

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

// =========================================================
// 5. ANALYTICS TRACKING
// =========================================================
$url = $_GET['url'] ?? 'home';
$url = rtrim($url, '/');

if (strpos($url, 'admin') === false && strpos($url, 'api') === false && strpos($url, 'location/') === false) {
    require_once __DIR__ . '/../app/Models/Analytics.php';
    $analytics = new \App\Models\Analytics($db);

    $usuarioId = $_SESSION['user_id'] ?? null;
    $comunaId  = $_SESSION['comuna_id'] ?? 29;
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $agente    = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $analytics->registrarVisita($usuarioId, $comunaId, $url, $ip, $agente);
}

// =========================================================
// 6. RUTAS DINÁMICAS Y SLUGS (SEO Friendly)
// =========================================================
$url_parts = explode('/', $url);

if (strpos($url, 'buscar/') === 0) {
    $slug = end($url_parts);
    $_GET['q'] = urldecode(str_replace('-', ' ', $slug));
    (new \App\Controllers\HomeController($db))->catalogo();
    exit();
}

if (strpos($url, 'categoria/') === 0) {
    $slug = end($url_parts);
    $_GET['categoria'] = urldecode(str_replace('-', ' ', $slug));
    (new \App\Controllers\HomeController($db))->catalogo();
    exit();
}

if (strpos($url, 'coleccion/') === 0) {
    $slug = end($url_parts);
    $_GET['coleccion'] = urldecode(str_replace('-', ' ', $slug));
    (new \App\Controllers\HomeController($db))->catalogo();
    exit();
}

// --- SLUGS ADMIN ---
if (strpos($url, 'admin/pedido/ver/') === 0) {
    $id = end($url_parts);
    require_once '../app/Controllers/AdminPedidoController.php';
    (new \App\Controllers\AdminPedidoController($db))->verDetalle($id);
    exit();
}

if (strpos($url, 'admin/producto/editar/') === 0) {
    $id = end($url_parts);
    require_once '../app/Controllers/AdminProductoController.php';
    (new \App\Controllers\AdminProductoController($db))->editar($id);
    exit();
}

if (strpos($url, 'admin/producto/eliminar/') === 0) {
    $id = end($url_parts);
    require_once '../app/Controllers/AdminProductoController.php';
    (new \App\Controllers\AdminProductoController($db))->eliminar($id);
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
    case 'auth/send-recovery':
        (new \App\Controllers\AuthController($db))->sendRecovery();
        break;
    case 'auth/reset':
        (new \App\Controllers\AuthController($db))->reset();
        break;
    case 'auth/check-duplicate':
        (new \App\Controllers\AuthController($db))->checkDuplicate();
        break;
    case 'intranet':
        (new \App\Controllers\AuthController($db))->intranetLogin();
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
    // 👤 PERFIL DE USUARIO
    // ----------------------------------------------------
    case 'perfil':
        (new \App\Controllers\PerfilController($db))->index();
        break;
    case 'perfil/guardar':
        (new \App\Controllers\PerfilController($db))->guardar();
        break;
    case 'perfil/cambiarPassword':
        (new \App\Controllers\PerfilController($db))->cambiarPassword();
        break;
    case 'perfil/obtenerComunas':
        (new \App\Controllers\PerfilController($db))->obtenerComunas();
        break;
    case 'perfil/obtenerDireccionPorId':
        (new \App\Controllers\PerfilController($db))->obtenerDireccionPorId();
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
    // 💳 PAGOS WEBPAY
    // ----------------------------------------------------
    case 'webpay/iniciar':
        (new \App\Controllers\WebpayController($db))->iniciar($_GET['id'] ?? null);
        break;
    case 'webpay/confirmar':
        (new \App\Controllers\WebpayController($db))->confirmar();
        break;
    case 'pedido/exito':
        require_once __DIR__ . '/../app/Controllers/PedidoController.php';
        (new \App\Controllers\PedidoController($db))->exito();
        break;

    // ----------------------------------------------------
    // 📊 ZONA ADMIN: DASHBOARD, SUCURSALES & VENTA ASISTIDA
    // ----------------------------------------------------
    case 'admin/dashboard':
        (new \App\Controllers\AdminController($db))->dashboard();
        break;
    case 'admin/analytics':
        (new \App\Controllers\AdminController($db))->analytics();
        break;
    case 'admin/importar_erp':
        (new \App\Controllers\AdminController($db))->importarERP();
        break;
    case 'admin/cambiar_sucursal':
        (new \App\Controllers\AdminController($db))->cambiarSucursalAjax();
        break;
    case 'admin/buscar_reemplazo':
        require_once __DIR__ . '/../app/Controllers/AdminController.php';
        (new \App\Controllers\AdminController($db))->buscarReemplazoAjax();
        break;

    case 'admin/usuarios/get':
        (new \App\Controllers\AdminController($db))->getUsuario();
        break;

    case 'pedido/detalle':
        (new \App\Controllers\PedidoController($db))->detalle();
        break;

    case 'admin/pedidos/subir_comprobante':
        $controller = new \App\Controllers\AdminPedidoController($db);
        $controller->subirComprobante();
        break;


    case 'admin/usuarios/filtrar':
        (new \App\Controllers\AdminController($db))->filtrarUsuarios();
        break;

    case 'admin/usuarios/update':
        (new \App\Controllers\AdminController($db))->actualizarUsuario();
        break;

    case 'admin/usuarios/crear_colaborador':
        (new \App\Controllers\AdminController($db))->crearColaborador();
        break;

    // 🔥 RUTAS DE VENTA ASISTIDA
    case 'admin/iniciarVentaAsistida':
        (new \App\Controllers\AdminController($db))->iniciarVentaAsistida();
        break;
    case 'admin/salirVentaAsistida':
        (new \App\Controllers\AdminController($db))->salirVentaAsistida();
        break;
    case 'admin/buscar_cliente_venta_asistida':
        (new \App\Controllers\AdminController($db))->buscarClienteVentaAsistidaAjax();
        break;
    case 'admin/ventas_sucursal':
        (new \App\Controllers\AdminController($db))->ventas_sucursal();
        break;

    // ----------------------------------------------------
    // 👥 ADMIN: USUARIOS
    // ----------------------------------------------------
    case 'admin/usuarios':
        (new \App\Controllers\AdminController($db))->usuarios();
        break;
    case 'admin/usuarios/get':
        (new \App\Controllers\AdminController($db))->obtenerUsuarioAjax();
        break;
    case 'admin/usuarios/update':
        (new \App\Controllers\AdminController($db))->actualizarUsuarioAjax();
        break;

    // ----------------------------------------------------
    // 📦 ADMIN: PRODUCTOS
    // ----------------------------------------------------
    case 'admin/productos':
        require_once '../app/Controllers/AdminProductoController.php';
        (new \App\Controllers\AdminProductoController($db))->index();
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
        (new \App\Controllers\AdminProductoController($db))->toggleAjax();
        break;

    // ----------------------------------------------------
    // 🧾 ADMIN: PEDIDOS
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
    // 🖼️ ADMIN: BANNERS & MARCAS
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
    case 'admin/banners/borrarAjax':
        (new \App\Controllers\AdminController($db))->borrarBannerAjax();
        break;
    case 'admin/banners/reordenarAjax':
        (new \App\Controllers\AdminController($db))->reordenarBannersAjax();
        break;
    case 'admin/banners/toggleAjax':
        (new \App\Controllers\AdminController($db))->toggleBannerAjax();
        break;
    case 'admin/banners/buscarParaBannerAjax':
        (new \App\Controllers\AdminController($db))->buscarParaBannerAjax();
        break;
    case 'admin/banners/cargarProductosPorCodigosAjax':
        (new \App\Controllers\AdminController($db))->cargarProductosPorCodigosAjax();
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
    case 'admin/marcas/borrarAjax':
        (new \App\Controllers\AdminController($db))->borrarMarcaAjax();
        break;
    case 'admin/marcas/reordenarAjax':
        (new \App\Controllers\AdminController($db))->reordenarMarcasAjax();
        break;

    // ----------------------------------------------------
    // 👔 EMPLEOS Y RECURSOS HUMANOS
    // ----------------------------------------------------
    case 'empleos':
    case 'empleos/index':
        (new \App\Controllers\EmpleosController($db))->index();
        break;
    case 'empleos/postulante':
        (new \App\Controllers\EmpleosController($db))->postulante();
        break;
    case 'empleos/guardar':
        (new \App\Controllers\EmpleosController($db))->guardar();
        break;
    case 'empleos/getCargosAjax':
        (new \App\Controllers\EmpleosController($db))->getCargosAjax();
        break;
    case 'empleos/rrhh_dashboard':
        (new \App\Controllers\EmpleosController($db))->rrhh_dashboard();
        break;
    case 'empleos/rrhh_reclutamiento':
        (new \App\Controllers\EmpleosController($db))->rrhh_reclutamiento();
        break;
    case 'empleos/rrhh_mantenedor':
        (new \App\Controllers\EmpleosController($db))->rrhh_mantenedor();
        break;
    case 'empleos/guardarCargo':
        (new \App\Controllers\EmpleosController($db))->guardarCargo();
        break;
    case 'empleos/toggleCargoAjax':
        (new \App\Controllers\EmpleosController($db))->toggleCargoAjax();
        break;
    case 'empleos/exportarExcelRRHH':
        (new \App\Controllers\EmpleosController($db))->exportarExcelRRHH();
        break;
    case 'empleos/cambiarEstado':
        (new \App\Controllers\EmpleosController($db))->cambiarEstado();
        break;

    case 'home/rastrearPedido':
        $controller = new \App\Controllers\HomeController($db);
        $controller->rastrearPedido();
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
    // 🗺️ LOCALIZACIÓN
    // ----------------------------------------------------
    case 'location/actualizar':
        (new \App\Controllers\LocationController($db))->actualizar();
        break;
    case 'location/detectar':
        (new \App\Controllers\LocationController($db))->detectar();
        break;
    case 'location/actualizar_por_nombre':
        (new \App\Controllers\LocationController($db))->actualizar_por_nombre();
        break;
    case 'location/guardarGPSAjax':
        (new \App\Controllers\LocationController($db))->guardarGPSAjax();
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
