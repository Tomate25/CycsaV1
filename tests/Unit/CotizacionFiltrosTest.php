<?php

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use PHPUnit\Framework\TestCase;

class CotizacionFiltrosTest extends TestCase {
    private ?CotizacionModelo $modelo = null;

    protected function setUp(): void {
        parent::setUp();
        try {
            $this->modelo = new CotizacionModelo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para probar filtros de cotizaciones.');
        }
    }

    public function testFiltroCombinadoRespetaTextoFechaYCliente(): void {
        $todas = $this->modelo->obtenerTodas();
        if ($todas === []) {
            $this->markTestSkipped('No hay cotizaciones disponibles para validar el filtro combinado.');
        }

        $muestra = $todas[0];
        $fecha = substr((string)$muestra['fecha_creacion'], 0, 10);
        $resultado = $this->modelo->obtenerTodas((string)$muestra['codigo'], [
            'fecha_desde' => $fecha,
            'fecha_hasta' => $fecha,
            'id_cliente' => (int)$muestra['id_cliente'],
        ]);

        $this->assertNotEmpty($resultado);
        foreach ($resultado as $cotizacion) {
            $this->assertSame((int)$muestra['id_cliente'], (int)$cotizacion['id_cliente']);
            $this->assertSame($fecha, substr((string)$cotizacion['fecha_creacion'], 0, 10));
            $this->assertStringContainsString((string)$muestra['codigo'], (string)$cotizacion['codigo']);
        }
    }

    public function testGruposDeEstadoSoloIncluyenEstadosCorrespondientes(): void {
        $grupos = [
            'revision' => ['En Revision'],
            'observadas' => ['Observada'],
            'aprobadas' => ['Aprobada Internamente', 'Enviada al Cliente', 'Aprobada por Cliente'],
            'rechazadas' => ['Rechazada por Cliente'],
        ];

        foreach ($grupos as $filtro => $permitidos) {
            foreach ($this->modelo->obtenerTodas('', ['estado' => $filtro]) as $cotizacion) {
                $this->assertContains($cotizacion['estado'], $permitidos, "El grupo {$filtro} devolvió un estado ajeno.");
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testFechasInvalidasSeIgnoranSinAlterarElListado(): void {
        $sinFiltro = $this->modelo->obtenerTodas();
        $conFechaInvalida = $this->modelo->obtenerTodas('', [
            'fecha_desde' => '2026-99-45',
            'fecha_hasta' => 'no-es-fecha',
        ]);

        $this->assertSame(
            array_column($sinFiltro, 'id'),
            array_column($conFechaInvalida, 'id')
        );
    }

    public function testVistaConservaFiltrosEnBusquedaYPestanas(): void {
        $busqueda = 'COT-2026';
        $tabActual = 'revision';
        $filtros = [
            'fecha_desde' => '2026-09-01',
            'fecha_hasta' => '2026-09-30',
            'id_cliente' => 7,
            'estado' => 'observadas',
        ];
        $clientes = [['id' => 7, 'nombre_razon_social' => 'Cliente de Prueba']];
        $cotizaciones = [];
        $bitacora_logs = [];
        $sesionAnterior = $_SESSION ?? [];
        $_SESSION['usuario_rol'] = 1;

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/index.php';
        $html = (string)ob_get_clean();
        $_SESSION = $sesionAnterior;

        $this->assertStringContainsString('name="fecha_desde" value="2026-09-01"', $html);
        $this->assertStringContainsString('name="fecha_hasta" value="2026-09-30"', $html);
        $this->assertStringContainsString('name="id_cliente" value="7"', $html);
        $this->assertStringContainsString('name="estado" value="observadas"', $html);
        $this->assertStringContainsString(
            'q=COT-2026&amp;fecha_desde=2026-09-01&amp;fecha_hasta=2026-09-30&amp;id_cliente=7&amp;estado=observadas&amp;tab=aprobadas',
            $html
        );
    }
}
