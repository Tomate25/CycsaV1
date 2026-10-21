<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Prueba de Regresión de los 21 Formatos Antiguos (RT-FM-22 A-H).
 * Garantiza que ninguna tabla ni plantilla legacy fue alterada o eliminada.
 */
class FormatosLegacyRegresionTest extends TestCase {

    private PDO $db;
    private string $schemaPath;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->schemaPath = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
    }

    public function testVeintiunFormatosLegacyEnJsonPermanecenIntactos(): void {
        $this->assertFileExists($this->schemaPath);
        $schema = json_decode(file_get_contents($this->schemaPath), true);
        $this->assertIsArray($schema);
        $this->assertGreaterThanOrEqual(21, count($schema), 'Deben permanecer intactos al menos los 21 formatos en database/ensayos/formatos_schema.json');

        $archivosMd = glob(dirname(__DIR__, 2) . '/database/ensayos/*.md') ?: [];
        $this->assertGreaterThanOrEqual(21, count($archivosMd), 'Deben existir al menos los 21 archivos .md de formatos legacy');

        foreach ($archivosMd as $rutaMd) {
            $archivo = basename($rutaMd);
            $this->assertArrayHasKey($archivo, $schema, "El formato legacy {$archivo} debe seguir existiendo en formatos_schema.json");
            $fmt = $schema[$archivo];
            $this->assertNotEmpty($fmt['codigo_formato']);
            $this->assertNotEmpty($fmt['columns']);
        }
    }

    public function testFormatosEnsayosYPlantillasLegacyEnBaseDeDatosIntactas(): void {
        $countFormatos = (int)$this->db->query("SELECT COUNT(*) FROM formatos_ensayos")->fetchColumn();
        $this->assertGreaterThanOrEqual(21, $countFormatos, 'La tabla formatos_ensayos debe conservar los 21 formatos originales');

        $countPlantillasLegacy = (int)$this->db->query("SELECT COUNT(*) FROM plantillas WHERE codigo LIKE 'PL-CYCSA-RT-FM-22%'")->fetchColumn();
        $this->assertGreaterThanOrEqual(21, $countPlantillasLegacy, 'Las 21 plantillas migradas de RT-FM-22 deben permanecer intactas en la tabla plantillas');
    }
}
