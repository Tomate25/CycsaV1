<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;

class GatePagoResultadosTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/operaciones';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/Cycsa/publico/index.php';
    }

    private function crearPdoSQLite(): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("
            CREATE TABLE cuentas_por_cobrar (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                factura_numero VARCHAR(100),
                estado VARCHAR(50),
                saldo DECIMAL(14,2),
                monto DECIMAL(14,2) DEFAULT 0
            )
        ");
        return $pdo;
    }

    /**
     * Helper: Verificar autorización en condición Crédito (autoriza despacho automáticamente)
     */
    public function testHelperVerificarAutorizacionPagoEnvioCreditoAutorizaDespacho(): void {
        $db = $this->crearPdoSQLite();
        // Insertar CxC con saldo pendiente grande
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('FAC-COT-CRED-01', 'Pendiente', 50000.00)");

        $variantesCredito = [
            'credito',
            'Crédito',
            'CRÉDITO',
            'credito 30 dias',
            'Crédito 15 días',
            'credito_comercial'
        ];

        foreach ($variantesCredito as $cond) {
            $datos = [
                'condicion_pago' => $cond,
                'cot_codigo' => 'COT-CRED-01'
            ];
            $res = verificarAutorizacionPagoEnvio($datos, $db);

            $this->assertTrue($res['autorizado'], "Condición {$cond} debe estar autorizada para envío/despacho");
            $this->assertSame('Crédito autoriza despacho', $res['motivo'] ?? null);
        }

        // Probar también vía condicion_comercial y forma_pago
        $resCom = verificarAutorizacionPagoEnvio(['condicion_comercial' => 'crédito', 'cot_codigo' => 'COT-CRED-01'], $db);
        $this->assertTrue($resCom['autorizado']);

        $resForma = verificarAutorizacionPagoEnvio(['forma_pago' => 'credito', 'cot_codigo' => 'COT-CRED-01'], $db);
        $this->assertTrue($resForma['autorizado']);
    }

    /**
     * Helper: Condición de pago inmediato / Contado con saldo pendiente deniega despacho
     */
    public function testHelperVerificarAutorizacionPagoEnvioContadoConSaldoPendienteBloquea(): void {
        $db = $this->crearPdoSQLite();
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('FAC-COT-CONT-01', 'Pendiente', 2500.50)");
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('COT-CONT-02', 'Parcial', 800.00)");
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('FAC-COT-CONT-03', 'Pagado', 15.00)"); // Pagado pero con saldo > 0.01

        // Caso A: Coincidencia por 'FAC-' . $codigo con saldo pendiente
        $resA = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'Contado',
            'cot_codigo' => 'COT-CONT-01'
        ], $db);
        $this->assertFalse($resA['autorizado']);
        $this->assertSame('El cliente no ha pagado; no se pueden enviar resultados', $resA['mensaje']);
        $this->assertEquals(2500.50, $resA['saldo']);

        // Caso B: Coincidencia directa por $codigo sin prefijo FAC-
        $resB = verificarAutorizacionPagoEnvio([
            'condicion_comercial' => 'contra_entrega',
            'cot_codigo' => 'COT-CONT-02'
        ], $db);
        $this->assertFalse($resB['autorizado']);
        $this->assertSame('El cliente no ha pagado; no se pueden enviar resultados', $resB['mensaje']);
        $this->assertEquals(800.00, $resB['saldo']);

        // Caso C: Estado Pagado pero saldo > 0.01
        $resC = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'pago contra entrega',
            'codigo' => 'COT-CONT-03'
        ], $db);
        $this->assertFalse($resC['autorizado']);
        $this->assertSame('El cliente no ha pagado; no se pueden enviar resultados', $resC['mensaje']);

        // Caso D: Condición 'inmediato' con saldo pendiente
        $resD = verificarAutorizacionPagoEnvio([
            'forma_pago' => 'inmediato',
            'cot_codigo' => 'COT-CONT-01'
        ], $db);
        $this->assertFalse($resD['autorizado']);
    }

    /**
     * Helper: Contado con saldo 0.00 / Pagado autoriza despacho
     */
    public function testHelperVerificarAutorizacionPagoEnvioContadoPagadoAutoriza(): void {
        $db = $this->crearPdoSQLite();
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('FAC-COT-PAG-01', 'Pagado', 0.00)");
        $db->exec("INSERT INTO cuentas_por_cobrar (factura_numero, estado, saldo) VALUES ('FAC-COT-PAG-02', 'Pagado', 0.005)"); // <= 0.01 redondeo centavos

        $res1 = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'Contado',
            'cot_codigo' => 'COT-PAG-01'
        ], $db);
        $this->assertTrue($res1['autorizado'], 'Contado con saldo 0 debe autorizar');

        $res2 = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'contado',
            'cot_codigo' => 'COT-PAG-02'
        ], $db);
        $this->assertTrue($res2['autorizado'], 'Contado con saldo residual despreciable <= 0.01 debe autorizar');
    }

    /**
     * Helper: Contado sin registro de CxC o sin código autoriza
     */
    public function testHelperVerificarAutorizacionPagoEnvioSinCxCAutoriza(): void {
        $db = $this->crearPdoSQLite();

        $resSinCxC = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'Contado',
            'cot_codigo' => 'COT-NO-EXISTE'
        ], $db);
        $this->assertTrue($resSinCxC['autorizado']);

        $resVacio = verificarAutorizacionPagoEnvio([
            'condicion_pago' => 'Contado'
        ], $db);
        $this->assertTrue($resVacio['autorizado']);
    }

    /**
     * Flujo Operaciones: Acceso a capturaMatrizProducto y guardado de matriz técnica
     * con saldo pendiente en CxC -> PERMITIDO (no se bloquea el trabajo técnico interno).
     */
    public function testAccesoCapturaYGuardadoMatrizPermitidoConSaldoPendiente(): void {
        $db = Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            // Crear cliente, cotización Contado con saldo pendiente, O/S y detalle
            $stmtCli = $db->prepare("INSERT INTO clientes (nombre_razon_social, email, telefono, direccion) VALUES ('Cliente Técnico Test', 'tec@example.com', '88888888', 'Managua')");
            $stmtCli->execute();
            $idCli = (int)$db->lastInsertId();

            $codigoCot = 'COT-TECH-TEST-01';
            $stmtCot = $db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, condicion_comercial, total, estado) VALUES (:cod, :cli, 1, 'Ingeniero', 'Proyecto Lab', 'Obra', 'Contado', 'contado', 3500.00, 'Aprobada')");
            $stmtCot->execute(['cod' => $codigoCot, 'cli' => $idCli]);
            $idCot = (int)$db->lastInsertId();

            $stmtOS = $db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado) VALUES ('OS-TECH-TEST-01', :cot, '2026-11-02', 'En Proceso')");
            $stmtOS->execute(['cot' => $idCot]);
            $idOS = (int)$db->lastInsertId();

            $stmtHS = $db->prepare("INSERT INTO hojas_solicitud (id_os) VALUES (:os)");
            $stmtHS->execute(['os' => $idOS]);

            $stmtRM = $db->prepare("INSERT INTO recepcion_muestras (id_os, codigo_muestra, fecha_recepcion, recibido_por) VALUES (:os, 'MS-TECH-01-26', '2026-11-02', 1)");
            $stmtRM->execute(['os' => $idOS]);

            $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad) VALUES (:cot, 'Ensayo Compresion Cilindros Concreto', 1)");
            $stmtDet->execute(['cot' => $idCot]);
            $idDetalle = (int)$db->lastInsertId();

            // Saldo pendiente en CxC
            $stmtCxC = $db->prepare("INSERT INTO cuentas_por_cobrar (factura_numero, monto, saldo, estado, fecha_emision, id_cliente) VALUES (:fn, 3500.00, 3500.00, 'Pendiente', '2026-11-02', :cli)");
            $stmtCxC->execute(['fn' => 'FAC-' . $codigoCot, 'cli' => $idCli]);

            $_SESSION['usuario_id'] = 1;
            $_SESSION['rol_id'] = 1;
            $_SESSION['usuario_rol'] = 1;

            $controlador = new OperacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            // 1. Acceso a capturaMatrizProducto
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['id_detalle'] = $idDetalle;
            unset($_SESSION['error']);

            ob_start();
            $controlador->capturaMatrizProducto($peticion, $respuesta);
            $output = ob_get_clean();

            $this->assertNull($_SESSION['error'] ?? null, 'capturaMatrizProducto NO debe bloquear el acceso técnico por saldo pendiente');
            $this->assertGreaterThan(0, strlen($output), 'capturaMatrizProducto debe renderizar la vista de captura técnica');

            // 2. Guardado de matriz técnica (guardarMatrizProductoPOST)
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'id_detalle' => $idDetalle,
                'resultados_json' => json_encode([
                    'filas' => [
                        ['Código laboratorio' => 'MS-TECH-01-26', 'Carga (lb)' => '65000', 'R. Compresión (kg/cm²)' => '320']
                    ]
                ])
            ];
            $peticionPost = new Peticion();
            unset($_SESSION['error']);
            unset($_SESSION['exito']);

            $controlador->guardarMatrizProductoPOST($peticionPost, $respuesta);

            $this->assertNull($_SESSION['error'] ?? null, 'guardarMatrizProductoPOST NO debe bloquear el guardado de datos técnicos por saldo pendiente');
            $this->assertNotNull($_SESSION['exito'] ?? null, 'guardarMatrizProductoPOST debe registrar éxito');

            // Verificar persistencia de resultados en BD
            $stmtCheck = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id");
            $stmtCheck->execute(['id' => $idDetalle]);
            $savedJson = $stmtCheck->fetchColumn();
            $this->assertStringContainsString('65000', $savedJson, 'Los resultados técnicos deben guardarse en la base de datos');

        } finally {
            $db->rollBack();
        }
    }

    /**
     * Flujo Operaciones: Intento de enviarMatrizCliente en cliente Contado con saldo pendiente
     * -> DENEGADO con el mensaje exacto «El cliente no ha pagado; no se pueden enviar resultados».
     */
    public function testEnviarMatrizClienteContadoConSaldoPendienteDenegado(): void {
        $db = Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            $stmtCli = $db->prepare("INSERT INTO clientes (nombre_razon_social, email, telefono, direccion) VALUES ('Cliente Contado Pendiente', 'contado@example.com', '77777777', 'Leon')");
            $stmtCli->execute();
            $idCli = (int)$db->lastInsertId();

            $codigoCot = 'COT-CONT-PEND-01';
            $stmtCot = $db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, total, estado) VALUES (:cod, :cli, 1, 'Encargado', 'Obra Leon', 'Leon', 'Contado', 2000.00, 'Aprobada')");
            $stmtCot->execute(['cod' => $codigoCot, 'cli' => $idCli]);
            $idCot = (int)$db->lastInsertId();

            $stmtOS = $db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado) VALUES ('OS-CONT-PEND-01', :cot, '2026-11-02', 'En Proceso')");
            $stmtOS->execute(['cot' => $idCot]);
            $idOS = (int)$db->lastInsertId();

            $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad) VALUES (:cot, 'Ensayo Humedad Suelo', 1)");
            $stmtDet->execute(['cot' => $idCot]);
            $idDetalle = (int)$db->lastInsertId();

            // Saldo pendiente en cuentas_por_cobrar
            $stmtCxC = $db->prepare("INSERT INTO cuentas_por_cobrar (factura_numero, monto, saldo, estado, fecha_emision, id_cliente) VALUES (:fn, 2000.00, 2000.00, 'Pendiente', '2026-11-02', :cli)");
            $stmtCxC->execute(['fn' => 'FAC-' . $codigoCot, 'cli' => $idCli]);

            $_SESSION['usuario_id'] = 1;
            $_SESSION['rol_id'] = 1;
            $_SESSION['usuario_rol'] = 1;

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['id_detalle'] = $idDetalle;
            unset($_SESSION['error']);

            $controlador = new OperacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->enviarMatrizCliente($peticion, $respuesta);

            $this->assertSame(
                'El cliente no ha pagado; no se pueden enviar resultados',
                $_SESSION['error'] ?? null,
                'El gate debe rechazar con el mensaje exacto exigido por negocio'
            );

        } finally {
            $db->rollBack();
        }
    }

    /**
     * Flujo Operaciones: Intento de enviarMatrizCliente en cliente Contado con saldo 0 / Pagado
     * -> PERMITIDO por el gate de pago (no se bloquea por pago).
     */
    public function testEnviarMatrizClienteContadoPagadoPermitido(): void {
        $db = Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            $stmtCli = $db->prepare("INSERT INTO clientes (nombre_razon_social, email, telefono, direccion) VALUES ('Cliente Contado Pagado', 'pagado@example.com', '66666666', 'Granada')");
            $stmtCli->execute();
            $idCli = (int)$db->lastInsertId();

            $codigoCot = 'COT-CONT-PAG-01';
            $stmtCot = $db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, total, estado) VALUES (:cod, :cli, 1, 'Admin', 'Obra Granada', 'Granada', 'Contado', 1800.00, 'Aprobada')");
            $stmtCot->execute(['cod' => $codigoCot, 'cli' => $idCli]);
            $idCot = (int)$db->lastInsertId();

            $stmtOS = $db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado) VALUES ('OS-CONT-PAG-01', :cot, '2026-11-02', 'En Proceso')");
            $stmtOS->execute(['cot' => $idCot]);
            $idOS = (int)$db->lastInsertId();

            $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad) VALUES (:cot, 'Ensayo Granulometria', 1)");
            $stmtDet->execute(['cot' => $idCot]);
            $idDetalle = (int)$db->lastInsertId();

            // Factura 100% pagada con saldo 0
            $stmtCxC = $db->prepare("INSERT INTO cuentas_por_cobrar (factura_numero, monto, saldo, estado, fecha_emision, id_cliente) VALUES (:fn, 1800.00, 0.00, 'Pagado', '2026-11-02', :cli)");
            $stmtCxC->execute(['fn' => 'FAC-' . $codigoCot, 'cli' => $idCli]);

            $_SESSION['usuario_id'] = 1;
            $_SESSION['rol_id'] = 1;
            $_SESSION['usuario_rol'] = 1;

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['id_detalle'] = $idDetalle;
            unset($_SESSION['error']);

            $controlador = new OperacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->enviarMatrizCliente($peticion, $respuesta);

            // El gate de pago NO debe haber bloqueado; pasa al siguiente control (resultados de matriz)
            $this->assertNotSame(
                'El cliente no ha pagado; no se pueden enviar resultados',
                $_SESSION['error'] ?? null,
                'Cliente con saldo 0 no debe ser bloqueado por gate de pago'
            );

        } finally {
            $db->rollBack();
        }
    }

    /**
     * Flujo Operaciones: Envío en cliente Crédito con saldo pendiente
     * -> PERMITIDO (crédito autoriza despacho con la cuenta por cobrar).
     */
    public function testEnviarMatrizClienteCreditoConSaldoPendientePermitido(): void {
        $db = Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            $stmtCli = $db->prepare("INSERT INTO clientes (nombre_razon_social, email, telefono, direccion) VALUES ('Cliente Corporativo Crédito', 'credito@empresa.com', '55555555', 'Managua')");
            $stmtCli->execute();
            $idCli = (int)$db->lastInsertId();

            $codigoCot = 'COT-CRED-DISP-01';
            $stmtCot = $db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, condicion_comercial, total, estado) VALUES (:cod, :cli, 1, 'Gerente', 'Proyecto Vial', 'Carretera Masaya', 'Crédito', 'credito', 15000.00, 'Aprobada')");
            $stmtCot->execute(['cod' => $codigoCot, 'cli' => $idCli]);
            $idCot = (int)$db->lastInsertId();

            $stmtOS = $db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado, condicion_comercial) VALUES ('OS-CRED-DISP-01', :cot, '2026-11-02', 'En Proceso', 'credito')");
            $stmtOS->execute(['cot' => $idCot]);
            $idOS = (int)$db->lastInsertId();

            $stmtDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad) VALUES (:cot, 'Densidad In Situ Nuclear', 1)");
            $stmtDet->execute(['cot' => $idCot]);
            $idDetalle = (int)$db->lastInsertId();

            // Saldo abierto en CxC (condición crédito habitual)
            $stmtCxC = $db->prepare("INSERT INTO cuentas_por_cobrar (factura_numero, monto, saldo, estado, fecha_emision, id_cliente) VALUES (:fn, 15000.00, 15000.00, 'Pendiente', '2026-11-02', :cli)");
            $stmtCxC->execute(['fn' => 'FAC-' . $codigoCot, 'cli' => $idCli]);

            $_SESSION['usuario_id'] = 1;
            $_SESSION['rol_id'] = 1;
            $_SESSION['usuario_rol'] = 1;

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['id_detalle'] = $idDetalle;
            unset($_SESSION['error']);

            $controlador = new OperacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            $controlador->enviarMatrizCliente($peticion, $respuesta);

            // El gate de pago NO bloquea a clientes de crédito
            $this->assertNotSame(
                'El cliente no ha pagado; no se pueden enviar resultados',
                $_SESSION['error'] ?? null,
                'Cliente a crédito con saldo pendiente debe tener despacho autorizado'
            );

        } finally {
            $db->rollBack();
        }
    }

    /**
     * Flujo Operaciones: descargarInforme unificado con gate de pago
     */
    public function testDescargarInformeGatePagoUnificado(): void {
        $db = Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            $stmtCli = $db->prepare("INSERT INTO clientes (nombre_razon_social, email, telefono, direccion) VALUES ('Cliente Informe Test', 'inf@example.com', '44444444', 'Chinandega')");
            $stmtCli->execute();
            $idCli = (int)$db->lastInsertId();

            $codigoCot = 'COT-INF-GATE-01';
            $stmtCot = $db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, total, estado) VALUES (:cod, :cli, 1, 'Supervisor', 'Proyecto Chinandega', 'Chinandega', 'Contado', 4000.00, 'Aprobada')");
            $stmtCot->execute(['cod' => $codigoCot, 'cli' => $idCli]);
            $idCot = (int)$db->lastInsertId();

            $stmtOS = $db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado) VALUES ('OS-INF-GATE-01', :cot, '2026-11-02', 'En Proceso')");
            $stmtOS->execute(['cot' => $idCot]);
            $idOS = (int)$db->lastInsertId();

            $stmtRM = $db->prepare("INSERT INTO recepcion_muestras (id_os, codigo_muestra, fecha_recepcion, recibido_por) VALUES (:os, 'MS-INF-01-26', '2026-11-02', 1)");
            $stmtRM->execute(['os' => $idOS]);
            $idRM = (int)$db->lastInsertId();

            $stmtLM = $db->prepare("INSERT INTO lotes_muestras (id_recepcion, nombre_lote) VALUES (:rm, 'Lote Informe')");
            $stmtLM->execute(['rm' => $idRM]);
            $idLM = (int)$db->lastInsertId();

            // Crear un archivo PDF temporal dentro de almacenamiento/
            $almacenamientoDir = dirname(__DIR__, 2) . '/almacenamiento/informes';
            if (!is_dir($almacenamientoDir)) {
                mkdir($almacenamientoDir, 0777, true);
            }
            $archivoDummy = $almacenamientoDir . '/informe_test_gate.pdf';
            file_put_contents($archivoDummy, '%PDF-1.4 TEST GATE PAGO DUMMY');

            $stmtIC = $db->prepare("INSERT INTO informes_control (id_lote, codigo_informe, ruta_archivo_pdf) VALUES (:lm, 'INF-GATE-001', 'almacenamiento/informes/informe_test_gate.pdf')");
            $stmtIC->execute(['lm' => $idLM]);
            $idIC = (int)$db->lastInsertId();

            // Saldo pendiente en CxC
            $stmtCxC = $db->prepare("INSERT INTO cuentas_por_cobrar (factura_numero, monto, saldo, estado, fecha_emision, id_cliente) VALUES (:fn, 4000.00, 4000.00, 'Pendiente', '2026-11-02', :cli)");
            $stmtCxC->execute(['fn' => 'FAC-' . $codigoCot, 'cli' => $idCli]);

            $_SESSION['usuario_id'] = 1;
            $_SESSION['rol_id'] = 1;
            $_SESSION['usuario_rol'] = 1;

            $controlador = new OperacionesControlador();
            $peticion = new Peticion();
            $respuesta = new Respuesta();

            // 1. Contado con saldo pendiente -> Denegado con el mensaje exacto
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['id'] = $idIC;
            unset($_SESSION['error']);

            $controlador->descargarInforme($peticion, $respuesta);

            $this->assertSame(
                'El cliente no ha pagado; no se pueden enviar resultados',
                $_SESSION['error'] ?? null,
                'descargarInforme debe denegar la descarga si el cliente de Contado tiene saldo pendiente'
            );

            // 2. Si se actualiza a Pagado -> Gate de pago superado
            $db->prepare("UPDATE cuentas_por_cobrar SET estado = 'Pagado', saldo = 0.00 WHERE factura_numero = :fn")
               ->execute(['fn' => 'FAC-' . $codigoCot]);
            unset($_SESSION['error']);

            ob_start();
            $controlador->descargarInforme($peticion, $respuesta);
            $pdfContent = ob_get_clean();

            $this->assertNotSame(
                'El cliente no ha pagado; no se pueden enviar resultados',
                $_SESSION['error'] ?? null,
                'descargarInforme con saldo 0 no debe rechazar por falta de pago'
            );
            $this->assertStringContainsString('%PDF-1.4', $pdfContent, 'descargarInforme debe entregar el PDF cuando el pago está saldado');

            if (file_exists($archivoDummy)) {
                @unlink($archivoDummy);
            }

        } finally {
            $db->rollBack();
        }
    }
}
