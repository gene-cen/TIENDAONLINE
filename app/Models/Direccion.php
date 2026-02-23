<?php

namespace App\Models;

use PDO;

class Direccion
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // LECTURA
    // =========================================================================

    public function obtenerPorUsuario($usuario_id)
    {
        // Traemos datos geográficos haciendo JOIN (Comuna -> Provincia -> Región)
        $sql = "SELECT d.*, 
                       c.nombre as nombre_comuna, 
                       r.nombre as nombre_region, 
                       p.nombre as nombre_provincia
                FROM direcciones_usuarios d
                JOIN comunas c ON d.comuna_id = c.id
                JOIN provincias p ON c.provincia_id = p.id
                JOIN regiones r ON p.region_id = r.id
                WHERE d.usuario_id = :uid 
                AND d.activo = 1  /* Solo direcciones visibles */
                ORDER BY d.es_principal DESC, d.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPorId($id, $usuario_id)
    {
        $sql = "SELECT * FROM direcciones_usuarios 
                WHERE id = :id AND usuario_id = :uid AND activo = 1 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':uid' => $usuario_id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // =========================================================================
    // ESCRITURA (CRUD)
    // =========================================================================

    public function crear($datos)
    {
        // 1. VALIDACIÓN ANTI-DUPLICADOS (Solo activas)
        $sqlDup = "SELECT count(*) FROM direcciones_usuarios 
                   WHERE usuario_id = :uid 
                   AND direccion = :dir 
                   AND comuna_id = :comuna
                   AND activo = 1";
        
        $stmtDup = $this->db->prepare($sqlDup);
        $stmtDup->execute([
            ':uid'    => $datos['usuario_id'],
            ':dir'    => $datos['direccion'],
            ':comuna' => $datos['comuna_id']
        ]);

        if ($stmtDup->fetchColumn() > 0) {
            return false; // Ya existe
        }

        // 2. VERIFICAR SI DEBE SER PRINCIPAL (Si es la primera, gana la corona)
        $sqlCheck = "SELECT count(*) FROM direcciones_usuarios WHERE usuario_id = ? AND activo = 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([$datos['usuario_id']]);
        
        $esPrincipal = ($stmtCheck->fetchColumn() == 0) ? 1 : 0;

        // 3. INSERTAR
        $sql = "INSERT INTO direcciones_usuarios 
                (usuario_id, nombre_etiqueta, direccion, comuna_id, latitud, longitud, es_principal, activo) 
                VALUES 
                (:uid, :tag, :dir, :comuna, :lat, :lng, :principal, 1)";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':uid'       => $datos['usuario_id'],
            ':tag'       => $datos['nombre_etiqueta'],
            ':dir'       => $datos['direccion'],
            ':comuna'    => $datos['comuna_id'],
            ':lat'       => $datos['latitud'] ?? null,
            ':lng'       => $datos['longitud'] ?? null,
            ':principal' => $esPrincipal
        ]);
    }

    public function actualizar($id, $datos)
    {
        $sql = "UPDATE direcciones_usuarios SET 
                nombre_etiqueta = :tag, 
                direccion = :dir, 
                comuna_id = :comuna, 
                latitud = :lat, 
                longitud = :lng 
                WHERE id = :id AND usuario_id = :uid";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':tag'    => $datos['nombre_etiqueta'],
            ':dir'    => $datos['direccion'],
            ':comuna' => $datos['comuna_id'],
            ':lat'    => $datos['latitud'],
            ':lng'    => $datos['longitud'],
            ':id'     => $id,
            ':uid'    => $datos['usuario_id']
        ]);
    }

    public function eliminar($id, $usuario_id)
    {
        // BORRADO LÓGICO (Soft Delete)
        $sql = "UPDATE direcciones_usuarios SET activo = 0 WHERE id = :id AND usuario_id = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':uid' => $usuario_id]);
    }

    // =========================================================================
    // LÓGICA DE NEGOCIO
    // =========================================================================

    // Fijar como Principal (Transacción para evitar conflictos)
    public function fijarPrincipal($id, $usuario_id)
    {
        try {
            $this->db->beginTransaction();

            // 1. Quitar corona a todas
            $sqlReset = "UPDATE direcciones_usuarios SET es_principal = 0 WHERE usuario_id = :uid";
            $stmt = $this->db->prepare($sqlReset);
            $stmt->execute([':uid' => $usuario_id]);

            // 2. Dar corona a la elegida
            $sqlSet = "UPDATE direcciones_usuarios SET es_principal = 1 WHERE id = :id AND usuario_id = :uid";
            $stmt = $this->db->prepare($sqlSet);
            $stmt->execute([':id' => $id, ':uid' => $usuario_id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}