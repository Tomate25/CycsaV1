<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Sprint G.4 & G.1b: Erradicación de hardcodes contables y Hooks automáticos G.4
 *
 * Cobertura de pruebas unitarias:
 * 1. Verificación de ausencia de IDs fijos mágicos en ContabilidadModelo (206, 121, 221).
 * 2. Asiento contable para Anticipo a Proveedor (ANTICIPO_PROV).
 * 3. Asiento contable para Recepción de Compra inventariable (RECEPCION_COMPRA).
 * 4. Asiento contable para Consumo de Insumos por Ensayo (CONSUMO_INVENTARIO).
 */
class SprintG4ContabilidadHooksTest extends TestCase {

    private PDO $db;
    private ContabilidadModelo $contaModelo;
    private array $idsLimpieza = [
        'partidas' => []
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->contaModelo = new ContabilidadModelo();
    }

    protected function tearDown(): void {
        foreach ($this->idsLimpieza['partidas'] as $idPartida) {
            $this->db->exec("DELETE FROM partidas_diario_detalles WHERE id_partida = {$idPartida}");
            $this->db->exec("DELETE FROM partidas_diario WHERE id = {$idPartida}");
        }
        parent::tearDown();
    }

    public function testContabilidadModeloMetodosSinHardcodesMagicos(): void {
        $archivo = dirname(__DIR__, 2) . '/app/Modulos/Contabilidad/Modelos/ContabilidadModelo.php';
        $contenido = file_get_contents($archivo);

        // En guardarCxc, la cuenta 206 no debe estar hardcodeada
        $this->assertStringNotContainsString(
            "['id_cuenta_contable' => 206, 'debe' => 0.0, 'haber' => \$datos['monto']]",
            $contenido,
            'guardarCxc debe resolver la cuenta de ingresos dinámicamente, no hardcoded a 206'
        );

        // En registrarPagoCxp, la cuenta 121 no debe estar fija sin resolución
        $this->assertStringNotContainsString(
            "['id_cuenta_contable' => 121, 'debe' => \$monto, 'haber' => 0.0]",
            $contenido,
            'registrarPagoCxp debe resolver la cuenta de proveedores dinámicamente'
        );

        // En reconstruirDiario, las cuentas default no deben ser hardcodes fijos sin config
        $this->assertStringNotContainsString(
            "\$incomeDefaultAcc = 206;",
            $contenido,
            'reconstruirDiario debe resolver la cuenta de ingresos con ConfigCuentas'
        );
    }

    public function testRegistrarAnticipoProveedorGeneraAsientoBalanceado(): void {
        $monto = 2500.00;
        $idProveedor = 99991;
        $idBancoCuenta = 1;

        $idPartida = $this->contaModelo->registrarAnticipoProveedor(
            $idProveedor,
            $monto,
            $idBancoCuenta,
            'REF-ANTICIPO-TEST',
            date('Y-m-d')
        );

        $this->assertGreaterThan(0, $idPartida, 'Debe retornar el ID de la partida creada');
        $this->idsLimpieza['partidas'][] = $idPartida;

        $partida = $this->contaModelo->obtenerPartidaPorId($idPartida);
        $this->assertNotNull($partida);
        $this->assertEquals('ANTICIPO_PROV', $partida['origen']);

        $detalles = $this->contaModelo->obtenerDetallesPartida($idPartida);
        $this->assertCount(2, $detalles, 'El asiento de anticipo debe tener 2 líneas (Débito y Crédito)');

        $totalDebe = 0.0;
        $totalHaber = 0.0;
        foreach ($detalles as $det) {
            $totalDebe += (float)$det['debe'];
            $totalHaber += (float)$det['haber'];
        }

        $this->assertEquals($monto, $totalDebe, 'Total DEBE debe ser igual al monto del anticipo');
        $this->assertEquals($totalDebe, $totalHaber, 'DEBE == HABER estricto');
    }

    public function testAsientoRecepcionCompraBalanceado(): void {
        $monto = 4800.50;
        $idRecepcion = 99992;
        $cuentaInventario = ConfigCuentasModelo::obtenerIdPorClave('INVENTARIO_LAB', $this->db) ?: 84;
        $cuentaProveedores = ConfigCuentasModelo::obtenerIdPorClave('PROVEEDORES', $this->db) ?: 121;

        $idPartida = $this->contaModelo->registrarAsientoContable(
            date('Y-m-d'),
            "Recepción de Compra #{$idRecepcion} - Test G.4",
            'RECEPCION_COMPRA',
            $idRecepcion,
            [
                ['id_cuenta_contable' => $cuentaInventario, 'debe' => $monto, 'haber' => 0.0],
                ['id_cuenta_contable' => $cuentaProveedores, 'debe' => 0.0, 'haber' => $monto]
            ]
        );

        $this->assertGreaterThan(0, $idPartida);
        $this->idsLimpieza['partidas'][] = $idPartida;

        $detalles = $this->contaModelo->obtenerDetallesPartida($idPartida);
        $this->assertCount(2, $detalles);
        $this->assertEquals($monto, (float)$detalles[0]['debe']);
        $this->assertEquals($monto, (float)$detalles[1]['haber']);
    }

    public function testAsientoConsumoInventarioBalanceado(): void {
        $costoConsumo = 320.00;
        $idEnsayo = 99993;
        $cuentaCosto = ConfigCuentasModelo::obtenerIdPorClave('COSTO_MAT_LAB', $this->db) ?: 221;
        $cuentaInventario = ConfigCuentasModelo::obtenerIdPorClave('INVENTARIO_LAB', $this->db) ?: 84;

        $idPartida = $this->contaModelo->registrarAsientoContable(
            date('Y-m-d'),
            "Consumo de Insumo Cemento / Reactivo - Ensayo #{$idEnsayo}",
            'CONSUMO_INVENTARIO',
            $idEnsayo,
            [
                ['id_cuenta_contable' => $cuentaCosto, 'debe' => $costoConsumo, 'haber' => 0.0],
                ['id_cuenta_contable' => $cuentaInventario, 'debe' => 0.0, 'haber' => $costoConsumo]
            ]
        );

        $this->assertGreaterThan(0, $idPartida);
        $this->idsLimpieza['partidas'][] = $idPartida;

        $detalles = $this->contaModelo->obtenerDetallesPartida($idPartida);
        $this->assertCount(2, $detalles);
        $this->assertEquals($costoConsumo, (float)$detalles[0]['debe']);
        $this->assertEquals($costoConsumo, (float)$detalles[1]['haber']);
    }
}
