<?php

namespace Cycsa\Modulos\Clientes\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ClienteModelo extends ModeloBase {
    
    // 🔍 OBTENER TODOS (CON BUSCADOR INTEGRADO Y PDO ESTRICTO)
    public function obtenerTodos(string $busqueda = ''): array {
        if ($busqueda !== '') {
            $sql = "SELECT id, nombre_razon_social, identificacion, email, telefono, direccion, activo 
                    FROM clientes 
                    WHERE nombre_razon_social LIKE :q1 
                       OR identificacion LIKE :q2 
                       OR email LIKE :q3 
                    ORDER BY id DESC";
            
            $stmt = $this->db->prepare($sql);
            
            // Preparamos el término de búsqueda de forma segura
            $termino = '%' . trim($busqueda) . '%';
            
            // Pasamos las 3 variables separadas para respetar el EMULATE_PREPARES=false
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino
            ]);
        } else {
            $sql = "SELECT id, nombre_razon_social, identificacion, email, telefono, direccion, activo 
                    FROM clientes 
                    ORDER BY id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✏️ OBTENER UN SOLO CLIENTE POR SU ID
    public function obtenerPorId(int $id) {
        $sql = "SELECT * FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🛡️ CONTROL DE DUPLICADOS: Verificar si un email ya existe (Soporta exclusión al editar)
    public function emailExiste(string $email, int $id_excluir = 0): bool {
        $sql = "SELECT COUNT(*) FROM clientes WHERE email = :email AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email), 'id' => $id_excluir]); 
        return $stmt->fetchColumn() > 0;
    }

    // 🛡️ CONTROL DE DUPLICADOS: Verificar si la identificación ya existe (Soporta exclusión al editar)
    public function identificacionExiste(string $identificacion, int $id_excluir = 0): bool {
        if (empty(trim($identificacion))) return false;
        $sql = "SELECT COUNT(*) FROM clientes WHERE identificacion = :identificacion AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['identificacion' => trim($identificacion), 'id' => $id_excluir]);
        return $stmt->fetchColumn() > 0;
    }

    // 💾 GUARDAR NUEVO CLIENTE (Con mapeo seguro)
    public function guardar(array $datos): bool {
        $sql = "INSERT INTO clientes (nombre_razon_social, identificacion, email, telefono, direccion, activo) 
                VALUES (:nombre, :identificacion, :email, :telefono, :direccion, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre'         => trim($datos['nombre_razon_social']),
            'identificacion' => !empty(trim($datos['identificacion'])) ? trim($datos['identificacion']) : null,
            'email'          => !empty(trim($datos['email'])) ? trim($datos['email']) : null,
            'telefono'       => !empty(trim($datos['telefono'])) ? trim($datos['telefono']) : null,
            'direccion'      => !empty(trim($datos['direccion'])) ? trim($datos['direccion']) : null
        ]);
    }

    // ✏️ ACTUALIZAR CLIENTE (Con mapeo seguro)
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE clientes 
                SET nombre_razon_social = :nombre, 
                    identificacion = :identificacion, 
                    email = :email, 
                    telefono = :telefono, 
                    direccion = :direccion 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre'         => trim($datos['nombre_razon_social']),
            'identificacion' => !empty(trim($datos['identificacion'])) ? trim($datos['identificacion']) : null,
            'email'          => !empty(trim($datos['email'])) ? trim($datos['email']) : null,
            'telefono'       => !empty(trim($datos['telefono'])) ? trim($datos['telefono']) : null,
            'direccion'      => !empty(trim($datos['direccion'])) ? trim($datos['direccion']) : null,
            'id'             => $id
        ]);
    }
}