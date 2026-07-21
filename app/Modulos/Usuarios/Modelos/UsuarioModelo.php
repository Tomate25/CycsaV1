<?php

namespace Cycsa\Modulos\Usuarios\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class UsuarioModelo extends ModeloBase {
    
    // 🔍 OBTENER TODOS CON BUSCADOR
    public function obtenerTodos(string $busqueda = ''): array {
        if ($busqueda !== '') {
            $sql = "SELECT u.id, u.nombre, u.email, u.activo, u.bloqueado, r.nombre AS rol 
                    FROM usuarios u 
                    INNER JOIN roles r ON u.id_rol = r.id 
                    WHERE u.nombre LIKE :q1 
                       OR u.email LIKE :q2 
                       OR r.nombre LIKE :q3 
                    ORDER BY u.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino
            ]);
        } else {
            $sql = "SELECT u.id, u.nombre, u.email, u.activo, u.bloqueado, r.nombre AS rol 
                    FROM usuarios u 
                    INNER JOIN roles r ON u.id_rol = r.id 
                    ORDER BY u.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔑 OBTENER UN SOLO USUARIO POR SU ID
    public function obtenerPorId(int $id) {
        $sql = "SELECT * FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 📋 OBTENER ROLES
    public function obtenerRoles() {
        $sql = "SELECT id, nombre, permisos FROM roles ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🛡️ VERIFICAR DUPLICADOS DE EMAIL
    public function emailExiste(string $email, int $id_excluir = 0): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email), 'id' => $id_excluir]);
        return $stmt->fetchColumn() > 0;
    }

    // 💾 GUARDAR NUEVO USUARIO
    public function guardarUsuario($nombre, $email, $password, $id_rol, $permisos = null) {
        $hashSeguro = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password, id_rol, activo, permisos) 
                VALUES (:nombre, :email, :password, :id_rol, 1, :permisos)";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $hashSeguro,
            'id_rol' => $id_rol,
            'permisos' => $permisos
        ]);
    }

    // ✏️ ACTUALIZAR USUARIO EXISTENTE
    public function actualizar(int $id, array $datos): bool {
        if (!empty(trim($datos['password'] ?? ''))) {
            $hashSeguro = password_hash($datos['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios 
                    SET nombre = :nombre, 
                        email = :email, 
                        password = :password, 
                        id_rol = :id_rol, 
                        activo = :activo,
                        permisos = :permisos
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'nombre'   => trim($datos['nombre']),
                'email'    => trim($datos['email']),
                'password' => $hashSeguro,
                'id_rol'   => $datos['id_rol'],
                'activo'   => $datos['activo'],
                'permisos' => $datos['permisos'] ?? null,
                'id'       => $id
            ]);
        } else {
            $sql = "UPDATE usuarios 
                    SET nombre = :nombre, 
                        email = :email, 
                        id_rol = :id_rol, 
                        activo = :activo,
                        permisos = :permisos
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'nombre' => trim($datos['nombre']),
                'email'  => trim($datos['email']),
                'id_rol' => $datos['id_rol'],
                'activo' => $datos['activo'],
                'permisos' => $datos['permisos'] ?? null,
                'id'     => $id
            ]);
        }
    }

    // 🗑️ DESACTIVAR USUARIO
    public function desactivar(int $id): bool {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}