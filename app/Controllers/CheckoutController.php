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
    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['carrito']) && isset($_SESSION['user_id'])) {

            try {
                $user = $this->userModel->getById($_SESSION['user_id']);

                // 1. LÓGICA DE FECHA Y RANGO HORARIO
                date_default_timezone_set('America/Santiago');
                $horaActual = (int)date('H');

                $fechaEntrega = date('Y-m-d');
                $rangoId = 1;

                if ($horaActual >= 17) {
                    $fechaEntrega = date('Y-m-d', strtotime('+1 day'));
                    $rangoId = 1;
                } else {
                    if ($horaActual < 9) $rangoId = 1;
                    elseif ($horaActual < 11) $rangoId = 2;
                    elseif ($horaActual < 15) $rangoId = 3;
                    else $rangoId = 4;
                }

                // 2. DATOS DE CONTACTO (Estructura Normalizada)
                $nombreDestinatario = $user->nombre;

                // --- PROCESAMIENTO DE TELÉFONOS (Titular + Segundo Contacto) ---
                // El primer teléfono es el del titular (fijo según tu requerimiento)
                $telefonoContacto = $_POST['telefono_contacto'] ?? '';

                // Lógica para el Segundo Teléfono (Agenda o Nuevo)
                $segundoSeleccionado = $_POST['telefono_seleccionado_2'] ?? '';
                $telefonoContacto2 = null;

                if ($segundoSeleccionado === 'nuevo') {
                    $telefonoContacto2 = $_POST['telefono_nuevo_2'] ?? null;

                    // Si el usuario marcó guardar, lo añadimos a la base de datos de usuario_telefonos
                    if (!empty($_POST['guardar_nuevo_2']) && !empty($telefonoContacto2)) {
                        $alias = !empty($_POST['nuevo_alias_2']) ? $_POST['nuevo_alias_2'] : 'Contacto Alternativo';
                        $this->userModel->agregarTelefono($user->id, $telefonoContacto2, $alias, 0);
                    }
                } elseif (!empty($segundoSeleccionado)) {
                    // Si eligió uno existente de su lista (marido, santiago, etc.)
                    $telefonoContacto2 = $segundoSeleccionado;
                }

                // 3. CALCULO DE TOTALES
                $totalBruto = 0;
                $cantidadItems = 0;
                foreach ($_SESSION['carrito'] as $id => $item) {
                    $totalBruto += $item['precio'] * $item['cantidad'];
                    $cantidadItems += $item['cantidad'];
                }
                $totalNeto = round($totalBruto / 1.19);

                // 4. LÓGICA DE ENTREGA
                $tipoEntrega = isset($_POST['tipo_entrega']) ? (int)$_POST['tipo_entrega'] : 1;
                $sucursalCodigo = '10';
                $vendedorCodigo = '0003';
                $costoEnvio = 0;
                $direccionTexto = '';
                $comunaId = null;
                $latitud = null;
                $longitud = null;

                if ($tipoEntrega === 2) {
                    // RETIRO
                    $sucursalCodigo = $_POST['sucursal_codigo'] ?? '10';
                    $direccionTexto = "RETIRO EN TIENDA: Sucursal " . $sucursalCodigo;
                    $comunaId = $user->comuna_id;
                    $costoEnvio = 0;
                } else {
                    // DESPACHO
                    $origenDireccion = $_POST['origen_direccion'] ?? 'perfil';

                    if ($origenDireccion === 'nueva') {
                        $direccionTexto = $_POST['nueva_direccion'];
                        $comunaId = $_POST['nueva_comuna_id'];
                        // Capturar coordenadas del formulario
                        $latitud = !empty($_POST['nueva_lat']) ? $_POST['nueva_lat'] : null;
                        $longitud = !empty($_POST['nueva_lng']) ? $_POST['nueva_lng'] : null;

                        // Si no vienen por POST pero es una dirección guardada, las buscamos en la DB
                        if (empty($latitud) && isset($_POST['origen_direccion']) && is_numeric($_POST['origen_direccion'])) {
                            $dirGuardada = $this->direccionModel->obtenerPorId($_POST['origen_direccion'], $user->id);
                            if ($dirGuardada) {
                                $latitud = $dirGuardada->latitud;
                                $longitud = $dirGuardada->longitud;
                            }
                        }

                        try {
                            $this->direccionModel->crear([
                                'usuario_id'      => $user->id,
                                'nombre_etiqueta' => $_POST['nueva_alias'] ?? 'Nueva',
                                'direccion'       => $direccionTexto,
                                'comuna_id'       => $comunaId,
                                'latitud'         => $latitud,
                                'longitud'        => $longitud,
                                'activo'          => 1
                            ]);
                        } catch (\Exception $e) {
                            error_log("Error guardando dirección nueva: " . $e->getMessage());
                        }
                    } else {
                        $dirId = $origenDireccion;
                        if (is_numeric($dirId)) {
                            $dirGuardada = $this->direccionModel->obtenerPorId($dirId, $user->id);
                            if ($dirGuardada) {
                                $direccionTexto = $dirGuardada->direccion;
                                $comunaId = $dirGuardada->comuna_id;
                                $latitud = $dirGuardada->latitud;
                                $longitud = $dirGuardada->longitud;
                            } else {
                                $direccionTexto = $user->direccion;
                                $comunaId = $user->comuna_id;
                            }
                        } else {
                            $direccionTexto = $user->direccion;
                            $comunaId = $user->comuna_id;
                        }
                    }

                    $costoEnvio = ($totalBruto > 49950) ? 0 : 1990;
                }

                // ASIGNACIÓN SUCURSAL LOGÍSTICA (Cencocal Rules)
                $idsComunasInterior = [63, 66, 64, 65, 62, 80];
                if (in_array((int)$comunaId, $idsComunasInterior)) {
                    if ($tipoEntrega !== 2) $sucursalCodigo = '29';
                    $vendedorCodigo = '2990';
                } else {
                    if ($tipoEntrega !== 2) $sucursalCodigo = '10';
                    $vendedorCodigo = '0003';
                }

                // 5. PREPARAR DATOS PARA EL MODELO PEDIDO
                $montoTotalFinal = $totalBruto + $costoEnvio;
                $rutLimpio = str_replace(['.', '-'], '', $user->rut);
                $rutERP = str_pad($rutLimpio, 11, '0', STR_PAD_LEFT);

                $datosPedido = [
                    'usuario_id'               => $user->id,
                    'rut_cliente'              => $rutERP,
                    'sucursal_codigo'          => $sucursalCodigo,
                    'vendedor_codigo'          => $vendedorCodigo,
                    'total_neto'               => $totalNeto,
                    'monto_total'              => $montoTotalFinal,
                    'costo_envio'              => $costoEnvio,
                    'direccion_entrega_texto'  => $direccionTexto,
                    'comuna_id'                => $comunaId,
                    'estado_pedido_id'         => 2, // PAGADO
                    'forma_pago_id'            => 3,
                    'cantidad_items'           => $cantidadItems,
                    'cantidad_total_productos' => count($_SESSION['carrito']),
                    'tipo_entrega_id'          => $tipoEntrega,
                    'latitud'                  => $latitud,
                    'longitud'                 => $longitud,
                    'nombre_destinatario'      => $nombreDestinatario,
                    'fecha_entrega_estimada'   => $fechaEntrega,
                    'rango_horario_id'         => $rangoId,
                    'telefono_contacto'        => $telefonoContacto,
                    'telefono_contacto_2'      => $telefonoContacto2
                ];

                $this->db->beginTransaction();

                $resultado = $this->pedidoModel->crear($datosPedido);
                $pedidoId = $resultado['id'];
                $tracking = $resultado['tracking'];

                foreach ($_SESSION['carrito'] as $id => $item) {
                    $productoReal = $this->productoModel->getById($id);
                    if ($productoReal) {
                        $precioBruto = $item['precio'];
                        $precioNeto = round($precioBruto / 1.19);
                        $this->pedidoModel->agregarDetalle(
                            $pedidoId,
                            $productoReal,
                            $item['cantidad'],
                            ['neto' => $precioNeto, 'bruto' => $precioBruto]
                        );
                    }
                }

                $this->db->commit();

                // Notificación por Mail
                try {
                    $mailService = new \App\Services\MailService();
                    $mailService->enviarConfirmacionCompra($user->email, $user->nombre, $pedidoId, $montoTotalFinal, $tracking);
                } catch (\Exception $e) {
                    error_log("Error mail: " . $e->getMessage());
                }

                $_SESSION['carrito'] = [];
                header("Location: " . BASE_URL . "home?msg=compra_exitosa&folio=" . $pedidoId . "&trk=" . $tracking);
                exit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log("Error checkout: " . $e->getMessage());
                header("Location: " . BASE_URL . "checkout?error=" . urlencode($e->getMessage()));
                exit;
            }
        } else {
            header("Location: " . BASE_URL . "home");
        }
    }
}
