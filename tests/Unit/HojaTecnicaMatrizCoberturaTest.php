<?php

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Servicios\HojaTecnicaMatrizServicio;
use PHPUnit\Framework\TestCase;

class HojaTecnicaMatrizCoberturaTest extends TestCase {

    public function testTodosLosFormatosDeSchemaJsonTienenFichaTecnicaValida(): void {
        $schemaPath = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
        $this->assertFileExists($schemaPath);

        $schema = json_decode((string)file_get_contents($schemaPath), true);
        $this->assertIsArray($schema);
        $this->assertGreaterThanOrEqual(25, count($schema));

        foreach (array_keys($schema) as $archivoMd) {
            $def = HojaTecnicaMatrizServicio::obtenerDefinicionPorFormato($archivoMd);
            $this->assertNotEmpty($def['codigo_hc'] ?? '', "Falta codigo_hc para {$archivoMd}");
            $this->assertNotEmpty($def['titulo'] ?? '', "Falta titulo para {$archivoMd}");
            $this->assertNotEmpty($def['motor_calculo'] ?? '', "Falta motor_calculo para {$archivoMd}");
            $this->assertNotEmpty($def['campos_entrada'] ?? [], "Faltan campos_entrada para {$archivoMd}");
            $this->assertNotEmpty($def['campos_calculados'] ?? [], "Faltan campos_calculados para {$archivoMd}");
        }
    }

    public function testCalculoAutoritativoCilindroRtFm61CoincideConMuestraOficial(): void {
        // Caso oficial MS-15192-26: D1=15.20, D2=15.20, L1=30.60, L2=30.40, Masa=13.095, Carga=101645
        $filasTec = [[
            'codigo_lab' => 'MS-15192-26',
            'diametro_1' => '15.20',
            'diametro_2' => '15.20',
            'largo_1' => '30.60',
            'largo_2' => '30.40',
            'masa_kg' => '13.095',
            'carga_lb' => '101645',
            'fecha_ensayo' => '2026-11-22T21:00',
            'tipo_falla' => 'Tipo 1 (Cono bien formado)',
        ]];
        $filasMat = [[
            'Código laboratorio' => 'MS-15192-26',
            'Nombre muestra' => 'Columna Eje A',
            'Fecha de Fabricación' => '2026-10-25',
        ]];

        $res = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('resistencia_de_concreto.md', $filasTec, $filasMat);
        $this->assertTrue($res['tiene_datos']);

        $tec0 = $res['captura_tecnica'][0];
        $mat0 = $res['filas_matriz'][0];

        $this->assertEqualsWithDelta(28.13, (float)$tec0['area_in2'], 0.02);
        $this->assertSame('2370', (string)$tec0['peso_volumetrico']);
        $this->assertSame('3610', (string)$tec0['esfuerzo_psi']);
        $this->assertSame('254', (string)$tec0['esfuerzo_kgcm2']);

        $this->assertSame('101645', (string)$mat0['Carga (lb)']);
        $this->assertSame('2370', (string)$mat0['Peso Volumétrico (kg/m³)']);
        $this->assertSame('3610', (string)$mat0['R. Compresión (lb/in²)']);
        $this->assertSame('254', (string)$mat0['R. Compresión (kg/cm²)']);
        $this->assertSame('2026-11-22', (string)$mat0['Fecha de Ensayo']);
    }

    public function testCalculoAutoritativoTodosLosMotoresPrincipales(): void {
        // 1. Mortero RT-FM-57
        $rMort = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('resistencia_de_mortero.md', [[
            'codigo_lab' => 'MS-0001-26', 'largo_cm' => '5.08', 'ancho_cm' => '5.08', 'alto_cm' => '5.08',
            'masa_kg' => '0.285', 'carga_lb' => '12000', 'fecha_ensayo' => '2026-11-22T10:00',
        ]], [['Código laboratorio' => 'MS-0001-26']]);
        $this->assertTrue($rMort['tiene_datos']);
        $this->assertSame('4.00', $rMort['filas_matriz'][0]['Área (in²)']);
        $this->assertSame('3000', $rMort['filas_matriz'][0]['R. Compresión (lb/in²)']);

        // 2. Flexión Viguetas RT-FM-38
        $rFlex = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_de_resistencia_de_a_la_flexion.md', [[
            'codigo_lab' => 'MS-0002-26',
            'ancho_1' => '6.0', 'ancho_2' => '6.0', 'ancho_3' => '6.0',
            'espesor_1' => '6.0', 'espesor_2' => '6.0', 'espesor_3' => '6.0',
            'luz_in' => '18.0', 'carga_lb' => '7200', 'en_tercio_medio' => 'Sí (Tercio Medio)',
            'fecha_ensayo' => '2026-11-22T11:00',
        ]], [['Código laboratorio' => 'MS-0002-26']]);
        $this->assertTrue($rFlex['tiene_datos']);
        $this->assertEqualsWithDelta(42.18, (float)$rFlex['filas_matriz'][0]['Resistencia a la flexión. (kg/cm²)'], 0.05);

        // 3. Proctor RT-FM-27 (elige máximo medido entre puntos)
        $rProc = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('proctor_estandar.md', [
            ['codigo_lab' => 'Punto 1', 'molde_humedo_g' => '5900', 'masa_molde_g' => '4200', 'volumen_molde_ml' => '947.26', 'tara_humedo_g' => '150', 'tara_seco_g' => '140', 'tara_g' => '40', 'metodo_proctor' => 'Proctor Estándar A', 'fecha_ensayo' => '2026-11-22'],
            ['codigo_lab' => 'Punto 2', 'molde_humedo_g' => '6150', 'masa_molde_g' => '4200', 'volumen_molde_ml' => '947.26', 'tara_humedo_g' => '156', 'tara_seco_g' => '140', 'tara_g' => '40', 'metodo_proctor' => 'Proctor Estándar A', 'fecha_ensayo' => '2026-11-22'],
        ], [['Código laboratorio' => 'MS-0003-26']]);
        $this->assertTrue($rProc['tiene_datos']);
        $this->assertGreaterThan(1700, (int)$rProc['filas_matriz'][0]['P.Vol.Seco Máximo (kg/m³)']);
        $this->assertSame('16.0', $rProc['filas_matriz'][0]['Cont. Humedad Óptima ((%)(P/P))']);

        // 4. Cono de Arena RT-FM-55
        $rCono = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_de_compactacion_por_cono_de_arena.md', [[
            'codigo_lab' => 'MC-0001-26', 'arena_empleada_g' => '4500', 'arena_sobrante_g' => '2000',
            'arena_cono_g' => '0', 'pu_arena' => '1250', 'excavado_tara_g' => '4300', 'tara_exc_g' => '300',
            'rec_humedo_g' => '152', 'rec_seco_g' => '140', 'rec_g' => '40', 'pvs_max' => '1800',
            'fecha_ensayo' => '2026-11-22',
        ]], [['Código laboratorio' => 'MC-0001-26']]);
        $this->assertTrue($rCono['tiene_datos']);
        $this->assertGreaterThan(95.0, (float)$rCono['filas_matriz'][0]['Compactación ((%) (P/P))']);

        // 5. Bloques RT-FM-36 (promedia hacia tabla fija)
        $rBloq = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_de_resistencia_de_bloques.md', [[
            'codigo_lab' => 'Bloque 1', 'largo_cm' => '39.0', 'alto_cm' => '19.0', 'ancho_cm' => '15.0',
            'esp_ext_cm' => '2.5', 'esp_int_cm' => '2.5', 'masa_seca_kg' => '11.5', 'masa_sumergida_kg' => '6.5',
            'masa_saturada_kg' => '12.2', 'carga_lb' => '85000', 'fecha_ensayo' => '2026-11-22',
        ]], [
            ['Descripción' => 'Área bruta.', 'Unidad' => 'in²', 'Resultado' => ''],
            ['Descripción' => 'Área neta.', 'Unidad' => 'in²', 'Resultado' => ''],
            ['Descripción' => 'R. Compresión', 'Unidad' => 'lb/in²', 'Resultado' => ''],
            ['Descripción' => 'Absorción', 'Unidad' => '(%)(P/P)', 'Resultado' => ''],
        ]);
        $this->assertTrue($rBloq['tiene_datos']);
        $this->assertGreaterThan(0, (float)$rBloq['filas_matriz'][0]['Resultado']);
        $this->assertGreaterThan(0, (float)$rBloq['filas_matriz'][1]['Resultado']);
        $this->assertGreaterThan(0, (float)$rBloq['filas_matriz'][2]['Resultado']);
        $this->assertGreaterThan(0, (float)$rBloq['filas_matriz'][3]['Resultado']);

        // 6. Equivalente de Arena PE-50
        $rEq = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_de_equivalente_de_arena.md', [[
            'codigo_lab' => 'MS-0004-26', 'arcilla_1' => '5.0', 'arena_1' => '4.2',
        ]], [['Código laboratorio' => 'MS-0004-26']]);
        $this->assertSame('84', $rEq['filas_matriz'][0]['Equivalente de arena. ((%)(P/P))']);

        // 7. Acero Tensión RT-FM-64
        $rAce = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_tension_acero_h37.md', [[
            'codigo_lab' => 'MS-0005-26', 'varilla_num' => '#4 (1/2")', 'diametro_mm' => '12.7',
            'peso_kg' => '0.994', 'largo_cm' => '100', 'l0_cm' => '20', 'lf_cm' => '23',
            'fuerza_max_kn' => '75', 'fluencia_mpa' => '420', 'fecha_ensayo' => '2026-11-22',
        ]], [['Código laboratorio' => 'MS-0005-26']]);
        $this->assertTrue($rAce['tiene_datos']);
        $this->assertSame('15.0', $rAce['filas_matriz'][0]['Elongación (%)']);
        $this->assertGreaterThan(60000, (int)$rAce['filas_matriz'][0]['Fluencia (lbf/in²)']);

        // 8. Colorimetría PE-05
        $rCol = HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica('formato_colorimetria_pe05.md', [[
            'codigo_lab' => 'MS-0006-26', 'placa_gardner' => 'Placa N° 2 (Claro)', 'fecha_ensayo' => '2026-11-22',
        ]], [['Código laboratorio' => 'MS-0006-26']]);
        $this->assertSame('Aceptable (≤ Placa 3)', $rCol['filas_matriz'][0]['Resultado']);
    }

    public function testCapturaMatrizIncluyeSeccionTecnicaParaTodosLosProductos(): void {
        $vista = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('id="seccion-captura-tecnica-lab"', $vista);
        $this->assertStringContainsString('id="tabla-captura-tecnica-lab"', $vista);
        $this->assertStringContainsString('const HOJA_TECNICA_CONFIG =', $vista);
        $this->assertStringContainsString('captura_tecnica: leerFilasCapturaTecnicaActual()', $vista);
        $this->assertStringContainsString('inicializarHojaTecnica();', $vista);
    }
}
