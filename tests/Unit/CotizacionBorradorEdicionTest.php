<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;

class CotizacionBorradorEdicionTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
    }

    private function getDb(): ?PDO {
        try {
            return Conexion::obtenerInstancia();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function crearDatosBase(PDO $db): array {
        $cli = $db->query("SELECT id FROM clientes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $usr = $db->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios en la BD.');
        }
        return [(int)$cli['id'], (int)$usr['id']];
    }

    public function testActualizarBorradorMantieneEstadoBorradorSinVersionar(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }
        [$idCliente, $idUsuario] = $this->crearDatosBase($db);

        $codigoTest = 'CB-' . substr(bin2hex(random_bytes(6)), 0, 10);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, tipo_moneda, subtotal, impuesto, total, condicion_pago, tiempo_entrega, vigencia_oferta, estado, version, id_usuario_creador)
            VALUES (:cod, :cli_pago, :cli_fact, :cli_inf, 'contado', 'NIO', 500, 75, 575, 'Contado', '3 días', '15 días', 'Borrador', 0, :usr)");
        $stmtIns->execute(['cod' => $codigoTest, 'cli_pago' => $idCliente, 'cli_fact' => $idCliente, 'cli_inf' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id, 'Item inicial', 1, 500, 500)");
        $stmtDet->execute(['id' => $idCot]);

        try {
            $modelo = new CotizacionModelo();

            $cabecera = [
                'id_cliente' => $idCliente,
                'id_cliente_factura' => $idCliente,
                'id_cliente_informe' => $idCliente,
                'condicion_comercial' => 'contado',
                'tipo_moneda' => 1,
                'atencion_a' => 'Cliente Borrador',
                'nombre_proyecto' => 'Proyecto Borrador Editado',
                'direccion_proyecto' => 'Managua',
                'condicion_pago' => 'Contado',
                'tiempo_entrega' => '5 días',
                'vigencia_oferta' => '20 días',
                'configuracion_notas' => null,
                'contactos' => null,
                'incluir_anexo_tecnico' => 0,
                'anexo_tecnico' => null,
                'archivo_adjunto' => null,
                'subtotal' => 800.0,
                'descuento' => 0.0,
                'exonerado' => 0,
                'exoneracion_no' => null,
                'impuesto' => 120.0,
                'total' => 920.0,
                'fecha_entrega' => null,
                'fecha_seguimiento' => null
            ];
            $detalles = [
                [
                    'id_producto' => 1,
                    'descripcion' => 'Item Modificado en Borrador',
                    'cantidad' => 2,
                    'precio_unitario' => 400.0,
                    'subtotal' => 800.0
                ]
            ];

            $res = $modelo->actualizarCotizacionCompleta($idCot, $cabecera, $detalles);
            $this->assertTrue($res, 'actualizarCotizacionCompleta debe retornar true para Borrador');

            $cotActual = $modelo->obtenerPorId($idCot);
            $this->assertSame('Borrador', $cotActual['estado'], 'El estado debe seguir siendo Borrador');
            $this->assertEquals(0, (int)$cotActual['version'], 'La versión no debe incrementarse');
            $this->assertEquals(920.0, (float)$cotActual['total']);

            // Verificar que no se insertó ninguna fila en cotizacion_versiones
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM cotizacion_versiones WHERE id_cotizacion = :id");
            $stmtCount->execute(['id' => $idCot]);
            $this->assertEquals(0, (int)$stmtCount->fetchColumn(), 'No deben existir versiones archivadas para una edición de borrador');

        } finally {
            $db->prepare("DELETE FROM cotizacion_versiones WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
        }
    }

    public function testEnviarRevisionTransicionaBorradorAEnRevisionConIdDecodificado(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }
        [$idCliente, $idUsuario] = $this->crearDatosBase($db);

        $codigoTest = 'CB-REV-' . substr(bin2hex(random_bytes(6)), 0, 8);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, condicion_comercial, tipo_moneda, subtotal, impuesto, total, estado, version, id_usuario_creador)
            VALUES (:cod, :cli, 'contado', 'NIO', 100, 15, 115, 'Borrador', 0, :usr)");
        $stmtIns->execute(['cod' => $codigoTest, 'cli' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        try {
            $_SESSION['usuario_id'] = $idUsuario;
            $_SESSION['usuario_rol'] = 1;
            $_SESSION['csrf_token'] = 'csrf-rev-test-token';
            $_SERVER['REQUEST_METHOD'] = 'POST';

            // Enviamos el id codificado tal como lo envía detalle.php
            $_POST['id'] = codificarId($idCot);
            $_POST['csrf_token'] = 'csrf-rev-test-token';

            $controlador = new CotizacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->enviarRevision($peticion, $respuesta);

            $modelo = new CotizacionModelo();
            $cot = $modelo->obtenerPorId($idCot);
            $this->assertSame('En Revision', $cot['estado'], 'enviarRevision debe transicionar la cotización a En Revision');
            $this->assertNotEmpty($_SESSION['envio_exitoso'] ?? null, 'Debe establecer mensaje de éxito en sesión');

        } finally {
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
            unset($_POST['id']);
            unset($_POST['csrf_token']);
            unset($_SESSION['csrf_token']);
            unset($_SESSION['envio_exitoso']);
        }
    }

    public function testActualizarObservadaSigueVersionando(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }
        [$idCliente, $idUsuario] = $this->crearDatosBase($db);

        $codigoTest = 'CB-OBS-' . substr(bin2hex(random_bytes(6)), 0, 8);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, condicion_comercial, tipo_moneda, subtotal, impuesto, total, estado, version, motivo_observacion, id_usuario_creador)
            VALUES (:cod, :cli, 'contado', 'NIO', 300, 45, 345, 'Observada', 1, 'Ajustar términos de pago', :usr)");
        $stmtIns->execute(['cod' => $codigoTest, 'cli' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id, 'Ensayo Obs', 1, 300, 300)");
        $stmtDet->execute(['id' => $idCot]);

        try {
            $modelo = new CotizacionModelo();
            $cabecera = [
                'id_cliente' => $idCliente,
                'id_cliente_factura' => $idCliente,
                'id_cliente_informe' => $idCliente,
                'condicion_comercial' => 'contado',
                'tipo_moneda' => 1,
                'atencion_a' => 'Cliente Obs',
                'nombre_proyecto' => 'Proyecto Observado',
                'direccion_proyecto' => 'Managua',
                'condicion_pago' => 'Contado',
                'tiempo_entrega' => '3 días',
                'vigencia_oferta' => '15 días',
                'configuracion_notas' => null,
                'contactos' => null,
                'incluir_anexo_tecnico' => 0,
                'anexo_tecnico' => null,
                'archivo_adjunto' => null,
                'subtotal' => 300.0,
                'descuento' => 0.0,
                'exonerado' => 0,
                'exoneracion_no' => null,
                'impuesto' => 45.0,
                'total' => 345.0,
                'fecha_entrega' => null,
                'fecha_seguimiento' => null
            ];
            $detalles = [
                [
                    'id_producto' => 1,
                    'descripcion' => 'Ensayo Obs Corregido',
                    'cantidad' => 1,
                    'precio_unitario' => 300.0,
                    'subtotal' => 300.0
                ]
            ];

            $res = $modelo->actualizarCotizacionCompleta($idCot, $cabecera, $detalles);
            $this->assertTrue($res);

            $cot = $modelo->obtenerPorId($idCot);
            $this->assertSame('En Revision', $cot['estado'], 'Una cotización Observada debe pasar a En Revision');
            $this->assertEquals(2, (int)$cot['version'], 'La versión debe incrementarse de 1 a 2');
            $this->assertNull($cot['motivo_observacion'], 'El motivo de observación debe limpiarse');

            $stmtVer = $db->prepare("SELECT * FROM cotizacion_versiones WHERE id_cotizacion = :id AND version = 1");
            $stmtVer->execute(['id' => $idCot]);
            $this->assertNotEmpty($stmtVer->fetch(PDO::FETCH_ASSOC), 'Debe registrar la versión histórica 1');

        } finally {
            $db->prepare("DELETE FROM cotizacion_versiones WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
        }
    }

    public function testEstadoNoEditableRechazaActualizacionSinMutar(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }
        [$idCliente, $idUsuario] = $this->crearDatosBase($db);

        $codigoTest = 'CB-LOCK-' . substr(bin2hex(random_bytes(6)), 0, 8);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, condicion_comercial, tipo_moneda, subtotal, impuesto, total, estado, version, id_usuario_creador)
            VALUES (:cod, :cli, 'contado', 'NIO', 1000, 150, 1150, 'Aprobada por Cliente', 1, :usr)");
        $stmtIns->execute(['cod' => $codigoTest, 'cli' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        try {
            $modelo = new CotizacionModelo();
            $cabecera = [
                'id_cliente' => $idCliente,
                'subtotal' => 2000.0,
                'impuesto' => 300.0,
                'total' => 2300.0
            ];
            $detalles = [];

            // El modelo debe rechazar
            $res = $modelo->actualizarCotizacionCompleta($idCot, $cabecera, $detalles);
            $this->assertFalse($res, 'No debe permitir actualizar una cotización Aprobada por Cliente');

            $cot = $modelo->obtenerPorId($idCot);
            $this->assertSame('Aprobada por Cliente', $cot['estado'], 'El estado debe quedar inmutable');
            $this->assertEquals(1150.0, (float)$cot['total'], 'El total no debe alterarse');

            // El controlador también debe rechazar
            $_SESSION['usuario_id'] = $idUsuario;
            $_SESSION['usuario_rol'] = 1;
            $_SESSION['csrf_token'] = 'csrf-lock-token';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST['id'] = codificarId($idCot);
            $_POST['csrf_token'] = 'csrf-lock-token';
            unset($_SESSION['error']);

            $controlador = new CotizacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->actualizar($peticion, $respuesta);

            $this->assertNotEmpty($_SESSION['error'] ?? null);
            $this->assertStringContainsString('No se puede editar una cotización en estado Aprobada por Cliente', $_SESSION['error']);

        } finally {
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
            unset($_POST['id']);
            unset($_POST['csrf_token']);
            unset($_SESSION['csrf_token']);
            unset($_SESSION['error']);
        }
    }

    public function testGateEditarBorradorValidaPermisos(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }
        [$idCliente, $idUsuario] = $this->crearDatosBase($db);

        $codigoTest = 'CB-GATE-' . substr(bin2hex(random_bytes(6)), 0, 8);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, condicion_comercial, tipo_moneda, subtotal, impuesto, total, estado, version, id_usuario_creador)
            VALUES (:cod, :cli, 'contado', 'NIO', 100, 15, 115, 'Borrador', 0, :usr)");
        $stmtIns->execute(['cod' => $codigoTest, 'cli' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        try {
            $controlador = new CotizacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            // Caso 1: Usuario con permiso del módulo pero no creador y rol no admin (ej. rol 2, id 999999)
            $_SESSION['usuario_id'] = 999999;
            $_SESSION['usuario_rol'] = 2;
            $_SESSION['usuario_permisos'] = ['cotizaciones' => ['crear_editar' => 1, 'ver' => 1]];
            $_GET['id'] = codificarId($idCot);
            unset($_SESSION['error']);

            $controlador->editar($peticion, $respuesta);

            $this->assertNotEmpty($_SESSION['error'] ?? null);
            $this->assertStringContainsString('No tienes permiso para editar este borrador', $_SESSION['error']);

            // Caso 2: Cotización en estado no editable (ej. Enviada al Cliente)
            $db->prepare("UPDATE cotizaciones SET estado = 'Enviada al Cliente' WHERE id = :id")->execute(['id' => $idCot]);
            $_SESSION['usuario_id'] = $idUsuario;
            $_SESSION['usuario_rol'] = 1;
            unset($_SESSION['error']);

            // Al intentar editar, debe redirigir al detalle sin permitir abrir editor
            $controlador->editar($peticion, $respuesta);

        } finally {
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
            unset($_GET['id']);
            unset($_SESSION['error']);
        }
    }
}
