<?php

namespace Tests\Unit;

use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;
use Cycsa\Nucleo\Conexion;
use PHPUnit\Framework\TestCase;
use PDO;

class PlantillasEnsayosTest extends TestCase {
    public function testMigracionTieneLosVeintiunFormatosConfigurados(): void {
        $plantillas = (new ConfiguracionModelo())->obtenerPlantillasEnsayos();
        $this->assertCount(21, $plantillas);
        foreach ($plantillas as $plantilla) {
            $this->assertNotEmpty($plantilla['configuracion_json'], $plantilla['nombre']);
            $config = json_decode($plantilla['configuracion_json'], true);
            $this->assertIsArray($config);
            $this->assertNotEmpty($config['columns'] ?? []);
            $this->assertNotEmpty($config['firmante_nombre'] ?? '');
        }
    }

    public function testCambioDePlantillaSePropagaACapturaImpresionYPdf(): void {
        $db = Conexion::obtenerInstancia();
        $formato = $db->query("SELECT id, archivo_markdown FROM formatos_ensayos WHERE archivo_markdown = 'resistencia_de_concreto.md' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($formato);
        $modelo = new ConfiguracionModelo();
        $original = $modelo->obtenerPlantillaPorId((int)$formato['id']);
        $this->assertNotEmpty($original['configuracion']);

        $db->beginTransaction();
        try {
            $config = $original['configuracion'];
            $config['codigo_formato'] = 'CYCSA-RT-FM-22 A V2R1';
            $config['version_documento'] = 'V2R1';
            $config['titulo_informe'] = 'INFORME DE ENSAYO ACTUALIZADO';
            $config['subtitulo_laboratorio'] = 'Laboratorio CYCSA de Prueba';
            $config['notas'][] = 'Equipo de prueba EQ-999';
            $columnaOriginal = $config['columns'][2];
            $config['columns'][2] = 'Lectura actualizada';
            $config['column_aliases']['Lectura actualizada'] = $columnaOriginal;
            $config['column_methods']['Lectura actualizada'] = $config['column_methods'][$columnaOriginal] ?? '';
            $config['columns'][] = 'Resultado adicional';
            $config['column_methods']['Resultado adicional'] = 'CYCSA-PE-99';
            $config['firmante_nombre'] = 'Ing. Firma de Prueba';
            $config['firmante_cargo'] = 'Dirección Técnica';
            $this->assertTrue($modelo->guardarPlantillaEnsayo((int)$formato['id'], $config));

            $esquema = obtenerEsquemaPlantillaEnsayo($formato['archivo_markdown'], (int)$formato['id']);
            $this->assertSame($config['codigo_formato'], $esquema['codigo_formato']);
            $this->assertContains('Resultado adicional', $esquema['columns']);
            $this->assertSame($columnaOriginal, $esquema['column_aliases']['Lectura actualizada']);
            $this->assertContains('Equipo de prueba EQ-999', $esquema['notas']);

            $detalle = [
                'id' => 999,
                'formato_id' => (int)$formato['id'],
                'archivo_markdown' => $formato['archivo_markdown'],
                'codigo_os' => 'OS-PRUEBA-001',
                'descripcion_ensayo' => 'Resistencia de Concreto',
                'norma_astm' => 'ASTM C39',
                'resultados_json' => json_encode(['filas' => [[$columnaOriginal => '321', 'Resultado adicional' => '42']]]),
            ];
            $columnas = $esquema['columns'];
            $muestrasSeteadas = [];
            $metadatos = resolverMetadatosEnsayo($detalle, $esquema);
            $formatosSchemaJson = json_encode([$formato['archivo_markdown'] => $esquema], JSON_UNESCAPED_UNICODE);
            $_SESSION = ['usuario_rol' => 1, 'csrf_token' => 'token-prueba'];

            ob_start();
            include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
            $captura = ob_get_clean();
            $this->assertStringContainsString('CYCSA-RT-FM-22 A V2R1', $captura);
            $this->assertStringContainsString('Resultado adicional', $captura);
            $this->assertStringContainsString('Lectura actualizada', $captura);
            $this->assertStringContainsString('Equipo de prueba EQ-999', $captura);

            ob_start();
            include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php';
            $impresion = ob_get_clean();
            foreach (['CYCSA-RT-FM-22 A V2R1', 'Resultado adicional', 'Lectura actualizada', '321', 'Equipo de prueba EQ-999', 'Ing. Firma de Prueba', 'INFORME DE ENSAYO ACTUALIZADO'] as $texto) {
                $this->assertStringContainsString($texto, $impresion);
            }
            $pdf = generarMatrizTecnicaPDF($detalle);
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertGreaterThan(5000, strlen($pdf));

            $this->assertTrue($modelo->restablecerPlantillaOriginal((int)$formato['id']));
            $restablecida = obtenerEsquemaPlantillaEnsayo($formato['archivo_markdown'], (int)$formato['id']);
            $this->assertSame($original['configuracion']['codigo_formato'], $restablecida['codigo_formato']);
            $this->assertNotContains('Equipo de prueba EQ-999', $restablecida['notas']);
        } finally {
            if ($db->inTransaction()) $db->rollBack();
        }
    }

    public function testVistaPlantillasIncluyeVisualizadorDeFormulas(): void {
        $plantillas = (new ConfiguracionModelo())->obtenerPlantillasEnsayos();
        $_SESSION = ['usuario_rol' => 1, 'csrf_token' => 'token-test'];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Configuracion/Vistas/plantillas_ensayos.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('Fórmulas y cálculos', $html);
        $this->assertStringContainsString('CATALOGO_FORMULAS', $html);
        $this->assertStringContainsString('actualizarVisualizacionFormulas', $html);
        $this->assertStringContainsString('col-pill calc', $html);
        $this->assertStringContainsString('ASTM C39', $html);
    }
}
