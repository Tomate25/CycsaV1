<?php

namespace Cycsa\Modulos\Configuracion\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ConfiguracionModelo extends ModeloBase {
    
    /**
     * Obtiene todos los valores de configuración comercial filtrados por tipo.
     */
    public function obtenerPorTipo(string $tipo): array {
        $sql = "SELECT id, tipo, valor, fecha_creacion 
                FROM configuracion_comercial 
                WHERE tipo = :tipo 
                ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tipo' => $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro de configuración por su ID.
     */
    public function obtenerPorId(int $id) {
        $sql = "SELECT * FROM configuracion_comercial WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un nuevo valor de configuración.
     */
    public function guardar(string $tipo, string $valor): bool {
        $sql = "INSERT INTO configuracion_comercial (tipo, valor) VALUES (:tipo, :valor)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo' => $tipo,
            'valor' => trim($valor)
        ]);
    }

    /**
     * Actualiza un valor de configuración.
     */
    public function actualizar(int $id, string $valor): bool {
        $sql = "UPDATE configuracion_comercial SET valor = :valor WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'valor' => trim($valor),
            'id' => $id
        ]);
    }

    /**
     * Elimina un valor de configuración.
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM configuracion_comercial WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
