<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Controladores\LaboratorioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;

class LaboratorioAutoIngresoMuestrasNuevasTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;
    private int $idOSPrueba = 0;
    private int $idCotizacionPrueba = 0;
    private int $idClientePrueba = 0;
    private int $idDetalleConcreto = 0;
    private int $idDetalleCompactacion = 0;
    private int $idProdConcreto = 0;
    private int $idProdCompactacion = 0;
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
        $_SESSION['usuario_nombre'] = 'Test Lab';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = ['laboratorio' => ['ver' => 1, 'crear_editar' => 1], 'ordenes_servicio' => ['ver' => 1, 'crear_editar' => 1]];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $this->idClientePrueba = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($this->idClientePrueba <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Auto Lab Test', 'J0310000007777', 'autolab@test.com', '22227777')");
            $this->idClientePrueba = (int)$this->db->lastInsertId();
        }

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Tester', 'tester_autolab@test.com', '123456', 1, 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $this->idProdConcreto = (int)$this->db->query("SELECT id FROM productos WHERE COALESCE(ensayo_servicio, nombre_comercial) LIKE '%Concreto%' OR COALESCE(norma_astm, '') LIKE '%C39%' LIMIT 1")->fetchColumn();
        if ($this->idProdConcreto <= 0) {
            $this->idProdConcreto = (int)$this->db->query("SELECT id FROM productos LIMIT 1")->fetchColumn();
        }
        $this->idProdCompactacion = (int)$this->db->query("SELECT id FROM productos WHERE COALESCE(nombre_comercial, ensayo_servicio) LIKE '%Densímetro%' OR COALESCE(norma_astm, '') LIKE '%D6938%' LIMIT 1")->fetchColumn();
        if ($this->idProdCompactacion <= 0) {
            $this->idProdCompactacion = $this->idProdConcreto;
        }

        $cotCode = 'COT-ALAB-' . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:code, :cli, :usr, 'Ing. Lab', 'Proyecto Auto Ingreso Lab', 'Contado', 'Aprobada por Cliente', 1200.00, 1380.00)");
        $stmtCot->execute(['code' => $cotCode, 'cli' => $this->idClientePrueba, 'usr' => $idUser]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        $stmtDet1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Resistencia a Compresión Cilindros Concreto', 'ASTM C39', 3, 200.00, 600.00)");
        $stmtDet1->execute(['cot' => $this->idCotizacionPrueba, 'prod' => $this->idProdConcreto]);
        $this->idDetalleConcreto = (int)$this->db->lastInsertId();

        $stmtDet2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Densímetro Nuclear Compactación In Situ', 'ASTM D6938', 2, 300.00, 600.00)");
        $stmtDet2->execute(['cot' => $this->idCotizacionPrueba, 'prod' => $this->idProdCompactacion]);
        $this->idDetalleCompactacion = (int)$this->db->lastInsertId();

        $this->idOSPrueba = $this->osModelo->crear([
            'codigo_os' => 'OS-ALAB-' . uniqid(),
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePrueba,
            'atencion_a' => 'Ing. Lab',
            'nombre_proyecto' => 'Proyecto Auto Ingreso Lab',
            'forma_pago' => 'Contado',
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
                $stmtRms = $this->db->prepare("SELECT id FROM recepcion_muestras WHERE id_os = ?");
                $stmtRms->execute([$this->idOSPrueba]);
                $recIds = $stmtRms->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($recIds)) {
                    $inR = implode(',', array_map('intval', $recIds));
                    $this->db->exec("DELETE FROM plan_rotura_asignaciones WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id IN ({$inR}))");
                    $this->db->exec("DELETE FROM plan_rotura_edades WHERE plan_rotura_id IN (SELECT id FROM planes_rotura WHERE muestra_id IN ({$inR}))");
                    $this->db->exec("DELETE FROM planes_rotura WHERE muestra_id IN ({$inR})");
                    $this->db->exec("DELETE FROM especimenes WHERE muestra_id IN ({$inR})");
                    $this->db->exec("DELETE FROM lotes_muestras WHERE id_recepcion IN ({$inR})");
                    $this->db->exec("DELETE FROM recepcion_muestras WHERE id IN ({$inR})");
                }
            } catch (\Throwable $e) {
            }
        }
        foreach ($this->hojasCreadas as $hid) {
            try {
                $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = ?")->execute([$hid]);
                $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = ?")->execute([$hid]);
            } catch (\Throwable $e) {
            }
        }
        if ($this->idOSPrueba > 0) {
            try {
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

    public function testGuardarHojaRTFM13IngresaMuestrasAutomaticamenteALaboratorioYExcluyeCompactacion(): void {
        $anio2 = date('y');
        $hsCtrl = new HojasServicioControlador();
        $capturador = new class extends Respuesta {
            public ?string $redir = null;
            public ?array $json = null;
            public function redirigir(string $url): void {
                $this->redir = $url;
            }
            public function enviarJson(array $datos, int $codigoHttp = 200): void {
                $this->json = $datos;
            }
        };

        $idPlt = (int)$this->db->query("SELECT id FROM plantillas LIMIT 1")->fetchColumn();
        if ($idPlt <= 0) {
            $this->db->exec("INSERT INTO plantillas (codigo, nombre, estado, version_actual) VALUES ('PLT-TEST-LAB', 'Plantilla Concreto Test', 'aprobada', 1)");
            $idPlt = (int)$this->db->lastInsertId();
        }

        // 1. Guardar Hoja RT-FM-13 de Concreto (NO compactación)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'id_hoja' => 0,
            'solicitante' => 'Cliente Auto Lab Test',
            'proyecto_referencia' => 'Proyecto Auto Ingreso Lab',
            'prefijo_seleccionado' => 'MS',
            'm_nombre' => ["MS-7701-{$anio2}", "MS-7702-{$anio2}", "MS-7703-{$anio2}"],
            'm_desc' => ['Columna C1', 'Columna C2', 'Columna C3'],
            'm_info' => ['7 días', '14 días', '28 días'],
            'm_ensayo_id' => [$this->idProdConcreto, $this->idProdConcreto, $this->idProdConcreto],
            'm_plantilla_id' => [$idPlt, $idPlt, $idPlt],
            'confirmar_recepcion' => 1,
        ];
        $hsCtrl->guardarHojaSolicitud(new Peticion(), $capturador);

        $stmtHojaConc = $this->db->prepare("SELECT id, numero_registro, etapa_flujo, recepcion_completa FROM hojas_solicitud WHERE id_os = ? AND id_detalle_cotizacion = ? LIMIT 1");
        $stmtHojaConc->execute([$this->idOSPrueba, $this->idDetalleConcreto]);
        $hojaConc = $stmtHojaConc->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($hojaConc, 'Debe guardarse la Hoja RT-FM-13 de concreto.');
        $idHojaConc = (int)$hojaConc['id'];
        $this->hojasCreadas[] = $idHojaConc;

        // 1A. Al guardar RT-FM-13 sin marcar recepción completa, permanece en recepcion_borrador y aún no ingresa a recepcion_muestras
        $this->assertSame('recepcion_borrador', $hojaConc['etapa_flujo'] ?? 'recepcion_borrador');
        $this->assertSame(0, (int)($hojaConc['recepcion_completa'] ?? 0));
        $stmtRmBorrador = $this->db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_os = ? AND id_hoja = ?");
        $stmtRmBorrador->execute([$this->idOSPrueba, $idHojaConc]);
        $this->assertSame(0, (int)$stmtRmBorrador->fetchColumn(), 'En recepcion_borrador aún no debe ingresar a recepcion_muestras.');

        // 1B. Completar datos de recepción y marcar recepción completa -> transiciona a recepcion_completa e ingresa las 3 muestras a recepcion_muestras
        $stmtCods = $this->db->prepare("SELECT codigo_muestra FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = ? ORDER BY orden ASC, id ASC");
        $stmtCods->execute([$idHojaConc]);
        $codigosHoja = $stmtCods->fetchAll(PDO::FETCH_COLUMN);
        $payloadRec = [];
        foreach ($codigosHoja as $idxC => $codH) {
            $payloadRec[$codH] = [
                'Nombre muestra' => 'Columna C' . ($idxC + 1),
                'Reven. (in)' => '4',
                'Temp. (°C)' => '27',
                'Diseño de concreto (lb/in²)' => '3000',
                'Fecha de Fabricación' => date('Y-m-d'),
            ];
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_hoja' => $idHojaConc,
            'muestras_recepcion_json' => json_encode($payloadRec, JSON_UNESCAPED_UNICODE),
            'marcar_recepcion_completa' => 1,
        ];
        $hsCtrl->marcarRecepcionCompleta(new Peticion(), $capturador);

        $stmtHojaConc->execute([$this->idOSPrueba, $this->idDetalleConcreto]);
        $hojaConcPost = $stmtHojaConc->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('recepcion_completa', $hojaConcPost['etapa_flujo']);
        $this->assertSame(1, (int)$hojaConcPost['recepcion_completa']);

        // Verificar que las 3 muestras cayeron automáticamente a recepcion_muestras al marcar recepción completa
        $stmtRm = $this->db->prepare("SELECT id, codigo_muestra FROM recepcion_muestras WHERE id_os = ? AND id_hoja = ? ORDER BY orden_en_lote ASC");
        $stmtRm->execute([$this->idOSPrueba, $idHojaConc]);
        $recRows = $stmtRm->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(3, $recRows, 'Las 3 muestras nuevas deben caer automáticamente a recepcion_muestras al marcar recepción completa.');
        $codRec1 = (string)($recRows[0]['codigo_muestra'] ?? '');
        $this->assertStringStartsWith('MS-', $codRec1);

        // Verificar que cotizacion_detalles.resultados_json NO quedó marcado prematuramente en_revision ni con 14.137
        $stmtRes = $this->db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = ?");
        $stmtRes->execute([$this->idDetalleConcreto]);
        $resRaw = (string)$stmtRes->fetchColumn();
        $this->assertStringNotContainsString('en_revision', $resRaw);
        $this->assertStringNotContainsString('14.137', $resRaw);

        // 2. Guardar Hoja RT-FM-13 de Compactación in-situ (Densímetro Nuclear) -> NO debe caer en recepcion_muestras
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleCompactacion,
            'id_hoja' => 0,
            'solicitante' => 'Cliente Auto Lab Test',
            'proyecto_referencia' => 'Proyecto Auto Ingreso Lab',
            'prefijo_seleccionado' => 'MC',
            'm_nombre' => ["MC-7711-{$anio2}"],
            'm_desc' => ['Terraplén Tramo 1'],
            'm_info' => ['Ensayo in situ'],
            'm_ensayo_id' => [$this->idProdCompactacion],
            'm_plantilla_id' => [$idPlt],
            'confirmar_recepcion' => 1,
        ];
        $hsCtrl->guardarHojaSolicitud(new Peticion(), $capturador);

        $stmtHojaComp = $this->db->prepare("SELECT id FROM hojas_solicitud WHERE id_os = ? AND id_detalle_cotizacion = ? LIMIT 1");
        $stmtHojaComp->execute([$this->idOSPrueba, $this->idDetalleCompactacion]);
        $idHojaComp = (int)$stmtHojaComp->fetchColumn();
        $this->assertGreaterThan(0, $idHojaComp);
        $this->hojasCreadas[] = $idHojaComp;

        $stmtRmComp = $this->db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_os = ? AND id_hoja = ?");
        $stmtRmComp->execute([$this->idOSPrueba, $idHojaComp]);
        $this->assertSame(0, (int)$stmtRmComp->fetchColumn(), 'La compactación in situ no debe ingresar a Laboratorio.');

        // 3. Consultar Bandeja Anónima y comprobar hoja relacionada sin fuga comercial
        $roturasModelo = new RoturasModelo();
        $bandeja = $roturasModelo->obtenerBandejaAnonima(['busqueda' => $codRec1]);
        $this->assertNotEmpty($bandeja);
        $this->assertArrayHasKey('hoja_numero_registro', $bandeja[0]);
        $this->assertSame($hojaConc['numero_registro'], $bandeja[0]['hoja_numero_registro']);
        $this->assertArrayNotHasKey('codigo_os', $bandeja[0]);
        $this->assertArrayNotHasKey('cliente_nombre', $bandeja[0]);

        // 4. Renderizar Dashboard de Laboratorio por defecto (sin ?tab) y verificar:
        //    - Solo muestras nuevas/activas con su Hoja Relacionada
        //    - Sin pestaña/botones/modales de Aprobar/Observar
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['busqueda' => $codRec1];
        $labCtrl = new LaboratorioControlador();
        ob_start();
        $labCtrl->index(new Peticion(), new class extends Respuesta {
            public function redirigir(string $url): void {}
        });
        $html = (string)ob_get_clean();

        $this->assertStringContainsString('Muestras Nuevas y Hoja Relacionada', $html);
        $this->assertStringContainsString('Hoja Relacionada', $html);
        $this->assertStringContainsString('RT-FM-13 Reg. ' . $hojaConc['numero_registro'], $html);
        $this->assertStringContainsString('Hoja RT-FM-60', $html);
        $this->assertStringNotContainsString('Solicitudes por Aprobar', $html);
        $this->assertStringNotContainsString('modalAprobarMuestra', $html);
        $this->assertStringNotContainsString('modalObservarSolicitud', $html);

        // 5. Si cerramos el plan del lote, NO debe mostrarse en la vista por defecto (solo nuevas/activas)
        $this->db->prepare("UPDATE planes_rotura SET estado = 'cerrado' WHERE muestra_id IN (SELECT id FROM recepcion_muestras WHERE id_hoja = ?)")->execute([$idHojaConc]);
        ob_start();
        $labCtrl->index(new Peticion(), new class extends Respuesta {
            public function redirigir(string $url): void {}
        });
        $htmlSinCerradas = (string)ob_get_clean();
        $this->assertStringNotContainsString('Reg. ' . $hojaConc['numero_registro'], $htmlSinCerradas, 'Las muestras cerradas no deben caer en la vista por defecto de muestras nuevas.');

        // 6. Visualizar Hoja RT-FM-60 usando solo muestra_id (sin id_os) y usando solo id_hoja
        $rmIdTest = (int)$recRows[0]['id'];
        $_GET = ['muestra_id' => $rmIdTest, 'id_hoja' => $idHojaConc];
        ob_start();
        $labCtrl->imprimirHojaSolicitud(new Peticion(), new class extends Respuesta {
            public ?string $redir = null;
            public function redirigir(string $url): void {
                $this->redir = $url;
            }
        });
        $htmlFm60Muestra = (string)ob_get_clean();
        $this->assertStringContainsString('CYCSA-RT-FM-60', $htmlFm60Muestra);
        $this->assertStringContainsString('HOJA DE SOLICITUD', $htmlFm60Muestra);
        $this->assertStringContainsString($codRec1, $htmlFm60Muestra);

        $_GET = ['id_hoja' => $idHojaConc];
        ob_start();
        $labCtrl->imprimirHojaSolicitud(new Peticion(), new class extends Respuesta {
            public ?string $redir = null;
            public function redirigir(string $url): void {
                $this->redir = $url;
            }
        });
        $htmlFm60Hoja = (string)ob_get_clean();
        $this->assertStringContainsString('CYCSA-RT-FM-60', $htmlFm60Hoja);
        $this->assertStringContainsString($codRec1, $htmlFm60Hoja);
    }
}
