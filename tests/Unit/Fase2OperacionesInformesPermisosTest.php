<?php

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;
use PHPUnit\Framework\TestCase;

class TestRespuestaFase2Operaciones extends Respuesta {
    public string $urlRedirigida = '';
    public ?array $jsonRespuesta = null;
    public int $codigoRespuesta = 200;

    public function redirigir(string $url): void {
        $this->urlRedirigida = $url;
    }

    public function enviarJson(array $datos, int $codigoHttp = 200): void {
        $this->jsonRespuesta = $datos;
        $this->codigoRespuesta = $codigoHttp;
    }
}

class Fase2OperacionesInformesPermisosTest extends TestCase {
    private PDO $db;
    private array $idsCleanup = [
        'detalles' => [],
        'hojas' => [],
        'os' => [],
        'cotizaciones' => [],
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Supervisor QA Test';
        $_SESSION['csrf_token'] = 'token_fase2_test_123';
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown(): void {
        try {
            foreach ($this->idsCleanup['hojas'] as $hid) {
                $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id")->execute(['id' => $hid]);
                $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = :id")->execute(['id' => $hid]);
            }
            foreach ($this->idsCleanup['os'] as $oid) {
                $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $oid]);
            }
            foreach ($this->idsCleanup['detalles'] as $did) {
                $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id = :id")->execute(['id' => $did]);
            }
            foreach ($this->idsCleanup['cotizaciones'] as $cid) {
                $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $cid]);
            }
        } catch (\Throwable $e) {
        }
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }

    private function crearFixtureMatrizConcreto(): array {
        $idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, email) VALUES ('Cliente Fase2', 'cliente@fase2.test')");
            $idCliente = (int)$this->db->lastInsertId();
        }
        $idUsuario = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $uid = uniqid();
        $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, nombre_proyecto, estado, subtotal, impuesto, total) VALUES (:cod, :cli, :uid, 'Proyecto Fase2', 'Aprobada por Cliente', 1000, 150, 1150)")
            ->execute(['cod' => 'COT-F2-' . $uid, 'cli' => $idCliente, 'uid' => $idUsuario]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsCleanup['cotizaciones'][] = $idCot;

        $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, norma_astm, cantidad, precio_unitario, subtotal, resultados_json) VALUES (:cot, 'Resistencia de Concreto', 'ASTM C39', 1, 500, 500, :json)")
            ->execute([
                'cot' => $idCot,
                'json' => json_encode([
                    'version_actual' => 1,
                    'filas' => [[
                        'Código laboratorio' => 'MC-9001-26',
                        'Nombre muestra' => 'Viga Principal Original',
                        'Reven. (in)' => '4',
                        'Reven. (cm)' => '10',
                        'Temp. (°C)' => '26.5',
                        'Diseño de concreto (lb/in²)' => '4000',
                        'Fecha de Fabricación' => '2026-11-01',
                        'Fecha de Ensayo' => '2026-11-29',
                        'Edad (Días)' => '28',
                        'Área (in²)' => '28.27',
                        'Carga (lb)' => '',
                    ]],
                    'metadatos' => [
                        'equipo_marca' => 'TROXLER',
                        'equipo_modelo' => '3440',
                        'equipo_serie' => '99112',
                        'equipo_codigo' => 'Eq-9999',
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);
        $idDet = (int)$this->db->lastInsertId();
        $this->idsCleanup['detalles'][] = $idDet;

        $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, nombre_proyecto, estado, fecha_emision) VALUES (:os, :cot, :cli, 'Proyecto Fase2', 'En Proceso', CURRENT_DATE)")
            ->execute(['os' => 'OS-F2-' . $uid, 'cot' => $idCot, 'cli' => $idCliente]);
        $idOs = (int)$this->db->lastInsertId();
        $this->idsCleanup['os'][] = $idOs;

        $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, numero_registro, muestras_json) VALUES (:os, :det, :reg, :mj)")
            ->execute([
                'os' => $idOs,
                'det' => $idDet,
                'reg' => 'REG-F2-' . $uid,
                'mj' => json_encode([['nombre_muestra' => 'MC-9001-26', 'descripcion' => 'Viga Principal Original']]),
            ]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->idsCleanup['hojas'][] = $idHoja;

        return ['id_cot' => $idCot, 'id_detalle' => $idDet, 'id_os' => $idOs, 'id_hoja' => $idHoja];
    }

    public function testClasificacionColumnasOcultaNombreMuestraYCompactacionATecnico(): void {
        $this->assertSame('identidad', clasificarColumnaRecepcionOTecnico('Código laboratorio'));
        $this->assertSame('recepcion', clasificarColumnaRecepcionOTecnico('Nombre muestra'));
        $this->assertSame('tecnico', clasificarColumnaRecepcionOTecnico('Carga (lb)'));

        $colsDensimetro = [
            'Código laboratorio', 'Nombre muestra', 'Capa (N°)', 'Espesor (cm)',
            'Profundidad (cm)', 'P.V.S Max (kg/m³)', 'Humedad Óptima ((% )(P/P))',
            'P.V.S.Sitio (kg/m³)', 'Humedad Sitio ((%) (P/P))', 'Compactación ((%) (P/P))',
            'Fecha de muestreo'
        ];
        $clasif = clasificarColumnasMatrizPorRol($colsDensimetro, 'compactacion_densimetro_nuclear.md');
        $this->assertSame('identidad', $clasif['Código laboratorio']);
        $this->assertSame('recepcion', $clasif['Nombre muestra']);
        $this->assertSame('recepcion', $clasif['P.V.S Max (kg/m³)']);
        $this->assertSame('recepcion', $clasif['Humedad Óptima ((% )(P/P))']);
        $this->assertSame('recepcion', $clasif['P.V.S.Sitio (kg/m³)']);
        $this->assertSame('recepcion', $clasif['Compactación ((%) (P/P))']);
        $this->assertSame('recepcion', $clasif['Capa (N°)']);
        $this->assertSame('recepcion', $clasif['Espesor (cm)']);
        $this->assertSame('recepcion', $clasif['Profundidad (cm)']);
        $this->assertSame('recepcion', $clasif['Fecha de muestreo']);
    }

    public function testTecnicoNoPuedeSobrescribirRecepcionNombreMuestraNiDensimetroEnBackend(): void {
        $fx = $this->crearFixtureMatrizConcreto();
        $_SESSION['usuario_rol'] = 6; // Laboratorista / Técnico (no supervisor)
        $_SESSION['usuario_nombre'] = 'Técnico Ensayos';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'resultados_json' => json_encode([
                'filas' => [[
                    'Código laboratorio' => 'MC-9001-26',
                    'Nombre muestra' => 'HACKED NOMBRE',
                    'Reven. (in)' => '99',
                    'Diseño de concreto (lb/in²)' => '9999',
                    'Área (in²)' => '28.30',
                    'Carga (lb)' => '112000',
                    'R. Compresión (lb/in²)' => '3960',
                ]],
                'metadatos' => [
                    'equipo_marca' => 'FALSO',
                    'equipo_serie' => '00000',
                    'nota_aditivos' => 'Plastificante X',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];

        $ctrl = new OperacionesControlador();
        $resp = new TestRespuestaFase2Operaciones();
        $ctrl->guardarMatrizProductoPOST(new Peticion(), $resp);

        $raw = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode((string)$raw, true);

        // El técnico SÍ pudo guardar sus mediciones técnicas
        $this->assertSame('112000', (string)$dec['filas'][0]['Carga (lb)']);
        $this->assertSame('3960', (string)$dec['filas'][0]['R. Compresión (lb/in²)']);

        // Pero el backend restauró TODOS los datos de recepción, Nombre muestra, fechas/edad y equipo densímetro
        $this->assertSame('Viga Principal Original', $dec['filas'][0]['Nombre muestra']);
        $this->assertSame('4', (string)$dec['filas'][0]['Reven. (in)']);
        $this->assertSame('10', (string)$dec['filas'][0]['Reven. (cm)']);
        $this->assertSame('4000', (string)$dec['filas'][0]['Diseño de concreto (lb/in²)']);
        $this->assertSame('2026-11-01', (string)$dec['filas'][0]['Fecha de Fabricación']);
        $this->assertSame('2026-11-29', (string)$dec['filas'][0]['Fecha de Ensayo']);
        $this->assertSame('28', (string)$dec['filas'][0]['Edad (Días)']);
        $this->assertSame('TROXLER', $dec['metadatos']['equipo_marca']);
        $this->assertSame('99112', $dec['metadatos']['equipo_serie']);
        $this->assertSame('Plastificante X', $dec['metadatos']['nota_aditivos']);
    }

    public function testVistaCapturaOcultaRecepcionYMetadatosATecnicoYMuestraASupervisor(): void {
        $htmlCaptura = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('if ($esSupervisor && !$esModoHistorico)', $htmlCaptura);
        $this->assertStringContainsString('col-recepcion-oculta', $htmlCaptura);
        $this->assertStringContainsString('id="meta_nota_aditivos"', $htmlCaptura);
        $this->assertStringContainsString('id="meta_observaciones_pendientes"', $htmlCaptura);
        $this->assertStringContainsString('id="meta_columnas_asterisco"', $htmlCaptura);

        // Renderizar la vista real como Técnico (rol 6) y verificar que NO ve recepción ni metadatos
        $detalle = [
            'id' => 999,
            'id_os' => 888,
            'codigo_os' => 'OS-2026-0888',
            'descripcion_ensayo' => 'Resistencia a la compresión de cilindros',
            'norma_astm' => 'ASTM C39',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'resultados_json' => json_encode(['filas' => [['Código laboratorio' => 'MS-16798-26', 'Carga (lb)' => '79204']]], JSON_UNESCAPED_UNICODE),
        ];
        $idDetalle = 999;
        $columnas = ['Código laboratorio', 'Nombre muestra', 'Área (in²)', 'Reven. (in)', 'Reven. (cm)', 'Temp. (°C)', 'Carga (lb)', 'Peso Volumétrico (kg/m³)', 'Diseño de concreto (lb/in²)', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)', 'Fecha de Fabricación', 'Fecha de Ensayo', 'Edad (Días)'];
        $clasificacionColumnas = clasificarColumnasMatrizPorRol($columnas, 'resistencia_de_concreto.md');
        $tieneCamposRecepcion = true;
        $muestrasRecepcionCompletas = 1;
        $muestrasSeteadas = [['codigo_lab' => 'MS-16798-26', 'datos_recepcion' => []]];
        $schemaInfo = obtenerEsquemaPlantillaEnsayo('resistencia_de_concreto.md');
        $formatosSchemaJson = json_encode(['resistencia_de_concreto.md' => $schemaInfo], JSON_UNESCAPED_UNICODE);
        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, ['cliente_nombre' => 'CLIENTE CONFIDENCIAL TEST', 'proyecto' => 'PROYECTO SECRETO']);
        $versionActual = 1;
        $versionSolicitada = 1;
        $esHistorica = false;
        $listaVersiones = [];
        $versionActivaInfo = null;
        $prefijoMuestraOS = 'MS';
        $esEnsayoRoturaLab = true;

        $_SESSION['usuario_rol'] = 6;
        $_SESSION['usuario_permisos'] = ['operaciones' => ['ver' => true, 'crear_editar' => true, 'aprobar' => false]];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $htmlTecnico = (string)ob_get_clean();

        $this->assertStringContainsString('id="seccion-captura-tecnica-lab"', $htmlTecnico);
        $this->assertStringNotContainsString('id="recepcion-vs-tecnico-bar"', $htmlTecnico, 'El técnico NO debe ver la barra de recepción ni el botón para mostrar columnas de recepción.');
        $this->assertStringNotContainsString('id="btn-toggle-plegar-recepcion"', $htmlTecnico);
        $this->assertStringNotContainsString('CLIENTE CONFIDENCIAL TEST', $htmlTecnico, 'El técnico NO debe ver metadatos de cliente/recepción.');
        $this->assertStringNotContainsString('Metadatos Oficiales del Encabezado del Informe', $htmlTecnico);

        // Renderizar como Supervisor (rol 1): SÍ ve metadatos y control de columnas de recepción
        $_SESSION['usuario_rol'] = 1;
        unset($_SESSION['usuario_permisos']);
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $htmlSupervisor = (string)ob_get_clean();

        $this->assertStringContainsString('id="recepcion-vs-tecnico-bar"', $htmlSupervisor);
        $this->assertStringContainsString('id="btn-toggle-plegar-recepcion"', $htmlSupervisor);
        $this->assertStringContainsString('Metadatos Oficiales del Encabezado del Informe', $htmlSupervisor);
    }

    public function testTecnicoCiegoIso17025BloqueadoEnOperacionesYEntregaHabilitadaEnOrdenesServicio(): void {
        // 1. Detección estricta de rol técnico ciego
        $_SESSION['usuario_rol'] = 6;
        $_SESSION['usuario_permisos'] = ['operaciones' => ['ver' => true, 'crear_editar' => true]];
        $this->assertTrue(esTecnicoCiegoISO17025());
        $this->assertFalse(esSupervisorOperacionesCYCSA());

        $_SESSION['usuario_rol'] = 4; // Asistente / Atención al Cliente
        $this->assertFalse(esTecnicoCiegoISO17025());

        $_SESSION['usuario_rol'] = 3; // Supervisor
        $this->assertFalse(esTecnicoCiegoISO17025());
        $this->assertTrue(esSupervisorOperacionesCYCSA());

        // 2. Backend bloquea impresión, PDF y envío al cliente para Técnico (rol 6)
        $_SESSION['usuario_rol'] = 6;
        $_SESSION['usuario_permisos'] = ['operaciones' => ['ver' => true, 'crear_editar' => true]];
        $_GET = ['id_detalle' => 999];
        $_POST = ['csrf_token' => $_SESSION['csrf_token'], 'id_detalle' => 999];
        $ctrl = new OperacionesControlador();

        $respImp = new TestRespuestaFase2Operaciones();
        $ctrl->imprimirMatrizProducto(new Peticion(), $respImp);
        $this->assertSame('/Cycsa/publico/operaciones', $respImp->urlRedirigida);
        $this->assertStringContainsString('ISO/IEC 17025', (string)($_SESSION['error'] ?? ''));

        unset($_SESSION['error']);
        $respPdf = new TestRespuestaFase2Operaciones();
        $ctrl->descargarMatrizPDF(new Peticion(), $respPdf);
        $this->assertSame('/Cycsa/publico/operaciones', $respPdf->urlRedirigida);
        $this->assertStringContainsString('ISO/IEC 17025', (string)($_SESSION['error'] ?? ''));

        unset($_SESSION['error']);
        $respEnv = new TestRespuestaFase2Operaciones();
        $ctrl->enviarMatrizCliente(new Peticion(), $respEnv);
        $this->assertSame('/Cycsa/publico/operaciones', $respEnv->urlRedirigida);
        $this->assertStringContainsString('ISO/IEC 17025', (string)($_SESSION['error'] ?? ''));

        // 3. Operaciones/Vistas/index.php y ver.php en modo Técnico (rol 6) ocultan cliente, proyecto, imprimir y envío
        $ordenMock = [
            'id' => 501,
            'codigo_os' => 'OS-2026-0501',
            'cot_codigo' => 'COT-2026-0501',
            'cliente_nombre' => 'CONSTRUCTORA SECRETA S.A.',
            'cliente_informe_nombre' => 'CONSTRUCTORA SECRETA S.A.',
            'cliente_factura_nombre' => 'FACTURADORA SECRETA S.A.',
            'cliente_email' => 'secreto@constructora.test',
            'nombre_proyecto' => 'TORRE PRIVADA 99',
            'estado' => 'En Proceso',
            'items' => [[
                'id' => 701,
                'descripcion_ensayo' => 'Resistencia a la compresión de cilindros',
                'norma_astm' => 'ASTM C39',
                'cantidad' => 3,
                'permitido' => 1,
                'resultados_json' => json_encode(['filas' => [['Código laboratorio' => 'MS-0001-26', 'Carga (lb)' => '90000']], 'revision' => ['estado' => 'aprobada']]),
            ]],
        ];

        $ordenes = [$ordenMock];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php';
        $htmlIndexTec = (string)ob_get_clean();

        $this->assertStringContainsString('OS-2026-0501', $htmlIndexTec);
        $this->assertStringNotContainsString('CONSTRUCTORA SECRETA S.A.', $htmlIndexTec);
        $this->assertStringNotContainsString('TORRE PRIVADA 99', $htmlIndexTec);
        $this->assertStringNotContainsString('secreto@constructora.test', $htmlIndexTec);
        $this->assertStringNotContainsString('Imprimir RT-FM-22', $htmlIndexTec);
        $this->assertStringNotContainsString('Enviar al cliente', $htmlIndexTec);
        $this->assertStringNotContainsString('Descargar PDF', $htmlIndexTec);

        $o = $ordenMock;
        $informes_unificados = [];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/ver.php';
        $htmlVerTec = (string)ob_get_clean();

        $this->assertStringContainsString('OS-2026-0501', $htmlVerTec);
        $this->assertStringNotContainsString('CONSTRUCTORA SECRETA S.A.', $htmlVerTec);
        $this->assertStringNotContainsString('TORRE PRIVADA 99', $htmlVerTec);
        $this->assertStringNotContainsString('Imprimir RT-FM-22', $htmlVerTec);
        $this->assertStringNotContainsString('Enviar al cliente', $htmlVerTec);

        // 4. Órdenes de Servicio habilita Ver / Imprimir, PDF y Enviar al Cliente para Atención al Cliente
        $htmlOS = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php');
        $this->assertStringContainsString('4. Ensayos e Informes para Cliente', $htmlOS);
        $this->assertStringContainsString('id="modalEnviarInformeOS"', $htmlOS);
        $this->assertStringContainsString('/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=', $htmlOS);
        $this->assertStringContainsString('/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=', $htmlOS);
        $this->assertStringContainsString('abrirModalEnviarInformeOS(', $htmlOS);

        // 5. Nuevo módulo independiente Captura Técnica (/captura-tecnica) y Operaciones Documento Terminado (/operaciones/documento)
        $rutasWeb = (string)file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertStringContainsString("'/captura-tecnica'", $rutasWeb);
        $this->assertStringContainsString("'/captura-tecnica/llenar'", $rutasWeb);
        $this->assertStringContainsString("'/operaciones/documento'", $rutasWeb);

        $panelCtrlSrc = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Usuarios/Controladores/PanelControlador.php');
        $this->assertStringContainsString("'/Cycsa/publico/captura-tecnica'", $panelCtrlSrc);
        $this->assertStringContainsString("'nombre' => 'Captura Técnica'", $panelCtrlSrc);

        // Si un técnico (rol 6) intenta entrar a /operaciones o /operaciones/documento, es redirigido a /captura-tecnica
        $_SESSION['usuario_rol'] = 6;
        $_SESSION['usuario_permisos'] = ['operaciones' => ['ver' => true, 'crear_editar' => true]];
        $respOpIdx = new TestRespuestaFase2Operaciones();
        $ctrl->index(new Peticion(), $respOpIdx);
        $this->assertSame('/Cycsa/publico/captura-tecnica', $respOpIdx->urlRedirigida);

        $respDoc = new TestRespuestaFase2Operaciones();
        $ctrl->verDocumentoTerminado(new Peticion(), $respDoc);
        $this->assertSame('/Cycsa/publico/captura-tecnica', $respDoc->urlRedirigida);

        // Renderizar la vista del nuevo módulo Captura Técnica (100% ciega) y la vista del Documento Terminado (todos los datos)
        $ordenes = [[
            'id' => 501,
            'codigo_os' => 'OS-2026-0501',
            'items' => [[
                'id' => 701,
                'descripcion_ensayo' => 'Resistencia a la compresión de cilindros',
                'norma_astm' => 'ASTM C39',
                'cantidad' => 3,
                'estado' => 'pendiente',
                'tiene_resultados' => false,
                'motivo_devolucion' => '',
                'usuario_revisor' => '',
                'fecha_revision' => '',
            ]],
            'total_ensayos' => 1,
            'llenadas' => 0,
            'aprobadas' => 0,
            'devueltas' => 0,
            'en_revision' => 0,
            'solo_en_revision' => false,
            'id_accion_principal' => 701,
        ]];
        $filtroActivo = 'pendientes';
        $conteos = ['pendientes' => 1, 'devueltas' => 0, 'en_revision' => 0, 'todas' => 1];
        $busqueda = '';
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_tecnica_index.php';
        $htmlCT = (string)ob_get_clean();

        $this->assertStringContainsString('Captura Técnica', $htmlCT);
        $this->assertStringContainsString('/Cycsa/publico/captura-tecnica/llenar?id_detalle=701', $htmlCT);
        $this->assertStringNotContainsString('Imprimir RT-FM-22', $htmlCT);
        $this->assertStringNotContainsString('Enviar al cliente', $htmlCT);

        // Renderizar Documento Terminado en Operaciones (Supervisor) con Recepción + Técnico unidos
        $_SESSION['usuario_rol'] = 1;
        unset($_SESSION['usuario_permisos']);
        $detalle = [
            'id' => 701,
            'id_os' => 501,
            'codigo_os' => 'OS-2026-0501',
            'descripcion_ensayo' => 'Resistencia a la compresión de cilindros',
            'norma_astm' => 'ASTM C39',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'cliente_nombre' => 'CLIENTE FINAL S.A.',
            'cliente_email' => 'cliente@final.test',
            'resultados_json' => json_encode(['filas' => [[
                'Código laboratorio' => 'MS-16798-26',
                'Nombre muestra' => 'Columna C1',
                'Reven. (in)' => '7.5',
                'Reven. (cm)' => '19',
                'Carga (lb)' => '79204',
                'R. Compresión (lb/in²)' => '6300',
            ]], 'revision' => ['estado' => 'aprobada']], JSON_UNESCAPED_UNICODE),
        ];
        $schemaInfo = obtenerEsquemaPlantillaEnsayo('resistencia_de_concreto.md');
        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, ['cliente_nombre' => 'CLIENTE FINAL S.A.', 'proyecto' => 'EDIFICIO NORTE']);
        $columnasDocumento = ['Código laboratorio', 'Nombre muestra', 'Reven. (in)', 'Reven. (cm)', 'Carga (lb)', 'R. Compresión (lb/in²)'];
        $filasDocumento = [[
            'Código laboratorio' => 'MS-16798-26',
            'Nombre muestra' => 'Columna C1',
            'Reven. (in)' => '7.5',
            'Reven. (cm)' => '19',
            'Carga (lb)' => '79204',
            'R. Compresión (lb/in²)' => '6300',
        ]];
        $versionActual = 1;
        $codigoInformeConsecutivo = 'IC-0001-26';
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/documento_terminado.php';
        $htmlDoc = (string)ob_get_clean();

        $this->assertStringContainsString('id="documento-oficial-terminado"', $htmlDoc);
        $this->assertStringContainsString('CLIENTE FINAL S.A.', $htmlDoc);
        $this->assertStringContainsString('EDIFICIO NORTE', $htmlDoc);
        $this->assertStringContainsString('MS-16798-26', $htmlDoc);
        $this->assertStringContainsString('Columna C1', $htmlDoc);
        $this->assertStringContainsString('79204', $htmlDoc);
        $this->assertStringContainsString('6300', $htmlDoc);
        $this->assertStringContainsString('Imprimir RT-FM-22', $htmlDoc);
        $this->assertStringContainsString('Descargar PDF', $htmlDoc);
        $this->assertStringContainsString('Enviar al cliente', $htmlDoc);

        // 6. Compactación queda excluida de Captura Técnica (solo se llena en Órdenes de Servicio)
        $ctCtrlSrc = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/CapturaTecnicaControlador.php');
        $this->assertStringContainsString('esItemCompactacion', $ctCtrlSrc);
        $printSrc = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringNotContainsString('Observaciones y Condiciones del Ensayo', $printSrc);
        $this->assertStringNotContainsString('Declaración de Conformidad e Imparcialidad', $printSrc);
    }

    public function testAprobarMatrizPersisteRevisorAprobadorYObservacionesPendientes(): void {
        $fx = $this->crearFixtureMatrizConcreto();
        $_SESSION['usuario_rol'] = 3; // Supervisor
        $_SESSION['usuario_id'] = 7;
        $_SESSION['usuario_nombre'] = 'Ing. María Morales';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'nota_aprobacion' => 'Validación conforme.',
            'observaciones_pendientes' => 'Pendiente firma de revisión técnica',
        ];

        $ctrl = new OperacionesControlador();
        $resp = new TestRespuestaFase2Operaciones();
        $ctrl->aprobarMatrizProducto(new Peticion(), $resp);

        $raw = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode((string)$raw, true);

        $this->assertSame('aprobada', $dec['revision']['estado']);
        $this->assertSame('Ing. María Morales', $dec['revision']['usuario_revisor']);
        $this->assertSame('Ing. María Morales', $dec['revision']['usuario_aprobador']);
        $this->assertSame(7, (int)$dec['revision']['usuario_aprobador_id']);
        $this->assertNotEmpty($dec['revision']['fecha_aprobacion']);
        $this->assertSame('Pendiente firma de revisión técnica', $dec['revision']['observaciones_pendientes']);
    }

    public function testMatrizPrintYPdfRenderizanFirmasRealesAditivosAsteriscosYSinLogos(): void {
        $detalle = [
            'id' => 555,
            'codigo_os' => 'OS-2026-0555',
            'descripcion_ensayo' => 'Resistencia de Concreto',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'codigo_documento' => 'CYCSA-RT-FM-22 A V1-R2',
            'cliente_nombre' => 'CONSTRUCTORA FASE 2',
            'nombre_proyecto' => 'Torre Norte',
            'resultados_json' => json_encode([
                'version_actual' => 1,
                'filas' => [[
                    'Código laboratorio' => 'MC-0010-26',
                    'Nombre muestra' => 'Columna C1',
                    'Área (in²)' => '28.27',
                    'Carga (lb)' => '112000',
                    'Peso Volumétrico (kg/m³)' => '*2380',
                    'R. Compresión (lb/in²)' => '3960',
                    'R. Compresión (kg/cm²)' => '279',
                ]],
                'metadatos' => [
                    'logo_acreditacion' => '/Cycsa/publico/img/sello_acreditacion_default.jpeg',
                    'nota_aditivos' => 'Aditivo superplastificante 0.8% dosis',
                    'observaciones_pendientes' => 'Pendiente verificación de equipo calibrado',
                    'columnas_asterisco' => ['Peso Volumétrico (kg/m³)'],
                ],
                'revision' => [
                    'estado' => 'aprobada',
                    'usuario_revisor' => 'Ing. Ana Revisora',
                    'usuario_aprobador' => 'Ing. Carlos Aprobador',
                    'historial' => [[
                        'accion' => 'enviado_revision',
                        'nota' => 'BITACORA_INTERNA_SECRETA_NO_IMPRIMIR',
                    ]],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];

        // 1. Renderizar vista HTML con sin_logo=1 y sin_logo_acreditacion=1
        $sinLogo = true;
        $sinLogoAcreditacion = true;
        $columnas = ['Código laboratorio', 'Nombre muestra', 'Área (in²)', 'Carga (lb)', 'Peso Volumétrico (kg/m³)', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)'];
        $muestrasSeteadas = [];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('Ing. Ana Revisora', $html);
        $this->assertStringContainsString('Ing. Carlos Aprobador', $html);
        $this->assertStringContainsString('Pendiente verificación de equipo calibrado', $html);
        $this->assertStringContainsString('Aditivo superplastificante 0.8% dosis', $html);
        $this->assertStringContainsString('Peso Volumétrico (kg/m³) *', $html);
        $this->assertStringContainsString('Parámetro o resultado marcado fuera del alcance de acreditación', $html);
        $this->assertStringContainsString('background-image: none;', $html);
        $this->assertStringNotContainsString('logo-acreditacion-print', $html);
        $this->assertStringNotContainsString('BITACORA_INTERNA_SECRETA_NO_IMPRIMIR', $html);

        // 2. Generar PDF con sin_logo y sin_logo_acreditacion
        $pdfNormal = generarMatrizTecnicaPDF($detalle, [], $columnas, '', []);
        $pdfSinLogos = generarMatrizTecnicaPDF($detalle, [], $columnas, '', [
            'sin_logo' => true,
            'sin_logo_acreditacion' => true,
        ]);
        $this->assertStringStartsWith('%PDF-', $pdfNormal);
        $this->assertStringStartsWith('%PDF-', $pdfSinLogos);
        // Al omitir fondo membretado, el PDF sin logo debe ser considerablemente más liviano
        $this->assertLessThan(strlen($pdfNormal), strlen($pdfSinLogos));
    }

    public function testParsearListaCorreosSoportaMultiplesCorreosYRechazaInvalidos(): void {
        $res = parsearListaCorreos('uno@cycsa.com, DOS@cycsa.com; tres@obra.ni, invalido@, uno@cycsa.com');
        $this->assertSame(['uno@cycsa.com', 'DOS@cycsa.com', 'tres@obra.ni'], $res);

        $resArr = parsearListaCorreos(['a@b.com; c@d.com', 'not-an-email', 'e@f.org']);
        $this->assertSame(['a@b.com', 'c@d.com', 'e@f.org'], $resArr);

        $this->assertSame([], parsearListaCorreos('correo-invalido, otro-malo'));
    }

    public function testVistasOperacionesContienenEnlaceHojaRtFm60YMultiplesCorreos(): void {
        $indexHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php');
        $verHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/ver.php');
        $printHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');

        $this->assertStringContainsString('/laboratorio/imprimir-solicitud?id_hoja=', $indexHtml);
        $this->assertStringContainsString('Hoja RT-FM-60', $indexHtml);
        $this->assertStringContainsString('/laboratorio/imprimir-solicitud?id_hoja=', $verHtml);
        $this->assertStringContainsString('Hoja RT-FM-60', $verHtml);

        $this->assertStringContainsString('type="text" name="destinatario" id="envio_destinatario"', $indexHtml);
        $this->assertStringContainsString('type="text" name="destinatario" required', $printHtml);
    }

    public function testSincronizarRoturasConMatrizYRevenimientoYAreaSinDefaults(): void {
        // 1.7: Revenimiento a 0.5 cm
        $this->assertSame(10.0, convertirUnidadMedicionPHP(4.0, 'in', 'cm'));
        $this->assertSame(11.5, convertirUnidadMedicionPHP(4.5, 'in', 'cm'));

        // 1.2 / 1.11: obtenerDatosMuestraDesdeMatriz NO inyecta área nominal 12.566 / 28.274 cuando no existe
        $datosSinArea = obtenerDatosMuestraDesdeMatriz([
            ['Código laboratorio' => 'MC-0100-26', 'Nombre muestra' => 'Cilindro 6x12'],
        ], 'MC-0100-26');
        $this->assertTrue($datosSinArea['encontrada']);
        $this->assertSame('6x12 in (15x30 cm)', $datosSinArea['tamano_cilindro']);
        $this->assertNull($datosSinArea['area']);

        // 1.3 / 1.4: sincronizarRoturasConMatriz escribe peso_volumetrico, fecha_real, edad_calculada y redondeos RT-FM-61
        $fx = $this->crearFixtureMatrizConcreto();
        $act = sincronizarRoturasConMatriz($this->db, $fx['id_hoja'], $fx['id_detalle'], [[
            'codigo_lab' => 'MC-9001-26',
            'carga' => 112345.6,
            'area' => 28.2743,
            'peso_volumetrico' => 2378.4,
            'esfuerzo_psi' => 3970.4,
            'esfuerzo_kgcm2' => 279.6,
            'fecha_real' => '2026-11-29',
            'edad_calculada' => 28,
        ]]);
        $this->assertSame(1, $act);

        $raw = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode((string)$raw, true);
        $fila0 = $dec['filas'][0];
        $this->assertEquals(112346, $fila0['Carga (lb)']);
        $this->assertEquals(28.27, $fila0['Área (in²)']);
        $this->assertEquals(2378, $fila0['Peso Volumétrico (kg/m³)']);
        $this->assertSame('2026-11-29', $fila0['Fecha de Ensayo']);
        $this->assertEquals(28, $fila0['Edad (Días)']);
    }
}
