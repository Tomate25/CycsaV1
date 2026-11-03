<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Controladores\LaboratorioControlador;
use Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo;
use PDO;

class TestRespuestaPlantillaHoja extends Respuesta {
    public ?string $urlRedireccion = null;
    public ?array $jsonData = null;
    public int $statusCode = 200;

    public function redirigir(string $url): void {
        $this->urlRedireccion = $url;
    }

    public function enviarJson($datos, int $codigo = 200): void {
        $this->jsonData = $datos;
        $this->statusCode = $codigo;
    }
}

class Rtfm13PlantillaYEnsayoPorHojaTest extends TestCase {

    private PDO $db;
    private OperacionModelo $opModelo;
    private HojasServicioControlador $hojaControlador;
    private LaboratorioControlador $labControlador;
    private PlantillaModelo $pltModelo;

    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idOS = 0;
    private int $idProdDensimetro = 0;
    private int $idProdConcreto = 0;
    private int $idDetalleDensimetro = 0;
    private int $idDetalleConcreto = 0;
    private int $idPltDensimetro = 0;
    private int $idPltConcreto = 0;

    private array $hojasCreadas = [];
    private array $plantillasCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->opModelo = new OperacionModelo();
        $this->hojaControlador = new HojasServicioControlador();
        $this->labControlador = new LaboratorioControlador();
        $this->pltModelo = new PlantillaModelo();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        unset($_SESSION['error'], $_SESSION['exito']);

        $usrId = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $_SESSION['usuario_id'] = $usrId;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Plantilla Test';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['usuario_permisos'] = [
            'hojas_servicio' => ['crear_editar' => 1, 'ver' => 1],
            'laboratorio' => ['crear_editar' => 1, 'ver' => 1],
            'operaciones' => ['crear_editar' => 1, 'ver' => 1],
            'ordenes_servicio' => ['crear_editar' => 1, 'ver' => 1]
        ];

        $this->crearFixtures();
    }

    protected function tearDown(): void {
        if (isset($_SESSION)) {
            unset($_SESSION['error'], $_SESSION['exito']);
        }
        if (!empty($this->hojasCreadas)) {
            $inClause = implode(',', array_map('intval', $this->hojasCreadas));
            $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inClause})");
            $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inClause})");
        }
        if (!empty($this->plantillasCreadas)) {
            $inPlt = implode(',', array_map('intval', $this->plantillasCreadas));
            $this->db->exec("DELETE FROM plantillas WHERE id IN ({$inPlt})");
        }
        if ($this->idOS > 0) {
            $this->db->prepare("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = :id")->execute(['id' => $this->idOS]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_os = :id")->execute(['id' => $this->idOS]);
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
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES (:n, :r, :e, :t)");
        $stmtCli->execute([
            'n' => "Cliente Plantilla Test {$unique}",
            'r' => "RUC-PL-{$unique}",
            'e' => "plt_{$unique}@cycsa.test",
            't' => '2222-7777'
        ]);
        $this->idCliente = (int)$this->db->lastInsertId();

        // 2. Productos:
        // Producto 1: Densímetro Nuclear
        $stmtP1 = $this->db->query("SELECT id FROM productos WHERE norma_astm LIKE '%D6938%' OR nombre_comercial LIKE '%Densimetro%' OR nombre_comercial LIKE '%Densímetro%' LIMIT 1");
        $this->idProdDensimetro = (int)$stmtP1->fetchColumn();
        if ($this->idProdDensimetro <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 B', 'Densímetro Nuclear Compactación In Situ', 'ASTM D6938', 1, 1200)");
            $this->idProdDensimetro = (int)$this->db->lastInsertId();
        }

        // Producto 2: Concreto
        $stmtP2 = $this->db->query("SELECT id FROM productos WHERE (nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%') AND id != {$this->idProdDensimetro} LIMIT 1");
        $this->idProdConcreto = (int)$stmtP2->fetchColumn();
        if ($this->idProdConcreto <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 A', 'Resistencia Cilindros Concreto', 'ASTM C39', 21, 500)");
            $this->idProdConcreto = (int)$this->db->lastInsertId();
        }

        $usrId = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;

        // 3. Plantillas aprobadas para cada producto
        // Plantilla para Densímetro
        $stmtPlt1 = $this->db->prepare("
            INSERT INTO plantillas (codigo, nombre, ensayo_id, norma, orientacion, cliente_id, estado, version_actual, es_por_defecto, creado_por)
            VALUES (:cod, :nom, :eid, 'ASTM D6938', 'vertical', NULL, 'aprobada', 'Rev. 01', 1, :usr)
        ");
        $stmtPlt1->execute([
            'cod' => "PL-DENS-{$unique}",
            'nom' => "Compactación Densímetro Nuclear {$unique}",
            'eid' => $this->idProdDensimetro,
            'usr' => $usrId
        ]);
        $this->idPltDensimetro = (int)$this->db->lastInsertId();
        $this->plantillasCreadas[] = $this->idPltDensimetro;

        // Plantilla para Concreto
        $stmtPlt2 = $this->db->prepare("
            INSERT INTO plantillas (codigo, nombre, ensayo_id, norma, orientacion, cliente_id, estado, version_actual, es_por_defecto, creado_por)
            VALUES (:cod, :nom, :eid, 'ASTM C39', 'vertical', NULL, 'aprobada', 'Rev. 01', 1, :usr)
        ");
        $stmtPlt2->execute([
            'cod' => "PL-CONC-{$unique}",
            'nom' => "Resistencia a la Compresión Concreto {$unique}",
            'eid' => $this->idProdConcreto,
            'usr' => $usrId
        ]);
        $this->idPltConcreto = (int)$this->db->lastInsertId();
        $this->plantillasCreadas[] = $this->idPltConcreto;

        // 4. Cotización
        $codigoCot = "COT-PL-" . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total, fecha_creacion)
                                       VALUES (:c, :cli, :usr, 'Ing. Supervisor', 'Proyecto O/S Mixta Plantillas', 'Contado', 'Aprobada por Cliente', 1700.00, 1700.00, NOW())");
        $stmtCot->execute([
            'c' => $codigoCot,
            'cli' => $this->idCliente,
            'usr' => $usrId
        ]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        // 5. Cotización Detalles:
        // IMPORTANTE: Detalle 1 = Densímetro Nuclear (por tanto ensayos[0] es Densímetro)
        $stmtDet1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Densímetro Nuclear ASTM D6938 In Situ', 'CYCSA-RT-FM-22 B', 'ASTM D6938', 2, 600.00, 1200.00)");
        $stmtDet1->execute(['cot' => $this->idCotizacion, 'p' => $this->idProdDensimetro]);
        $this->idDetalleDensimetro = (int)$this->db->lastInsertId();

        // Detalle 2 = Concreto (ensayos[1] es Concreto)
        $stmtDet2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Cilindros Concreto f\'c 280 kg/cm²', 'CYCSA-RT-FM-22 A', 'ASTM C39', 2, 250.00, 500.00)");
        $stmtDet2->execute(['cot' => $this->idCotizacion, 'p' => $this->idProdConcreto]);
        $this->idDetalleConcreto = (int)$this->db->lastInsertId();

        // 6. Orden de Servicio Aprobada
        $osModelo = new OrdenServicioModelo();
        $this->idOS = $osModelo->crear([
            'codigo_os' => "OS-PL-" . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Supervisor',
            'nombre_proyecto' => 'Proyecto O/S Mixta Plantillas',
            'forma_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada'
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS}");

        // Whitelist para ambos detalles
        $this->db->exec("INSERT INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES ({$this->idOS}, {$this->idDetalleDensimetro})");
        $this->db->exec("INSERT INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES ({$this->idOS}, {$this->idDetalleConcreto})");
    }

    public function testGuardarHojaConcretoPreservaPlantillaElegidaSinSobrescrituraDeDensimetro(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'numero_registro' => 'REG-CONC-EXP-01',
            'nombre_empresa_o_cliente' => 'Cliente Plantilla Test',
            'procedencia_punto_muestreo' => 'Sitio Obra',
            'nombre_persona_entrega_muestra' => 'Ing. Residente',
            'nombre_persona_toma_muestra' => 'Cliente / Entregada por Cliente',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),
            'nombre_recibe_cycsa' => 'Receptor Test',
            'plantilla_global_id' => $this->idPltConcreto,
            'm_nombre' => ['MC-0001-26', 'MC-0002-26'],
            'm_desc' => ['Cilindro Zapata 1', 'Cilindro Zapata 2'],
            'm_info' => ['7 días', '14 días'],
            'm_ensayo_id' => [$this->idProdConcreto, $this->idProdConcreto],
            'm_plantilla_id' => [$this->idPltConcreto, $this->idPltConcreto]
        ];

        $peticion = new Peticion();
        $respuesta = new TestRespuestaPlantillaHoja();

        $this->hojaControlador->guardarHojaSolicitud($peticion, $respuesta);

        $this->assertNotEmpty($_SESSION['exito'] ?? null, 'Debe registrar mensaje de éxito al guardar.');
        $this->assertNull($_SESSION['error'] ?? null, 'No debe haber error en sesión.');

        $stmtH = $this->db->prepare("SELECT * FROM hojas_solicitud WHERE id_os = :os AND id_detalle_cotizacion = :det ORDER BY id DESC LIMIT 1");
        $stmtH->execute(['os' => $this->idOS, 'det' => $this->idDetalleConcreto]);
        $hoja = $stmtH->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($hoja, 'La hoja de concreto debe haberse creado.');
        $this->hojasCreadas[] = (int)$hoja['id'];

        // Verificar hojas_solicitud_detalles
        $stmtDet = $this->db->prepare("SELECT * FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid ORDER BY orden ASC, id ASC");
        $stmtDet->execute(['hid' => (int)$hoja['id']]);
        $filas = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $filas);
        foreach ($filas as $f) {
            $this->assertEquals($this->idPltConcreto, (int)$f['plantilla_id'], 'La plantilla guardada debe ser exactamente la de Concreto, jamás la de Densímetro.');
            $this->assertNotEquals($this->idPltDensimetro, (int)$f['plantilla_id'], 'No debe contener la plantilla de densímetro.');
            $this->assertEquals($this->idProdConcreto, (int)$f['ensayo_id'], 'El ensayo_id debe ser el producto de Concreto.');
        }
    }

    public function testGuardarHojaConcretoSinPlantillaAsignaPlantillaSugeridaDeConcreto(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'numero_registro' => 'REG-CONC-SUG-02',
            'nombre_empresa_o_cliente' => 'Cliente Plantilla Test',
            'procedencia_punto_muestreo' => 'Sitio Obra',
            'nombre_persona_entrega_muestra' => 'Ing. Residente',
            'nombre_persona_toma_muestra' => 'Cliente / Entregada por Cliente',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),
            'nombre_recibe_cycsa' => 'Receptor Test',
            'plantilla_global_id' => '',
            'm_nombre' => ['MC-0003-26'],
            'm_desc' => ['Cilindro Losa 1'],
            'm_info' => ['28 días'],
            'm_ensayo_id' => [''],
            'm_plantilla_id' => ['']
        ];

        $peticion = new Peticion();
        $respuesta = new TestRespuestaPlantillaHoja();

        $this->hojaControlador->guardarHojaSolicitud($peticion, $respuesta);

        $this->assertNotEmpty($_SESSION['exito'] ?? null);
        $this->assertNull($_SESSION['error'] ?? null);

        $stmtH = $this->db->prepare("SELECT * FROM hojas_solicitud WHERE id_os = :os AND numero_registro = 'REG-CONC-SUG-02' LIMIT 1");
        $stmtH->execute(['os' => $this->idOS]);
        $hoja = $stmtH->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($hoja);
        $this->hojasCreadas[] = (int)$hoja['id'];

        $stmtDet = $this->db->prepare("SELECT * FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid LIMIT 1");
        $stmtDet->execute(['hid' => (int)$hoja['id']]);
        $fila = $stmtDet->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($fila);
        $this->assertEquals($this->idPltConcreto, (int)$fila['plantilla_id'], 'La plantilla sugerida por fallback debe ser la de Concreto, nunca ensayosOS[0] (Densímetro).');
        $this->assertEquals($this->idProdConcreto, (int)$fila['ensayo_id'], 'El ensayo_id por fallback debe ser el id_producto de la hoja de Concreto.');
    }

    public function testImprimirHojaMuestraUnicamenteElEnsayoDeLaHoja(): void {
        // Crear una hoja de Concreto
        $idHoja = 0;
        $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'id_producto' => $this->idProdConcreto,
            'ensayo_id' => $this->idProdConcreto,
            'numero_registro' => 'REG-CONC-PRINT-03',
            'nombre_empresa_o_cliente' => 'Cliente Print Test',
            'muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0005-26', 'descripcion' => 'Cilindro Vigas']
            ])
        ], $idHoja);
        $this->hojasCreadas[] = $idHoja;

        $_GET['id_os'] = $this->idOS;
        $_GET['id_hoja'] = $idHoja;

        $peticion = new Peticion();
        $respuesta = new TestRespuestaPlantillaHoja();

        ob_start();
        $this->labControlador->imprimirHojaSolicitud($peticion, $respuesta);
        $outputHtml = ob_get_clean();

        $this->assertNotEmpty($outputHtml, 'La vista de impresión debe renderizar HTML.');
        $this->assertStringContainsString('Cilindros Concreto', $outputHtml, 'Debe contener la descripción del ensayo de Concreto.');
        $this->assertStringNotContainsString('Densímetro Nuclear', $outputHtml, 'El documento de la hoja de Concreto jamás debe mencionar Densímetro Nuclear.');
    }

    public function testBandejaLaboratorioReportaEnsayoCorrectoPorHoja(): void {
        // En la O/S mixta:
        // - Hoja 1 = Densímetro (in situ, excluida de Lab)
        // - Hoja 2 = Concreto (Lab)
        $idHojaConc = 0;
        $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalleConcreto,
            'id_producto' => $this->idProdConcreto,
            'ensayo_id' => $this->idProdConcreto,
            'numero_registro' => 'REG-CONC-LAB-04',
            'nombre_empresa_o_cliente' => 'Cliente Lab Test',
            'muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0007-26', 'descripcion' => 'Cilindro Zapata']
            ])
        ], $idHojaConc);
        $this->hojasCreadas[] = $idHojaConc;

        $_GET['id_os'] = $this->idOS;
        $peticion = new Peticion();
        $respuesta = new TestRespuestaPlantillaHoja();

        // Consultar directamente la lógica de index() para solicitudes por aprobar
        $operacionModelo = new OperacionModelo();
        $todasOrdenes = $this->db->query("
            SELECT os.id AS id_os, os.codigo_os, os.fecha_emision, os.estado AS estado_os,
                   os.requiere_muestreo, os.tipo_contrato, os.motivo_observacion,
                   hs.id AS id_hoja, hs.codigo_documento AS hoja_codigo, hs.numero_registro,
                   hs.id_detalle_cotizacion, hs.id_producto,
                   hs.fecha_hora_toma_muestra, hs.muestras_json, hs.naturaleza_muestra,
                   hs.procedencia_punto_muestreo, hs.observaciones, hs.fecha_creacion AS hoja_fecha_creacion,
                   cot.id AS id_cotizacion,
                   c.nombre_razon_social AS cliente_nombre,
                   (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_hoja = hs.id) AS total_recibidas_lab
            FROM hojas_solicitud hs
            INNER JOIN ordenes_servicio os ON hs.id_os = os.id
            INNER JOIN cotizaciones cot ON os.id_cotizacion = cot.id
            INNER JOIN clientes c ON os.id_cliente = c.id
            WHERE hs.id = {$idHojaConc}
        ")->fetchAll(PDO::FETCH_ASSOC);

        $solicitudes = [];
        foreach ($todasOrdenes as $o) {
            $ensayos = $operacionModelo->filtrarDetallesPermitidos((int)$o['id_os'], $operacionModelo->obtenerDetallesCotizacion((int)$o['id_cotizacion']));
            $stmtD = $this->db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial FROM cotizacion_detalles cd LEFT JOIN productos p ON cd.id_producto = p.id WHERE cd.id = :id");
            $stmtD->execute(['id' => (int)$o['id_detalle_cotizacion']]);
            $detHoja = $stmtD->fetch(PDO::FETCH_ASSOC);

            if (!empty($o['id_detalle_cotizacion']) && !empty($detHoja)) {
                $o['ensayos'] = [$detHoja];
            } else {
                $o['ensayos'] = $ensayos;
            }
            $solicitudes[] = $o;
        }

        $this->assertCount(1, $solicitudes);
        $solConcreto = $solicitudes[0];
        $this->assertCount(1, $solConcreto['ensayos'], 'Debe contener únicamente el ensayo de la hoja, no todos los de la O/S.');
        $this->assertEquals((int)$this->idDetalleConcreto, (int)$solConcreto['ensayos'][0]['id']);
        $this->assertStringContainsString('Cilindros Concreto', $solConcreto['ensayos'][0]['descripcion_ensayo']);
    }

    public function testEndpointDatosHojaSolicitudRetornaProductoYSugeridaCorrectos(): void {
        $_GET['id_os'] = $this->idOS;
        $_GET['id_detalle_cotizacion'] = $this->idDetalleConcreto;
        $_GET['nueva'] = '1';

        $peticion = new Peticion();
        $respuesta = new TestRespuestaPlantillaHoja();

        $this->hojaControlador->hojaSolicitudDatosAjax($peticion, $respuesta);

        $json = $respuesta->jsonData;
        $this->assertIsArray($json, 'Debe retornar JSON estructurado.');
        $this->assertEquals('success', $json['status']);
        $this->assertEquals($this->idProdConcreto, $json['producto_hoja_id'], 'producto_hoja_id debe coincidir con Concreto.');
        $this->assertFalse($json['es_compactacion_hoja'], 'es_compactacion_hoja debe ser falso para Concreto.');
        $this->assertNotEmpty($json['plantilla_sugerida_hoja'], 'Debe tener plantilla sugerida para Concreto.');
        $this->assertEquals($this->idPltConcreto, (int)$json['plantilla_sugerida_hoja']['id'], 'La plantilla sugerida debe ser la de Concreto, no la de Densímetro.');
    }
}
