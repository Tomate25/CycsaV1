<?php

namespace Cycsa\Modulos\Prestamos\Modelos;

use Cycsa\Nucleo\ModeloBase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use PDO;
use Exception;
use Throwable;

/**
 * Modelo para la Gestión Integral de Préstamos Bancarios (TAREA-052).
 * FASE 9: Amortización (Método Francés), Desembolsos, Pagos de Cuota e Integración Contable y Bancaria.
 */
class PrestamosModelo extends ModeloBase {

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    /**
     * Calcula la fecha de vencimiento sumando N meses de forma segura.
     * Respeta el último día del mes en caso de meses con menos días (ej. 31 enero -> 28 febrero).
     */
    public static function calcularFechaVencimiento(string $fechaInicio, int $mesesDespues): string {
        $dt = new \DateTime($fechaInicio);
        $diaOriginal = (int)$dt->format('d');
        $anoOriginal = (int)$dt->format('Y');
        $mesOriginal = (int)$dt->format('m');

        $mesTotal = $mesOriginal + $mesesDespues;
        $anoDestino = $anoOriginal + (int)intdiv($mesTotal - 1, 12);
        $mesDestino = (($mesTotal - 1) % 12) + 1;

        $diasEnMes = (int)date('t', strtotime(sprintf('%04d-%02d-01', $anoDestino, $mesDestino)));
        $diaDestino = min($diaOriginal, $diasEnMes);

        return sprintf('%04d-%02d-%02d', $anoDestino, $mesDestino, $diaDestino);
    }

    /**
     * Genera la tabla completa de amortización bajo el método francés (cuota nivelada).
     * Garantiza que la suma de amortizaciones de capital cuadre exactamente con el monto original.
     *
     * @param float $monto Monto del préstamo
     * @param float $tasaAnual Tasa de interés nominal anual (%)
     * @param int $plazoMeses Número total de cuotas mensuales
     * @param string $fechaInicio Fecha del desembolso (Y-m-d)
     * @return array
     */
    public function calcularTablaAmortizacion(float $monto, float $tasaAnual, int $plazoMeses, string $fechaInicio): array {
        $monto = round($monto, 2);
        if ($monto <= 0 || $plazoMeses <= 0) {
            return [];
        }

        $tasaMensual = ($tasaAnual / 100.0) / 12.0;

        if ($tasaMensual > 0) {
            // Cuota fija francesa: C = P * [ i / (1 - (1 + i)^(-n)) ]
            $factor = pow(1.0 + $tasaMensual, -$plazoMeses);
            $cuotaNivelada = round($monto * ($tasaMensual / (1.0 - $factor)), 2);
        } else {
            $cuotaNivelada = round($monto / $plazoMeses, 2);
        }

        $tabla = [];
        $saldoActual = $monto;

        for ($k = 1; $k <= $plazoMeses; $k++) {
            $fechaVenc = self::calcularFechaVencimiento($fechaInicio, $k);

            $interes = ($tasaMensual > 0) ? round($saldoActual * $tasaMensual, 2) : 0.0;

            if ($k === $plazoMeses) {
                // Última cuota: absorbe cualquier residuo por redondeo centesimal
                $amortizacion = $saldoActual;
                $cuotaTotal = round($amortizacion + $interes, 2);
                $saldoPendiente = 0.0;
            } else {
                $amortizacion = round($cuotaNivelada - $interes, 2);
                if ($amortizacion > $saldoActual) {
                    $amortizacion = $saldoActual;
                }
                $cuotaTotal = round($amortizacion + $interes, 2);
                $saldoPendiente = round($saldoActual - $amortizacion, 2);
            }

            $tabla[] = [
                'numero_cuota' => $k,
                'fecha_vencimiento' => $fechaVenc,
                'cuota_total' => $cuotaTotal,
                'amortizacion_capital' => $amortizacion,
                'interes' => $interes,
                'saldo_pendiente' => $saldoPendiente
            ];

            $saldoActual = $saldoPendiente;
        }

        return $tabla;
    }

    /**
     * Genera el siguiente código consecutivo disponible para préstamos.
     */
    public function generarSiguienteCodigo(): string {
        $ano = date('Y');
        $prefijo = "PB-{$ano}-";

        $stmt = $this->db->prepare("
            SELECT codigo FROM prestamos_bancarios
            WHERE codigo LIKE :prefijo
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['prefijo' => $prefijo . '%']);
        $ultimo = $stmt->fetchColumn();

        if ($ultimo) {
            $partes = explode('-', $ultimo);
            $secuencia = (int)end($partes);
            $siguiente = $secuencia + 1;
        } else {
            $siguiente = 1;
        }

        return $prefijo . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un nuevo préstamo bancario:
     * 1. Valida datos y genera tabla de amortización nivelada (francés).
     * 2. Registra el préstamo en prestamos_bancarios y sus cuotas en prestamos_cuotas.
     * 3. Aumenta el saldo de la cuenta bancaria en bancos_cuentas y registra la transacción bancaria.
     * 4. Genera el asiento contable de desembolso (D: Banco / H: Pasivo Préstamo).
     *
     * @param array $datos
     * @param int $usuarioId
     * @return array
     * @throws Exception
     */
    public function registrarPrestamo(array $datos, int $usuarioId): array {
        $institucion = trim($datos['institucion_financiera'] ?? '');
        $monto = round((float)($datos['monto_prestamo'] ?? 0), 2);
        $tasaAnual = round((float)($datos['tasa_interes_anual'] ?? 0), 2);
        $plazo = (int)($datos['plazo_meses'] ?? 0);
        $fechaDesembolso = trim($datos['fecha_desembolso'] ?? date('Y-m-d'));
        $cuentaBancoId = (int)($datos['cuenta_banco_id'] ?? 0);
        $cuentaPasivoId = !empty($datos['cuenta_pasivo_id']) ? (int)$datos['cuenta_pasivo_id'] : null;

        $codigo = trim($datos['codigo'] ?? '');
        if ($codigo === '') {
            $codigo = $this->generarSiguienteCodigo();
        }

        if ($institucion === '') {
            throw new Exception("La institución financiera es obligatoria.");
        }
        if ($monto <= 0) {
            throw new Exception("El monto del préstamo debe ser mayor a cero.");
        }
        if ($tasaAnual < 0) {
            throw new Exception("La tasa de interés anual no puede ser negativa.");
        }
        if ($plazo <= 0) {
            throw new Exception("El plazo en meses debe ser al menos 1.");
        }
        if ($cuentaBancoId <= 0) {
            throw new Exception("Debe seleccionar una cuenta bancaria receptora.");
        }

        $transaccionIniciada = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transaccionIniciada = true;
        }

        try {
            // Verificar código único
            $stmtCheckCod = $this->db->prepare("SELECT id FROM prestamos_bancarios WHERE codigo = :codigo LIMIT 1");
            $stmtCheckCod->execute(['codigo' => $codigo]);
            if ($stmtCheckCod->fetch()) {
                throw new Exception("Ya existe un préstamo con el código '{$codigo}'.");
            }

            // Obtener y bloquear cuenta bancaria
            $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
            $stmtBco->execute(['id' => $cuentaBancoId]);
            $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);
            if (!$banco) {
                throw new Exception("La cuenta bancaria seleccionada no existe.");
            }

            // Resolver cuenta pasivo si no fue seleccionada
            if (!$cuentaPasivoId) {
                $cuentaPasivoId = ConfigCuentasModelo::obtenerIdPorClave('PRESTAMOS_BANCARIOS', $this->db);
                if (!$cuentaPasivoId) {
                    $tc = $this->db->query("SHOW TABLES LIKE 'cuentas_contables'")->fetch() ? 'cuentas_contables' : 'cuentas';
                    $ctaFallback = $this->db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '20104%' AND tipo = 'DETALLE' ORDER BY id ASC LIMIT 1")->fetchColumn();
                    $cuentaPasivoId = $ctaFallback ? (int)$ctaFallback : 139;
                }
            }

            // Generar tabla de amortización
            $cuotas = $this->calcularTablaAmortizacion($monto, $tasaAnual, $plazo, $fechaDesembolso);
            if (empty($cuotas)) {
                throw new Exception("Error calculando la tabla de amortización.");
            }

            // 1. Insertar préstamo en prestamos_bancarios
            $sqlPrestamo = "
                INSERT INTO prestamos_bancarios (
                    codigo, institucion_financiera, monto_prestamo, tasa_interes_anual,
                    plazo_meses, fecha_desembolso, cuenta_banco_id, cuenta_pasivo_id,
                    saldo_capital, estado, id_asiento_desembolso
                ) VALUES (
                    :codigo, :institucion, :monto, :tasa,
                    :plazo, :fecha, :cuenta_banco_id, :cuenta_pasivo_id,
                    :saldo_capital, 'activo', NULL
                )
            ";
            $stmtInsPb = $this->db->prepare($sqlPrestamo);
            $stmtInsPb->execute([
                'codigo' => $codigo,
                'institucion' => $institucion,
                'monto' => $monto,
                'tasa' => $tasaAnual,
                'plazo' => $plazo,
                'fecha' => $fechaDesembolso,
                'cuenta_banco_id' => $cuentaBancoId,
                'cuenta_pasivo_id' => $cuentaPasivoId,
                'saldo_capital' => $monto
            ]);
            $prestamoId = (int)$this->db->lastInsertId();

            // 2. Insertar cuotas en prestamos_cuotas
            $sqlCuota = "
                INSERT INTO prestamos_cuotas (
                    prestamo_id, numero_cuota, fecha_vencimiento, cuota_total,
                    amortizacion_capital, interes, saldo_pendiente, estado, fecha_pago, id_asiento_pago
                ) VALUES (
                    :prestamo_id, :num_cuota, :vencimiento, :cuota_total,
                    :amortizacion, :interes, :saldo_pendiente, 'pendiente', NULL, NULL
                )
            ";
            $stmtInsCuota = $this->db->prepare($sqlCuota);
            foreach ($cuotas as $c) {
                $stmtInsCuota->execute([
                    'prestamo_id' => $prestamoId,
                    'num_cuota' => $c['numero_cuota'],
                    'vencimiento' => $c['fecha_vencimiento'],
                    'cuota_total' => $c['cuota_total'],
                    'amortizacion' => $c['amortizacion_capital'],
                    'interes' => $c['interes'],
                    'saldo_pendiente' => $c['saldo_pendiente']
                ]);
            }

            // 3. Afectar cuenta bancaria y registrar movimiento
            $nuevoSaldoBanco = round((float)$banco['saldo_actual'] + $monto, 2);
            $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
            $updBco->execute(['saldo' => $nuevoSaldoBanco, 'id' => $cuentaBancoId]);

            $insTx = $this->db->prepare("
                INSERT INTO bancos_transacciones 
                (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                VALUES 
                (:id_banco_cuenta, 'DEPOSITO', :num_doc, :beneficiario, :monto, :fecha, 'Cobrado', :descripcion)
            ");
            $insTx->execute([
                'id_banco_cuenta' => $cuentaBancoId,
                'num_doc' => $codigo,
                'beneficiario' => $institucion,
                'monto' => $monto,
                'fecha' => $fechaDesembolso,
                'descripcion' => "Desembolso Préstamo Bancario {$codigo} ({$institucion})"
            ]);

            // 4. Asiento Contable de Desembolso: D: Banco / H: Pasivo Préstamo
            $cuentaBancoContableId = (int)$banco['id_cuenta_contable'];
            if ($cuentaBancoContableId <= 0) {
                $tc = $this->db->query("SHOW TABLES LIKE 'cuentas_contables'")->fetch() ? 'cuentas_contables' : 'cuentas';
                $ctaBcoFallback = $this->db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '10101%' AND tipo = 'DETALLE' LIMIT 1")->fetchColumn();
                $cuentaBancoContableId = $ctaBcoFallback ? (int)$ctaBcoFallback : 4;
            }

            $lineasAsiento = [
                [
                    'id_cuenta_contable' => $cuentaBancoContableId,
                    'debe' => $monto,
                    'haber' => 0.0
                ],
                [
                    'id_cuenta_contable' => $cuentaPasivoId,
                    'debe' => 0.0,
                    'haber' => $monto
                ]
            ];

            $contabilidad = new ContabilidadModelo($this->db);
            $idAsiento = $contabilidad->registrarAsientoContable(
                $fechaDesembolso,
                "Desembolso Préstamo Bancario {$codigo} - {$institucion}",
                'PRESTAMO_BANCARIO',
                $prestamoId,
                $lineasAsiento
            );

            if ($idAsiento) {
                $updPb = $this->db->prepare("UPDATE prestamos_bancarios SET id_asiento_desembolso = :asiento WHERE id = :id");
                $updPb->execute(['asiento' => $idAsiento, 'id' => $prestamoId]);
            }

            if ($transaccionIniciada) {
                $this->db->commit();
            }

            return [
                'ok' => true,
                'prestamo_id' => $prestamoId,
                'codigo' => $codigo,
                'id_asiento' => $idAsiento,
                'cuotas_generadas' => count($cuotas),
                'monto' => $monto,
                'saldo_banco_actualizado' => $nuevoSaldoBanco
            ];
        } catch (Throwable $e) {
            if ($transaccionIniciada && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra el pago de una cuota de préstamo:
     * 1. Actualiza el estado de la cuota a 'pagada' y asigna fecha de pago.
     * 2. Descuenta la amortización de capital del saldo_capital del préstamo. Si llega a 0, cancela el préstamo.
     * 3. Descuenta la cuota_total de la cuenta bancaria y registra la transacción de retiro.
     * 4. Genera asiento contable en partida doble:
     *    - Debe: Pasivo Préstamo (amortización)
     *    - Debe: Gastos Financieros Intereses (interés)
     *    - Haber: Banco (cuota total)
     *
     * @param int $cuotaId
     * @param string $fechaPago
     * @param int $cuentaBancoId
     * @param int $usuarioId
     * @return array
     * @throws Exception
     */
    public function pagarCuota(int $cuotaId, string $fechaPago, int $cuentaBancoId, int $usuarioId): array {
        if ($cuotaId <= 0) {
            throw new Exception("ID de cuota inválido.");
        }
        if ($cuentaBancoId <= 0) {
            throw new Exception("Debe seleccionar una cuenta bancaria para debitar la cuota.");
        }
        if (empty($fechaPago)) {
            $fechaPago = date('Y-m-d');
        }

        $transaccionIniciada = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transaccionIniciada = true;
        }

        try {
            // 1. Obtener y bloquear la cuota
            $stmtCuota = $this->db->prepare("SELECT * FROM prestamos_cuotas WHERE id = :id FOR UPDATE");
            $stmtCuota->execute(['id' => $cuotaId]);
            $cuota = $stmtCuota->fetch(PDO::FETCH_ASSOC);

            if (!$cuota) {
                throw new Exception("La cuota especificada no existe.");
            }
            if ($cuota['estado'] === 'pagada') {
                throw new Exception("La cuota #{$cuota['numero_cuota']} ya fue pagada anteriormente.");
            }

            // 2. Obtener y bloquear el préstamo asociado
            $stmtPb = $this->db->prepare("SELECT * FROM prestamos_bancarios WHERE id = :id FOR UPDATE");
            $stmtPb->execute(['id' => $cuota['prestamo_id']]);
            $prestamo = $stmtPb->fetch(PDO::FETCH_ASSOC);

            if (!$prestamo) {
                throw new Exception("El préstamo asociado a la cuota no existe.");
            }
            if ($prestamo['estado'] === 'anulado') {
                throw new Exception("No es posible pagar cuotas de un préstamo anulado.");
            }

            // 3. Obtener y bloquear la cuenta bancaria pagadora
            $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
            $stmtBco->execute(['id' => $cuentaBancoId]);
            $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);

            if (!$banco) {
                throw new Exception("La cuenta bancaria seleccionada no existe.");
            }

            $montoTotal = round((float)$cuota['cuota_total'], 2);
            $amortizacion = round((float)$cuota['amortizacion_capital'], 2);
            $interes = round((float)$cuota['interes'], 2);

            // 4. Afectar saldo bancario y registrar transacción
            $nuevoSaldoBanco = round((float)$banco['saldo_actual'] - $montoTotal, 2);
            $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
            $updBco->execute(['saldo' => $nuevoSaldoBanco, 'id' => $cuentaBancoId]);

            $insTx = $this->db->prepare("
                INSERT INTO bancos_transacciones 
                (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                VALUES 
                (:id_banco_cuenta, 'RETIRO', :num_doc, :beneficiario, :monto, :fecha, 'Emitido', :descripcion)
            ");
            $insTx->execute([
                'id_banco_cuenta' => $cuentaBancoId,
                'num_doc' => "CUOTA-{$cuota['numero_cuota']}-{$prestamo['codigo']}",
                'beneficiario' => $prestamo['institucion_financiera'],
                'monto' => $montoTotal,
                'fecha' => $fechaPago,
                'descripcion' => "Pago Cuota {$cuota['numero_cuota']}/{$prestamo['plazo_meses']} Préstamo {$prestamo['codigo']}"
            ]);

            // 5. Asiento contable de pago de cuota
            // D: Pasivo Préstamo (amortización)
            // D: Gastos Financieros Intereses (interés)
            // H: Banco (cuota total)
            $cuentaPasivoId = !empty($prestamo['cuenta_pasivo_id']) 
                ? (int)$prestamo['cuenta_pasivo_id'] 
                : (ConfigCuentasModelo::obtenerIdPorClave('PRESTAMOS_BANCARIOS', $this->db) ?? 139);

            $cuentaInteresId = ConfigCuentasModelo::obtenerIdPorClave('GASTOS_INTERESES', $this->db);
            if (!$cuentaInteresId) {
                $tc = $this->db->query("SHOW TABLES LIKE 'cuentas_contables'")->fetch() ? 'cuentas_contables' : 'cuentas';
                $ctaGasto = $this->db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '60201%' AND tipo = 'DETALLE' ORDER BY id ASC LIMIT 1")->fetchColumn();
                $cuentaInteresId = $ctaGasto ? (int)$ctaGasto : 328;
            }

            $cuentaBancoContableId = (int)$banco['id_cuenta_contable'];
            if ($cuentaBancoContableId <= 0) {
                $tc = $this->db->query("SHOW TABLES LIKE 'cuentas_contables'")->fetch() ? 'cuentas_contables' : 'cuentas';
                $ctaBcoFallback = $this->db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '10101%' AND tipo = 'DETALLE' LIMIT 1")->fetchColumn();
                $cuentaBancoContableId = $ctaBcoFallback ? (int)$ctaBcoFallback : 4;
            }

            $lineasAsiento = [];
            if ($amortizacion > 0) {
                $lineasAsiento[] = [
                    'id_cuenta_contable' => $cuentaPasivoId,
                    'debe' => $amortizacion,
                    'haber' => 0.0
                ];
            }
            if ($interes > 0) {
                $lineasAsiento[] = [
                    'id_cuenta_contable' => $cuentaInteresId,
                    'debe' => $interes,
                    'haber' => 0.0
                ];
            }
            $lineasAsiento[] = [
                'id_cuenta_contable' => $cuentaBancoContableId,
                'debe' => 0.0,
                'haber' => $montoTotal
            ];

            $contabilidad = new ContabilidadModelo($this->db);
            $idAsiento = $contabilidad->registrarAsientoContable(
                $fechaPago,
                "Pago Cuota {$cuota['numero_cuota']}/{$prestamo['plazo_meses']} Préstamo {$prestamo['codigo']} ({$prestamo['institucion_financiera']})",
                'PAGO_CUOTA_PRESTAMO',
                $cuotaId,
                $lineasAsiento
            );

            // 6. Actualizar la cuota
            $updCuota = $this->db->prepare("
                UPDATE prestamos_cuotas 
                SET estado = 'pagada', fecha_pago = :fecha, id_asiento_pago = :asiento
                WHERE id = :id
            ");
            $updCuota->execute([
                'fecha' => $fechaPago,
                'asiento' => $idAsiento,
                'id' => $cuotaId
            ]);

            // 7. Actualizar el saldo de capital del préstamo
            $nuevoSaldoCapital = max(0.0, round((float)$prestamo['saldo_capital'] - $amortizacion, 2));

            // Verificar si quedan cuotas pendientes
            $stmtPend = $this->db->prepare("
                SELECT COUNT(*) FROM prestamos_cuotas 
                WHERE prestamo_id = :pid AND estado = 'pendiente'
            ");
            $stmtPend->execute(['pid' => $prestamo['id']]);
            $pendientes = (int)$stmtPend->fetchColumn();

            $nuevoEstado = ($pendientes === 0 || $nuevoSaldoCapital <= 0.01) ? 'cancelado' : 'activo';

            $updPb = $this->db->prepare("
                UPDATE prestamos_bancarios 
                SET saldo_capital = :saldo, estado = :estado
                WHERE id = :id
            ");
            $updPb->execute([
                'saldo' => $nuevoSaldoCapital,
                'estado' => $nuevoEstado,
                'id' => $prestamo['id']
            ]);

            if ($transaccionIniciada) {
                $this->db->commit();
            }

            return [
                'ok' => true,
                'cuota_id' => $cuotaId,
                'prestamo_id' => (int)$prestamo['id'],
                'id_asiento' => $idAsiento,
                'monto_pagado' => $montoTotal,
                'amortizacion' => $amortizacion,
                'interes' => $interes,
                'saldo_capital' => $nuevoSaldoCapital,
                'estado_prestamo' => $nuevoEstado
            ];
        } catch (Throwable $e) {
            if ($transaccionIniciada && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista préstamos con filtros y datos consolidados.
     */
    public function listarPrestamos(?string $busqueda = null, ?string $estado = null): array {
        $sql = "
            SELECT pb.*, bc.banco_nombre, bc.numero_cuenta, bc.moneda,
                   (SELECT COUNT(*) FROM prestamos_cuotas WHERE prestamo_id = pb.id) AS total_cuotas,
                   (SELECT COUNT(*) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS cuotas_pagadas,
                   (SELECT SUM(amortizacion_capital) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS capital_pagado,
                   (SELECT SUM(interes) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS intereses_pagados,
                   (SELECT num_partida FROM partidas_diario WHERE id = pb.id_asiento_desembolso) AS partida_desembolso
            FROM prestamos_bancarios pb
            LEFT JOIN bancos_cuentas bc ON pb.cuenta_banco_id = bc.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($estado)) {
            $sql .= " AND pb.estado = :estado";
            $params['estado'] = $estado;
        }

        if (!empty($busqueda)) {
            $sql .= " AND (pb.codigo LIKE :q1 OR pb.institucion_financiera LIKE :q2)";
            $termino = '%' . trim($busqueda) . '%';
            $params['q1'] = $termino;
            $params['q2'] = $termino;
        }

        $sql .= " ORDER BY pb.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un préstamo por su ID con todos sus metadatos.
     */
    public function obtenerPrestamoPorId(int $id): ?array {
        $sql = "
            SELECT pb.*, bc.banco_nombre, bc.numero_cuenta, bc.moneda,
                   (SELECT COUNT(*) FROM prestamos_cuotas WHERE prestamo_id = pb.id) AS total_cuotas,
                   (SELECT COUNT(*) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS cuotas_pagadas,
                   (SELECT SUM(cuota_total) FROM prestamos_cuotas WHERE prestamo_id = pb.id) AS total_pagar_proyectado,
                   (SELECT SUM(interes) FROM prestamos_cuotas WHERE prestamo_id = pb.id) AS total_intereses_proyectado,
                   (SELECT SUM(amortizacion_capital) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS capital_pagado,
                   (SELECT SUM(interes) FROM prestamos_cuotas WHERE prestamo_id = pb.id AND estado = 'pagada') AS intereses_pagados,
                   (SELECT num_partida FROM partidas_diario WHERE id = pb.id_asiento_desembolso) AS partida_desembolso
            FROM prestamos_bancarios pb
            LEFT JOIN bancos_cuentas bc ON pb.cuenta_banco_id = bc.id
            WHERE pb.id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lista todas las cuotas asociadas a un préstamo ordenadas por número de cuota.
     */
    public function obtenerCuotasPorPrestamo(int $prestamoId): array {
        $sql = "
            SELECT pc.*,
                   (SELECT num_partida FROM partidas_diario WHERE id = pc.id_asiento_pago) AS partida_pago
            FROM prestamos_cuotas pc
            WHERE pc.prestamo_id = :pid
            ORDER BY pc.numero_cuota ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $prestamoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una cuota específica por ID.
     */
    public function obtenerCuotaPorId(int $id): ?array {
        $sql = "
            SELECT pc.*, pb.codigo AS prestamo_codigo, pb.institucion_financiera, pb.cuenta_banco_id,
                   (SELECT num_partida FROM partidas_diario WHERE id = pc.id_asiento_pago) AS partida_pago
            FROM prestamos_cuotas pc
            INNER JOIN prestamos_bancarios pb ON pc.prestamo_id = pb.id
            WHERE pc.id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Resumen de dashboard para préstamos bancarios.
     */
    public function obtenerResumenDashboard(): array {
        $resumen = [
            'total_prestamos' => 0,
            'activos' => 0,
            'cancelados' => 0,
            'monto_total_prestado' => 0.0,
            'saldo_capital_vigente' => 0.0,
            'cuotas_pendientes_mes' => 0,
            'monto_pendientes_mes' => 0.0
        ];

        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) AS total_prestamos,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados,
                    COALESCE(SUM(monto_prestamo), 0) AS monto_total_prestado,
                    COALESCE(SUM(CASE WHEN estado = 'activo' THEN saldo_capital ELSE 0 END), 0) AS saldo_capital_vigente
                FROM prestamos_bancarios
                WHERE estado != 'anulado'
            ");
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $resumen['total_prestamos'] = (int)$data['total_prestamos'];
                $resumen['activos'] = (int)$data['activos'];
                $resumen['cancelados'] = (int)$data['cancelados'];
                $resumen['monto_total_prestado'] = (float)$data['monto_total_prestado'];
                $resumen['saldo_capital_vigente'] = (float)$data['saldo_capital_vigente'];
            }

            // Cuotas pendientes del mes actual o vencidas
            $mesActualFin = date('Y-m-t');
            $stmtCuotas = $this->db->prepare("
                SELECT COUNT(*) AS cantidad, COALESCE(SUM(cuota_total), 0) AS monto
                FROM prestamos_cuotas pc
                INNER JOIN prestamos_bancarios pb ON pc.prestamo_id = pb.id
                WHERE pb.estado = 'activo' AND pc.estado = 'pendiente' AND pc.fecha_vencimiento <= :limite
            ");
            $stmtCuotas->execute(['limite' => $mesActualFin]);
            $dCuotas = $stmtCuotas->fetch(PDO::FETCH_ASSOC);
            if ($dCuotas) {
                $resumen['cuotas_pendientes_mes'] = (int)$dCuotas['cantidad'];
                $resumen['monto_pendientes_mes'] = (float)$dCuotas['monto'];
            }
        } catch (Throwable $e) {
            error_log("Error en obtenerResumenDashboard préstamos: " . $e->getMessage());
        }

        return $resumen;
    }
}
