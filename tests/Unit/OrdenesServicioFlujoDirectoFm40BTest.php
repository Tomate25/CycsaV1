<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Reportes\Modelos\ReporteModelo;
use PDO;

class OrdenesServicioFlujoDirectoFm40BTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;
    private int $idOSPrueba = 0;
    private string $codigoOSPrueba = '';
    private int $idCotizacionPrueba = 0;
    private int $idClientePrueba = 0;
    private int $idDetallePrueba = 0;
    private array $hojasCreadas = [];
    private array $secuenciasBackup = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->osModelo = new OrdenServicioModelo();
        $this->opModelo = new OperacionModelo();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Test Runner';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $idCli = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($idCli <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Test Flujo Directo', 'J0310000009999', 'flujo@test.com', '22229999')");
            $idCli = (int)$this->db->lastInsertId();
        }
        $this->idClientePrueba = $idCli;

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Tester', 'tester_flujo@test.com', '123456', 1, 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $cotCode = 'COT-FD40B-' . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:code, :cli, :usr, 'Atn Test', 'Proyecto Flujo Directo 40B', 'Pago contra entrega', 'Aprobada por Cliente', 1000.00, 1150.00)");
        $stmtCot->execute(['code' => $cotCode, 'cli' => $this->idClientePrueba, 'usr' => $idUser]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:cot, 1, 'Compresión Concreto / Densidad Campo', 4, 250.00, 1000.00)");
        $stmtDet->execute(['cot' => $this->idCotizacionPrueba]);
        $this->idDetallePrueba = (int)$this->db->lastInsertId();

        $this->codigoOSPrueba = 'OS-FD40B-' . uniqid();
        $stmtOS = $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, estado, requiere_muestreo) VALUES (:code, :cot, :cli, CURDATE(), 'Aprobada', NULL)");
        $stmtOS->execute(['code' => $this->codigoOSPrueba, 'cot' => $this->idCotizacionPrueba, 'cli' => $this->idClientePrueba]);
        $this->idOSPrueba = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        foreach ($this->hojasCreadas as $idHoja) {
            try {
                $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_hoja_solicitud = ?")->execute([$idHoja]);
                $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = ?")->execute([$idHoja]);
            } catch (\Throwable $e) {
            }
        }
        if ($this->idOSPrueba > 0) {
            try {
                $this->db->prepare("DELETE FROM programacion_muestreo WHERE id_orden_servicio = ?")->execute([$this->idOSPrueba]);
                $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$this->idOSPrueba]);
            } catch (\Throwable $e) {
            }
        }
        if ($this->idCotizacionPrueba > 0) {
            try {
                $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = ?")->execute([$this->idCotizacionPrueba]);
                $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$this->idCotizacionPrueba]);
            } catch (\Throwable $e) {
            }
        }
        if (!empty($this->secuenciasBackup)) {
            try {
                $this->db->exec("DELETE FROM secuencias_muestras");
                $stmtSeq = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (?, ?, ?)");
                foreach ($this->secuenciasBackup as $row) {
                    $stmtSeq->execute([$row['anio'], $row['tipo_muestra'], $row['ultimo_correlativo']]);
                }
            } catch (\Throwable $e) {
            }
        }
        parent::tearDown();
    }

    public function testVistaOrdenesServicioFlujoDirectoYModal40BEnLinea(): void {
        $viewPath = dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php';
        $this->assertFileExists($viewPath);
        $html = file_get_contents($viewPath);

        // 1. Apertura directa de RT-FM-13 sin pasar por el diálogo previo abrirModalDecisionMuestreo
        $this->assertStringContainsString('abrirModalHojaSolicitud(idOS, codigoOS, 0, true)', $html);
        $this->assertStringContainsString('abrirModalHojaSolicitud(idOSModalActual, codigoOSModalActual, 0, true)', $html);
        $this->assertStringNotContainsString('onclick="abrirModalDecisionMuestreo', $html);

        // 2. Selector interactivo MC / MS al lado de + Agregar Muestra
        $this->assertStringContainsString('id="hs_prefijo_seleccionado"', $html);
        $this->assertStringContainsString('id="btn_prefijo_mc"', $html);
        $this->assertStringContainsString('id="btn_prefijo_ms"', $html);
        $this->assertStringContainsString("seleccionarPrefijoMuestraModal('MC')", $html);
        $this->assertStringContainsString("seleccionarPrefijoMuestraModal('MS')", $html);

        // 3. Modal 40 B en línea asociado a RT-FM-13 con botón Guardar y Cerrar 40 B (Finalizado)
        $this->assertStringContainsString('id="modalFm40B"', $html);
        $this->assertStringContainsString('id="fm40b_id_hoja_solicitud"', $html);
        $this->assertStringContainsString('onclick="abrirModalFm40B(', $html);
        $this->assertStringContainsString("guardarFm40bModal('finalizar')", $html);
    }

    public function testHojaSolicitudDatosAjaxDevuelveCorrelativosMCyMSyRespetaPrefijoElegido(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [
            'id_os' => $this->idOSPrueba,
            'nueva' => 1,
        ];

        $hsCtrl = new HojasServicioControlador();
        ob_start();
        $hsCtrl->hojaSolicitudDatosAjax(new Peticion(), new Respuesta());
        $json = ob_get_clean();

        $res = json_decode($json, true);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status'] ?? null);
        $this->assertArrayHasKey('siguiente_consecutivo_mc', $res);
        $this->assertArrayHasKey('siguiente_consecutivo_ms', $res);
        $this->assertGreaterThanOrEqual(1, (int)$res['siguiente_consecutivo_mc']);
        $this->assertGreaterThanOrEqual(1, (int)$res['siguiente_consecutivo_ms']);

        // Elegir MS explícitamente desde el toggle de #modalHojaSolicitud al guardar
        $anio2 = date('y');
        $idHojaMS = 0;
        $okMS = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetallePrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'prefijo_seleccionado' => 'MS',
            'nombre_persona_toma_muestra' => 'Cliente / Entregada por Cliente',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MS-0001-{$anio2}", 'descripcion' => 'Muestra entregada en laboratorio', 'info_importante' => 'Recepción Lab Central', 'ensayo_id' => 1]
            ]),
        ], $idHojaMS);

        $this->assertTrue($okMS, 'Debe guardar hoja RT-FM-13 con prefijo MS elegido en el modal.');
        $this->assertGreaterThan(0, $idHojaMS);
        $this->hojasCreadas[] = $idHojaMS;

        $stmtM = $this->db->prepare("SELECT muestras_json FROM hojas_solicitud WHERE id = ?");
        $stmtM->execute([$idHojaMS]);
        $muestrasGuardadas = json_decode((string)$stmtM->fetchColumn(), true);
        $this->assertNotEmpty($muestrasGuardadas);
        $this->assertStringStartsWith('MS-', $muestrasGuardadas[0]['nombre_muestra'] ?? '');
    }

    public function testFm40BPorCadaHojaRTFM13GuardarYCerrarFinalizado(): void {
        $stmtDet2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:cot, 1, 'Densidad de Campo Nuclear ASTM D6938', 2, 150.00, 300.00)");
        $stmtDet2->execute(['cot' => $this->idCotizacionPrueba]);
        $idDetalle2 = (int)$this->db->lastInsertId();

        $anio2 = date('y');
        // Crear 2 hojas RT-FM-13 distintas en la misma Orden de Servicio
        $idHoja1 = 0;
        $ok1 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetallePrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'prefijo_seleccionado' => 'MC',
            'nombre_persona_toma_muestra' => 'Técnico Campo 1',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MC-0001-{$anio2}", 'descripcion' => 'Cilindro Zona A', 'info_importante' => 'Muestreo en Obra', 'ensayo_id' => 1]
            ]),
        ], $idHoja1);
        $this->assertTrue($ok1);
        $this->assertGreaterThan(0, $idHoja1);
        $this->hojasCreadas[] = $idHoja1;

        $idHoja2 = 0;
        $ok2 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $idDetalle2,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'prefijo_seleccionado' => 'MC',
            'nombre_persona_toma_muestra' => 'Técnico Campo 2',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MC-0002-{$anio2}", 'descripcion' => 'Cilindro Zona B', 'info_importante' => 'Muestreo en Obra', 'ensayo_id' => 1]
            ]),
        ], $idHoja2);
        $this->assertTrue($ok2);
        $this->assertGreaterThan(0, $idHoja2);
        $this->assertNotSame($idHoja1, $idHoja2);
        $this->hojasCreadas[] = $idHoja2;

        $idTec = (int)$this->db->query("SELECT id FROM tecnicos LIMIT 1")->fetchColumn();
        if ($idTec <= 0) {
            $this->db->exec("INSERT INTO tecnicos (nombre, activo) VALUES ('Técnico Test 40B', 1)");
            $idTec = (int)$this->db->lastInsertId();
        }
        $idVeh = (int)$this->db->query("SELECT id FROM vehiculos LIMIT 1")->fetchColumn();
        if ($idVeh <= 0) {
            $this->db->exec("INSERT INTO vehiculos (marca, modelo, placa, activo) VALUES ('Toyota', 'Hilux', 'M-TEST40B', 1)");
            $idVeh = (int)$this->db->lastInsertId();
        }

        $osCtrl = new OrdenesServicioControlador();

        // Guardar 40 B para Hoja 1 como En Campo y luego cerrarlo como Finalizado vía AJAX
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'id_hoja_solicitud' => $idHoja1,
            'accion_muestreo' => 'finalizar',
            'fecha_ida' => '2026-11-16 08:00:00',
            'fecha_llegada' => '2026-11-16 12:00:00',
            'id_tecnico' => $idTec,
            'id_vehiculo' => $idVeh,
            'lugar_muestreo' => 'Frente Norte - Hoja 1',
            'cantidad_muestras_est' => '3',
            'num_muestreadores' => 2,
            'observaciones_campo' => 'Cierre 40 B Hoja 1',
            'chk' => ['seg_0_chk_a' => '1', 'mue_0_chk_a' => '1'],
        ];
        ob_start();
        $osCtrl->guardarFm40bAjax(new Peticion(), new Respuesta());
        $jsonSave1 = ob_get_clean();
        $resSave1 = json_decode($jsonSave1, true);
        $this->assertSame('success', $resSave1['status'] ?? null);
        $this->assertSame('Finalizado', $resSave1['estado_muestreo'] ?? null);
        $this->assertStringContainsString("id_hoja={$idHoja1}", $resSave1['imprimir_url'] ?? '');

        // Guardar 40 B independiente para Hoja 2 en estado "En Campo"
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'id_hoja_solicitud' => $idHoja2,
            'accion_muestreo' => 'guardar',
            'fecha_ida' => '2026-11-16 14:00:00',
            'fecha_llegada' => '2026-11-16 17:00:00',
            'id_tecnico' => $idTec,
            'id_vehiculo' => $idVeh,
            'lugar_muestreo' => 'Frente Sur - Hoja 2',
            'cantidad_muestras_est' => '5',
            'num_muestreadores' => 1,
            'observaciones_campo' => 'En progreso Hoja 2',
            'chk' => ['seg_2_chk_a' => '1'],
        ];
        ob_start();
        $osCtrl->guardarFm40bAjax(new Peticion(), new Respuesta());
        $jsonSave2 = ob_get_clean();
        $resSave2 = json_decode($jsonSave2, true);
        $this->assertSame('success', $resSave2['status'] ?? null);
        $this->assertSame('En Campo', $resSave2['estado_muestreo'] ?? null);

        // Consultar fm40b-datos para Hoja 1 y Hoja 2 y comprobar independencia y vínculo exacto
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_os' => $this->idOSPrueba, 'id_hoja' => $idHoja1];
        ob_start();
        $osCtrl->fm40bDatosAjax(new Peticion(), new Respuesta());
        $resGet1 = json_decode(ob_get_clean(), true);
        $this->assertSame('success', $resGet1['status'] ?? null);
        $this->assertSame('Frente Norte - Hoja 1', $resGet1['fm40b']['lugar_muestreo'] ?? null);
        $this->assertSame('Finalizado', $resGet1['fm40b']['estado_muestreo'] ?? null);
        $this->assertSame($idHoja1, (int)($resGet1['fm40b']['id_hoja_solicitud'] ?? 0));

        // Verificar que OrdenServicioModelo::obtenerPorId($idOS, $idHoja2) devuelve el FM-40 B específico de Hoja 2
        $osConHoja2 = $this->osModelo->obtenerPorId($this->idOSPrueba, $idHoja2);
        $this->assertIsArray($osConHoja2);
        $this->assertSame('Frente Sur - Hoja 2', $osConHoja2['programacion_muestreo']['lugar_muestreo'] ?? null);
        $this->assertSame($idHoja2, (int)($osConHoja2['programacion_muestreo']['id_hoja_solicitud'] ?? 0));

        // Verificar que obtenerTodas() adjunta cada 40 B a su respectiva hoja dentro de hojas_solicitud
        $listado = $this->osModelo->obtenerTodas($this->codigoOSPrueba);
        $this->assertNotEmpty($listado);
        $osRow = null;
        foreach ($listado as $row) {
            if ((int)$row['id'] === $this->idOSPrueba) {
                $osRow = $row;
                break;
            }
        }
        $this->assertNotNull($osRow);
        $hojasMap = [];
        foreach ($osRow['hojas_servicio'] ?? [] as $hRow) {
            $hojasMap[(int)$hRow['id']] = $hRow;
        }
        $this->assertSame('Finalizado', $hojasMap[$idHoja1]['fm40b']['estado_muestreo'] ?? null);
        $this->assertSame('En Campo', $hojasMap[$idHoja2]['fm40b']['estado_muestreo'] ?? null);

        // Verificar que ReporteModelo::generarReporteModulo('fm40b', ...) incluye reg_rt13 e id_hoja en _pdfs
        $repModelo = new ReporteModelo();
        $rep40b = $repModelo->generarReporteModulo('fm40b', ['q' => $this->codigoOSPrueba]);
        $this->assertCount(2, $rep40b['rows']);
        $urls = [];
        foreach ($rep40b['rows'] as $rowRep) {
            foreach ($rowRep['_pdfs'] ?? [] as $pdfItem) {
                $urls[] = $pdfItem['url'] ?? '';
            }
        }
        $linksJoined = implode(' | ', $urls);
        $this->assertStringContainsString("id_hoja={$idHoja1}", $linksJoined);
        $this->assertStringContainsString("id_hoja={$idHoja2}", $linksJoined);
    }
}
