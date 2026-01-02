<?php
namespace App\Models;

use PDO;

class Usuario {
    private $db;
    private $table = "usuarios";

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($email, $password) {
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
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // NUEVO: Buscar usuario por ID (para refrescar sesión)
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // NUEVO: Crear usuario desde Google
    public function crear($data) {
        $sql = "INSERT INTO {$this->table} (nombre, email, password, rol, google_id) 
                VALUES (:nombre, :email, :password, :rol, :google_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'    => $data['nombre'],
            ':email'     => $data['email'],
            ':password'  => $data['password'],
            ':rol'       => $data['rol'] ?? 'cliente',
            ':google_id' => $data['google_id'] ?? null
        ]);
        
        $userId = $this->db->lastInsertId();
        $this->registrarAcceso($userId, true); // Registramos el primer acceso
        return $userId;
    }

    private function registrarAcceso($userId, $exitoso) {
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
}