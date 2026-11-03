<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Operaciones\Controladores\LaboratorioControlador;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;

class TestRespuestaScope extends Respuesta {
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

class Rtfm13MuestrasScopeYCompactacionLabTest extends TestCase {
    private PDO $db;
    private OperacionModelo $opModelo;
    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idOS = 0;
    private int $idDetalleConcreto = 0;
    private int $idDetalleDensimetro = 0;
    private int $idDetalleProctor = 0;
    private int $idProdConcreto = 0;
    private int $idProdDensimetro = 0;
    private int $idProdProctor = 0;
    private int $idHojaConcreto = 0;
    private int $idHojaDensimetro = 0;
    private int $idHojaProctor = 0;
    private array $hojasCreadas = [];
    private array $recepcionesCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->opModelo = new OperacionModelo();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        unset($_SESSION['error'], $_SESSION['exito']);
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Scope Test';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->crearFixtures();
    }

    protected function tearDown(): void {
        if (isset($_SESSION)) {
            unset($_SESSION['error'], $_SESSION['exito']);
        }
        if (!empty($this->recepcionesCreadas)) {
            $inRec = implode(',', array_map('intval', $this->recepcionesCreadas));
            $this->db->exec("DELETE FROM lotes_muestras WHERE id_recepcion IN ({$inRec})");
            $this->db->exec("DELETE FROM recepcion_muestras WHERE id IN ({$inRec})");
        }
        if (!empty($this->hojasCreadas)) {
            $inClause = implode(',', array_map('intval', $this->hojasCreadas));
            $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inClause})");
            $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inClause})");
        }
        if ($this->idOS > 0) {
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $this->idOS]);
        }
        if ($this->idCotizacion > 0) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $this->idCotizacion]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $this->idCotizacion]);
        }
        if ($this->idCliente > 0) {
            $this->db->prepare("DELETE FROM clientes WHERE id = :id")->execute(['id' => $this->idCliente]);
        }
        parent::tearDown();
    }

    private function crearFixtures(): void {
        $unique = time() . '_' . mt_rand(1000, 9999);

        // 1. Cliente
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) 
                                      VALUES (:n, :r, :e, :t)");
        $stmtCli->execute([
            'n' => "Cliente Scope {$unique}",
            'r' => "RUC-SC-{$unique}",
            'e' => "scope_{$unique}@cycsa.test",
            't' => '2222-4444'
        ]);
        $this->idCliente = (int)$this->db->lastInsertId();

        // 2. Productos: Concreto (Lab), Densímetro Nuclear (Compactación in situ), Proctor (Lab Suelos)
        // Concreto
        $stmtP1 = $this->db->query("SELECT id FROM productos WHERE nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%' LIMIT 1");
        $this->idProdConcreto = (int)$stmtP1->fetchColumn();
        if ($this->idProdConcreto <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 A', 'Resistencia Cilindros Concreto', 'ASTM C39', 21, 500)");
            $this->idProdConcreto = (int)$this->db->lastInsertId();
        }

        // Densímetro Nuclear
        $stmtP2 = $this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%D6938%' OR nombre_comercial LIKE '%Densimetro%' OR nombre_comercial LIKE '%Densímetro%' LIMIT 1");
        $this->idProdDensimetro = (int)$stmtP2->fetchColumn();
        if ($this->idProdDensimetro <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 B', 'Densímetro Nuclear Compactación In Situ', 'ASTM D6938', 1, 1200)");
            $this->idProdDensimetro = (int)$this->db->lastInsertId();
        }

        // Proctor (Lab de Suelo)
        $stmtP3 = $this->db->query("SELECT id FROM productos WHERE (nombre_comercial LIKE '%Proctor%' OR norma_astm LIKE '%D1557%') AND id NOT IN ({$this->idProdConcreto}, {$this->idProdDensimetro}) LIMIT 1");
        $this->idProdProctor = (int)$stmtP3->fetchColumn();
        if ($this->idProdProctor <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 F', 'Proctor Modificado de Suelos en Laboratorio', 'ASTM D1557', 15, 800)");
            $this->idProdProctor = (int)$this->db->lastInsertId();
        }

        // 3. Cotización
        $codigoCot = "COT-SC-" . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total, fecha_creacion)
                                       VALUES (:c, :cli, 1, 'Ing. Scope', 'Proyecto Scope Multi-Hoja', 'Contado', 'Aprobada por Cliente', 2500.00, 2500.00, NOW())");
        $stmtCot->execute([
            'c' => $codigoCot,
            'cli' => $this->idCliente
        ]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        // 4. Cotización Detalles
        // Detalle 1: Concreto
        $stmtDet1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Cilindros Concreto f\'c 280 kg/cm²', 'CYCSA-RT-FM-22 A', 'ASTM C39', 2, 250.00, 500.00)");
        $stmtDet1->execute(['cot' => $this->idCotizacion, 'p' => $this->idProdConcreto]);
        $this->idDetalleConcreto = (int)$this->db->lastInsertId();

        // Detalle 2: Densímetro Nuclear
        $stmtDet2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Densímetro Nuclear ASTM D6938 In Situ', 'CYCSA-RT-FM-22 B', 'ASTM D6938', 2, 600.00, 1200.00)");
        $stmtDet2->execute(['cot' => $this->idCotizacion, 'p' => $this->idProdDensimetro]);
        $this->idDetalleDensimetro = (int)$this->db->lastInsertId();

        // Detalle 3: Proctor
        $stmtDet3 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Proctor Modificado ASTM D1557', 'CYCSA-RT-FM-22 F', 'ASTM D1557', 1, 800.00, 800.00)");
        $stmtDet3->execute(['cot' => $this->idCotizacion, 'p' => $this->idProdProctor]);
        $this->idDetalleProctor = (int)$this->db->lastInsertId();

        // 5. Orden de Servicio Aprobada
        $osModelo = new OrdenServicioModelo();
        $this->idOS = $osModelo->crear([
            'codigo_os' => "OS-SC-" . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Scope',
            'nombre_proyecto' => 'Proyecto Scope Multi-Hoja',
            'forma_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada'
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS}");

        // 6. Hoja 1: Concreto (2 muestras)
        $okH1 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'id_producto' => $this->idProdConcreto,
            'ensayo_id' => $this->idProdConcreto,
            'numero_registro' => 'REG-CONC-01',
            'nombre_empresa_o_cliente' => 'Cliente Scope Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0001-26', 'descripcion' => 'Cilindro Zapata 1', 'ensayo_id' => $this->idProdConcreto],
                ['nombre_muestra' => 'MC-0002-26', 'descripcion' => 'Cilindro Zapata 2', 'ensayo_id' => $this->idProdConcreto]
            ])
        ], $this->idHojaConcreto);
        $this->hojasCreadas[] = $this->idHojaConcreto;

        $pltMod = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
        $pltMod->guardarDetallesMuestrasHoja($this->idHojaConcreto, [
            ['codigo_muestra' => 'MC-0001-26', 'descripcion_ubicacion' => 'Cilindro Zapata 1', 'ensayo_id' => $this->idProdConcreto],
            ['codigo_muestra' => 'MC-0002-26', 'descripcion_ubicacion' => 'Cilindro Zapata 2', 'ensayo_id' => $this->idProdConcreto]
        ], 1);

        // Muestras recibidas en Lab para Concreto
        $usrId = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $stmtRec1 = $this->db->prepare("INSERT INTO recepcion_muestras (id_os, id_hoja, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado) 
                                        VALUES (:os, :h, 'MC-0001-26', 'MC-0001-26', NOW(), :usr, 'Aprobada')");
        $stmtRec1->execute(['os' => $this->idOS, 'h' => $this->idHojaConcreto, 'usr' => $usrId]);
        $this->recepcionesCreadas[] = (int)$this->db->lastInsertId();

        $stmtRec2 = $this->db->prepare("INSERT INTO recepcion_muestras (id_os, id_hoja, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado) 
                                        VALUES (:os, :h, 'MC-0002-26', 'MC-0002-26', NOW(), :usr, 'Aprobada')");
        $stmtRec2->execute(['os' => $this->idOS, 'h' => $this->idHojaConcreto, 'usr' => $usrId]);
        $this->recepcionesCreadas[] = (int)$this->db->lastInsertId();

        // 7. Hoja 2: Densímetro Nuclear (2 muestras)
        $okH2 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleDensimetro,
            'id_producto' => $this->idProdDensimetro,
            'ensayo_id' => $this->idProdDensimetro,
            'numero_registro' => 'REG-DENS-02',
            'nombre_empresa_o_cliente' => 'Cliente Scope Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0003-26', 'descripcion' => 'Punto Compactación 1', 'ensayo_id' => $this->idProdDensimetro],
                ['nombre_muestra' => 'MS-0004-26', 'descripcion' => 'Punto Compactación 2', 'ensayo_id' => $this->idProdDensimetro]
            ])
        ], $this->idHojaDensimetro);
        $this->hojasCreadas[] = $this->idHojaDensimetro;

        $pltMod->guardarDetallesMuestrasHoja($this->idHojaDensimetro, [
            ['codigo_muestra' => 'MS-0003-26', 'descripcion_ubicacion' => 'Punto Compactación 1', 'ensayo_id' => $this->idProdDensimetro],
            ['codigo_muestra' => 'MS-0004-26', 'descripcion_ubicacion' => 'Punto Compactación 2', 'ensayo_id' => $this->idProdDensimetro]
        ], 1);

        // 8. Hoja 3: Proctor (Lab Suelos)
        $okH3 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleProctor,
            'id_producto' => $this->idProdProctor,
            'ensayo_id' => $this->idProdProctor,
            'numero_registro' => 'REG-PROC-03',
            'nombre_empresa_o_cliente' => 'Cliente Scope Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0005-26', 'descripcion' => 'Muestra Suelo Banco 1', 'ensayo_id' => $this->idProdProctor]
            ])
        ], $this->idHojaProctor);
        $this->hojasCreadas[] = $this->idHojaProctor;

        $pltMod->guardarDetallesMuestrasHoja($this->idHojaProctor, [
            ['codigo_muestra' => 'MS-0005-26', 'descripcion_ubicacion' => 'Muestra Suelo Banco 1', 'ensayo_id' => $this->idProdProctor]
        ], 1);
    }

    public function testHelperResolverHojaPorDetalle(): void {
        $hConc = resolverHojaPorDetalle($this->db, $this->idOS, $this->idDetalleConcreto);
        $this->assertNotNull($hConc);
        $this->assertEquals($this->idHojaConcreto, (int)$hConc['id']);

        $hDens = resolverHojaPorDetalle($this->db, $this->idOS, $this->idDetalleDensimetro);
        $this->assertNotNull($hDens);
        $this->assertEquals($this->idHojaDensimetro, (int)$hDens['id']);

        $hProc = resolverHojaPorDetalle($this->db, $this->idOS, $this->idDetalleProctor);
        $this->assertNotNull($hProc);
        $this->assertEquals($this->idHojaProctor, (int)$hProc['id']);
    }

    public function testMatrizDensimetroCargaUnicamenteSusMuestrasDeLaHojaYNoLasDeConcreto(): void {
        // Ejecutar simulación de carga de muestras para Densímetro (idDetalleDensimetro)
        $detalleDens = $this->db->query("SELECT * FROM cotizacion_detalles WHERE id = {$this->idDetalleDensimetro}")->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(esItemCompactacion($detalleDens), 'Densímetro debe ser identificado como compactación in situ.');

        $hojaDens = resolverHojaPorDetalle($this->db, $this->idOS, $this->idDetalleDensimetro);
        $this->assertNotNull($hojaDens);

        $stmtHsd = $this->db->prepare("SELECT codigo_muestra FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id_hoja ORDER BY orden ASC, id ASC");
        $stmtHsd->execute(['id_hoja' => (int)$hojaDens['id']]);
        $codigosDens = $stmtHsd->fetchAll(PDO::FETCH_COLUMN);

        $this->assertCount(2, $codigosDens, 'Densímetro debe tener exactamente 2 muestras.');
        $this->assertEquals(['MS-0003-26', 'MS-0004-26'], $codigosDens);
        $this->assertNotContains('MC-0001-26', $codigosDens, 'Densímetro jamás debe contener muestras de Concreto.');
        $this->assertNotContains('MC-0002-26', $codigosDens, 'Densímetro jamás debe contener muestras de Concreto.');

        // Ahora verificar Concreto: debe tener exclusivamente sus 2 muestras de recepcion_muestras
        $hojaConc = resolverHojaPorDetalle($this->db, $this->idOS, $this->idDetalleConcreto);
        $stmtMuestrasLab = $this->db->prepare("
            SELECT rm.codigo_muestra
            FROM recepcion_muestras rm
            WHERE rm.id_os = :id_os AND (rm.id_hoja = :id_hoja OR (rm.id_hoja IS NULL AND :id_hoja_fb = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = :id_os_fb)))
            ORDER BY rm.id ASC
        ");
        $stmtMuestrasLab->execute([
            'id_os' => $this->idOS,
            'id_hoja' => (int)$hojaConc['id'],
            'id_hoja_fb' => (int)$hojaConc['id'],
            'id_os_fb' => $this->idOS
        ]);
        $codigosConc = $stmtMuestrasLab->fetchAll(PDO::FETCH_COLUMN);

        $this->assertCount(2, $codigosConc);
        $this->assertEquals(['MC-0001-26', 'MC-0002-26'], $codigosConc);
        $this->assertNotContains('MS-0003-26', $codigosConc);
        $this->assertNotContains('MS-0004-26', $codigosConc);
    }

    public function testHojaDensimetroNoApareceEnBandejaLaboratorioPeroConcretoYProctorSi(): void {
        // Replicar la consulta y filtrado oficial de LaboratorioControlador::index
        $sqlSolicitudes = "SELECT os.id AS id_os, os.codigo_os, os.fecha_emision, os.estado AS estado_os,
                                  os.requiere_muestreo, os.tipo_contrato, os.motivo_observacion,
                                  hs.id AS id_hoja, hs.codigo_documento AS hoja_codigo, hs.numero_registro,
                                  hs.id_detalle_cotizacion, hs.id_producto,
                                  hs.fecha_hora_toma_muestra, hs.muestras_json, hs.naturaleza_muestra,
                                  hs.procedencia_punto_muestreo, hs.observaciones, hs.fecha_creacion AS hoja_fecha_creacion,
                                  cot.id AS id_cotizacion,
                                  (CASE 
                                      WHEN hs.id IS NOT NULL THEN (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = os.id AND (rm.id_hoja = hs.id OR (rm.id_hoja IS NULL AND hs.id = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = os.id))))
                                      ELSE (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = os.id)
                                   END) AS total_recibidas_lab
                           FROM ordenes_servicio os
                           LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
                           LEFT JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                           WHERE os.id = :id_os";
        $stmt = $this->db->prepare($sqlSolicitudes);
        $stmt->execute(['id_os' => $this->idOS]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $solicitudesAprobacionLab = [];
        foreach ($filas as $o) {
            $ensayos = $this->opModelo->filtrarDetallesPermitidos((int)$o['id_os'], $this->opModelo->obtenerDetallesCotizacion((int)$o['id_cotizacion']));

            if (!empty($o['id_detalle_cotizacion'])) {
                $detHoja = null;
                foreach ($ensayos as $eItem) {
                    if ((int)$eItem['id'] === (int)$o['id_detalle_cotizacion']) {
                        $detHoja = $eItem;
                        break;
                    }
                }
                if (!$detHoja) {
                    $stmtD = $this->db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre 
                                           FROM cotizacion_detalles cd
                                           LEFT JOIN productos p ON cd.id_producto = p.id
                                           LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                           WHERE cd.id = :id");
                    $stmtD->execute(['id' => (int)$o['id_detalle_cotizacion']]);
                    $detHoja = $stmtD->fetch(PDO::FETCH_ASSOC);
                }
                if ($detHoja && esItemCompactacion($detHoja)) {
                    continue; // Excluir compactación in situ
                }
            } else {
                if (!empty($ensayos) && esOrdenSoloCompactacion($ensayos)) {
                    continue;
                }
            }
            $solicitudesAprobacionLab[] = (int)$o['id_hoja'];
        }

        // Densímetro NO debe aparecer en Laboratorio
        $this->assertNotContains($this->idHojaDensimetro, $solicitudesAprobacionLab, 'La hoja de densímetro/compactación no debe aparecer en Laboratorio.');

        // Concreto y Proctor SÍ deben procesarse en Laboratorio
        $this->assertContains($this->idHojaConcreto, $solicitudesAprobacionLab, 'La hoja de concreto sí debe figurar en Laboratorio.');
        $this->assertContains($this->idHojaProctor, $solicitudesAprobacionLab, 'La hoja de proctor (suelos lab) sí debe figurar en Laboratorio.');
    }

    public function testAprobarMuestraEnLaboratorioRechazaHojaDeCompactacion(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $labCtrl = new LaboratorioControlador();
        $peticion = new Peticion([], [
            'id_os' => $this->idOS,
            'id_hoja' => $this->idHojaDensimetro,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
        $_POST = [
            'id_os' => $this->idOS,
            'id_hoja' => $this->idHojaDensimetro,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        $respuesta = new TestRespuestaScope();

        unset($_SESSION['error']);
        $labCtrl->aceptarMuestraRapida($peticion, $respuesta);

        $this->assertStringContainsString('/laboratorio', $respuesta->urlRedirigida);
        $this->assertNotEmpty($_SESSION['error']);
        $this->assertStringContainsString('compactación in situ no requieren recepción', $_SESSION['error']);
    }

    public function testHojasServicioControladorEsProductoCompactacionUnificado(): void {
        $hsCtrl = new HojasServicioControlador();

        $detDens = ['nombre_comercial' => 'Densímetro Nuclear', 'norma_astm' => 'ASTM D6938', 'formato_id' => 1];
        $this->assertTrue($hsCtrl->esProductoCompactacion($detDens));

        $detConc = ['nombre_comercial' => 'Cilindros de Concreto', 'norma_astm' => 'ASTM C39', 'formato_id' => 21];
        $this->assertFalse($hsCtrl->esProductoCompactacion($detConc));

        $detProc = ['nombre_comercial' => 'Proctor Modificado', 'norma_astm' => 'ASTM D1557', 'formato_id' => 15];
        $this->assertFalse($hsCtrl->esProductoCompactacion($detProc), 'Proctor no debe ser compactación in situ; es ensayo de laboratorio.');
    }
}
