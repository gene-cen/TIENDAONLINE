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
        // ==========================================
        // 🔥 1. REGLAS DE NEGOCIO Y AUTOMATIZACIÓN
        // ==========================================

        // A) Determinar Tipo de Cliente
        $esAsistido = (isset($_SESSION['modo_venta_asistida']) && $_SESSION['modo_venta_asistida'] === true);
        $tipo_cliente_id = 3; // Invitado
        if ($esAsistido) {
            $tipo_cliente_id = 2; // Asistido
        } elseif (!empty($datos['usuario_id'])) {
            $tipo_cliente_id = 1; // Registrado
        }

        // B) Vendedor por Defecto
        $vendedor_codigo = ($datos['sucursal_codigo'] == 10) ? '0049' : '2990';

        // C) Fecha y Hora actual
        $fecha_c = (int)date('Ymd');
        $hora_c = date('H:i:s');

        // D) Lógica de Entrega Inmediata (Venta Asistida + Retiro)
        $fechaEntrega = $datos['fecha_entrega_estimada'];
        $rangoHorarioId = $datos['rango_horario_id'];

        if ($esAsistido && $datos['tipo_entrega_id'] == 2) {
            // Si es retiro presencial asistido, la entrega es HOY e INMEDIATA
            $fechaEntrega = date('Y-m-d');

            /* Consideramos el rango más cercano. 
               Aquí podrías consultar tu tabla 'rangos_horarios' 
               o asignar un ID específico (ej: 1) que represente el bloque actual.
            */
            $rangoHorarioId = 1;
        }

        // E) Coordenadas Únicas y Transportista
        $lat = $datos['latitud'] ?? null;
        $lng = $datos['longitud'] ?? null;
        $id_transportista = null;

        if ($datos['tipo_entrega_id'] == 2) {
            // RETIRO EN TIENDA: Coordenadas fijas de la sucursal
            if ($datos['sucursal_codigo'] == 10) {
                $lat = '-33.044167';
                $lng = '-71.375556';
            } else {
                $lat = '-32.783333';
                $lng = '-71.216667';
            }
        } else {
            // DESPACHO: Asignar Transportista de la sucursal
            try {
                $stmtTrans = $this->db->prepare("SELECT id FROM usuarios WHERE rol_id = 5 AND sucursal_asignada = ? AND estado = 1 LIMIT 1");
                $stmtTrans->execute([$datos['sucursal_codigo']]);
                $id_transportista = $stmtTrans->fetchColumn() ?: null;
            } catch (\Exception $e) {
                $id_transportista = null;
            }
        }

        // ==========================================
        // 💾 2. INSERCIÓN DEL PEDIDO (Limpia)
        // ==========================================
        $sql = "INSERT INTO pedidos 
            (usuario_id, tipo_cliente_id, rut_cliente, nombre_cliente, sucursal_codigo, vendedor_codigo, 
             total_neto, monto_total, costo_envio, direccion_entrega_texto, comuna_id, 
             estado_pedido_id, estado_pago_id, forma_pago_id, cantidad_items, cantidad_total_productos, 
             tipo_entrega_id, latitud, longitud, transportista_id,
             nombre_destinatario, telefono_contacto, telefono_contacto_2, fecha_entrega_estimada, rango_horario_id,
             fecha_creacion, hora_creacion) 
            VALUES 
            (:uid, :tipo_cliente, :rut, :nombre, :suc, :vend, 
             :neto, :total, :costo, :dir, :comuna, 
             :estado_id, :estado_pago_id, :fpago_id, :cant_items, :cant_prod, 
             :tipo_entrega, :lat, :lng, :trans_id,
             :nombre_dest, :tel_cont, :tel_cont_2, :fecha_ent, :rango_id,
             :fecha_c, :hora_c)";

        $stmt = $this->db->prepare($sql);
        $estadoInicial = $datos['estado_pedido_id'] ?? 1;

        $stmt->execute([
            ':uid'             => $datos['usuario_id'],
            ':tipo_cliente'    => $tipo_cliente_id,
            ':rut'             => $datos['rut_cliente'],
            ':nombre'          => $datos['nombre_cliente'],
            ':suc'             => $datos['sucursal_codigo'],
            ':vend'            => $vendedor_codigo,
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
            ':tipo_entrega'    => $datos['tipo_entrega_id'],
            ':lat'             => $lat,
            ':lng'             => $lng,
            ':trans_id'        => $id_transportista,
            ':nombre_dest'     => $datos['nombre_destinatario'],
            ':tel_cont'        => $datos['telefono_contacto'],
            ':tel_cont_2'      => $datos['telefono_contacto_2'] ?? null,
            ':fecha_ent'       => $fechaEntrega, // Ajustada si es inmediata
            ':rango_id'        => $rangoHorarioId, // Ajustada si es inmediata
            ':fecha_c'         => $fecha_c,
            ':hora_c'          => $hora_c
        ]);

        $idPedido = $this->db->lastInsertId();

        // 🚀 Generar Tracking
        $sucursal = str_pad($datos['sucursal_codigo'], 2, '0', STR_PAD_LEFT);
        $fechaTk = date('ymd');
        $idAcolchado = str_pad($idPedido, 8, '0', STR_PAD_LEFT);
        $seguimiento = $sucursal . $fechaTk . $idAcolchado;

        $this->db->prepare("UPDATE pedidos SET numero_seguimiento = ? WHERE id = ?")->execute([$seguimiento, $idPedido]);

        $this->registrarHistorial($idPedido, $estadoInicial, 'Pedido recibido. Seguimiento: ' . $seguimiento);

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
    // 🛡️ LÓGICA DE STOCK RESERVADO
    // =========================================================
    public function reservarStock($pedido_id)
    {
        $sql = "SELECT dp.cod_producto, dp.cantidad, p.sucursal_codigo 
            FROM pedidos_detalle dp
            JOIN pedidos p ON dp.pedido_id = p.id
            WHERE dp.pedido_id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles as $det) {
            $sqlUpdate = "UPDATE productos_sucursales 
                      SET stock_reservado = stock_reservado + :cant 
                      WHERE cod_producto = :cod 
                      AND sucursal_id = :sucursal";

            $stmtUp = $this->db->prepare($sqlUpdate);
            $stmtUp->execute([
                ':cant' => $det['cantidad'],
                ':cod'  => $det['cod_producto'],
                ':sucursal' => $det['sucursal_codigo']
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
    // 2. MÉTODOS DE LECTURA 
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
            $pedido = $this->obtenerPorId($pedidoId);
            $sucursalId = $pedido['sucursal_codigo'];

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
                       -- Ahora usamos directamente la nueva columna p.nombre_cliente
                       COALESCE(u.nombre, p.nombre_cliente, p.nombre_destinatario, 'Cliente Invitado') as nombre_cliente, 
                       COALESCE(u.email, 'Sin correo (Invitado)') as email_cliente, 
                       COALESCE(ut.numero, p.telefono_contacto, 'Sin teléfono') as telefono_cliente,
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

        $estados_que_liberan_stock = [4, 5];

        if (in_array($nuevo_estado, $estados_que_liberan_stock)) {
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
        $fechaInicioInt = (int) str_replace('-', '', $fechaInicio);
        $fechaFinInt = (int) str_replace('-', '', $fechaFin);
        $buscandoId = (is_numeric($busqueda) && intval($busqueda) > 0);

        $sql = "SELECT p.*, 
                       -- También actualizada aquí
                       COALESCE(u.nombre, p.nombre_cliente, p.nombre_destinatario, 'Cliente Invitado') as nombre_cliente, 
                       COALESCE(u.email, 'Sin correo (Invitado)') as email_cliente, 
                       COALESCE(u.rut, p.rut_cliente, 'Sin RUT') as rut_cliente,
                       COALESCE(ep.nombre, 'pendiente') as estado,
                       COALESCE(ep.badge_class, 'secondary') as color_estado,
                       COALESCE(rh.nombre, 'Por definir') as rango_horario,
                       DATE_FORMAT(STR_TO_DATE(CAST(p.fecha_creacion AS CHAR), '%Y%m%d'), '%d/%m/%Y') as fecha_entrega_fmt
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                LEFT JOIN rangos_horarios rh ON p.rango_horario_id = rh.id
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

        $sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limite', (int)$limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerDetallesConEdiciones($idPedido, $sucursalId)
    {
        // 1. OBTENER PRODUCTOS VIGENTES
        $sqlActuales = "SELECT pd.id, pd.producto_id, p.cod_producto, p.nombre as nombre_producto, p.imagen, 
                               pd.cantidad, pd.precio_bruto, pd.precio_neto,
                               COALESCE(ps.stock, 0) as stock_sucursal,
                               (SELECT COUNT(*) FROM pedidos_ediciones_detalle ped 
                                JOIN pedidos_ediciones pe ON ped.edicion_id = pe.id 
                                WHERE pe.pedido_id = pd.pedido_id AND ped.producto_id = pd.producto_id AND ped.accion = 'agregado') as es_agregado,
                               0 as es_eliminado
                        FROM pedidos_detalle pd 
                        JOIN productos p ON pd.producto_id = p.id
                        LEFT JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto AND ps.sucursal_id = :sucursal
                        WHERE pd.pedido_id = :pedido_id";

        $stmtActuales = $this->db->prepare($sqlActuales);
        $stmtActuales->execute([':pedido_id' => $idPedido, ':sucursal' => $sucursalId]);
        $productosActuales = $stmtActuales->fetchAll(\PDO::FETCH_ASSOC);

        // 2. OBTENER PRODUCTOS ELIMINADOS
        // 🔥 MAGIA AQUÍ: Usamos COLLATE para que los dialectos coincidan y no de error
        $sqlEliminados = "SELECT 0 as id, ped.producto_id, ped.cod_producto, ped.nombre_producto, p.imagen,
                                 ped.cantidad, ped.precio_bruto as precio_bruto, ROUND(ped.precio_bruto / 1.19) as precio_neto,
                                 COALESCE(ps.stock, 0) as stock_sucursal,
                                 0 as es_agregado,
                                 1 as es_eliminado
                          FROM pedidos_ediciones_detalle ped
                          JOIN pedidos_ediciones pe ON ped.edicion_id = pe.id
                          LEFT JOIN productos p ON ped.producto_id = p.id
                          LEFT JOIN productos_sucursales ps ON ped.cod_producto COLLATE utf8mb4_unicode_ci = ps.cod_producto COLLATE utf8mb4_unicode_ci AND ps.sucursal_id = :sucursal
                          WHERE pe.pedido_id = :pedido_id AND ped.accion = 'eliminado'";

        $stmtEliminados = $this->db->prepare($sqlEliminados);
        $stmtEliminados->execute([':pedido_id' => $idPedido, ':sucursal' => $sucursalId]);
        $productosEliminados = $stmtEliminados->fetchAll(\PDO::FETCH_ASSOC);

        // 3. UNIR AMBOS ARREGLOS
        return array_merge($productosActuales, $productosEliminados);
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
    public function aplicarEdicion($pedido_id, $admin_id, $motivo, $itemsEliminados, $itemsAgregados, $itemsFinales, $rutaEvidencia, $nuevoTotal, $nuevoSubsidio)
    {
        $this->db->beginTransaction();
        try {
            $pedidoPre = $this->obtenerPorId($pedido_id);
            $montoOriginalBD = (int)$pedidoPre['monto_total'];

            // 1. RECONSTRUCCIÓN: Borrar detalle actual e insertar la lista final limpia
            $this->db->prepare("DELETE FROM pedidos_detalle WHERE pedido_id = ?")->execute([$pedido_id]);
            $sqlIns = "INSERT INTO pedidos_detalle (pedido_id, producto_id, cod_producto, unidad_medida, cantidad, precio_neto, precio_bruto) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtIns = $this->db->prepare($sqlIns);

            foreach ($itemsFinales as $item) {
                $stmtIns->execute([
                    $pedido_id,
                    $item['producto_id'],
                    $item['cod_producto'],
                    $item['unidad_medida'],
                    $item['cantidad'],
                    round($item['precio_bruto'] / 1.19),
                    $item['precio_bruto']
                ]);
            }

            // 2. ACTUALIZAR CABECERA
            $this->db->prepare("UPDATE pedidos SET monto_total = ?, subsidio_empresa = ? WHERE id = ?")
                ->execute([$nuevoTotal, $nuevoSubsidio, $pedido_id]);

            // 3. REGISTRAR HISTORIAL (pedidos_ediciones y pedidos_ediciones_detalle)
            $sqlAudit = "INSERT INTO pedidos_ediciones (pedido_id, admin_id, motivo_cambio, monto_original, monto_nuevo, subsidio_generado, evidencia_imagen) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->prepare($sqlAudit)->execute([$pedido_id, $admin_id, $motivo, $montoOriginalBD, $nuevoTotal, $nuevoSubsidio, $rutaEvidencia]);
            $edicion_id = $this->db->lastInsertId();

            $sqlLog = "INSERT INTO pedidos_ediciones_detalle (edicion_id, accion, producto_id, cod_producto, nombre_producto, cantidad, precio_bruto) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtLog = $this->db->prepare($sqlLog);

            foreach ($itemsEliminados as $it) {
                $stmtLog->execute([$edicion_id, 'eliminado', $it['producto_id'], $it['cod_producto'], $it['nombre_producto'], $it['cantidad'], $it['precio_bruto']]);
            }
            foreach ($itemsAgregados as $it) {
                $stmtLog->execute([$edicion_id, 'agregado', $it['producto_id'], $it['cod_producto'], $it['nombre_producto'], $it['cantidad'], $it['precio_bruto']]);
            }

            $this->db->commit();
            return ['status' => true];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // NUEVA FUNCIÓN PARA LA VISTA
    public function obtenerDetallesConAuditoria($pedido_id)
    {
        // Obtenemos productos actuales + productos eliminados en la última edición
        $sql = "SELECT producto_id, cod_producto, nombre_producto as nombre, cantidad, precio_bruto, 0 as es_eliminado, 
            (SELECT COUNT(*) FROM pedidos_ediciones_detalle ped 
             JOIN pedidos_ediciones pe ON ped.edicion_id = pe.id 
             WHERE pe.pedido_id = :p1 AND ped.cod_producto = pd.cod_producto AND ped.accion = 'agregado' 
             ORDER BY pe.id DESC LIMIT 1) as es_agregado
            FROM pedidos_detalle pd
            JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = :p2
            UNION
            SELECT ped.producto_id, ped.cod_producto, ped.nombre_producto as nombre, ped.cantidad, ped.precio_bruto, 1 as es_eliminado, 0 as es_agregado
            FROM pedidos_ediciones_detalle ped
            JOIN pedidos_ediciones pe ON ped.edicion_id = pe.id
            WHERE pe.pedido_id = :p3 AND ped.accion = 'eliminado'
            AND pe.id = (SELECT MAX(id) FROM pedidos_ediciones WHERE pedido_id = :p4)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p1' => $pedido_id, ':p2' => $pedido_id, ':p3' => $pedido_id, ':p4' => $pedido_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function guardarComprobante($pedidoId, $nombreArchivo, $medioPago, $folio)
    {
        // Preparamos la consulta para actualizar la orden
        // Usaremos "numero_factura" para guardar el folio del documento
        $sql = "UPDATE pedidos 
                SET comprobante_pago = ?, 
                    metodo_pago_real = ?, 
                    numero_factura = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        // Ejecutamos la consulta pasándole los parámetros
        $resultado = $stmt->execute([
            $nombreArchivo,
            $medioPago,
            $folio,
            $pedidoId
        ]);

        return $resultado;
    }
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

    public function anularYDevolverStock($pedido_id, $admin_id, $motivo)
    {
        $this->db->beginTransaction();
        try {
            $stmtUpdate = $this->db->prepare("UPDATE pedidos SET estado_pedido_id = 6 WHERE id = ?");
            $stmtUpdate->execute([$pedido_id]);

            $pedido = $this->obtenerPorId($pedido_id);
            $sucursal_id = $pedido['sucursal_codigo'];

            $detalles = $this->obtenerDetalleProductos($pedido_id);

            $stmtStock = $this->db->prepare("UPDATE productos_sucursales SET stock = stock + ? WHERE cod_producto = ? AND sucursal_id = ?");
            foreach ($detalles as $d) {
                $stmtStock->execute([$d['cantidad'], $d['cod_producto'], $sucursal_id]);
            }

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

        $sqlVenta = "SELECT COALESCE(SUM(monto_total), 0) as total_general, 
                            COALESCE(SUM(costo_envio), 0) as total_despacho
                     FROM pedidos 
                     WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal 
                     AND DATE(fecha_creacion) BETWEEN :desde AND :hasta";
        $stmtVenta = $this->db->prepare($sqlVenta);
        $stmtVenta->execute($params);
        $rowVenta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

        $sqlPend = "SELECT COUNT(*) FROM pedidos WHERE estado_pedido_id = 1" .
            (!empty($sucursalAsignada) ? " AND sucursal_codigo = :sucursal" : "");
        $paramsPend = !empty($sucursalAsignada) ? [':sucursal' => strval($sucursalAsignada)] : [];
        $stmtPend = $this->db->prepare($sqlPend);
        $stmtPend->execute($paramsPend);
        $pendientes = $stmtPend->fetchColumn();

        $sqlGrafico = "SELECT DATE_FORMAT(fecha_creacion, '%d/%m') as fecha, SUM(monto_total) as total 
                       FROM pedidos 
                       WHERE estado_pedido_id NOT IN (1, 6) $filtroSucursal 
                       AND DATE(fecha_creacion) BETWEEN :desde AND :hasta 
                       GROUP BY DATE(fecha_creacion) ORDER BY fecha_creacion ASC";
        $stmtGraf = $this->db->prepare($sqlGrafico);
        $stmtGraf->execute($params);
        $datosGrafico = $stmtGraf->fetchAll(PDO::FETCH_ASSOC);

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
            'venta_periodo'    => (float)$rowVenta['total_general'],
            'ingreso_despacho' => (float)$rowVenta['total_despacho'],
            'pendientes'       => $pendientes,
            'datos_grafico'    => $datosGrafico,
            'top_productos'    => $topProductos
        ];
    }

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

    public function calcularSubtotalActual($pedidoId)
    {
        $sql = "SELECT COALESCE(SUM(precio_bruto * cantidad), 0) 
                FROM pedidos_detalle 
                WHERE pedido_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedidoId]);
        return (int)$stmt->fetchColumn();
    }
}
