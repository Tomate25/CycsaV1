<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculosLaboratorioTest extends TestCase {

    /**
     * Calcula el esfuerzo a la compresión (ASTM C39) y realiza conversión de unidades.
     */
    public function calcularCompresion(float $carga, float $area, string $unidadCarga = 'kg', string $unidadArea = 'cm2'): ?array {
        if ($area <= 0 || $carga < 0) {
            return null;
        }

        $isLb = strtolower($unidadCarga) === 'lb';
        $isIn2 = strtolower($unidadArea) === 'in2';
        $factorKgCm2ToPsi = 14.2233433;
        $factorPsiToKgCm2 = 0.070307;

        if ($isLb && $isIn2) {
            $psi = $carga / $area;
            $kgcm2 = $psi * $factorPsiToKgCm2;
        } elseif (!$isLb && !$isIn2) {
            $kgcm2 = $carga / $area;
            $psi = $kgcm2 * $factorKgCm2ToPsi;
        } elseif ($isLb && !$isIn2) {
            $kgcm2 = ($carga * 0.45359237) / $area;
            $psi = $kgcm2 * $factorKgCm2ToPsi;
        } else {
            $psi = ($carga * 2.2046226) / $area;
            $kgcm2 = $psi * $factorPsiToKgCm2;
        }

        return [
            'esfuerzo_kg_cm2' => round($kgcm2, 2),
            'esfuerzo_psi' => round($psi, 2)
        ];
    }

    /**
     * Calcula el contenido de humedad en suelos (ASTM D2216).
     * w (%) = ((W_w - W_s) / (W_s - W_t)) * 100
     */
    public function calcularContenidoHumedad(float $pesoHumedoConTara, float $pesoSecoConTara, float $pesoTara): ?float {
        $pesoSueloSeco = $pesoSecoConTara - $pesoTara;
        $pesoAgua = $pesoHumedoConTara - $pesoSecoConTara;

        if ($pesoSueloSeco <= 0 || $pesoAgua < 0) {
            return null;
        }

        return round(($pesoAgua / $pesoSueloSeco) * 100, 2);
    }

    /**
     * Calcula el grado de compactación respecto al Proctor (ASTM D1556 / D6938).
     * % Comp = (densidad_seca_campo / densidad_max_proctor) * 100
     */
    public function calcularGradoCompactacion(float $densidadSecaCampo, float $densidadMaxProctor): ?float {
        if ($densidadMaxProctor <= 0 || $densidadSecaCampo < 0) {
            return null;
        }

        return round(($densidadSecaCampo / $densidadMaxProctor) * 100, 2);
    }

    /**
     * Calcula los días de edad de rotura entre la fecha de fabricación y ensayo.
     */
    public function calcularEdadRoturaDias(string $fechaMoldeo, string $fechaEnsayo): ?int {
        try {
            $dMoldeo = new \DateTimeImmutable($fechaMoldeo);
            $dEnsayo = new \DateTimeImmutable($fechaEnsayo);

            if ($dEnsayo < $dMoldeo) {
                return null;
            }

            $diff = $dMoldeo->diff($dEnsayo);
            return (int)$diff->format('%a');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // =========================================================================
    // 1. Pruebas de Compresión ASTM C39
    // =========================================================================

    public function testCompresionConcretoCilindroEstandar(): void {
        // Cilindro 4"x8": Área nominal 81.07 cm² (12.566 in²), carga de rotura 25,000 kg
        $resultado = $this->calcularCompresion(25000, 81.07, 'kg', 'cm2');

        $this->assertNotNull($resultado);
        $this->assertEquals(308.38, $resultado['esfuerzo_kg_cm2'], 'El esfuerzo en kg/cm² debe ser carga / area.');
        // 308.37548 * 14.2233433 = 4386.13 psi
        $this->assertEquals(4386.13, $resultado['esfuerzo_psi'], 'La conversión a psi debe aplicar factor 14.2233433.');
    }

    public function testCompresionConcretoEnLibrasYPulgadasCuadradas(): void {
        // Carga en lb y Área en in²: Carga 55,000 lb, Área 12.566 in²
        $resultado = $this->calcularCompresion(55000, 12.566, 'lb', 'in2');

        $this->assertNotNull($resultado);
        $this->assertEquals(4376.89, $resultado['esfuerzo_psi']);
        // 4376.8899 * 0.070307 = 307.73 kg/cm²
        $this->assertEquals(307.73, $resultado['esfuerzo_kg_cm2']);
    }

    public function testCompresionPrevieneDivisionPorCero(): void {
        $resultadoZero = $this->calcularCompresion(30000, 0, 'kg', 'cm2');
        $this->assertNull($resultadoZero, 'El cálculo con área cero debe retornar null y no generar división por cero.');

        $resultadoNegativo = $this->calcularCompresion(30000, -15.5, 'kg', 'cm2');
        $this->assertNull($resultadoNegativo, 'El cálculo con área negativa debe retornar null.');
    }

    public function testCompresionRechazaCargaNegativa(): void {
        $resultado = $this->calcularCompresion(-500, 100, 'kg', 'cm2');
        $this->assertNull($resultado, 'Cargas negativas no son físicamente válidas y deben retornar null.');
    }

    // =========================================================================
    // 2. Pruebas de Contenido de Humedad ASTM D2216
    // =========================================================================

    public function testContenidoHumedadCalculoEstandar(): void {
        // Muestra de suelo: húmedo+tara = 150.80 g, seco+tara = 135.20 g, tara = 32.10 g
        // Peso agua = 15.60 g, Peso seco = 103.10 g
        // w = (15.60 / 103.10) * 100 = 15.13%
        $humedad = $this->calcularContenidoHumedad(150.80, 135.20, 32.10);

        $this->assertNotNull($humedad);
        $this->assertEquals(15.13, $humedad);
    }

    public function testContenidoHumedadCeroPorCiento(): void {
        // Suelo completamente seco: peso húmedo igual a peso seco
        $humedad = $this->calcularContenidoHumedad(120.00, 120.00, 25.00);

        $this->assertNotNull($humedad);
        $this->assertEquals(0.00, $humedad);
    }

    public function testContenidoHumedadPrevieneDivisionPorCeroOTaraInvalida(): void {
        // Peso seco igual a peso tara (sin suelo) -> división por cero
        $humedad = $this->calcularContenidoHumedad(100.00, 30.00, 30.00);
        $this->assertNull($humedad, 'Debe prevenir división por cero cuando peso seco es igual a la tara.');

        // Peso tara mayor a peso seco (imposible)
        $humedadInvalida = $this->calcularContenidoHumedad(100.00, 30.00, 40.00);
        $this->assertNull($humedadInvalida, 'Debe retornar null cuando el peso seco es menor a la tara.');
    }

    public function testContenidoHumedadRechazaPesoHumedoMenorQueSeco(): void {
        // Muestra donde el peso húmedo es menor que el seco (anomalía de pesaje)
        $humedad = $this->calcularContenidoHumedad(110.00, 120.00, 20.00);
        $this->assertNull($humedad, 'No puede existir contenido de humedad con agua negativa.');
    }

    // =========================================================================
    // 3. Pruebas de Grado de Compactación ASTM D1556 / D6938
    // =========================================================================

    public function testGradoCompactacionCalculoEstandar(): void {
        // Densidad seca en sitio = 1980 kg/m³, Proctor máximo = 2050 kg/m³
        // % Comp = (1980 / 2050) * 100 = 96.59%
        $comp = $this->calcularGradoCompactacion(1980, 2050);

        $this->assertNotNull($comp);
        $this->assertEquals(96.59, $comp);
    }

    public function testGradoCompactacionPrevieneDivisionPorCero(): void {
        $comp = $this->calcularGradoCompactacion(1900, 0);
        $this->assertNull($comp, 'Debe retornar null si la densidad Proctor máxima es cero.');
    }

    public function testGradoCompactacionSoportaSobrecompactacion(): void {
        // En suelos o sub-bases granulares bien compactadas es factible obtener > 100%
        $comp = $this->calcularGradoCompactacion(2100, 2050);

        $this->assertNotNull($comp);
        $this->assertEquals(102.44, $comp);
    }

    // =========================================================================
    // 4. Pruebas de Edad de Rotura (Días)
    // =========================================================================

    public function testEdadRoturaMismoDia(): void {
        $dias = $this->calcularEdadRoturaDias('2026-05-10', '2026-05-10');
        $this->assertSame(0, $dias);
    }

    public function testEdadRoturaEstandar28Dias(): void {
        // Periodo típico de diseño de 28 días
        $dias = $this->calcularEdadRoturaDias('2026-03-01', '2026-03-29');
        $this->assertSame(28, $dias);
    }

    public function testEdadRoturaConCambioDeMes(): void {
        // Del 25 de enero al 22 de febrero en año común (2026):
        // Enero tiene 31 días (6 días restantes) + 22 días de febrero = 28 días
        $dias = $this->calcularEdadRoturaDias('2026-01-25', '2026-02-22');
        $this->assertSame(28, $dias);
    }

    public function testEdadRoturaEnAnoBisiesto(): void {
        // 2024 fue bisiesto (febrero con 29 días).
        // Del 15 de febrero de 2024 al 14 de marzo de 2024:
        // Febrero aporta 29 - 15 = 14 días + 14 días de marzo = 28 días.
        $diasBisiesto = $this->calcularEdadRoturaDias('2024-02-15', '2024-03-14');
        $this->assertSame(28, $diasBisiesto);

        // En año no bisiesto (2025), el mismo rango da 27 días:
        $diasNoBisiesto = $this->calcularEdadRoturaDias('2025-02-15', '2025-03-14');
        $this->assertSame(27, $diasNoBisiesto);
    }

    public function testEdadRoturaConCambioDeAno(): void {
        // Del 20 de diciembre de 2025 al 17 de enero de 2026:
        // 11 días en dic + 17 días en enero = 28 días
        $dias = $this->calcularEdadRoturaDias('2025-12-20', '2026-01-17');
        $this->assertSame(28, $dias);
    }

    public function testEdadRoturaRechazaFechaEnsayoAnteriorAFabricacion(): void {
        // Fecha de ensayo previa a la fecha de moldeo
        $dias = $this->calcularEdadRoturaDias('2026-05-15', '2026-05-10');
        $this->assertNull($dias, 'Una fecha de rotura anterior a la fabricación debe retornar null.');
    }
}
