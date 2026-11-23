<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class InformesOficialesParidadTest extends TestCase {

    private function extraerTextoDePdf(string $pdfBytes): string {
        $texto = $pdfBytes;
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfBytes, $matches)) {
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

    private function getDetallePruebaConcreto(): array {
        return [
            'id' => 20,
            'id_cotizacion' => 5,
            'codigo_servicio' => 'ENS-CONC-01',
            'descripcion_ensayo' => 'Ensayo de compresión de cilindros de concreto',
            'norma_astm' => 'ASTM C39 / C39M',
            'codigo_os' => 'OS-2026-0006',
            'tecnico_muestreo' => 'Ing. Juan Pérez',
            'cliente_nombre' => 'IALSA CONSTRUCCIONES',
            'cliente_informe_nombre' => 'DESTINATARIO INFORME S.A.',
            'cliente_direccion' => 'Managua, Nicaragua',
            'nombre_proyecto' => 'Proyecto Prueba Auditoria PDF',
            'fecha_muestreo' => '2026-10-28',
            'fecha_ingreso' => '2026-10-29',
            'fecha_ejecucion' => '2026-10-29',
            'fecha_emision' => '2026-10-29',
            'procedimiento_muestreo' => 'DE-ASTM C31/ASTM C172M-17',
            'muestra_tomada_por' => 'El Cliente y entrega en Laboratorio',
            'ubicacion' => 'Proyecto Prueba Auditoria PDF - Managua',
            'resultados_json' => json_encode([
                'version_actual' => 1,
                'filas' => [
                    [
                        'Código laboratorio' => 'MS-9994-26',
                        'Fecha de moldeo' => '2026-10-01',
                        'Fecha de ensayo' => '2026-10-29',
                        'Edad (días)' => '28',
                        'Diámetro (cm)' => '15.0',
                        'Área (cm²)' => '176.71',
                        'Carga (kg)' => '37110',
                        'Resistencia (kg/cm²)' => '210.0',
                        'f\'c especificado' => '210.0',
                        '% Resistencia' => '100.0%',
                        'Tipo de fractura' => 'Cono'
                    ],
                    [
                        'Código laboratorio' => 'MS-9995-26',
                        'Fecha de moldeo' => '2026-10-01',
                        'Fecha de ensayo' => '2026-10-29',
                        'Edad (días)' => '28',
                        'Diámetro (cm)' => '15.0',
                        'Área (cm²)' => '176.71',
                        'Carga (kg)' => '37500',
                        'Resistencia (kg/cm²)' => '212.2',
                        'f\'c especificado' => '210.0',
                        '% Resistencia' => '101.0%',
                        'Tipo de fractura' => 'Cono'
                    ],
                    [
                        'Código laboratorio' => 'MS-9994-26-CR',
                        '_es_replica' => true,
                        '_muestra_origen' => 'MS-9994-26',
                        'Área (cm²)' => '176.71',
                        'Carga (kg)' => '37300',
                        'Resistencia (kg/cm²)' => '211.1'
                    ]
                ],
                'metadatos' => [
                    'cliente_nombre' => 'DESTINATARIO INFORME S.A.',
                    'cliente_direccion' => 'Managua, Nicaragua',
                    'proyecto' => 'Proyecto Prueba Auditoria PDF',
                    'equipo_marca' => 'TROXLER',
                    'equipo_modelo' => '3440',
                    'equipo_serie' => '62677',
                    'equipo_codigo' => 'Eq-6555'
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];
    }

    private function getDetallePruebaCompactacion(): array {
        return [
            'id' => 21,
            'id_cotizacion' => 5,
            'codigo_servicio' => 'ENS-SUEL-01',
            'descripcion_ensayo' => 'Compactación con Densímetro Nuclear (In Situ)',
            'norma_astm' => 'ASTM D6938-23',
            'codigo_os' => 'OS-2026-0005',
            'tecnico_muestreo' => 'Ing. Juan Pérez',
            'cliente_nombre' => 'IALSA CONSTRUCCIONES',
            'cliente_direccion' => 'Managua, Nicaragua',
            'nombre_proyecto' => 'Proyecto Carretera Norte',
            'fecha_muestreo' => '2026-10-28',
            'fecha_ingreso' => '2026-10-29',
            'fecha_ejecucion' => '2026-10-29',
            'fecha_emision' => '2026-10-29',
            'procedimiento_muestreo' => 'Aleatorio Procedimiento A (TD)',
            'muestra_tomada_por' => 'Laboratorio - Consultoría y Construcción S.A. CYCSA.',
            'ubicacion' => 'Estación 0+150',
            'resultados_json' => json_encode([
                'version_actual' => 1,
                'filas' => [
                    [
                        'Código laboratorio' => 'MS-1001-26',
                        'Nombre muestra' => 'Subrasante Tramo 1',
                        'Capa (N°)' => 'Subrasante',
                        'Espesor (cm)' => '20',
                        'Profundidad (cm)' => '15',
                        'P.V.S Max (kg/m³)' => '1950',
                        'Humedad Óptima ((% )(P/P))' => '12.5',
                        'P.V.S.Sitio (kg/cm²)' => '1920', // Clave con unidad legacy para verificar B.7
                        'Humedad Sitio ((%) (P/P))' => '12.1',
                        'Compactación ((%) (P/P))' => '98.5'
                    ]
                ],
                'metadatos' => [
                    'cliente_nombre' => 'IALSA CONSTRUCCIONES',
                    'equipo_marca' => 'TROXLER',
                    'equipo_modelo' => '3440',
                    'equipo_serie' => '62677',
                    'equipo_codigo' => 'Eq-6555'
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];
    }

    /**
     * B.1: Supresión de UI en Impresión y PDF
     */
    public function testB1SupresionUIEnImpresionYPdf(): void {
        $printContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');

        // Regla @media print oculta elementos de UI de pantalla
        $this->assertStringContainsString('.barra-pantalla, .no-print, nav, .btn-matriz-action, #modalEnviarPrint', $printContent);
        $this->assertStringContainsString('display: none !important;', $printContent);

        // Barra flotante condicionada si se solicita modo PDF
        $this->assertStringContainsString('empty($sinBarraPantalla)', $printContent);

        // El PDF generado por Dompdf no contiene elementos interactivos de pantalla
        $detalle = $this->getDetallePruebaConcreto();
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $this->assertNotEmpty($pdfBytes);
        $this->assertStringNotContainsString('Volver a Matriz', $pdfBytes);
        $this->assertStringNotContainsString('Imprimir Matriz', $pdfBytes);
        $this->assertStringNotContainsString('barra-pantalla', $pdfBytes);
    }

    /**
     * B.2: Título Oficial y Código Normativo con Revisión
     */
    public function testB2TituloOficialYCodigosNormativosConRevision(): void {
        $detalleConcreto = $this->getDetallePruebaConcreto();
        $metaConcreto = resolverMetadatosEnsayo($detalleConcreto);
        $this->assertSame('CYCSA-RT-FM-22 A (V1-R2)', $metaConcreto['codigo_formato']);

        $detalleCompactacion = $this->getDetallePruebaCompactacion();
        $metaCompactacion = resolverMetadatosEnsayo($detalleCompactacion);
        $this->assertSame('CYCSA-RT-FM-22 B (V1-R2)', $metaCompactacion['codigo_formato']);

        // Granulometría -> CYCSA-RT-FM-22 C (V1-R2)
        $metaGranulo = resolverMetadatosEnsayo(['descripcion_ensayo' => 'Análisis Granulométrico de Agregados', 'codigo_formato' => 'CYCSA-RT-FM-22 C']);
        $this->assertSame('CYCSA-RT-FM-22 C (V1-R2)', $metaGranulo['codigo_formato']);

        // Asfalto -> CYCSA-RT-FM-22 F (V1-R2)
        $metaAsfalto = resolverMetadatosEnsayo(['descripcion_ensayo' => 'Ensayo de Asfalto', 'codigo_formato' => 'CYCSA-RT-FM-22 F']);
        $this->assertSame('CYCSA-RT-FM-22 F (V1-R2)', $metaAsfalto['codigo_formato']);

        // Banner Sustituye al Informe: NUNCA en versión 1
        $pdfBytesV1 = generarMatrizTecnicaPDF($detalleConcreto);
        $textoV1 = $this->extraerTextoDePdf($pdfBytesV1);
        $this->assertStringNotContainsString('SUSTITUYE AL INFORME', $textoV1);

        // Banner Sustituye al Informe: SÍ en versión > 1 con reemisión
        $detalleV2 = $detalleConcreto;
        $detalleV2['resultados_json'] = json_encode([
            'version_actual' => 2,
            'reemision' => [
                'sustituye_a' => 'CYCSA-INF-MS-9994-9995-26',
                'motivo' => 'Corrección de razón social solicitada por cliente'
            ],
            'filas' => []
        ]);
        $pdfBytesV2 = generarMatrizTecnicaPDF($detalleV2);
        $textoV2 = $this->extraerTextoDePdf($pdfBytesV2);
        $this->assertStringContainsString('SUSTITUYE AL INFORME', $textoV2);
        $this->assertStringContainsString('CYCSA-INF-MS-9994-9995-26', $textoV2);
    }

    /**
     * B.3: Cabecera Oficial Completa y Marcadores ** del Cliente
     */
    public function testB3CabeceraOficialCompletaYMarcadoresCliente(): void {
        $detalle = $this->getDetallePruebaConcreto();
        $meta = resolverMetadatosEnsayo($detalle);

        // Prioridad de razón social de informe (ISO 17025) sobre cliente pagador
        $this->assertSame('DESTINATARIO INFORME S.A.', $meta['cliente_nombre']);
        $this->assertSame('Managua, Nicaragua', $meta['cliente_direccion']);
        $this->assertSame('Proyecto Prueba Auditoria PDF', $meta['proyecto']);
        $this->assertSame('2026-10-28', $meta['fecha_muestreo']);
        $this->assertSame('2026-10-29', $meta['fecha_ingreso']);
        $this->assertSame('2026-10-29', $meta['fecha_emision']);
        $this->assertSame('DE-ASTM C31/ASTM C172M-17', $meta['procedimiento_muestreo']);
        $this->assertSame('El Cliente y entrega en Laboratorio', $meta['muestra_tomada_por']);

        // Verificación de plantilla de impresión y PDF
        $printContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString('** Nombre del cliente:', $printContent);
        $this->assertStringContainsString('** Proyecto:', $printContent);
        $this->assertStringContainsString('** Dirección:', $printContent);
        $this->assertStringContainsString('** Fecha muestreo:', $printContent);
        $this->assertStringContainsString('** Procedimiento de muestreo:', $printContent);
        $this->assertStringContainsString('** Ubicación:', $printContent);
        $this->assertStringContainsString('** Información Proporcionada por el cliente', $printContent);
    }

    /**
     * B.4 y B.7: Matriz Compactación RT-FM-22 B y Unidad PE-25 (kg/m³)
     */
    public function testB4YB7MatrizCompactacionRtFm22BUnidadKgm3(): void {
        $detalle = $this->getDetallePruebaCompactacion();
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $this->assertNotEmpty($pdfBytes);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);

        // La unidad debe ser estrictamente kg/m³ para P.V.S. Máximo y P.V.S. Sitio (nunca kg/cm²)
        $this->assertStringNotContainsString('P.V.S.Sitio (kg/cm²)', $textoPdf);
        $this->assertStringContainsString('kg/m', $textoPdf);

        // Columna '#' presente
        $this->assertStringContainsString('#', $textoPdf);
    }

    /**
     * B.5: Campo Estructurado "Muestra tomada con equipo" (Nota 1 Densímetro)
     */
    public function testB5MuestraTomadaConEquipoNota1Densimetro(): void {
        $detalle = $this->getDetallePruebaCompactacion();
        $meta = resolverMetadatosEnsayo($detalle, [], [
            'equipo_marca' => 'TROXLER',
            'equipo_modelo' => '3440',
            'equipo_serie' => '62677',
            'equipo_codigo' => 'Eq-6555'
        ]);

        $this->assertSame(
            'Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3440, N/S : 62677 Eq-6555',
            $meta['nota_equipo_densimetro']
        );

        // Captura Matriz posee los inputs estructurados de equipo
        $capturaContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('name="metadatos[equipo_marca]"', $capturaContent);
        $this->assertStringContainsString('name="metadatos[equipo_modelo]"', $capturaContent);
        $this->assertStringContainsString('name="metadatos[equipo_serie]"', $capturaContent);
        $this->assertStringContainsString('name="metadatos[equipo_codigo]"', $capturaContent);

        // PDF incluye la Nota 1 estructurada
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);
        $this->assertStringContainsString('Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3440, N/S : 62677 Eq-6555', $textoPdf);
    }

    /**
     * B.6: Paridad de Firmas y Pie Normativo Oficial
     */
    public function testB6ParidadFirmasUltimaLineaYDeclaracionConformidad(): void {
        $detalle = $this->getDetallePruebaConcreto();
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);

        // Línea de cierre
        $this->assertStringContainsString('-------------------------------- Última Línea ---------------------------------', $textoPdf);

        // Firma oficial Gerente General
        $this->assertStringContainsString('Ing. Noel Quintana Lira', $textoPdf);
        $this->assertTrue(stripos($textoPdf, 'Gerente General') !== false, 'Debe contener cargo Gerente General');

        // Los bloques redundantes de Observaciones y Condiciones / Declaración de Conformidad e Imparcialidad fueron retirados del PDF e impresión
        $this->assertFalse(mb_stripos($textoPdf, 'Observaciones y Condiciones del Ensayo', 0, 'UTF-8') !== false, 'No debe incluir el bloque Observaciones y Condiciones del Ensayo');
        $this->assertFalse(mb_stripos($textoPdf, 'Declaración de Conformidad e Imparcialidad', 0, 'UTF-8') !== false, 'No debe incluir el bloque Declaración de Conformidad e Imparcialidad');
    }

    /**
     * B.8: Paridad PE-07 / CYCSA-RT-FM-22 A (14 Columnas en PDF)
     */
    public function testB8ParidadPe07CycsaRtFm22A14ColumnasPdf(): void {
        $detalle = $this->getDetallePruebaConcreto();
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);

        // Todas las 14 columnas deben estar presentes
        $columnasEsperadas = [
            'Código laboratorio',
            'Nombre muestra',
            'Área (in²)',
            'Reven. (in)',
            'Reven. (cm)',
            'Temp. (°C)',
            'Carga (lb)',
            'Peso Volumétrico (kg/m³)',
            'Diseño de concreto (lb/in²)',
            'R. Compresión (lb/in²)',
            'R. Compresión (kg/cm²)',
            'Fecha de Fabricación',
            'Fecha de Ensayo',
            'Edad (Días)'
        ];

        foreach ($columnasEsperadas as $col) {
            $this->assertStringContainsString($col, $textoPdf, "La columna '{$col}' debe estar en el PDF de 14 columnas.");
        }

        // Conversión dimensional reflejada en el PDF
        // Carga: 37110 kg -> 81813.5 lb
        $this->assertStringContainsString('81813.5', $textoPdf);
        // Área: 176.71 cm² -> 27.39 in²
        $this->assertStringContainsString('27.39', $textoPdf);
        // Resistencia: 210 kg/cm² -> 2986.9 psi y 210.0 kg/cm²
        $this->assertStringContainsString('2986.9', $textoPdf);
        $this->assertStringContainsString('210.0', $textoPdf);

        // El PDF debe ser exactamente de 1 página (sin desbordamiento)
        $paginas = preg_match_all("/\/Page\W/", $pdfBytes, $matches);
        $this->assertSame(1, $paginas, "El informe oficial de 14 columnas debe caber exactamente en 1 página.");
    }

    /**
     * B.9: Coherencia de Código de Informe (MS/MC)
     */
    public function testB9CoherenciaCodigoInformeConsecutivoMsMc(): void {
        $detalle = $this->getDetallePruebaConcreto();

        // Código generado en captura
        $codigoCaptura = generarCodigoInformeEnsayo(
            json_decode($detalle['resultados_json'], true)['filas'],
            '2026-10-28',
            'Cilindros de Concreto',
            $detalle['codigo_os']
        );

        // Las muestras son MS-9994-26 y MS-9995-26 -> consecutivas
        $this->assertSame('CYCSA-INF-MS-9994-9995-26', $codigoCaptura);

        // El PDF contiene exactamente el mismo código consecutivo
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);
        $this->assertStringContainsString('CYCSA-INF-MS-9994-9995-26', $textoPdf);
    }

    /**
     * B.10: Fila / Subtítulo de Método Normativo en Cabecera de Tabla
     */
    public function testB10ParidadFilaMetodoEnCabecera(): void {
        $detalleCompactacion = $this->getDetallePruebaCompactacion();
        $pdfBytes = generarMatrizTecnicaPDF($detalleCompactacion);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);

        // Cabeceras con métodos normativos asociados
        $this->assertStringContainsString('ASTM D698 / D1557', $textoPdf);
        $this->assertStringContainsString('ASTM D6938 / D1556', $textoPdf);

        $printView = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString('$colMethods', $printView);
        $this->assertStringContainsString('metodo-sub', $printView);
    }

    /**
     * B.11: Paridad de Formatos y Ensayos Adicionales (RT-FM-22 C, RT-FM-22 F y 21 PE)
     */
    public function testB11ParidadFormatosAdicionalesCYCSARtFm22C_F_21PE(): void {
        // Flexión en vigas (PE-10) -> RT-FM-22 C
        $metaFlexion = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Resistencia a la Flexión de Vigas de Concreto',
            'codigo_servicio' => 'ENS-CONC-02',
            'norma_astm' => 'ASTM C78 / C78M',
            'codigo_formato' => 'CYCSA-RT-FM-22 C'
        ]);
        $this->assertSame('CYCSA-RT-FM-22 C (V1-R2)', $metaFlexion['codigo_formato']);

        // Granulometría de Suelos y Límites (PE-16 / PE-17) -> RT-FM-22 F
        $metaGranulo = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Análisis Granulométrico y Límites de Atterberg',
            'codigo_servicio' => 'ENS-SUEL-01',
            'norma_astm' => 'ASTM D6913 / D4318',
            'codigo_formato' => 'CYCSA-RT-FM-22 F'
        ]);
        $this->assertSame('CYCSA-RT-FM-22 F (V1-R2)', $metaGranulo['codigo_formato']);

        // Densidad in situ PE-20 (cono de arena ASTM D1556) y PE-25 (densímetro nuclear ASTM D6938)
        $metaCono = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Densidad de campo por Cono de Arena',
            'norma_astm' => 'ASTM D1556 / D1556M'
        ]);
        $this->assertSame('CYCSA-RT-FM-22 B (V1-R2)', $metaCono['codigo_formato']);

        $metaNuclear = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Densidad y Humedad in situ por Métodos Nucleares',
            'norma_astm' => 'ASTM D6938'
        ]);
        $this->assertSame('CYCSA-RT-FM-22 B (V1-R2)', $metaNuclear['codigo_formato']);

        // CBR de Suelos (PE-46 ASTM D1883)
        $metaCbr = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Relación de Soporte de California (CBR)',
            'norma_astm' => 'ASTM D1883'
        ]);
        $this->assertNotEmpty($metaCbr['codigo_formato']);
    }

    /**
     * B.12: Cláusula y Notas editables por matriz ubicadas arriba de la tabla en Operaciones y propagadas al PDF/Impresión
     */
    public function testB12NotasYDisclaimerEditablesPropaganAPdfEImpresionYEstanArribaDeTabla(): void {
        $detalle = $this->getDetallePruebaConcreto();
        $dec = json_decode($detalle['resultados_json'], true);
        $dec['metadatos']['disclaimer_personalizado'] = 'Cláusula editada CYCSA prueba de exactitud en muestras de campo.';
        $dec['metadatos']['notas_informe'] = [
            'Nota: Ensayos realizados in Situ: Ensayo "CYCSA-PE-09" "CYCSA-PE-27"',
            'Nota personalizada: Verificación especial solicitada por obra.'
        ];
        $dec['metadatos']['notas_informe_personalizadas'] = true;
        $detalle['resultados_json'] = json_encode($dec, JSON_UNESCAPED_UNICODE);

        $schema = obtenerEsquemaPlantillaEnsayo('resistencia_de_concreto.md');
        $meta = resolverMetadatosEnsayo($detalle, $schema, $dec['metadatos']);
        $this->assertSame('Cláusula editada CYCSA prueba de exactitud en muestras de campo.', $meta['disclaimer_personalizado']);
        $this->assertTrue($meta['notas_informe_personalizadas']);
        $this->assertCount(2, $meta['notas_informe']);

        // Verificar propagación al PDF
        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);
        $this->assertStringContainsString('CYCSA-PE-09', $textoPdf);
        $this->assertStringContainsString('CYCSA-PE-27', $textoPdf);
        $this->assertStringContainsString('Verificación especial solicitada por obra.', $textoPdf);
        $this->assertStringContainsString('Cláusula editada CYCSA prueba', $textoPdf);

        // Verificar posición ARRIBA de la tabla en documento_terminado.php y captura_matriz.php
        $docView = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/documento_terminado.php');
        $posPanelDoc = strpos($docView, 'id="panel-notas-documento-arriba"');
        $posTablaDoc = strpos($docView, 'id="tabla-documento-terminado"');
        $this->assertNotFalse($posPanelDoc);
        $this->assertNotFalse($posTablaDoc);
        $this->assertLessThan($posTablaDoc, $posPanelDoc, 'El bloque editable de notas debe aparecer ARRIBA de la tabla en documento_terminado.php.');

        $capView = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $posPanelCap = strpos($capView, 'id="panel-notas-matriz-arriba"');
        $posTablaCap = strpos($capView, 'id="tabla-captura-matriz"');
        $this->assertNotFalse($posPanelCap);
        $this->assertNotFalse($posTablaCap);
        $this->assertLessThan($posTablaCap, $posPanelCap, 'El bloque editable de notas debe aparecer ARRIBA de la tabla en captura_matriz.php.');

        // Verificar propagación en matriz_print.php
        $printView = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString("disclaimer_personalizado", $printView);
        $this->assertStringContainsString("notas_informe_personalizadas", $printView);

        // En impresión (matriz_print.php) y PDF (generarMatrizTecnicaPDF) las notas/disclaimer van DEBAJO de la tabla y sin subtítulo/norma técnica en cabecera
        $posBloqueNormPrint = strpos($printView, 'class="bloque-normativo-formato"');
        $posTablaPrint = strpos($printView, 'class="tabla-matriz-print"');
        $this->assertNotFalse($posBloqueNormPrint);
        $this->assertNotFalse($posTablaPrint);
        $this->assertGreaterThan($posTablaPrint, $posBloqueNormPrint, 'En matriz_print.php las notas y cláusula deben aparecer DEBAJO de la tabla.');
        $funcionesSrc = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Helpers/funciones.php');
        $posTablaPdfHtml = strpos($funcionesSrc, '<table class="matriz-tbl">');
        $posBloquePdfHtml = strpos($funcionesSrc, '<div class="bloque-normativo"', (int)$posTablaPdfHtml);
        $this->assertNotFalse($posTablaPdfHtml);
        $this->assertNotFalse($posBloquePdfHtml, 'En generarMatrizTecnicaPDF el bloque de notas/disclaimer debe aparecer DEBAJO de la tabla matriz-tbl.');
        $this->assertStringNotContainsString('Orden de Servicio: <strong', $printView);
        $this->assertStringNotContainsString('Norma técnica:', $printView);
        $this->assertStringContainsString('class="zona-firmas-pie"', $printView);
        $this->assertStringContainsString('incluir_firma_adicional', $printView);

        // En el PDF tampoco aparecen subtítulo/norma técnica en cabecera, y con firma adicional RT-FM-13 incluye firmante izquierdo + derecho
        $this->assertStringNotContainsString('Norma técnica:', $textoPdf);
        $this->assertStringNotContainsString('Laboratorio de Ensayos y Control de Calidad', $textoPdf);

        $detalleConFirmaAdicional = $detalle;
        $detalleConFirmaAdicional['incluir_firma_adicional'] = 1;
        $detalleConFirmaAdicional['firma_adicional_nombre'] = 'Ing. Carlos Supervisor Obra';
        $detalleConFirmaAdicional['firma_adicional_cargo'] = 'Revisado por Cliente';
        $pdfConFirma = generarMatrizTecnicaPDF($detalleConFirmaAdicional);
        $textoPdfFirma = $this->extraerTextoDePdf($pdfConFirma);
        $this->assertStringContainsString('Ing. Carlos Supervisor Obra', $textoPdfFirma);
        $this->assertTrue(stripos($textoPdfFirma, 'Revisado por Cliente') !== false, 'El cargo de la firma adicional debe aparecer en el PDF.');
        $this->assertStringContainsString('Ing. Noel Quintana Lira', $textoPdfFirma);
    }
}
