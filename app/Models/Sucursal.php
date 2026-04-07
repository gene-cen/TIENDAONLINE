<?php

namespace App\Models;

use PDO;

class Sucursal
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    // Método para listados generales (Admin, etc)
    public function obtenerTodas()
    {
        $sql = "SELECT s.*, 
                   c.nombre AS nombre_comuna, 
                   r.nombre AS nombre_region,
                   e.nombre AS nombre_encargado
            FROM sucursales s
            LEFT JOIN comunas c ON s.comuna_id = c.id
            LEFT JOIN provincias p ON c.provincia_id = p.id
            LEFT JOIN regiones r ON p.region_id = r.id
            LEFT JOIN encargados e ON s.encargado_id = e.id
            WHERE s.activo = 1 
              AND s.nombre IS NOT NULL -- Evita traer locales sin nombre
              AND s.nombre != ''       -- Evita traer locales vacíos
            GROUP BY s.id              -- Evita que se repitan por las comunas
            ORDER BY s.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function obtenerParaRetiro()
    {
        // Seleccionamos TODOS los campos necesarios para el mapa y la card de detalle.
        // Hacemos JOIN con comunas para obtener 'nombre_comuna' (vital para el filtro JS).

        $sql = "SELECT s.id, 
                       s.codigo_erp, 
                       s.nombre, 
                       s.direccion, 
                       s.latitud, 
                       s.longitud, 
                       s.horario, 
                       s.fono, 
                       s.imagen,
                       c.nombre AS nombre_comuna
                FROM sucursales s
                LEFT JOIN comunas c ON s.comuna_id = c.id
                WHERE s.id IN (1, 2)  -- Mantenemos tu filtro actual (Prat y V. Alemana)
                AND s.activo = 1 
                ORDER BY c.nombre ASC"; // Ordenamos por Comuna para que se vea ordenado

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
