<?php

namespace App\Controllers;

use App\Models\Pedido;

class PedidoController
{
    private $db;
    private $pedidoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
    }

    /**
     * Muestra la pantalla de éxito cuando un cliente de confianza
     * elige el método de pago "Contra Entrega".
     */
    public function exito()
    {
        // 1. Validamos que exista una sesión activa
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        // 2. Validamos que venga el ID del pedido por la URL
        $idPedido = $_GET['id'] ?? null;
        if (!$idPedido) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 3. Obtenemos la información completa del pedido
        $pedido = $this->pedidoModel->obtenerPorId($idPedido);

        // 4. Seguridad: Validamos que el pedido exista y pertenezca al usuario actual
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user_id']) {
            header("Location: " . BASE_URL . "perfil?tab=pedidos");
            exit();
        }

        // 5. Renderizamos la vista de éxito
        ob_start();
        include __DIR__ . '/../../views/pedido/exito.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
}