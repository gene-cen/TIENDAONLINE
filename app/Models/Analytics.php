<?php

namespace App\Models;

use PDO;
use Exception;

/**
 * Modelo Analytics
 * Responsabilidad: Gestión de métricas, tracking y reportes de comportamiento.
 */
class Analytics
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ==========================================================================
    // SECCIÓN 1: CAPTURA DE DATOS (ESCRITURA)
    // ==========================================================================

    public function registrarVisita($url, $userId = null)
    {
        if ($this->esRutaExcluida($url)) return;

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $sql = "INSERT INTO analytics_visitas (session_id, user_id, url, ip_address, user_agent) 
                VALUES (:sid, :uid, :url, :ip, :agent)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sid'   => session_id(),
                ':uid'   => $userId,
                ':url'   => $url,
                ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Analytics Write Error: " . $e->getMessage());
        }
    }

    public function registrarEvento($tipoEvento, $etiqueta)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $sql = "INSERT INTO analytics_eventos (session_id, user_id, tipo_evento, etiqueta) 
                VALUES (:sid, :uid, :tipo, :etiqueta)";

        try {
            $this->db->prepare($sql)->execute([
                ':sid'      => session_id(),
                ':uid'      => $_SESSION['user_id'] ?? null,
                ':tipo'     => $tipoEvento,
                ':etiqueta' => $etiqueta
            ]);
        } catch (Exception $e) {
            error_log("Analytics Event Error: " . $e->getMessage());
        }
    }

    

    private function esRutaExcluida($url)
    {
        return strpos($url, 'admin') === 0 || 
               preg_match('/\.(css|js|jpg|png|ico|svg|woff2)$/i', $url);
    }

    // ==========================================================================
    // SECCIÓN 2: REPORTES Y KPIS (LECTURA)
    // ==========================================================================

    // Nota: He renombrado este a obtenerTrafico para que coincida con tu controlador
    public function obtenerTrafico($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $sql = "SELECT DATE(v.fecha_hora) as etiqueta, COUNT(DISTINCT v.session_id) as total
                FROM analytics_visitas v $join $where
                GROUP BY etiqueta ORDER BY etiqueta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKPIs($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $sql = "SELECT 
                    COUNT(*) as total_visitas, 
                    COUNT(DISTINCT v.session_id) as visitantes_unicos,
                    (SELECT COUNT(*) FROM (
                        SELECT session_id FROM analytics_visitas GROUP BY session_id HAVING COUNT(*) = 1
                    ) AS tmp) as sesiones_rebote
                FROM analytics_visitas v $join $where";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalSesiones = $data['visitantes_unicos'] ?: 1;
        $rebote = ($data['sesiones_rebote'] / $totalSesiones) * 100;

        return [
            'total_visitas'     => $data['total_visitas'] ?: 0,
            'visitantes_unicos' => $data['visitantes_unicos'] ?: 0,
            'rebote'            => round($rebote, 1),
            'duracion_promedio' => $this->obtenerDuracionMedia($where, $params, $join)
        ];
    }

    private function obtenerDuracionMedia($where, $params, $join)
    {
        $sql = "SELECT AVG(duracion) FROM (
                    SELECT TIMESTAMPDIFF(SECOND, MIN(fecha_hora), MAX(fecha_hora)) as duracion
                    FROM analytics_visitas v $join $where
                    GROUP BY v.session_id
                    HAVING duracion > 0
                ) t";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $segundos = $stmt->fetchColumn() ?: 0;
        return round($segundos / 60, 1);
    }

    public function obtenerPaginasPopulares($fechaInicio, $fechaFin, $busqueda = '', $limit = 10)
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $sql = "SELECT v.url, COUNT(*) as visitas, COUNT(DISTINCT v.session_id) as visitantes_unicos
                FROM analytics_visitas v $join $where
                GROUP BY v.url ORDER BY visitas DESC LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🔥 AQUÍ ESTÁ LA FUNCIÓN QUE FALTABA
     */
    public function obtenerClicsPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'e');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON e.user_id = u.id" : "";

        $sql = "SELECT e.etiqueta, COUNT(*) as total
                FROM analytics_eventos e
                $join $where AND e.tipo_evento = 'click'
                GROUP BY e.etiqueta ORDER BY total DESC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVisitasPorComuna($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');

        $sql = "SELECT c.nombre as comuna, COUNT(*) as visitas
                FROM analytics_visitas v
                INNER JOIN usuarios u ON v.user_id = u.id
                INNER JOIN comunas c ON u.comuna_id = c.id
                $where
                GROUP BY c.nombre ORDER BY visitas DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================================
    // HELPERS
    // ==========================================================================

    private function construirWhere($fechaInicio, $fechaFin, $busqueda, $alias = 'v')
    {
        $where = "WHERE DATE($alias.fecha_hora) BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];

        if (!empty($busqueda)) {
            $where .= " AND (u.nombre LIKE :bus OR u.email LIKE :bus OR $alias.session_id LIKE :bus)";
            $params[':bus'] = "%$busqueda%";
        }
        return [$where, $params];
    }
}