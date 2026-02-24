<?php
class CarruselBanner {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Obtener todos los banners para el administrador
    public function obtenerTodos() {
        $stmt = $this->db->prepare("SELECT * FROM carrusel_banners ORDER BY orden ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener solo los activos para mostrarlos en el Home (Front-end)
    public function obtenerActivos() {
        $stmt = $this->db->prepare("SELECT * FROM carrusel_banners WHERE estado_activo = 1 ORDER BY orden ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insertar un nuevo banner
    public function agregar($titulo, $ruta_imagen, $url_destino, $orden) {
        $stmt = $this->db->prepare("INSERT INTO carrusel_banners (titulo, ruta_imagen, url_destino, orden) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$titulo, $ruta_imagen, $url_destino, $orden]);
    }

    // Eliminar un banner
    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM carrusel_banners WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>