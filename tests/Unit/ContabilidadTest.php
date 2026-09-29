<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use ReflectionClass;

class ContabilidadTest extends TestCase {
    private ?ContabilidadModelo $modelo = null;
    private bool $dbAvailable = false;
    private array $partidasCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        try {
            $this->modelo = new ContabilidadModelo();
            $this->dbAvailable = true;
        } catch (\Throwable $e) {
            $reflector = new ReflectionClass(ContabilidadModelo::class);
            $this->modelo = $reflector->newInstanceWithoutConstructor();
            $this->dbAvailable = false;
        }
    }

    protected function tearDown(): void {
        // Limpieza de partidas de prueba creadas durante los tests
        if ($this->dbAvailable && !empty($this->partidasCreadas)) {
            foreach ($this->partidasCreadas as $id) {
                try {
                    $this->modelo->eliminarAsiento($id);
                } catch (\Throwable $e) {
                    // Ignorar errores de limpieza
                }
            }
        }
        parent::tearDown();
    }

    /**
     * Tarea 2.1: Validar que rechace asientos descuadrados (abs(Debe - Haber) > 0.01) retornando null.
     */
    public function testRechazaAsientoDescuadrado(): void {
        $fecha = date('Y-m-d');
        $concepto = 'Asiento descuadrado de prueba';

        // Descuadre evidente: Debe 1000.00 vs Haber 950.00 (diferencia 50.00 > 0.01)
        $lineasDescuadradas = [
            ['id_cuenta_contable' => 1, 'debe' => 1000.00, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => 950.00],
        ];

        $resultado = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasDescuadradas);
        $this->assertNull($resultado, "Un asiento con diferencia de $50.00 debe ser rechazado retornando null.");

        // Descuadre sutil: Debe 100.00 vs Haber 100.02 (diferencia 0.02 > 0.01)
        $lineasDescuadreCentavos = [
            ['id_cuenta_contable' => 1, 'debe' => 100.00, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => 100.02],
        ];

        $resultadoCentavos = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasDescuadreCentavos);
        $this->assertNull($resultadoCentavos, "Un asiento con diferencia de 0.02 debe ser rechazado retornando null.");
    }

    /**
     * Tarea 2.2: Validar que rechace asientos con menos de 2 líneas contables válidas.
     */
    public function testRechazaAsientoConMenosDeDosLineas(): void {
        $fecha = date('Y-m-d');
        $concepto = 'Asiento líneas insuficientes';

        // 1. Array vacío
        $resultadoVacio = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, []);
        $this->assertNull($resultadoVacio, "Un array vacío de líneas debe ser rechazado retornando null.");

        // 2. Solo una línea
        $unaLinea = [
            ['id_cuenta_contable' => 1, 'debe' => 500.00, 'haber' => 0.0]
        ];
        $resultadoUna = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $unaLinea);
        $this->assertNull($resultadoUna, "Un asiento con solo una línea debe ser rechazado retornando null.");

        // 3. Dos líneas pero una con id_cuenta_contable = 0 (inválida)
        $lineasCuentaInvalida = [
            ['id_cuenta_contable' => 1, 'debe' => 500.00, 'haber' => 0.0],
            ['id_cuenta_contable' => 0, 'debe' => 0.0, 'haber' => 500.00],
        ];
        $resultadoCuentaInvalida = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasCuentaInvalida);
        $this->assertNull($resultadoCuentaInvalida, "Un asiento con una sola línea con cuenta válida debe ser rechazado.");

        // 4. Dos líneas pero una sin montos (debe = 0 y haber = 0)
        $lineasMontoCero = [
            ['id_cuenta_contable' => 1, 'debe' => 500.00, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => 0.0],
        ];
        $resultadoMontoCero = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasMontoCero);
        $this->assertNull($resultadoMontoCero, "Un asiento donde una línea tiene montos 0 debe ser rechazado.");
    }

    /**
     * Tarea 2.3: Validar que rechace débitos o créditos menores o iguales a cero en el total (totalDebe <= 0.0001).
     */
    public function testRechazaAsientoConMontosCeroONegativos(): void {
        $fecha = date('Y-m-d');
        $concepto = 'Asiento montos cero o negativos';

        // Total debe = 0.0
        $lineasCero = [
            ['id_cuenta_contable' => 1, 'debe' => 0.0, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => 0.0],
        ];
        $resultadoCero = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasCero);
        $this->assertNull($resultadoCero, "Un asiento con total debe = 0 debe ser rechazado retornando null.");

        // Débitos negativos
        $lineasNegativas = [
            ['id_cuenta_contable' => 1, 'debe' => -100.00, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => -100.00],
        ];
        $resultadoNegativo = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasNegativas);
        $this->assertNull($resultadoNegativo, "Un asiento con total debe negativo debe ser rechazado retornando null.");

        // Débito microscópico menor o igual al umbral 0.0001
        $lineasMicro = [
            ['id_cuenta_contable' => 1, 'debe' => 0.00005, 'haber' => 0.0],
            ['id_cuenta_contable' => 2, 'debe' => 0.0, 'haber' => 0.00005],
        ];
        $resultadoMicro = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineasMicro);
        $this->assertNull($resultadoMicro, "Un asiento con total debe <= 0.0001 debe ser rechazado retornando null.");
    }

    /**
     * Tarea 2.4: Validar el balance correcto de partida doble en un asiento equilibrado.
     */
    public function testProcesaAsientoBalanceado(): void {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Base de datos no disponible para registrar asiento contable balanceado.');
        }

        // Obtener dos cuentas de detalle activas existentes
        $cuentas = $this->modelo->obtenerCuentasDetalle();
        if (count($cuentas) < 2) {
            $this->markTestSkipped('Se requieren al menos 2 cuentas de detalle activas en la BD para probar el asiento.');
        }

        $idCuenta1 = (int)$cuentas[0]['id'];
        $idCuenta2 = (int)$cuentas[1]['id'];

        $fecha = date('Y-m-d');
        $concepto = 'TEST UNITARIO AUTOMATIZADO ASIENTO BALANCEADO';
        $monto = 275.50;

        $lineas = [
            ['id_cuenta_contable' => $idCuenta1, 'debe' => $monto, 'haber' => 0.0],
            ['id_cuenta_contable' => $idCuenta2, 'debe' => 0.0, 'haber' => $monto],
        ];

        $partidaId = $this->modelo->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineas);

        $this->assertNotNull($partidaId, "El registro de un asiento equilibrado debe retornar un ID entero no nulo.");
        $this->assertGreaterThan(0, $partidaId, "El ID de la partida generada debe ser mayor a 0.");

        $this->partidasCreadas[] = $partidaId;

        // Verificar los detalles registrados
        $detalles = $this->modelo->obtenerAsientoDetalles($partidaId);
        $this->assertCount(2, $detalles, "La partida debe tener exactamente 2 líneas de detalle.");

        $sumaDebe = 0.0;
        $sumaHaber = 0.0;
        foreach ($detalles as $d) {
            $sumaDebe += (float)$d['debe'];
            $sumaHaber += (float)$d['haber'];
        }

        $this->assertEqualsWithDelta($monto, $sumaDebe, 0.001, "La suma del Debe en detalles debe coincidir con el monto registrado.");
        $this->assertEqualsWithDelta($monto, $sumaHaber, 0.001, "La suma del Haber en detalles debe coincidir con el monto registrado.");
        $this->assertEqualsWithDelta($sumaDebe, $sumaHaber, 0.001, "El balance de partida doble Debe == Haber debe ser exacto.");

        // Limpieza inmediata del asiento de prueba
        $eliminado = $this->modelo->eliminarAsiento($partidaId);
        $this->assertTrue($eliminado, "El asiento de prueba debe eliminarse correctamente.");
        $this->partidasCreadas = array_diff($this->partidasCreadas, [$partidaId]);
    }

    /**
     * Prueba adicional: Tolerancia admisible dentro del umbral de 0.01.
     */
    public function testToleranciaDescuadreMinimo(): void {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Base de datos no disponible para probar tolerancia de redondeo.');
        }

        $cuentas = $this->modelo->obtenerCuentasDetalle();
        if (count($cuentas) < 2) {
            $this->markTestSkipped('Se requieren al menos 2 cuentas de detalle.');
        }

        $idCuenta1 = (int)$cuentas[0]['id'];
        $idCuenta2 = (int)$cuentas[1]['id'];

        // Diferencia de 0.005 (menor a 0.01) debe ser tolerada por redondeo
        $lineas = [
            ['id_cuenta_contable' => $idCuenta1, 'debe' => 100.005, 'haber' => 0.0],
            ['id_cuenta_contable' => $idCuenta2, 'debe' => 0.0, 'haber' => 100.000],
        ];

        $partidaId = $this->modelo->registrarAsientoContable(date('Y-m-d'), 'TEST TOLERANCIA REDONDEO', 'MANUAL', null, $lineas);
        $this->assertNotNull($partidaId, "Diferencia <= 0.01 debe permitirse por tolerancia de redondeo.");

        if ($partidaId) {
            $this->modelo->eliminarAsiento($partidaId);
        }
    }

    /**
     * TAREA-021: Validar cálculo de antigüedad para cuenta corriente (sin vencer).
     */
    public function testProcesarAntiguedadFilaCorriente(): void {
        $fechaEmision = date('Y-m-d', strtotime('-5 days'));
        $fechaVencimiento = date('Y-m-d', strtotime('+25 days'));

        $fila = [
            'id' => 101,
            'factura_numero' => 'FAC-TEST-01',
            'monto' => 1500.00,
            'saldo' => 1500.00,
            'estado' => 'Pendiente',
            'fecha_emision' => $fechaEmision,
            'fecha_vencimiento' => $fechaVencimiento,
        ];

        $procesada = $this->modelo->procesarAntiguedadFila($fila);

        $this->assertEquals('corriente', $procesada['antiguedad_cat'], "La cuenta vigente debe clasificarse como corriente.");
        $this->assertEquals(0, $procesada['dias_mora'], "Una cuenta corriente debe tener 0 días de mora.");
        $this->assertGreaterThan(0, $procesada['dias_faltantes'], "Debe tener días faltantes para su vencimiento.");
        $this->assertEquals('badge-ant-corriente', $procesada['antiguedad_badge_class']);
        $this->assertTrue($procesada['tiene_vencimiento_explicito']);
    }

    /**
     * TAREA-021: Validar cálculo de cubetas de mora (1-30, 31-60 y +60 días).
     */
    public function testProcesarAntiguedadFilaMora(): void {
        // Caso 1: Mora 1-30 (10 días de vencida)
        $fila1 = [
            'id' => 102,
            'factura_numero' => 'FAC-TEST-02',
            'monto' => 2000.00,
            'saldo' => 2000.00,
            'estado' => 'Pendiente',
            'fecha_emision' => date('Y-m-d', strtotime('-40 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('-10 days')),
        ];
        $res1 = $this->modelo->procesarAntiguedadFila($fila1);
        $this->assertEquals('1_30', $res1['antiguedad_cat']);
        $this->assertEquals(10, $res1['dias_mora']);
        $this->assertEquals('badge-ant-1-30', $res1['antiguedad_badge_class']);

        // Caso 2: Mora 31-60 (45 días de vencida)
        $fila2 = [
            'id' => 103,
            'factura_numero' => 'FAC-TEST-03',
            'monto' => 3500.00,
            'saldo' => 3500.00,
            'estado' => 'Pendiente',
            'fecha_emision' => date('Y-m-d', strtotime('-75 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('-45 days')),
        ];
        $res2 = $this->modelo->procesarAntiguedadFila($fila2);
        $this->assertEquals('31_60', $res2['antiguedad_cat']);
        $this->assertEquals(45, $res2['dias_mora']);
        $this->assertEquals('badge-ant-31-60', $res2['antiguedad_badge_class']);

        // Caso 3: Mora +60 (90 días de vencida sin fecha explícita, emision hace 120 días -> venció hace 90 días)
        $fila3 = [
            'id' => 104,
            'factura_numero' => 'FAC-TEST-04',
            'monto' => 5000.00,
            'saldo' => 5000.00,
            'estado' => 'Pendiente',
            'fecha_emision' => date('Y-m-d', strtotime('-120 days')),
            'fecha_vencimiento' => null, // Sin vencimiento explícito: 120 - 30 = 90 días de mora
        ];
        $res3 = $this->modelo->procesarAntiguedadFila($fila3);
        $this->assertEquals('mas_60', $res3['antiguedad_cat']);
        $this->assertFalse($res3['tiene_vencimiento_explicito']);
        $this->assertEquals(90, $res3['dias_mora']);
        $this->assertEquals('badge-ant-mas-60', $res3['antiguedad_badge_class']);
    }

    /**
     * TAREA-021: Validar que cuentas saldadas o con saldo <= 0 se clasifiquen como pagado sin mora.
     */
    public function testProcesarAntiguedadFilaPagada(): void {
        $filaPagada = [
            'id' => 105,
            'factura_numero' => 'FAC-TEST-05',
            'monto' => 1000.00,
            'saldo' => 0.00,
            'estado' => 'Pagado',
            'fecha_emision' => date('Y-m-d', strtotime('-100 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('-70 days')),
        ];

        $res = $this->modelo->procesarAntiguedadFila($filaPagada);
        $this->assertEquals('pagado', $res['antiguedad_cat'], "Una factura con saldo cero debe ser tratada como pagada.");
        $this->assertEquals(0, $res['dias_mora'], "Una factura pagada no genera días de mora.");
        $this->assertEquals('badge-ant-pagado', $res['antiguedad_badge_class']);
    }

    /**
     * TAREA-021: Validar el cálculo consolidado del resumen financiero de antigüedad y porcentajes de morosidad.
     */
    public function testResumenAntiguedadCxcCalculos(): void {
        $cxcList = [
            // Corriente: saldo 1000
            [
                'id' => 1, 'monto' => 1000.0, 'saldo' => 1000.0, 'estado' => 'Pendiente',
                'fecha_emision' => date('Y-m-d'), 'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days'))
            ],
            // Mora 1-30: saldo 2000
            [
                'id' => 2, 'monto' => 2000.0, 'saldo' => 2000.0, 'estado' => 'Pendiente',
                'fecha_emision' => date('Y-m-d', strtotime('-40 days')), 'fecha_vencimiento' => date('Y-m-d', strtotime('-10 days'))
            ],
            // Mora 31-60: saldo 3000
            [
                'id' => 3, 'monto' => 3000.0, 'saldo' => 3000.0, 'estado' => 'Pendiente',
                'fecha_emision' => date('Y-m-d', strtotime('-75 days')), 'fecha_vencimiento' => date('Y-m-d', strtotime('-45 days'))
            ],
            // Mora +60: saldo 4000
            [
                'id' => 4, 'monto' => 5000.0, 'saldo' => 4000.0, 'estado' => 'Parcial',
                'fecha_emision' => date('Y-m-d', strtotime('-100 days')), 'fecha_vencimiento' => date('Y-m-d', strtotime('-70 days'))
            ],
            // Pagado: saldo 0 (monto 1500)
            [
                'id' => 5, 'monto' => 1500.0, 'saldo' => 0.0, 'estado' => 'Pagado',
                'fecha_emision' => date('Y-m-d', strtotime('-50 days')), 'fecha_vencimiento' => date('Y-m-d', strtotime('-20 days'))
            ],
        ];

        $resumen = $this->modelo->obtenerResumenAntiguedadCxc($cxcList);

        $this->assertEquals(1000.0, $resumen['total_corriente']);
        $this->assertEquals(1, $resumen['cant_corriente']);

        $this->assertEquals(2000.0, $resumen['total_mora_1_30']);
        $this->assertEquals(1, $resumen['cant_1_30']);

        $this->assertEquals(3000.0, $resumen['total_mora_31_60']);
        $this->assertEquals(1, $resumen['cant_31_60']);

        $this->assertEquals(4000.0, $resumen['total_mora_mas_60']);
        $this->assertEquals(1, $resumen['cant_mas_60']);

        // Total vencido = 2000 + 3000 + 4000 = 9000
        $this->assertEquals(9000.0, $resumen['total_vencido']);
        $this->assertEquals(3, $resumen['cant_vencido']);

        // Saldo pendiente total = 1000 + 2000 + 3000 + 4000 = 10000
        $this->assertEquals(10000.0, $resumen['total_saldo_pendiente']);

        // Porcentaje vencido = 9000 / 10000 * 100 = 90%
        $this->assertEquals(90.0, $resumen['porcentaje_vencido']);

        // Total cobrado = 1000 (de abono parcial) + 1500 (de pagado) = 2500
        $this->assertEquals(2500.0, $resumen['total_cobrado']);

        // Total registrado = 1000 + 2000 + 3000 + 5000 + 1500 = 12500
        $this->assertEquals(12500.0, $resumen['total_registrado']);
    }
}

