<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Modulos\Operaciones\Servicios\MapeadorRtFm22Servicio;

/**
 * Prueba unitaria de Fase 5: Generación y renderizado de los 5 Informes Nuevos
 * para los formatos de Hojas de Cálculo sin equivalente RT-FM-22:
 * - RT-FM-46 (CBR de Laboratorio)
 * - RT-FM-56 (Calibración de Picnómetros)
 * - RT-FM-58 (Sanidad de Agregados por Intemperismo Acelerado)
 * - RT-FM-62 (Infiltración / Percolación de Suelo)
 * - RT-FM-64 (Tensión en Varillas de Acero de Refuerzo)
 */
class ReportesNuevosHcTest extends TestCase {

    private MotorHojasCalculoServicio $motor;
    private MapeadorRtFm22Servicio $mapeador;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
        $this->mapeador = new MapeadorRtFm22Servicio($this->motor);
    }

    public function testGeneracionYRenderizadoDeLos5ReportesNuevos(): void {
        $formatosNuevos = ['RT-FM-46', 'RT-FM-56', 'RT-FM-58', 'RT-FM-62', 'RT-FM-64'];
        $rutaVista = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/reporte_hc_nuevo_print.php';
        $this->assertFileExists($rutaVista);

        foreach ($formatosNuevos as $codHc) {
            $spec = $this->motor->cargarEspecificacion($codHc);
            $entradas = [];
            foreach ($spec['caso_prueba'] as $cp) {
                $entradas[] = $cp['entradas'];
            }

            $evalConjunto = $this->motor->evaluarConjunto($codHc, $entradas);
            $reporte = $this->mapeador->mapearHaciaInforme($codHc, $evalConjunto, [
                'codigo_muestra' => $spec['caso_prueba'][0]['nombre'] ?? 'MS-0001-26',
                'fecha_ensayo' => '2026-10-20',
                'nombre_proyecto' => 'Proyecto Prueba ' . $codHc,
                'cliente_nombre' => 'CYCSA Control de Calidad',
            ]);

            $this->assertTrue($reporte['es_reporte_nuevo'], "{$codHc} debe generar reporte nuevo");
            $this->assertNull($reporte['letra_rt_fm_22']);
            $this->assertNotEmpty($reporte['codigo_reporte_nuevo']);
            $this->assertNotEmpty($reporte['columnas']);
            $this->assertNotEmpty($reporte['filas']);

            ob_start();
            include $rutaVista;
            $html = (string)ob_get_clean();

            $this->assertStringContainsString($reporte['codigo_reporte_nuevo'], $html);
            $this->assertStringContainsString($codHc, $html);
            $this->assertStringContainsString($reporte['titulo_reporte'], $html);
        }
    }
}
