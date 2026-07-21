<?php

namespace Cycsa\App\Helpers;

/**
 * Helper para manejar cálculos monetarios, IVA y formatos.
 */
class MoneyHelper
{
    /**
     * Formatea un monto con la moneda especificada.
     *
     * @param float $amount
     * @param string $currency 'NIO' o 'USD'
     * @return string
     */
    public static function format(float $amount, string $currency = 'NIO'): string
    {
        $symbol = $currency === 'NIO' ? 'C$' : '$';
        return $symbol . ' ' . number_format($amount, 2, '.', ',');
    }

    /**
     * Calcula el IVA de un monto.
     *
     * @param float $amount
     * @param float $ivaRate
     * @return float
     */
    public static function calculateIVA(float $amount, float $ivaRate = 0.15): float
    {
        return $amount * $ivaRate;
    }

    /**
     * Calcula la retención aplicable a un monto.
     *
     * @param float $amount
     * @param float $retentionRate
     * @return float
     */
    public static function calculateRetention(float $amount, float $retentionRate = 0.02): float
    {
        return $amount * $retentionRate;
    }

    /**
     * Calcula el total incluyendo IVA y descontando retenciones.
     *
     * @param float $subtotal
     * @param float $ivaRate
     * @param float $retentionRate
     * @return float
     */
    public static function calculateTotal(float $subtotal, float $ivaRate = 0.15, float $retentionRate = 0.0): float
    {
        $iva = self::calculateIVA($subtotal, $ivaRate);
        $retention = self::calculateRetention($subtotal, $retentionRate);
        return $subtotal + $iva - $retention;
    }
}
