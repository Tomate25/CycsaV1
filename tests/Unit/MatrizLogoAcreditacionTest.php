<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrizLogoAcreditacionTest extends TestCase {

    public function testResolverMetadatosEnsayoIncluyeLogoAcreditacionSiExiste(): void {
        $detalle = [
            'descripcion_ensayo' => 'Compresión de Cilindros',
            'codigo_documento' => 'CYCSA-RT-FM-22'
        ];
        $metadatosGuardados = [
            'cliente_nombre' => 'Test Cliente',
            'logo_acreditacion' => '/Cycsa/publico/uploads/acreditaciones/acred_test_logo.png'
        ];

        $res = resolverMetadatosEnsayo($detalle, [], $metadatosGuardados);

        $this->assertArrayHasKey('logo_acreditacion', $res);
        $this->assertSame('/Cycsa/publico/uploads/acreditaciones/acred_test_logo.png', $res['logo_acreditacion']);
    }

    public function testResolverMetadatosEnsayoRetornaVacioSiNoHayLogoAcreditacion(): void {
        $detalle = [
            'descripcion_ensayo' => 'Ensayo General',
            'codigo_documento' => 'CYCSA-RT-FM-22'
        ];

        $res = resolverMetadatosEnsayo($detalle, [], []);

        $this->assertArrayHasKey('logo_acreditacion', $res);
        $this->assertSame('', $res['logo_acreditacion']);
    }

    public function testGenerarMatrizTecnicaPdfConLogoAcreditacionGeneraPdfValido(): void {
        // Crear una imagen temporal de prueba en uploads/acreditaciones
        $uploadDir = dirname(__DIR__, 2) . '/publico/uploads/acreditaciones';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $testImgPath = $uploadDir . '/test_unit_logo.png';
        
        // Crear imagen PNG 100x50 mínima
        $img = imagecreatetruecolor(100, 50);
        $bg = imagecolorallocate($img, 2, 132, 199);
        imagefilledrectangle($img, 0, 0, 99, 49, $bg);
        imagepng($img, $testImgPath);
        imagedestroy($img);

        $this->assertFileExists($testImgPath);

        $detalle = [
            'id' => 150,
            'codigo_os' => 'OS-2026-0099',
            'descripcion_ensayo' => 'Ensayo Acreditado ISO/IEC 17025',
            'norma_astm' => 'ASTM C39',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'cliente_nombre' => 'CLIENTE TEST ACREDITACION',
            'nombre_proyecto' => 'PROYECTO AUDITORIA',
            'resultados_json' => json_encode([
                'version_actual' => 1,
                'filas' => [
                    [
                        'Código laboratorio' => 'MS-0001-26',
                        'Nombre muestra' => 'Muestra Test 1'
                    ]
                ],
                'metadatos' => [
                    'cliente_nombre' => 'CLIENTE TEST ACREDITACION',
                    'logo_acreditacion' => '/Cycsa/publico/uploads/acreditaciones/test_unit_logo.png'
                ]
            ])
        ];

        $pdfBytes = generarMatrizTecnicaPDF($detalle, [], ['Código laboratorio', 'Nombre muestra']);

        $this->assertNotEmpty($pdfBytes);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertGreaterThan(5000, strlen($pdfBytes));

        // Limpiar archivo de prueba
        if (file_exists($testImgPath)) {
            unlink($testImgPath);
        }
    }

    public function testVistasCapturaEImpresionContienenElementosDeAcreditacion(): void {
        $vistaCaptura = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $vistaImpresion = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');

        $this->assertNotFalse($vistaCaptura);
        $this->assertNotFalse($vistaImpresion);

        // Captura: debe tener input oculto, preview en cabecera y apartado de acreditación
        $this->assertStringContainsString('id="header-logo-acreditacion-preview"', $vistaCaptura);
        $this->assertStringContainsString('id="input_hidden_logo_acreditacion"', $vistaCaptura);
        $this->assertStringContainsString('id="input_file_acreditacion"', $vistaCaptura);
        $this->assertStringContainsString('manejarSubidaLogoAcreditacion', $vistaCaptura);
        $this->assertStringContainsString('quitarLogoAcreditacion', $vistaCaptura);

        // Impresión: debe renderizar logo_acreditacion_print y zona-cabecera con ajuste dinámico
        $this->assertStringContainsString('logo-acreditacion-print', $vistaImpresion);
        $this->assertStringContainsString('logo_acreditacion', $vistaImpresion);
    }

    public function testRutasOperacionesContieneSubirYEliminarLogoAcreditacion(): void {
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertNotFalse($rutas);
        $this->assertStringContainsString('/operaciones/subir-logo-acreditacion', $rutas);
        $this->assertStringContainsString('/operaciones/eliminar-logo-acreditacion', $rutas);
    }

    public function testMatrizPrintNoLanzaErrorCuandoSoloExistenMetadatosEnResultadosJson(): void {
        $detalle = [
            'id' => 99,
            'codigo_os' => 'OS-2026-0001',
            'descripcion_ensayo' => 'Resistencia de Cilindros',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'archivo_markdown' => 'formato_de_resistencia_de_cilindros_de_concreto.md',
            'resultados_json' => json_encode([
                'metadatos' => [
                    'logo_acreditacion' => '/Cycsa/publico/uploads/acreditaciones/prueba.png'
                ]
            ])
        ];
        $muestrasSeteadas = [
            ['codigo_lab' => 'MC-0001-26', 'nombre_muestra' => 'Viga 1']
        ];
        $columnas = ['Código laboratorio', 'Nombre muestra', 'Área (in²)', 'Carga (lb)', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)'];

        ob_start();
        try {
            require dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php';
            $salida = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->fail("matriz_print.php lanzó excepción con resultados_json solo metadatos: " . $e->getMessage());
        }

        $this->assertNotEmpty($salida);
        $this->assertStringContainsString('MC-0001-26', $salida);
        $this->assertStringContainsString('<td style="font-weight: bold;">1</td>', $salida);
    }
}
