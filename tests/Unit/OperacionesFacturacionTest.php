<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Enrutador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

class OperacionesFacturacionTest extends TestCase {

    public function testNumeroALetrasFormateaValoresCorrectamente(): void {
        $this->assertTrue(function_exists('numeroALetras'));

        $texto1 = numeroALetras(1495.00, 'C$');
        $this->assertStringContainsString('MIL CUATROCIENTOS NOVENTA Y CINCO', $texto1);
        $this->assertStringContainsString('00/100 CÓRDOBAS NETOS', $texto1);

        $texto2 = numeroALetras(250.50, '$');
        $this->assertStringContainsString('DOSCIENTOS CINCUENTA', $texto2);
        $this->assertStringContainsString('50/100 DÓLARES NETOS', $texto2);

        $texto3 = numeroALetras(1035.75, 'C$');
        $this->assertStringContainsString('MIL TREINTA Y CINCO', $texto3);
        $this->assertStringContainsString('75/100 CÓRDOBAS NETOS', $texto3);
    }

    public function testRutasFacturacionEstanRegistradas(): void {
        $peticion = new Peticion();
        $respuesta = new Respuesta();
        $enrutador = new Enrutador($peticion, $respuesta);

        $appObj = (new \ReflectionClass(\Cycsa\Nucleo\Aplicacion::class))->newInstanceWithoutConstructor();
        $appObj->enrutador = $enrutador;
        \Cycsa\Nucleo\Aplicacion::$app = $appObj;

        $app = $appObj;

        require dirname(__DIR__, 2) . '/rutas/web.php';

        $refEnrutador = new \ReflectionClass($enrutador);
        $propRutas = $refEnrutador->getProperty('rutas');
        $propRutas->setAccessible(true);
        $rutas = $propRutas->getValue($enrutador);

        // Validar ruta POST de procesar facturación
        $this->assertArrayHasKey('POST', $rutas);
        $this->assertArrayHasKey('/operaciones/procesar-facturacion', $rutas['POST']);
        $this->assertEquals(
            'Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador',
            $rutas['POST']['/operaciones/procesar-facturacion']['callback'][0]
        );
        $this->assertEquals(
            'procesarFacturacion',
            $rutas['POST']['/operaciones/procesar-facturacion']['callback'][1]
        );

        // Validar ruta GET de imprimir factura
        $this->assertArrayHasKey('GET', $rutas);
        $this->assertArrayHasKey('/operaciones/imprimir-factura', $rutas['GET']);
        $this->assertEquals(
            'Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador',
            $rutas['GET']['/operaciones/imprimir-factura']['callback'][0]
        );
        $this->assertEquals(
            'imprimirFactura',
            $rutas['GET']['/operaciones/imprimir-factura']['callback'][1]
        );
    }

    public function testPartidaDobleAsientoFacturacionCuadraEstrictamente(): void {
        $montoFactura = 1495.00;
        
        // Simulación de Facturación en Efectivo
        // Debe: Caja Principal (Cuenta 4)
        // Haber: Consultorías-Laboratorios (Cuenta 208)
        $lineasEfectivo = [
            ['id_cuenta_contable' => 4, 'debe' => $montoFactura, 'haber' => 0.0],
            ['id_cuenta_contable' => 208, 'debe' => 0.0, 'haber' => $montoFactura]
        ];

        $totalDebe = array_sum(array_column($lineasEfectivo, 'debe'));
        $totalHaber = array_sum(array_column($lineasEfectivo, 'haber'));
        $this->assertEquals($totalDebe, $totalHaber);
        $this->assertEquals($montoFactura, $totalDebe);

        // Simulación de Facturación por Transferencia Bancaria
        // Debe: Cuenta Bancaria LAFISE (Cuenta 9) o BAC (Cuenta 6)
        // Haber: Consultorías-Laboratorios (Cuenta 208)
        $lineasTransferencia = [
            ['id_cuenta_contable' => 6, 'debe' => $montoFactura, 'haber' => 0.0],
            ['id_cuenta_contable' => 208, 'debe' => 0.0, 'haber' => $montoFactura]
        ];

        $totalDebeTx = array_sum(array_column($lineasTransferencia, 'debe'));
        $totalHaberTx = array_sum(array_column($lineasTransferencia, 'haber'));
        $this->assertEquals($totalDebeTx, $totalHaberTx);
        $this->assertEquals($montoFactura, $totalDebeTx);
    }

    public function testRegistroRealAsientoContableFacturacionEnBaseDatos(): void {
        try {
            $db = Conexion::obtenerInstancia();
            $stmt = $db->query("SELECT 1");
            if (!$stmt) {
                $this->markTestSkipped("Base de datos no disponible en entorno de pruebas.");
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped("Conexión BD fallida: " . $e->getMessage());
        }

        $modelo = new ContabilidadModelo();
        $fecha = date('Y-m-d');
        $monto = 500.00;
        $concepto = "TEST FACTURACION O/S EFECTIVO ASIGNADO";
        
        $lineas = [
            ['id_cuenta_contable' => 4, 'debe' => $monto, 'haber' => 0.0],
            ['id_cuenta_contable' => 208, 'debe' => 0.0, 'haber' => $monto]
        ];

        $partidaId = $modelo->registrarAsientoContable($fecha, $concepto, 'FACTURACION', 99999, $lineas);
        $this->assertNotNull($partidaId);
        $this->assertGreaterThan(0, $partidaId);

        // Validar que se insertó en partidas_diario y que cuadró
        $db = Conexion::obtenerInstancia();
        $stmtPartida = $db->prepare("SELECT * FROM partidas_diario WHERE id = :id");
        $stmtPartida->execute(['id' => $partidaId]);
        $row = $stmtPartida->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row);
        $this->assertEquals('FACTURACION', $row['origen']);
        $this->assertEquals(99999, $row['origen_id']);

        // Validar los detalles de la partida
        $stmtDet = $db->prepare("SELECT * FROM partidas_diario_detalles WHERE id_partida = :id");
        $stmtDet->execute(['id' => $partidaId]);
        $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $detalles);

        // Limpieza de prueba unitaria
        $modelo->eliminarAsiento($partidaId);
    }
}
