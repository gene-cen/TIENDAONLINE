<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Usuario;

class ApiTransporteController
{
    private $db;
    private $pedidoModel;
    private $usuarioModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new \App\Models\Pedido($this->db);
        $this->usuarioModel = new \App\Models\Usuario($this->db);
        
        // Configuramos las cabeceras para responder JSON
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * Valida el token enviado por la App de Android en la cabecera HTTP
     * Retorna el ID del chofer si es válido, o corta la ejecución si no lo es.
     */
    private function validarTokenApp()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
        
        // Esperamos un header tipo "Bearer token_aqui"
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            // Aquí debes buscar en tu BD si el token pertenece a un transportista válido
            $chofer = $this->usuarioModel->getByToken($token); // Deberás adaptar esto
            
            if ($chofer && in_array($chofer->rol, ['transportista', 'admin'])) {
                return $chofer->id;
            }
        }
        
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit;
    }

    // ----------------------------------------------------
    // ENDPOINT 1: Obtener la ruta del día (GET)
    // URL: http://tu-dominio.com/api/transporte/entregas
    // ----------------------------------------------------
    public function getMisEntregas()
    {
        $choferId = $this->validarTokenApp();

        // Obtienes los pedidos asignados a ese chofer
        // Idealmente: $pedidos = $this->pedidoModel->obtenerPorTransportista($choferId);
        $pedidos = $this->pedidoModel->obtenerTodas(); 

        $payload = [];
        foreach ($pedidos as $p) {
            // Empaquetamos solo la data dura que la App Android necesita para dibujar las tarjetas
            $payload[] = [
                'id_pedido'           => $p['id'],
                'hora_creacion'       => $p['hora_creacion'],
                'nombre_cliente'      => $p['nombre_destinatario'],
                'direccion'           => $p['direccion_entrega_texto'],
                'telefono'            => $p['telefono_contacto'],
                'requiere_foto'       => in_array($p['forma_pago_id'], [5, 7]), // true o false
                'lat_destino'         => $p['latitud'] ?? null, // Si ya la tienes
                'lng_destino'         => $p['longitud'] ?? null,
            ];
        }

        echo json_encode([
            'status' => 'success', 
            'total' => count($payload),
            'data' => $payload
        ]);
        exit;
    }

    // ----------------------------------------------------
    // ENDPOINT 2: Finalizar la entrega desde la App (POST)
    // URL: http://tu-dominio.com/api/transporte/finalizar
    // ----------------------------------------------------
    public function finalizarEntregaAndroid()
    {
        $choferId = $this->validarTokenApp();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pedidoId = $_POST['pedido_id'] ?? null;
            $latitud = $_POST['latitud'] ?? null;
            $longitud = $_POST['longitud'] ?? null;
            $rutaFoto = null;

            if (!$pedidoId || !$latitud || !$longitud) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Faltan coordenadas o ID de pedido']);
                exit;
            }

            // 1. Procesar la foto enviada desde el dispositivo Android
            if (!empty($_FILES['comprobante']['name'])) {
                $uploadDir = __DIR__ . '/../../public/img/comprobantes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = "POD_" . $pedidoId . "_" . time() . "." . $ext;

                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $uploadDir . $nombreArchivo)) {
                    $rutaFoto = $nombreArchivo;
                }
            }

            $datosEntrega = [
                'pedido_id' => $pedidoId,
                'latitud'   => $latitud,
                'longitud'  => $longitud,
                'ruta_foto' => $rutaFoto,
                'chofer_id' => $choferId // Quien entregó
            ];

            if ($this->pedidoModel->registrarEntregaFinal($datosEntrega)) {
                echo json_encode(['status' => 'success', 'message' => 'Entrega sincronizada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error de guardado en servidor']);
            }
            exit;
        }
    }
}