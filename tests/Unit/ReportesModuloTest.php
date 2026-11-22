<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Reportes\Controladores\ReportesControlador;
use Cycsa\Modulos\Reportes\Modelos\ReporteModelo;
use Cycsa\Modulos\Reportes\Servicios\ExportadorExcelXlsx;
use PDO;
use ZipArchive;

class ReportesRespuestaSpy extends Respuesta {
    public string $urlRedirigida = '';

    public function redirigir(string $url): void {
        $this->urlRedirigida = $url;
    }
}

class ReportesModuloTest extends TestCase {

    private PDO $db;
    private array $sessionBackup = [];

    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idDetalle = 0;
    private int $idOS = 0;
    private int $idHoja = 0;
    private int $idProd = 0;
    private int $idOrdenCompra = 0;
    private int $idPartida = 0;
    private int $idPm = 0;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->sessionBackup = $_SESSION ?? [];
        $_SESSION = [
            'usuario_id' => 1,
            'usuario_rol' => 1,
            'usuario_nombre' => 'Admin Reportes Test',
            'usuario_permisos' => [],
        ];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (id_rol, nombre, email, password, activo) VALUES (1, 'User Rep', 'rep@test.local', 'hash', 1)");
            $idUser = (int)$this->db->lastInsertId();
        }
        $_SESSION['usuario_id'] = $idUser;

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($this->idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email) VALUES ('Cliente Reporte Test', 'J0310000077777', 'creport@test.local')");
            $this->idCliente = (int)$this->db->lastInsertId();
        }

        $this->idProd = (int)$this->db->query("SELECT id FROM productos LIMIT 1")->fetchColumn();

        $codCot = 'COT-REP-' . substr(uniqid(), -7);
        $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, impuesto, total) VALUES (:cod, :cli, :usr, 'Ing. Rep', 'Proyecto Reportes & KPIs', 'Contado', 'Aprobada por Cliente', 1000, 150, 1150)")
                 ->execute(['cod' => $codCot, 'cli' => $this->idCliente, 'usr' => $idUser]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Resistencia de Concreto Reporte', 'CONC-REP', 'ASTM C39', 2, 500, 1000)")
                 ->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProd]);
        $this->idDetalle = (int)$this->db->lastInsertId();

        $codOs = 'OS-REP-' . uniqid();
        $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, id_cliente_factura, id_cliente_informe, fecha_emision, nombre_proyecto, condicion_comercial, estado, requiere_muestreo) VALUES (:cod, :cot, :cli, :clif, :clii, '2026-11-16', 'Proyecto Reportes & KPIs', 'contado', 'Aprobada', 1)")
                 ->execute(['cod' => $codOs, 'cot' => $this->idCotizacion, 'cli' => $this->idCliente, 'clif' => $this->idCliente, 'clii' => $this->idCliente]);
        $this->idOS = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, codigo_documento, numero_registro, nombre_empresa_o_cliente, muestras_json) VALUES (:os, :det, :prod, :ens, 'CYCSA-RT-FM-13', 'REG-REP-01', 'Cliente Reporte Test', :mjson)")
                 ->execute([
                     'os' => $this->idOS,
                     'det' => $this->idDetalle,
                     'prod' => $this->idProd,
                     'ens' => $this->idProd,
                     'mjson' => json_encode([['nombre_muestra' => 'MC-9001-26'], ['nombre_muestra' => 'MC-9002-26']]),
                 ]);
        $this->idHoja = (int)$this->db->lastInsertId();

        $idTec = (int)$this->db->query("SELECT id FROM tecnicos LIMIT 1")->fetchColumn();
        $idVeh = (int)$this->db->query("SELECT id FROM vehiculos LIMIT 1")->fetchColumn();
        if ($idTec > 0 && $idVeh > 0) {
            $this->db->prepare("INSERT INTO programacion_muestreo (id_orden_servicio, fecha_ida, fecha_llegada, id_tecnico, id_vehiculo, estado_muestreo, lugar_muestreo, checklist_json) VALUES (:os, '2026-11-16 08:00:00', '2026-11-16 12:00:00', :tec, :veh, 'Finalizado', 'Obra Central', '{\"cono\":\"1\",\"termometro\":\"1\"}')")
                     ->execute(['os' => $this->idOS, 'tec' => $idTec, 'veh' => $idVeh]);
            $this->idPm = (int)$this->db->lastInsertId();
        }

        $this->db->prepare("INSERT INTO ordenes_compra (proveedor, fecha, subtotal, iva, total, estado) VALUES ('Proveedor Reactivos Test', '2026-11-16', 500, 75, 575, 'Aprobada')")
                 ->execute();
        $this->idOrdenCompra = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO partidas_diario (num_partida, fecha, concepto, origen) VALUES ('AS-REP-01', '2026-11-16', 'Registro contable prueba reportes', 'Manual')")
                 ->execute();
        $this->idPartida = (int)$this->db->lastInsertId();
        $idCta = (int)$this->db->query("SELECT id FROM cuentas_contables LIMIT 1")->fetchColumn();
        if ($idCta > 0) {
            $this->db->prepare("INSERT INTO partidas_diario_detalles (id_partida, id_cuenta_contable, debe, haber) VALUES (:p, :c, 250.00, 0.00), (:p2, :c2, 0.00, 250.00)")
                     ->execute(['p' => $this->idPartida, 'c' => $idCta, 'p2' => $this->idPartida, 'c2' => $idCta]);
        }
    }

    protected function tearDown(): void {
        try {
            if ($this->idPartida > 0) {
                $this->db->exec("DELETE FROM partidas_diario_detalles WHERE id_partida = {$this->idPartida}");
                $this->db->exec("DELETE FROM partidas_diario WHERE id = {$this->idPartida}");
            }
            if ($this->idOrdenCompra > 0) {
                $this->db->exec("DELETE FROM ordenes_compra WHERE id = {$this->idOrdenCompra}");
            }
            if ($this->idPm > 0) {
                $this->db->exec("DELETE FROM programacion_muestreo WHERE id = {$this->idPm}");
            }
            if ($this->idHoja > 0) {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = {$this->idHoja}");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id = {$this->idHoja}");
            }
            if ($this->idOS > 0) {
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOS}");
            }
            if ($this->idCotizacion > 0) {
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacion}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacion}");
            }
        } catch (\Throwable $e) {}

        $_SESSION = $this->sessionBackup;
        $_GET = [];
        parent::tearDown();
    }

    public function testTodosLosMiniModulosYSubvistasGeneranKpisYFilasSinError(): void {
        $modelo = new ReporteModelo();
        $catalogo = $modelo->obtenerCatalogoMiniModulos();
        $this->assertCount(14, $catalogo);

        foreach ($catalogo as $slug => $meta) {
            $rep = $modelo->generarReporteModulo($slug);
            $this->assertNotEmpty($rep['kpis'], "Debe incluir tarjetas KPI en {$slug}");
            $this->assertNotEmpty($rep['columns'], "Debe incluir columnas en {$slug}");
            $this->assertIsArray($rep['rows']);

            if (!empty($rep['subvistas'])) {
                foreach (array_keys($rep['subvistas']) as $sv) {
                    $repSv = $modelo->generarReporteModulo($slug, ['subvista' => $sv]);
                    $this->assertNotEmpty($repSv['columns'], "Debe incluir columnas en {$slug}/{$sv}");
                }
            }
        }
    }

    public function testTrazabilidadRtFm13VinculaClienteOSCotizacionYPdf(): void {
        $modelo = new ReporteModelo();
        $rep = $modelo->generarReporteModulo('rtfm13', ['q' => 'REG-REP-01']);

        $this->assertCount(1, $rep['rows']);
        $fila = $rep['rows'][0];
        $this->assertSame('REG-REP-01', $fila['numero_registro']);
        $this->assertNotEmpty($fila['cliente']);
        $this->assertStringStartsWith('OS-REP-', $fila['codigo_os']);
        $this->assertStringStartsWith('COT-REP-', $fila['cotizacion']);
        $this->assertSame(2, $fila['cant_muestras']);

        $labels = array_column($fila['_pdfs'], 'label');
        $urls = array_column($fila['_pdfs'], 'url');
        $this->assertContains('RT-FM-13 PDF', $labels);
        $this->assertContains('O/S PDF', $labels);
        $this->assertContains('Cotización PDF', $labels);
        $this->assertStringContainsString('/hojas-servicio/descargar?id_os=' . $this->idOS . '&id_hoja=' . $this->idHoja, $urls[0]);
    }

    public function testExportadorExcelXlsxYControladorGeneranLibroValido(): void {
        $ctrlSpy = new class extends ReportesControlador {
            public string $archivo = '';
            public string $binario = '';
            protected function emitirBinarioExcel(string $nombreArchivo, string $contenidoXlsx): void {
                $this->archivo = $nombreArchivo;
                $this->binario = $contenidoXlsx;
            }
        };

        $_GET = ['modulo' => 'rtfm13', 'q' => 'REG-REP-01'];
        $ctrlSpy->exportarExcel(new Peticion(), new ReportesRespuestaSpy());

        $this->assertStringStartsWith('reporte_rtfm13_', $ctrlSpy->archivo);
        $this->assertStringEndsWith('.xlsx', $ctrlSpy->archivo);
        $this->assertStringStartsWith('PK', $ctrlSpy->binario, 'El .xlsx debe ser un contenedor ZIP OOXML válido.');

        $tmp = tempnam(sys_get_temp_dir(), 'verify_xlsx_');
        file_put_contents($tmp, $ctrlSpy->binario);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $contentTypesXml = $zip->getFromName('[Content_Types].xml');
        $rootRelsXml = $zip->getFromName('_rels/.rels');
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertNotFalse($contentTypesXml);
        $this->assertNotFalse($rootRelsXml);
        $this->assertNotFalse($workbookXml);
        $this->assertNotFalse($workbookRelsXml);
        $this->assertNotFalse($stylesXml);
        $this->assertNotFalse($sheetXml);

        // Validar ContentType OPC oficial para partes .rels (ECMA-376 Parte 2)
        $this->assertStringContainsString('application/vnd.openxmlformats-package.relationships+xml', $contentTypesXml);
        $this->assertStringNotContainsString('application/vnd.openxmlformats-officedocument.relationships+xml', $contentTypesXml);

        // Validar que todas las partes XML del paquete OOXML sean XML bien formado
        foreach ([$contentTypesXml, $rootRelsXml, $workbookXml, $workbookRelsXml, $stylesXml, $sheetXml] as $xmlPart) {
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($xmlPart), 'Cada parte XML del .xlsx debe ser XML válido.');
        }

        $this->assertStringContainsString('<cellStyles count="1">', $stylesXml);
        $this->assertStringContainsString('REG-REP-01', $sheetXml);
        $this->assertStringContainsString('Proyecto Reportes &amp; KPIs', $sheetXml);
    }

    public function testExportadorExcelXlsxCasosBordeOoxmlValido(): void {
        $xlsxUnaCol = ExportadorExcelXlsx::generar(
            "'Reporte: [Prueba] / Especial'",
            'Sin registros',
            [['key' => 'unico', 'label' => 'Única', 'type' => 'text']],
            [['unico' => "Valor & <ok>\x01\x0B"]]
        );
        $tmp = tempnam(sys_get_temp_dir(), 'verify_xlsx_edge_');
        file_put_contents($tmp, $xlsxUnaCol);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $workbookXml = (string)$zip->getFromName('xl/workbook.xml');
        $sheetXml = (string)$zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringNotContainsString('<mergeCells', $sheetXml, 'Con 1 sola columna no debe emitir mergeCells A1:A1.');
        $this->assertStringNotContainsString("name=\"'", $workbookXml, 'El nombre de hoja no debe iniciar con comilla simple.');
        $this->assertStringContainsString('Valor &amp; &lt;ok&gt;', $sheetXml);
    }

    public function testRbacReportesAislaMiniModulosSegunPermisos(): void {
        // 1. Usuario sin permiso 'reportes' es redirigido a /panel
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = ['cotizaciones' => ['ver' => 1]];
        $ctrl = new ReportesControlador();
        $resp = new ReportesRespuestaSpy();
        $ctrl->index(new Peticion(), $resp);
        $this->assertStringEndsWith('/panel', $resp->urlRedirigida);

        // 2. Usuario con 'reportes' + solo 'cotizaciones' solo ve mini-modulo cotizaciones y no puede abrir contabilidad
        $_SESSION['usuario_permisos'] = [
            'reportes' => ['ver' => 1],
            'cotizaciones' => ['ver' => 1],
        ];
        ob_start();
        $ctrl->index(new Peticion(), new ReportesRespuestaSpy());
        $htmlHub = ob_get_clean();
        $this->assertStringContainsString('Cotizaciones (RG-FM-31)', $htmlHub);
        $this->assertStringNotContainsString('Contabilidad y Registros Diarios', $htmlHub);
        $this->assertStringNotContainsString('Compras, Órdenes y Recepciones', $htmlHub);

        $_GET = ['modulo' => 'contabilidad'];
        $respContab = new ReportesRespuestaSpy();
        $ctrl->ver(new Peticion(), $respContab);
        $this->assertStringEndsWith('/reportes', $respContab->urlRedirigida);
    }
}
