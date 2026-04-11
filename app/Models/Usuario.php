<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Modelo Usuario
 * Responsabilidad: Gestión integral de perfiles, autenticación, trazabilidad de accesos
 * y datos de contacto (teléfonos/direcciones).
 */
class Usuario
{
    private $db;
    private $table = "usuarios";

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================
    // 🔍 SECCIÓN 1: BÚSQUEDA Y LECTURA PRINCIPAL
    // =========================================================

    public function getById($id)
    {
        // JOIN optimizado para traer nombres geográficos (útil en Checkout y Perfil)
        $sql = "SELECT u.*, 
                       c.nombre as nombre_comuna, 
                       p.nombre as nombre_provincia,
                       r.nombre as nombre_region
                FROM {$this->table} u
                LEFT JOIN comunas c ON u.comuna_id = c.id
                LEFT JOIN provincias p ON c.provincia_id = p.id
                LEFT JOIN regiones r ON p.region_id = r.id
                WHERE u.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE token_confirmacion = :token LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // =========================================================
    // 🔐 SECCIÓN 2: AUTENTICACIÓN Y SEGURIDAD
    // =========================================================

    public function login($email, $password)
    {
        $user = $this->getByEmail($email);

        if ($user && password_verify($password, $user->password)) {
            $this->registrarAcceso($user->id, true);
            return $user;
        }

        if ($user) {
            $this->registrarAcceso($user->id, false);
        }
        return false;
    }

    public function activarCuenta($token)
    {
        $sql = "UPDATE {$this->table} SET confirmado = 1, token_confirmacion = NULL 
                WHERE token_confirmacion = :token";
        return $this->db->prepare($sql)->execute([':token' => $token]);
    }

    public function generarTokenRecuperacion($email)
    {
        $user = $this->getByEmail($email);
        if (!$user) return false;

        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE {$this->table} SET reset_token = :token, reset_expire = :expire WHERE email = :email";
        $res = $this->db->prepare($sql)->execute([':token' => $token, ':expire' => $expire, ':email' => $email]);

        return $res ? $token : false;
    }

    public function getByResetToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE reset_token = :token AND reset_expire > :now LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token, ':now' => date('Y-m-d H:i:s')]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function actualizarPassword($id, $newPassword)
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} SET password = :pass, reset_token = NULL, reset_expire = NULL WHERE id = :id";
        return $this->db->prepare($sql)->execute([':pass' => $hash, ':id' => $id]);
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
        } catch (PDOException $e) {
            error_log("Error log_accesos: " . $e->getMessage());
        }
    }

    // =========================================================
    // ✍️ SECCIÓN 3: ESCRITURA Y MANTENIMIENTO
    // =========================================================

    public function crear($data)
    {
        $token = bin2hex(random_bytes(32));
        $sql = "INSERT INTO {$this->table} 
                (nombre, rut, email, direccion, giro, latitud, longitud, password, rol, google_id, token_confirmacion, confirmado) 
                VALUES (:nombre, :rut, :email, :dir, :giro, :lat, :lng, :pass, :rol, :google, :token, 0)";

        $this->db->prepare($sql)->execute([
            ':nombre' => $data['nombre'],
            ':rut'    => $data['rut'] ?? '',
            ':email'  => $data['email'],
            ':dir'    => $data['direccion'],
            ':giro'   => $data['giro'] ?? '',
            ':lat'    => $data['latitud'] ?? null,
            ':lng'    => $data['longitud'] ?? null,
            ':pass'   => $data['password'],
            ':rol'    => $data['rol'] ?? 'cliente',
            ':google' => $data['google_id'] ?? null,
            ':token'  => $token
        ]);

        $usuarioId = $this->db->lastInsertId();

        if (!empty($data['telefono'])) {
            $this->agregarTelefono($usuarioId, $data['telefono'], 'Principal', 1);
        }

        return ['id' => $usuarioId, 'token' => $token];
    }

    public function actualizar($id, $datos)
    {
        $sql = "UPDATE usuarios SET nombre = :nombre, rut = :rut, email = :email, 
                direccion = :direccion, comuna_id = :comuna_id, giro = :giro WHERE id = :id";

        return $this->db->prepare($sql)->execute([
            ':nombre'    => $datos['nombre'],
            ':rut'       => $datos['rut'],
            ':email'     => $datos['email'],
            ':direccion' => $datos['direccion'],
            ':comuna_id' => $datos['comuna_id'],
            ':giro'      => $datos['giro'] ?? null,
            ':id'        => $id
        ]);
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
}