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

    public function registrarVisita($usuario_id, $comuna_id, $url, $ip, $agent) {
        
        $pais = 'Desconocido';
        $ciudad = 'Desconocido';
        $lat = null;
        $lng = null;

        // 🌟 PRIORIDAD 1: Revisar si ya tenemos el GPS exacto en la sesión
        if (isset($_SESSION['gps_lat']) && isset($_SESSION['gps_lng'])) {
            $pais = $_SESSION['gps_pais'] ?? 'Chile';
            $ciudad = $_SESSION['gps_ciudad'] ?? 'Coordenadas GPS';
            $lat = $_SESSION['gps_lat'];
            $lng = $_SESSION['gps_lng'];
        } 
        // 🌟 PRIORIDAD 2 (Fallback): Usar la IP si no hay GPS autorizado aún
        else if (!empty($ip) && $ip !== '127.0.0.1' && $ip !== '::1') {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://get.geojs.io/v1/ip/geo/{$ip}.json");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $respuesta = curl_exec($ch);
                curl_close($ch);

                if ($respuesta) {
                    $geoData = json_decode($respuesta);
                    if ($geoData && isset($geoData->latitude)) {
                        $pais = $geoData->country;
                        $ciudad = $geoData->city;
                        $lat = $geoData->latitude;
                        $lng = $geoData->longitude;
                    }
                }
            } catch (Exception $e) {
                // Silencioso
            }
        }

        $sql = "INSERT INTO analitica_visitas 
                (usuario_id, comuna_id, url_visitada, ip_address, user_agent, fecha, pais, ciudad, lat_real, lng_real) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $exito = $stmt->execute([
                $usuario_id, $comuna_id, $url, $ip, $agent, $pais, $ciudad, $lat, $lng
            ]);
            
            if ($exito) {
                $_SESSION['ultima_visita_id'] = $this->db->lastInsertId();
            }
            return $exito;
        } catch (Exception $e) {
            error_log("Error guardando visita Analytics: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarGPSVisita($ip, $lat, $lng) {
        $ciudad = 'Coordenadas GPS';
        $pais = 'Desconocido';

        // 1. Preguntarle a OpenStreetMap
        try {
            $ch = curl_init();
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&addressdetails=1";
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Cencocal-Analytics/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $respuesta = curl_exec($ch);
            curl_close($ch);

            if ($respuesta) {
                $data = json_decode($respuesta, true);
                if (isset($data['address'])) {
                    $ciudad = $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? $data['address']['county'] ?? 'Coordenadas GPS';
                    $pais = $data['address']['country'] ?? 'Chile';
                }
            }
        } catch (Exception $e) { }

        // 2. Actualizamos la Base de Datos con los nombres reales
        try {
            $sql = "UPDATE analitica_visitas 
                    SET lat_real = ?, lng_real = ?, ciudad = ?, pais = ? 
                    WHERE ip_address = ? AND DATE(fecha) = CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lat, $lng, $ciudad, $pais, $ip]);
            
            return ['ciudad' => $ciudad, 'pais' => $pais];
        } catch (Exception $e) {
            return false;
        }
    }

    // ==========================================================================
    // SECCIÓN 2: REPORTES Y KPIS (LECTURA PARA EL DASHBOARD)
    // ==========================================================================

    public function obtenerTrafico($desde, $hasta, $comuna = '')
    {
        $sql = "SELECT DATE(v.fecha) as etiqueta, COUNT(DISTINCT v.ip_address) as total 
                FROM analitica_visitas v
                WHERE DATE(v.fecha) BETWEEN :desde AND :hasta ";
        
        $params = [':desde' => $desde, ':hasta' => $hasta];

        if (!empty($comuna)) {
            $sql .= " AND v.comuna_id = :comuna ";
            $params[':comuna'] = $comuna;
        }

        $sql .= " GROUP BY DATE(v.fecha) ORDER BY DATE(v.fecha) ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKPIs($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'v', 'visitas');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON v.usuario_id = u.id" : "";

        // 1. Visitantes Totales y Únicos
        $sqlBase = "SELECT 
                    COUNT(*) as total_visitas, 
                    COUNT(DISTINCT v.ip_address) as visitantes_unicos
                FROM analitica_visitas v $join $where";
        
        $stmt = $this->db->prepare($sqlBase);
        $stmt->execute($params);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Tasa de Rebote
        $sqlRebote = "SELECT COUNT(*) FROM (
                        SELECT v.ip_address FROM analitica_visitas v $join $where 
                        GROUP BY v.ip_address, DATE(v.fecha) HAVING COUNT(*) = 1
                      ) as tmp";
        $stmtRebote = $this->db->prepare($sqlRebote);
        $stmtRebote->execute($params);
        $reboteCount = $stmtRebote->fetchColumn() ?: 0;

        $totalSesiones = $data['visitantes_unicos'] ?: 1;
        $rebote = ($reboteCount / $totalSesiones) * 100;

        return [
            'total_visitas'     => $data['total_visitas'] ?: 0,
            'visitantes_unicos' => $data['visitantes_unicos'] ?: 0,
            'rebote'            => round(min($rebote, 100), 1),
            'duracion_promedio' => $this->obtenerDuracionMedia($where, $params, $join)
        ];
    }

    private function obtenerDuracionMedia($where, $params, $join)
    {
        $sql = "SELECT AVG(duracion) FROM (
                    SELECT TIMESTAMPDIFF(MINUTE, MIN(v.fecha), MAX(v.fecha)) as duracion
                    FROM analitica_visitas v $join $where
                    GROUP BY v.ip_address, DATE(v.fecha)
                    HAVING duracion > 0
                ) t";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $minutos = $stmt->fetchColumn() ?: 0;
        
        return round($minutos, 1);
    }

    public function obtenerPaginasPopulares($desde, $hasta, $comuna = '')
    {
        $sql = "SELECT v.url_visitada as url, COUNT(*) as visitas 
                FROM analitica_visitas v
                WHERE DATE(v.fecha) BETWEEN :desde AND :hasta ";
        
        $params = [':desde' => $desde, ':hasta' => $hasta];

        if (!empty($comuna)) {
            $sql .= " AND v.comuna_id = :comuna ";
            $params[':comuna'] = $comuna;
        }

        $sql .= " GROUP BY v.url_visitada ORDER BY visitas DESC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerClicsPopulares($fechaInicio, $fechaFin, $busqueda = '')
    {
        list($where, $params) = $this->construirWhere($fechaInicio, $fechaFin, $busqueda, 'e', 'eventos');
        $join = !empty($busqueda) ? "LEFT JOIN usuarios u ON e.user_id = u.id" : "";

        $sql = "SELECT e.etiqueta, COUNT(*) as total
                FROM analytics_eventos e
                $join $where AND e.tipo_evento = 'click'
                GROUP BY e.etiqueta ORDER BY total DESC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVisitasMapaGlobal($desde, $hasta)
    {
        $sql = "SELECT pais, ciudad, lat_real as lat, lng_real as lng, COUNT(id) as visitas
                FROM analitica_visitas
                WHERE DATE(fecha) BETWEEN ? AND ? 
                AND lat_real IS NOT NULL AND lng_real IS NOT NULL
                GROUP BY pais, ciudad, lat_real, lng_real
                ORDER BY visitas DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================================
    // HELPERS
    // ==========================================================================

    private function construirWhere($fechaInicio, $fechaFin, $busqueda, $alias = 'v', $tabla = 'visitas')
    {
        $colFecha = ($tabla === 'eventos') ? 'fecha_hora' : 'fecha';
        $colSession = ($tabla === 'eventos') ? 'session_id' : 'ip_address';

        $where = "WHERE DATE($alias.$colFecha) BETWEEN :inicio AND :fin";
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];

        if (!empty($busqueda)) {
            $where .= " AND (u.nombre LIKE :bus OR u.email LIKE :bus OR $alias.$colSession LIKE :bus)";
            $params[':bus'] = "%$busqueda%";
        }
        return [$where, $params];
    }
}