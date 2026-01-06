<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

use App\Models\Producto;

class HomeController {
    private $db;
    private $productoModel;

    public function __construct($db) {
        $this->db = $db;
        $this->productoModel = new Producto($db);
    }

    public function categoria($id) {
        // 1. Configuración de Paginación
        $por_pagina = 25; 
        $pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;
        
        $offset = ($pagina_actual - 1) * $por_pagina;

        // 2. Obtener Productos
        $productos = $this->productoModel->obtenerPorCategoria($id, $por_pagina, $offset);
        $total_registros = $this->productoModel->contarPorCategoria($id);
        $total_paginas = ceil($total_registros / $por_pagina);

        // 3. Obtener nombre de la categoría (Consulta rápida)
        // Ajusta "categorias" al nombre real de tu tabla si es diferente
        $stmt = $this->db->prepare("SELECT nombre FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch(\PDO::FETCH_OBJ);
        $nombre_categoria = $cat ? $cat->nombre : 'Categoría';

        // 4. Cargar la Vista
        ob_start();
        // Ajusta la ruta si tu carpeta se llama 'shop' o 'tienda'
        require_once __DIR__ . '/../../views/shop/categoria.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }
}