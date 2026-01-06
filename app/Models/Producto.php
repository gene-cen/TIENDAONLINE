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

    // Obtener todos (Para el Home)
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM productos ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerDisponibles()
    {
        // Aquí filtramos: Que esté activo (1)
        // Opcional: Podrías agregar "AND stock > 0" si quisieras ocultar sin stock automáticamente
        $sql = "SELECT * FROM productos WHERE activo = 1 ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // 3. Obtener por ID (Admin)
    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }



    // --- AGREGAR ESTO (NUEVO) ---
    // Obtener uno solo (Para el Checkout)
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Sincronizar desde ERP (Busca por Código en lugar de ID)
    public function sincronizar($datos)
    {
        // Verificamos si existe
        $sql = "SELECT id FROM productos WHERE cod_producto = :cod";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cod' => $datos['cod_producto']]);
        $producto = $stmt->fetch(PDO::FETCH_OBJ);

        if ($producto) {
            // --- ACTUALIZAR ---
            $sql = "UPDATE productos SET 
                    nombre = :nombre, 
                    precio = :precio, 
                    stock = :stock,
                    categoria = :cat,
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

            // <--- CAMBIO VITAL: Devolver el ID existente
            return $producto->id;
        } else {
            // --- CREAR ---
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

            // <--- CAMBIO VITAL: Devolver el ID del nuevo producto insertado
            return $this->db->lastInsertId();
        }
    }

    public function cambiarEstado($id, $estado)
    {
        $sql = "UPDATE productos SET activo = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    // 5. CRUD Básico (Crear/Actualizar/Eliminar manual)
    public function crear($datos)
    {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen, stock, activo) VALUES (:nombre, :desc, :precio, :img, 0, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $datos['nombre'], ':desc' => $datos['descripcion'], ':precio' => $datos['precio'], ':img' => $datos['imagen']]);
    }

    public function actualizar($id, $datos)
    {
        $sql = "UPDATE productos SET nombre = :nombre, descripcion = :desc, precio = :precio";
        $params = [':nombre' => $datos['nombre'], ':desc' => $datos['descripcion'], ':precio' => $datos['precio'], ':id' => $id];
        if (isset($datos['imagen'])) {
            $sql .= ", imagen = :img";
            $params[':img'] = $datos['imagen'];
        }
        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function contarTotal()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM productos");
        return $stmt->fetchColumn();
    }

    // 2. Obtener productos paginados (LIMIT y OFFSET)
    public function obtenerPaginados($limite, $offset)
    {
        // Ordenamos por ID descendente para ver los nuevos primero
        $sql = "SELECT * FROM productos ORDER BY id DESC LIMIT :limite OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        // PDO requiere que estos parámetros sean explícitamente enteros
        $stmt->bindValue(':limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // En tu clase Producto (Model)

    public function sincronizarCategorias($producto_id, $string_categorias)
    {
        // 1. Limpiar relaciones anteriores (Estrategia: Borrar y re-crear)
        // Esto es vital si estás actualizando productos existentes.
        $sqlDelete = "DELETE FROM producto_categoria WHERE producto_id = :pid";
        $stmt = $this->db->prepare($sqlDelete);
        $stmt->execute([':pid' => $producto_id]);

        // 2. Si viene vacío o nulo, terminamos aquí.
        if (empty($string_categorias)) return;

        // 3. Convertir el string en array
        // Asumimos que en el CSV vienen separados por barra "|" o coma ","
        // Ajusta el separador según tu CSV real.
        // Si quieres asegurarte, reemplaza esa línea por:
        // Esto reemplaza comas por pipes y luego explota por pipes, cubriendo ambos casos.
        $string_normalizado = str_replace(',', '|', $string_categorias);
        $ids = explode('|', $string_normalizado);

        // 4. Preparar la inserción (Preparamos UNA vez, ejecutamos VARIAS)
        $sqlInsert = "INSERT INTO producto_categoria (producto_id, categoria_id) VALUES (:pid, :cid)";
        $stmtInsert = $this->db->prepare($sqlInsert);

        foreach ($ids as $categoria_id) {
            $cat_id_limpio = trim($categoria_id);

            // Validación básica: que sea numérico
            if (is_numeric($cat_id_limpio) && $cat_id_limpio > 0) {
                try {
                    $stmtInsert->execute([
                        ':pid' => $producto_id,
                        ':cid' => $cat_id_limpio
                    ]);
                } catch (PDOException $e) {
                    // Si la categoría ID 999 no existe en la tabla 'categorias', saltará error de FK.
                    // Lo capturamos para que no detenga todo el proceso de importación.
                    error_log("Error vinculando prod $producto_id con cat $cat_id_limpio: " . $e->getMessage());
                }
            }
        }
    }

    // Obtener productos de una categoría específica con paginación
    public function obtenerPorCategoria($categoria_id, $limite, $offset) {
        $sql = "SELECT p.* FROM productos p
                INNER JOIN producto_categoria pc ON p.id = pc.producto_id
                WHERE pc.categoria_id = :cat_id AND p.activo = 1
                ORDER BY p.id DESC 
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat_id', $categoria_id, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // Contar total de productos en una categoría (Para saber cuántas páginas hay)
    public function contarPorCategoria($categoria_id) {
        $sql = "SELECT COUNT(*) 
                FROM productos p
                INNER JOIN producto_categoria pc ON p.id = pc.producto_id
                WHERE pc.categoria_id = :cat_id AND p.activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cat_id' => $categoria_id]);
        return $stmt->fetchColumn();
    }
}
