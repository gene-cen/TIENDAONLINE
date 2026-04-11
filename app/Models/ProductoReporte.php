<?php
namespace App\Models;

use PDO;

class ProductoReporte {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerMasVendidos($limit = 5) {
        $sucursal_id = (int)($_SESSION['sucursal_activa'] ?? 29);
        $sql = "SELECT p.id, COALESCE(w.nombre_web, p.nombre) as nombre_mostrar, 
                       ps.precio, (ps.stock - COALESCE(ps.stock_reservado, 0)) as stock,
                       SUM(pd.cantidad) as total_vendido
                FROM productos p
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = $sucursal_id
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                LEFT JOIN pedidos_detalle pd ON p.id = pd.producto_id
                WHERE p.activo = 1 AND w.visible_web = 1
                GROUP BY p.id
                ORDER BY total_vendido DESC LIMIT " . (int)$limit;

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}