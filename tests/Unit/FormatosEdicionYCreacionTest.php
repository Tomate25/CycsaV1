<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

class FormatosEdicionYCreacionTest extends TestCase {

    private ConfiguracionModelo $modelo;
    private PDO $db;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->modelo = new ConfiguracionModelo();
    }

    public function testObtenerPlantillasEnsayosIncluyeCamposCompletos(): void {
        $plantillas = $this->modelo->obtenerPlantillasEnsayos();
        $this->assertIsArray($plantillas);
        $this->assertGreaterThanOrEqual(21, count($plantillas), "Debe haber al menos 21 formatos configurados.");

        $primerFormato = $plantillas[0];
        $this->assertArrayHasKey('id', $primerFormato);
        $this->assertArrayHasKey('nombre', $primerFormato);
        $this->assertArrayHasKey('codigo_vigente', $primerFormato);
        $this->assertArrayHasKey('orientacion', $primerFormato);
        $this->assertArrayHasKey('columnas_count', $primerFormato);
        $this->assertArrayHasKey('total_productos', $primerFormato);
    }

    public function testObtenerPlantillaPorIdRetornaConfiguracionYProductos(): void {
        $plantilla = $this->modelo->obtenerPlantillaPorId(1);
        $this->assertNotNull($plantilla);
        $this->assertSame(1, (int)$plantilla['id']);
        $this->assertArrayHasKey('configuracion', $plantilla);
        $this->assertIsArray($plantilla['configuracion']);
        $this->assertArrayHasKey('columns', $plantilla['configuracion']);
        $this->assertArrayHasKey('productos_vinculados', $plantilla);
    }

    public function testCrearYModificarNuevoFormatoConSincronizacionTotal(): void {
        $nombreNuevo = 'Formato Prueba Test Automatizado ' . time();
        $config = [
            'docx_source' => '',
            'codigo_formato' => 'CYCSA-RT-FM-22 TEST V1R1',
            'version_documento' => 'V1R1',
            'titulo_informe' => 'INFORME DE ENSAYO DE PRUEBA',
            'subtitulo_laboratorio' => 'Laboratorio de Ensayos y Control de Calidad',
            'ensayo_titulo' => 'Ensayo Especial de Prueba Unitaria',
            'metodo_muestreo' => 'DE-ASTM D0000',
            'tipo_muestra' => 'Probeta Especial',
            'norma' => 'ASTM TEST-2026',
            'orientacion' => 'vertical',
            'columns' => ['Código laboratorio', 'Nombre muestra', 'Lectura Inicial', 'Resultado Final'],
            'column_methods' => [
                'Lectura Inicial' => 'CYCSA-PE-TEST-01',
                'Resultado Final' => 'CYCSA-PE-TEST-02'
            ],
            'column_aliases' => [],
            'fixed_rows' => [],
            'disclaimer' => 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.  ** Información Proporcionada por el cliente.',
            'notas' => ['Nota 1: Prueba de calibración automática.'],
            'firmante_nombre' => 'Ing. Noel Quintana Lira',
            'firmante_cargo' => 'Gerente General'
        ];

        // 1. Crear nuevo formato
        $nuevoId = $this->modelo->crearPlantillaEnsayo($nombreNuevo, $config, []);
        $this->assertGreaterThan(0, $nuevoId, "El formato debe crearse con un ID autoincremental positivo.");

        try {
            // Verificar persistencia en base de datos
            $formatoCreado = $this->modelo->obtenerPlantillaPorId($nuevoId);
            $this->assertNotNull($formatoCreado);
            $this->assertSame($nombreNuevo, $formatoCreado['nombre']);
            $this->assertSame('CYCSA-RT-FM-22 TEST V1R1', $formatoCreado['codigo_formato']);

            // Verificar esquema dinámico desde la base de datos
            $archivoMd = $formatoCreado['archivo_markdown'];
            $schemaInfo = obtenerEsquemaPlantillaEnsayo($archivoMd, $nuevoId);
            $this->assertSame('vertical', $schemaInfo['orientacion']);
            $this->assertCount(4, $schemaInfo['columns']);
            $this->assertSame('CYCSA-PE-TEST-01', $schemaInfo['column_methods']['Lectura Inicial']);

            // 2. Modificar formato (reordenar columnas y cambiar orientación)
            $configModificada = $config;
            $configModificada['orientacion'] = 'horizontal';
            $configModificada['version_documento'] = 'V1R2';
            $configModificada['columns'] = ['Código laboratorio', 'Nombre muestra', 'Resultado Final', 'Lectura Inicial', 'Observación Extra'];
            $configModificada['column_methods']['Observación Extra'] = 'CYCSA-PE-TEST-03';

            $nombreModificado = $nombreNuevo . ' Modificado';
            $ok = $this->modelo->guardarPlantillaEnsayo($nuevoId, $configModificada, $nombreModificado, []);
            $this->assertTrue($ok);

            $formatoModificado = $this->modelo->obtenerPlantillaPorId($nuevoId);
            $this->assertSame($nombreModificado, $formatoModificado['nombre']);
            $this->assertSame('V1R2', $formatoModificado['version_formato']);
            $this->assertSame('horizontal', $formatoModificado['configuracion']['orientacion']);
            $this->assertCount(5, $formatoModificado['configuracion']['columns']);

            // 3. Probar duplicación de formato
            $duplicadoId = $this->modelo->duplicarPlantillaEnsayo($nuevoId);
            $this->assertGreaterThan($nuevoId, $duplicadoId);

            $formatoDuplicado = $this->modelo->obtenerPlantillaPorId($duplicadoId);
            $this->assertStringContainsString('(Copia)', $formatoDuplicado['nombre']);
            $this->assertSame('horizontal', $formatoDuplicado['configuracion']['orientacion']);

            // Limpieza del duplicado
            if ($duplicadoId > 0) {
                $archivoDuplicado = $formatoDuplicado['archivo_markdown'];
                $this->db->exec("DELETE FROM formatos_ensayos WHERE id = {$duplicadoId}");
                $rutaDupMd = dirname(__DIR__, 2) . '/database/ensayos/' . $archivoDuplicado;
                if (file_exists($rutaDupMd)) @unlink($rutaDupMd);
            }
        } finally {
            // Limpieza del formato de prueba
            if ($nuevoId > 0) {
                $formatoLimpiar = $this->modelo->obtenerPlantillaPorId($nuevoId);
                $archivoLimpiar = $formatoLimpiar['archivo_markdown'] ?? '';
                $this->db->exec("DELETE FROM formatos_ensayos WHERE id = {$nuevoId}");
                $rutaMd = dirname(__DIR__, 2) . '/database/ensayos/' . $archivoLimpiar;
                if (!empty($archivoLimpiar) && file_exists($rutaMd)) @unlink($rutaMd);

                // Remover clave de formatos_schema.json si quedó
                $schemaFile = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
                if (!empty($archivoLimpiar) && file_exists($schemaFile)) {
                    $schemas = json_decode(file_get_contents($schemaFile), true) ?: [];
                    if (isset($schemas[$archivoLimpiar])) {
                        unset($schemas[$archivoLimpiar]);
                        file_put_contents($schemaFile, json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
                $detailedFile = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema_detailed.json';
                if (!empty($archivoLimpiar) && file_exists($detailedFile)) {
                    $detailed = json_decode(file_get_contents($detailedFile), true) ?: [];
                    if (isset($detailed[$archivoLimpiar])) {
                        unset($detailed[$archivoLimpiar]);
                        file_put_contents($detailedFile, json_encode($detailed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
            }
        }
    }

    public function testRenderizarPdfConFormatoPersonalizado(): void {
        // Verificar que generarMatrizTecnicaPDF compila correctamente con formato y metadatos
        $plantilla = $this->modelo->obtenerPlantillaPorId(1);
        $this->assertNotNull($plantilla);

        $cols = $plantilla['configuracion']['columns'] ?? ['Código laboratorio', 'Resultado'];
        $muestras = [
            array_fill_keys($cols, '100'),
            array_fill_keys($cols, '200')
        ];

        $detalle = [
            'formato_id' => 1,
            'archivo_markdown' => $plantilla['archivo_markdown'],
            'codigo_os' => 'OS-2026-UNIT',
            'descripcion_ensayo' => $plantilla['nombre'],
            'norma_astm' => $plantilla['configuracion']['norma'] ?? 'ASTM',
            'orientacion' => $plantilla['configuracion']['orientacion'] ?? 'horizontal',
            'metadatos' => json_encode([
                'cliente_nombre' => 'CONSTRUCTORA PRUEBA S.A.',
                'proyecto' => 'PROYECTO EDIFICIO CENTRAL',
                'cliente_direccion' => 'Carretera a Masaya',
                'ubicacion' => 'Tramo 2',
                'fecha_muestreo' => '2026-11-08',
                'fecha_ingreso' => '2026-11-08',
                'fecha_ejecucion' => '2026-11-08',
                'fecha_emision' => '2026-11-08',
                'tipo_muestra' => 'Suelo',
                'procedimiento_muestreo' => 'DE-ASTM',
                'ensayo_realizado' => $plantilla['nombre'],
                'muestra_tomada_por' => 'CYCSA Lab',
                'ensayado_por' => 'Ingeniero Responsable',
                'metodo_muestreo' => 'DE-ASTM',
            ])
        ];

        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $pdf = generarMatrizTecnicaPDF($detalle, $muestras, $cols);

        $this->assertIsString($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
