<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;

class MultiplesHojasServicioTest extends TestCase {

    private PDO $db;
    private OperacionModelo $opModelo;
    private OrdenServicioModelo $osModelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->opModelo = new OperacionModelo();
        $this->osModelo = new OrdenServicioModelo();
    }

    public function testCrearYActualizarMultiplesHojasEnMismaOS(): void {
        // 1. Obtener una Orden de Servicio de prueba
        $stmt = $this->db->query("SELECT os.id, os.codigo_os, c.nombre_razon_social as cliente_nombre FROM ordenes_servicio os LEFT JOIN clientes c ON c.id = os.id_cliente ORDER BY os.id ASC LIMIT 1");
        $os = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$os) {
            $this->markTestSkipped('No hay órdenes de servicio en la base de datos para probar.');
        }

        $idOS = (int)$os['id'];

        // Contar cuántas hojas existen antes
        $hojasPrevias = $this->opModelo->obtenerHojasSolicitudPorOS($idOS);
        $totalPrevias = count($hojasPrevias);

        // 2. Crear Primera Hoja (Entrega 1)
        $datosHoja1 = [
            'id_os' => $idOS,
            'id_hoja' => 0, // Nueva
            'numero_registro' => 'TEST-REG-01',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'nombre_persona_entrega_muestra' => 'Ing. Entrega 1',
            'naturaleza_muestra' => 'Concreto',
            'procedencia_punto_muestreo' => 'Sector Norte - Entrega Inicial',
            'nombre_persona_toma_muestra' => 'Tecnico Campo 1',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0001-26', 'descripcion' => 'Cilindro Concreto 1', 'info_importante' => 'Zapata 1']
            ]),
            'req_resistencia_concreto' => 1
        ];

        $idHoja1 = 0;
        $creada1 = $this->opModelo->guardarHojaSolicitud($datosHoja1, $idHoja1);
        $this->assertTrue($creada1, 'La primera hoja debe crearse exitosamente.');
        $this->assertGreaterThan(0, $idHoja1, 'El ID de la primera hoja generada debe ser mayor a cero.');

        // 3. Crear Segunda Hoja (Entrega 2 para proyecto de meses / contrato activo)
        $datosHoja2 = [
            'id_os' => $idOS,
            'id_hoja' => 0, // Nueva
            'numero_registro' => 'TEST-REG-02',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'nombre_persona_entrega_muestra' => 'Ing. Entrega 2',
            'naturaleza_muestra' => 'Suelo',
            'procedencia_punto_muestreo' => 'Sector Sur - Entrega Mensual 2',
            'nombre_persona_toma_muestra' => 'Tecnico Campo 2',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0002-26', 'descripcion' => 'Muestra de Suelo Pozo 2', 'info_importante' => 'Estrato 2m'],
                ['nombre_muestra' => 'MS-0003-26', 'descripcion' => 'Muestra de Suelo Pozo 3', 'info_importante' => 'Estrato 3m']
            ]),
            'req_granulometria' => 1,
            'req_humedad' => 1
        ];

        $idHoja2 = 0;
        $creada2 = $this->opModelo->guardarHojaSolicitud($datosHoja2, $idHoja2);
        $this->assertTrue($creada2, 'La segunda hoja de la misma O/S debe crearse exitosamente.');
        $this->assertGreaterThan(0, $idHoja2);
        $this->assertNotEquals($idHoja1, $idHoja2, 'Cada hoja debe tener su propio ID único.');

        // 4. Verificar obtenerHojasSolicitudPorOS devuelve ambas
        $hojasTodas = $this->opModelo->obtenerHojasSolicitudPorOS($idOS);
        $this->assertGreaterThanOrEqual($totalPrevias + 2, count($hojasTodas), 'Debe contener al menos las 2 hojas creadas.');

        // 5. Verificar obtenerHojaSolicitudPorId independiente
        $h1Recuperada = $this->opModelo->obtenerHojaSolicitudPorId($idHoja1);
        $this->assertNotNull($h1Recuperada);
        $this->assertEquals('Ing. Entrega 1', $h1Recuperada['nombre_persona_entrega_muestra']);
        $this->assertEquals('Sector Norte - Entrega Inicial', $h1Recuperada['procedencia_punto_muestreo']);

        $h2Recuperada = $this->opModelo->obtenerHojaSolicitudPorId($idHoja2);
        $this->assertNotNull($h2Recuperada);
        $this->assertEquals('Ing. Entrega 2', $h2Recuperada['nombre_persona_entrega_muestra']);
        $this->assertEquals('Sector Sur - Entrega Mensual 2', $h2Recuperada['procedencia_punto_muestreo']);

        // 6. Probar modificación puntual de la Hoja 1 sin afectar la Hoja 2
        $datosUpdate1 = $h1Recuperada;
        $datosUpdate1['id_hoja'] = $idHoja1;
        $datosUpdate1['nombre_persona_entrega_muestra'] = 'Ing. Entrega 1 MODIFICADO';
        $datosUpdate1['req_resistencia_adoquin'] = 1;

        $idGeneradoUpdate = 0;
        $actualizado1 = $this->opModelo->guardarHojaSolicitud($datosUpdate1, $idGeneradoUpdate);
        $this->assertTrue($actualizado1);
        $this->assertEquals($idHoja1, $idGeneradoUpdate);

        // Comprobar que Hoja 1 cambió
        $h1Verif = $this->opModelo->obtenerHojaSolicitudPorId($idHoja1);
        $this->assertEquals('Ing. Entrega 1 MODIFICADO', $h1Verif['nombre_persona_entrega_muestra']);

        // Comprobar que Hoja 2 NO fue alterada
        $h2Verif = $this->opModelo->obtenerHojaSolicitudPorId($idHoja2);
        $this->assertEquals('Ing. Entrega 2', $h2Verif['nombre_persona_entrega_muestra']);

        // 7. Probar deduplicación en OrdenServicioModelo::obtenerTodas()
        $todasOS = $this->osModelo->obtenerTodas();
        $conteoOcurrenciasOS = 0;
        $itemOS = null;
        foreach ($todasOS as $filaOS) {
            if ((int)$filaOS['id'] === $idOS) {
                $conteoOcurrenciasOS++;
                $itemOS = $filaOS;
            }
        }
        $this->assertEquals(1, $conteoOcurrenciasOS, 'La O/S debe aparecer exactamente una sola vez en la lista de obtenerTodas(), sin duplicarse por tener múltiples hojas.');
        $this->assertNotNull($itemOS);
        $this->assertArrayHasKey('hojas_servicio', $itemOS);
        $this->assertGreaterThanOrEqual(2, count($itemOS['hojas_servicio']));

        // Limpieza de prueba
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$idHoja1}, {$idHoja2})");
    }

    public function testTipoContratoEnOrdenServicio(): void {
        // Verificar que la columna tipo_contrato exista y acepte valores
        $stmt = $this->db->query("SHOW COLUMNS FROM ordenes_servicio LIKE 'tipo_contrato'");
        $columna = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($columna, 'La columna tipo_contrato debe existir en la tabla ordenes_servicio.');

        // Actualizar una orden a 'Marco / Contrato Activo'
        $stmtOS = $this->db->query("SELECT id FROM ordenes_servicio LIMIT 1");
        $idOS = (int)$stmtOS->fetchColumn();

        if ($idOS > 0) {
            $stmtUpdate = $this->db->prepare("UPDATE ordenes_servicio SET tipo_contrato = 'Marco / Contrato Activo' WHERE id = :id");
            $res = $stmtUpdate->execute(['id' => $idOS]);
            $this->assertTrue($res);

            $stmtCheck = $this->db->prepare("SELECT tipo_contrato FROM ordenes_servicio WHERE id = :id");
            $stmtCheck->execute(['id' => $idOS]);
            $this->assertEquals('Marco / Contrato Activo', $stmtCheck->fetchColumn());
        }
    }
}
