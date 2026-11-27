<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;
use PHPUnit\Framework\TestCase;

class RespuestaCapturaDistribuidor extends Respuesta {
    public ?array $jsonRespuesta = null;
    public int $codigoHttp = 200;
    public ?string $redireccion = null;

    public function enviarJson(array $datos, int $codigo = 200): void {
        $this->jsonRespuesta = $datos;
        $this->codigoHttp = $codigo;
    }

    public function redirigir(string $url): void {
        $this->redireccion = $url;
    }
}

class DistribuidorMuestrasRtfm13Test extends TestCase {
    private PDO $db;
    private HojasServicioControlador $controlador;
    private OperacionModelo $opModelo;
    private int $idCliente = 0;
    private int $idCot = 0;
    private int $idOS = 0;
    private int $idDetComercial = 0;
    private int $idDetProd1 = 0;
    private int $idDetProd2 = 0;
    private int $idDetProd3 = 0;
    private int $idProd1 = 0;
    private int $idProd2 = 0;
    private int $idProd3 = 0;
    private array $secuenciasBackup = [];

    protected function setUp(): void {
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->controlador = new HojasServicioControlador();
        $this->opModelo = new OperacionModelo();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Test';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['permisos'] = [
            'recepcion' => ['ver' => 1, 'crear_editar' => 1, 'eliminar' => 1],
            'ordenes_servicio' => ['ver' => 1, 'crear_editar' => 1, 'eliminar' => 1],
            'operaciones' => ['ver' => 1, 'crear_editar' => 1, 'eliminar' => 1],
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = '/Cycsa/publico/index.php';
        unset($_SESSION['error'], $_SESSION['exito']);

        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $prods = $this->db->query("SELECT id, nombre_comercial FROM productos WHERE activo = 1 ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(3, $prods, 'Se requieren al menos 3 productos activos en cycsa_test');
        $this->idProd1 = (int)$prods[0]['id'];
        $this->idProd2 = (int)$prods[1]['id'];
        $this->idProd3 = (int)$prods[2]['id'];

        $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Distribuidor 16 Muestras', 'J0310000999991', 'dist16@test.local', '22224444')");
        $this->idCliente = (int)$this->db->lastInsertId();

        $uniq = substr(bin2hex(random_bytes(4)), 0, 8);
        $codCot = 'CD-' . $uniq;
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (?, ?, 1, 'Ing. Obra', 'Proyecto Distribución 16', 'Contado', 'Aprobada por Cliente', 1000, 1000)");
        $stmtCot->execute([$codCot, $this->idCliente]);
        $this->idCot = (int)$this->db->lastInsertId();

        // Línea comercial genérica (id_producto NULL) + 3 productos técnicos del anexo mensual
        $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal, es_incluido_anexo_mensual) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtDet->execute([$this->idCot, null, 'Servicio mensual de laboratorio in situ (30 días)', 1, 1000, 1000, 0]);
        $this->idDetComercial = (int)$this->db->lastInsertId();

        $stmtDet->execute([$this->idCot, $this->idProd1, 'Producto Técnico A (Concreto)', 1, 0, 0, 1]);
        $this->idDetProd1 = (int)$this->db->lastInsertId();

        $stmtDet->execute([$this->idCot, $this->idProd2, 'Producto Técnico B (Compactación)', 1, 0, 0, 1]);
        $this->idDetProd2 = (int)$this->db->lastInsertId();

        $stmtDet->execute([$this->idCot, $this->idProd3, 'Producto Técnico C (Granulometría)', 1, 0, 0, 1]);
        $this->idDetProd3 = (int)$this->db->lastInsertId();

        $codOS = 'OS-D-' . $uniq;
        $osModelo = new OrdenServicioModelo();
        $this->idOS = $osModelo->crear([
            'codigo_os' => $codOS,
            'id_cotizacion' => $this->idCot,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Obra',
            'nombre_proyecto' => 'Proyecto Distribución 16',
            'forma_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Mensual',
            'es_multimes' => 1,
            'duracion_meses' => 1,
            'fecha_inicio' => date('Y-m-d'),
            'fecha_fin' => date('Y-m-d', strtotime('+30 days')),
            'estado' => 'Aprobada',
        ]);

        $stmtPerm = $this->db->prepare("INSERT INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES (?, ?)");
        foreach ([$this->idDetComercial, $this->idDetProd1, $this->idDetProd2, $this->idDetProd3] as $detId) {
            $stmtPerm->execute([$this->idOS, $detId]);
        }
    }

    protected function tearDown(): void {
        if ($this->idOS > 0) {
            $this->db->prepare("DELETE FROM hojas_matrices_resultados WHERE id_os = ?")->execute([$this->idOS]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_os = ?")->execute([$this->idOS]);
            $this->db->prepare("DELETE hsd FROM hojas_solicitud_detalles hsd INNER JOIN hojas_solicitud hs ON hs.id = hsd.id_hoja_solicitud WHERE hs.id_os = ?")->execute([$this->idOS]);
            $this->db->prepare("DELETE FROM hojas_solicitud WHERE id_os = ?")->execute([$this->idOS]);
            $this->db->prepare("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = ?")->execute([$this->idOS]);
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$this->idOS]);
        }
        if ($this->idCot > 0) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = ?")->execute([$this->idCot]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$this->idCot]);
        }
        if ($this->idCliente > 0) {
            $this->db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$this->idCliente]);
        }
        if (!empty($this->secuenciasBackup)) {
            $stmtRest = $this->db->prepare("UPDATE secuencias_muestras SET ultimo_correlativo = ? WHERE anio = ? AND tipo_muestra = ?");
            foreach ($this->secuenciasBackup as $row) {
                $stmtRest->execute([(int)$row['ultimo_correlativo'], (int)$row['anio'], (string)$row['tipo_muestra']]);
            }
        }
        $_POST = [];
        $_GET = [];
    }

    public function testPartialDistribuidorIncluidoEnAmbosModalesRtfm13(): void {
        $partial = __DIR__ . '/../../app/Modulos/HojasServicio/Vistas/partials/distribuidor_muestras_productos_rtfm13.php';
        $vistaOS = file_get_contents(__DIR__ . '/../../app/Modulos/OrdenesServicio/Vistas/index.php');
        $vistaHS = file_get_contents(__DIR__ . '/../../app/Modulos/HojasServicio/Vistas/index.php');

        $this->assertFileExists($partial);
        $this->assertStringContainsString('distribuidor_muestras_productos_rtfm13.php', (string)$vistaOS);
        $this->assertStringContainsString('distribuidor_muestras_productos_rtfm13.php', (string)$vistaHS);
        $this->assertStringContainsString('inicializarDistribuidorProductosRTFM13(true)', (string)$vistaOS);
        $this->assertStringContainsString('inicializarDistribuidorProductosRTFM13(true)', (string)$vistaHS);
        $this->assertStringContainsString('chk_sel_todas_muestras_rtfm13', (string)$vistaOS);
        $this->assertStringContainsString('chk_sel_todas_muestras_rtfm13', (string)$vistaHS);
    }

    public function testHojaSolicitudDatosAjaxExcluyeLineaComercialMensualSiHayProductosTecnicos(): void {
        $_GET = ['id_os' => $this->idOS, 'nueva' => '1'];
        $resp = new RespuestaCapturaDistribuidor();
        $this->controlador->hojaSolicitudDatosAjax(new Peticion(), $resp);

        $this->assertSame('success', $resp->jsonRespuesta['status'] ?? null);
        $prods = $resp->jsonRespuesta['productos_permitidos_os'] ?? [];
        $this->assertCount(3, $prods, 'Solo deben mostrarse los 3 productos técnicos, excluyendo la línea comercial genérica mensual.');
        $detIds = array_map(static fn($p) => (int)($p['id_detalle_cotizacion'] ?? 0), $prods);
        $this->assertNotContains($this->idDetComercial, $detIds);
        $this->assertContains($this->idDetProd1, $detIds);
        $this->assertContains($this->idDetProd2, $detIds);
        $this->assertContains($this->idDetProd3, $detIds);
    }

    public function testDistribuir16MuestrasEn3ProductosPropagaATodoElFlujo(): void {
        // Simula la distribución 16 muestras: 6 -> Prod1, 6 -> Prod2, 4 -> Prod3 desde el Distribuidor RT-FM-13
        $mNombre = [];
        $mDesc = [];
        $mInfo = [];
        $mDetalle = [];
        $mEnsayo = [];
        for ($i = 1; $i <= 16; $i++) {
            $mNombre[] = sprintf('MS-%04d-26', 9000 + $i);
            $mDesc[] = 'Muestra #' . $i;
            $mInfo[] = 'Lote distribución 16';
            if ($i <= 6) {
                $mDetalle[] = (string)$this->idDetProd1;
                $mEnsayo[] = (string)$this->idProd1;
            } elseif ($i <= 12) {
                $mDetalle[] = (string)$this->idDetProd2;
                $mEnsayo[] = (string)$this->idProd2;
            } else {
                $mDetalle[] = (string)$this->idDetProd3;
                $mEnsayo[] = (string)$this->idProd3;
            }
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => '',
            'numero_registro' => '9916',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d\TH:i'),
            'nombre_empresa_o_cliente' => 'Cliente Distribuidor 16 Muestras',
            'direccion_proyecto' => 'Proyecto 16 Muestras',
            'nombre_persona_entrega_muestra' => 'Ingeniero Obra',
            'procedencia_punto_muestreo' => 'Frente Norte',
            'nombre_persona_toma_muestra' => 'Brigada 1',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),
            'nombre_recibe_cycsa' => 'Recepción CYCSA',
            'm_nombre' => $mNombre,
            'm_desc' => $mDesc,
            'm_info' => $mInfo,
            'm_detalle_cotizacion' => $mDetalle,
            'm_ensayo_id' => $mEnsayo,
        ];

        $resp = new RespuestaCapturaDistribuidor();
        $this->controlador->guardarHojaSolicitud(new Peticion(), $resp);
        $this->assertEmpty($_SESSION['error'] ?? null, 'Error guardando hoja con 16 muestras: ' . (string)($_SESSION['error'] ?? ''));

        $hojas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);
        $this->assertCount(1, $hojas);
        $idHoja = (int)$hojas[0]['id'];

        // 1. Verificar distribución exacta por producto en la capa compartida (Recepción / Técnico / Operaciones / Lab)
        $mP1 = obtenerMuestrasHojaParaDetalle($this->db, $this->idOS, $this->idDetProd1, $hojas[0]);
        $mP2 = obtenerMuestrasHojaParaDetalle($this->db, $this->idOS, $this->idDetProd2, $hojas[0]);
        $mP3 = obtenerMuestrasHojaParaDetalle($this->db, $this->idOS, $this->idDetProd3, $hojas[0]);

        $this->assertCount(6, $mP1, 'Producto 1 debe recibir exactamente 6 muestras');
        $this->assertCount(6, $mP2, 'Producto 2 debe recibir exactamente 6 muestras');
        $this->assertCount(4, $mP3, 'Producto 3 debe recibir exactamente 4 muestras');

        // 2. Verificar que se crearon automáticamente las 3 matrices aisladas (Hoja × Producto) en hojas_matrices_resultados
        $stmtMat = $this->db->prepare("SELECT id_detalle_cotizacion FROM hojas_matrices_resultados WHERE id_hoja = ? ORDER BY id_detalle_cotizacion ASC");
        $stmtMat->execute([$idHoja]);
        $matDets = array_map('intval', $stmtMat->fetchAll(PDO::FETCH_COLUMN));
        $this->assertCount(3, $matDets);
        $this->assertContains($this->idDetProd1, $matDets);
        $this->assertContains($this->idDetProd2, $matDets);
        $this->assertContains($this->idDetProd3, $matDets);
    }
}
