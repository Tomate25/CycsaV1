<?php

namespace Cycsa\Modulos\Usuarios\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class UsuarioModelo extends ModeloBase {
    
    public function obtenerTodos() {
        $sql = "SELECT u.id, u.nombre, u.email, u.activo, r.nombre AS rol 
                FROM usuarios u 
                INNER JOIN roles r ON u.id_rol = r.id 
                ORDER BY u.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRoles() {
        $sql = "SELECT id, nombre FROM roles ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarUsuario($nombre, $email, $password, $id_rol) {
        // Encriptamos la contraseña por seguridad
        $hashSeguro = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password, id_rol, activo) 
                VALUES (:nombre, :email, :password, :id_rol, 1)";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $hashSeguro,
            'id_rol' => $id_rol
        ]);
    }
}