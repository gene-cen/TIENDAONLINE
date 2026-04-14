<?php

namespace App\Services;

use Exception;
use PDO;

class ImportadorService
{
    private $db;
    private $metricas = [
        'archivos_encontrados' => 0,
        'sucursales_procesadas' => [],
        'total_filas' => 0,
        'productos_nuevos' => 0,
        'actualizaciones' => 0,
        'errores' => []
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function ejecutar($directorio)
    {
        // Busca dinámicamente TODOS los archivos que terminen en _productos_web2.csv
        $archivos = glob($directorio . '*_productos_web2.csv');
        $this->metricas['archivos_encontrados'] = count($archivos);

        if (empty($archivos)) {
            throw new Exception("No se encontraron archivos en la ruta especificada.");
        }

        foreach ($archivos as $archivo) {
            $nombreArchivo = basename($archivo);
            $sucursalId = (int)explode('_', $nombreArchivo)[0];

            if ($sucursalId <= 0) {
                $this->metricas['errores'][] = "Nombre de archivo inválido: $nombreArchivo";
                continue;
            }

            $this->procesarArchivo($archivo, $sucursalId);
            $this->metricas['sucursales_procesadas'][] = $sucursalId;
        }

        return $this->metricas;
    }

    private function procesarArchivo($ruta, $sucursalId)
    {
        // 1. Limpieza previa: Bajamos a 0 SOLO el stock de la sucursal que estamos procesando
        $stmtClear = $this->db->prepare("UPDATE productos_sucursales SET stock = 0 WHERE sucursal_id = ?");
        $stmtClear->execute([$sucursalId]);

        $this->db->beginTransaction();
        try {
            $handle = fopen($ruta, "r");
            $filaCount = 0;
            $separador = ',';

            // --- 1. MAESTRO GLOBAL (Tabla productos) ---
            $stmtMaestro = $this->db->prepare("
                INSERT INTO productos (cod_producto, nombre, categoria, precio_unidad_medida, imagen, activo) 
                VALUES (?, ?, ?, ?, ?, 0) 
                ON DUPLICATE KEY UPDATE 
                    nombre = VALUES(nombre), 
                    categoria = VALUES(categoria),
                    precio_unidad_medida = VALUES(precio_unidad_medida),
                    imagen = IF(VALUES(imagen) != '' AND VALUES(imagen) IS NOT NULL, VALUES(imagen), imagen)
            ");

            // --- 2. INFO WEB (Tabla productos_info_web) ---
            // 🔥 EL GRAN FIX ESTÁ AQUÍ: ELIMINAMOS LA COLUMNA FANTASMA 'stock_seguridad'
            $stmtInfoWeb = $this->db->prepare("
                INSERT IGNORE INTO productos_info_web (cod_producto, nombre_web, visible_web) 
                VALUES (?, ?, 1)
            ");

            // --- 3. DETALLE SUCURSAL (Tabla productos_sucursales) ---
            $stmtDetalle = $this->db->prepare("
                INSERT INTO productos_sucursales (cod_producto, sucursal_id, precio, stock, stock_reservado) 
                VALUES (?, ?, ?, ?, 0) 
                ON DUPLICATE KEY UPDATE precio = VALUES(precio), stock = VALUES(stock)
            ");

            while (($linea = fgets($handle)) !== false) {
                $filaCount++;

                if ($filaCount === 1) {
                    if (strpos($linea, ';') !== false) {
                        $separador = ';';
                    }
                    continue;
                }

                $data = str_getcsv(trim($linea), $separador);
                if (count($data) < 5) continue;

                // --- BÚSQUEDA INTELIGENTE DE ÍNDICES ---
                $stockIndex = -1;
                for ($i = count($data) - 1; $i >= 2; $i--) {
                    if (is_numeric(trim($data[$i])) && is_numeric(trim($data[$i - 1]))) {
                        $stockIndex = $i;
                        break;
                    }
                }

                if ($stockIndex === -1) continue;

                $sku = trim($data[$stockIndex - 2]);

                // 🔥 FIX DEL CERO A LA IZQUIERDA
                if (strlen($sku) === 6) {
                    $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
                }

                $stock     = (int)trim($data[$stockIndex]);
                $precio    = (int)trim($data[$stockIndex - 1]);
                $categoria = trim($data[$stockIndex - 3]);

                $img = isset($data[$stockIndex + 1]) ? trim($data[$stockIndex + 1], " \t\n\r\0\x0B\"'") : '';
                $descripcion_nula = isset($data[$stockIndex + 2]) ? trim($data[$stockIndex + 2]) : null;

                $pum = isset($data[$stockIndex + 3]) ? trim($data[$stockIndex + 3]) : null;
                if ($pum === "") {
                    $pum = null;
                }

                $nombreParts = array_slice($data, 0, $stockIndex - 3);
                $nombre      = trim(implode(',', $nombreParts));

                // 🔥 LA BALA DE PLATA: DETECTOR MULTI-NIVEL DE BUGS DEL ERP
                // 1. Coincidencia de SKU y Precio
                // 2. La palabra "PESABLE" en el nombre
                // 3. Precios absurdos mayores a 500 mil pesos
                if (
                    (int)$precio === (int)$sku ||
                    strpos(strtoupper($nombre), 'PESABLE') !== false ||
                    $precio > 500000
                ) {
                    $precio = 0;
                }

                if (!empty($sku)) {
                    // ... (Aquí continúa tu código intacto hacia abajo con el $stmtMaestro->execute)
                    $stmtMaestro->execute([$sku, $nombre, $categoria, $pum, $img]);

                    if ($stmtMaestro->rowCount() === 1) {
                        $this->metricas['productos_nuevos']++;
                    }

                    // Al quitar la columna fantasma, esta línea ya no colapsará la base de datos
                    $stmtInfoWeb->execute([$sku, $nombre]);

                    $stmtDetalle->execute([$sku, $sucursalId, $precio, $stock]);
                    if ($stmtDetalle->rowCount() > 0) {
                        $this->metricas['actualizaciones']++;
                    }

                    $this->metricas['total_filas']++;
                }
            }
            fclose($handle);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->metricas['errores'][] = "Falla crítica en Sucursal $sucursalId: " . $e->getMessage();
        }
    }
}
