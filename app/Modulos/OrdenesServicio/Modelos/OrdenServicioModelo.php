<?php

namespace Cycsa\Modulos\OrdenesServicio\Modelos;

use Cycsa\Nucleo\Conexion;
use PDO;
use Exception;

class OrdenServicioModelo {

    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtenerInstancia();
    }

    /**
     * Genera un código correlativo único para la Orden de Servicio
     * Formato: YYYYXXX-ORDEN DE SERVICIO (Ej: 2026001-ORDEN DE SERVICIO)
     */
    public function generarCodigoOS(): string {
        $anio = date('Y');
        $sql = "SELECT COUNT(*) FROM ordenes_servicio WHERE codigo_os LIKE :anio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => $anio . '%']);
        $count = $stmt->fetchColumn() + 1;
        
        $correlativo = str_pad($count, 3, '0', STR_PAD_LEFT);
        return "{$anio}{$correlativo}-ORDEN DE SERVICIO";
    }

    /**
     * Obtiene todas las órdenes de servicio con datos de cotización y cliente
     */
    public function obtenerTodas(string $busqueda = ''): array {
        $sql = "SELECT os.*, 
                       cot.codigo AS cotizacion_codigo, 
                       cli.nombre_razon_social AS cliente_nombre,
                       COALESCE(cli.identificacion, cli.numero_ruc, cli.numero_cedula, '') AS cliente_rfc,
                       pm.fecha_ida, pm.fecha_llegada, pm.estado_muestreo,
                       t.nombre AS tecnico_nombre,
                       v.vehiculo_info
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN programacion_muestreo pm ON pm.id_orden_servicio = os.id
                LEFT JOIN tecnicos t ON pm.id_tecnico = t.id
                LEFT JOIN (SELECT id, CONCAT(marca, ' ', modelo, ' (', placa, ')') AS vehiculo_info FROM vehiculos) v ON pm.id_vehiculo = v.id";
        
        if (!empty($busqueda)) {
            $sql .= " WHERE os.codigo_os LIKE :q OR cot.codigo LIKE :q OR cli.nombre_razon_social LIKE :q OR os.nombre_proyecto LIKE :q";
        }

        $sql .= " ORDER BY os.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($busqueda)) {
            $term = '%' . $busqueda . '%';
            $stmt->execute(['q' => $term]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una Orden de Servicio por su ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT os.*, 
                       cot.codigo AS cotizacion_codigo, 
                       cot.version AS cotizacion_version,
                       cli.nombre_razon_social AS cliente_nombre,
                       COALESCE(cli.identificacion, cli.numero_ruc, cli.numero_cedula, '') AS cliente_rfc,
                       cli.direccion AS cliente_direccion,
                       cli.telefono AS cliente_telefono,
                       cli.email AS cliente_email
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                WHERE os.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $os = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$os) {
            return null;
        }

        // Obtener programación de muestreo si existe
        $sqlPm = "SELECT pm.*, t.nombre AS tecnico_nombre,
                         v.marca, v.modelo, v.placa
                  FROM programacion_muestreo pm
                  LEFT JOIN tecnicos t ON pm.id_tecnico = t.id
                  LEFT JOIN vehiculos v ON pm.id_vehiculo = v.id
                  WHERE pm.id_orden_servicio = :id_os";
        $stmtPm = $this->db->prepare($sqlPm);
        $stmtPm->execute(['id_os' => $id]);
        $os['programacion_muestreo'] = $stmtPm->fetch(PDO::FETCH_ASSOC) ?: null;

        // Obtener detalles de ensayos heredados de la cotización
        $sqlDet = "SELECT cd.*, 
                          COALESCE(cd.descripcion_ensayo, p.ensayo_servicio, p.nombre_comercial, '') AS nombre_ensayo, 
                          COALESCE(cd.codigo_servicio, p.codigo_servicio, '') AS codigo_servicio, 
                          COALESCE(cd.norma_astm, p.norma_astm, '') AS norma_astm
                   FROM cotizacion_detalles cd
                   LEFT JOIN productos p ON cd.id_producto = p.id
                   WHERE cd.id_cotizacion = :id_cotizacion";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute(['id_cotizacion' => $os['id_cotizacion']]);
        $os['ensayos'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $os;
    }

    /**
     * Guardar una nueva Orden de Servicio heredando datos de la cotización
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO ordenes_servicio (
                    codigo_os, id_cotizacion, id_cliente, elaborado_por, fecha_emision,
                    atencion_a, nombre_proyecto, forma_pago, notas_condiciones,
                    contactos_json, requiere_muestreo, estado
                ) VALUES (
                    :codigo_os, :id_cotizacion, :id_cliente, :elaborado_por, :fecha_emision,
                    :atencion_a, :nombre_proyecto, :forma_pago, :notas_condiciones,
                    :contactos_json, :requiere_muestreo, :estado
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'codigo_os' => $datos['codigo_os'],
            'id_cotizacion' => $datos['id_cotizacion'],
            'id_cliente' => $datos['id_cliente'],
            'elaborado_por' => $datos['elaborado_por'] ?? ($_SESSION['usuario_nombre'] ?? 'Sistema'),
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'atencion_a' => $datos['atencion_a'] ?? '',
            'nombre_proyecto' => $datos['nombre_proyecto'] ?? '',
            'forma_pago' => $datos['forma_pago'] ?? 'Pago contra entrega',
            'notas_condiciones' => $datos['notas_condiciones'] ?? '',
            'contactos_json' => is_array($datos['contactos_json'] ?? null) ? json_encode($datos['contactos_json']) : ($datos['contactos_json'] ?? '[]'),
            'requiere_muestreo' => !empty($datos['requiere_muestreo']) ? 1 : 0,
            'estado' => $datos['estado'] ?? 'Borrador'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Registrar o actualizar la programación de muestreo
     */
    public function guardarProgramacionMuestreo(int $idOS, array $datos): bool {
        $sqlCheck = "SELECT id FROM programacion_muestreo WHERE id_orden_servicio = :id_os";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id_os' => $idOS]);
        $existente = $stmtCheck->fetchColumn();

        if ($existente) {
            $sql = "UPDATE programacion_muestreo SET
                        fecha_ida = :fecha_ida,
                        fecha_llegada = :fecha_llegada,
                        id_tecnico = :id_tecnico,
                        id_vehiculo = :id_vehiculo,
                        observaciones_campo = :observaciones_campo,
                        estado_muestreo = :estado_muestreo
                    WHERE id_orden_servicio = :id_os";
        } else {
            $sql = "INSERT INTO programacion_muestreo (
                        id_orden_servicio, fecha_ida, fecha_llegada, id_tecnico, id_vehiculo, observaciones_campo, estado_muestreo
                    ) VALUES (
                        :id_os, :fecha_ida, :fecha_llegada, :id_tecnico, :id_vehiculo, :observaciones_campo, :estado_muestreo
                    )";
        }

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            'id_os' => $idOS,
            'fecha_ida' => $datos['fecha_ida'],
            'fecha_llegada' => $datos['fecha_llegada'],
            'id_tecnico' => $datos['id_tecnico'],
            'id_vehiculo' => $datos['id_vehiculo'],
            'observaciones_campo' => $datos['observaciones_campo'] ?? null,
            'estado_muestreo' => $datos['estado_muestreo'] ?? 'Programado'
        ]);

        // Actualizar estado de la Orden de Servicio
        $stmtState = $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = 1, estado = 'Pendiente de Muestreo' WHERE id = :id");
        $stmtState->execute(['id' => $idOS]);

        return $res;
    }

    /**
     * Marcar la orden como ingreso directo (sin muestreo en campo)
     */
    public function establecerIngresoDirecto(int $idOS): bool {
        $stmt = $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = 0, estado = 'Estado 1: Recepcion' WHERE id = :id");
        return $stmt->execute(['id' => $idOS]);
    }

    /**
     * Marcar el muestreo como finalizado (retorno de campo al laboratorio)
     */
    public function finalizarMuestreo(int $idOS): bool {
        // 1. Actualizar estado del muestreo
        $sqlPm = "UPDATE programacion_muestreo SET 
                    estado_muestreo = 'Finalizado', 
                    fecha_finalizacion = NOW() 
                  WHERE id_orden_servicio = :id_os";
        $stmtPm = $this->db->prepare($sqlPm);
        $stmtPm->execute(['id_os' => $idOS]);

        // 2. Cambiar el estado de la Orden de Servicio a 'Estado 1: Recepcion' (para Hoja de Servicio)
        $sqlOs = "UPDATE ordenes_servicio SET requiere_muestreo = 1, estado = 'Estado 1: Recepcion' WHERE id = :id_os";
        $stmtOs = $this->db->prepare($sqlOs);
        return $stmtOs->execute(['id_os' => $idOS]);
    }

    /**
     * Obtener lista de técnicos activos
     */
    public function obtenerTecnicos(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM tecnicos WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener lista de vehículos activos
     */
    public function obtenerVehiculos(): array {
        $stmt = $this->db->query("SELECT id, marca, modelo, placa FROM vehiculos WHERE activo = 1 ORDER BY marca ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
