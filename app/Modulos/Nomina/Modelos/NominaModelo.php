<?php

namespace Cycsa\Modulos\Nomina\Modelos;

use Cycsa\Nucleo\ModeloBase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use PDO;
use Exception;
use Throwable;

/**
 * Modelo de Nómina, Anticipos a Empleados, Provisiones y Cargas Patronales (TAREA-050).
 */
class NominaModelo extends ModeloBase {

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // =========================================================================
    // 1. GESTIÓN DE DEPARTAMENTOS
    // =========================================================================

    public function listarDepartamentos(bool $soloActivos = true): array {
        $sql = "SELECT d.*, cc.codigo AS cuenta_codigo, cc.nombre AS cuenta_nombre 
                FROM rrhh_departamentos d
                LEFT JOIN cuentas_contables cc ON d.cuenta_gasto_id = cc.id";
        if ($soloActivos) {
            $sql .= " WHERE d.activo = 1";
        }
        $sql .= " ORDER BY d.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDepartamentoPorId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM rrhh_departamentos WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function guardarDepartamento(array $datos): int {
        $codigo = strtoupper(trim($datos['codigo'] ?? ''));
        $nombre = trim($datos['nombre'] ?? '');
        $cuentaGastoId = !empty($datos['cuenta_gasto_id']) ? (int)$datos['cuenta_gasto_id'] : null;

        if ($codigo === '' || $nombre === '') {
            throw new Exception("El código y nombre del departamento son obligatorios.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO rrhh_departamentos (codigo, nombre, cuenta_gasto_id, activo)
            VALUES (:codigo, :nombre, :cuenta_gasto_id, 1)
        ");
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cuenta_gasto_id' => $cuentaGastoId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function actualizarDepartamento(int $id, array $datos): bool {
        $nombre = trim($datos['nombre'] ?? '');
        $cuentaGastoId = !empty($datos['cuenta_gasto_id']) ? (int)$datos['cuenta_gasto_id'] : null;
        $activo = isset($datos['activo']) ? (int)$datos['activo'] : 1;

        $stmt = $this->db->prepare("
            UPDATE rrhh_departamentos 
            SET nombre = :nombre, cuenta_gasto_id = :cuenta_gasto_id, activo = :activo 
            WHERE id = :id
        ");
        return $stmt->execute([
            'nombre' => $nombre,
            'cuenta_gasto_id' => $cuentaGastoId,
            'activo' => $activo,
            'id' => $id
        ]);
    }

    // =========================================================================
    // 2. GESTIÓN DE EMPLEADOS
    // =========================================================================

    public function listarEmpleados(string $busqueda = '', string $estado = '', int $departamentoId = 0): array {
        $sql = "SELECT e.*, d.nombre AS departamento_nombre, d.codigo AS departamento_codigo,
                       COALESCE((
                           SELECT SUM(a.saldo) 
                           FROM rrhh_anticipos a 
                           WHERE a.empleado_id = e.id AND a.estado IN ('pendiente', 'descontado_parcial')
                       ), 0.00) AS saldo_anticipos
                FROM rrhh_empleados e
                INNER JOIN rrhh_departamentos d ON e.departamento_id = d.id
                WHERE 1=1";
        $params = [];

        if ($busqueda !== '') {
            $sql .= " AND (e.nombre LIKE :q1 OR e.codigo LIKE :q2 OR e.cedula LIKE :q3 OR e.puesto LIKE :q4)";
            $term = '%' . trim($busqueda) . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
        }

        if ($estado !== '') {
            $sql .= " AND e.estado = :estado";
            $params['estado'] = $estado;
        }

        if ($departamentoId > 0) {
            $sql .= " AND e.departamento_id = :depto";
            $params['depto'] = $departamentoId;
        }

        $sql .= " ORDER BY e.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEmpleadoPorId(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT e.*, d.nombre AS departamento_nombre, d.codigo AS departamento_codigo,
                   COALESCE((
                       SELECT SUM(a.saldo) 
                       FROM rrhh_anticipos a 
                       WHERE a.empleado_id = e.id AND a.estado IN ('pendiente', 'descontado_parcial')
                   ), 0.00) AS saldo_anticipos
            FROM rrhh_empleados e
            INNER JOIN rrhh_departamentos d ON e.departamento_id = d.id
            WHERE e.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function guardarEmpleado(array $datos): int {
        $codigo = trim($datos['codigo'] ?? '');
        $nombre = trim($datos['nombre'] ?? '');
        $cedula = !empty($datos['cedula']) ? trim($datos['cedula']) : null;
        $departamentoId = (int)($datos['departamento_id'] ?? 0);
        $puesto = trim($datos['puesto'] ?? '');
        $salarioBase = (float)($datos['salario_base'] ?? 0.0);
        $cuentaAnticipoId = !empty($datos['cuenta_anticipo_id']) ? (int)$datos['cuenta_anticipo_id'] : null;
        $fechaIngreso = !empty($datos['fecha_ingreso']) ? trim($datos['fecha_ingreso']) : date('Y-m-d');
        $estado = in_array($datos['estado'] ?? '', ['activo', 'inactivo', 'liquidado']) ? $datos['estado'] : 'activo';

        if ($nombre === '' || $departamentoId <= 0 || $puesto === '') {
            throw new Exception("El nombre, departamento y puesto del empleado son obligatorios.");
        }

        if ($codigo === '') {
            $stmtMax = $this->db->query("SELECT MAX(id) FROM rrhh_empleados");
            $next = ((int)$stmtMax->fetchColumn()) + 1;
            $codigo = 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
        }

        $stmt = $this->db->prepare("
            INSERT INTO rrhh_empleados 
            (codigo, nombre, cedula, departamento_id, puesto, salario_base, cuenta_anticipo_id, fecha_ingreso, estado)
            VALUES 
            (:codigo, :nombre, :cedula, :departamento_id, :puesto, :salario_base, :cuenta_anticipo_id, :fecha_ingreso, :estado)
        ");
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cedula' => $cedula,
            'departamento_id' => $departamentoId,
            'puesto' => $puesto,
            'salario_base' => $salarioBase,
            'cuenta_anticipo_id' => $cuentaAnticipoId,
            'fecha_ingreso' => $fechaIngreso,
            'estado' => $estado
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function actualizarEmpleado(int $id, array $datos): bool {
        $nombre = trim($datos['nombre'] ?? '');
        $cedula = !empty($datos['cedula']) ? trim($datos['cedula']) : null;
        $departamentoId = (int)($datos['departamento_id'] ?? 0);
        $puesto = trim($datos['puesto'] ?? '');
        $salarioBase = (float)($datos['salario_base'] ?? 0.0);
        $cuentaAnticipoId = !empty($datos['cuenta_anticipo_id']) ? (int)$datos['cuenta_anticipo_id'] : null;
        $fechaIngreso = !empty($datos['fecha_ingreso']) ? trim($datos['fecha_ingreso']) : date('Y-m-d');
        $estado = in_array($datos['estado'] ?? '', ['activo', 'inactivo', 'liquidado']) ? $datos['estado'] : 'activo';

        if ($nombre === '' || $departamentoId <= 0 || $puesto === '') {
            throw new Exception("El nombre, departamento y puesto son obligatorios.");
        }

        $stmt = $this->db->prepare("
            UPDATE rrhh_empleados 
            SET nombre = :nombre, cedula = :cedula, departamento_id = :departamento_id,
                puesto = :puesto, salario_base = :salario_base, cuenta_anticipo_id = :cuenta_anticipo_id,
                fecha_ingreso = :fecha_ingreso, estado = :estado
            WHERE id = :id
        ");
        return $stmt->execute([
            'nombre' => $nombre,
            'cedula' => $cedula,
            'departamento_id' => $departamentoId,
            'puesto' => $puesto,
            'salario_base' => $salarioBase,
            'cuenta_anticipo_id' => $cuentaAnticipoId,
            'fecha_ingreso' => $fechaIngreso,
            'estado' => $estado,
            'id' => $id
        ]);
    }

    public function cambiarEstadoEmpleado(int $id, string $estado): bool {
        if (!in_array($estado, ['activo', 'inactivo', 'liquidado'])) {
            throw new Exception("Estado inválido.");
        }
        $stmt = $this->db->prepare("UPDATE rrhh_empleados SET estado = :estado WHERE id = :id");
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    // =========================================================================
    // 3. ANTICIPOS A EMPLEADOS
    // =========================================================================

    public function registrarAnticipo(int $empleadoId, float $monto, string $fecha, ?string $motivo = null, ?int $idBancoCuenta = null): int {
        if ($monto <= 0) {
            throw new Exception("El monto del anticipo debe ser mayor a cero.");
        }

        $empleado = $this->obtenerEmpleadoPorId($empleadoId);
        if (!$empleado) {
            throw new Exception("El empleado especificado no existe.");
        }

        $debeIniciarTransaccion = !$this->db->inTransaction();
        if ($debeIniciarTransaccion) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO rrhh_anticipos (empleado_id, monto, saldo, fecha, motivo, estado, id_asiento)
                VALUES (:empleado_id, :monto, :saldo, :fecha, :motivo, 'pendiente', NULL)
            ");
            $stmt->execute([
                'empleado_id' => $empleadoId,
                'monto' => $monto,
                'saldo' => $monto,
                'fecha' => $fecha,
                'motivo' => $motivo
            ]);
            $anticipoId = (int)$this->db->lastInsertId();

            // Si se proporciona cuenta bancaria, generar movimiento y asiento contable
            if ($idBancoCuenta !== null && $idBancoCuenta > 0) {
                $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
                $stmtBco->execute(['id' => $idBancoCuenta]);
                $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);

                if (!$banco) {
                    throw new Exception("La cuenta bancaria seleccionada no existe.");
                }

                $nuevoSaldoBanco = (float)$banco['saldo_actual'] - $monto;
                $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
                $updBco->execute(['saldo' => $nuevoSaldoBanco, 'id' => $idBancoCuenta]);

                // Registrar transacción bancaria
                $insTx = $this->db->prepare("
                    INSERT INTO bancos_transacciones 
                    (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                    VALUES 
                    (:id_banco_cuenta, 'RETIRO', :num_doc, :beneficiario, :monto, :fecha, 'Emitido', :descripcion)
                ");
                $insTx->execute([
                    'id_banco_cuenta' => $idBancoCuenta,
                    'num_doc' => 'ANT-' . $anticipoId,
                    'beneficiario' => $empleado['nombre'],
                    'monto' => $monto,
                    'fecha' => $fecha,
                    'descripcion' => "Desembolso de Anticipo de Salario #" . $anticipoId . ($motivo ? " - " . $motivo : "")
                ]);

                // Asiento Contable: Debe Anticipos Empleados / Haber Bancos
                $ctaAnticipoId = $empleado['cuenta_anticipo_id'];
                if (!$ctaAnticipoId) {
                    $ctaAnticipoId = ConfigCuentasModelo::obtenerIdPorClave('ANTICIPO_EMPLEADOS') ?? 21; // 1010310
                }

                $contabilidad = new ContabilidadModelo($this->db);
                $idAsiento = $contabilidad->registrarAsientoContable(
                    $fecha,
                    "Anticipo de Salario - " . $empleado['nombre'] . ($motivo ? " (" . $motivo . ")" : ""),
                    'ANTICIPO_EMPLEADO',
                    $anticipoId,
                    [
                        ['id_cuenta_contable' => $ctaAnticipoId, 'debe' => $monto, 'haber' => 0.0],
                        ['id_cuenta_contable' => (int)$banco['id_cuenta_contable'], 'debe' => 0.0, 'haber' => $monto]
                    ]
                );

                if ($idAsiento) {
                    $updAnt = $this->db->prepare("UPDATE rrhh_anticipos SET id_asiento = :id_asiento WHERE id = :id");
                    $updAnt->execute(['id_asiento' => $idAsiento, 'id' => $anticipoId]);
                }
            }

            if ($debeIniciarTransaccion) {
                $this->db->commit();
            }

            return $anticipoId;
        } catch (Throwable $e) {
            if ($debeIniciarTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function listarAnticipos(int $empleadoId = 0, string $estado = '', string $busqueda = ''): array {
        $sql = "SELECT a.*, e.nombre AS empleado_nombre, e.codigo AS empleado_codigo,
                       d.nombre AS departamento_nombre, p.num_partida AS asiento_numero
                FROM rrhh_anticipos a
                INNER JOIN rrhh_empleados e ON a.empleado_id = e.id
                INNER JOIN rrhh_departamentos d ON e.departamento_id = d.id
                LEFT JOIN partidas_diario p ON a.id_asiento = p.id
                WHERE 1=1";
        $params = [];

        if ($empleadoId > 0) {
            $sql .= " AND a.empleado_id = :emp";
            $params['emp'] = $empleadoId;
        }

        if ($estado !== '') {
            $sql .= " AND a.estado = :estado";
            $params['estado'] = $estado;
        }

        if ($busqueda !== '') {
            $sql .= " AND (e.nombre LIKE :q1 OR e.codigo LIKE :q2 OR a.motivo LIKE :q3)";
            $term = '%' . trim($busqueda) . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }

        $sql .= " ORDER BY a.fecha DESC, a.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAnticiposPendientesPorEmpleado(int $empleadoId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM rrhh_anticipos 
            WHERE empleado_id = :id AND estado IN ('pendiente', 'descontado_parcial') AND saldo > 0 
            ORDER BY fecha ASC, id ASC
        ");
        $stmt->execute(['id' => $empleadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function anularAnticipo(int $id): bool {
        $stmt = $this->db->prepare("SELECT * FROM rrhh_anticipos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $ant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ant) {
            throw new Exception("El anticipo no existe.");
        }
        if ($ant['estado'] === 'descontado_total') {
            throw new Exception("No se puede anular un anticipo que ya fue totalmente descontado en nómina.");
        }

        $upd = $this->db->prepare("UPDATE rrhh_anticipos SET estado = 'anulado', saldo = 0.00 WHERE id = :id");
        return $upd->execute(['id' => $id]);
    }

    // =========================================================================
    // 4. CÁLCULO DE LEY (INSS, IR, PROVISIONES)
    // =========================================================================

    /**
     * Cálculo de Retención de IR Salarial según Ley 822 de Concertación Tributaria (Nicaragua).
     */
    public static function calcularIrLaboral(float $totalDevengado, float $inssLaboral): float {
        $baseMensual = max(0.0, $totalDevengado - $inssLaboral);
        $baseAnual = $baseMensual * 12.0;

        $irAnual = 0.0;
        if ($baseAnual > 500000.0) {
            $irAnual = 82500.0 + ($baseAnual - 500000.0) * 0.30;
        } elseif ($baseAnual > 350000.0) {
            $irAnual = 45000.0 + ($baseAnual - 350000.0) * 0.25;
        } elseif ($baseAnual > 200000.0) {
            $irAnual = 15000.0 + ($baseAnual - 200000.0) * 0.20;
        } elseif ($baseAnual > 100000.0) {
            $irAnual = ($baseAnual - 100000.0) * 0.15;
        } else {
            $irAnual = 0.0;
        }

        return round($irAnual / 12.0, 2);
    }

    /**
     * Previsualiza y simula el desglose de nómina para un empleado.
     */
    public function simularCalculoEmpleado(
        array $empleado,
        float $salarioBase,
        float $horasExtras = 0.0,
        ?float $descuentoAnticipoForzado = null,
        float $tasaInssPatronal = 0.225
    ): array {
        $totalDevengado = round($salarioBase + $horasExtras, 2);
        $inssLaboral = round($totalDevengado * 0.07, 2);
        $irLaboral = self::calcularIrLaboral($totalDevengado, $inssLaboral);

        // Anticipos pendientes
        $pendientes = $this->obtenerAnticiposPendientesPorEmpleado((int)$empleado['id']);
        $totalSaldoAnticipo = array_sum(array_column($pendientes, 'saldo'));

        if ($descuentoAnticipoForzado !== null) {
            $anticiposDescontados = min($totalSaldoAnticipo, max(0.0, $descuentoAnticipoForzado));
        } else {
            $netoPrevio = max(0.0, $totalDevengado - $inssLaboral - $irLaboral);
            $anticiposDescontados = min($totalSaldoAnticipo, $netoPrevio);
        }
        $anticiposDescontados = round($anticiposDescontados, 2);

        $totalDeducciones = round($inssLaboral + $irLaboral + $anticiposDescontados, 2);
        $netoAPagar = round($totalDevengado - $totalDeducciones, 2);

        // Cargas Patronales y Provisiones
        $inssPatronal = round($totalDevengado * $tasaInssPatronal, 2);
        $inatecPatronal = round($totalDevengado * 0.02, 2);
        $vacaciones = round($totalDevengado * 0.0833, 2);
        $aguinaldo = round($totalDevengado * 0.0833, 2);
        $indemnizacion = round($totalDevengado * 0.0833, 2);

        return [
            'empleado_id' => (int)$empleado['id'],
            'codigo' => $empleado['codigo'],
            'nombre' => $empleado['nombre'],
            'puesto' => $empleado['puesto'],
            'departamento_id' => (int)$empleado['departamento_id'],
            'salario_base' => $salarioBase,
            'horas_extras_monto' => $horasExtras,
            'total_devengado' => $totalDevengado,
            'inss_laboral' => $inssLaboral,
            'ir_laboral' => $irLaboral,
            'anticipos_descontados' => $anticiposDescontados,
            'saldo_anticipos_restante' => round($totalSaldoAnticipo - $anticiposDescontados, 2),
            'total_deducciones' => $totalDeducciones,
            'neto_a_pagar' => $netoAPagar,
            'inss_patronal' => $inssPatronal,
            'inatec_patronal' => $inatecPatronal,
            'vacaciones_provision' => $vacaciones,
            'aguinaldo_provision' => $aguinaldo,
            'indemnizacion_provision' => $indemnizacion
        ];
    }

    // =========================================================================
    // 5. PROCESAMIENTO COMPLETO DE NÓMINA Y ASIENTO CONTABLE
    // =========================================================================

    public function procesarNomina(
        string $codigo,
        string $periodoInicio,
        string $periodoFin,
        array $empleadosData,
        int $usuarioId,
        float $tasaInssPatronal = 0.225
    ): array {
        $codigo = trim($codigo);
        if ($codigo === '') {
            throw new Exception("El código de la nómina es obligatorio.");
        }
        if (empty($empleadosData)) {
            throw new Exception("Debe incluir al menos un empleado para procesar la nómina.");
        }

        // Validar unicidad del código
        $checkCod = $this->db->prepare("SELECT id FROM rrhh_nominas WHERE codigo = :codigo LIMIT 1");
        $checkCod->execute(['codigo' => $codigo]);
        if ($checkCod->fetch()) {
            throw new Exception("Ya existe una nómina con el código '{$codigo}'.");
        }

        $this->db->beginTransaction();

        try {
            $totalDevengadoGlobal = 0.0;
            $totalDeduccionesGlobal = 0.0;
            $totalNetoGlobal = 0.0;
            $totalInssPatronalGlobal = 0.0;
            $totalInatecGlobal = 0.0;
            $totalVacacionesGlobal = 0.0;
            $totalAguinaldoGlobal = 0.0;
            $totalIndemnizacionGlobal = 0.0;

            $detallesProcesados = [];
            $gastosPorDepto = []; // [depto_id => ['devengado' => x, 'inss_pat' => x, ...]]
            $amortizacionesAnticipos = []; // [cuenta_anticipo_id => monto]

            foreach ($empleadosData as $item) {
                $empleadoId = (int)($item['empleado_id'] ?? 0);
                $empleado = $this->obtenerEmpleadoPorId($empleadoId);
                if (!$empleado) {
                    throw new Exception("Empleado ID {$empleadoId} no encontrado.");
                }

                $salarioBase = isset($item['salario_base']) ? (float)$item['salario_base'] : (float)$empleado['salario_base'];
                $horasExtras = (float)($item['horas_extras_monto'] ?? 0.0);
                $totalDevengado = round($salarioBase + $horasExtras, 2);

                $inssLaboral = round($totalDevengado * 0.07, 2);
                $irLaboral = isset($item['ir_laboral']) ? (float)$item['ir_laboral'] : self::calcularIrLaboral($totalDevengado, $inssLaboral);

                // Aplicar descuento de anticipos con saldo pendiente
                $anticiposPendientes = $this->obtenerAnticiposPendientesPorEmpleado($empleadoId);
                $montoAnticipoDeseado = isset($item['anticipos_descontados'])
                    ? (float)$item['anticipos_descontados']
                    : null;

                $totalSaldoAnticipo = array_sum(array_column($anticiposPendientes, 'saldo'));
                if ($montoAnticipoDeseado !== null) {
                    $saldoADescontar = min($totalSaldoAnticipo, max(0.0, $montoAnticipoDeseado));
                } else {
                    $netoDisponible = max(0.0, $totalDevengado - $inssLaboral - $irLaboral);
                    $saldoADescontar = min($totalSaldoAnticipo, $netoDisponible);
                }

                $anticiposAplicados = 0.0;
                foreach ($anticiposPendientes as $ant) {
                    if ($saldoADescontar <= 0.0001) {
                        break;
                    }
                    $descuentoLinea = min((float)$ant['saldo'], $saldoADescontar);
                    $nuevoSaldo = round((float)$ant['saldo'] - $descuentoLinea, 2);
                    $nuevoEstado = ($nuevoSaldo <= 0.005) ? 'descontado_total' : 'descontado_parcial';

                    $updAnt = $this->db->prepare("
                        UPDATE rrhh_anticipos 
                        SET saldo = :saldo, estado = :estado 
                        WHERE id = :id
                    ");
                    $updAnt->execute([
                        'saldo' => $nuevoSaldo,
                        'estado' => $nuevoEstado,
                        'id' => $ant['id']
                    ]);

                    $anticiposAplicados += $descuentoLinea;
                    $saldoADescontar -= $descuentoLinea;
                }

                $anticiposDescontados = round($anticiposAplicados, 2);
                $totalDeducciones = round($inssLaboral + $irLaboral + $anticiposDescontados, 2);
                $netoAPagar = round($totalDevengado - $totalDeducciones, 2);

                $inssPatronal = round($totalDevengado * $tasaInssPatronal, 2);
                $inatecPatronal = round($totalDevengado * 0.02, 2);
                $vacaciones = round($totalDevengado * 0.0833, 2);
                $aguinaldo = round($totalDevengado * 0.0833, 2);
                $indemnizacion = round($totalDevengado * 0.0833, 2);

                // Acumular globales
                $totalDevengadoGlobal += $totalDevengado;
                $totalDeduccionesGlobal += $totalDeducciones;
                $totalNetoGlobal += $netoAPagar;
                $totalInssPatronalGlobal += $inssPatronal;
                $totalInatecGlobal += $inatecPatronal;
                $totalVacacionesGlobal += $vacaciones;
                $totalAguinaldoGlobal += $aguinaldo;
                $totalIndemnizacionGlobal += $indemnizacion;

                $detallesProcesados[] = [
                    'empleado_id' => $empleadoId,
                    'salario_base' => $salarioBase,
                    'horas_extras_monto' => $horasExtras,
                    'total_devengado' => $totalDevengado,
                    'inss_laboral' => $inssLaboral,
                    'ir_laboral' => $irLaboral,
                    'anticipos_descontados' => $anticiposDescontados,
                    'total_deducciones' => $totalDeducciones,
                    'neto_a_pagar' => $netoAPagar,
                    'inss_patronal' => $inssPatronal,
                    'inatec_patronal' => $inatecPatronal,
                    'vacaciones_provision' => $vacaciones,
                    'aguinaldo_provision' => $aguinaldo,
                    'indemnizacion_provision' => $indemnizacion,
                    'departamento_id' => (int)$empleado['departamento_id'],
                    'departamento_codigo' => $empleado['departamento_codigo'],
                    'cuenta_anticipo_id' => $empleado['cuenta_anticipo_id']
                ];

                // Agrupar por departamento para asientos
                $deptoId = (int)$empleado['departamento_id'];
                if (!isset($gastosPorDepto[$deptoId])) {
                    $gastosPorDepto[$deptoId] = [
                        'depto_codigo' => $empleado['departamento_codigo'],
                        'devengado' => 0.0,
                        'inss_patronal' => 0.0,
                        'inatec' => 0.0,
                        'vacaciones' => 0.0,
                        'aguinaldo' => 0.0,
                        'indemnizacion' => 0.0
                    ];
                }
                $gastosPorDepto[$deptoId]['devengado'] += $totalDevengado;
                $gastosPorDepto[$deptoId]['inss_patronal'] += $inssPatronal;
                $gastosPorDepto[$deptoId]['inatec'] += $inatecPatronal;
                $gastosPorDepto[$deptoId]['vacaciones'] += $vacaciones;
                $gastosPorDepto[$deptoId]['aguinaldo'] += $aguinaldo;
                $gastosPorDepto[$deptoId]['indemnizacion'] += $indemnizacion;

                // Agrupar amortización de anticipos
                if ($anticiposDescontados > 0) {
                    $ctaAnt = $empleado['cuenta_anticipo_id'];
                    if (!$ctaAnt) {
                        $ctaAnt = ConfigCuentasModelo::obtenerIdPorClave('ANTICIPO_EMPLEADOS') ?? 21; // 1010310
                    }
                    if (!isset($amortizacionesAnticipos[$ctaAnt])) {
                        $amortizacionesAnticipos[$ctaAnt] = 0.0;
                    }
                    $amortizacionesAnticipos[$ctaAnt] += $anticiposDescontados;
                }
            }

            // 1. Guardar cabecera de la nómina
            $insNom = $this->db->prepare("
                INSERT INTO rrhh_nominas 
                (codigo, periodo_inicio, periodo_fin, total_devengado, total_deducciones, total_neto, estado, procesada_por)
                VALUES 
                (:codigo, :periodo_inicio, :periodo_fin, :total_devengado, :total_deducciones, :total_neto, 'procesada', :procesada_por)
            ");
            $insNom->execute([
                'codigo' => $codigo,
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'total_devengado' => $totalDevengadoGlobal,
                'total_deducciones' => $totalDeduccionesGlobal,
                'total_neto' => $totalNetoGlobal,
                'procesada_por' => $usuarioId
            ]);
            $nominaId = (int)$this->db->lastInsertId();

            // 2. Guardar detalles
            $insDet = $this->db->prepare("
                INSERT INTO rrhh_nomina_detalles
                (nomina_id, empleado_id, salario_base, horas_extras_monto, total_devengado,
                 inss_laboral, ir_laboral, anticipos_descontados, total_deducciones, neto_a_pagar,
                 inss_patronal, inatec_patronal, vacaciones_provision, aguinaldo_provision, indemnizacion_provision)
                VALUES
                (:nomina_id, :empleado_id, :salario_base, :horas_extras_monto, :total_devengado,
                 :inss_laboral, :ir_laboral, :anticipos_descontados, :total_deducciones, :neto_a_pagar,
                 :inss_patronal, :inatec_patronal, :vacaciones_provision, :aguinaldo_provision, :indemnizacion_provision)
            ");

            foreach ($detallesProcesados as $det) {
                $insDet->execute([
                    'nomina_id' => $nominaId,
                    'empleado_id' => $det['empleado_id'],
                    'salario_base' => $det['salario_base'],
                    'horas_extras_monto' => $det['horas_extras_monto'],
                    'total_devengado' => $det['total_devengado'],
                    'inss_laboral' => $det['inss_laboral'],
                    'ir_laboral' => $det['ir_laboral'],
                    'anticipos_descontados' => $det['anticipos_descontados'],
                    'total_deducciones' => $det['total_deducciones'],
                    'neto_a_pagar' => $det['neto_a_pagar'],
                    'inss_patronal' => $det['inss_patronal'],
                    'inatec_patronal' => $det['inatec_patronal'],
                    'vacaciones_provision' => $det['vacaciones_provision'],
                    'aguinaldo_provision' => $det['aguinaldo_provision'],
                    'indemnizacion_provision' => $det['indemnizacion_provision']
                ]);
            }

            // 3. Generar Asiento Contable en Partida Doble
            $lineasAsiento = $this->construirLineasAsientoNomina(
                $gastosPorDepto,
                $detallesProcesados,
                $totalNetoGlobal,
                $amortizacionesAnticipos,
                $totalInssPatronalGlobal,
                $totalInatecGlobal,
                $totalVacacionesGlobal,
                $totalAguinaldoGlobal,
                $totalIndemnizacionGlobal
            );

            $contabilidad = new ContabilidadModelo($this->db);
            $idAsiento = $contabilidad->registrarAsientoContable(
                $periodoFin,
                "Planilla de Sueldos y Provisiones Laborales - Nómina " . $codigo,
                'NOMINA',
                $nominaId,
                $lineasAsiento
            );

            if ($idAsiento) {
                $updNom = $this->db->prepare("UPDATE rrhh_nominas SET id_asiento = :asiento WHERE id = :id");
                $updNom->execute(['asiento' => $idAsiento, 'id' => $nominaId]);
            }

            $this->db->commit();

            return [
                'ok' => true,
                'nomina_id' => $nominaId,
                'codigo' => $codigo,
                'id_asiento' => $idAsiento,
                'totales' => [
                    'devengado' => $totalDevengadoGlobal,
                    'deducciones' => $totalDeduccionesGlobal,
                    'neto' => $totalNetoGlobal,
                    'inss_patronal' => $totalInssPatronalGlobal,
                    'inatec' => $totalInatecGlobal,
                    'vacaciones' => $totalVacacionesGlobal,
                    'aguinaldo' => $totalAguinaldoGlobal,
                    'indemnizacion' => $totalIndemnizacionGlobal
                ],
                'empleados_procesados' => count($detallesProcesados)
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Construye y cuadra estrictamente las líneas del asiento contable en partida doble.
     */
    private function construirLineasAsientoNomina(
        array $gastosPorDepto,
        array $detalles,
        float $totalNeto,
        array $amortizacionesAnticipos,
        float $totalInssPatronal,
        float $totalInatec,
        float $totalVacaciones,
        float $totalAguinaldo,
        float $totalIndemnizacion
    ): array {
        // Cuentas de Pasivo
        $ctaNeto = ConfigCuentasModelo::obtenerIdPorClave('SUELDOS_POR_PAGAR') ?? 172; // 2010806
        $ctaInssLab = ConfigCuentasModelo::obtenerIdPorClave('INSS_LABORAL_PAGAR') ?? 150; // 2010504
        $ctaIrLab = ConfigCuentasModelo::obtenerIdPorClave('IR_SALARIOS_PAGAR') ?? 152; // 2010506
        $ctaInssPat = ConfigCuentasModelo::obtenerIdPorClave('INSS_PATRONAL_PAGAR') ?? 170; // 2010804
        $ctaInatec = ConfigCuentasModelo::obtenerIdPorClave('INATEC_PAGAR') ?? 171; // 2010805
        $ctaProvVac = ConfigCuentasModelo::obtenerIdPorClave('PROV_VACACIONES') ?? 167; // 2010801
        $ctaProvAguin = ConfigCuentasModelo::obtenerIdPorClave('PROV_AGUINALDO') ?? 168; // 2010802
        $ctaProvIndem = ConfigCuentasModelo::obtenerIdPorClave('PROV_INDEMNIZACION') ?? 169; // 2010803

        // Cuentas de Gasto
        $ctaGastoLabSueldos = ConfigCuentasModelo::obtenerIdPorClave('GASTO_SUELDOS_LAB') ?? 243; // 5010201
        $ctaGastoAdmSueldos = ConfigCuentasModelo::obtenerIdPorClave('GASTO_SUELDOS_ADM') ?? 266; // 6010101
        $ctaGastoLabInssPat = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INSS_PAT_LAB') ?? 248; // 5010206
        $ctaGastoAdmInssPat = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INSS_PAT_ADM') ?? 271; // 6010106
        $ctaGastoLabInatec = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INATEC_LAB') ?? 249; // 5010207
        $ctaGastoAdmInatec = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INATEC_ADM') ?? 272; // 6010107
        $ctaGastoLabVac = ConfigCuentasModelo::obtenerIdPorClave('GASTO_VACACIONES_LAB') ?? 245; // 5010203
        $ctaGastoAdmVac = ConfigCuentasModelo::obtenerIdPorClave('GASTO_VACACIONES_ADM') ?? 268; // 6010103
        $ctaGastoLabAguin = ConfigCuentasModelo::obtenerIdPorClave('GASTO_AGUINALDO_LAB') ?? 246; // 5010204
        $ctaGastoAdmAguin = ConfigCuentasModelo::obtenerIdPorClave('GASTO_AGUINALDO_ADM') ?? 269; // 6010104
        $ctaGastoLabIndem = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INDEMNIZACION_LAB') ?? 247; // 5010205
        $ctaGastoAdmIndem = ConfigCuentasModelo::obtenerIdPorClave('GASTO_INDEMNIZACION_ADM') ?? 270; // 6010105

        $mapDebe = [];  // [id_cuenta => monto]
        $mapHaber = []; // [id_cuenta => monto]

        // 1. CARGOS (DEBE): Gasto de Salarios y Cargas por Departamento
        foreach ($gastosPorDepto as $deptoId => $g) {
            $esLab = ($g['depto_codigo'] === 'LAB');

            $cDev = $esLab ? $ctaGastoLabSueldos : $ctaGastoAdmSueldos;
            $cInssPat = $esLab ? $ctaGastoLabInssPat : $ctaGastoAdmInssPat;
            $cInatec = $esLab ? $ctaGastoLabInatec : $ctaGastoAdmInatec;
            $cVac = $esLab ? $ctaGastoLabVac : $ctaGastoAdmVac;
            $cAguin = $esLab ? $ctaGastoLabAguin : $ctaGastoAdmAguin;
            $cIndem = $esLab ? $ctaGastoLabIndem : $ctaGastoAdmIndem;

            $mapDebe[$cDev] = ($mapDebe[$cDev] ?? 0.0) + $g['devengado'];
            $mapDebe[$cInssPat] = ($mapDebe[$cInssPat] ?? 0.0) + $g['inss_patronal'];
            $mapDebe[$cInatec] = ($mapDebe[$cInatec] ?? 0.0) + $g['inatec'];
            $mapDebe[$cVac] = ($mapDebe[$cVac] ?? 0.0) + $g['vacaciones'];
            $mapDebe[$cAguin] = ($mapDebe[$cAguin] ?? 0.0) + $g['aguinaldo'];
            $mapDebe[$cIndem] = ($mapDebe[$cIndem] ?? 0.0) + $g['indemnizacion'];
        }

        // 2. ABONOS (HABER): Pasivo Sueldos Netos, Retenciones, Anticipos y Provisiones
        $totalInssLab = array_sum(array_column($detalles, 'inss_laboral'));
        $totalIrLab = array_sum(array_column($detalles, 'ir_laboral'));

        $mapHaber[$ctaNeto] = ($mapHaber[$ctaNeto] ?? 0.0) + $totalNeto;
        if ($totalInssLab > 0) {
            $mapHaber[$ctaInssLab] = ($mapHaber[$ctaInssLab] ?? 0.0) + $totalInssLab;
        }
        if ($totalIrLab > 0) {
            $mapHaber[$ctaIrLab] = ($mapHaber[$ctaIrLab] ?? 0.0) + $totalIrLab;
        }
        foreach ($amortizacionesAnticipos as $ctaAnt => $montoAnt) {
            if ($montoAnt > 0) {
                $mapHaber[$ctaAnt] = ($mapHaber[$ctaAnt] ?? 0.0) + $montoAnt;
            }
        }
        if ($totalInssPatronal > 0) {
            $mapHaber[$ctaInssPat] = ($mapHaber[$ctaInssPat] ?? 0.0) + $totalInssPatronal;
        }
        if ($totalInatec > 0) {
            $mapHaber[$ctaInatec] = ($mapHaber[$ctaInatec] ?? 0.0) + $totalInatec;
        }
        if ($totalVacaciones > 0) {
            $mapHaber[$ctaProvVac] = ($mapHaber[$ctaProvVac] ?? 0.0) + $totalVacaciones;
        }
        if ($totalAguinaldo > 0) {
            $mapHaber[$ctaProvAguin] = ($mapHaber[$ctaProvAguin] ?? 0.0) + $totalAguinaldo;
        }
        if ($totalIndemnizacion > 0) {
            $mapHaber[$ctaProvIndem] = ($mapHaber[$ctaProvIndem] ?? 0.0) + $totalIndemnizacion;
        }

        // 3. Consolidar a líneas
        $lineas = [];
        $sumDebe = 0.0;
        $sumHaber = 0.0;

        foreach ($mapDebe as $cta => $monto) {
            $m = round($monto, 2);
            if ($m > 0) {
                $lineas[] = ['id_cuenta_contable' => (int)$cta, 'debe' => $m, 'haber' => 0.0];
                $sumDebe += $m;
            }
        }

        $lineaNetoIndex = null;
        foreach ($mapHaber as $cta => $monto) {
            $m = round($monto, 2);
            if ($m > 0) {
                if ($cta === $ctaNeto) {
                    $lineaNetoIndex = count($lineas);
                }
                $lineas[] = ['id_cuenta_contable' => (int)$cta, 'debe' => 0.0, 'haber' => $m];
                $sumHaber += $m;
            }
        }

        // 4. Ajuste de centavos para cuadre 100% exacto
        $diff = round($sumDebe - $sumHaber, 2);
        if (abs($diff) > 0.0001 && $lineaNetoIndex !== null) {
            $lineas[$lineaNetoIndex]['haber'] = round($lineas[$lineaNetoIndex]['haber'] + $diff, 2);
        }

        return $lineas;
    }

    // =========================================================================
    // 6. CONSULTAS DE NÓMINA
    // =========================================================================

    public function listarNominas(string $busqueda = '', string $estado = ''): array {
        $sql = "SELECT n.*, u.nombre AS procesada_por_nombre, p.num_partida AS asiento_numero,
                       (SELECT COUNT(*) FROM rrhh_nomina_detalles WHERE nomina_id = n.id) AS cantidad_empleados
                FROM rrhh_nominas n
                LEFT JOIN usuarios u ON n.procesada_por = u.id
                LEFT JOIN partidas_diario p ON n.id_asiento = p.id
                WHERE 1=1";
        $params = [];

        if ($busqueda !== '') {
            $sql .= " AND (n.codigo LIKE :q1 OR p.num_partida LIKE :q2)";
            $term = '%' . trim($busqueda) . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
        }

        if ($estado !== '') {
            $sql .= " AND n.estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY n.periodo_fin DESC, n.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerNominaPorId(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT n.*, u.nombre AS procesada_por_nombre, p.num_partida AS asiento_numero
            FROM rrhh_nominas n
            LEFT JOIN usuarios u ON n.procesada_por = u.id
            LEFT JOIN partidas_diario p ON n.id_asiento = p.id
            WHERE n.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $nomina = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$nomina) {
            return null;
        }

        $stmtDet = $this->db->prepare("
            SELECT nd.*, e.nombre AS empleado_nombre, e.codigo AS empleado_codigo, e.cedula AS empleado_cedula,
                   e.puesto AS empleado_puesto, d.nombre AS departamento_nombre, d.codigo AS departamento_codigo
            FROM rrhh_nomina_detalles nd
            INNER JOIN rrhh_empleados e ON nd.empleado_id = e.id
            INNER JOIN rrhh_departamentos d ON e.departamento_id = d.id
            WHERE nd.nomina_id = :id
            ORDER BY d.nombre ASC, e.nombre ASC
        ");
        $stmtDet->execute(['id' => $id]);
        $nomina['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $nomina;
    }

    public function anularNomina(int $id, int $usuarioId): bool {
        $nomina = $this->obtenerNominaPorId($id);
        if (!$nomina) {
            throw new Exception("La nómina no existe.");
        }
        if ($nomina['estado'] === 'anulada') {
            throw new Exception("La nómina ya se encuentra anulada.");
        }

        $this->db->beginTransaction();
        try {
            // Revertir amortización de anticipos
            foreach ($nomina['detalles'] as $det) {
                if ((float)$det['anticipos_descontados'] > 0) {
                    $montoARestaurar = (float)$det['anticipos_descontados'];
                    $stmtAnts = $this->db->prepare("
                        SELECT * FROM rrhh_anticipos 
                        WHERE empleado_id = :emp_id AND estado IN ('descontado_total', 'descontado_parcial')
                        ORDER BY fecha DESC, id DESC
                    ");
                    $stmtAnts->execute(['emp_id' => $det['empleado_id']]);
                    $ants = $stmtAnts->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($ants as $a) {
                        if ($montoARestaurar <= 0.0001) {
                            break;
                        }
                        $reponer = min((float)$a['monto'] - (float)$a['saldo'], $montoARestaurar);
                        $nuevoSaldo = (float)$a['saldo'] + $reponer;
                        $nuevoEstado = ($nuevoSaldo >= (float)$a['monto']) ? 'pendiente' : 'descontado_parcial';

                        $this->db->prepare("UPDATE rrhh_anticipos SET saldo = :saldo, estado = :estado WHERE id = :id")
                                 ->execute(['saldo' => $nuevoSaldo, 'estado' => $nuevoEstado, 'id' => $a['id']]);

                        $montoARestaurar -= $reponer;
                    }
                }
            }

            // Actualizar estado de nómina
            $this->db->prepare("UPDATE rrhh_nominas SET estado = 'anulada' WHERE id = :id")
                     ->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
