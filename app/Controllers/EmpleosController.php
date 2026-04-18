<?php

namespace App\Controllers;

use PDO;
use Exception;

/**
 * ARCHIVO: EmpleosController.php
 * Descripción: Maneja el ciclo de vida de las postulaciones laborales y el dashboard de RRHH.
 */
class EmpleosController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================
    // 🛡️ SEGURIDAD INTERNA
    // =========================================================
    private function verificarPermisoRRHH()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1 = SuperAdmin, 2 = Admin Sucursal, 4 = RRHH
        if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2, 4])) {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    // =========================================================
    // 🌐 RUTAS PÚBLICAS (PARA POSTULANTES)
    // =========================================================

    public function index()
    {
        ob_start();
        require_once __DIR__ . '/../../views/empleos/landing.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function postulante()
    {
        // Traemos comunas de interés
        $nombresComunas = ['La Calera', 'La Cruz', 'Quillota', 'Nogales', 'Hijuelas'];
        $inQuery = implode(',', array_fill(0, count($nombresComunas), '?'));
        $stmt = $this->db->prepare("SELECT id, nombre FROM comunas WHERE nombre IN ($inQuery) ORDER BY nombre ASC");
        $stmt->execute($nombresComunas);
        $comunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🔥 Traemos las sucursales para el formulario público
        $sucursales = $this->db->query("SELECT id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        require_once __DIR__ . '/../../views/empleos/formulario.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }
public function getCargosAjax()
    {
        header('Content-Type: application/json');
        
        // Verificamos si nos envían sucursal_id (desde RRHH) o el nombre antiguo (si quedó algún rastro)
        $sucursal_id = $_GET['sucursal_id'] ?? null;
        
        if (!$sucursal_id) {
             echo json_encode([]);
             exit;
        }

        // Buscamos el nombre de la sucursal para mapearla al tipo de cargo
        $stmtSuc = $this->db->prepare("SELECT nombre FROM sucursales WHERE id = ?");
        $stmtSuc->execute([(int)$sucursal_id]);
        $nombreSucursal = $stmtSuc->fetchColumn();

        if (!$nombreSucursal) {
             echo json_encode([]);
             exit;
        }

        // Mapeo lógico de sucursales a tipos de cargo
        $tipo = 'casa_matriz';
        if (stripos($nombreSucursal, 'Prat') !== false || stripos($nombreSucursal, 'Femacal') !== false || stripos($nombreSucursal, 'Villa') !== false) {
            $tipo = 'tienda';
        } elseif (stripos($nombreSucursal, 'Bodega') !== false || stripos($nombreSucursal, 'Nogales') !== false) {
            $tipo = 'bodega';
        }

        $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM cargos_empleo WHERE tipo_sucursal = ? AND activo = 1");
        $stmt->execute([$tipo]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // =========================================================
    // 💾 PROCESAMIENTO DE DATOS (GUARDAR POSTULACIÓN)
    // =========================================================

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rutLimpio = trim($_POST['rut']);

            // 1. Validar duplicados por RUT
            $stmtCheck = $this->db->prepare("SELECT id FROM postulaciones WHERE rut = ? AND estado != 'Descartado'");
            $stmtCheck->execute([$rutLimpio]);
            if ($stmtCheck->fetch()) {
                header("Location: " . BASE_URL . "empleos/postulante?msg=duplicado");
                exit;
            }

            // 2. Normalización de Nacionalidad
            $nacionalidad = ($_POST['nacionalidad_tipo'] === 'Extranjera')
                ? ($_POST['pais_origen'] ?? 'Extranjera')
                : 'Chilena';

            // Normalización de Texto
            $nombresFormateados   = ucwords(strtolower(trim($_POST['nombres'])));
            $apellidosFormateados = ucwords(strtolower(trim($_POST['apellidos'])));
            $emailFormateado      = strtolower(trim($_POST['email']));

            // 3. Manejo del CV
            $rutaCV = null;
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
                $nombreArchivo = 'CV_' . str_replace(['.', '-'], '', $rutLimpio) . '_' . time() . '.' . $ext;
                $destino = __DIR__ . '/../../public/cvs/' . $nombreArchivo;

                if (!file_exists(dirname($destino))) mkdir(dirname($destino), 0777, true);
                if (move_uploaded_file($_FILES['cv']['tmp_name'], $destino)) {
                    $rutaCV = 'cvs/' . $nombreArchivo;
                }
            } else {
                header("Location: " . BASE_URL . "empleos/postulante?msg=error_cv");
                exit;
            }

            // 4. Inserción en BD (Ahora usa sucursal_id y comuna_id)
            $sql = "INSERT INTO postulaciones 
                    (sucursal_id, cargo_id, nombres, apellidos, rut, edad, sexo, nacionalidad, permiso_trabajo, experiencia, comuna_id, telefono, email, ruta_cv, fecha_postulacion, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')";

            $stmt = $this->db->prepare($sql);
            $ejecucion = $stmt->execute([
                $_POST['sucursal_id'],
                $_POST['cargo_id'],
                $nombresFormateados,
                $apellidosFormateados,
                $rutLimpio,
                $_POST['edad'],
                $_POST['sexo'],
                $nacionalidad,
                $_POST['permiso_trabajo'] ?? 'N/A',
                trim($_POST['experiencia']),
                $_POST['comuna_id'], 
                trim($_POST['telefono']),
                $emailFormateado,
                $rutaCV
            ]);

            header("Location: " . BASE_URL . "empleos/postulante?msg=" . ($ejecucion ? "exito" : "error"));
            exit;
        }
    }

    // =========================================================
    // 📊 PANEL RRHH (SOLO ADMINS Y RRHH)
    // =========================================================

    public function rrhh_dashboard() {
        $this->verificarPermisoRRHH();
        ob_start();
        require __DIR__ . '/../../views/empleos/rrhh_hub.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/main.php';
    }
public function rrhh_reclutamiento() {
        $this->verificarPermisoRRHH();

        // Filtros
        $sucursal_id  = $_GET['sucursal_id'] ?? '';
        $sexo         = $_GET['sexo'] ?? '';
        $estado       = $_GET['estado'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin    = $_GET['fecha_fin'] ?? '';
        $orden        = (isset($_GET['orden']) && $_GET['orden'] === 'asc') ? 'ASC' : 'DESC';

        $por_pagina    = 20;
        $pagina_actual = max(1, (int)($_GET['page'] ?? 1));
        $offset        = ($pagina_actual - 1) * $por_pagina;

        // JOIN CON SUCURSALES Y COMUNAS
        $sqlBase = "FROM postulaciones p 
                    JOIN cargos_empleo c ON p.cargo_id = c.id 
                    LEFT JOIN sucursales s ON p.sucursal_id = s.id
                    LEFT JOIN comunas com ON p.comuna_id = com.id
                    WHERE 1=1 ";
        $params  = [];

        if (!empty($sucursal_id)) {
            $sqlBase .= " AND p.sucursal_id = ?"; $params[] = $sucursal_id;
        }
        if (!empty($sexo)) {
            $sqlBase .= " AND p.sexo = ?"; $params[] = $sexo;
        }
        if (!empty($estado)) {
            $sqlBase .= " AND p.estado = ?"; $params[] = $estado;
        }
        if (!empty($fecha_inicio)) {
            $sqlBase .= " AND DATE(p.fecha_postulacion) >= ?"; $params[] = $fecha_inicio;
        }
        if (!empty($fecha_fin)) {
            $sqlBase .= " AND DATE(p.fecha_postulacion) <= ?"; $params[] = $fecha_fin;
        }

        // Conteo para paginación
        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = $stmtCount->fetchColumn();
        $total_paginas   = ceil($total_registros / $por_pagina);

        // Datos finales
        // IMPORTANTE: Asegúrate de seleccionar el estado también p.estado
        $sql = "SELECT p.id, p.nombres, p.apellidos, p.nacionalidad, p.permiso_trabajo, 
                       p.fecha_postulacion, p.rut, p.edad, p.telefono, p.email, p.estado, p.ruta_cv,
                       c.nombre as cargo_nombre, 
                       COALESCE(s.nombre, 'No asignada') as nombre_sucursal, 
                       COALESCE(com.nombre, 'No especificada') as nombre_comuna " . 
                $sqlBase . " ORDER BY p.fecha_postulacion $orden LIMIT $por_pagina OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Traer sucursales para poblar el dropdown de filtros
        $sucursales = $this->db->query("SELECT id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        require __DIR__ . '/../../views/empleos/rrhh_reclutamiento.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/main.php';
    }

    public function rrhh_mantenedor() {
        $this->verificarPermisoRRHH();
        
        $stmt = $this->db->query("SELECT * FROM cargos_empleo ORDER BY tipo_sucursal ASC, nombre ASC");
        $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        require __DIR__ . '/../../views/empleos/rrhh_mantenedor.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/main.php';
    }

    public function guardarCargo() {
        $this->verificarPermisoRRHH();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $tipo_sucursal = $_POST['tipo_sucursal'];
            
            if ($id) {
                $stmt = $this->db->prepare("UPDATE cargos_empleo SET nombre = ?, descripcion = ?, tipo_sucursal = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $tipo_sucursal, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO cargos_empleo (nombre, descripcion, tipo_sucursal, activo) VALUES (?, ?, ?, 1)");
                $stmt->execute([$nombre, $descripcion, $tipo_sucursal]);
            }
            
            header("Location: " . BASE_URL . "empleos/rrhh_mantenedor?msg=exito");
            exit();
        }
    }

    public function toggleCargoAjax() {
        $this->verificarPermisoRRHH();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $activo = (int)$_POST['activo'];
            
            $stmt = $this->db->prepare("UPDATE cargos_empleo SET activo = ? WHERE id = ?");
            $success = $stmt->execute([$activo, $id]);
            
            echo json_encode(['success' => $success]);
            exit();
        }
    }

    // =========================================================
    // 🛠️ UTILIDADES RRHH
    // =========================================================

    public function exportarExcelRRHH()
    {
        $this->verificarPermisoRRHH();

        // 🔥 SQL Actualizado para Excel
        $sql = "SELECT p.*, c.nombre as cargo_nombre, s.nombre as nombre_sucursal, com.nombre as nombre_comuna 
                FROM postulaciones p 
                JOIN cargos_empleo c ON p.cargo_id = c.id 
                LEFT JOIN sucursales s ON p.sucursal_id = s.id
                LEFT JOIN comunas com ON p.comuna_id = com.id
                WHERE 1=1 ";
        $params = [];

        // Filtros (Se aplican igual si vienen en GET)
        if (!empty($_GET['sucursal_id'])) { $sql .= " AND p.sucursal_id = ?"; $params[] = $_GET['sucursal_id']; }
        if (!empty($_GET['estado'])) { $sql .= " AND p.estado = ?"; $params[] = $_GET['estado']; }
        if (!empty($_GET['sexo'])) { $sql .= " AND p.sexo = ?"; $params[] = $_GET['sexo']; }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="RRHH_Postulaciones_' . date('Ymd') . '.csv"');

        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel

        fputcsv($output, ['Fecha', 'Estado', 'Nombres', 'Apellidos', 'RUT', 'Edad', 'Sexo', 'Residencia', 'Postula a', 'Cargo', 'Telefono', 'Correo'], ';');

        foreach ($data as $row) {
            fputcsv($output, [
                $row['fecha_postulacion'],
                $row['estado'],
                $row['nombres'],
                $row['apellidos'],
                $row['rut'],
                $row['edad'],
                $row['sexo'],
                $row['nombre_comuna'],
                $row['nombre_sucursal'],
                $row['cargo_nombre'],
                $row['telefono'],
                $row['email']
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function cambiarEstado()
    {
        $this->verificarPermisoRRHH();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $estado = $_POST['estado'];

            $stmt = $this->db->prepare("UPDATE postulaciones SET estado = ? WHERE id = ?");
            $success = $stmt->execute([$estado, $id]);

            echo json_encode(['success' => $success]);
            exit;
        }
    }
}