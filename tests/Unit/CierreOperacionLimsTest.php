<?php

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Modelos\CierreOperacionLims;
use PHPUnit\Framework\TestCase;

class CierreOperacionLimsTest extends TestCase {
    private function ensayo(string $estado): array {
        return [
            'descripcion_ensayo' => 'Compresión',
            'resultados_json' => json_encode([
                'filas' => [['Carga' => 42000]],
                'metadatos' => ['codigo_formato' => 'CYCSA-RT-FM-22'],
                'revision' => ['estado' => $estado]
            ])
        ];
    }

    public function testRechazaEstadosTecnicosNoAprobados(): void {
        foreach (['pendiente', 'en_revision', 'devuelta'] as $estado) {
            $items = $estado === 'pendiente'
                ? [['descripcion_ensayo' => 'Compresión', 'resultados_json' => '']]
                : [$this->ensayo($estado)];
            $actualizaciones = 0;
            $auditorias = 0;
            $error = CierreOperacionLims::cerrar(
                $items, ['saldo' => 0, 'estado' => 'Pagado'],
                function () use (&$actualizaciones): bool { $actualizaciones++; return true; },
                function () use (&$auditorias): bool { $auditorias++; return true; }
            );
            $this->assertNotNull($error, $estado);
            $this->assertSame(0, $actualizaciones);
            $this->assertSame(0, $auditorias);
        }
    }

    public function testRechazaSaldoOEstadoComercialPendiente(): void {
        foreach ([['saldo' => 0.02, 'estado' => 'Pagado'], ['saldo' => 0, 'estado' => 'Pendiente'], null] as $cxc) {
            $this->assertNotNull(CierreOperacionLims::validar([$this->ensayo('aprobada')], $cxc));
        }
        $this->assertNotNull(CierreOperacionLims::validar([], ['saldo' => 0, 'estado' => 'Pagado']));
        $this->assertNotNull(CierreOperacionLims::validar([
            ['descripcion_ensayo' => 'Sin filas', 'resultados_json' => json_encode(['filas' => [], 'revision' => ['estado' => 'aprobada']])]
        ], ['saldo' => 0, 'estado' => 'Pagado']));
    }

    public function testCierraYAuditaSoloConAmbasValidaciones(): void {
        $estadoFinal = 'Estado 7: Revision Resultados';
        $bitacora = [];
        $error = CierreOperacionLims::cerrar(
            [$this->ensayo('aprobada'), $this->ensayo('aprobada')],
            ['saldo' => 0, 'estado' => 'Pagado'],
            function (string $estado) use (&$estadoFinal): bool { $estadoFinal = $estado; return true; },
            function () use (&$bitacora): bool { $bitacora[] = 'cerrar_orden'; return true; }
        );
        $this->assertNull($error);
        $this->assertSame('Finalizado', $estadoFinal);
        $this->assertSame(['cerrar_orden'], $bitacora);
    }
}
