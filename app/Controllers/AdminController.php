<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoAdmin;
use App\Models\Analytics;
use App\Services\ImportadorService;
use Exception;

class AdminController
{
    private $db;
    private $pedidoModel;
    private $productoModel;
    private $productoAdminModel;
    private $analyticsModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);
        $this->productoAdminModel = new ProductoAdmin($db);

        require_once __DIR__ . '/../Models/Analytics.php';
        $this->analyticsModel = new Analytics($db);
    }

    // =========================================================
    // 🛡️ SEGURIDAD Y ACCESO
    // =========================================================

    private function verificarAdmin()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    private function verificarSuperAdmin()
    {
        $this->verificarAdmin();
        if (!empty($_SESSION['admin_sucursal'])) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=solo_superadmin");
            exit();
        }
    }

    // =========================================================
    // 📊 DASHBOARD PRINCIPAL (EL CORAZÓN)
    // =========================================================

    public function dashboard()
    {
        $this->verificarAdmin();

        $desde = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        $_GET['desde'] = $desde; $_GET['hasta'] = $hasta;

        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;
        $paramsBase = [':desde' => $desde, ':hasta' => $hasta];
        $filtroSucursal = ""; $filtroSucursalTop = "";

        if (!empty($sucursalAsignada)) {
            $filtroSucursal = " AND sucursal_codigo = :sucursal";
            $filtroSucursalTop = " AND ped.sucursal_codigo = :sucursal";
            $paramsBase[':sucursal'] = strval($sucursalAsignada);
        }

        // A. VENTA TOTAL Y DESPACHOS
        $sqlVenta = "SELECT COALESCE(SUM(monto_total), 0) as total_general, COALESCE(SUM(costo_envio), 0) as total_despacho
                     FROM pedidos WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal AND DATE(fecha_creacion) BETWEEN :desde AND :hasta";
        $stmtVenta = $this->db->prepare($sqlVenta);
        $stmtVenta->execute($paramsBase);
        $rowVenta = $stmtVenta->fetch(\PDO::FETCH_ASSOC);
        $ventaPeriodo = (float)$rowVenta['total_general'];
        $ingresoDespacho = (float)$rowVenta['total_despacho'];

        // B. PEDIDOS PENDIENTES
        $paramsPend = !empty($sucursalAsignada) ? [':sucursal' => strval($sucursalAsignada)] : [];
        $sqlPend = "SELECT COUNT(*) FROM pedidos WHERE estado_pedido_id = 1" . (!empty($sucursalAsignada) ? " AND sucursal_codigo = :sucursal" : "");
        $stmtPend = $this->db->prepare($sqlPend); $stmtPend->execute($paramsPend);
        $pendientes = $stmtPend->fetchColumn();

        // C. PRODUCTOS BAJO STOCK
        if (!empty($sucursalAsignada)) {
            $sqlStock = "SELECT p.nombre, ps.stock, p.imagen FROM productos_sucursales ps JOIN productos p ON ps.cod_producto = p.cod_producto 
                         WHERE ps.stock < 10 AND ps.sucursal_id = :sucursal ORDER BY ps.stock ASC LIMIT 5";
            $stmtStock = $this->db->prepare($sqlStock); $stmtStock->execute([':sucursal' => $sucursalAsignada]);
            $stockCritico = $stmtStock->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $sqlStock = "SELECT p.nombre, SUM(ps.stock) as stock, p.imagen FROM productos_sucursales ps JOIN productos p ON ps.cod_producto = p.cod_producto 
                         GROUP BY p.id HAVING stock < 10 ORDER BY stock ASC LIMIT 5";
            $stockCritico = $this->db->query($sqlStock)->fetchAll(\PDO::FETCH_ASSOC);
        }

        // D. GRAFICO Y TOP PRODUCTOS
        $datosGrafico = $this->db->prepare("SELECT DATE_FORMAT(fecha_creacion, '%d/%m') as fecha, SUM(monto_total) as total FROM pedidos 
                                           WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal AND DATE(fecha_creacion) BETWEEN :desde AND :hasta 
                                           GROUP BY DATE(fecha_creacion) ORDER BY fecha_creacion ASC");
        $datosGrafico->execute($paramsBase); $datosGrafico = $datosGrafico->fetchAll(\PDO::FETCH_ASSOC);

        $topProductos = $this->db->prepare("SELECT p.nombre, SUM(dp.cantidad) as vendidos FROM pedidos_detalle dp JOIN pedidos ped ON dp.pedido_id = ped.id 
                                            JOIN productos p ON dp.producto_id = p.id WHERE ped.estado_pedido_id NOT IN (1, 6) $filtroSucursalTop 
                                            AND DATE(ped.fecha_creacion) BETWEEN :desde AND :hasta GROUP BY p.id ORDER BY vendidos DESC LIMIT 5");
        $topProductos->execute($paramsBase); $topProductos = $topProductos->fetchAll(\PDO::FETCH_ASSOC);

        // E. ÚLTIMOS 5 PEDIDOS
        $recientes = $this->db->prepare("SELECT p.*, u.nombre as nombre_cliente, ep.nombre as estado, ep.badge_class as color_estado FROM pedidos p 
                                         LEFT JOIN usuarios u ON p.usuario_id = u.id LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id 
                                         WHERE 1=1 $filtroSucursal ORDER BY p.id DESC LIMIT 5");
        $recientes->execute(!empty($sucursalAsignada) ? [':sucursal' => strval($sucursalAsignada)] : []);
        $ultimosPedidos = $recientes->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../../views/admin/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 📈 ANALÍTICA WEB (REPORTE DE TRAFICO)
    // =========================================================

    public function analytics()
    {
        $this->verificarSuperAdmin();
        $desde = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        $busqueda = $_GET['q'] ?? '';
        $_GET['desde'] = $desde; $_GET['hasta'] = $hasta;

        $traficoChart = $this->analyticsModel->obtenerTrafico($desde, $hasta, $busqueda);
        $paginasTop   = $this->analyticsModel->obtenerPaginasPopulares($desde, $hasta, $busqueda);
        $clicsTop     = $this->analyticsModel->obtenerClicsPopulares($desde, $hasta, $busqueda);
        $kpis         = $this->analyticsModel->obtenerKPIs($desde, $hasta, $busqueda);
        $visitasMapa  = $this->analyticsModel->obtenerVisitasPorComuna($desde, $hasta, $busqueda);

        $chartLabels = []; $chartData = [];
        foreach ($traficoChart as $dato) {
            $chartLabels[] = date_format(date_create($dato['etiqueta']), 'd/m');
            $chartData[] = $dato['total'];
        }

        $esAdmin = true;
        ob_start();
        require_once __DIR__ . '/../../views/admin/analytics_dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 👤 GESTIÓN DE USUARIOS
    // =========================================================

    public function usuarios()
    {
        $this->verificarAdmin();
        $usuarios_lista = $this->db->query("SELECT id, nombre, rut, email, rol, es_cliente_confianza, creado_en FROM usuarios ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC);
        ob_start();
        include __DIR__ . '/../../views/admin/usuarios.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function obtenerUsuarioAjax()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT id, nombre, rut, email, rol, es_cliente_confianza FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo json_encode($user ? ['status' => 'success', 'data' => $user] : ['status' => 'error']);
        exit;
    }

    public function actualizarUsuarioAjax()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, rut = ?, rol = ?, es_cliente_confianza = ? WHERE id = ?");
        $res = $stmt->execute([$data['nombre'], $data['rut'], $data['rol'], $data['es_cliente_confianza'], $data['id']]);
        echo json_encode($res ? ['status' => 'success'] : ['status' => 'error']);
        exit;
    }

    public function buscarClienteVentaAsistidaAjax()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $rutLimpio = strtoupper(str_replace(['.', '-'], '', trim($data['rut'] ?? '')));
        $stmt = $this->db->prepare("SELECT nombre, es_cliente_confianza FROM usuarios WHERE REPLACE(REPLACE(UPPER(rut), '.', ''), '-', '') = ? ORDER BY es_cliente_confianza DESC LIMIT 1");
        $stmt->execute([$rutLimpio]);
        $cliente = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo json_encode($cliente ? ['status' => 'success', 'nombre' => $cliente['nombre'], 'es_cliente_confianza' => (int)$cliente['es_cliente_confianza']] : ['status' => 'not_found']);
        exit;
    }

    // =========================================================
    // 🖼️ CMS: BANNERS (PRINCIPAL Y SECUNDARIO)
    // =========================================================

    public function banners()
    {
        $this->verificarSuperAdmin();
        $bannersPrincipal = $this->db->query("SELECT * FROM carrusel_banners ORDER BY orden ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $bannersSecundario = $this->db->query("SELECT * FROM carrusel_secundario ORDER BY orden ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $esAdmin = true;
        ob_start();
        require_once __DIR__ . '/../../views/admin/banners.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

   public function guardarBanner()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Definir tabla de destino
            $tipo = $_POST['tipo_carrusel'] ?? 'principal';
            $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
            
            // 2. Lógica de Programación Permanente (La magia del NULL)
            // Verificamos si el switch existe y está marcado ('on' o '1' dependiendo de tu HTML)
            $esPermanente = isset($_POST['es_permanente']) && ($_POST['es_permanente'] === 'on' || $_POST['es_permanente'] == '1');
            
            // Si es permanente o si el campo viene vacío, enviamos un NULL real a la base de datos
            $fechaInicio = (!$esPermanente && !empty($_POST['fecha_inicio'])) ? $_POST['fecha_inicio'] : null;
            $fechaFin = (!$esPermanente && !empty($_POST['fecha_fin'])) ? $_POST['fecha_fin'] : null;

            // 3. Procesamiento de Imagen y Guardado
            $img = $_FILES['imagen'];
            if ($img['error'] === UPLOAD_ERR_OK) {
                $nombre_archivo = time() . '_' . basename($img['name']);
                
                if (move_uploaded_file($img['tmp_name'], __DIR__ . '/../../public/img/banner/' . $nombre_archivo)) {
                    $sql = "INSERT INTO $tabla (titulo, enlace, palabra_clave, productos_ids, ruta_imagen, orden, sucursal_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $_POST['titulo'], 
                        $_POST['enlace'], 
                        $_POST['palabra_clave'], 
                        $_POST['productos_ids'], 
                        'img/banner/' . $nombre_archivo, 
                        $_POST['orden'], 
                        $_POST['sucursal_id'], 
                        $fechaInicio, // Ahora viaja como NULL y no rompe el SQL
                        $fechaFin     // Ahora viaja como NULL y no rompe el SQL
                    ]);
                }
            }
            
            // Redirección clásica de vuelta al administrador
            header("Location: " . BASE_URL . "admin/banners?msg=creado"); 
            exit();
        }
    }

    public function actualizarBanner()
    {
        $this->verificarAdmin();
        $tabla = ($_POST['tipo_carrusel_edit'] ?? 'principal' === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $this->db->prepare("UPDATE $tabla SET titulo = ?, enlace = ?, palabra_clave = ?, productos_ids = ?, orden = ? WHERE id = ?")
                 ->execute([$_POST['titulo'], $_POST['enlace'], $_POST['palabra_clave'], $_POST['productos_ids'], $_POST['orden'], $_POST['id']]);
        header("Location: " . BASE_URL . "admin/banners?msg=actualizado"); exit();
    }

    public function toggleBannerAjax()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $stmt = $this->db->prepare("SELECT estado_activo FROM $tabla WHERE id = ?"); $stmt->execute([$data['id']]);
        $nuevo = $stmt->fetchColumn() ? 0 : 1;
        $this->db->prepare("UPDATE $tabla SET estado_activo = ? WHERE id = ?")->execute([$nuevo, $data['id']]);
        echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo]); exit;
    }

    public function borrarBanner($id)
    {
        $tabla = ($_GET['tipo'] ?? 'principal' === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?"); $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(__DIR__ . '/../../public/' . $img)) unlink(__DIR__ . '/../../public/' . $img);
        $this->db->prepare("DELETE FROM $tabla WHERE id = ?")->execute([$id]);
        header("Location: " . BASE_URL . "admin/banners?msg=eliminado"); exit();
    }

    public function reordenarBannersAjax()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tabla'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        foreach ($data['ordenes'] as $item) { $this->db->prepare("UPDATE $tabla SET orden = ? WHERE id = ?")->execute([$item['orden'], $item['id']]); }
        echo json_encode(['status' => 'success']); exit;
    }
public function buscarParaBannerAjax()
{
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $q = $_GET['q'] ?? '';
        $sucursal_id = empty($_GET['sucursal']) ? 29 : (int)$_GET['sucursal'];

        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }

        // 🔥 SQL CORREGIDO: Volvemos a tu estándar original (cod_producto)
        $sql = "SELECT p.id, 
                       p.cod_producto, 
                       COALESCE(piw.nombre_web, p.nombre) as nombre,
                       COALESCE(ps.stock, 0) as stock_real,
                       p.imagen
                FROM productos p
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                -- El cruce correcto con tu tabla de sucursales usando cod_producto
                LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                     AND ps.sucursal_id = :sucursal
                WHERE (p.cod_producto LIKE :term1 OR p.nombre LIKE :term2 OR piw.nombre_web LIKE :term3)
                AND p.activo = 1
                LIMIT 15";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':term1' => "%$q%",
            ':term2' => "%$q%",
            ':term3' => "%$q%",
            ':sucursal' => $sucursal_id
        ]);

        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $resultado = array_map(function($prod) {
            $img = $prod['imagen'];
            if (empty($img)) {
                $urlImagen = BASE_URL . 'img/no-image.png';
            } elseif (strpos($img, 'http') === 0) {
                $urlImagen = $img;
            } else {
                $urlImagen = BASE_URL . 'img/productos/' . $img;
            }

            return [
                'id'           => $prod['id'],
                'cod_producto' => $prod['cod_producto'],
                'nombre'       => $prod['nombre'],
                // Aplicamos tu regla de restarle 12 al stock real
                'stock'        => max(0, (int)$prod['stock_real'] - 12), 
                'imagen'       => $urlImagen
            ];
        }, $productos);

        echo json_encode($resultado);

    } catch (\Exception $e) {
        http_response_code(500);
        // Ahora, si falla, escupirá el error exacto de SQL
        echo json_encode(['error' => 'Error de SQL: ' . $e->getMessage()]);
    }
    exit;
}
    public function actualizarFechasAjax()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $res = $this->db->prepare("UPDATE $tabla SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?")->execute([$data['inicio'], $data['fin'], $data['id']]);
        echo json_encode(['status' => $res ? 'success' : 'error']); exit;
    }

    // =========================================================
    // 🤝 CMS: MARCAS / PARTNERS
    // =========================================================

    public function marcas()
    {
        $this->verificarAdmin();
        $marcas = $this->db->query("SELECT * FROM marcas_destacadas ORDER BY orden ASC")->fetchAll(\PDO::FETCH_ASSOC);
        ob_start(); include __DIR__ . '/../../views/admin/marcas_mantenedor.php'; $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardarMarca()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $img = $_FILES['imagen'];
            if ($img['error'] === UPLOAD_ERR_OK) {
                $nombre = time() . '_' . basename($img['name']);
                move_uploaded_file($img['tmp_name'], __DIR__ . '/../../public/img/marcas_destacadas/' . $nombre);
                $this->db->prepare("INSERT INTO marcas_destacadas (nombre, ruta_imagen, orden) VALUES (?, ?, ?)")->execute([$_POST['nombre'], 'img/marcas_destacadas/'.$nombre, $_POST['orden']]);
            }
            header("Location: " . BASE_URL . "admin/marcas?msg=creada"); exit;
        }
    }

    public function actualizarMarca()
    {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            if ($_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $nombre = time() . '_' . basename($_FILES['imagen']['name']);
                move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/../../public/img/marcas_destacadas/' . $nombre);
                $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, ruta_imagen = ?, orden = ? WHERE id = ?")->execute([$_POST['nombre'], 'img/marcas_destacadas/'.$nombre, $_POST['orden'], $id]);
            } else {
                $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, orden = ? WHERE id = ?")->execute([$_POST['nombre'], $_POST['orden'], $id]);
            }
            header("Location: " . BASE_URL . "admin/marcas?msg=actualizada"); exit;
        }
    }

    public function toggleMarcaAjax()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->db->prepare("SELECT estado_activo FROM marcas_destacadas WHERE id = ?"); $stmt->execute([$data['id']]);
        $nuevo = $stmt->fetchColumn() ? 0 : 1;
        $this->db->prepare("UPDATE marcas_destacadas SET estado_activo = ? WHERE id = ?")->execute([$nuevo, $data['id']]);
        echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo]); exit;
    }

    public function borrarMarca($id)
    {
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?"); $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(__DIR__ . '/../../public/' . $img)) unlink(__DIR__ . '/../../public/' . $img);
        $this->db->prepare("DELETE FROM marcas_destacadas WHERE id = ?")->execute([$id]);
        header("Location: " . BASE_URL . "admin/marcas?msg=eliminada"); exit();
    }

    public function reordenarMarcasAjax()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data['ordenes'] as $item) { $this->db->prepare("UPDATE marcas_destacadas SET orden = ? WHERE id = ?")->execute([$item['orden'], $item['id']]); }
        echo json_encode(['status' => 'success']); exit;
    }

    // =========================================================
    // ⚙️ TAREAS DE SISTEMA Y ERP
    // =========================================================

    public function importarERP()
    {
        $this->verificarAdmin();
        try {
            $service = new ImportadorService($this->db);
            $reporte = $service->ejecutar(dirname(__DIR__, 2) . '/erp_data/');
            $_SESSION['ultimo_reporte_erp'] = $reporte;
            header("Location: " . BASE_URL . "admin/dashboard?msg=sync_ok");
        } catch (Exception $e) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=error&info=" . urlencode($e->getMessage()));
        }
        exit();
    }

    public function borrarBannerAjax()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            // Verificamos de qué tabla viene
            $tabla = (isset($data['tipo']) && $data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            if ($id > 0) {
                // Buscamos la imagen para borrarla del servidor
                $stmt = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?"); 
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();
                
                if ($img && file_exists(__DIR__ . '/../../public/' . $img)) {
                    unlink(__DIR__ . '/../../public/' . $img);
                }
                
                // Borramos el registro
                $this->db->prepare("DELETE FROM $tabla WHERE id = ?")->execute([$id]);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}