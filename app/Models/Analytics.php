<?php

namespace App\Models;

use PDO;

class Analytics
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function registrarVisita($url, $userId = null)
    {
        if (strpos($url, 'admin') === 0 || preg_match('/\.(css|js|jpg|png|ico)$/', $url)) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionId = session_id();

        $sql = "INSERT INTO analytics_visitas (session_id, user_id, url, ip_address, user_agent) 
                VALUES (:session_id, :user_id, :url, :ip, :agent)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':session_id' => $sessionId,
                ':user_id' => $userId,
                ':url' => $url,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Analytics Error: " . $e->getMessage());
        }
    }

    public function obtenerTrafico($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
        $formato = ($dias <= 60) ? '%Y-%m-%d' : '%Y-%m';

        $sql = "SELECT 
                    DATE_FORMAT(v.fecha_hora, '$formato') as etiqueta, 
                    COUNT(DISTINCT v.session_id) as total
                FROM analytics_visitas v
                $join
                $where
                GROUP BY etiqueta
                ORDER BY etiqueta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKPIs($fechaInicio = null, $fechaFin = null, $busqueda = '')
    {
        $fechaInicio = $fechaInicio ?? date('Y-m-01');
        $fechaFin = $fechaFin ?? date('Y-m-d');

        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        // 1. Totales (Visitas y Visitantes)
        $sqlBasico = "SELECT COUNT(*) as total_visitas, COUNT(DISTINCT v.session_id) as visitantes_unicos 
                      FROM analytics_visitas v $join $where";
        $stmt = $this->db->prepare($sqlBasico);
        $stmt->execute($params);
        $basicos = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Tasa de Rebote
        $sqlRebote = "SELECT 
                        COUNT(DISTINCT v.session_id) as total_sesiones,
                        COUNT(DISTINCT CASE WHEN c.conteo = 1 THEN v.session_id END) as sesiones_rebote
                      FROM analytics_visitas v
                      $join
                      JOIN (SELECT session_id, COUNT(*) as conteo FROM analytics_visitas GROUP BY session_id) c 
                      ON v.session_id = c.session_id
                      $where";

        $stmt = $this->db->prepare($sqlRebote);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $tasaRebote = ($res['total_sesiones'] > 0) ? ($res['sesiones_rebote'] / $res['total_sesiones']) * 100 : 0;

        // 3. Duración promedio
        $sqlDuracion = "SELECT AVG(duracion) FROM (
                            SELECT TIMESTAMPDIFF(SECOND, MIN(fecha_hora), MAX(fecha_hora)) as duracion
                            FROM analytics_visitas v $join $where
                            GROUP BY v.session_id
                        ) t WHERE duracion > 0";

        $stmt2 = $this->db->prepare($sqlDuracion);
        $stmt2->execute($params);
        $segundos = $stmt2->fetchColumn() ?: 0;

        return [
            'total_visitas' => $basicos['total_visitas'] ?? 0,
            'visitantes_unicos' => $basicos['visitantes_unicos'] ?? 0,
            'rebote' => round($tasaRebote, 1),
            'duracion_promedio' => round($segundos / 60, 1)
        ];
    }

    public function obtenerPaginasPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $sql = "SELECT 
                    v.url, 
                    COUNT(*) as visitas,
                    COUNT(DISTINCT v.session_id) as visitantes_unicos
                FROM analytics_visitas v
                $join $where
                GROUP BY v.url
                ORDER BY visitas DESC
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerClicsPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        // Cambiado a tabla analytics_eventos según tu SQL
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'e');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON e.user_id = u.id" : "";

        $sql = "SELECT 
                    e.etiqueta, 
                    COUNT(*) as total
                FROM analytics_eventos e
                $join $where AND e.tipo_evento = 'click'
                GROUP BY e.etiqueta
                ORDER BY total DESC
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerVisitasPorComuna($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');

        $sql = "SELECT 
                c.nombre as comuna, 
                COUNT(*) as visitas, -- Cambiado 'total' por 'visitas' para que coincida con tu JS original
                COUNT(*) as total    -- Mantenemos 'total' por compatibilidad
            FROM analytics_visitas v
            INNER JOIN usuarios u ON v.user_id = u.id
            INNER JOIN comunas c ON u.comuna_id = c.id
            $where
            GROUP BY c.nombre
            ORDER BY visitas DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private function construirWhere($fechaInicio, $fechaFin, $busqueda, $aliasTabla = 'v')
    {
        $where = "WHERE DATE($aliasTabla.fecha_hora) BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];

        if (!empty($busqueda)) {
            $where .= " AND (u.nombre LIKE :bus OR u.email LIKE :bus OR $aliasTabla.session_id LIKE :bus)";
            $params[':bus'] = "%$busqueda%";
        }
        return [$where, $params];
    }

    // Métodos para evitar errores si las columnas no existen aún
    public function obtenerDispositivos($fI, $fF, $b = '')
    {
        return [];
    }
    public function obtenerFuentes($fI, $fF, $b = '')
    {
        return [];
    }
}
