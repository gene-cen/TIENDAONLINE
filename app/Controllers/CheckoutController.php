<?php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;

class CheckoutController {
    private $db;
    private $pedidoModel;
    private $productoModel;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db); // Necesitamos leer el codigo_erp fresco
        $this->userModel = new Usuario($db);
    }

    // Paso 1: Mostrar resumen antes de pagar
    public function index() {
        if (empty($_SESSION['carrito'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }
        
        // Traemos datos del usuario para mostrar su dirección
        $usuario = $this->userModel->getById($_SESSION['user_id']);
        
        ob_start();
        include __DIR__ . '/../../views/shop/checkout.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // Paso 2: Procesar la venta
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrito'])) {
            
            // 1. Obtener Usuario
            $user = $this->userModel->getById($_SESSION['user_id']);
            
            // 2. Cálculos Matemáticos (Totales)
            $totalBruto = 0;
            foreach ($_SESSION['carrito'] as $item) {
                $totalBruto += $item['precio'] * $item['cantidad'];
            }
            $totalNeto = round($totalBruto / 1.19); // IVA 19% Chile

            // 3. Formateo de Datos para ERP
            // A. Fecha: YYYYMMDD
            $fechaERP = date('Ymd');
            
            // B. RUT: Limpiar puntos, guión y rellenar ceros a la izquierda (11 chars)
            $rutLimpio = str_replace(['.', '-'], '', $user->rut); // Ej: 76000111K
            $rutERP = str_pad($rutLimpio, 11, '0', STR_PAD_LEFT); // Ej: 0076000111K

            // 4. Preparar Cabecera
            $datosPedido = [
                'usuario_id'      => $user->id,
                'sucursal_codigo' => '10',    // Fijo: Villa Alemana
                'vendedor_codigo' => '0003',  // Fijo: Marcelo Zuleta
                'rut_cliente'     => $rutERP,
                'fecha_pedido' => date('Y-m-d H:i:s'), // Esto incluye Hora:Minuto:Segundo
                'total_bruto'     => $totalBruto,
                'total_neto'      => $totalNeto,
                'direccion_envio' => $_POST['direccion'] ?? $user->direccion
            ];

            // 5. Guardar Cabecera
            $pedidoId = $this->pedidoModel->crear($datosPedido);

            // 6. Guardar Detalle (Iteramos el carrito)
            foreach ($_SESSION['carrito'] as $id => $item) {
                // Buscamos el producto en BD para tener el 'cod_producto' real y fresco
                $productoReal = $this->productoModel->getById($id);
                
                $precioBruto = $item['precio'];
                $precioNeto = round($precioBruto / 1.19);

                $this->pedidoModel->agregarDetalle(
                    $pedidoId, 
                    $productoReal, 
                    $item['cantidad'], 
                    ['neto' => $precioNeto, 'bruto' => $precioBruto]
                );
            }

            // 7. Vaciar Carrito y Redirigir
            $_SESSION['carrito'] = [];
            
            // Redirigimos a una página de éxito
            header("Location: " . BASE_URL . "home?msg=compra_exitosa&folio=" . $pedidoId);
            exit();
        }
    }
}