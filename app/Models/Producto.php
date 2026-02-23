<?php

namespace App\Models;

use PDO;
use PDOException;

class Producto
{
    private $db;
    private $table = 'productos';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ====================================================================
    // 1. MÉTODOS DE LECTURA PRINCIPAL (NORMALIZADOS)
    // ====================================================================

    // Helper privado para no repetir la consulta gigante en todas partes
    private function getBaseQuery()
    {
        return "SELECT p.*, 
                       -- Prioridad al nombre web, sino el del ERP
                       COALESCE(w.nombre_web, p.nombre) as nombre_mostrar,
                       
                       -- Datos Normalizados (Traídos por ID)
                       m.nombre as nombre_marca,
                       wc.nombre as nombre_categoria_web,
                       wc.icono as icono_categoria,
                       wsc.nombre as nombre_subcategoria_web

                FROM productos p 
                -- 1. Unimos con la info web
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                
                -- 2. Unimos con las tablas maestras (NORMALIZACIÓN)
                LEFT JOIN marcas m ON w.marca_id = m.id
                LEFT JOIN web_categorias wc ON w.web_categoria_id = wc.id
                LEFT JOIN web_subcategorias wsc ON w.web_subcategoria_id = wsc.id";
    }

    public function getAll()
    {
        $sql = $this->getBaseQuery() . " ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerDisponibles()
    {
        $sql = $this->getBaseQuery() . " WHERE p.activo = 1 ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPorId($id)
    {
        $sql = $this->getBaseQuery() . " WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        return $this->obtenerPorId($id); // Reutilizamos lógica
    }

    // ====================================================================
    // 2. PAGINACIÓN Y FILTROS
    // ====================================================================



    // --- NUEVO: Obtener por CATEGORÍA WEB (Usando el ID de la nueva tabla) ---
    public function obtenerPorCategoriaWeb($web_categoria_id, $limite, $offset)
    {
        $sql = $this->getBaseQuery() . " 
                WHERE w.web_categoria_id = :cat_id AND p.activo = 1
                ORDER BY p.id DESC 
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat_id', $web_categoria_id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function contarPorCategoriaWeb($web_categoria_id)
    {
        $sql = "SELECT COUNT(*) 
                FROM productos p
                INNER JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                WHERE w.web_categoria_id = :cat_id AND p.activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cat_id' => $web_categoria_id]);
        return $stmt->fetchColumn();
    }

    // ====================================================================
    // 3. DATOS DE NEGOCIO (VENTAS Y RANKING)
    // ====================================================================

    public function obtenerMasVendidos()
    {
        // Se mantiene la lógica pero aprovechamos el BaseQuery para traer marcas
        $sql = "SELECT p.*, 
                       COALESCE(w.nombre_web, p.nombre) as nombre_mostrar, 
                       COALESCE(SUM(pd.cantidad), 0) as total_vendido,
                       m.nombre as nombre_marca,             /* Nuevo */
                       wc.nombre as nombre_categoria_web     /* Nuevo */
                FROM productos p
                LEFT JOIN productos_info_web w ON p.cod_producto = w.cod_producto
                LEFT JOIN pedidos_detalle pd ON p.id = pd.producto_id
                
                -- Joins Normalizados
                LEFT JOIN marcas m ON w.marca_id = m.id
                LEFT JOIN web_categorias wc ON w.web_categoria_id = wc.id

                WHERE p.activo = 1
                GROUP BY p.id
                ORDER BY total_vendido DESC, p.id DESC
                LIMIT 5";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // --- ACTUALIZADO: Obtener Categorías Destacadas (Desde tabla web_categorias) ---
    public function obtenerCategoriasDestacadas()
    {
        // Ahora agrupamos por el ID de la tabla web_categorias, no por texto
        $sql = "SELECT wc.id, 
                       wc.nombre, 
                       wc.imagen, 
                       wc.icono,
                       COUNT(p.id) as cantidad_productos,
                       COALESCE(SUM(pd.cantidad), 0) as ranking_ventas
                FROM web_categorias wc
                
                -- Unimos para ver qué productos tienen esta categoría
                JOIN productos_info_web w ON wc.id = w.web_categoria_id
                JOIN productos p ON w.cod_producto = p.cod_producto
                
                -- Unimos para contar ventas (opcional para el ranking)
                LEFT JOIN pedidos_detalle pd ON p.id = pd.producto_id
                
                WHERE wc.activo = 1
                GROUP BY wc.id
                ORDER BY ranking_ventas DESC, cantidad_productos DESC
                LIMIT 6";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ====================================================================
    // 4. SINCRONIZACIÓN Y ESCRITURA (ERP) - SE MANTIENE CASI IGUAL
    // ====================================================================
    
    // Esta función toca SOLO la tabla 'productos' (ERP), así que no interfiere
    // con la normalización web.
    public function sincronizar($datos)
    {
        $sql = "SELECT id FROM productos WHERE cod_producto = :cod";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cod' => $datos['cod_producto']]);
        $producto = $stmt->fetch(PDO::FETCH_OBJ);

        if ($producto) {
            // ACTUALIZAR (ERP)
            $sql = "UPDATE productos SET 
                    nombre = :nombre, 
                    precio = :precio, 
                    stock = :stock,
                    categoria = :cat, -- Categoria original del ERP (texto)
                    imagen = :img
                    WHERE id = :id";

            $params = [
                ':nombre' => $datos['nombre'],
                ':precio' => $datos['precio'],
                ':stock'  => $datos['stock'],
                ':cat'    => $datos['categoria'],
                ':img'    => $datos['imagen'],
                ':id'     => $producto->id
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $producto->id;
        } else {
            // CREAR (ERP)
            $sql = "INSERT INTO productos 
                    (cod_producto, nombre, precio, stock, categoria, imagen, descripcion, activo) 
                    VALUES 
                    (:cod, :nombre, :precio, :stock, :cat, :img, :desc, 1)";

            $params = [
                ':cod'    => $datos['cod_producto'],
                ':nombre' => $datos['nombre'],
                ':precio' => $datos['precio'],
                ':stock'  => $datos['stock'],
                ':cat'    => $datos['categoria'],
                ':img'    => $datos['imagen'],
                ':desc'   => $datos['descripcion']
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $this->db->lastInsertId();
        }
    }

    // Métodos CRUD básicos para Admin (si los usas)
    public function cambiarEstado($id, $estado)
    {
        $sql = "UPDATE productos SET activo = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    

    // =========================================================
    // 5. MÉTODOS CRUD MANUALES (Faltaban estos)
    // =========================================================

    public function crear($datos)
    {
        // Generamos un SKU automático si es creación manual
        $sku = 'MAN-' . time(); 

        $sql = "INSERT INTO productos (cod_producto, nombre, precio, stock, descripcion, imagen, activo) 
                VALUES (:cod, :nombre, :precio, :stock, :desc, :img, 1)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cod'    => $sku,
            ':nombre' => $datos['nombre'],
            ':precio' => $datos['precio'],
            ':stock'  => $datos['stock'] ?? 0,
            ':desc'   => $datos['descripcion'] ?? '',
            ':img'    => $datos['imagen'] ?? ''
        ]);
    }

    public function actualizar($id, $datos)
    {
        // Construimos la consulta base
        $sql = "UPDATE productos SET 
                nombre = :nombre, 
                precio = :precio, 
                stock = :stock,
                descripcion = :desc";
        
        $params = [
            ':nombre' => $datos['nombre'],
            ':precio' => $datos['precio'],
            ':stock'  => $datos['stock'],
            ':desc'   => $datos['descripcion'],
            ':id'     => $id
        ];

        // Solo actualizamos la imagen si viene una nueva
        if (!empty($datos['imagen'])) {
            $sql .= ", imagen = :img";
            $params[':img'] = $datos['imagen'];
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function sincronizarCategorias($producto_id, $raw_ids)
    {
        // 1. Obtenemos el código del producto (sku) usando su ID numérico
        $stmt = $this->db->prepare("SELECT cod_producto, nombre FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$prod) return;

        // 2. Parseamos los IDs que vienen del CSV (ej: "1|5|8")
        // Tomaremos el primero como la "Categoría Principal" para la web
        $ids = explode('|', $raw_ids);
        $categoria_principal_id = $ids[0] ?? null;

        if ($categoria_principal_id) {
            // Verificamos si ya existe la info web para este producto
            $check = $this->db->prepare("SELECT id FROM productos_info_web WHERE cod_producto = ?");
            $check->execute([$prod->cod_producto]);
            
            if ($check->fetch()) {
                // Actualizamos
                $sql = "UPDATE productos_info_web SET web_categoria_id = ? WHERE cod_producto = ?";
                $this->db->prepare($sql)->execute([$categoria_principal_id, $prod->cod_producto]);
            } else {
                // Creamos
                $sql = "INSERT INTO productos_info_web (cod_producto, web_categoria_id, nombre_web) VALUES (?, ?, ?)";
                $this->db->prepare($sql)->execute([$prod->cod_producto, $categoria_principal_id, $prod->nombre]);
            }
        }
    }

    // =========================================================
    // 7. MÉTODOS PARA EL CATÁLOGO PÚBLICO MASIVO (CHORIZO)
    // =========================================================

    public function contarPublicos($busqueda = '')
    {
        $sql = "SELECT COUNT(*) FROM productos WHERE activo = 1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (nombre LIKE :q OR cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerPublicosPaginados($limite, $offset, $busqueda = '')
    {
        // Usamos getBaseQuery para tener las marcas y categorías
        $sql = $this->getBaseQuery() . " WHERE p.activo = 1";
        $params = [
            ':limite' => (int) $limite, 
            ':offset' => (int) $offset
        ];

        if (!empty($busqueda)) {
            $sql .= " AND (p.nombre LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }

        $sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // PDO bind manual para limite/offset (a veces PDO da problemas si se pasan como string)
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // En App/Models/Producto.php

    public function obtenerTodosPublicos($busqueda = '')
    {
        // Usamos la query base para traer marcas, categorias, etc.
        $sql = $this->getBaseQuery() . " WHERE p.activo = 1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (p.nombre LIKE :q OR p.cod_producto LIKE :q)";
            $params[':q'] = "%$busqueda%";
        }

        // SIN LIMIT NI OFFSET
        $sql .= " ORDER BY p.id DESC"; 

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // 1. Obtener lista de categorías desde la tabla web_categorias
    public function obtenerCategoriasUnicas()
    {
        // Traemos ID y Nombre para el combobox
        $sql = "SELECT id, nombre FROM web_categorias WHERE activo = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    // 2. Contar Total (Con Joins a info_web)
    public function contarTotal($busqueda = '', $categoriaId = '')
    {
        $sql = "SELECT COUNT(*) 
                FROM productos p
                LEFT JOIN productos_info_web pi ON p.cod_producto = pi.cod_producto
                LEFT JOIN web_categorias wc ON pi.web_categoria_id = wc.id
                WHERE 1=1";
        
        $params = [];

        // CORRECCIÓN: Usamos q1, q2, q3 para evitar "Invalid parameter number"
        if (!empty($busqueda)) {
            $sql .= " AND (pi.nombre_web LIKE :q1 OR p.nombre LIKE :q2 OR p.cod_producto LIKE :q3)";
            $term = "%$busqueda%";
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
        }

        if (!empty($categoriaId)) {
            $sql .= " AND wc.id = :catId";
            $params[':catId'] = $categoriaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerPaginados($limit, $offset, $busqueda = '', $categoriaId = '')
    {
        $sql = "SELECT 
                    p.*, 
                    COALESCE(pi.nombre_web, p.nombre) as nombre_mostrar,
                    wc.nombre as categoria_nombre,
                    wc.id as categoria_id
                FROM productos p
                LEFT JOIN productos_info_web pi ON p.cod_producto = pi.cod_producto
                LEFT JOIN web_categorias wc ON pi.web_categoria_id = wc.id
                WHERE 1=1";
        
        $params = [];

        // CORRECCIÓN: Usamos q1, q2, q3 aquí también
        if (!empty($busqueda)) {
            $sql .= " AND (pi.nombre_web LIKE :q1 OR p.nombre LIKE :q2 OR p.cod_producto LIKE :q3)";
            $term = "%$busqueda%";
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
        }

        if (!empty($categoriaId)) {
            $sql .= " AND wc.id = :catId";
            $params[':catId'] = $categoriaId;
        }

        $sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";

        // PDO no permite pasar LIMIT y OFFSET en el array execute() fácilmente
        // porque los trata como strings y rompe el SQL.
        // La forma segura es bindValue o insertar los enteros directo si son seguros (calculados internamente).
        
        $stmt = $this->db->prepare($sql);

        // Bindeamos los parámetros de búsqueda primero
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


}