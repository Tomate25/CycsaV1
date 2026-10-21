<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Modulos\Operaciones\Servicios\MapeadorRtFm22Servicio;

/**
 * Prueba unitaria de Fase 5: Mapeo bidireccional desde las Hojas de Cálculo
 * hacia los informes acreditados/no acreditados RT-FM-22 (Letras A a G),
 * verificando coincidencia exacta de columnas contra database/ensayos/formatos_schema.json.
 */
class MapeoRtFm22Test extends TestCase {

    private MotorHojasCalculoServicio $motor;
    private MapeadorRtFm22Servicio $mapeador;
    private array $formatosSchema;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
        $this->mapeador = new MapeadorRtFm22Servicio($this->motor);
        $rutaSchema = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
        $this->formatosSchema = json_decode((string)file_get_contents($rutaSchema), true) ?: [];
    }

    public function testMapeoDeLos12FormatosConEquivalenteRtFm22(): void {
        $formatosConRtFm22 = [
            'RT-FM-61' => 'A',
            'RT-FM-36' => 'A',
            'RT-FM-57' => 'A',
            'RT-FM-63' => 'A',
            'RT-FM-54' => 'B',
            'RT-FM-55' => 'B',
            'RT-FM-27' => 'C',
            'RT-FM-59' => 'D',
            'RT-FM-38' => 'E',
            'RT-FM-25' => 'F',
            'RT-FM-42' => 'F',
            'RT-FM-35' => 'G',
        ];

        foreach ($formatosConRtFm22 as $codHc => $letraEsperada) {
            $spec = $this->motor->cargarEspecificacion($codHc);
            $entradas = [$spec['caso_prueba'][0]['entradas']];
            $evalConjunto = $this->motor->evaluarConjunto($codHc, $entradas);

            $mapeo = $this->mapeador->mapearHaciaInforme($codHc, $evalConjunto, [
                'codigo_muestra' => $spec['caso_prueba'][0]['nombre'] ?? 'MS-0001-26',
                'nombre_muestra' => 'Muestra Oficial ' . $codHc,
                'fecha_moldeo' => '2026-10-01',
                'fecha_ruptura' => '2026-10-29',
                'fecha_ensayo' => '2026-10-29',
                'edad_dias' => 28,
            ]);

            $this->assertFalse($mapeo['es_reporte_nuevo'], "{$codHc} debe mapear a RT-FM-22 existente");
            $this->assertSame($letraEsperada, $mapeo['letra_rt_fm_22'], "Letra RT-FM-22 incorrecta para {$codHc}");
            $archivoMd = $mapeo['archivo_markdown'];
            $this->assertArrayHasKey($archivoMd, $this->formatosSchema, "El archivo markdown {$archivoMd} debe existir en formatos_schema.json");

            $columnasEsperadas = $this->formatosSchema[$archivoMd]['columns'] ?? [];
            $filasMapeadas = $mapeo['resultados_json']['filas'] ?? [];
            $this->assertNotEmpty($filasMapeadas, "Las filas mapeadas para {$codHc} no pueden estar vacías");

            foreach ($columnasEsperadas as $col) {
                $this->assertArrayHasKey(
                    $col,
                    $filasMapeadas[0],
                    "La fila mapeada de {$codHc} hacia {$archivoMd} debe incluir la columna exacta '{$col}'"
                );
            }
        }
    }

    public function testLos21FormatosSchemaJsonYLetraHPermanecenIntactos(): void {
        $this->assertCount(25, $this->formatosSchema);
        $fuentesUnicas = array_unique(array_column($this->formatosSchema, 'docx_source'));
        $this->assertGreaterThanOrEqual(17, count($fuentesUnicas));
        foreach ($this->motor->listarCodigosFormatos() as $codHc) {
            $destino = $this->mapeador->obtenerDestinoFormato($codHc);
            $this->assertNotSame('H', $destino['letra'], "Ningún formato HC debe alterar ni sobrescribir la letra H");
        }
    }
}
