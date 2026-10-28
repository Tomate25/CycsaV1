<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\CierreOperacionLims;
use PDO;

class OperacionesFacturacionCondicionTest extends TestCase {

    private function getDb(): ?PDO {
        try {
            return Conexion::obtenerInstancia();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function testMigracion022CamposFacturaInformeExisten(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('BD de pruebas no disponible.');
        }

        $colsCot = $db->query("SHOW COLUMNS FROM cotizaciones")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('id_cliente_factura', $colsCot);
        $this->assertContains('id_cliente_informe', $colsCot);
        $this->assertContains('condicion_comercial', $colsCot);

        $colsOS = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('id_cliente_factura', $colsOS);
        $this->assertContains('id_cliente_informe', $colsOS);
        $this->assertContains('condicion_comercial', $colsOS);
    }

    public function testResolverMetadatosPriorizaClienteInformeSobreClienteGeneral(): void {
        $detalle = [
            'id' => 999,
            'cliente_nombre' => 'CONSTRUCTORA PAGADORA S.A.',
            'cliente_factura_nombre' => 'CONSTRUCTORA PAGADORA S.A.',
            'cliente_informe_nombre' => 'MINISTERIO DE TRANSPORTE E INFRAESTRUCTURA (MTI)',
            'nombre_proyecto' => 'Puente Paso Real Km 140',
            'codigo_documento' => 'CYCSA-RT-FM-22'
        ];

        $meta = resolverMetadatosEnsayo($detalle);

        // La figura jurídica del informe de ensayo debe ser el MTI (ISO/IEC 17025)
        $this->assertSame('MINISTERIO DE TRANSPORTE E INFRAESTRUCTURA (MTI)', $meta['cliente_nombre']);
        $this->assertSame('Puente Paso Real Km 140', $meta['proyecto']);
    }

    public function testCondicionCreditoPermiteCierreConFacturaDocumentadaYSaldoAbierto(): void {
        $ensayoAprobado = [
            'descripcion_ensayo' => 'Resistencia de Concreto a 28 días',
            'resultados_json' => json_encode([
                'filas' => [['Carga' => 55000, 'Código laboratorio' => 'C-01']],
                'revision' => ['estado' => 'aprobada', 'estado_label' => 'Aprobada por Supervisión']
            ])
        ];

        // Caso 1: Crédito con CxC documentada (saldo C$ 45,000 pendiente) -> CIERRE AUTORIZADO
        $cxcAbierta = [
            'saldo' => 45000.00,
            'estado' => 'Pendiente',
            'factura_numero' => 'FAC-COT-2026-0099'
        ];

        $errorCredito = CierreOperacionLims::validar([$ensayoAprobado], $cxcAbierta, 'credito');
        $this->assertNull($errorCredito, 'En condición crédito con CxC documentada el cierre debe permitirse');

        // Caso 2: Crédito sin documento CxC registrado -> BLOQUEO
        $errorSinCxc = CierreOperacionLims::validar([$ensayoAprobado], null, 'credito');
        $this->assertNotNull($errorSinCxc);
        $this->assertStringContainsString('crédito', $errorSinCxc);
    }

    public function testCondicionContadoBloqueaCierreSiExisteSaldoPendiente(): void {
        $ensayoAprobado = [
            'descripcion_ensayo' => 'Compactación in situ',
            'resultados_json' => json_encode([
                'filas' => [['Densidad' => 1.95, 'Código laboratorio' => 'D-01']],
                'revision' => ['estado' => 'aprobada', 'estado_label' => 'Aprobada por Supervisión']
            ])
        ];

        // Caso 1: Contado con saldo pendiente (C$ 500) -> BLOQUEADO
        $cxcSaldo = [
            'saldo' => 500.00,
            'estado' => 'Parcial',
            'factura_numero' => 'FAC-COT-2026-0100'
        ];
        $errorContado = CierreOperacionLims::validar([$ensayoAprobado], $cxcSaldo, 'contado');
        $this->assertNotNull($errorContado);
        $this->assertStringContainsString('saldo C$ 0.00', $errorContado);

        // Caso 2: Contado pagado al 100% (saldo C$ 0.00) -> AUTORIZADO
        $cxcPagada = [
            'saldo' => 0.00,
            'estado' => 'Pagado',
            'factura_numero' => 'FAC-COT-2026-0100'
        ];
        $errorPagado = CierreOperacionLims::validar([$ensayoAprobado], $cxcPagada, 'contado');
        $this->assertNull($errorPagado);
    }

    public function testCierreExigeEnsayosCompletamenteAprobadosIndependienteDeCondicionComercial(): void {
        $ensayoEnRevision = [
            'descripcion_ensayo' => 'Granulometría',
            'resultados_json' => json_encode([
                'filas' => [['Malla' => '#4', 'Pasa' => 95]],
                'revision' => ['estado' => 'en_revision', 'estado_label' => 'En Revisión']
            ])
        ];

        // Incluso con crédito y factura pagada, si el ensayo técnico no está aprobado, SE BLOQUEA
        $cxcOk = ['saldo' => 0.00, 'estado' => 'Pagado'];
        $error = CierreOperacionLims::validar([$ensayoEnRevision], $cxcOk, 'credito');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Todos los ensayos deben estar APROBADOS', $error);
    }
}
