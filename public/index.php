<?php
// 1. Iniciar sesión es obligatorio para el Login
session_start();

// 2. Configuración de errores (Desactivar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. Definir la URL base
// Asegúrate de que termine con /public/ si tu index está dentro de esa carpeta
define('BASE_URL', 'http://localhost/tienda-online/public/');

// 4. Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Controllers\AuthController;

// 5. Inicializar conexión a Base de Datos
$database = new Database();
$db = $database->getConnection();

// 6. Instanciar controladores necesarios
$authController = new AuthController($db);

// 7. Obtener la URL amigable (.htaccess debe estar configurado)
$url = $_GET['url'] ?? 'auth/login'; // Por defecto mandamos al login
$url = rtrim($url, '/');
$urlParts = explode('/', $url);

// 8. SISTEMA DE RUTEO MVC
switch ($url) {
    // --- RUTAS DE AUTENTICACIÓN ---
    case 'auth/login':
        // Si ya está logueado, mandar al home
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        // Procesa el login (si es POST) o muestra el formulario
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
        session_destroy();
        header("Location: " . BASE_URL . "auth/login");
        exit();
        break;

    case 'auth/login':
    if (isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "home"); exit(); }
    $authController->login();  // <--- OJO AQUÍ
    include __DIR__ . '/../views/auth/login.php';
    break;

    // --- RUTA PRINCIPAL (TIENDA) ---
    case 'home':
        // Verificar seguridad: Solo usuarios logueados
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        // Preparamos el contenido dinámico para el Layout
        // Aquí eventualmente instanciarás un 'ProductController'
        ob_start(); // Iniciamos buffer para capturar el HTML
        ?>
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2 class="text-primary">Hola, <?= htmlspecialchars($_SESSION['user_nombre']) ?> 👋</h2>
                <p class="text-muted">Bienvenido a la tienda oficial de CENCOCAL S.A.</p>
            </div>
            <div class="col-md-4 text-end">
                <img src="<?= BASE_URL ?>assets/img/cencocalin.png" alt="Mascota" height="80">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    Aquí cargaremos tus productos pronto.
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean(); // Guardamos el HTML en $content
        
        // Cargar el Layout Maestro
        include __DIR__ . '/../views/layouts/main.php'; 
        break;

    // --- RUTAS NO ENCONTRADAS ---
    default:
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
        echo "<a href='" . BASE_URL . "home'>Volver al inicio</a>";
        break;
}