<?php

namespace Tests\Unit;

use Cycsa\Modulos\Contabilidad\Servicios\ExportadorCsv;
use PHPUnit\Framework\TestCase;

class ExportadorCsvTest extends TestCase {
    public function testGeneraCsvConBomUtf8YAcentosCompatiblesConExcel(): void {
        $csv = ExportadorCsv::generar(
            ['Cliente', 'Monto', 'Descripción'],
            [['José Pérez', 1250.50, 'Ensayo, compactación']]
        );

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('José Pérez', $csv);
        $this->assertStringContainsString('"Ensayo, compactación"', $csv);
        $this->assertStringContainsString("\r\n", $csv);
    }

    public function testNeutralizaFormulasInyectadasEnCeldasDeTexto(): void {
        $csv = ExportadorCsv::generar(['Referencia'], [['=HYPERLINK("https://example.test")']]);

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString("\r\n=HYPERLINK", $csv);
    }

    public function testRutasDeExportacionExigenMiddlewareDeContabilidad(): void {
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertNotFalse($rutas);

        foreach (['cxc', 'cxp', 'diario'] as $listado) {
            $patron = sprintf(
                "#/contabilidad/%s/exportar'.*\[AuthMiddleware::class, ContabilidadMiddleware::class\]#",
                $listado
            );
            $this->assertMatchesRegularExpression($patron, $rutas);
        }
    }
}
