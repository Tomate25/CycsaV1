<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use PDO;

class RtFm61CilindrosTest extends TestCase {

    private MotorHojasCalculoServicio $motor;
    private PDO $db;
    private ?int $clienteId = null;
    private ?int $osId = null;
    private ?int $recepcionId = null;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
        $this->db = Conexion::obtenerInstancia();
    }

    protected function tearDown(): void {
        if ($this->recepcionId) {
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = :id)")->execute(['id' => $this->recepcionId]);
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
     * Valida todos los casos de prueba oficiales en RT-FM-61.json con tolerancia relativa del 0.1% (0.001)
     * utilizando EvaluadorFormula real a través de MotorHojasCalculoServicio.
     */
    public function testCasosPruebaOficialesRtFm61(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-61');
        $this->assertNotEmpty($spec['caso_prueba'], 'RT-FM-61 debe contener casos de prueba');

        foreach ($spec['caso_prueba'] as $caso) {
            $nombreCaso = $caso['nombre'];
            $tolRel = (float)($caso['tolerancia_relativa'] ?? 0.001);
            $resultado = $this->motor->evaluarEspecimen('RT-FM-61', $caso['entradas']);
            $valores = $resultado['valores'];

            foreach ($caso['esperados'] as $variable => $esperado) {
                $this->assertArrayHasKey($variable, $valores, "Variable '{$variable}' no fue calculada en caso '{$nombreCaso}'");
                $obtenido = (float)$valores[$variable];
                $espFloat = (float)$esperado;
                $deltaPermitido = max(abs($espFloat) * $tolRel, 1e-6);

                $this->assertEqualsWithDelta(
                    $espFloat,
                    $obtenido,
                    $deltaPermitido,
                    "Fallo en RT-FM-61 caso '{$nombreCaso}' variable '{$variable}': esperado {$espFloat}, obtenido {$obtenido}"
                );
            }
        }
    }

    /**
     * Verifica la parametrización de la corrección dudosa FM61-01 (aplicación de factor L/D cuando L/D < 1.75).
     */
    public function testParametrizacionCorreccionDudosaFactorLdEnRtFm61(): void {
        $entradasEsbelto = [
            'diametro_1' => 10.0,
            'diametro_2' => 10.0,
            'largo_1' => 15.0, // L/D = 1.50 < 1.75
            'largo_2' => 15.0,
            'masa_kg' => 2.8,
            'lectura_carga_lbf' => 50000
        ];

        // 1. Con corrección FM61-01 activa (por defecto): factor_ld < 1.0 (~0.96)
        $evalCorregido = $this->motor->evaluarEspecimen('RT-FM-61', $entradasEsbelto, [
            'correcciones_override' => ['FM61-01' => true]
        ]);
        $this->assertEqualsWithDelta(1.5, $evalCorregido['valores']['relacion_ld'], 0.001);
        $this->assertEqualsWithDelta(0.96, $evalCorregido['valores']['factor_ld'], 0.005);

        // 2. Con corrección FM61-01 desactivada (modo original Excel): factor_ld = 1.0
        $evalOriginalExcel = $this->motor->evaluarEspecimen('RT-FM-61', $entradasEsbelto, [
            'correcciones_override' => ['FM61-01' => false]
        ]);
        $this->assertEqualsWithDelta(1.0, $evalOriginalExcel['valores']['factor_ld'], 1e-6);
        $this->assertGreaterThan($evalCorregido['valores']['esfuerzo_psi'], $evalOriginalExcel['valores']['esfuerzo_psi']);
    }

    /**
     * Verifica la integración de RT-FM-61 con RoturasModelo::registrarRotura guardando datos_crudos
     * y aplicando la ecuación de la prensa PRENSA-01 y el factor L/D.
     */
    public function testIntegracionRtFm61ConRoturasModeloRegistrarRotura(): void {
        $suffix = bin2hex(random_bytes(4));
        $codigoLab = 'MS-61-' . $suffix;

        $this->db->prepare("
            INSERT INTO clientes (nombre_razon_social, identificacion, numero_ruc, email, telefono, direccion)
            VALUES ('Cliente Prueba FM61', 'J088888888', 'J088888888', :email, '2222-1111', 'Managua')
        ")->execute(['email' => "fm61_{$suffix}@test.com"]);
        $this->clienteId = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, nombre_proyecto, atencion_a, estado)
            VALUES (:cos, 1, :cid, CURDATE(), 'Proyecto Cilindros FM61', 'Residente', 'Estado 4: Ingreso Laboratorio')
        ")->execute(['cos' => 'OS-61-' . $suffix, 'cid' => $this->clienteId]);
        $this->osId = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado)
            VALUES (:os_id, 9610, 2026, 'Laboratorio', :cod, 'C-61-01', NOW(), 1, 'Registrado')
        ")->execute(['os_id' => $this->osId, 'cod' => $codigoLab]);
        $this->recepcionId = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, temperatura_c, procedimiento_muestreo)
            VALUES (:rec_id, 'Concreto 6000 PSI', '6000 PSI', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 5.7, 27.0, 'ASTM C31')
        ")->execute(['rec_id' => $this->recepcionId]);

        $roturasModelo = new RoturasModelo();
        $fechaMoldeo = date('Y-m-d', strtotime('-3 days'));
        $roturasModelo->crearEspecimenesParaMuestra($this->recepcionId, 1, $fechaMoldeo, 'cilindro_concreto', '10 x 20 cm');
        $roturasModelo->guardarPlanRotura($this->recepcionId, null, [['edad_dias' => 3, 'cantidad' => 1]], 1);

        $detalle = $roturasModelo->obtenerDetalleMuestraAnonima($this->recepcionId);
        $esp = $detalle['especimenes'][0];

        // Registrar rotura enviando lecturas crudas del caso MS-0213-26 (RT-FM-61)
        $res = $roturasModelo->registrarRotura([
            'especimen_id' => $esp['id'],
            'plan_edad_id' => $esp['plan_edad_id'],
            'fecha_real' => $esp['fecha_programada'] . ' 09:00:00',
            'tipo_falla' => 'Tipo 1 (Cono)',
            'datos_crudos' => [
                'codigo_formato' => 'RT-FM-61',
                'revenimiento_cm' => 14.5,
                'diametro_1' => 10.1,
                'diametro_2' => 10.2,
                'largo_1' => 20.2,
                'largo_2' => 20.2,
                'masa_kg' => 4.02,
                'lectura_carga_lbf' => 91932
            ]
        ], 1);

        $this->assertTrue($res['exito'], $res['mensaje'] ?? '');
        $this->assertEqualsWithDelta(91727.92, $res['carga'], 1.0);
        $this->assertEqualsWithDelta(7310.0, $res['esfuerzo_psi'], 5.0);
        $this->assertNotEmpty($res['datos_crudos']);

        $crudosGuardados = json_decode($res['datos_crudos'], true);
        $this->assertEquals('RT-FM-61', $crudosGuardados['codigo_formato']);
        $this->assertEqualsWithDelta(2459.71, $crudosGuardados['calculados_hc']['densidad'], 1.0);
    }
}
