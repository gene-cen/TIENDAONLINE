<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Direccion;
use App\Models\Pedido;

class PerfilController
{
    private $db;
    private $userModel;
    private $direccionModel;
    private $pedidoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new Usuario($db);
        $this->direccionModel = new Direccion($db);
        $this->pedidoModel = new Pedido($db);
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        $usuario = $this->userModel->getById($_SESSION['user_id']);

        // --- NUEVO: Cargar teléfonos de la tabla normalizada ---
        $telefonos = $this->userModel->getTelefonos($_SESSION['user_id']);

        // Identificamos el principal para mostrarlo en la pestaña "Mis Datos"
        $usuario->telefono_principal = '';
        foreach ($telefonos as $t) {
            if ($t->es_principal) {
                $usuario->telefono_principal = $t->numero;
                break;
            }
        }

        $misDirecciones = $this->direccionModel->obtenerPorUsuario($_SESSION['user_id']);

        $stmt = $this->db->query("SELECT * FROM regiones ORDER BY id ASC");
        $regiones = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $misPedidos = $this->pedidoModel->obtenerPorUsuario($_SESSION['user_id']);

        $categorias = [];
        try {
            $stmtCat = $this->db->query("SELECT * FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC");
            if ($stmtCat) {
                $categorias = $stmtCat->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
        }

        ob_start();
        include __DIR__ . '/../../views/auth/perfil.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardar()
    {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $nombre = $_POST['nombre'];
            $nuevo_telefono = $_POST['telefono'];

            // 1. RECUPERAR DATOS ACTUALES PARA NO PERDER EL RUT
            $usuarioActual = $this->userModel->getById($user_id);

            // Preparamos los datos asegurándonos de RE-PASAR el RUT existente
            $datosUser = [
                'nombre'    => $nombre,
                'rut'       => $usuarioActual->rut, // <--- AQUÍ: Mantenemos el RUT para que no desaparezca
                'email'     => $usuarioActual->email,
                'direccion' => $usuarioActual->direccion ?? '',
                'comuna_id' => $usuarioActual->comuna_id,
                'giro'      => $usuarioActual->giro
            ];

            if ($this->userModel->actualizar($user_id, $datosUser)) {

                // 2. LÓGICA DE TELÉFONO EN TABLA NORMALIZADA
                $telefonos = $this->userModel->getTelefonos($user_id);
                $idPrincipal = null;

                foreach ($telefonos as $t) {
                    if ($t->es_principal) {
                        $idPrincipal = $t->id;
                        break;
                    }
                }

                if ($idPrincipal) {
                    // Si ya tiene un principal, lo actualizamos directamente en la tabla relacional
                    $sql = "UPDATE usuario_telefonos SET numero = ? WHERE id = ?";
                    $this->db->prepare($sql)->execute([$nuevo_telefono, $idPrincipal]);
                } else {
                    // Si no tiene principal, creamos el primer registro
                    $this->userModel->agregarTelefono($user_id, $nuevo_telefono, 'Titular', 1);
                }

                $_SESSION['user_nombre'] = $nombre;
                header("Location: " . BASE_URL . "perfil?tab=datos&msg=datos_ok");
                exit();
            }
        }
    }

    // ... Resto de funciones (cambiarPassword, agregarDireccion, etc.) se mantienen igual ...

    public function cambiarPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $passActual = $_POST['pass_actual'];
            $passNueva = $_POST['pass_nueva'];
            $user = $this->userModel->getById($_SESSION['user_id']);

            if (password_verify($passActual, $user->password)) {
                $this->userModel->actualizarPassword($_SESSION['user_id'], $passNueva);
                header("Location: " . BASE_URL . "perfil?tab=seguridad&msg=pass_ok");
            } else {
                header("Location: " . BASE_URL . "perfil?tab=seguridad&msg=pass_error");
            }
        }
    }

    public function agregarDireccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $datos = [
                'usuario_id'      => $_SESSION['user_id'],
                'nombre_etiqueta' => $_POST['etiqueta'],
                'direccion'       => $_POST['direccion'],
                'comuna_id'       => $_POST['comuna_id'],
                'latitud'         => $_POST['latitud'],
                'longitud'        => $_POST['longitud']
            ];
            if ($this->direccionModel->crear($datos)) {
                header("Location: " . BASE_URL . "perfil?tab=direcciones&msg=dir_ok");
            } else {
                header("Location: " . BASE_URL . "perfil?tab=direcciones&msg=dir_existe");
            }
        }
    }

    public function eliminarDireccion()
    {
        if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
            $this->direccionModel->eliminar($_GET['id'], $_SESSION['user_id']);
            header("Location: " . BASE_URL . "perfil?tab=direcciones&msg=dir_deleted");
        }
    }

    public function hacerPrincipal()
    {
        if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
            $this->direccionModel->fijarPrincipal($_GET['id'], $_SESSION['user_id']);
            header("Location: " . BASE_URL . "perfil?tab=direcciones&msg=fav_ok");
        }
    }

    public function actualizarDireccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $datos = [
                'usuario_id'      => $_SESSION['user_id'],
                'nombre_etiqueta' => $_POST['etiqueta'],
                'direccion'       => $_POST['direccion'],
                'comuna_id'       => $_POST['comuna_id'],
                'latitud'         => $_POST['latitud'],
                'longitud'        => $_POST['longitud']
            ];
            $this->direccionModel->actualizar($_POST['id_direccion'], $datos);
            header("Location: " . BASE_URL . "perfil?tab=direcciones&msg=dir_updated");
        }
    }

    public function obtenerComunas()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $regionId = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
        if ($regionId <= 0) {
            echo json_encode([]);
            exit;
        }
        try {
            $sql = "SELECT c.id, c.nombre FROM comunas c 
                    JOIN provincias p ON c.provincia_id = p.id 
                    WHERE p.region_id = :rid ORDER BY c.nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':rid' => $regionId]);
            echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    public function obtenerDireccionPorId()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? 0;
        if ($id && isset($_SESSION['user_id'])) {
            $data = $this->direccionModel->obtenerPorId($id, $_SESSION['user_id']);
            if ($data) {
                $stmt = $this->db->prepare("SELECT provincia_id FROM comunas WHERE id = ?");
                $stmt->execute([$data->comuna_id]);
                $provId = $stmt->fetchColumn();
                $stmt = $this->db->prepare("SELECT region_id FROM provincias WHERE id = ?");
                $stmt->execute([$provId]);
                $data->region_id = $stmt->fetchColumn();
            }
            echo json_encode($data);
        } else {
            echo json_encode(null);
        }
        exit;
    }

    public function obtenerDetallePedido()
    {
        $idPedido = $_GET['id'] ?? 0;
        $productos = $this->pedidoModel->obtenerDetalleProductos($idPedido);
        $historial = $this->pedidoModel->obtenerHistorial($idPedido);
        echo json_encode(['productos' => $productos, 'historial' => $historial]);
    }

    public function agregarDireccionAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            try {
                if (empty($_POST['direccion']) || empty($_POST['comuna_id'])) {
                    throw new \Exception("Dirección y comuna son obligatorias.");
                }
                $datos = [
                    'usuario_id'      => $_SESSION['user_id'],
                    'nombre_etiqueta' => !empty($_POST['etiqueta']) ? $_POST['etiqueta'] : 'Nueva Dirección',
                    'direccion'       => $_POST['direccion'],
                    'comuna_id'       => $_POST['comuna_id'],
                    'latitud'         => $_POST['latitud'],
                    'longitud'        => $_POST['longitud']
                ];
                if ($this->direccionModel->crear($datos)) {
                    echo json_encode(['status' => 'success', 'message' => '¡Dirección guardada!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Esta dirección ya existe.']);
                }
            } catch (\Exception $e) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        exit;
    }

    public function eliminarDireccionAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        if (isset($_POST['id']) && isset($_SESSION['user_id'])) {
            try {
                $this->direccionModel->eliminar($_POST['id'], $_SESSION['user_id']);
                echo json_encode(['status' => 'success', 'message' => 'Dirección eliminada.']);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar.']);
            }
        }
        exit;
    }

    public function hacerPrincipalAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        if (isset($_POST['id']) && isset($_SESSION['user_id'])) {
            try {
                $this->direccionModel->fijarPrincipal($_POST['id'], $_SESSION['user_id']);
                echo json_encode(['status' => 'success', 'message' => 'Dirección actualizada.']);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error interno.']);
            }
        }
        exit;
    }

    // En App/Controllers/PerfilController.php

    public function eliminarTelefono()
    {
        if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
            $this->userModel->eliminarTelefono($_GET['id'], $_SESSION['user_id']);
            header("Location: " . BASE_URL . "perfil?tab=datos&msg=tel_deleted");
        }
    }

    // Método para procesar la edición (vía POST)
    public function editarTelefono()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $id = $_POST['id_telefono'];
            $numero = $_POST['numero'];
            $alias = $_POST['alias'];

            $this->userModel->actualizarTelefono($id, $_SESSION['user_id'], $numero, $alias);
            header("Location: " . BASE_URL . "perfil?tab=datos&msg=tel_updated");
        }
    }

    // En App/Controllers/PerfilController.php

    public function agregarTelefonoPerfil()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $alias = $_POST['alias'];
            $numero = $_POST['numero'];

            // Usamos el método que ya tienes en el modelo Usuario
            $this->userModel->agregarTelefono($user_id, $numero, $alias, 0); // 0 porque no es el principal

            header("Location: " . BASE_URL . "perfil?tab=datos&msg=tel_ok");
            exit();
        }
    }
}
