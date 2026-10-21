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

    public function testValidacionWhitelistEstrictaDeFormatosReporteNuevo(): void {
        foreach (MapeadorRtFm22Servicio::FORMATOS_REPORTE_NUEVO as $fmt) {
            $this->assertTrue($this->mapeador->esFormatoReporteNuevoValido($fmt));
        }

        // Formatos que tienen RT-FM-22 existente o valores arbitrarios NO son reportes nuevos válidos
        $this->assertFalse($this->mapeador->esFormatoReporteNuevoValido('RT-FM-61'));
        $this->assertFalse($this->mapeador->esFormatoReporteNuevoValido('RT-FM-25'));
        $this->assertFalse($this->mapeador->esFormatoReporteNuevoValido('INVALIDO'));
        $this->assertFalse($this->mapeador->esFormatoReporteNuevoValido('../etc/passwd'));
        $this->assertFalse($this->mapeador->esFormatoReporteNuevoValido(''));

        $this->expectException(\InvalidArgumentException::class);
        $this->mapeador->construirReporteNuevoDesdeBaseDatos('RT-FM-61');
    }

    public function testConstruirReporteNuevoDesdeBaseDatosUsaRegistrosRealesYRetornaNullSiNoExisten(): void {
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

        // 1. Si se pide un registro inexistente (ej. registro_id = 99999999), retorna null (no imprime datos demo)
        $inexistente = $this->mapeador->construirReporteNuevoDesdeBaseDatos('RT-FM-64', ['registro_id' => 99999999], $db);
        $this->assertNull($inexistente);

        // 2. Insertar un registro real en hojas_campo_registros para RT-FM-62 y verificar que carga los datos reales
        $spec62 = $this->motor->cargarEspecificacion('RT-FM-62');
        $puntoReal = array_merge($spec62['caso_prueba'][0]['entradas'], [
            'codigo_muestra' => 'MC-REAL-6299-26',
            'descenso_20min' => 12.1,
            'descenso_25min' => 12.5,
            'descenso_30min' => 12.9, // dif_25 = 0.4, dif_30 = 0.4 -> tasa_lm2_dia = 315.5 * SQRT(0.8 / 600) = 11.52
        ]);
        $encabezadoReal = [
            'codigo_muestra' => 'MC-REAL-6299-26',
            'nombre_proyecto' => 'Proyecto Real Infiltración Residencial',
            'cliente_nombre' => 'Constructora Real S.A.',
            'fecha_ensayo' => '2026-10-20',
        ];

        $stmtIns = $db->prepare("
            INSERT INTO hojas_campo_registros (id_os, codigo_formato, variante, datos_encabezado_json, datos_puntos_json, creado_por)
            VALUES (1, 'RT-FM-62', 'ESTANDAR', :enc, :pts, 1)
        ");
        $stmtIns->execute([
            'enc' => json_encode($encabezadoReal, JSON_UNESCAPED_UNICODE),
            'pts' => json_encode([$puntoReal], JSON_UNESCAPED_UNICODE),
        ]);
        $regId = (int)$db->lastInsertId();

        try {
            $reporteReal = $this->mapeador->construirReporteNuevoDesdeBaseDatos('RT-FM-62', ['registro_id' => $regId], $db);
            $this->assertNotNull($reporteReal);
            $this->assertSame('hojas_campo_registros', $reporteReal['metadatos']['origen_datos']);
            $this->assertSame('MC-REAL-6299-26', $reporteReal['metadatos']['codigo_muestra']);
            $this->assertSame('Proyecto Real Infiltración Residencial', $reporteReal['metadatos']['nombre_proyecto']);
            $this->assertEqualsWithDelta(11.52, (float)$reporteReal['filas'][0]['Tasa Q (L/m²·día)'], 0.01);
        } finally {
            $db->prepare("DELETE FROM hojas_campo_registros WHERE id = :id")->execute(['id' => $regId]);
        }

        // 3. Para RT-FM-56 (Picnómetros), verificar que carga del catálogo real calibrado en BD (equipos_calibracion_picnometros)
        $reportePic = $this->mapeador->construirReporteNuevoDesdeBaseDatos('RT-FM-56', ['numero_picnometro' => 2], $db);
        $this->assertNotNull($reportePic);
        $this->assertSame('equipos_calibracion_picnometros', $reportePic['metadatos']['origen_datos']);
        $this->assertSame('PICNO-02', $reportePic['metadatos']['codigo_muestra']);
        $this->assertEqualsWithDelta(199.73, (float)$reportePic['filas'][0]['Volumen Calibrado (mL)'], 0.05);
    }
}
