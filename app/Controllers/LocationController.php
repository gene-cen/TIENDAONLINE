<?php

namespace App\Controllers;

use PDO;

class LocationController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Actualiza la sesión manualmente desde el modal
     */
    public function actualizar()
    {
        $comunaId = $_POST['comuna_id'] ?? null;
        $nombre = $_POST['nombre'] ?? '';

        if ($comunaId) {
            // Buscamos la sucursal asociada a esa comuna en la BD
            $stmt = $this->db->prepare("SELECT sucursal_id FROM comunas WHERE id = ?");
            $stmt->execute([$comunaId]);
            $sucursalId = $stmt->fetchColumn();

            $_SESSION['comuna_id'] = $comunaId;
            $_SESSION['comuna_nombre'] = $nombre;
            $_SESSION['sucursal_activa'] = $sucursalId ? $sucursalId : 29; // Default Prat si no hay asignada

            // ========================================================
            // 🚨 TRAMPA PARA LISTILLOS: VALIDADOR DE CARRITO 🚨
            // ========================================================
            $carrito = new \App\Controllers\CarritoController($this->db);
            $carrito->validarCambioSucursal($_SESSION['sucursal_activa']);
            // ========================================================

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    /**
     * Detecta la comuna más cercana mediante coordenadas GPS
     */
    public function detectar()
    {
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        if ($lat && $lng) {
            // Buscamos la más cercana entre las que tienen despacho
            $sql = "SELECT id, nombre, sucursal_id FROM comunas 
                WHERE sucursal_id IS NOT NULL
                ORDER BY (ABS(latitud - :lat) + ABS(longitud - :lng)) ASC LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':lat' => $lat, ':lng' => $lng]);
            $comuna = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($comuna) {
                echo json_encode(['success' => true, 'comuna_id' => $comuna['id'], 'comuna_nombre' => $comuna['nombre']]);
                exit;
            }
        }

        // Si no hay GPS o falla, devolvemos La Calera (ID 63) como base
        echo json_encode(['success' => true, 'comuna_id' => 63, 'comuna_nombre' => 'La Calera']);
        exit;
    }
    public function actualizar_por_nombre()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $nombre = trim($_POST['nombre'] ?? '');
        // NUEVO: Verificamos si el JS nos está mandando la confirmación de forzar el cambio
        $confirmado = isset($_POST['confirmado']) ? (int)$_POST['confirmado'] : 0;

        $stmt = $this->db->prepare("SELECT id, nombre, sucursal_id FROM comunas WHERE nombre LIKE ? LIMIT 1");
        $stmt->execute([$nombre]);
        $comuna = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($comuna) {
            $nueva_sucursal_id = $comuna['sucursal_id'];

            require_once __DIR__ . '/CarritoController.php';
            $carrito = new \App\Controllers\CarritoController($this->db);

            // 🚨 PASO 1: SIMULACRO (Si no está confirmado)
            if ($confirmado === 0) {
                $reporte = $carrito->validarCambioSucursal($nueva_sucursal_id, false); // false = modo simulacro

                if ($reporte['cambios']) {
                    // Hay conflictos, detenemos todo y pedimos confirmación
                    echo json_encode([
                        'status' => 'requiere_confirmacion',
                        'titulo' => '¡Atención con tu Carrito!',
                        'html' => 'Si cambias el despacho a <b>' . $comuna['nombre'] . '</b>, se ajustará tu pedido:<br><ul class="text-start mt-3 mb-0" style="font-size: 0.9rem;">' . implode('', $reporte['mensajes']) . '</ul><br><b>¿Deseas continuar?</b>'
                    ]);
                    exit;
                }
            }

            // 🚨 PASO 2: EJECUCIÓN (Si confirmó o si no hubo conflictos)
            $_SESSION['comuna_id'] = $comuna['id'];
            $_SESSION['comuna_nombre'] = $comuna['nombre'];
            $_SESSION['sucursal_activa'] = $nueva_sucursal_id;

            // Ahora sí, ejecutamos la limpieza real
            $carrito->validarCambioSucursal($nueva_sucursal_id, true); // true = limpiar carro

            echo json_encode(['status' => 'success', 'sucursal' => $nueva_sucursal_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No encontramos la comuna']);
        }
        exit;
    }
}
