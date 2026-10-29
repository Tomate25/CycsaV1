<?php

namespace Cycsa\Modulos\OrdenesServicio\Servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * HTML de impresión y PDF Dompdf del documento CYCSA-RG-FM-39.
 * Ambas salidas usan la misma vista de detalle.
 */
class DocumentoRgFm39Servicio {

    public static function html(array $os): string {
        ob_start();
        require dirname(__DIR__) . '/Vistas/detalle.php';
        return (string)ob_get_clean();
    }

    public static function pdf(array $os): string {
        $html = self::html($os);
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultMediaType', 'print');
        $options->set('defaultFont', 'DejaVu Sans');

        $cache = dirname(__DIR__, 4) . '/storage/cache';
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        $options->set('tempDir', $cache);
        $options->set('fontCache', $cache);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
