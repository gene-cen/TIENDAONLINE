<?php

namespace App\Models;

use PDO;

class Categoria
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Obtener todas las categorías (Para el menú lateral)
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
        $stmt->execute();
        // Devolvemos como array asociativo para que funcione con $cat['nombre']
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una categoría por ID (Para saber el nombre en la vista de productos)
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}