<?php

namespace App\Models;

use PDO;

class Pedido
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function crear($datos)
    {
        $seguimiento = 'TRK-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $sql = "INSERT INTO pedidos 
            (usuario_id, rut_cliente, sucursal_codigo, vendedor_codigo, 
             total_neto, monto_total, costo_envio, direccion_entrega_texto, comuna_id, 
             estado_pedido_id, estado_pago_id, forma_pago_id, cantidad_items, cantidad_total_productos, 
             numero_seguimiento, tipo_entrega_id, latitud, longitud, 
             nombre_destinatario, telefono_contacto, telefono_contacto_2, fecha_entrega_estimada, rango_horario_id,
             fecha_creacion, hora_creacion) 
            VALUES 
            (:uid, :rut, :suc, :vend, 
             :neto, :total, :costo, :dir, :comuna, 
             :estado_id, :estado_pago_id, :fpago_id, :cant_items, :cant_prod, 
             :tracking, :tipo_entrega, :lat, :lng, 
             :nombre_dest, :tel_cont, :tel_cont_2, :fecha_ent, :rango_id,
             CURDATE(), CURTIME())";

        $stmt = $this->db->prepare($sql);
        $estadoInicial = $datos['estado_pedido_id'] ?? 1;

        $stmt->execute([
            ':uid'             => $datos['usuario_id'],
            ':rut'             => $datos['rut_cliente'],
            ':suc'             => $datos['sucursal_codigo'],
            ':vend'            => $datos['vendedor_codigo'],
            ':neto'            => $datos['total_neto'],
            ':total'           => $datos['monto_total'],
            ':costo'           => $datos['costo_envio'] ?? 0,
            ':dir'             => $datos['direccion_entrega_texto'],
            ':comuna'          => $datos['comuna_id'],
            ':estado_id'       => $estadoInicial,
            ':estado_pago_id'  => $datos['estado_pago_id'] ?? 1,
            ':fpago_id'        => $datos['forma_pago_id'] ?? 3,
            ':cant_items'      => $datos['cantidad_items'],
            ':cant_prod'       => $datos['cantidad_total_productos'],
            ':tracking'        => $seguimiento,
            ':tipo_entrega'    => $datos['tipo_entrega_id'],
            ':lat'             => $datos['latitud'] ?? null,
            ':lng'             => $datos['longitud'] ?? null,
            ':nombre_dest'     => $datos['nombre_destinatario'],
            ':tel_cont'        => $datos['telefono_contacto'],
            ':tel_cont_2'      => $datos['telefono_contacto_2'] ?? null,
            ':fecha_ent'       => $datos['fecha_entrega_estimada'],
            ':rango_id'        => $datos['rango_horario_id']
        ]);

        $idPedido = $this->db->lastInsertId();

        // 🔥 MAGIA: Reservamos el stock inmediatamente para que el catálogo web se actualice
        $this->reservarStock($idPedido);

        $this->registrarHistorial($idPedido, $estadoInicial, 'Pedido recibido exitosamente');

        return ['id' => $idPedido, 'tracking' => $seguimiento];
    }
    public function agregarDetalle($pedido_id, $producto, $cantidad, $precios)
    {
        $sql = "INSERT INTO pedidos_detalle 
                (pedido_id, producto_id, cod_producto, unidad_medida, cantidad, precio_neto, precio_bruto) 
                VALUES 
                (:pedido_id, :prod_id, :cod_erp, :um, :cant, :p_neto, :p_bruto)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pedido_id' => $pedido_id,
            ':prod_id'   => $producto->id,
            ':cod_erp'   => $producto->cod_producto ?? 'SIN-CODIGO',
            ':um'        => $producto->unidad_medida ?? 'UN',
            ':cant'      => $cantidad,
            ':p_neto'    => $precios['neto'],
            ':p_bruto'   => $precios['bruto']
        ]);
    }

    // =========================================================
    // 🛡️ LÓGICA DE STOCK RESERVADO (NUEVO)
    // =========================================================
    public function reservarStock($pedido_id)
    {
        // 1. Obtenemos los productos y la sucursal del pedido
        // Usamos TRIM y CAST para evitar errores de espacios o tipos de datos
        $sql = "SELECT dp.cod_producto, dp.cantidad, p.sucursal_codigo 
            FROM pedidos_detalle dp
            JOIN pedidos p ON dp.pedido_id = p.id
            WHERE dp.pedido_id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles as $det) {
            // 2. Actualizamos la reserva en productos_sucursales
            // IMPORTANTE: Aseguramos que sucursal_id coincida con el código del pedido
            $sqlUpdate = "UPDATE productos_sucursales 
                      SET stock_reservado = stock_reservado + :cant 
                      WHERE cod_producto = :cod 
                      AND sucursal_id = :sucursal";

            $stmtUp = $this->db->prepare($sqlUpdate);
            $stmtUp->execute([
                ':cant' => $det['cantidad'],
                ':cod'  => $det['cod_producto'],
                ':sucursal' => $det['sucursal_codigo'] // Debe ser 10 o 29
            ]);
        }
    }

    public function liberarStockReservado($pedido_id)
    {
        $sql = "SELECT dp.cod_producto, dp.cantidad, p.sucursal_codigo 
                FROM pedidos_detalle dp
                JOIN pedidos p ON dp.pedido_id = p.id
                WHERE dp.pedido_id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles as $det) {
            // Restamos del stock_reservado (usamos GREATEST para no bajar de 0 por seguridad)
            $sqlUpdate = "UPDATE productos_sucursales 
                          SET stock_reservado = GREATEST(0, stock_reservado - :cant) 
                          WHERE cod_producto = :cod AND sucursal_id = :sucursal";
            $stmtUp = $this->db->prepare($sqlUpdate);
            $stmtUp->execute([
                ':cant' => $det['cantidad'],
                ':cod'  => $det['cod_producto'],
                ':sucursal' => $det['sucursal_codigo']
            ]);
        }
    }

    // ==========================================
    // 2. MÉTODOS DE LECTURA (Dashboard / Mis Pedidos)
    // ==========================================

    public function obtenerTodos()
    {
        $sql = "SELECT p.*, 
                       u.nombre as nombre_cliente, 
                       u.email as email_cliente,
                       COALESCE(ep.nombre, 'pendiente') as estado
                FROM pedidos p 
                LEFT JOIN usuarios u ON p.usuario_id = u.id 
                LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                ORDER BY p.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function descontarStockFisicoFinal($pedidoId)
    {
        try {
            // 1. Buscamos el pedido para saber la sucursal exacta
            $pedido = $this->obtenerPorId($pedidoId);
            $sucursalId = $pedido['sucursal_codigo'];

            // 2. SQL BLINDADO: 
            // - Restamos del stock real (Salida física de bodega)
            // - Restamos de la reserva (Ya no es una promesa, es una venta)
            // - Usamos GREATEST para asegurar que nunca baje de cero
            $sql = "UPDATE productos_sucursales ps
                INNER JOIN pedidos_detalle pd ON ps.cod_producto = pd.cod_producto
                SET ps.stock = GREATEST(0, ps.stock - pd.cantidad),
                    ps.stock_reservado = GREATEST(0, ps.stock_reservado - pd.cantidad)
                WHERE pd.pedido_id = :pedido_id 
                  AND ps.sucursal_id = :sucursal_id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':pedido_id' => $pedidoId,
                ':sucursal_id' => $sucursalId
            ]);
        } catch (\PDOException $e) {
            error_log("Error crítico en descuento físico sucursal: " . $e->getMessage());
            return false;
        }
    }
    public function obtenerPorId($id)
    {
        $sql = "SELECT p.*, 
                       -- Rescatamos el nombre: 1° Usuario, 2° Destinatario, 3° Concatenado, 4° Default
                       COALESCE(u.nombre, p.nombre_destinatario, CONCAT(p.primer_nombre, ' ', p.primer_apellido), 'Cliente Invitado') as nombre_cliente, 
                       
                       -- Rescatamos el email
                       COALESCE(u.email, 'Sin correo (Invitado)') as email_cliente, 
                       
                       -- Rescatamos el teléfono: 1° Tabla telefonos, 2° Tabla pedidos
                       COALESCE(ut.numero, p.telefono_contacto, 'Sin teléfono') as telefono_cliente,
                       
                       -- Rescatamos el RUT: 1° Usuario, 2° Tabla pedidos
                       COALESCE(u.rut, p.rut_cliente, 'Sin RUT') as rut_cliente,
                       
                       c.nombre as nombre_comuna,
                       ep_pago.nombre as nombre_estado_pago,
                       ep_pedido.nombre as nombre_estado_pedido,
                       ep_pedido.badge_class as badge_estado
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN usuario_telefonos ut ON u.id = ut.usuario_id AND ut.es_principal = 1 
                LEFT JOIN comunas c ON p.comuna_id = c.id
                LEFT JOIN estados_pago ep_pago ON p.estado_pago_id = ep_pago.id
                LEFT JOIN estados_pedido ep_pedido ON p.estado_pedido_id = ep_pedido.id
                WHERE p.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function obtenerDetalles($id)
    {
        $sql = "SELECT dp.*, p.nombre as nombre_producto, p.imagen 
                FROM pedidos_detalle dp
                LEFT JOIN productos p ON dp.producto_id = p.id
                WHERE dp.pedido_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 3. MÉTODOS DE GESTIÓN (Admin)
    // ==========================================

    public function actualizarEstado($id, $nuevo_estado)
    {
        $sql = "UPDATE pedidos SET estado_pedido_id = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $nuevo_estado, ':id' => $id]);

        // --- MAGIA: LIBERACIÓN AUTOMÁTICA DE STOCK ---
        // Asumiendo que 3=En Ruta, 4=Entregado/Facturado, 5=Cancelado
        // (Ajusta estos IDs según cómo estén en tu tabla `estados_pedido`)
        $estados_que_liberan_stock = [4, 5];

        if (in_array($nuevo_estado, $estados_que_liberan_stock)) {
            // Cuando la boleta ya se emitió y entregó, o si se canceló el pedido,
            // soltamos el "candado" virtual del stock_reservado.
            // Para el caso de cancelado (5), el próximo CSV de 20 min volverá a mostrar el stock real arriba.
            // Para el caso facturado (4), el próximo CSV de 20 min ya vendrá con el stock restado del ERP.
            $this->liberarStockReservado($id);
        }
    }

    public function obtenerEstadosPosibles()
    {
        $sql = "SELECT * FROM estados_pedido ORDER BY id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorUsuario($usuario_id)
    {
        $sql = "SELECT 
                    p.id, 
                    p.monto_total as total, 
                    p.numero_seguimiento,
                    DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y') as fecha_compra,
                    DATE_FORMAT(p.fecha_entrega_estimada, '%d/%m/%Y') as fecha_entrega,
                    COALESCE(rh.nombre, 'Por definir') as rango_horario,
                    ep.nombre as estado,
                    COALESCE(ep.badge_class, 'secondary') as color_estado
                FROM pedidos p
                LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                LEFT JOIN rangos_horarios rh ON p.rango_horario_id = rh.id
                WHERE p.usuario_id = ? 
                ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerDetalleProductos($pedido_id)
    {
        // MAGIA INYECTADA: Extraemos el stock en tiempo real de la sucursal asignada al pedido
        $sql = "SELECT dp.*, 
                       p.nombre, 
                       p.imagen, 
                       p.cod_producto, 
                       dp.precio_bruto as precio_unitario,
                       COALESCE(ps.stock, 0) as stock_sucursal
                FROM pedidos_detalle dp 
                INNER JOIN productos p ON dp.producto_id = p.id 
                INNER JOIN pedidos ped ON dp.pedido_id = ped.id
                LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ped.sucursal_codigo = ps.sucursal_id
                WHERE dp.pedido_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function obtenerFiltrados($fechaInicio, $fechaFin, $busqueda = '', $estado = '', $limite = 25, $offset = 0, $sucursalAdmin = null)
    {
        // 1. Limpiar las fechas para que coincidan con tu INT de la base de datos
        // Convierte "2026-03-04" a "20260304" (Número entero)
        $fechaInicioInt = (int) str_replace('-', '', $fechaInicio);
        $fechaFinInt = (int) str_replace('-', '', $fechaFin);

        $buscandoId = (is_numeric($busqueda) && intval($busqueda) > 0);
        $sql = "SELECT p.*, 
                       -- Magia COALESCE: Si no hay usuario registrado, lee los datos del pedido
                       COALESCE(u.nombre, p.nombre_destinatario, CONCAT(p.primer_nombre, ' ', p.primer_apellido), 'Cliente Invitado') as nombre_cliente, 
                       COALESCE(u.email, 'Sin correo (Invitado)') as email_cliente, 
                       COALESCE(u.rut, p.rut_cliente, 'Sin RUT') as rut_cliente,
                       
                       COALESCE(ep.nombre, 'pendiente') as estado,
                       COALESCE(ep.badge_class, 'secondary') as color_estado,
                       COALESCE(rh.nombre, 'Por definir') as rango_horario,
                       -- Si guardaste la fecha como INT, esto la formatea visualmente bien
                       DATE_FORMAT(STR_TO_DATE(CAST(p.fecha_creacion AS CHAR), '%Y%m%d'), '%d/%m/%Y') as fecha_entrega_fmt
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                LEFT JOIN rangos_horarios rh ON p.rango_horario_id = rh.id
                WHERE 1=1";

        $params = [];

        // Filtro de fecha con comparación numérica (Mucho más seguro y rápido)
        if (!$buscandoId) {
            $sql .= " AND p.fecha_creacion BETWEEN :inicio AND :fin";
            $params[':inicio'] = $fechaInicioInt;
            $params[':fin'] = $fechaFinInt;
        }

        // Filtro de sucursal
        if (!empty($sucursalAdmin)) {
            $sql .= " AND p.sucursal_codigo = :sucursal_admin";
            $params[':sucursal_admin'] = trim(strval($sucursalAdmin));
        }

        // Filtro de búsqueda (RUT, Folio, Nombre, etc)
        if (!empty($busqueda)) {
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);
            $sql .= " AND (u.nombre LIKE :q1 OR u.email LIKE :q2 OR REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q3 OR p.id = :q4 OR p.numero_seguimiento LIKE :q5)";
            $params[':q1'] = "%$busqueda%";
            $params[':q2'] = "%$busqueda%";
            $params[':q3'] = "%$busquedaLimpia%";
            $params[':q4'] = $busqueda;
            $params[':q5'] = "%$busqueda%";
        }

        // Filtro de Estado de Pedido (Logística)
        if (!empty($estado)) {
            $sql .= " AND p.estado_pedido_id = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarFiltrados($fechaInicio, $fechaFin, $busqueda = '', $estado = '', $sucursalAdmin = null)
    {
        $fechaInicioInt = (int) str_replace('-', '', $fechaInicio);
        $fechaFinInt = (int) str_replace('-', '', $fechaFin);
        $buscandoId = (is_numeric($busqueda) && intval($busqueda) > 0);

        $sql = "SELECT COUNT(p.id) as total FROM pedidos p 
                LEFT JOIN usuarios u ON p.usuario_id = u.id 
                WHERE 1=1";

        $params = [];
        if (!$buscandoId) {
            $sql .= " AND p.fecha_creacion BETWEEN :inicio AND :fin";
            $params[':inicio'] = $fechaInicioInt;
            $params[':fin'] = $fechaFinInt;
        }

        if (!empty($sucursalAdmin)) {
            $sql .= " AND p.sucursal_codigo = :sucursal_admin";
            $params[':sucursal_admin'] = trim(strval($sucursalAdmin));
        }

        if (!empty($busqueda)) {
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);
            $sql .= " AND (u.nombre LIKE :q1 OR u.email LIKE :q2 OR REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q3 OR p.id = :q4 OR p.numero_seguimiento LIKE :q5)";
            $params[':q1'] = "%$busqueda%";
            $params[':q2'] = "%$busqueda%";
            $params[':q3'] = "%$busquedaLimpia%";
            $params[':q4'] = $busqueda;
            $params[':q5'] = "%$busqueda%";
        }

        if (!empty($estado)) {
            $sql .= " AND p.estado_pedido_id = :estado";
            $params[':estado'] = $estado;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function registrarHistorial($pedidoId, $estadoId, $comentario = '')
    {
        $sql = "INSERT INTO historial_pedidos (pedido_id, estado_pedido_id, comentario, fecha_creacion) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$pedidoId, $estadoId, $comentario]);
    }

    public function obtenerHistorial($pedidoId)
    {
        $sql = "SELECT h.*, 
                       ep.nombre as nombre_estado,
                       ep.badge_class as color_estado,
                       DATE_FORMAT(h.fecha_creacion, '%d/%m/%Y') as fecha,
                       DATE_FORMAT(h.fecha_creacion, '%H:%i') as hora
                FROM historial_pedidos h
                JOIN estados_pedido ep ON h.estado_pedido_id = ep.id
                WHERE h.pedido_id = ?
                ORDER BY h.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pedidoId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function actualizarEstadoPago($id, $estadoPagoId)
    {
        $sql = "UPDATE pedidos SET estado_pago_id = :estado_pago WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':estado_pago' => $estadoPagoId, ':id' => $id]);
    }

    public function registrarEntregaFinal($data)
    {
        // 1. Definimos los IDs de estado (Asegúrate que coincidan con tus tablas maestras)
        // Supongamos: Entregado = 4, Pagado = 2
        $estadoEntregado = 4;
        $estadoPagado = 2;

        $sql = "UPDATE pedidos SET 
                estado_pedido_id = :estado_ped,
                estado_pago_id   = :estado_pag,
                latitud_entrega  = :lat_e,
                longitud_entrega = :lng_e,
                fecha_hora_entrega = :fecha_e,
                comprobante_pago = :foto,
                transportista_id = :trans_id
            WHERE id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado_ped' => $estadoEntregado,
            ':estado_pag' => $estadoPagado,
            ':lat_e'      => $data['latitud'],
            ':lng_e'      => $data['longitud'],
            ':fecha_e'    => date('Y-m-d H:i:s'),
            ':foto'       => $data['ruta_foto'],
            ':trans_id'   => $_SESSION['user_id'],
            ':pedido_id'  => $data['pedido_id']
        ]);
    }

    public function obtenerTodas()
    {
        // Seleccionamos los campos exactos de tu tabla pedidos (7).sql
        $sql = "SELECT id, nombre_destinatario, direccion_entrega_texto, 
                       telefono_contacto, hora_creacion, forma_pago_id, 
                       estado_pedido_id, latitud, longitud 
                FROM pedidos 
                ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // =========================================================
    // LÓGICA DE AUDITORÍA Y EDICIÓN DE PEDIDOS
    // =========================================================
    public function aplicarEdicion($pedido_id, $admin_id, $motivo, $itemsEliminados, $itemsAgregados, $rutaEvidencia, $nuevoTotal, $nuevoSubsidio)
    {
        $this->db->beginTransaction();

        try {
            // 1. Obtener datos antes de la edición para el respaldo de auditoría
            $pedidoPreEdicion = $this->obtenerPorId($pedido_id);
            $montoOriginalBD = (int)$pedidoPreEdicion['monto_total'];

            // 2. Procesar Items Eliminados (Sacar de la tabla detalle)
            foreach ($itemsEliminados as $itemOut) {
                $stmtDel = $this->db->prepare("DELETE FROM pedidos_detalle WHERE pedido_id = ? AND producto_id = ? LIMIT 1");
                $stmtDel->execute([$pedido_id, $itemOut['producto_id']]);
            }

            // 3. Procesar Items Agregados (Insertar en la tabla detalle)
            foreach ($itemsAgregados as $itemIn) {
                // Adaptador para usar tu método existente agregarDetalle()
                $productoAdapter = new \stdClass();
                $productoAdapter->id = $itemIn['producto_id'];
                $productoAdapter->cod_producto = $itemIn['cod_producto'];

                $this->agregarDetalle($pedido_id, $productoAdapter, $itemIn['cantidad'], [
                    'neto' => round($itemIn['precio_bruto'] / 1.19),
                    'bruto' => $itemIn['precio_bruto']
                ]);
            }

            // 4. ACTUALIZACIÓN FINANCIERA (El corazón del cambio)
            // Guardamos el nuevo total del ERP y el subsidio calculado
            $stmtUpdate = $this->db->prepare("UPDATE pedidos SET monto_total = ?, subsidio_empresa = ? WHERE id = ?");
            $stmtUpdate->execute([$nuevoTotal, $nuevoSubsidio, $pedido_id]);

            // 5. REGISTRAR CABECERA DE AUDITORÍA
            $sqlAudit = "INSERT INTO pedidos_ediciones 
                         (pedido_id, admin_id, motivo_cambio, monto_original, monto_nuevo, subsidio_generado, evidencia_imagen) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtAudit = $this->db->prepare($sqlAudit);
            $stmtAudit->execute([
                $pedido_id,
                $admin_id,
                $motivo,
                $montoOriginalBD,
                $nuevoTotal,
                $nuevoSubsidio,
                $rutaEvidencia
            ]);
            $edicion_id = $this->db->lastInsertId();

            // 6. REGISTRAR DETALLE DE AUDITORÍA (Qué productos cambiaron)
            $sqlAuditDet = "INSERT INTO pedidos_ediciones_detalle 
                            (edicion_id, accion, producto_id, cod_producto, nombre_producto, cantidad, precio_bruto) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtAuditDet = $this->db->prepare($sqlAuditDet);

            foreach ($itemsEliminados as $itemOut) {
                $stmtAuditDet->execute([
                    $edicion_id,
                    'eliminado',
                    $itemOut['producto_id'],
                    $itemOut['cod_producto'],
                    $itemOut['nombre'],
                    $itemOut['cantidad'],
                    $itemOut['precio_bruto']
                ]);
            }
            foreach ($itemsAgregados as $itemIn) {
                $stmtAuditDet->execute([
                    $edicion_id,
                    'agregado',
                    $itemIn['producto_id'],
                    $itemIn['cod_producto'],
                    $itemIn['nombre'],
                    $itemIn['cantidad'],
                    $itemIn['precio_bruto']
                ]);
            }

            // 7. REGISTRAR EN BITÁCORA (Historial visible)
            $comentarioLog = "EDICIÓN: Se actualizaron productos. Nuevo Total: $" . number_format($nuevoTotal, 0, ',', '.') . " (Subsidio: $" . number_format($nuevoSubsidio, 0, ',', '.') . ")";
            $this->registrarHistorial($pedido_id, $pedidoPreEdicion['estado_pedido_id'], $comentarioLog);

            $this->db->commit();
            return ['status' => true, 'nuevo_total' => $nuevoTotal, 'subsidio' => $nuevoSubsidio];
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error en aplicarEdicion: " . $e->getMessage());
            return ['status' => false, 'error' => 'Error al procesar la edición: ' . $e->getMessage()];
        }
    }

    // =========================================================
    // LECTURA DE AUDITORÍA VISUAL
    // =========================================================
    public function obtenerItemsEliminados($pedido_id)
    {
        $sql = "SELECT ed.producto_id, ed.cod_producto, ed.nombre_producto as nombre, ed.cantidad, ed.precio_bruto, p.imagen 
                FROM pedidos_ediciones_detalle ed
                JOIN pedidos_ediciones e ON ed.edicion_id = e.id
                LEFT JOIN productos p ON ed.producto_id = p.id
                WHERE e.pedido_id = ? AND ed.accion = 'eliminado'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerIdsAgregados($pedido_id)
    {
        $sql = "SELECT ed.producto_id 
                FROM pedidos_ediciones_detalle ed
                JOIN pedidos_ediciones e ON ed.edicion_id = e.id
                WHERE e.pedido_id = ? AND ed.accion = 'agregado'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // =========================================================
    // LÓGICA DE ANULACIÓN Y DEVOLUCIÓN DE STOCK
    // =========================================================
    public function anularYDevolverStock($pedido_id, $admin_id, $motivo)
    {
        $this->db->beginTransaction();
        try {
            // 1. Cambiamos el estado logístico a 6 (Anulado) y pago a 3 (Reembolsado/Anulado - asumiendo que exista)
            $stmtUpdate = $this->db->prepare("UPDATE pedidos SET estado_pedido_id = 6 WHERE id = ?");
            $stmtUpdate->execute([$pedido_id]);

            // 2. Obtenemos los detalles y la sucursal origen
            $pedido = $this->obtenerPorId($pedido_id);
            $sucursal_id = $pedido['sucursal_codigo'];

            $detalles = $this->obtenerDetalleProductos($pedido_id);

            // 3. Devolvemos el stock al inventario físico
            $stmtStock = $this->db->prepare("UPDATE productos_sucursales SET stock = stock + ? WHERE cod_producto = ? AND sucursal_id = ?");
            foreach ($detalles as $d) {
                $stmtStock->execute([$d['cantidad'], $d['cod_producto'], $sucursal_id]);
            }

            // 4. Registramos en el historial la anulación con el motivo y quién lo hizo
            $comentario = "ANULACIÓN Y REEMBOLSO. Motivo: " . strtoupper($motivo);
            $this->registrarHistorial($pedido_id, 6, $comentario);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error anulando pedido: " . $e->getMessage());
            return false;
        }
    }

    // En app/Models/Pedido.php

/**
 * Obtiene todas las métricas consolidadas para el Dashboard Administrativo
 */
public function obtenerMetricasDashboard($desde, $hasta, $sucursalAsignada = null)
{
    $params = [':desde' => $desde, ':hasta' => $hasta];
    $filtroSucursal = "";
    $filtroSucursalTop = "";

    if (!empty($sucursalAsignada)) {
        $filtroSucursal = " AND sucursal_codigo = :sucursal";
        $filtroSucursalTop = " AND ped.sucursal_codigo = :sucursal";
        $params[':sucursal'] = strval($sucursalAsignada);
    }

    // 1. Venta Total y Despachos
    $sqlVenta = "SELECT COALESCE(SUM(monto_total), 0) as total_general, 
                        COALESCE(SUM(costo_envio), 0) as total_despacho
                 FROM pedidos 
                 WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal 
                 AND DATE(fecha_creacion) BETWEEN :desde AND :hasta";
    $stmtVenta = $this->db->prepare($sqlVenta);
    $stmtVenta->execute($params);
    $rowVenta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

    // 2. Pedidos Pendientes
    $sqlPend = "SELECT COUNT(*) FROM pedidos WHERE estado_pedido_id = 1" . 
               (!empty($sucursalAsignada) ? " AND sucursal_codigo = :sucursal" : "");
    $paramsPend = !empty($sucursalAsignada) ? [':sucursal' => strval($sucursalAsignada)] : [];
    $stmtPend = $this->db->prepare($sqlPend);
    $stmtPend->execute($paramsPend);
    $pendientes = $stmtPend->fetchColumn();

    // 3. Gráfico de Ventas
    $sqlGrafico = "SELECT DATE_FORMAT(fecha_creacion, '%d/%m') as fecha, SUM(monto_total) as total 
                   FROM pedidos 
                   WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal 
                   AND DATE(fecha_creacion) BETWEEN :desde AND :hasta 
                   GROUP BY DATE(fecha_creacion) ORDER BY fecha_creacion ASC";
    $stmtGraf = $this->db->prepare($sqlGrafico);
    $stmtGraf->execute($params);
    $datosGrafico = $stmtGraf->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top 5 Productos (Usando el Modelo Producto para el nombre)
    $sqlTop = "SELECT p.nombre, SUM(dp.cantidad) as vendidos 
               FROM pedidos_detalle dp 
               JOIN pedidos ped ON dp.pedido_id = ped.id 
               JOIN productos p ON dp.producto_id = p.id 
               WHERE ped.estado_pedido_id NOT IN (1, 6) $filtroSucursalTop 
               AND DATE(ped.fecha_creacion) BETWEEN :desde AND :hasta 
               GROUP BY p.id ORDER BY vendidos DESC LIMIT 5";
    $stmtTop = $this->db->prepare($sqlTop);
    $stmtTop->execute($params);
    $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    return [
        'venta_periodo'   => (float)$rowVenta['total_general'],
        'ingreso_despacho' => (float)$rowVenta['total_despacho'],
        'pendientes'      => $pendientes,
        'datos_grafico'   => $datosGrafico,
        'top_productos'   => $topProductos
    ];
}

// En app/Models/Pedido.php (Añadir este método)

public function obtenerUltimosPedidos($sucursalCodigo = null, $limite = 5)
{
    $filtro = "";
    $params = [];

    if (!empty($sucursalCodigo)) {
        $filtro = " WHERE p.sucursal_codigo = :sucursal";
        $params[':sucursal'] = strval($sucursalCodigo);
    }

    $sql = "SELECT p.*, u.nombre as nombre_cliente, ep.nombre as estado, ep.badge_class as color_estado 
            FROM pedidos p 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id 
            $filtro 
            ORDER BY p.id DESC LIMIT " . (int)$limite;

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
}
