<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Cotizaciones\Servicios;

/**
 * Anexo Técnico – Alcance del Servicio Mensual (CYCSA).
 * Basado en el formato oficial "ANEXO ALCANCE DEL SERVICIO MENSUAL".
 * Se almacena estructurado en JSON dentro de cotizaciones.anexo_tecnico
 * y se renderiza de forma idéntica en vista web (detalle.php) y PDF oficial (RG-FM-31).
 */
class AnexoServicioMensualServicio
{
    public const TIPO_ANEXO = 'anexo_alcance_mensual';

    /**
     * Retorna la estructura oficial por defecto del Anexo de Servicio Mensual.
     *
     * @return array<string, mixed>
     */
    public static function plantillaPorDefecto(): array
    {
        return [
            'tipo' => self::TIPO_ANEXO,
            'titulo' => 'CYCSA  |  ANEXO TÉCNICO – ALCANCE MENSUAL',
            'descripcion_servicio' => 'Servicio mensual de laboratorio in situ (30 días) para muestreo, ejecución de ensayos de suelos, concreto y emisión de informes de ensayos para el control de calidad del proyecto.',
            'condiciones' => [
                ['label' => 'Personal:', 'valor' => 'Un técnico analista de laboratorio tipo B'],
                ['label' => 'Jornada:', 'valor' => 'Jornada de 63 horas diurnas semanales, con régimen de salidas catorcenales y autorización de una (1) hora extra por día. El servicio no incluye técnico de reemplazo durante los días de descanso correspondientes a la rotación del personal.'],
                ['label' => 'Alimentación y viáticos del técnico:', 'valor' => 'Incluida.'],
                ['label' => 'Hospedaje:', 'valor' => 'Incluido'],
                ['label' => 'Traslado de muestras:', 'valor' => 'Incluye traslado de muestras una vez por semana.'],
                ['label' => 'Horas extras', 'valor' => 'Las horas que excedan la jornada semanal contemplada en el servicio mensual tendrán un costo adicional de C$250.00 por hora, previa autorización del cliente y presentación del registro de asistencia debidamente firmado. La hora extra diaria contemplada en el servicio contratado no generará cobros adicionales.'],
                ['label' => 'Cancelación anticipada:', 'valor' => 'El servicio es mensual, mínimo C$ 7,400 por día.'],
                ['label' => 'Procesamiento posterior al retiro:', 'valor' => '50 % del valor mensual o costo unitario de ensayos de resistencia posterior a la finalización.'],
                ['label' => 'Exclusiones', 'valor' => 'Diseños de mezcla y ensayos fuera de los detallados en lista de ensayos.'],
                ['label' => 'Movilizaciones adicionales', 'valor' => 'Las movilizaciones adicionales solicitadas tendrán un costo de C$ 2,300.00 más IVA.'],
            ],
            'ensayos_intro' => 'Incluye realizar los siguientes ensayos in situ y ensayos de resistencia a compresión en el Laboratorio CYCSA.',
            'ensayos' => [
                ['categoria' => 'Agregados', 'ensayo' => 'Humedad en agregados, ASTM C566-25', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Absorción de Agregado Grueso – ASTM C127-25', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Absorción de Agregado Fino – ASTM C128-25', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Densidad relativa (gravedad específica) y absorción de agregado grueso – ASTM C127-25', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Densidad relativa (gravedad específica) y absorción de agregado fino ASTM C128-25', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Peso unitario PVSS, PVSC ASTM C29', 'incluido' => true],
                ['categoria' => 'Agregados', 'ensayo' => 'Granulometría y contenido de finos de agregados ASTM C136/C136M-25 ASTM C117-23', 'incluido' => true],
                ['categoria' => 'Concreto/Cilindro', 'ensayo' => 'Resistencia a Compresión del Concreto – ASTM C39/C39M-26', 'incluido' => true],
                ['categoria' => 'Concreto', 'ensayo' => 'Revenimiento del Concreto – ASTM C143/C143M-26b', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Contenido de Humedad en Suelos – ASTM D2216-19', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Gravedad Específica de Suelos (Picnómetro) – ASTM D854-23', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Granulometría de Suelos – ASTM D6913/D6913M-17 (2025)', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Límites de Atterberg de Suelos – ASTM D4318-26', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Compactación de Suelos (Proctor Estándar) – ASTM D698-12 (2021)', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Compactación de Suelos (Proctor Modificado) – ASTM D1557-12 (2021)', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23', 'incluido' => true],
                ['categoria' => 'Concreto', 'ensayo' => 'Temperatura del Concreto Fresco – ASTM C1064/C1064M-23', 'incluido' => true],
                ['categoria' => 'Viguetas', 'ensayo' => 'Resistencia a Flexión del Concreto (Tercer Punto) – ASTM C78/C78M-22', 'incluido' => true],
                ['categoria' => 'Mortero', 'ensayo' => 'Resistencia a Compresión de Morteros – ASTM C109/C109M-26a', 'incluido' => true],
                ['categoria' => 'Suelo', 'ensayo' => 'Resistencia a Compresión de Suelo-Cemento – ASTM D1633-17', 'incluido' => true],
            ],
            'recursos_entrega' => "Informe de ensayos en formato digital.\nCYCSA proporcionará en el sitio del proyecto todos los equipos y materiales necesarios para la realización de los ensayos.\nEl técnico analista de laboratorio presentará en el campo al representante autorizado por EL CLIENTE un informe preliminar inmediato a la realización de las pruebas y posteriormente se enviará informe formal de ensayos en formato pdf al correo designado por el cliente, pudiendo establecer cortes, semanales, quincenales o mensuales.",
            'responsabilidades_cliente' => 'El servicio no incluye vehículo para la movilización dentro del proyecto, área para la instalación del laboratorio ni vigilancia de las instalaciones. EL CLIENTE deberá proporcionar el vehículo, un espacio adecuado para la instalación de los equipos y herramientas de laboratorio, así como garantizar la vigilancia las 24 horas del día para la protección y seguridad de las instalaciones, equipos y materiales asignados por CYCSA al proyecto.',
            'incluir_seguridad_radiologica' => true,
            'seguridad_radiologica_intro' => 'Densímetro nuclear: El Cliente deberá suministrar un lugar adecuado para el almacenaje del densímetro nuclear; el lugar debe tener condiciones de seguridad física y de protección radiológica. A continuación, se detallan las condiciones mínimas que el operador y/o Técnico Analista de Laboratorio deberá verificar e informar a CYCSA que se cumpla con los siguientes:',
            'seguridad_radiologica_puntos' => [
                '1. No existirán personas que duerman o trabajen en los locales contiguos al área donde se almacenará temporalmente el equipo.',
                '2. Para el almacenamiento temporal de densímetros en la obra el cliente deberá asignar un bunker exclusivo mínimo 3mx3m la que deberá contar con suficientes medidas de seguridad física y de protección radiológica como son candados en las puertas, control de llaves, puertas con suficiente seguridad estructural, etc.',
                '3. Se requerirá la existencia de una persona de seguridad física (guardia de seguridad) que controle este local mientras los equipos se encuentren almacenados en el mismo.',
                '4. Se colocará temporalmente señalización de peligro radiactivo en la puerta del local seleccionado.',
            ],
            'seguridad_radiologica_cierre' => 'El equipo debe ser utilizado únicamente en el proyecto para el cuál ha sido contratado, de haber cambios el cliente deberá notificar a CYCSA con anticipación.',
        ];
    }

    public static function esAnexoMensualJson(?string $raw): bool
    {
        if ($raw === null) {
            return false;
        }
        $trim = trim($raw);
        if ($trim === '' || $trim[0] !== '{') {
            return false;
        }
        $dec = json_decode($trim, true);
        return is_array($dec) && ($dec['tipo'] ?? '') === self::TIPO_ANEXO;
    }

    /**
     * Decodifica el JSON del Anexo Mensual o devuelve la plantilla por defecto.
     *
     * @return array<string, mixed>
     */
    public static function decodificar(?string $raw): array
    {
        $def = self::plantillaPorDefecto();
        if (!self::esAnexoMensualJson($raw)) {
            return $def;
        }
        $dec = json_decode((string)$raw, true);
        if (!is_array($dec)) {
            return $def;
        }
        return array_merge($def, $dec);
    }

    /**
     * Construye la estructura normalizada del Anexo Mensual desde los campos POST del formulario.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public static function construirDesdePost(array $datos): array
    {
        $def = self::plantillaPorDefecto();
        $am = isset($datos['anexo_mensual']) && is_array($datos['anexo_mensual']) ? $datos['anexo_mensual'] : [];
        if (empty($am)) {
            return $def;
        }

        $titulo = trim((string)($am['titulo'] ?? $def['titulo']));
        $desc = trim((string)($am['descripcion_servicio'] ?? $def['descripcion_servicio']));
        $ensIntro = trim((string)($am['ensayos_intro'] ?? $def['ensayos_intro']));
        $recEnt = trim((string)($am['recursos_entrega'] ?? $def['recursos_entrega']));
        $respCli = trim((string)($am['responsabilidades_cliente'] ?? $def['responsabilidades_cliente']));
        $incRad = !empty($am['incluir_seguridad_radiologica']);
        $radIntro = trim((string)($am['seguridad_radiologica_intro'] ?? $def['seguridad_radiologica_intro']));
        $radCierre = trim((string)($am['seguridad_radiologica_cierre'] ?? $def['seguridad_radiologica_cierre']));

        // Condiciones (pares etiqueta/valor)
        $condiciones = [];
        $condLabels = is_array($am['cond_label'] ?? null) ? $am['cond_label'] : [];
        $condValores = is_array($am['cond_valor'] ?? null) ? $am['cond_valor'] : [];
        $maxC = max(count($condLabels), count($condValores));
        for ($i = 0; $i < $maxC; $i++) {
            $lbl = trim((string)($condLabels[$i] ?? ''));
            $val = trim((string)($condValores[$i] ?? ''));
            if ($lbl !== '' || $val !== '') {
                $condiciones[] = ['label' => $lbl, 'valor' => $val];
            }
        }
        if (empty($condiciones)) {
            $condiciones = $def['condiciones'];
        }

        // Ensayos incluidos
        $ensayos = [];
        $ensCats = is_array($am['ensayo_categoria'] ?? null) ? $am['ensayo_categoria'] : [];
        $ensNoms = is_array($am['ensayo_nombre'] ?? null) ? $am['ensayo_nombre'] : [];
        $ensActivos = is_array($am['ensayo_incluido'] ?? null) ? $am['ensayo_incluido'] : [];
        $maxE = max(count($ensCats), count($ensNoms));
        for ($i = 0; $i < $maxE; $i++) {
            $cat = trim((string)($ensCats[$i] ?? ''));
            $nom = trim((string)($ensNoms[$i] ?? ''));
            if ($cat === '' && $nom === '') {
                continue;
            }
            $activo = !empty($ensActivos[$i]) && (string)$ensActivos[$i] !== '0';
            $ensayos[] = [
                'categoria' => $cat !== '' ? $cat : 'General',
                'ensayo' => $nom,
                'incluido' => $activo,
            ];
        }
        if (empty($ensayos)) {
            $ensayos = $def['ensayos'];
        }

        // Puntos de seguridad radiológica (1 por línea en textarea)
        $radPuntosRaw = (string)($am['seguridad_radiologica_puntos_texto'] ?? '');
        $radPuntos = [];
        if (trim($radPuntosRaw) !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $radPuntosRaw) ?: [] as $linea) {
                $l = trim($linea);
                if ($l !== '') {
                    $radPuntos[] = $l;
                }
            }
        }
        if (empty($radPuntos)) {
            $radPuntos = $def['seguridad_radiologica_puntos'];
        }

        return [
            'tipo' => self::TIPO_ANEXO,
            'titulo' => $titulo !== '' ? $titulo : $def['titulo'],
            'descripcion_servicio' => $desc !== '' ? $desc : $def['descripcion_servicio'],
            'condiciones' => $condiciones,
            'ensayos_intro' => $ensIntro !== '' ? $ensIntro : $def['ensayos_intro'],
            'ensayos' => $ensayos,
            'recursos_entrega' => $recEnt !== '' ? $recEnt : $def['recursos_entrega'],
            'responsabilidades_cliente' => $respCli !== '' ? $respCli : $def['responsabilidades_cliente'],
            'incluir_seguridad_radiologica' => $incRad,
            'seguridad_radiologica_intro' => $radIntro !== '' ? $radIntro : $def['seguridad_radiologica_intro'],
            'seguridad_radiologica_puntos' => $radPuntos,
            'seguridad_radiologica_cierre' => $radCierre !== '' ? $radCierre : $def['seguridad_radiologica_cierre'],
        ];
    }

    /**
     * @param array<string, mixed> $anexo
     */
    public static function codificar(array $anexo): string
    {
        $anexo['tipo'] = self::TIPO_ANEXO;
        return (string)json_encode($anexo, JSON_UNESCAPED_UNICODE);
    }

    public static function renderEstilos(bool $esPdf = false): string
    {
        $fontSize = $esPdf ? '8.4px' : '12.5px';
        $titleSize = $esPdf ? '10.5px' : '15px';
        $secSize = $esPdf ? '9px' : '13px';
        $padCell = $esPdf ? '4px 6px' : '7px 11px';

        return "
        .anx-mensual-doc {
            font-family: 'DejaVu Sans', 'Inter', Arial, sans-serif;
            font-size: {$fontSize};
            color: #111827;
            line-height: 1.42;
            width: 100%;
        }
        .anx-mensual-banner {
            background: #1F3864;
            color: #ffffff;
            font-weight: 700;
            font-size: {$titleSize};
            padding: " . ($esPdf ? '6px 9px' : '10px 14px') . ";
            letter-spacing: 0.4px;
            text-transform: uppercase;
            border: 1px solid #1F3864;
        }
        .anx-mensual-sec {
            background: #e8eef7;
            color: #1F3864;
            font-weight: 700;
            font-size: {$secSize};
            padding: " . ($esPdf ? '4.5px 8px' : '7px 12px') . ";
            border: 1px solid #cbd5e1;
            border-top: none;
        }
        .anx-mensual-body {
            border: 1px solid #cbd5e1;
            border-top: none;
            padding: " . ($esPdf ? '5px 8px' : '9px 12px') . ";
            background: #ffffff;
        }
        .anx-mensual-body p {
            margin: 0 0 5px 0;
        }
        .anx-mensual-body p:last-child {
            margin-bottom: 0;
        }
        .anx-mensual-table {
            width: 100%;
            border-collapse: collapse;
        }
        .anx-mensual-table th,
        .anx-mensual-table td {
            border: 1px solid #cbd5e1;
            padding: {$padCell};
            vertical-align: top;
            text-align: left;
        }
        .anx-mensual-table thead th {
            background: #f1f5f9;
            color: #1F3864;
            font-weight: 700;
        }
        .anx-mensual-k {
            width: 28%;
            font-weight: 700;
            background: #f8fafc;
            color: #1e293b;
        }
        .anx-mensual-num {
            width: 7%;
            text-align: center !important;
            font-weight: 600;
            color: #334155;
        }
        .anx-mensual-cat {
            width: 23%;
            font-weight: 600;
            color: #1F3864;
        }
        .anx-mensual-rad-list {
            margin: 4px 0 6px 0;
            padding-left: 14px;
            list-style: none;
        }
        .anx-mensual-rad-list li {
            margin-bottom: 3px;
        }
        ";
    }

    /**
     * Renderiza el Anexo Técnico – Alcance Mensual con la estructura exacta del documento oficial.
     *
     * @param array<string, mixed> $anexo
     */
    public static function renderHtml(array $anexo, bool $esPdf = false): string
    {
        $esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $titulo = trim((string)($anexo['titulo'] ?? 'CYCSA  |  ANEXO TÉCNICO – ALCANCE MENSUAL'));
        $desc = trim((string)($anexo['descripcion_servicio'] ?? ''));
        $condiciones = is_array($anexo['condiciones'] ?? null) ? $anexo['condiciones'] : [];
        $ensIntro = trim((string)($anexo['ensayos_intro'] ?? ''));
        $ensayosAll = is_array($anexo['ensayos'] ?? null) ? $anexo['ensayos'] : [];
        $ensayosIncluidos = array_values(array_filter($ensayosAll, static fn($e): bool => is_array($e) && (!array_key_exists('incluido', $e) || !empty($e['incluido']))));
        $recEnt = trim((string)($anexo['recursos_entrega'] ?? ''));
        $respCli = trim((string)($anexo['responsabilidades_cliente'] ?? ''));
        $incRad = !array_key_exists('incluir_seguridad_radiologica', $anexo) || !empty($anexo['incluir_seguridad_radiologica']);
        $radIntro = trim((string)($anexo['seguridad_radiologica_intro'] ?? ''));
        $radPuntos = is_array($anexo['seguridad_radiologica_puntos'] ?? null) ? $anexo['seguridad_radiologica_puntos'] : [];
        $radCierre = trim((string)($anexo['seguridad_radiologica_cierre'] ?? ''));

        $html = '<div class="anx-mensual-doc">';
        $html .= '<div class="anx-mensual-banner">' . $esc($titulo) . '</div>';

        // 1. Descripción del servicio
        $html .= '<div class="anx-mensual-sec">1. Descripción del servicio.</div>';
        $html .= '<div class="anx-mensual-body"><p>' . nl2br($esc($desc)) . '</p></div>';

        // 2. Condiciones del servicio
        $html .= '<div class="anx-mensual-sec">2. Condiciones del servicio.</div>';
        $html .= '<table class="anx-mensual-table" style="border-top: none;">';
        foreach ($condiciones as $c) {
            if (!is_array($c)) {
                continue;
            }
            $lbl = trim((string)($c['label'] ?? ''));
            $val = trim((string)($c['valor'] ?? ''));
            if ($lbl === '' && $val === '') {
                continue;
            }
            $html .= '<tr>';
            $html .= '<td class="anx-mensual-k">' . $esc($lbl) . '</td>';
            $html .= '<td>' . nl2br($esc($val)) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // 3. Ensayos incluidos en el servicio
        $html .= '<div class="anx-mensual-sec">3. Ensayos incluidos en el servicio.</div>';
        if ($ensIntro !== '') {
            $html .= '<div class="anx-mensual-body"><p>' . nl2br($esc($ensIntro)) . '</p></div>';
        }
        $html .= '<table class="anx-mensual-table">';
        $html .= '<thead><tr><th class="anx-mensual-num">N.º</th><th class="anx-mensual-cat">Categoría</th><th>Ensayo incluido.</th></tr></thead>';
        $html .= '<tbody>';
        $n = 1;
        foreach ($ensayosIncluidos as $ens) {
            $cat = trim((string)($ens['categoria'] ?? ''));
            $nom = trim((string)($ens['ensayo'] ?? ''));
            if ($cat === '' && $nom === '') {
                continue;
            }
            $html .= '<tr>';
            $html .= '<td class="anx-mensual-num">' . $n++ . '</td>';
            $html .= '<td class="anx-mensual-cat">' . $esc($cat) . '</td>';
            $html .= '<td>' . $esc($nom) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // 4. Recursos asignados y entrega de informes de ensayos
        $html .= '<div class="anx-mensual-sec">4. Recursos asignados y entrega de informes de ensayos.</div>';
        $html .= '<div class="anx-mensual-body">';
        $parrafosRec = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $recEnt) ?: []));
        foreach ($parrafosRec as $pRec) {
            $html .= '<p>' . $esc($pRec) . '</p>';
        }
        $html .= '</div>';

        // 5. Recursos y responsabilidades del cliente
        $html .= '<div class="anx-mensual-sec">5. Recursos y responsabilidades del cliente.</div>';
        $html .= '<div class="anx-mensual-body"><p>' . nl2br($esc($respCli)) . '</p></div>';

        // 6. Condiciones de seguridad radiológica
        if ($incRad) {
            $html .= '<div class="anx-mensual-sec">6. Condiciones de seguridad radiológica.</div>';
            $html .= '<div class="anx-mensual-body">';
            if ($radIntro !== '') {
                $html .= '<p>' . nl2br($esc($radIntro)) . '</p>';
            }
            if (!empty($radPuntos)) {
                $html .= '<ul class="anx-mensual-rad-list">';
                foreach ($radPuntos as $pt) {
                    $html .= '<li>' . $esc((string)$pt) . '</li>';
                }
                $html .= '</ul>';
            }
            if ($radCierre !== '') {
                $html .= '<p><strong>' . nl2br($esc($radCierre)) . '</strong></p>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el anexo técnico de una cotización (soporta Anexo Mensual estructurado o texto/HTML legado).
     *
     * @param array<string, mixed> $cotizacion
     */
    public static function renderDesdeCotizacion(array $cotizacion, bool $esPdf = false): string
    {
        $raw = trim((string)($cotizacion['anexo_tecnico'] ?? ''));
        $esMensual = !empty($cotizacion['es_mensual']) && (int)$cotizacion['es_mensual'] === 1;
        $incluir = !empty($cotizacion['incluir_anexo_tecnico']);

        if (self::esAnexoMensualJson($raw)) {
            if (!$incluir) {
                return '';
            }
            $anexo = self::decodificar($raw);
            return '<style>' . self::renderEstilos($esPdf) . '</style>' . self::renderHtml($anexo, $esPdf);
        }

        // Si es servicio mensual y se solicitó incluir anexo pero aún no tiene texto guardado, renderizar plantilla oficial
        if ($esMensual && $incluir && $raw === '') {
            return '<style>' . self::renderEstilos($esPdf) . '</style>' . self::renderHtml(self::plantillaPorDefecto(), $esPdf);
        }

        // Retrocompatibilidad: cotizaciones anteriores con anexo HTML/texto plano
        if ($incluir && $raw !== '') {
            return $raw;
        }

        return '';
    }
}
