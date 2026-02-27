<?php

namespace App\Models;

use PDO;
use PDOException;

class Producto
{
    private $db;
    private $table = 'productos';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ====================================================================
    // 1. MÉTODOS DE LECTURA PRINCIPAL
    // ====================================================================
    private function getBaseQuery($para_web = true)
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;

        $sql = "SELECT p.id, p.cod_producto, p.nombre, p.descripcion, p.imagen, p.activo,
                       COALESCE(w.nombre_web, p.nombre) as nombre_mostrar,
                       ps.precio,
                       GREATEST(0, (ps.stock - ps.stock_reservado - COALESCE(w.stock_seguridad, 5))) as stock,
                       ps.stock as stock_real_erp,
                       m.nombre as nombre_marca,
                       wc.nombre as nombre_categoria_web,
                       wc.icono as icono_categoria,
                       wc.id as categoria_id,
                       wsc.nombre as nombre_subcategoria_web
                FROM productos p 
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                LEFT JOIN marcas m ON w.marca_id = m.id
                LEFT JOIN web_categorias wc ON w.web_categoria_id = wc.id
                LEFT JOIN web_subcategorias wsc ON w.web_subcategoria_id = wsc.id";

        if ($para_web) {
            // FILTRO TAJANTE: Visible, con Precio y de la Sucursal Correcta
            $sql .= " WHERE w.visible_web = 1 
                      AND ps.precio > 0 
                      AND ps.sucursal_id = $sucursal_id ";
        } else {
            // Para el admin, filtramos por sucursal pero dejamos ver precios 0 si existen
            $sql .= " WHERE ps.sucursal_id = $sucursal_id ";
        }
        return $sql;
    }

    public function getAll()
    {
        $sql = $this->getBaseQuery(false) . " ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerDisponibles()
    {
        $sql = $this->getBaseQuery(true) . " AND p.activo = 1 ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPorId($id)
    {
        $sql = $this->getBaseQuery(false) . " AND p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        return $this->obtenerPorId($id);
    }

    // ====================================================================
    // 2. PAGINACIÓN Y FILTROS
    // ====================================================================

    public function obtenerPorCategoriaWeb($web_categoria_id, $limite, $offset)
    {
        $sql = $this->getBaseQuery(true) . " 
                AND w.web_categoria_id = :cat_id AND p.activo = 1
                ORDER BY p.id DESC 
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat_id', $web_categoria_id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function contarPorCategoriaWeb($web_categoria_id)
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
        $sql = "SELECT COUNT(*) 
                FROM productos p
                INNER JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                WHERE w.web_categoria_id = :cat_id 
                  AND p.activo = 1 
                  AND w.visible_web = 1 
                  AND ps.precio > 0 
                  AND ps.sucursal_id = $sucursal_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cat_id' => $web_categoria_id]);
        return $stmt->fetchColumn();
    }

    // ====================================================================
    // 3. DATOS DE NEGOCIO (VENTAS Y RANKING) -> ¡AQUÍ ESTABA EL FALLO DEL HOME!
    // ====================================================================
    public function obtenerMasVendidos()
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;

        $sql = "SELECT p.id, p.cod_producto, p.nombre, p.descripcion, p.imagen, p.activo, 
                       COALESCE(w.nombre_web, p.nombre) as nombre_mostrar, 
                       COALESCE(SUM(pd.cantidad), 0) as total_vendido,
                       ps.precio,
                       GREATEST(0, (ps.stock - ps.stock_reservado - COALESCE(w.stock_seguridad, 5))) as stock,
                       m.nombre as nombre_marca,
                       wc.nombre as nombre_categoria_web
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                LEFT JOIN pedidos_detalle pd ON p.id = pd.producto_id
                LEFT JOIN marcas m ON w.marca_id = m.id
                LEFT JOIN web_categorias wc ON w.web_categoria_id = wc.id
                WHERE p.activo = 1 
                  AND w.visible_web = 1 
                  AND ps.precio > 0 
                  AND ps.sucursal_id = $sucursal_id
                GROUP BY p.id
                ORDER BY total_vendido DESC, p.id DESC
                LIMIT 5";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerCategoriasDestacadas()
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;

        $sql = "SELECT wc.id, wc.nombre, wc.imagen, wc.icono,
                       COUNT(DISTINCT p.id) as cantidad_productos,
                       COALESCE(SUM(pd.cantidad), 0) as ranking_ventas
                FROM web_categorias wc
                JOIN productos_info_web w ON wc.id = w.web_categoria_id
                JOIN productos p ON w.cod_producto = p.cod_producto
                JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                LEFT JOIN pedidos_detalle pd ON p.id = pd.producto_id
                WHERE wc.activo = 1 AND w.visible_web = 1 AND ps.precio > 0
                GROUP BY wc.id
                ORDER BY ranking_ventas DESC, cantidad_productos DESC
                LIMIT 6";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ====================================================================
    // 4. MÉTODOS PÚBLICOS
    // ====================================================================

    public function contarPublicos($busqueda = '')
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
        $sql = "SELECT COUNT(*) FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                INNER JOIN productos_info_web w ON p.cod_producto = w.cod_producto 
                WHERE p.activo = 1 
                  AND w.visible_web = 1 
                  AND ps.precio > 0 
                  AND ps.sucursal_id = $sucursal_id";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (w.nombre_web LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    public function obtenerPublicosPaginados($limite, $offset, $busqueda = '')
    {
        $sql = $this->getBaseQuery(true) . " AND p.activo = 1";
        $params = [':limite' => (int) $limite, ':offset' => (int) $offset];

        if (!empty($busqueda)) {
            $sql .= " AND (w.nombre_web LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }
        $sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerTodosPublicos($busqueda = '')
    {
        $sql = $this->getBaseQuery(true) . " AND p.activo = 1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (w.nombre_web LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }
        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ====================================================================
    // 5. MÉTODOS PARA EL PANEL DE ADMIN
    // ====================================================================

    public function obtenerCategoriasUnicas()
    {
        $sql = "SELECT id, nombre FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function contarTotal($busqueda = '', $categoriaId = '')
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
        $sql = "SELECT COUNT(*) 
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                LEFT JOIN productos_info_web pi ON p.cod_producto = pi.cod_producto
                LEFT JOIN web_categorias wc ON pi.web_categoria_id = wc.id
                WHERE 1=1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (pi.nombre_web LIKE :q1 OR p.nombre LIKE :q2 OR p.cod_producto LIKE :q3)";
            $term = "%$busqueda%";
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
        }
        if (!empty($categoriaId)) {
            $sql .= " AND wc.id = :catId";
            $params[':catId'] = $categoriaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerPaginados($limit, $offset, $busqueda = '', $categoriaId = '')
    {
        $sucursal_id = $_SESSION['sucursal_activa'] ?? 29;
        $sql = "SELECT 
                    p.id, p.cod_producto, p.nombre, p.descripcion, p.imagen, p.activo,
                    COALESCE(pi.nombre_web, p.nombre) as nombre_mostrar,
                    wc.nombre as categoria_nombre,
                    wc.id as categoria_id,
                    ps.precio,
                    ps.stock,
                    pi.visible_web
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                LEFT JOIN productos_info_web pi ON p.cod_producto = pi.cod_producto
                LEFT JOIN web_categorias wc ON pi.web_categoria_id = wc.id
                WHERE 1=1";

        $params = [];
        if (!empty($busqueda)) {
            $sql .= " AND (pi.nombre_web LIKE :q1 OR p.nombre LIKE :q2 OR p.cod_producto LIKE :q3)";
            $term = "%$busqueda%";
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
        }
        if (!empty($categoriaId)) {
            $sql .= " AND wc.id = :catId";
            $params[':catId'] = $categoriaId;
        }

        $sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // ====================================================================
    // 6. ESCRITURA (Admin / ERP)
    // ====================================================================
    public function sincronizar($datos) {}
    public function cambiarEstado($id, $estado)
    {
        $sql = "UPDATE productos SET activo = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function crear($datos)
    {
        $sku = 'MAN-' . time();
        $sql = "INSERT INTO productos (cod_producto, nombre, precio, stock, descripcion, imagen, activo) 
                VALUES (:cod, :nombre, :precio, :stock, :desc, :img, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cod'    => $sku,
            ':nombre' => $datos['nombre'],
            ':precio' => $datos['precio'],
            ':stock'  => $datos['stock'] ?? 0,
            ':desc'   => $datos['descripcion'] ?? '',
            ':img'    => $datos['imagen'] ?? ''
        ]);
    }

    public function actualizar($id, $datos)
    {
        $sql = "UPDATE productos SET nombre = :nombre, precio = :precio, stock = :stock, descripcion = :desc";
        $params = [':nombre' => $datos['nombre'], ':precio' => $datos['precio'], ':stock'  => $datos['stock'], ':desc'   => $datos['descripcion'], ':id'     => $id];
        if (!empty($datos['imagen'])) {
            $sql .= ", imagen = :img";
            $params[':img'] = $datos['imagen'];
        }
        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
