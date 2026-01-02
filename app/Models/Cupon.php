<?php
namespace App\Models;

use PDO;

class Cupon {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function validar($codigo) {
        $sql = "SELECT * FROM cupones WHERE codigo = :codigo AND activo = 1 AND (fecha_expiracion >= CURDATE() OR fecha_expiracion IS NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        return $stmt->fetch();
    }
}