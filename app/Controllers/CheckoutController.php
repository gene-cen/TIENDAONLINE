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
        // 1. SEGURIDAD: Si el carrito está vacío, no hay nada que pagar
        if (empty($_SESSION['carrito'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        // 2. PREPARACIÓN DE DATOS SEGÚN TIPO DE USUARIO
        $usuario = new \stdClass();
        $telefonos = [];
        $direcciones = [];
        $esAsistido = false;

        // --- CASO A: Venta Asistida ---
        if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin' && isset($_GET['modo']) && $_GET['modo'] === 'asistido') {
            $esAsistido = true;
            $usuario->id = null;
            $usuario->nombre = "MODO: VENTA ASISTIDA";
            $usuario->rut = "";
            $usuario->email = "";
            $usuario->telefono_principal = "";
            $usuario->direccion = "";
            $usuario->comuna_id = null;
            $usuario->es_cliente_confianza = 0;
        }
        // --- CASO B: Usuario Registrado Logueado ---
        elseif (!empty($_SESSION['user_id'])) {
            $usuario = $this->userModel->getById($_SESSION['user_id']);

            if (method_exists($this->userModel, 'getTelefonos')) {
                $telefonos = $this->userModel->getTelefonos($_SESSION['user_id']);
            }

            $usuario->telefono_principal = '';
            foreach ($telefonos as $t) {
                if ($t->es_principal) {
                    $usuario->telefono_principal = $t->numero;
                    break;
                }
            }
            $direcciones = $this->direccionModel->obtenerPorUsuario($_SESSION['user_id']);
            $usuario->es_cliente_confianza = $usuario->es_cliente_confianza ?? 0;
        }
        // --- CASO C: Invitado ---
        elseif (!empty($_SESSION['invitado'])) {
            $invitado = $_SESSION['invitado'];
            $usuario->id = null;
            $usuario->nombre = $invitado['nombre'];
            $usuario->rut = $invitado['rut'];
            $usuario->email = $invitado['email'];
            $usuario->telefono_principal = $invitado['telefono'];
            $usuario->direccion = '';
            $usuario->comuna_id = null;
            $usuario->es_cliente_confianza = 0;
        }
        // --- CASO D: Nadie identificado ---
        else {
            header("Location: " . BASE_URL . "carrito/ver?auth=requerido");
            exit();
        }

        $sucursales = $this->sucursalModel->obtenerParaRetiro();
        $listaComunas = [];

        try {
            if (method_exists($this->userModel, 'obtenerComunasValparaiso')) {
                $listaComunas = $this->userModel->obtenerComunasValparaiso();
            } else {
                $stmt = $this->db->query("SELECT * FROM comunas ORDER BY nombre ASC");
                $listaComunas = $stmt->fetchAll(\PDO::FETCH_OBJ);
            }
        } catch (\Exception $e) {
            error_log("Error obteniendo comunas en Checkout: " . $e->getMessage());
        }

        ob_start();
        include __DIR__ . '/../../views/shop/checkout.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrito'])) {

            if (ob_get_level()) ob_end_clean();
            ob_start();

            // 1. IDENTIFICACIÓN DE DATOS
            $tipoCliente = 'registrado';
            $adminAsistenteId = null;
            $usuarioIdBD = $_SESSION['user_id'] ?? null;
            $esClienteConfianza = 0;
            $correoCliente = null;

            $primerNombre = null;
            $segundoNombre = null;
            $primerApellido = null;
            $segundoApellido = null;

            if (!empty($_POST['venta_asistida_flag'])) {
                // --- CASO: VENTA ASISTIDA ---
                $tipoCliente = 'asistido';
                $adminAsistenteId = $_SESSION['user_id'];
                $usuarioIdBD = null; // Desvinculamos del perfil de Admin

                $rutCliente = $_POST['asistido_rut'];
                $correoCliente = !empty($_POST['asistido_email']) ? trim($_POST['asistido_email']) : null;

                $primerNombre = $_POST['asistido_p_nombre'] ?? '';
                $primerApellido = $_POST['asistido_p_apellido'] ?? '';
                $segundoNombre = $_POST['asistido_s_nombre'] ?? null;
                $segundoApellido = $_POST['asistido_s_apellido'] ?? null;

                $nombreCliente = trim("$primerNombre $segundoNombre $primerApellido $segundoApellido");
                $telefonoContacto = "+569" . preg_replace('/[^0-9]/', '', $_POST['asistido_telefono'] ?? '');
            } elseif (!empty($_SESSION['user_id'])) {
                // --- CASO: USUARIO REGISTRADO ---
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
                // --- CASO: INVITADO ---
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

                // 2. LÓGICA DE FECHAS
                date_default_timezone_set('America/Santiago');
                $fechaProcesamiento = new \DateTime();

                if ($tipoCliente === 'asistido') {
                    if ($tipoEntrega === 2) {
                        $fechaEntrega = $fechaProcesamiento->format('Y-m-d H:i:s');
                    } else {
                        $fechaProcesamiento->modify('+2 hours');
                        $fechaEntrega = $fechaProcesamiento->format('Y-m-d H:i:s');
                    }
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

                // 3. TOTALES, COSTOS Y CANTIDADES (MODIFICADO)
                $totalBruto = 0;
                $cantidad_items = count($_SESSION['carrito']); // Cuántos productos distintos hay
                $cantidad_total_productos = 0; // Cuántas unidades físicas hay en total

                foreach ($_SESSION['carrito'] as $id => $item) {
                    $totalBruto += $item['precio'] * $item['cantidad'];
                    $cantidad_total_productos += $item['cantidad'];
                }

                $totalNeto = round($totalBruto / 1.19);
                $costoEnvio = ($tipoEntrega === 2 || $totalBruto >= 39950) ? 0 : 2990;
                $costoServicio = 490;

                // 4. LÓGICA DE COMUNA BLINDADA POR SUCURSAL ACTIVA
                // Forzamos a que siempre se respete la sucursal de la sesión actual
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

                // 5. MEDIO DE PAGO
                $metodoPagoInput = $_POST['metodo_pago_final'] ?? $_POST['metodo_pago'] ?? 'webpay';
                $formaPagoFinal = 5;

                if ($tipoCliente === 'asistido') {
                    $formaPagoFinal = 8; // Pago en Tienda Físico
                } elseif ($metodoPagoInput === 'contra_entrega' && $esClienteConfianza == 1) {
                    $formaPagoFinal = 7;
                }

                $rutLimpio = str_replace(['.', '-'], '', $rutCliente);
                $rutERP = str_pad($rutLimpio, 11, '0', STR_PAD_LEFT);

                // Asignamos un rango horario nulo o por defecto (ya que no lo envías desde la vista)
                $rangoHorarioId = null;

                // 6. DATA FINAL PARA INSERTAR EN BD
                $datosPedido = [
                    'usuario_id'               => $usuarioIdBD,
                    'rut_cliente'              => $rutERP,
                    'sucursal_codigo'          => $sucursalCodigo,
                    'vendedor_codigo'          => '0003',
                    'total_neto'               => $totalNeto,
                    'monto_total'              => $totalBruto + $costoEnvio + $costoServicio,
                    'costo_envio'              => $costoEnvio,
                    'direccion_entrega_texto'  => $direccionTexto,
                    'comuna_id'                => $comunaId,
                    'estado_pedido_id'         => 1,
                    'estado_pago_id'           => 1, // Se agrega el estado_pago inicial explícitamente (1 = Pendiente)
                    'forma_pago_id'            => $formaPagoFinal,
                    'cantidad_items'           => $cantidad_items, // NUEVO
                    'cantidad_total_productos' => $cantidad_total_productos, // NUEVO
                    'tipo_entrega_id'          => $tipoEntrega,
                    'nombre_destinatario'      => $nombreCliente,
                    'telefono_contacto'        => $telefonoContacto,
                    'email_cliente'            => $correoCliente,
                    'tipo_cliente'             => $tipoCliente,
                    'primer_nombre'            => $primerNombre,
                    'primer_apellido'          => $primerApellido,
                    'segundo_nombre'           => $segundoNombre,
                    'segundo_apellido'         => $segundoApellido,
                    'admin_asistente_id'       => $adminAsistenteId,
                    'fecha_entrega_estimada'   => $fechaEntrega,
                    'rango_horario_id'         => $rangoHorarioId // NUEVO
                ];

                $this->db->beginTransaction();
                $resultado = $this->pedidoModel->crear($datosPedido);
                $pedidoId = $resultado['id'];

                // 🔥 BLOQUE CORREGIDO: INYECCIÓN DE ID PARA EVITAR NULL EN BD 🔥
                foreach ($_SESSION['carrito'] as $key => $item) {
                    
                    // Aseguramos capturar el ID desde la llave o el item
                    $idProducto = $item['id'] ?? $item['producto_id'] ?? $key;
                    
                    $productoReal = $this->productoModel->getById($idProducto);
                    
                    if (is_array($productoReal)) {
                        if (is_array($productoReal)) {
                            $productoReal = (object)$productoReal;
                        }

                        // Forzamos la asignación del ID para que agregarDetalle lo encuentre sí o sí
                        $productoReal->id = $idProducto;
                        $productoReal->producto_id = $idProducto;

                        $this->pedidoModel->agregarDetalle($pedidoId, $productoReal, $item['cantidad'], [
                            'neto' => round($item['precio'] / 1.19),
                            'bruto' => $item['precio']
                        ]);
                    }
                }

                $montoFinalReal = $totalBruto + $costoEnvio + $costoServicio;
                $this->db->prepare("UPDATE pedidos SET monto_total = ? WHERE id = ?")->execute([$montoFinalReal, $pedidoId]);

                $this->db->commit();

                // --- LÓGICA DE CORREOS ---
                if ($tipoCliente === 'asistido' && class_exists('\App\Services\MailService')) {
                    $mailService = new \App\Services\MailService();
                    $correoAdmin = $_SESSION['email'] ?? 'admin@cencocal.cl';

                    // Enviar siempre comprobante interno al admin
                    $mailService->enviarConfirmacion($correoAdmin, $pedidoId);

                    // Si el cliente dio su correo, le enviamos copia
                    if (!empty($correoCliente)) {
                        $mailService->enviarConfirmacion($correoCliente, $pedidoId);
                    }
                }

                unset($_SESSION['carrito']);

                // 7. REDIRECCIÓN FINAL
                if ($formaPagoFinal == 5) {
                    // Cambiamos "pagar" por "iniciar" para que coincida con el WebpayController
                    header("Location: " . BASE_URL . "webpay/iniciar?id=" . $pedidoId);
                } else {
                    $this->pedidoModel->reservarStock($pedidoId);
                    $msg = ($tipoCliente === 'asistido') ? 'asistido_ok' : 'exito';
                    header("Location: " . BASE_URL . "pedido/exito?id=" . $pedidoId . "&type=" . $msg);
                }
                exit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();

                // ESTO CONGELARÁ LA PANTALLA Y NOS MOSTRARÁ EL ERROR REAL
                die("<div style='padding: 30px; text-align: center; font-family: sans-serif; background: #fff;'>
                        <h1 style='color: #dc3545;'>¡Ups! Error en la Base de Datos</h1>
                        <p style='font-size: 18px;'>Algo falló al intentar guardar el pedido. El error exacto es:</p>
                        <div style='background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24; display: inline-block; font-weight: bold; font-size: 16px; border: 1px solid #f5c6cb;'>
                            " . $e->getMessage() . "
                        </div>
                    </div>");
            }
        }
    }
}
