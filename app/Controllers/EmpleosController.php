<?php

namespace App\Controllers;

use PDO;

class EmpleosController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // 1. VISTA DIVIDIDA (Landing)
    public function index()
    {
        ob_start();
        require_once __DIR__ . '/../../views/empleos/landing.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

  
    
    // 3. AJAX: OBTENER CARGOS SEGÚN SUCURSAL
    public function getCargosAjax()
    {
        header('Content-Type: application/json');
        $sucursal = $_GET['sucursal'] ?? '';
        
        // Mapeamos la sucursal al tipo de cargo
        $tipo = 'casa_matriz';
        if (in_array($sucursal, ['Femacal', 'Prat'])) $tipo = 'tienda';
        if ($sucursal === 'Bodega Nogales') $tipo = 'bodega';

        $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM cargos_empleo WHERE tipo_sucursal = ? AND activo = 1");
        $stmt->execute([$tipo]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
// 2. VISTA FORMULARIO POSTULANTE
    public function postulante()
    {
        // Traemos solo las comunas solicitadas desde la BD
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

   // 4. GUARDAR POSTULACIÓN
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rutLimpio = trim($_POST['rut']);

            // 1. Validar que el RUT no haya postulado antes
            $stmtCheck = $this->db->prepare("SELECT id FROM postulaciones WHERE rut = ?");
            $stmtCheck->execute([$rutLimpio]);
            if ($stmtCheck->fetch()) {
                header("Location: " . BASE_URL . "empleos/postulante?msg=duplicado");
                exit;
            }

            // 2. Determinar Nacionalidad Real
            $nacionalidad = $_POST['nacionalidad_tipo'];
            if ($nacionalidad === 'Extranjera') {
                $nacionalidad = $_POST['pais_origen'] ?? 'Extranjera';
            }

            // 🔥 MAGIA DE TEXTO: Estandarizamos Nombres, Apellidos y Comuna (Capitalizar cada palabra)
            $nombresFormateados = ucwords(strtolower(trim($_POST['nombres'])));
            $apellidosFormateados = ucwords(strtolower(trim($_POST['apellidos'])));
            $comunaFormateada = ucwords(strtolower(trim($_POST['comuna'])));
            $emailFormateado = strtolower(trim($_POST['email'])); // El correo siempre en minúscula

            $datos = [
                $_POST['sucursal'], 
                $_POST['cargo_id'], 
                $nombresFormateados,   // 🔥 Usamos la variable formateada
                $apellidosFormateados, // 🔥 Usamos la variable formateada
                $rutLimpio, 
                $_POST['edad'], 
                $_POST['sexo'], 
                $nacionalidad,
                $_POST['permiso_trabajo'] ?? 'N/A', 
                trim($_POST['experiencia']), 
                $comunaFormateada,     // 🔥 Usamos la variable formateada
                trim($_POST['telefono']), 
                $emailFormateado       // 🔥 Usamos la variable formateada
            ];

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
            $datos[] = $rutaCV;

            $sql = "INSERT INTO postulaciones (sucursal, cargo_id, nombres, apellidos, rut, edad, sexo, nacionalidad, permiso_trabajo, experiencia, comuna, telefono, email, ruta_cv) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($datos)){
                header("Location: " . BASE_URL . "empleos/postulante?msg=exito");
            } else {
                header("Location: " . BASE_URL . "empleos/postulante?msg=error");
            }
            exit;
        }
    }


   // 5. DASHBOARD RRHH
    public function dashboardRRHH()
    {
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "home?msg=requiere_login"); exit; }
        if (!in_array(strtolower($_SESSION['rol'] ?? ''), ['admin', 'rrhh'])) { header("Location: " . BASE_URL . "home?msg=acceso_denegado"); exit; }

        // Filtros ampliados
        $sucursal = $_GET['sucursal'] ?? '';
        $sexo = $_GET['sexo'] ?? '';
        $estado = $_GET['estado'] ?? ''; // 🔥 Nuevo Filtro
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';
        $orden = $_GET['orden'] ?? 'asc'; 
        if(!in_array($orden, ['asc', 'desc'])) $orden = 'asc';

        $por_pagina = 20;
        $pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $sqlBase = "FROM postulaciones p JOIN cargos_empleo c ON p.cargo_id = c.id WHERE 1=1 ";
        $params = [];

        if (!empty($sucursal)) { $sqlBase .= " AND p.sucursal = ?"; $params[] = $sucursal; }
        if (!empty($sexo)) { $sqlBase .= " AND p.sexo = ?"; $params[] = $sexo; }
        if (!empty($estado)) { $sqlBase .= " AND p.estado = ?"; $params[] = $estado; } // 🔥 Aplicar Filtro
        if (!empty($fecha_inicio)) { $sqlBase .= " AND DATE(p.fecha_postulacion) >= ?"; $params[] = $fecha_inicio; }
        if (!empty($fecha_fin)) { $sqlBase .= " AND DATE(p.fecha_postulacion) <= ?"; $params[] = $fecha_fin; }

        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = $stmtCount->fetchColumn();
        $total_paginas = ceil($total_registros / $por_pagina);

        $sql = "SELECT p.*, c.nombre as cargo_nombre " . $sqlBase . " ORDER BY p.fecha_postulacion " . strtoupper($orden) . " LIMIT $por_pagina OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $postulaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $esAdmin = true; 
        
        ob_start();
        require_once __DIR__ . '/../../views/empleos/rrhh_dashboard.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

  // 6. EXPORTAR A EXCEL (CSV)
    public function exportarExcelRRHH()
    {
        if (!isset($_SESSION['user_id'])) exit;
        if (!in_array(strtolower($_SESSION['rol'] ?? ''), ['admin', 'rrhh'])) exit;

        $sucursal = $_GET['sucursal'] ?? '';
        $sexo = $_GET['sexo'] ?? '';
        $estado = $_GET['estado'] ?? ''; 
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';
        $orden = $_GET['orden'] ?? 'asc';

        $sql = "SELECT p.*, c.nombre as cargo_nombre FROM postulaciones p JOIN cargos_empleo c ON p.cargo_id = c.id WHERE 1=1 ";
        $params = [];

        if (!empty($sucursal)) { $sql .= " AND p.sucursal = ?"; $params[] = $sucursal; }
        if (!empty($sexo)) { $sql .= " AND p.sexo = ?"; $params[] = $sexo; }
        if (!empty($estado)) { $sql .= " AND p.estado = ?"; $params[] = $estado; } 
        if (!empty($fecha_inicio)) { $sql .= " AND DATE(p.fecha_postulacion) >= ?"; $params[] = $fecha_inicio; } // 🔥 Corregido aquí
        if (!empty($fecha_fin)) { $sql .= " AND DATE(p.fecha_postulacion) <= ?"; $params[] = $fecha_fin; }       // 🔥 Corregido aquí
        $sql .= " ORDER BY p.fecha_postulacion " . ($orden === 'desc' ? 'DESC' : 'ASC');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Postulaciones_RRHH_' . date('Ymd_Hi') . '.csv"');
        $output = fopen('php://output', 'w');
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // Añadimos la columna Estado al Excel
        fputcsv($output, ['Fecha', 'Estado', 'Nombres', 'Apellidos', 'RUT', 'Edad', 'Sexo', 'Nacionalidad', 'Permiso', 'Ubicacion', 'Cargo', 'Comuna', 'Telefono', 'Correo', 'Experiencia'], ';');

        foreach ($data as $row) {
            fputcsv($output, [
                $row['fecha_postulacion'], $row['estado'], $row['nombres'], $row['apellidos'], $row['rut'], $row['edad'], 
                $row['sexo'], $row['nacionalidad'], $row['permiso_trabajo'], $row['sucursal'], 
                $row['cargo_nombre'], $row['comuna'], $row['telefono'], $row['email'], preg_replace("/\r|\n/", " ", $row['experiencia'])
            ], ';');
        }
        fclose($output);
        exit;
    }

    // 7. AJAX: CAMBIAR ESTADO DE LA POSTULACIÓN
    public function cambiarEstado()
    {
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