<?php

namespace App\Controllers;

use App\Models\Usuario;
use Google\Client as GoogleClient; // Importamos la librería de Google

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
        $this->googleClient->setClientId('TU_CLIENT_ID.apps.googleusercontent.com');
        $this->googleClient->setClientSecret('TU_CLIENT_SECRET');
        $this->googleClient->setRedirectUri(BASE_URL . "auth/google-callback");
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    // El login tradicional se mantiene igual
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $user = $this->userModel->login($email, $password);

            if ($user) {
                $this->setSession($user);
                header("Location: " . BASE_URL . "home");
                exit();
            } else {
                return "Credenciales incorrectas.";
            }
        }
    }

    // NUEVO: Método para iniciar el flujo de Google
    public function googleLogin()
    {
        $authUrl = $this->googleClient->createAuthUrl();
        header("Location: " . $authUrl);
        exit();
    }

    // NUEVO: Método para procesar la respuesta de Google
    public function googleCallback()
    {
        if (isset($_GET['code'])) {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
            $this->googleClient->setAccessToken($token);

            $googleService = new \Google\Service\Oauth2($this->googleClient);
            $data = $googleService->userinfo->get();

            // Buscamos si el usuario existe por su correo de Google
            $user = $this->userModel->getByEmail($data->email);

            if (!$user) {
                // Si no existe, lo registramos automáticamente (Sincronización)
                // Usamos una contraseña aleatoria ya que entrará por Google
                $userData = [
                    'nombre' => $data->name,
                    'email' => $data->email,
                    'password' => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                    'rol' => 'cliente',
                    'google_id' => $data->id // Sería ideal añadir esta columna a tu tabla
                ];
                $userId = $this->userModel->crear($userData);
                $user = $this->userModel->getById($userId);
            }

            $this->setSession($user);
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }

    // Función auxiliar para no repetir código de sesiones
    private function setSession($user)
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_rol'] = $user->rol;
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre    = $_POST['nombre'] ?? '';
            $rut       = $_POST['rut'] ?? '';
            $email     = $_POST['email'] ?? '';
            $telefono  = "+569" . ($_POST['telefono'] ?? ''); // Agregamos el prefijo
            $direccion = $_POST['direccion'] ?? '';
            $password  = $_POST['password'] ?? '';

            if ($this->userModel->getByEmail($email)) {
                return "Este correo ya está registrado.";
            }

            $datos = [
                'nombre'    => $nombre,
                'rut'       => $rut,
                'email'     => $email,
                'telefono'  => $telefono,
                'direccion' => $direccion,
                'password'  => password_hash($password, PASSWORD_DEFAULT)
            ];

            // Guardamos
            $resultado = $this->userModel->crear($datos);

            if ($resultado['id']) {
                // AQUÍ SIMULAMOS EL ENVÍO DE CORREO DE VERIFICACIÓN
                // En un sistema real usarías PHPMailer.
                // Guardamos el link en un archivo de texto para que lo veas.
                $link = BASE_URL . "auth/verificar?token=" . $resultado['token'];
                file_put_contents(__DIR__ . '/../../public/simulacion_email.txt', "Hola $nombre, verifica tu cuenta aquí: $link");

                return true;
            } else {
                return "Hubo un error al registrar.";
            }
        }
    }

    public function verificar()
    {
        // Capturamos el token de la URL (auth/verificar?token=XYZ...)
        $token = $_GET['token'] ?? null;

        if ($token && $this->userModel->activarCuenta($token)) {
            // ¡Éxito! Lo mandamos al login con un aviso visual
            header("Location: " . BASE_URL . "auth/login?msg=cuenta_activada");
            exit();
        } else {
            // Fallo
            echo "<h1>Error: El link es inválido o ya fue usado.</h1>";
            echo "<a href='" . BASE_URL . "auth/login'>Volver al Login</a>";
        }
    }

    // VISTA: Formulario para pedir el correo
    public function forgot()
    {
        include __DIR__ . '/../../views/auth/forgot.php';
    }

    // PROCESO: Generar link y enviar correo (Simulado)
    public function sendRecovery()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $token = $this->userModel->generarTokenRecuperacion($email);

            if ($token) {
                // SIMULACIÓN DE CORREO
                $link = BASE_URL . "auth/reset?token=" . $token;
                $msg = "RECUPERACIÓN: Entra aquí para cambiar tu clave: $link";

                // Guardamos en el mismo archivo txt de antes
                file_put_contents(__DIR__ . '/../../public/simulacion_email.txt', $msg . PHP_EOL, FILE_APPEND);
            }

            // Por seguridad, SIEMPRE decimos "si existe, te enviamos un correo" 
            // para no revelar qué correos están registrados.
            header("Location: " . BASE_URL . "auth/login?msg=recuperacion_enviada");
            exit();
        }
    }

    // VISTA: Formulario para poner la nueva contraseña
    public function reset()
    {
        $token = $_GET['token'] ?? '';

        // Validamos si el token es real antes de mostrar el formulario
        $usuario = $this->userModel->getByResetToken($token);

        if (!$usuario) {
            die("El enlace es inválido o ha expirado. <a href='" . BASE_URL . "auth/forgot'>Intenta de nuevo</a>");
        }

        include __DIR__ . '/../../views/auth/reset.php';
    }

    // PROCESO: Guardar la nueva contraseña
    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'];
            $password = $_POST['password'];

            // Doble chequeo de seguridad
            $usuario = $this->userModel->getByResetToken($token);

            if ($usuario && $this->userModel->actualizarPassword($usuario->id, $password)) {
                header("Location: " . BASE_URL . "auth/login?msg=pass_actualizada");
                exit();
            } else {
                echo "Error al actualizar.";
            }
        }
    }

}
