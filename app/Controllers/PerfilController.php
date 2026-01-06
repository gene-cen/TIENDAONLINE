<?php
namespace App\Controllers;

class PerfilController {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new \App\Models\Usuario($db);
    }

    public function index() {
        // 1. Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        // 2. Obtener datos FRESCOS de la base de datos
        $usuario = $this->userModel->getById($_SESSION['user_id']);

        // 3. Cargar la vista
        // (Pasamos $usuario a la vista para rellenar los inputs)
        ob_start();
        include __DIR__ . '/../../views/perfil.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardar() {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre'    => $_POST['nombre'],
                'telefono'  => $_POST['telefono'],
                'direccion' => $_POST['direccion']
            ];

            if ($this->userModel->actualizar($_SESSION['user_id'], $datos)) {
                // Actualizamos el nombre en la sesión también por si cambió
                $_SESSION['user_nombre'] = $datos['nombre'];
                
                // Redirigir con mensaje de éxito
                header("Location: " . BASE_URL . "perfil?msg=guardado");
                exit();
            }
        }
    }
}