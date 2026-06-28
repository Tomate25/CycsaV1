<?php

namespace Cycsa\Modulos\Usuarios\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class RolModelo extends ModeloBase {
    
    // 🔍 OBTENER TODOS LOS ROLES
    public function obtenerTodos(): array {
        $sql = "SELECT * FROM roles ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 📋 OBTENER UN ROL POR SU ID
    public function obtenerPorId(int $id) {
        $sql = "SELECT * FROM roles WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🛡️ VERIFICAR SI EL NOMBRE DEL ROL YA EXISTE
    public function nombreExiste(string $nombre, int $id_excluir = 0): bool {
        $sql = "SELECT COUNT(*) FROM roles WHERE nombre = :nombre AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => trim($nombre),
            'id' => $id_excluir
        ]);
        return $stmt->fetchColumn() > 0;
    }

    // 💾 CREAR UN NUEVO ROL
    public function guardar(string $nombre, string $descripcion, ?string $permisosJson = null): bool {
        $sql = "INSERT INTO roles (nombre, descripcion, permisos) VALUES (:nombre, :descripcion, :permisos)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre' => trim($nombre),
            'descripcion' => trim($descripcion),
            'permisos' => $permisosJson
        ]);
    }

    // ✏️ ACTUALIZAR UN ROL EXISTENTE
    public function actualizar(int $id, string $nombre, string $descripcion, ?string $permisosJson = null): bool {
        $sql = "UPDATE roles SET nombre = :nombre, descripcion = :descripcion, permisos = :permisos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre' => trim($nombre),
            'descripcion' => trim($descripcion),
            'permisos' => $permisosJson,
            'id' => $id
        ]);
    }

    // 🗑️ ELIMINAR ROL (CON BARRERAS DE SEGURIDAD)
    public function eliminar(int $id): bool {
        // Impedir eliminar roles fundamentales del sistema (ID 1: Admin, ID 2: Vendedor)
        if ($id === 1 || $id === 2) {
            return false;
        }

        // Verificar si hay usuarios asociados a este rol
        $sqlCheck = "SELECT COUNT(*) FROM usuarios WHERE id_rol = :id_rol";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id_rol' => $id]);
        if ($stmtCheck->fetchColumn() > 0) {
            return false; // No se puede eliminar si hay usuarios usándolo
        }

        $sql = "DELETE FROM roles WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
