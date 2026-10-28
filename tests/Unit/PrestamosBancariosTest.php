<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Prestamos\Modelos\PrestamosModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;
use PDO;

/**
 * Suite de pruebas unitarias para el Módulo de Préstamos Bancarios (TAREA-052).
 * Valida amortización método francés, desembolso, pagos de cuotas y estricta partida doble.
 */
class PrestamosBancariosTest extends TestCase {

    private PDO $db;
    private PrestamosModelo $modelo;
    private int $bancoPruebaId = 0;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar ejecución de la migración 019
        $db = $this->db;
        $ruta019 = dirname(__DIR__, 2) . '/database/migrations/019_prestamos_bancarios.php';
        if (file_exists($ruta019)) {
            ob_start();
            include $ruta019;
            ob_end_clean();
        }

        $this->modelo = new PrestamosModelo($this->db);

        // Obtener o crear una cuenta bancaria de prueba
        $stmtBco = $this->db->query("SELECT id FROM bancos_cuentas WHERE activo = 1 LIMIT 1");
        $idBco = $stmtBco->fetchColumn();
        if ($idBco) {
            $this->bancoPruebaId = (int)$idBco;
        } else {
            // Resolver una cuenta contable para el banco de prueba
            $ctaBco = (int)($this->db->query("SELECT id FROM cuentas_contables WHERE codigo LIKE '10101%' AND tipo = 'DETALLE' LIMIT 1")->fetchColumn() ?: 4);
            $this->db->exec("
                INSERT INTO bancos_cuentas (id_cuenta_contable, banco_nombre, numero_cuenta, moneda, saldo_inicial, saldo_actual, activo)
                VALUES ({$ctaBco}, 'BANCO TEST', 'TEST-001', 'C$', 50000.00, 50000.00, 1)
            ");
            $this->bancoPruebaId = (int)$this->db->lastInsertId();
        }
    }

    /**
     * Valida que la migración 019 cree las tablas de préstamos y siembre config_cuentas.
     */
    public function testMigracion019CreaTablasYSiembraConfigCuentas(): void {
        $tablas = $this->db->query("SHOW TABLES LIKE 'prestamos_%'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('prestamos_bancarios', $tablas, "La tabla 'prestamos_bancarios' debe existir.");
        $this->assertContains('prestamos_cuotas', $tablas, "La tabla 'prestamos_cuotas' debe existir.");

        // Validar columnas clave en prestamos_bancarios
        $colsPb = $this->db->query("SHOW COLUMNS FROM `prestamos_bancarios`")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('codigo', $colsPb);
        $this->assertContains('monto_prestamo', $colsPb);
        $this->assertContains('tasa_interes_anual', $colsPb);
        $this->assertContains('saldo_capital', $colsPb);
        $this->assertContains('id_asiento_desembolso', $colsPb);

        // Validar columnas clave en prestamos_cuotas
        $colsPc = $this->db->query("SHOW COLUMNS FROM `prestamos_cuotas`")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('prestamo_id', $colsPc);
        $this->assertContains('cuota_total', $colsPc);
        $this->assertContains('amortizacion_capital', $colsPc);
        $this->assertContains('interes', $colsPc);
        $this->assertContains('saldo_pendiente', $colsPc);
        $this->assertContains('id_asiento_pago', $colsPc);

        // Validar sembrado en config_cuentas
        $idPb = ConfigCuentasModelo::obtenerIdPorClave('PRESTAMOS_BANCARIOS', $this->db);
        $this->assertNotNull($idPb, "La clave 'PRESTAMOS_BANCARIOS' debe estar configurada en config_cuentas.");

        $idGi = ConfigCuentasModelo::obtenerIdPorClave('GASTOS_INTERESES', $this->db);
        $this->assertNotNull($idGi, "La clave 'GASTOS_INTERESES' debe estar configurada en config_cuentas.");
    }

    /**
     * Valida la integración de la migración 019 en MigradorServicio de forma idempotente.
     */
    public function testMigradorServicioIdempotenteFase13(): void {
        $res = MigradorServicio::asegurarTablas($this->db);
        $this->assertIsArray($res);
        $this->assertArrayNotHasKey('error', $res, "MigradorServicio no debe arrojar errores.");
    }

    /**
     * Valida que las rutas de Préstamos Bancarios estén registradas en web.php con AuthMiddleware.
     */
    public function testRutasPrestamosRegistradasEnWebPhp(): void {
        $contenidoRutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertNotFalse($contenidoRutas);

        $this->assertStringContainsString("'/prestamos'", $contenidoRutas);
        $this->assertStringContainsString("'/prestamos/crear'", $contenidoRutas);
        $this->assertStringContainsString("'/prestamos/simular-ajax'", $contenidoRutas);
        $this->assertStringContainsString("'/prestamos/guardar'", $contenidoRutas);
        $this->assertStringContainsString("'/prestamos/detalle'", $contenidoRutas);
        $this->assertStringContainsString("'/prestamos/pagar-cuota'", $contenidoRutas);
        $this->assertStringContainsString('PrestamosControlador::class', $contenidoRutas);
    }

    /**
     * Valida matemáticamente el método francés de amortización con cuota nivelada.
     */
    public function testCalculoAmortizacionMetodoFrances(): void {
        $monto = 60000.00;
        $tasaAnual = 12.00; // 1% mensual
        $plazo = 12;
        $fechaInicio = '2026-01-15';

        $tabla = $this->modelo->calcularTablaAmortizacion($monto, $tasaAnual, $plazo, $fechaInicio);

        $this->assertCount(12, $tabla, "Deben generarse exactamente 12 cuotas.");

        $cuotaNiveladaEsperada = $tabla[0]['cuota_total'];
        $sumaCapital = 0.0;
        $sumaInteres = 0.0;

        foreach ($tabla as $i => $cuota) {
            $num = $i + 1;
            $this->assertEquals($num, $cuota['numero_cuota']);

            // Validar coherencia aritmética de cada cuota: Cuota = Capital + Interés
            $sumaCuota = round((float)$cuota['amortizacion_capital'] + (float)$cuota['interes'], 2);
            $this->assertEquals(
                (float)$cuota['cuota_total'],
                $sumaCuota,
                "En la cuota #{$num}, la cuota total debe ser la suma de capital e interés."
            );

            // Las cuotas 1 a 11 deben tener valor nivelado uniforme (diferencia máx 1 centavo)
            if ($num < $plazo) {
                $this->assertEqualsWithDelta($cuotaNiveladaEsperada, $cuota['cuota_total'], 0.05);
            }

            $sumaCapital += (float)$cuota['amortizacion_capital'];
            $sumaInteres += (float)$cuota['interes'];
        }

        // La suma de todas las amortizaciones de capital debe ser EXACTAMENTE el monto original
        $this->assertEqualsWithDelta(
            $monto,
            $sumaCapital,
            0.001,
            "La amortización total de capital debe cuadrar exactamente con el monto del préstamo."
        );

        // El saldo pendiente final de la última cuota debe ser 0.00
        $ultimaCuota = end($tabla);
        $this->assertEquals(0.00, (float)$ultimaCuota['saldo_pendiente'], "El saldo pendiente final debe ser 0.00.");

        // Interés total debe ser positivo
        $this->assertGreaterThan(0, $sumaInteres, "El interés total acumulado debe ser positivo.");
    }

    /**
     * Valida amortización a tasa cero (0% interés).
     */
    public function testCalculoAmortizacionTasaCero(): void {
        $monto = 24000.00;
        $tasaAnual = 0.00;
        $plazo = 6;
        $fechaInicio = '2026-05-01';

        $tabla = $this->modelo->calcularTablaAmortizacion($monto, $tasaAnual, $plazo, $fechaInicio);

        $this->assertCount(6, $tabla);
        foreach ($tabla as $c) {
            $this->assertEquals(0.00, (float)$c['interes']);
            $this->assertEquals(4000.00, (float)$c['cuota_total']);
            $this->assertEquals(4000.00, (float)$c['amortizacion_capital']);
        }

        $this->assertEquals(0.00, (float)end($tabla)['saldo_pendiente']);
    }

    /**
     * Valida que calcularFechaVencimiento maneje correctamente saltos de mes y días límite (ej. 31 enero).
     */
    public function testCalculoFechaVencimientoMesesVariables(): void {
        // Desembolso el 31 de enero -> Febrero debe caer en el último día de febrero
        $f1 = PrestamosModelo::calcularFechaVencimiento('2026-01-31', 1);
        $this->assertEquals('2026-02-28', $f1);

        // Marzo debe volver al día 31
        $f2 = PrestamosModelo::calcularFechaVencimiento('2026-01-31', 2);
        $this->assertEquals('2026-03-31', $f2);

        // Abril debe ajustar a 30 días
        $f3 = PrestamosModelo::calcularFechaVencimiento('2026-01-31', 3);
        $this->assertEquals('2026-04-30', $f3);

        // Salto a través del fin de año: 15 de noviembre + 2 meses -> 15 de enero del año siguiente
        $fAno = PrestamosModelo::calcularFechaVencimiento('2026-11-15', 2);
        $this->assertEquals('2027-01-15', $fAno);
    }

    /**
     * Valida el registro de préstamo con incremento de saldo bancario y asiento contable de desembolso.
     */
    public function testRegistroPrestamoGeneraAsientoDesembolsoYMovimientoBancario(): void {
        $montoPrestamo = 80000.00;
        $tasaAnual = 14.50;
        $plazo = 24;
        $codigo = 'PB-TEST-' . bin2hex(random_bytes(3));
        $fechaDesembolso = '2026-10-15';

        // Obtener saldo previo de la cuenta bancaria
        $stmtBco = $this->db->prepare("SELECT saldo_actual FROM bancos_cuentas WHERE id = :id");
        $stmtBco->execute(['id' => $this->bancoPruebaId]);
        $saldoPrevio = (float)$stmtBco->fetchColumn();

        // Registrar préstamo
        $res = $this->modelo->registrarPrestamo([
            'codigo' => $codigo,
            'institucion_financiera' => 'BANCO BAC CREDOMATIC',
            'monto_prestamo' => $montoPrestamo,
            'tasa_interes_anual' => $tasaAnual,
            'plazo_meses' => $plazo,
            'fecha_desembolso' => $fechaDesembolso,
            'cuenta_banco_id' => $this->bancoPruebaId
        ], 1);

        $this->assertTrue($res['ok']);
        $prestamoId = $res['prestamo_id'];
        $idAsiento = $res['id_asiento'];
        $this->assertGreaterThan(0, $prestamoId);
        $this->assertNotNull($idAsiento, "Debe haberse generado un asiento contable de desembolso.");

        // 1. Verificar registro del préstamo en base de datos
        $prestamoDb = $this->modelo->obtenerPrestamoPorId($prestamoId);
        $this->assertNotNull($prestamoDb);
        $this->assertEquals($codigo, $prestamoDb['codigo']);
        $this->assertEquals('activo', $prestamoDb['estado']);
        $this->assertEquals($montoPrestamo, (float)$prestamoDb['monto_prestamo']);
        $this->assertEquals($montoPrestamo, (float)$prestamoDb['saldo_capital']);
        $this->assertEquals($plazo, (int)$prestamoDb['total_cuotas']);

        // 2. Verificar incremento en saldo bancario
        $stmtBco->execute(['id' => $this->bancoPruebaId]);
        $saldoNuevo = (float)$stmtBco->fetchColumn();
        $this->assertEqualsWithDelta(
            $saldoPrevio + $montoPrestamo,
            $saldoNuevo,
            0.01,
            "El saldo de la cuenta bancaria debe incrementarse por el monto del préstamo."
        );

        // 3. Verificar movimiento bancario en bancos_transacciones
        $stmtTx = $this->db->prepare("
            SELECT * FROM bancos_transacciones 
            WHERE id_banco_cuenta = :bid AND numero_documento = :cod 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtTx->execute(['bid' => $this->bancoPruebaId, 'cod' => $codigo]);
        $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($tx, "Debe existir la transacción bancaria de depósito.");
        $this->assertEquals('DEPOSITO', $tx['tipo_transaccion']);
        $this->assertEquals($montoPrestamo, (float)$tx['monto']);

        // 4. Verificar asiento contable en partida doble (D: Banco / H: Pasivo Préstamo)
        $stmtPartida = $this->db->prepare("
            SELECT p.num_partida, p.concepto, p.origen,
                   SUM(pd.debe) AS total_debe,
                   SUM(pd.haber) AS total_haber,
                   COUNT(pd.id) AS total_lineas
            FROM partidas_diario p
            INNER JOIN partidas_diario_detalles pd ON p.id = pd.id_partida
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtPartida->execute(['id' => $idAsiento]);
        $partida = $stmtPartida->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($partida, "El asiento contable de desembolso debe existir.");
        $this->assertEquals('PRESTAMO_BANCARIO', $partida['origen']);
        $this->assertGreaterThanOrEqual(2, (int)$partida['total_lineas']);

        $totalDebe = (float)$partida['total_debe'];
        $totalHaber = (float)$partida['total_haber'];

        $this->assertEqualsWithDelta($montoPrestamo, $totalDebe, 0.01);
        $this->assertEqualsWithDelta($montoPrestamo, $totalHaber, 0.01);

        // Partida doble estrictamente balanceada
        $diferencia = abs($totalDebe - $totalHaber);
        $this->assertLessThan(
            0.001,
            $diferencia,
            "El asiento de desembolso debe estar balanceado: Debe={$totalDebe}, Haber={$totalHaber}"
        );
    }

    /**
     * Valida el pago de una cuota: afectación de capital, débito bancario y asiento en partida doble:
     * D: Pasivo Préstamo (amortización) + D: Gastos Financieros (interés) / H: Banco (cuota total).
     */
    public function testPagoCuotaDisminuyeCapitalGeneraAsientoBalanceadoYAfectaBanco(): void {
        $montoPrestamo = 30000.00;
        $tasaAnual = 18.00; // 1.5% mensual
        $plazo = 6;
        $codigo = 'PB-PAGO-' . bin2hex(random_bytes(3));

        $resReg = $this->modelo->registrarPrestamo([
            'codigo' => $codigo,
            'institucion_financiera' => 'BANPRO',
            'monto_prestamo' => $montoPrestamo,
            'tasa_interes_anual' => $tasaAnual,
            'plazo_meses' => $plazo,
            'fecha_desembolso' => '2026-10-01',
            'cuenta_banco_id' => $this->bancoPruebaId
        ], 1);

        $prestamoId = $resReg['prestamo_id'];
        $cuotas = $this->modelo->obtenerCuotasPorPrestamo($prestamoId);
        $cuota1 = $cuotas[0];

        $saldoCapitalAntes = $montoPrestamo;

        // Consultar saldo de banco antes de pagar
        $stmtBco = $this->db->prepare("SELECT saldo_actual FROM bancos_cuentas WHERE id = :id");
        $stmtBco->execute(['id' => $this->bancoPruebaId]);
        $saldoBancoAntes = (float)$stmtBco->fetchColumn();

        // Ejecutar pago de la Cuota 1
        $fechaPago = '2026-11-01';
        $resPago = $this->modelo->pagarCuota($cuota1['id'], $fechaPago, $this->bancoPruebaId, 1);

        $this->assertTrue($resPago['ok']);
        $this->assertEquals($cuota1['id'], $resPago['cuota_id']);
        $idAsientoPago = $resPago['id_asiento'];
        $this->assertNotNull($idAsientoPago, "Debe generarse asiento contable para el pago de la cuota.");

        // 1. Validar que la cuota quedó en estado 'pagada'
        $cuota1Db = $this->modelo->obtenerCuotaPorId($cuota1['id']);
        $this->assertEquals('pagada', $cuota1Db['estado']);
        $this->assertEquals($fechaPago, $cuota1Db['fecha_pago']);
        $this->assertEquals($idAsientoPago, $cuota1Db['id_asiento_pago']);

        // 2. Validar disminución del saldo_capital del préstamo
        $amortizacion = (float)$cuota1['amortizacion_capital'];
        $saldoCapitalEsperado = round($saldoCapitalAntes - $amortizacion, 2);

        $prestamoDb = $this->modelo->obtenerPrestamoPorId($prestamoId);
        $this->assertEqualsWithDelta(
            $saldoCapitalEsperado,
            (float)$prestamoDb['saldo_capital'],
            0.01,
            "El saldo de capital debe disminuir exactamente en la amortización de la cuota."
        );

        // 3. Validar débito en cuenta bancaria
        $stmtBco->execute(['id' => $this->bancoPruebaId]);
        $saldoBancoDespues = (float)$stmtBco->fetchColumn();
        $this->assertEqualsWithDelta(
            $saldoBancoAntes - (float)$cuota1['cuota_total'],
            $saldoBancoDespues,
            0.01,
            "La cuenta bancaria debe debitar la cuota total pagada."
        );

        // 4. Validar asiento contable balanceado en estricta partida doble
        // D: Pasivo Préstamo (amortización)
        // D: Gastos Financieros Intereses (interés)
        // H: Banco (cuota total)
        $stmtPartida = $this->db->prepare("
            SELECT p.num_partida, p.concepto, p.origen,
                   SUM(pd.debe) AS total_debe,
                   SUM(pd.haber) AS total_haber,
                   COUNT(pd.id) AS total_lineas
            FROM partidas_diario p
            INNER JOIN partidas_diario_detalles pd ON p.id = pd.id_partida
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtPartida->execute(['id' => $idAsientoPago]);
        $partidaPago = $stmtPartida->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($partidaPago, "El asiento contable de pago de cuota debe existir.");
        $this->assertEquals('PAGO_CUOTA_PRESTAMO', $partidaPago['origen']);
        $this->assertGreaterThanOrEqual(2, (int)$partidaPago['total_lineas']);

        $totalDebe = (float)$partidaPago['total_debe'];
        $totalHaber = (float)$partidaPago['total_haber'];

        // Suma Debe == Suma Haber == cuota_total
        $this->assertEqualsWithDelta((float)$cuota1['cuota_total'], $totalDebe, 0.01);
        $this->assertEqualsWithDelta((float)$cuota1['cuota_total'], $totalHaber, 0.01);

        $dif = abs($totalDebe - $totalHaber);
        $this->assertLessThan(
            0.001,
            $dif,
            "El asiento de pago de cuota debe cuadrar exactamente: Debe={$totalDebe}, Haber={$totalHaber}"
        );
    }

    /**
     * Valida que al pagar todas las cuotas de un préstamo, el estado cambie a 'cancelado' y el saldo sea 0.00.
     */
    public function testPagoTotalCuotasCancelaPrestamo(): void {
        $montoPrestamo = 15000.00;
        $tasaAnual = 10.00;
        $plazo = 2; // 2 cuotas para prueba rápida
        $codigo = 'PB-CANCEL-' . bin2hex(random_bytes(3));

        $resReg = $this->modelo->registrarPrestamo([
            'codigo' => $codigo,
            'institucion_financiera' => 'LAFISE BANCENTRO',
            'monto_prestamo' => $montoPrestamo,
            'tasa_interes_anual' => $tasaAnual,
            'plazo_meses' => $plazo,
            'fecha_desembolso' => '2026-09-01',
            'cuenta_banco_id' => $this->bancoPruebaId
        ], 1);

        $prestamoId = $resReg['prestamo_id'];
        $cuotas = $this->modelo->obtenerCuotasPorPrestamo($prestamoId);
        $this->assertCount(2, $cuotas);

        // Pagar Cuota 1
        $this->modelo->pagarCuota($cuotas[0]['id'], '2026-10-01', $this->bancoPruebaId, 1);
        $prestamoIntermedio = $this->modelo->obtenerPrestamoPorId($prestamoId);
        $this->assertEquals('activo', $prestamoIntermedio['estado']);

        // Pagar Cuota 2 (Última)
        $resUltima = $this->modelo->pagarCuota($cuotas[1]['id'], '2026-11-01', $this->bancoPruebaId, 1);
        $this->assertEquals('cancelado', $resUltima['estado_prestamo']);
        $this->assertEquals(0.00, (float)$resUltima['saldo_capital']);

        // Verificar en BD
        $prestamoFinal = $this->modelo->obtenerPrestamoPorId($prestamoId);
        $this->assertEquals('cancelado', $prestamoFinal['estado']);
        $this->assertEquals(0.00, (float)$prestamoFinal['saldo_capital']);
        $this->assertEquals(2, (int)$prestamoFinal['cuotas_pagadas']);
    }

    /**
     * Valida listado de préstamos y resumen de dashboard.
     */
    public function testListarPrestamosYResumenDashboard(): void {
        $resumen = $this->modelo->obtenerResumenDashboard();
        $this->assertIsArray($resumen);
        $this->assertArrayHasKey('monto_total_prestado', $resumen);
        $this->assertArrayHasKey('saldo_capital_vigente', $resumen);
        $this->assertArrayHasKey('activos', $resumen);

        $lista = $this->modelo->listarPrestamos();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
    }

    /**
     * Valida que pagar una cuota ya pagada sea rechazado con excepción.
     */
    public function testRechazoDoblePagoCuota(): void {
        $montoPrestamo = 10000.00;
        $codigo = 'PB-DUP-' . bin2hex(random_bytes(3));

        $res = $this->modelo->registrarPrestamo([
            'codigo' => $codigo,
            'institucion_financiera' => 'BANPRO',
            'monto_prestamo' => $montoPrestamo,
            'tasa_interes_anual' => 12.00,
            'plazo_meses' => 3,
            'fecha_desembolso' => '2026-10-01',
            'cuenta_banco_id' => $this->bancoPruebaId
        ], 1);

        $cuotas = $this->modelo->obtenerCuotasPorPrestamo($res['prestamo_id']);
        $cuota1Id = $cuotas[0]['id'];

        // Primer pago exitoso
        $this->modelo->pagarCuota($cuota1Id, '2026-11-01', $this->bancoPruebaId, 1);

        // Segundo pago debe lanzar excepción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("ya fue pagada");
        $this->modelo->pagarCuota($cuota1Id, '2026-11-01', $this->bancoPruebaId, 1);
    }
}
