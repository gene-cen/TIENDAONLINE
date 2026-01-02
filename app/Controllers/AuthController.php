<?php
namespace App\Controllers;

use App\Models\Usuario;
use Google\Client as GoogleClient; // Importamos la librería de Google

class AuthController {
    private $userModel;
    private $googleClient;
    private $db;

    public function __construct($db) {
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
    public function login() {
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
    public function googleLogin() {
        $authUrl = $this->googleClient->createAuthUrl();
        header("Location: " . $authUrl);
        exit();
    }

    // NUEVO: Método para procesar la respuesta de Google
    public function googleCallback() {
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
    private function setSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_rol'] = $user->rol;
    }
}