<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use Exception;

class AdminController
{
    private $db;
    private $pedidoModel;
    private $productoModel;
    private $analyticsModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);

        require_once __DIR__ . '/../Models/Analytics.php';
        $this->analyticsModel = new \App\Models\Analytics($db);
    }

    private function verificarAdmin()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    private function verificarSuperAdmin()
    {
        // 1. Primero verificamos que sea admin
        $this->verificarAdmin();

        // 2. Si tiene una sucursal asignada, NO es el Big Boss. Bloqueo inmediato.
        if (!empty($_SESSION['admin_sucursal'])) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=solo_superadmin");
            exit();
        }
    }


    // =========================================================
    // 📊 DASHBOARD
    // =========================================================
    public function dashboard()
    {
        $this->verificarAdmin();

        // CORRECCIÓN CLAVE: Usamos !empty() para evitar que un string vacío "" rompa la consulta
        $desde = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

        // TRUCO: Reflejamos las fechas de vuelta a $_GET para que la vista no muestre "dd-mm-aaaa"
        $_GET['desde'] = $desde;
        $_GET['hasta'] = $hasta;

        // --- MAGIA MULTI-SUCURSAL ---
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;
        $filtroSucursal = "";
        $filtroSucursalTop = "";
        $paramsBase = [':desde' => $desde, ':hasta' => $hasta];

        if (!empty($sucursalAsignada)) {
            $filtroSucursal = " AND sucursal_codigo = :sucursal";
            $filtroSucursalTop = " AND ped.sucursal_codigo = :sucursal";
            $paramsBase[':sucursal'] = strval($sucursalAsignada);
        }
        // -----------------------------

        // A. VENTA TOTAL Y DESPACHOS (MODIFICADO)
        // Extraemos ambas sumas en una sola consulta ultra rápida
        $sqlVenta = "SELECT 
                        COALESCE(SUM(monto_total), 0) as total_general,
                        COALESCE(SUM(costo_envio), 0) as total_despacho
                     FROM pedidos 
                     WHERE estado_pedido_id NOT IN (1, 6) 
                     $filtroSucursal
                     AND DATE(fecha_creacion) BETWEEN :desde AND :hasta";
        $stmtVenta = $this->db->prepare($sqlVenta);
        $stmtVenta->execute($paramsBase);
        $rowVenta = $stmtVenta->fetch(\PDO::FETCH_ASSOC);

        // Asignamos las variables para la vista
        $ventaPeriodo = (float)($rowVenta['total_general'] ?? 0);
        $ingresoDespacho = (float)($rowVenta['total_despacho'] ?? 0);

        // B. PEDIDOS PENDIENTES
        $paramsPend = [];
        $sqlPend = "SELECT COUNT(*) FROM pedidos WHERE estado_pedido_id = 1";
        if (!empty($sucursalAsignada)) {
            $sqlPend .= " AND sucursal_codigo = :sucursal";
            $paramsPend[':sucursal'] = strval($sucursalAsignada);
        }
        $stmtPend = $this->db->prepare($sqlPend);
        $stmtPend->execute($paramsPend);
        $pendientes = $stmtPend->fetchColumn();

        // C. PRODUCTOS BAJO STOCK (Stock Crítico < 10)
        if (!empty($sucursalAsignada)) {
            $sqlStock = "SELECT p.nombre, ps.stock, p.imagen 
                         FROM productos_sucursales ps 
                         JOIN productos p ON ps.cod_producto = p.cod_producto 
                         WHERE ps.stock < 10 AND ps.sucursal_id = :sucursal
                         ORDER BY ps.stock ASC LIMIT 5";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([':sucursal' => $sucursalAsignada]);
            $stockCritico = $stmtStock->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $sqlStock = "SELECT p.nombre, SUM(ps.stock) as stock, p.imagen 
                         FROM productos_sucursales ps 
                         JOIN productos p ON ps.cod_producto = p.cod_producto 
                         GROUP BY p.id
                         HAVING stock < 10
                         ORDER BY stock ASC LIMIT 5";
            $stockCritico = $this->db->query($sqlStock)->fetchAll(\PDO::FETCH_ASSOC);
        }

        // D. VENTAS ÚLTIMOS 7 DÍAS (Ahora toma el rango seleccionado)
        $sqlChart = "SELECT DATE_FORMAT(fecha_creacion, '%d/%m') as fecha, SUM(monto_total) as total 
                     FROM pedidos 
                     WHERE estado_pedido_id NOT IN (1, 6)
                     $filtroSucursal
                     AND DATE(fecha_creacion) BETWEEN :desde AND :hasta
                     GROUP BY DATE(fecha_creacion) 
                     ORDER BY fecha_creacion ASC";
        $stmtChart = $this->db->prepare($sqlChart);
        $stmtChart->execute($paramsBase);
        $datosGrafico = $stmtChart->fetchAll(\PDO::FETCH_ASSOC);

        // E. TOP 5 PRODUCTOS MÁS VENDIDOS
        $sqlTop = "SELECT p.nombre, SUM(dp.cantidad) as vendidos 
                   FROM pedidos_detalle dp
                   JOIN pedidos ped ON dp.pedido_id = ped.id
                   JOIN productos p ON dp.producto_id = p.id
                   WHERE ped.estado_pedido_id NOT IN (1, 6)
                   $filtroSucursalTop
                   AND DATE(ped.fecha_creacion) BETWEEN :desde AND :hasta
                   GROUP BY p.id 
                   ORDER BY vendidos DESC 
                   LIMIT 5";
        $stmtTop = $this->db->prepare($sqlTop);
        $stmtTop->execute($paramsBase);
        $topProductos = $stmtTop->fetchAll(\PDO::FETCH_ASSOC);

        // F. ÚLTIMOS 5 PEDIDOS
        $sqlRecientes = "SELECT p.*, u.nombre as nombre_cliente, ep.nombre as estado, ep.badge_class as color_estado 
                         FROM pedidos p 
                         LEFT JOIN usuarios u ON p.usuario_id = u.id
                         LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                         WHERE 1=1 $filtroSucursal
                         ORDER BY p.id DESC LIMIT 5";
        $stmtRecientes = $this->db->prepare($sqlRecientes);
        $paramsRecientes = [];
        if (!empty($sucursalAsignada)) {
            $paramsRecientes[':sucursal'] = strval($sucursalAsignada);
        }
        $stmtRecientes->execute($paramsRecientes);
        $ultimosPedidos = $stmtRecientes->fetchAll(\PDO::FETCH_ASSOC);

        // Pasar las variables a la vista
        ob_start();
        include __DIR__ . '/../../views/admin/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
    // =========================================================
    // 📈 ANALÍTICA WEB
    // =========================================================
    public function analytics()
    {
        $this->verificarSuperAdmin(); // Candado solo Big Boss

        // 1. Capturar Filtros (con validación estricta de vacíos)
        $fechaInicio = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $fechaFin    = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        $busqueda    = !empty($_GET['q']) ? $_GET['q'] : '';

        // TRUCO: Reflejamos las fechas de vuelta a $_GET para que la vista no muestre casillas en blanco
        $_GET['desde'] = $fechaInicio;
        $_GET['hasta'] = $fechaFin;

        // 2. Obtener Datos Filtrados
        $traficoChart = $this->analyticsModel->obtenerTrafico($fechaInicio, $fechaFin, $busqueda);
        $paginasTop   = $this->analyticsModel->obtenerPaginasPopulares($fechaInicio, $fechaFin, $busqueda);
        $clicsTop     = $this->analyticsModel->obtenerClicsPopulares($fechaInicio, $fechaFin, $busqueda);
        $kpis         = $this->analyticsModel->obtenerKPIs($fechaInicio, $fechaFin, $busqueda);
        $visitasMapa  = $this->analyticsModel->obtenerVisitasPorComuna($fechaInicio, $fechaFin, $busqueda);

        // Menú Lateral
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;

        // 3. Preparar datos para Chart.js
        $chartLabels = [];
        $chartData = [];
        foreach ($traficoChart as $dato) {
            $fechaObj = date_create($dato['etiqueta']);
            $chartLabels[] = date_format($fechaObj, 'd/m');
            $chartData[] = $dato['total'];
        }

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/analytics_dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function verDetalle($id)
    {

        // --- OBTENER HISTORIAL DE EDICIONES CON DETALLES (Corregido) ---
        $sqlEdiciones = "SELECT ed.*, u.nombre as admin_nombre 
                 FROM pedidos_ediciones ed
                 LEFT JOIN usuarios u ON ed.admin_id = u.id
                 WHERE ed.pedido_id = ? 
                 ORDER BY ed.fecha_edicion DESC";
        $stmtEd = $this->db->prepare($sqlEdiciones);
        $stmtEd->execute([$id]); // <--- Antes tenía un punto, ahora la flecha correcta
        $ediciones = $stmtEd->fetchAll(\PDO::FETCH_ASSOC);

        // Para cada edición, buscamos qué productos cambiaron exactamente
        foreach ($ediciones as &$ed) {
            $sqlDet = "SELECT * FROM pedidos_ediciones_detalle WHERE edicion_id = ?";
            $stmtDet = $this->db->prepare($sqlDet);
            $stmtDet->execute([$ed['id']]); // <--- Flecha corregida
            $ed['detalles_cambio'] = $stmtDet->fetchAll(\PDO::FETCH_ASSOC); // <--- Flecha corregida
        }
        $this->verificarAdmin();
        $pedido = $this->pedidoModel->obtenerPorId($id);

        // --- CANDADO MULTI-SUCURSAL ---
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;
        if ($pedido && $sucursalAsignada !== null) {
            if ((int)$pedido['sucursal_codigo'] !== (int)$sucursalAsignada) {
                header("Location: " . BASE_URL . "admin/pedidos?msg=acceso_denegado");
                exit();
            }
        }

        if (!$pedido) {
            header("Location: " . BASE_URL . "admin/dashboard");
            exit();
        }

        // 1. Obtener productos activos actuales en el pedido
        if (method_exists($this->pedidoModel, 'obtenerDetalleProductos')) {
            $detallesActivos = $this->pedidoModel->obtenerDetalleProductos($id);
        } else {
            $detallesActivos = $this->pedidoModel->obtenerDetalles($id);
        }

        // 2. Obtener trazabilidad de ediciones
        $itemsEliminados = method_exists($this->pedidoModel, 'obtenerItemsEliminados') ? $this->pedidoModel->obtenerItemsEliminados($id) : [];
        $idsAgregados = method_exists($this->pedidoModel, 'obtenerIdsAgregados') ? $this->pedidoModel->obtenerIdsAgregados($id) : [];

        // 3. Mezclar y etiquetar para la vista
        $detalles = [];
        foreach ($detallesActivos as $d) {
            $d['es_eliminado'] = false;
            $d['es_agregado'] = in_array($d['producto_id'], $idsAgregados);
            $detalles[] = $d;
        }
        foreach ($itemsEliminados as $d) {
            $d['es_eliminado'] = true;
            $d['es_agregado'] = false;
            $detalles[] = $d;
        }

        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;
        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/detalle_pedido.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
    public function cambiarEstado()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPedido = (int)$_POST['pedido_id'];
            $nuevoEstadoId = (int)$_POST['estado_id'];

            $this->pedidoModel->actualizarEstado($idPedido, $nuevoEstadoId);
            $this->pedidoModel->registrarHistorial($idPedido, $nuevoEstadoId);

            try {
                $pedido = $this->pedidoModel->obtenerPorId($idPedido);
                $stmt = $this->db->prepare("SELECT nombre FROM estados_pedido WHERE id = ?");
                $stmt->execute([$nuevoEstadoId]);
                $nombreEstado = $stmt->fetchColumn();

                if ($pedido && !empty($pedido['email_cliente'])) {
                    $mailService = new \App\Services\MailService();
                    $mailService->enviarActualizacionEstado(
                        $pedido['email_cliente'],
                        $pedido['nombre_cliente'],
                        $idPedido,
                        $nombreEstado,
                        $pedido['numero_seguimiento'] ?? '---'
                    );
                }
            } catch (\Exception $e) {
                error_log("Error notificación email: " . $e->getMessage());
            }

            header("Location: " . BASE_URL . "admin/pedido/ver/" . $idPedido . "?msg=estado_actualizado");
            exit;
        } else {
            header("Location: " . BASE_URL . "admin/pedidos");
            exit;
        }
    }
    public function exportar()
    {
        $this->verificarAdmin();

        // 1. Recibir los mismos filtros que se usaron en la pantalla
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $q = $_GET['q'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;

        // 2. Traer los datos filtrados (Límite altísimo para traer todo, sin offset)
        $pedidos = $this->pedidoModel->obtenerFiltrados($desde, $hasta, $q, $estado, 999999, 0, $sucursalAsignada);

        // 3. Preparar la salida del archivo CSV para Excel
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Reporte_Ventas_Cencocal_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');

        // TRUCO CRÍTICO: Agregar BOM de UTF-8 para que el Excel lea los tildes y las "ñ" sin romperse
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // 4. Escribir Encabezados
        fputcsv($output, [
            'Folio',
            'Fecha Compra',
            'Cliente',
            'RUT',
            'Tipo Entrega',
            'Sucursal',
            'Medio de Pago',
            'Estado Logístico',
            'Cobro Cliente ($)',
            'Total ERP ($)',
            'Subsidio Empresa ($)'
        ], ';');

        // 5. Escribir Datos
        foreach ($pedidos as $p) {
            $folio = $p['id'];
            $fecha = date('d/m/Y H:i', strtotime($p['fecha_creacion'] . ' ' . $p['hora_creacion']));
            $cliente = $p['nombre_cliente'];
            $rut = $p['rut_cliente'];
            $tipoEntrega = ((int)($p['tipo_entrega_id'] ?? 1) === 2) ? 'Retiro' : 'Domicilio';
            $sucursal = $p['sucursal_codigo'] ?? 'WEB';
            $estadoStr = strtoupper($p['estado'] ?? 'Pendiente');
            $medioPago = $p['forma_pago_nombre'] ?? (($p['forma_pago_id'] == 7) ? 'Crédito Confianza' : 'Webpay');

            // Lógica matemática
            $totalERP = (int)($p['total_bruto'] ?? $p['monto_total'] ?? 0);
            $subsidio = (int)($p['subsidio_empresa'] ?? 0);
            $cobroCliente = $totalERP - $subsidio;

            fputcsv($output, [
                $folio,
                $fecha,
                $cliente,
                $rut,
                $tipoEntrega,
                $sucursal,
                $medioPago,
                $estadoStr,
                $cobroCliente,
                $totalERP,
                $subsidio
            ], ';');
        }

        // 6. ESTRUCTURA POWER BI: Agregar la metadata de los filtros al final
        fputcsv($output, [], ';'); // Fila vacía separadora
        fputcsv($output, ['--- CONTEXTO Y FILTROS APLICADOS EN ESTE REPORTE ---'], ';');
        fputcsv($output, ['Fecha de Emisión', date('d/m/Y H:i:s')], ';');
        fputcsv($output, ['Rango de Fechas', "$desde al $hasta"], ';');
        fputcsv($output, ['Término de Búsqueda', empty($q) ? 'Todas (Sin filtro de texto)' : $q], ';');

        // Mapear el nombre del estado
        $estadoNombre = 'Todos los Estados';
        if (!empty($estado)) {
            $estadosMap = [
                '1' => 'Pendiente de Pago',
                '2' => 'Pagado / Confirmado',
                '3' => 'En Preparación',
                '4' => 'En Ruta',
                '5' => 'Entregado',
                '6' => 'Anulado'
            ];
            $estadoNombre = $estadosMap[$estado] ?? "Estado ID: $estado";
        }
        fputcsv($output, ['Filtro de Estado', $estadoNombre], ';');

        if ($sucursalAsignada) {
            fputcsv($output, ['Sucursal Asignada', "Sucursal $sucursalAsignada"], ';');
        }

        fclose($output);
        exit;
    }

    // =========================================================
    // 🛍️ GESTIÓN DE PRODUCTOS
    // =========================================================
    public function productos()
    {
        $this->verificarAdmin();

        $busqueda = $_GET['q'] ?? '';
        $filtroCategoriaId = $_GET['categoria'] ?? '';

        // --- MAGIA MULTI-SUCURSAL ---
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;
        // -----------------------------

        $por_pagina = 20;
        $pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $total_registros = $this->productoModel->contarTotal($busqueda, $filtroCategoriaId, $sucursalAsignada);
        $total_paginas = ceil($total_registros / $por_pagina);

        $productos = $this->productoModel->obtenerPaginados($por_pagina, $offset, $busqueda, $filtroCategoriaId, $sucursalAsignada);

        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/productos_index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function toggleProducto($id)
    {
        $this->verificarAdmin();
        $prod = $this->productoModel->obtenerPorId($id);

        // CORRECCIÓN: Ahora leemos 'activo' como Array y no como Objeto
        $nuevo_estado = ($prod['activo'] == 1) ? 0 : 1;

        $this->productoModel->cambiarEstado($id, $nuevo_estado);
        header("Location: " . BASE_URL . "admin/productos");
        exit();
    }

    public function crearProducto()
    {
        $this->verificarAdmin();
        $producto = null;

        // CORRECCIÓN: También aquí
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/producto_form.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function editarProducto($id)
    {
        $this->verificarAdmin();
        $producto = $this->productoModel->obtenerPorId($id);

        if (!$producto) {
            header("Location: " . BASE_URL . "admin/productos");
            exit();
        }

        // CORRECCIÓN: También aquí
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/producto_form.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardarProducto()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $datos = [
                'nombre' => $_POST['nombre'],
                'precio' => $_POST['precio'],
                'stock'  => $_POST['stock'],
                'descripcion' => $_POST['descripcion']
            ];

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = time() . '_' . $_FILES['imagen']['name'];
                $rutaDestino = __DIR__ . '/../../public/img/productos/' . $nombreArchivo;
                if (!file_exists(dirname($rutaDestino))) mkdir(dirname($rutaDestino), 0777, true);
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                    $datos['imagen'] = $nombreArchivo;
                }
            }

            if ($id) $this->productoModel->actualizar($id, $datos);
            else $this->productoModel->crear($datos);

            header("Location: " . BASE_URL . "admin/productos");
            exit();
        }
    }

    public function eliminarProducto($id)
    {
        $this->verificarAdmin();
        $this->productoModel->eliminar($id);
        header("Location: " . BASE_URL . "admin/productos");
        exit();
    }
    public function importarERP()
    {
        $this->verificarAdmin();

        try {
            $service = new \App\Services\ImportadorService($this->db);
            $rutaData = dirname(__DIR__, 2) . '/erp_data/';

            // Ejecutamos la importación y recibimos el reporte de métricas
            $reporte = $service->ejecutar($rutaData);

            // Guardamos el reporte en la sesión para mostrarlo en el Dashboard
            $_SESSION['ultimo_reporte_erp'] = $reporte;

            header("Location: " . BASE_URL . "admin/dashboard?msg=sync_ok");
            exit();
        } catch (Exception $e) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=error&info=" . urlencode($e->getMessage()));
            exit();
        }
    }

    public function buscarProductosAjax()
    {
        $this->verificarAdmin();
        $busqueda = $_GET['q'] ?? '';
        $categoriaId = $_GET['categoria'] ?? '';

        // --- MAGIA MULTI-SUCURSAL ---
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;
        // -----------------------------

        $productos = $this->productoModel->obtenerPaginados(50, 0, $busqueda, $categoriaId, $sucursalAsignada);

        if (empty($productos)) {
            echo '<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-emoji-frown fs-1 d-block mb-2 opacity-50"></i>No se encontraron coincidencias.</td></tr>';
            return;
        }
        foreach ($productos as $prod) {
            $nombre = $prod->nombre_mostrar ?? $prod->nombre ?? 'Sin Nombre';
            $codigo = $prod->cod_producto ?? '---';
            $catNombre = $prod->categoria_nombre ?? 'General';
            $id = $prod->id;
            $precio = $prod->precio ?? 0;
            $stock = $prod->stock ?? 0;
            $activo = $prod->activo ?? 0;
            $imgRaw = $prod->imagen ?? '';

            if (empty($imgRaw)) $rutaImagen = BASE_URL . 'img/no-photo_small.png';
            elseif (strpos($imgRaw, 'http') === 0) $rutaImagen = $imgRaw;
            else $rutaImagen = BASE_URL . 'img/productos/' . $imgRaw;

            if ($stock > 10) $badgeStock = '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 rounded-pill">' . $stock . ' un.</span>';
            elseif ($stock > 0) $badgeStock = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 rounded-pill">' . $stock . ' un.</span>';
            else $badgeStock = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 rounded-pill">Agotado</span>';

            $badgeEstado = $activo
                ? '<span class="badge rounded-pill bg-success px-3">Visible</span>'
                : '<span class="badge rounded-pill bg-secondary px-3">Oculto</span>';

            $iconoOjo = $activo ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-muted';

            echo '<tr>
                <td class="ps-4 py-2"><div class="bg-white border rounded-3 p-1 shadow-sm position-relative" style="width: 50px; height: 50px;"><img src="' . $rutaImagen . '" class="w-100 h-100 object-fit-contain" onerror="this.src=\'' . BASE_URL . 'img/no-image.png\'"></div></td>
                <td><div class="fw-bold text-dark text-truncate" style="max-width: 300px;" title="' . htmlspecialchars($nombre) . '">' . htmlspecialchars($nombre) . '</div><div class="d-flex align-items-center gap-2 mt-1"><span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;"><i class="bi bi-upc-scan me-1"></i>' . htmlspecialchars($codigo) . '</span><span class="badge bg-cenco-indigo bg-opacity-10 text-cenco-indigo fw-bold" style="font-size: 0.7rem;">' . htmlspecialchars($catNombre) . '</span></div></td>
               <td class="fw-bold text-cenco-indigo">$' . number_format($precio, 0, ',', '.') . '</td>
                <td class="text-center">' . $badgeStock . '</td>
                <td class="text-center">' . $badgeEstado . '</td>
                <td class="text-end pe-4"><div class="btn-group">
                    <button onclick="toggleProducto(' . $id . ', this)" class="btn btn-sm btn-light border shadow-sm" title="' . ($activo ? 'Ocultar' : 'Mostrar') . '"><i class="bi ' . $iconoOjo . '"></i></button>
                    <a href="' . BASE_URL . 'admin/producto/editar/' . $id . '" class="btn btn-sm btn-light border shadow-sm text-primary"><i class="bi bi-pencil-fill"></i></a>
                    <button onclick="confirmarEliminar(' . $id . ')" class="btn btn-sm btn-light border shadow-sm text-danger"><i class="bi bi-trash-fill"></i></button>
                </div></td>
            </tr>';
        }
    }

    public function toggleProductoAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $this->verificarAdmin();

        // 1. Soporte dual: Leemos por FormData ($_POST) o JSON
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
        }

        if ($id) {
            $prod = $this->productoModel->obtenerPorId($id);
            if ($prod) {
                // 2. CORRECCIÓN: Leemos 'activo' como Array
                $nuevo_estado = ($prod['activo'] == 1) ? 0 : 1;
                $this->productoModel->cambiarEstado($id, $nuevo_estado);

                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
                exit;
            }
        }

        echo json_encode(['status' => 'error', 'message' => 'No se procesó el ID correctamente.']);
        exit;
    }

    // =========================================================
    // 🛒 VISTA DEDICADA DE PEDIDOS
    // =========================================================
    public function pedidos()
    {
        $this->verificarAdmin();

        $desde  = $_GET['desde'] ?? date('Y-m-01');
        $hasta  = $_GET['hasta'] ?? date('Y-m-d');
        $q      = $_GET['q'] ?? '';
        $estado = $_GET['estado'] ?? '';

        // Obtenemos la sucursal de la sesión del usuario
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;

        // --- LÓGICA DE SEGURIDAD PARA SUBSIDIOS ---
        // --- BLOQUE PARA EL RADAR DE SUBSIDIOS (Corregido) ---
        if (empty($sucursalAsignada)) {
            $sqlKpi = "SELECT 
                COUNT(id) as total_pedidos,
                SUM(CASE WHEN subsidio_empresa > 0 THEN 1 ELSE 0 END) as pedidos_con_subsidio, -- Alias corregido
                SUM(COALESCE(subsidio_empresa, 0)) as monto_total_subsidio,
                (SELECT sucursal_codigo FROM pedidos WHERE subsidio_empresa > 0 
                 AND DATE(fecha_creacion) BETWEEN :d1 AND :h1 
                 GROUP BY sucursal_codigo ORDER BY SUM(subsidio_empresa) DESC LIMIT 1) as sucursal_critica
               FROM pedidos 
               WHERE DATE(fecha_creacion) BETWEEN :d2 AND :h2";

            $stmtKpi = $this->db->prepare($sqlKpi);
            $stmtKpi->execute([
                ':d1' => $desde,
                ':h1' => $hasta,
                ':d2' => $desde,
                ':h2' => $hasta
            ]);
            $kpisSubsidios = $stmtKpi->fetch(\PDO::FETCH_ASSOC);
        }

        // 2. Configurar Paginación
        $limite = 25; // 25 filas máximo
        $paginaActual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($paginaActual < 1) $paginaActual = 1;
        $offset = ($paginaActual - 1) * $limite;

        // 3. Consultar Base de Datos (Enviando la sucursal asignada)
        $totalRegistros = $this->pedidoModel->contarFiltrados($desde, $hasta, $q, $estado, $sucursalAsignada);
        $totalPaginas = ceil($totalRegistros / $limite);

        $pedidos = $this->pedidoModel->obtenerFiltrados($desde, $hasta, $q, $estado, $limite, $offset, $sucursalAsignada);

        // 4. Renderizar vista 
        ob_start();
        include __DIR__ . '/../../views/admin/pedidos_index.php'; // ASEGÚRATE DE QUE EL NOMBRE SEA CORRECTO AQUÍ
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
    public function guardarEdicionPedido()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');

        $pedidoId = $_POST['pedido_id'] ?? null;
        $motivo = $_POST['motivo'] ?? null;
        // El 'original' que viene del frontend ahora representa el estado *antes de esta edición*
        $original = json_decode($_POST['carrito_original'] ?? '[]', true);
        $editado = json_decode($_POST['carrito_editado'] ?? '[]', true);
        $adminId = $_SESSION['user_id'];

        if (!$pedidoId || empty($original) || empty($editado)) {
            echo json_encode(['status' => false, 'message' => 'Datos de carrito inválidos o vacíos']);
            return;
        }

        // --- 1. OBTENER EL PAGO ORIGINAL INMUTABLE (WEBPAY) ---
        $stmtWp = $this->db->prepare("SELECT amount FROM transacciones_webpay WHERE pedido_id = ? AND status IN ('autorizado', 'capturado') LIMIT 1");
        $stmtWp->execute([$pedidoId]);
        $txWebpay = $stmtWp->fetch(\PDO::FETCH_ASSOC);

        // Si no hay Webpay, usamos una fallback seguro basado en el estado inicial
        if ($txWebpay) {
            $montoPagadoPorCliente = (int)$txWebpay['amount'];
        } else {
            // Fallback: buscamos en el historial de ediciones la primera, o usamos el actual si no hay ediciones
            $stmtPrimeraEd = $this->db->prepare("SELECT monto_original FROM pedidos_ediciones WHERE pedido_id = ? ORDER BY id ASC LIMIT 1");
            $stmtPrimeraEd->execute([$pedidoId]);
            $primeraEd = $stmtPrimeraEd->fetchColumn();

            if ($primeraEd) {
                $montoPagadoPorCliente = (int)$primeraEd;
            } else {
                $pedidoActual = $this->pedidoModel->obtenerPorId($pedidoId);
                $montoPagadoPorCliente = (int)($pedidoActual['monto_total'] ?? 0) - (int)($pedidoActual['subsidio_empresa'] ?? 0);
            }
        }

        // --- 2. CALCULAR EL NUEVO TOTAL DEL CARRITO EDITADO ---
        // --- 2. CALCULAR EL NUEVO TOTAL DEL CARRITO EDITADO ---
        $nuevoTotalProductos = 0;
        foreach ($editado as $item) {
            // Solo sumamos los que NO están eliminados
            if (empty($item['es_eliminado']) || $item['es_eliminado'] === false || $item['es_eliminado'] === 'false') {
                $nuevoTotalProductos += (int)$item['precio_bruto'] * (int)$item['cantidad'];
            }
        }

        // Obtenemos el costo de envío actual del pedido
        $pedidoOriginal = $this->pedidoModel->obtenerPorId($pedidoId);
        $costoEnvio = (int)($pedidoOriginal['costo_envio'] ?? 0);

        // ¡FIX: SUMAMOS EL COSTO POR SERVICIO FIJO INTRANSFERIBLE!
        $costoServicioFijo = 490;

        // El Total final es: Productos + Despacho + Servicio
        $nuevoTotalERP = $nuevoTotalProductos + $costoEnvio + $costoServicioFijo;

        // --- 3. CALCULAR EL NUEVO SUBSIDIO GLOBAL ---
        $nuevoSubsidio = 0;
        if ($nuevoTotalERP > $montoPagadoPorCliente) {
            $nuevoSubsidio = $nuevoTotalERP - $montoPagadoPorCliente;

            // ========================================================
            // BLOQUEO TEMPORAL: NO ASUMIR COSTOS (REEMPLAZO MÁS CARO)
            // ========================================================
            // En el futuro, cuando la empresa asuma estas diferencias, 
            // simplemente borra o comenta desde aquí hasta el 'exit;'
            echo json_encode([
                'status' => false,
                'message' => 'Por ahora, no se permite reemplazar por productos que encarezcan el pedido original. Por favor, selecciona una alternativa que mantenga o disminuya el total de la compra.'
            ]);
            exit;
            // ========================================================
        }

        // --- 4. MANEJO DE LA IMAGEN (EVIDENCIA DE WHATSAPP) ---
        $rutaEvidencia = null;
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $directorio_destino = __DIR__ . '/../../public/img/auditoria/';
            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }
            $nombreArchivo = 'evidencia_' . $pedidoId . '_' . time() . '_' . basename($_FILES['evidencia']['name']);
            if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $directorio_destino . $nombreArchivo)) {
                $rutaEvidencia = 'img/auditoria/' . $nombreArchivo;
            }
        }

        // --- 5. DETECTAR DIFERENCIAS PARA AUDITORÍA DE ESTA EDICIÓN ESPECÍFICA ---
        $itemsAgregados = [];
        $itemsEliminados = [];

        // Mapeamos los items originales (activos y su cantidad)
        $mapaOriginal = [];
        foreach ($original as $itemOrig) {
            if (empty($itemOrig['es_eliminado']) || $itemOrig['es_eliminado'] === false || $itemOrig['es_eliminado'] === 'false') {
                $mapaOriginal[$itemOrig['producto_id'] ?? $itemOrig['id_producto']] = $itemOrig;
            }
        }

        // Mapeamos los items editados (activos y su cantidad)
        $mapaEditado = [];
        foreach ($editado as $itemEdit) {
            if (empty($itemEdit['es_eliminado']) || $itemEdit['es_eliminado'] === false || $itemEdit['es_eliminado'] === 'false') {
                $mapaEditado[$itemEdit['producto_id'] ?? $itemEdit['id_producto']] = $itemEdit;
            }
        }

        // Detectamos qué se eliminó o redujo
        foreach ($mapaOriginal as $idProd => $itemOrig) {
            if (!isset($mapaEditado[$idProd])) {
                // Se eliminó por completo en esta edición
                $itemsEliminados[] = [
                    'producto_id' => $idProd,
                    'cod_producto' => $itemOrig['cod_producto'] ?? '',
                    'nombre' => $itemOrig['nombre_producto'] ?? $itemOrig['nombre'],
                    'cantidad' => (int)$itemOrig['cantidad'],
                    'precio_bruto' => $itemOrig['precio_bruto'] ?? ($itemOrig['precio_neto'] * 1.19)
                ];
            } else {
                // Sigue existiendo, ¿bajó la cantidad?
                $cantOrig = (int)$itemOrig['cantidad'];
                $cantEdit = (int)$mapaEditado[$idProd]['cantidad'];
                if ($cantOrig > $cantEdit) {
                    $itemsEliminados[] = [
                        'producto_id' => $idProd,
                        'cod_producto' => $itemOrig['cod_producto'] ?? '',
                        'nombre' => $itemOrig['nombre_producto'] ?? $itemOrig['nombre'],
                        'cantidad' => $cantOrig - $cantEdit,
                        'precio_bruto' => $itemOrig['precio_bruto'] ?? ($itemOrig['precio_neto'] * 1.19)
                    ];
                }
            }
        }

        // Detectamos qué se agregó o aumentó
        foreach ($mapaEditado as $idProd => $itemEdit) {
            if (!isset($mapaOriginal[$idProd])) {
                // Se agregó por primera vez en esta edición
                $itemsAgregados[] = [
                    'producto_id' => $idProd,
                    'cod_producto' => $itemEdit['cod_producto'] ?? '',
                    'nombre' => $itemEdit['nombre_producto'] ?? $itemEdit['nombre'],
                    'cantidad' => (int)$itemEdit['cantidad'],
                    'precio_bruto' => $itemEdit['precio_bruto'] ?? 0
                ];
            } else {
                // Ya existía, ¿subió la cantidad?
                $cantOrig = (int)$mapaOriginal[$idProd]['cantidad'];
                $cantEdit = (int)$itemEdit['cantidad'];
                if ($cantEdit > $cantOrig) {
                    $itemsAgregados[] = [
                        'producto_id' => $idProd,
                        'cod_producto' => $itemEdit['cod_producto'] ?? '',
                        'nombre' => $itemEdit['nombre_producto'] ?? $itemEdit['nombre'],
                        'cantidad' => $cantEdit - $cantOrig,
                        'precio_bruto' => $itemEdit['precio_bruto'] ?? 0
                    ];
                }
            }
        }

        // ==========================================================
        // --- 5.5 RADAR DE STOCK FANTASMA ---
        // ==========================================================
        // Registramos en la tabla de alertas todo lo que tuvimos que eliminar del pedido original
        if (!empty($itemsEliminados)) {
            $stmtFantasma = $this->db->prepare("INSERT INTO alertas_stock_fantasma (pedido_id, producto_id, nombre_producto, cantidad_faltante) VALUES (?, ?, ?, ?)");
            foreach ($itemsEliminados as $itemFaltante) {
                $stmtFantasma->execute([
                    $pedidoId,
                    $itemFaltante['producto_id'],
                    $itemFaltante['nombre'],
                    $itemFaltante['cantidad']
                ]);
            }
        }

        // --- 6. LLAMAR AL MODELO ---
        $resultado = $this->pedidoModel->aplicarEdicion(
            $pedidoId,
            $adminId,
            $motivo,
            $itemsEliminados,
            $itemsAgregados,
            $rutaEvidencia,
            $nuevoTotalERP,
            $nuevoSubsidio
        );

        echo json_encode($resultado);
        exit;
    }




    // =========================================================
    // 🖼️ MANTENEDOR: GESTIÓN DE BANNERS (DOBLE CARRUSEL)
    // =========================================================
    public function banners()
    {
        $this->verificarSuperAdmin();

        // Traemos banners de ambas tablas
        $stmtPrin = $this->db->query("SELECT * FROM carrusel_banners ORDER BY orden ASC");
        $bannersPrincipal = $stmtPrin->fetchAll(\PDO::FETCH_ASSOC);

        $stmtSec = $this->db->query("SELECT * FROM carrusel_secundario ORDER BY orden ASC");
        $bannersSecundario = $stmtSec->fetchAll(\PDO::FETCH_ASSOC);

        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;
        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/banners.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function capturar_pago()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedidoId = (int)$_POST['pedido_id'];
            $montoFinal = (int)$_POST['monto_final'];

            try {
                $this->db->beginTransaction();

                // 1. Llamamos al controlador de Webpay para hacer la captura real en Transbank
                $webpayController = new \App\Controllers\WebpayController($this->db);
                $resultado = $webpayController->capturarMontoFinal($pedidoId, $montoFinal);

                if ($resultado['status'] === true) {

                    // 2. ¡MAGIA CRÍTICA!: Descontamos el stock físico del inventario
                    // Pasamos de "Reservado" a "Vendido/Salida Física"
                    $exitoStock = $this->pedidoModel->descontarStockFisicoFinal($pedidoId);

                    if ($exitoStock) {
                        // 3. Cambiamos el estado a "En Preparación" (ID 3)
                        $this->pedidoModel->actualizarEstado($pedidoId, 3);
                        $this->pedidoModel->registrarHistorial($pedidoId, 3, "Pago capturado por $" . number_format($montoFinal, 0, ',', '.') . ". Stock descontado de bodega y enviado a preparación.");

                        $this->db->commit();
                        header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=captura_ok");
                    } else {
                        throw new Exception("Error al descontar stock físico.");
                    }
                } else {
                    throw new Exception($resultado['msg']);
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=error_captura&info=" . urlencode($e->getMessage()));
            }
            exit;
        }
    }

    // ==========================================
    // FUNCIONES AJAX PARA MARCAS DESTACADAS
    // ==========================================
    public function reordenarMarcasAjax()
    {
        $this->verificarAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['ordenes'])) {
            foreach ($data['ordenes'] as $item) {
                $stmt = $this->db->prepare("UPDATE marcas_destacadas SET orden = ? WHERE id = ?");
                $stmt->execute([$item['orden'], $item['id']]);
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    public function toggleMarcaAjax()
    {
        $this->verificarAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            $id = (int)$data['id'];
            $stmt = $this->db->prepare("SELECT estado_activo FROM marcas_destacadas WHERE id = ?");
            $stmt->execute([$id]);
            $estado_actual = $stmt->fetchColumn();

            $nuevo_estado = $estado_actual ? 0 : 1;
            $update = $this->db->prepare("UPDATE marcas_destacadas SET estado_activo = ? WHERE id = ?");
            if ($update->execute([$nuevo_estado, $id])) {
                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
                exit;
            }
        }
        echo json_encode(['status' => 'error']);
        exit;
    }

    public function borrarMarcaAjax()
    {
        $this->verificarAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            $id = (int)$data['id'];

            // Opcional: Eliminar la foto del servidor
            try {
                $stmtImg = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?");
                $stmtImg->execute([$id]);
                $ruta = $stmtImg->fetchColumn();
                if ($ruta && file_exists($_SERVER['DOCUMENT_ROOT'] . '/tienda-online/public/' . ltrim($ruta, '/'))) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/tienda-online/public/' . ltrim($ruta, '/'));
                }
            } catch (\Exception $e) {
            }

            $stmt = $this->db->prepare("DELETE FROM marcas_destacadas WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        echo json_encode(['status' => 'error']);
        exit;
    }

    // ==========================================
    // GUARDAR FECHAS DE BANNER INDEPENDIENTEMENTE
    // ==========================================
    public function actualizarFechasAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['tipo'])) {
            $id = (int)$data['id'];
            $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            // Limpiamos la "T" que envía el HTML5
            $inicio = !empty($data['inicio']) ? str_replace('T', ' ', $data['inicio']) : null;
            $fin = !empty($data['fin']) ? str_replace('T', ' ', $data['fin']) : null;

            $stmt = $this->db->prepare("UPDATE $tabla SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
            if ($stmt->execute([$inicio, $fin, $id])) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }

        echo json_encode(['status' => 'error', 'msg' => 'Faltan datos']);
        exit;
    }

    public function guardarBanner()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = $_POST['titulo'] ?? '';
            $enlace = !empty($_POST['enlace']) ? $_POST['enlace'] : null;

            $palabra_clave = !empty($_POST['palabra_clave']) ? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['palabra_clave']))) : null;
            $productos_ids = !empty($_POST['productos_ids']) ? $_POST['productos_ids'] : null;
            $sucursal_id = $_POST['sucursal_id'] ?? 0;
            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 1;

            // 🔥 CAPTURA DE FECHAS
            $fecha_inicio = !empty($_POST['fecha_inicio']) ? str_replace('T', ' ', $_POST['fecha_inicio']) : null;
            $fecha_fin = !empty($_POST['fecha_fin']) ? str_replace('T', ' ', $_POST['fecha_fin']) : null;

            $tipo = $_POST['tipo_carrusel'] ?? 'principal';
            $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            $imagen = $_FILES['imagen'];
            $directorio_destino = __DIR__ . '/../../public/img/banner/';

            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }

            if ($imagen['error'] === UPLOAD_ERR_OK) {
                $nombre_archivo = time() . '_' . basename($imagen['name']);
                $ruta_fisica = $directorio_destino . $nombre_archivo;
                $ruta_bd = 'img/banner/' . $nombre_archivo;

                if (move_uploaded_file($imagen['tmp_name'], $ruta_fisica)) {
                    // 🔥 INSERTAMOS CON FECHAS Y SUCURSAL
                    $stmt = $this->db->prepare("INSERT INTO $tabla (titulo, enlace, palabra_clave, productos_ids, ruta_imagen, orden, sucursal_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titulo, $enlace, $palabra_clave, $productos_ids, $ruta_bd, $orden, $sucursal_id, $fecha_inicio, $fecha_fin]);
                }
            }

            header("Location: " . BASE_URL . "admin/banners?msg=banner_creado");
            exit();
        }
    }
    public function actualizarBanner()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $titulo = $_POST['titulo'] ?? '';
            $enlace = !empty($_POST['enlace']) ? $_POST['enlace'] : null;

            // 🔥 NUEVOS CAMPOS PARA COLECCIONES (Buscador de productos)
            $palabra_clave = !empty($_POST['palabra_clave']) ? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['palabra_clave']))) : null;
            $productos_ids = !empty($_POST['productos_ids']) ? $_POST['productos_ids'] : null;

            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 1;

            $tipo = $_POST['tipo_carrusel_edit'] ?? 'principal';
            $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            // 🔥 ACTUALIZAMOS EL UPDATE PARA INCLUIR LOS NUEVOS CAMPOS
            $stmt = $this->db->prepare("UPDATE $tabla SET titulo = ?, enlace = ?, palabra_clave = ?, productos_ids = ?, orden = ? WHERE id = ?");
            $stmt->execute([$titulo, $enlace, $palabra_clave, $productos_ids, $orden, $id]);

            header("Location: " . BASE_URL . "admin/banners?msg=banner_actualizado");
            exit();
        }
    }


    public function toggleBannerAjax()
    {
        $this->verificarAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $tipo = $data['tipo'] ?? 'principal';
        $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

        if ($id) {
            $stmt = $this->db->prepare("SELECT estado_activo FROM $tabla WHERE id = ?");
            $stmt->execute([$id]);
            $estado_actual = $stmt->fetchColumn();

            if ($estado_actual !== false) {
                $nuevo_estado = ($estado_actual == 1) ? 0 : 1;

                $stmtUp = $this->db->prepare("UPDATE $tabla SET estado_activo = ? WHERE id = ?");
                $stmtUp->execute([$nuevo_estado, $id]);

                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
                exit;
            }
        }

        echo json_encode(['status' => 'error']);
        exit;
    }
    // ==========================================
    // MANTENEDOR DE MARCAS DESTACADAS (PARTNERS)
    // ==========================================

    // 1. CARGAR LA VISTA PRINCIPAL
    public function marcas()
    {
        $this->verificarAdmin(); // Protegemos la ruta

        // Traemos todas las marcas ordenadas
        $stmt = $this->db->query("SELECT * FROM marcas_destacadas ORDER BY orden ASC");
        $marcas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../../views/admin/marcas_mantenedor.php';
        $content = ob_get_clean();

        // 👇 ESTA ES LA LÍNEA QUE DEBES CAMBIAR POR EL NOMBRE REAL DE TU LAYOUT
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // 2. GUARDAR UNA NUEVA MARCA
    public function guardarMarca()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 999; // Si no hay orden, se va al final
            $imagen = $_FILES['imagen'] ?? null;

            $ruta_bd = '';

            if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
                // Creamos una carpeta separada para tener ordenado el servidor
                $directorio_destino = __DIR__ . '/../../public/img/marcas_destacadas/';
                if (!file_exists($directorio_destino)) {
                    mkdir($directorio_destino, 0777, true);
                }

                // Limpiamos el nombre del archivo para evitar errores en la web
                $nombre_archivo = time() . '_' . preg_replace('/[^A-Za-z0-9\-.]/', '_', basename($imagen['name']));
                $ruta_fisica = $directorio_destino . $nombre_archivo;

                // Esta es la ruta que se guarda en la Base de Datos
                $ruta_bd = 'img/marcas_destacadas/' . $nombre_archivo;

                move_uploaded_file($imagen['tmp_name'], $ruta_fisica);
            }

            $stmt = $this->db->prepare("INSERT INTO marcas_destacadas (nombre, ruta_imagen, orden) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $ruta_bd, $orden]);

            header("Location: " . BASE_URL . "admin/marcas?msg=marca_creada");
            exit;
        }
    }

    // 3. ACTUALIZAR UNA MARCA EXISTENTE
    public function actualizarMarca()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $nombre = trim($_POST['nombre'] ?? '');
            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 999;
            $imagen = $_FILES['imagen'] ?? null;

            if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
                // Si el usuario subió una imagen nueva, la procesamos
                $directorio_destino = __DIR__ . '/../../public/img/marcas_destacadas/';
                if (!file_exists($directorio_destino)) {
                    mkdir($directorio_destino, 0777, true);
                }

                $nombre_archivo = time() . '_' . preg_replace('/[^A-Za-z0-9\-.]/', '_', basename($imagen['name']));
                $ruta_fisica = $directorio_destino . $nombre_archivo;
                $ruta_bd = 'img/marcas_destacadas/' . $nombre_archivo;

                move_uploaded_file($imagen['tmp_name'], $ruta_fisica);

                $stmt = $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, ruta_imagen = ?, orden = ? WHERE id = ?");
                $stmt->execute([$nombre, $ruta_bd, $orden, $id]);
            } else {
                // Si no subió imagen, solo actualizamos el nombre y el orden
                $stmt = $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, orden = ? WHERE id = ?");
                $stmt->execute([$nombre, $orden, $id]);
            }

            header("Location: " . BASE_URL . "admin/marcas?msg=marca_actualizada");
            exit;
        }
    }

    public function borrarBanner($id)
    {
        $this->verificarAdmin();

        $tipo = $_GET['tipo'] ?? 'principal';
        $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

        $stmt = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($banner) {
            $ruta_fisica = __DIR__ . '/../../public/' . $banner['ruta_imagen'];
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                unlink($ruta_fisica);
            }
            $stmtDel = $this->db->prepare("DELETE FROM $tabla WHERE id = ?");
            $stmtDel->execute([$id]);
        }

        header("Location: " . BASE_URL . "admin/banners?msg=banner_eliminado");
        exit();
    }

    // --- MARCAS DESTACADAS ---
    public function marcasDestacadas()
    {
        $this->verificarAdmin();
        $stmt = $this->db->query("SELECT * FROM marcas_destacadas ORDER BY orden ASC");
        $marcas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Variables para layout
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;
        $esAdmin = true;

        ob_start();
        include __DIR__ . '/../../views/admin/marcas_mantenedor.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardarMarcaDestacada()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'];
            $orden = $_POST['orden'] ?? 1;
            $imagen = $_FILES['imagen'];

            if ($imagen['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = 'logo_' . time() . '_' . $imagen['name'];
                $rutaFisica = __DIR__ . '/../../public/img/marcas_destacadas/' . $nombreArchivo;

                if (!file_exists(dirname($rutaFisica))) mkdir(dirname($rutaFisica), 0777, true);

                if (move_uploaded_file($imagen['tmp_name'], $rutaFisica)) {
                    $rutaBD = 'img/marcas_destacadas/' . $nombreArchivo;
                    $stmt = $this->db->prepare("INSERT INTO marcas_destacadas (nombre, ruta_imagen, orden) VALUES (?, ?, ?)");
                    $stmt->execute([$nombre, $rutaBD, $orden]);
                }
            }
            header("Location: " . BASE_URL . "admin/marcas");
        }
    }

    public function actualizarMarcaDestacada()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $orden = $_POST['orden'] ?? 1;

            // 1. Actualizar datos básicos
            $stmt = $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, orden = ? WHERE id = ?");
            $stmt->execute([$nombre, $orden, $id]);

            // 2. Si se subió una imagen nueva, procesarla
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                // Borrar imagen anterior (opcional pero recomendado)
                $stmtImg = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?");
                $stmtImg->execute([$id]);
                $vieja = $stmtImg->fetchColumn();
                if ($vieja && file_exists(__DIR__ . '/../../public/' . $vieja)) {
                    unlink(__DIR__ . '/../../public/' . $vieja);
                }

                $nombreArchivo = 'logo_' . time() . '_' . $_FILES['imagen']['name'];
                $rutaFisica = __DIR__ . '/../../public/img/marcas_destacadas/' . $nombreArchivo;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFisica)) {
                    $rutaBD = 'img/marcas_destacadas/' . $nombreArchivo;
                    $stmtUp = $this->db->prepare("UPDATE marcas_destacadas SET ruta_imagen = ? WHERE id = ?");
                    $stmtUp->execute([$rutaBD, $id]);
                }
            }
            header("Location: " . BASE_URL . "admin/marcas?msg=actualizado");
            exit();
        }
    }

    public function borrarMarca($id)
    {
        $this->verificarAdmin();

        // 1. Buscamos la ruta de la imagen antes de borrar el registro
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?");
        $stmt->execute([$id]);
        $marca = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($marca) {
            // 2. Intentamos borrar el archivo físico del servidor
            $rutaFisica = __DIR__ . '/../../public/' . $marca['ruta_imagen'];
            if (file_exists($rutaFisica) && is_file($rutaFisica)) {
                unlink($rutaFisica);
            }

            // 3. Borramos el registro de la base de datos
            $stmtDel = $this->db->prepare("DELETE FROM marcas_destacadas WHERE id = ?");
            $stmtDel->execute([$id]);
        }

        header("Location: " . BASE_URL . "admin/marcas?msg=marca_eliminada");
        exit();
    }

    // =========================================================
    // 🛑 ANULACIÓN Y REEMBOLSO (WEBPAY)
    // =========================================================
    public function anularYReembolsarAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $idPedido = $data['pedido_id'] ?? null;
        $motivo = $data['motivo'] ?? '';
        $adminId = $_SESSION['user_id'];

        if (!$idPedido || empty($motivo)) {
            echo json_encode(['status' => false, 'message' => 'Faltan datos para anular.']);
            exit;
        }

        // 1. Intentar el reembolso en Transbank
        $webpayController = new \App\Controllers\WebpayController($this->db);
        $resultadoReembolso = $webpayController->reembolsarPagoAdmin($idPedido);

        if ($resultadoReembolso['status'] === true) {
            // 2. Si Transbank devuelve la plata, restauramos el stock interno
            $exitoStock = $this->pedidoModel->anularYDevolverStock($idPedido, $adminId, $motivo);

            if ($exitoStock) {
                // OPCIONAL: Aquí podrías llamar al MailService para enviar un correo de "Pedido Anulado y Dinero Devuelto"
                echo json_encode(['status' => true, 'message' => 'Dinero devuelto y stock restaurado.']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Se devolvió el dinero, pero hubo un error restaurando el stock. Revise el ERP.']);
            }
        } else {
            // Transbank rechazó la devolución
            echo json_encode(['status' => false, 'message' => $resultadoReembolso['msg']]);
        }
        exit;
    }

    public function buscar_reemplazo_ajax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $q = $_GET['q'] ?? '';
        $pedido_id = $_GET['pedido_id'] ?? null;

        if (strlen($q) < 3 || !$pedido_id) {
            echo json_encode([]);
            exit;
        }

        try {
            // Buscamos la sucursal del pedido actual
            $stmtPedido = $this->db->prepare("SELECT sucursal_codigo FROM pedidos WHERE id = ?");
            $stmtPedido->execute([$pedido_id]);
            $sucursal_codigo = $stmtPedido->fetchColumn();

            // Buscamos productos con stock > 0 en ESA sucursal
            $sql = "SELECT p.id, p.cod_producto, ps.precio, p.imagen, 
                           COALESCE(piw.nombre_web, p.nombre) as nombre,
                           (ps.stock - ps.stock_reservado) as stock_disponible
                    FROM productos p
                    INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                    WHERE (p.nombre LIKE ? OR p.cod_producto LIKE ? OR piw.nombre_web LIKE ?)
                    AND ps.sucursal_id = ? 
                    AND p.activo = 1 
                    AND (ps.stock - ps.stock_reservado) > 0
                    ORDER BY stock_disponible DESC
                    LIMIT 15";

            $stmt = $this->db->prepare($sql);
            $termino = "%$q%";
            $stmt->execute([$termino, $termino, $termino, $sucursal_codigo]);
            $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($productos as &$prod) {
                $prod['imagen'] = !empty($prod['imagen'])
                    ? (strpos($prod['imagen'], 'http') === 0 ? $prod['imagen'] : BASE_URL . 'img/productos/' . $prod['imagen'])
                    : BASE_URL . 'img/no-image.png';
            }

            echo json_encode($productos);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    public function dashboardStockFantasma()
    {
        // Traemos las alertas no revisadas y calculamos el stock actual que dice tener el sistema
        $sql = "SELECT a.*, p.cod_producto, 
                       (ps.stock - ps.stock_reservado) as stock_sistema
                FROM alertas_stock_fantasma a
                LEFT JOIN productos p ON a.producto_id = p.id
                LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                WHERE a.revisado = 0
                ORDER BY a.fecha DESC";

        $alertas = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $titulo = "Radar de Stock Fantasma";
        ob_start();
        include __DIR__ . '/../../views/admin/stock_fantasma.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function resolverStockFantasma()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $this->db->prepare("UPDATE alertas_stock_fantasma SET revisado = 1 WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        echo json_encode(['status' => 'error']);
        exit;
    }
    public function subirComprobantePago()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['comprobante']) && !empty($_POST['pedido_id'])) {
            $pedidoId = (int)$_POST['pedido_id'];

            // Capturamos el medio de pago real para la auditoría
            $metodoPagoReal = $_POST['metodo_pago_real'] ?? 'No especificado';

            $file = $_FILES['comprobante'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nuevoNombre = 'voucher_ORD_' . $pedidoId . '_' . time() . '.' . $ext;
                $rutaDestino = __DIR__ . '/../../public/img/comprobantes/' . $nuevoNombre;

                if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                    try {
                        $this->db->beginTransaction();

                        // ¡AQUÍ ESTÁ LA MAGIA! Agregamos metodo_pago_real a la actualización
                        $sql = "UPDATE pedidos 
                                SET comprobante_pago = ?, 
                                    estado_pago_id = 2, 
                                    estado_pedido_id = 3,
                                    metodo_pago_real = ?
                                WHERE id = ?";
                        $this->db->prepare($sql)->execute([$nuevoNombre, $metodoPagoReal, $pedidoId]);

                        // MAGIA DE AUDITORÍA en el Historial
                        if (method_exists($this->pedidoModel, 'registrarHistorial')) {
                            $mensajeAuditoria = "Comprobante físico adjuntado. Pagado con: **" . strtoupper($metodoPagoReal) . "**. Pedido confirmado y enviado a preparación.";
                            $this->pedidoModel->registrarHistorial($pedidoId, 3, $mensajeAuditoria);
                        }

                        $this->db->commit();
                        header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=comprobante_ok");
                        exit;
                    } catch (\Exception $e) {
                        if ($this->db->inTransaction()) {
                            $this->db->rollBack();
                        }
                        error_log("Error guardando comprobante: " . $e->getMessage());
                    }
                }
            }
        }

        header("Location: " . BASE_URL . "admin/pedidos?error=subida_comprobante");
        exit;
    }

    // ==========================================
    // 1. REORDENAR BANNERS (DRAG AND DROP)
    // ==========================================
    public function reordenarBannersAjax()
    {
        $this->verificarAdmin(); // Solo admins pueden hacer esto
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['ordenes']) && isset($data['tabla'])) {
            $tabla = ($data['tabla'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            foreach ($data['ordenes'] as $item) {
                $stmt = $this->db->prepare("UPDATE $tabla SET orden = ? WHERE id = ?");
                $stmt->execute([$item['orden'], $item['id']]);
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Datos inválidos']);
        }
        exit;
    }

    // ==========================================
    // 2. BUSCADOR DINÁMICO POR SUCURSAL
    // ==========================================
    public function buscarParaBannerAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $q = $_GET['q'] ?? '';
        $excluir = $_GET['excluir'] ?? '';

        // 🔥 LÓGICA DE ROLES: 
        // Si el admin envía una sucursal por GET (ej: Admin Mayor eligió "La Calera" en el select), usamos esa.
        // Si no, usamos la de su sesión obligatoria.
        if (empty($_SESSION['admin_sucursal'])) {
            // Es Admin Mayor
            $sucursal_buscar = isset($_GET['sucursal']) && $_GET['sucursal'] != 0 ? (int)$_GET['sucursal'] : 29; // Por defecto busca en 29 si elige "Ambas"
        } else {
            // Es Admin Local (Forzamos su sucursal)
            $sucursal_buscar = (int)$_SESSION['admin_sucursal'];
        }

        if (strlen($q) < 3) {
            echo json_encode([]);
            exit;
        }

        $sql = "SELECT p.cod_producto, COALESCE(piw.nombre_web, p.nombre) as nombre, p.imagen, ps.stock as stock_real 
                FROM productos p 
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                WHERE (p.cod_producto LIKE ? OR p.nombre LIKE ? OR piw.nombre_web LIKE ?) 
                AND p.activo = 1 AND ps.sucursal_id = $sucursal_buscar 
                AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0"; // 🔥 REGLA DE BUFFER APLICADA

        $params = ["%$q%", "%$q%", "%$q%"];
        if (!empty($excluir)) {
            $cods = array_filter(array_map('trim', explode(',', $excluir)));
            if (!empty($cods)) {
                $placeholders = implode(',', array_fill(0, count($cods), '?'));
                $sql .= " AND p.cod_producto NOT IN ($placeholders)";
                $params = array_merge($params, $cods);
            }
        }
        $sql .= " LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function cargarProductosPorCodigosAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $codigos = $_GET['codigos'] ?? '';

        // Misma lógica de roles para cargar la vista previa al editar
        if (empty($_SESSION['admin_sucursal'])) {
            $sucursal_buscar = isset($_GET['sucursal']) && $_GET['sucursal'] != 0 ? (int)$_GET['sucursal'] : 29;
        } else {
            $sucursal_buscar = (int)$_SESSION['admin_sucursal'];
        }

        if (empty($codigos)) {
            echo json_encode([]);
            exit;
        }

        $codsArray = array_filter(array_map('trim', explode(',', $codigos)));
        $placeholders = implode(',', array_fill(0, count($codsArray), '?'));
        $sql = "SELECT p.cod_producto, COALESCE(piw.nombre_web, p.nombre) as nombre, p.imagen, ps.stock as stock_real 
                FROM productos p 
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                WHERE p.cod_producto IN ($placeholders) AND ps.sucursal_id = $sucursal_buscar
                AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0"; // 🔥 REGLA DE BUFFER APLICADA

        $stmt = $this->db->prepare($sql);
        $stmt->execute($codsArray);
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    // ==========================================
    // ELIMINAR BANNER (MODO AJAX SIN RECARGA)
    // ==========================================
    public function borrarBannerAjax()
    {
        // $this->verificarAdmin(); // Descomenta si usas una función de validación de seguridad

        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['tipo'])) {
            $id = (int)$data['id'];
            $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            // Opcional: Borrar la imagen física del servidor si quieres ahorrar espacio
            try {
                $stmtImg = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?");
                $stmtImg->execute([$id]);
                $ruta = $stmtImg->fetchColumn();
                if ($ruta && file_exists($_SERVER['DOCUMENT_ROOT'] . '/tienda-online/public' . $ruta)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/tienda-online/public' . $ruta);
                }
            } catch (\Exception $e) {
                // Si la imagen no se borra, no importa, continuamos con la BD
            }

            // Borrar de la base de datos
            $stmt = $this->db->prepare("DELETE FROM $tabla WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }

        echo json_encode(['status' => 'error', 'msg' => 'Faltan datos o error en BD']);
        exit;
    }

    // ==========================================
    // MANTENEDOR DE PRODUCTOS NUEVOS (HOMOLOGACIÓN)
    // ==========================================

    public function productosNuevos()
    {
        $this->verificarAdmin();

        // Traemos productos INACTIVOS (Nuevos o apagados) junto con su info web si existe
        $sql = "SELECT p.id, p.cod_producto, p.nombre as nombre_erp, p.imagen, p.activo, 
                       piw.nombre_web, piw.marca_id, piw.web_categoria_id,
                       m.nombre as marca_nombre, wc.nombre as categoria_nombre
                FROM productos p
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                LEFT JOIN marcas m ON piw.marca_id = m.id
                LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                WHERE p.activo = 0
                ORDER BY p.id DESC";
        $productos = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Listas para los Combobox del Modal
        $categorias = $this->db->query("SELECT id, nombre FROM web_categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $marcas = $this->db->query("SELECT id, nombre FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../../views/admin/productos_nuevos.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php'; // Usa el nombre de tu layout admin
    }

    public function guardarProductoWebAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['cod_producto'])) {
            $cod_producto = $data['cod_producto'];
            $nombre_web = trim($data['nombre_web']);
            $marca_id = !empty($data['marca_id']) ? (int)$data['marca_id'] : null;
            $categoria_id = !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null;

            // Verificamos si ya existe el registro en info_web
            $stmtCheck = $this->db->prepare("SELECT cod_producto FROM productos_info_web WHERE cod_producto = ?");
            $stmtCheck->execute([$cod_producto]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe) {
                $stmt = $this->db->prepare("UPDATE productos_info_web SET nombre_web = ?, marca_id = ?, web_categoria_id = ? WHERE cod_producto = ?");
                $result = $stmt->execute([$nombre_web, $marca_id, $categoria_id, $cod_producto]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO productos_info_web (cod_producto, nombre_web, marca_id, web_categoria_id) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$cod_producto, $nombre_web, $marca_id, $categoria_id]);
            }

            if ($result) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'msg' => 'Faltan datos']);
        exit;
    }

    public function activarProductoWebAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['cod_producto'])) {
            $cod_producto = $data['cod_producto'];

            // 1. Validar que tenga imagen y nombre web
            $stmtVal = $this->db->prepare("
                SELECT p.imagen, piw.nombre_web 
                FROM productos p 
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                WHERE p.cod_producto = ?
            ");
            $stmtVal->execute([$cod_producto]);
            $prod = $stmtVal->fetch(\PDO::FETCH_ASSOC);

            if (!$prod) {
                echo json_encode(['status' => 'error', 'msg' => 'Producto no encontrado.']);
                exit;
            }

            if (empty($prod['nombre_web'])) {
                echo json_encode(['status' => 'error', 'msg' => 'Debes asignarle un "Nombre Web" antes de activarlo.']);
                exit;
            }

            // Validar que la imagen exista y no sea nula
            if (empty($prod['imagen']) || strpos($prod['imagen'], 'no-image') !== false) {
                echo json_encode(['status' => 'error', 'msg' => 'Falta la imagen. Solicita a Eliseo que cargue la foto antes de activar el producto.']);
                exit;
            }

            // 2. Si pasa las validaciones, lo activamos
            $stmtAct = $this->db->prepare("UPDATE productos SET activo = 1 WHERE cod_producto = ?");
            if ($stmtAct->execute([$cod_producto])) {
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'msg' => 'Error de servidor.']);
        exit;
    }
}
