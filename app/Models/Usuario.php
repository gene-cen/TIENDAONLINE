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
        // Usamos FETCH_OBJ para mantener consistencia con el controlador ($user->id)
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

    // NUEVO: Buscar usuario por email (usado por Google Callback)
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // NUEVO: Buscar usuario por ID (para refrescar sesión)
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function crear($data)
    {
        // Generamos un token único para validar el correo
        $token = bin2hex(random_bytes(32));

        $sql = "INSERT INTO {$this->table} 
                (nombre, rut, email, telefono, direccion, password, rol, google_id, token_confirmacion, confirmado) 
                VALUES (:nombre, :rut, :email, :tel, :dir, :pass, :rol, :google, :token, 0)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'   => $data['nombre'],
            ':rut'      => $data['rut'],
            ':email'    => $data['email'],
            ':tel'      => $data['telefono'],
            ':dir'      => $data['direccion'],
            ':pass'     => $data['password'],
            ':rol'      => $data['rol'] ?? 'cliente',
            ':google'   => $data['google_id'] ?? null,
            ':token'    => $token
        ]);

        // Retornamos el ID y el Token para poder "enviar" el correo
        return ['id' => $this->db->lastInsertId(), 'token' => $token];
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

    // Validar cuenta por token
    public function activarCuenta($token) {
        // 1. Buscamos si existe alguien con ese token
        $sql = "SELECT id FROM {$this->table} WHERE token_confirmacion = :token LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user) {
            // 2. Si existe, lo activamos (confirmado = 1) y borramos el token para que no se use 2 veces
            $updateSql = "UPDATE {$this->table} 
                          SET confirmado = 1, token_confirmacion = NULL 
                          WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            return $updateStmt->execute([':id' => $user->id]);
        }

        return false; // Token inválido o ya usado
    }

    // Actualizar perfil del usuario
    public function actualizar($id, $data) {
        $sql = "UPDATE {$this->table} 
                SET nombre = :nombre, 
                    telefono = :telefono, 
                    direccion = :direccion 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'    => $data['nombre'],
            ':telefono'  => $data['telefono'],
            ':direccion' => $data['direccion'],
            ':id'        => $id
        ]);
    }

    // 1. Generar token de recuperación
    public function generarTokenRecuperacion($email) {
        // Verificar si el correo existe
        $user = $this->getByEmail($email);
        if (!$user) return false;

        // Generar token y fecha de expiración (1 hora)
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

    // 2. Buscar usuario por token válido
    public function getByResetToken($token) {
        $now = date('Y-m-d H:i:s');
        
        // Buscamos token que coincida Y que no haya expirado todavía
        $sql = "SELECT * FROM {$this->table} 
                WHERE reset_token = :token AND reset_expire > :now LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token, ':now' => $now]);
        
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    // 3. Actualizar contraseña final
    public function actualizarPassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizamos la pass y borramos el token para que no se use de nuevo
        $sql = "UPDATE {$this->table} 
                SET password = :pass, reset_token = NULL, reset_expire = NULL 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':pass' => $hash, ':id' => $id]);
    }
}
