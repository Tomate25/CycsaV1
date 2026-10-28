<?php

namespace Cycsa\Modulos\Proyectos\Modelos;

use Cycsa\Nucleo\ModeloBase;
use Cycsa\Nucleo\Conexion;
use PDO;
use InvalidArgumentException;
use Throwable;

class ProyectosModelo extends ModeloBase {

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // =========================================================================
    // 1. CRUD DE PROYECTOS / ÓRDENES DE OBRA
    // =========================================================================

    /**
     * Crea un nuevo proyecto u orden de obra.
     *
     * @param array $datos
     * @return int ID del proyecto insertado
     * @throws InvalidArgumentException
     */
    public function crear(array $datos): int {
        $nombre = trim($datos['nombre'] ?? '');
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre del proyecto es obligatorio.");
        }

        $codigo = trim($datos['codigo'] ?? '');
        if (empty($codigo)) {
            $codigo = $this->generarSiguienteCodigo();
        }

        // Verificar unicidad del código
        $stmtCheck = $this->db->prepare("SELECT id FROM proyectos WHERE codigo = :codigo LIMIT 1");
        $stmtCheck->execute(['codigo' => $codigo]);
        if ($stmtCheck->fetch()) {
            throw new InvalidArgumentException("Ya existe un proyecto con el código {$codigo}.");
        }

        $clienteId = !empty($datos['cliente_id']) ? (int)$datos['cliente_id'] : null;
        $ubicacion = !empty($datos['ubicacion']) ? trim($datos['ubicacion']) : null;
        $presupuesto = isset($datos['presupuesto_estimado']) ? max(0, (float)$datos['presupuesto_estimado']) : 0.00;
        $fechaInicio = !empty($datos['fecha_inicio']) ? trim($datos['fecha_inicio']) : date('Y-m-d');
        $fechaFin = !empty($datos['fecha_fin']) ? trim($datos['fecha_fin']) : null;

        $estadosValidos = ['planificado', 'en_ejecucion', 'completado', 'suspendido'];
        $estado = !empty($datos['estado']) && in_array($datos['estado'], $estadosValidos, true) 
            ? $datos['estado'] 
            : 'planificado';

        $cuentaObraId = !empty($datos['cuenta_obra_proceso_id']) ? (int)$datos['cuenta_obra_proceso_id'] : null;
        if ($cuentaObraId === null) {
            $cuentaObraId = $this->obtenerCuentaObraDefault();
        }

        $sql = "INSERT INTO proyectos (
                    codigo, nombre, cliente_id, ubicacion, presupuesto_estimado,
                    fecha_inicio, fecha_fin, estado, cuenta_obra_proceso_id
                ) VALUES (
                    :codigo, :nombre, :cliente_id, :ubicacion, :presupuesto,
                    :fecha_inicio, :fecha_fin, :estado, :cuenta_obra
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'codigo'       => $codigo,
            'nombre'       => $nombre,
            'cliente_id'   => $clienteId,
            'ubicacion'    => $ubicacion,
            'presupuesto'  => $presupuesto,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'estado'       => $estado,
            'cuenta_obra'  => $cuentaObraId,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un proyecto existente.
     *
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return false;
        }

        $campos = [];
        $params = ['id' => $id];

        if (isset($datos['nombre'])) {
            $nombre = trim($datos['nombre']);
            if (empty($nombre)) {
                throw new InvalidArgumentException("El nombre del proyecto no puede estar vacío.");
            }
            $campos[] = "nombre = :nombre";
            $params['nombre'] = $nombre;
        }

        if (isset($datos['codigo'])) {
            $codigo = trim($datos['codigo']);
            if (!empty($codigo) && $codigo !== $actual['codigo']) {
                $check = $this->db->prepare("SELECT id FROM proyectos WHERE codigo = :cod AND id != :id LIMIT 1");
                $check->execute(['cod' => $codigo, 'id' => $id]);
                if ($check->fetch()) {
                    throw new InvalidArgumentException("El código {$codigo} ya se encuentra en uso.");
                }
                $campos[] = "codigo = :codigo";
                $params['codigo'] = $codigo;
            }
        }

        if (array_key_exists('cliente_id', $datos)) {
            $campos[] = "cliente_id = :cliente_id";
            $params['cliente_id'] = !empty($datos['cliente_id']) ? (int)$datos['cliente_id'] : null;
        }

        if (array_key_exists('ubicacion', $datos)) {
            $campos[] = "ubicacion = :ubicacion";
            $params['ubicacion'] = !empty($datos['ubicacion']) ? trim($datos['ubicacion']) : null;
        }

        if (isset($datos['presupuesto_estimado'])) {
            $campos[] = "presupuesto_estimado = :presupuesto";
            $params['presupuesto'] = max(0, (float)$datos['presupuesto_estimado']);
        }

        if (isset($datos['fecha_inicio'])) {
            $campos[] = "fecha_inicio = :fecha_inicio";
            $params['fecha_inicio'] = trim($datos['fecha_inicio']);
        }

        if (array_key_exists('fecha_fin', $datos)) {
            $campos[] = "fecha_fin = :fecha_fin";
            $params['fecha_fin'] = !empty($datos['fecha_fin']) ? trim($datos['fecha_fin']) : null;
        }

        if (isset($datos['estado'])) {
            $estadosValidos = ['planificado', 'en_ejecucion', 'completado', 'suspendido'];
            if (in_array($datos['estado'], $estadosValidos, true)) {
                $campos[] = "estado = :estado";
                $params['estado'] = $datos['estado'];
            }
        }

        if (array_key_exists('cuenta_obra_proceso_id', $datos)) {
            $campos[] = "cuenta_obra_proceso_id = :cuenta_obra";
            $params['cuenta_obra'] = !empty($datos['cuenta_obra_proceso_id']) ? (int)$datos['cuenta_obra_proceso_id'] : null;
        }

        if (empty($campos)) {
            return true;
        }

        $sql = "UPDATE proyectos SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Obtiene un proyecto por ID con datos de cliente, cuenta y costos consolidados.
     *
     * @param int $id
     * @param bool $incluirCostos Si debe incluir los costos consolidados y avance
     * @return array|null
     */
    public function obtenerPorId(int $id, bool $incluirCostos = true): ?array {
        $sql = "SELECT p.*,
                       c.nombre_razon_social AS cliente_nombre,
                       c.identificacion AS cliente_identificacion,
                       c.telefono AS cliente_telefono,
                       cc.nombre AS cuenta_nombre,
                       cc.codigo AS cuenta_codigo
                FROM proyectos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN cuentas_contables cc ON p.cuenta_obra_proceso_id = cc.id
                WHERE p.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$proyecto) {
            return null;
        }

        $proyecto['presupuesto_estimado'] = (float)$proyecto['presupuesto_estimado'];

        if ($incluirCostos) {
            $costos = $this->obtenerCostosConsolidados($id);
            $proyecto['costos'] = $costos;
            $proyecto['avance'] = $this->calcularAvance($proyecto, $costos);
        }

        return $proyecto;
    }

    /**
     * Lista los proyectos con filtros opcionales.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array {
        $sql = "SELECT p.*,
                       c.nombre_razon_social AS cliente_nombre,
                       cc.nombre AS cuenta_nombre,
                       cc.codigo AS cuenta_codigo
                FROM proyectos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN cuentas_contables cc ON p.cuenta_obra_proceso_id = cc.id";

        $where = [];
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $term = '%' . trim($filtros['busqueda']) . '%';
            $where[] = "(p.codigo LIKE :q_cod OR p.nombre LIKE :q_nom OR p.ubicacion LIKE :q_ubi OR c.nombre_razon_social LIKE :q_cli)";
            $params['q_cod'] = $term;
            $params['q_nom'] = $term;
            $params['q_ubi'] = $term;
            $params['q_cli'] = $term;
        }

        if (!empty($filtros['estado'])) {
            $where[] = "p.estado = :estado";
            $params['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['cliente_id'])) {
            $where[] = "p.cliente_id = :cliente_id";
            $params['cliente_id'] = (int)$filtros['cliente_id'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer cada proyecto con resumen rápido de costos
        foreach ($proyectos as &$p) {
            $p['presupuesto_estimado'] = (float)$p['presupuesto_estimado'];
            $costos = $this->obtenerCostosConsolidados((int)$p['id'], false);
            $p['costo_total'] = $costos['costo_total'];
            $p['desviacion'] = $costos['desviacion'];
            $p['porcentaje_consumido'] = $costos['porcentaje_consumido'];
            $p['estado_presupuesto'] = $costos['estado_presupuesto'];
        }
        unset($p);

        return $proyectos;
    }

    /**
     * Cambia el estado de un proyecto.
     *
     * @param int $id
     * @param string $nuevoEstado
     * @return bool
     */
    public function cambiarEstado(int $id, string $nuevoEstado): bool {
        $estadosValidos = ['planificado', 'en_ejecucion', 'completado', 'suspendido'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            throw new InvalidArgumentException("Estado '{$nuevoEstado}' no válido.");
        }

        $stmt = $this->db->prepare("UPDATE proyectos SET estado = :estado WHERE id = :id");
        return $stmt->execute(['estado' => $nuevoEstado, 'id' => $id]);
    }

    /**
     * Elimina un proyecto si no tiene registros bloqueantes o borra en cascada segura.
     *
     * @param int $id
     * @return bool
     */
    public function eliminar(int $id): bool {
        // Desvincular proyecto_id en tablas relacionadas antes de eliminar
        $tablas = [
            'ordenes_compra',
            'ordenes_compra_detalle',
            'cuentas_por_pagar_detalle',
            'inventario_movimientos',
            'rrhh_nomina_detalles'
        ];

        foreach ($tablas as $tabla) {
            try {
                $existe = $this->db->query("SHOW TABLES LIKE '{$tabla}'")->fetch();
                if ($existe) {
                    $stmtDesv = $this->db->prepare("UPDATE `{$tabla}` SET proyecto_id = NULL WHERE proyecto_id = :id");
                    $stmtDesv->execute(['id' => $id]);
                }
            } catch (Throwable $t) {
                // Continuar limpiando
            }
        }

        $stmt = $this->db->prepare("DELETE FROM proyectos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // =========================================================================
    // 2. IMPUTACIÓN Y AGREGACIÓN TRANSVERSAL DE COSTOS (CUATRO CUADRANTES)
    // =========================================================================

    /**
     * Obtiene la agregación transversal de costos consolidados por proyecto:
     * - Cuadrante 1: Compras / Órdenes de Compra (OC).
     * - Cuadrante 2: Gastos Directos (Cuentas por Pagar CXP).
     * - Cuadrante 3: Insumos y Movimientos de Inventario (Kárdex).
     * - Cuadrante 4: Mano de Obra y Nómina (Total Devengado).
     *
     * Retorna además el Presupuesto Estimado, Costo Total Acumulado y Desviación.
     *
     * @param int $proyectoId
     * @param bool $incluirDetalles Si debe retornar el desglose fila por fila
     * @return array
     */
    public function obtenerCostosConsolidados(int $proyectoId, bool $incluirDetalles = true): array {
        // Presupuesto del proyecto
        $stmtPres = $this->db->prepare("SELECT presupuesto_estimado FROM proyectos WHERE id = :pid LIMIT 1");
        $stmtPres->execute(['pid' => $proyectoId]);
        $presupuestoEstimado = (float)($stmtPres->fetchColumn() ?: 0.00);

        // ---------------------------------------------------------------------
        // 1. Costo Compras / OC: SUM en ordenes_compra_detalle por proyecto_id
        // ---------------------------------------------------------------------
        $costoCompras = 0.00;
        $detallesCompras = [];
        try {
            $existeOcDet = $this->db->query("SHOW TABLES LIKE 'ordenes_compra_detalle'")->fetch();
            if ($existeOcDet) {
                $sqlCompras = "
                    SELECT COALESCE(SUM(CASE WHEN ocd.total > 0 THEN ocd.total ELSE ocd.subtotal END), 0) AS total_compras
                    FROM ordenes_compra_detalle ocd
                    LEFT JOIN ordenes_compra oc ON ocd.orden_compra_id = oc.id
                    WHERE (ocd.proyecto_id = :pid OR (ocd.proyecto_id IS NULL AND oc.proyecto_id = :pid_oc))
                      AND (oc.estado IS NULL OR oc.estado != 'anulada')
                ";
                $stmtCompras = $this->db->prepare($sqlCompras);
                $stmtCompras->execute(['pid' => $proyectoId, 'pid_oc' => $proyectoId]);
                $costoCompras = (float)$stmtCompras->fetchColumn();

                if ($incluirDetalles) {
                    $sqlDetCompras = "
                        SELECT ocd.id,
                               ocd.orden_compra_id,
                               ocd.insumo_id,
                               ocd.descripcion,
                               ocd.cantidad,
                               ocd.costo_unitario,
                               ocd.subtotal,
                               ocd.total,
                               ocd.created_at,
                               oc.id AS numero_orden,
                               oc.fecha AS orden_fecha,
                               COALESCE(p.razon_social, oc.proveedor) AS proveedor_nombre
                        FROM ordenes_compra_detalle ocd
                        LEFT JOIN ordenes_compra oc ON ocd.orden_compra_id = oc.id
                        LEFT JOIN proveedores p ON oc.proveedor_id = p.id
                        WHERE (ocd.proyecto_id = :pid OR (ocd.proyecto_id IS NULL AND oc.proyecto_id = :pid_oc))
                          AND (oc.estado IS NULL OR oc.estado != 'anulada')
                        ORDER BY ocd.id DESC
                    ";
                    $stmtDetCompras = $this->db->prepare($sqlDetCompras);
                    $stmtDetCompras->execute(['pid' => $proyectoId, 'pid_oc' => $proyectoId]);
                    $detallesCompras = $stmtDetCompras->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Throwable $e) {
            error_log("Error al calcular costos de compras proyecto {$proyectoId}: " . $e->getMessage());
        }

        // ---------------------------------------------------------------------
        // 2. Costo Gastos Directos: SUM en cuentas_por_pagar_detalle por proyecto_id
        // ---------------------------------------------------------------------
        $costoGastos = 0.00;
        $detallesGastos = [];
        try {
            $existeCxpDet = $this->db->query("SHOW TABLES LIKE 'cuentas_por_pagar_detalle'")->fetch();
            if ($existeCxpDet) {
                $sqlGastos = "
                    SELECT COALESCE(SUM(CASE WHEN cxpd.total > 0 THEN cxpd.total ELSE cxpd.monto END), 0) AS total_gastos
                    FROM cuentas_por_pagar_detalle cxpd
                    LEFT JOIN cuentas_por_pagar cxp ON cxpd.cxp_id = cxp.id
                    WHERE cxpd.proyecto_id = :pid
                      AND (cxp.estado IS NULL OR cxp.estado != 'anulada')
                ";
                $stmtGastos = $this->db->prepare($sqlGastos);
                $stmtGastos->execute(['pid' => $proyectoId]);
                $costoGastos = (float)$stmtGastos->fetchColumn();

                if ($incluirDetalles) {
                    $sqlDetGastos = "
                        SELECT cxpd.id,
                               cxpd.cxp_id,
                               cxpd.cuenta_contable_id,
                               cxpd.concepto,
                               cxpd.monto,
                               cxpd.iva,
                               cxpd.retencion_ir,
                               cxpd.retencion_alcaldia,
                               cxpd.total,
                               cxpd.created_at,
                               cxp.factura_numero,
                               cxp.fecha_emision,
                               COALESCE(p.razon_social, cxp.proveedor_nombre) AS proveedor_nombre,
                               cc.nombre AS cuenta_nombre,
                               cc.codigo AS cuenta_codigo
                        FROM cuentas_por_pagar_detalle cxpd
                        LEFT JOIN cuentas_por_pagar cxp ON cxpd.cxp_id = cxp.id
                        LEFT JOIN proveedores p ON cxp.proveedor_id = p.id
                        LEFT JOIN cuentas_contables cc ON cxpd.cuenta_contable_id = cc.id
                        WHERE cxpd.proyecto_id = :pid
                          AND (cxp.estado IS NULL OR cxp.estado != 'anulada')
                        ORDER BY cxpd.id DESC
                    ";
                    $stmtDetGastos = $this->db->prepare($sqlDetGastos);
                    $stmtDetGastos->execute(['pid' => $proyectoId]);
                    $detallesGastos = $stmtDetGastos->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Throwable $e) {
            error_log("Error al calcular gastos directos proyecto {$proyectoId}: " . $e->getMessage());
        }

        // ---------------------------------------------------------------------
        // 3. Costo Insumos / Kárdex: SUM(cantidad * costo_unitario) en inventario_movimientos
        // ---------------------------------------------------------------------
        $costoInsumos = 0.00;
        $detallesInsumos = [];
        try {
            $existeInvMov = $this->db->query("SHOW TABLES LIKE 'inventario_movimientos'")->fetch();
            if ($existeInvMov) {
                $sqlInsumos = "
                    SELECT COALESCE(SUM(cantidad * costo_unitario), 0) AS total_insumos
                    FROM inventario_movimientos
                    WHERE proyecto_id = :pid
                ";
                $stmtInsumos = $this->db->prepare($sqlInsumos);
                $stmtInsumos->execute(['pid' => $proyectoId]);
                $costoInsumos = (float)$stmtInsumos->fetchColumn();

                if ($incluirDetalles) {
                    $sqlDetInsumos = "
                        SELECT im.id,
                               im.insumo_id,
                               im.lote_id,
                               im.tipo_movimiento,
                               im.cantidad,
                               im.costo_unitario,
                               (im.cantidad * im.costo_unitario) AS subtotal,
                               im.observaciones,
                               im.created_at,
                               ii.nombre AS insumo_nombre,
                               ii.codigo AS insumo_codigo,
                               ii.unidad_medida
                        FROM inventario_movimientos im
                        LEFT JOIN inventario_insumos ii ON im.insumo_id = ii.id
                        WHERE im.proyecto_id = :pid
                        ORDER BY im.id DESC
                    ";
                    $stmtDetInsumos = $this->db->prepare($sqlDetInsumos);
                    $stmtDetInsumos->execute(['pid' => $proyectoId]);
                    $detallesInsumos = $stmtDetInsumos->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Throwable $e) {
            error_log("Error al calcular costos de insumos proyecto {$proyectoId}: " . $e->getMessage());
        }

        // ---------------------------------------------------------------------
        // 4. Costo Mano de Obra: SUM(total_devengado) en rrhh_nomina_detalles
        // ---------------------------------------------------------------------
        $costoManoObra = 0.00;
        $detallesManoObra = [];
        try {
            $existeNomDet = $this->db->query("SHOW TABLES LIKE 'rrhh_nomina_detalles'")->fetch();
            if ($existeNomDet) {
                $sqlNomina = "
                    SELECT COALESCE(SUM(rnd.total_devengado), 0) AS total_mano_obra
                    FROM rrhh_nomina_detalles rnd
                    LEFT JOIN rrhh_nominas rn ON rnd.nomina_id = rn.id
                    WHERE rnd.proyecto_id = :pid
                      AND (rn.estado IS NULL OR rn.estado != 'anulada')
                ";
                $stmtNomina = $this->db->prepare($sqlNomina);
                $stmtNomina->execute(['pid' => $proyectoId]);
                $costoManoObra = (float)$stmtNomina->fetchColumn();

                if ($incluirDetalles) {
                    $sqlDetNomina = "
                        SELECT rnd.id,
                               rnd.nomina_id,
                               rnd.empleado_id,
                               rnd.salario_base,
                               rnd.horas_extras_monto,
                               rnd.total_devengado,
                               rnd.neto_a_pagar,
                               rnd.created_at,
                               rn.codigo AS nomina_codigo,
                               rn.periodo_inicio,
                               rn.periodo_fin,
                               e.nombre AS empleado_nombre,
                               e.cedula AS empleado_cedula,
                               e.puesto AS empleado_puesto
                        FROM rrhh_nomina_detalles rnd
                        LEFT JOIN rrhh_nominas rn ON rnd.nomina_id = rn.id
                        LEFT JOIN rrhh_empleados e ON rnd.empleado_id = e.id
                        WHERE rnd.proyecto_id = :pid
                          AND (rn.estado IS NULL OR rn.estado != 'anulada')
                        ORDER BY rnd.id DESC
                    ";
                    $stmtDetNomina = $this->db->prepare($sqlDetNomina);
                    $stmtDetNomina->execute(['pid' => $proyectoId]);
                    $detallesManoObra = $stmtDetNomina->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Throwable $e) {
            error_log("Error al calcular mano de obra proyecto {$proyectoId}: " . $e->getMessage());
        }

        // ---------------------------------------------------------------------
        // 5. Consolidación Total y Desviación
        // ---------------------------------------------------------------------
        $costoTotal = round($costoCompras + $costoGastos + $costoInsumos + $costoManoObra, 2);
        $desviacion = round($presupuestoEstimado - $costoTotal, 2); // > 0: superávit, < 0: déficit
        $porcentajeConsumido = ($presupuestoEstimado > 0) 
            ? round(($costoTotal / $presupuestoEstimado) * 100, 2) 
            : 0.00;

        $estadoPresupuesto = ($costoTotal > $presupuestoEstimado) ? 'deficit' : 'superavit';

        return [
            'proyecto_id'           => $proyectoId,
            'costo_compras'         => $costoCompras,
            'compras_oc'            => $costoCompras,
            'costo_gastos'          => $costoGastos,
            'gastos_directos'       => $costoGastos,
            'costo_insumos'         => $costoInsumos,
            'insumos_kardex'        => $costoInsumos,
            'costo_mano_obra'       => $costoManoObra,
            'mano_obra'             => $costoManoObra,
            'costo_total'           => $costoTotal,
            'costo_total_acumulado' => $costoTotal,
            'total_acumulado'       => $costoTotal,
            'presupuesto_estimado'  => $presupuestoEstimado,
            'presupuesto'           => $presupuestoEstimado,
            'desviacion'            => $desviacion,
            'diferencia'            => $desviacion,
            'porcentaje_consumido'  => $porcentajeConsumido,
            'estado_presupuesto'    => $estadoPresupuesto,
            'superavit_deficit'     => ($desviacion >= 0) ? 'superavit' : 'deficit',
            'desglose'              => [
                'compras'   => $detallesCompras,
                'gastos'    => $detallesGastos,
                'insumos'   => $detallesInsumos,
                'mano_obra' => $detallesManoObra,
            ],
            'conteo'                => [
                'compras'   => count($detallesCompras),
                'gastos'    => count($detallesGastos),
                'insumos'   => count($detallesInsumos),
                'mano_obra' => count($detallesManoObra),
            ]
        ];
    }

    /**
     * Calcula métricas de avance y estado para un proyecto.
     *
     * @param array $proyecto
     * @param array $costos
     * @return array
     */
    public function calcularAvance(array $proyecto, array $costos): array {
        $presupuesto = (float)($proyecto['presupuesto_estimado'] ?? 0);
        $costoTotal = (float)($costos['costo_total'] ?? 0);
        $desviacion = (float)($costos['desviacion'] ?? 0);

        // Avance temporal basado en fechas
        $avanceTemporal = 0.00;
        $diasTotales = 0;
        $diasTranscurridos = 0;

        if (!empty($proyecto['fecha_inicio']) && !empty($proyecto['fecha_fin'])) {
            $tsInicio = strtotime($proyecto['fecha_inicio']);
            $tsFin = strtotime($proyecto['fecha_fin']);
            $tsHoy = time();

            if ($tsFin > $tsInicio) {
                $diasTotales = max(1, (int)round(($tsFin - $tsInicio) / 86400));
                $diasTranscurridos = max(0, min($diasTotales, (int)round(($tsHoy - $tsInicio) / 86400)));
                $avanceTemporal = round(($diasTranscurridos / $diasTotales) * 100, 2);
            }
        }

        return [
            'porcentaje_presupuesto' => $costos['porcentaje_consumido'] ?? 0.00,
            'desviacion'             => $desviacion,
            'tipo_desviacion'        => ($desviacion >= 0) ? 'superavit' : 'deficit',
            'avance_temporal'        => $avanceTemporal,
            'dias_totales'           => $diasTotales,
            'dias_transcurridos'     => $diasTranscurridos,
            'estado'                 => $proyecto['estado'] ?? 'planificado',
        ];
    }

    // =========================================================================
    // 3. MÉTODOS DE IMPUTACIÓN DIRECTA (HELPERS)
    // =========================================================================

    /**
     * Imputa una línea de orden de compra a un proyecto.
     */
    public function imputarCompra(int $ordenCompraDetalleId, int $proyectoId): bool {
        $stmt = $this->db->prepare("UPDATE ordenes_compra_detalle SET proyecto_id = :pid WHERE id = :id");
        return $stmt->execute(['pid' => $proyectoId, 'id' => $ordenCompraDetalleId]);
    }

    /**
     * Imputa una línea de detalle de gasto (CXP) a un proyecto.
     */
    public function imputarGasto(int $cxpDetalleId, int $proyectoId): bool {
        $stmt = $this->db->prepare("UPDATE cuentas_por_pagar_detalle SET proyecto_id = :pid WHERE id = :id");
        return $stmt->execute(['pid' => $proyectoId, 'id' => $cxpDetalleId]);
    }

    /**
     * Imputa un movimiento de inventario / kárdex a un proyecto.
     */
    public function imputarInsumo(int $inventarioMovimientoId, int $proyectoId): bool {
        $stmt = $this->db->prepare("UPDATE inventario_movimientos SET proyecto_id = :pid WHERE id = :id");
        return $stmt->execute(['pid' => $proyectoId, 'id' => $inventarioMovimientoId]);
    }

    /**
     * Imputa un detalle de nómina (mano de obra) a un proyecto.
     */
    public function imputarManoObra(int $nominaDetalleId, int $proyectoId): bool {
        $stmt = $this->db->prepare("UPDATE rrhh_nomina_detalles SET proyecto_id = :pid WHERE id = :id");
        return $stmt->execute(['pid' => $proyectoId, 'id' => $nominaDetalleId]);
    }

    // =========================================================================
    // 4. UTILIDADES Y CATALOGOS
    // =========================================================================

    /**
     * Genera el siguiente código sugerido para un proyecto (ej: PRY-2026-001).
     */
    public function generarSiguienteCodigo(): string {
        $prefijo = 'PRY-' . date('Y') . '-';
        $stmt = $this->db->prepare("SELECT codigo FROM proyectos WHERE codigo LIKE :prefijo ORDER BY id DESC LIMIT 1");
        $stmt->execute(['prefijo' => $prefijo . '%']);
        $ultimo = $stmt->fetchColumn();

        if ($ultimo) {
            $numero = (int)str_replace($prefijo, '', $ultimo);
            return $prefijo . str_pad((string)($numero + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefijo . '001';
    }

    /**
     * Obtiene el ID de la cuenta contable predeterminada para obras en proceso desde config_cuentas.
     */
    public function obtenerCuentaObraDefault(): ?int {
        try {
            $stmt = $this->db->query("SELECT id_cuenta FROM config_cuentas WHERE clave = 'OBRAS_EN_PROCESO' LIMIT 1");
            $idCuenta = $stmt->fetchColumn();
            if ($idCuenta) {
                return (int)$idCuenta;
            }

            // Alternativa: buscar en cuentas_contables
            $stmtAlt = $this->db->query("SELECT id FROM cuentas_contables WHERE codigo = '10104' LIMIT 1");
            $idAlt = $stmtAlt->fetchColumn();
            return $idAlt ? (int)$idAlt : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Obtiene la lista de clientes para selects.
     */
    public function listarClientesCatalogo(): array {
        try {
            $stmt = $this->db->query("SELECT id, nombre_razon_social, identificacion FROM clientes ORDER BY nombre_razon_social ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
            return [];
        }
    }

    /**
     * Obtiene la lista de cuentas contables de obras y costos.
     */
    public function listarCuentasObrasCatalogo(): array {
        try {
            $stmt = $this->db->query("
                SELECT id, codigo, nombre
                FROM cuentas_contables
                WHERE codigo LIKE '10104%' OR codigo LIKE '50101%' OR nombre LIKE '%obra%' OR nombre LIKE '%construc%'
                ORDER BY codigo ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
            return [];
        }
    }

    /**
     * Obtiene métricas globales de proyectos para dashboards.
     */
    public function obtenerEstadisticasGenerales(): array {
        $totales = [
            'total_proyectos'   => 0,
            'en_ejecucion'      => 0,
            'planificados'      => 0,
            'completados'       => 0,
            'total_presupuesto' => 0.00,
            'total_ejecutado'   => 0.00,
        ];

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN estado = 'en_ejecucion' THEN 1 ELSE 0 END) AS en_ejecucion,
                       SUM(CASE WHEN estado = 'planificado' THEN 1 ELSE 0 END) AS planificados,
                       SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) AS completados,
                       COALESCE(SUM(presupuesto_estimado), 0) AS total_presupuesto
                FROM proyectos
            ");
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fila) {
                $totales['total_proyectos'] = (int)$fila['total'];
                $totales['en_ejecucion'] = (int)$fila['en_ejecucion'];
                $totales['planificados'] = (int)$fila['planificados'];
                $totales['completados'] = (int)$fila['completados'];
                $totales['total_presupuesto'] = (float)$fila['total_presupuesto'];
            }
        } catch (Throwable $t) {
            // Ignorar
        }

        return $totales;
    }
}
