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

        // 1. INSERTAR EL PEDIDO (Actualizado con telefono_contacto_2)
        $sql = "INSERT INTO pedidos 
                (usuario_id, rut_cliente, sucursal_codigo, vendedor_codigo, 
                 total_neto, monto_total, costo_envio, direccion_entrega_texto, comuna_id, 
                 estado_pedido_id, forma_pago_id, cantidad_items, cantidad_total_productos, 
                 numero_seguimiento, tipo_entrega_id, latitud, longitud, 
                 nombre_destinatario, telefono_contacto, telefono_contacto_2, fecha_entrega_estimada, rango_horario_id,
                 fecha_creacion, hora_creacion) 
                VALUES 
                (:uid, :rut, :suc, :vend, 
                 :neto, :total, :costo, :dir, :comuna, 
                 :estado_id, :fpago_id, :cant_items, :cant_prod, 
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
            ':fpago_id'    => $datos['forma_pago_id'] ?? 3,
            ':cant_items'  => $datos['cantidad_items'],
            ':cant_prod'   => $datos['cantidad_total_productos'],
            ':tracking'    => $seguimiento,
            ':tipo_entrega' => $datos['tipo_entrega_id'],
            ':lat'         => $datos['latitud'] ?? null,
            ':lng'         => $datos['longitud'] ?? null,
            ':nombre_dest' => $datos['nombre_destinatario'],
            ':tel_cont'    => $datos['telefono_contacto'],
            ':tel_cont_2'  => $datos['telefono_contacto_2'] ?? null, // <--- AQUÍ EL NUEVO CAMPO
            ':fecha_ent'   => $datos['fecha_entrega_estimada'],
            ':rango_id'    => $datos['rango_horario_id']
        ]);

        $idPedido = $this->db->lastInsertId();

        $this->registrarHistorial($idPedido, $estadoInicial, 'Pedido recibido exitosamente');

        return ['id' => $idPedido, 'tracking' => $seguimiento];
    }
    public function agregarDetalle($pedido_id, $producto, $cantidad, $precios)
    {
        // Este método sigue igual, asumiendo que la tabla 'pedidos_detalle' existe
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

    // ==========================================
    // 2. MÉTODOS DE LECTURA (Dashboard / Mis Pedidos)
    // ==========================================


    public function obtenerTodos()
    {
        // JOIN simple para obtener datos básicos
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
                       ut.numero as telefono_cliente, /* <--- AHORA LEE DE LA NUEVA TABLA */
                       u.rut as rut_cliente,
                       c.nombre as nombre_comuna,
                       ep_pago.nombre as nombre_estado_pago,
                       ep_pedido.nombre as nombre_estado_pedido,
                       ep_pedido.badge_class as badge_estado
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN usuario_telefonos ut ON u.id = ut.usuario_id AND ut.es_principal = 1 /* <--- CONEXIÓN A LA TABLA */
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
        // Asumiendo que $nuevo_estado es el ID
        $sql = "UPDATE pedidos SET estado_pedido_id = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $nuevo_estado, ':id' => $id]);
    }
    // Lista para el <select> del Admin
    public function obtenerEstadosPosibles()
    {
        $sql = "SELECT * FROM estados_pedido ORDER BY id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // En App/Models/Pedido.php

    // 1. Obtener todos los pedidos de un usuario
    // En App/Models/Pedido.php

    public function obtenerPorUsuario($usuario_id)
    {
        // JOIN con rangos_horarios para mostrar el texto "09:00 - 11:00"
        // JOIN con estados_pedido para el color
        $sql = "SELECT 
                    p.id, 
                    p.monto_total as total, 
                    p.numero_seguimiento,
                    DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y') as fecha_compra,
                    
                    -- DATOS DE ENTREGA
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

    // 2. Obtener los productos de un pedido específico (para el modal)
    public function obtenerDetalleProductos($pedido_id)
    {
        // Tu tabla se llama 'pedidos_detalle', no 'detalle_pedidos'
        $sql = "SELECT dp.*, p.nombre, p.imagen, p.cod_producto, dp.precio_bruto as precio_unitario
                FROM pedidos_detalle dp 
                INNER JOIN productos p ON dp.producto_id = p.id 
                WHERE dp.pedido_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    // En App/Models/Pedido.php -> obtenerFiltrados

    public function obtenerFiltrados($fechaInicio, $fechaFin, $busqueda = '', $estado = '')
    {
        // Agregamos los JOINS para traer nombre del rango y formateamos la fecha
        $sql = "SELECT p.*, 
                       u.nombre as nombre_cliente, 
                       u.email as email_cliente, 
                       u.rut as rut_cliente,
                       COALESCE(ep.nombre, 'pendiente') as estado,
                       COALESCE(ep.badge_class, 'secondary') as color_estado,
                       
                       -- DATOS LOGÍSTICOS
                       COALESCE(rh.nombre, 'Por definir') as rango_horario,
                       DATE_FORMAT(p.fecha_entrega_estimada, '%d/%m/%Y') as fecha_entrega_fmt
                       
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN estados_pedido ep ON p.estado_pedido_id = ep.id
                LEFT JOIN rangos_horarios rh ON p.rango_horario_id = rh.id  /* <--- JOIN NUEVO */
                WHERE DATE(p.fecha_creacion) BETWEEN :inicio AND :fin";

        $params = [
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin
        ];

        if (!empty($busqueda)) {
            // Tu lógica de limpieza de RUT se mantiene
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);

            $sql .= " AND (
                        u.nombre LIKE :q1 
                        OR u.email LIKE :q2 
                        OR REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q3 
                        OR p.id LIKE :q4
                        OR p.numero_seguimiento LIKE :q5 /* <--- Agregamos búsqueda por tracking */
                    )";

            $term = "%$busqueda%";
            $termClean = "%$busquedaLimpia%";

            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $termClean;
            $params[':q4'] = $term;
            $params[':q5'] = $term;
        }

        if (!empty($estado)) {
            $sql .= " AND ep.nombre = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // En App/Models/Pedido.php

    // 1. GUARDAR EN LA BITÁCORA (Para el Admin)
    public function registrarHistorial($pedidoId, $estadoId, $comentario = '')
    {
        $sql = "INSERT INTO historial_pedidos (pedido_id, estado_pedido_id, comentario, fecha_creacion) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$pedidoId, $estadoId, $comentario]);
    }

    // --- PEGAR ESTO AL FINAL DE App/Models/Pedido.php ---

    // Obtener historial para la línea de tiempo
    public function obtenerHistorial($pedidoId)
    {
        // Verifica si la tabla existe o manéjalo con try/catch si prefieres
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
            return []; // Si falla (ej: tabla no existe), retorna array vacío para no romper el JSON
        }
    }
}
