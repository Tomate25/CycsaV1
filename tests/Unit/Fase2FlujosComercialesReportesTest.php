<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;
use Cycsa\Modulos\Reportes\Modelos\ReporteModelo;
use Cycsa\Modulos\Reportes\Controladores\ReportesControlador;
use PDO;
use ZipArchive;

class Fase2RespuestaSpy extends Respuesta {
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

class Fase2FlujosComercialesReportesTest extends TestCase {

    private PDO $db;
    private array $sessionBackup = [];
    private string $respGlobalBackup = '';
    private int $idCliente = 0;
    private int $idUsuario = 0;
    private int $idProdConcreto = 0;
    private int $idProdCompactacion = 0;
    private array $cotizacionesCreadas = [];
    private array $osCreadas = [];
    private array $hojasCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $mig039 = dirname(__DIR__, 2) . '/database/migrations/039_plan_maestro_lab_flujos_formatos.php';
        if (file_exists($mig039)) {
            require_once $mig039;
            if (function_exists('ejecutarMigracion039')) {
                ejecutarMigracion039($this->db);
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->sessionBackup = $_SESSION ?? [];

        $this->idUsuario = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($this->idUsuario <= 0) {
            $this->db->exec("INSERT INTO usuarios (id_rol, nombre, email, password, activo) VALUES (1, 'Admin Fase2', 'fase2@test.local', 'hash', 1)");
            $this->idUsuario = (int)$this->db->lastInsertId();
        }

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($this->idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Fase2 SA', 'J0310000099999', 'cliente.fase2@test.local', '22229999')");
            $this->idCliente = (int)$this->db->lastInsertId();
        }

        $this->idProdConcreto = (int)$this->db->query("SELECT id FROM productos WHERE nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%' LIMIT 1")->fetchColumn();
        if ($this->idProdConcreto <= 0) {
            $this->idProdConcreto = (int)$this->db->query("SELECT id FROM productos LIMIT 1")->fetchColumn();
        }

        $this->idProdCompactacion = (int)$this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%D6938%' OR norma_astm LIKE '%D1556%' OR nombre_comercial LIKE '%Densímetro%' OR nombre_comercial LIKE '%Compactaci%' LIMIT 1")->fetchColumn();
        if ($this->idProdCompactacion <= 0) {
            $this->idProdCompactacion = $this->idProdConcreto;
        }

        $this->respGlobalBackup = ConfigCalculoLabServicio::obtener('cotizacion_responsable_interno_id', '');

        $_SESSION = [
            'usuario_id' => $this->idUsuario,
            'usuario_rol' => 1,
            'usuario_nombre' => 'Supervisor Fase 2',
            'csrf_token' => bin2hex(random_bytes(16)),
        ];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    }

    protected function tearDown(): void {
        ConfigCalculoLabServicio::guardar('cotizacion_responsable_interno_id', $this->respGlobalBackup);

        foreach ($this->hojasCreadas as $idH) {
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = {$idH}");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id = {$idH}");
            } catch (\Throwable $e) {}
        }
        foreach ($this->osCreadas as $idOs) {
            try {
                $this->db->exec("DELETE FROM ordenes_servicio_versiones WHERE id_os = {$idOs}");
                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = {$idOs}");
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$idOs}");
            } catch (\Throwable $e) {}
        }
        foreach ($this->cotizacionesCreadas as $idCot) {
            try {
                $this->db->exec("DELETE FROM cotizacion_versiones WHERE id_cotizacion = {$idCot}");
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$idCot}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$idCot}");
            } catch (\Throwable $e) {}
        }

        $_SESSION = $this->sessionBackup;
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    private function crearCotizacionBase(string $estado = 'Borrador', int $version = 0, ?int $idResp = null): int {
        $cod = 'F2-' . substr(bin2hex(random_bytes(5)), 0, 10);
        $stmt = $this->db->prepare("
            INSERT INTO cotizaciones
                (codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, tipo_moneda,
                 id_usuario_creador, id_responsable_interno, atencion_a, nombre_proyecto, direccion_proyecto,
                 condicion_pago, tiempo_entrega, vigencia_oferta, subtotal, descuento, impuesto, total, estado, version)
            VALUES
                (:cod, :cli, :cli2, :cli3, 'contado', 1,
                 :usr, :resp, 'Ing. Fase2', 'Proyecto Fase 2', 'Managua',
                 'Contado', '3 días', '15 días', 1000, 0, 150, 1150, :est, :ver)
        ");
        $stmt->execute([
            'cod' => $cod,
            'cli' => $this->idCliente,
            'cli2' => $this->idCliente,
            'cli3' => $this->idCliente,
            'usr' => $this->idUsuario,
            'resp' => $idResp,
            'est' => $estado,
            'ver' => $version,
        ]);
        $idCot = (int)$this->db->lastInsertId();
        $this->cotizacionesCreadas[] = $idCot;

        $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:id, :prod, 'Ensayo Concreto Fase2', 'CYCSA-RT-FM-22 A', 'ASTM C39', 2, 500, 1000)")
                 ->execute(['id' => $idCot, 'prod' => $this->idProdConcreto]);

        return $idCot;
    }

    /**
     * 2.5 — Versionado de Órdenes de Servicio: snapshot, incremento de versión, motivo y reseteo a Revisión.
     */
    public function testVersionadoOrdenServicioAlEditarOSAprobada(): void {
        $idCot = $this->crearCotizacionBase('Aprobada por Cliente', 1);
        $osModelo = new OrdenServicioModelo();

        $idOs = $osModelo->crear([
            'codigo_os' => 'OS-VER-' . uniqid(),
            'id_cotizacion' => $idCot,
            'id_cliente' => $this->idCliente,
            'atencion_a' => 'Ing. Inicial',
            'nombre_proyecto' => 'Proyecto Original v1',
            'forma_pago' => 'Contado',
            'estado' => 'Aprobada',
        ]);
        $this->osCreadas[] = $idOs;

        $osV1 = $osModelo->obtenerPorId($idOs);
        $this->assertEquals(1, (int)$osV1['version']);
        $this->assertEmpty($osModelo->obtenerHistorialVersiones($idOs));

        // Editar vía controlador cambiando datos comerciales + nota de modificación
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $idOs,
            'atencion_a' => 'Ing. Actualizado v2',
            'nombre_proyecto' => 'Proyecto Modificado v2',
            'forma_pago' => 'Crédito 30 días',
            'condicion_comercial' => 'credito',
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'notas_condiciones' => 'Cambio de condiciones de entrega',
            'motivo_cambio_version' => 'Cliente solicitó cambio de razón social de proyecto y crédito 30 días',
        ];
        $ctrl = new OrdenesServicioControlador();
        $resp = new Fase2RespuestaSpy();
        $ctrl->actualizar(new Peticion(), $resp);

        $osV2 = $osModelo->obtenerPorId($idOs);
        $this->assertEquals(2, (int)$osV2['version'], 'Debe incrementar versión a v2');
        $this->assertSame('Estado 2: Revision', $osV2['estado'], 'Debe resetear a Estado 2: Revision');
        $this->assertSame('Cliente solicitó cambio de razón social de proyecto y crédito 30 días', $osV2['motivo_cambio_version']);

        $historial = $osModelo->obtenerHistorialVersiones($idOs);
        $this->assertCount(1, $historial);
        $this->assertEquals(1, (int)$historial[0]['version']);
        $snap = json_decode((string)$historial[0]['snapshot_json'], true);
        $this->assertSame('Proyecto Original v1', $snap['nombre_proyecto']);
        $this->assertSame('Ing. Inicial', $snap['atencion_a']);

        // Verificar que las vistas de OS renderizan badge v2, textarea de motivo y tabla de historial
        $root = dirname(__DIR__, 2);
        $this->assertStringContainsString('motivo_cambio_version', file_get_contents($root . '/app/Modulos/OrdenesServicio/Vistas/editar.php'));
        $this->assertStringContainsString('Historial de Versiones de la O/S', file_get_contents($root . '/app/Modulos/OrdenesServicio/Vistas/detalle.php'));
        $this->assertStringContainsString("v<?= (int)(\$os['version'] ?? 1) ?>", file_get_contents($root . '/app/Modulos/OrdenesServicio/Vistas/index.php'));
    }

    /**
     * 2.9, 2.10, 2.11 — Cotizaciones: versión solo sube al salir al cliente,
     * aprobación interna diferenciada de aprobación cliente y responsable interno configurable.
     */
    public function testCotizacionesVersionadoAlSalirAprobacionInternaYResponsable(): void {
        $modelo = new CotizacionModelo();
        $ctrl = new CotizacionesControlador();

        // Crear borrador v0 con responsable interno específico ($this->idUsuario)
        $idCot = $this->crearCotizacionBase('Borrador', 0, $this->idUsuario);

        // 2.11: Verificar reglas de puedeAprobarInternamente()
        $cotArr = $modelo->obtenerPorId($idCot);
        $this->assertTrue(CotizacionesControlador::puedeAprobarInternamente($cotArr, 999, 1), 'Admin (rol 1) puede aprobar');
        $this->assertTrue(CotizacionesControlador::puedeAprobarInternamente($cotArr, $this->idUsuario, 2), 'Responsable asignado a la cotización puede aprobar');
        $this->assertFalse(CotizacionesControlador::puedeAprobarInternamente($cotArr, 888, 2), 'Usuario no asignado sin permiso no puede aprobar');

        // Permiso explícito cotizaciones.aprobar
        $_SESSION['usuario_permisos'] = ['cotizaciones' => ['aprobar' => 1, 'ver' => 1]];
        $this->assertTrue(CotizacionesControlador::puedeAprobarInternamente($cotArr, 888, 2), 'Usuario con permiso cotizaciones.aprobar puede aprobar');
        $_SESSION['usuario_permisos'] = [];

        // Responsable global configurado en config_calculo_lab
        ConfigCalculoLabServicio::guardar('cotizacion_responsable_interno_id', '555');
        $this->assertTrue(CotizacionesControlador::puedeAprobarInternamente($cotArr, 555, 2), 'Responsable interno global configurado puede aprobar');
        ConfigCalculoLabServicio::guardar('cotizacion_responsable_interno_id', '');

        // 2.9: Editar en Observada NO incrementa versión
        $this->db->prepare("UPDATE cotizaciones SET estado = 'Observada', motivo_observacion = 'Revisar notas' WHERE id = :id")->execute(['id' => $idCot]);
        $cab = [
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'condicion_comercial' => 'contado',
            'tipo_moneda' => 1,
            'atencion_a' => 'Ing. Fase2 Corregido',
            'nombre_proyecto' => 'Proyecto Fase 2',
            'direccion_proyecto' => 'Managua',
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'configuracion_notas' => null,
            'contactos' => null,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 1000.0,
            'descuento' => 0.0,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 150.0,
            'total' => 1150.0,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null,
        ];
        $dets = [[
            'id_producto' => $this->idProdConcreto,
            'descripcion' => 'Ensayo Concreto Corregido',
            'cantidad' => 2,
            'precio' => 500.0,
            'subtotal' => 1000.0,
        ]];
        $this->assertTrue($modelo->actualizarCotizacionCompleta($idCot, $cab, $dets));
        $cotPostObs = $modelo->obtenerPorId($idCot);
        $this->assertSame('En Revision', $cotPostObs['estado']);
        $this->assertEquals(0, (int)$cotPostObs['version'], 'Corregir Observada interna NO debe subir versión PDF');
        $this->assertEmpty($modelo->obtenerVersiones($idCot));

        // 2.10: Aprobación Interna por el responsable asignado ($this->idUsuario, rol 2 no-admin)
        $_SESSION['usuario_id'] = $this->idUsuario;
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id' => codificarId($idCot),
            'accion' => 'aprobar',
        ];
        $respRev = new Fase2RespuestaSpy();
        $ctrl->procesarRevision(new Peticion(), $respRev);

        $cotAprobInt = $modelo->obtenerPorId($idCot);
        $this->assertSame('Aprobada Internamente', $cotAprobInt['estado'], 'Aprobación interna debe pasar a Aprobada Internamente');
        $this->assertEquals($this->idUsuario, (int)$cotAprobInt['aprobacion_interna_por']);
        $this->assertNotEmpty($cotAprobInt['fecha_aprobacion_interna']);
        $this->assertEquals(0, (int)$cotAprobInt['version'], 'Aprobación interna aún no sale al cliente, versión sigue en 0');

        // 2.9: Despacho al cliente sube a v1
        $_SESSION['usuario_id'] = $this->idUsuario;
        $_SESSION['usuario_rol'] = 1;
        $this->assertTrue($modelo->marcarEnviadaAlCliente($idCot, $this->idUsuario));
        $cotEnv1 = $modelo->obtenerPorId($idCot);
        $this->assertSame('Enviada al Cliente', $cotEnv1['estado']);
        $this->assertEquals(1, (int)$cotEnv1['version'], 'Primera salida al cliente asigna v1');

        // Rechazo de cliente -> edición -> aprobación interna -> segundo envío al cliente -> sube a v2 y archiva v1
        $this->db->prepare("UPDATE cotizaciones SET estado = 'Rechazada por Cliente', motivo_rechazo_cliente = 'Ajustar tarifa' WHERE id = :id")->execute(['id' => $idCot]);
        $cab['subtotal'] = 900.0;
        $cab['impuesto'] = 135.0;
        $cab['total'] = 1035.0;
        $this->assertTrue($modelo->actualizarCotizacionCompleta($idCot, $cab, $dets));
        $cotTrasRechazo = $modelo->obtenerPorId($idCot);
        $this->assertSame('En Revision', $cotTrasRechazo['estado']);
        $this->assertEquals(1, (int)$cotTrasRechazo['version']);
        $this->assertNotNull($modelo->obtenerVersionHistorica($idCot, 1), 'Debe archivar snapshot v1 antes de sobreescribir');

        $this->assertTrue($modelo->registrarAprobacionInterna($idCot, $this->idUsuario));
        $this->assertTrue($modelo->marcarEnviadaAlCliente($idCot, $this->idUsuario));
        $cotEnv2 = $modelo->obtenerPorId($idCot);
        $this->assertSame('Enviada al Cliente', $cotEnv2['estado']);
        $this->assertEquals(2, (int)$cotEnv2['version'], 'Segunda salida al cliente sube a v2');

        // 2.10: Diferenciación de botones en detalle.php
        $htmlDetalle = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/detalle.php');
        $this->assertStringContainsString('Aprobación Interna', $htmlDetalle);
        $this->assertStringContainsString('Aprobar Cotización (Cliente)', $htmlDetalle);
    }

    /**
     * 2.13 & 2.14 — Alerta de ensayo fuera de alcance mensual (vigencia, whitelist y cuota_mensual)
     * expuesta en OrdenServicioModelo y HojasServicioControlador::hojaSolicitudDatosAjax().
     */
    public function testAlertaAlcanceMensualVigenciaWhitelistYCuota(): void {
        $idCot = $this->crearCotizacionBase('Aprobada por Cliente', 1);
        $idDet = (int)$this->db->query("SELECT id FROM cotizacion_detalles WHERE id_cotizacion = {$idCot} LIMIT 1")->fetchColumn();

        $osModelo = new OrdenServicioModelo();
        $idOs = $osModelo->crear([
            'codigo_os' => 'OS-ALC-' . uniqid(),
            'id_cotizacion' => $idCot,
            'id_cliente' => $this->idCliente,
            'tipo_contrato' => 'Marco',
            'es_multimes' => 1,
            'duracion_meses' => 3,
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-11-01',
            'periodicidad' => 'Mensual',
            'estado' => 'Aprobada',
        ]);
        $this->osCreadas[] = $idOs;

        // Habilitar whitelist con cuota_mensual = 1
        $this->db->prepare("INSERT INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle, cuota_mensual) VALUES (:os, :det, 1)")
                 ->execute(['os' => $idOs, 'det' => $idDet]);

        // 1) Contrato vencido al 2026-11-20
        $alertaVenc = $osModelo->verificarAlcanceMensual($idOs, $this->idProdConcreto, '2026-11-20');
        $this->assertTrue($alertaVenc['tiene_alerta']);
        $this->assertSame('danger', $alertaVenc['nivel']);
        $this->assertStringContainsString('vencido', strtolower(implode(' ', $alertaVenc['mensajes'])));

        // 2) Producto fuera de whitelist
        $alertaFuera = $osModelo->verificarAlcanceMensual($idOs, 999999, '2026-10-15');
        $this->assertTrue($alertaFuera['tiene_alerta']);
        $this->assertFalse($alertaFuera['en_alcance']);
        $this->assertSame('danger', $alertaFuera['nivel']);

        // 3) Consumo alcanza cuota mensual (1 hoja en 2026-10 con cuota_mensual = 1)
        $this->db->prepare("
            INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, codigo_documento, numero_registro, fecha_hora_toma_muestra, muestras_json)
            VALUES (:os, :det, :prod, :ens, 'CYCSA-RT-FM-13', 'REG-ALC-1', '2026-10-10 09:00:00', '[]')
        ")->execute(['os' => $idOs, 'det' => $idDet, 'prod' => $this->idProdConcreto, 'ens' => $this->idProdConcreto]);
        $this->hojasCreadas[] = (int)$this->db->lastInsertId();

        $alertaCuota = $osModelo->verificarAlcanceMensual($idOs, $this->idProdConcreto, '2026-10-15');
        $this->assertTrue($alertaCuota['tiene_alerta']);
        $this->assertFalse($alertaCuota['en_alcance']);
        $this->assertEquals(1, $alertaCuota['consumo_mes']);
        $this->assertEquals(1, $alertaCuota['cuota_mensual']);
        $this->assertSame('danger', $alertaCuota['nivel']);

        // 4) Expuesto vía HojasServicioControlador::hojaSolicitudDatosAjax()
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_os' => $idOs];
        $hsCtrl = new HojasServicioControlador();
        $respAjax = new Fase2RespuestaSpy();
        $hsCtrl->hojaSolicitudDatosAjax(new Peticion(), $respAjax);
        $this->assertSame('success', $respAjax->jsonRespuesta['status'] ?? null);
        $this->assertArrayHasKey('alerta_alcance_mensual', $respAjax->jsonRespuesta);
        $this->assertTrue((bool)$respAjax->jsonRespuesta['alerta_alcance_mensual']['tiene_alerta']);
    }

    /**
     * 2.4 & 1.7 — Recepción 'Nombre muestra' editable + columnas compactación/densímetro + redondeo 0.5 cm.
     */
    public function testRecepcionNombreMuestraYColumnasCompactacionYRedondeo(): void {
        $idCot = $this->crearCotizacionBase('Aprobada por Cliente', 1);
        $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:id, :prod, 'Densidad In Situ por Densímetro Nuclear (Compactación)', 'CYCSA-RT-FM-22 B', 'ASTM D6938', 2, 1000, 2000)")
                 ->execute(['id' => $idCot, 'prod' => $this->idProdCompactacion]);
        $idDetComp = (int)$this->db->lastInsertId();

        $osModelo = new OrdenServicioModelo();
        $idOs = $osModelo->crear([
            'codigo_os' => 'OS-REC-' . uniqid(),
            'id_cotizacion' => $idCot,
            'id_cliente' => $this->idCliente,
            'estado' => 'Aprobada',
        ]);
        $this->osCreadas[] = $idOs;

        $codMuestra = 'MC-F2-' . substr(uniqid(), -4);
        $this->db->prepare("
            INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, codigo_documento, numero_registro, muestras_json)
            VALUES (:os, :det, :prod, :ens, 'CYCSA-RT-FM-13', 'REG-REC-F2', :mjson)
        ")->execute([
            'os' => $idOs,
            'det' => $idDetComp,
            'prod' => $this->idProdCompactacion,
            'ens' => $this->idProdCompactacion,
            'mjson' => json_encode([['nombre_muestra' => $codMuestra, 'descripcion' => 'Inicial']]),
        ]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->hojasCreadas[] = $idHoja;

        $idPlantillaExistente = (int)$this->db->query("SELECT id FROM plantillas LIMIT 1")->fetchColumn();
        $plantillaParam = $idPlantillaExistente > 0 ? $idPlantillaExistente : null;
        $this->db->prepare("INSERT INTO hojas_solicitud_detalles (id_hoja_solicitud, codigo_muestra, descripcion_ubicacion, ensayo_id, plantilla_id, orden) VALUES (:hid, :cod, 'Inicial', :ens, :plt, 1)")
                 ->execute(['hid' => $idHoja, 'cod' => $codMuestra, 'ens' => $this->idProdCompactacion, 'plt' => $plantillaParam]);

        // 1. datosRecepcionAjax debe incluir 'Nombre muestra' + columnas de compactación/densímetro
        $hsCtrl = new HojasServicioControlador();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_hoja' => $idHoja];
        $respGet = new Fase2RespuestaSpy();
        $hsCtrl->datosRecepcionAjax(new Peticion(), $respGet);

        $this->assertSame('success', $respGet->jsonRespuesta['status'] ?? null);
        $nombresCampos = array_column($respGet->jsonRespuesta['campos_recepcion'] ?? [], 'nombre');
        $this->assertContains('Nombre muestra', $nombresCampos);
        $this->assertContains('Capa (N°)', $nombresCampos);
        $this->assertContains('Espesor (cm)', $nombresCampos);
        $this->assertContains('Profundidad (cm)', $nombresCampos);
        $this->assertContains('P.V.S Max (kg/m³)', $nombresCampos);
        $this->assertContains('Humedad Óptima', $nombresCampos);
        $this->assertContains('P.V.S.Sitio (kg/m³)', $nombresCampos);
        $this->assertContains('Humedad sitio', $nombresCampos);
        $this->assertContains('Compactación', $nombresCampos);
        $this->assertContains('Fecha de muestreo', $nombresCampos);

        // 2. guardarDatosRecepcion persiste 'Nombre muestra' en datos_recepcion_json y sincroniza a la fila de muestra
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHoja,
            'muestras_recepcion_json' => json_encode([
                $codMuestra => [
                    'Nombre muestra' => 'Terraplén Tramo Est. 0+120',
                    'Capa (N°)' => 'Capa 2',
                    'Espesor (cm)' => '20',
                    'Compactación' => '98.5',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $respSave = new Fase2RespuestaSpy();
        $hsCtrl->guardarDatosRecepcion(new Peticion(), $respSave);
        $this->assertSame('success', $respSave->jsonRespuesta['status'] ?? null);

        $stmtDet = $this->db->prepare("SELECT descripcion_ubicacion, datos_recepcion_json FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid LIMIT 1");
        $stmtDet->execute(['hid' => $idHoja]);
        $filaDet = $stmtDet->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Terraplén Tramo Est. 0+120', $filaDet['descripcion_ubicacion']);
        $decRec = json_decode((string)$filaDet['datos_recepcion_json'], true);
        $this->assertSame('Terraplén Tramo Est. 0+120', $decRec['Nombre muestra'] ?? null);
        $this->assertSame('Capa 2', $decRec['Capa (N°)'] ?? null);

        // 1.7: Verificar fórmula de redondeo al 0.5 cm en OrdenesServicio/Vistas/index.php
        $osIndexHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php');
        $this->assertStringContainsString('Math.round(parseFloat(inpIn.value) * 2.54 * 2) / 2', $osIndexHtml);
    }

    /**
     * 2.17 — Enlaces directos Hoja RT-FM-60 visibles en OrdenesServicio y HojasServicio.
     */
    public function testEnlacesDirectosFm60EnVistasOsYHs(): void {
        $root = dirname(__DIR__, 2);
        $vistas = [
            $root . '/app/Modulos/OrdenesServicio/Vistas/index.php',
            $root . '/app/Modulos/OrdenesServicio/Vistas/detalle.php',
            $root . '/app/Modulos/HojasServicio/Vistas/index.php',
        ];
        foreach ($vistas as $ruta) {
            $contenido = file_get_contents($ruta);
            $this->assertStringContainsString('/laboratorio/imprimir-solicitud?id_hoja=', $contenido, "Debe incluir enlace directo RT-FM-60 en {$ruta}");
            $this->assertStringContainsString('Hoja RT-FM-60', $contenido, "Debe mostrar etiqueta Hoja RT-FM-60 en {$ruta}");
        }
    }

    /**
     * 2.21 — Reportería mensual consolidada (14° mini-módulo), filtro ?mes=YYYY-MM y exportación .xlsx.
     */
    public function testReporteriaMensualFiltroMesYExportacionXlsx(): void {
        $idCot = $this->crearCotizacionBase('Aprobada por Cliente', 1);
        $osModelo = new OrdenServicioModelo();
        $idOs = $osModelo->crear([
            'codigo_os' => 'OS-REPM-' . uniqid(),
            'id_cotizacion' => $idCot,
            'id_cliente' => $this->idCliente,
            'fecha_emision' => '2026-11-18',
            'estado' => 'Aprobada',
        ]);
        $this->osCreadas[] = $idOs;

        $modelo = new ReporteModelo();
        $catalogo = $modelo->obtenerCatalogoMiniModulos();
        $this->assertArrayHasKey('reporteria_mensual', $catalogo);
        $this->assertCount(14, $catalogo);

        // Filtro ?mes=2026-11 auto-expande desde/hasta
        $repMes = $modelo->generarReporteModulo('reporteria_mensual', ['mes' => '2026-11']);
        $this->assertSame('2026-11-01', $repMes['filtros']['desde']);
        $this->assertSame('2026-11-30', $repMes['filtros']['hasta']);
        $this->assertNotEmpty($repMes['kpis']);
        $this->assertNotEmpty($repMes['rows']);
        $mesesEnFilas = array_column($repMes['rows'], 'mes');
        $this->assertContains('2026-11', $mesesEnFilas);

        // Subvista por cliente y contrato
        $repCli = $modelo->generarReporteModulo('reporteria_mensual', ['mes' => '2026-11', 'subvista' => 'por_cliente']);
        $this->assertNotEmpty($repCli['columns']);
        $this->assertNotEmpty($repCli['rows']);

        // Exportación .xlsx vía ReportesControlador con ?mes=2026-11
        $ctrlSpy = new class extends ReportesControlador {
            public string $archivo = '';
            public string $binario = '';
            protected function emitirBinarioExcel(string $nombreArchivo, string $contenidoXlsx): void {
                $this->archivo = $nombreArchivo;
                $this->binario = $contenidoXlsx;
            }
        };

        $_GET = ['modulo' => 'reporteria_mensual', 'mes' => '2026-11'];
        $ctrlSpy->exportarExcel(new Peticion(), new Fase2RespuestaSpy());
        $this->assertStringStartsWith('reporte_reporteria_mensual_', $ctrlSpy->archivo);
        $this->assertStringEndsWith('.xlsx', $ctrlSpy->archivo);
        $this->assertStringStartsWith('PK', $ctrlSpy->binario);

        $tmp = tempnam(sys_get_temp_dir(), 'rep_mensual_');
        file_put_contents($tmp, $ctrlSpy->binario);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $sheetXml = (string)$zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('2026-11', $sheetXml);
        $this->assertStringContainsString('Mes: 2026-11', $sheetXml);

        // Verificar selector <input type="month" id="f_mes" name="mes"> en Reportes/Vistas/ver.php
        $verHtml = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Reportes/Vistas/ver.php');
        $this->assertStringContainsString('type="month"', $verHtml);
        $this->assertStringContainsString('name="mes"', $verHtml);
    }
}
