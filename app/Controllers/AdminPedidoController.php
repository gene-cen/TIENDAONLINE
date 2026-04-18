<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use Exception;

class AdminPedidoController
{
    private $db;
    private $pedidoModel;
    private $productoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);
    }

    private function verificarAdmin()
    {
        // Ahora validamos con rol_id (1 = Super, 2 = Sucursal)
        if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2])) {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    // =========================================================
    // 🛒 GESTIÓN DE PEDIDOS (LISTADO)
    // =========================================================
    public function index()
    {
        $this->verificarAdmin();

        $desde  = $_GET['desde'] ?? date('Y-m-01');
        $hasta  = $_GET['hasta'] ?? date('Y-m-d');
        $q      = $_GET['q'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;

        // KPI de Subsidios (Solo para SuperAdmin)
        $kpisSubsidios = null;
        if (empty($sucursalAsignada)) {
            $sqlKpi = "SELECT 
                COUNT(id) as total_pedidos,
                SUM(CASE WHEN subsidio_empresa > 0 THEN 1 ELSE 0 END) as pedidos_con_subsidio,
                SUM(COALESCE(subsidio_empresa, 0)) as monto_total_subsidio,
                (SELECT sucursal_codigo FROM pedidos WHERE subsidio_empresa > 0 
                 AND DATE(fecha_creacion) BETWEEN :d1 AND :h1 
                 GROUP BY sucursal_codigo ORDER BY SUM(subsidio_empresa) DESC LIMIT 1) as sucursal_critica
               FROM pedidos 
               WHERE DATE(fecha_creacion) BETWEEN :d2 AND :h2";

            $stmtKpi = $this->db->prepare($sqlKpi);
            $stmtKpi->execute([':d1' => $desde, ':h1' => $hasta, ':d2' => $desde, ':h2' => $hasta]);
            $kpisSubsidios = $stmtKpi->fetch(\PDO::FETCH_ASSOC);
        }

        $limite = 25;
        $paginaActual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($paginaActual < 1) $paginaActual = 1;
        $offset = ($paginaActual - 1) * $limite;

        $totalRegistros = $this->pedidoModel->contarFiltrados($desde, $hasta, $q, $estado, $sucursalAsignada);
        $totalPaginas = ceil($totalRegistros / $limite);
        $pedidos = $this->pedidoModel->obtenerFiltrados($desde, $hasta, $q, $estado, $limite, $offset, $sucursalAsignada);

        $esAdmin = true;
        ob_start();
        include __DIR__ . '/../../views/admin/pedidos_index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    } // =========================================================
    // 🔍 DETALLE Y EDICIÓN COMPLEJA
    // =========================================================
    public function verDetalle($id)
    {
        $this->verificarAdmin();
        $pedido = $this->pedidoModel->obtenerPorId($id);
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;

        // 1. Candado de seguridad por sucursal
        if ($pedido && $sucursalAsignada !== null) {
            if ((int)$pedido['sucursal_codigo'] !== (int)$sucursalAsignada) {
                header("Location: " . BASE_URL . "admin/pedidos?msg=acceso_denegado");
                exit();
            }
        }

        if (!$pedido) {
            header("Location: " . BASE_URL . "admin/pedidos");
            exit();
        }

        // 2. 🔥 VARIABLES PARA CAPTURA WEBPAY (Fix para Administradores)
        $stmtWp = $this->db->prepare("SELECT status, amount FROM transacciones_webpay WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
        $stmtWp->execute([$id]);
        $txData = $stmtWp->fetch(\PDO::FETCH_ASSOC);

        $estadoWebpay   = $txData['status'] ?? 'sin_pago';
        $montoFijoBanco = $txData['amount'] ?? 0;
        $idPedido       = $id; // Variable requerida por tus formularios en la vista

        // 3. CÁLCULO DE COBRO ACTUAL (Post-edición de productos)
        $subtotal = method_exists($this->pedidoModel, 'calcularSubtotalActual')
            ? $this->pedidoModel->calcularSubtotalActual($id)
            : ($pedido['monto_total'] - ($pedido['costo_envio'] ?? 0) - 490);

        $cobroCliente = $subtotal + ($pedido['costo_envio'] ?? 0) + 490;

        // 4. Historial de Ediciones (Para mostrar el acordeón con evidencias)
        $sqlEdiciones = "SELECT ed.*, u.nombre as admin_nombre FROM pedidos_ediciones ed LEFT JOIN usuarios u ON ed.admin_id = u.id WHERE ed.pedido_id = ? ORDER BY ed.fecha_edicion DESC";
        $stmtEd = $this->db->prepare($sqlEdiciones);
        $stmtEd->execute([$id]);
        $ediciones = $stmtEd->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ediciones as &$ed) {
            $sqlDet = "SELECT * FROM pedidos_ediciones_detalle WHERE edicion_id = ?";
            $stmtDet = $this->db->prepare($sqlDet);
            $stmtDet->execute([$ed['id']]);
            $ed['detalles_cambio'] = $stmtDet->fetchAll(\PDO::FETCH_ASSOC);
        }

        // 5. 🔥 TRAZABILIDAD MÁGICA: Detalle de productos, eliminados, agregados y stock real 🔥
        $sucursalId = $pedido['sucursal_codigo'];
        // Usamos la función maestra que creamos en App\Models\Pedido.php
        if (method_exists($this->pedidoModel, 'obtenerDetallesConEdiciones')) {
            $detalles = $this->pedidoModel->obtenerDetallesConEdiciones($id, $sucursalId);
        } else {
            // Fallback en caso de que aún no hayas guardado el modelo
            $detalles = $this->pedidoModel->obtenerDetalles($id);
        }

        $categorias = $this->db->query("SELECT id, nombre FROM web_categorias WHERE activo=1")->fetchAll(\PDO::FETCH_ASSOC);
        $esAdmin = true;

        ob_start();
        // Las variables $estadoWebpay, $montoFijoBanco, $cobroCliente, $detalles e $idPedido ya están disponibles para la vista
        require_once __DIR__ . '/../../views/admin/detalle_pedido.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
    public function guardarEdicionPedido()
    {
        // 1. Validar Rol (Super Admin o Sucursal)
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedido_id = (int) $_POST['pedido_id'];
            $motivo_cambio = $_POST['motivo_cambio'] ?? 'Cliente lo solicitó';
            $admin_id = $_SESSION['usuario_id']; // Quien hace la edición

            // Asumimos que desde el frontend envías arreglos con los productos agregados y eliminados
            $productos_eliminados = $_POST['productos_eliminados'] ?? [];
            $productos_agregados = $_POST['productos_agregados'] ?? [];

            $monto_original = (int) $_POST['monto_original'];
            $monto_nuevo = (int) $_POST['monto_nuevo'];

            try {
                // Iniciar Transacción
                $this->db->beginTransaction();

                // 1. Crear el registro principal de la edición en `pedidos_ediciones`
                $sqlEdicion = "INSERT INTO pedidos_ediciones (pedido_id, admin_id, motivo_cambio, monto_original, monto_nuevo, fecha_edicion) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $this->db->prepare($sqlEdicion);
                $stmt->execute([$pedido_id, $admin_id, $motivo_cambio, $monto_original, $monto_nuevo]);

                $edicion_id = $this->db->lastInsertId();

                // 2. Registrar los productos ELIMINADOS
                if (!empty($productos_eliminados)) {
                    $sqlDel = "INSERT INTO pedidos_ediciones_detalle (edicion_id, accion, producto_id, cod_producto, nombre_producto, cantidad, precio_unitario) 
                           VALUES (?, 'eliminado', ?, ?, ?, ?, ?)";
                    $stmtDel = $this->db->prepare($sqlDel);

                    foreach ($productos_eliminados as $prod) {
                        $stmtDel->execute([
                            $edicion_id,
                            $prod['id'],
                            $prod['codigo'],
                            $prod['nombre'],
                            $prod['cantidad'],
                            $prod['precio']
                        ]);

                        // Aquí eliminas físicamente el producto de pedidos_detalle
                        $sqlRemove = "DELETE FROM pedidos_detalle WHERE pedido_id = ? AND producto_id = ?";
                        $this->db->prepare($sqlRemove)->execute([$pedido_id, $prod['id']]);
                    }
                }

                // 3. Registrar los productos AGREGADOS
                if (!empty($productos_agregados)) {
                    $sqlAdd = "INSERT INTO pedidos_ediciones_detalle (edicion_id, accion, producto_id, cod_producto, nombre_producto, cantidad, precio_unitario) 
                           VALUES (?, 'agregado', ?, ?, ?, ?, ?)";
                    $stmtAdd = $this->db->prepare($sqlAdd);

                    foreach ($productos_agregados as $prod) {
                        $stmtAdd->execute([
                            $edicion_id,
                            $prod['id'],
                            $prod['codigo'],
                            $prod['nombre'],
                            $prod['cantidad'],
                            $prod['precio']
                        ]);

                        // Aquí insertas el nuevo producto en pedidos_detalle para que sea parte del pedido actual
                        $sqlInsert = "INSERT INTO pedidos_detalle (pedido_id, producto_id, cod_producto, cantidad, precio_neto, precio_bruto) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                        $this->db->prepare($sqlInsert)->execute([
                            $pedido_id,
                            $prod['id'],
                            $prod['codigo'],
                            $prod['cantidad'],
                            $prod['precio_neto'],
                            $prod['precio_bruto']
                        ]);
                    }
                }

                // 4. Actualizar el total en la tabla pedidos
                $sqlUpdatePedido = "UPDATE pedidos SET total_bruto = ? WHERE id = ?";
                $this->db->prepare($sqlUpdatePedido)->execute([$monto_nuevo, $pedido_id]);

                // Confirmar cambios
                $this->db->commit();

                header("Location: " . BASE_URL . "admin/pedidos/ver/$pedido_id?msg=edicion_exitosa");
                exit();
            } catch (Exception $e) {
                $this->db->rollBack();
                // Manejo de error
                header("Location: " . BASE_URL . "admin/pedidos/editar/$pedido_id?error=falla_guardado");
                exit();
            }
        }
    }
    public function guardarEdicion()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');

        $pedidoId = $_POST['pedido_id'] ?? null;
        $pedidoActual = $this->pedidoModel->obtenerPorId($pedidoId);

        if (!$pedidoActual) {
            echo json_encode(['status' => false, 'message' => 'Pedido no encontrado.']);
            exit;
        }


        // Si ya es 3 (Preparación) o superior (porque ya se capturó el dinero), bloqueamos.
        if ((int)$pedidoActual['estado_pedido_id'] >= 3) {
            echo json_encode([
                'status' => false,
                'error' => 'No es posible editar: El pago ya fue capturado y la orden está en preparación.'
            ]);
            exit;
        }

        $motivo      = $_POST['motivo'] ?? 'Cliente lo solicitó';
        $originalRaw = json_decode($_POST['carrito_original'] ?? '[]', true);
        $editadoRaw  = json_decode($_POST['carrito_editado'] ?? '[]', true);
        $adminId     = $_SESSION['user_id'];

        $normalizar = function ($lista) {
            $nueva = [];
            foreach ($lista as $it) {
                $id = $it['producto_id'] ?? $it['id_producto'] ?? $it['id'] ?? null;
                if (!$id) continue;
                $nueva[$id] = [
                    'producto_id'     => (int)$id,
                    'cod_producto'    => $it['cod_producto'] ?? $it['codigo'] ?? 'S/C',
                    'nombre_producto' => $it['nombre_producto'] ?? $it['nombre'] ?? 'Producto',
                    'cantidad'        => (int)$it['cantidad'],
                    'precio_bruto'    => (int)($it['precio_bruto'] ?? $it['precio'] ?? 0),
                    'unidad_medida'   => $it['unidad_medida'] ?? 'UN'
                ];
            }
            return $nueva;
        };

        $mapaOriginal = $normalizar($originalRaw);
        $mapaFinal    = $normalizar($editadoRaw);

        $nuevoTotalProd = 0;
        foreach ($editadoRaw as $item) {
            if (!($item['es_eliminado'] ?? false)) {
                $precio = $item['precio_bruto'] ?? $item['precio'] ?? 0;
                $nuevoTotalProd += ($precio * $item['cantidad']);
            }
        }

        $nuevoTotalERP = $nuevoTotalProd + (int)($pedidoActual['costo_envio'] ?? 0) + 490;

        $itemsEliminados = [];
        $itemsAgregados = [];

        foreach ($mapaOriginal as $id => $org) {
            if (!isset($mapaFinal[$id])) {
                $itemsEliminados[] = $org;
            } elseif ($mapaFinal[$id]['cantidad'] < $org['cantidad']) {
                $dif = $org;
                $dif['cantidad'] = $org['cantidad'] - $mapaFinal[$id]['cantidad'];
                $itemsEliminados[] = $dif;
            }
        }
        foreach ($mapaFinal as $id => $fin) {
            if (!isset($mapaOriginal[$id])) {
                $itemsAgregados[] = $fin;
            } elseif ($fin['cantidad'] > $mapaOriginal[$id]['cantidad']) {
                $dif = $fin;
                $dif['cantidad'] = $fin['cantidad'] - $mapaOriginal[$id]['cantidad'];
                $itemsAgregados[] = $dif;
            }
        }

        try {
            $resultado = $this->pedidoModel->aplicarEdicion(
                $pedidoId,
                $adminId,
                $motivo,
                $itemsEliminados,
                $itemsAgregados,
                array_values($mapaFinal),
                null,
                $nuevoTotalERP,
                0
            );
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }


    // =========================================================
    // 💳 WEBPAY: CAPTURAS Y REEMBOLSOS
    // =========================================================
    public function capturarPago()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedidoId = (int)$_POST['pedido_id'];
            $montoFinal = (int)$_POST['monto_final'];

            try {
                $this->db->beginTransaction();
                $webpay = new \App\Controllers\WebpayController($this->db);
                $res = $webpay->capturarMontoFinal($pedidoId, $montoFinal);

                if ($res['status'] === true) {
                    $this->pedidoModel->descontarStockFisicoFinal($pedidoId);
                    $this->pedidoModel->actualizarEstado($pedidoId, 3);
                    $this->pedidoModel->registrarHistorial($pedidoId, 3, "Pago capturado por $" . number_format($montoFinal, 0, ',', '.'));
                    $this->db->commit();
                    header("Location: " . BASE_URL . "admin/pedido/ver/$pedidoId?msg=captura_ok");
                } else {
                    throw new Exception($res['msg']);
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                header("Location: " . BASE_URL . "admin/pedido/ver/$pedidoId?msg=error_captura&info=" . urlencode($e->getMessage()));
            }
            exit;
        }
    }

    public function anularYReembolsar()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $pedidoId = $data['pedido_id'] ?? null;
        $motivo = $data['motivo'] ?? '';

        $webpay = new \App\Controllers\WebpayController($this->db);
        $res = $webpay->reembolsarPagoAdmin($pedidoId);

        if ($res['status'] === true) {
            $this->pedidoModel->anularYDevolverStock($pedidoId, $_SESSION['user_id'], $motivo);
            echo json_encode(['status' => true, 'message' => 'Dinero devuelto y pedido anulado.']);
        } else {
            echo json_encode(['status' => false, 'message' => $res['msg']]);
        }
        exit;
    }

    // =========================================================
    // 📁 COMPROBANTES Y ESTADOS
    // =========================================================
    public function subirComprobante()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.']);
            return;
        }

        $pedidoId  = $_POST['pedido_id'] ?? $_POST['id_pedido'] ?? null;
        $medioPago = $_POST['medio_pago_real'] ?? null;
        $folio     = $_POST['folio_documento'] ?? null;

        if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'msg' => 'No se subió ningún archivo o hubo un error al procesarlo.']);
            return;
        }

        $archivo = $_FILES['comprobante'];
        $nombreTemporal = $archivo['tmp_name'];
        $nombreArchivo = time() . '_' . basename($archivo['name']);
        $rutaDestino = __DIR__ . '/../../public/img/comprobantes/' . $nombreArchivo;

        if (move_uploaded_file($nombreTemporal, $rutaDestino)) {
            // 1. Guardamos los datos del comprobante y folio
            $this->pedidoModel->guardarComprobante($pedidoId, $nombreArchivo, $medioPago, $folio);

            // 🔥 AUTOMATIZACIÓN: Al subir comprobante, el pedido pasa directo a 'En Preparación' (ID 3)
            // Esto garantiza que el flujo avance solo.
            $this->pedidoModel->actualizarEstado($pedidoId, 3);

            header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=comprobante_ok");
            exit;
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al mover el archivo al servidor.']);
        }
    }
    public function actualizarEstadoManual()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['pedido_id'];
            $estadoId = (int)$_POST['estado_id'];
            $this->pedidoModel->actualizarEstado($id, $estadoId);
            $this->pedidoModel->registrarHistorial($id, $estadoId);

            // Lógica de Mail
            try {
                $pedido = $this->pedidoModel->obtenerPorId($id);
                if ($pedido && !empty($pedido['email_cliente'])) {
                    $mail = new \App\Services\MailService();
                    $stmt = $this->db->prepare("SELECT nombre FROM estados_pedido WHERE id = ?");
                    $stmt->execute([$estadoId]);
                    $mail->enviarActualizacionEstado($pedido['email_cliente'], $pedido['nombre_cliente'], $id, $stmt->fetchColumn(), $pedido['numero_seguimiento'] ?? '---');
                }
            } catch (Exception $e) {
            }

            header("Location: " . BASE_URL . "admin/pedido/ver/$id?msg=ok");
            exit;
        }
    }
public function exportar()
    {
        $this->verificarAdmin();
        
        $pedidos = $this->pedidoModel->obtenerFiltrados(
            $_GET['desde'] ?? date('Y-m-01'), 
            $_GET['hasta'] ?? date('Y-m-d'), 
            $_GET['q'] ?? '', 
            $_GET['estado'] ?? '', 
            999999, 
            0, 
            $_SESSION['admin_sucursal'] ?? null
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Ventas_Cencocal_' . date('Ymd_Hi') . '.csv"');
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel reconozca los tildes y eñes correctamente sin romper el texto
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); 

        // 1. Nuevas Cabeceras de Columnas (Idénticas a la vista)
        fputcsv($output, [
            'Orden de Pedido', 
            'Fecha y Hora', 
            'Cliente', 
            'RUT', 
            'Logística', 
            'Modalidad de Pago', 
            'Estado Pago', 
            'Facturar (ERP)', 
            'Estado Pedido'
        ], ';');

        // 2. Procesamiento de filas
        foreach ($pedidos as $p) {
            // Orden de Pedido Formateada (Ej: 000311)
            $ordenId = str_pad($p['id'] ?? 0, 6, '0', STR_PAD_LEFT);

            // Fecha y Hora combinada y formateada
            $fechaRaw = $p['fecha_creacion'] ?? '';
            $horaRaw = $p['hora_creacion'] ?? '00:00';
            $fechaHoraObj = \DateTime::createFromFormat('Ymd H:i', $fechaRaw . ' ' . $horaRaw);
            $fechaHoraFmt = $fechaHoraObj ? $fechaHoraObj->format('d/m/Y H:i') : '---';

            // Cliente y RUT
            $cliente = $p['nombre_cliente'] ?? 'Cliente Web';
            $rut = $p['rut_cliente'] ?? '---';

            // Logística
            $tipoEntrega = (int)($p['tipo_entrega_id'] ?? 1);
            $logistica = ($tipoEntrega === 2) ? 'Retiro en Tienda' : 'Despacho a Domicilio';

            // Modalidad y Estado de Pago
            $formaPagoId = (int)($p['forma_pago_id'] ?? 5);
            $estadoPagoId = (int)($p['estado_pago_id'] ?? 1);
            
            if ($formaPagoId === 8) {
                $modalidadPago = 'Pago en Tienda';
                $estadoPago = ($estadoPagoId >= 2) ? 'Pagado' : 'Pendiente';
            } elseif ($formaPagoId === 7) {
                $modalidadPago = 'Contra Entrega';
                $estadoPago = ($estadoPagoId >= 2) ? 'Pagado' : 'Pendiente';
            } else {
                $modalidadPago = 'Webpay Plus';
                if ($estadoPagoId === 3) {
                    $estadoPago = 'Capturado';
                } elseif ($estadoPagoId === 2) {
                    $estadoPago = 'Retenido';
                } else {
                    $estadoPago = 'Pendiente';
                }
            }

            // Matemática (Facturar ERP)
            $totalERP = (int)($p['monto_total'] ?? $p['total_bruto'] ?? 0);

            // Estado Pedido (Logística General)
            $estadoPedido = strtoupper($p['estado'] ?? 'PENDIENTE');

            // Escribir la fila en el CSV
            fputcsv($output, [
                $ordenId,
                $fechaHoraFmt,
                $cliente,
                $rut,
                $logistica,
                $modalidadPago,
                $estadoPago,
                $totalERP,
                $estadoPedido
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}
