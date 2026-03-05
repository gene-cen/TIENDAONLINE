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
            ':uid'         => $datos['usuario_id'],
            ':rut'         => $datos['rut_cliente'],
            ':suc'         => $datos['sucursal_codigo'],
            ':vend'        => $datos['vendedor_codigo'],
            ':neto'        => $datos['total_neto'],
            ':total'       => $datos['monto_total'],
            ':costo'       => $datos['costo_envio'] ?? 0,
            ':dir'         => $datos['direccion_entrega_texto'],
            ':comuna'      => $datos['comuna_id'],
            ':estado_id'   => $estadoInicial,
            ':estado_pago_id' => $datos['estado_pago_id'] ?? 1, // 1 = Pendiente
            ':fpago_id'    => $datos['forma_pago_id'] ?? 3,
            ':cant_items'  => $datos['cantidad_items'],
            ':cant_prod'   => $datos['cantidad_total_productos'],
            ':tracking'    => $seguimiento,
            ':tipo_entrega' => $datos['tipo_entrega_id'],
            ':lat'         => $datos['latitud'] ?? null,
            ':lng'         => $datos['longitud'] ?? null,
            ':nombre_dest' => $datos['nombre_destinatario'],
            ':tel_cont'    => $datos['telefono_contacto'],
            ':tel_cont_2'  => $datos['telefono_contacto_2'] ?? null,
            ':fecha_ent'   => $datos['fecha_entrega_estimada'],
            ':rango_id'    => $datos['rango_horario_id']
        ]);

        $idPedido = $this->db->lastInsertId();
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
        // 1. Obtenemos qué productos y qué sucursal tiene este pedido
        $sql = "SELECT dp.cod_producto, dp.cantidad, p.sucursal_codigo 
                FROM pedidos_detalle dp
                JOIN pedidos p ON dp.pedido_id = p.id
                WHERE dp.pedido_id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Sumamos a la columna stock_reservado en la tabla pivote
        foreach ($detalles as $det) {
            $sqlUpdate = "UPDATE productos_sucursales 
                          SET stock_reservado = stock_reservado + :cant 
                          WHERE cod_producto = :cod AND sucursal_id = :sucursal";
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

    public function obtenerPorId($id)
    {
        $sql = "SELECT p.*, 
                       u.nombre as nombre_cliente, 
                       u.email as email_cliente, 
                       ut.numero as telefono_cliente,
                       u.rut as rut_cliente,
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        $sql = "SELECT dp.*, p.nombre, p.imagen, p.cod_producto, dp.precio_bruto as precio_unitario
                FROM pedidos_detalle dp 
                INNER JOIN productos p ON dp.producto_id = p.id 
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
                       u.nombre as nombre_cliente, 
                       u.email as email_cliente, 
                       u.rut as rut_cliente,
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
}
