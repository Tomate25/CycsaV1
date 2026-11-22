<?php

namespace Cycsa\Modulos\Reportes\Servicios;

use ZipArchive;

/**
 * Generador nativo de archivos Excel reales (.xlsx - Office Open XML) sin dependencias externas.
 * Requiere la extensión ZipArchive de PHP.
 */
final class ExportadorExcelXlsx {

    /**
     * Genera el contenido binario de un libro Excel (.xlsx) válido.
     *
     * @param string $titulo Hoja / título del reporte
     * @param string $subtitulo Línea descriptiva con filtros o fecha de generación
     * @param array<int, array<string, mixed>> $columnas Definición de columnas [['key' => ..., 'label' => ..., 'type' => 'text|money|number|date|badge']]
     * @param array<int, array<string, mixed>> $filas Filas asociativas indexadas por key
     */
    public static function generar(string $titulo, string $subtitulo, array $columnas, array $filas): string {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('La extensión ZipArchive de PHP es requerida para generar archivos .xlsx.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cycsa_xlsx_');
        if ($tmpFile === false) {
            throw new \RuntimeException('No fue posible crear archivo temporal para Excel.');
        }
        // ZipArchive sobre tempnam vacío requiere OVERWRITE
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpFile);
            throw new \RuntimeException('No fue posible abrir contenedor ZIP para .xlsx.');
        }

        $sheetName = self::sanitizarNombreHoja($titulo);
        $colCount = max(1, count($columnas));
        $lastColLetter = self::indiceAColumnaExcel($colCount);

        $zip->addFromString('[Content_Types].xml', self::construirContentTypes());
        $zip->addFromString('_rels/.rels', self::construirRelsRaiz());
        $zip->addFromString('xl/workbook.xml', self::construirWorkbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::construirWorkbookRels());
        $zip->addFromString('xl/styles.xml', self::construirStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::construirSheetXml($titulo, $subtitulo, $columnas, $filas, $lastColLetter));

        $zip->close();

        $binario = file_get_contents($tmpFile);
        @unlink($tmpFile);

        if ($binario === false) {
            throw new \RuntimeException('No fue posible leer el archivo Excel generado.');
        }

        return $binario;
    }

    private static function sanitizarNombreHoja(string $nombre): string {
        $limpio = preg_replace('/[\x00-\x1F\x7F\\\\\/\?\*\[\]:]/u', ' ', trim($nombre));
        if ($limpio === null) {
            $limpio = preg_replace('/[\x00-\x1F\x7F\\\\\/\?\*\[\]:]/', ' ', trim($nombre)) ?: 'Reporte';
        }
        $limpio = trim($limpio, " \t\n\r\0\x0B'");
        if (function_exists('mb_substr')) {
            $limpio = mb_substr($limpio, 0, 31, 'UTF-8');
        } else {
            $limpio = substr($limpio, 0, 31);
        }
        $limpio = trim($limpio, " \t\n\r\0\x0B'");
        return $limpio !== '' ? $limpio : 'Reporte';
    }

    public static function indiceAColumnaExcel(int $indice): string {
        $letra = '';
        while ($indice > 0) {
            $mod = ($indice - 1) % 26;
            $letra = chr(65 + $mod) . $letra;
            $indice = intdiv($indice - $mod, 26);
        }
        return $letra;
    }

    private static function escaparXml(string $texto): string {
        if (function_exists('mb_convert_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
        $limpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{FFFE}\x{FFFF}]/u', '', $texto);
        if ($limpio === null) {
            $limpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto) ?? '';
        }
        return htmlspecialchars($limpio, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function celdaTexto(string $ref, string $texto, int $estilo): string {
        return '<c r="' . $ref . '" t="inlineStr" s="' . $estilo . '"><is><t xml:space="preserve">' . self::escaparXml($texto) . '</t></is></c>';
    }

    private static function celdaNumero(string $ref, float|int $num, int $estilo): string {
        if (!is_finite((float)$num)) {
            return self::celdaTexto($ref, '', $estilo);
        }
        return '<c r="' . $ref . '" s="' . $estilo . '"><v>' . $num . '</v></c>';
    }

    private static function construirSheetXml(string $titulo, string $subtitulo, array $columnas, array $filas, string $lastColLetter): string {
        $anchos = [];
        foreach ($columnas as $idx => $col) {
            $label = (string)($col['label'] ?? $col['key'] ?? '');
            $anchos[$idx] = max(12, min(48, (function_exists('mb_strlen') ? mb_strlen($label, 'UTF-8') : strlen($label)) + 5));
        }

        $rowsXml = [];
        // Fila 1: Título corporativo
        $rowsXml[] = '<row r="1" ht="26" customHeight="1">' . self::celdaTexto('A1', 'CYCSA ERP / LIMS · ' . $titulo, 1) . '</row>';
        // Fila 2: Subtítulo / filtros
        $rowsXml[] = '<row r="2" ht="19" customHeight="1">' . self::celdaTexto('A2', $subtitulo, 2) . '</row>';

        // Fila 3: Encabezados
        $headerCells = [];
        foreach ($columnas as $idx => $col) {
            $ref = self::indiceAColumnaExcel($idx + 1) . '3';
            $headerCells[] = self::celdaTexto($ref, (string)($col['label'] ?? $col['key'] ?? ''), 3);
        }
        $rowsXml[] = '<row r="3" ht="22" customHeight="1">' . implode('', $headerCells) . '</row>';

        // Filas de datos (a partir de fila 4)
        $rowNum = 4;
        foreach ($filas as $fIdx => $fila) {
            $esPar = ($fIdx % 2 === 1);
            $cells = [];
            foreach ($columnas as $cIdx => $col) {
                $key = (string)($col['key'] ?? '');
                $type = (string)($col['type'] ?? 'text');
                $rawVal = $fila[$key] ?? '';
                $ref = self::indiceAColumnaExcel($cIdx + 1) . $rowNum;

                if ($type === 'money' || $type === 'number') {
                    if ($rawVal === '' || $rawVal === null || !is_numeric($rawVal)) {
                        $estilo = $esPar ? 5 : 4;
                        $cells[] = self::celdaTexto($ref, (string)($rawVal ?? ''), $estilo);
                    } else {
                        $numVal = (float)$rawVal;
                        $estilo = ($type === 'money') ? ($esPar ? 7 : 6) : ($esPar ? 9 : 8);
                        $cells[] = self::celdaNumero($ref, $numVal, $estilo);
                        $lenTxt = strlen(number_format($numVal, 2));
                        if ($lenTxt + 4 > $anchos[$cIdx]) {
                            $anchos[$cIdx] = min(48, $lenTxt + 4);
                        }
                    }
                } else {
                    $txt = is_array($rawVal) ? implode(', ', $rawVal) : (string)($rawVal ?? '');
                    $estilo = $esPar ? 5 : 4;
                    $cells[] = self::celdaTexto($ref, $txt, $estilo);
                    $lenTxt = function_exists('mb_strlen') ? mb_strlen($txt, 'UTF-8') : strlen($txt);
                    if ($lenTxt + 3 > $anchos[$cIdx]) {
                        $anchos[$cIdx] = min(48, $lenTxt + 3);
                    }
                }
            }
            $rowsXml[] = '<row r="' . $rowNum . '">' . implode('', $cells) . '</row>';
            $rowNum++;
        }

        $colsXmlStr = '';
        if (!empty($anchos)) {
            $colsXml = ['<cols>'];
            foreach ($anchos as $cIdx => $width) {
                $n = $cIdx + 1;
                $colsXml[] = '<col min="' . $n . '" max="' . $n . '" width="' . number_format($width, 2, '.', '') . '" customWidth="1"/>';
            }
            $colsXml[] = '</cols>';
            $colsXmlStr = implode('', $colsXml);
        }

        $lastRowNum = max(3, $rowNum - 1);
        $autoFilter = !empty($columnas)
            ? '<autoFilter ref="A3:' . $lastColLetter . $lastRowNum . '"/>'
            : '';
        $merges = '';
        if (count($columnas) > 1) {
            $merges = '<mergeCells count="2">'
                . '<mergeCell ref="A1:' . $lastColLetter . '1"/>'
                . '<mergeCell ref="A2:' . $lastColLetter . '2"/>'
                . '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastColLetter . $lastRowNum . '"/>'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A4" sqref="A4"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . $colsXmlStr
            . '<sheetData>' . implode('', $rowsXml) . '</sheetData>'
            . $autoFilter
            . $merges
            . '</worksheet>';
    }

    private static function construirContentTypes(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function construirRelsRaiz(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function construirWorkbook(string $sheetName): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::escaparXml($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function construirWorkbookRels(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function construirStyles(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2">'
            . '<numFmt numFmtId="164" formatCode="#,##0.00"/>'
            . '<numFmt numFmtId="165" formatCode="#,##0.##"/>'
            . '</numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><color rgb="FF103487"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF475569"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF103487"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
