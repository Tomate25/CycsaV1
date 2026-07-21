<?php

namespace Cycsa\App\Helpers;

use DateTime;
use Exception;

/**
 * Helper para manejar fechas según normativas.
 */
class DateHelper
{
    /**
     * Formatea una fecha según el estándar normativo.
     *
     * @param string $date Fecha en formato Y-m-d o Y-m-d H:i:s
     * @param string $format Formato de salida
     * @return string
     */
    public static function formatNormative(string $date, string $format = 'd/m/Y'): string
    {
        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Calcula la fecha de fin de un plazo de curado en días.
     *
     * @param string $startDate
     * @param int $days
     * @return string Fecha de fin de curado en formato Y-m-d
     */
    public static function calculateCuringDate(string $startDate, int $days): string
    {
        try {
            $dt = new DateTime($startDate);
            $dt->modify("+$days days");
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return $startDate;
        }
    }
}
