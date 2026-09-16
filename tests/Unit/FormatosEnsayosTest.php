<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FormatosEnsayosTest extends TestCase {
    private string $basePath;
    private string $schemaPath;
    private string $detailedSchemaPath;

    protected function setUp(): void {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'ensayos';
        $this->schemaPath = $this->basePath . DIRECTORY_SEPARATOR . 'formatos_schema.json';
        $this->detailedSchemaPath = $this->basePath . DIRECTORY_SEPARATOR . 'formatos_schema_detailed.json';
    }

    /**
     * Tarea 3.1: Validar que formatos_schema.json sea un JSON válido y parseable.
     */
    public function testFormatosSchemaEsJsonValidoYParseable(): void {
        $this->assertFileExists($this->schemaPath, "El archivo formatos_schema.json debe existir en database/ensayos/");
        
        $contenido = file_get_contents($this->schemaPath);
        $this->assertNotEmpty($contenido, "El archivo formatos_schema.json no debe estar vacío.");

        $data = json_decode($contenido, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), "formatos_schema.json debe ser un JSON sintácticamente válido: " . json_last_error_msg());
        $this->assertIsArray($data, "El contenido decodificado debe ser un array asociativo.");
        $this->assertGreaterThanOrEqual(20, count($data), "formatos_schema.json debe registrar al menos 20 definiciones de formato.");
    }

    /**
     * Tarea 3.2: Validar que cada formato en el esquema contenga las llaves requeridas:
     * codigo_formato, ensayo_titulo, norma, columns.
     */
    public function testFormatosSchemaContieneLlavesRequeridas(): void {
        $contenido = file_get_contents($this->schemaPath);
        $data = json_decode($contenido, true);

        $this->assertIsArray($data);

        foreach ($data as $archivo => $formato) {
            $contexto = "Formato clave '{$archivo}' en formatos_schema.json";

            $this->assertArrayHasKey('codigo_formato', $formato, "{$contexto} debe tener la clave 'codigo_formato'.");
            $this->assertIsString($formato['codigo_formato'], "{$contexto} 'codigo_formato' debe ser string.");
            $this->assertMatchesRegularExpression('/CYCSA-RT-FM-/i', $formato['codigo_formato'], "{$contexto} 'codigo_formato' debe seguir el patrón CYCSA-RT-FM-.");

            $this->assertArrayHasKey('ensayo_titulo', $formato, "{$contexto} debe tener la clave 'ensayo_titulo'.");
            $this->assertIsString($formato['ensayo_titulo'], "{$contexto} 'ensayo_titulo' debe ser string.");
            $this->assertNotEmpty(trim($formato['ensayo_titulo']), "{$contexto} 'ensayo_titulo' no debe estar vacío.");

            $this->assertArrayHasKey('norma', $formato, "{$contexto} debe tener la clave 'norma'.");
            $this->assertIsString($formato['norma'], "{$contexto} 'norma' debe ser string.");

            $this->assertArrayHasKey('columns', $formato, "{$contexto} debe tener la clave 'columns'.");
            $this->assertIsArray($formato['columns'], "{$contexto} 'columns' debe ser un array de columnas.");
            $this->assertNotEmpty($formato['columns'], "{$contexto} 'columns' debe definir al menos una columna técnica.");
        }
    }

    /**
     * Tarea 3.3: Verificar que existan al menos 20 plantillas de formato markdown en database/ensayos/.
     */
    public function testExistenAlMenosVeintePlantillasMarkdown(): void {
        $plantillas = glob($this->basePath . DIRECTORY_SEPARATOR . '*.md');
        $this->assertIsArray($plantillas, "La búsqueda de archivos .md debe retornar un array.");
        
        $conteo = count($plantillas);
        $this->assertGreaterThanOrEqual(20, $conteo, "Deben existir al menos 20 plantillas .md en database/ensayos/ (encontradas: {$conteo}).");

        foreach ($plantillas as $archivo) {
            $nombre = basename($archivo);
            $this->assertFileIsReadable($archivo, "La plantilla {$nombre} debe tener permisos de lectura.");
            $this->assertGreaterThan(500, filesize($archivo), "La plantilla {$nombre} debe contener una estructura no trivial (> 500 bytes).");
        }
    }

    /**
     * Tarea 3.4: Verificar que cada plantilla contenga la cabecera normativa con código de control
     * (CYCSA-RT-FM-...), título formal del ensayo y disclaimer de responsabilidad técnica.
     */
    public function testPlantillasMarkdownContienenEstructuraNormativaYDisclaimer(): void {
        $plantillas = glob($this->basePath . DIRECTORY_SEPARATOR . '*.md');
        $this->assertNotEmpty($plantillas);

        foreach ($plantillas as $archivo) {
            $nombre = basename($archivo);
            $contenido = file_get_contents($archivo);

            // 1. Cabecera con título de formato
            $this->assertMatchesRegularExpression(
                '/^#\s+Formato de Ensayo:/m',
                $contenido,
                "La plantilla {$nombre} debe iniciar con cabecera '# Formato de Ensayo:'."
            );

            // 2. Código de control normativo CYCSA-RT-FM-...
            $this->assertMatchesRegularExpression(
                '/CYCSA-RT-FM-[\w\s-]+/i',
                $contenido,
                "La plantilla {$nombre} debe contener un código oficial CYCSA-RT-FM-..."
            );

            // 3. Título formal del documento 'INFORME DE ENSAYO'
            $this->assertStringContainsString(
                'INFORME DE ENSAYO',
                $contenido,
                "La plantilla {$nombre} debe contener el encabezado 'INFORME DE ENSAYO'."
            );

            // 4. Disclaimer de responsabilidad técnica
            $tieneDisclaimer = stripos($contenido, 'responsable únicamente') !== false
                || stripos($contenido, 'Consultoría y Construcción') !== false;
            $this->assertTrue(
                $tieneDisclaimer,
                "La plantilla {$nombre} debe contener el disclaimer técnico de responsabilidad de CYCSA."
            );

            // 5. Cierre o firma institucional
            $tieneCierre = stripos($contenido, 'Gerente General') !== false
                || stripos($contenido, 'Noel Quintana') !== false
                || stripos($contenido, 'Página 1 de 1') !== false;
            $this->assertTrue(
                $tieneCierre,
                "La plantilla {$nombre} debe contener la firma o cierre institucional de control."
            );
        }
    }

    /**
     * Prueba complementaria: Validar esquema detallado si existe.
     */
    public function testFormatosSchemaDetailedEsValido(): void {
        if (!file_exists($this->detailedSchemaPath)) {
            $this->markTestSkipped("formatos_schema_detailed.json no existe en este entorno.");
        }

        $contenido = file_get_contents($this->detailedSchemaPath);
        $data = json_decode($contenido, true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), "formatos_schema_detailed.json debe ser un JSON válido.");
        $this->assertIsArray($data, "El esquema detallado debe ser un array de formatos.");
        $this->assertGreaterThanOrEqual(15, count($data), "El esquema detallado debe contener al menos 15 formatos.");
    }

    /**
     * Valida que cada formato en formatos_schema.json registre su disclaimer oficial
     * y su listado de notas técnicas (ISO 17025).
     */
    public function testFormatosSchemaContieneDisclaimerYNotasOficiales(): void {
        $contenido = file_get_contents($this->schemaPath);
        $data = json_decode($contenido, true);

        $this->assertIsArray($data);

        foreach ($data as $archivo => $formato) {
            $contexto = "Formato clave '{$archivo}' en formatos_schema.json";

            $this->assertArrayHasKey('disclaimer', $formato, "{$contexto} debe tener la clave 'disclaimer'.");
            $this->assertIsString($formato['disclaimer'], "{$contexto} 'disclaimer' debe ser string.");
            $this->assertNotEmpty(trim($formato['disclaimer']), "{$contexto} 'disclaimer' no debe estar vacío.");
            $this->assertStringContainsString('Consultoría y Construcción', $formato['disclaimer'], "{$contexto} debe contener 'Consultoría y Construcción'.");

            $this->assertArrayHasKey('notas', $formato, "{$contexto} debe tener la clave 'notas'.");
            $this->assertIsArray($formato['notas'], "{$contexto} 'notas' debe ser array.");
            $this->assertNotEmpty($formato['notas'], "{$contexto} 'notas' no debe estar vacío.");
        }

        // Validación puntual del formato Compactación Densímetro Nuclear (Captura 2026-09-16 092442)
        $this->assertArrayHasKey('compactacion_densimetro_nuclear.md', $data);
        $densimetro = $data['compactacion_densimetro_nuclear.md'];
        $this->assertCount(2, $densimetro['notas'], "El formato de densímetro nuclear debe contener exactamente 2 notas técnicas.");
        $this->assertStringContainsString('CYCSA-PE-25', $densimetro['notas'][0]);
        $this->assertStringContainsString('TROXLER, Modelo: 3440', $densimetro['notas'][1]);
    }
}
