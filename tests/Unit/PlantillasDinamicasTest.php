<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\EvaluadorFormula;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo;
use PDO;
use Throwable;

class PlantillasDinamicasTest extends TestCase {

    private PDO $db;
    private PlantillaModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $this->db = Conexion::obtenerInstancia();
        $this->modelo = new PlantillaModelo();
    }

    public function testEvaluadorFormulaSintaxisValida(): void {
        $formulasValidas = [
            '{masa_seca} / {volumen}',
            '({peso_humedo} - {peso_seco}) / {peso_seco} * 100',
            'sqrt({area})',
            'round({esfuerzo}, 2)',
            'abs({diferencia})',
            'pow({diametro}, 2) * 3.14159',
            'min({lectura_1}, {lectura_2})',
            'max({lectura_1}, {lectura_2})',
            'log({constante})'
        ];

        foreach ($formulasValidas as $formula) {
            $resultado = EvaluadorFormula::validarSintaxis($formula);
            $this->assertIsArray($resultado);
            $this->assertTrue(
                $resultado['valido'],
                "La fórmula '{$formula}' debería ser sintácticamente válida. Mensaje: " . ($resultado['mensaje'] ?? '')
            );
        }
    }

    public function testEvaluadorFormulaSintaxisInvalidaYProteccionSeguridad(): void {
        $formulasInvalidas = [
            '{peso_humedo} + ',
            '({a} + {b}',
            '{a}}',
            'eval("return 1;");',
            'system("dir");',
            'exec("whoami");',
            'passthru("echo test");',
            'shell_exec("calc");',
            'function() { return 1; }',
            'echo {campo};',
            '{campo con espacios} * 2'
        ];

        foreach ($formulasInvalidas as $formula) {
            $resultado = EvaluadorFormula::validarSintaxis($formula);
            $this->assertIsArray($resultado);
            $this->assertFalse(
                $resultado['valido'],
                "La fórmula maliciosa o inválida '{$formula}' debe ser rechazada."
            );
        }
    }

    public function testEvaluadorFormulaCalculoAritmeticoYFunciones(): void {
        $valores = [
            'masa_seca' => 2400.0,
            'volumen' => 1200.0,
            'peso_humedo' => 115.0,
            'peso_seco' => 100.0,
            'area' => 16.0,
            'esfuerzo' => 3.14159265,
            'cero' => 0.0
        ];

        // 1. División aritmética
        $resDiv = EvaluadorFormula::evaluar('{masa_seca} / {volumen}', $valores);
        $this->assertEqualsWithDelta(2.0, $resDiv, 0.0001);

        // 2. Cálculo porcentual con paréntesis
        $resPorc = EvaluadorFormula::evaluar('(({peso_humedo} - {peso_seco}) / {peso_seco}) * 100', $valores);
        $this->assertEqualsWithDelta(15.0, $resPorc, 0.0001);

        // 3. Raíz cuadrada
        $resSqrt = EvaluadorFormula::evaluar('sqrt({area})', $valores);
        $this->assertEqualsWithDelta(4.0, $resSqrt, 0.0001);

        // 4. Redondeo
        $resRound = EvaluadorFormula::evaluar('round({esfuerzo}, 2)', $valores);
        $this->assertEqualsWithDelta(3.14, $resRound, 0.0001);

        // 5. Manejo seguro de división por cero (sin excepción no controlada)
        $resCero = EvaluadorFormula::evaluar('{masa_seca} / {cero}', $valores);
        $this->assertSame(0.0, (float)$resCero);
    }

    public function testPlantillaModeloVarianteClientePrevaleceSobreGeneral(): void {
        $this->db->beginTransaction();
        try {
            // Obtener un ensayo existente
            $ensayoId = (int)$this->db->query("SELECT id FROM productos WHERE activo = 1 LIMIT 1")->fetchColumn();
            if ($ensayoId <= 0) {
                $ensayoId = 1;
            }

            // Obtener o crear un cliente de prueba
            $clienteId = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
            if ($clienteId <= 0) {
                $this->db->exec("INSERT INTO clientes (nombre_razon_social, activo) VALUES ('Cliente Prueba Test SA', 1)");
                $clienteId = (int)$this->db->lastInsertId();
            }

            // 1. Crear plantilla general aprobada
            $this->db->prepare("
                INSERT INTO plantillas (codigo, nombre, ensayo_id, norma, orientacion, cliente_id, estado, version_actual, es_por_defecto, creado_por)
                VALUES ('TEST-GEN-01', 'Plantilla General Test', :eid, 'ASTM TEST', 'vertical', NULL, 'aprobada', 'Rev. 01', 1, 1)
            ")->execute(['eid' => $ensayoId]);
            $pltGeneralId = (int)$this->db->lastInsertId();

            // 2. Crear plantilla variante para el cliente aprobada
            $this->db->prepare("
                INSERT INTO plantillas (codigo, nombre, ensayo_id, norma, orientacion, cliente_id, estado, version_actual, es_por_defecto, creado_por)
                VALUES ('TEST-VAR-01', 'Plantilla Variante Consorcio', :eid, 'ASTM TEST VAR', 'vertical', :cid, 'aprobada', 'Rev. 01', 0, 1)
            ")->execute(['eid' => $ensayoId, 'cid' => $clienteId]);
            $pltVarianteId = (int)$this->db->lastInsertId();

            // 3. Consultar para el cliente específico
            $disponiblesCliente = $this->modelo->obtenerPlantillasDisponiblesParaEnsayo($ensayoId, $clienteId);
            $this->assertNotEmpty($disponiblesCliente);

            // La primera debe ser la variante del cliente
            $primera = $disponiblesCliente[0];
            $this->assertEquals($pltVarianteId, $primera['id']);
            $this->assertEquals(1, $primera['es_variante_cliente']);

            // La sugerida debe ser la variante del cliente
            $sugerida = $this->modelo->obtenerPlantillaSugerida($ensayoId, $clienteId);
            $this->assertNotNull($sugerida);
            $this->assertEquals($pltVarianteId, $sugerida['id']);

            // 4. Si se consulta para otro cliente o sin cliente, no debe sugerir la variante ajena
            $sugeridaOtro = $this->modelo->obtenerPlantillaSugerida($ensayoId, 999999);
            $this->assertNotEquals($pltVarianteId, $sugeridaOtro['id'] ?? 0);
        } finally {
            $this->db->rollBack();
        }
    }

    public function testPlantillaModeloAprobacionGeneraSnapshotInmutableYHashSha256(): void {
        $this->db->beginTransaction();
        try {
            $ensayoId = (int)$this->db->query("SELECT id FROM productos WHERE activo = 1 LIMIT 1")->fetchColumn() ?: 1;

            // Crear plantilla borrador con secciones y campos
            $datos = [
                'id' => 0,
                'codigo' => 'PLT-TEST-REV',
                'nombre' => 'Plantilla de Ensayo para Revision',
                'ensayo_id' => $ensayoId,
                'norma' => 'ASTM D1234',
                'orientacion' => 'vertical',
                'estado' => 'borrador',
                'secciones' => [
                    [
                        'nombre' => 'Datos Principales',
                        'tipo_layout' => 'formulario',
                        'campos' => [
                            [
                                'variable_id' => 1,
                                'fila' => 1,
                                'columna' => 1,
                                'ancho_cols' => 6,
                                'visible_en_reporte' => 1
                            ]
                        ]
                    ]
                ]
            ];

            $plantillaId = $this->modelo->guardarPlantillaEstructural($datos, 1);
            $this->assertGreaterThan(0, $plantillaId);

            // Aprobar formalmente la plantilla
            $versionId = $this->modelo->aprobarPlantilla($plantillaId, 1, 'Aprobación de prueba unitaria');
            $this->assertGreaterThan(0, $versionId);

            // Verificar que se haya registrado en plantilla_versiones con hash SHA-256
            $stVer = $this->db->prepare("SELECT * FROM plantilla_versiones WHERE id = :vid");
            $stVer->execute(['vid' => $versionId]);
            $ver = $stVer->fetch(PDO::FETCH_ASSOC);

            $this->assertNotFalse($ver);
            $this->assertSame('Rev. 01', $ver['version']);
            $this->assertNotEmpty($ver['esquema_json']);
            $this->assertSame(64, strlen($ver['hash_integridad']));
            $this->assertSame(hash('sha256', $ver['esquema_json']), $ver['hash_integridad']);

            // Verificar estado de la plantilla principal
            $stPlt = $this->db->prepare("SELECT estado, version_actual FROM plantillas WHERE id = :pid");
            $stPlt->execute(['pid' => $plantillaId]);
            $plt = $stPlt->fetch(PDO::FETCH_ASSOC);

            $this->assertSame('aprobada', $plt['estado']);
            $this->assertSame('Rev. 01', $plt['version_actual']);
        } finally {
            $this->db->rollBack();
        }
    }

    public function testPlantillaModeloBloqueoYCongelamientoEnRecepcion(): void {
        $this->db->beginTransaction();
        try {
            $ensayoId = (int)$this->db->query("SELECT id FROM productos WHERE activo = 1 LIMIT 1")->fetchColumn() ?: 1;

            // Obtener una plantilla aprobada con versión
            $stPlt = $this->db->query("
                SELECT p.id, pv.id AS version_id
                FROM plantillas p
                JOIN plantilla_versiones pv ON (p.id = pv.plantilla_id AND p.version_actual = pv.version)
                WHERE p.estado = 'aprobada'
                LIMIT 1
            ");
            $plt = $stPlt->fetch(PDO::FETCH_ASSOC);
            $this->assertNotFalse($plt, "Debe existir al menos una plantilla aprobada para probar congelamiento.");

            $plantillaId = (int)$plt['id'];
            $versionEsperada = (int)$plt['version_id'];

            // Crear una hoja de solicitud de prueba en hojas_solicitud
            $this->db->exec("
                INSERT INTO hojas_solicitud (id_os, numero_registro, fecha_hora_llegada_laboratorio, nombre_empresa_o_cliente)
                VALUES (1, 'REG-TEST-999', NOW(), 'Cliente Test')
            ");
            $hojaId = (int)$this->db->lastInsertId();

            // Insertar detalle de muestra sin versión congelada
            $filas = [
                [
                    'codigo_muestra' => 'MS-9991-26',
                    'descripcion_ubicacion' => 'Punto KM 12',
                    'observacion_edad' => 'Edad 7 días',
                    'ensayo_id' => $ensayoId,
                    'plantilla_id' => $plantillaId,
                    'seleccion_manual' => 0,
                    'motivo_cambio' => ''
                ]
            ];
            $this->modelo->guardarDetallesMuestrasHoja($hojaId, $filas, 1);

            // Verificar inserción inicial
            $detallesAntes = $this->modelo->obtenerDetallesPorHoja($hojaId);
            $this->assertCount(1, $detallesAntes);
            $this->assertEquals(0, (int)$detallesAntes[0]['bloqueado_recepcion']);

            // Ejecutar bloqueo y congelamiento por recepción física
            $ok = $this->modelo->bloquearYCongelarDetallesRecepcion($hojaId, 1);
            $this->assertTrue($ok);

            // Verificar que se haya bloqueado y congelado la versión
            $detallesDespues = $this->modelo->obtenerDetallesPorHoja($hojaId);
            $this->assertEquals(1, (int)$detallesDespues[0]['bloqueado_recepcion']);
            $this->assertEquals($versionEsperada, (int)$detallesDespues[0]['plantilla_version_id']);

            // Verificar cambio manual con motivo en registro bloqueado
            $detalleId = (int)$detallesDespues[0]['id'];
            $okCambio = $this->modelo->cambiarPlantillaMuestraConMotivo(
                $detalleId,
                $plantillaId,
                $versionEsperada,
                'Reasignación autorizada por Gerencia de Calidad por cambio de método',
                1
            );
            $this->assertTrue($okCambio);

            $stDet = $this->db->prepare("SELECT seleccion_manual, motivo_cambio FROM hojas_solicitud_detalles WHERE id = :id");
            $stDet->execute(['id' => $detalleId]);
            $detActualizado = $stDet->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, (int)$detActualizado['seleccion_manual']);
            $this->assertStringContainsString('Reasignación autorizada por Gerencia de Calidad', $detActualizado['motivo_cambio']);
        } finally {
            $this->db->rollBack();
        }
    }

    public function testVeintiunFormatosMigradosTienenPlantillasYVersiones(): void {
        $st = $this->db->query("SELECT COUNT(*) FROM plantillas WHERE estado = 'aprobada'");
        $totalAprobadas = (int)$st->fetchColumn();
        $this->assertGreaterThanOrEqual(21, $totalAprobadas, "Deben existir al menos los 21 formatos migrados de CYCSA.");

        // Verificar que cada una tenga al menos una versión inmutable con hash
        $stVers = $this->db->query("
            SELECT p.codigo, pv.version, pv.hash_integridad
            FROM plantillas p
            JOIN plantilla_versiones pv ON p.id = pv.plantilla_id
            WHERE p.codigo LIKE 'PL-%'
        ");
        $versiones = $stVers->fetchAll(PDO::FETCH_ASSOC);
        $this->assertGreaterThanOrEqual(21, count($versiones));

        foreach ($versiones as $v) {
            $this->assertSame(64, strlen($v['hash_integridad']), "El hash de la plantilla {$v['codigo']} debe ser SHA-256.");
        }
    }

    public function testSelloAcreditacionPredeterminadoExisteYEsAccesible(): void {
        $rutaImagen = dirname(__DIR__, 2) . '/publico/img/sello_acreditacion_default.jpeg';
        $this->assertFileExists($rutaImagen, "El sello de acreditación predeterminado debe existir en publico/img/.");
        $this->assertGreaterThan(1000, filesize($rutaImagen), "El archivo de sello de acreditación no debe estar vacío.");

        $rutaUploads = dirname(__DIR__, 2) . '/publico/uploads/acreditaciones/sello_acreditacion_default.jpeg';
        $this->assertFileExists($rutaUploads, "El sello de acreditación predeterminado debe existir en uploads/acreditaciones/.");
    }

    public function testControladorIndexYEstudioRenderizanSinErroresSQL(): void {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/plantillas';

        $controlador = new \Cycsa\Modulos\Configuracion\Controladores\PlantillasControlador();
        $peticion = new \Cycsa\Nucleo\Peticion();
        $respuesta = new \Cycsa\Nucleo\Respuesta();

        // 1. Probar index()
        ob_start();
        $controlador->index($peticion, $respuesta);
        $salidaIndex = ob_get_clean();
        $this->assertNotEmpty($salidaIndex);
        $this->assertStringContainsString('Catálogo de Plantillas de Ensayo', $salidaIndex);

        // 2. Probar estudio() para nueva plantilla
        ob_start();
        $controlador->estudio($peticion, $respuesta);
        $salidaEstudio = ob_get_clean();
        $this->assertNotEmpty($salidaEstudio);
        $this->assertStringContainsString('studio-container', $salidaEstudio);

        // 3. Probar disponibles AJAX
        ob_start();
        $controlador->disponibles($peticion, $respuesta);
        $salidaDisponibles = ob_get_clean();
        $this->assertNotEmpty($salidaDisponibles);
        $json = json_decode($salidaDisponibles, true);
        $this->assertIsArray($json);

        // 4. Probar guardar() con secciones y campos vía AJAX
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/plantillas/guardar';
        $_POST = [
            'id' => 0,
            'codigo' => 'PL-TEST-UNIT-' . time(),
            'nombre' => 'Plantilla de Ensayo Unit Test',
            'ensayo_id' => 1,
            'norma' => 'ASTM D-TEST',
            'orientacion' => 'horizontal',
            'cliente_id' => '',
            'incluir_sello_acreditacion' => 1,
            'sello_acreditacion_url' => 'publico/img/sello_acreditacion_default.jpeg',
            'es_por_defecto' => 0,
            'estado' => 'borrador',
            'secciones_json' => json_encode([
                [
                    'nombre' => 'Sección 1 Identificación',
                    'tipo_layout' => 'formulario',
                    'campos' => [
                        [
                            'variable_id' => 1,
                            'ancho_cols' => 4,
                            'decimales' => 2,
                            'visible_en_reporte' => 1,
                            'solo_lectura' => 0,
                            'formula' => ''
                        ]
                    ]
                ],
                [
                    'nombre' => 'Sección 2 Resultados',
                    'tipo_layout' => 'formulario',
                    'campos' => [
                        [
                            'variable_id' => 2,
                            'ancho_cols' => 6,
                            'decimales' => 3,
                            'visible_en_reporte' => 1,
                            'solo_lectura' => 1,
                            'formula' => '{peso_humedo} - {peso_seco}'
                        ]
                    ]
                ]
            ]),
            'csrf_token' => $_SESSION['csrf_token'],
            'es_ajax' => '1'
        ];

        ob_start();
        $controlador->guardar($peticion, $respuesta);
        $salidaGuardar = ob_get_clean();
        $this->assertNotEmpty($salidaGuardar);
        $resGuardar = json_decode($salidaGuardar, true);
        $this->assertIsArray($resGuardar);
        $this->assertSame('success', $resGuardar['status'] ?? null, "Guardar falló: " . ($resGuardar['message'] ?? ''));
        $plantillaIdCreada = (int)($resGuardar['plantilla_id'] ?? 0);
        $this->assertGreaterThan(0, $plantillaIdCreada);

        // 5. Probar previewModal() en Horizontal y Vertical
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        $_GET['id'] = $plantillaIdCreada;
        $_GET['orientacion'] = 'horizontal';
        $_GET['incluir_sello'] = '1';
        ob_start();
        $controlador->previewModal($peticion, $respuesta);
        $salidaPreviewH = ob_get_clean();
        $jsonPreviewH = json_decode($salidaPreviewH, true);
        $this->assertSame('success', $jsonPreviewH['status'] ?? null, "Preview horizontal falló: " . ($jsonPreviewH['message'] ?? ''));
        $this->assertStringContainsString('hoja_membretada_horizontal.jpg', $jsonPreviewH['html'] ?? '');
        $this->assertStringContainsString('sheet-cabecera', $jsonPreviewH['html'] ?? '');
        $this->assertStringContainsString('preview-cuerpo', $jsonPreviewH['html'] ?? '');
        $this->assertStringContainsString('tabla-metadatos-preview', $jsonPreviewH['html'] ?? '');
        $this->assertStringContainsString('preview-sello-box', $jsonPreviewH['html'] ?? '');
        $this->assertStringContainsString('btnZoom_fit', $jsonPreviewH['html'] ?? '');

        // Validación estricta de CERO EMOJIS en el HTML
        $patronEmojis = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';
        $this->assertSame(0, preg_match($patronEmojis, $jsonPreviewH['html'] ?? ''), "La vista previa no debe contener ningún emoji.");

        $_GET['orientacion'] = 'vertical';
        ob_start();
        $controlador->previewModal($peticion, $respuesta);
        $salidaPreviewV = ob_get_clean();
        $jsonPreviewV = json_decode($salidaPreviewV, true);
        $this->assertSame('success', $jsonPreviewV['status'] ?? null);
        $this->assertStringContainsString('hoja_membretada_vertical.jpg', $jsonPreviewV['html'] ?? '');
        $this->assertStringContainsString('Vertical', $jsonPreviewV['html'] ?? '');
        $this->assertStringContainsString('preview-cuerpo', $jsonPreviewV['html'] ?? '');
        $this->assertSame(0, preg_match($patronEmojis, $jsonPreviewV['html'] ?? ''), "La vista previa vertical no debe contener emojis.");
    }

    public function testHojasMembretadasVerticalYHorizontalExistenYEstanCalibradas(): void {
        $rutaVert1 = dirname(__DIR__, 2) . '/publico/img/hoja_vertical.jpg';
        $rutaVert2 = dirname(__DIR__, 2) . '/publico/img/hoja_membretada_vertical.jpg';
        $rutaHoriz1 = dirname(__DIR__, 2) . '/publico/img/hoja_horizontal.jpg';
        $rutaHoriz2 = dirname(__DIR__, 2) . '/publico/img/hoja_membretada_horizontal.jpg';

        $this->assertFileExists($rutaVert1);
        $this->assertFileExists($rutaVert2);
        $this->assertFileExists($rutaHoriz1);
        $this->assertFileExists($rutaHoriz2);

        $szV = getimagesize($rutaVert1);
        $this->assertSame(2550, $szV[0], "Ancho vertical debe ser 2550px");
        $this->assertSame(3300, $szV[1], "Alto vertical debe ser 3300px");

        $szH = getimagesize($rutaHoriz1);
        $this->assertSame(3300, $szH[0], "Ancho horizontal debe ser 3300px");
        $this->assertSame(2550, $szH[1], "Alto horizontal debe ser 2550px");
    }

    public function testGenerarMatrizTecnicaPdfConOrientacionVerticalYHorizontal(): void {
        $detalleBase = [
            'descripcion_ensayo' => 'Ensayo de Compresión de Cilindros',
            'codigo_documento' => 'CYCSA-RT-FM-22 A',
            'codigo_os' => 'OS-2026-0001',
            'norma_astm' => 'ASTM C39',
            'cliente_nombre' => 'Consorcio Deportivo',
            'proyecto' => 'Estadio Nacional',
            'resultados_json' => json_encode([
                'filas' => [
                    ['Código laboratorio' => 'CYC-001', 'Nombre muestra' => 'Muestra 1', 'Carga (lb)' => '65000', 'Área (in²)' => '12.57']
                ]
            ])
        ];

        // 1. Probar en horizontal
        $detalleH = $detalleBase;
        $detalleH['orientacion'] = 'horizontal';
        $pdfH = generarMatrizTecnicaPDF($detalleH);
        $this->assertStringStartsWith('%PDF-', $pdfH, "El PDF horizontal debe iniciar con cabecera %PDF-");
        $this->assertGreaterThan(1000, strlen($pdfH));

        // 2. Probar en vertical
        $detalleV = $detalleBase;
        $detalleV['orientacion'] = 'vertical';
        $pdfV = generarMatrizTecnicaPDF($detalleV);
        $this->assertStringStartsWith('%PDF-', $pdfV, "El PDF vertical debe iniciar con cabecera %PDF-");
        $this->assertGreaterThan(1000, strlen($pdfV));
    }
}

