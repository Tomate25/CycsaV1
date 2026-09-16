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
}
