<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoAdmin;
use App\Models\Analytics;
use App\Models\Auditoria;
use App\Services\ImportadorService;
use Exception;

class AdminController
{
    private $db;
    private $pedidoModel;
    private $productoModel;
    private $productoAdminModel;
    private $analyticsModel;
    private $auditoria;
    private $usuarioModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);
        $this->productoAdminModel = new ProductoAdmin($db);
        $this->usuarioModel = new \App\Models\Usuario($db);


        $this->auditoria = new Auditoria($db);

        require_once __DIR__ . '/../Models/Analytics.php';
        $this->analyticsModel = new Analytics($db);
    }

    private function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 🔥 Asegúrate de que el 4 esté aquí adentro
        if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2, 4])) {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    private function verificarSuperAdmin()
    {
        $this->verificarAdmin();
        // 🔥 ZONA EXCLUSIVA PARA ROL 1
        if ((int)$_SESSION['rol_id'] !== 1) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=solo_superadmin");
            exit();
        }
    }


    /**
     * Muestra el Dashboard de Ventas de la Sucursal
     */
    public function ventas_sucursal()
    {
        // 1. Verificamos que sea Admin (Rol 1 o 2)
        $this->verificarAdmin();

        // 2. Obtenemos la sucursal actual de la sesión
        $sucursal_id = $_SESSION['admin_sucursal'] ?? 0;

        // 3. Definimos el título de la página
        $titulo = ($sucursal_id == 29) ? "Ventas Prat La Calera" : (($sucursal_id == 10) ? "Ventas Villa Alemana" : "Ventas Globales");

        // 4. (Opcional) Aquí podrías cargar datos de la BD para los gráficos
        // $datosVentas = $this->modeloVentas->obtenerResumen($sucursal_id);

        // 5. Renderizamos la vista
        // Por esto (con la barra invertida):
        $content = \include_view('admin/ventas_sucursal', [
            'titulo' => $titulo,
            'sucursal_id' => $sucursal_id
        ]);

        require_once __DIR__ . '/../../views/layouts/main.php';
    }


    public function getUsuario()
    {
        // 1. Leer el ID que envía Javascript
        $input = json_decode(file_get_contents('php://input'), true);

        // 2. Preparar la cabecera JSON
        header('Content-Type: application/json; charset=utf-8');

        if (empty($input['id'])) {
            echo json_encode(['status' => 'error', 'msg' => 'ID de usuario no proporcionado.']);
            exit;
        }

        try {
            // 3. Buscar el usuario usando la función que ya existe en tu modelo
            $usuario = $this->usuarioModel->getById($input['id']);

            if ($usuario) {
                echo json_encode(['status' => 'success', 'data' => $usuario]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Usuario no encontrado.']);
            }
        } catch (\PDOException $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()]);
        }

        // 4. Salir limpiamente
        exit;
    }


    // =========================================================
    // 📊 DASHBOARD PRINCIPAL 
    // =========================================================
    public function dashboard()
    {
        $this->verificarAdmin();

        $desde = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        $_GET['desde'] = $desde;
        $_GET['hasta'] = $hasta;
        $sucursalAsignada = $_SESSION['admin_sucursal'] ?? null;

        $metricas = $this->pedidoModel->obtenerMetricasDashboard($desde, $hasta, $sucursalAsignada);

        $ventaPeriodo    = $metricas['venta_periodo'];
        $ingresoDespacho = $metricas['ingreso_despacho'];
        $pendientes      = $metricas['pendientes'];
        $datosGrafico    = $metricas['datos_grafico'];
        $topProductos    = $metricas['top_productos'];

        $stockCritico = $this->productoAdminModel->obtenerStockCritico($sucursalAsignada);
        $ultimosPedidos = $this->pedidoModel->obtenerUltimosPedidos($sucursalAsignada, 5);

        $mostrarSidebarAdmin = false;

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
        // 🔥 CANDADO SUPERADMIN
        $this->verificarSuperAdmin();

        $desde = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
        $busqueda = $_GET['q'] ?? '';
        $_GET['desde'] = $desde;
        $_GET['hasta'] = $hasta;

        $traficoChart = $this->analyticsModel->obtenerTrafico($desde, $hasta, $busqueda);
        $paginasTop   = $this->analyticsModel->obtenerPaginasPopulares($desde, $hasta, $busqueda);
        $clicsTop     = $this->analyticsModel->obtenerClicsPopulares($desde, $hasta, $busqueda);
        $kpis         = $this->analyticsModel->obtenerKPIs($desde, $hasta, $busqueda);
        $visitasMapaGlobal = $this->analyticsModel->obtenerVisitasMapaGlobal($desde, $hasta);

        $chartLabels = [];
        $chartData = [];
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
        // 🔥 CANDADO SUPERADMIN
        $this->verificarSuperAdmin();

        $sql = "SELECT u.id, u.nombre, u.rut, u.email, r.nombre_rol, u.es_cliente_confianza, u.creado_en 
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                ORDER BY u.id DESC";

        $usuarios_lista = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $roles_disponibles = $this->db->query("SELECT id, nombre_rol FROM roles ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);

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

        $stmt = $this->db->prepare("SELECT id, nombre, rut, razon_social, email, rol_id, es_cliente_confianza, puntos FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        echo json_encode($user ? ['status' => 'success', 'data' => $user] : ['status' => 'error']);
        exit;
    }

    public function actualizarUsuarioAjax()
    {
        header('Content-Type: application/json');
        try {
            // Protección extra: solo SuperAdmin puede editar usuarios
            $this->verificarSuperAdmin();

            $data = json_decode(file_get_contents('php://input'), true);

            $sql = "UPDATE usuarios SET 
                        nombre = ?, 
                        rut = ?, 
                        razon_social = ?, 
                        rol_id = ?, 
                        es_cliente_confianza = ?,
                        puntos = ?
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $res = $stmt->execute([
                $data['nombre'],
                $data['rut'],
                $data['razon_social'] ?? null,
                $data['rol_id'],
                $data['es_cliente_confianza'],
                $data['puntos'] ?? 0,
                $data['id']
            ]);

            if ($res) {
                $this->auditoria->registrar('UPDATE_USUARIO', 'usuarios', $data['id'], null, [
                    'rol_id' => $data['rol_id'],
                    'confianza' => $data['es_cliente_confianza']
                ]);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
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
        // 🔥 ESTE QUEDA ABIERTO PARA ROL 1 Y ROL 2 (Admin de Sucursal puede entrar)
        $this->verificarAdmin();
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
            try {
                $tipo = $_POST['tipo_carrusel'] ?? 'principal';
                $tabla = ($tipo === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
                $sucursal_id = (int)($_POST['sucursal_id'] ?? 0);

                if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("error_subida");
                }

                $check = getimagesize($_FILES['imagen']['tmp_name']);
                if ($check === false) {
                    throw new Exception("formato_invalido");
                }

                $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM $tabla WHERE sucursal_id = ?");
                $stmtCount->execute([$sucursal_id]);
                $totalBanners = $stmtCount->fetchColumn();

                $ordenCalculado = ($totalBanners == 0) ? 1 : 999;

                $esPermanente = isset($_POST['es_permanente']) && ($_POST['es_permanente'] === 'on' || $_POST['es_permanente'] == '1');
                $fechaInicio = (!$esPermanente && !empty($_POST['fecha_inicio'])) ? $_POST['fecha_inicio'] : null;
                $fechaFin = (!$esPermanente && !empty($_POST['fecha_fin'])) ? $_POST['fecha_fin'] : null;

                $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $ruta_destino = __DIR__ . '/../../public/img/banner/' . $nombre_archivo;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {

                    $sql = "INSERT INTO $tabla (titulo, enlace, palabra_clave, productos_ids, ruta_imagen, orden, sucursal_id, fecha_inicio, fecha_fin) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $_POST['titulo'],
                        $_POST['enlace'],
                        $_POST['palabra_clave'],
                        $_POST['productos_ids'],
                        'img/banner/' . $nombre_archivo,
                        $ordenCalculado,
                        $sucursal_id,
                        $fechaInicio,
                        $fechaFin
                    ]);

                    if (property_exists($this, 'auditoria') && $this->auditoria) {
                        $idInsertado = $this->db->lastInsertId();
                        $this->auditoria->registrar('INSERT_BANNER', $tabla, $idInsertado, null, ['titulo' => $_POST['titulo']]);
                    }

                    header("Location: " . BASE_URL . "admin/banners?msg=creado");
                    exit();
                } else {
                    throw new Exception("error_mover_archivo");
                }
            } catch (Exception $e) {
                error_log("Error en guardarBanner: " . $e->getMessage());
                header("Location: " . BASE_URL . "admin/banners?msg=error&detalle=" . $e->getMessage());
                exit();
            }
        }
    }

    public function cargarProductosPorCodigosAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $codigosRaw = $_GET['codigos'] ?? '';
            $sucursal_id = empty($_GET['sucursal']) ? 29 : (int)$_GET['sucursal'];

            if (empty($codigosRaw)) {
                echo json_encode([]);
                exit;
            }

            $arrayCodigos = explode(',', $codigosRaw);
            $placeholders = implode(',', array_fill(0, count($arrayCodigos), '?'));

            $sql = "SELECT p.id, 
                       p.cod_producto, 
                       COALESCE(piw.nombre_web, p.nombre) as nombre,
                       (COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) as stock,
                       p.imagen
                FROM productos p
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                     AND ps.sucursal_id = ?
                WHERE p.cod_producto IN ($placeholders)";

            $stmt = $this->db->prepare($sql);
            $params = array_merge([$sucursal_id], $arrayCodigos);
            $stmt->execute($params);

            $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $resultado = array_map(function ($prod) {
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
                    'stock_real'   => (int)$prod['stock'],
                    'imagen'       => $urlImagen
                ];
            }, $productos);

            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function actualizarBanner()
    {
        $this->verificarAdmin();
        $tabla = ($_POST['tipo_carrusel_edit'] ?? 'principal' === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $this->db->prepare("UPDATE $tabla SET titulo = ?, enlace = ?, palabra_clave = ?, productos_ids = ?, orden = ? WHERE id = ?")
            ->execute([$_POST['titulo'], $_POST['enlace'], $_POST['palabra_clave'], $_POST['productos_ids'], $_POST['orden'], $_POST['id']]);

        $this->auditoria->registrar('UPDATE_BANNER', $tabla, $_POST['id'], null, ['titulo' => $_POST['titulo']]);

        header("Location: " . BASE_URL . "admin/banners?msg=actualizado");
        exit();
    }

    public function toggleBannerAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

        $stmt = $this->db->prepare("SELECT estado_activo FROM $tabla WHERE id = ?");
        $stmt->execute([$data['id']]);
        $estadoAnterior = $stmt->fetchColumn();

        $nuevo = $estadoAnterior ? 0 : 1;
        $this->db->prepare("UPDATE $tabla SET estado_activo = ? WHERE id = ?")->execute([$nuevo, $data['id']]);

        $this->auditoria->registrar('TOGGLE_BANNER', $tabla, $data['id'], ['activo' => $estadoAnterior], ['activo' => $nuevo]);

        echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo]);
        exit;
    }

    public function borrarBanner($id)
    {
        $this->verificarAdmin();
        $tabla = ($_GET['tipo'] ?? 'principal' === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(__DIR__ . '/../../public/' . $img)) unlink(__DIR__ . '/../../public/' . $img);

        $this->auditoria->registrar('DELETE_BANNER', $tabla, $id, ['info' => 'Banner eliminado físicamente'], null);

        $this->db->prepare("DELETE FROM $tabla WHERE id = ?")->execute([$id]);
        header("Location: " . BASE_URL . "admin/banners?msg=eliminado");
        exit();
    }

    public function borrarBannerAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            $tabla = (isset($data['tipo']) && $data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';

            if ($id > 0) {
                $stmt = $this->db->prepare("SELECT ruta_imagen FROM $tabla WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();

                if ($img && file_exists(__DIR__ . '/../../public/' . $img)) {
                    unlink(__DIR__ . '/../../public/' . $img);
                }

                $this->auditoria->registrar('DELETE_BANNER', $tabla, $id, ['info' => 'Banner eliminado vía AJAX'], null);

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

    public function reordenarBannersAjax()
    {
        $this->verificarAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tabla'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        foreach ($data['ordenes'] as $item) {
            $this->db->prepare("UPDATE $tabla SET orden = ? WHERE id = ?")->execute([$item['orden'], $item['id']]);
        }

        $this->auditoria->registrar('REORDER_BANNER', $tabla, 0, null, ['info' => 'Banners reordenados']);

        echo json_encode(['status' => 'success']);
        exit;
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

            $sql = "SELECT p.id, 
                           p.cod_producto, 
                           COALESCE(piw.nombre_web, p.nombre) as nombre,
                           (COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) as stock,
                           p.imagen
                    FROM productos p
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
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

            $resultado = array_map(function ($prod) {
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
                    'stock_real'   => (int)$prod['stock'],
                    'imagen'       => $urlImagen
                ];
            }, $productos);

            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de SQL: ' . $e->getMessage()]);
        }
        exit;
    }

    public function actualizarFechasAjax()
    {
        $this->verificarAdmin();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $tabla = ($data['tipo'] === 'secundario') ? 'carrusel_secundario' : 'carrusel_banners';
        $res = $this->db->prepare("UPDATE $tabla SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?")->execute([$data['inicio'], $data['fin'], $data['id']]);

        if ($res) {
            $this->auditoria->registrar('UPDATE_BANNER_DATE', $tabla, $data['id'], null, ['inicio' => $data['inicio'], 'fin' => $data['fin']]);
        }

        echo json_encode(['status' => $res ? 'success' : 'error']);
        exit;
    }

    // =========================================================
    // 🤝 CMS: MARCAS / PARTNERS
    // =========================================================

    public function marcas()
    {
        // 🔥 CANDADO SUPERADMIN
        $this->verificarSuperAdmin();

        $marcas = $this->db->query("SELECT * FROM marcas_destacadas ORDER BY orden ASC")->fetchAll(\PDO::FETCH_ASSOC);
        ob_start();
        include __DIR__ . '/../../views/admin/marcas_mantenedor.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardarMarca()
    {
        $this->verificarSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $img = $_FILES['imagen'];
            if ($img['error'] === UPLOAD_ERR_OK) {
                $nombre = time() . '_' . basename($img['name']);
                move_uploaded_file($img['tmp_name'], __DIR__ . '/../../public/img/marcas_destacadas/' . $nombre);
                $this->db->prepare("INSERT INTO marcas_destacadas (nombre, ruta_imagen, orden) VALUES (?, ?, ?)")->execute([$_POST['nombre'], 'img/marcas_destacadas/' . $nombre, $_POST['orden']]);

                $this->auditoria->registrar('INSERT_MARCA', 'marcas_destacadas', $this->db->lastInsertId(), null, ['nombre' => $_POST['nombre']]);
            }
            header("Location: " . BASE_URL . "admin/marcas?msg=creada");
            exit;
        }
    }

    public function actualizarMarca()
    {
        $this->verificarSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            if ($_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $nombre = time() . '_' . basename($_FILES['imagen']['name']);
                move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/../../public/img/marcas_destacadas/' . $nombre);
                $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, ruta_imagen = ?, orden = ? WHERE id = ?")->execute([$_POST['nombre'], 'img/marcas_destacadas/' . $nombre, $_POST['orden'], $id]);
            } else {
                $this->db->prepare("UPDATE marcas_destacadas SET nombre = ?, orden = ? WHERE id = ?")->execute([$_POST['nombre'], $_POST['orden'], $id]);
            }

            $this->auditoria->registrar('UPDATE_MARCA', 'marcas_destacadas', $id, null, ['nombre' => $_POST['nombre']]);

            header("Location: " . BASE_URL . "admin/marcas?msg=actualizada");
            exit;
        }
    }

    public function toggleMarcaAjax()
    {
        $this->verificarSuperAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->db->prepare("SELECT estado_activo FROM marcas_destacadas WHERE id = ?");
        $stmt->execute([$data['id']]);

        $estadoAnterior = $stmt->fetchColumn();
        $nuevo = $estadoAnterior ? 0 : 1;

        $this->db->prepare("UPDATE marcas_destacadas SET estado_activo = ? WHERE id = ?")->execute([$nuevo, $data['id']]);

        $this->auditoria->registrar('TOGGLE_MARCA', 'marcas_destacadas', $data['id'], ['activo' => $estadoAnterior], ['activo' => $nuevo]);

        echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo]);
        exit;
    }

    public function borrarMarca($id)
    {
        $this->verificarSuperAdmin();

        $stmt = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(__DIR__ . '/../../public/' . $img)) unlink(__DIR__ . '/../../public/' . $img);

        $this->auditoria->registrar('DELETE_MARCA', 'marcas_destacadas', $id, null, null);

        $this->db->prepare("DELETE FROM marcas_destacadas WHERE id = ?")->execute([$id]);
        header("Location: " . BASE_URL . "admin/marcas?msg=eliminada");
        exit();
    }

    public function borrarMarcaAjax()
    {
        $this->verificarSuperAdmin();
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            if ($id > 0) {
                $stmt = $this->db->prepare("SELECT ruta_imagen FROM marcas_destacadas WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();

                if ($img && file_exists(__DIR__ . '/../../public/' . $img)) {
                    unlink(__DIR__ . '/../../public/' . $img);
                }

                if (property_exists($this, 'auditoria') && $this->auditoria) {
                    $this->auditoria->registrar('DELETE_MARCA', 'marcas_destacadas', $id, ['info' => 'Marca eliminada vía AJAX'], null);
                }

                $this->db->prepare("DELETE FROM marcas_destacadas WHERE id = ?")->execute([$id]);

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'ID de marca no válido']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function reordenarMarcasAjax()
    {
        $this->verificarSuperAdmin();

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (isset($data['ordenes']) && is_array($data['ordenes'])) {
            try {
                $this->db->beginTransaction();
                $stmt = $this->db->prepare("UPDATE marcas_destacadas SET orden = ? WHERE id = ?");

                foreach ($data['ordenes'] as $item) {
                    $stmt->execute([$item['orden'], $item['id']]);
                }

                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Orden de marcas actualizado']);
            } catch (\Exception $e) {
                $this->db->rollBack();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        }
        exit();
    }

    public function buscarReemplazoAjax()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $q = $_GET['q'] ?? '';
            $pedido_id = (int)($_GET['pedido_id'] ?? 0);

            if (strlen($q) < 2) {
                echo json_encode([]);
                exit;
            }

            $stmtSuc = $this->db->prepare("SELECT sucursal_codigo FROM pedidos WHERE id = ?");
            $stmtSuc->execute([$pedido_id]);
            $sucursal_codigo = $stmtSuc->fetchColumn();

            if (!$sucursal_codigo) $sucursal_codigo = '29';

            $sql = "SELECT p.id, 
                           p.cod_producto, 
                           COALESCE(piw.nombre_web, p.nombre) as nombre,
                           COALESCE(ps.stock, 0) as stock_real,
                           COALESCE(ps.precio, p.precio, 0) as precio,
                           p.imagen
                    FROM productos p
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
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
                ':sucursal' => $sucursal_codigo
            ]);

            $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $resultado = array_map(function ($prod) {
                $img = $prod['imagen'];
                $urlImagen = (!empty($img))
                    ? (strpos($img, 'http') === 0 ? $img : BASE_URL . 'img/productos/' . $img)
                    : BASE_URL . 'img/no-image.png';

                return [
                    'id'               => $prod['id'],
                    'cod_producto'     => $prod['cod_producto'],
                    'nombre'           => $prod['nombre'],
                    'precio'           => (int)$prod['precio'],
                    'stock_disponible' => max(0, (int)$prod['stock_real']),
                    'imagen'           => $urlImagen
                ];
            }, $productos);

            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de BD: ' . $e->getMessage()]);
        }
        exit;
    }

    // =========================================================
    // ⚙️ TAREAS DE SISTEMA Y ERP
    // =========================================================

    public function importarERP()
    {
        $this->verificarSuperAdmin(); // 🔥 Tarea crítica, solo SuperAdmin
        try {
            $service = new ImportadorService($this->db);
            $reporte = $service->ejecutar(dirname(__DIR__, 2) . '/erp_data/');
            $_SESSION['ultimo_reporte_erp'] = $reporte;

            $this->auditoria->registrar('SYNC_ERP', 'sistema', 0, null, ['info' => 'Sincronización manual ejecutada']);

            header("Location: " . BASE_URL . "admin/dashboard?msg=sync_ok");
        } catch (Exception $e) {
            header("Location: " . BASE_URL . "admin/dashboard?msg=error&info=" . urlencode($e->getMessage()));
        }
        exit();
    }

    public function cambiarSucursalAjax()
    {
        // 1. Verificamos que sea un administrador válido
        $this->verificarAdmin();

        // 2. Leemos la sucursal enviada por AJAX
        if (isset($_POST['sucursal_id'])) {
            // 3. Actualizamos la variable de sesión clave
            $_SESSION['admin_sucursal'] = (int)$_POST['sucursal_id'];

            echo json_encode(['status' => 'success', 'nueva_sucursal' => $_SESSION['admin_sucursal']]);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }
    public function filtrarUsuarios()
    {
        // 1. Recibir y decodificar el JSON enviado por JS
        $input = json_decode(file_get_contents('php://input'), true);

        $busqueda = $input['busqueda'] ?? '';
        $rol_id = $input['rol_id'] ?? '';
        $sucursal_id = $input['sucursal_id'] ?? '';
        $estado = $input['estado'] ?? '';
        $confianza = $input['confianza'] ?? '';

        // 🔥 LÓGICA DE PAGINACIÓN 🔥
        $pagina = isset($input['pagina']) ? (int)$input['pagina'] : 1;
        $limite = 15; // Queremos 15 usuarios por página
        $offset = ($pagina - 1) * $limite; // Desde dónde empezar a buscar

        try {
            // 2. Traer solo los 15 usuarios correspondientes a la página actual
            $usuarios = $this->usuarioModel->obtenerFiltradosAdmin($busqueda, $rol_id, $sucursal_id, $estado, $confianza, $limite, $offset);

            // 3. Contar CUÁNTOS usuarios hay en total con esos filtros
            $totalRegistros = $this->usuarioModel->contarFiltradosAdmin($busqueda, $rol_id, $sucursal_id, $estado, $confianza);

            // 4. Calcular el total de páginas (ej: 40 registros / 15 = 3 páginas)
            $totalPaginas = ceil($totalRegistros / $limite);

            // 5. Enviar el JSON con los datos Y la paginación
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'data' => $usuarios,
                'paginacion' => [               // <--- ¡Esto es lo que Javascript está esperando!
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_registros' => $totalRegistros
                ]
            ]);
        } catch (\PDOException $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Error de BD: ' . $e->getMessage()]);
        }
        exit;
    }
    public function cambiarEstado()
    {
        $this->verificarAdmin();

        // Evitamos accesos directos por URL
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "admin/pedidos");
            exit;
        }

        $pedidoId = $_POST['pedido_id'] ?? null;
        $nuevoEstado = (int)$_POST['estado_id'];

        $pedidoActual = $this->pedidoModel->obtenerPorId($pedidoId);

        if (!$pedidoActual) {
            header("Location: " . BASE_URL . "admin/pedidos?msg=error");
            exit;
        }

        $estadoActual = (int)$pedidoActual['estado_pedido_id'];

        // 🔥 CORTAFUEGOS DE ESTADOS (SEGURIDAD ESTRICTA)

        // 1. Si el pedido ya está "Entregado" (5) o "Anulado" (6), es intocable.
        if ($estadoActual === 5 && $nuevoEstado !== 5) {
            header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=error_retroceso");
            exit;
        }
        if ($estadoActual === 6 && $nuevoEstado !== 6) {
            header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=error_retroceso");
            exit;
        }

        // 2. Regla de "No volver atrás": El nuevo estado no puede ser menor al actual...
        // ... EXCEPTO si el nuevo estado es 6 (Anulación), ya que puedes anular desde los estados 1, 2, 3 o 4.
        if ($nuevoEstado < $estadoActual && $nuevoEstado !== 6) {
            header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=error_retroceso");
            exit;
        }

        // Si pasa todas las validaciones de seguridad, entonces actualizamos:
        $this->pedidoModel->actualizarEstado($pedidoId, $nuevoEstado);

        header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedidoId . "?msg=estado_actualizado");
        exit;
    }

    // =========================================================
    // 🛒 MODO VENTA ASISTIDA (PUNTO DE VENTA)
    // =========================================================
    public function iniciarVentaAsistida()
    {
        $this->verificarAdmin();

        // 1. Limpiamos carrito anterior
        $_SESSION['carrito'] = [];
        $_SESSION['carrito_cantidad'] = 0;
        $_SESSION['carrito_total'] = 0;

        // 2. Activamos la Venta Asistida
        $_SESSION['modo_venta_asistida'] = true;

        // 3. Forzamos sucursal (Prat 29 -> Comuna 63 / Villa 10 -> Comuna 10)
        $sucursal_id = $_SESSION['admin_sucursal'] ?? 29;
        
        // 🔥 EL FIX: Usar los nombres exactos que lee el CarritoController y Checkout
        $_SESSION['sucursal_activa'] = $sucursal_id; 
        $_SESSION['sucursal_codigo'] = $sucursal_id; 
        $_SESSION['comuna_id'] = ($sucursal_id == 29) ? 63 : 10;

        // 4. 🔥 Redirigimos al HOME (Página principal)
        header("Location: " . BASE_URL . "home?msg=venta_asistida_on");
        exit();
    }

    public function salirVentaAsistida()
    {
        $this->verificarAdmin();

        // 1. Limpiamos absolutamente todo lo relacionado a la venta
        unset($_SESSION['modo_venta_asistida']);
        unset($_SESSION['cliente_venta_asistida']);
        
        // 🔥 Limpiamos las variables de sucursal para que no queden pegadas
        unset($_SESSION['sucursal_activa']); 
        unset($_SESSION['sucursal_codigo']); 
        
        $_SESSION['carrito'] = [];
        $_SESSION['carrito_cantidad'] = 0;
        $_SESSION['carrito_total'] = 0;

        // 2. Detectamos el rol para saber a dónde volver
        $rolId = (int)($_SESSION['rol_id'] ?? 0);

        if ($rolId === 4) {
            header("Location: " . BASE_URL . "empleos/rrhh_dashboard?msg=venta_finalizada");
        } else {
            header("Location: " . BASE_URL . "admin/dashboard?msg=venta_finalizada");
        }
        exit();
    }

    public function actualizarUsuario()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        header('Content-Type: application/json; charset=utf-8');

        if (!$input || empty($input['id'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos para actualizar.']);
            exit;
        }

        try {
            $resultado = $this->usuarioModel->actualizarDesdeAdmin($input['id'], $input);

            if ($resultado) {
                echo json_encode(['status' => 'success', 'msg' => 'Usuario actualizado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar el usuario.']);
            }
        } catch (\PDOException $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error de BD: ' . $e->getMessage()]);
        }

        exit;
    }

    public function crearColaborador()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        header('Content-Type: application/json; charset=utf-8');

        if (empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Nombre, email y contraseña son obligatorios.']);
            exit;
        }

        try {
            $resultado = $this->usuarioModel->crearColaborador($input);

            if ($resultado) {
                echo json_encode(['status' => 'success', 'msg' => 'Usuario creado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Error al crear el usuario.']);
            }
        } catch (\PDOException $e) {
            // Es muy común un error si el correo o RUT ya existen (llave única)
            $msg = strpos($e->getMessage(), 'Duplicate entry') !== false
                ? 'El correo o RUT ya está registrado.'
                : 'Error: ' . $e->getMessage();

            echo json_encode(['status' => 'error', 'msg' => $msg]);
        }
        exit;
    }
}
