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

    // --- MÉTODOS BASE (Registro) ---
    public function registrarVisita($url, $userId = null)
    {
        if (strpos($url, 'admin') === 0 || preg_match('/\.(css|js|jpg|png|ico)$/', $url)) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionId = session_id();

        $sql = "INSERT INTO analytics_visitas (session_id, user_id, url, ip_address, user_agent) 
                VALUES (:session_id, :user_id, :url, :ip, :agent)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':session_id' => $sessionId, ':user_id' => $userId, ':url' => $url,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null, ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    public function registrarEvento($tipo, $etiqueta)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;

        $sql = "INSERT INTO analytics_eventos (session_id, user_id, tipo_evento, etiqueta) 
                VALUES (:session_id, :user_id, :tipo, :etiqueta)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':session_id' => $sessionId, ':user_id' => $userId, ':tipo' => $tipo, ':etiqueta' => $etiqueta
        ]);
    }

    // =========================================================
    // 🔍 MÉTODOS DE REPORTE (Dashboard)
    // =========================================================

   private function construirWhere($fechaInicio, $fechaFin, $busqueda, $aliasTabla = 'v')
    {
        $where = "WHERE DATE($aliasTabla.fecha_hora) BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];

        if (!empty($busqueda)) {
            // Limpiamos input usuario
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);

            $where .= " AND (
                        REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q_rut 
                        OR u.nombre LIKE :q_gen 
                        OR u.email LIKE :q_gen
                      )";
            
            $params[':q_rut'] = "%$busquedaLimpia%";
            $params[':q_gen'] = "%$busqueda%";
        }
        return [$where, $params];
    }

    // 1. Tráfico (EL QUE FALTABA)
    public function obtenerTrafico($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        // Si el rango es pequeño (<= 60 días), mostramos por día. Si es grande, por mes.
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

    // 2. KPIs
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

        // Duración
        $sqlDuracion = "SELECT AVG(duracion) FROM (
                            SELECT TIMESTAMPDIFF(SECOND, MIN(fecha_hora), MAX(fecha_hora)) as duracion
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

    // 3. Paginas
    public function obtenerPaginasPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.user_id = u.id" : "";

        $sql = "SELECT v.url, COUNT(*) as visitas FROM analytics_visitas v
                $join $where
                GROUP BY v.url ORDER BY visitas DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Clics
    public function obtenerClicsPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        $where = "WHERE DATE(e.fecha_hora) BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        $join = "";
        if (!empty($busqueda)) {
            $join = "LEFT JOIN usuarios u ON e.user_id = u.id";
            $where .= " AND (u.rut LIKE :q OR u.nombre LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }

        $sql = "SELECT e.etiqueta, COUNT(*) as total FROM analytics_eventos e
                $join $where AND e.tipo_evento IN ('click_general', 'add_to_cart')
                GROUP BY e.etiqueta ORDER BY total DESC LIMIT 8";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Mapa
    public function obtenerVisitasPorComuna($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v');
        $sql = "SELECT c.nombre as comuna, COUNT(DISTINCT v.session_id) as visitas
                FROM analytics_visitas v
                INNER JOIN usuarios u ON v.user_id = u.id
                INNER JOIN direcciones_usuarios du ON u.id = du.usuario_id
                INNER JOIN comunas c ON du.comuna_id = c.id
                $where
                GROUP BY c.nombre ORDER BY visitas DESC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Método legacy para compatibilidad
    public function obtenerSesionesPorMes() { return $this->obtenerTrafico(date('Y-01-01'), date('Y-12-31')); }
}