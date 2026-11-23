<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GuiaDisenoSobrioOperacionesTest extends TestCase {

    private string $rootDir;
    private string $cssPath;
    private string $mockupPath;
    private string $indexViewPath;
    private string $verViewPath;

    protected function setUp(): void {
        parent::setUp();
        $this->rootDir = dirname(__DIR__, 2);
        $this->cssPath = $this->rootDir . '/publico/css/cycsa-sobrio.css';
        $this->mockupPath = $this->rootDir . '/publico/operaciones_redisenio.html';
        $this->indexViewPath = $this->rootDir . '/app/Modulos/Operaciones/Vistas/index.php';
        $this->verViewPath = $this->rootDir . '/app/Modulos/Operaciones/Vistas/ver.php';

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Supervisor QA Sobrio';
        $_SESSION['csrf_token'] = 'csrf_sobrio_test_2026';
        $_GET = [];
    }

    protected function tearDown(): void {
        $_GET = [];
        parent::tearDown();
    }

    public function testAssetsGuiaDisenoSobrioExistenYContienenTokens(): void {
        $this->assertFileExists($this->cssPath);
        $this->assertFileExists($this->mockupPath);

        $css = file_get_contents($this->cssPath);
        $mockup = file_get_contents($this->mockupPath);

        $this->assertNotFalse($css);
        $this->assertNotFalse($mockup);

        // Normalizar espacios para validar --cy-brand:#1F3864
        $cssCompacto = preg_replace('/\s+/', '', $css);
        $this->assertStringContainsString('--cy-brand:#1F3864', (string)$cssCompacto);

        $selectoresRequeridos = [
            '.cy-topbar',
            '.cy-stepper',
            '.cy-pill',
            '.cy-seg',
            '.cy-bar',
            '.cy-next',
            '.cy-row',
            '.cy-note',
        ];
        foreach ($selectoresRequeridos as $selector) {
            $this->assertStringContainsString($selector, $css, "cycsa-sobrio.css debe definir {$selector}");
            $this->assertStringContainsString(ltrim($selector, '.'), $mockup, "operaciones_redisenio.html debe usar {$selector}");
        }
    }

    public function testVistasOperacionesUsanComponentesSobriosYSinBotonesDeColores(): void {
        $this->assertFileExists($this->indexViewPath);
        $this->assertFileExists($this->verViewPath);

        $indexHtml = (string)file_get_contents($this->indexViewPath);
        $verHtml = (string)file_get_contents($this->verViewPath);

        // Clases sobrias compartidas en ambas vistas
        $clasesCompartidas = [
            'cy-stepper',
            'cy-next',
            'cy-row',
            'cy-pill',
            'cy-note',
            'btn-more',
            'dropdown-item text-danger',
            'Devolver',
            'cy-order-code',
            'cy-panel-meta-line',
            'cy-assay-name',
            'cy-assay-norm',
        ];

        foreach ($clasesCompartidas as $token) {
            $this->assertStringContainsString($token, $indexHtml, "index.php debe contener '{$token}'");
            $this->assertStringContainsString($token, $verHtml, "ver.php debe contener '{$token}'");
        }

        // Clases específicas de lista en index.php
        $this->assertStringContainsString('cy-seg', $indexHtml);
        $this->assertStringContainsString('cy-bar', $indexHtml);
        $this->assertStringContainsString('cy-page-title', $indexHtml);
        $this->assertStringContainsString('offcanvas offcanvas-end', $indexHtml);
        $this->assertStringContainsString('is-selected', $indexHtml);

        // Reglas estrictas de sobriedad en ambas vistas:
        // - Sin botones de colores (.btn-success, .btn-warning, .btn-danger, .btn-info)
        // - Sin monospace
        // - Sin font-size de 8px-12px en estilos inline
        foreach (['index.php' => $indexHtml, 'ver.php' => $verHtml] as $nombre => $contenido) {
            $this->assertDoesNotMatchRegularExpression(
                '/\bbtn-(?:success|warning|danger|info|outline-success|outline-warning|outline-danger|outline-info)\b/',
                $contenido,
                "{$nombre} no debe contener botones con clases de color."
            );
            $this->assertStringNotContainsStringIgnoringCase('monospace', $contenido, "{$nombre} no debe usar monospace.");
            $this->assertDoesNotMatchRegularExpression(
                '/font-size\s*:\s*(?:[89]|1[0-2])(?:\.\d+)?px/i',
                $contenido,
                "{$nombre} no debe usar tamaños de fuente entre 8px y 12px."
            );
        }
    }

    public function testStepperCincoPasosReglaDeterministica(): void {
        if (!function_exists('cycsaCalcularStepperOperacion')) {
            $ordenes = [];
            $bitacora_logs = [];
            ob_start();
            include $this->indexViewPath;
            ob_end_clean();
        }

        $this->assertTrue(function_exists('cycsaCalcularStepperOperacion'));

        // Caso 1: 1 de 3 matrices aprobadas, 1 en ejecución (borrador), 1 pendiente
        $ordenParcial = [
            'id' => 101,
            'codigo_os' => 'OS-2026-0101',
            'hoja_solicitud' => ['id' => 10, 'etapa_flujo' => 'en_ejecucion', 'recepcion_completa' => 1],
            'recepcion_completa_os' => 1,
            'etapa_flujo_os' => 'en_ejecucion',
            'items' => [
                [
                    'id' => 1,
                    'descripcion_ensayo' => 'Proctor modificado',
                    'norma_astm' => 'ASTM D1557',
                    'cantidad' => 1,
                    'resultados_json' => json_encode(['filas' => [['Muestra' => 'M1', 'P.V.S Max' => '2100']], 'revision' => ['estado' => 'aprobada']]),
                ],
                [
                    'id' => 2,
                    'descripcion_ensayo' => 'Resistencia a compresión',
                    'norma_astm' => 'ASTM C39',
                    'cantidad' => 8,
                    'resultados_json' => json_encode(['filas' => [['Código laboratorio' => 'MC-1', 'Carga (lb)' => '45000']], 'revision' => ['estado' => 'borrador']]),
                ],
                [
                    'id' => 3,
                    'descripcion_ensayo' => 'Granulometría',
                    'norma_astm' => 'ASTM D6913',
                    'cantidad' => 2,
                    'resultados_json' => '[]',
                ],
            ],
        ];

        $pasosParcial = cycsaCalcularStepperOperacion($ordenParcial);
        $this->assertCount(5, $pasosParcial);
        $this->assertSame('is-done', $pasosParcial[0]['clase']);
        $this->assertSame('is-done', $pasosParcial[1]['clase']);
        $this->assertSame('is-current', $pasosParcial[2]['clase']);
        $this->assertSame('', $pasosParcial[3]['clase']);
        $this->assertSame('', $pasosParcial[4]['clase'], 'El paso Informe jamás puede estar en is-done ni is-current cuando hay matrices sin aprobar.');

        // Caso 2: 1 de 3 aprobadas y 2 en revisión (aunque alguien force etapa_flujo_os = 'informe', Informe sigue bloqueado)
        $ordenEnRevision = $ordenParcial;
        $ordenEnRevision['etapa_flujo_os'] = 'informe';
        $ordenEnRevision['items'][1]['resultados_json'] = json_encode(['filas' => [['Carga (lb)' => '45000']], 'revision' => ['estado' => 'en_revision']]);
        $ordenEnRevision['items'][2]['resultados_json'] = json_encode(['filas' => [['P. Retenido' => '120']], 'revision' => ['estado' => 'en_revision']]);

        $pasosRevision = cycsaCalcularStepperOperacion($ordenEnRevision);
        $this->assertSame('is-done', $pasosRevision[0]['clase']);
        $this->assertSame('is-done', $pasosRevision[1]['clase']);
        $this->assertSame('is-done', $pasosRevision[2]['clase']);
        $this->assertSame('is-current', $pasosRevision[3]['clase']);
        $this->assertSame('', $pasosRevision[4]['clase'], 'El paso Informe jamás debe marcarse is-done si solo 1 de 3 matrices está aprobada.');

        // Caso 3: 3 de 3 matrices aprobadas pero informe aún no emitido
        $ordenPorEmitir = $ordenEnRevision;
        $ordenPorEmitir['informe_emitido'] = false;
        $ordenPorEmitir['items'][1]['resultados_json'] = json_encode(['filas' => [['Carga (lb)' => '45000']], 'revision' => ['estado' => 'aprobada']]);
        $ordenPorEmitir['items'][2]['resultados_json'] = json_encode(['filas' => [['P. Retenido' => '120']], 'revision' => ['estado' => 'aprobada']]);

        $pasosPorEmitir = cycsaCalcularStepperOperacion($ordenPorEmitir);
        $this->assertSame('is-done', $pasosPorEmitir[0]['clase']);
        $this->assertSame('is-done', $pasosPorEmitir[1]['clase']);
        $this->assertSame('is-done', $pasosPorEmitir[2]['clase']);
        $this->assertSame('is-done', $pasosPorEmitir[3]['clase']);
        $this->assertSame('is-current', $pasosPorEmitir[4]['clase']);

        // Caso 4: 3 de 3 matrices aprobadas e informe emitido -> los 5 pasos tienen is-done
        $ordenCompleta = $ordenPorEmitir;
        $ordenCompleta['informe_emitido'] = true;
        $ordenCompleta['etapa_flujo_os'] = 'informe';

        $pasosCompletos = cycsaCalcularStepperOperacion($ordenCompleta);
        $this->assertCount(5, $pasosCompletos);
        foreach ($pasosCompletos as $idx => $paso) {
            $this->assertSame('is-done', $paso['clase'], "El paso " . ($idx + 1) . " debe tener is-done cuando las 3 matrices están aprobadas y el informe emitido.");
        }
    }

    public function testRenderizadoVistasIndexYVerConDatosReales(): void {
        $itemsParcial = [
            [
                'id' => 201,
                'descripcion_ensayo' => 'Resistencia a compresión de cilindros',
                'norma_astm' => 'ASTM C39 / C31',
                'formato_nombre' => 'RT-FM-22 A',
                'cantidad' => 3,
                'permitido' => 1,
                'resultados_json' => json_encode(['filas' => [['Carga (lb)' => '90000']], 'revision' => ['estado' => 'aprobada']]),
            ],
            [
                'id' => 202,
                'descripcion_ensayo' => 'Proctor modificado',
                'norma_astm' => 'ASTM D1557',
                'formato_nombre' => 'RT-FM-22 C',
                'cantidad' => 1,
                'permitido' => 1,
                'resultados_json' => json_encode(['filas' => [['P.V.S Max' => '2050']], 'revision' => ['estado' => 'en_revision']]),
            ],
            [
                'id' => 203,
                'descripcion_ensayo' => 'Granulometría de suelos',
                'norma_astm' => 'ASTM D6913',
                'formato_nombre' => 'RT-FM-22 G',
                'cantidad' => 2,
                'permitido' => 1,
                'resultados_json' => '[]',
            ],
        ];

        $ordenBase = [
            'id' => 55,
            'codigo_os' => 'OS-2026-0055',
            'cot_codigo' => 'COT-2026-0055',
            'cliente_nombre' => 'Constructora del Pacífico S.A.',
            'cliente_informe_nombre' => 'Constructora del Pacífico S.A.',
            'cliente_factura_nombre' => 'Constructora del Pacífico S.A.',
            'cliente_email' => 'qa@pacifico.test',
            'nombre_proyecto' => 'Puente Río Grande',
            'condicion_comercial_activa' => 'contado',
            'estado' => 'Estado 6: Ejecucion Ensayos',
            'fecha_emision' => '2026-11-22',
            'requiere_muestreo' => 0,
            'muestras_aceptadas_lab' => 1,
            'hoja_solicitud' => ['id' => 77, 'numero_registro' => 'REG-055', 'etapa_flujo' => 'en_ejecucion', 'recepcion_completa' => 1],
            'hojas_solicitud' => [['id' => 77, 'numero_registro' => 'REG-055', 'etapa_flujo' => 'en_ejecucion', 'recepcion_completa' => 1, 'descripcion_ensayo' => 'Cilindros y suelos']],
            'recepcion_completa_os' => 1,
            'etapa_flujo_os' => 'en_ejecucion',
            'total_ensayos' => 3,
            'ensayos_aprobados' => 1,
            'ensayos_en_revision' => 1,
            'ensayos_devueltos' => 0,
            'ensayos_con_resultados' => 2,
            'tecnico_100' => false,
            'comercial_100' => false,
            'saldo_os' => 1500.00,
            'puede_cerrar' => false,
            'siguiente_paso_texto' => 'Capturar Lote en Lab',
            'siguiente_paso_url' => '/Cycsa/publico/laboratorio/ensayos-hoja?id_hoja=77',
            'items' => $itemsParcial,
        ];

        // 1. Renderizar index.php
        $ordenes = [$ordenBase];
        $conteoBandejas = ['todas' => 1, 'recepcion' => 0, 'tecnico' => 1, 'supervisor' => 0, 'informes' => 0];
        $conteosTabs = ['activas' => 1, 'muestreo' => 0, 'ensayos' => 1, 'historico' => 0];
        $tecnicos = [];
        $vehiculos = [];
        $bitacora_logs = [];

        ob_start();
        include $this->indexViewPath;
        $htmlIndex = (string)ob_get_clean();

        $this->assertStringContainsString('<h1 class="cy-page-title">Operaciones</h1>', $htmlIndex);
        $this->assertStringContainsString('OS-2026-0055', $htmlIndex);
        $this->assertStringContainsString('2 de 3 matrices', $htmlIndex);
        $this->assertStringContainsString('Ver documento listo (1/3)', $htmlIndex);
        $this->assertStringContainsString('Documentos listos para ver', $htmlIndex);
        $this->assertStringContainsString('Ver Matrices (2/3)', $htmlIndex);
        $this->assertStringContainsString('class="cy-step "', $htmlIndex);
        $this->assertStringContainsString('dropdown-item text-danger', $htmlIndex);
        $this->assertDoesNotMatchRegularExpression('/\bbtn-(?:success|warning|danger|info)\b/', $htmlIndex);

        // 2. Renderizar ver.php
        $o = $ordenBase;
        $informes_unificados = [];
        $consolidado_lab = ['total_muestras' => 0, 'version_consolidado' => 1];
        $historial_versiones_lab = [];

        ob_start();
        include $this->verViewPath;
        $htmlVer = (string)ob_get_clean();

        $this->assertStringContainsString('class="cy-order-code">OS-2026-0055</h1>', $htmlVer);
        $this->assertStringContainsString('Constructora del Pacífico S.A. · Puente Río Grande · COT-2026-0055 · Contado', $htmlVer);
        $this->assertStringContainsString('class="cy-stepper"', $htmlVer);
        $this->assertStringContainsString('class="cy-next"', $htmlVer);
        $this->assertStringContainsString('class="cy-row"', $htmlVer);
        $this->assertStringContainsString('class="cy-note" id="tab-full-cierre"', $htmlVer);
        $this->assertStringContainsString('dropdown-item text-danger', $htmlVer);
        $this->assertDoesNotMatchRegularExpression('/\bbtn-(?:success|warning|danger|info)\b/', $htmlVer);
        $this->assertStringNotContainsString('acciones_ensayo.php', $htmlVer, 'ver.php no debe imprimir la cadena acciones_ensayo.php en el HTML renderizado.');
    }
}
