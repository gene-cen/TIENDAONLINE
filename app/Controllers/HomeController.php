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


    public function buscar()
    {
        $q = $_GET['q'] ?? '';
        header("Location: " . BASE_URL . "home/catalogo?q=" . urlencode($q));
        exit;
    }
public function index()
    {
        $categorias = $this->cargarCategorias();
        $sucursal_id = (int)$_SESSION['sucursal_activa'];

        // 1. Detector híbrido de motor para orden aleatorio
        $motor = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $fnRand = ($motor === 'pgsql') ? 'RANDOM()' : 'RAND()';

        // 2. Marcas para el filtro superior
        $marcas = $this->db->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);

        // 3. NOVEDADES (Consultas Preparadas)
        $sqlNovedades = "SELECT p.id, p.cod_producto, p.precio_unidad_medida, 
                                COALESCE(ps.precio, 0) as precio, 
                                ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                                p.imagen, piw.nombre_web, m.nombre as marca, wc.nombre as categoria 
                         FROM productos p 
                         INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                         INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                         LEFT JOIN marcas m ON piw.marca_id = m.id
                         LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                         WHERE p.activo = 1 AND ps.sucursal_id = :sucursal
                         AND COALESCE(ps.precio, 0) > 0 
                         AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0 
                         ORDER BY p.id DESC LIMIT 5";
        $stmtNov = $this->db->prepare($sqlNovedades);
        $stmtNov->execute([':sucursal' => $sucursal_id]);
        $productos = $stmtNov->fetchAll(\PDO::FETCH_ASSOC);

        // 4. MÁS VENDIDOS (Orden Aleatorio Seguro)
        $sqlMasVendidos = "SELECT p.id, p.cod_producto, p.precio_unidad_medida, 
                                  COALESCE(ps.precio, 0) as precio, 
                                  ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                                  p.imagen, piw.nombre_web, m.nombre as marca 
                           FROM productos p 
                           INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                           INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                           LEFT JOIN marcas m ON piw.marca_id = m.id
                           WHERE p.activo = 1 AND ps.sucursal_id = :sucursal
                           AND COALESCE(ps.precio, 0) > 0 
                           AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0 
                           ORDER BY $fnRand LIMIT 5";
        $stmtMV = $this->db->prepare($sqlMasVendidos);
        $stmtMV->execute([':sucursal' => $sucursal_id]);
        $masVendidos = $stmtMV->fetchAll(\PDO::FETCH_ASSOC);

        // 5. BANNERS (Principal y Secundario con filtros de tiempo)
        $sqlBanners = "SELECT * FROM carrusel_banners 
                       WHERE estado_activo = 1 
                       AND (sucursal_id = 0 OR sucursal_id = :sucursal) 
                       AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) 
                       AND (fecha_fin IS NULL OR fecha_fin >= NOW()) 
                       ORDER BY orden ASC";
        $stmtBanners = $this->db->prepare($sqlBanners);
        $stmtBanners->execute([':sucursal' => $sucursal_id]);
        $bannersHome = $stmtBanners->fetchAll(\PDO::FETCH_ASSOC);

        $bannersSecundarios = [];
        try {
            $sqlBannersSec = "SELECT * FROM carrusel_secundario 
                              WHERE estado_activo = 1 
                              AND (sucursal_id = 0 OR sucursal_id = :sucursal) 
                              AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) 
                              AND (fecha_fin IS NULL OR fecha_fin >= NOW()) 
                              ORDER BY orden ASC";
            $stmtSec = $this->db->prepare($sqlBannersSec);
            $stmtSec->execute([':sucursal' => $sucursal_id]);
            $bannersSecundarios = $stmtSec->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error banners secundarios: " . $e->getMessage());
        }

        // 6. MARCAS DESTACADAS (Showcase Premium)
        $marcasHome = $this->db->query("SELECT * FROM marcas_destacadas WHERE estado_activo = 1 ORDER BY orden ASC LIMIT 8")->fetchAll(\PDO::FETCH_ASSOC);
        
        $sqlProdMarca = "SELECT p.id, p.cod_producto, p.precio_unidad_medida, 
                                COALESCE(ps.precio, 0) as precio, 
                                ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                                p.imagen, piw.nombre_web, m.nombre as marca 
                         FROM productos p 
                         INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                         INNER JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                         INNER JOIN marcas m ON piw.marca_id = m.id
                         WHERE p.activo = 1 AND ps.sucursal_id = :sucursal 
                         AND m.nombre = :marcaName 
                         AND COALESCE(ps.precio, 0) > 0 
                         AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0 
                         ORDER BY $fnRand LIMIT 4";
        $stmtPM = $this->db->prepare($sqlProdMarca);

        foreach ($marcasHome as &$marcaDestacada) {
            $stmtPM->execute([
                ':sucursal' => $sucursal_id, 
                ':marcaName' => $marcaDestacada['nombre']
            ]);
            $marcaDestacada['productos'] = $stmtPM->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Alias para compatibilidad con la vista sidebar
        $listaCategorias = $categorias;

        // Renderizado
        ob_start();
        require __DIR__ . '/../../views/home/home.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/main.php';
    }
    // =========================================================
    // 3. CATÁLOGO GENERAL 
    // =========================================================
    public function catalogo()
    {
        $sucursal_id = (int)$_SESSION['sucursal_activa'];
        $catFilter   = $_GET['categoria'] ?? null;
        $marcaFilter = $_GET['marca'] ?? null;
        $busqueda    = $_GET['q'] ?? null;
        $minPrecio   = $_GET['min_price'] ?? null;
        $maxPrecio   = $_GET['max_price'] ?? null;
        $orden       = $_GET['orden'] ?? 'relevancia';
        
        $coleccion   = $_GET['coleccion'] ?? null; 

        $categorias = $this->cargarCategorias();
        $marcasList = [];
        $rangoPrecio = ['min' => 0, 'max' => 1000000];

        try {
            $sqlMarcas = "SELECT DISTINCT m.nombre 
                          FROM marcas m 
                          INNER JOIN productos_info_web piw ON m.id = piw.marca_id 
                          INNER JOIN productos_sucursales ps ON piw.cod_producto = ps.cod_producto
                          WHERE ps.sucursal_id = $sucursal_id 
                          AND COALESCE(ps.precio, 0) > 0 
                          AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0";

            $paramsMarcas = [];
            if ($catFilter) {
                $sqlMarcas .= " AND piw.web_categoria_id = (SELECT id FROM web_categorias WHERE nombre = ? LIMIT 1)";
                $paramsMarcas[] = $catFilter;
            }
            $marcasList = $this->db->prepare($sqlMarcas . " ORDER BY m.nombre ASC");
            $marcasList->execute($paramsMarcas);
            $marcasList = $marcasList->fetchAll(\PDO::FETCH_ASSOC);

            $stmtPrecios = $this->db->prepare("SELECT MIN(COALESCE(precio, 0)) as min_p, MAX(COALESCE(precio, 0)) as max_p FROM productos_sucursales WHERE sucursal_id = ? AND COALESCE(precio, 0) > 0 AND ((COALESCE(stock, 0) - COALESCE(stock_reservado, 0)) - 12) > 0");
            $stmtPrecios->execute([$sucursal_id]);
            $preciosDb = $stmtPrecios->fetch(\PDO::FETCH_ASSOC);

            if ($preciosDb && $preciosDb['min_p'] !== null && $preciosDb['max_p'] !== null) {
                $rangoPrecio['min'] = floor((float)$preciosDb['min_p']);
                $rangoPrecio['max'] = ceil((float)$preciosDb['max_p']);
            }
        } catch (\Exception $e) { error_log($e->getMessage()); }

        if ($minPrecio === null) $minPrecio = $rangoPrecio['min'];
        if ($maxPrecio === null) $maxPrecio = $rangoPrecio['max'];

        $por_pagina = 25;
        $pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($pagina < 1) $pagina = 1;
        $offset = ($pagina - 1) * $por_pagina;

        $sqlBase = "FROM productos p 
                    INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                    LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                    LEFT JOIN marcas m ON piw.marca_id = m.id
                    LEFT JOIN web_categorias wc ON piw.web_categoria_id = wc.id
                    WHERE p.activo = 1 AND ps.sucursal_id = $sucursal_id 
                    AND COALESCE(ps.precio, 0) > 0 
                    AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0";

        $params = [];

        // 🔥 FILTRO DE COLECCIONES CON VALIDACIÓN DE FECHAS
        if ($coleccion) {
            $stmtCol = $this->db->prepare("SELECT productos_ids FROM carrusel_banners WHERE palabra_clave = ? AND estado_activo = 1 AND (sucursal_id = 0 OR sucursal_id = ?) AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) AND (fecha_fin IS NULL OR fecha_fin >= NOW()) ORDER BY id DESC LIMIT 1");
            $stmtCol->execute([$coleccion, $sucursal_id]);
            $idsString = $stmtCol->fetchColumn();

            if (!$idsString) {
                $stmtCol2 = $this->db->prepare("SELECT productos_ids FROM carrusel_secundario WHERE palabra_clave = ? AND estado_activo = 1 AND (sucursal_id = 0 OR sucursal_id = ?) AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) AND (fecha_fin IS NULL OR fecha_fin >= NOW()) ORDER BY id DESC LIMIT 1");
                $stmtCol2->execute([$coleccion, $sucursal_id]);
                $idsString = $stmtCol2->fetchColumn();
            }

            if ($idsString) {
                $codigosArray = array_filter(array_map('trim', explode(',', $idsString)));
                if (!empty($codigosArray)) {
                    $codigosLimpios = implode(',', array_map(function($val) { return $this->db->quote($val); }, $codigosArray));
                    $sqlBase .= " AND p.cod_producto IN ($codigosLimpios)";
                } else {
                    $sqlBase .= " AND 1=0"; 
                }
            } else {
                $sqlBase .= " AND 1=0"; 
            }
        }

        if ($catFilter) { $sqlBase .= " AND wc.nombre = ?"; $params[] = $catFilter; }
        if ($marcaFilter) { $sqlBase .= " AND m.nombre = ?"; $params[] = $marcaFilter; }
        if ($busqueda) {
            $sqlBase .= " AND (piw.nombre_web LIKE ? OR p.nombre LIKE ? OR m.nombre LIKE ?)";
            $termino = "%$busqueda%";
            $params[] = $termino; $params[] = $termino; $params[] = $termino;
        }
        $sqlBase .= " AND COALESCE(ps.precio, 0) BETWEEN ? AND ?";
        $params[] = $minPrecio; $params[] = $maxPrecio;

        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtCount->execute($params);
        $total_registros = (int) $stmtCount->fetchColumn();
        $total_paginas = ($total_registros > 0) ? (int) ceil($total_registros / $por_pagina) : 1;

        $sqlOrder = match ($orden) {
            'precio_asc' => " ORDER BY ps.precio ASC",
            'precio_desc' => " ORDER BY ps.precio DESC",
            'nombre_asc' => " ORDER BY piw.nombre_web ASC",
            default => " ORDER BY p.id DESC"
        };

        $sqlFinal = "SELECT p.*, COALESCE(ps.precio, 0) as precio, 
                     ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                     m.nombre as marca, piw.nombre_web, wc.nombre as cat_web " . $sqlBase . $sqlOrder . " LIMIT $por_pagina OFFSET $offset";
        $stmt = $this->db->prepare($sqlFinal);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($coleccion) {
            $titulo = "Colección Especial"; 
        } else {
            $titulo = $busqueda ? 'Resultados para: "' . htmlspecialchars($busqueda) . '"' : ($catFilter ?? "Catálogo Completo");
        }

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
    // 7. FICHA DE PRODUCTO
    // =========================================================
    public function producto()
    {
        $id = $_GET['id'] ?? null;
        $sucursal_id = (int)$_SESSION['sucursal_activa'];
        if (!$id) {
            header("Location: " . BASE_URL . "home/catalogo");
            exit;
        }

        // Ficha directa: Permite ver el producto, pero restamos los 12 para que muestre "Agotado" si baja el buffer
        $sql = "SELECT p.*, COALESCE(ps.precio, 0) as precio, 
                ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                m.nombre as marca, piw.nombre_web, piw.subcategoria, wc.nombre as categoria_web, wc.id as cat_id
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

        $motor = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $fnRand = ($motor === 'pgsql') ? 'RANDOM()' : 'RAND()';

        $relacionados = [];
        if (!empty($producto['cat_id'])) {
            $sqlRel = "SELECT p.id, COALESCE(ps.precio, 0) as precio, 
                       ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) as stock, 
                       p.imagen, piw.nombre_web, m.nombre as marca
                       FROM productos p 
                       INNER JOIN productos_sucursales ps ON p.cod_producto = ps.cod_producto
                       LEFT JOIN productos_info_web piw ON p.cod_producto = piw.cod_producto 
                       LEFT JOIN marcas m ON piw.marca_id = m.id
                       WHERE piw.web_categoria_id = ? AND p.id != ? AND ps.sucursal_id = $sucursal_id 
                       AND COALESCE(ps.precio, 0) > 0 
                       AND ((COALESCE(ps.stock, 0) - COALESCE(ps.stock_reservado, 0)) - 12) > 0 
                       AND p.activo = 1
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

    public function cambiarSucursal($codigoErp)
    {
        $codigoErp = (int)$codigoErp;

        if (in_array($codigoErp, [10, 29])) {
            $_SESSION['sucursal_activa'] = $codigoErp;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'home';
        header("Location: " . $referer);
        exit();
    }
}