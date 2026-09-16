<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CotizacionPdfAdjuntoTest extends TestCase {

    public function testDecodificarIdSoportaEnterosHashesYBase64(): void {
        // 1. Entero puro
        $this->assertSame(1, decodificarId(1));
        $this->assertSame(42, decodificarId("42"));

        // 2. Hash cifrado con HashHelper
        $hash = codificarId(1);
        $this->assertSame(1, decodificarId($hash));

        // 3. Cadena base64 simple (ej. base64_encode('1') = 'MQ==')
        $this->assertSame(1, decodificarId('MQ=='));
        $this->assertSame(10, decodificarId(base64_encode('10')));

        // 4. Cadena vacía o no válida
        $this->assertNull(decodificarId(''));
        $this->assertNull(decodificarId(null));
    }

    public function testProcesarArchivoAdjuntoDocxGeneraHtmlConEncabezadoOficial(): void {
        $docxPath = 'uploads/cotizaciones/anexo_20260915_141527_ce60f87af7c7.docx';
        $fullPath = dirname(__DIR__, 2) . '/publico/' . $docxPath;

        if (!file_exists($fullPath)) {
            $this->markTestSkipped('Archivo docx de prueba no existe en el entorno.');
        }

        $cotizacion = [
            'codigo' => 'COT-2026-TEST',
            'nombre_proyecto' => 'Proyecto Auditoria Docx',
            'version' => 1,
            'archivo_adjunto' => $docxPath
        ];

        $resultado = procesarArchivoAdjuntoCotizacion($cotizacion, '');
        $this->assertNotEmpty($resultado['html'], 'El HTML procesado del archivo adjunto DOCX no debe estar vacío.');
        $this->assertStringContainsString('Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto', $resultado['html']);
        $this->assertStringContainsString('COT-2026-TEST', $resultado['html']);
        $this->assertStringContainsString('Proyecto Auditoria Docx', $resultado['html']);
        $this->assertNull($resultado['ruta_pdf'], 'Un archivo DOCX no debe fijar ruta_pdf ya que se compila a HTML.');
    }

    public function testGenerarCotizacionPdfIncluyeAnexoDocx(): void {
        $docxPath = 'uploads/cotizaciones/anexo_20260915_141527_ce60f87af7c7.docx';
        $fullPath = dirname(__DIR__, 2) . '/publico/' . $docxPath;

        if (!file_exists($fullPath)) {
            $this->markTestSkipped('Archivo docx de prueba no existe en el entorno.');
        }

        $cotizacion = [
            'id' => 9999,
            'codigo' => 'COT-TEST-PDF',
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'tipo_moneda' => 1,
            'subtotal' => 1000.00,
            'descuento' => 0.00,
            'impuesto' => 150.00,
            'total' => 1150.00,
            'cliente_nombre' => 'Cliente de Prueba SA',
            'cliente_ruc' => 'J031000000000',
            'nombre_proyecto' => 'Prueba Unitaria PDF Anexo',
            'direccion_proyecto' => 'Managua, Nicaragua',
            'prioridad' => 'Normal',
            'version' => 1,
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'creador_nombre' => 'Tester Antigravity',
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => '',
            'archivo_adjunto' => $docxPath
        ];

        $detalles = [
            [
                'descripcion_ensayo' => 'Ensayo de Compresión en Cilindros',
                'nombre_comercial' => 'Compresión Concreto',
                'condiciones_muestra' => 'Estándar',
                'procedimiento' => 'ASTM C39',
                'unidad_medida' => 'Cilindro',
                'cantidad' => 3,
                'precio_unitario' => 300.00,
                'subtotal' => 900.00
            ]
        ];

        $pdfBytes = generarCotizacionPDF($cotizacion, $detalles);
        $this->assertNotEmpty($pdfBytes, 'El PDF generado no debe estar vacío.');
        $this->assertStringStartsWith('%PDF', $pdfBytes, 'El archivo binario retornado debe ser un PDF válido.');
        $this->assertGreaterThan(50000, strlen($pdfBytes), 'El PDF con el DOCX e imágenes adjuntas debe superar los 50KB.');
    }

    public function testFusionarPdfConAdjuntoConcatenaCorrectamente(): void {
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);

        $d1 = new \Dompdf\Dompdf($options);
        $d1->loadHtml('<h1>Documento Principal</h1>');
        $d1->render();
        $b1 = $d1->output();

        $d2 = new \Dompdf\Dompdf($options);
        $d2->loadHtml('<h1>Documento Anexo</h1>');
        $d2->render();
        $b2 = $d2->output();

        $tempAdjunto = dirname(__DIR__, 2) . '/storage/cache/unit_test_adjunto.pdf';
        file_put_contents($tempAdjunto, $b2);

        $fusionado = fusionarPdfConAdjunto($b1, $tempAdjunto);
        @unlink($tempAdjunto);

        $this->assertNotEmpty($fusionado);
        $this->assertStringStartsWith('%PDF', $fusionado);
        $this->assertGreaterThan(strlen($b1), strlen($fusionado), 'El PDF fusionado debe tener un tamaño mayor que el documento original.');
    }

    public function testProcesarArchivoAdjuntoImagenGeneraHtmlConImagenBase64(): void {
        $imgPath = dirname(__DIR__, 2) . '/publico/img/logo_cycsa.jpg';
        if (!file_exists($imgPath)) {
            $this->markTestSkipped('Imagen de logo no encontrada para prueba.');
        }

        $cotizacion = [
            'codigo' => 'COT-IMG-TEST',
            'nombre_proyecto' => 'Prueba Imagen',
            'version' => 1,
            'archivo_adjunto' => 'img/logo_cycsa.jpg'
        ];

        $resultado = procesarArchivoAdjuntoCotizacion($cotizacion, '');
        $this->assertNotEmpty($resultado['html']);
        $this->assertStringContainsString('Anexo Gráfico Adjunto', $resultado['html']);
        $this->assertStringContainsString('data:image/jpeg;base64,', $resultado['html']);
        $this->assertNull($resultado['ruta_pdf']);
    }

    public function testFallbackConstanciaDocumentoAdjuntoParaFormatosGenerales(): void {
        $tempFile = dirname(__DIR__, 2) . '/storage/cache/documento_auditoria.dat';
        file_put_contents($tempFile, 'Contenido binario de prueba de archivo adjunto no visual.');

        $cotizacion = [
            'codigo' => 'COT-DOC-TEST',
            'nombre_proyecto' => 'Prueba Fallback Certificado',
            'version' => 1,
            'archivo_adjunto' => '../storage/cache/documento_auditoria.dat'
        ];

        $resultado = procesarArchivoAdjuntoCotizacion($cotizacion, '');
        @unlink($tempFile);

        $this->assertNotEmpty($resultado['html']);
        $this->assertStringContainsString('Constancia de Documento Adjunto', $resultado['html']);
        $this->assertStringContainsString('Huella Digital (SHA-256):', $resultado['html']);
        $this->assertStringContainsString('Resguardado y Vinculado en Expediente Digital CYCSA', $resultado['html']);
    }
}

