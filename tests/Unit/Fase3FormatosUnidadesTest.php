<?php

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;
use PHPUnit\Framework\TestCase;

class Fase3FormatosUnidadesTest extends TestCase {
    private array $schema;

    protected function setUp(): void {
        parent::setUp();
        $ruta = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
        $this->schema = json_decode((string)file_get_contents($ruta), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'formatos_schema.json debe ser JSON válido');
    }

    public function testUnidadesCorregidasEnSchemaYArchivosMarkdown(): void {
        // 3.1: P.V.S.Sitio (kg/m³) en lugar de kg/cm²
        foreach (['compactacion_densimetro_nuclear.md', 'compactación_densimetro_nuclear.md'] as $claveDens) {
            $this->assertArrayHasKey($claveDens, $this->schema);
            $this->assertContains('P.V.S.Sitio (kg/m³)', $this->schema[$claveDens]['columns']);
            $this->assertNotContains('P.V.S.Sitio (kg/cm²)', $this->schema[$claveDens]['columns']);
            $this->assertArrayHasKey('P.V.S.Sitio (kg/m³)', $this->schema[$claveDens]['column_methods']);
            $this->assertArrayNotHasKey('P.V.S.Sitio (kg/cm²)', $this->schema[$claveDens]['column_methods']);
        }
        $mdDens = file_get_contents(dirname(__DIR__, 2) . '/database/ensayos/compactacion_densimetro_nuclear.md');
        $this->assertStringContainsString('P.V.S.Sitio (kg/m³)', $mdDens);
        $this->assertStringNotContainsString('P.V.S.Sitio (kg/cm²)', $mdDens);

        // Módulo de finura adimensional (sin (%) (P/P))
        $this->assertContains('Módulo de finura', $this->schema['ensayos_varios.md']['columns']);
        $this->assertNotContains('Módulo de finura ((%) (P/P))', $this->schema['ensayos_varios.md']['columns']);
        $mdVarios = file_get_contents(dirname(__DIR__, 2) . '/database/ensayos/ensayos_varios.md');
        $this->assertStringContainsString('| Módulo de finura |', $mdVarios);
        $this->assertStringNotContainsString('Módulo de finura ((%) (P/P))', $mdVarios);

        // Mortero: 50 mm en lugar de 50 nm
        $this->assertStringContainsString('50 mm', $this->schema['resistencia_de_mortero.md']['ensayo_titulo']);
        $this->assertStringNotContainsString('50 nm', $this->schema['resistencia_de_mortero.md']['ensayo_titulo']);
        $mdMortero = file_get_contents(dirname(__DIR__, 2) . '/database/ensayos/resistencia_de_mortero.md');
        $this->assertStringContainsString('50 mm', $mdMortero);
        $this->assertStringNotContainsString('50 nm', $mdMortero);

        // Granulometría: Tamiz 3", sin Atterberg en agregados y unidad (g) en lugar de (gr)
        $capturaHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('P. Retenido parcial (g)', $capturaHtml);
        $this->assertStringContainsString('"Malla": "3\\""', $capturaHtml);
        $posAgregados = strpos($capturaHtml, '"granulomnetria_de_agregados.md": [');
        $posBloques = strpos($capturaHtml, '"formato_de_resistencia_de_bloques.md": [');
        $bloqueAgregados = substr($capturaHtml, $posAgregados, $posBloques - $posAgregados);
        $this->assertStringNotContainsString('Límite Líquido', $bloqueAgregados);
        $this->assertStringNotContainsString('Límite Plástico', $bloqueAgregados);
    }

    public function testSeparacionPe57Pe58Pe05(): void {
        // Mortero -> PE-57
        $this->assertStringContainsString('CYCSA-PE-57', $this->schema['resistencia_de_mortero.md']['ensayo_titulo']);

        // Suelo-Cemento -> PE-58 (ASTM D1633-17), ya NO Colorimetría PE-05
        $sc = $this->schema['formato_de_suelo_cemento.md'];
        $this->assertStringContainsString('CYCSA-PE-58', $sc['ensayo_titulo']);
        $this->assertStringContainsString('D1633', $sc['norma']);
        $this->assertContains('R. compresión. (kg/cm²)', $sc['columns']);
        $this->assertNotContains('Color', $sc['columns']);

        $mdSc = file_get_contents(dirname(__DIR__, 2) . '/database/ensayos/formato_de_suelo_cemento.md');
        $this->assertStringContainsString('CYCSA-PE-58', $mdSc);
        $this->assertStringNotContainsString('CYCSA-PE-05', $mdSc);

        // Colorimetría separada -> PE-05
        $this->assertArrayHasKey('formato_colorimetria_pe05.md', $this->schema);
        $col = $this->schema['formato_colorimetria_pe05.md'];
        $this->assertSame('CYCSA-RT-FM-22 G', $col['codigo_formato']);
        $this->assertStringContainsString('CYCSA-PE-05', $col['ensayo_titulo']);
    }

    public function testNuevosFormatosHyFEnSchema(): void {
        $esperados = [
            'formato_cbr_h07.md' => ['codigo' => 'CYCSA-RT-FM-22 H-7', 'pe' => 'CYCSA-PE-11'],
            'formato_ge_suelos_h08.md' => ['codigo' => 'CYCSA-RT-FM-22 H-8', 'pe' => 'CYCSA-PE-14'],
            'formato_doblado_acero_h20.md' => ['codigo' => 'CYCSA-RT-FM-22 H-20', 'pe' => 'CYCSA-PE-38'],
            'formato_tension_acero_h37.md' => ['codigo' => 'CYCSA-RT-FM-22 H-37', 'pe' => 'CYCSA-PE-61'],
            'formato_compresion_roca_h22.md' => ['codigo' => 'CYCSA-RT-FM-22 H-22', 'pe' => 'CYCSA-PE-40'],
            'formato_sanidad_intemperismo_f.md' => ['codigo' => 'CYCSA-RT-FM-22 F', 'pe' => 'CYCSA-PE-52'],
            'formato_colorimetria_pe05.md' => ['codigo' => 'CYCSA-RT-FM-22 G', 'pe' => 'CYCSA-PE-05'],
        ];

        foreach ($esperados as $archivo => $metaEsp) {
            $this->assertArrayHasKey($archivo, $this->schema, "Falta formato {$archivo}");
            $entry = $this->schema[$archivo];
            $this->assertSame($metaEsp['codigo'], $entry['codigo_formato']);
            $this->assertStringContainsString($metaEsp['pe'], $entry['ensayo_titulo']);
            $this->assertNotEmpty($entry['columns']);
            $this->assertNotEmpty($entry['disclaimer']);
            $this->assertNotEmpty($entry['notas']);
        }
    }

    public function testSembradorPlantillasKeywordsCorregidas(): void {
        $this->assertContains('CYCSA-PE-02', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-35'));
        $this->assertContains('CYCSA-PE-30', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-38'));
        $this->assertContains('Flexión', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-38'));
        $this->assertContains('CYCSA-PE-17', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-42'));
        $this->assertContains('CYCSA-PE-46', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-54'));
        $this->assertContains('CYCSA-PE-52', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-58'));
        $this->assertContains('CYCSA-PE-12', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-59'));
        $this->assertContains('Esclerómetro', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-59'));
        $this->assertContains('Adoquín', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-63'));
        $this->assertContains('CYCSA-PE-61', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-64'));
        $this->assertContains('CYCSA-PE-57', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-57'));
        $this->assertContains('Bloque', SembradorPlantillasHcServicio::obtenerKeywordsProducto('RT-FM-36'));
    }

    public function testResolverMetadatosEnsayoNoFabricaTroxlerCuandoEstaVacio(): void {
        $metaConcreto = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Resistencia de Concreto',
            'archivo_markdown' => 'resistencia_de_concreto.md',
        ], [], []);

        $this->assertSame('', $metaConcreto['equipo_marca']);
        $this->assertSame('', $metaConcreto['equipo_modelo']);
        $this->assertSame('', $metaConcreto['equipo_serie']);
        $this->assertSame('', $metaConcreto['equipo_codigo']);
        $this->assertSame('', $metaConcreto['nota_equipo_densimetro']);

        $metaDensVacio = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Compactación Densímetro Nuclear',
            'archivo_markdown' => 'compactacion_densimetro_nuclear.md',
        ], [], []);
        $this->assertSame('', $metaDensVacio['equipo_serie']);
        $this->assertSame('', $metaDensVacio['nota_equipo_densimetro']);
    }
}
