<?php

namespace App\Controllers;

class CarritoController
{

    public function __construct()
    {
        // Iniciamos el carrito si no existe
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
    }

    // Agregar producto al carro
    public function agregar()
    {
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
    public function vaciar()
    {
        $_SESSION['carrito'] = [];
        header("Location: " . BASE_URL . "home");
        exit();
    }

    // Mostrar la vista del carrito
    public function ver()
    {
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
    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            unset($_SESSION['carrito'][$id]); // Borramos del array
        }
        header("Location: " . BASE_URL . "carrito/ver"); // Recargamos la vista
        exit();
    }

    // Sumar 1 unidad
    public function subir()
    {
        $id = $_GET['id'] ?? null;
        if ($id && isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad']++;
        }
        header("Location: " . BASE_URL . "carrito/ver");
        exit();
    }

    // Restar 1 unidad
    public function bajar()
    {
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

public function agregarAjax() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            
            // Lógica de agregar o crear
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad']++;
            } else {
                $_SESSION['carrito'][$id] = [
                    'id' => $id,
                    'nombre' => $_POST['nombre'],
                    'precio' => $_POST['precio'],
                    'imagen' => $_POST['imagen'],
                    'cantidad' => 1
                ];
            }

            // Usamos la función auxiliar para obtener TOTALES REALES
            $totales = $this->calcularTotales();
            $cantidadItem = $_SESSION['carrito'][$id]['cantidad'];

            echo json_encode([
                'status' => 'success',
                'mensaje' => 'Producto agregado',
                'totalCantidad' => $totales['cantidad'], // Cantidad global (Badge rojo)
                'totalMonto'    => $totales['monto'],    // <--- ESTO FALTABA (Plata global)
                'cantidadItem'  => $cantidadItem         // Cantidad de este producto
            ]);
            exit;
        }
    }
public function obtenerHtml()
    {
        header('Content-Type: application/json');
        $html = '';
        $totales = $this->calcularTotales(); 

        if (!empty($_SESSION['carrito'])) {
            $html .= '<div class="list-group list-group-flush">';
            foreach ($_SESSION['carrito'] as $item) {
                // Cálculo del subtotal por ítem
                $subtotalItem = $item['precio'] * $item['cantidad'];
                
                $imgSrc = !empty($item['imagen']) ? (strpos($item['imagen'], 'http') === 0 ? $item['imagen'] : BASE_URL . 'img/productos/' . $item['imagen']) : BASE_URL . 'img/no-image.png';
                
                $html .= '
                <div class="list-group-item p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="position-relative me-3">
                            <img src="' . $imgSrc . '" class="rounded border" style="width: 65px; height: 65px; object-fit: contain;">
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-indigo shadow-sm" style="font-size: 0.75rem;">' . $item['cantidad'] . '</span>
                        </div>

                        <div class="flex-grow-1 ps-2" style="min-width: 0;">
                            <h6 class="mb-1 fw-bold text-cenco-indigo text-truncate" style="max-width: 160px;">' . htmlspecialchars($item['nombre']) . '</h6>
                            
                            <div class="d-flex flex-column mb-2">
                                <span class="text-muted small" style="font-size: 0.75rem;">Unitario: $' . number_format($item['precio'], 0, ',', '.') . '</span>
                                <span class="fw-bold text-cenco-green" style="font-size: 0.9rem;">Total: $' . number_format($subtotalItem, 0, ',', '.') . '</span>
                            </div>
                            
                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary px-2" onclick="cambiarCantidad(' . $item['id'] . ', \'bajar\', ' . $item['cantidad'] . ')"><i class="bi bi-dash-lg"></i></button>
                                <span class="btn btn-white border-top border-bottom border-secondary px-3 disabled text-dark fw-bold" style="font-size: 0.85rem; background:#fff;">' . $item['cantidad'] . '</span>
                                <button type="button" class="btn btn-outline-secondary px-2" onclick="cambiarCantidad(' . $item['id'] . ', \'subir\', ' . $item['cantidad'] . ')"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>

                        <div class="ms-1 align-self-start">
                            <button type="button" class="btn btn-link text-danger p-0 hover-scale" onclick="cambiarCantidad(' . $item['id'] . ', \'eliminar\', ' . $item['cantidad'] . ')" title="Eliminar">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>';
            }
            $html .= '</div>';
        } else {
            $html = '<div class="text-center py-5 mt-5"><div class="mb-3"><i class="bi bi-basket2 display-1 text-muted opacity-25"></i></div><h5 class="text-muted fw-bold">Tu carrito está vacío</h5><button class="btn btn-cenco-indigo rounded-pill px-4" data-bs-dismiss="offcanvas">Seguir vitrineando</button></div>';
        }

        echo json_encode(['html' => $html, 'total' => '$' . number_format($totales['monto'], 0, ',', '.')]);
        exit;
    }


// --- FUNCIÓN PRIVADA PARA CALCULAR TOTALES (CLAVE PARA EL TIEMPO REAL) ---
    private function calcularTotales() {
        $cantidad = 0;
        $monto = 0;
        if (isset($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $cantidad += $item['cantidad'];
                $monto += $item['cantidad'] * $item['precio'];
            }
        }
        return ['cantidad' => $cantidad, 'monto' => $monto];
    }

   
    // --- 2. Modificar cantidad (+, -, Eliminar) vía AJAX (MEJORADO) ---
    public function modificarAjax() {
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? null;
        $accion = $_POST['accion'] ?? null;

        if ($id && isset($_SESSION['carrito'][$id])) {
            if ($accion === 'subir') {
                $_SESSION['carrito'][$id]['cantidad']++;
            } 
            elseif ($accion === 'bajar') {
                if ($_SESSION['carrito'][$id]['cantidad'] > 1) {
                    $_SESSION['carrito'][$id]['cantidad']--;
                } else {
                    unset($_SESSION['carrito'][$id]);
                }
            } 
            elseif ($accion === 'eliminar') {
                unset($_SESSION['carrito'][$id]);
            }
        }

        // Recalculamos totales
        $totales = $this->calcularTotales();
        $cantidadItem = isset($_SESSION['carrito'][$id]) ? $_SESSION['carrito'][$id]['cantidad'] : 0;

        echo json_encode([
            'status' => 'success',
            'totalCantidad' => $totales['cantidad'],
            'totalMonto'    => $totales['monto'],    // <--- ESTO FALTABA (Plata global)
            'cantidadItem'  => $cantidadItem
        ]);
        exit;
    }


    

    

  
  
}
