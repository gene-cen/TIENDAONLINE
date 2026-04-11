<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Services\MailService;
use Google\Client as GoogleClient;
use PDOException;
use Exception;

/**
 * ARCHIVO: AuthController.php
 * Descripción: Maneja el ciclo de vida de la identidad del usuario (Login, Registro, Recuperación y Utilidades externas).
 */
class AuthController
{
    private $userModel;
    private $googleClient;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new Usuario($db);
        $this->initGoogleClient();
    }

    /**
     * Configuración del cliente de Google Oauth2
     */
    private function initGoogleClient()
    {
        $this->googleClient = new GoogleClient();
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID');
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET');

        $this->googleClient->setClientId($clientId);
        $this->googleClient->setClientSecret($clientSecret);
        $this->googleClient->setRedirectUri(BASE_URL . "auth/google-callback");
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    // =========================================================
    // 🚪 SECCIÓN 1: LOGIN TRADICIONAL Y GOOGLE
    // =========================================================

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $redirect = $_GET['redirect'] ?? 'home';

            $user = $this->userModel->login($email, $password);

            if ($user) {
                $this->setSession($user);
                header("Location: " . BASE_URL . $redirect . "?msg=login_exito");
            } else {
                header("Location: " . BASE_URL . "home?msg=login_error");
            }
            exit();
        }
    }

    public function googleLogin()
    {
        header("Location: " . $this->googleClient->createAuthUrl());
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
                // Registro automático: Password aleatoria para usuarios de Google
                $userData = [
                    'nombre'    => $data->name,
                    'email'     => $data->email,
                    'password'  => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                    'rol'       => 'cliente',
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

    /**
     * Cierre de sesión completo (Limpieza de cookies y server-side)
     */
    public function logout()
    {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: " . BASE_URL . "home?msg=logout_exito");
        exit();
    }

    // =========================================================
    // 📝 SECCIÓN 2: REGISTRO Y VERIFICACIÓN
    // =========================================================

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            
            // Validación de Duplicidad
            if ($this->userModel->getByEmail($email)) {
                header("Location: " . BASE_URL . "home?msg=error_email_duplicado");
                exit();
            }

            // Limpieza de teléfono (Formato Chileno E.164)
            $telRaw = str_replace(['+569', ' ', '+'], '', $_POST['telefono'] ?? '');
            
            $datos = [
                'nombre'    => $_POST['nombre'] ?? '',
                'rut'       => $_POST['rut'] ?? '',
                'email'     => $email,
                'telefono'  => "+569" . $telRaw,
                'password'  => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                'direccion' => $_POST['direccion'] ?? '',
                'giro'      => $_POST['giro'] ?? '',
                'latitud'   => $_POST['latitud'] ?? null,
                'longitud'  => $_POST['longitud'] ?? null,
                'rol'       => 'cliente'
            ];

            $resultado = $this->userModel->crear($datos);

            if ($resultado['id']) {
                $mailService = new MailService();
                $enviado = $mailService->enviarVerificacion($email, $datos['nombre'], $resultado['token']);
                
                $msg = $enviado ? "registro_exito" : "registro_exito_sin_correo";
                header("Location: " . BASE_URL . "home?msg=" . $msg);
            } else {
                header("Location: " . BASE_URL . "home?msg=error_db");
            }
            exit();
        }
    }

    public function verificar()
    {
        $token = $_GET['token'] ?? null;
        if (!$token) { header("Location: " . BASE_URL . "home"); exit(); }

        $usuario = $this->userModel->getByToken($token);
        if ($usuario) {
            // Auto-login tras verificar correo
            $this->setSession($usuario);
            $this->userModel->activarCuenta($token);
            header("Location: " . BASE_URL . "home?msg=cuenta_activada");
        } else {
            header("Location: " . BASE_URL . "auth/login?msg=token_invalido");
        }
        exit();
    }

    // =========================================================
    // 🔑 SECCIÓN 3: RECUPERACIÓN DE CONTRASEÑA
    // =========================================================

    public function forgot() { include __DIR__ . '/../../views/auth/forgot.php'; }

    public function sendRecovery()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $token = $this->userModel->generarTokenRecuperacion($email);
            if ($token) {
                (new MailService())->enviarRecuperacion($email, $token);
            }
            header("Location: " . BASE_URL . "home?msg=recuperacion_enviada");
            exit();
        }
    }

    public function reset()
    {
        $token = $_GET['token'] ?? '';
        if (!$this->userModel->getByResetToken($token)) {
            die("El enlace es inválido o expiró. <a href='" . BASE_URL . "auth/forgot'>Reintentar</a>");
        }
        include __DIR__ . '/../../views/auth/reset.php';
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'];
            $user = $this->userModel->getByResetToken($token);
            if ($user && $this->userModel->actualizarPassword($user->id, $_POST['password'])) {
                header("Location: " . BASE_URL . "home?msg=pass_actualizada");
            } else {
                echo "Error en la actualización.";
            }
            exit();
        }
    }

    // =========================================================
    // 🕵️ SECCIÓN 4: UTILIDADES EXTERNAS (SII Y MAPAS)
    // =========================================================

    public function consultarSii()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $rut = preg_replace('/[^0-9kK]/', '', $_GET['rut'] ?? '');
        if (strlen($rut) < 8) {
            echo json_encode(['success' => false, 'message' => 'RUT inválido']);
            exit;
        }

        // Intento de consulta API Real
        $ch = curl_init("https://api.libreapi.cl/rut/activities?rut=" . $rut);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 2]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $res) {
            $data = json_decode($res, true);
            if (isset($data['data'])) {
                echo json_encode(['success' => true, 'razon_social' => $data['data']['name'], 'giro' => $data['data']['activities'][0]['activity_name'] ?? '', 'origen' => 'API']);
                exit;
            }
        }

        // Fallback: Modo Simulado o Offline
        $nombre = (strpos($rut, '96547640') !== false) ? "CENCOCAL S.A." : "EMPRESA GENÉRICA S.A.";
        echo json_encode(['success' => true, 'razon_social' => $nombre, 'giro' => 'VENTA DE INSUMOS', 'origen' => 'OFFLINE']);
        exit;
    }

    public function geolocalizar()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $direccion = $_GET['direccion'] ?? '';
        if (strlen($direccion) < 4) { echo json_encode([]); exit; }

        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($direccion) . "&countrycodes=cl&limit=1";
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERAGENT => "CencocalWeb/1.0", CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 3]);
        $res = curl_exec($ch);
        curl_close($ch);
        echo $res ?: json_encode([]);
        exit;
    }

    // =========================================================
    // 🛒 SECCIÓN 5: INVITADOS Y SESIONES
    // =========================================================

    public function guestLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tel = str_replace(['+569', ' ', '+'], '', $_POST['guest_telefono'] ?? '');
            $_SESSION['invitado'] = [
                'nombre'   => trim($_POST['guest_nombre'] ?? 'Invitado'),
                'email'    => trim($_POST['guest_email'] ?? ''),
                'rut'      => trim($_POST['guest_rut'] ?? ''),
                'telefono' => "+569" . $tel
            ];
            session_write_close(); 
            header("Location: " . BASE_URL . "checkout");
            exit();
        }
    }

    private function setSession($user)
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['rol'] = $user->rol;

        if ($user->rol === 'admin') {
            $_SESSION['admin_sucursal'] = $user->sucursal_admin_id ?? null;
        }

        // Lógica de sucursal basada en comuna
        $suc_asignada = 29; // La Calera por defecto
        if (!empty($user->comuna_id)) {
            try {
                $stmt = $this->db->prepare("SELECT sucursal_id FROM comunas WHERE id = ?");
                $stmt->execute([$user->comuna_id]);
                $found = $stmt->fetchColumn();
                if ($found) $suc_asignada = $found;
            } catch (PDOException $e) { error_log("Error sucursal sesión: " . $e->getMessage()); }
        }
        $_SESSION['sucursal_activa'] = $suc_asignada;
    }
}