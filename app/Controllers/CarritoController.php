<?php

namespace App\Controllers;

use App\Models\Producto;

class CarritoController
{
    private $db;
    private $productoModel;

    public function __construct($db = null)
    {
        $this->db = $db;
        if ($db) {
            $this->productoModel = new Producto($db);
        }

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
    }

    // =========================================================
    // 1. MÉTODOS AJAX (Para el catálogo y offcanvas)
    // =========================================================
    // --- AGREGAR AJAX (EL QUE USA EL BOTÓN +) ---
    public function agregarAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;

                if (!$id) {
                    echo json_encode(['status' => 'error', 'mensaje' => 'ID de producto no válido']);
                    exit;
                }

                // CONSULTA DIRECTA Y BLINDADA (Igual que en el Home)
                $sql = "SELECT p.id, ps.precio, ps.stock, p.imagen, COALESCE(piw.nombre_web, p.nombre) as nombre_mostrar
                        FROM productos p
                        INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                        LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                        WHERE p.id = ? AND ps.sucursal_id = ? AND p.activo = 1";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id, $sucursal_id]);
                $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Validamos como Array (['precio']) y no como Objeto (->precio)
                if (!$producto || $producto['precio'] <= 0 || $producto['stock'] <= 0) {
                    echo json_encode(['status' => 'error', 'mensaje' => 'Sin disponibilidad en esta zona']);
                    exit;
                }

                if (isset($_SESSION['carrito'][$id])) {
                    $_SESSION['carrito'][$id]['cantidad']++;
                } else {
                    $_SESSION['carrito'][$id] = [
                        'id' => $id,
                        'nombre' => $producto['nombre_mostrar'],
                        'precio' => $producto['precio'],
                        'imagen' => $producto['imagen'],
                        'cantidad' => 1,
                        'sucursal_id' => $sucursal_id
                    ];
                }

                $totales = $this->calcularTotales();
                echo json_encode([
                    'status' => 'success',
                    'totalCantidad' => $totales['cantidad'],
                    'totalMonto' => $totales['monto'],
                    'cantidadItem' => $_SESSION['carrito'][$id]['cantidad']
                ]);
                exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error Interno: ' . $e->getMessage()]);
            exit;
        }
    }

    // --- AGREGAR (VERSIÓN TRADICIONAL POST CON REDIRECCIÓN) ---
    public function agregar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;

            if ($id) {
                // Misma consulta blindada
                $sql = "SELECT p.id, ps.precio, ps.stock, p.imagen, COALESCE(piw.nombre_web, p.nombre) as nombre_mostrar
                        FROM productos p
                        INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                        LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                        WHERE p.id = ? AND ps.sucursal_id = ? AND p.activo = 1";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id, $sucursal_id]);
                $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($producto && $producto['precio'] > 0 && $producto['stock'] > 0) {
                    if (isset($_SESSION['carrito'][$id])) {
                        $_SESSION['carrito'][$id]['cantidad']++;
                    } else {
                        $_SESSION['carrito'][$id] = [
                            'id' => $id,
                            'nombre' => $producto['nombre_mostrar'],
                            'precio' => $producto['precio'],
                            'imagen' => $producto['imagen'],
                            'cantidad' => 1,
                            'sucursal_id' => $sucursal_id
                        ];
                    }
                }
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . "home";
        header("Location: " . $referer);
        exit();
    }

    public function modificarAjax()
    {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? null;
        $accion = $_POST['accion'] ?? null;

        if ($id && isset($_SESSION['carrito'][$id])) {
            if ($accion === 'subir') {
                $_SESSION['carrito'][$id]['cantidad']++;
            } elseif ($accion === 'bajar') {
                if ($_SESSION['carrito'][$id]['cantidad'] > 1) {
                    $_SESSION['carrito'][$id]['cantidad']--;
                } else {
                    unset($_SESSION['carrito'][$id]);
                }
            } elseif ($accion === 'eliminar') {
                unset($_SESSION['carrito'][$id]);
            }
        }

        $totales = $this->calcularTotales();
        $cantidadItem = isset($_SESSION['carrito'][$id]) ? $_SESSION['carrito'][$id]['cantidad'] : 0;

        echo json_encode([
            'status' => 'success',
            'totalCantidad' => $totales['cantidad'],
            'totalMonto' => $totales['monto'],
            'cantidadItem' => $cantidadItem
        ]);
        exit;
    }

    // =========================================================
    // 2. MÉTODOS ESTÁNDAR (Redirección directa)
    // =========================================================

    public function subir()
    {
        $id = $_GET['id'] ?? null;
        if ($id && isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad']++;
        }
        header("Location: " . $_SERVER['HTTP_REFERER']); // Vuelve a donde estaba
        exit();
    }

    public function bajar()
    {
        $id = $_GET['id'] ?? null;
        if ($id && isset($_SESSION['carrito'][$id])) {
            if ($_SESSION['carrito'][$id]['cantidad'] > 1) {
                $_SESSION['carrito'][$id]['cantidad']--;
            } else {
                unset($_SESSION['carrito'][$id]);
            }
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    public function eliminar()
    {
        $id = $_GET['id'] ?? null;
        if ($id) {
            unset($_SESSION['carrito'][$id]);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    public function vaciar()
    {
        $_SESSION['carrito'] = [];
        header("Location: " . BASE_URL . "home");
        exit();
    }

    // =========================================================
    // 3. VISTAS Y LOGICA INTERNA
    // =========================================================

    public function ver()
    {
        $totales = $this->calcularTotales();
        $total = $totales['monto'];
        ob_start();
        include __DIR__ . '/../../views/shop/carrito.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function obtenerHtml()
    {
        header('Content-Type: application/json');
        $html = '';
        $totales = $this->calcularTotales();

        if (!empty($_SESSION['carrito'])) {
            $html .= '<div class="list-group list-group-flush">';
            foreach ($_SESSION['carrito'] as $item) {
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
                            <div class="btn-group btn-group-sm shadow-sm">
                                <button type="button" class="btn btn-outline-secondary px-2" onclick="cambiarCantidad(' . $item['id'] . ', \'bajar\')"><i class="bi bi-dash-lg"></i></button>
                                <span class="btn btn-white border-top border-bottom border-secondary px-3 disabled text-dark fw-bold">' . $item['cantidad'] . '</span>
                                <button type="button" class="btn btn-outline-secondary px-2" onclick="cambiarCantidad(' . $item['id'] . ', \'subir\')"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                        <div class="ms-1 align-self-start">
                            <button type="button" class="btn btn-link text-danger p-0" onclick="cambiarCantidad(' . $item['id'] . ', \'eliminar\')">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>';
            }
            $html .= '</div>';
        } else {
            $html = '<div class="text-center py-5 mt-5"><i class="bi bi-basket2 display-1 text-muted opacity-25"></i><h5 class="text-muted fw-bold">Tu carrito está vacío</h5></div>';
        }

        echo json_encode(['html' => $html, 'total' => '$' . number_format($totales['monto'], 0, ',', '.')]);
        exit;
    }



    private function calcularTotales()
    {
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

    // =========================================================
    // 4. VALIDADOR DE CAMBIO DE SUCURSAL (EL BARREDOR)
    // =========================================================
    // =========================================================
    // 4. VALIDADOR DE CAMBIO DE SUCURSAL (CON MODO SIMULACRO)
    // =========================================================
    public function validarCambioSucursal($nueva_sucursal_id, $ejecutar = true)
    {
        if (empty($_SESSION['carrito'])) return ['cambios' => false, 'mensajes' => []];

        $mensajes = [];
        $carrito_limpio = [];
        $cambios = false;

        foreach ($_SESSION['carrito'] as $id => $item) {
            $sql = "SELECT ps.precio, ps.stock 
                    FROM productos_sucursales ps 
                    INNER JOIN productos p ON ps.cod_producto = p.cod_producto 
                    WHERE p.id = ? AND ps.sucursal_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $nueva_sucursal_id]);
            $prod = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$prod || $prod['precio'] <= 0 || $prod['stock'] <= 0) {
                // Producto perdido
                $mensajes[] = "<li class='text-danger'><b>" . $item['nombre'] . "</b> (Agotado)</li>";
                $cambios = true;
            } else {
                if ($item['precio'] != $prod['precio']) {
                    $mensajes[] = "<li class='text-warning'><b>" . $item['nombre'] . "</b> (Cambia a $" . number_format($prod['precio'], 0, ',', '.') . ")</li>";
                    $cambios = true;
                }
                if ($item['cantidad'] > $prod['stock']) {
                    $mensajes[] = "<li class='text-warning'><b>" . $item['nombre'] . "</b> (Quedan solo " . $prod['stock'] . " un)</li>";
                    $cambios = true;
                }

                // Preparamos cómo quedaría si se ejecuta
                if ($ejecutar) {
                    $item['sucursal_id'] = $nueva_sucursal_id;
                    $item['precio'] = $prod['precio'];
                    if ($item['cantidad'] > $prod['stock']) $item['cantidad'] = $prod['stock'];
                    $carrito_limpio[$id] = $item;
                }
            }
        }

        // Si es una ejecución real, aplicamos la masacre
        if ($ejecutar && $cambios) {
            $_SESSION['carrito'] = $carrito_limpio;
        }

        // Devolvemos el reporte del simulacro
        return ['cambios' => $cambios, 'mensajes' => $mensajes];
    }
}
