<?php
namespace App\Models;

use PDO;

class ReporteModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // 1. KPI FINANCIERO (Ventas vs Meta)
    public function obtenerResumenFinanciero($mes = null)
    {
        if (!$mes) $mes = date('Y-m');

        // Ventas Reales (Solo pagados/despachados)
        $sqlReal = "SELECT 
                        IFNULL(SUM(total), 0) as venta_actual, 
                        COUNT(id) as pedidos_actuales,
                        IFNULL(AVG(total), 0) as ticket_promedio
                    FROM pedidos 
                    WHERE DATE_FORMAT(fecha, '%Y-%m') = :mes 
                    AND estado != 'rechazado'";
        
        $stmt = $this->db->prepare($sqlReal);
        $stmt->execute([':mes' => $mes]);
        $real = $stmt->fetch(PDO::FETCH_ASSOC);

        // Meta
        $sqlMeta = "SELECT meta_venta, meta_pedidos FROM admin_metas WHERE mes = :mes";
        $stmt2 = $this->db->prepare($sqlMeta);
        $stmt2->execute([':mes' => $mes]);
        $meta = $stmt2->fetch(PDO::FETCH_ASSOC);

        // Cálculos de cumplimiento
        $metaVenta = $meta['meta_venta'] ?? 1; // Evitar div por 0
        $porcentajeCumplimiento = ($real['venta_actual'] / $metaVenta) * 100;

        return [
            'venta_real' => (int)$real['venta_actual'],
            'pedidos_real' => (int)$real['pedidos_actuales'],
            'ticket_promedio' => (int)$real['ticket_promedio'],
            'meta_venta' => (int)$metaVenta,
            'cumplimiento' => round($porcentajeCumplimiento, 1)
        ];
    }

    // 2. EMBUDO DE CONVERSIÓN (Funnel)
    public function obtenerEmbudo($mes = null)
    {
        if (!$mes) $mes = date('Y-m');

        // A. Visitas Totales
        $sqlVisitas = "SELECT COUNT(DISTINCT session_id) FROM analytics_visitas 
                       WHERE DATE_FORMAT(fecha_hora, '%Y-%m') = :mes";
        $visitas = $this->db->prepare($sqlVisitas);
        $visitas->execute([':mes' => $mes]);
        $totalVisitas = $visitas->fetchColumn();

        // B. Intención de Compra (Clic en Agregar al Carro)
        // OJO: Debes asegurarte que el JS del front registre el evento 'add_to_cart'
        $sqlIntencion = "SELECT COUNT(DISTINCT session_id) FROM analytics_eventos 
                         WHERE tipo_evento = 'add_to_cart' 
                         AND DATE_FORMAT(fecha_hora, '%Y-%m') = :mes";
        $intencion = $this->db->prepare($sqlIntencion);
        $intencion->execute([':mes' => $mes]);
        $totalIntencion = $intencion->fetchColumn();

        // C. Compras Reales
        $sqlCompras = "SELECT COUNT(id) FROM pedidos 
                       WHERE DATE_FORMAT(fecha, '%Y-%m') = :mes AND estado != 'rechazado'";
        $compras = $this->db->prepare($sqlCompras);
        $compras->execute([':mes' => $mes]);
        $totalCompras = $compras->fetchColumn();

        // Tasa de Conversión Global
        $tasaConversion = $totalVisitas > 0 ? ($totalCompras / $totalVisitas) * 100 : 0;

        return [
            'visitas' => $totalVisitas,
            'carrito' => $totalIntencion, // Usuarios que agregaron algo
            'compras' => $totalCompras,
            'tasa_conversion' => round($tasaConversion, 2)
        ];
    }

    // 3. TOP VENTAS (Productos y Marcas)
    public function obtenerTopVentas($mes = null)
    {
        if (!$mes) $mes = date('Y-m');

        // Top Productos
        $sqlProd = "SELECT p.nombre_web as nombre, SUM(d.cantidad) as unidades, SUM(d.precio * d.cantidad) as total_venta
                    FROM detalle_pedidos d
                    JOIN pedidos ped ON d.pedido_id = ped.id
                    JOIN productos p ON d.producto_id = p.id
                    WHERE DATE_FORMAT(ped.fecha, '%Y-%m') = :mes AND ped.estado != 'rechazado'
                    GROUP BY p.id
                    ORDER BY unidades DESC LIMIT 5";
        
        // Top Marcas
        $sqlMarca = "SELECT p.marca, SUM(d.precio * d.cantidad) as total_venta
                     FROM detalle_pedidos d
                     JOIN pedidos ped ON d.pedido_id = ped.id
                     JOIN productos p ON d.producto_id = p.id
                     WHERE DATE_FORMAT(ped.fecha, '%Y-%m') = :mes AND ped.estado != 'rechazado'
                     GROUP BY p.marca
                     ORDER BY total_venta DESC LIMIT 5";

        $stmtProd = $this->db->prepare($sqlProd);
        $stmtProd->execute([':mes' => $mes]);
        
        $stmtMarca = $this->db->prepare($sqlMarca);
        $stmtMarca->execute([':mes' => $mes]);

        return [
            'productos' => $stmtProd->fetchAll(PDO::FETCH_ASSOC),
            'marcas' => $stmtMarca->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // 4. DISPOSITIVOS (Móvil vs Escritorio)
    public function obtenerDispositivos($mes = null)
    {
        if (!$mes) $mes = date('Y-m');

        $sql = "SELECT user_agent FROM analytics_visitas WHERE DATE_FORMAT(fecha_hora, '%Y-%m') = :mes";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mes' => $mes]);
        
        $movil = 0;
        $desktop = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (preg_match('/(android|iphone|ipad|mobile)/i', $row['user_agent'])) {
                $movil++;
            } else {
                $desktop++;
            }
        }

        return ['movil' => $movil, 'desktop' => $desktop];
    }
}