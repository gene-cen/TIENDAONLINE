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
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->password)) {
            $this->registrarAcceso($user->id, true);
            return $user;
        }

        if ($user) {
            $this->registrarAcceso($user->id, false);
        }
        return false;
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