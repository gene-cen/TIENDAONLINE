<?php

namespace App\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Sucursal;

class HomeController
{
    private $db;
    private $productoModel;
    private $categoriaModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->productoModel = new Producto($db);
        $this->categoriaModel = new Categoria($db);
    }

    /**
     * MÉTODO PRIVADO AUXILIAR
     * Carga las categorías para el Sidebar en main.php
     * Evita repetir código y asegura que el menú siempre funcione.
     */
    private function cargarCategorias() {
        $categorias = [];
        try {
            // Usamos la tabla 'web_categorias' y ordenamos por nombre
            $stmt = $this->db->query("SELECT * FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC");
            if ($stmt) {
                $categorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            // Log de error silencioso para no romper la web
            error_log("Error cargando categorías sidebar: " . $e->getMessage());
        }
        return $categorias;
    }

    // =========================================================
    // 1. PORTADA (HOME)
    // =========================================================
   // =========================================================
    // 1. PORTADA (HOME) - ACTUALIZADO CON OFERTAS
    // =========================================================
    public function index()
    {
        // A. CATEGORÍAS (Sidebar)
        $categorias = $this->cargarCategorias();

        // B. MARCAS
        $stmtMarcas = $this->db->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC");
        $marcas = $stmtMarcas->fetchAll(\PDO::FETCH_ASSOC);

        // C. NOVEDADES (5 últimos)
        $sqlNovedades = "SELECT p.id, p.cod_producto, p.precio, p.stock, p.imagen, 
                                piw.nombre_web, 
                                m.nombre as marca, 
                                wc.nombre as categoria 
                         FROM productos p 
                         INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                         LEFT JOIN marcas m ON piw.marca_id = m.id
                         LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                         WHERE p.activo = 1 
                         ORDER BY p.id DESC LIMIT 5";
        $stmtNovedades = $this->db->query($sqlNovedades);
        $productos = $stmtNovedades->fetchAll(\PDO::FETCH_ASSOC);

        // D. OFERTAS EXCLUSIVAS (NUEVA SECCIÓN)
        // Traemos 4 productos aleatorios para simular ofertas
        $sqlOfertas = "SELECT p.id, p.cod_producto, p.precio, p.stock, p.imagen, 
                              piw.nombre_web, m.nombre as marca 
                       FROM productos p 
                       INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                       LEFT JOIN marcas m ON piw.marca_id = m.id
                       WHERE p.activo = 1 
                       ORDER BY RAND() LIMIT 4";
        $stmtOfertas = $this->db->query($sqlOfertas);
        $ofertas = $stmtOfertas->fetchAll(\PDO::FETCH_ASSOC);

        // E. MÁS VENDIDOS (Aleatorio 5)
        $sqlMasVendidos = "SELECT p.id, p.cod_producto, p.precio, p.stock, p.imagen, 
                                  piw.nombre_web, m.nombre as marca 
                           FROM productos p 
                           INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                           LEFT JOIN marcas m ON piw.marca_id = m.id
                           WHERE p.activo = 1 
                           ORDER BY RAND() LIMIT 5";
        $stmtMasVendidos = $this->db->query($sqlMasVendidos);
        $masVendidos = $stmtMasVendidos->fetchAll(\PDO::FETCH_ASSOC);

        // --- CARGAR VISTAS ---
        ob_start();
        require __DIR__ . '/../../views/home/home.php';
        $content = ob_get_clean();

        require __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 2. BUSCADOR
    // =========================================================
    public function buscar()
    {
        $q = $_GET['q'] ?? '';
        header("Location: " . BASE_URL . "home/catalogo?q=" . urlencode($q));
        exit;
    }

    // =========================================================
    // 3. CATÁLOGO GENERAL (CON MARCAS DINÁMICAS)
    // =========================================================
    public function catalogo()
    {
        // 1. PRIMERO CAPTURAMOS LOS FILTROS (GET)
        // (Movemos esto al principio para usarlos en las consultas de filtros laterales)
        $catFilter   = $_GET['categoria'] ?? null;
        $marcaFilter = $_GET['marca'] ?? null;
        $busqueda    = $_GET['q'] ?? null;
        $minPrecio   = $_GET['min_price'] ?? null; // Se define más abajo el default
        $maxPrecio   = $_GET['max_price'] ?? null;
        $orden       = $_GET['orden'] ?? 'relevancia';

        // 2. CARGA DE DATOS PARA FILTROS LATERALES (DINÁMICO)
        $categorias = [];
        $marcasList = [];
        $rangoPrecio = ['min' => 0, 'max' => 1000000];

        try {
            // A. CATEGORÍAS (Siempre mostramos todas para permitir navegar)
            $stmtCat = $this->db->query("SELECT * FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC");
            if ($stmtCat) $categorias = $stmtCat->fetchAll(\PDO::FETCH_ASSOC);

            // B. MARCAS (FILTRADAS POR CATEGORÍA ACTUAL)
            // Aquí está la magia: Hacemos un JOIN con la categoría para filtrar
            $sqlMarcas = "SELECT DISTINCT m.nombre 
                          FROM marcas m 
                          INNER JOIN productos_info_web piw ON m.id = piw.marca_id 
                          INNER JOIN productos p ON piw.cod_producto = p.cod_producto 
                          LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                          WHERE p.activo = 1";
            
            $paramsMarcas = [];

            // Si hay una categoría seleccionada, SOLO cargamos marcas de esa categoría
            if ($catFilter) {
                $sqlMarcas .= " AND wc.nombre = ?";
                $paramsMarcas[] = $catFilter;
            }

            // Opcional: Si hay búsqueda, también filtramos marcas que coincidan con la búsqueda
            if ($busqueda) {
                $sqlMarcas .= " AND (piw.nombre_web LIKE ? OR p.nombre LIKE ?)";
                $paramsMarcas[] = "%$busqueda%";
                $paramsMarcas[] = "%$busqueda%";
            }

            $sqlMarcas .= " ORDER BY m.nombre ASC";

            $stmtMarcas = $this->db->prepare($sqlMarcas);
            $stmtMarcas->execute($paramsMarcas);
            $marcasList = $stmtMarcas->fetchAll(\PDO::FETCH_ASSOC);


            // C. RANGO DE PRECIOS GLOBAL (O filtrado por categoría si prefieres)
            // Dejamos global para que el slider siempre tenga sentido base
            $stmtPrecios = $this->db->query("SELECT MIN(precio) as min_p, MAX(precio) as max_p FROM productos WHERE activo = 1");
            $preciosDb = $stmtPrecios->fetch(\PDO::FETCH_ASSOC);
            if ($preciosDb) {
                $rangoPrecio['min'] = floor($preciosDb['min_p']);
                $rangoPrecio['max'] = ceil($preciosDb['max_p']);
            }

        } catch (\Exception $e) { 
            error_log("Error filtros catalogo: " . $e->getMessage());
        }

        // Definir defaults de precio si no vienen en GET
        if ($minPrecio === null) $minPrecio = $rangoPrecio['min'];
        if ($maxPrecio === null) $maxPrecio = $rangoPrecio['max'];

        // 3. PAGINACIÓN
        $por_pagina = 25; 
        $pagina     = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($pagina < 1) $pagina = 1;
        $offset = ($pagina - 1) * $por_pagina;

        // 4. CONSTRUCCIÓN SQL PRODUCTOS
        $sqlBase = "FROM productos p 
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                    LEFT JOIN marcas m ON piw.marca_id = m.id
                    LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                    WHERE p.activo = 1";
        
        $params = [];

        // Filtros SQL
        if ($catFilter) {
            $sqlBase .= " AND wc.nombre = ?";
            $params[] = $catFilter;
        }
        if ($marcaFilter) {
            $sqlBase .= " AND m.nombre = ?";
            $params[] = $marcaFilter;
        }
        if ($busqueda) {
            $sqlBase .= " AND (piw.nombre_web LIKE ? OR p.nombre LIKE ? OR m.nombre LIKE ?)";
            $termino = "%$busqueda%";
            $params[] = $termino; $params[] = $termino; $params[] = $termino;
        }
        // Filtro Precio
        $sqlBase .= " AND p.precio BETWEEN ? AND ?";
        $params[] = $minPrecio;
        $params[] = $maxPrecio;

        // 5. CONTEO TOTAL
        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = $stmtCount->fetchColumn();
        $total_paginas   = ceil($total_registros / $por_pagina);

        // 6. ORDENAMIENTO
        $sqlOrder = "";
        switch ($orden) {
            case 'precio_asc':  $sqlOrder = " ORDER BY p.precio ASC"; break;
            case 'precio_desc': $sqlOrder = " ORDER BY p.precio DESC"; break;
            case 'nombre_asc':  $sqlOrder = " ORDER BY piw.nombre_web ASC"; break;
            default:            $sqlOrder = " ORDER BY p.id DESC"; break;
        }

        // 7. OBTENER PRODUCTOS
        $sqlFinal = "SELECT p.*, m.nombre as marca, piw.nombre_web, wc.nombre as cat_web 
                     " . $sqlBase . $sqlOrder . " LIMIT $por_pagina OFFSET $offset";
                      
        $stmt = $this->db->prepare($sqlFinal);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 8. TÍTULO DINÁMICO
        $titulo = "Catálogo Completo";
        if ($catFilter) $titulo = $catFilter;
        if ($busqueda) $titulo = 'Resultados para: "' . htmlspecialchars($busqueda) . '"';

        // 9. RENDERIZADO
        ob_start();
        include __DIR__ . '/../../views/home/catalogo_view.php';
        $content = ob_get_clean();

        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 4. AUTOCOMPLETADO
    // =========================================================
    public function autocomplete()
    {
        header('Content-Type: application/json');
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }

        $sql = "SELECT DISTINCT nombre_web FROM productos_info_web WHERE nombre_web LIKE ? LIMIT 6";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    // =========================================================
    // 5. LOCALES Y HORARIOS
    // =========================================================
    public function locales()
    {
       require_once __DIR__ . '/../Models/Sucursal.php';
        $sucursalModel = new \App\Models\Sucursal($this->db);
        
        // Obtenemos la data lista para la vista
        $sucursales = $sucursalModel->obtenerTodas();
        // Título de la página
        $titulo = "Nuestras Sucursales";

        // 2. Cargar Categorías (Usamos función auxiliar)
        $categorias = $this->cargarCategorias();

        // 3. Renderizar
        ob_start();
        include __DIR__ . '/../../views/home/locales.php';
        $content = ob_get_clean();

        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 6. TÉRMINOS Y CONDICIONES
    // =========================================================
    public function terminos() {
        // 1. Cargar Categorías (Usamos función auxiliar)
        $categorias = $this->cargarCategorias();

        // 2. Renderizar vista
        $titulo = "Términos y Condiciones";
        
        ob_start();
        include __DIR__ . '/../../views/home/terminos.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 7. FICHA DE PRODUCTO (DETALLE)
    // =========================================================
    public function producto()
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header("Location: " . BASE_URL . "home/catalogo");
            exit;
        }

        // A. Obtener datos del producto principal
        $sql = "SELECT p.*, 
                       m.nombre as marca, 
                       piw.nombre_web, 
                       piw.subcategoria,
                       wc.nombre as categoria_web,
                       wc.id as cat_id
                FROM productos p 
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                LEFT JOIN marcas m ON piw.marca_id = m.id
                LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                WHERE p.id = ? AND p.activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$producto) {
            // Si no existe o está inactivo, volver al catálogo
            header("Location: " . BASE_URL . "home/catalogo");
            exit;
        }

        // B. Obtener Productos Relacionados (Misma categoría)
        $relacionados = [];
        if (!empty($producto['cat_id'])) {
            $sqlRel = "SELECT p.id, p.precio, p.stock, p.imagen, 
                              piw.nombre_web, m.nombre as marca
                       FROM productos p 
                       LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                       LEFT JOIN marcas m ON piw.marca_id = m.id
                       WHERE piw.web_categoria_id = ? 
                       AND p.id != ? 
                       AND p.activo = 1
                       ORDER BY RAND() LIMIT 4"; // Traemos 4 al azar
            
            $stmtRel = $this->db->prepare($sqlRel);
            $stmtRel->execute([$producto['cat_id'], $id]);
            $relacionados = $stmtRel->fetchAll(\PDO::FETCH_ASSOC);
        }

        // C. Cargar Categorías para el Sidebar (siempre necesario para main.php)
        $categorias = $this->cargarCategorias();

        // Renderizar
        $titulo = !empty($producto['nombre_web']) ? $producto['nombre_web'] : $producto['nombre'];
        
        ob_start();
        include __DIR__ . '/../../views/home/producto.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // En App/Controllers/HomeController.php
// En App/Controllers/HomeController.php

    public function catalogoVisual()
    {
        // Aumentamos tiempo y memoria límite por si son demasiados productos
        ini_set('memory_limit', '512M'); 
        set_time_limit(300); 

        $busqueda = $_GET['q'] ?? '';

        // Obtenemos TODO de una vez
        $productos = $this->productoModel->obtenerTodosPublicos($busqueda);
        $total_registros = count($productos);

        // Variables para la vista (ya no necesitamos $paginas ni $pagina_actual)
        $data = [
            'productos' => $productos,
            'busqueda' => $busqueda,
            'total_registros' => $total_registros
        ];

        ob_start();
        // Nota: Como no usas extract($data), asegúrate de que la vista use $productos, $busqueda, etc.
        // Al hacer include en el mismo ámbito, las variables locales ($productos) pasan.
        require_once __DIR__ . '/../../views/home/catalogo_chorizo.php';
        $content = ob_get_clean();
        
        require_once __DIR__ . '/../../views/layouts/main.php';
    }
}