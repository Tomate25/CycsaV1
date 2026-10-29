<?php

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * CYCSA-RT-FM-40 B: ítems adicionales viven en checklist_json.custom_seg / custom_mue
 * y no reemplazan las filas oficiales seg_N_* / mue_N_*.
 */
class ChecklistRtFm40ExpandibleTest extends TestCase {

    private const VISTA_PROGRAMAR = '/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php';
    private const VISTA_IMPRIMIR = '/app/Modulos/OrdenesServicio/Vistas/imprimir_checklist.php';
    private const CONTROLADOR = '/app/Modulos/OrdenesServicio/Controladores/OrdenesServicioControlador.php';

    public function testListasOficialesCoincidenEntreArchivosYConteo(): void {
        $programar = $this->leerFuente(self::VISTA_PROGRAMAR);
        $imprimir = $this->leerFuente(self::VISTA_IMPRIMIR);

        $segProgramar = $this->extraerLista($programar, 'itemsSeguridad');
        $mueProgramar = $this->extraerLista($programar, 'itemsMuestras');
        $segImprimir = $this->extraerLista($imprimir, 'itemsSeguridad');
        $mueImprimir = $this->extraerLista($imprimir, 'itemsMuestras');

        $this->assertSame($segProgramar, $segImprimir, 'La lista oficial de seguridad debe ser idéntica en captura e impresión.');
        $this->assertSame($mueProgramar, $mueImprimir, 'La lista oficial de muestras debe ser idéntica en captura e impresión.');
        $this->assertCount(13, $segProgramar);
        $this->assertCount(16, $mueProgramar);
        $this->assertSame('Mascarilla', $segProgramar[0]);
        $this->assertSame('Cinta luminosa de vehículo', $segProgramar[12]);
        $this->assertSame('Densímetro Nuclear', $mueProgramar[0]);
        $this->assertSame('Proctor a utilizar', $mueProgramar[15]);
        $this->assertSame('Mecate para aseguran caja', $mueProgramar[8]);
    }

    public function testJsonRoundtripCustomNoSobrescribeSeg0NiMue0(): void {
        $oficial = [
            'seg_0_cant_a' => '2',
            'seg_0_chk_a' => '1',
            'seg_0_cant_p' => '2',
            'seg_0_chk_p' => '1',
            'mue_0_cant_a' => '1',
            'mue_0_chk_a' => '1',
            'mue_0_cant_p' => '1',
            'mue_0_chk_p' => '1',
        ];
        $customSeg = [
            'nombre' => 'Linterna frontal',
            'cant_a' => '1',
            'chk_a' => '1',
            'cant_p' => '1',
            'chk_p' => '1',
        ];
        $customMue = [
            'nombre' => 'Cinta métrica',
            'cant_a' => '3',
            'chk_a' => '1',
            'cant_p' => '',
            'chk_p' => '',
        ];

        $chk = $oficial + [
            'custom_seg' => [$customSeg],
            'custom_mue' => [$customMue],
        ];

        $controlador = $this->leerFuente(self::CONTROLADOR);
        $this->assertStringContainsString("\$checklist = \$datos['chk'] ?? [];", $controlador);
        $this->assertStringContainsString(
            'json_encode($checklist, JSON_UNESCAPED_UNICODE)',
            $controlador
        );

        // Misma codificación que OrdenesServicioControlador::guardarProgramacionMuestreo.
        $datos = ['chk' => $chk];
        $checklist = $datos['chk'] ?? [];
        $checklistJson = !empty($checklist) ? json_encode($checklist, JSON_UNESCAPED_UNICODE) : null;

        $this->assertIsString($checklistJson);
        $this->assertStringContainsString('Linterna frontal', $checklistJson);
        $this->assertStringContainsString('Cinta métrica', $checklistJson);

        $restaurado = json_decode($checklistJson, true);
        $this->assertIsArray($restaurado);

        foreach ($oficial as $clave => $valor) {
            $this->assertSame($valor, $restaurado[$clave], "La clave oficial {$clave} no debe cambiar.");
        }

        $this->assertCount(1, $restaurado['custom_seg']);
        $this->assertCount(1, $restaurado['custom_mue']);
        $this->assertSame($customSeg, $restaurado['custom_seg'][0]);
        $this->assertSame($customMue, $restaurado['custom_mue'][0]);

        $this->assertNotSame($restaurado['custom_seg'][0]['nombre'], $restaurado['seg_0_cant_a']);
        $this->assertNotSame($restaurado['custom_mue'][0]['nombre'], $restaurado['mue_0_cant_a']);
        $this->assertIsString($restaurado['seg_0_cant_a']);
        $this->assertIsString($restaurado['mue_0_cant_a']);
        $this->assertArrayNotHasKey('seg_0', $restaurado);
        $this->assertArrayNotHasKey('mue_0', $restaurado);
    }

    public function testVistaCargaCustomSinQuitarFilasOficialesYLaImpresionLasIncluye(): void {
        $programar = $this->leerFuente(self::VISTA_PROGRAMAR);
        $itemsSeguridad = $this->extraerLista($programar, 'itemsSeguridad');
        $itemsMuestras = $this->extraerLista($programar, 'itemsMuestras');

        $checklist = [
            'seg_0_cant_a' => '2',
            'seg_0_chk_a' => '1',
            'mue_0_cant_a' => '1',
            'mue_0_chk_a' => '1',
            'custom_seg' => [[
                'nombre' => 'Linterna frontal',
                'cant_a' => '1',
                'chk_a' => '1',
                'cant_p' => '1',
                'chk_p' => '1',
            ]],
            'custom_mue' => [[
                'nombre' => 'Cinta métrica',
                'cant_a' => '3',
                'chk_a' => '1',
                'cant_p' => '',
                'chk_p' => '',
            ]],
        ];

        $html = $this->renderProgramar($checklist);
        $xpath = $this->xpath($html);

        $filasSegOficiales = $xpath->query('//*[@id="tbody-checklist-seg"]/tr[not(contains(concat(" ", normalize-space(@class), " "), " chk-custom-row "))]');
        $filasMueOficiales = $xpath->query('//*[@id="tbody-checklist-mue"]/tr[not(contains(concat(" ", normalize-space(@class), " "), " chk-custom-row "))]');
        $filasSegCustom = $xpath->query('//*[@id="tbody-checklist-seg"]/tr[contains(concat(" ", normalize-space(@class), " "), " chk-custom-row ")]');
        $filasMueCustom = $xpath->query('//*[@id="tbody-checklist-mue"]/tr[contains(concat(" ", normalize-space(@class), " "), " chk-custom-row ")]');

        $this->assertSame(count($itemsSeguridad), $filasSegOficiales->length, 'El conteo oficial de seguridad debe coincidir con el archivo.');
        $this->assertSame(count($itemsMuestras), $filasMueOficiales->length, 'El conteo oficial de muestras debe coincidir con el archivo.');
        $this->assertSame(1, $filasSegCustom->length);
        $this->assertSame(1, $filasMueCustom->length);

        $this->assertSame($itemsSeguridad, $this->textosPrimeraCelda($filasSegOficiales));
        $this->assertSame($itemsMuestras, $this->textosPrimeraCelda($filasMueOficiales));

        $quitarOficial = $xpath->query(
            '//*[@id="tbody-checklist-seg" or @id="tbody-checklist-mue"]/tr[not(contains(concat(" ", normalize-space(@class), " "), " chk-custom-row "))]//button[contains(@class,"btn-quitar-custom")]'
        );
        $quitarCustom = $xpath->query('//tr[contains(concat(" ", normalize-space(@class), " "), " chk-custom-row ")]//button[contains(@class,"btn-quitar-custom")]');
        $this->assertSame(0, $quitarOficial->length);
        $this->assertSame(2, $quitarCustom->length);

        $this->assertSame('2', $this->valorInput($xpath, 'chk[seg_0_cant_a]'));
        $this->assertSame('1', $this->valorInput($xpath, 'chk[mue_0_cant_a]'));
        $this->assertSame('Linterna frontal', $this->valorInput($xpath, 'chk[custom_seg][0][nombre]'));
        $this->assertSame('Cinta métrica', $this->valorInput($xpath, 'chk[custom_mue][0][nombre]'));
        $this->assertSame('1', $this->valorInput($xpath, 'chk[custom_seg][0][cant_a]'));
        $this->assertSame('3', $this->valorInput($xpath, 'chk[custom_mue][0][cant_a]'));
        $this->assertNotSame(
            $this->valorInput($xpath, 'chk[custom_seg][0][nombre]'),
            $this->valorInput($xpath, 'chk[seg_0_cant_a]')
        );

        $botonesAgregar = $xpath->query('//button[contains(., "+ Agregar ítem")]');
        $this->assertSame(2, $botonesAgregar->length);
        $this->assertStringContainsString("agregarItemChecklist('seg')", $html);
        $this->assertStringContainsString("agregarItemChecklist('mue')", $html);

        $impresion = $this->renderImprimir($checklist);
        $xpathPrint = $this->xpath($impresion);
        $tablas = $xpathPrint->query('//table[contains(concat(" ", normalize-space(@class), " "), " table-items ")]');
        $this->assertGreaterThanOrEqual(2, $tablas->length);

        $filasPrintSeg = $xpathPrint->query('.//tbody/tr', $tablas->item(0));
        $filasPrintMue = $xpathPrint->query('.//tbody/tr', $tablas->item(1));
        $this->assertSame(count($itemsSeguridad) + 1, $filasPrintSeg->length);
        $this->assertSame(count($itemsMuestras) + 1, $filasPrintMue->length);

        $textosSeg = $this->textosPrimeraCelda($filasPrintSeg);
        $textosMue = $this->textosPrimeraCelda($filasPrintMue);
        $this->assertSame($itemsSeguridad, array_slice($textosSeg, 0, count($itemsSeguridad)));
        $this->assertSame($itemsMuestras, array_slice($textosMue, 0, count($itemsMuestras)));
        $this->assertSame('Linterna frontal', $textosSeg[count($itemsSeguridad)]);
        $this->assertSame('Cinta métrica', $textosMue[count($itemsMuestras)]);
        $this->assertSame('Mascarilla', $textosSeg[0]);
        $this->assertSame('Densímetro Nuclear', $textosMue[0]);
        $this->assertStringContainsString('>X<', $impresion);
    }

    private function leerFuente(string $relativo): string {
        $ruta = dirname(__DIR__, 2) . $relativo;
        $contenido = file_get_contents($ruta);
        $this->assertNotFalse($contenido, "No se pudo leer {$relativo}");
        return $contenido;
    }

    /**
     * @return list<string>
     */
    private function extraerLista(string $fuente, string $variable): array {
        $patron = '/\$' . preg_quote($variable, '/') . '\s*=\s*\[(.*?)\];/s';
        $coincidencias = [];
        $this->assertSame(1, preg_match($patron, $fuente, $coincidencias), "No se encontró \${$variable}.");
        preg_match_all("/'((?:\\\\'|[^'])*)'/", $coincidencias[1], $items);
        $lista = array_map(static fn(string $item): string => stripcslashes($item), $items[1]);
        $this->assertNotEmpty($lista);
        return array_values($lista);
    }

    /**
     * @param array<string, mixed> $checklist
     */
    private function renderProgramar(array $checklist): string {
        $os = [
            'id' => 41,
            'codigo_os' => '2026041-ORDEN DE SERVICIO',
            'cliente_nombre' => 'Cliente Checklist',
            'nombre_proyecto' => 'Tramo Norte',
            'estado' => 'Estado 3A: Programacion Muestreo',
            'programacion_muestreo' => [
                'checklist_json' => json_encode($checklist, JSON_UNESCAPED_UNICODE),
                'estado_muestreo' => 'En Campo',
                'fecha_ida' => '2026-10-29 08:00:00',
                'fecha_llegada' => '2026-10-29 12:00:00',
                'id_tecnico' => 1,
                'id_vehiculo' => 1,
                'lugar_muestreo' => 'Tramo Norte',
                'num_muestreadores' => 2,
                'observaciones_campo' => 'Sin novedad',
                'tecnico_nombre' => 'Técnico Uno',
            ],
        ];
        $tecnicos = [['id' => 1, 'nombre' => 'Técnico Uno']];
        $vehiculos = [['id' => 1, 'marca' => 'Toyota', 'modelo' => 'Hilux', 'placa' => 'LE-3050']];
        $_SESSION['csrf_token'] = 'token-prueba-checklist';

        ob_start();
        include dirname(__DIR__, 2) . self::VISTA_PROGRAMAR;
        return (string)ob_get_clean();
    }

    /**
     * @param array<string, mixed> $checklist
     */
    private function renderImprimir(array $checklist): string {
        $os = [
            'id' => 41,
            'codigo_os' => '2026041-ORDEN DE SERVICIO',
            'cliente_nombre' => 'Cliente Checklist',
            'nombre_proyecto' => 'Tramo Norte',
            'estado' => 'Estado 3A: Programacion Muestreo',
            'programacion_muestreo' => [
                'checklist_json' => json_encode($checklist, JSON_UNESCAPED_UNICODE),
                'estado_muestreo' => 'En Campo',
                'fecha_ida' => '2026-10-29 08:00:00',
                'fecha_llegada' => '2026-10-29 12:00:00',
                'tecnico_nombre' => 'Técnico Uno',
                'num_muestreadores' => 2,
                'lugar_muestreo' => 'Tramo Norte',
                'cantidad_muestras_est' => '4',
                'observaciones_campo' => 'Sin novedad',
                'marca' => 'Toyota',
                'modelo' => 'Hilux',
                'placa' => 'LE-3050',
            ],
        ];

        ob_start();
        include dirname(__DIR__, 2) . self::VISTA_IMPRIMIR;
        return (string)ob_get_clean();
    }

    private function xpath(string $html): DOMXPath {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return new DOMXPath($dom);
    }

    /**
     * @return list<string>
     */
    private function textosPrimeraCelda(\DOMNodeList $filas): array {
        $textos = [];
        foreach ($filas as $fila) {
            if (!$fila instanceof DOMElement) {
                continue;
            }
            $celdas = $fila->getElementsByTagName('td');
            $this->assertGreaterThan(0, $celdas->length);
            $textos[] = trim(preg_replace('/\s+/', ' ', $celdas->item(0)->textContent) ?? '');
        }
        return $textos;
    }

    private function valorInput(DOMXPath $xpath, string $name): string {
        $nodos = $xpath->query('//input[@name="' . $name . '"]');
        $this->assertNotFalse($nodos);
        $this->assertGreaterThan(0, $nodos->length, "Falta el campo {$name}.");
        $nodo = $nodos->item(0);
        $this->assertInstanceOf(DOMElement::class, $nodo);
        return $nodo->getAttribute('value');
    }
}
