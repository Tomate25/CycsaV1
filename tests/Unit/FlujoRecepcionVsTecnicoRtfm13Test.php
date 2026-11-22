<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo;
use PDO;

class TestRespuestaRecepcionVsTecnico extends Respuesta {
    public string $urlRedirigida = '';
    public ?array $jsonRespuesta = null;
    public int $codigoRespuesta = 200;

    public function redirigir(string $url): void {
        $this->urlRedirigida = $url;
    }

    public function enviarJson(array $datos, int $codigoEstado = 200): void {
        $this->codigoRespuesta = $codigoEstado;
        $this->jsonRespuesta = $datos;
    }
}

class FlujoRecepcionVsTecnicoRtfm13Test extends TestCase {

    private PDO $db;
    private OperacionModelo $opModelo;
    private OrdenServicioModelo $osModelo;
    private HojasServicioControlador $hsControlador;

    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idOS = 0;
    private int $idProdConcretoA = 0;
    private int $idProdConcretoB = 0;
    private int $idDetalleConcreto1 = 0;
    private int $idDetalleConcreto2 = 0;
    private array $productosCreados = [];
    private array $hojasCreadas = [];
    private array $secuenciasBackup = [];

    protected function setUp(): void {
        parent::setUp();

        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($this->db);

        $this->opModelo = new OperacionModelo();
        $this->osModelo = new OrdenServicioModelo();
        $this->hsControlador = new HojasServicioControlador();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = '/Cycsa/publico/index.php';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (id_rol, nombre, email, password, activo) VALUES (1, 'Tester Recepcion', 'rec_test@test.local', 'hash', 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $_SESSION['usuario_id'] = $idUser;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Tester Recepcion AGY';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($this->idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Test Recepcion', 'J0310000009999', 'cliente_rec@test.local', '22224444')");
            $this->idCliente = (int)$this->db->lastInsertId();
        }

        // Asegurar producto ligado al Formato #17 (Resistencia de Concreto)
        $stmtFmt17 = $this->db->query("SELECT id FROM formatos_ensayos WHERE id = 17 OR archivo_markdown = 'formato_de_resistencia_de_concreto.md' LIMIT 1");
        $idFormato17 = (int)$stmtFmt17->fetchColumn();
        $this->assertGreaterThan(0, $idFormato17, 'Debe existir formato #17 Resistencia de Concreto en BD.');

        $stmtP1 = $this->db->prepare("SELECT id FROM productos WHERE formato_id = :fid LIMIT 1");
        $stmtP1->execute(['fid' => $idFormato17]);
        $this->idProdConcretoA = (int)$stmtP1->fetchColumn();
        if ($this->idProdConcretoA <= 0) {
            $this->db->prepare("INSERT INTO productos (codigo_servicio, nombre_comercial, ensayo_servicio, norma_astm, formato_id, precio) VALUES ('CONC-TEST-A', 'Resistencia de Concreto Test A', 'Resistencia de Concreto', 'ASTM C39', :fid, 450)")
                     ->execute(['fid' => $idFormato17]);
            $this->idProdConcretoA = (int)$this->db->lastInsertId();
            $this->productosCreados[] = $this->idProdConcretoA;
        }

        $stmtP2 = $this->db->prepare("SELECT id FROM productos WHERE formato_id = :fid AND id != :pid1 LIMIT 1");
        $stmtP2->execute(['fid' => $idFormato17, 'pid1' => $this->idProdConcretoA]);
        $this->idProdConcretoB = (int)$stmtP2->fetchColumn();
        if ($this->idProdConcretoB <= 0) {
            $this->db->prepare("INSERT INTO productos (codigo_servicio, nombre_comercial, ensayo_servicio, norma_astm, formato_id, precio) VALUES ('CONC-TEST-B', 'Resistencia de Concreto Test B (Frente 2)', 'Resistencia de Concreto Frente 2', 'ASTM C39', :fid, 450)")
                     ->execute(['fid' => $idFormato17]);
            $this->idProdConcretoB = (int)$this->db->lastInsertId();
            $this->productosCreados[] = $this->idProdConcretoB;
        }

        $codigoCot = 'COT-REC-' . substr(uniqid(), -8);
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:cod, :cli, :usr, 'Ing. Calidad', 'Proyecto Control Concreto', 'Contado', 'Aprobada por Cliente', 2250, 2587.50)");
        $stmtCot->execute(['cod' => $codigoCot, 'cli' => $this->idCliente, 'usr' => $idUser]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        $stmtD1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Resistencia de Concreto Lote A (3 cilindros)', 'CONC-A', 'ASTM C39', 3, 450, 1350)");
        $stmtD1->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProdConcretoA]);
        $this->idDetalleConcreto1 = (int)$this->db->lastInsertId();

        $stmtD2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Resistencia de Concreto Lote B (2 cilindros)', 'CONC-B', 'ASTM C39', 2, 450, 900)");
        $stmtD2->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProdConcretoB]);
        $this->idDetalleConcreto2 = (int)$this->db->lastInsertId();

        $codigoOS = 'OS-REC-' . uniqid();
        $this->idOS = $this->osModelo->crear([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Calidad',
            'nombre_proyecto' => 'Proyecto Control Concreto',
            'forma_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada',
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS}");
        $this->db->exec("INSERT IGNORE INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES ({$this->idOS}, {$this->idDetalleConcreto1}), ({$this->idOS}, {$this->idDetalleConcreto2})");
    }

    protected function tearDown(): void {
        if (!empty($this->hojasCreadas)) {
            $inHojas = implode(',', array_map('intval', $this->hojasCreadas));
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inHojas})");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inHojas})");
            } catch (\Throwable $e) {}
        }

        if ($this->idOS > 0) {
            try {
                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = {$this->idOS}");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id_os = {$this->idOS}");
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOS}");
            } catch (\Throwable $e) {}
        }

        if ($this->idCotizacion > 0) {
            try {
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacion}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacion}");
            } catch (\Throwable $e) {}
        }

        if (!empty($this->productosCreados)) {
            $inProd = implode(',', array_map('intval', $this->productosCreados));
            try {
                $this->db->exec("DELETE FROM productos WHERE id IN ({$inProd})");
            } catch (\Throwable $e) {}
        }

        foreach ($this->secuenciasBackup as $sec) {
            try {
                $stmtRest = $this->db->prepare("UPDATE secuencias_muestras SET ultimo_correlativo = :ult WHERE anio = :anio AND tipo_muestra = :tm");
                $stmtRest->execute([
                    'ult' => $sec['ultimo_correlativo'],
                    'anio' => $sec['anio'],
                    'tm' => $sec['tipo_muestra'],
                ]);
            } catch (\Throwable $e) {}
        }

        $_POST = [];
        $_GET = [];
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_ACCEPT']);
        parent::tearDown();
    }

    private function crearHojaRtfm13ParaDetalle(int $idDetalle, int $cantidadMuestras, string $prefijo): array {
        $mNombres = [];
        $mDescs = [];
        $mInfos = [];
        for ($i = 1; $i <= $cantidadMuestras; $i++) {
            $mNombres[] = sprintf('%s-%04d-26', $prefijo, $i);
            $mDescs[] = 'Cilindro de concreto #' . $i;
            $mInfos[] = 'Ensayo 28d';
        }

        $idPlantillaSug = (int)$this->db->query("SELECT id FROM plantillas ORDER BY id ASC LIMIT 1")->fetchColumn();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOS,
            'id_detalle_cotizacion' => $idDetalle,
            'codigo_reg' => 'RT-FM-13-TEST-' . uniqid(),
            'fecha_recepcion' => '2026-11-12',
            'cliente_informe' => 'Constructora Prueba S.A.',
            'proyecto_informe' => 'Proyecto Control Concreto',
            'lugar_muestreo' => 'Obra Norte',
            'muestreado_por' => 'Técnico Campo',
            'observaciones' => 'Prueba flujo Recepción vs Técnico',
            'plantilla_global_id' => $idPlantillaSug,
            'm_nombre' => $mNombres,
            'm_desc' => $mDescs,
            'm_info' => $mInfos,
        ];

        $resp = new TestRespuestaRecepcionVsTecnico();
        $peticion = new Peticion();
        $this->hsControlador->guardarHojaSolicitud($peticion, $resp);

        $stmt = $this->db->prepare("SELECT id, muestras_json FROM hojas_solicitud WHERE id_os = :os AND id_detalle_cotizacion = :det ORDER BY id DESC LIMIT 1");
        $stmt->execute(['os' => $this->idOS, 'det' => $idDetalle]);
        $hojaRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $idHoja = (int)($hojaRow['id'] ?? 0);
        $this->assertGreaterThan(0, $idHoja);
        $this->hojasCreadas[] = $idHoja;

        // Verificar que redirige abriendo automáticamente la captura de recepción para esta hoja
        $this->assertStringContainsString('recepcion_hoja=' . $idHoja, $resp->urlRedirigida);

        $codigos = [];
        $mArr = json_decode((string)($hojaRow['muestras_json'] ?? '[]'), true) ?: [];
        foreach ($mArr as $m) {
            $codigos[] = (string)($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? ''));
        }

        return ['id_hoja' => $idHoja, 'codigos' => $codigos];
    }

    public function testClasificacionExactaColumnasFormatoConcreto(): void {
        $info = obtenerCamposRecepcionParaDetalle($this->db, $this->idDetalleConcreto1);

        $recNombres = array_column($info['campos_recepcion'], 'nombre');
        $tecNombres = $info['campos_tecnico'];

        // Casillas "LLENA RECEPCIÓN" en RT-FM-13 (Sección 5 Propuesta)
        $this->assertContains('Nombre muestra', $recNombres);
        $this->assertContains('Reven. (in)', $recNombres);
        $this->assertContains('Temp. (°C)', $recNombres);
        $this->assertContains('Diseño de concreto (lb/in²)', $recNombres);
        $this->assertContains('Fecha de Fabricación', $recNombres);
        $this->assertNotContains('Fecha de Ensayo', $recNombres, 'Fecha de Ensayo la genera el técnico al ensayar/romper.');
        $this->assertNotContains('Edad (Días)', $recNombres, 'Edad (Días) es calculada al registrar la fecha real de ensayo.');
        $this->assertNotContains('Reven. (cm)', $recNombres, 'Reven. (cm) es calculado automáticamente a partir de Reven. (in).');

        // Casillas de Técnico / Calculadas / Laboratorio en Operaciones
        $this->assertContains('Área (in²)', $tecNombres);
        $this->assertContains('Reven. (cm)', $tecNombres);
        $this->assertContains('Carga (lb)', $tecNombres);
        $this->assertContains('Peso Volumétrico (kg/m³)', $tecNombres);
        $this->assertContains('R. Compresión (lb/in²)', $tecNombres);
        $this->assertContains('R. Compresión (kg/cm²)', $tecNombres);
        $this->assertContains('Fecha de Ensayo', $tecNombres);
        $this->assertContains('Edad (Días)', $tecNombres);

        // Clasificación de origen exacto según Tabla de Sección 5
        $this->assertSame('identidad', clasificarOrigenColumnaMatriz('Código laboratorio', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('recepcion', clasificarOrigenColumnaMatriz('Nombre muestra', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('recepcion', clasificarOrigenColumnaMatriz('Reven. (in)', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('calculado', clasificarOrigenColumnaMatriz('Reven. (cm)', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('calculado', clasificarOrigenColumnaMatriz('Área (in²)', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('calculado', clasificarOrigenColumnaMatriz('Edad (Días)', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('laboratorio', clasificarOrigenColumnaMatriz('Carga (lb)', 'formato_de_resistencia_de_concreto.md'));
        $this->assertSame('laboratorio', clasificarOrigenColumnaMatriz('Fecha de Ensayo', 'formato_de_resistencia_de_concreto.md'));

        // Sin traslape entre roles
        $this->assertEmpty(array_intersect($recNombres, $tecNombres));
    }

    public function testDosRtfm13ConNMuestrasIndependientesEnMismaOS(): void {
        // Hoja 1 con 3 muestras, Hoja 2 con 2 muestras en la misma O/S
        $creada1 = $this->crearHojaRtfm13ParaDetalle($this->idDetalleConcreto1, 3, 'MCT-A');
        $creada2 = $this->crearHojaRtfm13ParaDetalle($this->idDetalleConcreto2, 2, 'MCT-B');
        $idHoja1 = $creada1['id_hoja'];
        $idHoja2 = $creada2['id_hoja'];
        $c1 = $creada1['codigos'];
        $c2 = $creada2['codigos'];
        $this->assertCount(3, $c1);
        $this->assertCount(2, $c2);

        // Consultar vía AJAX la Hoja 1: debe traer sus 3 muestras y pestañas de ambas hojas
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_hoja' => $idHoja1];
        $respAjax1 = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->datosRecepcionAjax(new Peticion(), $respAjax1);

        $this->assertSame(200, $respAjax1->codigoRespuesta);
        $this->assertSame('success', $respAjax1->jsonRespuesta['status']);
        $this->assertCount(3, $respAjax1->jsonRespuesta['muestras']);
        $this->assertCount(2, $respAjax1->jsonRespuesta['todas_las_hojas']);

        // Guardar datos de Recepción para las 3 muestras de Hoja 1 (dejando Reven. cm y Edad vacíos para verificar cálculo automático)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $payloadHoja1 = [
            $c1[0] => [
                'Reven. (in)' => '4',
                'Temp. (°C)' => '27.5',
                'Diseño de concreto (lb/in²)' => '3500',
                'Fecha de Fabricación' => '2026-11-01',
                'Fecha de Ensayo' => '2026-11-08',
            ],
            $c1[1] => [
                'Reven. (in)' => '5',
                'Temp. (°C)' => '28.0',
                'Diseño de concreto (lb/in²)' => '3500',
                'Fecha de Fabricación' => '2026-11-01',
                'Fecha de Ensayo' => '2026-11-15',
            ],
            $c1[2] => [
                'Reven. (in)' => '4.5',
                'Temp. (°C)' => '27.0',
                'Diseño de concreto (lb/in²)' => '4000',
                'Fecha de Fabricación' => '2026-11-01',
                'Fecha de Ensayo' => '2026-11-29',
            ],
        ];

        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja1,
            'muestras_recepcion_json' => json_encode($payloadHoja1, JSON_UNESCAPED_UNICODE),
        ];
        $respSave1 = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->guardarDatosRecepcion(new Peticion(), $respSave1);
        $this->assertSame(200, $respSave1->codigoRespuesta);
        $this->assertSame(3, $respSave1->jsonRespuesta['muestras_con_datos']);

        // Guardar datos distintos en Hoja 2 (sus 2 muestras)
        $payloadHoja2 = [
            $c2[0] => [
                'Reven. (in)' => '3',
                'Temp. (°C)' => '25.0',
                'Diseño de concreto (lb/in²)' => '3000',
                'Fecha de Fabricación' => '2026-11-05',
                'Fecha de Ensayo' => '2026-11-12',
            ],
            $c2[1] => [
                'Reven. (in)' => '3.5',
                'Temp. (°C)' => '25.5',
                'Diseño de concreto (lb/in²)' => '3000',
                'Fecha de Fabricación' => '2026-11-05',
                'Edad (Días)' => '28',
            ],
        ];
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja2,
            'muestras_recepcion_json' => json_encode($payloadHoja2, JSON_UNESCAPED_UNICODE),
        ];
        $respSave2 = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->guardarDatosRecepcion(new Peticion(), $respSave2);
        $this->assertSame(200, $respSave2->codigoRespuesta);
        $this->assertSame(2, $respSave2->jsonRespuesta['muestras_con_datos']);

        // Verificar independencia y derivaciones en BD
        $mapa1 = construirMapaDatosRecepcionPorHoja($this->db, $idHoja1);
        $k10 = strtoupper($c1[0]);
        $k11 = strtoupper($c1[1]);
        $k12 = strtoupper($c1[2]);
        $k20 = strtoupper($c2[0]);
        $k21 = strtoupper($c2[1]);
        $this->assertSame('10', $mapa1[$k10]['Reven. (cm)']);
        $this->assertSame('7', $mapa1[$k10]['Edad (Días)']);
        $this->assertSame('14', $mapa1[$k11]['Edad (Días)']);
        $this->assertSame('28', $mapa1[$k12]['Edad (Días)']);

        $mapa2 = construirMapaDatosRecepcionPorHoja($this->db, $idHoja2);
        $this->assertSame('3000', $mapa2[$k20]['Diseño de concreto (lb/in²)']);
        $this->assertSame('2026-12-03', $mapa2[$k21]['Fecha de Ensayo']);
        $this->assertArrayNotHasKey($k20, $mapa1);

        // CRÍTICO: No debe pre-marcar cotizacion_detalles.resultados_json como "en_revision" antes de Operaciones
        $resDet1 = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$this->idDetalleConcreto1}")->fetchColumn();
        $this->assertEmpty($resDet1);
        $revInfo = obtenerEstadoRevisionMatriz($resDet1 ?: null);
        $this->assertSame('pendiente', $revInfo['estado']);
        $this->assertFalse($revInfo['tiene_resultados']);

        // Verificar que re-guardar detalles con PlantillaModelo conserva datos_recepcion_json
        $plantillaModelo = new PlantillaModelo();
        $detallesExistentes = $plantillaModelo->obtenerDetallesPorHoja($idHoja1);
        $this->assertTrue($plantillaModelo->guardarDetallesMuestrasHoja($idHoja1, $detallesExistentes, (int)$_SESSION['usuario_id']));
        $mapa1Post = construirMapaDatosRecepcionPorHoja($this->db, $idHoja1);
        $this->assertSame('3500', $mapa1Post[$k10]['Diseño de concreto (lb/in²)']);
    }

    public function testOperacionesPrehidrataRecepcionYGuardaMedicionesTecnico(): void {
        $creada = $this->crearHojaRtfm13ParaDetalle($this->idDetalleConcreto1, 2, 'MCT-OP');
        $idHoja1 = $creada['id_hoja'];
        $cods = $creada['codigos'];
        $this->assertCount(2, $cods);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja1,
            'muestras_recepcion_json' => json_encode([
                $cods[0] => [
                    'Reven. (in)' => '4',
                    'Temp. (°C)' => '26.4',
                    'Diseño de concreto (lb/in²)' => '4000',
                    'Fecha de Fabricación' => '2026-10-15',
                    'Fecha de Ensayo' => '2026-11-12',
                ],
                $cods[1] => [
                    'Reven. (in)' => '4',
                    'Temp. (°C)' => '26.6',
                    'Diseño de concreto (lb/in²)' => '4000',
                    'Fecha de Fabricación' => '2026-10-15',
                    'Fecha de Ensayo' => '2026-11-12',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $respSave = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->guardarDatosRecepcion(new Peticion(), $respSave);
        $this->assertSame(200, $respSave->codigoRespuesta);

        // 1. Verificar que capturaMatrizProducto entrega a la vista MUESTRAS_SETEADAS con datos_recepcion y clasificación
        $opCtrlSpy = new class extends OperacionesControlador {
            public array $captura = [];
            public function renderizar(string $vista, array $datos = []): void {
                $this->captura = ['vista' => $vista, 'datos' => $datos];
            }
        };

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_detalle' => $this->idDetalleConcreto1];
        $respOp = new TestRespuestaRecepcionVsTecnico();
        $opCtrlSpy->capturaMatrizProducto(new Peticion(), $respOp);

        $this->assertSame('', $respOp->urlRedirigida, 'No debe redirigir: ' . ($_SESSION['error'] ?? ''));
        $this->assertSame('operaciones/vistas/captura_matriz', $opCtrlSpy->captura['vista']);
        $this->assertTrue($opCtrlSpy->captura['datos']['tieneCamposRecepcion']);
        $this->assertSame(2, $opCtrlSpy->captura['datos']['muestrasRecepcionCompletas']);
        $this->assertSame('4000', $opCtrlSpy->captura['datos']['muestrasSeteadas'][0]['datos_recepcion']['Diseño de concreto (lb/in²)']);
        $this->assertSame('28', $opCtrlSpy->captura['datos']['muestrasSeteadas'][0]['datos_recepcion']['Edad (Días)']);
        $this->assertSame('recepcion', $opCtrlSpy->captura['datos']['clasificacionColumnas']['Reven. (in)']);
        $this->assertSame('tecnico', $opCtrlSpy->captura['datos']['clasificacionColumnas']['Carga (lb)']);

        // 2. Técnico guarda mediciones en Operaciones (aunque vengan vacías las casillas de recepción en el POST, backend respalda con RT-FM-13)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $this->idDetalleConcreto1,
            'resultados_json' => json_encode([
                [
                    'Código laboratorio' => $cods[0],
                    'Nombre muestra' => 'Cilindro 1',
                    'Área (in²)' => '28.27',
                    'Carga (lb)' => '125000',
                    'Peso Volumétrico (kg/m³)' => '2380',
                    'R. Compresión (lb/in²)' => '4422',
                    'R. Compresión (kg/cm²)' => '311',
                ],
                [
                    'Código laboratorio' => $cods[1],
                    'Nombre muestra' => 'Cilindro 2',
                    'Área (in²)' => '28.27',
                    'Carga (lb)' => '128000',
                    'Peso Volumétrico (kg/m³)' => '2390',
                    'R. Compresión (lb/in²)' => '4528',
                    'R. Compresión (kg/cm²)' => '318',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];

        $respMat = new TestRespuestaRecepcionVsTecnico();
        $opCtrlSpy->guardarMatrizProductoPOST(new Peticion(), $respMat);

        $jsonGuardado = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$this->idDetalleConcreto1}")->fetchColumn();
        $dec = json_decode((string)$jsonGuardado, true);
        $this->assertNotEmpty($dec['filas']);
        $this->assertSame('4000', $dec['filas'][0]['Diseño de concreto (lb/in²)']);
        $this->assertSame('28', $dec['filas'][0]['Edad (Días)']);
        $this->assertSame('4422', $dec['filas'][0]['R. Compresión (lb/in²)']);

        // 3. Si Recepción corrige Temp. (°C) en O/S después de que Operaciones creó la matriz, se sincroniza
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja1,
            'muestras_recepcion_json' => json_encode([
                $cods[0] => [
                    'Reven. (in)' => '4',
                    'Temp. (°C)' => '29.1',
                    'Diseño de concreto (lb/in²)' => '4000',
                    'Fecha de Fabricación' => '2026-10-15',
                    'Fecha de Ensayo' => '2026-11-12',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $this->hsControlador->guardarDatosRecepcion(new Peticion(), new TestRespuestaRecepcionVsTecnico());
        $jsonSync = $this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$this->idDetalleConcreto1}")->fetchColumn();
        $decSync = json_decode((string)$jsonSync, true);
        $this->assertSame('29.1', $decSync['filas'][0]['Temp. (°C)']);
        $this->assertSame('4422', $decSync['filas'][0]['R. Compresión (lb/in²)']);
    }

    public function testOrdenServicioModeloReportaProgresoRecepcionPorHoja(): void {
        $creada = $this->crearHojaRtfm13ParaDetalle($this->idDetalleConcreto1, 2, 'MCT-MOD');
        $idHoja1 = $creada['id_hoja'];
        $cods = $creada['codigos'];

        $ordenes = $this->osModelo->obtenerTodas();
        $encontrada = null;
        foreach ($ordenes as $os) {
            if ((int)$os['id'] === $this->idOS) {
                $encontrada = $os;
                break;
            }
        }
        $this->assertNotNull($encontrada);
        $this->assertNotEmpty($encontrada['hojas_servicio']);
        $h0 = $encontrada['hojas_servicio'][0];
        $this->assertSame($idHoja1, (int)$h0['id']);
        $this->assertTrue($h0['requiere_datos_recepcion']);
        $this->assertSame(0, (int)$h0['recepcion_muestras_completas']);

        // Capturar datos para 1 de 2 muestras y verificar conteo
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja1,
            'muestras_recepcion_json' => json_encode([
                $cods[0] => ['Reven. (in)' => '3.5', 'Temp. (°C)' => '26'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $this->hsControlador->guardarDatosRecepcion(new Peticion(), new TestRespuestaRecepcionVsTecnico());

        $ordenesDespues = $this->osModelo->obtenerTodas();
        foreach ($ordenesDespues as $os) {
            if ((int)$os['id'] === $this->idOS) {
                $this->assertSame(1, (int)$os['hojas_servicio'][0]['recepcion_muestras_completas']);
            }
        }
    }

    public function testMaquinaEtapasRecepcionYClampingFechaEmisionInforme(): void {
        $creada = $this->crearHojaRtfm13ParaDetalle($this->idDetalleConcreto1, 2, 'MCT-ETP');
        $idHoja = $creada['id_hoja'];
        $cods = $creada['codigos'];

        // 1. Validar rechazo 422 si faltan campos obligatorios (Fecha de Fabricación) sin forzar_urgente
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja,
            'muestras_recepcion_json' => json_encode([
                $cods[0] => ['Nombre muestra' => 'Viga V1'],
                $cods[1] => ['Nombre muestra' => 'Viga V2'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $respIncompleta = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->marcarRecepcionCompleta(new Peticion(), $respIncompleta);
        $this->assertSame(422, $respIncompleta->codigoRespuesta);
        $this->assertStringContainsString('Fecha de Fabricación', $respIncompleta->jsonRespuesta['message'] ?? '');

        // 2. Completar Fecha de Fabricación y marcar recepción completa -> 200 OK y recepcion_completa = 1
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja,
            'muestras_recepcion_json' => json_encode([
                $cods[0] => ['Nombre muestra' => 'Viga V1', 'Reven. (in)' => '4', 'Temp. (°C)' => '27', 'Diseño de concreto (lb/in²)' => '3500', 'Fecha de Fabricación' => '2026-11-01'],
                $cods[1] => ['Nombre muestra' => 'Viga V2', 'Reven. (in)' => '4', 'Temp. (°C)' => '27', 'Diseño de concreto (lb/in²)' => '3500', 'Fecha de Fabricación' => '2026-11-01'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $respCompleta = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->marcarRecepcionCompleta(new Peticion(), $respCompleta);
        $this->assertSame(200, $respCompleta->codigoRespuesta);
        $this->assertSame('recepcion_completa', $respCompleta->jsonRespuesta['etapa_flujo']);

        // Verificar que la matriz inicializada NO tiene 14.137
        $resMatJson = (string)$this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$this->idDetalleConcreto1}")->fetchColumn();
        $this->assertStringNotContainsString('14.137', $resMatJson);

        // 3. Reabrir recepción como supervisor con motivo obligatorio
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja,
            'motivo_reapertura' => 'Corrección solicitada de diseño de mezcla en recepción',
        ];
        $respReabrir = new TestRespuestaRecepcionVsTecnico();
        $this->hsControlador->reabrirRecepcion(new Peticion(), $respReabrir);
        $this->assertSame(200, $respReabrir->codigoRespuesta);
        $this->assertSame('recepcion_borrador', $respReabrir->jsonRespuesta['etapa_flujo']);
        $this->assertSame(0, $respReabrir->jsonRespuesta['recepcion_completa']);

        // 4. Hallazgo #3: Fecha de emisión en resolverMetadatosEnsayo nunca anterior a fecha de ejecución
        $meta = resolverMetadatosEnsayo([
            'fecha_emision' => '2026-11-09',
            'fecha_ejecucion' => '2026-11-10',
            'resultados_json' => json_encode([
                'filas' => [
                    ['Código laboratorio' => $cods[0], 'Fecha de Ensayo' => '2026-11-12'],
                ],
            ]),
        ]);
        $this->assertSame('2026-11-12', $meta['fecha_emision']);
    }
}
