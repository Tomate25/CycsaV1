<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use PDO;

class ProgramaRoturasTest extends TestCase {

    private PDO $db;
    private RoturasModelo $modelo;
    private ?int $clienteId = null;
    private ?int $osId = null;
    private ?int $recepcionId = null;
    private ?int $loteId = null;
    private string $codigoLab = 'MS-ROT-TEST-26';

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->modelo = new RoturasModelo();

        $randomSuffix = bin2hex(random_bytes(4));
        $this->codigoLab = 'MS-ROT-' . $randomSuffix . '-26';
        $codigoOS = 'OS-ROT-' . $randomSuffix;

        // 1. Fixture de cliente y OS
        $stmtC = $this->db->prepare("
            INSERT INTO clientes (nombre_razon_social, identificacion, numero_ruc, email, telefono, direccion)
            VALUES ('Consorcio Concretos del Norte', 'J099999999', 'J099999999', :email, '2222-0000', 'Managua')
        ");
        $stmtC->execute(['email' => 'lab_' . $randomSuffix . '@consorcio.com']);
        $this->clienteId = (int)$this->db->lastInsertId();

        $stmtOS = $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, nombre_proyecto, atencion_a, estado)
            VALUES (:cos, 1, :cid, CURDATE(), 'Edificio Corporativo Central', 'Ing. Inspector', 'Estado 4: Ingreso Laboratorio')
        ");
        $stmtOS->execute(['cos' => $codigoOS, 'cid' => $this->clienteId]);
        $this->osId = (int)$this->db->lastInsertId();

        // 2. Fixture de Recepción y Lote
        $stmtRec = $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado)
            VALUES (:os_id, 8888, 2026, 'Laboratorio', :cod, 'C-ROT-01', NOW(), 1, 'Registrado')
        ");
        $stmtRec->execute(['os_id' => $this->osId, 'cod' => $this->codigoLab]);
        $this->recepcionId = (int)$this->db->lastInsertId();

        $stmtLote = $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, temperatura_c, procedimiento_muestreo)
            VALUES (:rec_id, 'Concreto Estructural f\'c = 3000 PSI', '3000 PSI', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 4.0, 26.0, 'ASTM C172')
        ");
        $stmtLote->execute(['rec_id' => $this->recepcionId]);
        $this->loteId = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->recepcionId) {
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = :id)")->execute(['id' => $this->recepcionId]);
            $this->db->prepare("DELETE FROM informes_rotura WHERE muestra_id = :id")->execute(['id' => $this->recepcionId]);
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
     * Prueba 1: Creación automática de especímenes con sufijo secuencial (-C1, -C2, ...).
     */
    public function testCreacionAutomaticaDeEspecimenesConSufijo(): void {
        $fechaMoldeo = date('Y-m-d', strtotime('-7 days'));
        $exito = $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 4, $fechaMoldeo, 'cilindro_concreto', '15 x 30 cm');

        $this->assertTrue($exito);

        $stmt = $this->db->prepare("SELECT codigo_lab, estado FROM especimenes WHERE muestra_id = :mid ORDER BY id ASC");
        $stmt->execute(['mid' => $this->recepcionId]);
        $especimenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(4, $especimenes);
        $this->assertEquals($this->codigoLab . '-C1', $especimenes[0]['codigo_lab']);
        $this->assertEquals($this->codigoLab . '-C2', $especimenes[1]['codigo_lab']);
        $this->assertEquals($this->codigoLab . '-C3', $especimenes[2]['codigo_lab']);
        $this->assertEquals($this->codigoLab . '-C4', $especimenes[3]['codigo_lab']);
        $this->assertEquals('en_curado', $especimenes[0]['estado']);
    }

    /**
     * Prueba 2: El plan amplía los especímenes existentes y conserva testigos.
     */
    public function testValidacionCantidadesDelPlanYAsignacionTestigos(): void {
        $fechaMoldeo = date('Y-m-d', strtotime('-7 days'));
        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 4, $fechaMoldeo);

        // El plan requiere 5 probetas y solo existen 4: debe crear la faltante.
        $resExceso = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [
                ['edad_dias' => 7, 'cantidad' => 2],
                ['edad_dias' => 28, 'cantidad' => 3] // Total = 5
            ],
            1
        );
        $this->assertTrue($resExceso['exito']);

        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM especimenes WHERE muestra_id = :mid");
        $stmtTotal->execute(['mid' => $this->recepcionId]);
        $this->assertEquals(5, (int)$stmtTotal->fetchColumn());

        // Editar el plan a 3 probetas de 5 debe dejar 2 como testigos.
        $resValido = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [
                ['edad_dias' => 7, 'cantidad' => 1],
                ['edad_dias' => 28, 'cantidad' => 2] // Total = 3
            ],
            1
        );
        $this->assertTrue($resValido['exito']);

        // Verificar que 3 están programadas y 2 como testigo
        $stmtProg = $this->db->prepare("SELECT COUNT(*) FROM especimenes WHERE muestra_id = :mid AND estado = 'programado'");
        $stmtProg->execute(['mid' => $this->recepcionId]);
        $this->assertEquals(3, (int)$stmtProg->fetchColumn());

        $stmtTestigo = $this->db->prepare("SELECT COUNT(*) FROM especimenes WHERE muestra_id = :mid AND estado = 'testigo'");
        $stmtTestigo->execute(['mid' => $this->recepcionId]);
        $this->assertEquals(2, (int)$stmtTestigo->fetchColumn());
    }

    public function testPlanCreaEspecimenesCuandoLaMuestraNoTienePrevios(): void {
        $resultado = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [
                ['edad_dias' => 7, 'cantidad' => 2],
                ['edad_dias' => 28, 'cantidad' => 2]
            ],
            1
        );

        $this->assertTrue($resultado['exito']);

        $stmt = $this->db->prepare("SELECT codigo_lab, estado FROM especimenes WHERE muestra_id = :mid ORDER BY id ASC");
        $stmt->execute(['mid' => $this->recepcionId]);
        $especimenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(4, $especimenes);
        $this->assertSame($this->codigoLab . '-C1', $especimenes[0]['codigo_lab']);
        $this->assertSame($this->codigoLab . '-C4', $especimenes[3]['codigo_lab']);
        $this->assertSame('programado', $especimenes[0]['estado']);
    }

    /**
     * Prueba 3: Cálculo de fechas programadas según edades.
     */
    public function testCalculoFechasProgramadasSegunEdades(): void {
        $fechaMoldeo = date('Y-m-d', strtotime('-7 days'));
        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 3, $fechaMoldeo);

        $res = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [
                ['edad_dias' => 7, 'cantidad' => 1],
                ['edad_dias' => 28, 'cantidad' => 2]
            ],
            1
        );
        $this->assertTrue($res['exito']);

        $stmtEdades = $this->db->prepare("SELECT edad_dias, fecha_programada FROM plan_rotura_edades WHERE plan_rotura_id = :pid ORDER BY edad_dias ASC");
        $stmtEdades->execute(['pid' => $res['plan_id']]);
        $edades = $stmtEdades->fetchAll(PDO::FETCH_ASSOC);

        $stmtM = $this->db->prepare("SELECT fecha_moldeo FROM lotes_muestras WHERE id_recepcion = :id");
        $stmtM->execute(['id' => $this->recepcionId]);
        $fechaMoldeoDb = $stmtM->fetchColumn();

        $esperada7 = date('Y-m-d', strtotime("{$fechaMoldeoDb} + 7 days"));
        $esperada28 = date('Y-m-d', strtotime("{$fechaMoldeoDb} + 28 days"));

        $this->assertEquals($esperada7, $edades[0]['fecha_programada']);
        $this->assertEquals($esperada28, $edades[1]['fecha_programada']);
    }

    /**
     * Prueba 4: Registro de rotura, cálculos físicos de compresión y tolerancia ASTM C39.
     */
    public function testRegistroRoturaCalculoEsfuerzoYTolerancia(): void {
        $stmtM = $this->db->prepare("SELECT fecha_moldeo FROM lotes_muestras WHERE id_recepcion = :id");
        $stmtM->execute(['id' => $this->recepcionId]);
        $fechaMoldeo = $stmtM->fetchColumn();

        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 2, $fechaMoldeo);

        $resPlan = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => 7, 'cantidad' => 2]],
            1
        );

        $detalle = $this->modelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $esp1 = $detalle['especimenes'][0];
        $planEdadId = (int)$esp1['plan_edad_id'];

        // Carga = 85,000 lb, Área = 28.274 in²
        // Esfuerzo PSI esperado = 85000 / 28.274 = ~3006.29 PSI
        // Esfuerzo kg/cm² = 3006.29 * 0.070307 = ~211.36 kg/cm²
        $resRotura = $this->modelo->registrarRotura([
            'especimen_id' => $esp1['id'],
            'plan_edad_id' => $planEdadId,
            'fecha_real' => $esp1['fecha_programada'] . ' 10:00:00',
            'carga' => 85000,
            'area' => 28.274,
            'tipo_falla' => 'Tipo 1 (Cono)',
            'justificacion' => ''
        ], 1);

        $this->assertTrue($resRotura['exito']);
        $this->assertGreaterThan(3000, $resRotura['esfuerzo_psi']);
        $this->assertGreaterThan(210, $resRotura['esfuerzo_kgcm2']);
        $this->assertGreaterThanOrEqual(100, $resRotura['porcentaje_fc']); // Sobre 3000 PSI diseño
    }

    /**
     * Prueba 5: Bloqueo de rotura fuera de tolerancia sin justificación.
     */
    public function testRoturaFueraDeToleranciaRequiereJustificacion(): void {
        $fechaMoldeo = date('Y-m-d', strtotime('-30 days'));
        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 2, $fechaMoldeo);

        $resPlan = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => 7, 'cantidad' => 2]],
            1
        );

        $detalle = $this->modelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $esp1 = $detalle['especimenes'][0];

        // Se intenta ensayar hoy una edad que correspondía a hace 23 días (fuera de tolerancia) sin justificación
        $res = $this->modelo->registrarRotura([
            'especimen_id' => $esp1['id'],
            'plan_edad_id' => $esp1['plan_edad_id'],
            'fecha_real' => date('Y-m-d H:i:s'),
            'carga' => 75000,
            'area' => 28.274,
            'justificacion' => '' // Vacía
        ], 1);

        $this->assertFalse($res['exito']);
        $this->assertTrue($res['requiere_justificacion'] ?? false);
        $this->assertStringContainsString('tolerancia ASTM C39', $res['mensaje']);

        // Ahora enviando la justificación técnica requerida -> Debe registrar exitosamente
        $resConJust = $this->modelo->registrarRotura([
            'especimen_id' => $esp1['id'],
            'plan_edad_id' => $esp1['plan_edad_id'],
            'fecha_real' => date('Y-m-d H:i:s'),
            'carga' => 75000,
            'area' => 28.274,
            'justificacion' => 'Cierre temporal por calibración de máquina compresora oficial'
        ], 1);

        $this->assertTrue($resConJust['exito']);
    }

    /**
     * Prueba 6: Bloqueo estricto del Informe Consolidado si los parciales no están aprobados.
     */
    public function testBloqueoDeInformeConsolidadoSiParcialesPendientesODevueltos(): void {
        $fechaMoldeo = date('Y-m-d', strtotime('-7 days'));
        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 1, $fechaMoldeo);

        $resPlan = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => 7, 'cantidad' => 1]],
            1
        );

        $detalle = $this->modelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $esp1 = $detalle['especimenes'][0];

        // 1. Antes de romper: Intento de consolidado -> Bloqueado
        $resBloq1 = $this->modelo->generarInformeConsolidado($this->recepcionId, 1, 1);
        $this->assertFalse($resBloq1['exito']);
        $this->assertStringContainsString('aún no está completada', $resBloq1['mensaje']);

        // 2. Se rompe el espécimen dentro de tolerancia
        $this->modelo->registrarRotura([
            'especimen_id' => $esp1['id'],
            'plan_edad_id' => $esp1['plan_edad_id'],
            'fecha_real' => $esp1['fecha_programada'] . ' 10:00:00',
            'carga' => 80000,
            'area' => 28.274
        ], 1);

        // 3. Edad completada pero sin informe parcial emitido -> Bloqueado
        $resBloq2 = $this->modelo->generarInformeConsolidado($this->recepcionId, 1, 1);
        $this->assertFalse($resBloq2['exito']);
        $this->assertStringContainsString('deben estar APROBADOS por Calidad', $resBloq2['mensaje']);

        // 4. Se emite el informe parcial (queda en estado 'en_revision') -> Sigue bloqueado
        $resParcial = $this->modelo->generarInformeParcial($this->recepcionId, (int)$esp1['plan_edad_id'], 1);
        $this->assertTrue($resParcial['exito']);

        $resBloq3 = $this->modelo->generarInformeConsolidado($this->recepcionId, 1, 1);
        $this->assertFalse($resBloq3['exito']);
        $this->assertStringContainsString('en_revision', $resBloq3['mensaje']);

        // 5. Calidad aprueba el informe parcial
        $resAprobar = $this->modelo->procesarRevisionInforme($resParcial['informe_id'], 'aprobado', null, 1, 3);
        $this->assertTrue($resAprobar['exito']);

        // 6. Ahora con 100% de parciales aprobados -> Consolidado se emite con éxito
        $resConsolidado = $this->modelo->generarInformeConsolidado($this->recepcionId, 1, 3);
        $this->assertTrue($resConsolidado['exito']);
        $this->assertStringStartsWith('INF-', $resConsolidado['numero']);
    }

    /**
     * Prueba 7: Bloqueo de modificación del plan una vez iniciadas las roturas.
     */
    public function testBloqueoModificacionPlanIniciadoSinPermisoCalidad(): void {
        $stmtM = $this->db->prepare("SELECT fecha_moldeo FROM lotes_muestras WHERE id_recepcion = :id");
        $stmtM->execute(['id' => $this->recepcionId]);
        $fechaMoldeo = $stmtM->fetchColumn();

        $this->modelo->crearEspecimenesParaMuestra($this->recepcionId, 2, $fechaMoldeo);

        $resPlan = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => 7, 'cantidad' => 2]],
            1
        );

        $detalle = $this->modelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $esp1 = $detalle['especimenes'][0];

        // Se ejecuta una rotura dentro de tolerancia
        $this->modelo->registrarRotura([
            'especimen_id' => $esp1['id'],
            'plan_edad_id' => $esp1['plan_edad_id'],
            'fecha_real' => $esp1['fecha_programada'] . ' 10:00:00',
            'carga' => 70000,
            'area' => 28.274
        ], 1);

        // Intento de modificar el plan como usuario normal -> Bloqueado
        $resModifNormal = $this->modelo->guardarPlanRotura(
            $this->recepcionId,
            null,
            [['edad_dias' => 28, 'cantidad' => 2]],
            1
        );
        $this->assertFalse($resModifNormal['exito']);
        $this->assertStringContainsString('roturas ejecutadas', $resModifNormal['mensaje']);

        // Intento con rol Calidad pero sin motivo justificado -> Rechazado
        $resModifCalidadSinMotivo = $this->modelo->modificarPlanRoturaConAutorizacion(
            $resPlan['plan_id'],
            [],
            1,
            3, // Rol Calidad
            'corto'
        );
        $this->assertFalse($resModifCalidadSinMotivo['exito']);

        // Intento con rol Calidad y motivo justificado -> Aprobado
        $resModifCalidadOk = $this->modelo->modificarPlanRoturaConAutorizacion(
            $resPlan['plan_id'],
            [],
            1,
            3, // Rol Calidad
            'Modificación autorizada por supervisión ante reprogramación de vaciado'
        );
        $this->assertTrue($resModifCalidadOk['exito']);
    }
    public function testLaboratorioDetalleViewScriptTagsAndModalButtons(): void {
        $viewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/laboratorio_detalle.php';
        $this->assertFileExists($viewPath);
        $content = file_get_contents($viewPath);

        // Validar presencia de botón y llamada a abrirModalConfigurarPlan
        $this->assertStringContainsString('onclick="abrirModalConfigurarPlan()"', $content);
        $this->assertStringContainsString('Configurar Plan de Roturas Ahora', $content);

        // Validar modal y funciones JavaScript correspondientes
        $this->assertStringContainsString('id="modalPlanRotura"', $content);
        $this->assertStringContainsString('function abrirModalConfigurarPlan()', $content);
        $this->assertStringContainsString('function cerrarModalPlanRotura()', $content);
        $this->assertStringContainsString('function abrirModalModificarPlanAutorizado()', $content);
        $this->assertStringContainsString('function recalcularPlanEspecimenes()', $content);

        // Validar que no existan etiquetas HTML dentro de los bloques script
        preg_match_all('/<script>(.*?)<\/script>/s', $content, $matches);
        $this->assertNotEmpty($matches[1], 'Debe haber bloques script validos.');
        foreach ($matches[1] as $idx => $script) {
            $this->assertStringNotContainsString('<div id="modalReemitirInforme"', $script, 'El modal de reemision no debe estar dentro del script.');
            $this->assertStringNotContainsString('<!-- Modal', $script, 'Un bloque script no debe contener comentarios HTML de modales.');
        }

        // Conteo simétrico de etiquetas script
        $opens = substr_count($content, '<script>');
        $closes = substr_count($content, '</script>');
        $this->assertEquals($opens, $closes, 'El número de etiquetas <script> y </script> debe ser exactamente igual.');
    }
}