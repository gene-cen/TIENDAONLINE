<?php
namespace App\Controllers;

class CarritoController {

    public function __construct() {
        // Iniciamos el carrito si no existe
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
    }

    // Agregar producto al carro
    public function agregar() {
        // Solo aceptamos peticiones POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $precio = $_POST['precio'];
            $imagen = $_POST['imagen'];
            
            // Si el producto ya existe, sumamos cantidad
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad']++;
            } else {
                // Si es nuevo, lo creamos
                $_SESSION['carrito'][$id] = [
                    'id' => $id,
                    'nombre' => $nombre,
                    'precio' => $precio,
                    'imagen' => $imagen,
                    'cantidad' => 1
                ];
            }
        }
        
        // Volvemos a la página anterior (Home)
        header("Location: " . BASE_URL . "home");
        exit();
    }

    // Vaciar todo el carro
    public function vaciar() {
        $_SESSION['carrito'] = [];
        header("Location: " . BASE_URL . "home");
        exit();
    }

    // Mostrar la vista del carrito
    public function ver() {
        // Calculamos el total aquí para pasarlo listo a la vista
        $total = 0;
        if (isset($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
        }

        ob_start();
        // Usaremos una carpeta nueva 'shop' para ser ordenados
        include __DIR__ . '/../../views/shop/carrito.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // Eliminar un producto específico
    public function eliminar() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            unset($_SESSION['carrito'][$id]); // Borramos del array
        }
        header("Location: " . BASE_URL . "carrito/ver"); // Recargamos la vista
        exit();
    }

    // Sumar 1 unidad
    public function subir() {
        $id = $_GET['id'] ?? null;
        if ($id && isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad']++;
        }
        header("Location: " . BASE_URL . "carrito/ver");
        exit();
    }

    // Restar 1 unidad
    public function bajar() {
        $id = $_GET['id'] ?? null;
        if ($id && isset($_SESSION['carrito'][$id])) {
            // Si tiene más de 1, restamos
            if ($_SESSION['carrito'][$id]['cantidad'] > 1) {
                $_SESSION['carrito'][$id]['cantidad']--;
            } else {
                // Si queda 1 y resta, ¿lo borramos? Por seguridad mejor no hacemos nada
                // o puedes descomentar la línea de abajo para que lo borre:
                // unset($_SESSION['carrito'][$id]);
            }
        }
        header("Location: " . BASE_URL . "carrito/ver");
        exit();
    }
}