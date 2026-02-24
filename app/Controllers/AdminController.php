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
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }

    // =========================================================
    // 📊 DASHBOARD
    // =========================================================
   // En App/Controllers/AdminController.php

    public function dashboard()
    {
        // 1. Verificación de Seguridad
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        // --- FILTROS DE FECHA (Por defecto: Mes Actual) ---
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        // ======================================================
        // 2. OBTENCIÓN DE DATOS (KPIs)
        // ======================================================
        
        // A. VENTA TOTAL (Solo pedidos pagados/completados en el rango)
        // Ajusta los IDs de estado según tu DB (ej: 2=Pagado, 3=En Ruta, 4=Entregado)
        $sqlVenta = "SELECT COALESCE(SUM(monto_total), 0) FROM pedidos 
                     WHERE estado_pedido_id IN (2,3,4) 
                     AND DATE(fecha_creacion) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sqlVenta);
        $stmt->execute([$desde, $hasta]);
        $ventaPeriodo = $stmt->fetchColumn();

        // B. PEDIDOS PENDIENTES (Total histórico, no solo del rango, porque es trabajo acumulado)
        $sqlPend = "SELECT COUNT(*) FROM pedidos WHERE estado_pedido_id = 1"; // 1 = Pendiente
        $pendientes = $this->db->query($sqlPend)->fetchColumn();

        // C. PRODUCTOS BAJO STOCK (Stock Crítico < 10)
        $sqlStock = "SELECT nombre, stock, imagen FROM productos WHERE stock < 10 AND activo = 1 ORDER BY stock ASC LIMIT 5";
        $stockCritico = $this->db->query($sqlStock)->fetchAll(\PDO::FETCH_ASSOC);

        // ======================================================
        // 3. DATOS PARA GRÁFICOS Y TABLAS
        // ======================================================

        // D. VENTAS ÚLTIMOS 7 DÍAS (Para el gráfico)
        $sqlChart = "SELECT DATE_FORMAT(fecha_creacion, '%d/%m') as fecha, SUM(monto_total) as total 
                     FROM pedidos 
                     WHERE estado_pedido_id IN (2,3,4) 
                     AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY DATE(fecha_creacion) 
                     ORDER BY fecha_creacion ASC";
        $datosGrafico = $this->db->query($sqlChart)->fetchAll(\PDO::FETCH_ASSOC);

        // E. TOP 5 PRODUCTOS MÁS VENDIDOS (En el periodo seleccionado)
        $sqlTop = "SELECT p.nombre, SUM(dp.cantidad) as vendidos 
                   FROM pedidos_detalle dp
                   JOIN pedidos ped ON dp.pedido_id = ped.id
                   JOIN productos p ON dp.producto_id = p.id
                   WHERE ped.estado_pedido_id IN (2,3,4)
                   AND DATE(ped.fecha_creacion) BETWEEN ? AND ?
                   GROUP BY p.id 
                   ORDER BY vendidos DESC 
                   LIMIT 5";
        $stmtTop = $this->db->prepare($sqlTop);
        $stmtTop->execute([$desde, $hasta]);
        $topProductos = $stmtTop->fetchAll(\PDO::FETCH_ASSOC);

        // F. ÚLTIMOS 5 PEDIDOS (Resumen rápido)
        $pedidoModel = new \App\Models\Pedido($this->db);
        // Reusamos obtenerFiltrados pero limitamos a 5 para no cargar todo
        // Nota: Si obtenerFiltrados no tiene LIMIT, mejor hacemos una query simple aquí:
        $sqlRecientes = "SELECT p.*, u.nombre as nombre_cliente, ep.nombre as estado, ep.badge_class as color_estado 
                         FROM pedidos p 
                         LEFT JOIN usuarios u ON p.usuario_id = u.id
                         LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                         ORDER BY p.id DESC LIMIT 5";
        $ultimosPedidos = $this->db->query($sqlRecientes)->fetchAll(\PDO::FETCH_ASSOC);


        // ======================================================
        // 4. RENDERIZAR VISTA
        // ======================================================
        ob_start();
        include __DIR__ . '/../../views/admin/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php'; // <--- ESTO ES LO CORRECTO
    }
    // =========================================================
    // 📈 ANALÍTICA WEB
    // =========================================================
    public function analytics()
    {
        $this->verificarAdmin();

        // 1. Capturar Filtros
        $fechaInicio = $_GET['desde'] ?? date('Y-m-01'); // 1ro del mes actual
        $fechaFin    = $_GET['hasta'] ?? date('Y-m-d');  // Hoy
        $busqueda    = $_GET['q'] ?? '';                 // Cliente

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
            // Formatear fecha bonita para el gráfico
            $fechaObj = date_create($dato['etiqueta']);
            $chartLabels[] = date_format($fechaObj, 'd/m'); // Día/Mes
            $chartData[] = $dato['total'];
        }

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/analytics_dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 📦 GESTIÓN DE PEDIDOS
    // =========================================================
    public function verDetalle($id)
    {
        $this->verificarAdmin();
        $pedido = $this->pedidoModel->obtenerPorId($id);

        if (method_exists($this->pedidoModel, 'obtenerDetalleProductos')) {
            $detalles = $this->pedidoModel->obtenerDetalleProductos($id);
        } else {
            $detalles = $this->pedidoModel->obtenerDetalles($id);
        }

        if (!$pedido) {
            header("Location: " . BASE_URL . "admin/dashboard");
            exit();
        }

        // CORRECCIÓN: También aquí por si el admin quiere navegar
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;

        $esAdmin = true;

        ob_start();
        require_once __DIR__ . '/../../views/admin/detalle_pedido.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }
// En AdminController.php

   public function cambiarEstado()
    {
        // 1. Verificación básica de seguridad (Solo admin)
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
            header("Location: " . BASE_URL . "auth/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPedido = $_POST['pedido_id'];
            $nuevoEstadoId = $_POST['estado_id'];

            // 2. Actualizar el estado en la tabla principal (pedidos)
            $this->pedidoModel->actualizarEstado($idPedido, $nuevoEstadoId);

            // ------------------------------------------------------------------
            // 2.1. [NUEVO] REGISTRAR EN EL HISTORIAL (BITÁCORA)
            // ------------------------------------------------------------------
            // Esto guarda la fecha y hora exacta del cambio para la línea de tiempo
            $this->pedidoModel->registrarHistorial($idPedido, $nuevoEstadoId);


            // 3. ENVIAR CORREO AUTOMÁTICO AL CLIENTE
            try {
                // A. Obtenemos los datos del pedido
                $pedido = $this->pedidoModel->obtenerPorId($idPedido);
                
                // B. Obtenemos el nombre "bonito" del estado para el correo
                $stmt = $this->db->prepare("SELECT nombre FROM estados_pedido WHERE id = ?");
                $stmt->execute([$nuevoEstadoId]);
                $nombreEstado = $stmt->fetchColumn();

                // C. Enviamos el correo
                if ($pedido && !empty($pedido['email_cliente'])) {
                    $mailService = new \App\Services\MailService();
                    $mailService->enviarActualizacionEstado(
                        $pedido['email_cliente'],     // Email Destino
                        $pedido['nombre_cliente'],    // Nombre Cliente
                        $idPedido,                    // N° Pedido
                        $nombreEstado,                // Nuevo Estado (Texto)
                        $pedido['numero_seguimiento'] // Tracking
                    );
                }
            } catch (\Exception $e) {
                // Si falla el correo, solo registramos el error, no detenemos el flujo
                error_log("Error enviando mail estado: " . $e->getMessage());
            }

            // 4. Redireccionar de vuelta al detalle del pedido
            header("Location: " . BASE_URL . "admin/pedido/ver/" . $idPedido . "?msg=estado_actualizado");
            exit;
        } else {
            // Si intentan entrar directo por URL sin POST
            header("Location: " . BASE_URL . "admin/pedidos");
            exit;
        }
    }

    public function exportar()
    {
        $this->verificarAdmin();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pedidos_export.csv"');
        echo "ID,Fecha,Cliente,Total,Estado\n";
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

        $por_pagina = 20;
        $pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $total_registros = $this->productoModel->contarTotal($busqueda, $filtroCategoriaId);
        $total_paginas = ceil($total_registros / $por_pagina);

        $productos = $this->productoModel->obtenerPaginados($por_pagina, $offset, $busqueda, $filtroCategoriaId);
        
        // CORRECCIÓN: Definimos ambas variables
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
        $nuevo_estado = ($prod->activo == 1) ? 0 : 1;
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
        $ruta_base = dirname(__DIR__, 2);
        $archivo = $ruta_base . '/erp_data/29_productos_web2.csv';

        if (!file_exists($archivo)) die("Error: Archivo CSV no encontrado");

        $handle = fopen($archivo, "r");
        $fila = 0;
        while (($linea = fgets($handle)) !== false) {
            $fila++;
            if ($fila === 1) continue;
            $linea = trim($linea);
            if (empty($linea)) continue;
            $partes = explode(',', $linea);
            $total_partes = count($partes);
            if ($total_partes < 7) continue;

            $category_ids_raw = $partes[$total_partes - 1];
            $imagen_url  = $partes[$total_partes - 3];
            $stock       = (int) $partes[$total_partes - 4];
            $precio      = (int) $partes[$total_partes - 5];
            $sku         = trim($partes[$total_partes - 6]);
            $categoria   = trim($partes[$total_partes - 7]);
            $nombre_parts = array_slice($partes, 0, $total_partes - 7);
            $nombre       = trim(implode(',', $nombre_parts));
            $nombre       = mb_convert_encoding($nombre, 'UTF-8', 'ISO-8859-1');

            $datos = [
                'cod_producto' => $sku, 'nombre' => $nombre, 'categoria' => $categoria,
                'precio' => $precio, 'stock' => $stock, 'imagen' => $imagen_url, 'descripcion' => ''
            ];

            try {
                $this->db->beginTransaction();
                $producto_id = $this->productoModel->sincronizar($datos);
                if ($producto_id && method_exists($this->productoModel, 'sincronizarCategorias')) {
                    $this->productoModel->sincronizarCategorias($producto_id, $category_ids_raw);
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
            }
        }
        fclose($handle);
        header("Location: " . BASE_URL . "admin/dashboard?msg=sync_ok");
        exit();
    }

    public function buscarProductosAjax()
    {
        $this->verificarAdmin();
        $busqueda = $_GET['q'] ?? '';
        $categoriaId = $_GET['categoria'] ?? '';
        
        $productos = $this->productoModel->obtenerPaginados(50, 0, $busqueda, $categoriaId);

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

            if ($stock > 10) $badgeStock = '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 rounded-pill">'.$stock.' un.</span>';
            elseif ($stock > 0) $badgeStock = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 rounded-pill">'.$stock.' un.</span>';
            else $badgeStock = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 rounded-pill">Agotado</span>';

            $badgeEstado = $activo 
                ? '<span class="badge rounded-pill bg-success px-3">Visible</span>' 
                : '<span class="badge rounded-pill bg-secondary px-3">Oculto</span>';
            
            $iconoOjo = $activo ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-muted';

            echo '<tr>
                <td class="ps-4 py-2"><div class="bg-white border rounded-3 p-1 shadow-sm position-relative" style="width: 50px; height: 50px;"><img src="'.$rutaImagen.'" class="w-100 h-100 object-fit-contain" onerror="this.src=\''.BASE_URL.'img/no-image.png\'"></div></td>
                <td><div class="fw-bold text-dark text-truncate" style="max-width: 300px;" title="'.htmlspecialchars($nombre).'">'.htmlspecialchars($nombre).'</div><div class="d-flex align-items-center gap-2 mt-1"><span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;"><i class="bi bi-upc-scan me-1"></i>'.htmlspecialchars($codigo).'</span><span class="badge bg-cenco-indigo bg-opacity-10 text-cenco-indigo fw-bold" style="font-size: 0.7rem;">'.htmlspecialchars($catNombre).'</span></div></td>
                <td class="fw-bold text-cenco-indigo">$'.number_format($precio, 0, ',', '.').'</td>
                <td class="text-center">'.$badgeStock.'</td>
                <td class="text-center">'.$badgeEstado.'</td>
                <td class="text-end pe-4"><div class="btn-group">
                    <button onclick="toggleProducto('.$id.', this)" class="btn btn-sm btn-light border shadow-sm" title="'.($activo ? 'Ocultar' : 'Mostrar').'"><i class="bi '.$iconoOjo.'"></i></button>
                    <a href="'.BASE_URL.'admin/producto/editar/'.$id.'" class="btn btn-sm btn-light border shadow-sm text-primary"><i class="bi bi-pencil-fill"></i></a>
                    <button onclick="confirmarEliminar('.$id.')" class="btn btn-sm btn-light border shadow-sm text-danger"><i class="bi bi-trash-fill"></i></button>
                </div></td>
            </tr>';
        }
    }

    public function toggleProductoAjax()
    {
        $this->verificarAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if ($id) {
            $prod = $this->productoModel->obtenerPorId($id);
            if ($prod) {
                $nuevo_estado = ($prod->activo == 1) ? 0 : 1;
                $this->productoModel->cambiarEstado($id, $nuevo_estado);
                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
                exit;
            }
        }
        echo json_encode(['status' => 'error']);
        exit;
    }

    // =========================================================
    // 🛒 VISTA DEDICADA DE PEDIDOS
    // =========================================================
    public function pedidos()
    {
        $this->verificarAdmin();

        // 1. Capturar Filtros (Igual que en Dashboard)
        $fechaInicio = $_GET['desde'] ?? date('Y-m-01');
        $fechaFin    = $_GET['hasta'] ?? date('Y-m-d');
        $busqueda    = $_GET['q'] ?? '';
        $estado      = $_GET['estado'] ?? '';

        // 2. Obtener Pedidos con el método blindado del Modelo
        $pedidos = $this->pedidoModel->obtenerFiltrados($fechaInicio, $fechaFin, $busqueda, $estado);
        
        // 3. Variables para la vista
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias; // Para el sidebar
        $esAdmin = true;

        ob_start();
        // OJO: Crearemos este archivo en el paso 3
        require_once __DIR__ . '/../../views/admin/pedidos_index.php'; 
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 🖼️ MANTENEDOR: GESTIÓN DE BANNERS
    // =========================================================
    public function banners()
    {
        $this->verificarAdmin();

        // 1. Obtener todos los banners de la base de datos
        $stmt = $this->db->query("SELECT * FROM carrusel_banners ORDER BY orden ASC");
        $banners = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Variables obligatorias para que no se rompa el Layout
        $listaCategorias = $this->productoModel->obtenerCategoriasUnicas();
        $categorias = $listaCategorias;
        $esAdmin = true;

        // 3. Renderizar la vista que creaste
        ob_start();
        require_once __DIR__ . '/../../views/admin/banners.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

 public function guardarBanner()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = $_POST['titulo'] ?? '';
            // Si viene vacío, asigna 1 por defecto
            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 1; 
            
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
                    // Quitamos la URL de la consulta SQL
                    $stmt = $this->db->prepare("INSERT INTO carrusel_banners (titulo, ruta_imagen, orden) VALUES (?, ?, ?)");
                    $stmt->execute([$titulo, $ruta_bd, $orden]);
                }
            }
            
            header("Location: " . BASE_URL . "admin/banners?msg=banner_creado");
            exit();
        }
    }

    // Actualizar Título y Orden desde el Modal
    public function actualizarBanner()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $titulo = $_POST['titulo'] ?? '';
            $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 1;

            $stmt = $this->db->prepare("UPDATE carrusel_banners SET titulo = ?, orden = ? WHERE id = ?");
            $stmt->execute([$titulo, $orden, $id]);

            header("Location: " . BASE_URL . "admin/banners?msg=banner_actualizado");
            exit();
        }
    }

    // Activar/Desactivar (Ojito) por AJAX
    public function toggleBannerAjax()
    {
        $this->verificarAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if ($id) {
            $stmt = $this->db->prepare("SELECT estado_activo FROM carrusel_banners WHERE id = ?");
            $stmt->execute([$id]);
            $estado_actual = $stmt->fetchColumn();

            if ($estado_actual !== false) {
                $nuevo_estado = ($estado_actual == 1) ? 0 : 1;
                
                $stmtUp = $this->db->prepare("UPDATE carrusel_banners SET estado_activo = ? WHERE id = ?");
                $stmtUp->execute([$nuevo_estado, $id]);
                
                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
                exit;
            }
        }
        
        echo json_encode(['status' => 'error']);
        exit;
    }

    public function borrarBanner($id)
    {
        $this->verificarAdmin();
        
        // Buscar el banner para borrar primero el archivo de la carpeta
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM carrusel_banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($banner) {
            $ruta_fisica = __DIR__ . '/../../public/' . $banner['ruta_imagen'];
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                unlink($ruta_fisica); // Elimina la imagen del disco
            }
            // Elimina el registro de la BD
            $stmtDel = $this->db->prepare("DELETE FROM carrusel_banners WHERE id = ?");
            $stmtDel->execute([$id]);
        }
        
        header("Location: " . BASE_URL . "admin/banners?msg=banner_eliminado");
        exit();
    }

    // --- MARCAS DESTACADAS ---
public function marcasDestacadas() {
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

public function guardarMarcaDestacada() {
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

public function actualizarMarcaDestacada() {
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
            if($vieja && file_exists(__DIR__ . '/../../public/' . $vieja)) {
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
    
}