<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\App\Helpers\PdfHelper;

class PdfPaginacionTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = dirname(__DIR__, 2) . '/storage/cache/test_pdf_' . uniqid();
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $f) {
                @unlink($f);
            }
            @rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    private function extraerTextoPdf(string $pdfBytes): string
    {
        $tempPdf = $this->tempDir . '/extract_' . uniqid() . '.pdf';
        $tempScript = $this->tempDir . '/extract_' . uniqid() . '.py';
        file_put_contents($tempPdf, $pdfBytes);

        $pyCode = "import sys\nimport io\nimport pymupdf\nsys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')\ndoc = pymupdf.open(sys.argv[1])\nfor p in doc:\n    print(p.get_text())\n";
        file_put_contents($tempScript, $pyCode);

        $cmd = 'python ' . escapeshellarg($tempScript) . ' ' . escapeshellarg($tempPdf);
        $output = [];
        $ret = 1;
        @exec($cmd, $output, $ret);
        @unlink($tempPdf);
        @unlink($tempScript);

        if ($ret === 0) {
            return implode("\n", $output);
        }

        return $pdfBytes;
    }

    public function testPdfHelperRenderizaUnaPaginaConPaginacion(): void
    {
        $html = '<html><body><h1>Documento Simple</h1><p>Contenido</p></body></html>';
        $pdf = PdfHelper::renderPdf($html);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);

        $texto = $this->extraerTextoPdf($pdf);
        $this->assertStringContainsString('Página 1 de 1', $texto);
    }

    public function testPdfHelperRenderizaMultiplesPaginasConPaginacionConsecutiva(): void
    {
        $html = '<html><body>'
            . '<h1>Página Uno</h1><p>Contenido uno</p>'
            . '<div style="page-break-before: always;"><h1>Página Dos</h1><p>Contenido dos</p></div>'
            . '<div style="page-break-before: always;"><h1>Página Tres</h1><p>Contenido tres</p></div>'
            . '</body></html>';

        $pdf = PdfHelper::renderPdf($html);
        $this->assertNotEmpty($pdf);

        $texto = $this->extraerTextoPdf($pdf);
        $this->assertStringContainsString('Página 1 de 3', $texto);
        $this->assertStringContainsString('Página 2 de 3', $texto);
        $this->assertStringContainsString('Página 3 de 3', $texto);
    }

    public function testGenerarCotizacionPdfContienePaginacion(): void
    {
        $cotizacion = [
            'id' => 99,
            'codigo' => 'COT-2026-TEST',
            'version' => 1,
            'fecha_creacion' => '2026-09-15',
            'cliente_nombre' => 'Cliente de Prueba S.A.',
            'cliente_ruc' => 'J0310000000000',
            'atencion_a' => 'Contacto',
            'nombre_proyecto' => 'Proyecto Test',
            'direccion_proyecto' => 'León',
            'prioridad' => 'Normal',
            'tipo_moneda' => 1,
            'subtotal' => 1000,
            'descuento' => 0,
            'impuesto' => 150,
            'total' => 1150,
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'creador_nombre' => 'Asesor Test',
            'archivo_adjunto' => null,
            'incluir_anexo_tecnico' => 0
        ];

        $detalles = [
            [
                'descripcion_ensayo' => 'Ensayo 1 ASTM C39',
                'nombre_comercial' => 'Compresión de Cilindros',
                'condiciones_muestra' => 'Curado 28 días',
                'procedimiento' => 'ASTM C39',
                'unidad_medida' => 'Cilindro',
                'cantidad' => 3,
                'precio_unitario' => 500,
                'subtotal' => 1500
            ]
        ];

        $pdf = generarCotizacionPDF($cotizacion, $detalles);
        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);

        $texto = $this->extraerTextoPdf($pdf);
        $this->assertStringContainsString('Página 1 de 1', $texto);
    }

    public function testGenerarHojaSolicitudPdfContienePaginacion(): void
    {
        $hoja = [
            'id' => 55,
            'id_os' => 12,
            'numero_registro' => '99887',
            'naturaleza_muestra' => 'Suelo',
            'fecha_hora_llegada_laboratorio' => '2026-09-15 08:30:00',
            'muestras_json' => json_encode([
                ['nombre_muestra' => 'M-01', 'descripcion' => 'Suelo', 'info_importante' => 'Estándar']
            ]),
            'nombre_recibe_cycsa' => 'Receptor CYCSA',
            'nombre_empresa_o_cliente' => 'Empresa Solicitante'
        ];

        $os = [
            'id' => 12,
            'codigo_os' => 'OS-2026-0012',
            'nombre_proyecto' => 'Proyecto Solicitud'
        ];

        $pdf = generarHojaSolicitudPDF($hoja, $os);
        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);

        $texto = $this->extraerTextoPdf($pdf);
        $this->assertStringContainsString('Página 1 de 1', $texto);
    }

    public function testFusionarPdfConAdjuntoNumeraTodasLasHojasUnificadas(): void
    {
        // Generar un PDF principal de 1 página
        $pdf1 = PdfHelper::renderPdf('<html><body><h1>Página Principal</h1></body></html>', 'A4', 'portrait', false);
        // Generar un PDF adjunto de 1 página
        $pdf2 = PdfHelper::renderPdf('<html><body><h1>Documento Anexo Externo</h1></body></html>', 'A4', 'portrait', false);

        $rutaAdjunto = $this->tempDir . '/adjunto.pdf';
        file_put_contents($rutaAdjunto, $pdf2);

        $pdfFusionado = fusionarPdfConAdjunto($pdf1, $rutaAdjunto);
        $this->assertNotEmpty($pdfFusionado);

        $texto = $this->extraerTextoPdf($pdfFusionado);
        // Debe tener 2 páginas en total y estar enumerado unificadamente
        $this->assertStringContainsString('Página 1 de 2', $texto);
        $this->assertStringContainsString('Página 2 de 2', $texto);
    }
}
