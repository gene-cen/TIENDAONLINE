<?php
session_start();
date_default_timezone_set('America/Santiago');
// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir URL Base
define('BASE_URL', 'http://localhost/tienda-online/public/');

// Cargar librerías
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\HomeController;

// Conexión a Base de Datos
$database = new Database();
$db = $database->getConnection();

// Instancia de Controladores
$authController = new AuthController($db);
$perfilController = new \App\Controllers\PerfilController($db);
$carritoController = new \App\Controllers\CarritoController();
$checkoutController = new \App\Controllers\CheckoutController($db);
$adminController = new \App\Controllers\AdminController($db);
$homeController = new \App\Controllers\HomeController($db);

// Router Simple
$url = $_GET['url'] ?? 'auth/login';
$url = rtrim($url, '/');

// =========================================================
// 1. RUTAS DINÁMICAS (Con parámetros ID)
// =========================================================
// IMPORTANTE: Estas van SIEMPRE FUERA del switch

// Ver Pedido
if (strpos($url, 'admin/pedido/ver/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->verDetalle($id);
    exit();
}

// --- GESTIÓN DE PRODUCTOS (IDs) ---

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

// Toggle (Activar/Desactivar) - AQUÍ ESTABA TU ERROR
if (strpos($url, 'admin/producto/toggle/') === 0) {
    $partes = explode('/', $url);
    $id = end($partes);
    $adminController->toggleProducto($id);
    exit();
}

// =========================================================
// 2. RUTAS ESTÁTICAS (Switch normal)
// =========================================================

switch ($url) {
    // --- LOGIN ---
    case 'auth/login':
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        $error = $authController->login();
        include __DIR__ . '/../views/auth/login.php';
        break;

    // --- GOOGLE ---
    case 'auth/google':
        $authController->googleLogin();
        break;

    case 'auth/google-callback':
        $authController->googleCallback();
        break;

    // --- LOGOUT ---
    case 'auth/logout':
        session_destroy();
        header("Location: " . BASE_URL . "auth/login");
        exit();
        break;

    // --- HOME (TIENDA PÚBLICA) ---
    case 'home':
        $productoModel = new \App\Models\Producto($db);
        $productos = $productoModel->obtenerDisponibles(); // Solo activos
        ob_start();
        include __DIR__ . '/../views/home.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
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

    // --- VERIFICACIÓN DE EMAIL ---
    case 'auth/verificar':
        $authController->verificar();
        break;

    // --- PERFIL DE USUARIO ---
    case 'perfil':
        $perfilController->index();
        break;

    case 'perfil/guardar':
        $perfilController->guardar();
        break;

    // --- CARRITO DE COMPRAS ---
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

    // --- CHECKOUT (CAJA) ---
    case 'checkout':
        $checkoutController->index();
        break;

    case 'checkout/procesar':
        $checkoutController->procesar();
        break;

    // --- RECUPERACIÓN DE CONTRASEÑA ---
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

    // =====================================================
    // ZONA ADMIN
    // =====================================================

    // Dashboard General
    case 'admin/dashboard':
        $adminController->dashboard();
        break;

    // Exportar Pedidos (Excel/JSON)
    case 'admin/exportar_pedidos':
        $adminController->exportar();
        break;

    // Cambiar estado de Pedido
    case 'admin/pedido/cambiar_estado':
        $adminController->cambiarEstado();
        break;

    // --- GESTIÓN DE PRODUCTOS ---

    // 1. Listar Productos (Esta te faltaba)
    case 'admin/productos':
        $adminController->productos();
        break;

    // 2. Importar desde ERP (CSV)
    case 'admin/importar_erp':
        $adminController->importarERP();
        break;

    // 3. Crear Producto Manual
    case 'admin/producto/crear':
        $adminController->crearProducto();
        break;

    // 4. Guardar Producto (Sirve para Crear y Editar)
    case 'admin/producto/guardar':
        $adminController->guardarProducto();
        break;

        // Ejemplo en tu router
        if ($url_parts[0] === 'categoria' && isset($url_parts[1])) {
            $controller = new HomeController($db);
            $controller->categoria($url_parts[1]);
        }

        // --- 404 ---
    default:
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
        echo "<p>Ruta buscada: " . htmlspecialchars($url) . "</p>";
        echo "<a href='" . BASE_URL . "home'>Volver</a>";
        break;
}
