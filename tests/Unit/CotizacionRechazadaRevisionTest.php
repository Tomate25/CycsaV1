<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;

class CotizacionRechazadaRevisionTest extends TestCase {

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

    public function testActualizarCotizacionRechazadaPasaAEnRevisionYNoAutoEnvia(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }

        $cli = $db->query("SELECT id FROM clientes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $usr = $db->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios en la base de datos para ejecutar la prueba.');
        }
        $idCliente = (int)$cli['id'];
        $idUsuario = (int)$usr['id'];

        $modelo = new CotizacionModelo();

        // Código corto < 20 chars
        $codigoTest = 'CR-' . substr(bin2hex(random_bytes(6)), 0, 12);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, tipo_moneda, subtotal, impuesto, total, condicion_pago, tiempo_entrega, vigencia_oferta, estado, version, motivo_rechazo_cliente, token_seguridad, id_usuario_creador)
            VALUES (:cod, :cli_pago, :cli_fact, :cli_inf, 'contado', 'NIO', 1000, 150, 1150, 'Contado', '3 días', '15 días', 'Rechazada por Cliente', 1, 'Precio muy elevado según cliente', 'token-test-123', :usr)");
        $stmtIns->execute([
            'cod' => $codigoTest,
            'cli_pago' => $idCliente,
            'cli_fact' => $idCliente,
            'cli_inf' => $idCliente,
            'usr' => $idUsuario
        ]);
        $idCot = (int)$db->lastInsertId();

        // Agregar un detalle
        $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles 
            (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal)
            VALUES (:id, 'Ensayo de Prueba Rechazo', 1, 1000, 1000)");
        $stmtDet->execute(['id' => $idCot]);

        try {
            // Simular actualización/corrección de la cotización rechazada
            $cabecera = [
                'id_cliente' => $idCliente,
                'id_cliente_factura' => $idCliente,
                'id_cliente_informe' => $idCliente,
                'condicion_comercial' => 'contado',
                'tipo_moneda' => 'NIO',
                'atencion_a' => 'Ing. Revisor',
                'nombre_proyecto' => 'Proyecto Test Rechazo',
                'direccion_proyecto' => 'Managua',
                'condicion_pago' => 'Contado',
                'tiempo_entrega' => '3 días',
                'vigencia_oferta' => '15 días',
                'configuracion_notas' => null,
                'contactos' => null,
                'incluir_anexo_tecnico' => 0,
                'anexo_tecnico' => null,
                'archivo_adjunto' => null,
                'subtotal' => 900,
                'descuento' => 100,
                'exonerado' => 0,
                'exoneracion_no' => null,
                'impuesto' => 135,
                'total' => 1035,
                'fecha_entrega' => null,
                'fecha_seguimiento' => null
            ];
            $detalles = [
                [
                    'id_producto' => 1,
                    'descripcion' => 'Ensayo con descuento ajustado',
                    'cantidad' => 1,
                    'precio' => 900,
                    'subtotal' => 900
                ]
            ];

            $ok = $modelo->actualizarCotizacionCompleta($idCot, $cabecera, $detalles);
            $this->assertTrue($ok, 'La actualización de la cotización debe ser exitosa');

            // Verificar que el estado resultante sea 'En Revision' y NO 'Enviada al Cliente'
            $cotActualizada = $modelo->obtenerPorId($idCot);
            $this->assertSame('En Revision', $cotActualizada['estado'], 'Una cotización rechazada debe pasar a En Revision');
            $this->assertEquals(2, (int)$cotActualizada['version'], 'La versión debe incrementarse a 2');
            $this->assertNull($cotActualizada['motivo_rechazo_cliente'], 'El motivo de rechazo debe limpiarse de la versión activa');

            // Verificar que se archivó la versión 1 en cotizacion_versiones
            $stmtVer = $db->prepare("SELECT * FROM cotizacion_versiones WHERE id_cotizacion = :id AND version = 1");
            $stmtVer->execute(['id' => $idCot]);
            $versionArchivada = $stmtVer->fetch(PDO::FETCH_ASSOC);
            $this->assertNotEmpty($versionArchivada, 'Debe existir la versión 1 archivada en cotizacion_versiones');
            $this->assertStringContainsString('Devuelta por cliente', $versionArchivada['motivo_cambio']);

        } finally {
            $db->prepare("DELETE FROM cotizacion_versiones WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $idCot]);
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
        }
    }

    public function testBloqueoEnvioDirectoAClienteSiEstaRechazada(): void {
        $db = $this->getDb();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }

        $cli = $db->query("SELECT id FROM clientes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $usr = $db->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios en la base de datos.');
        }
        $idCliente = (int)$cli['id'];
        $idUsuario = (int)$usr['id'];

        $codBloqueo = 'CB-' . substr(bin2hex(random_bytes(6)), 0, 12);
        $stmtIns = $db->prepare("INSERT INTO cotizaciones 
            (codigo, id_cliente, condicion_comercial, tipo_moneda, subtotal, impuesto, total, estado, version, id_usuario_creador)
            VALUES (:cod, :cli, 'contado', 'NIO', 1000, 150, 1150, 'Rechazada por Cliente', 1, :usr)");
        $stmtIns->execute(['cod' => $codBloqueo, 'cli' => $idCliente, 'usr' => $idUsuario]);
        $idCot = (int)$db->lastInsertId();

        try {
            $_SESSION['usuario_id'] = 1;
            $_SESSION['usuario_rol'] = 1;
            $_SESSION['csrf_token'] = 'token-test-csrf-1234';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST['id'] = $idCot;
            $_POST['csrf_token'] = 'token-test-csrf-1234';
            $_GET['id'] = $idCot;
            unset($_SESSION['error']);

            $controlador = new CotizacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->enviarCliente($peticion, $respuesta);

            // Debe haber puesto un error de bloqueo en la sesión
            $this->assertNotEmpty($_SESSION['error'] ?? null);
            $this->assertStringContainsString('rechazada por el cliente. Debe editarse y pasar por aprobacion interna', $_SESSION['error']);

            // El estado debe seguir siendo 'Rechazada por Cliente'
            $stmtCheck = $db->prepare("SELECT estado FROM cotizaciones WHERE id = :id");
            $stmtCheck->execute(['id' => $idCot]);
            $estadoActual = $stmtCheck->fetchColumn();
            $this->assertSame('Rechazada por Cliente', $estadoActual);

        } finally {
            $db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idCot]);
            unset($_GET['id']);
            unset($_POST['id']);
            unset($_POST['csrf_token']);
        }
    }
}
