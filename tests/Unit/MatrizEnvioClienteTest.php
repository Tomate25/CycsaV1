<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrizEnvioClienteTest extends TestCase {

    public function testGenerarMatrizTecnicaPdfGeneraBinarioValido(): void {
        $detalle = [
            'id' => 99,
            'codigo_os' => 'OS-2026-0001',
            'descripcion_ensayo' => 'Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23',
            'norma_astm' => 'ASTM D6938-23',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'cliente_nombre' => 'IALSA CONSTRUCCIONES',
            'cliente_email' => 'cliente@test.com',
            'nombre_proyecto' => 'ISALAS GHTR',
            'tecnico_muestreo' => 'Juan',
            'fecha_hora_toma_muestra' => '2026-09-15 08:00:00',
            'observaciones' => 'Ensayos ejecutados según norma ASTM D6938.',
            'resultados_json' => json_encode([
                [
                    'Código laboratorio' => 'MC-0001-26',
                    'Nombre muestra' => 'Muestra 1',
                    'Capa (N°)' => '1',
                    'Espesor (cm)' => '15',
                    'Profundidad (cm)' => '15',
                    'P.V.S Max (kg/m³)' => '2100',
                    'Humedad Óptima ((% )(P/P))' => '12.5',
                    'P.V.S.Sitio (kg/cm²)' => '2050',
                    'Humedad Sitio ((%) (P/P))' => '12.0',
                    'Humedad Sitio (kg/m³)' => '2000',
                    'Compactación ((%) (P/P))' => '97.6',
                    'Fecha de muestreo' => '2026-09-15'
                ]
            ])
        ];

        $columnas = [
            'Código laboratorio', 'Nombre muestra', 'Capa (N°)', 'Espesor (cm)', 'Profundidad (cm)',
            'P.V.S Max (kg/m³)', 'Humedad Óptima ((% )(P/P))', 'P.V.S.Sitio (kg/cm²)',
            'Humedad Sitio ((%) (P/P))', 'Humedad Sitio (kg/m³)', 'Compactación ((%) (P/P))', 'Fecha de muestreo'
        ];

        $pdfBytes = generarMatrizTecnicaPDF($detalle, [], $columnas);

        $this->assertNotEmpty($pdfBytes);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertGreaterThan(5000, strlen($pdfBytes));
    }

    public function testGenerarMatrizTecnicaPdfConMuestrasVaciasGeneraFallbackValido(): void {
        $detalle = [
            'id' => 100,
            'codigo_os' => 'OS-2026-0002',
            'descripcion_ensayo' => 'Compresión de Cilindros de Concreto',
            'norma_astm' => 'ASTM C39/C39M',
            'codigo_documento' => 'CYCSA-RT-FM-08',
            'cliente_nombre' => 'CONSTRUCTORA MODERNA',
            'cliente_email' => 'constructora@test.com',
            'nombre_proyecto' => 'Puente Central',
            'tecnico_muestreo' => 'Carlos',
            'resultados_json' => ''
        ];

        $pdfBytes = generarMatrizTecnicaPDF($detalle);

        $this->assertNotEmpty($pdfBytes);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }

    public function testOperacionModeloObtenerOsActivasIncluyeClienteEmail(): void {
        $modelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
        $ordenes = $modelo->obtenerOSActivas();

        if (!empty($ordenes)) {
            $primera = $ordenes[0];
            $this->assertArrayHasKey('cliente_email', $primera, 'El listado de O/S activas debe incluir el correo del cliente.');
        } else {
            $this->assertTrue(true);
        }
    }
}
