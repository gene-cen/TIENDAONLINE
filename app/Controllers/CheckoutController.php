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

    // Paso 1: Mostrar resumen antes de pagar
    public function index()
    {
        // 1. SEGURIDAD
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        if (empty($_SESSION['carrito'])) {
            header("Location: " . BASE_URL . "home");
            exit();
        }

        $usuario = $this->userModel->getById($_SESSION['user_id']);
        $telefonos = $this->userModel->getTelefonos($_SESSION['user_id']);

        // Buscamos el teléfono principal para pasarlo como "Titular"
        $usuario->telefono_principal = '';
        foreach ($telefonos as $t) {
            if ($t->es_principal) {
                $usuario->telefono_principal = $t->numero;
                break;
            }
        }
        // 2. OBTENER DATOS PARA VISTA
        $sucursales = $this->sucursalModel->obtenerParaRetiro();
        $direcciones = $this->direccionModel->obtenerPorUsuario($_SESSION['user_id']);

        // --- OBTENER LISTA DE TELÉFONOS (Normalización con Alias) ---
        $telefonos = [];
        if (method_exists($this->userModel, 'getTelefonos')) {
            $telefonos = $this->userModel->getTelefonos($_SESSION['user_id']);
        }

        $listaComunas = [];
        try {
            if (method_exists($this->userModel, 'obtenerComunasValparaiso')) {
                $listaComunas = $this->userModel->obtenerComunasValparaiso();
            } else {
                // Query de respaldo si el método no existe
                $stmt = $this->db->query("SELECT * FROM comunas WHERE provincia_id IN (SELECT id FROM provincias WHERE region_id = 6) ORDER BY nombre ASC");
                $listaComunas = $stmt->fetchAll(\PDO::FETCH_OBJ);
            }
        } catch (\Exception $e) {
            error_log("Error obteniendo comunas: " . $e->getMessage());
        }

        // 3. RENDERIZAR
        ob_start();
        include __DIR__ . '/../../views/shop/checkout.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // Paso 2: Procesar la compra
    /**
     * Paso 2: Procesar la compra y derivar a Webpay
     * Se encarga de la persistencia del pedido en estado "Pendiente" (1) 
     * y la redirección limpia a la pasarela de pago.
     */
    public function procesar()
    {
        // Validamos que la petición sea POST y existan datos de sesión
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrito']) && isset($_SESSION['user_id'])) {

            // 1. SEGURIDAD DE SALIDA (Output Buffering)
            if (ob_get_level()) ob_end_clean();
            ob_start();

            try {
                $user = $this->userModel->getById($_SESSION['user_id']);

                // 2. LÓGICA DE FECHA Y RANGO HORARIO
                date_default_timezone_set('America/Santiago');
                $horaActual = (int)date('H');
                $fechaEntrega = ($horaActual >= 17) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');

                if ($horaActual >= 17) $rangoId = 1;
                else {
                    if ($horaActual < 9) $rangoId = 1;
                    elseif ($horaActual < 11) $rangoId = 2;
                    elseif ($horaActual < 15) $rangoId = 3;
                    else $rangoId = 4;
                }

                // 3. PROCESAMIENTO DE TELÉFONOS
                $telefonoContacto = $_POST['telefono_contacto'] ?? '';
                $segundoSeleccionado = $_POST['telefono_seleccionado_2'] ?? '';
                $telefonoContacto2 = null;

                if ($segundoSeleccionado === 'nuevo') {
                    $telefonoContacto2 = $_POST['telefono_nuevo_2'] ?? null;
                    if (!empty($_POST['guardar_nuevo_2']) && !empty($telefonoContacto2)) {
                        $alias = !empty($_POST['nuevo_alias_2']) ? $_POST['nuevo_alias_2'] : 'Contacto Alternativo';
                        $this->userModel->agregarTelefono($user->id, $telefonoContacto2, $alias, 0);
                    }
                } elseif (!empty($segundoSeleccionado)) {
                    $telefonoContacto2 = $segundoSeleccionado;
                }

                // 4. CÁLCULO DE TOTALES
                $totalBruto = 0;
                $cantidadItems = 0;
                foreach ($_SESSION['carrito'] as $id => $item) {
                    $totalBruto += $item['precio'] * $item['cantidad'];
                    $cantidadItems += $item['cantidad'];
                }
                $totalNeto = round($totalBruto / 1.19);

                // 5. LÓGICA LOGÍSTICA
                $tipoEntrega = isset($_POST['tipo_entrega']) ? (int)$_POST['tipo_entrega'] : 1;
                $sucursalCodigo = '10';
                $vendedorCodigo = '0003';
                $costoEnvio = ($totalBruto > 49950) ? 0 : 1990;

                $origenDireccion = $_POST['origen_direccion'] ?? 'perfil';
                $direccionTexto = $_POST['direccion'] ?? $user->direccion;
                $comunaId = $_POST['nueva_comuna_id'] ?? $user->comuna_id;
                $latitud = $_POST['nueva_lat'] ?? null;
                $longitud = $_POST['nueva_lng'] ?? null;
                if ($tipoEntrega === 2) {
                    // Caso Retiro: Usamos !empty para asegurar que si viene en blanco (""), se asigne un valor por defecto.
                    $sucursalCodigo = !empty($_POST['sucursal_codigo']) ? trim($_POST['sucursal_codigo']) : '29'; // Ponemos 29 como default si es tu matriz
                    $direccionTexto = "RETIRO EN TIENDA: Sucursal " . $sucursalCodigo;
                    $comunaId = $user->comuna_id;
                    $costoEnvio = 0;
                } else if (is_numeric($origenDireccion)) {
                    $dirGuardada = $this->direccionModel->obtenerPorId($origenDireccion, $user->id);
                    if ($dirGuardada) {
                        $direccionTexto = $dirGuardada->direccion;
                        $comunaId = $dirGuardada->comuna_id;
                        $latitud = $dirGuardada->latitud;
                        $longitud = $dirGuardada->longitud;
                    }
                }

                $idsComunasInterior = [63, 66, 64, 65, 62, 80];
                if (in_array((int)$comunaId, $idsComunasInterior)) {
                    if ($tipoEntrega !== 2) $sucursalCodigo = '29';
                    $vendedorCodigo = '2990';
                }

                // ==========================================
                // LÓGICA DE MEDIO DE PAGO Y ESTADOS
                // ==========================================
                $metodoPagoInput = $_POST['metodo_pago'] ?? 'webpay';
                $formaPagoFinal = 5; // Webpay
                $esContraEntrega = false;

                if ($metodoPagoInput === 'contra_entrega' && isset($user->es_cliente_confianza) && $user->es_cliente_confianza == 1) {
                    $formaPagoFinal = 7; // Crédito de confianza
                    $esContraEntrega = true;
                }

                // 6. PERSISTENCIA DEL PEDIDO
                $montoTotalFinal = $totalBruto + (($tipoEntrega === 2) ? 0 : $costoEnvio);
                $rutLimpio = str_replace(['.', '-'], '', $user->rut);
                $rutERP = str_pad($rutLimpio, 11, '0', STR_PAD_LEFT);

                $datosPedido = [
                    'usuario_id'               => $user->id,
                    'rut_cliente'              => $rutERP,
                    'sucursal_codigo'          => $sucursalCodigo,
                    'vendedor_codigo'          => $vendedorCodigo,
                    'total_neto'               => $totalNeto,
                    'monto_total'              => $montoTotalFinal,
                    'costo_envio'              => ($tipoEntrega === 2) ? 0 : $costoEnvio,
                    'direccion_entrega_texto'  => $direccionTexto,
                    'comuna_id'                => $comunaId,
                    'estado_pedido_id'         => 1,
                    'estado_pago_id'           => 1, // Siempre inicia en 1 (Pendiente)
                    'forma_pago_id'            => $formaPagoFinal,
                    'cantidad_items'           => $cantidadItems,
                    'cantidad_total_productos' => count($_SESSION['carrito']),
                    'tipo_entrega_id'          => $tipoEntrega,
                    'latitud'                  => $latitud,
                    'longitud'                 => $longitud,
                    'nombre_destinatario'      => $user->nombre,
                    'fecha_entrega_estimada'   => $fechaEntrega,
                    'rango_horario_id'         => $rangoId,
                    'telefono_contacto'        => $telefonoContacto,
                    'telefono_contacto_2'      => $telefonoContacto2
                ];

                $this->db->beginTransaction();

                $resultado = $this->pedidoModel->crear($datosPedido);
                $pedidoId = $resultado['id'];

                foreach ($_SESSION['carrito'] as $id => $item) {
                    $productoReal = $this->productoModel->getById($id);
                    if ($productoReal) {
                        $this->pedidoModel->agregarDetalle(
                            $pedidoId,
                            $productoReal,
                            $item['cantidad'],
                            ['neto' => round($item['precio'] / 1.19), 'bruto' => $item['precio']]
                        );
                    }
                }

                $this->db->commit();

                // 7. REDIRECCIÓN FINAL
                ob_clean();

                if ($esContraEntrega) {
                    // Si es crédito de confianza, registramos historial indicando el método
                    $this->pedidoModel->registrarHistorial($pedidoId, 1, 'Pedido realizado con Crédito de Confianza (Pendiente de pago).');
                    unset($_SESSION['carrito']);
                    header("Location: " . BASE_URL . "pedido/exito?id=" . $pedidoId);
                } else {
                    header("Location: " . BASE_URL . "webpay/pagar?id=" . $pedidoId);
                }
                exit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log("Error crítico en Checkout: " . $e->getMessage());
                if (ob_get_level()) ob_end_clean();
                header("Location: " . BASE_URL . "checkout?error=fallo_sistema&info=" . urlencode($e->getMessage()));
                exit;
            }
        } else {
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }
}
