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
        // 1 = SuperAdmin, 2 = Admin Sucursal (Asumimos que ambos ven RRHH)
        if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2])) {
            header("Location: " . BASE_URL . "home?msg=acceso_denegado");
            exit();
        }
    }

    // =========================================================
    // 🌐 VISTAS PÚBLICAS (POSTULANTES)
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
        // Traemos solo las comunas de interés para el reclutamiento local
        $nombresComunas = ['La Calera', 'La Cruz', 'Quillota', 'Nogales', 'Hijuelas'];
        $inQuery = implode(',', array_fill(0, count($nombresComunas), '?'));
        
        $stmt = $this->db->prepare("SELECT id, nombre FROM comunas WHERE nombre IN ($inQuery) ORDER BY nombre ASC");
        $stmt->execute($nombresComunas);
        $comunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        require_once __DIR__ . '/../../views/empleos/formulario.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function getCargosAjax()
    {
        header('Content-Type: application/json');
        $sucursal = $_GET['sucursal'] ?? '';
        
        // Mapeo lógico de sucursales a tipos de cargo
        $tipo = 'casa_matriz';
        if (in_array($sucursal, ['Femacal', 'Prat'])) $tipo = 'tienda';
        if ($sucursal === 'Bodega Nogales') $tipo = 'bodega';

        $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM cargos_empleo WHERE tipo_sucursal = ? AND activo = 1");
        $stmt->execute([$tipo]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // =========================================================
    // 💾 PROCESAMIENTO DE DATOS
    // =========================================================

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rutLimpio = trim($_POST['rut']);

            // 1. Validar duplicados por RUT
            $stmtCheck = $this->db->prepare("SELECT id FROM postulaciones WHERE rut = ? AND estado != 'Rechazado'");
            $stmtCheck->execute([$rutLimpio]);
            if ($stmtCheck->fetch()) {
                header("Location: " . BASE_URL . "empleos/postulante?msg=duplicado");
                exit;
            }

            // 2. Normalización de Nacionalidad
            $nacionalidad = ($_POST['nacionalidad_tipo'] === 'Extranjera') 
                ? ($_POST['pais_origen'] ?? 'Extranjera') 
                : 'Chilena';

            // 🔥 NORMALIZACIÓN DE TEXTO (Estilo Cencocal)
            $nombresFormateados   = ucwords(strtolower(trim($_POST['nombres'])));
            $apellidosFormateados = ucwords(strtolower(trim($_POST['apellidos'])));
            $comunaFormateada    = ucwords(strtolower(trim($_POST['comuna'])));
            $emailFormateado     = strtolower(trim($_POST['email']));

            // 3. Manejo del CV (Obligatorio)
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

            // 4. Inserción en BD
            $sql = "INSERT INTO postulaciones 
                    (sucursal, cargo_id, nombres, apellidos, rut, edad, sexo, nacionalidad, permiso_trabajo, experiencia, comuna, telefono, email, ruta_cv, fecha_postulacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $ejecucion = $stmt->execute([
                $_POST['sucursal'], $_POST['cargo_id'], $nombresFormateados, $apellidosFormateados,
                $rutLimpio, $_POST['edad'], $_POST['sexo'], $nacionalidad, 
                $_POST['permiso_trabajo'] ?? 'N/A', trim($_POST['experiencia']), 
                $comunaFormateada, trim($_POST['telefono']), $emailFormateado, $rutaCV
            ]);

            header("Location: " . BASE_URL . "empleos/postulante?msg=" . ($ejecucion ? "exito" : "error"));
            exit;
        }
    }

    // =========================================================
    // 📊 PANEL RRHH (SOLO ADMINS)
    // =========================================================

    public function dashboardRRHH()
    {
        $this->verificarPermisoRRHH();

        // Filtros
        $sucursal     = $_GET['sucursal'] ?? '';
        $sexo         = $_GET['sexo'] ?? '';
        $estado       = $_GET['estado'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin    = $_GET['fecha_fin'] ?? '';
        $orden        = (isset($_GET['orden']) && $_GET['orden'] === 'desc') ? 'DESC' : 'ASC';

        $por_pagina    = 20;
        $pagina_actual = max(1, (int)($_GET['page'] ?? 1));
        $offset        = ($pagina_actual - 1) * $por_pagina;

        $sqlBase = "FROM postulaciones p JOIN cargos_empleo c ON p.cargo_id = c.id WHERE 1=1 ";
        $params  = [];

        if (!empty($sucursal))     { $sqlBase .= " AND p.sucursal = ?"; $params[] = $sucursal; }
        if (!empty($sexo))         { $sqlBase .= " AND p.sexo = ?"; $params[] = $sexo; }
        if (!empty($estado))       { $sqlBase .= " AND p.estado = ?"; $params[] = $estado; }
        if (!empty($fecha_inicio)) { $sqlBase .= " AND DATE(p.fecha_postulacion) >= ?"; $params[] = $fecha_inicio; }
        if (!empty($fecha_fin))    { $sqlBase .= " AND DATE(p.fecha_postulacion) <= ?"; $params[] = $fecha_fin; }

        // Conteo para paginación
        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = $stmtCount->fetchColumn();
        $total_paginas   = ceil($total_registros / $por_pagina);

        // Datos finales
        $sql = "SELECT p.*, c.nombre as cargo_nombre " . $sqlBase . " ORDER BY p.fecha_postulacion $orden LIMIT $por_pagina OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $esAdmin = true; 
        ob_start();
        require_once __DIR__ . '/../../views/empleos/rrhh_dashboard.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    public function exportarExcelRRHH()
    {
        $this->verificarPermisoRRHH();

        $sql = "SELECT p.*, c.nombre as cargo_nombre FROM postulaciones p JOIN cargos_empleo c ON p.cargo_id = c.id WHERE 1=1 ";
        $params = [];
        // (Aquí podrías replicar los filtros del dashboard si quieres exportaciones filtradas)

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="RRHH_Postulaciones_' . date('Ymd') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel
        
        fputcsv($output, ['Fecha', 'Estado', 'Nombres', 'Apellidos', 'RUT', 'Edad', 'Sexo', 'Ubicacion', 'Cargo', 'Telefono', 'Correo'], ';');

        foreach ($data as $row) {
            fputcsv($output, [
                $row['fecha_postulacion'], $row['estado'], $row['nombres'], $row['apellidos'], 
                $row['rut'], $row['edad'], $row['sexo'], $row['sucursal'], 
                $row['cargo_nombre'], $row['telefono'], $row['email']
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