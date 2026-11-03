<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;
use PDOException;

class TestRespuestaAntiDupe extends Respuesta {
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

class Rtfm13AntiDuplicadosTest extends TestCase {
    private PDO $db;
    private OperacionModelo $opModelo;
    private HojasServicioControlador $controlador;
    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idOS = 0;
    private int $idDetalle1 = 0;
    private int $idDetalle2 = 0;
    private int $idProd1 = 0;
    private int $idProd2 = 0;
    private array $hojasCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->opModelo = new OperacionModelo();
        $this->controlador = new HojasServicioControlador();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Test AntiDupe';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->crearFixtures();
    }

    protected function tearDown(): void {
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
            'n' => "Cliente AntiDupe {$unique}",
            'r' => "RUC-AD-{$unique}",
            'e' => "antidupe_{$unique}@cycsa.test",
            't' => '2222-3333'
        ]);
        $this->idCliente = (int)$this->db->lastInsertId();

        // 2. Productos
        $stmtProd1 = $this->db->query("SELECT id FROM productos WHERE nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%' LIMIT 1");
        $this->idProd1 = (int)$stmtProd1->fetchColumn();
        if ($this->idProd1 <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 A', 'Resistencia Concreto AntiDupe', 'ASTM C39', 21, 500)");
            $this->idProd1 = (int)$this->db->lastInsertId();
        }

        $stmtProd2 = $this->db->query("SELECT id FROM productos WHERE (nombre_comercial LIKE '%Granulo%' OR nombre_comercial LIKE '%Proctor%') AND id != {$this->idProd1} LIMIT 1");
        $this->idProd2 = (int)$stmtProd2->fetchColumn();
        if ($this->idProd2 <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 F', 'Suelo AntiDupe', 'ASTM D6913', 21, 750)");
            $this->idProd2 = (int)$this->db->lastInsertId();
        }

        // 3. Cotización
        $codigoCot = "COT-AD-" . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total, fecha_creacion)
                                       VALUES (:c, :cli, 1, 'Ing. AntiDupe', 'Proyecto AntiDupe', 'Contado', 'Aprobada por Cliente', 1500.00, 1500.00, NOW())");
        $stmtCot->execute([
            'c' => $codigoCot,
            'cli' => $this->idCliente
        ]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        // 4. Cotización Detalles (2 productos)
        $stmtDet1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Detalle 1 AntiDupe', 'CYCSA-RT-FM-22 A', 'ASTM C39', 2, 250.00, 500.00)");
        $stmtDet1->execute(['cot' => $this->idCotizacion, 'p' => $this->idProd1]);
        $this->idDetalle1 = (int)$this->db->lastInsertId();

        $stmtDet2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal)
                                        VALUES (:cot, :p, 'Detalle 2 AntiDupe', 'CYCSA-RT-FM-22 F', 'ASTM D6913', 1, 400.00, 400.00)");
        $stmtDet2->execute(['cot' => $this->idCotizacion, 'p' => $this->idProd2]);
        $this->idDetalle2 = (int)$this->db->lastInsertId();

        // 5. Orden de Servicio (Estado Aprobada)
        $osModelo = new OrdenServicioModelo();
        $this->idOS = $osModelo->crear([
            'codigo_os' => "OS-AD-" . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. AntiDupe',
            'nombre_proyecto' => 'Proyecto AntiDupe',
            'forma_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada'
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS}");
    }

    public function testDobleSubmitSecuencialMismoProductoRetornaMismaHojaYSoloCreaUnaFila(): void {
        $datosHojaPrimerClick = [
            'id_os' => $this->idOS,
            'id_hoja' => 0, // Nuevo registro
            'id_detalle_cotizacion' => $this->idDetalle1,
            'id_producto' => $this->idProd1,
            'ensayo_id' => $this->idProd1,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'nombre_persona_entrega_muestra' => 'Ing. Primer Click',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0001-26', 'descripcion' => 'Espécimen 1', 'ensayo_id' => $this->idProd1]
            ])
        ];

        // 1er Submit
        $idGenerado1 = 0;
        $ok1 = $this->opModelo->guardarHojaSolicitud($datosHojaPrimerClick, $idGenerado1);
        $this->assertTrue($ok1, 'El primer submit debe procesarse exitosamente.');
        $this->assertGreaterThan(0, $idGenerado1);
        $this->hojasCreadas[] = $idGenerado1;

        $hoja1 = $this->opModelo->obtenerHojaSolicitudPorId($idGenerado1);
        $numReg1 = $hoja1['numero_registro'];
        $this->assertNotEmpty($numReg1, 'El primer registro debe tener un número asignado.');

        // 2do Submit idéntico (simulando doble clic o reenvío rápido con id_hoja=0)
        $datosHojaSegundoClick = [
            'id_os' => $this->idOS,
            'id_hoja' => 0, // Llega con 0 porque el usuario clickeó dos veces antes de la recarga
            'id_detalle_cotizacion' => $this->idDetalle1,
            'id_producto' => $this->idProd1,
            'ensayo_id' => $this->idProd1,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'nombre_persona_entrega_muestra' => 'Ing. Segundo Click',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0001-26', 'descripcion' => 'Espécimen 1 Modificado', 'ensayo_id' => $this->idProd1]
            ])
        ];

        $idGenerado2 = 0;
        $ok2 = $this->opModelo->guardarHojaSolicitud($datosHojaSegundoClick, $idGenerado2);
        $this->assertTrue($ok2, 'El segundo submit debe resolverse de manera idempotente.');

        // Debe retornar el MISMO ID de hoja que la primera emisión
        $this->assertEquals($idGenerado1, $idGenerado2, 'El segundo submit no debe crear un nuevo ID; debe adoptar la hoja existente.');

        // Verificar conteo en BD: debe existir exactamente 1 fila para este producto
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM hojas_solicitud WHERE id_os = :os AND id_detalle_cotizacion = :det");
        $stmtCount->execute(['os' => $this->idOS, 'det' => $this->idDetalle1]);
        $totalFilas = (int)$stmtCount->fetchColumn();
        $this->assertEquals(1, $totalFilas, 'Debe haber exactamente 1 fila en hojas_solicitud tras el doble submit.');

        // Verificar que el numero_registro no se alteró ni se quemó un correlativo nuevo
        $hojaActualizada = $this->opModelo->obtenerHojaSolicitudPorId($idGenerado1);
        $this->assertEquals($numReg1, $hojaActualizada['numero_registro'], 'El numero_registro debe permanecer inmutable.');
    }

    public function testRestriccionUniqueEnBaseDeDatosRechazaInsercionDirectaDuplicada(): void {
        // Insertar una primera hoja directamente en BD
        $stmtIns1 = $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, numero_registro, fecha_creacion)
                                        VALUES (:os, :det, :p, :ens, 'REG-TEST-1', NOW())");
        $stmtIns1->execute(['os' => $this->idOS, 'det' => $this->idDetalle1, 'p' => $this->idProd1, 'ens' => $this->idProd1]);
        $id1 = (int)$this->db->lastInsertId();
        $this->hojasCreadas[] = $id1;

        // Intentar insertar una segunda fila directamente violando la restricción UNIQUE
        $this->expectException(PDOException::class);
        $stmtIns2 = $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, numero_registro, fecha_creacion)
                                        VALUES (:os, :det, :p, :ens, 'REG-TEST-2', NOW())");
        $stmtIns2->execute(['os' => $this->idOS, 'det' => $this->idDetalle1, 'p' => $this->idProd1, 'ens' => $this->idProd1]);
    }

    public function testDistintosProductosEnMismaOSPermitenHojasIndependientesConCorrelativosConsecutivos(): void {
        // Hoja para Producto 1
        $idH1 = 0;
        $ok1 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalle1,
            'id_producto' => $this->idProd1,
            'ensayo_id' => $this->idProd1,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'identificacion_muestras_json' => '[]'
        ], $idH1);
        $this->assertTrue($ok1);
        $this->hojasCreadas[] = $idH1;

        // Hoja para Producto 2 (distinto ítem)
        $idH2 = 0;
        $ok2 = $this->opModelo->guardarHojaSolicitud([
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalle2,
            'id_producto' => $this->idProd2,
            'ensayo_id' => $this->idProd2,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'identificacion_muestras_json' => '[]'
        ], $idH2);
        $this->assertTrue($ok2);
        $this->hojasCreadas[] = $idH2;

        $this->assertNotEquals($idH1, $idH2, 'Dos productos distintos en la misma O/S deben generar IDs distintos.');

        $h1 = $this->opModelo->obtenerHojaSolicitudPorId($idH1);
        $h2 = $this->opModelo->obtenerHojaSolicitudPorId($idH2);
        $this->assertNotEquals($h1['numero_registro'], $h2['numero_registro'], 'Cada producto debe tener su propio numero_registro correlativo.');
    }

    public function testControladorManejaDobleSubmitSinExcepcionYRedirigeConExito(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $payload = [
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalle1,
            'csrf_token' => $_SESSION['csrf_token'],
            'm_nombre' => ['M-01'],
            'm_desc' => ['Ubicación M-01'],
            'm_info' => ['Info M-01'],
            'm_ensayo_id' => [$this->idProd1],
            'm_plantilla_id' => [1]
        ];

        // 1er POST
        $_POST = $payload;
        $peticion1 = new Peticion();
        $respuesta1 = new TestRespuestaAntiDupe();
        $this->controlador->guardarHojaSolicitud($peticion1, $respuesta1);
        if (isset($_SESSION['error'])) {
            $this->fail("Error en guardarHojaSolicitud 1er POST: " . $_SESSION['error']);
        }
        $this->assertStringContainsString('/ordenes-servicio', $respuesta1->urlRedirigida);
        $this->assertNotEmpty($_SESSION['exito']);

        // Registrar hoja para limpieza
        $stmtH = $this->db->prepare("SELECT id FROM hojas_solicitud WHERE id_os = :os AND id_detalle_cotizacion = :det");
        $stmtH->execute(['os' => $this->idOS, 'det' => $this->idDetalle1]);
        $idGenerado = (int)$stmtH->fetchColumn();
        $this->assertGreaterThan(0, $idGenerado);
        $this->hojasCreadas[] = $idGenerado;

        // 2do POST idéntico (simula doble request concurrente / doble clic en navegador)
        unset($_SESSION['exito']);
        $_POST = $payload;
        $peticion2 = new Peticion();
        $respuesta2 = new TestRespuestaAntiDupe();
        $this->controlador->guardarHojaSolicitud($peticion2, $respuesta2);
        if (isset($_SESSION['error'])) {
            $this->fail("Error en guardarHojaSolicitud 2do POST: " . $_SESSION['error']);
        }
        $this->assertStringContainsString('/ordenes-servicio', $respuesta2->urlRedirigida);
        $this->assertNotEmpty($_SESSION['exito']);

        // Verificar que en base de datos SIGUE habiendo exactamente 1 hoja
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM hojas_solicitud WHERE id_os = :os AND id_detalle_cotizacion = :det");
        $stmtCount->execute(['os' => $this->idOS, 'det' => $this->idDetalle1]);
        $this->assertEquals(1, (int)$stmtCount->fetchColumn(), 'El controlador debe garantizar exactamente 1 hoja tras doble envío.');
    }

    public function testMuestrasJsonYDetallesSeActualizanSinDuplicarEspecimenes(): void {
        $idH = 0;
        $datos1 = [
            'id_os' => $this->idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $this->idDetalle1,
            'id_producto' => $this->idProd1,
            'ensayo_id' => $this->idProd1,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0001-26', 'descripcion' => 'Espécimen A', 'ensayo_id' => $this->idProd1]
            ])
        ];
        $this->opModelo->guardarHojaSolicitud($datos1, $idH);
        $this->hojasCreadas[] = $idH;

        $pltModelo = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
        $pltModelo->guardarDetallesMuestrasHoja($idH, [
            ['codigo_muestra' => 'MS-0001-26', 'descripcion_ubicacion' => 'Punto 1', 'ensayo_id' => $this->idProd1]
        ], 1);

        $cnt1 = $this->db->query("SELECT COUNT(*) FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = {$idH}")->fetchColumn();
        $this->assertEquals(1, (int)$cnt1);

        // Segundo guardado con los mismos especímenes actualizados
        $datos2 = [
            'id_os' => $this->idOS,
            'id_hoja' => 0, // simula reenvío concurrente
            'id_detalle_cotizacion' => $this->idDetalle1,
            'id_producto' => $this->idProd1,
            'ensayo_id' => $this->idProd1,
            'nombre_empresa_o_cliente' => 'Cliente AntiDupe Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0001-26', 'descripcion' => 'Espécimen A Actualizado', 'ensayo_id' => $this->idProd1]
            ])
        ];
        $idH2 = 0;
        $this->opModelo->guardarHojaSolicitud($datos2, $idH2);
        $this->assertEquals($idH, $idH2);

        $pltModelo->guardarDetallesMuestrasHoja($idH2, [
            ['codigo_muestra' => 'MS-0001-26', 'descripcion_ubicacion' => 'Punto 1 Actualizado', 'ensayo_id' => $this->idProd1]
        ], 1);

        $cnt2 = $this->db->query("SELECT COUNT(*) FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = {$idH}")->fetchColumn();
        $this->assertEquals(1, (int)$cnt2, 'No se deben duplicar las filas en hojas_solicitud_detalles.');
    }
}
