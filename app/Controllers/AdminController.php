<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use Exception;

class AdminController
{
    private $db;
    private $pedidoModel;
    private $productoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
        $this->productoModel = new Producto($db);
    }

    // Proteger ruta: Solo admins pasan
    private function verificarAdmin()
    {
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }

    public function dashboard()
    {
        $this->verificarAdmin(); // 🔒 Candado de seguridad

        $pedidos = $this->pedidoModel->obtenerTodos();

        ob_start();
        include __DIR__ . '/../../views/admin/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function verDetalle($id)
    {
        $this->verificarAdmin();

        // 1. Obtener datos reales de la BD
        $pedido = $this->pedidoModel->obtenerPorId($id);
        $detalles = $this->pedidoModel->obtenerDetalles($id); // <--- Asegúrate de tener este método en tu Modelo

        if (!$pedido) {
            header("Location: " . BASE_URL . "admin/dashboard");
            exit();
        }

        // 2. Cargar la vista bonita (La que creamos antes)
        ob_start();
        // QUITA ESTO: echo "<h1>Aquí iría el detalle del pedido #$id</h1>"; 

        // PON ESTO:
        require_once __DIR__ . '/../../views/admin/detalle_pedido.php';

        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/main.php';
    }

    public function exportar()
    {
        $this->verificarAdmin();
        // Lógica para generar el Excel/JSON
        echo "Generando archivo...";
    }

    public function cambiarEstado()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedido_id = $_POST['pedido_id'];
            $nuevo_estado = $_POST['nuevo_estado'];

            // Actualizamos en BD
            $this->pedidoModel->actualizarEstado($pedido_id, $nuevo_estado);

            // Redirigimos de vuelta al detalle del pedido
            header("Location: " . BASE_URL . "admin/pedido/ver/" . $pedido_id);
            exit();
        }
    }


    public function importarERP()
    {
        $this->verificarAdmin();

        $ruta_base = dirname(__DIR__, 2);
        $archivo = $ruta_base . '/erp_data/29_productos_web2.csv';

        if (!file_exists($archivo)) {
            die("Error inesperado: El archivo no se puede leer en: $archivo");
        }

        $handle = fopen($archivo, "r");
        $fila = 0;
        $actualizados = 0;

        // Leer línea por línea
        while (($linea = fgets($handle)) !== false) {
            $fila++;
            if ($fila === 1) continue; // Saltar encabezado

            $linea = trim($linea);
            if (empty($linea)) continue;

            $partes = explode(',', $linea);
            $total_partes = count($partes);

            if ($total_partes < 7) continue;

            // --- MAPEO DE COLUMNAS (AJUSTA SEGÚN TU NUEVO CSV) ---
            // Asumiendo que category_ids está al final ahora, o ajusta los índices:

            // Ejemplo: Si category_ids es la ÚLTIMA columna:
            $category_ids_raw = $partes[$total_partes - 1]; // <--- NUEVO: Capturamos los IDs (ej: "1|5|8")

            // Ajustamos los índices de los otros campos según donde hayan quedado
            // (Revisa si al agregar la columna en el CSV, estas posiciones cambiaron)
            $descripcion = $partes[$total_partes - 2];
            $imagen_url  = $partes[$total_partes - 3];
            $stock       = (int) $partes[$total_partes - 4];
            $precio      = (int) $partes[$total_partes - 5];
            $sku         = trim($partes[$total_partes - 6]);
            $categoria   = trim($partes[$total_partes - 7]); // Nombre categoría principal

            // El nombre
            $nombre_parts = array_slice($partes, 0, $total_partes - 7);
            $nombre       = trim(implode(',', $nombre_parts));

            // Limpieza UTF-8
            $nombre = mb_convert_encoding($nombre, 'UTF-8', 'ISO-8859-1');
            // $categoria = mb_convert_encoding($categoria, 'UTF-8', 'ISO-8859-1'); // Opcional si ya usas IDs

            $datos = [
                'cod_producto' => $sku,
                'nombre'       => $nombre,
                'categoria'    => $categoria, // Aún guardamos la principal por si acaso
                'precio'       => $precio,
                'stock'        => $stock,
                'imagen'       => $imagen_url,
                'descripcion'  => ''
            ];

            // --- BLOQUE DE TRANSACCIÓN Y GUARDADO ---
            try {
                // 1. Iniciamos transacción para que producto + categorías se guarden juntos o fallen juntos
                $this->db->beginTransaction(); // <--- NUEVO

                // 2. Guardamos el producto Y RECIBIMOS EL ID
                // IMPORTANTE: Tu método sincronizar() en el Modelo debe devolver el ID del producto
                $producto_id = $this->productoModel->sincronizar($datos); // <--- CAMBIO

                // 3. Sincronizamos las categorías N:M
                if ($producto_id) {
                    $this->productoModel->sincronizarCategorias($producto_id, $category_ids_raw); // <--- NUEVO
                }

                // 4. Confirmamos cambios
                $this->db->commit(); // <--- NUEVO
                $actualizados++;
            } catch (Exception $e) {
                // Si algo falla, deshacemos esta fila
                $this->db->rollBack(); // <--- NUEVO
                // Opcional: Loguear error -> error_log("Error en SKU $sku: " . $e->getMessage());
            }
        }

        fclose($handle);
        header("Location: " . BASE_URL . "admin/dashboard?msg=sync_ok");
        exit();
    }

    // Agregar dentro de AdminController

    public function toggleProducto($id)
    {
        $this->verificarAdmin();

        // Obtenemos el producto para ver su estado actual
        $prod = $this->productoModel->obtenerPorId($id);

        // Si es 1 pasa a 0, si es 0 pasa a 1
        $nuevo_estado = ($prod->activo == 1) ? 0 : 1;

        $this->productoModel->cambiarEstado($id, $nuevo_estado);

        // Volvemos a la lista
        header("Location: " . BASE_URL . "admin/productos");
        exit();
    }

    // =========================================================
    // GESTIÓN DE PRODUCTOS (CRUD)
    // =========================================================

    // 1. Listar Productos (Esta es la que te daba el error Fatal Error)
    public function productos()
    {
        $this->verificarAdmin();

        // Configuración de Paginación
        $por_pagina = 20; // <--- Aquí defines de cuánto en cuánto

        // Obtenemos la página actual de la URL (si no existe, es la 1)
        $pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;

        // Calculamos el inicio (offset)
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Obtenemos total de registros y calculamos total de páginas
        $total_registros = $this->productoModel->contarTotal();
        $total_paginas = ceil($total_registros / $por_pagina);

        // Obtenemos SOLO los productos de esta página
        $productos = $this->productoModel->obtenerPaginados($por_pagina, $offset);

        ob_start();
        // Pasamos variables extra a la vista ($pagina_actual, $total_paginas)
        require_once __DIR__ . '/../../views/admin/productos_index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    // 3. Formulario Crear
    public function crearProducto()
    {
        $this->verificarAdmin();
        $producto = null; // Para que la vista sepa que es nuevo
        ob_start();
        require_once __DIR__ . '/../../views/admin/producto_form.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    // 4. Formulario Editar
    public function editarProducto($id)
    {
        $this->verificarAdmin();
        $producto = $this->productoModel->obtenerPorId($id);

        if (!$producto) {
            header("Location: " . BASE_URL . "admin/productos");
            exit();
        }

        ob_start();
        require_once __DIR__ . '/../../views/admin/producto_form.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    // 5. Guardar (Insertar o Actualizar)
    public function guardarProducto()
    {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;

            $datos = [
                'nombre' => $_POST['nombre'],
                'precio' => $_POST['precio'],
                'descripcion' => $_POST['descripcion']
            ];

            // Manejo de Imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = time() . '_' . $_FILES['imagen']['name'];
                $rutaDestino = __DIR__ . '/../../public/img/productos/' . $nombreArchivo;

                if (!file_exists(dirname($rutaDestino))) {
                    mkdir(dirname($rutaDestino), 0777, true);
                }

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                    $datos['imagen'] = $nombreArchivo;
                }
            }

            if ($id) {
                $this->productoModel->actualizar($id, $datos);
            } else {
                $this->productoModel->crear($datos);
            }

            header("Location: " . BASE_URL . "admin/productos");
            exit();
        }
    }

    // 6. Eliminar
    public function eliminarProducto($id)
    {
        $this->verificarAdmin();
        $this->productoModel->eliminar($id);
        header("Location: " . BASE_URL . "admin/productos");
        exit();
    }
}
