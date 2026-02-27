<?php
session_start();
date_default_timezone_set('America/Santiago');
// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// URL base
define('BASE_URL', 'http://localhost/tienda-online/public/');

// Cargar librerías
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../app/helpers.php';

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\HomeController;

// Conexión a Base de Datos
$database = new Database();
$db = $database->getConnection();

// Analytics Tracking (Ignorar Admin)
if (!isset($_GET['url']) || strpos($_GET['url'], 'admin') === false) {
    require_once __DIR__ . '/../app/Models/Analytics.php';
    $analytics = new \App\Models\Analytics($db);
    $urlActual = $_GET['url'] ?? 'home';
    $userId = $_SESSION['user_id'] ?? null;
    $analytics->registrarVisita($urlActual, $userId);
}

// Instancia de Controladores
$authController = new AuthController($db);
$perfilController = new \App\Controllers\PerfilController($db);
$carritoController = new \App\Controllers\CarritoController($db);
$checkoutController = new \App\Controllers\CheckoutController($db);
$adminController = new \App\Controllers\AdminController($db); // Usamos este para todo lo admin
$homeController = new \App\Controllers\HomeController($db);
$webpayController = new \App\Controllers\WebpayController($db);
// Router Simple
$url = $_GET['url'] ?? 'auth/login';
$url = rtrim($url, '/');

// =========================================================
// 1. RUTAS DINÁMICAS (Con parámetros ID al final)
// =========================================================

// Ver Pedido
if (strpos($url, 'admin/pedido/ver/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->verDetalle($id);
    exit();
}

// Editar Producto
if (strpos($url, 'admin/producto/editar/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->editarProducto($id);
    exit();
}

// Eliminar Producto
if (strpos($url, 'admin/producto/eliminar/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->eliminarProducto($id);
    exit();
}

// Toggle Producto
if (strpos($url, 'admin/producto/toggle/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->toggleProducto($id);
    exit();
}

// Eliminar Banner
if (strpos($url, 'admin/banners/borrar/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->borrarBanner($id);
    exit();
}

if (strpos($url, 'admin/marcas/borrar/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->borrarMarca($id); // Debes crear esta función similar a borrarBanner
    exit();
}

// =========================================================
// 2. RUTAS ESTÁTICAS (Switch)
// =========================================================

switch ($url) {
    // --- LOGIN & AUTH ---
    case 'auth/login':
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        $error = $authController->login();
        include __DIR__ . '/../views/auth/login.php';
        break;

    case 'auth/google':
        $authController->googleLogin();
        break;

    case 'auth/google-callback':
        $authController->googleCallback();
        break;

    case 'auth/logout':
        $authController->logout();
        break;

    case 'auth/register':
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        $error = null;
        $success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $authController->register();
            if ($resultado === true) {
                $success = true;
            } else {
                $error = $resultado;
            }
        }
        include __DIR__ . '/../views/auth/register.php';
        break;

    case 'auth/verificar':
        $authController->verificar();
        break;

    case 'auth/consultar_sii':
        $authController->consultarSii();
        break;

    case 'auth/geolocalizar':
        $authController->geolocalizar();
        break;

    // Recuperación pass
    case 'auth/forgot':
        $authController->forgot();
        break;
    case 'auth/send-recovery':
        $authController->sendRecovery();
        break;
    case 'auth/reset':
        $authController->reset();
        break;
    case 'auth/update-password':
        $authController->updatePassword();
        break;


    // --- HOME (TIENDA PÚBLICA) ---
    case 'home':
        $homeController->index();
        break;
    case 'home/catalogo':
        $homeController->catalogo();
        break;
    case 'home/buscar':
        $homeController->buscar();
        break;
    case 'home/autocomplete':
        $homeController->autocomplete();
        break;
    case 'home/locales':
        $homeController->locales();
        break;
    case 'home/terminos':
        $homeController->terminos();
        break;
    case 'home/producto':
        $homeController->producto();
        break;
    case 'home/catalogo_chorizo': // Test visual
        $homeController->catalogoVisual();
        break;


    // --- PERFIL DE USUARIO ---
    case 'perfil':
        $perfilController->index();
        break;
    case 'perfil/guardar':
        $perfilController->guardar();
        break;
    case 'perfil/cambiarPassword':
        $perfilController->cambiarPassword();
        break;
    case 'perfil/agregarDireccion':
        $perfilController->agregarDireccion();
        break;
    case 'perfil/obtenerComunas':
        $perfilController->obtenerComunas();
        break;
    case 'perfil/eliminarDireccion':
        $perfilController->eliminarDireccion();
        break;
    case 'perfil/hacerPrincipal':
        $perfilController->hacerPrincipal();
        break;
    case 'perfil/obtenerDireccionPorId':
        $perfilController->obtenerDireccionPorId();
        break;
    case 'perfil/actualizarDireccion':
        $perfilController->actualizarDireccion();
        break;
    case 'perfil/obtenerDetallePedido':
        $perfilController->obtenerDetallePedido();
        break;

    case 'perfil/agregarDireccionAjax':
        $perfilController->agregarDireccionAjax();
        break;

    // ... rutas anteriores ...
    case 'perfil/agregarDireccionAjax':
        $perfilController->agregarDireccionAjax();
        break;

    // --- AGREGA ESTO AQUÍ ---
    case 'perfil/eliminarDireccionAjax':
        $perfilController->eliminarDireccionAjax();
        break;

    case 'perfil/hacerPrincipalAjax':
        $perfilController->hacerPrincipalAjax();
        break;
    // ------------------------

    // --- CARRITO ---
    case 'carrito/agregar':
        $carritoController->agregar();
        break;
    case 'carrito/vaciar':
        $carritoController->vaciar();
        break;
    case 'carrito/ver':
        $carritoController->ver();
        break;
    case 'carrito/eliminar':
        $carritoController->eliminar();
        break;
    case 'carrito/subir':
        $carritoController->subir();
        break;
    case 'carrito/bajar':
        $carritoController->bajar();
        break;
    case 'carrito/agregarAjax':
        $carritoController->agregarAjax();
        break;
    case 'carrito/obtenerHtml':
        $carritoController->obtenerHtml();
        break;
    case 'carrito/modificarAjax':
        $carritoController->modificarAjax();
        break;


    // --- CHECKOUT ---
    case 'checkout':
        $checkoutController->index();
        break;
    case 'checkout/procesar':
        $checkoutController->procesar();
        break;


    // =====================================================
    // ZONA ADMIN
    // =====================================================

    // Dashboard & Analytics
    case 'admin/dashboard':
        $adminController->dashboard();
        break;
    case 'admin/analytics':
        $adminController->analytics();
        break;

    // Pedidos
    case 'admin/exportar_pedidos':
        $adminController->exportar();
        break;
    case 'admin/pedido/cambiarEstado':
        $adminController->cambiarEstado();
        break;

    // Productos
    case 'admin/productos':
        $adminController->productos();
        break;

    // --- ESTA ES LA RUTA QUE FALTABA PARA EL BUSCADOR ---
    case 'admin/productos/ajax':
        $adminController->buscarProductosAjax();
        break;
    // ----------------------------------------------------

    case 'admin/importar_erp':
        $adminController->importarERP();
        break;
    case 'admin/producto/crear':
        $adminController->crearProducto();
        break;
    case 'admin/producto/guardar':
        $adminController->guardarProducto();
        break;


    // --- ANALYTICS (Registro de eventos JS) ---
    case 'analytics/registrar-evento':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../app/Models/Analytics.php';
            // Usamos la variable $db que ya definimos arriba
            $analyticsModel = new \App\Models\Analytics($db);
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data) {
                $analyticsModel->registrarEvento($data['tipo'], $data['etiqueta']);
            }
            exit();
        }
        break;

    // ... dentro del switch de admin ...

    case 'admin/producto/toggleAjax':
        // AQUI ESTABA EL ERROR: Antes decia $controller->..., debe ser $adminController->...
        $adminController->toggleProductoAjax();
        break;

    case 'admin/pedidos':
        $adminController->pedidos();
        break;

    // En public/index.php busca la sección de Perfil y añade:
    case 'perfil/eliminarTelefono':
        $controller = new App\Controllers\PerfilController($db);
        $controller->eliminarTelefono();
        break;

    case 'perfil/editarTelefono':
        $controller = new App\Controllers\PerfilController($db);
        $controller->editarTelefono();
        break;

    // En public/index.php
    case 'perfil/agregarTelefonoPerfil':
        $controller = new App\Controllers\PerfilController($db);
        $controller->agregarTelefonoPerfil();
        break;

    // webpay
    case 'webpay/pagar':
        $webpayController->iniciar($_GET['id'] ?? null);
        exit(); // Agrega este exit
        break;

    case 'webpay/confirmar':
        $webpayController->confirmar();
        break;

    // Banners (Mantenedor)
    case 'admin/banners':
        $adminController->banners();
        break;
    case 'admin/banners/guardar':
        $adminController->guardarBanner();
        break;
    case 'admin/banners/borrar': // Solo por si lo necesitas atajar por switch
        // (Ya lo tienes en la parte de arriba con strpos, así que esto es opcional)
        break;

    // --- AÑADE ESTAS DOS LÍNEAS NUEVAS ---
    case 'admin/banners/actualizar':
        $adminController->actualizarBanner();
        break;
    case 'admin/banners/toggleAjax':
        $adminController->toggleBannerAjax();
        break;

    case 'admin/marcas':
        $adminController->marcasDestacadas();
        break;
    case 'admin/marcas/guardar':
        $adminController->guardarMarcaDestacada();
        break;

    case 'admin/marcas/actualizar':
        $adminController->actualizarMarcaDestacada();
        break;

    // Rutas de Ubicación Geográfica
    case 'location/actualizar':
        $locController = new \App\Controllers\LocationController($db);
        $locController->actualizar();
        break;

    case 'location/detectar':
        $locController = new \App\Controllers\LocationController($db);
        $locController->detectar();
        break;

    case 'location/actualizar_por_nombre':
        $locController = new \App\Controllers\LocationController($db);
        $locController->actualizar_por_nombre();
        break;


    // --- 404 NOT FOUND ---
    default:
        http_response_code(404);
        // Puedes crear una vista bonita para el 404
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
        echo "<h1 style='color:#e63946; font-size:50px;'>404</h1>";
        echo "<h2>Página no encontrada</h2>";
        echo "<p>Ruta: " . htmlspecialchars($url) . "</p>";
        echo "<a href='" . BASE_URL . "home' style='color:#283593; font-weight:bold;'>Volver al inicio</a>";
        echo "</div>";
        break;
}
