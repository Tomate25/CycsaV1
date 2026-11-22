<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\Conexion;
use PDO;
use Throwable;

/**
 * Servicio centralizado de parámetros configurables y fórmulas oficiales de laboratorio
 * (Acuerdos 16 Nov 2026 / Hoja RT-FM-61 oficial "campo" + parámetros pendientes de confirmación).
 *
 * Todos los parámetros con respuesta pendiente del laboratorio se almacenan en `config_calculo_lab`
 * con valores por defecto alineados a las hojas oficiales de CYCSA.
 */
class ConfigCalculoLabServicio {

    public const DEFAULTS = [
        // 1.1 - 1.3 Cilindros RT-FM-61 oficial
        'cilindros_divisor_kgcm2' => '14.2029',
        'cilindros_usar_prensa' => '0',
        'cilindros_usar_factor_ld' => '0',
        'cilindros_redondear_psi_10' => '1',
        'cilindros_redondear_pv_10' => '1',
        // 1.9 Proctor RT-FM-27
        'proctor_metodo' => 'maximo_medido', // 'maximo_medido' | 'regresion_parabolica'
        // 1.10 CBR RT-FM-46
        'cbr_modo_reporte' => 'golpes', // 'golpes' (100/95/90) | 'interpolado'
        // 1.12 Martillo Suizo RT-FM-59
        'martillo_posicion_defecto' => 'A',
        'martillo_usar_factor_cal' => '0',
        'martillo_redondeo_psi' => '100', // 1 | 10 | 100
        // 2.x Flujos configurables
        'informe_incluir_incertidumbre' => '0',
        'informe_envio_auto_aprobar' => '0',
        'informe_parcial_es_interno' => '0',
        'informe_ubicacion_aditivos' => 'pie', // 'pie' | 'cabecera' | 'ambos'
        'cotizacion_responsable_interno_id' => '',
        'informe_observaciones_pendientes_catalogo' => '["Pendiente firma de revisión técnica","Pendiente verificación de equipo calibrado","Pendiente confirmación de datos de recepción","Observación de cliente en trámite"]',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function limpiarCache(): void {
        self::$cache = null;
    }

    /**
     * Obtiene todos los parámetros efectivos de `config_calculo_lab` mezclados con DEFAULTS.
     *
     * @return array<string, string>
     */
    public static function obtenerTodos(?PDO $db = null): array {
        if ($db === null && self::$cache !== null) {
            return self::$cache;
        }

        $valores = self::DEFAULTS;
        try {
            $pdo = $db ?? Conexion::obtenerInstancia();
            $stmt = $pdo->query("SELECT clave, valor FROM config_calculo_lab");
            if ($stmt) {
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $k = (string)($fila['clave'] ?? '');
                    if ($k !== '' && $fila['valor'] !== null) {
                        $valores[$k] = (string)$fila['valor'];
                    }
                }
            }
        } catch (Throwable $e) {
            // Si la tabla aún no existe en algún entorno aislado, usar DEFAULTS
        }

        if ($db === null) {
            self::$cache = $valores;
        }
        return $valores;
    }

    public static function obtener(string $clave, ?string $default = null, ?PDO $db = null): string {
        $todos = self::obtenerTodos($db);
        if (array_key_exists($clave, $todos)) {
            return $todos[$clave];
        }
        return $default ?? (self::DEFAULTS[$clave] ?? '');
    }

    public static function obtenerFloat(string $clave, float $default, ?PDO $db = null): float {
        $v = self::obtener($clave, (string)$default, $db);
        return is_numeric($v) ? (float)$v : $default;
    }

    public static function obtenerBool(string $clave, bool $default = false, ?PDO $db = null): bool {
        $v = strtolower(trim(self::obtener($clave, $default ? '1' : '0', $db)));
        return in_array($v, ['1', 'true', 'si', 'yes', 'on'], true);
    }

    public static function guardar(string $clave, string $valor, ?string $descripcion = null, ?PDO $db = null): bool {
        try {
            $pdo = $db ?? Conexion::obtenerInstancia();
            $stmt = $pdo->prepare("
                INSERT INTO config_calculo_lab (clave, valor, descripcion)
                VALUES (:clave, :valor, :desc)
                ON DUPLICATE KEY UPDATE
                    valor = VALUES(valor),
                    descripcion = COALESCE(VALUES(descripcion), descripcion),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $ok = $stmt->execute([
                'clave' => $clave,
                'valor' => $valor,
                'desc' => $descripcion,
            ]);
            self::limpiarCache();
            return $ok;
        } catch (Throwable $e) {
            error_log('ConfigCalculoLabServicio::guardar error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fórmula oficial RT-FM-61:
     * A (in²) = π · (D̄ / 2)² / 6.4516, donde D̄ = (D1 + D2) / 2 en cm.
     */
    public static function calcularAreaCilindroIn2(float $d1Cm, float $d2Cm): float {
        if ($d1Cm <= 0 || $d2Cm <= 0) {
            return 0.0;
        }
        $dProm = ($d1Cm + $d2Cm) / 2.0;
        return (M_PI * (($dProm / 2.0) ** 2)) / 6.4516;
    }

    /**
     * Fórmula oficial RT-FM-61:
     * PV (kg/m³) = redondear_a_10( m (kg) / (A (in²) · 6.4516 · L̄ (cm) / 1,000,000) )
     */
    public static function calcularPesoVolumetricoKgM3(
        float $masaKg,
        float $areaIn2,
        float $largoPromCm,
        ?bool $redondearA10 = null,
        ?PDO $db = null
    ): ?float {
        if ($masaKg <= 0 || $areaIn2 <= 0 || $largoPromCm <= 0) {
            return null;
        }
        $volM3 = ($areaIn2 * 6.4516 * $largoPromCm) / 1000000.0;
        if ($volM3 <= 0) {
            return null;
        }
        $pvBruto = $masaKg / $volM3;
        $usar10 = $redondearA10 ?? self::obtenerBool('cilindros_redondear_pv_10', true, $db);
        return $usar10 ? (round($pvBruto / 10.0) * 10.0) : round($pvBruto, 0);
    }

    /**
     * Fórmula oficial RT-FM-61 (acuerdo 16 nov):
     * - R (psi) = redondear_a_10( P_efectiva (lb) / A (in²) )
     * - R (kgf/cm²) = round( R (psi) ya redondeado / divisor, 0 )  [divisor configurable, default 14.2029]
     *
     * @param array<string, mixed> $opciones Overrides opcionales: divisor_kgcm2, redondear_psi_10, usar_prensa, usar_factor_ld, largo_prom_cm, diametro_prom_cm
     * @return array{carga_efectiva_lb: float, factor_ld: float, esfuerzo_psi_bruto: float, esfuerzo_psi: float, esfuerzo_kgcm2: float, divisor_kgcm2: float}
     */
    public static function calcularResistenciaCilindro(
        float $cargaLb,
        float $areaIn2,
        array $opciones = [],
        ?PDO $db = null
    ): array {
        $divisor = isset($opciones['divisor_kgcm2']) && is_numeric($opciones['divisor_kgcm2']) && (float)$opciones['divisor_kgcm2'] > 0
            ? (float)$opciones['divisor_kgcm2']
            : self::obtenerFloat('cilindros_divisor_kgcm2', 14.2029, $db);
        if ($divisor <= 0) {
            $divisor = 14.2029;
        }

        $redondearPsi10 = array_key_exists('redondear_psi_10', $opciones)
            ? (bool)$opciones['redondear_psi_10']
            : self::obtenerBool('cilindros_redondear_psi_10', true, $db);

        $usarPrensa = array_key_exists('usar_prensa', $opciones)
            ? (bool)$opciones['usar_prensa']
            : self::obtenerBool('cilindros_usar_prensa', false, $db);

        $usarFactorLd = array_key_exists('usar_factor_ld', $opciones)
            ? (bool)$opciones['usar_factor_ld']
            : self::obtenerBool('cilindros_usar_factor_ld', false, $db);

        $cargaEfectiva = $cargaLb;
        if ($usarPrensa && $cargaLb > 0) {
            $a = (float)($opciones['prensa_a_e14'] ?? -4.6765);
            $b = (float)($opciones['prensa_b_e8'] ?? 2.5071);
            $c = (float)($opciones['prensa_c'] ?? 0.99583);
            $d = (float)($opciones['prensa_d'] ?? 3.7217);
            $cargaEfectiva = $a * (($cargaLb / 100000.0) ** 3) * 10.0
                + $b * (($cargaLb / 10000.0) ** 2)
                + $c * $cargaLb
                + $d;
        }

        $factorLd = 1.0;
        if ($usarFactorLd && !empty($opciones['largo_prom_cm']) && !empty($opciones['diametro_prom_cm'])) {
            $ld = (float)$opciones['largo_prom_cm'] / (float)$opciones['diametro_prom_cm'];
            if ($ld > 0 && $ld < 1.75) {
                $ldA = (float)($opciones['ld_a'] ?? 0.2133);
                $ldB = (float)($opciones['ld_b'] ?? -1.04);
                $ldC = (float)($opciones['ld_c'] ?? 1.7667);
                $ldD = (float)($opciones['ld_d'] ?? -0.07);
                $factorLd = $ldA * ($ld ** 3) + $ldB * ($ld ** 2) + $ldC * $ld + $ldD;
            }
        }

        $psiBruto = ($areaIn2 > 0) ? (($cargaEfectiva * $factorLd) / $areaIn2) : 0.0;
        $psiFinal = $redondearPsi10 ? (round($psiBruto / 10.0) * 10.0) : round($psiBruto, 2);
        $kgCm2Final = round($psiFinal / $divisor, 0);

        return [
            'carga_efectiva_lb' => round($cargaEfectiva, 2),
            'factor_ld' => round($factorLd, 4),
            'esfuerzo_psi_bruto' => round($psiBruto, 2),
            'esfuerzo_psi' => (float)$psiFinal,
            'esfuerzo_kgcm2' => (float)$kgCm2Final,
            'divisor_kgcm2' => $divisor,
        ];
    }

    /**
     * Fórmula oficial RT-FM-61 / ASTM C143:
     * Revenimiento (cm) = round(in * 2.54 * 2) / 2 (redondeo al 0.5 cm más cercano).
     */
    public static function calcularRevenimientoCm(float $pulgadas): float {
        return round($pulgadas * 2.54 * 2.0) / 2.0;
    }

    /**
     * Calcula la edad real en días enteros entre fecha de colado/moldeo y fecha de ensayo/rotura.
     */
    public static function calcularEdadDias(?string $fechaColado, ?string $fechaEnsayo): ?int {
        $f1 = trim((string)$fechaColado);
        $f2 = trim((string)$fechaEnsayo);
        if ($f1 === '' || $f2 === '') {
            return null;
        }
        $ts1 = strtotime(substr($f1, 0, 10));
        $ts2 = strtotime(substr($f2, 0, 10));
        if ($ts1 === false || $ts2 === false) {
            return null;
        }
        $diff = (int)round(($ts2 - $ts1) / 86400);
        return $diff >= 0 ? $diff : null;
    }

    /**
     * Tabla oficial de tolerancias de edad ASTM C39 (en horas):
     * - 24 h (1 día): ±0.5 h
     * - 3 días: ±2 h
     * - 7 días: ±6 h
     * - 28 días: ±20 h
     * - 90 días: ±48 h (2 días)
     */
    public static function obtenerToleranciaHorasAstmC39(int $edadDias): float {
        return match ($edadDias) {
            1 => 0.5,
            3 => 2.0,
            7 => 6.0,
            14 => 12.0,
            28 => 20.0,
            56 => 36.0,
            90 => 48.0,
            default => max(2.0, round($edadDias * 0.75, 1)),
        };
    }

    /**
     * Redondeo configurable para estimación de martillo suizo (RT-FM-59):
     * paso = 100 (centenas), 10 (decenas), o 1 (entero).
     */
    public static function redondearMartilloPsi(float $valor, ?int $paso = null, ?PDO $db = null): float {
        $p = $paso ?? (int)self::obtener('martillo_redondeo_psi', '100', $db);
        if ($p >= 10) {
            return round($valor / $p) * $p;
        }
        return round($valor, 0);
    }
}
