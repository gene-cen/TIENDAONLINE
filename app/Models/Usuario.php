<?php

namespace App\Models;

use PDO;

class Usuario
{
    private $db;
    private $table = "usuarios";

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($email, $password)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user && password_verify($password, $user->password)) {
            $this->registrarAcceso($user->id, true);
            return $user;
        }

        if ($user) {
            $this->registrarAcceso($user->id, false);
        }
        return false;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // =========================================================
    // MODIFICADO: TRAER DATOS GEOGRÁFICOS (Checkout)
    // =========================================================
    public function getById($id)
    {
        // Hacemos JOIN para traer los nombres de Comuna y Región
        // Esto permite mostrar "La Calera, Valparaíso" en el checkout
        $sql = "SELECT u.*, 
                       c.nombre as nombre_comuna, 
                       p.nombre as nombre_provincia,
                       r.nombre as nombre_region
                FROM {$this->table} u
                LEFT JOIN comunas c ON u.comuna_id = c.id
                LEFT JOIN provincias p ON c.provincia_id = p.id
                LEFT JOIN regiones r ON p.region_id = r.id
                WHERE u.id = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE token_confirmacion = :token LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function activarCuenta($token)
    {
        $sql = "UPDATE {$this->table} 
                SET confirmado = 1, token_confirmacion = NULL 
                WHERE token_confirmacion = :token";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':token' => $token]);
    }
    public function crear($data)
    {
        $token = bin2hex(random_bytes(32));

        // 1. Quitamos ':tel' de la consulta y de la lista de columnas
        $sql = "INSERT INTO {$this->table} 
            (nombre, rut, email, direccion, giro, latitud, longitud, password, rol, google_id, token_confirmacion, confirmado) 
            VALUES 
            (:nombre, :rut, :email, :dir, :giro, :lat, :lng, :pass, :rol, :google, :token, 0)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'   => $data['nombre'],
            ':rut'      => $data['rut'] ?? '',
            ':email'    => $data['email'],
            ':dir'      => $data['direccion'],
            ':giro'     => $data['giro'] ?? '',
            ':lat'      => $data['latitud'] ?? null,
            ':lng'      => $data['longitud'] ?? null,
            ':pass'     => $data['password'],
            ':rol'      => $data['rol'] ?? 'cliente',
            ':google'   => $data['google_id'] ?? null,
            ':token'    => $token
        ]);

        $usuarioId = $this->db->lastInsertId();

        // 2. Insertamos el teléfono en la tabla correspondiente usando tu método existente
        if (!empty($data['telefono'])) {
            $this->agregarTelefono($usuarioId, $data['telefono'], 'Principal', 1);
        }

        return ['id' => $usuarioId, 'token' => $token];
    }
    private function registrarAcceso($userId, $exitoso)
    {
        $sql = "INSERT INTO log_accesos (usuario_id, ip_address, user_agent, exitoso) 
                VALUES (:uid, :ip, :ua, :exito)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid'   => $userId,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':ua'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ':exito' => $exitoso ? 1 : 0
        ]);
    }

    // En App\Models\Usuario.php

    public function actualizar($id, $datos)
    {
        // Eliminamos 'telefono' del array de datos para que no rompa la consulta
        // si es que viene desde el formulario del Perfil.
        $sql = "UPDATE usuarios SET 
                nombre = :nombre, 
                rut = :rut, 
                email = :email, 
                direccion = :direccion, 
                comuna_id = :comuna_id,
                giro = :giro
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':rut'       => $datos['rut'],
            ':email'     => $datos['email'],
            ':direccion' => $datos['direccion'],
            ':comuna_id' => $datos['comuna_id'],
            ':giro'      => $datos['giro'] ?? null,
            ':id'        => $id
        ]);
    }

    // --- RECUPERACIÓN DE CONTRASEÑA ---

    public function generarTokenRecuperacion($email)
    {
        $user = $this->getByEmail($email);
        if (!$user) return false;

        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE {$this->table} SET reset_token = :token, reset_expire = :expire WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            ':token'  => $token,
            ':expire' => $expire,
            ':email'  => $email
        ]);

        return $res ? $token : false;
    }

    public function getByResetToken($token)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE reset_token = :token AND reset_expire > :now LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token, ':now' => $now]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function actualizarPassword($id, $newPassword)
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE {$this->table} 
                SET password = :pass, reset_token = NULL, reset_expire = NULL 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':pass' => $hash, ':id' => $id]);
    }

    // ... tu código existente ...

    // NUEVO MÉTODO PARA CHECKOUT
    public function obtenerDirecciones($id_usuario)
    {
        // Asumiendo que existe la tabla 'direcciones_usuarios'
        $sql = "SELECT * FROM direcciones_usuarios 
                WHERE usuario_id = :uid AND activo = 1 
                ORDER BY es_principal DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ... código anterior ...

    // NUEVO: Obtener listado de comunas para el selector
    public function obtenerTodasLasComunas()
    {
        // Asumiendo que tienes la estructura: comunas -> provincias -> regiones
        $sql = "SELECT c.id, c.nombre, r.nombre as nombre_region 
                FROM comunas c 
                JOIN provincias p ON c.provincia_id = p.id 
                JOIN regiones r ON p.region_id = r.id 
                ORDER BY c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerComunasPorRegion($regionId)
    {
        $sql = "SELECT c.id, c.nombre 
            FROM comunas c 
            JOIN provincias p ON c.provincia_id = p.id 
            WHERE p.region_id = :rid 
            ORDER BY c.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $regionId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerComunasValparaiso()
    {
        // El ID 6 corresponde a Valparaíso según tu base de datos
        $sql = "SELECT c.id, c.nombre 
            FROM comunas c 
            JOIN provincias p ON c.provincia_id = p.id 
            WHERE p.region_id = 6 
            ORDER BY c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // En App/Models/Usuario.php

    public function getTelefonos($usuario_id)
    {
        $sql = "SELECT id, numero, alias, es_principal 
            FROM usuario_telefonos 
            WHERE usuario_id = ? 
            ORDER BY es_principal DESC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function agregarTelefono($usuario_id, $numero, $alias = 'Titular', $es_principal = 0)
    {
        $sql = "INSERT INTO usuario_telefonos (usuario_id, numero, alias, es_principal) 
            VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$usuario_id, $numero, $alias, $es_principal]);
    }

    // En App/Models/Usuario.php

    public function eliminarTelefono($tel_id, $usuario_id)
    {
        $sql = "DELETE FROM usuario_telefonos WHERE id = ? AND usuario_id = ? AND es_principal = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$tel_id, $usuario_id]);
    }

    public function obtenerTelefonoPorId($tel_id, $usuario_id)
    {
        $sql = "SELECT * FROM usuario_telefonos WHERE id = ? AND usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tel_id, $usuario_id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function actualizarTelefono($tel_id, $usuario_id, $numero, $alias)
    {
        $sql = "UPDATE usuario_telefonos SET numero = ?, alias = ? WHERE id = ? AND usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$numero, $alias, $tel_id, $usuario_id]);
    }

    // En App/Models/Usuario.php
    public function setTelefonoPrincipal($tel_id, $usuario_id)
    {
        // 1. Quitar principal actual
        $this->db->prepare("UPDATE usuario_telefonos SET es_principal = 0 WHERE usuario_id = ?")->execute([$usuario_id]);
        // 2. Setear el nuevo
        $sql = "UPDATE usuario_telefonos SET es_principal = 1 WHERE id = ? AND usuario_id = ?";
        return $this->db->prepare($sql)->execute([$tel_id, $usuario_id]);
    }
}
