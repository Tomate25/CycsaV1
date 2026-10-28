<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Nomina\Modelos\NominaModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use PDO;

class NominaAnticiposTest extends TestCase {

    private PDO $db;
    private NominaModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 017 se haya ejecutado
        $db = $this->db;
        $ruta017 = dirname(__DIR__, 2) . '/database/migrations/017_rrhh_nomina_anticipos.php';
        if (file_exists($ruta017)) {
            ob_start();
            include $ruta017;
            ob_end_clean();
        }

        $this->modelo = new NominaModelo($this->db);
    }

    /**
     * Valida que las tablas relacionales de nómina existan y los departamentos base estén sembrados.
     */
    public function testMigracion017CreaTablasYSiembraDepartamentos(): void {
        $tablasEsperadas = [
            'rrhh_departamentos',
            'rrhh_empleados',
            'rrhh_anticipos',
            'rrhh_nominas',
            'rrhh_nomina_detalles'
        ];

        foreach ($tablasEsperadas as $tabla) {
            $check = $this->db->query("SHOW TABLES LIKE '{$tabla}'")->fetchAll(PDO::FETCH_COLUMN);
            $this->assertNotEmpty($check, "La tabla {$tabla} debe existir tras la migración 017.");
        }

        $deptos = $this->modelo->listarDepartamentos();
        $codigos = array_column($deptos, 'codigo');
        $this->assertContains('LAB', $codigos, "Debe existir el departamento base 'LAB'.");
        $this->assertContains('ADM', $codigos, "Debe existir el departamento base 'ADM'.");
        $this->assertContains('VTAS', $codigos, "Debe existir el departamento base 'VTAS'.");

        // Validar que LAB tenga cuenta de mano de obra
        $deptoLab = array_values(array_filter($deptos, fn($d) => $d['codigo'] === 'LAB'))[0];
        $this->assertNotNull($deptoLab['cuenta_gasto_id'], "LAB debe tener cuenta_gasto_id asignada.");
    }

    /**
     * Valida CRUD completo de empleados y asignación de salarios.
     */
    public function testCrudEmpleados(): void {
        $deptos = $this->modelo->listarDepartamentos();
        $labId = $deptos[0]['id'];

        $codigoPrueba = 'EMP-TEST-' . bin2hex(random_bytes(3));
        $empleadoId = $this->modelo->guardarEmpleado([
            'codigo' => $codigoPrueba,
            'nombre' => 'Juan Carlos Perez Test',
            'cedula' => '001-' . rand(100000, 999999) . '-0001A',
            'departamento_id' => $labId,
            'puesto' => 'Técnico de Ensayos de Concreto',
            'salario_base' => 15000.00,
            'fecha_ingreso' => '2026-01-15',
            'estado' => 'activo'
        ]);

        $this->assertGreaterThan(0, $empleadoId);

        $emp = $this->modelo->obtenerEmpleadoPorId($empleadoId);
        $this->assertNotNull($emp);
        $this->assertEquals($codigoPrueba, $emp['codigo']);
        $this->assertEquals(15000.00, (float)$emp['salario_base']);
        $this->assertEquals('activo', $emp['estado']);

        // Actualizar salario y puesto
        $actualizado = $this->modelo->actualizarEmpleado($empleadoId, [
            'nombre' => 'Juan Carlos Perez Modificado',
            'cedula' => $emp['cedula'],
            'departamento_id' => $labId,
            'puesto' => 'Especialista Senior de Ensayos',
            'salario_base' => 18000.00,
            'fecha_ingreso' => '2026-01-15',
            'estado' => 'activo'
        ]);
        $this->assertTrue($actualizado);

        $empActualizado = $this->modelo->obtenerEmpleadoPorId($empleadoId);
        $this->assertEquals(18000.00, (float)$empActualizado['salario_base']);
        $this->assertEquals('Especialista Senior de Ensayos', $empActualizado['puesto']);

        // Cambiar estado a inactivo
        $cambio = $this->modelo->cambiarEstadoEmpleado($empleadoId, 'inactivo');
        $this->assertTrue($cambio);
        $empInactivo = $this->modelo->obtenerEmpleadoPorId($empleadoId);
        $this->assertEquals('inactivo', $empInactivo['estado']);
    }

    /**
     * Valida el registro de anticipos y la trazabilidad de su saldo.
     */
    public function testRegistroAnticipoYTrazabilidadSaldo(): void {
        $deptos = $this->modelo->listarDepartamentos();
        $labId = $deptos[0]['id'];

        $empId = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-ANT-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Anticipo Test',
            'departamento_id' => $labId,
            'puesto' => 'Laboratorista',
            'salario_base' => 14000.00,
            'fecha_ingreso' => date('Y-m-d'),
            'estado' => 'activo'
        ]);

        // Registrar anticipo sin banco
        $antId = $this->modelo->registrarAnticipo(
            $empId,
            2500.00,
            date('Y-m-d'),
            'Adelanto para compra de repuestos personales'
        );

        $this->assertGreaterThan(0, $antId);

        $anticipos = $this->modelo->listarAnticipos($empId);
        $this->assertCount(1, $anticipos);
        $this->assertEquals(2500.00, (float)$anticipos[0]['monto']);
        $this->assertEquals(2500.00, (float)$anticipos[0]['saldo']);
        $this->assertEquals('pendiente', $anticipos[0]['estado']);

        // Saldo anticipo reflejado en empleado
        $emp = $this->modelo->obtenerEmpleadoPorId($empId);
        $this->assertEquals(2500.00, (float)$emp['saldo_anticipos']);

        // Si existe cuenta bancaria de prueba, registrar con desembolso contable
        $bancos = $this->db->query("SELECT id, saldo_actual FROM bancos_cuentas WHERE activo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($bancos) {
            $saldoBcoInicial = (float)$bancos['saldo_actual'];
            $antBcoId = $this->modelo->registrarAnticipo(
                $empId,
                500.00,
                date('Y-m-d'),
                'Adelanto vía transferencia bancaria',
                (int)$bancos['id']
            );

            $this->assertGreaterThan(0, $antBcoId);
            $antBco = $this->modelo->listarAnticipos($empId);
            $regBco = array_values(array_filter($antBco, fn($a) => (int)$a['id'] === $antBcoId))[0];
            $this->assertNotNull($regBco['id_asiento'], "Debe generar partida de diario contable al desembolsar vía banco.");

            // Saldo banco actualizado
            $stmtBco = $this->db->prepare("SELECT saldo_actual FROM bancos_cuentas WHERE id = :id");
            $stmtBco->execute(['id' => $bancos['id']]);
            $saldoBcoNuevo = (float)$stmtBco->fetchColumn();
            $this->assertEquals(round($saldoBcoInicial - 500.00, 2), round($saldoBcoNuevo, 2));
        }
    }

    /**
     * Valida el cálculo de Retención de IR según la legislación nicaragüense (Ley 822).
     */
    public function testCalculoIrLaboralSegunLey822(): void {
        // Caso 1: Salario exento (<= C$ 100,000 anual / 12 = C$ 8,333.33 neto gravable)
        $ir1 = NominaModelo::calcularIrLaboral(8000.00, 560.00); // Base: 7,440 -> Anual: 89,280
        $this->assertEquals(0.00, $ir1);

        // Caso 2: Salario en tramo 15% (C$ 15,000 bruto)
        // INSS Lab: 15,000 * 0.07 = 1,050. Base gravable mensual: 13,950.
        // Base anual: 13,950 * 12 = 167,400. Exceso sobre 100,000 = 67,400.
        // IR anual = 67,400 * 0.15 = 10,110. IR mensual = 10,110 / 12 = 842.50.
        $ir2 = NominaModelo::calcularIrLaboral(15000.00, 1050.00);
        $this->assertEquals(842.50, $ir2);

        // Caso 3: Salario alto tramo 20% (C$ 25,000 bruto)
        // INSS Lab: 1,750. Base mensual: 23,250. Base anual: 279,000.
        // Exceso sobre 200,000 = 79,000 * 0.20 = 15,800 + 15,000 base = 30,800 anual / 12 = 2,566.67
        $ir3 = NominaModelo::calcularIrLaboral(25000.00, 1750.00);
        $this->assertEquals(2566.67, $ir3);
    }

    /**
     * Valida el procesamiento de nómina completo con:
     * - Cálculo exacto de INSS laboral (7%) e IR
     * - Descuento automático de anticipos (actualizando saldo y estado)
     * - Cargas patronales (INSS Patronal 22.5%, INATEC 2%)
     * - Provisiones de ley (Vacaciones, Aguinaldo, Indemnización al 8.33%)
     */
    public function testProcesamientoNominaDescuentoAutomaticoAnticiposYCargas(): void {
        $deptos = $this->modelo->listarDepartamentos();
        $labId = array_values(array_filter($deptos, fn($d) => $d['codigo'] === 'LAB'))[0]['id'];
        $admId = array_values(array_filter($deptos, fn($d) => $d['codigo'] === 'ADM'))[0]['id'];

        // Empleado 1 (LAB) con anticipo pendiente
        $emp1Id = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-P1-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Laboratorio Nómina',
            'departamento_id' => $labId,
            'puesto' => 'Técnico de Suelos',
            'salario_base' => 15000.00,
            'fecha_ingreso' => '2026-01-01',
            'estado' => 'activo'
        ]);

        $this->modelo->registrarAnticipo($emp1Id, 1000.00, '2026-10-05', 'Anticipo quincenal');

        // Empleado 2 (ADM) con horas extras sin anticipo
        $emp2Id = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-P2-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Administración Nómina',
            'departamento_id' => $admId,
            'puesto' => 'Asistente Contable',
            'salario_base' => 12000.00,
            'fecha_ingreso' => '2026-02-01',
            'estado' => 'activo'
        ]);

        $codigoNomina = 'NOM-TEST-' . bin2hex(random_bytes(4));
        $empleadosData = [
            [
                'empleado_id' => $emp1Id,
                'salario_base' => 15000.00,
                'horas_extras_monto' => 0.00
            ],
            [
                'empleado_id' => $emp2Id,
                'salario_base' => 12000.00,
                'horas_extras_monto' => 2000.00 // Total devengado: 14,000.00
            ]
        ];

        $resultado = $this->modelo->procesarNomina(
            $codigoNomina,
            '2026-10-01',
            '2026-10-31',
            $empleadosData,
            1,
            0.225
        );

        $this->assertTrue($resultado['ok']);
        $this->assertGreaterThan(0, $resultado['nomina_id']);
        $this->assertNotNull($resultado['id_asiento'], "La nómina debe generar un asiento contable.");

        // Validar totales de la nómina
        $nomina = $this->modelo->obtenerNominaPorId($resultado['nomina_id']);
        $this->assertNotNull($nomina);
        $this->assertEquals(29000.00, (float)$nomina['total_devengado']); // 15000 + 14000
        $this->assertEquals('procesada', $nomina['estado']);

        // Validar detalle empleado 1
        $detalles = $nomina['detalles'];
        $det1 = array_values(array_filter($detalles, fn($d) => (int)$d['empleado_id'] === $emp1Id))[0];
        $this->assertEquals(15000.00, (float)$det1['total_devengado']);
        $this->assertEquals(1050.00, (float)$det1['inss_laboral']); // 15000 * 0.07
        $this->assertEquals(842.50, (float)$det1['ir_laboral']);
        $this->assertEquals(1000.00, (float)$det1['anticipos_descontados']);
        $this->assertEquals(2892.50, (float)$det1['total_deducciones']); // 1050 + 842.50 + 1000
        $this->assertEquals(12107.50, (float)$det1['neto_a_pagar']); // 15000 - 2892.50

        // Patronales y Provisiones Empleado 1
        $this->assertEquals(3375.00, (float)$det1['inss_patronal']); // 15000 * 0.225
        $this->assertEquals(300.00, (float)$det1['inatec_patronal']); // 15000 * 0.02
        $this->assertEquals(1249.50, (float)$det1['vacaciones_provision']); // 15000 * 0.0833
        $this->assertEquals(1249.50, (float)$det1['aguinaldo_provision']);
        $this->assertEquals(1249.50, (float)$det1['indemnizacion_provision']);

        // Validar detalle empleado 2
        $det2 = array_values(array_filter($detalles, fn($d) => (int)$d['empleado_id'] === $emp2Id))[0];
        $this->assertEquals(14000.00, (float)$det2['total_devengado']);
        $this->assertEquals(980.00, (float)$det2['inss_laboral']); // 14000 * 0.07
        $this->assertEquals(0.00, (float)$det2['anticipos_descontados']);
        $this->assertEquals(3150.00, (float)$det2['inss_patronal']); // 14000 * 0.225
        $this->assertEquals(280.00, (float)$det2['inatec_patronal']); // 14000 * 0.02

        // Validar que el anticipo de emp1 haya pasado a 'descontado_total' y saldo 0.00
        $anticiposEmp1 = $this->modelo->listarAnticipos($emp1Id);
        $this->assertEquals(0.00, (float)$anticiposEmp1[0]['saldo']);
        $this->assertEquals('descontado_total', $anticiposEmp1[0]['estado']);
    }

    /**
     * Valida el balance exacto en partida doble (SUM(Debe) == SUM(Haber)) del asiento contable de nómina.
     */
    public function testBalanceExactoDebeHaberEnAsientoContableNomina(): void {
        $deptos = $this->modelo->listarDepartamentos();
        $labId = array_values(array_filter($deptos, fn($d) => $d['codigo'] === 'LAB'))[0]['id'];
        $admId = array_values(array_filter($deptos, fn($d) => $d['codigo'] === 'ADM'))[0]['id'];

        $emp1 = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-B1-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Balance Lab',
            'departamento_id' => $labId,
            'puesto' => 'Ingeniero de Suelos',
            'salario_base' => 20000.00,
            'fecha_ingreso' => '2026-01-01',
            'estado' => 'activo'
        ]);

        $emp2 = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-B2-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Balance Adm',
            'departamento_id' => $admId,
            'puesto' => 'Administrador',
            'salario_base' => 16500.00,
            'fecha_ingreso' => '2026-01-01',
            'estado' => 'activo'
        ]);

        // Registrar anticipo parcial
        $this->modelo->registrarAnticipo($emp1, 3500.00, '2026-10-10', 'Anticipo quincena');

        $codigoNomina = 'NOM-BAL-' . bin2hex(random_bytes(4));
        $res = $this->modelo->procesarNomina($codigoNomina, '2026-10-01', '2026-10-31', [
            ['empleado_id' => $emp1, 'salario_base' => 20000.00, 'horas_extras_monto' => 1500.00],
            ['empleado_id' => $emp2, 'salario_base' => 16500.00, 'horas_extras_monto' => 0.00]
        ], 1);

        $this->assertTrue($res['ok']);
        $idAsiento = $res['id_asiento'];
        $this->assertNotEmpty($idAsiento, "El asiento contable de nómina no puede ser nulo.");

        // Consultar líneas contables del asiento generado
        $stmtPartida = $this->db->prepare("
            SELECT p.num_partida, p.concepto, p.origen,
                   SUM(pd.debe) AS total_debe,
                   SUM(pd.haber) AS total_haber,
                   COUNT(pd.id) AS total_lineas
            FROM partidas_diario p
            INNER JOIN partidas_diario_detalles pd ON p.id = pd.id_partida
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtPartida->execute(['id' => $idAsiento]);
        $partida = $stmtPartida->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($partida, "El asiento contable debe existir en la base de datos.");
        $this->assertEquals('NOMINA', $partida['origen']);
        $this->assertGreaterThanOrEqual(4, (int)$partida['total_lineas'], "Debe contener múltiples cuentas contables.");

        $totalDebe = (float)$partida['total_debe'];
        $totalHaber = (float)$partida['total_haber'];

        $this->assertGreaterThan(0, $totalDebe, "El total Debe debe ser positivo.");
        $this->assertGreaterThan(0, $totalHaber, "El total Haber debe ser positivo.");

        // Verificación estricta de partida doble: SUM(Debe) == SUM(Haber)
        $diferencia = abs($totalDebe - $totalHaber);
        $this->assertLessThan(
            0.001,
            $diferencia,
            "El asiento contable de nómina está descuadrado: Debe={$totalDebe}, Haber={$totalHaber}, Dif={$diferencia}"
        );
    }

    /**
     * Valida que al anular una nómina se restauren los saldos de anticipos descontados.
     */
    public function testAnulacionNominaRestauraSaldosAnticipos(): void {
        $deptos = $this->modelo->listarDepartamentos();
        $labId = $deptos[0]['id'];

        $empId = $this->modelo->guardarEmpleado([
            'codigo' => 'EMP-REV-' . bin2hex(random_bytes(3)),
            'nombre' => 'Colaborador Reversion Test',
            'departamento_id' => $labId,
            'puesto' => 'Técnico',
            'salario_base' => 10000.00,
            'fecha_ingreso' => '2026-01-01',
            'estado' => 'activo'
        ]);

        $antId = $this->modelo->registrarAnticipo($empId, 1200.00, date('Y-m-d'), 'Anticipo a revertir');

        $cod = 'NOM-REV-' . bin2hex(random_bytes(4));
        $res = $this->modelo->procesarNomina($cod, '2026-10-01', '2026-10-31', [
            ['empleado_id' => $empId, 'salario_base' => 10000.00]
        ], 1);

        $this->assertTrue($res['ok']);

        // Verificar que anticipo se descontó
        $antDescontado = $this->modelo->listarAnticipos($empId)[0];
        $this->assertEquals('descontado_total', $antDescontado['estado']);
        $this->assertEquals(0.00, (float)$antDescontado['saldo']);

        // Anular nómina
        $anulada = $this->modelo->anularNomina($res['nomina_id'], 1);
        $this->assertTrue($anulada);

        $nomAnulada = $this->modelo->obtenerNominaPorId($res['nomina_id']);
        $this->assertEquals('anulada', $nomAnulada['estado']);

        // El anticipo debe haberse restaurado a 1,200.00 y estado 'pendiente'
        $antRestaurado = $this->modelo->listarAnticipos($empId)[0];
        $this->assertEquals(1200.00, (float)$antRestaurado['saldo']);
        $this->assertEquals('pendiente', $antRestaurado['estado']);
    }
}
