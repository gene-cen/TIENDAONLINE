<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Sucursal;
use App\Models\Direccion;

class CheckoutController
{
    private $db;
    private $pedidoModel;
    private $productoModel;
    private $userModel;
    private $sucursalModel;
    private $direccionModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);
        $this->userModel = new Usuario($db);
        $this->sucursalModel = new Sucursal($db);
        $this->direccionModel = new Direccion($db);
    }
public function index()
    {
        // 1. SEGURIDAD: Si el carrito está vacío
        if (empty($_SESSION['carrito'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // --- EL GUARDIÁN: Validación preventiva antes de mostrar el formulario ---
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
        $erroresStock = $this->validarStockAntesDePagar($sucursal_id);
        if (!empty($erroresStock)) {
            $_SESSION['checkout_errors'] = $erroresStock;
            header("Location: " . BASE_URL . "carrito/ver");
            exit;
        }

        $usuario = new \stdClass();
        $telefonos = [];
        $direcciones = [];
        $esAsistido = false;

        // 🔥 1. MODO VENTA ASISTIDA (Normalizado)
        // Eliminamos el chequeo de $_SESSION['rol'] (texto) y usamos rol_id (numérico)
        if (isset($_SESSION['rol_id']) && in_array((int)$_SESSION['rol_id'], [1, 2]) && isset($_GET['modo']) && $_GET['modo'] === 'asistido') {
            $esAsistido = true;
            $usuario->id = null;
            $usuario->nombre = "MODO: VENTA ASISTIDA";
            $usuario->rut = ""; 
            $usuario->email = ""; 
            $usuario->telefono_principal = "";
            $usuario->razon_social = ""; 
            $usuario->giro = "";
            
            // Usamos los nuevos nombres de propiedad que vienen del JOIN
            $usuario->direccion_principal = ""; 
            $usuario->comuna_id = null; 
            $usuario->nombre_comuna = "Seleccione Comuna";
            $usuario->es_cliente_confianza = 0;
        }
        
        // 🔥 2. USUARIO REGISTRADO (Normalizado)
        elseif (!empty($_SESSION['user_id'])) {
            // El método getById ahora trae direccion_principal y nombre_rol vía JOIN
            $usuario = $this->userModel->getById($_SESSION['user_id']);
            
            if (method_exists($this->userModel, 'getTelefonos')) {
                $telefonos = $this->userModel->getTelefonos($_SESSION['user_id']);
            }
            
            $usuario->telefono_principal = '';
            foreach ($telefonos as $t) {
                if ($t->es_principal) { $usuario->telefono_principal = $t->numero; break; }
            }

            // Traemos su libreta de direcciones completa
            $direcciones = $this->direccionModel->obtenerPorUsuario($_SESSION['user_id']);
            
            // Aseguramos valores por defecto para evitar Warnings de PHP
            $usuario->es_cliente_confianza = $usuario->es_cliente_confianza ?? 0;
            $usuario->razon_social = $usuario->razon_social ?? '';
            $usuario->giro = $usuario->giro ?? '';
        }
        
        // 🔥 3. INVITADO (Normalizado)
        elseif (!empty($_SESSION['invitado'])) {
            $invitado = $_SESSION['invitado'];
            $usuario->id = null;
            $usuario->nombre = $invitado['nombre'];
            $usuario->rut = $invitado['rut'];
            $usuario->email = $invitado['email'];
            $usuario->telefono_principal = $invitado['telefono'];
            $usuario->razon_social = "";
            $usuario->giro = "";
            
            // Propiedades de dirección vacías para el formulario de invitado
            $usuario->direccion_principal = ''; 
            $usuario->nombre_comuna = '';
            $usuario->comuna_id = null; 
            $usuario->es_cliente_confianza = 0;
        }
        else {
            header("Location: " . BASE_URL . "carrito/ver?auth=requerido");
            exit();
        }

        // --- CARGA DE LISTAS PARA SELECTS ---
        $sucursales = $this->sucursalModel->obtenerParaRetiro();
        $listaComunas = [];
        try {
            if (method_exists($this->userModel, 'obtenerComunasValparaiso')) {
                $listaComunas = $this->userModel->obtenerComunasValparaiso();
            } else {
                $stmt = $this->db->query("SELECT * FROM comunas ORDER BY nombre ASC");
                $listaComunas = $stmt->fetchAll(\PDO::FETCH_OBJ);
            }
        } catch (\Exception $e) { error_log("Error cargando comunas en Checkout: " . $e->getMessage()); }

        ob_start();
        include __DIR__ . '/../../views/shop/checkout.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrito'])) {
            if (ob_get_level()) ob_end_clean();
            ob_start();

            // 🔥 VALIDACIÓN FINAL DE STOCK (JUST-IN-TIME) 🔥
            // Si alguien compró el último pack de pañales mientras el cliente llenaba el Rut, aquí lo detectamos.
            $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
            $erroresStock = $this->validarStockAntesDePagar($sucursal_id);
            if (!empty($erroresStock)) {
                $_SESSION['checkout_errors'] = $erroresStock;
                header("Location: " . BASE_URL . "carrito/ver");
                exit;
            }

            // 1. IDENTIFICACIÓN DE DATOS (Mantenemos todas tus variables originales)
            $tipoCliente = 'registrado';
            $adminAsistenteId = null;
            $usuarioIdBD = $_SESSION['user_id'] ?? null;
            $esClienteConfianza = 0;
            $correoCliente = null;
            $primerNombre = $segundoNombre = $primerApellido = $segundoApellido = null;

            if (!empty($_POST['venta_asistida_flag'])) {
                $tipoCliente = 'asistido';
                $adminAsistenteId = $_SESSION['user_id'];
                $usuarioIdBD = null;
                $rutCliente = $_POST['asistido_rut'];
                $correoCliente = !empty($_POST['asistido_email']) ? trim($_POST['asistido_email']) : null;
                $primerNombre = $_POST['asistido_p_nombre'] ?? '';
                $primerApellido = $_POST['asistido_p_apellido'] ?? '';
                $segundoNombre = $_POST['asistido_s_nombre'] ?? null;
                $segundoApellido = $_POST['asistido_s_apellido'] ?? null;
                $nombreCliente = trim("$primerNombre $segundoNombre $primerApellido $segundoApellido");
                $telefonoContacto = "+569" . preg_replace('/[^0-9]/', '', $_POST['asistido_telefono'] ?? '');
            } elseif (!empty($_SESSION['user_id'])) {
                $user = $this->userModel->getById($_SESSION['user_id']);
                $rutCliente = $user->rut;
                $nombreCliente = $user->nombre;
                $correoCliente = $user->email;
                $esClienteConfianza = $user->es_cliente_confianza ?? 0;
                $telefonoContacto = $_POST['telefono_contacto'] ?? '';
                $partesNombre = explode(' ', trim($nombreCliente));
                $primerNombre = $partesNombre[0] ?? '';
                $primerApellido = $partesNombre[1] ?? '';
            } else {
                $tipoCliente = 'invitado';
                $rutCliente = $_SESSION['invitado']['rut'] ?? '';
                $nombreCliente = $_SESSION['invitado']['nombre'] ?? 'Invitado';
                $correoCliente = $_SESSION['invitado']['email'] ?? null;
                $telefonoContacto = $_SESSION['invitado']['telefono'] ?? '';
                $partesNombre = explode(' ', trim($nombreCliente));
                $primerNombre = $partesNombre[0] ?? 'Invitado';
                $primerApellido = $partesNombre[1] ?? '';
            }

            try {
                $tipoEntrega = (int)$_POST['tipo_entrega'];
                date_default_timezone_set('America/Santiago');
                $fechaProcesamiento = new \DateTime();

                // Lógica de Fechas (Mantenida 1:1)
                if ($tipoCliente === 'asistido') {
                    if ($tipoEntrega === 2) { $fechaEntrega = $fechaProcesamiento->format('Y-m-d H:i:s'); } 
                    else { $fechaProcesamiento->modify('+2 hours'); $fechaEntrega = $fechaProcesamiento->format('Y-m-d H:i:s'); }
                } else {
                    if ((int)$fechaProcesamiento->format('H') >= 15 || in_array((int)$fechaProcesamiento->format('w'), [0, 6])) {
                        $fechaProcesamiento->modify('+1 day');
                        while (in_array((int)$fechaProcesamiento->format('w'), [0, 6])) $fechaProcesamiento->modify('+1 day');
                    }
                    $diasAgregados = 0;
                    while ($diasAgregados < 2) {
                        $fechaProcesamiento->modify('+1 day');
                        if (!in_array((int)$fechaProcesamiento->format('w'), [0, 6])) $diasAgregados++;
                    }
                    $fechaEntrega = $fechaProcesamiento->format('Y-m-d');
                }

                $totalBruto = 0; $cantidad_total_productos = 0;
                foreach ($_SESSION['carrito'] as $id => $item) {
                    $totalBruto += $item['precio'] * $item['cantidad'];
                    $cantidad_total_productos += $item['cantidad'];
                }
                $cantidad_items = count($_SESSION['carrito']);
                $totalNeto = round($totalBruto / 1.19);
                $costoEnvio = ($tipoEntrega === 2 || $totalBruto >= 39950) ? 0 : 2990;
                $costoServicio = 490;

                $sucursalActivaID = $_SESSION['sucursal_activa'] ?? 29;
                $sucursalCodigo = ($sucursalActivaID == 10) ? '10' : '29';

                if ($tipoEntrega === 2) {
                    $comunaId = ($sucursalCodigo === '10') ? 82 : 63;
                    $direccionTexto = "RETIRO EN TIENDA: Sucursal " . $sucursalCodigo;
                } else {
                    $comunaId = !empty($_POST['nueva_comuna_id']) ? (int)$_POST['nueva_comuna_id'] : null;
                    $direccionTexto = $_POST['direccion'] ?? '';
                    if (!$comunaId && !empty($_SESSION['user_id']) && $tipoCliente !== 'asistido') {
                        $userFull = $this->userModel->getById($_SESSION['user_id']);
                        $comunaId = is_object($userFull) ? ($userFull->comuna_id ?? null) : ($userFull['comuna_id'] ?? null);
                    }
                }

                $metodoPagoInput = $_POST['metodo_pago_final'] ?? $_POST['metodo_pago'] ?? 'webpay';
                $formaPagoFinal = ($tipoCliente === 'asistido') ? 8 : (($metodoPagoInput === 'contra_entrega' && $esClienteConfianza == 1) ? 7 : 5);

                $rutLimpio = str_replace(['.', '-'], '', $rutCliente);
                $rutERP = str_pad($rutLimpio, 11, '0', STR_PAD_LEFT);
                $rangoHorarioId = null;

                $datosPedido = [
                    'usuario_id' => $usuarioIdBD, 'rut_cliente' => $rutERP, 'sucursal_codigo' => $sucursalCodigo,
                    'vendedor_codigo' => '0003', 'total_neto' => $totalNeto,
                    'monto_total' => $totalBruto + $costoEnvio + $costoServicio, 'costo_envio' => $costoEnvio,
                    'direccion_entrega_texto' => $direccionTexto, 'comuna_id' => $comunaId,
                    'estado_pedido_id' => 1, 'estado_pago_id' => 1, 'forma_pago_id' => $formaPagoFinal,
                    'cantidad_items' => $cantidad_items, 'cantidad_total_productos' => $cantidad_total_productos,
                    'tipo_entrega_id' => $tipoEntrega, 'nombre_destinatario' => $nombreCliente,
                    'telefono_contacto' => $telefonoContacto, 'email_cliente' => $correoCliente,
                    'tipo_cliente' => $tipoCliente, 'primer_nombre' => $primerNombre, 'primer_apellido' => $primerApellido,
                    'segundo_nombre' => $segundoNombre, 'segundo_apellido' => $segundoApellido,
                    'admin_asistente_id' => $adminAsistenteId, 'fecha_entrega_estimada' => $fechaEntrega,
                    'rango_horario_id' => $rangoHorarioId
                ];

                $this->db->beginTransaction();
                $resultado = $this->pedidoModel->crear($datosPedido);
                $pedidoId = $resultado['id'];

                foreach ($_SESSION['carrito'] as $key => $item) {
                    $idProducto = $item['id'] ?? $key;
                    $productoReal = $this->productoModel->getById($idProducto);
                    if ($productoReal) {
                        $productoReal = (object)$productoReal;
                        $productoReal->id = $idProducto;
                        $this->pedidoModel->agregarDetalle($pedidoId, $productoReal, $item['cantidad'], [
                            'neto' => round($item['precio'] / 1.19), 'bruto' => $item['precio']
                        ]);
                    }
                }

                $montoFinalReal = $totalBruto + $costoEnvio + $costoServicio;
                $this->db->prepare("UPDATE pedidos SET monto_total = ? WHERE id = ?")->execute([$montoFinalReal, $pedidoId]);
                $this->db->commit();

                // Lógica de Correos (Mantenida)
                if ($tipoCliente === 'asistido' && class_exists('\App\Services\MailService')) {
                    $mailService = new \App\Services\MailService();
                    $correoAdmin = $_SESSION['email'] ?? 'admin@cencocal.cl';
                    $mailService->enviarConfirmacion($correoAdmin, $pedidoId);
                    if (!empty($correoCliente)) $mailService->enviarConfirmacion($correoCliente, $pedidoId);
                }

                unset($_SESSION['carrito']);

                if ($formaPagoFinal == 5) {
                    header("Location: " . BASE_URL . "webpay/iniciar?id=" . $pedidoId);
                } else {
                    $this->pedidoModel->reservarStock($pedidoId);
                    $msg = ($tipoCliente === 'asistido') ? 'asistido_ok' : 'exito';
                    header("Location: " . BASE_URL . "pedido/exito?id=" . $pedidoId . "&type=" . $msg);
                }
                exit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                die("<div style='padding: 30px; text-align: center; font-family: sans-serif;'>
                        <h1 style='color: #dc3545;'>Error en el Checkout</h1>
                        <p>{$e->getMessage()}</p>
                    </div>");
            }
        }
    }

    /**
     * 🔥 LA FUNCIÓN GUARDIANA (Basada en tu nueva regla unificada)
     */
    private function validarStockAntesDePagar($sucursal_id)
    {
        $errores = [];
        $sqlStock = Producto::getSqlStockDisponible('ps', 'piw');

        foreach ($_SESSION['carrito'] as $id => $item) {
            $sql = "SELECT {$sqlStock} as stock_disponible
                    FROM productos p
                    INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                    WHERE p.id = ? AND ps.sucursal_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $sucursal_id]);
            $disponible = (int)$stmt->fetchColumn();

            if ($disponible < $item['cantidad']) {
                $errores[] = "No hay stock suficiente de '{$item['nombre']}'. Disponibles para web: $disponible.";
            }
        }
        return $errores;
    }
}