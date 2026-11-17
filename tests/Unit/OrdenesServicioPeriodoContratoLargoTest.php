<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Reportes\Modelos\ReporteModelo;
use PDO;

class OrdenesServicioPeriodoContratoLargoTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private int $idOSPrueba = 0;
    private string $codigoOSPrueba = '';
    private int $idCotizacionPrueba = 0;
    private int $idClientePrueba = 0;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->osModelo = new OrdenServicioModelo();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Test Runner';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $idCli = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($idCli <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Test Contrato Largo', 'J0310000008888', 'contrato@test.com', '22228888')");
            $idCli = (int)$this->db->lastInsertId();
        }
        $this->idClientePrueba = $idCli;

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Tester', 'tester_contrato@test.com', '123456', 1, 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $cotCode = 'COT-PC-' . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:code, :cli, :usr, 'Ing. Supervisor', 'Proyecto Contrato Largo Mensual', 'Crédito 30 días', 'Aprobada por Cliente', 5000.00, 5750.00)");
        $stmtCot->execute(['code' => $cotCode, 'cli' => $this->idClientePrueba, 'usr' => $idUser]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        $this->codigoOSPrueba = 'OS-PC-' . uniqid();
        $this->idOSPrueba = $this->osModelo->crear([
            'codigo_os' => $this->codigoOSPrueba,
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePrueba,
            'atencion_a' => 'Ing. Supervisor',
            'nombre_proyecto' => 'Proyecto Contrato Largo Mensual',
            'forma_pago' => 'Crédito 30 días',
            'tipo_contrato' => 'Puntual',
            'es_multimes' => 0,
            'estado' => 'Aprobada',
        ]);
    }

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        if ($this->idOSPrueba > 0) {
            try {
                $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$this->idOSPrueba]);
            } catch (\Throwable $e) {
            }
        }
        if ($this->idCotizacionPrueba > 0) {
            try {
                $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$this->idCotizacionPrueba]);
            } catch (\Throwable $e) {
            }
        }
        parent::tearDown();
    }

    public function testHelpersVigenciaContratoYFechaFinPorMeses(): void {
        $fin6 = calcularFechaFinPorMesesOS('2026-11-16', 6);
        $this->assertSame('2027-05-16', $fin6);

        $vigVigente = calcularVigenciaContratoOS([
            'tipo_contrato' => 'Marco',
            'es_multimes' => 1,
            'duracion_meses' => 6,
            'fecha_inicio' => '2026-11-16',
            'fecha_fin' => '2027-05-16',
            'periodicidad' => 'Mensual',
        ], '2026-11-16');

        $this->assertTrue($vigVigente['es_contrato_largo']);
        $this->assertSame(6, $vigVigente['duracion_meses']);
        $this->assertSame('vigente', $vigVigente['estado_vigencia']);
        $this->assertSame(181, $vigVigente['dias_restantes']);
        $this->assertStringContainsString('6 meses', $vigVigente['etiqueta_corta']);

        $vigPorVencer = calcularVigenciaContratoOS([
            'es_multimes' => 1,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-11-26',
        ], '2026-11-16');
        $this->assertSame('por_vencer', $vigPorVencer['estado_vigencia']);
        $this->assertSame(10, $vigPorVencer['dias_restantes']);

        $vigVencida = calcularVigenciaContratoOS([
            'es_multimes' => 1,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-11-01',
        ], '2026-11-16');
        $this->assertSame('vencido', $vigVencida['estado_vigencia']);
        $this->assertLessThan(0, $vigVencida['dias_restantes']);
    }

    public function testConfigurarPeriodoMensualEnOSAceptadaConservaEstadoSinReaprobacion(): void {
        $ctrl = new OrdenesServicioControlador();

        foreach (['Aprobada', 'En Proceso', 'Estado 4: Ingreso Laboratorio'] as $estadoAceptado) {
            $this->db->prepare("UPDATE ordenes_servicio SET estado = ? WHERE id = ?")->execute([$estadoAceptado, $this->idOSPrueba]);

            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'csrf_token' => $_SESSION['csrf_token'],
                'id_os' => $this->idOSPrueba,
                'es_multimes' => '1',
                'duracion_meses' => '6',
                'fecha_inicio' => '2026-11-16',
                'fecha_fin' => '',
                'periodicidad' => 'Mensual',
            ];

            ob_start();
            $ctrl->guardarPeriodoContratoAjax(new Peticion(), new Respuesta());
            $res = json_decode(ob_get_clean(), true);

            $this->assertSame('success', $res['status'] ?? null);
            $this->assertSame($estadoAceptado, $res['estado_conservado'] ?? null, "La O/S en estado '{$estadoAceptado}' NO debe perder su aprobación ni volver a Revisión.");

            $osActual = $this->osModelo->obtenerPorId($this->idOSPrueba);
            $this->assertSame($estadoAceptado, $osActual['estado']);
            $this->assertSame('Marco', $osActual['tipo_contrato']);
            $this->assertSame(1, (int)$osActual['es_multimes']);
            $this->assertSame(6, (int)$osActual['duracion_meses']);
            $this->assertSame('2027-05-16', $osActual['fecha_fin']);
        }

        // Consultar periodo-contrato-datos via GET AJAX
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['id_os' => $this->idOSPrueba];
        ob_start();
        $ctrl->periodoContratoDatosAjax(new Peticion(), new Respuesta());
        $resGet = json_decode(ob_get_clean(), true);
        $this->assertSame('success', $resGet['status'] ?? null);
        $this->assertTrue((bool)($resGet['os']['es_aprobada'] ?? false));
        $this->assertSame(6, (int)($resGet['os']['duracion_meses'] ?? 0));
        $this->assertSame('2027-05-16', $resGet['os']['fecha_fin'] ?? null);

        // Validar rechazo si fecha_fin < fecha_inicio
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'es_multimes' => '1',
            'duracion_meses' => '6',
            'fecha_inicio' => '2026-11-16',
            'fecha_fin' => '2026-10-01',
            'periodicidad' => 'Mensual',
        ];
        ob_start();
        $ctrl->guardarPeriodoContratoAjax(new Peticion(), new Respuesta());
        $resErr = json_decode(ob_get_clean(), true);
        $this->assertSame('error', $resErr['status'] ?? null);

        // Verificar que ReporteModelo reporteOrdenesServicio muestra el período configurado
        $rep = (new ReporteModelo())->generarReporteModulo('ordenes_servicio', ['q' => $this->codigoOSPrueba]);
        $this->assertCount(1, $rep['rows']);
        $this->assertStringContainsString('Contrato Largo', $rep['rows'][0]['vigencia']);
        $this->assertStringContainsString('6 meses', $rep['rows'][0]['vigencia']);

        // Verificar que editar vía OrdenesServicioControlador::actualizar cambiando solo duración NO retrocede a Estado 2: Revision
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = ?")->execute([$this->idOSPrueba]);
        $osSnap = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'atencion_a' => $osSnap['atencion_a'],
            'nombre_proyecto' => $osSnap['nombre_proyecto'],
            'forma_pago' => $osSnap['forma_pago'],
            'condicion_comercial' => $osSnap['condicion_comercial'],
            'id_cliente_factura' => $osSnap['id_cliente_factura'],
            'id_cliente_informe' => $osSnap['id_cliente_informe'],
            'notas_condiciones' => $osSnap['notas_condiciones'] ?? '',
            'tipo_contrato' => 'Marco',
            'es_multimes' => '1',
            'duracion_meses' => '12',
            'fecha_inicio' => '2026-11-16',
            'fecha_fin' => '2027-11-16',
            'periodicidad' => 'Mensual',
        ];
        ob_start();
        $ctrl->actualizar(new Peticion(), new Respuesta());
        ob_end_clean();

        $osTrasEditarSoloPeriodo = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertSame('Aprobada', $osTrasEditarSoloPeriodo['estado']);
        $this->assertSame(12, (int)$osTrasEditarSoloPeriodo['duracion_meses']);
        $this->assertSame('2027-11-16', $osTrasEditarSoloPeriodo['fecha_fin']);
    }

    public function testBloqueaCambiosEnOrdenFinalizada(): void {
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Finalizado' WHERE id = ?")->execute([$this->idOSPrueba]);

        $ctrl = new OrdenesServicioControlador();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'es_multimes' => '1',
            'duracion_meses' => '6',
            'fecha_inicio' => '2026-11-16',
            'periodicidad' => 'Mensual',
        ];
        ob_start();
        $ctrl->guardarPeriodoContratoAjax(new Peticion(), new Respuesta());
        $res = json_decode(ob_get_clean(), true);

        $this->assertSame('error', $res['status'] ?? null);
    }
}
