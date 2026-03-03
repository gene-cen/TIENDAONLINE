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

        // ASEGURAR QUE SIEMPRE HAYA UNA SUCURSAL ACTIVA
        if (!isset($_SESSION['sucursal_activa'])) {
            $_SESSION['sucursal_activa'] = 29; // La Calera por defecto
            $_SESSION['comuna_nombre'] = 'La Calera';
        }
    }

    private function cargarCategorias()
    {
        $categorias = [];
        try {
            $stmt = $this->db->query("SELECT * FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC");
            if ($stmt) {
                $categorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            error_log("Error cargando categorías sidebar: " . $e->getMessage());
        }
        return $categorias;
    }

    // =========================================================
    // 1. PORTADA (HOME) - SOLO PRODUCTOS CON STOCK Y PRECIO
    // =========================================================
    // =========================================================
    // 1. PORTADA (HOME) - SOLO PRODUCTOS CON STOCK Y PRECIO
    // =========================================================
    public function index()
    {
        $categorias = $this->cargarCategorias();
        $sucursal_id = $_SESSION['sucursal_activa'];

        // --- DETECTOR HÍBRIDO DE MOTOR DE BASE DE DATOS ---
        $motor = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $fnRand = ($motor === 'pgsql') ? 'RANDOM()' : 'RAND()';

        $stmtMarcas = $this->db->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC");
        $marcas = $stmtMarcas->fetchAll(\PDO::FETCH_ASSOC);

        // --- CORRECCIÓN: AGREGADO ps.stock > 0 A TODAS LAS CONSULTAS ---

        // C. NOVEDADES (Solo con stock > 0)
        $sqlNovedades = "SELECT p.id, p.cod_producto, ps.precio, ps.stock, p.imagen, 
                                 piw.nombre_web, m.nombre as marca, wc.nombre as categoria 
                         FROM productos p 
                         INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                         INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                         LEFT JOIN marcas m ON piw.marca_id = m.id
                         LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                         WHERE p.activo = 1 AND ps.sucursal_id = $sucursal_id 
                         AND ps.precio > 0 AND ps.stock > 0 
                         ORDER BY p.id DESC LIMIT 5";
        $productos = $this->db->query($sqlNovedades)->fetchAll(\PDO::FETCH_ASSOC);

        // D. OFERTAS EXCLUSIVAS (Solo con stock > 0)
        $sqlOfertas = "SELECT p.id, p.cod_producto, ps.precio, ps.stock, p.imagen, 
                              piw.nombre_web, m.nombre as marca 
                       FROM productos p 
                       INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                       INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                       LEFT JOIN marcas m ON piw.marca_id = m.id
                       WHERE p.activo = 1 AND ps.sucursal_id = $sucursal_id 
                       AND ps.precio > 0 AND ps.stock > 0 
                       ORDER BY " . $fnRand . " LIMIT 4";
        $ofertas = $this->db->query($sqlOfertas)->fetchAll(\PDO::FETCH_ASSOC);

        // E. MÁS VENDIDOS (Solo con stock > 0)
        $sqlMasVendidos = "SELECT p.id, p.cod_producto, ps.precio, ps.stock, p.imagen, 
                                 piw.nombre_web, m.nombre as marca 
                           FROM productos p 
                           INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                           INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                           LEFT JOIN marcas m ON piw.marca_id = m.id
                           WHERE p.activo = 1 AND ps.sucursal_id = $sucursal_id 
                           AND ps.precio > 0 AND ps.stock > 0 
                           ORDER BY " . $fnRand . " LIMIT 5";
        $masVendidos = $this->db->query($sqlMasVendidos)->fetchAll(\PDO::FETCH_ASSOC);

        $bannersHome = $this->db->query("SELECT * FROM carrusel_banners WHERE estado_activo = 1 ORDER BY orden ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $marcasHome = $this->db->query("SELECT * FROM marcas_destacadas WHERE estado_activo = 1 ORDER BY orden ASC LIMIT 8")->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        require __DIR__ . '/../../views/home/home.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/main.php';
    }

    public function buscar()
    {
        $q = $_GET['q'] ?? '';
        header("Location: " . BASE_URL . "home/catalogo?q=" . urlencode($q));
        exit;
    }

    // =========================================================
    // 3. CATÁLOGO GENERAL (CON FILTRO DE SUCURSAL Y STOCK)
    // =========================================================
    public function catalogo()
    {
        $sucursal_id = $_SESSION['sucursal_activa'];
        $catFilter   = $_GET['categoria'] ?? null;
        $marcaFilter = $_GET['marca'] ?? null;
        $busqueda    = $_GET['q'] ?? null;
        $minPrecio   = $_GET['min_price'] ?? null;
        $maxPrecio   = $_GET['max_price'] ?? null;
        $orden       = $_GET['orden'] ?? 'relevancia';

        $categorias = $this->cargarCategorias();
        $marcasList = [];
        $rangoPrecio = ['min' => 0, 'max' => 1000000];

        try {
            // Ajuste Marcas: Solo marcas que tengan productos con precio y stock en esta sucursal
            $sqlMarcas = "SELECT DISTINCT m.nombre 
                          FROM marcas m 
                          INNER JOIN productos_info_web piw ON m.id = piw.marca_id 
                          INNER JOIN productos_sucursales ps ON piw.cod_producto = ps.cod_producto
                          WHERE ps.sucursal_id = $sucursal_id AND ps.precio > 0 AND ps.stock > 0";

            $paramsMarcas = [];
            if ($catFilter) {
                $sqlMarcas .= " AND piw.web_categoria_id = (SELECT id FROM web_categorias WHERE nombre = ? LIMIT 1)";
                $paramsMarcas[] = $catFilter;
            }
            $marcasList = $this->db->prepare($sqlMarcas . " ORDER BY m.nombre ASC");
            $marcasList->execute($paramsMarcas);
            $marcasList = $marcasList->fetchAll(\PDO::FETCH_ASSOC);

            // Rango de precios dinámico por sucursal (solo productos con stock)
            $stmtPrecios = $this->db->prepare("SELECT MIN(precio) as min_p, MAX(precio) as max_p FROM productos_sucursales WHERE sucursal_id = ? AND precio > 0 AND stock > 0");
            $stmtPrecios->execute([$sucursal_id]);
            $preciosDb = $stmtPrecios->fetch(\PDO::FETCH_ASSOC);
            if ($preciosDb) {
                $rangoPrecio['min'] = floor($preciosDb['min_p']);
                $rangoPrecio['max'] = ceil($preciosDb['max_p']);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        if ($minPrecio === null) $minPrecio = $rangoPrecio['min'];
        if ($maxPrecio === null) $maxPrecio = $rangoPrecio['max'];

        $por_pagina = 25;
        $pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($pagina < 1) $pagina = 1;
        $offset = ($pagina - 1) * $por_pagina;

        // SQL BASE: Uniendo con productos_sucursales Y EXIGIENDO STOCK > 0
        $sqlBase = "FROM productos p 
                    INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                    LEFT JOIN marcas m ON piw.marca_id = m.id
                    LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                    WHERE p.activo = 1 AND ps.sucursal_id = $sucursal_id AND ps.precio > 0 AND ps.stock > 0";

        $params = [];
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
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }
        $sqlBase .= " AND ps.precio BETWEEN ? AND ?";
        $params[] = $minPrecio;
        $params[] = $maxPrecio;

        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = $stmtCount->fetchColumn();
        $total_paginas = ceil($total_registros / $por_pagina);

        $sqlOrder = match ($orden) {
            'precio_asc' => " ORDER BY ps.precio ASC",
            'precio_desc' => " ORDER BY ps.precio DESC",
            'nombre_asc' => " ORDER BY piw.nombre_web ASC",
            default => " ORDER BY p.id DESC"
        };

        $sqlFinal = "SELECT p.*, ps.precio, ps.stock, m.nombre as marca, piw.nombre_web, wc.nombre as cat_web " . $sqlBase . $sqlOrder . " LIMIT $por_pagina OFFSET $offset";
        $stmt = $this->db->prepare($sqlFinal);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $titulo = $busqueda ? 'Resultados para: "' . htmlspecialchars($busqueda) . '"' : ($catFilter ?? "Catálogo Completo");

        ob_start();
        include __DIR__ . '/../../views/home/catalogo_view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function autocomplete()
    {
        header('Content-Type: application/json');
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        // Solo autocompletar si el producto está activo (puedes añadir filtro por sucursal aquí también si quieres, pero suele ser más ligero así)
        $stmt = $this->db->prepare("SELECT DISTINCT nombre_web FROM productos_info_web WHERE nombre_web LIKE ? LIMIT 6");
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function locales()
    {
        require_once __DIR__ . '/../Models/Sucursal.php';
        $sucursalModel = new \App\Models\Sucursal($this->db);
        $sucursales = $sucursalModel->obtenerTodas();
        $titulo = "Nuestras Sucursales";
        $categorias = $this->cargarCategorias();
        ob_start();
        include __DIR__ . '/../../views/home/locales.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function terminos()
    {
        $categorias = $this->cargarCategorias();
        $titulo = "Términos y Condiciones";
        ob_start();
        include __DIR__ . '/../../views/home/terminos.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    // =========================================================
    // 7. FICHA DE PRODUCTO (CON PRECIO DE SUCURSAL Y STOCK)
    // =========================================================
    // =========================================================
    // 7. FICHA DE PRODUCTO (CON PRECIO DE SUCURSAL Y STOCK)
    // =========================================================
    public function producto()
    {
        $id = $_GET['id'] ?? null;
        $sucursal_id = $_SESSION['sucursal_activa'];
        if (!$id) {
            header("Location: " . BASE_URL . "home/catalogo");
            exit;
        }

        // Aquí NO filtramos por stock > 0, porque si entran por link directo y no hay, queremos que vean el cartel de "Agotado"
        $sql = "SELECT p.*, ps.precio, ps.stock, m.nombre as marca, piw.nombre_web, piw.subcategoria, wc.nombre as categoria_web, wc.id as cat_id
                FROM productos p 
                INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                LEFT JOIN marcas m ON piw.marca_id = m.id
                LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                WHERE p.id = ? AND ps.sucursal_id = $sucursal_id AND p.activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$producto) {
            header("Location: " . BASE_URL . "home/catalogo");
            exit;
        }

        // --- DETECTOR HÍBRIDO DE MOTOR DE BASE DE DATOS ---
        $motor = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $fnRand = ($motor === 'pgsql') ? 'RANDOM()' : 'RAND()';

        $relacionados = [];
        if (!empty($producto['cat_id'])) {
            // Relacionados SÍ deben tener stock
            $sqlRel = "SELECT p.id, ps.precio, ps.stock, p.imagen, piw.nombre_web, m.nombre as marca
                       FROM productos p 
                       INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                       LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                       LEFT JOIN marcas m ON piw.marca_id = m.id
                       WHERE piw.web_categoria_id = ? AND p.id != ? AND ps.sucursal_id = $sucursal_id AND ps.precio > 0 AND ps.stock > 0 AND p.activo = 1
                       ORDER BY " . $fnRand . " LIMIT 4";
            $stmtRel = $this->db->prepare($sqlRel);
            $stmtRel->execute([$producto['cat_id'], $id]);
            $relacionados = $stmtRel->fetchAll(\PDO::FETCH_ASSOC);
        }

        $categorias = $this->cargarCategorias();
        $titulo = $producto['nombre_web'] ?? $producto['nombre'];
        ob_start();
        include __DIR__ . '/../../views/home/producto.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function catalogoVisual()
    {
        ini_set('memory_limit', '512M');
        $busqueda = $_GET['q'] ?? '';
        $productos = $this->productoModel->obtenerTodosPublicos($busqueda);
        $total_registros = count($productos);
        $data = ['productos' => $productos, 'busqueda' => $busqueda, 'total_registros' => $total_registros];
        ob_start();
        require_once __DIR__ . '/../../views/home/catalogo_chorizo.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }
}
