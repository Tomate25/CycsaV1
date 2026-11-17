<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

class OrdenServicioEstadosAprobadosTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;
    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idDetalle1 = 0;
    private int $idDetalle2 = 0;
    private int $idOS = 0;
    private array $hojasCreadas = [];
    private array $recepcionesCreadas = [];
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
        $_SESSION['usuario_nombre'] = 'Supervisor Test';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $idCli = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($idCli <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Test Estados', 'J0310000000099', 'estados@test.com', '22223344')");
            $idCli = (int)$this->db->lastInsertId();
        }
        $this->idCliente = $idCli;

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Tester Estados', 'testerestados@test.com', '123456', 1, 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:code, :cli, :usr, 'Atn Test', 'Proyecto Estados OS', 'Pago contra entrega', 'Aprobada por Cliente', 2000.00, 2300.00)");
        $stmtCot->execute(['code' => 'COT-EST-' . uniqid(), 'cli' => $this->idCliente, 'usr' => $idUser]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:cot, 1, :desc, 1, 1000.00, 1000.00)");
        $stmtDet->execute(['cot' => $this->idCotizacion, 'desc' => 'Ensayo Concreto 1']);
        $this->idDetalle1 = (int)$this->db->lastInsertId();
        $stmtDet->execute(['cot' => $this->idCotizacion, 'desc' => 'Ensayo Suelos 2']);
        $this->idDetalle2 = (int)$this->db->lastInsertId();

        $this->idOS = $this->osModelo->crear([
            'codigo_os' => 'OS-EST-' . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Atn Test',
            'nombre_proyecto' => 'Proyecto Estados OS',
            'forma_pago' => 'Pago contra entrega',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'es_multimes' => 0,
            'periodicidad' => 'Mensual',
            'estado' => 'Aprobada'
        ]);
    }

    protected function tearDown(): void {
        if (!empty($this->secuenciasBackup)) {
            try {
                $this->db->exec("DELETE FROM secuencias_muestras");
                $stmtInsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr)");
                foreach ($this->secuenciasBackup as $row) {
                    $stmtInsert->execute(['anio' => $row['anio'], 'tipo' => $row['tipo_muestra'], 'corr' => $row['ultimo_correlativo']]);
                }
            } catch (\Throwable $e) {}
        }

        if ($this->idOS > 0) {
            try {
                $this->db->exec("DELETE FROM ensayo_edades WHERE id_lote IN (SELECT lm.id FROM lotes_muestras lm JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id WHERE rm.id_os = {$this->idOS})");
                $this->db->exec("DELETE FROM lotes_muestras WHERE id_recepcion IN (SELECT id FROM recepcion_muestras WHERE id_os = {$this->idOS})");
                $this->db->exec("DELETE FROM recepcion_muestras WHERE id_os = {$this->idOS}");
            } catch (\Throwable $e) {}
        }

        if (!empty($this->hojasCreadas)) {
            $inHojas = implode(',', array_map('intval', $this->hojasCreadas));
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inHojas})");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inHojas})");
            } catch (\Throwable $e) {}
        }

        if ($this->idOS > 0) {
            try {
                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_orden_servicio = {$this->idOS}");
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOS}");
            } catch (\Throwable $e) {}
        }

        if ($this->idCotizacion > 0) {
            try {
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacion}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacion}");
            } catch (\Throwable $e) {}
        }

        parent::tearDown();
    }

    public function testHelperReconoceTodosLosEstadosOperativosAprobados(): void {
        $aprobados = [
            'Aprobada',
            'Estado 3: Aprobada',
            'Estado 3: Ingreso Directo',
            'Estado 3A: Programacion Muestreo',
            'Estado 3B: Ejecucion Muestreo',
            'Estado 3C: Espera Muestreo',
            'Estado 4: Ingreso Laboratorio',
            'En Proceso',
            'Estado 5: Solicitud Tecnicos',
            'Estado 6: Ejecucion Ensayos',
            'Estado 7: Revision Resultados',
        ];
        foreach ($aprobados as $estado) {
            $this->assertTrue(esOrdenServicioAprobada($estado), "El estado '{$estado}' debe considerarse aprobado.");
        }

        $noAprobados = [
            null,
            '',
            'Borrador',
            'Pendiente',
            'Pendiente de Muestreo',
            'Estado 1: Recepcion',
            'Estado 2: Revision',
            'Estado 2: Observada',
            'Finalizado',
            'Archivado',
            'Cerrado',
        ];
        foreach ($noAprobados as $estado) {
            $this->assertFalse(esOrdenServicioAprobada($estado), "El estado '" . ($estado ?? 'null') . "' no debe considerarse aprobado.");
        }
    }

    public function testGateRTFM13PermiteEstadosOperativosAvanzadosComoEnProcesoYEstado7(): void {
        foreach (['En Proceso', 'Estado 3C: Espera Muestreo', 'Estado 7: Revision Resultados'] as $estado) {
            $this->db->prepare("UPDATE ordenes_servicio SET estado = :est WHERE id = :id")
                     ->execute(['est' => $estado, 'id' => $this->idOS]);

            $idHoja = 0;
            $ok = $this->opModelo->guardarHojaSolicitud([
                'id_os' => $this->idOS,
                'id_hoja' => 0,
                'id_detalle_cotizacion' => $this->idDetalle1,
                'codigo_documento' => 'CYCSA-RT-FM-13',
                'numero_registro' => 'REG-EST-' . uniqid(),
                'nombre_empresa_o_cliente' => 'Cliente Test',
                'identificacion_muestras_json' => json_encode([
                    ['nombre_muestra' => 'MS-9991-' . date('y'), 'descripcion' => 'Probeta Test']
                ]),
            ], $idHoja);

            $this->assertTrue($ok, "En estado '{$estado}' no debe bloquearse el registro de RT-FM-13: " . ($this->opModelo->obtenerUltimoError() ?? ''));
            $this->assertGreaterThan(0, $idHoja);
            $this->hojasCreadas[] = $idHoja;
        }
    }

    public function testRecepcionParcialEnLabMantieneAprobacionYNoRetrocedeEstadosAvanzados(): void {
        // 1. Recepción parcial (1 de 2 ítems) pone 'En Proceso', que sigue siendo aprobado
        $ok = $this->opModelo->registrarRecepcion([
            'id_os' => $this->idOS,
            'tipo_muestra' => 'Laboratorio',
            'recibido_por' => 1,
            'entregado_por' => 'Cliente',
            'observaciones' => 'Prueba parcial',
            'id_detalle_cotizacion' => $this->idDetalle1,
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $this->idDetalle1,
                    'codigo_campo' => 'M-1',
                    'id_cilindro' => 'M-1',
                    'nombre_lote' => 'Lote 1',
                ]
            ]),
        ]);
        $this->assertTrue($ok, (string)$this->opModelo->obtenerUltimoErrorRecepcion());

        $estadoActual = (string)$this->db->query("SELECT estado FROM ordenes_servicio WHERE id = {$this->idOS}")->fetchColumn();
        $this->assertSame('En Proceso', $estadoActual);
        $this->assertTrue(esOrdenServicioAprobada($estadoActual), "Una O/S que pasó a 'En Proceso' al aprobar muestra en Lab no debe perder la aprobación.");

        // 2. Si la O/S ya está en Estado 7: Revision Resultados, otra recepción en Lab no debe retrocederla
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 7: Revision Resultados' WHERE id = :id")
                 ->execute(['id' => $this->idOS]);

        $ok2 = $this->opModelo->registrarRecepcion([
            'id_os' => $this->idOS,
            'tipo_muestra' => 'Laboratorio',
            'recibido_por' => 1,
            'entregado_por' => 'Cliente',
            'observaciones' => 'Segunda recepción',
            'id_detalle_cotizacion' => $this->idDetalle2,
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $this->idDetalle2,
                    'codigo_campo' => 'M-2',
                    'id_cilindro' => 'M-2',
                    'nombre_lote' => 'Lote 2',
                ]
            ]),
        ]);
        $this->assertTrue($ok2, (string)$this->opModelo->obtenerUltimoErrorRecepcion());

        $estadoDespues = (string)$this->db->query("SELECT estado FROM ordenes_servicio WHERE id = {$this->idOS}")->fetchColumn();
        $this->assertSame('Estado 7: Revision Resultados', $estadoDespues, 'No debe retroceder desde Estado 7: Revision Resultados.');
    }

    public function testVistaOrdenesServicioNoVuelveAPedirAprobacionEnEstadosAvanzadosNiCerrados(): void {
        $estadosSinReaprobacion = [
            'En Proceso',
            'Estado 3C: Espera Muestreo',
            'Estado 5: Solicitud Tecnicos',
            'Estado 6: Ejecucion Ensayos',
            'Estado 7: Revision Resultados',
            'Finalizado',
        ];

        foreach ($estadosSinReaprobacion as $estado) {
            $html = $this->renderizarVistaOSConEstado($estado);
            $this->assertStringNotContainsString('O/S Por Aprobar', $html, "El estado '{$estado}' no debe mostrar badge 'O/S Por Aprobar'.");
            $this->assertStringNotContainsString('onclick="abrirModalAprobarOS(', $html, "El estado '{$estado}' no debe renderizar el botón 'Aprobar O/S'.");
        }

        foreach (['Estado 1: Recepcion', 'Estado 2: Revision'] as $estadoPendiente) {
            $html = $this->renderizarVistaOSConEstado($estadoPendiente);
            $this->assertStringContainsString('O/S Por Aprobar', $html, "El estado '{$estadoPendiente}' sí debe mostrar badge 'O/S Por Aprobar'.");
            $this->assertStringContainsString('onclick="abrirModalAprobarOS(', $html, "El estado '{$estadoPendiente}' sí debe renderizar el botón 'Aprobar O/S'.");
        }
    }

    public function testAprobarOSRechazaOrdenCerradaOFinalizada(): void {
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Finalizado' WHERE id = :id")
                 ->execute(['id' => $this->idOS]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOS,
            'ensayos_permitidos' => [$this->idDetalle1],
        ];
        $_SESSION['error'] = null;

        $ctrl = new OrdenesServicioControlador();
        $ctrl->aprobarOS(new Peticion(), new Respuesta());

        $this->assertStringContainsString('cerrada o archivada', strtolower((string)($_SESSION['error'] ?? '')));
        $estadoActual = (string)$this->db->query("SELECT estado FROM ordenes_servicio WHERE id = {$this->idOS}")->fetchColumn();
        $this->assertSame('Finalizado', $estadoActual);
    }

    private function renderizarVistaOSConEstado(string $estado): string {
        $ordenes = [[
            'id' => $this->idOS,
            'codigo_os' => '2026999-ORDEN DE SERVICIO',
            'cotizacion_codigo' => 'COT-999',
            'cliente_nombre' => 'Cliente Vista Test',
            'cliente_rfc' => 'J0310000000099',
            'atencion_a' => 'Ing. Test',
            'nombre_proyecto' => 'Proyecto Vista',
            'forma_pago' => 'Contado',
            'fecha_emision' => date('Y-m-d'),
            'estado' => $estado,
            'requiere_muestreo' => 0,
            'items' => [
                [
                    'id' => $this->idDetalle1,
                    'descripcion_ensayo' => 'Compresión ASTM C39',
                    'norma_astm' => 'ASTM C39',
                    'codigo_servicio' => 'CO-01',
                ],
            ],
            'hojas_servicio' => [],
        ]];
        $tecnicos = [];
        $busqueda = '';
        $tabActiva = 'todas';
        $conteosTabs = ['activas' => 1, 'muestreo' => 0, 'historico' => 0, 'todas' => 1];
        $filtros = ['estado' => '', 'desde' => '', 'hasta' => ''];
        $id_os_auto = 0;

        ob_start();
        require dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php';
        return (string)ob_get_clean();
    }
}
