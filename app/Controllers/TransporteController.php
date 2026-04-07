<?php

namespace App\Controllers;

use App\Models\Pedido;

class TransporteController
{

    private $db;
    private $pedidoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new \App\Models\Pedido($this->db);

        // 1. Si no hay sesión iniciada, al login
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // 2. Si está logueado pero NO es transportista ni admin, al home (NO al login)
        // Esto es lo que rompe el bucle de redirección
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['transportista', 'admin'])) {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
    }

    // Muestra la lista de entregas para el chofer
    public function misEntregas()
    {
        // Obtenemos los pedidos pendientes para el transportista logueado
        // Si quieres probar con todos por ahora, puedes usar otro método
        $pedidos = $this->pedidoModel->obtenerTodas();

        include __DIR__ . '/../../views/transporte/mis_entregas.php';
    }

    // Procesa el formulario de cierre de entrega (GPS + FOTO)
    public function finalizarEntrega()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pedidoId = $_POST['pedido_id'];
            $rutaFoto = null;

            // 1. Procesar la foto del comprobante
            if (!empty($_FILES['comprobante']['name'])) {
                $uploadDir = __DIR__ . '/../../public/img/comprobantes/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = "POD_" . $pedidoId . "_" . time() . "." . $ext;

                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $uploadDir . $nombreArchivo)) {
                    $rutaFoto = $nombreArchivo;
                }
            }

            // 2. Preparar datos para el modelo
            $datosEntrega = [
                'pedido_id' => $pedidoId,
                'latitud'   => $_POST['latitud'],
                'longitud'  => $_POST['longitud'],
                'ruta_foto' => $rutaFoto
            ];

            // 3. Llamar a la función del modelo que ya creaste (Punto 2 anterior)
            if ($this->pedidoModel->registrarEntregaFinal($datosEntrega)) {
                echo json_encode(['status' => 'success', 'message' => 'Entrega confirmada']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al guardar en BD']);
            }
            exit();
        }
    }
}
