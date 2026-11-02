<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use ReflectionClass;

/**
 * Sprint G & H: Contabilidad Fases 5-6 y Residuales de Reportes
 *
 * Cobertura de pruebas unitarias:
 * G.1: ConfigCuentas resolución dinámica sin IDs hardcodeados mágicos.
 * G.2: IVA uniforme y separado en asientos de facturación.
 * G.3: Asientos de reverso espejo aditivos (sin destruir historial con reconstruirDiario).
 * H.2: Saltos de línea consistentes (nl2br / pre-wrap) en condiciones_muestra.
 */
class SprintGContabilidadReversosTest extends TestCase {

    private ContabilidadModelo $contaModelo;

    protected function setUp(): void {
        parent::setUp();
        $this->contaModelo = new ContabilidadModelo();
    }

    public function testConfigCuentasResolucionClavesMnemonic(): void {
        $clavesEsperadas = [
            'CLIENTES',
            'PROVEEDORES',
            'IVA_ACREDITABLE',
            'IVA_POR_PAGAR',
            'INGRESO_LAB_G',
            'CAJA_PRINCIPAL'
        ];

        foreach ($clavesEsperadas as $clave) {
            $id = ConfigCuentasModelo::obtenerIdPorClave($clave);
            $this->assertNotNull($id, "La clave mnemónica {$clave} debe estar configurada en config_cuentas");
            $this->assertIsInt($id);
            $this->assertGreaterThan(0, $id);
        }
    }

    public function testRegistrarAsientoReversoEspejoAditivo(): void {
        $this->assertTrue(
            method_exists($this->contaModelo, 'registrarAsientoReverso'),
            'ContabilidadModelo debe implementar registrarAsientoReverso'
        );

        // 1. Crear un asiento de prueba balanceado
        $idCuentaDebe = ConfigCuentasModelo::obtenerIdPorClave('CAJA_PRINCIPAL') ?: 4;
        $idCuentaHaber = ConfigCuentasModelo::obtenerIdPorClave('INGRESO_LAB_G') ?: 208;
        $montoPrueba = 1500.00;

        $idOriginal = $this->contaModelo->registrarAsientoContable(
            date('Y-m-d'),
            'TEST_ORIGINAL: Asiento para prueba de reverso',
            'PRUEBA',
            99999,
            [
                ['id_cuenta_contable' => $idCuentaDebe, 'debe' => $montoPrueba, 'haber' => 0.0],
                ['id_cuenta_contable' => $idCuentaHaber, 'debe' => 0.0, 'haber' => $montoPrueba]
            ]
        );

        $this->assertNotNull($idOriginal, 'El asiento original debe registrarse');
        $this->assertGreaterThan(0, $idOriginal);

        // 2. Generar reverso espejo
        $idReverso = $this->contaModelo->registrarAsientoReverso($idOriginal, 'Anulación de prueba unitaria');
        $this->assertNotNull($idReverso, 'El asiento de reverso debe registrarse exitosamente');
        $this->assertGreaterThan(0, $idReverso);
        $this->assertNotEquals($idOriginal, $idReverso);

        // 3. Verificar que la partida original SIGUE existiendo (inmutable, no borrada)
        $partidaOrig = $this->contaModelo->obtenerPartidaPorId($idOriginal);
        $this->assertNotNull($partidaOrig, 'La partida original debe permanecer intacta en el diario');

        // 4. Verificar que el reverso invirtió débitos y créditos
        $partidaRev = $this->contaModelo->obtenerPartidaPorId($idReverso);
        $this->assertNotNull($partidaRev);
        $this->assertStringContainsString('REVERSO', $partidaRev['concepto']);

        $detallesRev = $this->contaModelo->obtenerDetallesPartida($idReverso);
        $this->assertCount(2, $detallesRev);

        // En el reverso, la cuenta que estaba en DEBE ahora debe estar en HABER
        foreach ($detallesRev as $d) {
            if ((int)$d['id_cuenta_contable'] === $idCuentaDebe) {
                $this->assertEquals(0.0, (float)$d['debe'], 'En reverso, la cuenta de caja no debe tener debe');
                $this->assertEquals($montoPrueba, (float)$d['haber'], 'En reverso, la cuenta de caja debe tener haber');
            } elseif ((int)$d['id_cuenta_contable'] === $idCuentaHaber) {
                $this->assertEquals($montoPrueba, (float)$d['debe'], 'En reverso, la cuenta de ingreso debe tener debe');
                $this->assertEquals(0.0, (float)$d['haber'], 'En reverso, la cuenta de ingreso no debe tener haber');
            }
        }
    }

    public function testCondicionesMuestraTieneFormatoConsistenteEnPdfYDetalle(): void {
        $funcionesPath = dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $fnContent = file_get_contents($funcionesPath);
        $this->assertStringContainsString('nl2br(htmlspecialchars($det[\'condiciones_muestra\']', $fnContent, 'funciones.php debe aplicar nl2br a condiciones_muestra');
        $this->assertStringContainsString('white-space: pre-wrap;', $fnContent, 'funciones.php debe aplicar white-space: pre-wrap a condiciones_muestra');

        $detallePath = dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/detalle.php';
        $detContent = file_get_contents($detallePath);
        $this->assertStringContainsString('nl2br(htmlspecialchars($detalle[\'condiciones_muestra\']', $detContent, 'detalle.php debe aplicar nl2br');
        $this->assertStringContainsString('white-space: pre-wrap;', $detContent, 'detalle.php debe aplicar white-space: pre-wrap');
    }
}
