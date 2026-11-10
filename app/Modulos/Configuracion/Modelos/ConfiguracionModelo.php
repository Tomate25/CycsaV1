<?php

namespace Cycsa\Modulos\Configuracion\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ConfiguracionModelo extends ModeloBase {

    public function obtenerPlantillasEnsayos(): array {
        $sql = '
            SELECT f.id, f.nombre, f.codigo_formato, f.procedimientos, f.archivo_markdown, 
                   f.configuracion_json, f.version_formato, f.fecha_actualizacion,
                   COUNT(p.id) AS total_productos
            FROM formatos_ensayos f
            LEFT JOIN productos p ON p.formato_id = f.id AND p.activo = 1
            GROUP BY f.id, f.nombre, f.codigo_formato, f.procedimientos, f.archivo_markdown, 
                     f.configuracion_json, f.version_formato, f.fecha_actualizacion
            ORDER BY f.nombre ASC
        ';
        $filas = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $config = json_decode($fila['configuracion_json'] ?? '', true);
            $fila['norma'] = is_array($config) ? ($config['norma'] ?? '') : '';
            $fila['codigo_vigente'] = is_array($config) ? ($config['codigo_formato'] ?? $fila['codigo_formato']) : $fila['codigo_formato'];
            $fila['orientacion'] = is_array($config) ? ($config['orientacion'] ?? 'horizontal') : 'horizontal';
            $fila['columnas_count'] = is_array($config) && !empty($config['columns']) ? count($config['columns']) : 0;
            $fila['total_productos'] = (int)($fila['total_productos'] ?? 0);
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
        
        $stmtProds = $this->db->prepare('SELECT id, no_item, nombre_comercial, ensayo_servicio FROM productos WHERE formato_id = :id AND activo = 1 ORDER BY no_item ASC');
        $stmtProds->execute(['id' => $id]);
        $fila['productos_vinculados'] = $stmtProds->fetchAll(PDO::FETCH_ASSOC);
        return $fila;
    }

    public function guardarPlantillaEnsayo(int $id, array $config, ?string $nombre = null, ?array $productosIds = null): bool {
        $stmt = $this->db->prepare('
            UPDATE formatos_ensayos 
            SET nombre = COALESCE(:nombre, nombre),
                configuracion_json = :json, 
                codigo_formato = :codigo, 
                procedimientos = :procedimientos, 
                version_formato = :version, 
                fecha_actualizacion = CURRENT_TIMESTAMP 
            WHERE id = :id
        ');
        $nombreFinal = ($nombre !== null && trim($nombre) !== '') ? trim($nombre) : null;
        $stmt->execute([
            'nombre' => $nombreFinal,
            'json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'codigo' => $config['codigo_formato'],
            'procedimientos' => $config['ensayo_titulo'],
            'version' => $config['version_documento'],
            'id' => $id,
        ]);

        $this->sincronizarArchivoYEsquemaFisico($id, $config, $nombreFinal);

        if ($productosIds !== null) {
            $stmtClear = $this->db->prepare('UPDATE productos SET formato_id = NULL WHERE formato_id = :id');
            $stmtClear->execute(['id' => $id]);

            if (!empty($productosIds)) {
                $idsClean = array_map('intval', array_filter($productosIds, 'is_numeric'));
                if (!empty($idsClean)) {
                    $inQuery = implode(',', $idsClean);
                    $this->db->query("UPDATE productos SET formato_id = {$id} WHERE id IN ({$inQuery})");
                }
            }
        }

        return true;
    }

    public function crearPlantillaEnsayo(string $nombre, array $config, array $productosIds = []): int {
        $slug = $this->generarSlug($nombre);
        $archivoMd = $slug . '.md';
        
        $stmtCheck = $this->db->prepare('SELECT COUNT(*) FROM formatos_ensayos WHERE archivo_markdown = :arch');
        $stmtCheck->execute(['arch' => $archivoMd]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $archivoMd = $slug . '_' . time() . '.md';
        }

        $stmt = $this->db->prepare('
            INSERT INTO formatos_ensayos (nombre, codigo_formato, procedimientos, archivo_markdown, configuracion_json, version_formato, fecha_creacion, fecha_actualizacion)
            VALUES (:nombre, :codigo, :procedimientos, :archivo, :json, :version, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([
            'nombre' => trim($nombre),
            'codigo' => $config['codigo_formato'],
            'procedimientos' => $config['ensayo_titulo'],
            'archivo' => $archivoMd,
            'json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'version' => $config['version_documento'] ?? 'V1R1'
        ]);
        $id = (int)$this->db->lastInsertId();

        $this->sincronizarArchivoYEsquemaFisico($id, $config, trim($nombre), $archivoMd);

        if (!empty($productosIds)) {
            $idsClean = array_map('intval', array_filter($productosIds, 'is_numeric'));
            if (!empty($idsClean)) {
                $inQuery = implode(',', $idsClean);
                $this->db->query("UPDATE productos SET formato_id = {$id} WHERE id IN ({$inQuery})");
            }
        }

        return $id;
    }

    public function duplicarPlantillaEnsayo(int $id): int {
        $original = $this->obtenerPlantillaPorId($id);
        if (!$original) {
            throw new \InvalidArgumentException('Formato base no encontrado para duplicar.');
        }
        $nuevoNombre = $original['nombre'] . ' (Copia)';
        $config = $original['configuracion'];
        $config['version_documento'] = 'V1R1';
        return $this->crearPlantillaEnsayo($nuevoNombre, $config, []);
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

    public function sincronizarArchivoYEsquemaFisico(int $id, array $config, ?string $nombre = null, ?string $archivoMd = null): void {
        try {
            if ($archivoMd === null) {
                $stmt = $this->db->prepare('SELECT archivo_markdown, nombre FROM formatos_ensayos WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $archivoMd = $row['archivo_markdown'] ?? '';
                if ($nombre === null) {
                    $nombre = $row['nombre'] ?? '';
                }
            }
            if (empty($archivoMd)) return;

            $baseDir = dirname(__DIR__, 4);
            $schemaFile = $baseDir . '/database/ensayos/formatos_schema.json';
            $detailedFile = $baseDir . '/database/ensayos/formatos_schema_detailed.json';
            $mdFile = $baseDir . '/database/ensayos/' . $archivoMd;

            if (file_exists($schemaFile)) {
                $schemas = json_decode(file_get_contents($schemaFile), true) ?: [];
                if (isset($schemas[$archivoMd])) {
                    $schemas[$archivoMd] = $config;
                    file_put_contents($schemaFile, json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

            if (file_exists($detailedFile)) {
                $detailed = json_decode(file_get_contents($detailedFile), true) ?: [];
                if (isset($detailed[$archivoMd])) {
                    $detailed[$archivoMd] = $config;
                    file_put_contents($detailedFile, json_encode($detailed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

            if (file_exists($mdFile)) {
                $mdContent = $this->generarContenidoMarkdown($nombre ?? 'Formato de Ensayo', $config);
                file_put_contents($mdFile, $mdContent);
            }
        } catch (\Throwable $e) {
            error_log('Aviso: no se pudo sincronizar archivo fisico de plantilla: ' . $e->getMessage());
        }
    }

    private function generarSlug(string $texto): string {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        $texto = preg_replace('/[^a-z0-9]+/i', '_', $texto);
        $texto = trim($texto, '_');
        return $texto ?: 'nuevo_formato_' . time();
    }

    private function generarContenidoMarkdown(string $nombre, array $config): string {
        $codigo = $config['codigo_formato'] ?? 'CYCSA-RT-FM-22';
        $titulo = $config['titulo_informe'] ?? 'INFORME DE ENSAYO';
        $ensayoTitulo = $config['ensayo_titulo'] ?? $nombre;
        $norma = $config['norma'] ?? '';
        $metodoMuestreo = $config['metodo_muestreo'] ?? '';
        $tipoMuestra = $config['tipo_muestra'] ?? '';
        $cols = $config['columns'] ?? ['Código laboratorio', 'Nombre muestra', 'Resultado'];
        $methods = $config['column_methods'] ?? [];
        $disclaimer = $config['disclaimer'] ?? 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.  ** Información Proporcionada por el cliente.';
        $notas = $config['notas'] ?? [];
        $firmante = $config['firmante_nombre'] ?? 'Ing. Noel Quintana Lira';
        $cargo = $config['firmante_cargo'] ?? 'Gerente General';

        $headerMetodos = [];
        foreach ($cols as $col) {
            $headerMetodos[] = $methods[$col] ?? '';
        }

        $md = "# Formato de Ensayo: {$nombre}\n\n";
        $md .= "## {$codigo}\n\n";
        $md .= "## {$titulo}\n\n";
        $md .= "** Nombre del cliente:\t\t** Proyecto:\n";
        $md .= "** Dirección:\t\t** Fecha muestreo:\n";
        $md .= "Fecha  recepción ítems de ensayo:\t\tFecha de ejecución:\n";
        $md .= "Tipo de muestra:\t{$tipoMuestra}\tFecha de emisión del informe:\n";
        $md .= "** Procedimiento de muestreo:\tAleatorio\tMuestra tomada por:\tLaboratorio - Consultoría y Construcción S.A. CYCSA.\n";
        $md .= "Ensayo realizado:\t{$ensayoTitulo}\tEnsayado por:\n";
        $md .= "** Ubicación:\n";
        $md .= "Método de muestreo:\t{$metodoMuestreo}\n\n";

        $md .= "| " . implode(" | ", array_map(fn($m) => $m !== '' ? $m : ' ', $headerMetodos)) . " |\n";
        $md .= "|" . str_repeat(" --- |", count($cols)) . "\n";
        $md .= "| " . implode(" | ", $cols) . " |\n";
        $md .= "|" . str_repeat("  |", count($cols)) . "\n\n";

        $md .= "{$disclaimer}\n";
        foreach ($notas as $nota) {
            $md .= "{$nota}\n";
        }
        $md .= "--------------------------------Última Línea---------------------------------\n\n";
        $md .= "## {$firmante}\n\n";
        $md .= "{$cargo}\n";
        $md .= "Página 1 de 1\n";

        return $md;
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
     * Inserta un nuevo valor de configuración comercial.
     */
    public function agregar(string $tipo, string $valor): int {
        $sql = "INSERT INTO configuracion_comercial (tipo, valor, fecha_creacion) 
                VALUES (:tipo, :valor, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tipo' => $tipo,
            'valor' => $valor
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un valor existente por su ID.
     */
    public function actualizar(int $id, string $valor): bool {
        $sql = "UPDATE configuracion_comercial 
                SET valor = :valor 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'valor' => $valor
        ]);
    }

    /**
     * Elimina un valor por su ID.
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM configuracion_comercial WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Obtiene todos los vehículos de muestreo.
     */
    public function obtenerVehiculos(): array {
        $sql = "SELECT * FROM vehiculos_muestreo ORDER BY placa ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agrega un nuevo vehículo de muestreo.
     */
    public function agregarVehiculo(array $datos): int {
        $sql = "INSERT INTO vehiculos_muestreo (placa, modelo, anio, rendimiento_km_galon, fecha_creacion)
                VALUES (:placa, :modelo, :anio, :rendimiento, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'placa' => $datos['placa'],
            'modelo' => $datos['modelo'],
            'anio' => $datos['anio'],
            'rendimiento' => $datos['rendimiento_km_galon']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un vehículo existente.
     */
    public function actualizarVehiculo(int $id, array $datos): bool {
        $sql = "UPDATE vehiculos_muestreo 
                SET placa = :placa, modelo = :modelo, anio = :anio, rendimiento_km_galon = :rendimiento
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'placa' => $datos['placa'],
            'modelo' => $datos['modelo'],
            'anio' => $datos['anio'],
            'rendimiento' => $datos['rendimiento_km_galon']
        ]);
    }

    /**
     * Elimina un vehículo por su ID.
     */
    public function eliminarVehiculo(int $id): bool {
        $sql = "DELETE FROM vehiculos_muestreo WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
