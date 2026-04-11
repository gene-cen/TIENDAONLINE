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

    $nombreOriginal = trim($_POST['nombre'] ?? '');
    $confirmado = isset($_POST['confirmado']) ? (int)$_POST['confirmado'] : 0;
    
    // Atrapamos la sucursal exacta que el usuario seleccionó en el Modal (10 o 29)
    $sucursal_js = isset($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null;

    // 🔥 TRUCO DE MAPEADO: 
    // Como Peñablanca es un sector de Villa Alemana y no suele estar en la tabla 'comunas',
    // lo traducimos internamente para que la consulta SQL no falle.
    $nombreParaBuscar = ($nombreOriginal === 'Peñablanca') ? 'Villa Alemana' : $nombreOriginal;

    $stmt = $this->db->prepare("SELECT id, nombre, sucursal_id FROM comunas WHERE nombre LIKE ? LIMIT 1");
    $stmt->execute([$nombreParaBuscar]);
    $comuna = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($comuna) {
        // Determinamos la sucursal: Prioridad al JS, sino la de la BD.
        $nueva_sucursal_id = $sucursal_js ?: (int)$comuna['sucursal_id'];

        require_once __DIR__ . '/CarritoController.php';
        $carrito = new \App\Controllers\CarritoController($this->db);

        // 🚨 PASO 1: SIMULACRO (Validación de Stock/Precios al cambiar de sucursal)
        if ($confirmado === 0) {
            $reporte = $carrito->validarCambioSucursal($nueva_sucursal_id, false); // false = simulacro

            if (!empty($reporte['cambios'])) {
                $nombreSucursalAlerta = ($nueva_sucursal_id == 29) ? 'La Calera' : 'Villa Alemana';
                
                echo json_encode([
                    'status' => 'requiere_confirmacion',
                    'titulo' => '¡Atención con tu Carrito!',
                    'html' => 'Si cambias a la sucursal de <b>' . $nombreSucursalAlerta . '</b>, se ajustará tu pedido:<br><ul class="text-start mt-3 mb-0" style="font-size: 0.9rem;">' . implode('', $reporte['mensajes']) . '</ul><br><b>¿Deseas continuar?</b>'
                ]);
                exit;
            }
        }

        // 🚨 PASO 2: EJECUCIÓN (Confirmado o sin conflictos)
        
        // Guardamos en sesión. 
        // Nota: Guardamos el ID de la comuna encontrada (Villa Alemana si era Peñablanca)
        // pero en 'comuna_nombre' dejamos el nombre original para que el Navbar diga "Peñablanca".
        $_SESSION['comuna_id'] = $comuna['id'];
        $_SESSION['comuna_nombre'] = $nombreOriginal; 
        
        // El PASO CRÍTICO: Cambiamos la sucursal activa
        $_SESSION['sucursal_activa'] = $nueva_sucursal_id;

        // Ejecutamos la limpieza real del carrito en la nueva sucursal
        $carrito->validarCambioSucursal($nueva_sucursal_id, true); 

        echo json_encode([
            'status' => 'success', 
            'sucursal' => $nueva_sucursal_id,
            'comuna_final' => $nombreOriginal
        ]);

    } else {
        // Si no se encuentra ni por el nombre original ni por el mapeado
        echo json_encode([
            'status' => 'error', 
            'message' => "La zona '$nombreOriginal' no está disponible para despacho en este momento."
        ]);
    }
    exit;
}
}
