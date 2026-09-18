<?php

namespace Cycsa\Modulos\Configuracion\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ConfiguracionModelo extends ModeloBase {

    public function obtenerPlantillasEnsayos(): array {
        $filas = $this->db->query('SELECT id, nombre, codigo_formato, procedimientos, archivo_markdown, configuracion_json, version_formato, fecha_actualizacion FROM formatos_ensayos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $config = json_decode($fila['configuracion_json'] ?? '', true);
            $fila['norma'] = is_array($config) ? ($config['norma'] ?? '') : '';
            $fila['codigo_vigente'] = is_array($config) ? ($config['codigo_formato'] ?? $fila['codigo_formato']) : $fila['codigo_formato'];
        }
        unset($fila);
        return $filas;
    }

    public function obtenerPlantillaPorId(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id, nombre, codigo_formato, procedimientos, archivo_markdown, configuracion_json, version_formato, fecha_actualizacion FROM formatos_ensayos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) return null;
        $fila['configuracion'] = obtenerEsquemaPlantillaEnsayo($fila['archivo_markdown'], $id);
        return $fila;
    }

    public function guardarPlantillaEnsayo(int $id, array $config): bool {
        $stmt = $this->db->prepare('UPDATE formatos_ensayos SET configuracion_json = :json, codigo_formato = :codigo, procedimientos = :procedimientos, version_formato = :version, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            'json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'codigo' => $config['codigo_formato'],
            'procedimientos' => $config['ensayo_titulo'],
            'version' => $config['version_documento'],
            'id' => $id,
        ]);
        return true;
    }

    public function restablecerPlantillaOriginal(int $id): bool {
        $stmt = $this->db->prepare('SELECT archivo_markdown FROM formatos_ensayos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $archivo = $stmt->fetchColumn();
        if (!$archivo) return false;
        $ruta = dirname(__DIR__, 4) . '/database/ensayos/formatos_schema.json';
        $base = json_decode(file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($base[$archivo])) return false;
        $config = $base[$archivo];
        $config['version_documento'] = 'V1R2';
        $config['titulo_informe'] = 'INFORME DE ENSAYO';
        $config['subtitulo_laboratorio'] = 'Laboratorio de Ensayos y Control de Calidad';
        $config['firmante_nombre'] = 'Ing. Noel Quintana Lira';
        $config['firmante_cargo'] = 'Gerente General';
        return $this->guardarPlantillaEnsayo($id, $config);
    }
    
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

    // --- Gestión de Técnicos ---
    public function obtenerTecnicos(): array {
        $sql = "SELECT id, nombre, activo, fecha_registro FROM tecnicos ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agregarTecnico(string $nombre): bool {
        $sql = "INSERT INTO tecnicos (nombre) VALUES (:nombre)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['nombre' => trim($nombre)]);
    }

    public function eliminarTecnico(int $id): bool {
        $sql = "DELETE FROM tecnicos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // --- Gestión de Vehículos ---
    public function obtenerVehiculos(): array {
        $sql = "SELECT id, placa, marca, modelo, activo, fecha_registro FROM vehiculos ORDER BY placa ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agregarVehiculo(string $placa, string $marca, string $modelo): bool {
        $sql = "INSERT INTO vehiculos (placa, marca, modelo) VALUES (:placa, :marca, :modelo)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'placa' => strtoupper(trim($placa)),
            'marca' => trim($marca),
            'modelo' => trim($modelo)
        ]);
    }

    public function eliminarVehiculo(int $id): bool {
        $sql = "DELETE FROM vehiculos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function actualizarTecnico(int $id, string $nombre): bool {
        $sql = "UPDATE tecnicos SET nombre = :nombre WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['nombre' => trim($nombre), 'id' => $id]);
    }

    public function actualizarVehiculo(int $id, string $placa, string $marca, string $modelo): bool {
        $sql = "UPDATE vehiculos SET placa = :placa, marca = :marca, modelo = :modelo WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'placa' => strtoupper(trim($placa)),
            'marca' => trim($marca),
            'modelo' => trim($modelo),
            'id' => $id
        ]);
    }
}
