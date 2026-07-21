<?php

namespace Cycsa\Modulos\Productos\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ProductoModelo extends ModeloBase {
    
    // 🔍 OBTENER TODOS LOS PRODUCTOS CON BUSCADOR Y FILTRO DE CATEGORÍA
    public function obtenerTodos(string $busqueda = '', string $categoria = '', int $soloActivos = 1): array {
        $sql = "SELECT p.*, f.codigo_formato AS formato_reporte, f.nombre AS formato_nombre 
                FROM productos p 
                LEFT JOIN formatos_ensayos f ON p.formato_id = f.id 
                WHERE 1=1";
        $params = [];
        
        if ($soloActivos) {
            $sql .= " AND p.activo = 1";
        }
        
        if ($busqueda !== '') {
            $sql .= " AND (p.nombre_comercial LIKE :q1 
                        OR p.ensayo_servicio LIKE :q2 
                        OR p.codigo_servicio LIKE :q3 
                        OR p.norma_astm LIKE :q4 
                        OR p.tipo_muestra LIKE :q5)";
            $termino = '%' . trim($busqueda) . '%';
            $params['q1'] = $termino;
            $params['q2'] = $termino;
            $params['q3'] = $termino;
            $params['q4'] = $termino;
            $params['q5'] = $termino;
        }
        
        if ($categoria !== '') {
            $sql .= " AND p.matriz_tipo = :categoria";
            $params['categoria'] = $categoria;
        }
        
        // Ordenamos por número de ítem de forma numérica/alfabética
        $sql .= " ORDER BY ABS(p.no_item) ASC, p.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 📂 OBTENER CATEGORÍAS ÚNICAS
    public function obtenerCategorias(): array {
        $sql = "SELECT DISTINCT matriz_tipo FROM productos WHERE matriz_tipo IS NOT NULL AND matriz_tipo != '' ORDER BY matriz_tipo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // 📋 OBTENER TODOS LOS FORMATOS DE ENSAYO DISPONIBLES
    public function obtenerFormatos(): array {
        $sql = "SELECT id, nombre, codigo_formato, archivo_markdown FROM formatos_ensayos ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ✏️ OBTENER UN SOLO PRODUCTO POR SU ID
    public function obtenerPorId(int $id) {
        $sql = "SELECT p.*, f.codigo_formato AS formato_reporte, f.nombre AS formato_nombre 
                FROM productos p 
                LEFT JOIN formatos_ensayos f ON p.formato_id = f.id 
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 🛡️ CONTROL DE DUPLICADOS: Verificar si un código de servicio ya existe (Soporta exclusión al editar)
    public function codigoExiste(string $codigo, int $id_excluir = 0): bool {
        if (empty(trim($codigo))) return false;
        $sql = "SELECT COUNT(*) FROM productos WHERE codigo_servicio = :codigo AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => trim($codigo), 'id' => $id_excluir]);
        return $stmt->fetchColumn() > 0;
    }
    
    // 💾 GUARDAR NUEVO PRODUCTO
    public function guardar(array $datos): bool {
        $sql = "INSERT INTO productos (
                    no_item, formato_id, tipo_muestra, matriz_tipo, tipo_muestreo, ensayo_servicio, 
                    nombre_comercial, condiciones_muestra, codigo_servicio, estatus, 
                    norma_astm, procedimiento_muestreo, codigo_hoja_campo, unidad_medida, 
                    precio, observaciones, activo
                ) VALUES (
                    :no_item, :formato_id, :tipo_muestra, :matriz_tipo, :tipo_muestreo, :ensayo_servicio, 
                    :nombre_comercial, :condiciones_muestra, :codigo_servicio, :estatus, 
                    :norma_astm, :procedimiento_muestreo, :codigo_hoja_campo, :unidad_medida, 
                    :precio, :observaciones, 1
                )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'no_item'                => !empty(trim($datos['no_item'])) ? trim($datos['no_item']) : null,
            'formato_id'             => !empty($datos['formato_id']) ? intval($datos['formato_id']) : null,
            'tipo_muestra'           => !empty(trim($datos['tipo_muestra'])) ? trim($datos['tipo_muestra']) : null,
            'matriz_tipo'            => !empty(trim($datos['matriz_tipo'])) ? trim($datos['matriz_tipo']) : null,
            'tipo_muestreo'          => !empty(trim($datos['tipo_muestreo'])) ? trim($datos['tipo_muestreo']) : null,
            'ensayo_servicio'        => trim($datos['ensayo_servicio']),
            'nombre_comercial'       => !empty(trim($datos['nombre_comercial'])) ? trim($datos['nombre_comercial']) : null,
            'condiciones_muestra'    => !empty(trim($datos['condiciones_muestra'])) ? trim($datos['condiciones_muestra']) : null,
            'codigo_servicio'        => !empty(trim($datos['codigo_servicio'])) ? trim($datos['codigo_servicio']) : null,
            'estatus'                => !empty(trim($datos['estatus'])) ? trim($datos['estatus']) : 'No acreditado',
            'norma_astm'             => !empty(trim($datos['norma_astm'])) ? trim($datos['norma_astm']) : null,
            'procedimiento_muestreo' => !empty(trim($datos['procedimiento_muestreo'])) ? trim($datos['procedimiento_muestreo']) : null,
            'codigo_hoja_campo'      => !empty(trim($datos['codigo_hoja_campo'])) ? trim($datos['codigo_hoja_campo']) : null,
            'unidad_medida'          => !empty(trim($datos['unidad_medida'])) ? trim($datos['unidad_medida']) : 'Unidad',
            'precio'                 => floatval($datos['precio']),
            'observaciones'          => !empty(trim($datos['observaciones'])) ? trim($datos['observaciones']) : null
        ]);
    }
    
    // ✏️ ACTUALIZAR PRODUCTO
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE productos SET 
                    no_item = :no_item, 
                    formato_id = :formato_id, 
                    tipo_muestra = :tipo_muestra, 
                    matriz_tipo = :matriz_tipo, 
                    tipo_muestreo = :tipo_muestreo, 
                    ensayo_servicio = :ensayo_servicio, 
                    nombre_comercial = :nombre_comercial, 
                    condiciones_muestra = :condiciones_muestra, 
                    codigo_servicio = :codigo_servicio, 
                    estatus = :estatus, 
                    norma_astm = :norma_astm, 
                    procedimiento_muestreo = :procedimiento_muestreo, 
                    codigo_hoja_campo = :codigo_hoja_campo, 
                    unidad_medida = :unidad_medida, 
                    precio = :precio, 
                    observaciones = :observaciones,
                    activo = :activo
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'no_item'                => !empty(trim($datos['no_item'])) ? trim($datos['no_item']) : null,
            'formato_id'             => !empty($datos['formato_id']) ? intval($datos['formato_id']) : null,
            'tipo_muestra'           => !empty(trim($datos['tipo_muestra'])) ? trim($datos['tipo_muestra']) : null,
            'matriz_tipo'            => !empty(trim($datos['matriz_tipo'])) ? trim($datos['matriz_tipo']) : null,
            'tipo_muestreo'          => !empty(trim($datos['tipo_muestreo'])) ? trim($datos['tipo_muestreo']) : null,
            'ensayo_servicio'        => trim($datos['ensayo_servicio']),
            'nombre_comercial'       => !empty(trim($datos['nombre_comercial'])) ? trim($datos['nombre_comercial']) : null,
            'condiciones_muestra'    => !empty(trim($datos['condiciones_muestra'])) ? trim($datos['condiciones_muestra']) : null,
            'codigo_servicio'        => !empty(trim($datos['codigo_servicio'])) ? trim($datos['codigo_servicio']) : null,
            'estatus'                => !empty(trim($datos['estatus'])) ? trim($datos['estatus']) : 'No acreditado',
            'norma_astm'             => !empty(trim($datos['norma_astm'])) ? trim($datos['norma_astm']) : null,
            'procedimiento_muestreo' => !empty(trim($datos['procedimiento_muestreo'])) ? trim($datos['procedimiento_muestreo']) : null,
            'codigo_hoja_campo'      => !empty(trim($datos['codigo_hoja_campo'])) ? trim($datos['codigo_hoja_campo']) : null,
            'unidad_medida'          => !empty(trim($datos['unidad_medida'])) ? trim($datos['unidad_medida']) : 'Unidad',
            'precio'                 => floatval($datos['precio']),
            'observaciones'          => !empty(trim($datos['observaciones'])) ? trim($datos['observaciones']) : null,
            'activo'                 => isset($datos['activo']) ? intval($datos['activo']) : 1,
            'id'                     => $id
        ]);
    }
    
    // 🗑️ DESACTIVAR PRODUCTO (SOFT DELETE)
    public function desactivar(int $id): bool {
        $sql = "UPDATE productos SET activo = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}

