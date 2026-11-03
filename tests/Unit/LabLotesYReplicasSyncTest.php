<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Modulos\Operaciones\Controladores\LaboratorioControlador;
use PDO;

require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
require_once dirname(__DIR__, 2) . '/database/migrations/031_lab_lotes_y_replicas_sync.php';

class LabLotesYReplicasSyncTest extends TestCase
{
    private PDO $db;
    private array $idsCreados = [
        'recepcion_muestras' => [],
        'hojas_solicitud' => [],
        'ordenes_servicio' => [],
        'cotizacion_detalles' => [],
        'cotizaciones' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        unset($_SESSION['error'], $_SESSION['exito']);
        $this->db = Conexion::obtenerInstancia();
        ejecutarMigracion031($this->db);
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION) && is_array($_SESSION)) {
            unset($_SESSION['error'], $_SESSION['exito']);
        }
        foreach ($this->idsCreados['recepcion_muestras'] as $rmId) {
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = ?)")->execute([$rmId]);
            $this->db->prepare("DELETE FROM plan_rotura_asignaciones WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = ?)")->execute([$rmId]);
            $this->db->prepare("DELETE FROM plan_rotura_edades WHERE plan_rotura_id IN (SELECT id FROM planes_rotura WHERE muestra_id = ?)")->execute([$rmId]);
            $this->db->prepare("DELETE FROM planes_rotura WHERE muestra_id = ?")->execute([$rmId]);
            $this->db->prepare("DELETE FROM especimenes WHERE muestra_id = ?")->execute([$rmId]);
            $this->db->prepare("DELETE FROM ensayo_edades WHERE id_lote IN (SELECT id FROM lotes_muestras WHERE id_recepcion = ?)")->execute([$rmId]);
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id_recepcion = ?")->execute([$rmId]);
        }
        foreach ($this->idsCreados['hojas_solicitud'] as $idHoja) {
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_hoja = ?")->execute([$idHoja]);
            $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = ?")->execute([$idHoja]);
            $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = ?")->execute([$idHoja]);
        }
        foreach ($this->idsCreados['ordenes_servicio'] as $idOs) {
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_os = ?")->execute([$idOs]);
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$idOs]);
        }
        foreach ($this->idsCreados['cotizacion_detalles'] as $idDet) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id = ?")->execute([$idDet]);
        }
        foreach ($this->idsCreados['cotizaciones'] as $idCot) {
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$idCot]);
        }
        parent::tearDown();
    }

    private function crearFixtureConcretoLote(): array
    {
        $idCliente = (int)($this->db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
        $idUsuario = (int)($this->db->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);

        $prodConcreto = (int)($this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%C39%' OR nombre_comercial LIKE '%Compresi%' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 2);
        $plantillaConcreto = (int)($this->db->query("SELECT id FROM plantillas WHERE norma LIKE '%C39%' OR nombre LIKE '%Compresi%' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);

        $uniq = uniqid('LOTE31_');
        $this->db->prepare("
            INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total, fecha_creacion)
            VALUES (?, ?, ?, 'Ing. Residente', 'Proyecto Lote Concreto', 'Contado', 'Aprobada por Cliente', 200, 200, NOW())
        ")->execute(['COT-' . $uniq, $idCliente, $idUsuario]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsCreados['cotizaciones'][] = $idCot;

        $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, 'Resistencia a la Compresión ASTM C39', 'CYCSA-RT-FM-22 A', 'ASTM C39', 2, 100, 200)
        ")->execute([$idCot, $prodConcreto]);
        $idDetConcreto = (int)$this->db->lastInsertId();
        $this->idsCreados['cotizacion_detalles'][] = $idDetConcreto;

        $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, estado, requiere_muestreo)
            VALUES (?, ?, ?, CURDATE(), 'Estado 1: Recepcion', 0)
        ")->execute(['OS-' . $uniq, $idCot, $idCliente]);
        $idOs = (int)$this->db->lastInsertId();
        $this->idsCreados['ordenes_servicio'][] = $idOs;

        $muestrasDecl = [
            ['nombre_muestra' => 'C39-M1', 'descripcion' => 'Cilindro Eje A', 'plantilla_id' => $plantillaConcreto],
            ['nombre_muestra' => 'C39-M2', 'descripcion' => 'Cilindro Eje B', 'plantilla_id' => $plantillaConcreto],
        ];
        $this->db->prepare("
            INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, codigo_documento, numero_registro, naturaleza_muestra, muestras_json)
            VALUES (?, ?, ?, ?, '88001-02', 'Concreto Hidráulico', ?)
        ")->execute([$idOs, $idDetConcreto, $prodConcreto, 'HS-' . $uniq, json_encode($muestrasDecl)]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->idsCreados['hojas_solicitud'][] = $idHoja;

        $this->db->prepare("
            INSERT INTO hojas_solicitud_detalles (id_hoja_solicitud, codigo_muestra, ensayo_id, plantilla_id, orden)
            VALUES (?, 'C39-M1', ?, ?, 1), (?, 'C39-M2', ?, ?, 2)
        ")->execute([$idHoja, $prodConcreto, $plantillaConcreto, $idHoja, $prodConcreto, $plantillaConcreto]);

        return [
            'id_os' => $idOs,
            'id_hoja' => $idHoja,
            'id_detalle' => $idDetConcreto,
            'id_producto' => $prodConcreto,
            'plantilla_id' => $plantillaConcreto,
            'id_usuario' => $idUsuario,
        ];
    }

    /**
     * Test 1 & 5: Lote C39 con 2 muestras respeta orden_en_lote, plantilla_id y Bandeja Anónima expone id_hoja y total_muestras_lote.
     */
    public function testLoteDosMuestrasRespetaSecuenciaYPlantillaEnBandejaAnonima(): void
    {
        $fx = $this->crearFixtureConcretoLote();
        $opModelo = new OperacionModelo();

        $ok = $opModelo->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote C39',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 1,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M1',
                    'codigo_campo' => 'C39-M1',
                    'nombre_lote' => 'Cilindro Eje A',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 2,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M2',
                    'codigo_campo' => 'C39-M2',
                    'nombre_lote' => 'Cilindro Eje B',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
            ]),
        ]);
        $this->assertTrue($ok, 'La recepción del lote de 2 muestras debe ser exitosa.');

        $stmt = $this->db->prepare("SELECT * FROM v_lab_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, muestra_id ASC");
        $stmt->execute(['id_hoja' => $fx['id_hoja']]);
        $filasLote = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filasLote as $r) {
            $this->idsCreados['recepcion_muestras'][] = (int)$r['muestra_id'];
        }

        $this->assertCount(2, $filasLote, 'El lote debe contener exactamente las 2 muestras.');
        $this->assertSame('C39-M1', $filasLote[0]['codigo_campo']);
        $this->assertSame('C39-M2', $filasLote[1]['codigo_campo']);
        $this->assertSame(1, (int)$filasLote[0]['orden_en_lote']);
        $this->assertSame(2, (int)$filasLote[1]['orden_en_lote']);
        $this->assertSame(2, (int)$filasLote[0]['total_muestras_lote']);
        $this->assertSame($fx['plantilla_id'], (int)$filasLote[0]['plantilla_id']);
        $this->assertNotEmpty($filasLote[0]['plantilla_nombre'], 'La vista anónima debe exponer el nombre real de la plantilla y no quedar NULL.');
    }

    /**
     * Test 2: Réplica CR marcada en matriz se sincroniza en el lote de Lab justo después de su muestra origen.
     */
    public function testReplicaCrEnMatrizSeSincronizaConLoteLabEnOrden(): void
    {
        $fx = $this->crearFixtureConcretoLote();
        $opModelo = new OperacionModelo();

        $opModelo->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote C39',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 1,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M1',
                    'codigo_campo' => 'C39-M1',
                    'nombre_lote' => 'Cilindro Eje A',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 2,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M2',
                    'codigo_campo' => 'C39-M2',
                    'nombre_lote' => 'Cilindro Eje B',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, id ASC");
        $stmt->execute(['id_hoja' => $fx['id_hoja']]);
        $muestrasOrig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $muestrasOrig);

        $cod1 = $muestrasOrig[0]['codigo_muestra'];
        $cod2 = $muestrasOrig[1]['codigo_muestra'];

        // Simular filas de matriz donde la primera muestra tiene réplica -CR
        $filasMatriz = [
            ['Muestra' => $cod1, 'Edad (Días)' => '14', 'Temp. (°C)' => '22.5', 'Área (in²)' => '28.350'],
            ['Muestra' => $cod1 . '-CR', '_es_replica' => '1', '_muestra_origen' => $cod1, 'Edad (Días)' => '14', 'Temp. (°C)' => '22.5', 'Área (in²)' => '28.350'],
            ['Muestra' => $cod2, 'Edad (Días)' => '28', 'Temp. (°C)' => '23.0', 'Área (in²)' => '28.410'],
        ];

        $insertadas = sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMatriz);
        $this->assertSame(1, $insertadas, 'Debe insertarse exactamente 1 réplica -CR en recepcion_muestras.');

        // Segunda llamada debe ser idempotente (0 insertadas adicionales)
        $insertadas2 = sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMatriz);
        $this->assertSame(0, $insertadas2, 'La sincronización de réplicas CR debe ser idempotente.');

        $stmtLote = $this->db->prepare("SELECT * FROM v_lab_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, muestra_id ASC");
        $stmtLote->execute(['id_hoja' => $fx['id_hoja']]);
        $loteCompleto = $stmtLote->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loteCompleto as $r) {
            $this->idsCreados['recepcion_muestras'][] = (int)$r['muestra_id'];
        }

        $this->assertCount(3, $loteCompleto, 'El lote en Lab debe contener ahora 3 filas (M1, M1-CR, M2).');
        $this->assertSame($cod1, $loteCompleto[0]['codigo_lab']);
        $this->assertSame($cod1 . '-CR', $loteCompleto[1]['codigo_lab']);
        $this->assertSame(1, (int)$loteCompleto[1]['is_qa_qc']);
        $this->assertSame('-CR', $loteCompleto[1]['replica_codigo']);
        $this->assertSame($cod2, $loteCompleto[2]['codigo_lab']);
        $this->assertSame([1, 2, 3], array_map(fn($x) => (int)$x['orden_en_lote'], $loteCompleto));
    }

    /**
     * Test 3: Plan de roturas por lote aplica las edades a todas las muestras del lote y permite override individual.
     */
    public function testGuardarPlanRoturaLoteYOverrideIndividual(): void
    {
        $fx = $this->crearFixtureConcretoLote();
        $opModelo = new OperacionModelo();

        $opModelo->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote C39',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 1,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M1',
                    'codigo_campo' => 'C39-M1',
                    'nombre_lote' => 'Cilindro Eje A',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 2,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M2',
                    'codigo_campo' => 'C39-M2',
                    'nombre_lote' => 'Cilindro Eje B',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, id ASC");
        $stmt->execute(['id_hoja' => $fx['id_hoja']]);
        $mIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        foreach ($mIds as $id) {
            $this->idsCreados['recepcion_muestras'][] = $id;
        }

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = 'token_test_lote_031';
        $_SESSION['usuario_permisos'] = [
            'laboratorio' => ['crear_editar' => 1, 'ver' => 1],
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_POST = [
            'csrf_token' => 'token_test_lote_031',
            'id_hoja' => $fx['id_hoja'],
            'edad_dias' => [7, 28],
            'cantidad' => [1, 2],
        ];

        $peticion = new Peticion();
        $respuesta = new class extends Respuesta {
            public array $jsonPayload = [];
            public int $statusCode = 200;
            public function enviarJson(array $datos, int $codigo = 200): void {
                $this->jsonPayload = $datos;
                $this->statusCode = $codigo;
            }
            public function redirigir(string $url): void {}
        };

        $ctrl = new LaboratorioControlador();
        $ctrl->guardarPlanRoturaLote($peticion, $respuesta);

        $this->assertSame('success', $respuesta->jsonPayload['status'] ?? '');
        $this->assertSame(2, $respuesta->jsonPayload['actualizadas'] ?? 0);

        $roturasModelo = new RoturasModelo();
        foreach ($mIds as $mId) {
            $det = $roturasModelo->obtenerDetalleMuestraAnonima($mId);
            $edadesAsignadas = array_map(fn($e) => (int)$e['edad_dias'], $det['edades']);
            $this->assertSame([7, 28], $edadesAsignadas);
            $this->assertCount(3, $det['especimenes'], 'Cada muestra del lote debe tener 3 especímenes (1 a 7d + 2 a 28d).');
        }

        // Override individual para la muestra 2 (ej. solo 14 días)
        $resOverride = $roturasModelo->guardarPlanRotura($mIds[1], null, [['edad_dias' => 14, 'cantidad' => 2]], $fx['id_usuario']);
        $this->assertTrue($resOverride['exito']);

        $det1 = $roturasModelo->obtenerDetalleMuestraAnonima($mIds[0]);
        $det2 = $roturasModelo->obtenerDetalleMuestraAnonima($mIds[1]);
        $this->assertSame([7, 28], array_map(fn($e) => (int)$e['edad_dias'], $det1['edades']));
        $this->assertSame([14], array_map(fn($e) => (int)$e['edad_dias'], $det2['edades']));
    }

    /**
     * Test 4: Fuente de verdad desde la matriz (Edad, Temperatura, Área Transversal, Diámetros, Masa, Carga).
     */
    public function testObtenerDatosMuestraDesdeMatrizExtraeEdadTempAreaYDimensiones(): void
    {
        $filasMatriz = [
            [
                'Muestra' => 'MS-0003-26',
                'Edad (Días)' => '28',
                'Temp. (°C)' => '24.3',
                'Área (in²)' => '28.512',
                'Diámetro 1 (cm)' => '15.24',
                'Diámetro 2 (cm)' => '15.26',
                'Longitud 1 (cm)' => '30.48',
                'Masa (kg)' => '12.91',
                'Carga Máx. (lbf)' => '92400',
            ],
            [
                'Muestra' => 'MS-0003-26-CR',
                '_es_replica' => '1',
                '_muestra_origen' => 'MS-0003-26',
                'Edad (Días)' => '28',
                'Temp. (°C)' => '24.3',
                'Área (in²)' => '28.490',
                'Carga Máx. (lbf)' => '91850',
            ],
        ];

        $d1 = obtenerDatosMuestraDesdeMatriz($filasMatriz, 'MS-0003-26');
        $this->assertTrue($d1['encontrada']);
        $this->assertSame(28, $d1['edad_dias']);
        $this->assertSame(24.3, $d1['temperatura_c']);
        $this->assertSame(28.512, $d1['area']);
        $this->assertSame(15.24, $d1['diametro_1']);
        $this->assertSame(15.26, $d1['diametro_2']);
        $this->assertSame(30.48, $d1['largo_1']);
        $this->assertSame(12.91, $d1['masa_kg']);
        $this->assertSame(92400.0, $d1['carga']);
        $this->assertFalse($d1['es_replica']);

        $dCr = obtenerDatosMuestraDesdeMatriz($filasMatriz, 'MS-0003-26-CR');
        $this->assertTrue($dCr['encontrada']);
        $this->assertSame(28, $dCr['edad_dias']);
        $this->assertSame(24.3, $dCr['temperatura_c']);
        $this->assertSame(28.49, $dCr['area']);
        $this->assertSame(91850.0, $dCr['carga']);
        $this->assertTrue($dCr['es_replica']);
        $this->assertSame('MS-0003-26', $dCr['muestra_origen']);
    }

    /**
     * Test 5: El controlador detalleLote sincroniza réplicas desde resultados_json, lista las N muestras + réplica CR
     * en orden de matriz, precarga temperatura/área/edad y mantiene el resguardo de imparcialidad ISO 17025.
     */
    public function testVistaDetalleLoteRenderizaSecuenciaReplicasYDatosMatrizSinFiltrarCliente(): void
    {
        $fx = $this->crearFixtureConcretoLote();
        $opModelo = new OperacionModelo();

        $opModelo->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote C39',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 1,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M1',
                    'codigo_campo' => 'C39-M1',
                    'nombre_lote' => 'Cilindro Eje A',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
                [
                    'id_detalle_cotizacion' => $fx['id_detalle'],
                    'plantilla_id' => $fx['plantilla_id'],
                    'orden_en_lote' => 2,
                    'tipo_muestra' => 'Laboratorio',
                    'id_cilindro' => 'C39-M2',
                    'codigo_campo' => 'C39-M2',
                    'nombre_lote' => 'Cilindro Eje B',
                    'fecha_moldeo' => date('Y-m-d'),
                ],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, id ASC");
        $stmt->execute(['id_hoja' => $fx['id_hoja']]);
        $muestrasOrig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cod1 = $muestrasOrig[0]['codigo_muestra'];
        $cod2 = $muestrasOrig[1]['codigo_muestra'];

        // Guardar resultados_json en cotizacion_detalles con Réplica CR y datos técnicos
        $resultadosJson = [
            'filas' => [
                ['Código laboratorio' => $cod1, 'Edad (Días)' => '14', 'Temp. (°C)' => '23.8', 'Área (in²)' => '28.315', 'Diámetro 1 (cm)' => '15.22'],
                ['Código laboratorio' => $cod1 . '-CR', '_es_replica' => '1', '_muestra_origen' => $cod1, 'Edad (Días)' => '14', 'Temp. (°C)' => '23.8', 'Área (in²)' => '28.310'],
                ['Código laboratorio' => $cod2, 'Edad (Días)' => '28', 'Temp. (°C)' => '24.1', 'Área (in²)' => '28.420'],
            ],
        ];
        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = :rj WHERE id = :id")
                 ->execute(['rj' => json_encode($resultadosJson), 'id' => $fx['id_detalle']]);

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = [
            'laboratorio' => ['crear_editar' => 1, 'ver' => 1],
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/detalle-lote';
        $_GET = ['id_hoja' => $fx['id_hoja']];

        $peticion = new Peticion();
        $respuesta = new Respuesta();
        $ctrl = new LaboratorioControlador();

        ob_start();
        $ctrl->detalleLote($peticion, $respuesta);
        $html = (string)ob_get_clean();

        // Registrar también la réplica creada para limpieza en tearDown
        $stmtAll = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :id_hoja");
        $stmtAll->execute(['id_hoja' => $fx['id_hoja']]);
        $this->idsCreados['recepcion_muestras'] = array_map('intval', $stmtAll->fetchAll(PDO::FETCH_COLUMN));

        $this->assertStringContainsString('Hoja de Trabajo por Lote', $html);
        $this->assertStringContainsString($cod1, $html);
        $this->assertStringContainsString($cod1 . '-CR', $html);
        $this->assertStringContainsString($cod2, $html);
        $this->assertStringContainsString('Réplica Control de Calidad (-CR)', $html);
        $this->assertStringContainsString('23.8 °C', $html);
        $this->assertStringContainsString('28.315', $html);

        // Verificar orden visual en el HTML: cod1 aparece antes de cod1-CR, y cod1-CR aparece antes de cod2
        $pos1 = strpos($html, '#1 en Matriz');
        $pos2 = strpos($html, '#2 en Matriz');
        $pos3 = strpos($html, '#3 en Matriz');
        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertLessThan($pos2, $pos1);
        $this->assertLessThan($pos3, $pos2);
    }
}
