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

    public function exito()
    {
        // 1. Detectar si el usuario actual es Administrador (VIP)
        $esAdmin = (isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2]));

        // 2. Seguridad híbrida: Permitir acceso a Admin, Usuarios o Invitados
        if (!$esAdmin && empty($_SESSION['user_id']) && empty($_SESSION['invitado'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 3. Validamos que venga el ID del pedido por la URL
        $idPedido = $_GET['id'] ?? null;
        if (!$idPedido) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 4. Obtenemos la información completa del pedido
        $pedido = $this->pedidoModel->obtenerPorId($idPedido);

        if (!$pedido) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 5. Seguridad: Validamos pertenencia (Si es Admin, se salta esta validación)
        $esDueno = false;

        if ($esAdmin) {
            $esDueno = true; // El admin es dueño de todo
        } elseif (!empty($_SESSION['user_id'])) {
            // Validación para cliente registrado
            if ($pedido['usuario_id'] == $_SESSION['user_id']) {
                $esDueno = true;
            }
        } elseif (!empty($_SESSION['invitado'])) {
            // Validación para cliente invitado (comparamos RUT sin ceros a la izquierda)
            $rutPedido = $pedido['rut_cliente'] ?? '';
            $rutPedidoLimpio = ltrim(str_replace(['.', '-'], '', $rutPedido), '0');
            $rutInvitadoLimpio = ltrim(str_replace(['.', '-'], '', $_SESSION['invitado']['rut']), '0');

            if ($pedido['usuario_id'] === null && strtoupper($rutPedidoLimpio) === strtoupper($rutInvitadoLimpio)) {
                $esDueno = true;
            }
        }

        // Si no pasó ninguna validación, lo sacamos
        if (!$esDueno) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 6. Renderizamos la vista de éxito
        ob_start();
        include __DIR__ . '/../../views/pedido/exito.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 🔥 NUEVO MÉTODO: VER DETALLE (Con Ediciones y Stock)
    // =========================================================
    public function detalle()
    {
        // 1. Detectar Administrador
        $esAdmin = (isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2]));

        // 2. Seguridad: Debe estar logueado o ser un invitado válido
        if (!$esAdmin && empty($_SESSION['user_id']) && empty($_SESSION['invitado'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 3. Validar ID
        $idPedido = $_GET['id'] ?? null;
        if (!$idPedido) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 4. Obtener la cabecera del pedido (Info del cliente, totales, estado)
        $pedido = $this->pedidoModel->obtenerPorId($idPedido);
        if (!$pedido) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 5. Validar Pertenencia (Misma seguridad estricta que usas en 'exito')
        $esDueno = false;
        if ($esAdmin) {
            $esDueno = true;
        } elseif (!empty($_SESSION['user_id'])) {
            if ($pedido['usuario_id'] == $_SESSION['user_id']) {
                $esDueno = true;
            }
        } elseif (!empty($_SESSION['invitado'])) {
            $rutPedido = $pedido['rut_cliente'] ?? '';
            $rutPedidoLimpio = ltrim(str_replace(['.', '-'], '', $rutPedido), '0');
            $rutInvitadoLimpio = ltrim(str_replace(['.', '-'], '', $_SESSION['invitado']['rut']), '0');

            if ($pedido['usuario_id'] === null && strtoupper($rutPedidoLimpio) === strtoupper($rutInvitadoLimpio)) {
                $esDueno = true;
            }
        }

        if (!$esDueno) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // ✅ LA NUEVA FORMA (Trae los vivos, los eliminados y el stock real)
        $sucursalId = $pedido['sucursal_codigo']; // Extraemos la sucursal del pedido (ej: 10 o 29)
        $detalles = $this->pedidoModel->obtenerDetallesConEdiciones($idPedido, $sucursalId);

        // 7. Renderizar la vista "detalle.php"
        ob_start();
        include __DIR__ . '/../../views/pedido/detalle.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
}
