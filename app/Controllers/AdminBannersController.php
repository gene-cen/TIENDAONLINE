<?php
session_start();

// Aquí debes incluir tu conexión a la BD y el modelo
// require_once 'conexion.php';
// require_once 'CarruselBanner.php';

class AdminBannersController {
    
    private $modelo;

public function __construct($conexion) {
    // Validar con rol_id
    if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2])) {
        header("Location: " . BASE_URL . "auth/login");
        exit();
    }
    $this->modelo = new CarruselBanner($conexion);
}

    public function index() {
        $banners = $this->modelo->obtenerTodos();
        // Cargar la vista pasándole los $banners
        require_once 'vistas/admin/banners_lista.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $titulo = $_POST['titulo'];
            $url_destino = $_POST['url_destino'];
            $orden = $_POST['orden'] ?? 0;
            
            // Lógica de subida de imagen
            $imagen = $_FILES['imagen'];
            $directorio_destino = 'public/img/carrusel/'; // Asegúrate de crear esta carpeta
            
            if ($imagen['error'] == UPLOAD_ERR_OK) {
                // Generar un nombre único para evitar sobreescrituras
                $nombre_archivo = time() . '_' . basename($imagen['name']);
                $ruta_completa = $directorio_destino . $nombre_archivo;
                
                if (move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
                    // Guardar en la base de datos
                    $this->modelo->agregar($titulo, $ruta_completa, $url_destino, $orden);
                    header("Location: /admin/banners?exito=1");
                } else {
                    echo "Error al subir la imagen.";
                }
            }
        }
    }

    public function borrar($id) {
        // Opcional: Podrías buscar la ruta de la imagen y hacer unlink() para borrar el archivo del servidor
        $this->modelo->eliminar($id);
        header("Location: /admin/banners?borrado=1");
    }
}
?>