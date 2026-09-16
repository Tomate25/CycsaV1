<?php

namespace Cycsa\App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Helper para la generación de archivos PDF utilizando Dompdf.
 */
class PdfHelper
{
    /**
     * Genera un PDF a partir de HTML y lo devuelve o lo descarga.
     *
     * @param string $html Contenido HTML del PDF.
     * @param string $filename Nombre del archivo.
     * @param bool $download Si es true, fuerza la descarga; si es false, lo muestra.
     * @return void
     */
    public static function generatePdf(string $html, string $filename = 'documento.pdf', bool $download = true): void
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) {
            $font = $fontMetrics->getFont('Helvetica', 'normal');
            $size = 8;
            $text = "Página {$pageNumber} de {$pageCount}";
            $w = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text($canvas->get_width() - $w - 36, $canvas->get_height() - 24, $text, $font, $size, [0.35, 0.35, 0.35]);
        });

        $dompdf->stream($filename, ['Attachment' => $download ? 1 : 0]);
    }

    /**
     * Renderiza un PDF a partir de HTML y devuelve su contenido binario con paginación automática.
     *
     * @param string $html Contenido HTML.
     * @param string $paper Tamaño de papel ('A4', 'letter', etc.).
     * @param string $orientation Orientación ('portrait' o 'landscape').
     * @param bool $paginate Si se debe incluir numeración de páginas.
     * @return string Contenido binario del PDF.
     */
    public static function renderPdf(string $html, string $paper = 'A4', string $orientation = 'portrait', bool $paginate = true): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        if ($paginate) {
            $canvas = $dompdf->getCanvas();
            $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Helvetica', 'normal');
                $size = 8;
                $text = "Página {$pageNumber} de {$pageCount}";
                $w = $fontMetrics->getTextWidth($text, $font, $size);
                $canvas->text($canvas->get_width() - $w - 36, $canvas->get_height() - 24, $text, $font, $size, [0.35, 0.35, 0.35]);
            });
        }

        return $dompdf->output();
    }
}
