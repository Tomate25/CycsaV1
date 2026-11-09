<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MetodosCabeceraMatrizTest extends TestCase {

    private function extraerTextoDePdf(string $pdfBytes): string {
        $texto = $pdfBytes;
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfBytes, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $utf8 = mb_convert_encoding($decompressed, 'UTF-8', 'Windows-1252');
                    $texto .= ' ' . $decompressed . ' ' . $utf8;
                }
            }
        }
        return str_replace(['\\(', '\\)'], ['(', ')'], $texto);
    }

    /**
     * Prueba de paridad visual con la captura del usuario:
     * El formato PE-01 (ensayos_varios.md) debe tener cabecera de 2 filas:
     * Fila 1: "Método de ensayo" y los códigos exactos ASTM (ASTM C40/C40M-26, ASTM C29/C29M-23, etc.)
     * Fila 2: Nombres de columnas (Código laboratorio, Nombre muestra, Color, PVSS, etc.)
     */
    public function testCabeceraDobleFilaConMetodosAstmPe01(): void {
        $detalle = [
            'id' => 101,
            'id_cotizacion' => 12,
            'codigo_servicio' => 'ENS-AGR-01',
            'descripcion_ensayo' => 'Ensayos Varios en Agregados',
            'norma_astm' => 'DE-ASTM D75M-19',
            'archivo_markdown' => 'ensayos_varios.md',
            'codigo_formato' => 'CYCSA-RT-FM-22 G V1R2',
            'cliente_nombre' => 'CONSTRUCTORA PRUEBA S.A.',
            'cliente_direccion' => 'Managua, Nicaragua',
            'nombre_proyecto' => 'Proyecto Carretera Costanera',
            'fecha_muestreo' => '2026-10-25',
            'fecha_ingreso' => '2026-10-26',
            'fecha_ejecucion' => '2026-10-27',
            'fecha_emision' => '2026-10-28',
            'procedimiento_muestreo' => 'Aleatorio',
            'muestra_tomada_por' => 'Laboratorio CYCSA',
            'ubicacion' => 'Km 12 Carretera a Masaya',
            'resultados_json' => json_encode([
                'version_actual' => 1,
                'filas' => [
                    [
                        'Código laboratorio' => 'MS-0717-26',
                        'Nombre muestra' => 'Material 3/4 Procedente de Proinco',
                        'Color' => '1',
                        'PVSS (kg/m³)' => '1420',
                        'PVSC (kg/m³)' => '1550',
                        'Módulo de finura ((%) (P/P))' => '2.85',
                        'Humedad Natural ((%) (P/P))' => '3.5',
                        'Absorción ((%)(P/P))' => '1.8',
                        'Gravedad Específica.' => '2.65',
                        'Gravedad Específica (SSS)' => '2.68',
                        'Gravedad Específica Aparente' => '2.72'
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];

        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $this->assertNotEmpty($pdfBytes, 'El PDF no debe estar vacío');

        $textoPdf = $this->extraerTextoDePdf($pdfBytes);

        // Validar textos normativos del encabezado superior
        $this->assertStringContainsString('Método de ensayo', $textoPdf);
        $this->assertStringContainsString('ASTM C40/C40M-26', $textoPdf);
        $this->assertStringContainsString('ASTM C29/C29M-23', $textoPdf);
        $this->assertStringContainsString('ASTM C136/C136M-25', $textoPdf);
        $this->assertStringContainsString('ASTM C566-25', $textoPdf);
        $this->assertStringContainsString('ASTM C128-25', $textoPdf);
        $this->assertStringContainsString('ASTM C127-25', $textoPdf);

        // Validar columnas de datos de la fila inferior
        $this->assertStringContainsString('Código laboratorio', $textoPdf);
        $this->assertStringContainsString('Nombre muestra', $textoPdf);
        $this->assertStringContainsString('Color', $textoPdf);
        $this->assertStringContainsString('PVSS (kg/m³)', $textoPdf);
        $this->assertStringContainsString('PVSC (kg/m³)', $textoPdf);
        $this->assertStringContainsString('Módulo de finura', $textoPdf);
    }

    /**
     * Prueba que matriz_print.php incluye la estructura de cabecera de 2 filas con Método de ensayo
     */
    public function testMatrizPrintPoseeEstructuraDobleFilaMetodos(): void {
        $printContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString('tr-metodos-normativos', $printContent);
        $this->assertStringContainsString('th-metodo-ensayo', $printContent);
        $this->assertStringContainsString('Método de ensayo', $printContent);
        $this->assertStringContainsString('tr-columnas-nombres', $printContent);
    }

    /**
     * Prueba que formatos_schema.json tiene asignados los métodos ASTM en column_methods
     */
    public function testFormatosSchemaPoseenMetodosAstmOficiales(): void {
        $schemaPath = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
        $schema = json_decode((string)file_get_contents($schemaPath), true);

        // Ensayos varios
        $this->assertNotEmpty($schema['ensayos_varios.md']['column_methods']['Color']);
        $this->assertNotEmpty($schema['ensayos_varios.md']['column_methods']['PVSS (kg/m³)']);
        $this->assertNotEmpty($schema['ensayos_varios.md']['column_methods']['Módulo de finura ((%) (P/P))']);

        // Cilindros concreto
        $this->assertNotEmpty($schema['resistencia_de_concreto.md']['column_methods']['Área (in²)']);
        $this->assertNotEmpty($schema['resistencia_de_concreto.md']['column_methods']['Reven. (in)']);
        $this->assertNotEmpty($schema['resistencia_de_concreto.md']['column_methods']['Temp. (°C)']);

        // Viguetas flexión
        $this->assertNotEmpty($schema['formato_de_resistencia_de_a_la_flexion.md']['column_methods']['Ancho Promedio (in)']);
        $this->assertNotEmpty($schema['formato_de_resistencia_de_a_la_flexion.md']['column_methods']['Reven. (cm)']);
    }
}
