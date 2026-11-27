<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio;
use Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Servicios\DocumentoRgFm39Servicio;
use Cycsa\Modulos\OrdenesServicio\Servicios\ValidacionOrdenServicioServicio;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;

class ValidacionRgFm39V1R1Test extends TestCase
{
    private PDO $db;
    private int $idCliente = 0;
    private int $idAdmin = 0;
    private int $idLab = 0;
    private int $idProd = 0;
    /** @var array<int> */
    private array $osCreadas = [];
    /** @var array<int> */
    private array $cotCreadas = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $this->db = Conexion::obtenerInstancia();

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1")->fetchColumn();
        $this->idAdmin = (int)$this->db->query("SELECT id FROM usuarios WHERE id_rol = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
        $this->idLab = (int)$this->db->query("SELECT id FROM usuarios WHERE id_rol IN (3, 4, 6) ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($this->idLab <= 0) {
            $this->idLab = $this->idAdmin;
        }
        $this->idProd = (int)$this->db->query("SELECT id FROM productos WHERE activo = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();

        if ($this->idCliente <= 0 || $this->idAdmin <= 0 || $this->idProd <= 0) {
            $this->markTestSkipped('Se requiere cliente, usuario admin y producto activo en cycsa_test.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->osCreadas as $idOs) {
            $this->db->prepare("DELETE FROM notificaciones_ordenes_servicio WHERE id_os = ?")->execute([$idOs]);
            $this->db->prepare("DELETE FROM ordenes_servicio_validaciones_historial WHERE id_os = ?")->execute([$idOs]);
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$idOs]);
        }
        foreach ($this->cotCreadas as $idCot) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = ?")->execute([$idCot]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$idCot]);
        }
        parent::tearDown();
    }

    /**
     * @return array{id_os: int, id_cot: int, id_det: int}
     */
    private function crearOrdenPrueba(bool $esMensual, ?string $fechaIni = null, ?string $fechaFin = null, ?string $resultadosJson = null): array
    {
        $suf = substr(uniqid('', true), -6);
        $this->db->prepare("
            INSERT INTO cotizaciones (codigo, id_cliente, id_cliente_factura, id_cliente_informe, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, estado, es_mensual, duracion_meses, subtotal, impuesto, total)
            VALUES (:cod, :c1, :c2, :c3, :u, 'Ing. Prueba', 'Proyecto RG39 V1R1', 'Managua', 'Contado', 'Aprobada por Cliente', :em, :dm, 1000, 150, 1150)
        ")->execute([
            'cod' => 'COT-V1R1-' . $suf,
            'c1' => $this->idCliente,
            'c2' => $this->idCliente,
            'c3' => $this->idCliente,
            'u' => $this->idAdmin,
            'em' => $esMensual ? 1 : 0,
            'dm' => $esMensual ? 1 : null,
        ]);
        $idCot = (int)$this->db->lastInsertId();
        $this->cotCreadas[] = $idCot;

        $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, norma_astm, unidad_medida, cantidad, precio_unitario, subtotal, resultados_json)
            VALUES (:id_cot, :id_p, 'Resistencia a Compresión del Concreto', 'ASTM C39', 'Unidad', 3, 1000, 1000, :rjson)
        ")->execute([
            'id_cot' => $idCot,
            'id_p' => $this->idProd,
            'rjson' => $resultadosJson,
        ]);
        $idDet = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, id_cliente_factura, id_cliente_informe, tipo_contrato, es_multimes, duracion_meses, fecha_inicio, fecha_fin, periodicidad, fecha_emision, estado)
            VALUES (:cod, :id_cot, :c1, :c2, :c3, :tc, :em, :dm, :fi, :ff, 'Mensual', CURRENT_DATE, 'Estado 3: Ingreso Directo')
        ")->execute([
            'cod' => 'OS-V1R1-' . $suf,
            'id_cot' => $idCot,
            'c1' => $this->idCliente,
            'c2' => $this->idCliente,
            'c3' => $this->idCliente,
            'tc' => $esMensual ? 'Marco' : 'Puntual',
            'em' => $esMensual ? 1 : 0,
            'dm' => $esMensual ? 1 : null,
            'fi' => $fechaIni,
            'ff' => $fechaFin,
        ]);
        $idOs = (int)$this->db->lastInsertId();
        $this->osCreadas[] = $idOs;

        return ['id_os' => $idOs, 'id_cot' => $idCot, 'id_det' => $idDet];
    }

    public function testFlujoCompleto4FirmasAutomaticasInmutabilidadYCorreccionAutorizadaEnPuntual(): void
    {
        $jsonPendiente = json_encode(['filas' => [['Carga (lb)' => '80000']], 'revision' => ['estado' => 'en_revision']]);
        $creada = $this->crearOrdenPrueba(false, null, null, (string)$jsonPendiente);
        $idOs = $creada['id_os'];

        // Casilla 1: Autorizado por Administración
        $r1 = ValidacionOrdenServicioServicio::registrarAutorizacionAdmin($this->db, $idOs, $this->idAdmin, 1);
        $this->assertTrue($r1['ok'], $r1['mensaje']);

        // Punto 17: No permitir sobrescritura directa de Casilla 1
        $r1Dup = ValidacionOrdenServicioServicio::registrarAutorizacionAdmin($this->db, $idOs, $this->idAdmin, 1);
        $this->assertFalse($r1Dup['ok']);

        // Casilla 2: Recibido por Laboratorio / Recepción
        $r2 = ValidacionOrdenServicioServicio::registrarRecepcionLab($this->db, $idOs, $this->idLab, 6);
        $this->assertTrue($r2['ok'], $r2['mensaje']);

        // Mientras haya informes pendientes, NO pasa a Ejecutada
        $evPend = ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($this->db, $idOs);
        $this->assertFalse($evPend['transicion_ejecutada']);

        // Aprobar el 100% de los informes -> debe cambiar automáticamente a Ejecutada, llenar Casilla 3 y notificar a Administración/Contabilidad
        $jsonAprob = json_encode(['filas' => [['Carga (lb)' => '80000']], 'revision' => ['estado' => 'aprobada']]);
        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = :j WHERE id = :id")
            ->execute(['j' => $jsonAprob, 'id' => $creada['id_det']]);

        $evOk = ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($this->db, $idOs);
        $this->assertTrue($evOk['transicion_ejecutada']);

        $osMod = new OrdenServicioModelo();
        $osRow = $osMod->obtenerPorId($idOs);
        $this->assertSame('Ejecutada', $osRow['estado']);
        $this->assertNotEmpty($osRow['devuelto_sistema_fecha']);

        // Punto 13: Notificación creada para Administración y Contabilidad
        $notifs = ValidacionOrdenServicioServicio::obtenerNotificacionesPendientes($this->db, ValidacionOrdenServicioServicio::AREA_ADMIN_CONTABILIDAD, 50);
        $notifsOs = array_values(array_filter($notifs, static fn($n): bool => (int)$n['id_os'] === $idOs));
        $this->assertCount(1, $notifsOs);

        // Punto 14: Confirmación definitiva de recepción por Administración (Casilla 4)
        $r4 = ValidacionOrdenServicioServicio::registrarRecepcionAdminFinal($this->db, $idOs, $this->idAdmin, 1);
        $this->assertTrue($r4['ok'], $r4['mensaje']);

        // Punto 17: Corrección autorizada exige motivo obligatorio y deja trazabilidad
        $rCorrSinMot = ValidacionOrdenServicioServicio::corregirValidacionAutorizada($this->db, $idOs, 'autorizado_admin', '', $this->idAdmin, 1, $this->idAdmin);
        $this->assertFalse($rCorrSinMot['ok']);
        $rCorrOk = ValidacionOrdenServicioServicio::corregirValidacionAutorizada($this->db, $idOs, 'autorizado_admin', 'Ajuste por cambio de firmante autorizado', $this->idAdmin, 1, $this->idAdmin);
        $this->assertTrue($rCorrOk['ok']);

        // Punto 16: Historial completo (Casilla 1, Casilla 2, Ejecución Automática, Casilla 4 y Corrección)
        $hist = ValidacionOrdenServicioServicio::obtenerHistorial($this->db, $idOs);
        $this->assertGreaterThanOrEqual(5, count($hist));

        // Punto 15: HTML y PDF RG-FM-39 V1R1 reflejan código V1R1, 4 validaciones, membrete y tablas transparentes
        $osFinal = $osMod->obtenerPorId($idOs);
        $htmlPdf = DocumentoRgFm39Servicio::html($osFinal);
        $this->assertStringContainsString('CYCSA-RG-FM-39 V1R1', $htmlPdf);
        $this->assertStringContainsString('ENTREGA DE LA ORDEN', $htmlPdf);
        $this->assertStringContainsString('DEVOLUCIÓN DE LA ORDEN EJECUTADA', $htmlPdf);
        $this->assertStringContainsString('Devuelto automáticamente por el sistema', $htmlPdf);
        $this->assertStringContainsString('Orden ejecutada', $htmlPdf);
        $this->assertStringContainsString('Flujo completado', $htmlPdf);
        $this->assertStringContainsString('Validado en sistema', $htmlPdf);
        $this->assertStringNotContainsString('os-membrete-fondo-pdf', $htmlPdf);

        $pdfBytes = DocumentoRgFm39Servicio::pdf($osFinal);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }

    public function testOrdenMensualGeneraAlertaSiPlazoVenceConPendientesYPasaAEjecutadaAlCerrarPendientes(): void
    {
        $jsonPend = json_encode(['filas' => [['Carga (lb)' => '75000']], 'revision' => ['estado' => 'borrador']]);
        $creada = $this->crearOrdenPrueba(true, '2026-10-01', '2026-11-01', (string)$jsonPend);
        $idOs = $creada['id_os'];

        // Punto 9 y 10: Al evaluar con fecha posterior a fecha_fin y con informe pendiente -> Alerta
        $evAlerta = ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($this->db, $idOs, '2026-11-05');
        $this->assertTrue($evAlerta['alerta_generada']);
        $this->assertFalse($evAlerta['transicion_ejecutada']);

        $rowAl = $this->db->query("SELECT estado, estado_ejecucion_alerta, alerta_vencimiento_detalle FROM ordenes_servicio WHERE id = {$idOs}")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int)$rowAl['estado_ejecucion_alerta']);
        $this->assertStringContainsString('Plazo mensual finalizado', (string)$rowAl['alerta_vencimiento_detalle']);

        // Al aprobar el informe pendiente y reevaluar -> limpia alerta, pasa a Ejecutada y devuelve automáticamente
        $jsonOk = json_encode(['filas' => [['Carga (lb)' => '75000']], 'revision' => ['estado' => 'aprobada']]);
        $this->db->prepare("UPDATE cotizacion_detalles SET resultados_json = :j WHERE id = :id")
            ->execute(['j' => $jsonOk, 'id' => $creada['id_det']]);

        $evCierre = ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($this->db, $idOs, '2026-11-05');
        $this->assertTrue($evCierre['transicion_ejecutada']);
        $rowFin = $this->db->query("SELECT estado, estado_ejecucion_alerta, devuelto_sistema_fecha FROM ordenes_servicio WHERE id = {$idOs}")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Ejecutada', (string)$rowFin['estado']);
        $this->assertSame(0, (int)$rowFin['estado_ejecucion_alerta']);
        $this->assertNotEmpty($rowFin['devuelto_sistema_fecha']);
    }

    public function testEstilosCotizacionYAnexoMensualSonTransparentesConMembreteVertical(): void
    {
        $cssCot = DocumentoCotizacionRgFm31Servicio::renderEstilos(true);
        $cssAnx = AnexoServicioMensualServicio::renderEstilos(true);
        $membrete = DocumentoCotizacionRgFm31Servicio::resolverMembreteVerticalSrc(false);

        $this->assertStringContainsString('background: transparent;', $cssCot);
        $this->assertStringContainsString('background: transparent;', $cssAnx);
        $this->assertStringContainsString('hoja_membretada_vertical.jpg', $membrete);
    }
}
