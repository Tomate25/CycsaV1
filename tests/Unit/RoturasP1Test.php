<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use PDO;

class RoturasP1Test extends TestCase {

    private PDO $db;
    private RoturasModelo $modelo;
    private ?int $clienteId = null;
    private ?int $osId = null;
    private ?int $recepcionId = null;
    private ?int $loteId = null;
    private string $codigoLab = 'MS-P1-TEST-26';
    private int $adminUserId = 1;
    private int $calidadRolId = 3;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->modelo = new RoturasModelo();

        $randomSuffix = bin2hex(random_bytes(4));
        $this->codigoLab = 'MS-P1-' . $randomSuffix . '-26';
        $codigoOS = 'OS-P1-' . $randomSuffix;

        // 1. Cliente y OS de prueba
        $stmtC = $this->db->prepare("
            INSERT INTO clientes (nombre_razon_social, identificacion, numero_ruc, email, telefono, direccion)
            VALUES ('Consorcio P1 Lab Test', 'J999999991', 'J999999991', :email, '2222-1111', 'Managua')
        ");
        $stmtC->execute(['email' => 'p1_' . $randomSuffix . '@test.com']);
        $this->clienteId = (int)$this->db->lastInsertId();

        $stmtOS = $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, nombre_proyecto, atencion_a, estado)
            VALUES (:cos, 1, :cid, CURDATE(), 'Proyecto P1 Calidad ISO 17025', 'Ing. Supervisor', 'Estado 4: Ingreso Laboratorio')
        ");
        $stmtOS->execute(['cos' => $codigoOS, 'cid' => $this->clienteId]);
        $this->osId = (int)$this->db->lastInsertId();

        // 2. Recepción y Lote de prueba
        $stmtRec = $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado)
            VALUES (:os_id, 9991, 2026, 'Laboratorio', :cod, 'C-P1-01', NOW(), 1, 'Registrado')
        ");
        $stmtRec->execute(['os_id' => $this->osId, 'cod' => $this->codigoLab]);
        $this->recepcionId = (int)$this->db->lastInsertId();

        $stmtLote = $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, temperatura_c, procedimiento_muestreo)
            VALUES (:rec_id, 'Concreto Estructural f\'c = 4000 PSI', '4000 PSI', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 4.5, 25.0, 'ASTM C172')
        ");
        $stmtLote->execute(['rec_id' => $this->recepcionId]);
        $this->loteId = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->recepcionId) {
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = :id)")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM informes_rotura WHERE muestra_id = :id")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM plan_rotura_asignaciones WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = :id)")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM plan_rotura_edades WHERE plan_rotura_id IN (SELECT id FROM planes_rotura WHERE muestra_id = :id)")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM planes_rotura WHERE muestra_id = :id")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM especimenes WHERE muestra_id = :id")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id_recepcion = :id")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id = :id")->execute(['id' => $this->recepcionId]);
        }
        if ($this->osId) {
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $this->osId]);
        }
        if ($this->clienteId) {
            $this->db->prepare("DELETE FROM clientes WHERE id = :id")->execute(['id' => $this->clienteId]);
        }
        parent::tearDown();
    }

    /**
     * Helper para preparar especímenes, plan y roturas para 1 edad (7 días).
     */
    private function prepararEdadCompletada(int $dias = 7, int $cantidad = 2): array {
        $fechaMoldeo = date('Y-m-d', strtotime("-{$dias} days"));
        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, $cantidad, $fechaMoldeo);

        $resPlan = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => $dias, 'cantidad' => $cantidad]],
            $this->adminUserId
        );
        $this->assertTrue($resPlan['exito'], 'El plan debe guardarse con éxito');

        $detalle = $this->modelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $edadId = (int)$detalle['edades'][0]['id'];

        foreach ($detalle['especimenes'] as $esp) {
            $this->modelo->registrarRotura([
                'especimen_id' => $esp['id'],
                'plan_edad_id' => $edadId,
                'fecha_real' => date('Y-m-d 08:00:00'),
                'carga' => 85000,
                'area' => 28.274,
                'justificacion' => 'Ensayo de prueba en fecha programada'
            ], $this->adminUserId);
        }

        return ['edad_id' => $edadId, 'plan_id' => $resPlan['plan_id']];
    }

    /**
     * CP-LAB-01: Cálculo de versión N+1 y pase a 'anulado' del informe previo.
     */
    public function testCpLab01CalculoVersionNMas1YAnuladoPrevio(): void {
        $prep = $this->prepararEdadCompletada(7, 2);

        // 1. Generar informe parcial inicial (Versión 1)
        $resParcial = $this->modelo->generarInformeParcial($this->recepcionId, $prep['edad_id'], $this->adminUserId);
        $this->assertTrue($resParcial['exito'], 'El informe parcial inicial debe generarse con éxito');
        $informe1Id = (int)$resParcial['informe_id'];
        $this->assertGreaterThan(0, $informe1Id);

        // 2. Reemitir el informe especificando motivo
        $motivo1 = 'Ajuste de calibración de celda de carga según auditoría ISO 17025';
        $resReemision1 = $this->modelo->reemitirInforme($informe1Id, $motivo1, $this->adminUserId);

        $this->assertTrue($resReemision1['exito'], 'La reemisión debe ejecutarse con éxito');
        $this->assertEquals(2, $resReemision1['nueva_version'], 'La nueva versión debe ser 2 (N+1)');
        $this->assertNotEmpty($resReemision1['nuevo_numero'], 'Debe generar un nuevo número consecutivo');
        $informe2Id = (int)$resReemision1['nuevo_informe_id'];
        $this->assertNotEquals($informe1Id, $informe2Id);

        // 3. Verificar estado del informe previo (debe ser 'anulado' con su motivo)
        $stmt1 = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
        $stmt1->execute(['id' => $informe1Id]);
        $inf1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('anulado', $inf1['estado'], 'El informe previo debe quedar en estado anulado');
        $this->assertEquals($motivo1, $inf1['motivo_reemision'], 'Debe persistir el motivo de reemisión');

        // 4. Verificar estado del nuevo informe (debe ser 'en_revision', versión 2)
        $stmt2 = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
        $stmt2->execute(['id' => $informe2Id]);
        $inf2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('en_revision', $inf2['estado'], 'El nuevo informe debe crearse en estado en_revision');
        $this->assertEquals(2, (int)$inf2['version'], 'La versión debe ser exactamente 2');
        $this->assertEquals('parcial', $inf2['tipo']);
        $this->assertEquals($this->recepcionId, (int)$inf2['muestra_id']);
        $this->assertEquals($prep['edad_id'], (int)$inf2['plan_edad_id']);

        // 5. Segunda reemisión consecutiva (Versión 2 -> Versión 3)
        $motivo2 = 'Corrección en notas de procedimiento técnico';
        $resReemision2 = $this->modelo->reemitirInforme($informe2Id, $motivo2, $this->adminUserId);

        $this->assertTrue($resReemision2['exito']);
        $this->assertEquals(3, $resReemision2['nueva_version'], 'La siguiente reemisión debe ser versión 3 (N+1)');
        $informe3Id = (int)$resReemision2['nuevo_informe_id'];

        // Verificar que informe 2 pasó a anulado
        $stmt2Check = $this->db->prepare("SELECT estado, motivo_reemision FROM informes_rotura WHERE id = :id");
        $stmt2Check->execute(['id' => $informe2Id]);
        $inf2Check = $stmt2Check->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('anulado', $inf2Check['estado']);
        $this->assertEquals($motivo2, $inf2Check['motivo_reemision']);
    }

    /**
     * CP-LAB-02: Bloqueo estricto de edición/actualización sobre informes en estado 'aprobado'.
     */
    public function testCpLab02BloqueoEstrictoPostAprobacion(): void {
        $prep = $this->prepararEdadCompletada(7, 2);

        // 1. Generar y aprobar informe parcial
        $resParcial = $this->modelo->generarInformeParcial($this->recepcionId, $prep['edad_id'], $this->adminUserId);
        $this->assertTrue($resParcial['exito']);
        $informeId = (int)$resParcial['informe_id'];

        $resAprobar = $this->modelo->procesarRevisionInforme($informeId, 'aprobado', null, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resAprobar['exito'], 'El informe debe ser aprobado por Calidad');

        // 2. Intento de devolver informe ya aprobado -> BLOQUEO ESTRICTO
        $resDevolver = $this->modelo->procesarRevisionInforme(
            $informeId,
            'devuelto',
            'Intento indebido de devolver informe ya aprobado',
            $this->adminUserId,
            $this->calidadRolId
        );
        $this->assertFalse($resDevolver['exito'], 'Debe rechazar la devolución de un informe aprobado');
        $this->assertEquals(
            'El informe ya se encuentra aprobado y está bloqueado para modificaciones (integridad ISO/IEC 17025).',
            $resDevolver['mensaje'],
            'El mensaje de bloqueo debe cumplir la regla exacta de integridad'
        );

        // 3. Intento de re-aprobar un informe ya aprobado -> BLOQUEO ESTRICTO
        $resReaprobar = $this->modelo->procesarRevisionInforme(
            $informeId,
            'aprobado',
            null,
            $this->adminUserId,
            $this->calidadRolId
        );
        $this->assertFalse($resReaprobar['exito'], 'Debe rechazar re-aprobar un informe ya aprobado');
        $this->assertEquals(
            'El informe ya se encuentra aprobado y está bloqueado para modificaciones (integridad ISO/IEC 17025).',
            $resReaprobar['mensaje']
        );

        // 4. Comprobar en base de datos que el estado y aprobador no cambiaron
        $stmt = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
        $stmt->execute(['id' => $informeId]);
        $inf = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('aprobado', $inf['estado']);
        $this->assertEquals($this->adminUserId, (int)$inf['aprobado_por']);
        $this->assertNull($inf['motivo_devolucion']);
    }

    /**
     * CP-LAB-03: Asignación de pdf y sha256 al aprobar.
     */
    public function testCpLab03AsignacionPdfYSha256AlAprobar(): void {
        $prep = $this->prepararEdadCompletada(7, 2);

        $resParcial = $this->modelo->generarInformeParcial($this->recepcionId, $prep['edad_id'], $this->adminUserId);
        $informeId = (int)$resParcial['informe_id'];

        // Antes de aprobar: pdf y sha256 deben ser nulos
        $stmtPre = $this->db->prepare("SELECT pdf, sha256, estado FROM informes_rotura WHERE id = :id");
        $stmtPre->execute(['id' => $informeId]);
        $pre = $stmtPre->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('en_revision', $pre['estado']);
        $this->assertNull($pre['pdf']);
        $this->assertNull($pre['sha256']);

        // Aprobar informe
        $resAprob = $this->modelo->procesarRevisionInforme($informeId, 'aprobado', null, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resAprob['exito']);
        $this->assertNotEmpty($resAprob['pdf']);
        $this->assertNotEmpty($resAprob['sha256']);

        // Verificar persistencia en base de datos
        $stmtPost = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
        $stmtPost->execute(['id' => $informeId]);
        $post = $stmtPost->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('aprobado', $post['estado']);
        $this->assertNotNull($post['pdf'], 'La ruta del PDF no debe ser nula');
        $this->assertStringStartsWith('almacen/informes/', $post['pdf']);
        $this->assertStringEndsWith('.pdf', $post['pdf']);

        $this->assertNotNull($post['sha256'], 'El hash SHA-256 no debe ser nulo');
        $this->assertEquals(64, strlen($post['sha256']), 'El hash SHA-256 debe tener longitud exacta de 64 caracteres');
        $this->assertTrue(ctype_xdigit($post['sha256']), 'El hash SHA-256 debe contener solo caracteres hexadecimales válidos');
        $this->assertNotNull($post['fecha_aprobacion']);
        $this->assertEquals($this->adminUserId, (int)$post['aprobado_por']);
    }

    /**
     * CP-LAB-04: Invalidación del consolidado cuando se reemite un parcial.
     */
    public function testCpLab04InvalidacionConsolidadoAlReemitirParcial(): void {
        $prep = $this->prepararEdadCompletada(7, 2);

        // 1. Generar y aprobar parcial de 7 días
        $resParcial = $this->modelo->generarInformeParcial($this->recepcionId, $prep['edad_id'], $this->adminUserId);
        $parcialId = (int)$resParcial['informe_id'];
        $resAprobar = $this->modelo->procesarRevisionInforme($parcialId, 'aprobado', null, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resAprobar['exito']);

        // 2. Con todos los parciales aprobados, generar consolidado
        $resConsolidado = $this->modelo->generarInformeConsolidado($this->recepcionId, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resConsolidado['exito'], 'El informe consolidado debe emitirse exitosamente');
        $consolidadoId = (int)$resConsolidado['informe_id'];
        $this->assertGreaterThan(0, $consolidadoId);

        // Verificar consolidado activo
        $stmtCons = $this->db->prepare("SELECT estado FROM informes_rotura WHERE id = :id");
        $stmtCons->execute(['id' => $consolidadoId]);
        $this->assertEquals('aprobado', $stmtCons->fetchColumn());

        // 3. Reemitir el parcial
        $motivoReemision = 'Rectificación de datos por calibración periódica';
        $resReemitir = $this->modelo->reemitirInforme($parcialId, $motivoReemision, $this->adminUserId);
        $this->assertTrue($resReemitir['exito']);
        $nuevoParcialId = (int)$resReemitir['nuevo_informe_id'];

        // 4. Verificar que el consolidado preexistente fue INVALIDADO ('anulado')
        $stmtConsCheck = $this->db->prepare("SELECT estado, motivo_reemision FROM informes_rotura WHERE id = :id");
        $stmtConsCheck->execute(['id' => $consolidadoId]);
        $consCheck = $stmtConsCheck->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('anulado', $consCheck['estado'], 'El informe consolidado debe quedar anulado automáticamente tras reemitir un parcial');
        $this->assertStringContainsString('Invalidado', $consCheck['motivo_reemision']);

        // 5. Intentar generar consolidado nuevamente -> BLOQUEADO porque el nuevo parcial está en revisión
        $resIntentoCons = $this->modelo->generarInformeConsolidado($this->recepcionId, $this->adminUserId, $this->calidadRolId);
        $this->assertFalse($resIntentoCons['exito'], 'No debe permitir generar consolidado con parcial en revisión');
        $this->assertStringContainsString('en_revision', $resIntentoCons['mensaje']);

        // 6. Aprobar el nuevo informe parcial reemitido
        $resAprobarNuevo = $this->modelo->procesarRevisionInforme($nuevoParcialId, 'aprobado', null, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resAprobarNuevo['exito']);

        // 7. Ahora generar consolidado debe ser PERMITIDO nuevamente
        $resConsolidadoNuevo = $this->modelo->generarInformeConsolidado($this->recepcionId, $this->adminUserId, $this->calidadRolId);
        $this->assertTrue($resConsolidadoNuevo['exito'], 'Una vez aprobado el parcial reemitido, el consolidado debe poder emitirse nuevamente');
        $this->assertNotEquals($consolidadoId, $resConsolidadoNuevo['informe_id']);
    }
}
