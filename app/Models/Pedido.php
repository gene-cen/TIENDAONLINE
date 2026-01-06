<?php
namespace App\Models;

use PDO;

class Pedido {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ==========================================
    // 1. MÉTODOS DE ESCRITURA (Tu código original)
    // ==========================================

    public function crear($datos) {
        $sql = "INSERT INTO pedidos 
                (usuario_id, sucursal_codigo, vendedor_codigo, rut_cliente, fecha_pedido, total_bruto, total_neto, direccion_envio, estado) 
                VALUES 
                (:usuario_id, :sucursal, :vendedor, :rut, :fecha, :total_bruto, :total_neto, :direccion, 'pendiente')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id'  => $datos['usuario_id'],
            ':sucursal'    => $datos['sucursal_codigo'],
            ':vendedor'    => $datos['vendedor_codigo'],
            ':rut'         => $datos['rut_cliente'],
            ':fecha'       => $datos['fecha_pedido'],
            ':total_bruto' => $datos['total_bruto'],
            ':total_neto'  => $datos['total_neto'],
            ':direccion'   => $datos['direccion_envio']
        ]);
        
        return $this->db->lastInsertId();
    }

    public function agregarDetalle($pedido_id, $producto, $cantidad, $precios) {
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
    // 2. MÉTODOS DE LECTURA (Nuevos para el Admin)
    // ==========================================

    // Para el Dashboard
    public function obtenerTodos() {
        // OJO: Hago un alias 'fecha_pedido as fecha_creacion' para que coincida con tu vista
        $sql = "SELECT p.*, p.fecha_pedido as fecha_creacion, 
                       u.nombre as nombre_cliente, u.rut as rut_cliente 
                FROM pedidos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                ORDER BY p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Para "Ver Detalle" (Cabecera)
    public function obtenerPorId($id) {
        $sql = "SELECT p.*, p.fecha_pedido as fecha_creacion,
                       u.nombre as nombre_cliente, u.email as email_cliente, u.rut as rut_cliente
                FROM pedidos p
                JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Para "Ver Detalle" (Lista de productos del pedido)
    public function obtenerDetalles($pedido_id) {
        // Traemos también el nombre del producto original si existe
        // Asumo que tienes una tabla 'productos', si no, quita el LEFT JOIN
        $sql = "SELECT pd.*, p.nombre as nombre_producto, p.imagen
                FROM pedidos_detalle pd
                LEFT JOIN productos p ON pd.producto_id = p.id
                WHERE pd.pedido_id = :pedido_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pedido_id', $pedido_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Actualizar el estado del pedido
    public function actualizarEstado($id, $nuevo_estado) {
        $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}