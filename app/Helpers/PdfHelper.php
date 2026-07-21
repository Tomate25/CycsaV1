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

        $dompdf->stream($filename, ['Attachment' => $download ? 1 : 0]);
    }
}
