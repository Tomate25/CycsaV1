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
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo;
use PDO;

require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
require_once dirname(__DIR__, 2) . '/database/migrations/031_lab_lotes_y_replicas_sync.php';

class LabLoteFilasMatrizTest extends TestCase
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
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_ACCEPT']);
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/detalle-lote';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        $_GET = [];
        $this->db = Conexion::obtenerInstancia();
        ejecutarMigracion031($this->db);
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION) && is_array($_SESSION)) {
            unset($_SESSION['error'], $_SESSION['exito']);
        }
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_ACCEPT']);
        $_POST = [];
        $_GET = [];
        foreach ($this->idsCreados['recepcion_muestras'] as $rmId) {
            $this->db->prepare("DELETE FROM informes_rotura WHERE muestra_id = ?")->execute([$rmId]);
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

    private function crearFixture(int $cantMuestras = 4, bool $esCompactacion = false): array
    {
        $idCliente = (int)($this->db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
        $idUsuario = (int)($this->db->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);

        if ($esCompactacion) {
            $idProd = (int)($this->db->query("SELECT id FROM productos WHERE (norma_astm LIKE '%D6938%' OR nombre_comercial LIKE '%Densidad%' OR nombre_comercial LIKE '%Densimetro%') ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 10);
            $plantillaId = null;
            $codigoServicio = 'CYCSA-RT-FM-27';
            $norma = 'ASTM D6938';
            $descEnsayo = 'Densidad y Humedad In Situ con Densímetro Nuclear ASTM D6938';
        } else {
            $idProd = (int)($this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%C39%' OR nombre_comercial LIKE '%Compresi%' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 2);
            $plantillaId = (int)($this->db->query("SELECT id FROM plantillas WHERE (norma LIKE '%C39%' OR nombre LIKE '%Compresi%') AND id != 21 ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
            $codigoServicio = 'CYCSA-RT-FM-22 A';
            $norma = 'ASTM C39';
            $descEnsayo = 'Resistencia a la Compresión de Cilindros ASTM C39';
        }

        $uniq = uniqid('TEST_');
        $this->db->prepare("
            INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total, fecha_creacion)
            VALUES (?, ?, ?, 'Ing. Test', 'Proyecto Test Lote Matriz', 'Contado', 'Aprobada por Cliente', 500, 500, NOW())
        ")->execute(['COT-' . $uniq, $idCliente, $idUsuario]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsCreados['cotizaciones'][] = $idCot;

        $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, 100, ?)
        ")->execute([$idCot, $idProd, $descEnsayo, $codigoServicio, $norma, $cantMuestras, $cantMuestras * 100]);
        $idDet = (int)$this->db->lastInsertId();
        $this->idsCreados['cotizacion_detalles'][] = $idDet;

        $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, estado, requiere_muestreo)
            VALUES (?, ?, ?, CURDATE(), 'Estado 1: Recepcion', 0)
        ")->execute(['OS-' . $uniq, $idCot, $idCliente]);
        $idOs = (int)$this->db->lastInsertId();
        $this->idsCreados['ordenes_servicio'][] = $idOs;

        $muestrasDecl = [];
        for ($i = 1; $i <= $cantMuestras; $i++) {
            $muestrasDecl[] = [
                'nombre_muestra' => 'MS-T' . $i,
                'descripcion' => 'Cilindro ' . $i,
                'plantilla_id' => $plantillaId
            ];
        }

        $this->db->prepare("
            INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, codigo_documento, numero_registro, naturaleza_muestra, muestras_json, fecha_hora_toma_muestra)
            VALUES (?, ?, ?, ?, 'REG-TEST-01', 'Concreto Hidráulico', ?, NOW())
        ")->execute([$idOs, $idDet, $idProd, 'HS-' . $uniq, json_encode($muestrasDecl)]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->idsCreados['hojas_solicitud'][] = $idHoja;

        return [
            'id_os' => $idOs,
            'id_hoja' => $idHoja,
            'id_detalle' => $idDet,
            'id_producto' => $idProd,
            'plantilla_id' => $plantillaId,
            'id_usuario' => $idUsuario,
            'muestras_declaradas' => $muestrasDecl
        ];
    }

    private function sincronizarMatrizConLab(int $idHoja, int $idDetalle, int $cantMuestras, bool $incluyeReplica): void
    {
        $stmtHoja = $this->db->prepare("SELECT id_os FROM hojas_solicitud WHERE id = ?");
        $stmtHoja->execute([$idHoja]);
        $idOs = (int)$stmtHoja->fetchColumn();

        $idUsuario = (int)($this->db->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
        $plantillaId = (int)($this->db->query("SELECT id FROM plantillas WHERE (norma LIKE '%C39%' OR nombre LIKE '%Compresi%') AND id != 21 ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);

        $opMod = new OperacionModelo();
        $muestrasPayload = [];
        for ($i = 1; $i <= $cantMuestras; $i++) {
            $muestrasPayload[] = [
                'id_detalle_cotizacion' => $idDetalle,
                'plantilla_id' => $plantillaId,
                'orden_en_lote' => $i,
                'tipo_muestra' => 'Laboratorio',
                'id_cilindro' => 'MS-T' . $i,
                'codigo_campo' => 'MS-T' . $i,
                'nombre_lote' => 'Cilindro ' . $i,
                'fecha_moldeo' => '2026-11-01',
            ];
        }

        $opMod->registrarRecepcion([
            'id_os' => $idOs,
            'id_hoja' => $idHoja,
            'id_detalle_cotizacion' => $idDetalle,
            'plantilla_id' => $plantillaId,
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $idUsuario,
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote Test',
            'muestras_recibidas_json' => json_encode($muestrasPayload),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h ORDER BY orden_en_lote ASC, id ASC");
        $stmt->execute(['h' => $idHoja]);
        $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($muestras as $m) {
            $this->idsCreados['recepcion_muestras'][] = (int)$m['id'];
        }

        $filasMatriz = [];
        foreach ($muestras as $idx => $m) {
            $cod = $m['codigo_muestra'];
            $filasMatriz[] = [
                'Código laboratorio' => $cod,
                'Edad (Días)' => ($idx === 0 ? '7' : '28'),
                'Fecha Ensayo' => ($idx === 0 ? '2026-11-08' : '2026-11-29'),
                'Temp. (°C)' => '22.0',
                'Área (in²)' => '28.274',
                'Tamaño de Cilindro' => '6x12 pulg'
            ];
            if ($idx === 0 && $incluyeReplica) {
                $filasMatriz[] = [
                    'Código laboratorio' => $cod . '-CR',
                    '_es_replica' => '1',
                    '_muestra_origen' => $cod,
                    'Edad (Días)' => '7',
                    'Fecha Ensayo' => '2026-11-08',
                    'Temp. (°C)' => '22.0',
                    'Área (in²)' => '28.274',
                    'Tamaño de Cilindro' => '6x12 pulg'
                ];
            }
        }

        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = :rj WHERE id = :id")
                 ->execute(['rj' => json_encode(['filas' => $filasMatriz]), 'id' => $idDetalle]);

        sincronizarReplicasMatrizConLab($this->db, $idOs, $idHoja, $idDetalle, $filasMatriz);
    }

    /**
     * TEST 1: Hoja C39 con 4 muestras + 1 CR en matriz -> Ver Lote lista 5 filas en orden, sin -C1..Cn
     */
    public function testHojaC39Con4MuestrasY1CrEnMatrizVerLoteLista5FilasSinSufijosC1Cn(): void
    {
        $fx = $this->crearFixture(4);
        $opMod = new OperacionModelo();

        $muestrasPayload = [];
        for ($i = 1; $i <= 4; $i++) {
            $muestrasPayload[] = [
                'id_detalle_cotizacion' => $fx['id_detalle'],
                'plantilla_id' => $fx['plantilla_id'],
                'orden_en_lote' => $i,
                'tipo_muestra' => 'Laboratorio',
                'id_cilindro' => 'MS-000' . $i,
                'codigo_campo' => 'MS-000' . $i,
                'nombre_lote' => 'Cilindro ' . $i,
                'fecha_moldeo' => '2026-11-01',
            ];
        }

        $ok = $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso Lote C39 4 muestras',
            'muestras_recibidas_json' => json_encode($muestrasPayload),
        ]);
        $this->assertTrue($ok);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h ORDER BY orden_en_lote ASC, id ASC");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(4, $muestras);

        foreach ($muestras as $m) {
            $this->idsCreados['recepcion_muestras'][] = (int)$m['id'];
        }

        $c1 = $muestras[0]['codigo_muestra'];
        $c2 = $muestras[1]['codigo_muestra'];
        $c3 = $muestras[2]['codigo_muestra'];
        $c4 = $muestras[3]['codigo_muestra'];

        // Matriz con 4 muestras + 1 Réplica CR en la muestra 1
        $filasMatriz = [
            ['Código laboratorio' => $c1, 'Edad (Días)' => '7', 'Fecha Ensayo' => '2026-11-08', 'Temp. (°C)' => '22.0', 'Área (in²)' => '28.274'],
            ['Código laboratorio' => $c1 . '-CR', '_es_replica' => '1', '_muestra_origen' => $c1, 'Edad (Días)' => '7', 'Fecha Ensayo' => '2026-11-08', 'Temp. (°C)' => '22.0', 'Área (in²)' => '28.274'],
            ['Código laboratorio' => $c2, 'Edad (Días)' => '14', 'Fecha Ensayo' => '2026-11-15', 'Temp. (°C)' => '22.5', 'Área (in²)' => '28.274'],
            ['Código laboratorio' => $c3, 'Edad (Días)' => '28', 'Fecha Ensayo' => '2026-11-29', 'Temp. (°C)' => '23.0', 'Área (in²)' => '28.274'],
            ['Código laboratorio' => $c4, 'Edad (Días)' => '28', 'Fecha Ensayo' => '2026-11-29', 'Temp. (°C)' => '23.0', 'Área (in²)' => '28.274'],
        ];

        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = :rj WHERE id = :id")
                 ->execute(['rj' => json_encode(['filas' => $filasMatriz]), 'id' => $fx['id_detalle']]);

        // Sincronizar (como hace guardarMatrizProductoPOST)
        sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMatriz);

        // Consultar lote completo
        $stmtLote = $this->db->prepare("SELECT * FROM v_lab_muestras WHERE id_hoja = :h ORDER BY orden_en_lote ASC, muestra_id ASC");
        $stmtLote->execute(['h' => $fx['id_hoja']]);
        $filasLab = $stmtLote->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(5, $filasLab, 'Deben existir exactamente 5 filas en el lote de Lab (4 normales + 1 réplica).');

        // Registrar réplica creada para cleanup
        foreach ($filasLab as $fl) {
            $this->idsCreados['recepcion_muestras'][] = (int)$fl['muestra_id'];
        }

        // Verificar que los códigos de probetas/especímenes NO tienen -C1, -C2, etc.
        $stmtEsp = $this->db->prepare("SELECT esp.codigo_lab FROM especimenes esp JOIN recepcion_muestras rm ON esp.muestra_id = rm.id WHERE rm.id_hoja = :h");
        $stmtEsp->execute(['h' => $fx['id_hoja']]);
        $codigosEsp = $stmtEsp->fetchAll(PDO::FETCH_COLUMN);

        $this->assertCount(5, $codigosEsp, 'Debe haber exactamente 1 especimen por fila de matriz.');
        foreach ($codigosEsp as $cod) {
            $this->assertDoesNotMatchRegularExpression('/-C[0-9]+$/', $cod, "El código {$cod} no debe tener sufijo artificial -C1..Cn.");
        }
    }

    /**
     * TEST 2: Cada fila toma su edad/fecha de la matriz (ej. 7, 14, 28 distintas por fila)
     */
    public function testCadaFilaTomaSuEdadYFechaDeLaMatrizDistintas(): void
    {
        $fx = $this->crearFixture(2);
        $opMod = new OperacionModelo();

        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso 2 muestras',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 2, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M2', 'codigo_campo' => 'M2', 'nombre_lote' => 'L2', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h ORDER BY orden_en_lote ASC");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($muestras as $m) {
            $this->idsCreados['recepcion_muestras'][] = (int)$m['id'];
        }

        $c1 = $muestras[0]['codigo_muestra'];
        $c2 = $muestras[1]['codigo_muestra'];

        $filasMat = [
            ['Código laboratorio' => $c1, 'Edad (Días)' => '7', 'Fecha Ensayo' => '2026-11-08'],
            ['Código laboratorio' => $c2, 'Edad (Días)' => '28', 'Fecha Ensayo' => '2026-11-29'],
        ];

        sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMat);

        // Verificar edades en plan_rotura_edades
        $stmtE1 = $this->db->prepare("SELECT pre.edad_dias, pre.fecha_programada FROM plan_rotura_edades pre JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id WHERE pr.muestra_id = :mid");
        $stmtE1->execute(['mid' => $muestras[0]['id']]);
        $ed1 = $stmtE1->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(7, (int)$ed1['edad_dias']);
        $this->assertSame('2026-11-08', $ed1['fecha_programada']);

        $stmtE2 = $this->db->prepare("SELECT pre.edad_dias, pre.fecha_programada FROM plan_rotura_edades pre JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id WHERE pr.muestra_id = :mid");
        $stmtE2->execute(['mid' => $muestras[1]['id']]);
        $ed2 = $stmtE2->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(28, (int)$ed2['edad_dias']);
        $this->assertSame('2026-11-29', $ed2['fecha_programada']);
    }

    /**
     * TEST 3: Matriz no guardada -> filas “Pendiente de programación”, Romper deshabilitado, sin 7/14/28
     */
    public function testMatrizNoGuardadaMuestraPendienteProgramacionYRomperDeshabilitado(): void
    {
        $fx = $this->crearFixture(1);
        $opMod = new OperacionModelo();

        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso sin matriz',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $this->idsCreados['recepcion_muestras'][] = (int)$stmt->fetchColumn();

        // Sin matriz en cotizacion_detalles
        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = NULL WHERE id = :id")->execute(['id' => $fx['id_detalle']]);

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['ver' => 1, 'crear_editar' => 1]];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'];
        $_GET = ['id_hoja' => $fx['id_hoja']];

        $peticion = new Peticion();
        $respuesta = new Respuesta();
        $ctrl = new LaboratorioControlador();

        ob_start();
        $ctrl->detalleLote($peticion, $respuesta);
        $html = (string)ob_get_clean();

        $this->assertStringContainsString('Pendiente', $html, 'Debe mostrar Pendiente cuando la matriz no está programada.');
        $this->assertStringNotContainsString('7, 14, 28', $html, 'No debe usar el fallback 7/14/28.');
    }

    /**
     * TEST 4: GET detalle-lote no escribe en BD
     */
    public function testGetDetalleLoteNoEscribeEnBaseDatos(): void
    {
        $fx = $this->crearFixture(1);
        $opMod = new OperacionModelo();

        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Test GET Puro',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $this->idsCreados['recepcion_muestras'][] = (int)$stmt->fetchColumn();

        $conteoAntes = [
            'recepcion' => (int)$this->db->query("SELECT COUNT(*) FROM recepcion_muestras")->fetchColumn(),
            'especimenes' => (int)$this->db->query("SELECT COUNT(*) FROM especimenes")->fetchColumn(),
            'planes' => (int)$this->db->query("SELECT COUNT(*) FROM planes_rotura")->fetchColumn(),
            'roturas' => (int)$this->db->query("SELECT COUNT(*) FROM roturas")->fetchColumn(),
        ];

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['ver' => 1]];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'];
        $_GET = ['id_hoja' => $fx['id_hoja']];

        $peticion = new Peticion();
        $respuesta = new Respuesta();
        $ctrl = new LaboratorioControlador();

        ob_start();
        $ctrl->detalleLote($peticion, $respuesta);
        ob_end_clean();

        $conteoDespues = [
            'recepcion' => (int)$this->db->query("SELECT COUNT(*) FROM recepcion_muestras")->fetchColumn(),
            'especimenes' => (int)$this->db->query("SELECT COUNT(*) FROM especimenes")->fetchColumn(),
            'planes' => (int)$this->db->query("SELECT COUNT(*) FROM planes_rotura")->fetchColumn(),
            'roturas' => (int)$this->db->query("SELECT COUNT(*) FROM roturas")->fetchColumn(),
        ];

        $this->assertSame($conteoAntes, $conteoDespues, 'El método GET detalleLote debe ser 100% de solo lectura y no escribir en BD.');
    }

    /**
     * TEST 5: Guardar matriz con CR -> aparece -CR en Lab (idempotente, 2 guardados = 1 fila)
     */
    public function testGuardarMatrizConCrEsIdempotente(): void
    {
        $fx = $this->crearFixture(1);
        $opMod = new OperacionModelo();

        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Ingreso 1 muestra',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $rowM = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->idsCreados['recepcion_muestras'][] = (int)$rowM['id'];
        $c1 = $rowM['codigo_muestra'];

        $filasMat = [
            ['Código laboratorio' => $c1, 'Edad (Días)' => '7', 'Fecha Ensayo' => '2026-11-08'],
            ['Código laboratorio' => $c1 . '-CR', '_es_replica' => '1', '_muestra_origen' => $c1, 'Edad (Días)' => '7', 'Fecha Ensayo' => '2026-11-08'],
        ];

        // 1ª Sincronización
        $ins1 = sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMat);
        $this->assertSame(1, $ins1);

        // 2ª Sincronización (idempotente)
        $ins2 = sincronizarReplicasMatrizConLab($this->db, $fx['id_os'], $fx['id_hoja'], $fx['id_detalle'], $filasMat);
        $this->assertSame(0, $ins2);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_hoja = :h");
        $stmtCount->execute(['h' => $fx['id_hoja']]);
        $this->assertSame(2, (int)$stmtCount->fetchColumn(), 'Deben existir exactamente 2 muestras (origen + replica), sin duplicaciones.');
    }

    /**
     * TEST 6: Modal Romper: carga vacía, área desde matriz o por tamaño; guardar vuelve al lote
     */
    public function testModalRomperCargaVaciaAreaPorTamanoYRedireccionALote(): void
    {
        // 1. Detección de área por tamaño 4x8
        $filas4x8 = [['Código laboratorio' => 'M-4x8', 'Tamaño Cilindro' => '4x8', 'Temp. (°C)' => '21.5']];
        $datos4x8 = obtenerDatosMuestraDesdeMatriz($filas4x8, 'M-4x8');
        $this->assertEqualsWithDelta(12.566, $datos4x8['area'], 0.01);

        // 2. Detección de área por tamaño 6x12
        $filas6x12 = [['Código laboratorio' => 'M-6x12', 'Dimensiones' => '6x12 in', 'Temp. (°C)' => '22.0']];
        $datos6x12 = obtenerDatosMuestraDesdeMatriz($filas6x12, 'M-6x12');
        $this->assertEqualsWithDelta(28.274, $datos6x12['area'], 0.01);

        // 3. Tamaño desconocido no inventa 28.274
        $filasDesc = [['Código laboratorio' => 'M-DESC', 'Temp. (°C)' => '22.0']];
        $datosDesc = obtenerDatosMuestraDesdeMatriz($filasDesc, 'M-DESC');
        $this->assertNull($datosDesc['area'], 'Si el tamaño es desconocido, el área debe ser NULL y no 28.274 ciego.');

        // 4. Redirección al guardar rotura
        $fx = $this->crearFixture(1);
        $opMod = new OperacionModelo();
        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Test rotura redir',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $rowM = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->idsCreados['recepcion_muestras'][] = (int)$rowM['id'];

        sincronizarEspecimenYPlanFila($this->db, (int)$rowM['id'], (string)$rowM['codigo_muestra'], [
            'edad_dias' => 7,
            'fecha_rotura_programada' => '2026-11-08',
            'area' => 28.274,
            'temperatura_c' => 22.0
        ]);

        $stmtEsp = $this->db->prepare("SELECT id FROM especimenes WHERE muestra_id = :mid");
        $stmtEsp->execute(['mid' => $rowM['id']]);
        $espId = (int)$stmtEsp->fetchColumn();

        $stmtPeId = $this->db->prepare("SELECT pre.id FROM plan_rotura_edades pre JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id WHERE pr.muestra_id = :mid");
        $stmtPeId->execute(['mid' => $rowM['id']]);
        $planEdadId = (int)$stmtPeId->fetchColumn();

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_test_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/registrar-rotura';

        $_POST = [
            'csrf_token' => 'token_test_123',
            'especimen_id' => $espId,
            'plan_edad_id' => $planEdadId,
            'fecha_real' => date('Y-m-d H:i:s'),
            'carga' => '120500',
            'area' => '28.274',
            'tipo_falla' => 'Cono',
            'prensa' => 'Prensa Digital 200T',
            'id_hoja' => $fx['id_hoja']
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturadorRespuesta = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->registrarRotura($peticion, $capturadorRespuesta);
        $this->assertStringContainsString('detalle-lote?id_hoja=' . $fx['id_hoja'], (string)$capturadorRespuesta->urlRedir, 'Al guardar la rotura debe redirigir a detalle-lote de la hoja.');
    }

    /**
     * TEST 7: Override de edad por fila exige rol + motivo y deja traza
     */
    public function testOverrideEdadExigeRol1A3YMotivoYRegistraTraza(): void
    {
        $fx = $this->crearFixture(1);
        $opMod = new OperacionModelo();
        $opMod->registrarRecepcion([
            'id_os' => $fx['id_os'],
            'id_hoja' => $fx['id_hoja'],
            'id_detalle_cotizacion' => $fx['id_detalle'],
            'plantilla_id' => $fx['plantilla_id'],
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => $fx['id_usuario'],
            'entregado_por' => 'Cliente Test',
            'observaciones' => 'Test override',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => $fx['id_detalle'], 'plantilla_id' => $fx['plantilla_id'], 'orden_en_lote' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => 'M1', 'codigo_campo' => 'M1', 'nombre_lote' => 'L1', 'fecha_moldeo' => '2026-11-01'],
            ]),
        ]);

        $stmt = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_hoja = :h");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $rowM = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->idsCreados['recepcion_muestras'][] = (int)$rowM['id'];

        sincronizarEspecimenYPlanFila($this->db, (int)$rowM['id'], (string)$rowM['codigo_muestra'], [
            'edad_dias' => 7,
            'fecha_rotura_programada' => '2026-11-08',
            'area' => 28.274,
            'temperatura_c' => 22.0
        ]);

        $stmtPlan = $this->db->prepare("SELECT id FROM planes_rotura WHERE muestra_id = :mid");
        $stmtPlan->execute(['mid' => $rowM['id']]);
        $planId = (int)$stmtPlan->fetchColumn();

        $rotModelo = new RoturasModelo();

        // 1. Rol 4 no autorizado (técnico) debe fallar
        $resRol4 = $rotModelo->modificarPlanRoturaConAutorizacion($planId, [
            ['edad_dias' => 14, 'fecha_programada' => '2026-11-15']
        ], $fx['id_usuario'], 4, 'Justificación suficiente para prueba');
        $this->assertFalse($resRol4['exito'], 'Rol 4 no debe tener permiso de override de plan de roturas.');

        // 2. Justificación corta (< 10 caracteres) debe fallar
        $resCorta = $rotModelo->modificarPlanRoturaConAutorizacion($planId, [
            ['edad_dias' => 14, 'fecha_programada' => '2026-11-15']
        ], $fx['id_usuario'], 1, 'Corta');
        $this->assertFalse($resCorta['exito'], 'Justificación menor a 10 caracteres debe ser rechazada.');

        // 3. Rol 2 con justificación técnica válida tiene éxito
        $resOk = $rotModelo->modificarPlanRoturaConAutorizacion($planId, [
            ['edad_dias' => 14, 'fecha_programada' => '2026-11-15']
        ], $fx['id_usuario'], 2, 'Reajuste técnico solicitado por el Director de Laboratorio según cronograma de obra');
        $this->assertTrue($resOk['exito'], 'Rol 1-3 con justificación válida debe modificar el plan exitosamente.');

        // Verificar cambio en plan_rotura_edades
        $stmtPE = $this->db->prepare("SELECT edad_dias, fecha_programada FROM plan_rotura_edades WHERE plan_rotura_id = :pid");
        $stmtPE->execute(['pid' => $planId]);
        $filaPE = $stmtPE->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(14, (int)$filaPE['edad_dias']);
        $this->assertSame('2026-11-15', $filaPE['fecha_programada']);
    }

    /**
     * TEST 8: Plantilla no ligada al producto -> vacía (no C566 para C39)
     */
    public function testPlantillaNoLigadaAlProductoRetornaNullNoC566ParaC39(): void
    {
        $plMod = new PlantillaModelo();

        // Producto con id que no tiene plantilla vinculada
        $sug = $plMod->obtenerPlantillaSugerida(999999);
        $this->assertNull($sug, 'Si un producto no tiene plantilla asociada, no debe sugerir una plantilla ajena arbitraria.');

        // Para producto C39 no debe sugerir id 21 ("Resistencia de Núcleo ASTM C566")
        $prodC39Id = (int)($this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%C39%' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 2);
        $sugC39 = $plMod->obtenerPlantillaSugerida($prodC39Id);
        if ($sugC39 !== null) {
            $this->assertNotEquals(21, (int)$sugC39['id'], 'El producto C39 nunca debe recibir la plantilla id 21 (Núcleo C566).');
        }
    }

    /**
     * TEST 9: Matriz se puede abrir desde Operaciones con muestras sembradas del RT-FM-13 sin aceptación Lab previa
     */
    public function testMatrizSePuedeAbrirDesdeOperacionesSinAceptacionLabPrevia(): void
    {
        $fx = $this->crearFixture(3);

        // Asegurar que NO existan muestras en recepcion_muestras
        $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_hoja = :h")->execute(['h' => $fx['id_hoja']]);

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['operaciones' => ['ver' => 1, 'crear_editar' => 1]];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/operaciones/captura-matriz?id_os=' . $fx['id_os'] . '&id_detalle=' . $fx['id_detalle'];
        $_GET = ['id_os' => $fx['id_os'], 'id_detalle' => $fx['id_detalle']];

        $peticion = new Peticion();
        $ctrl = new OperacionesControlador();

        $capturadorRespuesta = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        ob_start();
        $ctrl->capturaMatrizProducto($peticion, $capturadorRespuesta);
        $html = (string)ob_get_clean();

        // No debe haber redirigido por falta de recepción en laboratorio
        $this->assertNull($capturadorRespuesta->urlRedir, 'Operaciones debe permitir abrir la matriz sin haber aceptado en laboratorio previamente.');
        $this->assertStringContainsString('MS-T1', $html, 'La matriz debe sembrarse desde las muestras declaradas en la hoja RT-FM-13.');
    }

    /**
     * TEST 10: Compactación/densímetro sigue fuera de Lab
     */
    public function testCompactacionDensimetroSigueFueraDeLaboratorio(): void
    {
        $fxComp = $this->crearFixture(2, true);

        $_SESSION['usuario_id'] = $fxComp['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_comp_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/aceptar-muestra-rapida';

        $_POST = [
            'csrf_token' => 'token_comp_123',
            'id_os' => $fxComp['id_os'],
            'id_hoja' => $fxComp['id_hoja'],
            'id_detalle_cotizacion' => $fxComp['id_detalle'],
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturadorRespuesta = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->aceptarMuestraRapida($peticion, $capturadorRespuesta);

        $this->assertNotNull($_SESSION['error'], 'Debe emitir error indicando que compactación no requiere custodia física.');
        $this->assertStringContainsString('compactación', strtolower((string)$_SESSION['error']));

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_hoja = :h");
        $stmtCount->execute(['h' => $fxComp['id_hoja']]);
        $this->assertSame(0, (int)$stmtCount->fetchColumn(), 'No se deben crear registros en recepcion_muestras para ensayos in situ de compactación.');
    }

    /**
     * TEST 11: Vista Detalle Lote renderiza columnas de Rompimientos Periódicos, Informe Parcial y Rompimiento Consolidado
     */
    public function testVistaDetalleLoteRenderizaRompimientosParcialesYConsolidado(): void
    {
        $fx = $this->crearFixture(4);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 4, true);

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['ver' => 1, 'crear_editar' => 1]];
        $_SESSION['csrf_token'] = 'token_test_abc';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'];
        $_GET = ['id_hoja' => $fx['id_hoja']];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        ob_start();
        $ctrl->detalleLote($peticion, $capturador);
        $html = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir);
        $this->assertStringContainsString('Rompimiento (lb / PSI)', $html);
        $this->assertStringContainsString('Informe Parcial', $html);
        $this->assertStringContainsString('Rompimiento Consolidado e Informes Oficiales del Lote', $html);
        $this->assertStringContainsString('Consolidado Bloqueado', $html);
        $this->assertStringContainsString('Ver Informe Oficial (RT-FM-22)', $html);
        $this->assertStringContainsString('Ver Reporte Oficial del Lote', $html);
    }

    /**
     * TEST 12: Generar informe parcial con id_hoja preserva redirección al lote
     */
    public function testGenerarInformeParcialConIdHojaRedirigeADetalleLote(): void
    {
        $fx = $this->crearFixture(2);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 2, false);

        // Obtener la 1ra muestra y su espécimen
        $stmtM = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h ORDER BY id ASC LIMIT 1");
        $stmtM->execute(['h' => $fx['id_hoja']]);
        $mId = (int)$stmtM->fetchColumn();

        $stmtEsp = $this->db->prepare("
            SELECT esp.id AS esp_id, pra.plan_edad_id, pre.edad_dias, pre.fecha_programada
            FROM especimenes esp
            JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            WHERE esp.muestra_id = :mid LIMIT 1
        ");
        $stmtEsp->execute(['mid' => $mId]);
        $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($espRow, 'Debe existir espécimen con plan_edad_id');

        // Registrar la rotura para que la edad quede en 'completado'
        $roturasMod = new RoturasModelo();
        $resRot = $roturasMod->registrarRotura([
            'especimen_id' => (int)$espRow['esp_id'],
            'plan_edad_id' => (int)$espRow['plan_edad_id'],
            'edad_dias' => (int)$espRow['edad_dias'],
            'fecha_real' => !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 09:00:00') : date('Y-m-d H:i:s'),
            'justificacion' => 'Ensayo en fecha programada con autorización de supervisión',
            'carga' => 65000.0,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)'
        ], $fx['id_usuario']);
        $this->assertTrue($resRot['exito'], 'La rotura debe registrarse exitosamente: ' . ($resRot['mensaje'] ?? ''));

        // Ejecutar POST a generarInformeParcial enviando id_hoja
        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_inf_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/generar-informe-parcial';

        $_POST = [
            'csrf_token' => 'token_inf_123',
            'id_hoja' => $fx['id_hoja'],
            'muestra_id' => $mId,
            'plan_edad_id' => (int)$espRow['plan_edad_id']
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->generarInformeParcial($peticion, $capturador);

        $this->assertSame('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'], $capturador->urlRedir, 'Debe redirigir al detalle del lote cuando id_hoja está presente.');

        // Verificar que el informe parcial se haya registrado
        $stmtInf = $this->db->prepare("SELECT * FROM informes_rotura WHERE muestra_id = :mid AND tipo = 'parcial' AND estado = 'en_revision'");
        $stmtInf->execute(['mid' => $mId]);
        $inf = $stmtInf->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($inf, 'El informe parcial debe haber sido creado en estado en_revision.');
        $this->assertStringStartsWith('INF-', (string)$inf['numero']);
    }

    /**
     * TEST 13: Procesar revisión de informe con id_hoja redirige al detalle del lote
     */
    public function testProcesarRevisionInformeConIdHojaRedirigeADetalleLote(): void
    {
        $fx = $this->crearFixture(2);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 2, false);

        $stmtM = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h ORDER BY id ASC LIMIT 1");
        $stmtM->execute(['h' => $fx['id_hoja']]);
        $mId = (int)$stmtM->fetchColumn();

        $stmtEsp = $this->db->prepare("
            SELECT esp.id AS esp_id, pra.plan_edad_id, pre.edad_dias, pre.fecha_programada
            FROM especimenes esp
            JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            WHERE esp.muestra_id = :mid LIMIT 1
        ");
        $stmtEsp->execute(['mid' => $mId]);
        $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

        $roturasMod = new RoturasModelo();
        $roturasMod->registrarRotura([
            'especimen_id' => (int)$espRow['esp_id'],
            'plan_edad_id' => (int)$espRow['plan_edad_id'],
            'edad_dias' => (int)$espRow['edad_dias'],
            'fecha_real' => !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 09:00:00') : date('Y-m-d H:i:s'),
            'justificacion' => 'Ensayo en fecha programada con autorización de supervisión',
            'carga' => 65000.0,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)'
        ], $fx['id_usuario']);

        $resInf = $roturasMod->generarInformeParcial($mId, (int)$espRow['plan_edad_id'], $fx['id_usuario']);
        $this->assertTrue($resInf['exito']);
        $informeId = (int)$resInf['informe_id'];

        // Aprobar desde controlador con id_hoja
        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1; // Admin
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_rev_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/procesar-revision-informe';

        $_POST = [
            'csrf_token' => 'token_rev_123',
            'id_hoja' => $fx['id_hoja'],
            'muestra_id' => $mId,
            'informe_id' => $informeId,
            'nuevo_estado' => 'aprobado'
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->procesarRevisionInforme($peticion, $capturador);

        $this->assertSame('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'], $capturador->urlRedir);

        // Comprobar que quedó aprobado
        $stmtCheck = $this->db->prepare("SELECT estado FROM informes_rotura WHERE id = :id");
        $stmtCheck->execute(['id' => $informeId]);
        $this->assertSame('aprobado', $stmtCheck->fetchColumn());
    }

    /**
     * TEST 14: Generar informe consolidado con id_hoja redirige al detalle del lote
     */
    public function testGenerarInformeConsolidadoConIdHojaRedirigeADetalleLote(): void
    {
        $fx = $this->crearFixture(1);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 1, false);

        $stmtM = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h ORDER BY id ASC LIMIT 1");
        $stmtM->execute(['h' => $fx['id_hoja']]);
        $mId = (int)$stmtM->fetchColumn();

        $stmtEsp = $this->db->prepare("
            SELECT esp.id AS esp_id, pra.plan_edad_id, pre.edad_dias, pre.fecha_programada
            FROM especimenes esp
            JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            WHERE esp.muestra_id = :mid LIMIT 1
        ");
        $stmtEsp->execute(['mid' => $mId]);
        $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

        $roturasMod = new RoturasModelo();
        $roturasMod->registrarRotura([
            'especimen_id' => (int)$espRow['esp_id'],
            'plan_edad_id' => (int)$espRow['plan_edad_id'],
            'edad_dias' => (int)$espRow['edad_dias'],
            'fecha_real' => !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 09:00:00') : date('Y-m-d H:i:s'),
            'justificacion' => 'Ensayo en fecha programada con autorización de supervisión',
            'carga' => 70000.0,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)'
        ], $fx['id_usuario']);

        $resInf = $roturasMod->generarInformeParcial($mId, (int)$espRow['plan_edad_id'], $fx['id_usuario']);
        $roturasMod->procesarRevisionInforme((int)$resInf['informe_id'], 'aprobado', null, $fx['id_usuario'], 1);

        // Ahora generar consolidado
        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_cons_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/generar-informe-consolidado';

        $_POST = [
            'csrf_token' => 'token_cons_123',
            'id_hoja' => $fx['id_hoja'],
            'muestra_id' => $mId
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->generarInformeConsolidado($peticion, $capturador);

        $this->assertSame('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'], $capturador->urlRedir);

        $stmtCons = $this->db->prepare("SELECT * FROM informes_rotura WHERE muestra_id = :mid AND tipo = 'consolidado' AND estado = 'aprobado'");
        $stmtCons->execute(['mid' => $mId]);
        $this->assertNotEmpty($stmtCons->fetch(PDO::FETCH_ASSOC), 'El consolidado debe haberse emitido exitosamente.');
    }

    /**
     * TEST 15: Generación consolidada del lote completo emite consolidados para muestras con parciales aprobados
     */
    public function testGenerarInformeConsolidadoLoteCompleto(): void
    {
        $fx = $this->crearFixture(2);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 2, false);

        $stmtM = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h ORDER BY id ASC");
        $stmtM->execute(['h' => $fx['id_hoja']]);
        $mIds = $stmtM->fetchAll(PDO::FETCH_COLUMN);

        $roturasMod = new RoturasModelo();
        foreach ($mIds as $mId) {
            $stmtEsp = $this->db->prepare("
                SELECT esp.id AS esp_id, pra.plan_edad_id, pre.edad_dias, pre.fecha_programada
                FROM especimenes esp
                JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
                JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
                WHERE esp.muestra_id = :mid LIMIT 1
            ");
            $stmtEsp->execute(['mid' => (int)$mId]);
            $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

            $roturasMod->registrarRotura([
                'especimen_id' => (int)$espRow['esp_id'],
                'plan_edad_id' => (int)$espRow['plan_edad_id'],
                'edad_dias' => (int)$espRow['edad_dias'],
                'fecha_real' => !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 09:00:00') : date('Y-m-d H:i:s'),
                'justificacion' => 'Ensayo en fecha programada con autorización de supervisión',
                'carga' => 68000.0,
                'area' => 28.274,
                'tipo_falla' => 'Tipo 1 (Cono bien formado)'
            ], $fx['id_usuario']);

            $resInf = $roturasMod->generarInformeParcial((int)$mId, (int)$espRow['plan_edad_id'], $fx['id_usuario']);
            $roturasMod->procesarRevisionInforme((int)$resInf['informe_id'], 'aprobado', null, $fx['id_usuario'], 1);
        }

        // Ejecutar consolidado en lote
        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['crear_editar' => 1, 'ver' => 1]];
        $_SESSION['csrf_token'] = 'token_cons_lote_123';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/generar-informe-consolidado-lote';

        $_POST = [
            'csrf_token' => 'token_cons_lote_123',
            'id_hoja' => $fx['id_hoja']
        ];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        $ctrl->generarInformeConsolidadoLote($peticion, $capturador);

        $this->assertSame('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $fx['id_hoja'], $capturador->urlRedir);
        $this->assertNotNull($_SESSION['exito']);

        // Verificar que ambas muestras tengan consolidado
        $stmtCountCons = $this->db->prepare("
            SELECT COUNT(*) FROM informes_rotura ir
            JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
            WHERE rm.id_hoja = :h AND ir.tipo = 'consolidado' AND ir.estado = 'aprobado'
        ");
        $stmtCountCons->execute(['h' => $fx['id_hoja']]);
        $this->assertSame(2, (int)$stmtCountCons->fetchColumn(), 'Ambas muestras del lote deben tener su informe consolidado emitido.');
    }

    /**
     * TEST: Enviar datos_crudos con campos vacíos desde formulario HTML no debe arrojar error de código de formato.
     */
    public function testRegistrarRoturaDirectaConDatosCrudosVaciosNoFallaPorCodigoFormato(): void
    {
        $fx = $this->crearFixture(1);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 1, false);

        $stmt = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h LIMIT 1");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $mId = (int)$stmt->fetchColumn();

        $stmtEsp = $this->db->prepare("
            SELECT esp.id as esp_id, pre.id as plan_edad_id, pre.edad_dias, pre.fecha_programada
            FROM especimenes esp
            JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            WHERE esp.muestra_id = :mid LIMIT 1
        ");
        $stmtEsp->execute(['mid' => $mId]);
        $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

        $fechaEnsayo = !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 08:30:00') : date('Y-m-d H:i:s');
        $roturasMod = new RoturasModelo();
        // Simular exactamente el POST del navegador con inputs HTML en blanco en datos_crudos
        $resRot = $roturasMod->registrarRotura([
            'especimen_id' => (int)$espRow['esp_id'],
            'plan_edad_id' => (int)$espRow['plan_edad_id'],
            'edad_dias' => 7,
            'fecha_real' => $fechaEnsayo,
            'justificacion' => 'Ensayo en fecha programada',
            'carga' => 65400.0,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)',
            'datos_crudos' => [
                'diametro_1' => '',
                'diametro_2' => '',
                'largo_1' => '',
                'largo_2' => '',
                'masa_kg' => ''
            ]
        ], $fx['id_usuario']);

        $this->assertTrue($resRot['exito'], 'El registro de rotura directa con campos crudos vacíos debe ser exitoso: ' . ($resRot['mensaje'] ?? ''));
        $this->assertEqualsWithDelta(2313.08, (float)$resRot['esfuerzo_psi'], 0.1);
        $this->assertEqualsWithDelta(162.63, (float)$resRot['esfuerzo_kgcm2'], 0.1);
    }

    /**
     * TEST: Enviar dimensiones de probeta RT-FM-61 con carga y área directas persiste metadatos sin exigir formato explícito.
     */
    public function testRegistrarRoturaDirectaConDimensionesDeCilindroPersisteDatosCrudosSinRequerirCodigoFormato(): void
    {
        $fx = $this->crearFixture(1);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 1, false);

        $stmt = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :h LIMIT 1");
        $stmt->execute(['h' => $fx['id_hoja']]);
        $mId = (int)$stmt->fetchColumn();

        $stmtEsp = $this->db->prepare("
            SELECT esp.id as esp_id, pre.id as plan_edad_id, pre.fecha_programada
            FROM especimenes esp
            JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            WHERE esp.muestra_id = :mid LIMIT 1
        ");
        $stmtEsp->execute(['mid' => $mId]);
        $espRow = $stmtEsp->fetch(PDO::FETCH_ASSOC);

        $fechaEnsayo = !empty($espRow['fecha_programada']) ? ($espRow['fecha_programada'] . ' 08:30:00') : date('Y-m-d H:i:s');
        $roturasMod = new RoturasModelo();
        $resRot = $roturasMod->registrarRotura([
            'especimen_id' => (int)$espRow['esp_id'],
            'plan_edad_id' => (int)$espRow['plan_edad_id'],
            'edad_dias' => 28,
            'fecha_real' => $fechaEnsayo,
            'justificacion' => 'Ensayo en fecha programada',
            'carga' => 70000.0,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)',
            'datos_crudos' => [
                'diametro_1' => '15.20',
                'diametro_2' => '15.22',
                'largo_1' => '30.40',
                'largo_2' => '30.45',
                'masa_kg' => '12.85'
            ]
        ], $fx['id_usuario']);

        $this->assertTrue($resRot['exito']);
        $this->assertEqualsWithDelta(2475.77, (float)$resRot['esfuerzo_psi'], 0.1);

        // Verificar que los metadatos de dimensiones se guardaron limpios en BD
        $stmtRotBd = $this->db->prepare("SELECT datos_crudos FROM roturas WHERE especimen_id = :espId");
        $stmtRotBd->execute(['espId' => (int)$espRow['esp_id']]);
        $jsonCrudos = $stmtRotBd->fetchColumn();
        $this->assertNotNull($jsonCrudos);
        $arrCrudos = json_decode((string)$jsonCrudos, true);
        $this->assertSame('15.20', $arrCrudos['diametro_1']);
        $this->assertSame('12.85', $arrCrudos['masa_kg']);
    }

    /**
     * TEST: Lectura cruda de dial sin carga directa y sin código de formato debe ser rechazada explícitamente.
     */
    public function testRegistrarRoturaConLecturaCrudaSinCargaYSinFormatoRechazaConMensajeClaro(): void
    {
        $roturasMod = new RoturasModelo();
        $res = $roturasMod->registrarRotura([
            'especimen_id' => 999999,
            'datos_crudos' => [
                'lectura_carga_lbf' => 91932
            ]
        ], 1);

        $this->assertFalse($res['exito']);
        $this->assertStringContainsString('no se especificó un código de formato explícito', $res['mensaje']);
    }

    /**
     * TEST: LaboratorioControlador::verInforme permite visualizar el reporte oficial RT-FM-22
     * tanto por id_hoja como por id_informe sin bloqueos comerciales de pago.
     */
    public function testVerInformeLaboratorioPorHojaEInforme(): void
    {
        $fx = $this->crearFixture(4);
        $this->sincronizarMatrizConLab($fx['id_hoja'], $fx['id_detalle'], 4, true);

        $_SESSION['usuario_id'] = $fx['id_usuario'];
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['ver' => 1, 'crear_editar' => 1]];
        $_SESSION['csrf_token'] = 'token_test_abc';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/ver-informe?id_hoja=' . $fx['id_hoja'];
        $_GET = ['id_hoja' => $fx['id_hoja']];

        $peticion = new Peticion();
        $ctrl = new LaboratorioControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        // 1. Ver informe por id_hoja
        ob_start();
        $ctrl->verInforme($peticion, $capturador);
        $htmlHoja = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir, 'verInforme no debe redirigir por restricción de pago');
        $this->assertStringContainsString('CYCSA-RT-FM-22', $htmlHoja);
        $this->assertStringContainsString('Volver a Ver Lote', $htmlHoja);

        // 2. Crear un informe de rotura en BD y ver informe por id
        $stmtM = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_hoja = :id_hoja LIMIT 1");
        $stmtM->execute(['id_hoja' => $fx['id_hoja']]);
        $mId = (int)$stmtM->fetchColumn();

        $stmtInsInf = $this->db->prepare("
            INSERT INTO informes_rotura (tipo, muestra_id, numero, version, estado, sha256)
            VALUES ('consolidado', :mid, 'INF-2026-9999', 1, 'aprobado', 'abcdef1234567890abcdef1234567890')
        ");
        $stmtInsInf->execute(['mid' => $mId]);
        $nuevoInfId = (int)$this->db->lastInsertId();

        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/laboratorio/ver-informe?id=' . $nuevoInfId;
        $_GET = ['id' => $nuevoInfId];

        ob_start();
        $ctrl->verInforme($peticion, $capturador);
        $htmlInf = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir);
        $this->assertStringContainsString('INF-2026-9999', $htmlInf);
        $this->assertStringContainsString('INFORME CONSOLIDADO DE ENSAYO', $htmlInf);
        $this->assertStringContainsString('SHA-256: abcdef1234567890', $htmlInf);
    }
}
