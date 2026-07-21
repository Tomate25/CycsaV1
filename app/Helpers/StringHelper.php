<?php

namespace Cycsa\App\Helpers;

/**
 * Helper para manipulación de cadenas, generación de folios amigables y slugs.
 */
class StringHelper {

    public static function sanitizar(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function generarCodigoMuestra(int $correlativo, int $anio, string $tipo = 'MS'): string {
        $anioCorto = date('y', strtotime("20{$anio}-01-01"));
        return sprintf("%s-%04d-%02d", strtoupper($tipo), $correlativo, $anioCorto);
    }

    public static function despojarCodigosInternos(string $codigoConSufijo): string {
        // Remueve sufijos internos de calidad CR, C1, C2 para entregar al cliente
        return preg_replace('/-(CR|C1|C2)$/i', '', trim($codigoConSufijo));
    }
}
