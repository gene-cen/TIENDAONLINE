<?php
namespace App\Controllers;

use App\Models\Usuario;

class AuthController {
    private $userModel;

    public function __construct($db) {
        // Inyectamos la conexión a la DB en el modelo de Usuario
        $this->userModel = new Usuario($db);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->login($email, $password);

            if ($user) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_nombre'] = $user->nombre;
                $_SESSION['user_rol'] = $user->rol;
                
                header("Location: " . BASE_URL . "home");
                exit();
            } else {
                return "Credenciales incorrectas.";
            }
        }
    }
}