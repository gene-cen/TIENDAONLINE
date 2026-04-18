<?php

namespace App\Models;

use PDO;

/**
 * Modelo ProductoAdmin
 * Responsabilidad: Gestión avanzada de inventario, filtros de stock y ERP.
 * Este modelo es exclusivo para el panel administrativo.
 */
class ProductoAdmin
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

 
    public function obtenerPaginadosAdmin($limit, $offset, $filtros = [])
    {
        $params = [];
        $sucursal_id = (int)($filtros['sucursal'] ?? 29);
        $params[':sucursal'] = $sucursal_id;

        $sql = "SELECT p.id, 
                   p.cod_producto, 
                   COALESCE(piw.nombre_web, p.nombre) as nombre_web, 
                   p.nombre, 
                   p.imagen, 
                   p.activo, 
                   wc.nombre as categoria_nombre,
                   COALESCE(ps.precio, 0) as precio, 
                   COALESCE(ps.stock, 0) as stock

            FROM productos p
            LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
            LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
            LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = :sucursal 
            WHERE 1=1 ";

        // 1. Búsqueda
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (p.nombre LIKE :busq1 OR piw.nombre_web LIKE :busq2 OR p.cod_producto LIKE :busq3)";
            $params[':busq1'] = "%{$filtros['busqueda']}%";
            $params[':busq2'] = "%{$filtros['busqueda']}%";
            $params[':busq3'] = "%{$filtros['busqueda']}%";
        }

        // 2. Categoría y Marca
        if (!empty($filtros['categoria'])) {
            $sql .= " AND p.categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['marca_id'])) {
            $sql .= " AND piw.marca_id = :marcaId";
            $params[':marcaId'] = $filtros['marca_id'];
        }

        // 3. Filtro Stock Cantidad
        if (!empty($filtros['filtro_stock'])) {
            $calcStock = "(ps.stock - COALESCE(ps.stock_reservado, 0))";
            switch ($filtros['filtro_stock']) {
                case 'agotados':
                    $sql .= " AND $calcStock <= 0";
                    break;
                case '0_50':
                    $sql .= " AND $calcStock > 0 AND $calcStock <= 50";
                    break;
                case '51_100':
                    $sql .= " AND $calcStock > 50 AND $calcStock <= 100";
                    break;
                case '100_mas':
                    $sql .= " AND $calcStock > 100";
                    break;
            }
        }


        // 5. Ordenamiento

        switch ($filtros['orden'] ?? '') {
            case 'asc':
                $sql .= " ORDER BY stock ASC, p.id DESC";
                break;
            case 'desc':
                $sql .= " ORDER BY stock DESC, p.id DESC";
                break;
            case 'precio_asc':
                $sql .= " ORDER BY ps.precio ASC, p.id DESC";
                break;
            case 'precio_desc':
                $sql .= " ORDER BY ps.precio DESC, p.id DESC";
                break;
            case 'nombre_asc':
                $sql .= " ORDER BY COALESCE(piw.nombre_web, p.nombre) ASC";
                break;

            // 🔥 LOS NUEVOS ORDENAMIENTOS POR STOCK VALORIZADO
            case 'valor_desc':
                $sql .= " ORDER BY (COALESCE(ps.precio, 0) * COALESCE(ps.stock, 0)) DESC, p.id DESC";
                break;
            case 'valor_asc':
                $sql .= " ORDER BY (COALESCE(ps.precio, 0) * COALESCE(ps.stock, 0)) ASC, p.id DESC";
                break;

            default:
                $sql .= " ORDER BY p.id DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Obtiene la suma total del dinero inmovilizado en bodega (Stock Valorizado)
     */
    public function obtenerTotalValorizadoSucursal($sucursal_id)
    {
        $sql = "SELECT SUM(COALESCE(ps.precio, 0) * COALESCE(ps.stock, 0)) 
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto 
                WHERE ps.sucursal_id = :sucursal AND p.activo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sucursal' => (int)$sucursal_id]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function contarTotalAdmin($filtros = [])
    {
        $params = [];
        $sucursal_id = (int)($filtros['sucursal'] ?? 29);
        $params[':sucursal'] = $sucursal_id;

        $sql = "SELECT COUNT(*) 
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = :sucursal
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto
                WHERE 1=1";

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (p.nombre LIKE :busq1 OR piw.nombre_web LIKE :busq2 OR p.cod_producto LIKE :busq3)";
            $params[':busq1'] = "%{$filtros['busqueda']}%";
            $params[':busq2'] = "%{$filtros['busqueda']}%";
            $params[':busq3'] = "%{$filtros['busqueda']}%";
        }

        if (!empty($filtros['categoria'])) {
            $sql .= " AND p.categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['marca_id'])) {
            $sql .= " AND piw.marca_id = :marcaId";
            $params[':marcaId'] = $filtros['marca_id'];
        }

        if (!empty($filtros['filtro_stock'])) {
            $calcStock = "(ps.stock - COALESCE(ps.stock_reservado, 0))";
            switch ($filtros['filtro_stock']) {
                case 'agotados':
                    $sql .= " AND $calcStock <= 0";
                    break;
                case '0_50':
                    $sql .= " AND $calcStock > 0 AND $calcStock <= 50";
                    break;
                case '51_100':
                    $sql .= " AND $calcStock > 50 AND $calcStock <= 100";
                    break;
                case '100_mas':
                    $sql .= " AND $calcStock > 100";
                    break;
            }
        }



        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    public function obtenerStockCritico($sucursalId = null)
    {
        // Usamos la constante del Modelo Producto para ser consistentes
        $buffer = \App\Models\Producto::BUFFER_SEGURIDAD_DEFAULT;

        if (!empty($sucursalId)) {
            // Para una sucursal específica
            $sql = "SELECT p.nombre, (ps.stock - $buffer) as stock, p.imagen 
                    FROM productos_sucursales ps 
                    JOIN productos p ON ps.cod_producto = p.cod_producto 
                    WHERE ps.sucursal_id = :sucursal 
                    AND (ps.stock - $buffer) < 10 
                    ORDER BY ps.stock ASC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':sucursal' => $sucursalId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            // Vista global (Superadmin)
            $sql = "SELECT p.nombre, (SUM(ps.stock) - $buffer) as stock, p.imagen 
                    FROM productos_sucursales ps 
                    JOIN productos p ON ps.cod_producto = p.cod_producto 
                    GROUP BY p.id 
                    HAVING stock < 10 
                    ORDER BY stock ASC LIMIT 5";
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * Acciones de escritura
     */
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("UPDATE productos SET activo = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function obtenerCategoriasUnicas()
    {
        return $this->db->query("SELECT DISTINCT categoria as nombre FROM productos WHERE categoria IS NOT NULL ORDER BY categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
