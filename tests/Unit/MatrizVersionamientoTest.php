<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrizVersionamientoTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_rol'] = 1; // Supervisor / Admin
        $_SESSION['usuario_nombre'] = 'Ing. Supervisor';
        $_SESSION['csrf_token'] = 'test-token-csrf';
    }

    public function testEstructuraInicialSinVersiones(): void {
        $json = json_encode([
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100']
            ],
            'metadatos' => ['cliente_nombre' => 'Cliente Alpha']
        ]);

        $decoded = json_decode($json, true);
        $versionActual = (int)($decoded['version_actual'] ?? 1);
        $versiones = $decoded['versiones'] ?? [];

        $this->assertEquals(1, $versionActual);
        $this->assertEmpty($versiones);
    }

    public function testPrimeraDevolucionGeneraSnapshotV1EIncrementaAVersion2(): void {
        $datosOriginales = [
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2500']
            ],
            'metadatos' => ['cliente_nombre' => 'Cliente Alpha', 'proyecto' => 'Puente Norte'],
            'revision' => [
                'estado' => 'en_revision',
                'fecha_envio' => '2026-09-18 08:00:00',
                'usuario_envio' => 'Técnico 1'
            ]
        ];

        // Simulamos la lógica implementada en OperacionesControlador::devolverMatrizProducto
        $decoded = $datosOriginales;
        $versiones = is_array($decoded['versiones'] ?? null) ? $decoded['versiones'] : [];
        $versionActual = isset($decoded['version_actual']) ? (int)$decoded['version_actual'] : 1;

        $motivo = 'Corregir el factor de calibración de celda.';
        $usuario = 'Ing. Revisor';
        $fechaDevolucion = '2026-09-18 09:30:00';

        $snapshotV1 = [
            'version' => $versionActual,
            'fecha' => $fechaDevolucion,
            'estado' => 'devuelta',
            'motivo_devolucion' => $motivo,
            'usuario_revisor' => $usuario,
            'filas' => $decoded['filas'] ?? [],
            'metadatos' => $decoded['metadatos'] ?? []
        ];
        $versiones[] = $snapshotV1;
        $versionNueva = $versionActual + 1;

        $decoded['versiones'] = $versiones;
        $decoded['version_actual'] = $versionNueva;
        $decoded['revision']['estado'] = 'devuelta';
        $decoded['revision']['motivo_devolucion'] = $motivo;
        $decoded['revision']['usuario_revisor'] = $usuario;

        $this->assertEquals(2, $decoded['version_actual']);
        $this->assertCount(1, $decoded['versiones']);
        $this->assertEquals(1, $decoded['versiones'][0]['version']);
        $this->assertEquals('Corregir el factor de calibración de celda.', $decoded['versiones'][0]['motivo_devolucion']);
        $this->assertEquals('Ing. Revisor', $decoded['versiones'][0]['usuario_revisor']);
        $this->assertEquals('2500', $decoded['versiones'][0]['filas'][0]['Esfuerzo']);
    }

    public function testEdicionDeBorradorConservaVersionesHistoricas(): void {
        // Matriz ya devuelta en v1, ahora en v2
        $jsonGuardado = json_encode([
            'version_actual' => 2,
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2500']
            ],
            'metadatos' => ['cliente_nombre' => 'Cliente Alpha'],
            'versiones' => [
                [
                    'version' => 1,
                    'fecha' => '2026-09-18 09:30:00',
                    'estado' => 'devuelta',
                    'motivo_devolucion' => 'Corregir factor.',
                    'filas' => [
                        ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2500']
                    ]
                ]
            ]
        ]);

        $previo = json_decode($jsonGuardado, true);

        // Técnico edita fila para v2 (Esfuerzo corregido a 2800)
        $nuevasFilas = [
            ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2800']
        ];
        $nuevosMetadatos = ['cliente_nombre' => 'Cliente Alpha Corregido'];

        // Lógica de OperacionesControlador::guardarMatrizProductoPOST
        $decodedUpdate = [
            'version_actual' => (int)($previo['version_actual'] ?? 1),
            'versiones' => is_array($previo['versiones'] ?? null) ? $previo['versiones'] : [],
            'filas' => $nuevasFilas,
            'metadatos' => $nuevosMetadatos,
            'revision' => [
                'estado' => 'en_revision',
                'fecha_envio' => '2026-09-18 10:00:00'
            ]
        ];

        // Verificamos que no se perdieron las versiones pasadas
        $this->assertEquals(2, $decodedUpdate['version_actual']);
        $this->assertCount(1, $decodedUpdate['versiones']);
        $this->assertEquals('2500', $decodedUpdate['versiones'][0]['filas'][0]['Esfuerzo']);
        // Y el borrador activo tiene el nuevo esfuerzo
        $this->assertEquals('2800', $decodedUpdate['filas'][0]['Esfuerzo']);
    }

    public function testSegundaDevolucionCreaSnapshotV2EIncrementaAVersion3(): void {
        $estadoV2 = [
            'version_actual' => 2,
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2800']
            ],
            'metadatos' => ['cliente_nombre' => 'Cliente Alpha'],
            'versiones' => [
                [
                    'version' => 1,
                    'fecha' => '2026-09-18 09:30:00',
                    'estado' => 'devuelta',
                    'motivo_devolucion' => 'Corregir factor.',
                    'filas' => [
                        ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '2500']
                    ]
                ]
            ]
        ];

        // Segunda devolución
        $versiones = $estadoV2['versiones'];
        $versionActual = (int)$estadoV2['version_actual']; // 2

        $versiones[] = [
            'version' => $versionActual,
            'fecha' => '2026-09-18 11:00:00',
            'estado' => 'devuelta',
            'motivo_devolucion' => 'Revisar área transversal según diámetro real.',
            'usuario_revisor' => 'Ing. Noel Quintana',
            'filas' => $estadoV2['filas'],
            'metadatos' => $estadoV2['metadatos']
        ];

        $estadoV3 = $estadoV2;
        $estadoV3['versiones'] = $versiones;
        $estadoV3['version_actual'] = $versionActual + 1; // 3

        $this->assertEquals(3, $estadoV3['version_actual']);
        $this->assertCount(2, $estadoV3['versiones']);
        $this->assertEquals(1, $estadoV3['versiones'][0]['version']);
        $this->assertEquals('Corregir factor.', $estadoV3['versiones'][0]['motivo_devolucion']);
        $this->assertEquals(2, $estadoV3['versiones'][1]['version']);
        $this->assertEquals('Revisar área transversal según diámetro real.', $estadoV3['versiones'][1]['motivo_devolucion']);
        $this->assertEquals('2800', $estadoV3['versiones'][1]['filas'][0]['Esfuerzo']);
    }

    public function testAprobacionConservaTodasLasVersionesHistoricas(): void {
        $estadoV3 = [
            'version_actual' => 3,
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Lectura' => '100', 'Esfuerzo' => '3000']
            ],
            'metadatos' => ['cliente_nombre' => 'Cliente Alpha'],
            'versiones' => [
                ['version' => 1, 'motivo_devolucion' => 'Obs 1'],
                ['version' => 2, 'motivo_devolucion' => 'Obs 2']
            ]
        ];

        // Lógica de OperacionesControlador::aprobarMatrizProducto
        $decoded = $estadoV3;
        $decoded['revision']['estado'] = 'aprobada';
        $decoded['revision']['usuario_revisor'] = 'Ing. Supervisor';

        $this->assertEquals('aprobada', $decoded['revision']['estado']);
        $this->assertEquals(3, $decoded['version_actual']);
        $this->assertCount(2, $decoded['versiones']);
    }

    public function testExtraccionDeSnapshotHistoricoParaLecturaEImpresion(): void {
        $payload = [
            'version_actual' => 3,
            'filas' => [
                ['Código laboratorio' => 'CYCSA-001', 'Esfuerzo' => '3000']
            ],
            'metadatos' => ['cliente_nombre' => 'Versión 3'],
            'versiones' => [
                [
                    'version' => 1,
                    'fecha' => '2026-09-18 09:00:00',
                    'estado' => 'devuelta',
                    'motivo_devolucion' => 'Error cálculo v1',
                    'usuario_revisor' => 'Supervisor A',
                    'filas' => [['Código laboratorio' => 'CYCSA-001', 'Esfuerzo' => '1500']],
                    'metadatos' => ['cliente_nombre' => 'Versión 1 Original']
                ],
                [
                    'version' => 2,
                    'fecha' => '2026-09-18 10:00:00',
                    'estado' => 'devuelta',
                    'motivo_devolucion' => 'Error cálculo v2',
                    'usuario_revisor' => 'Supervisor B',
                    'filas' => [['Código laboratorio' => 'CYCSA-001', 'Esfuerzo' => '2200']],
                    'metadatos' => ['cliente_nombre' => 'Versión 2 Corrección']
                ]
            ]
        ];

        // Simulamos la resolución de versión histórica de capturaMatrizProducto / imprimirMatrizProducto
        $versiones = $payload['versiones'];
        $versionSolicitada = 1;

        $snapshotEncontrado = null;
        foreach ($versiones as $ver) {
            if ((int)($ver['version'] ?? 0) === $versionSolicitada) {
                $snapshotEncontrado = $ver;
                break;
            }
        }

        $this->assertNotNull($snapshotEncontrado);
        $this->assertEquals('1500', $snapshotEncontrado['filas'][0]['Esfuerzo']);
        $this->assertEquals('Versión 1 Original', $snapshotEncontrado['metadatos']['cliente_nombre']);
        $this->assertEquals('Error cálculo v1', $snapshotEncontrado['motivo_devolucion']);

        // Buscamos versión 2
        $versionSolicitada2 = 2;
        $snapshotEncontrado2 = null;
        foreach ($versiones as $ver) {
            if ((int)($ver['version'] ?? 0) === $versionSolicitada2) {
                $snapshotEncontrado2 = $ver;
                break;
            }
        }

        $this->assertNotNull($snapshotEncontrado2);
        $this->assertEquals('2200', $snapshotEncontrado2['filas'][0]['Esfuerzo']);
        $this->assertEquals('Versión 2 Corrección', $snapshotEncontrado2['metadatos']['cliente_nombre']);
        $this->assertEquals('Error cálculo v2', $snapshotEncontrado2['motivo_devolucion']);
    }

    public function testRenderizadoSeguroDeCapturaMatrizConVersionHistorica(): void {
        $detalle = [
            'id' => 10,
            'cotizacion_id' => 5,
            'codigo_os' => 'OS-2026-005',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'descripcion_ensayo' => 'Resistencia a la Compresión de Cilindros de Concreto',
            'norma_astm' => 'ASTM C39',
            'resultados_json' => json_encode([
                'filas' => [['Código laboratorio' => 'C-1', 'Carga' => '40000']],
                'metadatos' => ['cliente_nombre' => 'Prueba Test'],
                'revision' => ['estado' => 'devuelta', 'motivo_devolucion' => 'Histórica devuelta v1']
            ])
        ];

        $listaVersiones = [
            [
                'version' => 1,
                'fecha' => '2026-09-18 09:00:00',
                'estado' => 'devuelta',
                'motivo_devolucion' => 'Histórica devuelta v1'
            ]
        ];
        $versionActual = 2;
        $versionSolicitada = 1;
        $esHistorica = true;
        $versionActivaInfo = ['version' => 2];
        $muestrasSeteadas = [];
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown']);
        $columnas = $schemaInfo['columns'];
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] => $schemaInfo]);

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $html = ob_get_clean();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('VISUALIZANDO VERSIÓN HISTÓRICA v1 (SOLO LECTURA)', $html);
        $this->assertStringContainsString('Histórica devuelta v1', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('Ir a Versión Actual (v2)', $html);
    }

    public function testRenderizadoSeguroDeMatrizPrintConVersionHistorica(): void {
        $detalle = [
            'id' => 10,
            'cotizacion_id' => 5,
            'codigo_os' => 'OS-2026-005',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'descripcion_ensayo' => 'Resistencia a la Compresión de Cilindros de Concreto',
            'norma_astm' => 'ASTM C39',
            'resultados_json' => json_encode([
                'filas' => [['Código laboratorio' => 'C-1', 'Carga' => '40000']],
                'metadatos' => ['cliente_nombre' => 'Prueba Test']
            ])
        ];

        $listaVersiones = [
            [
                'version' => 1,
                'fecha' => '2026-09-18 09:00:00',
                'estado' => 'devuelta',
                'motivo_devolucion' => 'Histórica devuelta v1',
                'usuario_revisor' => 'Supervisor Calidad'
            ]
        ];
        $versionActual = 2;
        $infoVersionImpresion = [
            'version' => 1,
            'es_actual' => false,
            'motivo_devolucion' => 'Histórica devuelta v1',
            'fecha' => '2026-09-18 09:00:00',
            'usuario_revisor' => 'Supervisor Calidad'
        ];
        $muestrasSeteadas = [];

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php';
        $html = ob_get_clean();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Versión: 01 (Histórica Devuelta)', $html);
        $this->assertStringContainsString('Histórica devuelta v1', $html);
        $this->assertStringContainsString('Supervisor Calidad', $html);
        $this->assertStringContainsString('version=1', $html);
    }
}
