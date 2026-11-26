<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Remisiones\Servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera el HTML de impresión y el PDF oficial Dompdf del documento CYCSA-RG-FM-41
 * (Remisión de Entrega de Informes de Ensayos) reutilizando la vista detalle.php.
 */
class DocumentoRgFm41Servicio
{
    public static function html(array $remision): string
    {
        $esModoPdfRgFm41 = true;
        $csrfToken = '';
        ob_start();
        require dirname(__DIR__) . '/Vistas/detalle.php';
        return (string)ob_get_clean();
    }

    public static function pdf(array $remision): string
    {
        $html = self::html($remision);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultMediaType', 'print');
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $cache = dirname(__DIR__, 4) . '/storage/cache';
        if (!is_dir($cache)) {
            @mkdir($cache, 0777, true);
        }
        $options->set('tempDir', $cache);
        $options->set('fontCache', $cache);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
