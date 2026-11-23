<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use PDO;

/**
 * Servicio Maestro de Captura Técnica de Laboratorio Integrada en Cada Matriz (RT-FM-22).
 *
 * Cubre el 100% de los 28 formatos oficiales registrados en `formatos_schema.json` y `formatos_ensayos`,
 * definiendo las mediciones crudas exactas que llena el técnico en laboratorio/campo (D1, D2, L1, L2,
 * Masa, Carga, Fecha Ensayo, Tipo de Falla, taras, rebotes, lecturas de dial, etc.) y ejecutando el
 * cálculo oficial autoritativo para auto-llenar la Matriz de Informe `RT-FM-22`.
 */
class HojaTecnicaMatrizServicio {

    /**
     * Normaliza nombres de archivo markdown con o sin tildes/espacios.
     */
    public static function normalizarArchivoMd(?string $archivoMd): string {
        $raw = strtolower(trim((string)$archivoMd));
        $sinTildes = strtr($raw, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
        return $sinTildes;
    }

    /**
     * Retorna el mapa completo de definiciones de captura técnica para los 28 formatos de matriz CYCSA.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function obtenerCatalogoDefiniciones(): array {
        $opcionesFallaC39 = [
            'Tipo 1 (Cono bien formado)',
            'Tipo 2 (Cono y división)',
            'Tipo 3 (División columnar)',
            'Tipo 4 (Fractura diagonal)',
            'Tipo 5 (Fractura en esquinas)',
            'Tipo 6 (Extremo en punta)',
        ];

        $opcionesVarilla = ['#3 (3/8")', '#4 (1/2")', '#5 (5/8")', '#6 (3/4")', '#7 (7/8")', '#8 (1")', '#9 (1 1/8")', '#10 (1 1/4")', '#11 (1 3/8")'];

        $defCilindrosPsi = [
            'codigo_hc' => 'RT-FM-61',
            'norma' => 'ASTM C39 / C31 / C42',
            'titulo' => 'Captura Técnica de Laboratorio — Compresión de Cilindros / Núcleos (RT-FM-61)',
            'subtitulo' => 'Llene las mediciones de prensa y geometría del espécimen. El Área (in²), Peso Volumétrico, Edad y Resistencia (psi y kgf/cm²) se calculan automáticamente y llenan la matriz RT-FM-22.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'cilindro_psi',
            'campos_entrada' => [
                ['clave' => 'diametro_1', 'etiqueta' => 'D1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'D1 cm', 'ancho' => 76],
                ['clave' => 'diametro_2', 'etiqueta' => 'D2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'D2 cm', 'ancho' => 76],
                ['clave' => 'largo_1', 'etiqueta' => 'L1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'L1 cm', 'ancho' => 76],
                ['clave' => 'largo_2', 'etiqueta' => 'L2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'L2 cm', 'ancho' => 76],
                ['clave' => 'masa_kg', 'etiqueta' => 'Masa (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 76],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 98],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 165],
                ['clave' => 'tipo_falla', 'etiqueta' => 'Tipo de Falla', 'tipo' => 'select', 'opciones' => $opcionesFallaC39, 'default' => 'Tipo 1 (Cono bien formado)', 'ancho' => 155],
            ],
            'campos_calculados' => [
                ['clave' => 'area_in2', 'etiqueta' => 'Área (in²)', 'placeholder' => 'D1 y D2', 'ancho' => 84],
                ['clave' => 'peso_volumetrico', 'etiqueta' => 'PV (kg/m³)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'R. Comp. (psi)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Comp. (kg/cm²)', 'placeholder' => '--', 'ancho' => 94, 'destacado' => true],
            ],
        ];

        $defCilindrosMetricos = [
            'codigo_hc' => 'RT-FM-61 (Métrico)',
            'norma' => 'ASTM D1633-17 (CYCSA-PE-58)',
            'titulo' => 'Captura Técnica de Laboratorio — Cilindros Suelo-Cemento / Lodo Concreto',
            'subtitulo' => 'Capture diámetros, longitudes, masa y carga en prensa (lb o kg). El sistema calcula Área (cm²), Carga (kg), Peso Volumétrico (kg/m³) y Resistencia (kg/cm²).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'cilindro_metrico',
            'campos_entrada' => [
                ['clave' => 'diametro_1', 'etiqueta' => 'D1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'D1 cm', 'ancho' => 76],
                ['clave' => 'diametro_2', 'etiqueta' => 'D2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'D2 cm', 'ancho' => 76],
                ['clave' => 'largo_1', 'etiqueta' => 'L1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'L1 cm', 'ancho' => 76],
                ['clave' => 'largo_2', 'etiqueta' => 'L2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'L2 cm', 'ancho' => 76],
                ['clave' => 'masa_kg', 'etiqueta' => 'Masa (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 76],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 94],
                ['clave' => 'carga_kg', 'etiqueta' => 'ó Carga (kg)', 'tipo' => 'decimal', 'placeholder' => 'Carga kg', 'ancho' => 90],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 165],
                ['clave' => 'tipo_falla', 'etiqueta' => 'Tipo de Falla', 'tipo' => 'select', 'opciones' => $opcionesFallaC39, 'default' => 'Tipo 1 (Cono bien formado)', 'ancho' => 150],
            ],
            'campos_calculados' => [
                ['clave' => 'area_cm2', 'etiqueta' => 'Área (cm²)', 'placeholder' => 'D1 y D2', 'ancho' => 84],
                ['clave' => 'peso_volumetrico', 'etiqueta' => 'PV (kg/m³)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Comp. (kg/cm²)', 'placeholder' => '--', 'ancho' => 94, 'destacado' => true],
            ],
        ];

        $defMortero = [
            'codigo_hc' => 'RT-FM-57',
            'norma' => 'ASTM C109M-24 (CYCSA-PE-57)',
            'titulo' => 'Captura Técnica de Laboratorio — Cubos de Mortero 2" (RT-FM-57)',
            'subtitulo' => 'Capture las dimensiones del cubo (Largo, Ancho, Alto en cm), Peso (kg), Carga (lb) y Fecha de Ensayo. El área, peso volumétrico y resistencia se sincronizan con RT-FM-22 A.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'mortero',
            'campos_entrada' => [
                ['clave' => 'largo_cm', 'etiqueta' => 'Largo (cm)', 'tipo' => 'decimal', 'placeholder' => '5.00', 'ancho' => 80],
                ['clave' => 'ancho_cm', 'etiqueta' => 'Ancho (cm)', 'tipo' => 'decimal', 'placeholder' => '5.00', 'ancho' => 80],
                ['clave' => 'alto_cm', 'etiqueta' => 'Alto (cm)', 'tipo' => 'decimal', 'placeholder' => '5.00', 'ancho' => 80],
                ['clave' => 'masa_kg', 'etiqueta' => 'Peso (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 80],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 98],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 165],
            ],
            'campos_calculados' => [
                ['clave' => 'area_in2', 'etiqueta' => 'Área (in²)', 'placeholder' => 'L × A', 'ancho' => 82],
                ['clave' => 'peso_volumetrico', 'etiqueta' => 'PV (kg/m³)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'R. Comp. (psi)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Comp. (kg/cm²)', 'placeholder' => '--', 'ancho' => 94, 'destacado' => true],
            ],
        ];

        $defBloques = [
            'codigo_hc' => 'RT-FM-36',
            'norma' => 'ASTM C140 / NTON 12 008-16 (CYCSA-PE-32)',
            'titulo' => 'Captura Técnica de Laboratorio — Bloques de Mampostería (RT-FM-36)',
            'subtitulo' => 'Registre dimensiones, espesores, masas (recibida, seca, sumergida, saturada) y carga de cada bloque. Los promedios (área bruta, área neta, absorción y resistencia sobre área neta) llenan automáticamente la matriz.',
            'modo_filas' => 'unidades_bloque',
            'permite_agregar_filas' => true,
            'etiqueta_fila' => 'Bloque',
            'filas_default' => 3,
            'motor_calculo' => 'bloque',
            'campos_entrada' => [
                ['clave' => 'largo_cm', 'etiqueta' => 'Largo (cm)', 'tipo' => 'decimal', 'placeholder' => '39.0', 'ancho' => 76],
                ['clave' => 'alto_cm', 'etiqueta' => 'Alto (cm)', 'tipo' => 'decimal', 'placeholder' => '19.0', 'ancho' => 76],
                ['clave' => 'ancho_cm', 'etiqueta' => 'Ancho (cm)', 'tipo' => 'decimal', 'placeholder' => '15.0', 'ancho' => 76],
                ['clave' => 'esp_ext_cm', 'etiqueta' => 'Esp. Ext (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 82],
                ['clave' => 'esp_int_cm', 'etiqueta' => 'Esp. Int (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 82],
                ['clave' => 'masa_recibida_kg', 'etiqueta' => 'M. Recib. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 82],
                ['clave' => 'masa_seca_kg', 'etiqueta' => 'M. Seca (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 82],
                ['clave' => 'masa_sumergida_kg', 'etiqueta' => 'M. Sumerg. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 86],
                ['clave' => 'masa_saturada_kg', 'etiqueta' => 'M. Sat. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 82],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 94],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 132],
            ],
            'campos_calculados' => [
                ['clave' => 'area_bruta_in2', 'etiqueta' => 'A. Bruta (in²)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'area_neta_in2', 'etiqueta' => 'A. Neta (in²)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'absorcion_pct', 'etiqueta' => 'Abs. (%)', 'placeholder' => '--', 'ancho' => 72],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'R. Neta (psi)', 'placeholder' => '--', 'ancho' => 86, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Neta (kg/cm²)', 'placeholder' => '--', 'ancho' => 92, 'destacado' => true],
            ],
        ];

        $defAdoquines = [
            'codigo_hc' => 'RT-FM-63',
            'norma' => 'NTON 12 009 / ASTM C140 (CYCSA-PE-32)',
            'titulo' => 'Captura Técnica de Laboratorio — Adoquines de Concreto (RT-FM-63)',
            'subtitulo' => 'Capture dimensiones (Largo, Ancho, Altura en cm), Carga (lb) y masas para absorción. Calcula Área (in²), Peso Volumétrico, Resistencia y Absorción (%) en vivo.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'adoquin',
            'campos_entrada' => [
                ['clave' => 'largo_cm', 'etiqueta' => 'Largo (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 76],
                ['clave' => 'ancho_cm', 'etiqueta' => 'Ancho (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 76],
                ['clave' => 'alto_cm', 'etiqueta' => 'Altura (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 76],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 94],
                ['clave' => 'masa_seca_kg', 'etiqueta' => 'M. Seca (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 80],
                ['clave' => 'masa_sumergida_kg', 'etiqueta' => 'M. Sumerg. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 84],
                ['clave' => 'masa_saturada_kg', 'etiqueta' => 'M. Sat. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 80],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 165],
            ],
            'campos_calculados' => [
                ['clave' => 'area_in2', 'etiqueta' => 'Área (in²)', 'placeholder' => '--', 'ancho' => 80],
                ['clave' => 'peso_volumetrico', 'etiqueta' => 'PV (kg/m³)', 'placeholder' => '--', 'ancho' => 80],
                ['clave' => 'absorcion_pct', 'etiqueta' => 'Abs. (%)', 'placeholder' => '--', 'ancho' => 74],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'R. Comp. (psi)', 'placeholder' => '--', 'ancho' => 86, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Comp. (kg/cm²)', 'placeholder' => '--', 'ancho' => 92, 'destacado' => true],
            ],
        ];

        $defFlexion = [
            'codigo_hc' => 'RT-FM-38',
            'norma' => 'ASTM C78 (CYCSA-PE-30)',
            'titulo' => 'Captura Técnica de Laboratorio — Viguetas a Flexión (RT-FM-38)',
            'subtitulo' => 'Capture las 3 lecturas de ancho y espesor (in), luz entre apoyos L (in), masa (kg), carga (lb) y zona de fractura. Evalúa automáticamente R = P·L/(b·d²) o 3·P·a/(b·d²) y valida si el ensayo es válido.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'flexion',
            'campos_entrada' => [
                ['clave' => 'ancho_1', 'etiqueta' => 'b1 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'ancho_2', 'etiqueta' => 'b2 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'ancho_3', 'etiqueta' => 'b3 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'espesor_1', 'etiqueta' => 'd1 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'espesor_2', 'etiqueta' => 'd2 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'espesor_3', 'etiqueta' => 'd3 (in)', 'tipo' => 'decimal', 'placeholder' => '6.0', 'ancho' => 68],
                ['clave' => 'luz_in', 'etiqueta' => 'Luz L (in)', 'tipo' => 'decimal', 'placeholder' => '18.0', 'ancho' => 76],
                ['clave' => 'masa_kg', 'etiqueta' => 'Masa (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 76],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'Carga lb', 'ancho' => 92],
                ['clave' => 'en_tercio_medio', 'etiqueta' => '¿1/3 Medio?', 'tipo' => 'select', 'opciones' => ['Sí (Tercio Medio)', 'No (<5% de L)', 'No (>=5% Descartar)'], 'default' => 'Sí (Tercio Medio)', 'ancho' => 142],
                ['clave' => 'dist_a_in', 'etiqueta' => 'Dist. a (in)', 'tipo' => 'decimal', 'placeholder' => 'Solo si No', 'ancho' => 78],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 160],
            ],
            'campos_calculados' => [
                ['clave' => 'ancho_prom', 'etiqueta' => 'b prom (in)', 'placeholder' => '--', 'ancho' => 76],
                ['clave' => 'espesor_prom', 'etiqueta' => 'd prom (in)', 'placeholder' => '--', 'ancho' => 76],
                ['clave' => 'mr_kgcm2', 'etiqueta' => 'MR (kg/cm²)', 'placeholder' => '--', 'ancho' => 86, 'destacado' => true],
                ['clave' => 'dictamen_falla', 'etiqueta' => 'Dictamen', 'placeholder' => '--', 'ancho' => 94],
            ],
        ];

        $defMartilloSuizo = [
            'codigo_hc' => 'RT-FM-59',
            'norma' => 'ASTM C805/C805M-25 (CYCSA-PE-12)',
            'titulo' => 'Captura Técnica de Laboratorio — Martillo Suizo / Esclerómetro (RT-FM-59)',
            'subtitulo' => 'Seleccione la posición de disparo (A abajo, B horizontal, C arriba) e ingrese las 10 lecturas de rebote. Descarta atípicos (±6 ASTM C805) y estima resistencia en psi y kg/cm².',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'martillo_suizo',
            'campos_entrada' => [
                ['clave' => 'posicion', 'etiqueta' => 'Posición', 'tipo' => 'select', 'opciones' => ['A', 'B', 'C'], 'default' => 'B', 'ancho' => 78],
                ['clave' => 'r1', 'etiqueta' => 'R1', 'tipo' => 'decimal', 'placeholder' => 'R1', 'ancho' => 56],
                ['clave' => 'r2', 'etiqueta' => 'R2', 'tipo' => 'decimal', 'placeholder' => 'R2', 'ancho' => 56],
                ['clave' => 'r3', 'etiqueta' => 'R3', 'tipo' => 'decimal', 'placeholder' => 'R3', 'ancho' => 56],
                ['clave' => 'r4', 'etiqueta' => 'R4', 'tipo' => 'decimal', 'placeholder' => 'R4', 'ancho' => 56],
                ['clave' => 'r5', 'etiqueta' => 'R5', 'tipo' => 'decimal', 'placeholder' => 'R5', 'ancho' => 56],
                ['clave' => 'r6', 'etiqueta' => 'R6', 'tipo' => 'decimal', 'placeholder' => 'R6', 'ancho' => 56],
                ['clave' => 'r7', 'etiqueta' => 'R7', 'tipo' => 'decimal', 'placeholder' => 'R7', 'ancho' => 56],
                ['clave' => 'r8', 'etiqueta' => 'R8', 'tipo' => 'decimal', 'placeholder' => 'R8', 'ancho' => 56],
                ['clave' => 'r9', 'etiqueta' => 'R9', 'tipo' => 'decimal', 'placeholder' => 'R9', 'ancho' => 56],
                ['clave' => 'r10', 'etiqueta' => 'R10', 'tipo' => 'decimal', 'placeholder' => 'R10', 'ancho' => 56],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 130],
            ],
            'campos_calculados' => [
                ['clave' => 'rebote_promedio', 'etiqueta' => 'Prom. Rebote', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'Est. (lb/in²)', 'placeholder' => '--', 'ancho' => 86, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'Est. (kg/cm²)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
            ],
        ];

        $defProctor = [
            'codigo_hc' => 'RT-FM-27',
            'norma' => 'ASTM D698 / D1557 (CYCSA-PE-18)',
            'titulo' => 'Captura Técnica de Laboratorio — Proctor Estándar / Modificado (RT-FM-27)',
            'subtitulo' => 'Capture los puntos de compactación (molde + suelo húmedo, masa y volumen del molde, taras de humedad). El sistema calcula PVS Máximo y Humedad Óptima y los llena en la matriz RT-FM-22 C.',
            'modo_filas' => 'puntos_proctor',
            'permite_agregar_filas' => true,
            'etiqueta_fila' => 'Punto',
            'filas_default' => 5,
            'motor_calculo' => 'proctor',
            'campos_entrada' => [
                ['clave' => 'molde_humedo_g', 'etiqueta' => 'Molde+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 98],
                ['clave' => 'masa_molde_g', 'etiqueta' => 'Masa Molde (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 94],
                ['clave' => 'volumen_molde_ml', 'etiqueta' => 'Vol. Molde (mL)', 'tipo' => 'decimal', 'placeholder' => '947.26', 'ancho' => 96],
                ['clave' => 'tara_humedo_g', 'etiqueta' => 'Tara+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 94],
                ['clave' => 'tara_seco_g', 'etiqueta' => 'Tara+Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 94],
                ['clave' => 'tara_g', 'etiqueta' => 'Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 78],
                ['clave' => 'metodo_proctor', 'etiqueta' => 'Método', 'tipo' => 'select', 'opciones' => ['Proctor Estándar A', 'Proctor Estándar B', 'Proctor Estándar C', 'Proctor Modificado A', 'Proctor Modificado B', 'Proctor Modificado C'], 'default' => 'Proctor Estándar A', 'ancho' => 150],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 130],
            ],
            'campos_calculados' => [
                ['clave' => 'humedad_pct', 'etiqueta' => 'Humedad (%)', 'placeholder' => '--', 'ancho' => 86],
                ['clave' => 'densidad_humeda', 'etiqueta' => 'PVH (kg/m³)', 'placeholder' => '--', 'ancho' => 88],
                ['clave' => 'densidad_seca', 'etiqueta' => 'PVS (kg/m³)', 'placeholder' => '--', 'ancho' => 90, 'destacado' => true],
            ],
        ];

        $defConoArena = [
            'codigo_hc' => 'RT-FM-55',
            'norma' => 'ASTM D1556/D1556M-24 (CYCSA-PE-20)',
            'titulo' => 'Captura Técnica de Campo/Lab — Compactación por Cono de Arena (RT-FM-55)',
            'subtitulo' => 'Ingrese arena empleada/sobrante, material excavado y taras de humedad + PVS Máximo de Proctor. Calcula P.V.S. Sitio, Humedad Sitio y % Compactación automáticamente.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'cono_arena',
            'campos_entrada' => [
                ['clave' => 'arena_empleada_g', 'etiqueta' => 'Arena Emp. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'arena_sobrante_g', 'etiqueta' => 'Arena Sob. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'arena_cono_g', 'etiqueta' => 'Arena Cono (g)', 'tipo' => 'decimal', 'placeholder' => 'g (opt)', 'ancho' => 90],
                ['clave' => 'pu_arena', 'etiqueta' => 'PU Arena (kg/m³)', 'tipo' => 'decimal', 'placeholder' => '1250', 'ancho' => 98],
                ['clave' => 'excavado_tara_g', 'etiqueta' => 'Exc.+Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'tara_exc_g', 'etiqueta' => 'Tara Exc. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 84],
                ['clave' => 'rec_humedo_g', 'etiqueta' => 'Rec.+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'rec_seco_g', 'etiqueta' => 'Rec.+Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'rec_g', 'etiqueta' => 'Recip. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 76],
                ['clave' => 'pvs_max', 'etiqueta' => 'PVS Máx (kg/m³)', 'tipo' => 'decimal', 'placeholder' => 'Proctor', 'ancho' => 96],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 128],
            ],
            'campos_calculados' => [
                ['clave' => 'humedad_pct', 'etiqueta' => 'Hum. Sitio (%)', 'placeholder' => '--', 'ancho' => 86],
                ['clave' => 'pvs_sitio', 'etiqueta' => 'PVS Sitio (kg/m³)', 'placeholder' => '--', 'ancho' => 96, 'destacado' => true],
                ['clave' => 'compactacion_pct', 'etiqueta' => 'Compact. (%)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
            ],
        ];

        $defReemplazoAgua = [
            'codigo_hc' => 'RT-FM-54',
            'norma' => 'ASTM D5030 (CYCSA-PE-46)',
            'titulo' => 'Captura Técnica de Campo/Lab — Compactación por Reemplazo de Agua (RT-FM-54)',
            'subtitulo' => 'Ingrese agua de anillo y pozo (L), material excavado húmedo (kg) y taras de humedad. Calcula P.V.S. Sitio, Humedad Sitio y % Compactación automáticamente.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'reemplazo_agua',
            'campos_entrada' => [
                ['clave' => 'agua_anillo_l', 'etiqueta' => 'Agua Anillo (L)', 'tipo' => 'decimal', 'placeholder' => 'L', 'ancho' => 92],
                ['clave' => 'agua_pozo_anillo_l', 'etiqueta' => 'Agua Pozo+An. (L)', 'tipo' => 'decimal', 'placeholder' => 'L', 'ancho' => 104],
                ['clave' => 'excavado_humedo_kg', 'etiqueta' => 'Mat. Exc. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 92],
                ['clave' => 'rec_humedo_g', 'etiqueta' => 'Rec.+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'rec_seco_g', 'etiqueta' => 'Rec.+Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'rec_g', 'etiqueta' => 'Recip. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 76],
                ['clave' => 'pvs_max', 'etiqueta' => 'PVS Máx (kg/m³)', 'tipo' => 'decimal', 'placeholder' => 'Proctor', 'ancho' => 96],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 128],
            ],
            'campos_calculados' => [
                ['clave' => 'humedad_pct', 'etiqueta' => 'Hum. Sitio (%)', 'placeholder' => '--', 'ancho' => 86],
                ['clave' => 'pvs_sitio', 'etiqueta' => 'PVS Sitio (kg/m³)', 'placeholder' => '--', 'ancho' => 96, 'destacado' => true],
                ['clave' => 'compactacion_pct', 'etiqueta' => 'Compact. (%)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
            ],
        ];

        $defDensimetro = [
            'codigo_hc' => 'PE-25 (Troxler)',
            'norma' => 'ASTM D6938-23 (CYCSA-PE-25)',
            'titulo' => 'Captura Técnica de Campo — Densímetro Nuclear (ASTM D6938)',
            'subtitulo' => 'Registre las lecturas del equipo nuclear (PVS Sitio, Humedad Sitio y referencia Proctor). El porcentaje de compactación se calcula en vivo y se refleja en la matriz RT-FM-22 B.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'densimetro',
            'campos_entrada' => [
                ['clave' => 'capa', 'etiqueta' => 'Capa (N°)', 'tipo' => 'texto', 'placeholder' => 'Capa', 'ancho' => 76],
                ['clave' => 'espesor_cm', 'etiqueta' => 'Espesor (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 82],
                ['clave' => 'profundidad_cm', 'etiqueta' => 'Prof. (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 82],
                ['clave' => 'pvs_max', 'etiqueta' => 'PVS Máx (kg/m³)', 'tipo' => 'decimal', 'placeholder' => 'Proctor', 'ancho' => 98],
                ['clave' => 'humedad_optima', 'etiqueta' => 'Hum. Ópt. (%)', 'tipo' => 'decimal', 'placeholder' => '%', 'ancho' => 88],
                ['clave' => 'pvs_sitio', 'etiqueta' => 'PVS Sitio (kg/m³)', 'tipo' => 'decimal', 'placeholder' => 'kg/m³', 'ancho' => 98],
                ['clave' => 'humedad_sitio_pct', 'etiqueta' => 'Hum. Sitio (%)', 'tipo' => 'decimal', 'placeholder' => '%', 'ancho' => 88],
                ['clave' => 'humedad_sitio_kgm3', 'etiqueta' => 'Hum. (kg/m³)', 'tipo' => 'decimal', 'placeholder' => 'kg/m³', 'ancho' => 90],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 128],
            ],
            'campos_calculados' => [
                ['clave' => 'compactacion_pct', 'etiqueta' => 'Compactación (%)', 'placeholder' => '--', 'ancho' => 96, 'destacado' => true],
            ],
        ];

        $defGranuloSuelo = [
            'codigo_hc' => 'RT-FM-25 + RT-FM-42',
            'norma' => 'ASTM D6913 / D4318 / D2216 (CYCSA-PE-16 / PE-17)',
            'titulo' => 'Captura Técnica de Laboratorio — Humedad y Límites de Atterberg (RT-FM-25 / RT-FM-42)',
            'subtitulo' => 'Registre las taras de humedad natural y Límites Líquido / Plástico. Los pesos retenidos por tamiz se capturan en la grilla granulométrica inferior; Humedad (%), LL, LP e IP se sincronizan automáticamente.',
            'modo_filas' => 'tamices_suelo',
            'permite_agregar_filas' => false,
            'etiqueta_fila' => 'Muestra',
            'filas_default' => 1,
            'motor_calculo' => 'granulometria_suelo',
            'campos_entrada' => [
                ['clave' => 'tara_g', 'etiqueta' => 'Tara Hum. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 88],
                ['clave' => 'humeda_tara_g', 'etiqueta' => 'Húm.+Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 94],
                ['clave' => 'seca_tara_g', 'etiqueta' => 'Seca+Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 94],
                ['clave' => 'll_tara_g', 'etiqueta' => 'LL Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 82],
                ['clave' => 'll_hum_g', 'etiqueta' => 'LL Húm+T (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'll_sec_g', 'etiqueta' => 'LL Sec+T (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'lp_tara_g', 'etiqueta' => 'LP Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 82],
                ['clave' => 'lp_hum_g', 'etiqueta' => 'LP Húm+T (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'lp_sec_g', 'etiqueta' => 'LP Sec+T (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
            ],
            'campos_calculados' => [
                ['clave' => 'humedad_pct', 'etiqueta' => 'Humedad (%)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'll_pct', 'etiqueta' => 'LL (%)', 'placeholder' => '--', 'ancho' => 72, 'destacado' => true],
                ['clave' => 'lp_pct', 'etiqueta' => 'LP (%)', 'placeholder' => '--', 'ancho' => 72, 'destacado' => true],
                ['clave' => 'ip_pct', 'etiqueta' => 'IP (%)', 'placeholder' => '--', 'ancho' => 72, 'destacado' => true],
            ],
        ];

        $defGranuloAgregados = [
            'codigo_hc' => 'RT-FM-25 (Agregados)',
            'norma' => 'ASTM C136 / C117 (CYCSA-PE-06)',
            'titulo' => 'Captura Técnica de Laboratorio — Control de Lavado y Masa Inicial (RT-FM-25)',
            'subtitulo' => 'Ingrese la masa seca inicial antes del lavado y después del lavado (g). Los pesos retenidos por tamiz en la grilla inferior calculan automáticamente % Retenido, % Pasa, Pérdida por Lavado y Módulo de Finura.',
            'modo_filas' => 'tamices_agregado',
            'permite_agregar_filas' => false,
            'etiqueta_fila' => 'Muestra',
            'filas_default' => 1,
            'motor_calculo' => 'granulometria_agregado',
            'campos_entrada' => [
                ['clave' => 'masa_inicial_g', 'etiqueta' => 'Masa Seca Inicial (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 126],
                ['clave' => 'masa_lavada_g', 'etiqueta' => 'Masa Lavada Seca (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 126],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 132],
            ],
            'campos_calculados' => [
                ['clave' => 'perdida_lavado_g', 'etiqueta' => 'Pérdida Lav. (g)', 'placeholder' => '--', 'ancho' => 104],
                ['clave' => 'perdida_lavado_pct', 'etiqueta' => 'Pérdida (%)', 'placeholder' => '--', 'ancho' => 90, 'destacado' => true],
            ],
        ];

        $defGeAgregados = [
            'codigo_hc' => 'RT-FM-35',
            'norma' => 'ASTM C127 / C128 (CYCSA-PE-02)',
            'titulo' => 'Captura Técnica de Laboratorio — GE y Absorción de Agregados (RT-FM-35)',
            'subtitulo' => 'Seleccione Fino (frasco) o Grueso (canasta sumergida) e ingrese las masas (g). Calcula Absorción (%), GE Bulk, GE SSS y GE Aparente automáticamente.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'ge_agregados',
            'campos_entrada' => [
                ['clave' => 'fraccion', 'etiqueta' => 'Fracción', 'tipo' => 'select', 'opciones' => ['Agregado Fino', 'Agregado Grueso'], 'default' => 'Agregado Fino', 'ancho' => 130],
                ['clave' => 'masa_sss_g', 'etiqueta' => 'Masa SSS (B) (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 96],
                ['clave' => 'masa_seca_g', 'etiqueta' => 'Masa Seca (A) (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 98],
                ['clave' => 'masa_frasco_agua_g', 'etiqueta' => 'Frasco+Agua (C) / Canasta (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 120],
                ['clave' => 'masa_sumergida_g', 'etiqueta' => 'Frasco+Ag+M (D) / Sumerg. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 124],
                ['clave' => 'color', 'etiqueta' => 'Color (PE-05)', 'tipo' => 'texto', 'placeholder' => 'Ej. Placa 2', 'ancho' => 90],
                ['clave' => 'humedad_natural', 'etiqueta' => 'Hum. Nat. (%)', 'tipo' => 'decimal', 'placeholder' => '%', 'ancho' => 86],
            ],
            'campos_calculados' => [
                ['clave' => 'absorcion_pct', 'etiqueta' => 'Absorción (%)', 'placeholder' => '--', 'ancho' => 84, 'destacado' => true],
                ['clave' => 'ge_bulk', 'etiqueta' => 'GE Bulk', 'placeholder' => '--', 'ancho' => 76, 'destacado' => true],
                ['clave' => 'ge_sss', 'etiqueta' => 'GE SSS', 'placeholder' => '--', 'ancho' => 76],
                ['clave' => 'ge_aparente', 'etiqueta' => 'GE Aparente', 'placeholder' => '--', 'ancho' => 84],
            ],
        ];

        $defCbr = [
            'codigo_hc' => 'RT-FM-46',
            'norma' => 'ASTM D1883-21 / AASHTO T193 (CYCSA-PE-11)',
            'titulo' => 'Captura Técnica de Laboratorio — Relación de Soporte California CBR (RT-FM-46)',
            'subtitulo' => 'Capture los 3 moldes CBR (12, 25 y 56 golpes): masas del molde, taras de humedad y lecturas del dial a 0.1" y 0.2". Calcula Densidad Seca, Compactación (%) y CBR Adoptado automáticamente.',
            'modo_filas' => 'moldes_cbr',
            'permite_agregar_filas' => true,
            'etiqueta_fila' => 'Molde',
            'filas_default' => 3,
            'motor_calculo' => 'cbr',
            'campos_entrada' => [
                ['clave' => 'golpes', 'etiqueta' => 'Golpes/Capa', 'tipo' => 'select', 'opciones' => ['12', '25', '56'], 'default' => '56', 'ancho' => 88],
                ['clave' => 'molde_humedo_g', 'etiqueta' => 'Molde+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 96],
                ['clave' => 'masa_molde_g', 'etiqueta' => 'Molde (g)', 'tipo' => 'decimal', 'placeholder' => '7849', 'ancho' => 84],
                ['clave' => 'volumen_molde_cm3', 'etiqueta' => 'Vol. (cm³)', 'tipo' => 'decimal', 'placeholder' => '3200', 'ancho' => 84],
                ['clave' => 'tara_humedo_g', 'etiqueta' => 'Tara+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'tara_seco_g', 'etiqueta' => 'Tara+Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'tara_g', 'etiqueta' => 'Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 76],
                ['clave' => 'pvs_max', 'etiqueta' => 'PVS Máx', 'tipo' => 'decimal', 'placeholder' => 'Proctor', 'ancho' => 86],
                ['clave' => 'lectura_01', 'etiqueta' => 'Lect. 0.1" (div)', 'tipo' => 'decimal', 'placeholder' => 'div', 'ancho' => 92],
                ['clave' => 'lectura_02', 'etiqueta' => 'Lect. 0.2" (div)', 'tipo' => 'decimal', 'placeholder' => 'div', 'ancho' => 92],
                ['clave' => 'constante_anillo', 'etiqueta' => 'K Anillo', 'tipo' => 'decimal', 'placeholder' => '10', 'ancho' => 74],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 126],
            ],
            'campos_calculados' => [
                ['clave' => 'densidad_seca', 'etiqueta' => 'PVS (kg/m³)', 'placeholder' => '--', 'ancho' => 86],
                ['clave' => 'compactacion_pct', 'etiqueta' => 'Comp. (%)', 'placeholder' => '--', 'ancho' => 78],
                ['clave' => 'cbr_01', 'etiqueta' => 'CBR 0.1" (%)', 'placeholder' => '--', 'ancho' => 82],
                ['clave' => 'cbr_02', 'etiqueta' => 'CBR 0.2" (%)', 'placeholder' => '--', 'ancho' => 82],
                ['clave' => 'cbr_adoptado', 'etiqueta' => 'CBR (%)', 'placeholder' => '--', 'ancho' => 76, 'destacado' => true],
            ],
        ];

        $defGeSuelos = [
            'codigo_hc' => 'RT-FM-56 / PE-14',
            'norma' => 'ASTM D854-23 (CYCSA-PE-14)',
            'titulo' => 'Captura Técnica de Laboratorio — Gravedad Específica de Suelos por Picnómetro',
            'subtitulo' => 'Seleccione el picnómetro, temperatura (°C), masa de suelo seco (Ms), masa picnómetro + agua (Mpw) y masa picnómetro + agua + suelo (Mpws). Calcula Gs automáticamente.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'ge_suelos',
            'campos_entrada' => [
                ['clave' => 'picnometro', 'etiqueta' => 'Picnómetro', 'tipo' => 'select', 'opciones' => ['PICNO-02', 'PICNO-03', 'PICNO-04', 'PICNO-05', 'PICNO-06', 'PICNO-07', 'PICNO-08', 'PICNO-09', 'PICNO-10'], 'default' => 'PICNO-02', 'ancho' => 104],
                ['clave' => 'temperatura_c', 'etiqueta' => 'Temp. (°C)', 'tipo' => 'decimal', 'placeholder' => '20.0', 'ancho' => 80],
                ['clave' => 'masa_suelo_seco_g', 'etiqueta' => 'Ms Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 92],
                ['clave' => 'masa_picno_agua_g', 'etiqueta' => 'Mpw Pic+Ag (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 102],
                ['clave' => 'masa_picno_agua_suelo_g', 'etiqueta' => 'Mpws Pic+Ag+S (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 112],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 130],
            ],
            'campos_calculados' => [
                ['clave' => 'factor_k', 'etiqueta' => 'Factor K(T)', 'placeholder' => '--', 'ancho' => 82],
                ['clave' => 'gs', 'etiqueta' => 'Gravedad Esp. (Gs)', 'placeholder' => '--', 'ancho' => 108, 'destacado' => true],
            ],
        ];

        $defAceroTension = [
            'codigo_hc' => 'RT-FM-64',
            'norma' => 'ASTM E8 / A370 / A615 (CYCSA-PE-61)',
            'titulo' => 'Captura Técnica de Laboratorio — Tensión en Varillas de Acero (RT-FM-64)',
            'subtitulo' => 'Capture calibre de varilla (#3 a #11), diámetro (mm), peso (kg), largo (cm), L0/Lf (cm), Fuerza Máx. (kN) y Fluencia (MPa). Calcula Área, Peso Unitario, Fluencia/Tensión (psi y kg/cm²) y Elongación (%).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'acero_tension',
            'campos_entrada' => [
                ['clave' => 'varilla_num', 'etiqueta' => 'Varilla N°', 'tipo' => 'select', 'opciones' => $opcionesVarilla, 'default' => '#4 (1/2")', 'ancho' => 102],
                ['clave' => 'diametro_mm', 'etiqueta' => 'Diám. (mm)', 'tipo' => 'decimal', 'placeholder' => '12.7', 'ancho' => 80],
                ['clave' => 'peso_kg', 'etiqueta' => 'Peso (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 76],
                ['clave' => 'largo_cm', 'etiqueta' => 'Largo (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 78],
                ['clave' => 'l0_cm', 'etiqueta' => 'L0 (cm)', 'tipo' => 'decimal', 'placeholder' => '20.0', 'ancho' => 74],
                ['clave' => 'lf_cm', 'etiqueta' => 'Lf (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 74],
                ['clave' => 'fuerza_max_kn', 'etiqueta' => 'Fm (kN)', 'tipo' => 'decimal', 'placeholder' => 'kN', 'ancho' => 82],
                ['clave' => 'fluencia_mpa', 'etiqueta' => 'Fluencia (MPa)', 'tipo' => 'decimal', 'placeholder' => 'MPa', 'ancho' => 94],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 126],
            ],
            'campos_calculados' => [
                ['clave' => 'area_mm2', 'etiqueta' => 'Área (mm²)', 'placeholder' => '--', 'ancho' => 80],
                ['clave' => 'peso_unitario', 'etiqueta' => 'kg/m', 'placeholder' => '--', 'ancho' => 70],
                ['clave' => 'fluencia_psi', 'etiqueta' => 'Fluencia (psi)', 'placeholder' => '--', 'ancho' => 88],
                ['clave' => 'tension_psi', 'etiqueta' => 'Tensión (psi)', 'placeholder' => '--', 'ancho' => 88, 'destacado' => true],
                ['clave' => 'elongacion_pct', 'etiqueta' => 'Elong. (%)', 'placeholder' => '--', 'ancho' => 76, 'destacado' => true],
            ],
        ];

        $defAceroDoblado = [
            'codigo_hc' => 'PE-38 (H-20)',
            'norma' => 'ASTM A615 / A706 (CYCSA-PE-38)',
            'titulo' => 'Captura Técnica de Laboratorio — Doblado Simple de Barras de Acero (H-20)',
            'subtitulo' => 'Registre el calibre de varilla, diámetro nominal, ángulo de doblado (°), diámetro de mandril y dictamen de inspección visual.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'acero_doblado',
            'campos_entrada' => [
                ['clave' => 'varilla_num', 'etiqueta' => 'Varilla N°', 'tipo' => 'select', 'opciones' => $opcionesVarilla, 'default' => '#4 (1/2")', 'ancho' => 104],
                ['clave' => 'diametro_mm', 'etiqueta' => 'Diám. Nom. (mm)', 'tipo' => 'decimal', 'placeholder' => '12.7', 'ancho' => 96],
                ['clave' => 'angulo_deg', 'etiqueta' => 'Ángulo (°)', 'tipo' => 'decimal', 'placeholder' => '180', 'ancho' => 82],
                ['clave' => 'mandril', 'etiqueta' => 'Mandril (D)', 'tipo' => 'texto', 'placeholder' => '3.5d / 5d', 'ancho' => 96],
                ['clave' => 'inspeccion', 'etiqueta' => 'Inspección Visual', 'tipo' => 'select', 'opciones' => ['Cumple (Sin grietas)', 'Falla (Presenta grietas)'], 'default' => 'Cumple (Sin grietas)', 'ancho' => 160],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 130],
            ],
            'campos_calculados' => [
                ['clave' => 'resultado', 'etiqueta' => 'Resultado', 'placeholder' => '--', 'ancho' => 94, 'destacado' => true],
            ],
        ];

        $defSanidad = [
            'codigo_hc' => 'RT-FM-58',
            'norma' => 'ASTM C88/C88M-24 (CYCSA-PE-52)',
            'titulo' => 'Captura Técnica de Laboratorio — Sanidad / Intemperismo Acelerado (RT-FM-58)',
            'subtitulo' => 'Capture por cada fracción de tamiz el retenido original, masa inicial y masa final retenida tras los ciclos de sulfato. Calcula % Gradación, Pérdida (g), % Pérdida y % Pérdida Ponderada.',
            'modo_filas' => 'fracciones_sanidad',
            'permite_agregar_filas' => true,
            'etiqueta_fila' => 'Fracción',
            'filas_default' => 4,
            'motor_calculo' => 'sanidad',
            'campos_entrada' => [
                ['clave' => 'fraccion', 'etiqueta' => 'Fracción / Tamiz', 'tipo' => 'texto', 'placeholder' => 'Ej. 3/4" - 3/8"', 'ancho' => 132],
                ['clave' => 'retenido_original_g', 'etiqueta' => 'Ret. Orig. (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 96],
                ['clave' => 'masa_inicial_g', 'etiqueta' => 'Masa Inicial (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 100],
                ['clave' => 'masa_final_g', 'etiqueta' => 'Masa Final (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 100],
            ],
            'campos_calculados' => [
                ['clave' => 'gradacion_pct', 'etiqueta' => '% Gradación', 'placeholder' => '--', 'ancho' => 88],
                ['clave' => 'perdida_g', 'etiqueta' => 'Pérdida (g)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'perdida_pct', 'etiqueta' => '% Pérdida', 'placeholder' => '--', 'ancho' => 82],
                ['clave' => 'perdida_ponderada_pct', 'etiqueta' => '% Ponderada', 'placeholder' => '--', 'ancho' => 90, 'destacado' => true],
            ],
        ];

        $defLadrillo = [
            'codigo_hc' => 'PE-32 (Ladrillo)',
            'norma' => 'ASTM C67 / C140 (CYCSA-PE-32)',
            'titulo' => 'Captura Técnica de Laboratorio — Compresión en Ladrillo de Barro',
            'subtitulo' => 'Capture Largo, Ancho, Alto (cm), Masa (kg), Carga (lb o kg) y Fecha de Ensayo. Calcula Área (cm²), Carga (kg), Peso Volumétrico y R. a la compresión (kg/cm²).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'ladrillo',
            'campos_entrada' => [
                ['clave' => 'largo_cm', 'etiqueta' => 'Largo (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 78],
                ['clave' => 'ancho_cm', 'etiqueta' => 'Ancho (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 78],
                ['clave' => 'alto_cm', 'etiqueta' => 'Alto (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 78],
                ['clave' => 'masa_kg', 'etiqueta' => 'Masa (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 78],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga (lb)', 'tipo' => 'decimal', 'placeholder' => 'lb', 'ancho' => 88],
                ['clave' => 'carga_kg', 'etiqueta' => 'ó Carga (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 88],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'datetime', 'placeholder' => '', 'ancho' => 160],
            ],
            'campos_calculados' => [
                ['clave' => 'area_cm2', 'etiqueta' => 'Área (cm²)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'peso_volumetrico', 'etiqueta' => 'PV (kg/m³)', 'placeholder' => '--', 'ancho' => 84],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'R. Comp. (kg/cm²)', 'placeholder' => '--', 'ancho' => 96, 'destacado' => true],
            ],
        ];

        $defRoca = [
            'codigo_hc' => 'PE-40 (H-22)',
            'norma' => 'ASTM D7012-23 (CYCSA-PE-40)',
            'titulo' => 'Captura Técnica de Laboratorio — Compresión Uniaxial en Núcleos de Roca (H-22)',
            'subtitulo' => 'Capture diámetros D1/D2 (cm), alturas H1/H2 (cm), masa (kg) y carga máxima (lb). Calcula relación L/D, Área (cm²) y Resistencia (MPa, kg/cm² y lbf/in²).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'roca',
            'campos_entrada' => [
                ['clave' => 'diametro_1', 'etiqueta' => 'D1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 74],
                ['clave' => 'diametro_2', 'etiqueta' => 'D2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 74],
                ['clave' => 'largo_1', 'etiqueta' => 'H1 (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 74],
                ['clave' => 'largo_2', 'etiqueta' => 'H2 (cm)', 'tipo' => 'decimal', 'placeholder' => 'cm', 'ancho' => 74],
                ['clave' => 'masa_kg', 'etiqueta' => 'Masa (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 76],
                ['clave' => 'carga_lb', 'etiqueta' => 'Carga Máx (lb)', 'tipo' => 'decimal', 'placeholder' => 'lb', 'ancho' => 96],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 130],
            ],
            'campos_calculados' => [
                ['clave' => 'relacion_ld', 'etiqueta' => 'L/D', 'placeholder' => '--', 'ancho' => 64],
                ['clave' => 'area_cm2', 'etiqueta' => 'Área (cm²)', 'placeholder' => '--', 'ancho' => 82],
                ['clave' => 'esfuerzo_mpa', 'etiqueta' => 'MPa', 'placeholder' => '--', 'ancho' => 76, 'destacado' => true],
                ['clave' => 'esfuerzo_kgcm2', 'etiqueta' => 'kg/cm²', 'placeholder' => '--', 'ancho' => 80, 'destacado' => true],
                ['clave' => 'esfuerzo_psi', 'etiqueta' => 'lbf/in²', 'placeholder' => '--', 'ancho' => 80],
            ],
        ];

        $defPesosVolumetricos = [
            'codigo_hc' => 'PE-03 (ASTM C29)',
            'norma' => 'ASTM C29/C29M-23 (CYCSA-PE-03)',
            'titulo' => 'Captura Técnica de Laboratorio — Pesos Volumétricos Seco Suelto y Compacto (PVSS / PVSC)',
            'subtitulo' => 'Capture volumen y peso del recipiente, pesos lleno suelto y lleno compacto, y taras de humedad. Calcula PVSS (kg/m³), PVSC (kg/m³) y Humedad Natural (%).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'pesos_volumetricos',
            'campos_entrada' => [
                ['clave' => 'vol_recipiente_l', 'etiqueta' => 'Vol. Recip. (L)', 'tipo' => 'decimal', 'placeholder' => 'L', 'ancho' => 88],
                ['clave' => 'peso_recipiente_kg', 'etiqueta' => 'Recip. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 84],
                ['clave' => 'peso_suelto_kg', 'etiqueta' => 'Rec.+Suelto (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 98],
                ['clave' => 'peso_compacto_kg', 'etiqueta' => 'Rec.+Comp. (kg)', 'tipo' => 'decimal', 'placeholder' => 'kg', 'ancho' => 98],
                ['clave' => 'tara_humedo_g', 'etiqueta' => 'Tara+Húm (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'tara_seco_g', 'etiqueta' => 'Tara+Seco (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 90],
                ['clave' => 'tara_g', 'etiqueta' => 'Tara (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 76],
            ],
            'campos_calculados' => [
                ['clave' => 'pvss', 'etiqueta' => 'PVSS (kg/m³)', 'placeholder' => '--', 'ancho' => 90, 'destacado' => true],
                ['clave' => 'pvsc', 'etiqueta' => 'PVSC (kg/m³)', 'placeholder' => '--', 'ancho' => 90, 'destacado' => true],
                ['clave' => 'humedad_pct', 'etiqueta' => 'Hum. Nat. (%)', 'placeholder' => '--', 'ancho' => 86],
            ],
        ];

        $defEquivalenteArena = [
            'codigo_hc' => 'PE-50 (ASTM D2419)',
            'norma' => 'ASTM D2419 (CYCSA-PE-50)',
            'titulo' => 'Captura Técnica de Laboratorio — Equivalente de Arena (ASTM D2419)',
            'subtitulo' => 'Ingrese las lecturas de Arcilla y Arena (in) de las probetas (hasta 3 réplicas). Calcula el Equivalente de Arena (%) redondeado al entero superior según norma ASTM D2419.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'equivalente_arena',
            'campos_entrada' => [
                ['clave' => 'arcilla_1', 'etiqueta' => 'Arcilla 1 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 88],
                ['clave' => 'arena_1', 'etiqueta' => 'Arena 1 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 86],
                ['clave' => 'arcilla_2', 'etiqueta' => 'Arcilla 2 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 88],
                ['clave' => 'arena_2', 'etiqueta' => 'Arena 2 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 86],
                ['clave' => 'arcilla_3', 'etiqueta' => 'Arcilla 3 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 88],
                ['clave' => 'arena_3', 'etiqueta' => 'Arena 3 (in)', 'tipo' => 'decimal', 'placeholder' => 'in', 'ancho' => 86],
            ],
            'campos_calculados' => [
                ['clave' => 'equiv_arena_pct', 'etiqueta' => 'Equiv. Arena (%)', 'placeholder' => '--', 'ancho' => 104, 'destacado' => true],
            ],
        ];

        $defParticulasDesmenuzables = [
            'codigo_hc' => 'PE-41 (ASTM C142)',
            'norma' => 'ASTM C142 (CYCSA-PE-41)',
            'titulo' => 'Captura Técnica de Laboratorio — Terrones de Arcilla y Partículas Desmenuzables',
            'subtitulo' => 'Ingrese la masa inicial seca (M) y la masa retenida seca tras el tamizado húmedo (R) en gramos. Calcula automáticamente el % de Terrones de Arcilla.',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'particulas_desmenuzables',
            'campos_entrada' => [
                ['clave' => 'masa_inicial_g', 'etiqueta' => 'Masa Inicial M (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 120],
                ['clave' => 'masa_retenida_g', 'etiqueta' => 'Masa Retenida R (g)', 'tipo' => 'decimal', 'placeholder' => 'g', 'ancho' => 124],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 132],
            ],
            'campos_calculados' => [
                ['clave' => 'terrones_pct', 'etiqueta' => 'Terrones Arcilla (%)', 'placeholder' => '--', 'ancho' => 118, 'destacado' => true],
            ],
        ];

        $defRevenimiento = [
            'codigo_hc' => 'PE-09 / PE-27',
            'norma' => 'ASTM C143 / C1064 (CYCSA-PE-09 / PE-27)',
            'titulo' => 'Captura Técnica de Campo/Lab — Revenimiento y Temperatura del Concreto Fresco',
            'subtitulo' => 'Ingrese el revenimiento medido en pulgadas (in) y temperatura (°C). Calcula automáticamente el revenimiento en cm redondeado al 0.5 cm más cercano (ASTM C143).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'revenimiento',
            'campos_entrada' => [
                ['clave' => 'revenimiento_in', 'etiqueta' => 'Revenimiento (in)', 'tipo' => 'decimal', 'placeholder' => 'Ej. 4.5', 'ancho' => 112],
                ['clave' => 'temperatura_c', 'etiqueta' => 'Temperatura (°C)', 'tipo' => 'decimal', 'placeholder' => 'Ej. 28.5', 'ancho' => 112],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Finalización', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 136],
            ],
            'campos_calculados' => [
                ['clave' => 'revenimiento_cm', 'etiqueta' => 'Revenimiento (cm)', 'placeholder' => '--', 'ancho' => 116, 'destacado' => true],
            ],
        ];

        $defColorimetria = [
            'codigo_hc' => 'PE-05 (ASTM C40)',
            'norma' => 'ASTM C40 (CYCSA-PE-05)',
            'titulo' => 'Captura Técnica de Laboratorio — Impurezas Orgánicas / Colorimetría (ASTM C40)',
            'subtitulo' => 'Seleccione la Placa Orgánica Gardner (N° 1 a 5) observada tras 24 horas en NaOH al 3%. Dictamina automáticamente Aceptable (≤ Placa 3) o Rechazado (> Placa 3).',
            'modo_filas' => 'por_muestra',
            'permite_agregar_filas' => false,
            'motor_calculo' => 'colorimetria',
            'campos_entrada' => [
                ['clave' => 'placa_gardner', 'etiqueta' => 'Placa Orgánica', 'tipo' => 'select', 'opciones' => ['Placa N° 1 (Muy claro)', 'Placa N° 2 (Claro)', 'Placa N° 3 (Estándar límite)', 'Placa N° 4 (Oscuro)', 'Placa N° 5 (Muy oscuro)'], 'default' => 'Placa N° 1 (Muy claro)', 'ancho' => 185],
                ['clave' => 'fecha_ensayo', 'etiqueta' => 'Fecha Ensayo', 'tipo' => 'date', 'placeholder' => '', 'ancho' => 132],
            ],
            'campos_calculados' => [
                ['clave' => 'resultado', 'etiqueta' => 'Dictamen ASTM C40', 'placeholder' => '--', 'ancho' => 140, 'destacado' => true],
            ],
        ];

        return [
            'resistencia_de_concreto.md' => $defCilindrosPsi,
            'resistencia_de_nucleo_de_concreto.md' => $defCilindrosPsi,
            'formato_de_lodo_concreto.md' => $defCilindrosMetricos,
            'formato_de_suelo_cemento.md' => $defCilindrosMetricos,
            'resistencia_de_mortero.md' => $defMortero,
            'formato_de_resistencia_de_bloques.md' => $defBloques,
            'resistencia_de_adoquines.md' => $defAdoquines,
            'formato_de_resistencia_de_a_la_flexion.md' => $defFlexion,
            'resistencia_de_martillo_suizo.md' => $defMartilloSuizo,
            'proctor_estandar.md' => $defProctor,
            'formato_de_compactacion_por_cono_de_arena.md' => $defConoArena,
            'formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md' => $defReemplazoAgua,
            'compactacion_densimetro_nuclear.md' => $defDensimetro,
            'formato_de_granulometria_de_suelo.md' => $defGranuloSuelo,
            'granulomnetria_de_agregados.md' => $defGranuloAgregados,
            'ensayos_varios.md' => $defGeAgregados,
            'formato_cbr_h07.md' => $defCbr,
            'formato_ge_suelos_h08.md' => $defGeSuelos,
            'formato_tension_acero_h37.md' => $defAceroTension,
            'formato_doblado_acero_h20.md' => $defAceroDoblado,
            'formato_sanidad_intemperismo_f.md' => $defSanidad,
            'resistencia_de_ladrillo.md' => $defLadrillo,
            'formato_compresion_roca_h22.md' => $defRoca,
            'pesos_volumetricos.md' => $defPesosVolumetricos,
            'formato_de_equivalente_de_arena.md' => $defEquivalenteArena,
            'formato_de_particulas_desmezurables.md' => $defParticulasDesmenuzables,
            'formato_de_reveniemiento_y_temperatura.md' => $defRevenimiento,
            'formato_colorimetria_pe05.md' => $defColorimetria,
        ];
    }

    /**
     * Obtiene la definición de captura técnica para cualquier archivo markdown de formato.
     */
    public static function obtenerDefinicionPorFormato(?string $archivoMd): array {
        $norm = self::normalizarArchivoMd($archivoMd);
        $catalogo = self::obtenerCatalogoDefiniciones();
        if (isset($catalogo[$norm])) {
            return $catalogo[$norm];
        }
        // Fallback por palabra clave si llega un formato personalizado nuevo
        if (str_contains($norm, 'mortero')) return $catalogo['resistencia_de_mortero.md'];
        if (str_contains($norm, 'bloque')) return $catalogo['formato_de_resistencia_de_bloques.md'];
        if (str_contains($norm, 'adoquin')) return $catalogo['resistencia_de_adoquines.md'];
        if (str_contains($norm, 'flexion') || str_contains($norm, 'viga')) return $catalogo['formato_de_resistencia_de_a_la_flexion.md'];
        if (str_contains($norm, 'martillo') || str_contains($norm, 'esclero')) return $catalogo['resistencia_de_martillo_suizo.md'];
        if (str_contains($norm, 'proctor')) return $catalogo['proctor_estandar.md'];
        if (str_contains($norm, 'cono')) return $catalogo['formato_de_compactacion_por_cono_de_arena.md'];
        if (str_contains($norm, 'reemplazo')) return $catalogo['formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md'];
        if (str_contains($norm, 'densimetro')) return $catalogo['compactacion_densimetro_nuclear.md'];
        if (str_contains($norm, 'granulo') && str_contains($norm, 'agregado')) return $catalogo['granulomnetria_de_agregados.md'];
        if (str_contains($norm, 'granulo')) return $catalogo['formato_de_granulometria_de_suelo.md'];
        if (str_contains($norm, 'cbr')) return $catalogo['formato_cbr_h07.md'];
        if (str_contains($norm, 'ge_suelo')) return $catalogo['formato_ge_suelos_h08.md'];
        if (str_contains($norm, 'tension') || str_contains($norm, 'acero')) return $catalogo['formato_tension_acero_h37.md'];
        if (str_contains($norm, 'doblado')) return $catalogo['formato_doblado_acero_h20.md'];
        if (str_contains($norm, 'sanidad') || str_contains($norm, 'intemperismo')) return $catalogo['formato_sanidad_intemperismo_f.md'];
        if (str_contains($norm, 'roca')) return $catalogo['formato_compresion_roca_h22.md'];
        if (str_contains($norm, 'ladrillo')) return $catalogo['resistencia_de_ladrillo.md'];
        if (str_contains($norm, 'lodo') || str_contains($norm, 'suelo_cemento')) return $catalogo['formato_de_lodo_concreto.md'];
        if (str_contains($norm, 'volumetrico')) return $catalogo['pesos_volumetricos.md'];
        if (str_contains($norm, 'equivalente')) return $catalogo['formato_de_equivalente_de_arena.md'];
        if (str_contains($norm, 'desmezurable') || str_contains($norm, 'desmenuzable') || str_contains($norm, 'terron')) return $catalogo['formato_de_particulas_desmezurables.md'];
        if (str_contains($norm, 'reven')) return $catalogo['formato_de_reveniemiento_y_temperatura.md'];
        if (str_contains($norm, 'color')) return $catalogo['formato_colorimetria_pe05.md'];

        return $catalogo['resistencia_de_concreto.md'];
    }

    /**
     * Precarga los datos de captura técnica iniciales combinando:
     * 1) `resultados_json['captura_tecnica']` ya guardado previamente.
     * 2) Registros de `roturas` + `especimenes` en BD (para sincronización transparente con Laboratorio).
     * 3) `muestrasSeteadas` / filas base del formato.
     */
    public static function hidratarCapturaTecnicaInicial(
        PDO $db,
        string $archivoMd,
        array $detalle,
        array $muestrasSeteadas,
        array $decResultados,
        int $idHoja
    ): array {
        $def = self::obtenerDefinicionPorFormato($archivoMd);
        $guardada = (isset($decResultados['captura_tecnica']) && is_array($decResultados['captura_tecnica']))
            ? $decResultados['captura_tecnica']
            : [];

        // Mapa de roturas reales de laboratorio (por código de muestra) para prellenar D1/D2/L1/L2/Masa/Carga/Fecha/Falla
        $roturasLabPorCod = [];
        if ($idHoja > 0 && in_array($def['motor_calculo'], ['cilindro_psi', 'cilindro_metrico', 'mortero', 'adoquin', 'flexion', 'bloque'], true)) {
            try {
                $stmtR = $db->prepare("
                    SELECT rm.codigo_muestra AS cod_m, esp.codigo_lab AS cod_e,
                           r.carga, r.area, r.peso_volumetrico, r.esfuerzo_psi, r.esfuerzo_kgcm2,
                           r.fecha_real, r.tipo_falla, r.datos_crudos
                    FROM roturas r
                    JOIN especimenes esp ON r.especimen_id = esp.id
                    JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                    WHERE rm.id_hoja = :id_hoja
                    ORDER BY r.id ASC
                ");
                $stmtR->execute(['id_hoja' => $idHoja]);
                foreach ($stmtR->fetchAll(PDO::FETCH_ASSOC) as $rr) {
                    $c1 = strtoupper(trim((string)($rr['cod_m'] ?? '')));
                    $c2 = strtoupper(trim((string)($rr['cod_e'] ?? '')));
                    if ($c1 !== '' && !isset($roturasLabPorCod[$c1])) {
                        $roturasLabPorCod[$c1] = $rr;
                    }
                    if ($c2 !== '' && !isset($roturasLabPorCod[$c2])) {
                        $roturasLabPorCod[$c2] = $rr;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if (!empty($guardada)) {
            // Enriquecer con roturas nuevas de BD si alguna fila guardada aún estaba vacía
            $mapaGuardadaPorCod = [];
            foreach ($guardada as $idx => $gRow) {
                if (!is_array($gRow)) continue;
                $cod = strtoupper(trim((string)($gRow['codigo_lab'] ?? '')));
                if ($cod !== '' && isset($roturasLabPorCod[$cod]) && empty($gRow['carga_lb']) && empty($gRow['carga_kg'])) {
                    $guardada[$idx] = self::aplicarRoturaDbEnFilaTecnica($gRow, $roturasLabPorCod[$cod]);
                }
                if ($cod !== '') {
                    $mapaGuardadaPorCod[$cod] = $guardada[$idx];
                }
            }
            // Si el formato es por muestra y la matriz tiene réplicas (-CR) aún no presentes en captura_tecnica, agregarlas en orden
            if (($def['modo_filas'] ?? 'por_muestra') === 'por_muestra') {
                $filasMatrizPrev = isset($decResultados['filas']) && is_array($decResultados['filas'])
                    ? $decResultados['filas']
                    : [];
                if (!empty($filasMatrizPrev)) {
                    $sincronizadas = [];
                    foreach ($filasMatrizPrev as $fm) {
                        if (!is_array($fm)) continue;
                        $codM = trim((string)($fm['Código laboratorio'] ?? ($fm['Codigo laboratorio'] ?? ($fm['codigo_lab'] ?? ''))));
                        $codUp = strtoupper($codM);
                        if ($codUp !== '' && isset($mapaGuardadaPorCod[$codUp])) {
                            $sincronizadas[] = $mapaGuardadaPorCod[$codUp];
                        } elseif ($codM !== '') {
                            $nuevaFila = ['codigo_lab' => $codM];
                            if (!empty($fm['_es_replica']) || preg_match('/-CR$/i', $codM)) {
                                $nuevaFila['_es_replica'] = true;
                                $nuevaFila['_muestra_origen'] = (string)($fm['_muestra_origen'] ?? preg_replace('/-CR$/i', '', $codM));
                            }
                            foreach ($def['campos_entrada'] as $ce) {
                                $nuevaFila[$ce['clave']] = $ce['default'] ?? '';
                            }
                            $sincronizadas[] = $nuevaFila;
                        }
                    }
                    if (!empty($sincronizadas)) {
                        return $sincronizadas;
                    }
                }
            }
            return array_values($guardada);
        }

        $modo = $def['modo_filas'] ?? 'por_muestra';
        $filasOut = [];

        if ($modo === 'por_muestra') {
            $baseMuestras = !empty($muestrasSeteadas) ? $muestrasSeteadas : [];
            $filasMatrizPrev = isset($decResultados['filas']) && is_array($decResultados['filas'])
                ? $decResultados['filas']
                : (isset($decResultados[0]) && is_array($decResultados[0]) ? $decResultados : []);

            $totalFilas = max(count($baseMuestras), count($filasMatrizPrev), 1);
            for ($i = 0; $i < $totalFilas; $i++) {
                $m = $baseMuestras[$i] ?? [];
                $fm = $filasMatrizPrev[$i] ?? [];
                $cod = trim((string)($fm['Código laboratorio'] ?? ($m['codigo_lab'] ?? ($m['codigo_campo'] ?? ('M-' . ($i + 1))))));
                $fila = ['codigo_lab' => $cod];
                foreach ($def['campos_entrada'] as $ce) {
                    $fila[$ce['clave']] = $ce['default'] ?? '';
                }
                $codUp = strtoupper($cod);
                if ($codUp !== '' && isset($roturasLabPorCod[$codUp])) {
                    $fila = self::aplicarRoturaDbEnFilaTecnica($fila, $roturasLabPorCod[$codUp]);
                }
                $filasOut[] = $fila;
            }
        } else {
            $nDef = max(1, (int)($def['filas_default'] ?? 3));
            $etq = (string)($def['etiqueta_fila'] ?? 'Fila');
            $golpesDef = ['12', '25', '56'];
            $fraccionesDef = ['1 1/2" - 3/4"', '3/4" - 3/8"', '3/8" - No. 4', 'No. 4 - No. 8'];
            for ($i = 0; $i < $nDef; $i++) {
                $fila = ['codigo_lab' => $etq . ' ' . ($i + 1)];
                foreach ($def['campos_entrada'] as $ce) {
                    $fila[$ce['clave']] = $ce['default'] ?? '';
                }
                if ($modo === 'moldes_cbr' && isset($golpesDef[$i])) {
                    $fila['golpes'] = $golpesDef[$i];
                    $fila['codigo_lab'] = 'Molde ' . $golpesDef[$i] . ' golpes';
                    $fila['masa_molde_g'] = '7849';
                    $fila['volumen_molde_cm3'] = '3200';
                    $fila['constante_anillo'] = '10';
                }
                if ($modo === 'puntos_proctor') {
                    $fila['volumen_molde_ml'] = '947.26';
                }
                if ($modo === 'fracciones_sanidad' && isset($fraccionesDef[$i])) {
                    $fila['fraccion'] = $fraccionesDef[$i];
                    $fila['codigo_lab'] = $fraccionesDef[$i];
                }
                $filasOut[] = $fila;
            }
        }

        return $filasOut;
    }

    private static function aplicarRoturaDbEnFilaTecnica(array $fila, array $rr): array {
        $crudos = [];
        if (!empty($rr['datos_crudos'])) {
            $crudos = is_string($rr['datos_crudos']) ? (json_decode($rr['datos_crudos'], true) ?: []) : (array)$rr['datos_crudos'];
        }
        $mapa = [
            'diametro_1' => $crudos['diametro_1'] ?? null,
            'diametro_2' => $crudos['diametro_2'] ?? null,
            'largo_1' => $crudos['largo_1'] ?? null,
            'largo_2' => $crudos['largo_2'] ?? null,
            'masa_kg' => $crudos['masa_kg'] ?? ($crudos['peso'] ?? null),
            'carga_lb' => !empty($rr['carga']) ? (string)(float)$rr['carga'] : null,
            'tipo_falla' => !empty($rr['tipo_falla']) ? (string)$rr['tipo_falla'] : null,
        ];
        foreach ($mapa as $k => $v) {
            if ($v !== null && $v !== '' && array_key_exists($k, $fila) && ($fila[$k] === '' || $fila[$k] === null)) {
                $fila[$k] = (string)$v;
            }
        }
        if (!empty($rr['fecha_real']) && array_key_exists('fecha_ensayo', $fila) && empty($fila['fecha_ensayo'])) {
            $ts = strtotime((string)$rr['fecha_real']);
            if ($ts !== false) {
                $fila['fecha_ensayo'] = date('Y-m-d\TH:i', $ts);
            }
        }
        return $fila;
    }

    /**
     * Cálculo autoritativo de servidor para TODOS los 28 formatos.
     * Recibe las filas de captura técnica del técnico, calcula los resultados oficiales con
     * ConfigCalculoLabServicio y sincroniza las celdas correspondientes en `$filasMatriz`.
     *
     * @return array{captura_tecnica: array<int, array<string, mixed>>, filas_matriz: array<int, array<string, mixed>>, tiene_datos: bool}
     */
    public static function recalcularDesdeCapturaTecnica(
        string $archivoMd,
        array $filasTecnicas,
        array $filasMatriz,
        ?PDO $db = null
    ): array {
        $def = self::obtenerDefinicionPorFormato($archivoMd);
        $motor = (string)($def['motor_calculo'] ?? 'cilindro_psi');
        $divisorKgCm2 = ConfigCalculoLabServicio::obtenerFloat('cilindros_divisor_kgcm2', 14.2029, $db);
        $redondearPsi10 = ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_psi_10', true, $db);
        $redondearPv10 = ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_pv_10', true, $db);

        $tieneDatos = false;
        $tecnicasOut = [];

        // Helper para leer float seguro
        $f = static function (array $row, string $k): ?float {
            if (!isset($row[$k])) return null;
            $raw = trim(str_replace(',', '.', (string)$row[$k]));
            return ($raw !== '' && is_numeric($raw)) ? (float)$raw : null;
        };

        // Helper para localizar índice de fila en matriz por código o posición
        $buscarIdxMatriz = static function (array $matriz, string $codLab, int $idxDefault): int {
            $codUp = strtoupper(trim($codLab));
            if ($codUp !== '') {
                foreach ($matriz as $i => $mRow) {
                    if (!is_array($mRow)) continue;
                    $mc = strtoupper(trim((string)($mRow['Código laboratorio'] ?? ($mRow['Codigo laboratorio'] ?? ($mRow['codigo_lab'] ?? '')))));
                    if ($mc !== '' && $mc === $codUp) {
                        return $i;
                    }
                }
            }
            return $idxDefault;
        };

        $fechaIso = static function (?string $raw): string {
            $raw = trim((string)$raw);
            if ($raw === '') return '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw, $m)) {
                return $m[0];
            }
            $ts = strtotime($raw);
            return $ts !== false ? date('Y-m-d', $ts) : '';
        };

        // Acumuladores para formatos multi-punto que resumen en la matriz (Proctor, Bloques, Sanidad)
        $proctorPuntos = [];
        $bloqueResultados = [];
        $sanidadTotalRetOrig = 0.0;
        if ($motor === 'sanidad') {
            foreach ($filasTecnicas as $ft) {
                if (is_array($ft)) {
                    $ro = $f($ft, 'retenido_original_g');
                    if ($ro !== null && $ro > 0) $sanidadTotalRetOrig += $ro;
                }
            }
        }

        foreach ($filasTecnicas as $idx => $row) {
            if (!is_array($row)) continue;
            $calc = $row;
            $codLab = trim((string)($row['codigo_lab'] ?? ''));

            switch ($motor) {
                case 'cilindro_psi': {
                    $d1 = $f($row, 'diametro_1');
                    $d2 = $f($row, 'diametro_2');
                    $l1 = $f($row, 'largo_1');
                    $l2 = $f($row, 'largo_2');
                    $masa = $f($row, 'masa_kg');
                    $carga = $f($row, 'carga_lb');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaIn2 = null;
                    if ($d1 !== null && $d1 > 0 && $d2 !== null && $d2 > 0) {
                        $areaIn2 = ConfigCalculoLabServicio::calcularAreaCilindroIn2($d1, $d2);
                        $calc['area_in2'] = number_format($areaIn2, 2, '.', '');
                        $tieneDatos = true;
                    }

                    $pv = null;
                    if ($areaIn2 !== null && $areaIn2 > 0 && $l1 !== null && $l1 > 0 && $l2 !== null && $l2 > 0 && $masa !== null && $masa > 0) {
                        $lProm = ($l1 + $l2) / 2.0;
                        $pv = ConfigCalculoLabServicio::calcularPesoVolumetricoKgM3($masa, $areaIn2, $lProm, null, $db);
                        if ($pv !== null) {
                            $calc['peso_volumetrico'] = (string)(int)round($pv);
                            $tieneDatos = true;
                        }
                    }

                    $psi = null;
                    $kgcm2 = null;
                    if ($areaIn2 !== null && $areaIn2 > 0 && $carga !== null && $carga > 0) {
                        $opcionesRes = [];
                        if ($d1 > 0 && $d2 > 0 && $l1 !== null && $l1 > 0 && $l2 !== null && $l2 > 0) {
                            $opcionesRes['diametro_prom_cm'] = ($d1 + $d2) / 2.0;
                            $opcionesRes['largo_prom_cm'] = ($l1 + $l2) / 2.0;
                        }
                        $res = ConfigCalculoLabServicio::calcularResistenciaCilindro($carga, $areaIn2, $opcionesRes, $db);
                        $psi = $res['esfuerzo_psi'];
                        $kgcm2 = $res['esfuerzo_kgcm2'];
                        $calc['esfuerzo_psi'] = (string)(int)round($psi);
                        $calc['esfuerzo_kgcm2'] = (string)(int)round($kgcm2);
                        $tieneDatos = true;
                    }

                    if ($areaIn2 !== null || $carga !== null || $pv !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx]) || !is_array($filasMatriz[$mIdx])) {
                            $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        }
                        if ($areaIn2 !== null) $filasMatriz[$mIdx]['Área (in²)'] = number_format($areaIn2, 2, '.', '');
                        if ($carga !== null && $carga > 0) $filasMatriz[$mIdx]['Carga (lb)'] = (string)(int)round($carga);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso Volumétrico (kg/m³)'] = (string)(int)round($pv);
                        if ($psi !== null) $filasMatriz[$mIdx]['R. Compresión (lb/in²)'] = (string)(int)round($psi);
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['R. Compresión (kg/cm²)'] = (string)(int)round($kgcm2);
                        if ($fEns !== '') {
                            if (array_key_exists('Fecha de Ruptura', $filasMatriz[$mIdx])) {
                                $filasMatriz[$mIdx]['Fecha de Ruptura'] = $fEns;
                            } else {
                                $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                            }
                        }
                    }
                    break;
                }

                case 'cilindro_metrico': {
                    $d1 = $f($row, 'diametro_1');
                    $d2 = $f($row, 'diametro_2');
                    $l1 = $f($row, 'largo_1');
                    $l2 = $f($row, 'largo_2');
                    $masa = $f($row, 'masa_kg');
                    $cargaLb = $f($row, 'carga_lb');
                    $cargaKg = $f($row, 'carga_kg');
                    if (($cargaKg === null || $cargaKg <= 0) && $cargaLb !== null && $cargaLb > 0) {
                        $cargaKg = round($cargaLb * 0.45359237, 1);
                    }
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaCm2 = null;
                    if ($d1 !== null && $d1 > 0 && $d2 !== null && $d2 > 0) {
                        $dProm = ($d1 + $d2) / 2.0;
                        $areaCm2 = round(M_PI * (($dProm / 2.0) ** 2), 2);
                        $calc['area_cm2'] = number_format($areaCm2, 2, '.', '');
                        $tieneDatos = true;
                    }
                    $pv = null;
                    if ($areaCm2 !== null && $areaCm2 > 0 && $l1 !== null && $l1 > 0 && $l2 !== null && $l2 > 0 && $masa !== null && $masa > 0) {
                        $lProm = ($l1 + $l2) / 2.0;
                        $pv = ConfigCalculoLabServicio::calcularPesoVolumetricoKgM3($masa, $areaCm2 / 6.4516, $lProm, null, $db);
                        if ($pv !== null) {
                            $calc['peso_volumetrico'] = (string)(int)round($pv);
                            $tieneDatos = true;
                        }
                    }
                    $kgcm2 = null;
                    if ($areaCm2 !== null && $areaCm2 > 0 && $cargaKg !== null && $cargaKg > 0) {
                        $kgcm2 = round($cargaKg / $areaCm2, 1);
                        $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', '');
                        $tieneDatos = true;
                    }
                    if ($areaCm2 !== null || $cargaKg !== null || $pv !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx]) || !is_array($filasMatriz[$mIdx])) {
                            $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        }
                        if ($areaCm2 !== null) $filasMatriz[$mIdx]['Área (cm²)'] = number_format($areaCm2, 2, '.', '');
                        if ($cargaKg !== null && $cargaKg > 0) $filasMatriz[$mIdx]['Carga (kg)'] = (string)round($cargaKg, 0);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso Volumétrico (kg/m³)'] = (string)(int)round($pv);
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['R. compresión. (kg/cm²)'] = number_format($kgcm2, 1, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'mortero': {
                    $largo = $f($row, 'largo_cm');
                    $ancho = $f($row, 'ancho_cm');
                    $alto = $f($row, 'alto_cm');
                    $masa = $f($row, 'masa_kg');
                    $carga = $f($row, 'carga_lb');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaIn2 = null;
                    if ($largo !== null && $largo > 0 && $ancho !== null && $ancho > 0) {
                        $areaIn2 = round(($largo * $ancho) / 6.4516, 2);
                        $calc['area_in2'] = number_format($areaIn2, 2, '.', '');
                        $tieneDatos = true;
                    }
                    $pv = null;
                    if ($largo > 0 && $ancho > 0 && $alto !== null && $alto > 0 && $masa !== null && $masa > 0) {
                        $volM3 = ($largo * $ancho * $alto) / 1000000.0;
                        $pvBruto = $masa / $volM3;
                        $pv = $redondearPv10 ? round($pvBruto / 10.0) * 10.0 : round($pvBruto, 0);
                        $calc['peso_volumetrico'] = (string)(int)round($pv);
                        $tieneDatos = true;
                    }
                    $psi = null;
                    $kgcm2 = null;
                    if ($areaIn2 !== null && $areaIn2 > 0 && $carga !== null && $carga > 0) {
                        $psiBruto = $carga / $areaIn2;
                        $psi = $redondearPsi10 ? round($psiBruto / 10.0) * 10.0 : round($psiBruto, 0);
                        $kgcm2 = round($psi / $divisorKgCm2, 1);
                        $calc['esfuerzo_psi'] = (string)(int)round($psi);
                        $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', '');
                        $tieneDatos = true;
                    }
                    if ($areaIn2 !== null || $carga !== null || $pv !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($areaIn2 !== null) $filasMatriz[$mIdx]['Área (in²)'] = number_format($areaIn2, 2, '.', '');
                        if ($carga !== null && $carga > 0) $filasMatriz[$mIdx]['Carga (lb)'] = (string)(int)round($carga);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso Volumétrico (kg/m³)'] = (string)(int)round($pv);
                        if ($psi !== null) $filasMatriz[$mIdx]['R. Compresión (lb/in²)'] = (string)(int)round($psi);
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['R. Compresión. (kg/cm²)'] = number_format($kgcm2, 1, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'bloque': {
                    $largo = $f($row, 'largo_cm');
                    $alto = $f($row, 'alto_cm');
                    $ancho = $f($row, 'ancho_cm');
                    $espExt = $f($row, 'esp_ext_cm');
                    $espInt = $f($row, 'esp_int_cm');
                    $mRec = $f($row, 'masa_recibida_kg');
                    $mSec = $f($row, 'masa_seca_kg');
                    $mSum = $f($row, 'masa_sumergida_kg');
                    $mSat = $f($row, 'masa_saturada_kg');
                    $carga = $f($row, 'carga_lb');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaBrutaIn2 = null;
                    if ($largo > 0 && $ancho > 0) {
                        $areaBrutaIn2 = round(($largo * $ancho) / 6.4516, 2);
                        $calc['area_bruta_in2'] = number_format($areaBrutaIn2, 2, '.', '');
                        $tieneDatos = true;
                    }
                    $areaNetaIn2 = null;
                    $densidad = null;
                    $absorcion = null;
                    if ($mSat !== null && $mSum !== null && ($mSat - $mSum) > 0 && $alto > 0) {
                        $volNetoCm3 = ($mSat - $mSum) * 1000.0;
                        $areaNetaCm2 = $volNetoCm3 / $alto;
                        $areaNetaIn2 = round($areaNetaCm2 / 6.4516, 2);
                        $calc['area_neta_in2'] = number_format($areaNetaIn2, 2, '.', '');
                        if ($mSec !== null && $mSec > 0) {
                            $densidad = round(($mSec * 1000000.0) / $volNetoCm3, 0);
                            $absorcion = round((($mSat - $mSec) / $mSec) * 100.0, 2);
                            $calc['absorcion_pct'] = number_format($absorcion, 2, '.', '');
                        }
                        $tieneDatos = true;
                    }
                    $areaEf = ($areaNetaIn2 !== null && $areaNetaIn2 > 0) ? $areaNetaIn2 : $areaBrutaIn2;
                    $psi = null;
                    $kgcm2 = null;
                    if ($areaEf !== null && $areaEf > 0 && $carga !== null && $carga > 0) {
                        $psi = round($carga / $areaEf, 0);
                        $kgcm2 = round($psi / $divisorKgCm2, 1);
                        $calc['esfuerzo_psi'] = (string)(int)$psi;
                        $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', '');
                        $tieneDatos = true;
                    }
                    if ($areaBrutaIn2 !== null || $carga !== null || $absorcion !== null) {
                        $bloqueResultados[] = [
                            'area_bruta_in2' => $areaBrutaIn2,
                            'area_neta_in2' => $areaNetaIn2,
                            'carga' => $carga,
                            'largo' => $largo,
                            'alto' => $alto,
                            'ancho' => $ancho,
                            'esp_int' => $espInt,
                            'esp_ext' => $espExt,
                            'densidad' => $densidad,
                            'psi' => $psi,
                            'kgcm2' => $kgcm2,
                            'absorcion' => $absorcion,
                            'fecha_ensayo' => $fEns,
                        ];
                    }
                    break;
                }

                case 'adoquin': {
                    $largo = $f($row, 'largo_cm');
                    $ancho = $f($row, 'ancho_cm');
                    $alto = $f($row, 'alto_cm');
                    $carga = $f($row, 'carga_lb');
                    $mSec = $f($row, 'masa_seca_kg');
                    $mSum = $f($row, 'masa_sumergida_kg');
                    $mSat = $f($row, 'masa_saturada_kg');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaIn2 = null;
                    if ($largo > 0 && $ancho > 0) {
                        $areaIn2 = round(($largo * $ancho) / 6.4516, 2);
                        $calc['area_in2'] = number_format($areaIn2, 2, '.', '');
                        $tieneDatos = true;
                    }
                    $pv = null;
                    $abs = null;
                    if ($mSec > 0 && $mSat !== null && $mSum !== null && ($mSat - $mSum) > 0) {
                        $volCm3 = ($mSat - $mSum) * 1000.0;
                        $pv = round(($mSec * 1000000.0) / $volCm3, 0);
                        $abs = round((($mSat - $mSec) / $mSec) * 100.0, 2);
                        $calc['peso_volumetrico'] = (string)(int)$pv;
                        $calc['absorcion_pct'] = number_format($abs, 2, '.', '');
                        $tieneDatos = true;
                    } elseif ($largo > 0 && $ancho > 0 && $alto > 0 && $mSec > 0) {
                        $pv = round($mSec / (($largo * $ancho * $alto) / 1000000.0), 0);
                        $calc['peso_volumetrico'] = (string)(int)$pv;
                    }
                    $psi = null;
                    $kgcm2 = null;
                    if ($areaIn2 > 0 && $carga > 0) {
                        $psi = round($carga / $areaIn2, 0);
                        $kgcm2 = round($psi / $divisorKgCm2, 1);
                        $calc['esfuerzo_psi'] = (string)(int)$psi;
                        $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', '');
                        $tieneDatos = true;
                    }
                    if ($areaIn2 !== null || $carga !== null || $abs !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($areaIn2 !== null) $filasMatriz[$mIdx]['Área (in²)'] = number_format($areaIn2, 2, '.', '');
                        if ($carga !== null && $carga > 0) $filasMatriz[$mIdx]['Carga (lb)'] = (string)(int)round($carga);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso Volumétrico (kg/m³)'] = (string)(int)$pv;
                        if ($psi !== null) $filasMatriz[$mIdx]['R. Compresión (lb/in²)'] = (string)(int)$psi;
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['R. Compresión. (kg/cm²)'] = number_format($kgcm2, 1, '.', '');
                        if ($abs !== null) $filasMatriz[$mIdx]['Absorción ((%)(P/P))'] = number_format($abs, 2, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'flexion': {
                    $bs = array_filter([$f($row, 'ancho_1'), $f($row, 'ancho_2'), $f($row, 'ancho_3')], static fn($v) => $v !== null && $v > 0);
                    $ds = array_filter([$f($row, 'espesor_1'), $f($row, 'espesor_2'), $f($row, 'espesor_3')], static fn($v) => $v !== null && $v > 0);
                    $luz = $f($row, 'luz_in');
                    $masa = $f($row, 'masa_kg');
                    $carga = $f($row, 'carga_lb');
                    $zona = (string)($row['en_tercio_medio'] ?? 'Sí (Tercio Medio)');
                    $distA = $f($row, 'dist_a_in');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $bProm = !empty($bs) ? round(array_sum($bs) / count($bs), 2) : null;
                    $dProm = !empty($ds) ? round(array_sum($ds) / count($ds), 2) : null;
                    if ($bProm !== null) {
                        $calc['ancho_prom'] = number_format($bProm, 2, '.', '');
                        $tieneDatos = true;
                    }
                    if ($dProm !== null) {
                        $calc['espesor_prom'] = number_format($dProm, 2, '.', '');
                        $tieneDatos = true;
                    }
                    $mrKg = null;
                    $dictamen = str_contains($zona, 'Descartar') ? 'DESCARTAR' : 'VÁLIDO';
                    if ($bProm > 0 && $dProm > 0 && $luz > 0 && $carga > 0) {
                        if (str_contains($zona, '<5%') && $distA !== null && $distA > 0) {
                            $mrPsi = (3.0 * $carga * $distA) / ($bProm * ($dProm ** 2));
                        } else {
                            $mrPsi = ($carga * $luz) / ($bProm * ($dProm ** 2));
                        }
                        $mrKg = round($mrPsi * 0.070307, 2);
                        $calc['mr_kgcm2'] = number_format($mrKg, 2, '.', '');
                        $calc['dictamen_falla'] = $dictamen;
                        $tieneDatos = true;
                    }
                    $pv = null;
                    if ($bProm > 0 && $dProm > 0 && $luz > 0 && $masa > 0) {
                        $volM3 = ($bProm * 0.0254) * ($dProm * 0.0254) * (($luz + 2.0) * 0.0254);
                        if ($volM3 > 0) $pv = round($masa / $volM3, 0);
                    }
                    if ($bProm !== null || $dProm !== null || $carga !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($bProm !== null) $filasMatriz[$mIdx]['Ancho Promedio (in)'] = number_format($bProm, 2, '.', '');
                        if ($dProm !== null) $filasMatriz[$mIdx]['Espesor Promedio (in)'] = number_format($dProm, 2, '.', '');
                        if ($luz !== null && $luz > 0) $filasMatriz[$mIdx]['Longitud de Apoyo (in)'] = number_format($luz, 2, '.', '');
                        if ($carga !== null && $carga > 0) $filasMatriz[$mIdx]['Carga (lb)'] = (string)(int)round($carga);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso volumétrico (kg/m³)'] = (string)(int)$pv;
                        if ($mrKg !== null) $filasMatriz[$mIdx]['Resistencia a la flexión. (kg/cm²)'] = number_format($mrKg, 2, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'martillo_suizo': {
                    $pos = strtoupper(trim((string)($row['posicion'] ?? 'B')));
                    if (!in_array($pos, ['A', 'B', 'C'], true)) $pos = 'B';
                    $rebotes = [];
                    for ($k = 1; $k <= 10; $k++) {
                        $rv = $f($row, 'r' . $k);
                        if ($rv !== null && $rv > 0) $rebotes[] = $rv;
                    }
                    if (!empty($rebotes)) {
                        $promRaw = array_sum($rebotes) / count($rebotes);
                        // ASTM C805: descartar lecturas que difieran > 6 unidades del promedio
                        $filtrados = array_values(array_filter($rebotes, static fn($v) => abs($v - $promRaw) <= 6.0));
                        $prom = !empty($filtrados) ? (array_sum($filtrados) / count($filtrados)) : $promRaw;
                        $factorCal = ConfigCalculoLabServicio::obtenerFloat('escl_factor_calibracion', 0.9708737864, $db);
                        $rCorr = $prom * $factorCal;
                        // Curvas oficiales ESCL-01 por posición A / B / C
                        $curvas = [
                            'A' => [0.0085, 0.2512, 128.4, -1820.0],
                            'B' => [0.0079, 0.2145, 118.2, -1610.0],
                            'C' => [0.0072, 0.1910, 108.5, -1420.0],
                        ];
                        [$ca, $cb, $cc, $cd] = $curvas[$pos];
                        $psiBruto = max(0.0, ($ca * ($rCorr ** 3)) + ($cb * ($rCorr ** 2)) + ($cc * $rCorr) + $cd);
                        $psi = round($psiBruto / 10.0) * 10.0;
                        $kgcm2 = round($psi / $divisorKgCm2, 0);

                        $calc['rebote_promedio'] = number_format($prom, 1, '.', '');
                        $calc['esfuerzo_psi'] = (string)(int)$psi;
                        $calc['esfuerzo_kgcm2'] = (string)(int)$kgcm2;
                        $tieneDatos = true;

                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$mIdx]['Forma de Prueba'] = 'Posición ' . $pos;
                        $filasMatriz[$mIdx]['Promedio de Rebotes (N°)'] = number_format($prom, 1, '.', '');
                        $filasMatriz[$mIdx]['Estimación R. compresión (lb/in²)'] = (string)(int)$psi;
                        $filasMatriz[$mIdx]['Estimación R. compresión (kg/cm²)'] = (string)(int)$kgcm2;
                    }
                    break;
                }

                case 'proctor': {
                    $mh = $f($row, 'molde_humedo_g');
                    $mm = $f($row, 'masa_molde_g');
                    $vol = $f($row, 'volumen_molde_ml') ?: 947.26;
                    $th = $f($row, 'tara_humedo_g');
                    $ts = $f($row, 'tara_seco_g');
                    $tt = $f($row, 'tara_g');

                    $hum = null;
                    if ($th !== null && $ts !== null && $tt !== null && ($ts - $tt) > 0) {
                        $hum = round((($th - $ts) / ($ts - $tt)) * 100.0, 2);
                        $calc['humedad_pct'] = number_format($hum, 2, '.', '');
                        $tieneDatos = true;
                    }
                    if ($mh !== null && $mm !== null && ($mh - $mm) > 0 && $vol > 0) {
                        $pvh = (($mh - $mm) / $vol) * 1000.0;
                        $calc['densidad_humeda'] = (string)(int)round($pvh);
                        $tieneDatos = true;
                        if ($hum !== null) {
                            $pvs = $pvh / (1.0 + ($hum / 100.0));
                            $calc['densidad_seca'] = (string)(int)round($pvs);
                            $proctorPuntos[] = [
                                'pvs' => $pvs,
                                'hum' => $hum,
                                'metodo' => (string)($row['metodo_proctor'] ?? 'Proctor Estándar A'),
                                'fecha' => $fechaIso($row['fecha_ensayo'] ?? ''),
                            ];
                        }
                    }
                    break;
                }

                case 'cono_arena': {
                    $aEmp = $f($row, 'arena_empleada_g');
                    $aSob = $f($row, 'arena_sobrante_g');
                    $aCono = $f($row, 'arena_cono_g') ?: 0.0;
                    $puArena = $f($row, 'pu_arena') ?: 1250.0;
                    $excT = $f($row, 'excavado_tara_g');
                    $tExc = $f($row, 'tara_exc_g') ?: 0.0;
                    $rh = $f($row, 'rec_humedo_g');
                    $rs = $f($row, 'rec_seco_g');
                    $rt = $f($row, 'rec_g');
                    $pvsMax = $f($row, 'pvs_max');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $hum = null;
                    if ($rh !== null && $rs !== null && $rt !== null && ($rs - $rt) > 0) {
                        $hum = round((($rh - $rs) / ($rs - $rt)) * 100.0, 1);
                        $calc['humedad_pct'] = number_format($hum, 1, '.', '');
                        $tieneDatos = true;
                    }
                    $pvsSitio = null;
                    $pvhSitio = null;
                    if ($aEmp !== null && $aSob !== null && $excT !== null && $puArena > 0) {
                        $arenaHuecoG = $aEmp - $aSob - $aCono;
                        $sueloHumG = $excT - $tExc;
                        if ($arenaHuecoG > 0 && $sueloHumG > 0) {
                            $volHuecoM3 = ($arenaHuecoG / 1000.0) / $puArena;
                            $pvhSitio = round(($sueloHumG / 1000.0) / $volHuecoM3, 0);
                            if ($hum !== null) {
                                $pvsSitio = round($pvhSitio / (1.0 + ($hum / 100.0)), 0);
                                $calc['pvs_sitio'] = (string)(int)$pvsSitio;
                            }
                            $tieneDatos = true;
                        }
                    }
                    $comp = null;
                    if ($pvsSitio !== null && $pvsMax !== null && $pvsMax > 0) {
                        $comp = min(100, (int)round(($pvsSitio / $pvsMax) * 100.0));
                        $calc['compactacion_pct'] = (string)$comp;
                    }
                    if ($pvsSitio !== null || $hum !== null || $pvsMax !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($pvsMax !== null && $pvsMax > 0) $filasMatriz[$mIdx]['P.V.S Max (kg/m³)'] = (string)(int)round($pvsMax);
                        if ($pvsSitio !== null) $filasMatriz[$mIdx]['P.V.S.Sitio (kg/m³)'] = (string)(int)$pvsSitio;
                        if ($hum !== null) $filasMatriz[$mIdx]['Humedad sitio ((%) (P/P))'] = number_format($hum, 1, '.', '');
                        if ($pvhSitio !== null) $filasMatriz[$mIdx]['Humedad Sitio (kg/m³)'] = (string)(int)$pvhSitio;
                        if ($comp !== null) $filasMatriz[$mIdx]['Compactación ((%) (P/P))'] = (string)$comp;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de muestreo'] = $fEns;
                    }
                    break;
                }

                case 'reemplazo_agua': {
                    $agAn = $f($row, 'agua_anillo_l') ?: 0.0;
                    $agPozo = $f($row, 'agua_pozo_anillo_l');
                    $excKg = $f($row, 'excavado_humedo_kg');
                    $rh = $f($row, 'rec_humedo_g');
                    $rs = $f($row, 'rec_seco_g');
                    $rt = $f($row, 'rec_g');
                    $pvsMax = $f($row, 'pvs_max');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $hum = null;
                    if ($rh !== null && $rs !== null && $rt !== null && ($rs - $rt) > 0) {
                        $hum = round((($rh - $rs) / ($rs - $rt)) * 100.0, 1);
                        $calc['humedad_pct'] = number_format($hum, 1, '.', '');
                        $tieneDatos = true;
                    }
                    $pvsSitio = null;
                    $pvhSitio = null;
                    if ($agPozo !== null && ($agPozo - $agAn) > 0 && $excKg !== null && $excKg > 0) {
                        $volM3 = ($agPozo - $agAn) / 1000.0;
                        $pvhSitio = round($excKg / $volM3, 0);
                        if ($hum !== null) {
                            $pvsSitio = round($pvhSitio / (1.0 + ($hum / 100.0)), 0);
                            $calc['pvs_sitio'] = (string)(int)$pvsSitio;
                        }
                        $tieneDatos = true;
                    }
                    $comp = null;
                    if ($pvsSitio !== null && $pvsMax !== null && $pvsMax > 0) {
                        $comp = min(100, (int)round(($pvsSitio / $pvsMax) * 100.0));
                        $calc['compactacion_pct'] = (string)$comp;
                    }
                    if ($pvsSitio !== null || $hum !== null || $pvsMax !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($pvsMax !== null && $pvsMax > 0) $filasMatriz[$mIdx]['**P.V.S Max (kg/m³)'] = (string)(int)round($pvsMax);
                        if ($pvsSitio !== null) $filasMatriz[$mIdx]['P.V.S.Sitio (kg/m³)'] = (string)(int)$pvsSitio;
                        if ($hum !== null) $filasMatriz[$mIdx]['Humedad sitio ((%) (P/P))'] = number_format($hum, 1, '.', '');
                        if ($pvhSitio !== null) $filasMatriz[$mIdx]['Humedad Sitio (kg/m³)'] = (string)(int)$pvhSitio;
                        if ($comp !== null) $filasMatriz[$mIdx]['Compactación ((%) (P/P))'] = (string)$comp;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de muestreo'] = $fEns;
                    }
                    break;
                }

                case 'densimetro': {
                    $pvsMax = $f($row, 'pvs_max');
                    $pvsSitio = $f($row, 'pvs_sitio');
                    $humOpt = $f($row, 'humedad_optima');
                    $humSitPct = $f($row, 'humedad_sitio_pct');
                    $humSitKg = $f($row, 'humedad_sitio_kgm3');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');
                    $comp = null;
                    if ($pvsMax > 0 && $pvsSitio > 0) {
                        $comp = min(100, (int)round(($pvsSitio / $pvsMax) * 100.0));
                        $calc['compactacion_pct'] = (string)$comp;
                        $tieneDatos = true;
                    }
                    if ($pvsSitio !== null || $pvsMax !== null || !empty($row['capa'])) {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if (!empty($row['capa'])) $filasMatriz[$mIdx]['Capa (N°)'] = (string)$row['capa'];
                        if (!empty($row['espesor_cm'])) $filasMatriz[$mIdx]['Espesor (cm)'] = (string)$row['espesor_cm'];
                        if (!empty($row['profundidad_cm'])) $filasMatriz[$mIdx]['Profundidad (cm)'] = (string)$row['profundidad_cm'];
                        if ($pvsMax !== null) $filasMatriz[$mIdx]['P.V.S Max (kg/m³)'] = (string)(int)round($pvsMax);
                        if ($humOpt !== null) $filasMatriz[$mIdx]['Humedad Óptima ((% )(P/P))'] = number_format($humOpt, 1, '.', '');
                        if ($pvsSitio !== null) $filasMatriz[$mIdx]['P.V.S.Sitio (kg/m³)'] = (string)(int)round($pvsSitio);
                        if ($humSitPct !== null) $filasMatriz[$mIdx]['Humedad Sitio ((%) (P/P))'] = number_format($humSitPct, 1, '.', '');
                        if ($humSitKg !== null) $filasMatriz[$mIdx]['Humedad Sitio (kg/m³)'] = (string)(int)round($humSitKg);
                        if ($comp !== null) $filasMatriz[$mIdx]['Compactación ((%) (P/P))'] = (string)$comp;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de muestreo'] = $fEns;
                    }
                    break;
                }

                case 'granulometria_suelo': {
                    $tt = $f($row, 'tara_g');
                    $th = $f($row, 'humeda_tara_g');
                    $ts = $f($row, 'seca_tara_g');
                    $llT = $f($row, 'll_tara_g');
                    $llH = $f($row, 'll_hum_g');
                    $llS = $f($row, 'll_sec_g');
                    $lpT = $f($row, 'lp_tara_g');
                    $lpH = $f($row, 'lp_hum_g');
                    $lpS = $f($row, 'lp_sec_g');

                    $hum = ($th !== null && $ts !== null && $tt !== null && ($ts - $tt) > 0) ? round((($th - $ts) / ($ts - $tt)) * 100.0, 2) : null;
                    $ll = ($llH !== null && $llS !== null && $llT !== null && ($llS - $llT) > 0) ? round((($llH - $llS) / ($llS - $llT)) * 100.0, 1) : null;
                    $lp = ($lpH !== null && $lpS !== null && $lpT !== null && ($lpS - $lpT) > 0) ? round((($lpH - $lpS) / ($lpS - $lpT)) * 100.0, 1) : null;
                    $ip = ($ll !== null && $lp !== null) ? round(max(0.0, $ll - $lp), 1) : null;

                    if ($hum !== null) { $calc['humedad_pct'] = number_format($hum, 2, '.', ''); $tieneDatos = true; }
                    if ($ll !== null) { $calc['ll_pct'] = number_format($ll, 1, '.', ''); $tieneDatos = true; }
                    if ($lp !== null) { $calc['lp_pct'] = number_format($lp, 1, '.', ''); $tieneDatos = true; }
                    if ($ip !== null) { $calc['ip_pct'] = number_format($ip, 1, '.', ''); $tieneDatos = true; }

                    foreach ($filasMatriz as $mi => $mRow) {
                        if (!is_array($mRow)) continue;
                        $desc = mb_strtolower(trim((string)($mRow['Descripción'] ?? ($mRow['Malla'] ?? ''))), 'UTF-8');
                        $colRes = array_key_exists('Resultado', $mRow) ? 'Resultado' : '% que pasa la malla';
                        if ($ll !== null && str_contains($desc, 'límite l')) $filasMatriz[$mi][$colRes] = number_format($ll, 1, '.', '');
                        if ($lp !== null && str_contains($desc, 'límite pl')) $filasMatriz[$mi][$colRes] = number_format($lp, 1, '.', '');
                        if ($ip !== null && ($desc === 'i.p' || str_contains($desc, 'índice'))) $filasMatriz[$mi][$colRes] = number_format($ip, 1, '.', '');
                        if ($hum !== null && str_contains($desc, 'humedad')) $filasMatriz[$mi][$colRes] = number_format($hum, 2, '.', '');
                    }
                    break;
                }

                case 'granulometria_agregado': {
                    $mIni = $f($row, 'masa_inicial_g');
                    $mLav = $f($row, 'masa_lavada_g');
                    if ($mIni !== null && $mIni > 0 && $mLav !== null && $mLav >= 0) {
                        $perdG = max(0.0, $mIni - $mLav);
                        $perdPct = round(($perdG / $mIni) * 100.0, 2);
                        $calc['perdida_lavado_g'] = number_format($perdG, 2, '.', '');
                        $calc['perdida_lavado_pct'] = number_format($perdPct, 2, '.', '');
                        $tieneDatos = true;
                        foreach ($filasMatriz as $mi => $mRow) {
                            if (!is_array($mRow)) continue;
                            $malla = mb_strtolower(trim((string)($mRow['Malla'] ?? ($mRow['Descripción'] ?? ''))), 'UTF-8');
                            if (str_contains($malla, 'lavado') || str_contains($malla, 'pérdida')) {
                                if (array_key_exists('P. Retenido parcial (g)', $mRow)) {
                                    $filasMatriz[$mi]['P. Retenido parcial (g)'] = number_format($perdG, 2, '.', '');
                                } elseif (array_key_exists('Resultado', $mRow)) {
                                    $filasMatriz[$mi]['Resultado'] = number_format($perdPct, 2, '.', '');
                                }
                            }
                        }
                    }
                    break;
                }

                case 'ge_agregados': {
                    $frac = strtolower(trim((string)($row['fraccion'] ?? 'agregado fino')));
                    $b = $f($row, 'masa_sss_g');
                    $a = $f($row, 'masa_seca_g');
                    $c = $f($row, 'masa_frasco_agua_g');
                    $d = $f($row, 'masa_sumergida_g');

                    if ($a > 0 && $b > 0) {
                        $abs = round((($b - $a) / $a) * 100.0, 2);
                        $calc['absorcion_pct'] = number_format($abs, 2, '.', '');
                        $tieneDatos = true;

                        $geBulk = null;
                        $geSss = null;
                        $geAp = null;
                        if (str_contains($frac, 'grueso') && $d !== null) {
                            $cCan = $c ?? 0.0;
                            $sumNeto = $d - $cCan;
                            if (($b - $sumNeto) > 0 && ($a - $sumNeto) > 0) {
                                $geBulk = round($a / ($b - $sumNeto), 3);
                                $geSss = round($b / ($b - $sumNeto), 3);
                                $geAp = round($a / ($a - $sumNeto), 3);
                            }
                        } elseif ($c !== null && $d !== null) {
                            if (($b + $c - $d) > 0 && ($a + $c - $d) > 0) {
                                $geBulk = round($a / ($b + $c - $d), 3);
                                $geSss = round($b / ($b + $c - $d), 3);
                                $geAp = round($a / ($a + $c - $d), 3);
                            }
                        }
                        if ($geBulk !== null) $calc['ge_bulk'] = number_format($geBulk, 3, '.', '');
                        if ($geSss !== null) $calc['ge_sss'] = number_format($geSss, 3, '.', '');
                        if ($geAp !== null) $calc['ge_aparente'] = number_format($geAp, 3, '.', '');

                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if (!empty($row['color'])) $filasMatriz[$mIdx]['Color'] = (string)$row['color'];
                        if (!empty($row['humedad_natural'])) $filasMatriz[$mIdx]['Humedad Natural ((%) (P/P))'] = (string)$row['humedad_natural'];
                        $filasMatriz[$mIdx]['Absorción ((%)(P/P))'] = number_format($abs, 2, '.', '');
                        if ($geBulk !== null) $filasMatriz[$mIdx]['Gravedad Específica.'] = number_format($geBulk, 3, '.', '');
                        if ($geSss !== null) $filasMatriz[$mIdx]['Gravedad Específica (SSS)'] = number_format($geSss, 3, '.', '');
                        if ($geAp !== null) $filasMatriz[$mIdx]['Gravedad Específica Aparente'] = number_format($geAp, 3, '.', '');
                    }
                    break;
                }

                case 'cbr': {
                    $golpes = trim((string)($row['golpes'] ?? '56'));
                    $mh = $f($row, 'molde_humedo_g');
                    $mm = $f($row, 'masa_molde_g') ?: 7849.0;
                    $vol = $f($row, 'volumen_molde_cm3') ?: 3200.0;
                    $th = $f($row, 'tara_humedo_g');
                    $ts = $f($row, 'tara_seco_g');
                    $tt = $f($row, 'tara_g');
                    $pvsMax = $f($row, 'pvs_max');
                    $l01 = $f($row, 'lectura_01');
                    $l02 = $f($row, 'lectura_02');
                    $kAnillo = $f($row, 'constante_anillo') ?: 10.0;
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $hum = ($th !== null && $ts !== null && $tt !== null && ($ts - $tt) > 0) ? round((($th - $ts) / ($ts - $tt)) * 100.0, 1) : null;
                    $pvs = null;
                    if ($mh !== null && ($mh - $mm) > 0 && $vol > 0) {
                        $pvh = (($mh - $mm) / $vol) * 1000.0;
                        $pvs = $hum !== null ? round($pvh / (1.0 + ($hum / 100.0)), 0) : round($pvh, 0);
                        $calc['densidad_seca'] = (string)(int)$pvs;
                        $tieneDatos = true;
                    }
                    $comp = ($pvs !== null && $pvsMax > 0) ? round(($pvs / $pvsMax) * 100.0, 1) : null;
                    if ($comp !== null) $calc['compactacion_pct'] = number_format($comp, 1, '.', '');

                    $cbr01 = ($l01 !== null && $l01 > 0) ? round((($l01 * $kAnillo) / 3.0) / 1000.0 * 100.0, 1) : null;
                    $cbr02 = ($l02 !== null && $l02 > 0) ? round((($l02 * $kAnillo) / 3.0) / 1500.0 * 100.0, 1) : null;
                    $cbrAd = ($cbr01 !== null || $cbr02 !== null) ? max((float)($cbr01 ?? 0), (float)($cbr02 ?? 0)) : null;

                    if ($cbr01 !== null) { $calc['cbr_01'] = number_format($cbr01, 1, '.', ''); $tieneDatos = true; }
                    if ($cbr02 !== null) { $calc['cbr_02'] = number_format($cbr02, 1, '.', ''); $tieneDatos = true; }
                    if ($cbrAd !== null) { $calc['cbr_adoptado'] = number_format($cbrAd, 1, '.', ''); }

                    if ($pvs !== null || $cbrAd !== null) {
                        if (!isset($filasMatriz[$idx])) $filasMatriz[$idx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$idx]['Golpes/Capa'] = $golpes;
                        if ($pvs !== null) $filasMatriz[$idx]['Densidad Seca (kg/m³)'] = (string)(int)$pvs;
                        if ($hum !== null) $filasMatriz[$idx]['Humedad Moldeo (%)'] = number_format($hum, 1, '.', '');
                        if ($comp !== null) $filasMatriz[$idx]['Compactación (%)'] = number_format($comp, 1, '.', '');
                        if ($cbr01 !== null) $filasMatriz[$idx]['CBR 0.1" (%)'] = number_format($cbr01, 1, '.', '');
                        if ($cbr02 !== null) $filasMatriz[$idx]['CBR 0.2" (%)'] = number_format($cbr02, 1, '.', '');
                        if ($cbrAd !== null) $filasMatriz[$idx]['CBR Adoptado (%)'] = number_format($cbrAd, 1, '.', '');
                        if ($fEns !== '') $filasMatriz[$idx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'ge_suelos': {
                    $pic = (string)($row['picnometro'] ?? 'PICNO-02');
                    $temp = $f($row, 'temperatura_c') ?? 20.0;
                    $ms = $f($row, 'masa_suelo_seco_g');
                    $mpw = $f($row, 'masa_picno_agua_g');
                    $mpws = $f($row, 'masa_picno_agua_suelo_g');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $rhoT = 1.00034038 - (0.00000777 * $temp) - (0.00000495 * ($temp ** 2));
                    $rho20 = 0.9982071;
                    $kFactor = round($rhoT / $rho20, 4);
                    $calc['factor_k'] = number_format($kFactor, 4, '.', '');

                    if ($ms > 0 && $mpw !== null && $mpws !== null && (($ms + $mpw) - $mpws) > 0) {
                        $gs = round(($ms / (($ms + $mpw) - $mpws)) * $kFactor, 3);
                        $calc['gs'] = number_format($gs, 3, '.', '');
                        $tieneDatos = true;

                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$mIdx]['Picnómetro (N°)'] = $pic;
                        $filasMatriz[$mIdx]['Temperatura (°C)'] = number_format($temp, 1, '.', '');
                        $filasMatriz[$mIdx]['Masa Suelo Seco (g)'] = number_format($ms, 2, '.', '');
                        $filasMatriz[$mIdx]['Gravedad Específica (Gs)'] = number_format($gs, 3, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'acero_tension': {
                    $varilla = (string)($row['varilla_num'] ?? '');
                    $diam = $f($row, 'diametro_mm');
                    $peso = $f($row, 'peso_kg');
                    $largo = $f($row, 'largo_cm');
                    $l0 = $f($row, 'l0_cm');
                    $lf = $f($row, 'lf_cm');
                    $fmKn = $f($row, 'fuerza_max_kn');
                    $fluMpa = $f($row, 'fluencia_mpa');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $areaMm2 = ($diam > 0) ? round(M_PI * (($diam / 2.0) ** 2), 2) : null;
                    $pesoUnit = ($peso > 0 && $largo > 0) ? round($peso / ($largo / 100.0), 3) : null;
                    $fluPsi = ($fluMpa > 0) ? round($fluMpa * 145.0377, 0) : null;
                    $tensPsi = ($fmKn > 0 && $areaMm2 > 0) ? round((($fmKn * 1000.0) / $areaMm2) * 145.0377, 0) : null;
                    $tensKg = ($tensPsi !== null) ? round($tensPsi / $divisorKgCm2, 0) : null;
                    $elong = ($l0 > 0 && $lf !== null && $lf >= $l0) ? round((($lf - $l0) / $l0) * 100.0, 1) : null;

                    if ($areaMm2 !== null) { $calc['area_mm2'] = number_format($areaMm2, 2, '.', ''); $tieneDatos = true; }
                    if ($pesoUnit !== null) $calc['peso_unitario'] = number_format($pesoUnit, 3, '.', '');
                    if ($fluPsi !== null) { $calc['fluencia_psi'] = (string)(int)$fluPsi; $tieneDatos = true; }
                    if ($tensPsi !== null) { $calc['tension_psi'] = (string)(int)$tensPsi; $tieneDatos = true; }
                    if ($elong !== null) { $calc['elongacion_pct'] = number_format($elong, 1, '.', ''); $tieneDatos = true; }

                    if ($areaMm2 !== null || $tensPsi !== null || $fluPsi !== null) {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($varilla !== '') $filasMatriz[$mIdx]['Varilla (N°)'] = $varilla;
                        if ($diam !== null) $filasMatriz[$mIdx]['Diámetro (mm)'] = number_format($diam, 2, '.', '');
                        if ($areaMm2 !== null) $filasMatriz[$mIdx]['Área (mm²)'] = number_format($areaMm2, 2, '.', '');
                        if ($pesoUnit !== null) $filasMatriz[$mIdx]['Peso Unitario (kg/m)'] = number_format($pesoUnit, 3, '.', '');
                        if ($fluMpa !== null) $filasMatriz[$mIdx]['Fluencia (MPa)'] = number_format($fluMpa, 1, '.', '');
                        if ($fluPsi !== null) $filasMatriz[$mIdx]['Fluencia (lbf/in²)'] = (string)(int)$fluPsi;
                        if ($tensPsi !== null) $filasMatriz[$mIdx]['Tensión Máx. (lbf/in²)'] = (string)(int)$tensPsi;
                        if ($tensKg !== null) $filasMatriz[$mIdx]['Tensión Máx. (kg/cm²)'] = (string)(int)$tensKg;
                        if ($elong !== null) $filasMatriz[$mIdx]['Elongación (%)'] = number_format($elong, 1, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'acero_doblado': {
                    $insp = (string)($row['inspeccion'] ?? '');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');
                    if ($insp !== '' || !empty($row['varilla_num'])) {
                        $resTxt = str_contains($insp, 'Falla') ? 'Falla' : 'Cumple';
                        $calc['resultado'] = $resTxt;
                        $tieneDatos = true;
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if (!empty($row['varilla_num'])) $filasMatriz[$mIdx]['Varilla (N°)'] = (string)$row['varilla_num'];
                        if (!empty($row['diametro_mm'])) $filasMatriz[$mIdx]['Diámetro Nominal (mm)'] = (string)$row['diametro_mm'];
                        if (!empty($row['angulo_deg'])) $filasMatriz[$mIdx]['Ángulo de Doblado (°)'] = (string)$row['angulo_deg'];
                        if (!empty($row['mandril'])) $filasMatriz[$mIdx]['Diámetro de Mandril'] = (string)$row['mandril'];
                        $filasMatriz[$mIdx]['Resultado (Cumple/Falla)'] = $resTxt;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'sanidad': {
                    $frac = trim((string)($row['fraccion'] ?? $codLab));
                    $retOrig = $f($row, 'retenido_original_g');
                    $mIni = $f($row, 'masa_inicial_g');
                    $mFin = $f($row, 'masa_final_g');
                    if ($mIni > 0 && $mFin !== null) {
                        $perdG = max(0.0, $mIni - $mFin);
                        $perdPct = round(($perdG / $mIni) * 100.0, 2);
                        $gradPct = ($sanidadTotalRetOrig > 0 && $retOrig > 0) ? round(($retOrig / $sanidadTotalRetOrig) * 100.0, 2) : 0.0;
                        $pondPct = round(($gradPct * $perdPct) / 100.0, 2);
                        $calc['gradacion_pct'] = number_format($gradPct, 2, '.', '');
                        $calc['perdida_g'] = number_format($perdG, 2, '.', '');
                        $calc['perdida_pct'] = number_format($perdPct, 2, '.', '');
                        $calc['perdida_ponderada_pct'] = number_format($pondPct, 2, '.', '');
                        $tieneDatos = true;

                        $filasMatriz[$idx] = [
                            'Fracción' => $frac,
                            'Retenido Original (g)' => $retOrig !== null ? number_format($retOrig, 2, '.', '') : '',
                            '% Gradación Original' => number_format($gradPct, 2, '.', ''),
                            'Masa Inicial (g)' => number_format($mIni, 2, '.', ''),
                            'Masa Final (g)' => number_format($mFin, 2, '.', ''),
                            'Pérdida (g)' => number_format($perdG, 2, '.', ''),
                            '% Pérdida Fracción' => number_format($perdPct, 2, '.', ''),
                            '% Pérdida Ponderada' => number_format($pondPct, 2, '.', ''),
                        ];
                    }
                    break;
                }

                case 'ladrillo': {
                    $largo = $f($row, 'largo_cm');
                    $ancho = $f($row, 'ancho_cm');
                    $alto = $f($row, 'alto_cm');
                    $masa = $f($row, 'masa_kg');
                    $cargaLb = $f($row, 'carga_lb');
                    $cargaKg = $f($row, 'carga_kg');
                    if (($cargaKg === null || $cargaKg <= 0) && $cargaLb > 0) {
                        $cargaKg = round($cargaLb * 0.45359237, 1);
                    }
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');
                    $areaCm2 = ($largo > 0 && $ancho > 0) ? round($largo * $ancho, 2) : null;
                    $pv = ($largo > 0 && $ancho > 0 && $alto > 0 && $masa > 0) ? round($masa / (($largo * $ancho * $alto) / 1000000.0), 0) : null;
                    $kgcm2 = ($areaCm2 > 0 && $cargaKg > 0) ? round($cargaKg / $areaCm2, 1) : null;
                    if ($areaCm2 !== null) { $calc['area_cm2'] = number_format($areaCm2, 2, '.', ''); $tieneDatos = true; }
                    if ($pv !== null) $calc['peso_volumetrico'] = (string)(int)$pv;
                    if ($kgcm2 !== null) { $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', ''); $tieneDatos = true; }
                    if ($areaCm2 !== null || $cargaKg !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($areaCm2 !== null) $filasMatriz[$mIdx]['Área (cm²)'] = number_format($areaCm2, 2, '.', '');
                        if ($cargaKg !== null && $cargaKg > 0) $filasMatriz[$mIdx]['Carga (kg)'] = (string)round($cargaKg, 0);
                        if ($pv !== null) $filasMatriz[$mIdx]['Peso Volumétrico (kg/m³)'] = (string)(int)$pv;
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['R. a la compresión (kg/cm²)'] = number_format($kgcm2, 1, '.', '');
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'roca': {
                    $d1 = $f($row, 'diametro_1');
                    $d2 = $f($row, 'diametro_2');
                    $h1 = $f($row, 'largo_1');
                    $h2 = $f($row, 'largo_2');
                    $cargaLb = $f($row, 'carga_lb');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');

                    $dProm = ($d1 > 0 && $d2 > 0) ? round(($d1 + $d2) / 2.0, 2) : ($d1 ?: $d2);
                    $hProm = ($h1 > 0 && $h2 > 0) ? round(($h1 + $h2) / 2.0, 2) : ($h1 ?: $h2);
                    $ld = ($dProm > 0 && $hProm > 0) ? round($hProm / $dProm, 2) : null;
                    $areaCm2 = ($dProm > 0) ? round(M_PI * (($dProm / 2.0) ** 2), 2) : null;
                    $psi = ($areaCm2 > 0 && $cargaLb > 0) ? round($cargaLb / ($areaCm2 / 6.4516), 0) : null;
                    $kgcm2 = ($psi !== null) ? round($psi / $divisorKgCm2, 1) : null;
                    $mpa = ($psi !== null) ? round($psi * 0.00689476, 2) : null;

                    if ($ld !== null) $calc['relacion_ld'] = number_format($ld, 2, '.', '');
                    if ($areaCm2 !== null) { $calc['area_cm2'] = number_format($areaCm2, 2, '.', ''); $tieneDatos = true; }
                    if ($mpa !== null) { $calc['esfuerzo_mpa'] = number_format($mpa, 2, '.', ''); $tieneDatos = true; }
                    if ($kgcm2 !== null) $calc['esfuerzo_kgcm2'] = number_format($kgcm2, 1, '.', '');
                    if ($psi !== null) $calc['esfuerzo_psi'] = (string)(int)$psi;

                    if ($areaCm2 !== null || $psi !== null) {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($dProm !== null) $filasMatriz[$mIdx]['Diámetro (cm)'] = number_format($dProm, 2, '.', '');
                        if ($hProm !== null) $filasMatriz[$mIdx]['Altura (cm)'] = number_format($hProm, 2, '.', '');
                        if ($ld !== null) $filasMatriz[$mIdx]['Relación L/D'] = number_format($ld, 2, '.', '');
                        if ($areaCm2 !== null) $filasMatriz[$mIdx]['Área (cm²)'] = number_format($areaCm2, 2, '.', '');
                        if ($cargaLb !== null) $filasMatriz[$mIdx]['Carga Máx. (lb)'] = (string)(int)round($cargaLb);
                        if ($mpa !== null) $filasMatriz[$mIdx]['Resistencia (MPa)'] = number_format($mpa, 2, '.', '');
                        if ($kgcm2 !== null) $filasMatriz[$mIdx]['Resistencia (kg/cm²)'] = number_format($kgcm2, 1, '.', '');
                        if ($psi !== null) $filasMatriz[$mIdx]['Resistencia (lbf/in²)'] = (string)(int)$psi;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }

                case 'pesos_volumetricos': {
                    $volL = $f($row, 'vol_recipiente_l');
                    $pRec = $f($row, 'peso_recipiente_kg');
                    $pSuel = $f($row, 'peso_suelto_kg');
                    $pComp = $f($row, 'peso_compacto_kg');
                    $th = $f($row, 'tara_humedo_g');
                    $ts = $f($row, 'tara_seco_g');
                    $tt = $f($row, 'tara_g');

                    $volM3 = ($volL > 0) ? ($volL / 1000.0) : null;
                    $pvss = ($volM3 > 0 && $pRec !== null && $pSuel !== null && ($pSuel - $pRec) > 0) ? round(($pSuel - $pRec) / $volM3, 0) : null;
                    $pvsc = ($volM3 > 0 && $pRec !== null && $pComp !== null && ($pComp - $pRec) > 0) ? round(($pComp - $pRec) / $volM3, 0) : null;
                    $hum = ($th !== null && $ts !== null && $tt !== null && ($ts - $tt) > 0) ? round((($th - $ts) / ($ts - $tt)) * 100.0, 2) : null;

                    if ($pvss !== null) { $calc['pvss'] = (string)(int)$pvss; $tieneDatos = true; }
                    if ($pvsc !== null) { $calc['pvsc'] = (string)(int)$pvsc; $tieneDatos = true; }
                    if ($hum !== null) { $calc['humedad_pct'] = number_format($hum, 2, '.', ''); $tieneDatos = true; }

                    if ($pvss !== null || $pvsc !== null || $hum !== null) {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($pvss !== null) $filasMatriz[$mIdx]['PVSS (kg/m³)'] = (string)(int)$pvss;
                        if ($pvsc !== null) $filasMatriz[$mIdx]['PVSC (kg/m³)'] = (string)(int)$pvsc;
                        if ($hum !== null) $filasMatriz[$mIdx]['Humedad Natural ((%)(P/P))'] = number_format($hum, 2, '.', '');
                    }
                    break;
                }

                case 'equivalente_arena': {
                    $vals = [];
                    for ($k = 1; $k <= 3; $k++) {
                        $arc = $f($row, 'arcilla_' . $k);
                        $are = $f($row, 'arena_' . $k);
                        if ($arc > 0 && $are !== null && $are >= 0) {
                            $vals[] = ceil(round(($are / $arc) * 100.0, 6));
                        }
                    }
                    if (!empty($vals)) {
                        $prom = (int)ceil(round(array_sum($vals) / count($vals), 6));
                        $calc['equiv_arena_pct'] = (string)$prom;
                        $tieneDatos = true;
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$mIdx]['Equivalente de arena. ((%)(P/P))'] = (string)$prom;
                    }
                    break;
                }

                case 'particulas_desmenuzables': {
                    $mIni = $f($row, 'masa_inicial_g');
                    $mRet = $f($row, 'masa_retenida_g');
                    if ($mIni > 0 && $mRet !== null && $mRet >= 0) {
                        $pct = round((max(0.0, $mIni - $mRet) / $mIni) * 100.0, 2);
                        $calc['terrones_pct'] = number_format($pct, 2, '.', '');
                        $tieneDatos = true;
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$mIdx]['Terrones de arcilla. ((%)(P/P))'] = number_format($pct, 2, '.', '');
                    }
                    break;
                }

                case 'revenimiento': {
                    $revIn = $f($row, 'revenimiento_in');
                    $tempC = $f($row, 'temperatura_c');
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');
                    $revCm = ($revIn !== null && $revIn > 0) ? ConfigCalculoLabServicio::calcularRevenimientoCm($revIn) : null;
                    if ($revCm !== null) {
                        $calc['revenimiento_cm'] = (string)$revCm;
                        $tieneDatos = true;
                    }
                    if ($revIn !== null || $tempC !== null || $fEns !== '') {
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        if ($revIn !== null) $filasMatriz[$mIdx]['Reven. (in)'] = (string)$revIn;
                        if ($revCm !== null) $filasMatriz[$mIdx]['Reven. (cm)'] = (string)$revCm;
                        if ($tempC !== null) $filasMatriz[$mIdx]['Temp. (°C)'] = (string)$tempC;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Finalización'] = $fEns;
                    }
                    break;
                }

                case 'colorimetria': {
                    $placa = trim((string)($row['placa_gardner'] ?? ''));
                    $fEns = $fechaIso($row['fecha_ensayo'] ?? '');
                    if ($placa !== '') {
                        $numPlaca = 1;
                        if (preg_match('/(\d+)/', $placa, $mPl)) $numPlaca = (int)$mPl[1];
                        $resTxt = ($numPlaca <= 3) ? 'Aceptable (≤ Placa 3)' : 'Rechazado (> Placa 3)';
                        $calc['resultado'] = $resTxt;
                        $tieneDatos = true;
                        $mIdx = $buscarIdxMatriz($filasMatriz, $codLab, $idx);
                        if (!isset($filasMatriz[$mIdx])) $filasMatriz[$mIdx] = ['Código laboratorio' => $codLab];
                        $filasMatriz[$mIdx]['Color (Placa Orgánica)'] = $placa;
                        $filasMatriz[$mIdx]['Resultado'] = $resTxt;
                        if ($fEns !== '') $filasMatriz[$mIdx]['Fecha de Ensayo'] = $fEns;
                    }
                    break;
                }
            }

            $tecnicasOut[] = $calc;
        }

        // Resumen multi-punto: Proctor (máximo medido por defecto según Fase 1.8)
        if ($motor === 'proctor' && !empty($proctorPuntos)) {
            usort($proctorPuntos, static fn($a, $b) => $b['pvs'] <=> $a['pvs']);
            $mejor = $proctorPuntos[0];
            if (!isset($filasMatriz[0]) || !is_array($filasMatriz[0])) {
                $filasMatriz[0] = ['Código laboratorio' => 'MS-0001-' . date('y')];
            }
            $filasMatriz[0]['P.Vol.Seco Máximo (kg/m³)'] = (string)(int)round($mejor['pvs']);
            $filasMatriz[0]['Cont. Humedad Óptima ((%)(P/P))'] = number_format((float)$mejor['hum'], 1, '.', '');
            if (!empty($mejor['metodo'])) $filasMatriz[0]['Método Ensayo'] = $mejor['metodo'];
            if (!empty($mejor['fecha'])) $filasMatriz[0]['Fecha de Ensayo'] = $mejor['fecha'];
        }

        // Resumen multi-unidad: Bloques (promedios hacia filas verticales fijas de RT-FM-22 A)
        if ($motor === 'bloque' && !empty($bloqueResultados)) {
            $avg = static function (array $arr, string $k): ?float {
                $v = array_values(array_filter(array_column($arr, $k), static fn($x) => $x !== null && is_numeric($x) && (float)$x > 0));
                return !empty($v) ? (array_sum($v) / count($v)) : null;
            };
            $mapaBloque = [
                'área bruta' => ($v = $avg($bloqueResultados, 'area_bruta_in2')) !== null ? number_format($v, 2, '.', '') : null,
                'área neta' => ($v = $avg($bloqueResultados, 'area_neta_in2')) !== null ? number_format($v, 2, '.', '') : null,
                'carga' => ($v = $avg($bloqueResultados, 'carga')) !== null ? (string)(int)round($v) : null,
                'largo' => ($v = $avg($bloqueResultados, 'largo')) !== null ? number_format($v, 2, '.', '') : null,
                'alto' => ($v = $avg($bloqueResultados, 'alto')) !== null ? number_format($v, 2, '.', '') : null,
                'ancho' => ($v = $avg($bloqueResultados, 'ancho')) !== null ? number_format($v, 2, '.', '') : null,
                'espesor interno' => ($v = $avg($bloqueResultados, 'esp_int')) !== null ? number_format($v, 2, '.', '') : null,
                'espesor externo' => ($v = $avg($bloqueResultados, 'esp_ext')) !== null ? number_format($v, 2, '.', '') : null,
                'peso volumétrico' => ($v = $avg($bloqueResultados, 'densidad')) !== null ? (string)(int)round($v) : null,
                'densidad' => ($v = $avg($bloqueResultados, 'densidad')) !== null ? (string)(int)round($v) : null,
                'absorción' => ($v = $avg($bloqueResultados, 'absorcion')) !== null ? number_format($v, 2, '.', '') : null,
            ];
            $avgPsi = $avg($bloqueResultados, 'psi');
            $avgKg = $avg($bloqueResultados, 'kgcm2');
            foreach ($filasMatriz as $mi => $mRow) {
                if (!is_array($mRow) || !isset($mRow['Descripción'])) continue;
                $desc = mb_strtolower(trim((string)$mRow['Descripción']), 'UTF-8');
                $unid = mb_strtolower(trim((string)($mRow['Unidad'] ?? '')), 'UTF-8');
                if (str_contains($desc, 'r. compresión')) {
                    if (str_contains($unid, 'lb') || str_contains($unid, 'psi')) {
                        if ($avgPsi !== null) $filasMatriz[$mi]['Resultado'] = (string)(int)round($avgPsi);
                    } elseif ($avgKg !== null) {
                        $filasMatriz[$mi]['Resultado'] = number_format($avgKg, 1, '.', '');
                    }
                    continue;
                }
                foreach ($mapaBloque as $clave => $val) {
                    if ($val !== null && str_contains($desc, $clave)) {
                        $filasMatriz[$mi]['Resultado'] = $val;
                        break;
                    }
                }
            }
        }

        return [
            'captura_tecnica' => $tecnicasOut,
            'filas_matriz' => $filasMatriz,
            'tiene_datos' => $tieneDatos,
        ];
    }

    /**
     * Factor de corrección por relación L/D según ASTM C39/C42.
     */
    public static function factorCorreccionLd(float $ld): float {
        if ($ld >= 1.75) return 1.0;
        $puntos = [
            [1.00, 0.87],
            [1.25, 0.93],
            [1.50, 0.96],
            [1.75, 0.98],
        ];
        if ($ld <= 1.00) return 0.87;
        for ($i = 0; $i < count($puntos) - 1; $i++) {
            [$x0, $y0] = $puntos[$i];
            [$x1, $y1] = $puntos[$i + 1];
            if ($ld >= $x0 && $ld <= $x1) {
                $t = ($ld - $x0) / ($x1 - $x0);
                return round($y0 + ($t * ($y1 - $y0)), 4);
            }
        }
        return 1.0;
    }
}
