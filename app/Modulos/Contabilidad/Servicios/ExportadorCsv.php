<?php

namespace Cycsa\Modulos\Contabilidad\Servicios;

final class ExportadorCsv {
    private const BOM_UTF8 = "\xEF\xBB\xBF";

    /**
     * Genera un CSV UTF-8 compatible con Excel.
     *
     * @param array<int, string> $encabezados
     * @param array<int, array<int, mixed>> $filas
     */
    public static function generar(array $encabezados, array $filas): string {
        $flujo = fopen('php://temp', 'w+b');
        if ($flujo === false) {
            throw new \RuntimeException('No fue posible crear el archivo CSV temporal.');
        }

        fwrite($flujo, self::BOM_UTF8);
        self::escribirFila($flujo, $encabezados);

        foreach ($filas as $fila) {
            self::escribirFila($flujo, $fila);
        }

        rewind($flujo);
        $contenido = stream_get_contents($flujo);
        fclose($flujo);

        if ($contenido === false) {
            throw new \RuntimeException('No fue posible generar el contenido CSV.');
        }

        // Normalizar a CRLF (\r\n) estándar RFC 4180 compatible con Excel en cualquier versión de PHP
        return preg_replace('/(?<!\r)\n/', "\r\n", $contenido);
    }

    /** @param resource $flujo */
    private static function escribirFila($flujo, array $fila): void {
        $valores = array_map([self::class, 'normalizarCelda'], array_values($fila));
        fputcsv($flujo, $valores, ',', '"', '\\');
    }

    private static function normalizarCelda(mixed $valor): string|int|float {
        if ($valor === null) {
            return '';
        }

        if (is_int($valor) || is_float($valor)) {
            return $valor;
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        $texto = (string)$valor;
        if (preg_match('/^[=+\-@\t\r]/u', $texto) === 1) {
            return "'" . $texto;
        }

        return $texto;
    }
}
