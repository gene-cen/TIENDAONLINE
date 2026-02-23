<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Services\MailService; // <--- IMPORTANTE: Importamos el servicio de correo
use Google\Client as GoogleClient;

class AuthController
{
    private $userModel;
    private $googleClient;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new Usuario($db);

        // Configuramos el cliente de Google
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri(BASE_URL . "auth/google-callback");
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    // Login tradicional
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->login($email, $password);

            if ($user) {
                // Login Exitoso
                $this->setSession($user);
                header("Location: " . BASE_URL . "home?msg=login_exito");
                exit();
            } else {
                // Login Fallido: Redirigimos al home con el error
                header("Location: " . BASE_URL . "home?msg=login_error");
                exit();
            }
        }
    }

    // Login con Google
    public function googleLogin()
    {
        $authUrl = $this->googleClient->createAuthUrl();
        header("Location: " . $authUrl);
        exit();
    }

    public function googleCallback()
    {
        if (isset($_GET['code'])) {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
            $this->googleClient->setAccessToken($token);

            $googleService = new \Google\Service\Oauth2($this->googleClient);
            $data = $googleService->userinfo->get();

            $user = $this->userModel->getByEmail($data->email);

            if (!$user) {
                // Registro automático por Google
                $userData = [
                    'nombre' => $data->name,
                    'email' => $data->email,
                    'password' => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                    'rol' => 'cliente',
                    'google_id' => $data->id
                ];
                $userId = $this->userModel->crear($userData);
                $user = $this->userModel->getById($userId);
            }

            $this->setSession($user);
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }

    private function setSession($user)
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_rol'] = $user->rol;
    }

    // REGISTRO DE USUARIO (Actualizado con Email Real)
    // REGISTRO DE USUARIO (Adaptado para Modales)
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 1. Recolección y limpieza de datos
            $nombre    = $_POST['nombre'] ?? '';
            $rut       = $_POST['rut'] ?? '';
            $email     = $_POST['email'] ?? '';
            // Limpiamos el teléfono para guardar solo números o formato E.164
            $telefono_raw = str_replace(['+569', ' ', '+'], '', $_POST['telefono'] ?? '');
            $telefono  = "+569" . $telefono_raw;
            $password  = $_POST['password'] ?? '';

            // 2. Datos adicionales
            $direccion = $_POST['direccion'] ?? '';
            $giro      = $_POST['giro'] ?? '';
            $latitud   = $_POST['latitud'] ?? null;
            $longitud  = $_POST['longitud'] ?? null;

            // --- VALIDACIÓN: Si el correo ya existe ---
            if ($this->userModel->getByEmail($email)) {
                // Redirigimos con un mensaje de error específico
                header("Location: " . BASE_URL . "home?msg=error_email_duplicado");
                exit();
            }

            $datos = [
                'nombre'    => $nombre,
                'rut'       => $rut,
                'email'     => $email,
                'telefono'  => $telefono,
                'password'  => password_hash($password, PASSWORD_DEFAULT),
                'direccion' => $direccion,
                'giro'      => $giro,
                'latitud'   => $latitud,
                'longitud'  => $longitud,
                'rol'       => 'cliente'
            ];

            // 3. Guardar en BD
            $resultado = $this->userModel->crear($datos);

            if ($resultado['id']) {
                // 4. ENVÍO DE CORREO REAL
                $mailService = new MailService();
                $enviado = $mailService->enviarVerificacion($email, $nombre, $resultado['token']);

                if ($enviado) {
                    // ÉXITO TOTAL: Redirigimos para mostrar a Cencocalín celebrando
                    header("Location: " . BASE_URL . "home?msg=registro_exito");
                    exit();
                } else {
                    // Se creó el usuario pero falló el correo (Caso borde)
                    header("Location: " . BASE_URL . "home?msg=registro_exito_sin_correo");
                    exit();
                }
            } else {
                // Fallo en la Base de Datos
                header("Location: " . BASE_URL . "home?msg=error_db");
                exit();
            }
        }
    }

    public function verificar()
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 1. Primero buscamos al usuario por el token para obtener sus datos
        $usuario = $this->userModel->getByToken($token);

        if ($usuario) {

            // 2. AUTO-LOGIN: Iniciamos la sesión manualmente
            $_SESSION['user_id'] = $usuario->id;
            $_SESSION['user_nombre'] = $usuario->nombre;
            $_SESSION['user_rol'] = $usuario->rol;

            // 3. Activamos la cuenta y QUEMAMOS el token (Ya no servirá más)
            $this->userModel->activarCuenta($token);

            // 4. Redirigimos al Home con el mensaje para el Modal de Cencocalín
            header("Location: " . BASE_URL . "home?msg=cuenta_activada");
            exit();
        } else {
            // Si el token no existe (porque ya se usó o es falso)
            // Lo enviamos al login con un aviso, obligándolo a ingresar credenciales
            header("Location: " . BASE_URL . "auth/login?msg=token_invalido");
            exit();
        }
    }

    public function forgot()
    {
        include __DIR__ . '/../../views/auth/forgot.php';
    }

    // RECUPERACIÓN DE CONTRASEÑA (Actualizado con Email Real)
    public function sendRecovery()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $token = $this->userModel->generarTokenRecuperacion($email);

            if ($token) {
                // ENVÍO DE CORREO REAL
                $mailService = new MailService();
                $mailService->enviarRecuperacion($email, $token);
            }

            // Siempre mostramos el mismo mensaje por seguridad
            header("Location: " . BASE_URL . "home?msg=recuperacion_enviada");

            exit();
        }
    }

    public function reset()
    {
        $token = $_GET['token'] ?? '';
        $usuario = $this->userModel->getByResetToken($token);

        if (!$usuario) {
            die("El enlace es inválido o ha expirado. <a href='" . BASE_URL . "auth/forgot'>Intenta de nuevo</a>");
        }

        include __DIR__ . '/../../views/auth/reset.php';
    }
    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'];
            $password = $_POST['password'];

            $usuario = $this->userModel->getByResetToken($token);

            if ($usuario && $this->userModel->actualizarPassword($usuario->id, $password)) {
                // --- CAMBIO AQUÍ ---
                // Antes: Redirigía a auth/login
                // Ahora: Redirige a home con el mensaje 'pass_actualizada'
                header("Location: " . BASE_URL . "home?msg=pass_actualizada");
                exit();
            } else {
                echo "Error al actualizar.";
            }
        }
    }

    public function consultarSii()
    {
        // ... (Tu código existente para SII) ...
        // Para ahorrar espacio aquí no lo repito, pero déjalo tal cual en tu archivo final
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        // ... (Copia y pega la lógica de SII que ya tenías) ...
        // SI LA NECESITAS COMPLETA, PÍDEMELA Y TE PEGO EL ARCHIVO ENTERO DE NUEVO
        $rut = $_GET['rut'] ?? '';
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 8) {
            echo json_encode(['success' => false, 'message' => 'RUT inválido']);
            exit;
        }

        $apiUrl = "https://api.libreapi.cl/rut/activities?rut=" . $rut;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['data'])) {
                $info = $data['data'];
                $giro = isset($info['activities'][0]['activity_name']) ? $info['activities'][0]['activity_name'] : '';

                echo json_encode([
                    'success' => true,
                    'razon_social' => $info['name'],
                    'giro' => $giro,
                    'direccion' => '',
                    'origen' => 'ONLINE (API REAL)'
                ]);
                exit;
            }
        }

        $nombre = "EMPRESA GENÉRICA S.A.";
        $giro = "VENTA DE INSUMOS AUTOMOTRICES";

        if (strpos($rut, '96547640') !== false || strpos($rut, '70251100') !== false) {
            $nombre = "CENCOCAL S.A.";
            $giro = "DISTRIBUIDORA DE REPUESTOS Y LUBRICANTES";
        }

        echo json_encode([
            'success' => true,
            'razon_social' => $nombre,
            'giro' => $giro,
            'direccion' => "Av. Principal 1234 (Dato Simulado Local)",
            'message' => '⚠️ Modo Offline: Tu red bloquea la API, usando datos internos.',
            'origen' => 'OFFLINE'
        ]);
        exit;
    }

    public function geolocalizar()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $direccion = $_GET['direccion'] ?? '';

        if (strlen($direccion) < 4) {
            echo json_encode([]);
            exit;
        }

        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($direccion) . "&countrycodes=cl&limit=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "CencocalWeb/1.0 (contacto@cencocal.cl)");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            echo $response;
        } else {
            echo json_encode([]);
        }
        exit;
    }

    // En controllers/AuthController.php

    public function logout()
    {
        // 1. Limpiar sesión
        $_SESSION = array();

        // 2. Borrar cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 3. Destruir
        session_destroy();

        // 4. REDIRECCIÓN CORRECTA -> HOME con mensaje
        header("Location: " . BASE_URL . "home?msg=logout_exito");
        exit();
    }
}
