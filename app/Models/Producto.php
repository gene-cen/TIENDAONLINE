<?php

namespace App\Models;

use PDO;

class Producto
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Base de consulta unificada para evitar repetir JOINs
     * centraliza la "Magia del Stock" (-12 unidades de buffer)
     */

    // ====================================================================
    // 1. MÉTODOS DE LECTURA PRINCIPAL
    // ====================================================================

    // ... (tus otros métodos como obtenerPorId) ...

    /**
     * Alias de compatibilidad para controladores antiguos (Checkout)
     */
    public function getById($id)
    {
        return $this->obtenerPorId($id);
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private function getBaseQuery($para_web = true)
    {
        $sucursal_id = (int)($_SESSION['sucursal_activa'] ?? 29);
        $sql = "SELECT p.id, p.cod_producto, p.nombre, p.precio_unidad_medida, p.descripcion, p.imagen, p.activo,
                       COALESCE(w.nombre_web, p.nombre) as nombre_mostrar,
                       ps.precio,
                       ((ps.stock - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                       m.nombre as nombre_marca,
                       wc.nombre as nombre_categoria_web,
                       wc.icono as icono_categoria
                FROM productos p 
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                LEFT JOIN marcas m ON w.marca_id = m.id
                LEFT JOIN web_categorias wc ON w.web_categoria_id = wc.id
                WHERE 1=1 ";

        if ($para_web) {
            $sql .= " AND w.visible_web = 1 AND ps.precio > 0 AND p.activo = 1
                      AND ((ps.stock - COALESCE(ps.stock_reservado, 0)) - 12) > 0 ";
        }
        return $sql;
    }

    public function obtenerPorId($id)
    {
        $sql = $this->getBaseQuery(false) . " AND p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPublicosPaginados($limite, $offset, $busqueda = '')
    {
        $sql = $this->getBaseQuery(true);
        if (!empty($busqueda)) $sql .= " AND (w.nombre_web LIKE :q OR p.cod_producto LIKE :q)";
        $sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        if (!empty($busqueda)) $stmt->bindValue(':q', "%$busqueda%", PDO::PARAM_STR);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPublicos($busqueda = '')
    {
        $sucursal_id = (int)($_SESSION['sucursal_activa'] ?? 29);
        $sql = "SELECT COUNT(*) FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                INNER JOIN productos_info_web w ON p.cod_producto = w.cod_producto 
                WHERE p.activo = 1 AND w.visible_web = 1 AND ps.precio > 0 
                AND ((ps.stock - COALESCE(ps.stock_reservado, 0)) - 12) > 0";

        $params = [];
        if (!empty($busqueda)) {
            $sql .= " AND (w.nombre_web LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
