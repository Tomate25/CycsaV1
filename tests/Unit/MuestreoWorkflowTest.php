<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;

class MuestreoWorkflowTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->osModelo = new OrdenServicioModelo();
    }

    public function testMarcarRequiereMuestreo(): void {
        // Buscar una orden existente (por ejemplo ID 1)
        $stmt = $this->db->query("SELECT id FROM ordenes_servicio LIMIT 1");
        $idOS = (int)$stmt->fetchColumn();

        if ($idOS > 0) {
            $res = $this->osModelo->marcarRequiereMuestreo($idOS);
            $this->assertTrue($res);

            $stmtCheck = $this->db->prepare("SELECT requiere_muestreo FROM ordenes_servicio WHERE id = :id");
            $stmtCheck->execute(['id' => $idOS]);
            $this->assertEquals(1, (int)$stmtCheck->fetchColumn());
        } else {
            $this->markTestSkipped('No hay órdenes de servicio en la base de datos de prueba.');
        }
    }

    public function testGuardarProgramacionMuestreoConFinalizar(): void {
        $stmt = $this->db->query("SELECT id FROM ordenes_servicio LIMIT 1");
        $idOS = (int)$stmt->fetchColumn();

        if ($idOS <= 0) {
            $this->markTestSkipped('No hay órdenes de servicio para probar.');
        }

        // Obtener IDs de técnicos y vehículos existentes
        $tecnicos = $this->osModelo->obtenerTecnicos();
        $vehiculos = $this->osModelo->obtenerVehiculos();
        $idTec = !empty($tecnicos) ? (int)$tecnicos[0]['id'] : 1;
        $idVeh = !empty($vehiculos) ? (int)$vehiculos[0]['id'] : 1;

        $datosPrueba = [
            'fecha_ida' => '2026-09-15 08:00:00',
            'fecha_llegada' => '2026-09-15 12:00:00',
            'id_tecnico' => $idTec,
            'id_vehiculo' => $idVeh,
            'lugar_muestreo' => 'Obra Carretera Central Km 15',
            'cantidad_muestras_est' => '4 cilindros',
            'num_muestreadores' => 2,
            'observaciones_campo' => 'Muestras tomadas según norma ASTM C31',
            'checklist_json' => json_encode(['equipo_cono' => '1', 'post_entrega_firma' => '1']),
            'estado_muestreo' => 'Finalizado'
        ];

        $guardado = $this->osModelo->guardarProgramacionMuestreo($idOS, $datosPrueba);
        $this->assertTrue($guardado);

        // Verificar en programacion_muestreo
        $stmtPm = $this->db->prepare("SELECT * FROM programacion_muestreo WHERE id_orden_servicio = :id_os");
        $stmtPm->execute(['id_os' => $idOS]);
        $pm = $stmtPm->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($pm);
        $this->assertEquals('Finalizado', $pm['estado_muestreo']);
        $this->assertNotNull($pm['fecha_finalizacion']);
        $this->assertEquals('Obra Carretera Central Km 15', $pm['lugar_muestreo']);
        $this->assertEquals($idTec, (int)$pm['id_tecnico']);

        // Verificar en ordenes_servicio
        $stmtOs = $this->db->prepare("SELECT requiere_muestreo, estado FROM ordenes_servicio WHERE id = :id");
        $stmtOs->execute(['id' => $idOS]);
        $os = $stmtOs->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, (int)$os['requiere_muestreo']);
        $this->assertEquals('Estado 1: Recepcion', $os['estado']);
    }

    public function testObtenerTodasIncluyeDatosMuestreoFinalizado(): void {
        $ordenes = $this->osModelo->obtenerTodas();
        $this->assertIsArray($ordenes);
        $this->assertNotEmpty($ordenes);

        $osEncontrada = null;
        foreach ($ordenes as $o) {
            if (!empty($o['id_pm']) && $o['estado_muestreo'] === 'Finalizado') {
                $osEncontrada = $o;
                break;
            }
        }

        $this->assertNotNull($osEncontrada, 'Debe existir una orden con muestreo Finalizado.');
        $this->assertArrayHasKey('id_pm', $osEncontrada);
        $this->assertArrayHasKey('lugar_muestreo', $osEncontrada);
        $this->assertArrayHasKey('tecnico_nombre', $osEncontrada);
        $this->assertArrayHasKey('vehiculo_info', $osEncontrada);
        $this->assertEquals('Finalizado', $osEncontrada['estado_muestreo']);
    }
}
