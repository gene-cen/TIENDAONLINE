<?php

namespace App\Controllers;

use App\Models\Producto;
use App\Models\ProductoAdmin;
use Exception;

class AdminProductoController
{
    private $db;
    private $productoModel;
    private $productoAdminModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->productoModel = new Producto($db);
        $this->productoAdminModel = new ProductoAdmin($db);
    }

    private function verificarAdmin()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    // =========================================================
    // 📦 GESTIÓN DE INVENTARIO PRINCIPAL
    // =========================================================
    public function index()
    {
        $this->verificarAdmin();

        $filtros = [
            'busqueda'     => $_GET['q'] ?? '',
            'categoria'    => $_GET['categoria'] ?? '',
            'orden'        => $_GET['orden'] ?? '',
            'filtro_stock' => $_GET['filtro_stock'] ?? '',
            'marca_id'     => $_GET['marca'] ?? '',
            'sucursal'     => $_SESSION['admin_sucursal'] ?? null
        ];

        $por_pagina = 25;
        $pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $total_registros = $this->productoAdminModel->contarTotalAdmin($filtros);
        $total_paginas = ceil($total_registros / $por_pagina);

        $productos = $this->productoAdminModel->obtenerPaginadosAdmin($por_pagina, $offset, $filtros);
        
        // Cargar datos para los combos de la vista
        $categorias = $this->productoAdminModel->obtenerCategoriasUnicas();
        $listaMarcas = $this->db->query("SELECT id, nombre FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $esAdmin = true;
        ob_start();
        require_once __DIR__ . '/../../views/admin/productos_index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function toggleEstadoAjax()
    {
        header('Content-Type: application/json');
        $this->verificarAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if ($id) {
            $prod = $this->productoModel->obtenerPorId($id);
            $nuevo_estado = ($prod['activo'] == 1) ? 0 : 1;
            $this->productoAdminModel->cambiarEstado($id, $nuevo_estado);
            echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
            exit;
        }
        echo json_encode(['status' => 'error']);
    }

    // =========================================================
    // 🆕 HOMOLOGACIÓN: PRODUCTOS NUEVOS Y FOTOS
    // =========================================================
   // =========================================================
    // 🆕 HOMOLOGACIÓN: PRODUCTOS NUEVOS Y FOTOS
    // =========================================================
    public function productosNuevos()
    {
        $this->verificarAdmin();

        // 1. Traemos productos INACTIVOS (esperando nombre web/categoría)
        $productos = $this->db->query("
            SELECT p.id, p.cod_producto, p.nombre as nombre_erp, p.imagen, p.activo, 
                   piw.nombre_web, piw.marca_id, piw.web_categoria_id,
                   m.nombre as marca_nombre, wc.nombre as categoria_nombre
            FROM productos p
            LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
            LEFT JOIN marcas m ON piw.marca_id = m.id
            LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
            WHERE p.activo = 0
            ORDER BY p.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // 2. 🔥 ESTA ES LA QUE FALTABA: Traemos productos SIN FOTOGRAFÍA
        $productosSinFoto = $this->db->query("
            SELECT p.id, p.cod_producto, p.nombre as nombre_erp, p.imagen, p.activo, 
                   piw.nombre_web, piw.marca_id, piw.web_categoria_id,
                   m.nombre as marca_nombre, wc.nombre as categoria_nombre
            FROM productos p
            LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
            LEFT JOIN marcas m ON piw.marca_id = m.id
            LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
            WHERE p.imagen IS NULL OR p.imagen = '' OR p.imagen LIKE '%no-image%'
            ORDER BY p.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Listas para los Combobox del Modal
        $categorias = $this->db->query("SELECT id, nombre FROM web_categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $marcas = $this->db->query("SELECT id, nombre FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $esAdmin = true;
        ob_start();
        include __DIR__ . '/../../views/admin/productos_nuevos.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 👻 RADAR DE STOCK FANTASMA
    // =========================================================
    public function stockFantasma()
    {
        $this->verificarAdmin();
        $alertas = $this->db->query("
            SELECT a.*, p.cod_producto, (ps.stock - ps.stock_reservado) as stock_sistema
            FROM alertas_stock_fantasma a
            LEFT JOIN productos p ON a.producto_id = p.id
            LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
            WHERE a.revisado = 0 ORDER BY a.fecha DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../../views/admin/stock_fantasma.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 📥 EXPORTACIÓN
    // =========================================================
    public function exportarExcel()
    {
        $this->verificarAdmin();
        
        $filtros = [
            'busqueda'     => $_GET['q'] ?? '',
            'categoria'    => $_GET['categoria'] ?? '',
            'filtro_stock' => $_GET['filtro_stock'] ?? '',
            'marca_id'     => $_GET['marca'] ?? '',
            'sucursal'     => $_SESSION['admin_sucursal'] ?? null
        ];

        $productos = $this->productoAdminModel->obtenerPaginadosAdmin(9999, 0, $filtros);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Inventario_Cencocal_'.date('Ymd').'.csv"');
        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, ['SKU', 'Nombre WEB', 'Nombre ERP', 'Categoría', 'Precio Bruto', 'Stock Real', 'Estado'], ';');

        foreach ($productos as $p) {
            fputcsv($output, [
                $p->cod_producto,
                $p->nombre_web ?? 'No asignado',
                $p->nombre,
                $p->categoria_nombre,
                $p->precio,
                $p->stock,
                $p->activo ? 'Visible' : 'Oculto'
            ], ';');
        }
        fclose($output);
        exit;
    }
}