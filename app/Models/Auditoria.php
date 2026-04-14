<?php

namespace App\Models;

use PDO;
use Exception;

class Auditoria {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function registrar($accion, $tabla, $registroId, $anterior = null, $nuevo = null) {
        
        // 1. Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Blindar los datos de entrada (0 = Acción del sistema si no hay admin logueado)
        $usuarioId = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $sql = "INSERT INTO logs_actividades (usuario_id, accion, tabla_afectada, registro_id, valor_anterior, valor_nuevo, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        // 3. Capturar errores para no romper la navegación del usuario
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $usuarioId,
                $accion,
                $tabla,
                $registroId,
                $anterior ? json_encode($anterior) : null,
                $nuevo ? json_encode($nuevo) : null,
                $ip
            ]);
        } catch (Exception $e) {
            // Escribe el error exacto en el log del servidor para que puedas depurarlo
            error_log("Auditoria Write Error: " . $e->getMessage());
        }
    }
}