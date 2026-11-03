<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

class OrdenServicioEdicionYGateTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;
    private int $idOSPrueba = 0;
    private int $idCotizacionPrueba = 0;
    private int $idClientePrueba = 0;
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
        $_SESSION['usuario_nombre'] = 'Test Runner';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Backup de secuencias_muestras para no contaminar otros tests
        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        // 1. Asegurar o crear cliente de prueba
        $stmtCli = $this->db->query("SELECT id FROM clientes LIMIT 1");
        $idCli = (int)$stmtCli->fetchColumn();
        if ($idCli <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Unit Test', 'J0310000000000', 'test@test.com', '22223333')");
            $idCli = (int)$this->db->lastInsertId();
        }
        $this->idClientePrueba = $idCli;

        // 2. Asegurar o crear cotización de prueba
        $stmtCot = $this->db->query("SELECT id FROM cotizaciones LIMIT 1");
        $idCot = (int)$stmtCot->fetchColumn();
        if ($idCot <= 0) {
            $this->db->exec("INSERT INTO cotizaciones (codigo, id_cliente, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES ('COT-TEST-EDICION', {$idCli}, 'Atn Test', 'Proyecto Test', 'Pago contra entrega', 'Aprobada por Cliente', 1000, 1150)");
            $idCot = (int)$this->db->lastInsertId();
        }
        $this->idCotizacionPrueba = $idCot;

        // 3. Crear una Orden de Servicio aislada para este test
        $codigoTest = 'TEST-OS-' . uniqid();
        $this->idOSPrueba = $this->osModelo->crear([
            'codigo_os' => $codigoTest,
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePrueba,
            'id_cliente_factura' => $this->idClientePrueba,
            'id_cliente_informe' => $this->idClientePrueba,
            'atencion_a' => 'Contacto Inicial',
            'nombre_proyecto' => 'Proyecto Inicial Base',
            'forma_pago' => 'Pago contra entrega',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'es_multimes' => 0,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'periodicidad' => 'Mensual',
            'estado' => 'Estado 1: Recepcion'
        ]);

        $this->assertGreaterThan(0, $this->idOSPrueba, 'La O/S base de prueba debe crearse con ID válido.');
    }

    protected function tearDown(): void {
        // Restaurar secuencias_muestras
        if (!empty($this->secuenciasBackup)) {
            try {
                $this->db->exec("DELETE FROM secuencias_muestras");
                $stmtInsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr)");
                foreach ($this->secuenciasBackup as $row) {
                    $stmtInsert->execute(['anio' => $row['anio'], 'tipo' => $row['tipo_muestra'], 'corr' => $row['ultimo_correlativo']]);
                }
            } catch (\Throwable $e) {}
        }

        // Limpiar hojas creadas
        if (!empty($this->hojasCreadas)) {
            $inHojas = implode(',', array_map('intval', $this->hojasCreadas));
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inHojas})");
            } catch (\Throwable $e) {}
            try {
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inHojas})");
            } catch (\Throwable $e) {}
        }

        // Limpiar O/S de prueba
        if ($this->idOSPrueba > 0) {
            try {
                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_orden_servicio = {$this->idOSPrueba}");
            } catch (\Throwable $e) {}
            try {
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOSPrueba}");
            } catch (\Throwable $e) {}
        }

        parent::tearDown();
    }

    /**
     * 1. Edición permitida en estados abiertos (ej. Estado 1: Recepción o Pendiente de Muestreo)
     */
    public function testEdicionPermitidaEnEstadosAbiertos(): void {
        $idOS = $this->idOSPrueba;

        $datosActualizar = [
            'atencion_a' => 'Ing. Juan Pérez Editado',
            'nombre_proyecto' => 'Construcción Puente Norte (Actualizado)',
            'forma_pago' => 'Pago a 30 días',
            'condicion_comercial' => 'credito',
            'id_cliente_factura' => $this->idClientePrueba,
            'id_cliente_informe' => $this->idClientePrueba,
            'notas_condiciones' => 'Entregar reporte impreso y digital con sellos',
            'contactos_json' => ['telefono' => '88889999', 'cargo' => 'Residente'],
            'tipo_contrato' => 'Marco'
        ];

        $actualizado = $this->osModelo->actualizar($idOS, $datosActualizar);
        $this->assertTrue($actualizado, 'La actualización de la O/S en estado abierto debe ser exitosa.');

        $os = $this->osModelo->obtenerPorId($idOS);
        $this->assertNotNull($os, 'La O/S debe existir.');
        $this->assertEquals('Ing. Juan Pérez Editado', $os['atencion_a']);
        $this->assertEquals('Construcción Puente Norte (Actualizado)', $os['nombre_proyecto']);
        $this->assertEquals('Pago a 30 días', $os['forma_pago']);
        $this->assertEquals('credito', $os['condicion_comercial']);
        $this->assertEquals('Marco', $os['tipo_contrato']);
        $this->assertEquals('Entregar reporte impreso y digital con sellos', $os['notas_condiciones']);
        $this->assertEquals('Estado 1: Recepcion', $os['estado'], 'El estado abierto previo debe conservarse.');
    }

    /**
     * 2. Edición rechazada en O/S Finalizada, Archivada o Cerrada
     */
    public function testEdicionRechazadaEnOSFinalizadaArchivadaCerrada(): void {
        $idOS = $this->idOSPrueba;
        $estadosCerrados = ['Finalizado', 'Archivado', 'Cerrado'];

        foreach ($estadosCerrados as $estadoCerrado) {
            // Colocar O/S en el estado cerrado
            $stmt = $this->db->prepare("UPDATE ordenes_servicio SET estado = :est WHERE id = :id");
            $stmt->execute(['est' => $estadoCerrado, 'id' => $idOS]);

            // Intentar editar
            $resultado = $this->osModelo->actualizar($idOS, [
                'atencion_a' => 'Intento No Permitido ' . $estadoCerrado,
                'nombre_proyecto' => 'Proyecto Bloqueado'
            ]);

            $this->assertFalse($resultado, "La edición debe ser rechazada cuando la O/S está en estado '{$estadoCerrado}'.");
            $this->assertEquals(
                'La Orden de Servicio está cerrada o archivada y no admite modificaciones.',
                $this->osModelo->obtenerUltimoError(),
                "El mensaje de error debe indicar el bloqueo de modificaciones en estado '{$estadoCerrado}'."
            );

            // Verificar que no se alteró en la BD
            $os = $this->osModelo->obtenerPorId($idOS);
            $this->assertNotEquals('Intento No Permitido ' . $estadoCerrado, $os['atencion_a']);
        }
    }

    /**
     * 3. Regla de consistencia: O/S Aprobada pasa a 'Estado 2: Revision' tras edición
     */
    public function testOSAprobadaPasaAEstado2RevisionTrasEdicion(): void {
        $idOS = $this->idOSPrueba;

        // Establecer O/S en estado 'Aprobada'
        $stmt = $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = :id");
        $stmt->execute(['id' => $idOS]);

        $osInicial = $this->osModelo->obtenerPorId($idOS);
        $this->assertEquals('Aprobada', $osInicial['estado']);

        // Realizar edición técnica o comercial
        $ok = $this->osModelo->actualizar($idOS, [
            'atencion_a' => 'Nuevo Coordinador de Calidad',
            'nombre_proyecto' => 'Ampliación Pista Aeropuerto - Fase 2'
        ]);

        $this->assertTrue($ok, 'La edición de la O/S aprobada debe ejecutarse correctamente.');

        // Verificar que transicionó automáticamente a 'Estado 2: Revision'
        $osActualizada = $this->osModelo->obtenerPorId($idOS);
        $this->assertEquals(
            'Estado 2: Revision',
            $osActualizada['estado'],
            "Una O/S que estaba 'Aprobada' debe transicionar automáticamente a 'Estado 2: Revision' al ser editada para requerir re-aprobación del supervisor."
        );
        $this->assertEquals('Nuevo Coordinador de Calidad', $osActualizada['atencion_a']);
    }

    /**
     * 4. Persistencia de campos multi-mes (es_multimes, fecha_inicio, fecha_fin, periodicidad)
     */
    public function testPersistenciaCamposMultiMes(): void {
        $idOS = $this->idOSPrueba;

        // 4a. Probar actualización con contrato multi-mes activo
        $okUpdate = $this->osModelo->actualizar($idOS, [
            'es_multimes' => 1,
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-10-31',
            'periodicidad' => 'Quincenal',
            'tipo_contrato' => 'Marco'
        ]);
        $this->assertTrue($okUpdate, 'La actualización de campos multi-mes debe ser exitosa.');

        $os = $this->osModelo->obtenerPorId($idOS);
        $this->assertEquals(1, (int)$os['es_multimes'], 'es_multimes debe persistir como 1.');
        $this->assertEquals('2026-04-01', $os['fecha_inicio'], 'fecha_inicio debe persistir con la fecha asignada.');
        $this->assertEquals('2026-10-31', $os['fecha_fin'], 'fecha_fin debe persistir con la fecha asignada.');
        $this->assertEquals('Quincenal', $os['periodicidad'], 'periodicidad debe persistir como Quincenal.');

        // 4b. Probar creación directa con multi-mes
        $codigoMulti = 'TEST-OS-MULTI-' . uniqid();
        $idMulti = $this->osModelo->crear([
            'codigo_os' => $codigoMulti,
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePrueba,
            'tipo_contrato' => 'Marco',
            'es_multimes' => 1,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-12-31',
            'periodicidad' => 'Trimestral',
            'estado' => 'Estado 1: Recepcion'
        ]);

        $this->assertGreaterThan(0, $idMulti);
        $osMulti = $this->osModelo->obtenerPorId($idMulti);
        $this->assertEquals(1, (int)$osMulti['es_multimes']);
        $this->assertEquals('2026-05-01', $osMulti['fecha_inicio']);
        $this->assertEquals('2026-12-31', $osMulti['fecha_fin']);
        $this->assertEquals('Trimestral', $osMulti['periodicidad']);

        // Limpiar registro de prueba multi-mes
        $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$idMulti}");
    }

    /**
     * 5. Gate duro RT-FM-13: intento de guardar hoja en O/S no aprobada falla con error;
     *    en O/S aprobada pasa el gate.
     */
    public function testGateRTM13FallaEnOSNoAprobadaYPasaEnOSAprobada(): void {
        $idOS = $this->idOSPrueba;

        // 5a. O/S en estado 'Estado 1: Recepcion' (NO aprobada)
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 1: Recepcion' WHERE id = :id")->execute(['id' => $idOS]);

        $datosHoja = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'numero_registro' => 'REG-TEST-GATE-01',
            'nombre_empresa_o_cliente' => 'Cliente Gate Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'nombre_persona_entrega_muestra' => 'Ing. Entrega Test',
            'naturaleza_muestra' => 'Concreto',
            'procedencia_punto_muestreo' => 'Punto de Muestreo Gate',
            'nombre_persona_toma_muestra' => 'Tecnico Test',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0001-26', 'descripcion' => 'Cilindro Test', 'info_importante' => 'Eje 1']
            ]),
            'req_resistencia_concreto' => 1
        ];

        $idHojaGenerada = 0;
        $resultadoNoAprobada = $this->opModelo->guardarHojaSolicitud($datosHoja, $idHojaGenerada);

        $this->assertFalse($resultadoNoAprobada, 'Intento de guardar hoja RT-FM-13 en O/S no aprobada debe ser rechazado.');
        $this->assertEquals(
            'La Orden de Servicio debe estar en estado Aprobada para registrar o modificar Hojas de Solicitud (CYCSA-RT-FM-13).',
            $this->opModelo->obtenerUltimoError(),
            'El mensaje de error debe coincidir con la especificación del Gate duro RT-FM-13.'
        );

        // 5b. O/S en estado 'Estado 2: Revision' (TAMPOCO aprobada)
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 2: Revision' WHERE id = :id")->execute(['id' => $idOS]);
        $resultadoRevision = $this->opModelo->guardarHojaSolicitud($datosHoja, $idHojaGenerada);
        $this->assertFalse($resultadoRevision, 'En Estado 2: Revision también debe bloquearse la RT-FM-13.');

        // 5c. O/S en estado 'Aprobada' (DEBE PASAR EL GATE)
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = :id")->execute(['id' => $idOS]);

        $idHojaValida = 0;
        $resultadoAprobada = $this->opModelo->guardarHojaSolicitud($datosHoja, $idHojaValida);

        $this->assertTrue($resultadoAprobada, 'En O/S con estado Aprobada, la hoja RT-FM-13 debe guardarse con éxito.');
        $this->assertGreaterThan(0, $idHojaValida, 'El ID de la hoja guardada debe ser mayor a cero.');
        $this->hojasCreadas[] = $idHojaValida;

        // 5c2. O/S en estado 'Estado 3: Ingreso Directo' (DEBE PASAR EL GATE)
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 3: Ingreso Directo' WHERE id = :id")->execute(['id' => $idOS]);
        $idHojaIngresoDirecto = 0;
        $resultadoIngresoDirecto = $this->opModelo->guardarHojaSolicitud($datosHoja, $idHojaIngresoDirecto);
        $this->assertTrue($resultadoIngresoDirecto, 'En O/S con Estado 3: Ingreso Directo, la hoja RT-FM-13 debe guardarse con éxito.');
        $this->assertGreaterThan(0, $idHojaIngresoDirecto);
        $this->hojasCreadas[] = $idHojaIngresoDirecto;

        // 5d. O/S en estado 'Finalizado' (Cerrada)
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Finalizado' WHERE id = :id")->execute(['id' => $idOS]);
        $idHojaCerrada = 0;
        $resultadoCerrada = $this->opModelo->guardarHojaSolicitud($datosHoja, $idHojaCerrada);
        $this->assertFalse($resultadoCerrada, 'En O/S Finalizada debe rechazarse con error de cerrada/archivada.');
        $this->assertEquals(
            'La Orden de Servicio está cerrada o archivada y no admite modificaciones.',
            $this->opModelo->obtenerUltimoError()
        );
    }
}
