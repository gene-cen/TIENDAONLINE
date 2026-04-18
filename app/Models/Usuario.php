<?php

namespace App\Models;

use PDO;
use PDOException;

class Usuario
{
    private $db;
    private $table = "usuarios";

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================
    // 🔍 SECCIÓN 1: BÚSQUEDA Y LECTURA (CON JOINS)
    // =========================================================

    public function getById($id)
    {
        // 🔥 AHORA TRAEMOS LA DIRECCIÓN DESDE LA OTRA TABLA
        $sql = "SELECT u.*, 
                       r.nombre_rol as nombre_rol,
                       d.direccion as direccion_principal,
                       c.nombre as nombre_comuna
                FROM {$this->table} u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN direcciones_usuarios d ON u.id = d.usuario_id AND d.es_principal = 1
                LEFT JOIN comunas c ON d.comuna_id = c.id
                WHERE u.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByEmail($email)
    {
        $sql = "SELECT u.*, r.nombre_rol 
                FROM {$this->table} u
                LEFT JOIN roles r ON u.rol_id = r.id
                WHERE u.email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // =========================================================
    // 🔐 SECCIÓN 2: AUTENTICACIÓN
    // =========================================================
// =========================================================
    // 🔐 SECCIÓN 2: AUTENTICACIÓN (CON MIGRACIÓN SILENCIOSA)
    // =========================================================

    public function login($email, $password)
    {
        $user = $this->getByEmail($email);

        if ($user) {
            // 🛡️ 1. Intento Moderno (Bcrypt / Argon2)
            if (password_verify($password, $user->password)) {
                $this->registrarAcceso($user->id, true);
                return $user;
            }

            // 🕰️ 2. Intento Legacy (Transición de MD5 a Bcrypt)
            // Si la clave moderna falla, comprobamos si era una clave antigua de la fase de pruebas
            if (md5($password) === $user->password) {
                
                // ¡La clave es correcta pero el formato es viejo!
                // Actualizamos silenciosamente a la seguridad moderna en este instante
                $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
                    $stmt->execute([$nuevoHash, $user->id]);
                    
                    // Actualizamos el objeto en memoria por consistencia
                    $user->password = $nuevoHash;
                } catch (PDOException $e) {
                    error_log("Error migrando hash de MD5 a Bcrypt para usuario ID {$user->id}: " . $e->getMessage());
                }

                $this->registrarAcceso($user->id, true);
                return $user;
            }

            // ❌ 3. Contraseña definitivamente incorrecta
            $this->registrarAcceso($user->id, false);
        }
        
        return false;
    }

    private function registrarAcceso($userId, $exitoso)
    {
        try {
            $sql = "INSERT INTO log_accesos (usuario_id, ip_address, user_agent, exito) 
                    VALUES (:uid, :ip, :ua, :exito)";
            $this->db->prepare($sql)->execute([
                ':uid'   => $userId,
                ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ':ua'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ':exito' => $exitoso ? 1 : 0
            ]);
            
            // Actualizamos la fecha de última conexión
            if ($exitoso) {
                $this->db->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$userId]);
            }
        } catch (PDOException $e) {
            error_log("Error log_accesos: " . $e->getMessage());
        }
    }

    // =========================================================
    // ✍️ SECCIÓN 3: ESCRITURA (LIMPIA DE COLUMNAS BORRADAS)
    // =========================================================

    public function crear($data)
    {
        $token = bin2hex(random_bytes(32));
        
        // 🔥 QUITAMOS DIRECCIÓN, LAT Y LONG PORQUE VAN EN OTRA TABLA
        $sql = "INSERT INTO {$this->table} 
                (nombre, rut, razon_social, email, giro, password, rol_id, google_id, token_confirmacion, confirmado) 
                VALUES (:nombre, :rut, :razon, :email, :giro, :pass, :rol_id, :google, :token, 0)";

        $this->db->prepare($sql)->execute([
            ':nombre' => $data['nombre'],
            ':rut'    => $data['rut'] ?? '',
            ':razon'  => $data['razon_social'] ?? null,
            ':email'  => $data['email'],
            ':giro'   => $data['giro'] ?? '',
            ':pass'   => $data['password'],
            ':rol_id' => $data['rol_id'] ?? 6, // 6 = Cliente
            ':google' => $data['google_id'] ?? null,
            ':token'  => $token
        ]);

        return ['id' => $this->db->lastInsertId(), 'token' => $token];
    }

    public function actualizar($id, $datos)
    {
        // 🔥 AHORA ACTUALIZAMOS SOLO DATOS DE IDENTIDAD
        $sql = "UPDATE usuarios SET 
                    nombre = :nombre, 
                    rut = :rut, 
                    razon_social = :razon,
                    email = :email, 
                    giro = :giro,
                    rol_id = :rol_id 
                WHERE id = :id";

        return $this->db->prepare($sql)->execute([
            ':nombre' => $datos['nombre'],
            ':rut'    => $datos['rut'],
            ':razon'  => $datos['razon_social'] ?? null,
            ':email'  => $datos['email'],
            ':giro'   => $datos['giro'] ?? null,
            ':rol_id' => $datos['rol_id'],
            ':id'     => $id
        ]);
    }

    // Añade estos métodos dentro de la clase Usuario en app/Models/Usuario.php

public function getByToken($token)
{
    $sql = "SELECT * FROM usuarios WHERE token_confirmacion = ? LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$token]);
    return $stmt->fetch(\PDO::FETCH_OBJ);
}

public function activarCuenta($token)
{
    $sql = "UPDATE usuarios SET confirmado = 1, token_confirmacion = NULL WHERE token_confirmacion = ?";
    return $this->db->prepare($sql)->execute([$token]);
}

public function generarTokenRecuperacion($email)
{
    $token = bin2hex(random_bytes(32));
    $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $sql = "UPDATE usuarios SET reset_token = ?, reset_expire = ? WHERE email = ?";
    $res = $this->db->prepare($sql)->execute([$token, $expire, $email]);
    return $res ? $token : false;
}

public function getByResetToken($token)
{
    $sql = "SELECT * FROM usuarios WHERE reset_token = ? AND reset_expire > NOW() LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$token]);
    return $stmt->fetch(\PDO::FETCH_OBJ);
}

public function actualizarPassword($id, $newPassword)
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?";
    return $this->db->prepare($sql)->execute([$hash, $id]);
}

    // =========================================================
    // 📞 SECCIÓN 4: GESTIÓN DE TELÉFONOS (Multi-entorno)
    // =========================================================

    public function getTelefonos($usuario_id)
    {
        $sql = "SELECT id, numero, alias, es_principal FROM usuario_telefonos 
                WHERE usuario_id = ? ORDER BY es_principal DESC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerTelefonoPorId($tel_id, $usuario_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuario_telefonos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$tel_id, $usuario_id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function agregarTelefono($usuario_id, $numero, $alias = 'Titular', $es_principal = 0)
    {
        $sql = "INSERT INTO usuario_telefonos (usuario_id, numero, alias, es_principal) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$usuario_id, $numero, $alias, $es_principal]);
    }

    public function actualizarTelefono($tel_id, $usuario_id, $numero, $alias)
    {
        $sql = "UPDATE usuario_telefonos SET numero = ?, alias = ? WHERE id = ? AND usuario_id = ?";
        return $this->db->prepare($sql)->execute([$numero, $alias, $tel_id, $usuario_id]);
    }

    public function setTelefonoPrincipal($tel_id, $usuario_id)
    {
        $this->db->prepare("UPDATE usuario_telefonos SET es_principal = 0 WHERE usuario_id = ?")->execute([$usuario_id]);
        return $this->db->prepare("UPDATE usuario_telefonos SET es_principal = 1 WHERE id = ? AND usuario_id = ?")->execute([$tel_id, $usuario_id]);
    }

    public function eliminarTelefono($tel_id, $usuario_id)
    {
        // Solo permite eliminar si no es el principal
        $sql = "DELETE FROM usuario_telefonos WHERE id = ? AND usuario_id = ? AND es_principal = 0";
        return $this->db->prepare($sql)->execute([$tel_id, $usuario_id]);
    }

    // =========================================================
    // 🗺️ SECCIÓN 5: GEOGRAFÍA Y DIRECCIONES
    // =========================================================

    public function obtenerDirecciones($id_usuario)
    {
        $sql = "SELECT * FROM direcciones_usuarios WHERE usuario_id = :uid AND activo = 1 ORDER BY es_principal DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasLasComunas()
    {
        $sql = "SELECT c.id, c.nombre, r.nombre as nombre_region 
                FROM comunas c 
                JOIN provincias p ON c.provincia_id = p.id 
                JOIN regiones r ON p.region_id = r.id 
                ORDER BY c.nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerComunasValparaiso()
    {
        // ID 6 = Región de Valparaíso (La casa de Cencocal)
        $sql = "SELECT c.id, c.nombre, c.sucursal_id FROM comunas c 
                JOIN provincias p ON c.provincia_id = p.id 
                WHERE p.region_id = 6 ORDER BY c.nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerComunasPorRegion($regionId)
    {
        $sql = "SELECT c.id, c.nombre FROM comunas c 
                JOIN provincias p ON c.provincia_id = p.id 
                WHERE p.region_id = :rid ORDER BY c.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $regionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un usuario por su RUT (sin importar puntos o guiones)
     */
    public function getByRut($rut)
    {
        // Limpiamos el RUT que entra para que sea solo números y K
        $rutLimpio = preg_replace('/[^0-9kK]/', '', $rut);

        // Usamos REPLACE en la consulta para que compare "limpio vs limpio"
        $sql = "SELECT * FROM usuarios WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$rutLimpio]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Guarda la dirección opcional del usuario en la tabla direcciones_usuarios
     */
    public function guardarDireccion($data)
    {
        try {
            $sql = "INSERT INTO direcciones_usuarios 
                    (usuario_id, region, comuna, calle, numero, latitud, longitud, es_principal) 
                    VALUES (:usuario_id, :region, :comuna, :calle, :numero, :latitud, :longitud, :es_principal)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':usuario_id'   => $data['usuario_id'],
                ':region'       => $data['region'],
                ':comuna'       => $data['comuna'],
                ':calle'        => $data['calle'],
                ':numero'       => $data['numero'],
                ':latitud'      => $data['latitud'],
                ':longitud'     => $data['longitud'],
                ':es_principal' => $data['es_principal']
            ]);
        } catch (\PDOException $e) {
            error_log("Error al guardar dirección: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================
    // 🧠 MÓDULO ADMINISTRATIVO (FILTROS Y GESTIÓN VIP)
    // =========================================================

    /**
     * Obtiene usuarios con filtros inteligentes
     */
    public function obtenerFiltradosAdmin($busqueda = '', $rol_id = '', $sucursal_id = '', $estado = '', $confianza = '')
    {
        $sql = "SELECT u.*, r.nombre_rol, s.nombre as nombre_sucursal 
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN sucursales s ON u.sucursal_asignada = s.id
                WHERE 1=1";
        
        $params = [];

        // 1. Filtro de Búsqueda (Nombre, Email o RUT)
        if (!empty($busqueda)) {
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);
            $sql .= " AND (u.nombre LIKE :q1 OR u.email LIKE :q2 OR REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q3)";
            $params[':q1'] = "%$busqueda%";
            $params[':q2'] = "%$busqueda%";
            $params[':q3'] = "%$busquedaLimpia%";
        }

        // 2. Filtro por Rol
        if ($rol_id !== '') {
            $sql .= " AND u.rol_id = :rol";
            $params[':rol'] = $rol_id;
        }

        // 3. Filtro por Sucursal Asignada
        if ($sucursal_id !== '') {
            $sql .= " AND u.sucursal_asignada = :sucursal";
            $params[':sucursal'] = $sucursal_id;
        }

        // 4. Filtro por Estado (Activo/Inactivo)
        if ($estado !== '') {
            $sql .= " AND u.estado = :estado";
            $params[':estado'] = $estado;
        }

        // 5. Filtro por Cliente de Confianza
        if ($confianza !== '') {
            $sql .= " AND u.es_cliente_confianza = :confianza";
            $params[':confianza'] = $confianza;
        }

        $sql .= " ORDER BY u.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualización Completa desde el Panel de Control
     */
    public function actualizarDesdeAdmin($id, $datos)
    {
        $sql = "UPDATE usuarios SET 
                    nombre = :nombre, 
                    rut = :rut, 
                    razon_social = :razon,
                    rol_id = :rol_id,
                    es_cliente_confianza = :confianza,
                    estado = :estado,
                    sucursal_asignada = :sucursal
                WHERE id = :id";

        return $this->db->prepare($sql)->execute([
            ':nombre'    => $datos['nombre'],
            ':rut'       => $datos['rut'],
            ':razon'     => $datos['razon_social'] ?? null,
            ':rol_id'    => $datos['rol_id'],
            ':confianza' => $datos['es_cliente_confianza'] ?? 0,
            ':estado'    => $datos['estado'] ?? 1,
            ':sucursal'  => !empty($datos['sucursal_asignada']) ? $datos['sucursal_asignada'] : null,
            ':id'        => $id
        ]);
    }

    /**
     * Crea un usuario Administrativo/Colaborador desde el Panel
     */
    public function crearColaborador($datos)
    {
        // Encriptamos la contraseña provista por el administrador
        $hash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios 
                (nombre, rut, email, password, rol_id, sucursal_asignada, estado, fecha_registro) 
                VALUES (:nombre, :rut, :email, :pass, :rol, :sucursal, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'   => $datos['nombre'],
            ':rut'      => $datos['rut'],
            ':email'    => $datos['email'],
            ':pass'     => $hash,
            ':rol'      => $datos['rol_id'],
            ':sucursal' => !empty($datos['sucursal_asignada']) ? $datos['sucursal_asignada'] : null
        ]);
    }

    /**
     * Cuenta el total de registros para calcular las páginas
     */
    public function contarFiltradosAdmin($busqueda = '', $rol_id = '', $sucursal_id = '', $estado = '', $confianza = '')
    {
        $sql = "SELECT COUNT(u.id) FROM usuarios u WHERE 1=1";
        $params = [];

        if (!empty($busqueda)) {
            $busquedaLimpia = preg_replace('/[^0-9kK]/', '', $busqueda);
            $sql .= " AND (u.nombre LIKE :q1 OR u.email LIKE :q2 OR REPLACE(REPLACE(u.rut, '.', ''), '-', '') LIKE :q3)";
            $params[':q1'] = "%$busqueda%";
            $params[':q2'] = "%$busqueda%";
            $params[':q3'] = "%$busquedaLimpia%";
        }
        if ($rol_id !== '') { $sql .= " AND u.rol_id = :rol"; $params[':rol'] = $rol_id; }
        if ($sucursal_id !== '') { $sql .= " AND u.sucursal_asignada = :sucursal"; $params[':sucursal'] = $sucursal_id; }
        if ($estado !== '') { $sql .= " AND u.estado = :estado"; $params[':estado'] = $estado; }
        if ($confianza !== '') { $sql .= " AND u.es_cliente_confianza = :confianza"; $params[':confianza'] = $confianza; }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    
}