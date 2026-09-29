<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CodigoInformeEnsayoTest extends TestCase {

    public function testCodigoInformeConsecutivosMS(): void {
        $muestras = [
            ['Código laboratorio' => 'MS-0001-26'],
            ['Código laboratorio' => 'MS-0002-26'],
            ['Código laboratorio' => 'MS-0003-26'],
            ['Código laboratorio' => 'MS-0004-26'],
            ['Código laboratorio' => 'MS-0005-26'],
        ];
        $codigo = generarCodigoInformeEnsayo($muestras, '2026-05-10');
        $this->assertSame('CYCSA-INF-MS-0001-0005-26', $codigo);
    }

    public function testCodigoInformeNoConsecutivosMS(): void {
        $muestras = [
            ['Código laboratorio' => 'MS-0001-26'],
            ['Código laboratorio' => 'MS-0005-26'],
            ['Código laboratorio' => 'MS-0007-26'],
            ['Código laboratorio' => 'MS-0009-26'],
        ];
        $codigo = generarCodigoInformeEnsayo($muestras, '2026-05-10');
        $this->assertSame('CYCSA-INF-MS-0001, 0005, 0007, 0009-26', $codigo);
    }

    public function testCodigoInformeConsecutivosMC(): void {
        $muestras = [
            ['Código laboratorio' => 'MC-0010-26'],
            ['Código laboratorio' => 'MC-0011-26'],
            ['Código laboratorio' => 'MC-0012-26'],
        ];
        $codigo = generarCodigoInformeEnsayo($muestras, '2026-05-10');
        $this->assertSame('CYCSA-INF-MC-0010-0012-26', $codigo);
    }

    public function testCodigoInformeNoConsecutivosMC(): void {
        $muestras = [
            ['Código laboratorio' => 'MC-0002-26'],
            ['Código laboratorio' => 'MC-0008-26'],
        ];
        $codigo = generarCodigoInformeEnsayo($muestras, '2026-05-10');
        $this->assertSame('CYCSA-INF-MC-0002, 0008-26', $codigo);
    }

    public function testCodigoInformeNoConsecutivosDensimetroNuclearE2E(): void {
        $muestras = [
            ['Código laboratorio' => 'MC-0004-26'],
            ['Código laboratorio' => 'MC-0006-26'],
            ['Código laboratorio' => 'MC-0007-26'],
            ['Código laboratorio' => 'MC-0010-26'],
        ];

        $codigo = generarCodigoInformeEnsayo($muestras, '2026-09-20');

        $this->assertSame('CYCSA-INF-MC-0004, 0006, 0007, 0010-26', $codigo);
    }

    public function testCodigoInformeMuestraUnica(): void {
        $muestras = [
            ['Código laboratorio' => 'MS-0001-26'],
        ];
        $codigo = generarCodigoInformeEnsayo($muestras, '2026-05-10');
        $this->assertSame('CYCSA-INF-MS-0001-26', $codigo);
    }

    public function testCodigoInformeFallbackTipoMuestra(): void {
        $codigoConcreto = generarCodigoInformeEnsayo([], '2026-05-10', 'Cilindros de Concreto');
        $this->assertSame('CYCSA-INF-MC-0001-26', $codigoConcreto);

        $codigoSuelo = generarCodigoInformeEnsayo([], '2026-05-10', 'Suelo');
        $this->assertSame('CYCSA-INF-MS-0001-26', $codigoSuelo);
    }

    public function testCodigoInformeSeRenderizaEnPdfYVistas(): void {
        $detalle = [
            'id' => 101,
            'codigo_os' => 'OS-2026-TEST',
            'descripcion_ensayo' => 'Resistencia de Concreto',
            'norma_astm' => 'ASTM C39',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'tipo_muestra' => 'Cilindros de Concreto',
            'resultados_json' => json_encode([
                'filas' => [
                    ['Código laboratorio' => 'MC-0001-26', 'Área (in²)' => '12.56', 'Carga (lb)' => '50000'],
                    ['Código laboratorio' => 'MC-0002-26', 'Área (in²)' => '12.56', 'Carga (lb)' => '52000'],
                    ['Código laboratorio' => 'MC-0003-26', 'Área (in²)' => '12.56', 'Carga (lb)' => '51000'],
                ]
            ]),
        ];

        // 1. PDF
        $pdf = generarMatrizTecnicaPDF($detalle);
        $this->assertStringStartsWith('%PDF-', $pdf);

        // 2. Vista Impresión
        ob_start();
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown']);
        $columnas = $schemaInfo['columns'];
        $muestrasSeteadas = [];
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php';
        $printHtml = ob_get_clean();
        $this->assertStringContainsString('CYCSA-INF-MC-0001-0003-26', $printHtml);

        // 3. Vista Captura
        ob_start();
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = 'test-token';
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] => $schemaInfo]);
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $capturaHtml = ob_get_clean();
        $this->assertStringContainsString('CYCSA-INF-MC-0001-0003-26', $capturaHtml);
        $this->assertStringContainsString('recalculateCodigoInforme', $capturaHtml);
    }
}
