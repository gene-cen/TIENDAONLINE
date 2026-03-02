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

        // Postgres usa los mismos INSERT, pero asegúrate que la tabla exista
        $sql = "INSERT INTO analytics_visitas (session_id, user_id, url, ip_address, user_agent) 
                VALUES (:session_id, :user_id, :url, :ip, :agent)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':session_id' => $sessionId, ':user_id' => $userId, ':url' => $url,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null, ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Si la tabla no existe aún, ignoramos el error para que el sitio no se caiga
            error_log("Analytics Error: " . $e->getMessage());
        }
    }

    public function obtenerTrafico($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
        // CAMBIO: Postgres usa to_char en lugar de DATE_FORMAT
        $formato = ($dias <= 60) ? 'YYYY-MM-DD' : 'YYYY-MM';

        $sql = "SELECT 
                    to_char(v.fecha_hora, '$formato') as etiqueta, 
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

        // Tasa de Rebote
        $sqlRebote = "SELECT 
                        COUNT(DISTINCT v.session_id) as total_sesiones,
                        COUNT(DISTINCT CASE WHEN conteo = 1 THEN v.session_id END) as sesiones_rebote
                      FROM analytics_visitas v
                      $join
                      JOIN (
                          SELECT session_id, COUNT(*) as conteo FROM analytics_visitas GROUP BY session_id
                      ) c ON v.session_id = c.session_id
                      $where";
        
        $stmt = $this->db->prepare($sqlRebote);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $tasaRebote = ($res['total_sesiones'] > 0) ? ($res['sesiones_rebote'] / $res['total_sesiones']) * 100 : 0;

        // CAMBIO: Postgres usa EXTRACT(EPOCH...) para duraciones en segundos
        $sqlDuracion = "SELECT AVG(duracion) FROM (
                            SELECT EXTRACT(EPOCH FROM (MAX(fecha_hora) - MIN(fecha_hora))) as duracion
                            FROM analytics_visitas v
                            $join
                            $where
                            GROUP BY v.session_id
                        ) t WHERE duracion > 0";
        
        $stmt2 = $this->db->prepare($sqlDuracion);
        $stmt2->execute($params);
        $segundos = $stmt2->fetchColumn() ?: 0;

        return ['rebote' => round($tasaRebote, 1), 'duracion_promedio' => round($segundos / 60, 1)];
    }

    private function construirWhere($fechaInicio, $fechaFin, $busqueda, $aliasTabla = 'v')
    {
        // CAMBIO: DATE(fecha_hora) en Postgres funciona igual, pero a veces es mejor usar ::date
        $where = "WHERE $aliasTabla.fecha_hora::date BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        // ... (resto del código igual)
        return [$where, $params];
    }
    
    // ... mantén el resto de los métodos pero recuerda cambiar DATE() por ::date si falla
}