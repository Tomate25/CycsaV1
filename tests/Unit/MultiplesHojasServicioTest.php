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
    private array $secuenciasBackup = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->opModelo = new OperacionModelo();
        $this->osModelo = new OrdenServicioModelo();
        $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function tearDown(): void {
        if (!empty($this->secuenciasBackup)) {
            $this->db->exec("DELETE FROM secuencias_muestras");
            $stmtInsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr)");
            foreach ($this->secuenciasBackup as $row) {
                $stmtInsert->execute(['anio' => $row['anio'], 'tipo' => $row['tipo_muestra'], 'corr' => $row['ultimo_correlativo']]);
            }
        }
        parent::tearDown();
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

    public function testConcurrenciaAsignacionMuestrasSinDuplicados(): void {
        $stmtOS = $this->db->query("SELECT os.id, os.codigo_os, c.nombre_razon_social as cliente_nombre FROM ordenes_servicio os LEFT JOIN clientes c ON c.id = os.id_cliente LIMIT 1");
        $os = $stmtOS->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($os);
        $idOS = (int)$os['id'];

        // Simular dos usuarios A y B guardando simultáneamente 3 muestras cada uno
        // Ambos formularios frontend envían los mismos nombres sugeridos: 'MC-0001-26', 'MC-0002-26', 'MC-0003-26'
        $datosUsuarioA = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'numero_registro' => 'CONCURR-A',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente A',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0001-26', 'descripcion' => 'Muestra Concurrente A1'],
                ['nombre_muestra' => 'MC-0002-26', 'descripcion' => 'Muestra Concurrente A2'],
                ['nombre_muestra' => 'MC-0003-26', 'descripcion' => 'Muestra Concurrente A3'],
            ])
        ];

        $datosUsuarioB = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'numero_registro' => 'CONCURR-B',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente B',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-0001-26', 'descripcion' => 'Muestra Concurrente B1'],
                ['nombre_muestra' => 'MC-0002-26', 'descripcion' => 'Muestra Concurrente B2'],
                ['nombre_muestra' => 'MC-0003-26', 'descripcion' => 'Muestra Concurrente B3'],
            ])
        ];

        $idHojaA = 0;
        $idHojaB = 0;

        $okA = $this->opModelo->guardarHojaSolicitud($datosUsuarioA, $idHojaA);
        $okB = $this->opModelo->guardarHojaSolicitud($datosUsuarioB, $idHojaB);

        $this->assertTrue($okA, 'Hoja de Usuario A debe guardarse exitosamente.');
        $this->assertTrue($okB, 'Hoja de Usuario B debe guardarse exitosamente.');

        $hojaA = $this->opModelo->obtenerHojaSolicitudPorId($idHojaA);
        $hojaB = $this->opModelo->obtenerHojaSolicitudPorId($idHojaB);

        $muestrasA = json_decode($hojaA['muestras_json'], true) ?: [];
        $muestrasB = json_decode($hojaB['muestras_json'], true) ?: [];

        $nombresA = array_column($muestrasA, 'nombre_muestra');
        $nombresB = array_column($muestrasB, 'nombre_muestra');

        $this->assertCount(3, $nombresA);
        $this->assertCount(3, $nombresB);

        // Ningún código de muestra de B puede estar en A (Cero duplicados)
        $interseccion = array_intersect($nombresA, $nombresB);
        $this->assertEmpty($interseccion, 'No deben existir códigos de muestras repetidos entre hojas concurrentes.');

        // Limpieza de prueba
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$idHojaA}, {$idHojaB})");
    }

    public function testConsecutivoGlobalSinColisionEntrePrefijosMCyMS(): void {
        $stmtOS = $this->db->query("SELECT os.id, os.codigo_os, c.nombre_razon_social as cliente_nombre FROM ordenes_servicio os LEFT JOIN clientes c ON c.id = os.id_cliente LIMIT 1");
        $os = $stmtOS->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($os);
        $idOS = (int)$os['id'];

        $anio = (int)date('Y');
        $anio2D = date('y');

        // Obtener siguiente correlativo disponible
        $siguienteInicial = $this->opModelo->obtenerSiguienteConsecutivoMuestra($anio, 'MS');
        $this->assertGreaterThan(0, $siguienteInicial);

        // Crear una hoja con prefijo MS intentando usar números ya existentes si los hubiera
        $datos = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'numero_registro' => 'TEST-COLLISION-CHECK',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente Global Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MS-0001-' . $anio2D, 'descripcion' => 'Muestra 1'],
                ['nombre_muestra' => 'MS-0002-' . $anio2D, 'descripcion' => 'Muestra 2']
            ])
        ];

        $idHoja = 0;
        $ok = $this->opModelo->guardarHojaSolicitud($datos, $idHoja);
        $this->assertTrue($ok);
        $this->assertGreaterThan(0, $idHoja);

        $hoja = $this->opModelo->obtenerHojaSolicitudPorId($idHoja);
        $muestras = json_decode($hoja['muestras_json'], true) ?: [];
        $this->assertCount(2, $muestras);

        // Los números asignados deben ser >= $siguienteInicial y distintos entre sí
        $numerosAsignados = [];
        foreach ($muestras as $m) {
            if (preg_match('/^[A-Za-z]+-(\d+)-/', $m['nombre_muestra'], $match)) {
                $num = (int)$match[1];
                $this->assertGreaterThanOrEqual($siguienteInicial, $num, "El número asignado ($num) debe ser mayor o igual al consecutivo disponible ($siguienteInicial)");
                $numerosAsignados[] = $num;
            }
        }
        $this->assertCount(2, array_unique($numerosAsignados), 'No debe haber números duplicados dentro de la hoja guardada');

        // Limpieza de prueba
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id = {$idHoja}");
    }

    public function testAprobacionIndependienteMultiplesHojasEnLaboratorio(): void {
        $stmtOS = $this->db->query("SELECT os.id, os.codigo_os, os.id_cotizacion, c.nombre_razon_social as cliente_nombre FROM ordenes_servicio os LEFT JOIN clientes c ON c.id = os.id_cliente ORDER BY os.id ASC LIMIT 1");
        $os = $stmtOS->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($os, 'Debe existir al menos una O/S para la prueba.');
        $idOS = (int)$os['id'];
        $idCotizacion = (int)$os['id_cotizacion'];

        $servicios = $this->opModelo->obtenerDetallesCotizacion($idCotizacion);
        $idDetalleCot = !empty($servicios) ? (int)$servicios[0]['id'] : 1;

        // 1. Crear dos hojas RT-FM-13 para la misma O/S
        $datosHoja1 = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'numero_registro' => 'LAB-ENTREGA-1',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'naturaleza_muestra' => 'Concreto F\'c=280',
            'procedencia_punto_muestreo' => 'Lote 1 - Pavimento',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-TEST-01', 'descripcion' => 'Cilindro 1'],
                ['nombre_muestra' => 'MC-TEST-02', 'descripcion' => 'Cilindro 2'],
                ['nombre_muestra' => 'MC-TEST-03', 'descripcion' => 'Cilindro 3']
            ])
        ];
        $idHoja1 = 0;
        $this->opModelo->guardarHojaSolicitud($datosHoja1, $idHoja1);
        $this->assertGreaterThan(0, $idHoja1);

        $datosHoja2 = [
            'id_os' => $idOS,
            'id_hoja' => 0,
            'numero_registro' => 'LAB-ENTREGA-2',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? 'Cliente Test',
            'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i:s'),
            'naturaleza_muestra' => 'Concreto F\'c=350',
            'procedencia_punto_muestreo' => 'Lote 2 - Columnas',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => 'MC-TEST-04', 'descripcion' => 'Cilindro 4'],
                ['nombre_muestra' => 'MC-TEST-05', 'descripcion' => 'Cilindro 5']
            ])
        ];
        $idHoja2 = 0;
        $this->opModelo->guardarHojaSolicitud($datosHoja2, $idHoja2);
        $this->assertGreaterThan(0, $idHoja2);

        // 2. Query de laboratorio con filtro por id_hoja
        $sqlCheck = "SELECT hs.id AS id_hoja, hs.numero_registro,
                            (CASE 
                                WHEN hs.id IS NOT NULL THEN (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = {$idOS} AND (rm.id_hoja = hs.id OR (rm.id_hoja IS NULL AND hs.id = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = {$idOS}))))
                                ELSE (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = {$idOS})
                             END) AS total_recibidas_lab
                     FROM hojas_solicitud hs
                     WHERE hs.id IN (:h1, :h2)
                     ORDER BY hs.id ASC";
        $stmtC = $this->db->prepare($sqlCheck);
        $stmtC->execute(['h1' => $idHoja1, 'h2' => $idHoja2]);
        $filas = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $filas);
        $this->assertEquals(0, (int)$filas[0]['total_recibidas_lab'], 'Hoja 1 debe iniciar con 0 recibidas en lab');
        $this->assertEquals(0, (int)$filas[1]['total_recibidas_lab'], 'Hoja 2 debe iniciar con 0 recibidas en lab');

        // 3. Aprobar y recepcionar únicamente la Hoja 1
        $payload1 = [
            'id_os' => $idOS,
            'id_hoja' => $idHoja1,
            'limpiar_previas' => false,
            'id_detalle_cotizacion' => $idDetalleCot,
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => 'Laboratorista Test',
            'entregado_por' => 'Transporte 1',
            'observaciones' => 'Entrega 1 conforme',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $idDetalleCot,
                    'tipo_muestra' => 'Campo',
                    'id_cilindro' => 'MC-TEST-01',
                    'codigo_campo' => 'MC-TEST-01',
                    'nombre_lote' => 'Lote 1',
                    'fecha_moldeo' => date('Y-m-d')
                ],
                [
                    'id_detalle_cotizacion' => $idDetalleCot,
                    'tipo_muestra' => 'Campo',
                    'id_cilindro' => 'MC-TEST-02',
                    'codigo_campo' => 'MC-TEST-02',
                    'nombre_lote' => 'Lote 1',
                    'fecha_moldeo' => date('Y-m-d')
                ]
            ])
        ];
        $okRec1 = $this->opModelo->registrarRecepcion($payload1);
        $this->assertTrue($okRec1, 'La recepción de la Hoja 1 debe guardarse exitosamente.');

        // Re-verificar estado en laboratorio
        $stmtC->execute(['h1' => $idHoja1, 'h2' => $idHoja2]);
        $filasDespues1 = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, (int)$filasDespues1[0]['total_recibidas_lab'], 'Hoja 1 ahora debe figurar como recibida/aprobada.');
        $this->assertEquals(0, (int)$filasDespues1[1]['total_recibidas_lab'], 'Hoja 2 DEBE PERMANECER con 0 recibidas (pendiente de aprobación en laboratorio).');

        // 4. Aprobar y recepcionar la Hoja 2
        $payload2 = [
            'id_os' => $idOS,
            'id_hoja' => $idHoja2,
            'limpiar_previas' => false,
            'id_detalle_cotizacion' => $idDetalleCot,
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => 'Laboratorista Test',
            'entregado_por' => 'Transporte 2',
            'observaciones' => 'Entrega 2 conforme',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => $idDetalleCot,
                    'tipo_muestra' => 'Campo',
                    'id_cilindro' => 'MC-TEST-04',
                    'codigo_campo' => 'MC-TEST-04',
                    'nombre_lote' => 'Lote 2',
                    'fecha_moldeo' => date('Y-m-d')
                ]
            ])
        ];
        $okRec2 = $this->opModelo->registrarRecepcion($payload2);
        $this->assertTrue($okRec2, 'La recepción de la Hoja 2 debe guardarse exitosamente sin afectar la Hoja 1.');

        // Verificar que ambas hojas están recibidas independientemente
        $stmtC->execute(['h1' => $idHoja1, 'h2' => $idHoja2]);
        $filasDespues2 = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, (int)$filasDespues2[0]['total_recibidas_lab'], 'Hoja 1 sigue estando recibida y no fue borrada.');
        $this->assertGreaterThan(0, (int)$filasDespues2[1]['total_recibidas_lab'], 'Hoja 2 ahora también está recibida.');

        // Verificar que recepcion_muestras tiene registros independientes con id_hoja correcto
        $stmtRM1 = $this->db->prepare("SELECT id, id_hoja, codigo_muestra FROM recepcion_muestras WHERE id_os = :id_os AND id_hoja = :h1");
        $stmtRM1->execute(['id_os' => $idOS, 'h1' => $idHoja1]);
        $rm1Rows = $stmtRM1->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($rm1Rows, 'Deben existir muestras recibidas asociadas a la Hoja 1.');

        $stmtRM2 = $this->db->prepare("SELECT id, id_hoja, codigo_muestra FROM recepcion_muestras WHERE id_os = :id_os AND id_hoja = :h2");
        $stmtRM2->execute(['id_os' => $idOS, 'h2' => $idHoja2]);
        $rm2Rows = $stmtRM2->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($rm2Rows, 'Deben existir muestras recibidas asociadas a la Hoja 2.');

        // Limpieza de prueba
        $allIdsRM = array_merge(array_column($rm1Rows, 'id'), array_column($rm2Rows, 'id'));
        if (!empty($allIdsRM)) {
            $inRM = implode(',', array_map('intval', $allIdsRM));
            $this->db->exec("DELETE FROM lotes_muestras WHERE id_recepcion IN ({$inRM})");
            $this->db->exec("DELETE FROM recepcion_muestras WHERE id IN ({$inRM})");
        }
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$idHoja1}, {$idHoja2})");
    }

    public function testReinicioConsecutivoAnualMuestrasMSyMC(): void {
        // 1. Simular año 2026 con correlativo avanzado (ej. 45 en Campo y 60 en Lab)
        $this->db->exec("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) 
                         VALUES (2026, 'Campo', 45), (2026, 'Laboratorio', 60)
                         ON DUPLICATE KEY UPDATE ultimo_correlativo = VALUES(ultimo_correlativo)");

        $sigMC2026 = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2026, 'MC');
        $sigMS2026 = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2026, 'MS');

        $this->assertEquals(46, $sigMC2026, 'En 2026 el siguiente de MC debe ser 46');
        $this->assertEquals(61, $sigMS2026, 'En 2026 el siguiente de MS debe ser 61');

        $codMC2026 = sprintf("MC-%04d-%02d", $sigMC2026, 26);
        $codMS2026 = sprintf("MS-%04d-%02d", $sigMS2026, 26);
        $this->assertEquals('MC-0046-26', $codMC2026);
        $this->assertEquals('MS-0061-26', $codMS2026);

        // 2. Simular cambio de año a 2027: DEBE REINICIAR DESDE 1 (0001) para MC y MS
        $sigMC2027 = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2027, 'MC');
        $sigMS2027 = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2027, 'MS');

        $this->assertEquals(1, $sigMC2027, 'Al iniciar 2027, MC debe reiniciar exactamente en 1');
        $this->assertEquals(1, $sigMS2027, 'Al iniciar 2027, MS debe reiniciar exactamente en 1');

        $codMC2027 = sprintf("MC-%04d-%02d", $sigMC2027, 27);
        $codMS2027 = sprintf("MS-%04d-%02d", $sigMS2027, 27);
        $this->assertEquals('MC-0001-27', $codMC2027, 'Formato oficial de inicio de año 2027 debe ser MC-0001-27');
        $this->assertEquals('MS-0001-27', $codMS2027, 'Formato oficial de inicio de año 2027 debe ser MS-0001-27');

        // 3. Simular recepción en año 2027
        $stmtOS = $this->db->query("SELECT id FROM ordenes_servicio LIMIT 1");
        $idOS = (int)$stmtOS->fetchColumn();

        $payload2027 = [
            'id_os' => $idOS,
            'limpiar_previas' => false,
            'fecha_recepcion' => '2027-01-05 09:00:00',
            'tipo_muestra' => 'Campo',
            'recibido_por' => 'Laboratorista 2027',
            'entregado_por' => 'Cliente',
            'muestras_recibidas_json' => json_encode([
                [
                    'id_detalle_cotizacion' => 1,
                    'tipo_muestra' => 'Campo',
                    'id_cilindro' => 'C-2027-01',
                    'codigo_campo' => 'C-2027-01',
                    'nombre_lote' => 'Lote 2027',
                    'fecha_moldeo' => '2027-01-05'
                ]
            ])
        ];
        $ok2027 = $this->opModelo->registrarRecepcion($payload2027);
        $this->assertTrue($ok2027);

        // Verificar código de muestra generado en recepcion_muestras para 2027
        $stmtRM2027 = $this->db->prepare("SELECT codigo_muestra, correlativo_anual, anio FROM recepcion_muestras WHERE anio = 2027 AND id_os = :id_os");
        $stmtRM2027->execute(['id_os' => $idOS]);
        $row2027 = $stmtRM2027->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row2027);
        $this->assertEquals('MC-0001-27', $row2027['codigo_muestra'], 'El primer código de muestra de 2027 debe ser MC-0001-27');
        $this->assertEquals(1, (int)$row2027['correlativo_anual']);
        $this->assertEquals(2027, (int)$row2027['anio']);

        // El siguiente consecutivo para 2027 ahora debe ser 2
        $sigMC2027_segundo = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2027, 'MC');
        $this->assertEquals(2, $sigMC2027_segundo);

        // Pero 2026 NO debe haber sido alterado
        $sigMC2026_recheck = $this->opModelo->obtenerSiguienteConsecutivoMuestra(2026, 'MC');
        $this->assertEquals(46, $sigMC2026_recheck, 'El correlativo de 2026 no debe alterarse por registros de 2027');

        // Limpieza de datos temporales del test
        $this->db->exec("DELETE FROM lotes_muestras WHERE id_recepcion IN (SELECT id FROM recepcion_muestras WHERE anio = 2027)");
        $this->db->exec("DELETE FROM recepcion_muestras WHERE anio = 2027");
        $this->db->exec("DELETE FROM secuencias_muestras WHERE anio = 2027");
    }

    public function testBlindajeConsecutivoGlobalMuestrasSinDuplicadosEntreClientes(): void {
        $anio = (int)date('Y');
        $anioShort = date('y');

        // 1. Obtener dos órdenes de servicio distintas
        $stmtOS = $this->db->query("SELECT id FROM ordenes_servicio ORDER BY id ASC LIMIT 2");
        $ids = $stmtOS->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) < 2) {
            $this->markTestSkipped('Se requieren al menos 2 órdenes de servicio para probar correlativos entre clientes.');
        }
        $idOS1 = (int)$ids[0];
        $idOS2 = (int)$ids[1];

        // Limpiar únicamente registros previos de estas 2 OS de prueba
        $this->db->exec("DELETE FROM recepcion_muestras WHERE id_os IN ({$idOS1}, {$idOS2})");
        $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN (SELECT id FROM hojas_solicitud WHERE id_os IN ({$idOS1}, {$idOS2}))");
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id_os IN ({$idOS1}, {$idOS2})");

        $base = $this->opModelo->obtenerSiguienteConsecutivoMuestra($anio, 'MS');

        // 2. Crear y guardar RT-FM-13 para Cliente 1 (OS 1) con 3 probetas de Laboratorio (MS)
        $codigosOS1 = [
            sprintf("MS-%04d-%s", $base, $anioShort),
            sprintf("MS-%04d-%s", $base + 1, $anioShort),
            sprintf("MS-%04d-%s", $base + 2, $anioShort)
        ];
        $muestrasOS1 = [
            ['nombre_muestra' => $codigosOS1[0], 'descripcion' => 'Cilindro 1', 'ensayo_id' => 1, 'plantilla_id' => 1],
            ['nombre_muestra' => $codigosOS1[1], 'descripcion' => 'Cilindro 2', 'ensayo_id' => 1, 'plantilla_id' => 1],
            ['nombre_muestra' => $codigosOS1[2], 'descripcion' => 'Cilindro 3', 'ensayo_id' => 1, 'plantilla_id' => 1]
        ];
        $datosHoja1 = [
            'id_os' => $idOS1,
            'id_hoja' => 0,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'numero_registro' => 'REG-OS1-01',
            'nombre_empresa_o_cliente' => 'Cliente Alpha',
            'identificacion_muestras_json' => json_encode($muestrasOS1)
        ];
        $idHoja1 = 0;
        $ok1 = $this->opModelo->guardarHojaSolicitud($datosHoja1, $idHoja1);
        $this->assertTrue($ok1);
        $this->assertGreaterThan(0, $idHoja1);

        // Guardar detalles relacionales
        $pltModelo = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
        $filasDetalles1 = [
            ['codigo_muestra' => $codigosOS1[0], 'descripcion_ubicacion' => 'Cilindro 1', 'observacion_edad' => '28d', 'ensayo_id' => 1, 'plantilla_id' => 1, 'seleccion_manual' => 0, 'motivo_cambio' => ''],
            ['codigo_muestra' => $codigosOS1[1], 'descripcion_ubicacion' => 'Cilindro 2', 'observacion_edad' => '28d', 'ensayo_id' => 1, 'plantilla_id' => 1, 'seleccion_manual' => 0, 'motivo_cambio' => ''],
            ['codigo_muestra' => $codigosOS1[2], 'descripcion_ubicacion' => 'Cilindro 3', 'observacion_edad' => '28d', 'ensayo_id' => 1, 'plantilla_id' => 1, 'seleccion_manual' => 0, 'motivo_cambio' => '']
        ];
        $pltModelo->guardarDetallesMuestrasHoja($idHoja1, $filasDetalles1, 1);

        // Aceptar/Aprobar formalmente en Laboratorio
        $payloadRecepcion1 = [
            'id_os' => $idOS1,
            'id_hoja' => $idHoja1,
            'limpiar_previas' => false,
            'id_detalle_cotizacion' => 1,
            'tipo_muestra' => 'Laboratorio',
            'fecha_recepcion' => date('Y-m-d H:i:s'),
            'recibido_por' => 'Laboratorista Jefe',
            'entregado_por' => 'Cliente Alpha',
            'muestras_recibidas_json' => json_encode([
                ['id_detalle_cotizacion' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => $codigosOS1[0], 'codigo_campo' => $codigosOS1[0]],
                ['id_detalle_cotizacion' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => $codigosOS1[1], 'codigo_campo' => $codigosOS1[1]],
                ['id_detalle_cotizacion' => 1, 'tipo_muestra' => 'Laboratorio', 'id_cilindro' => $codigosOS1[2], 'codigo_campo' => $codigosOS1[2]]
            ])
        ];
        $okRec1 = $this->opModelo->registrarRecepcion($payloadRecepcion1);
        $this->assertTrue($okRec1);

        // Verificar que los códigos en recepcion_muestras coinciden con los declarados
        $stmtRM1 = $this->db->prepare("SELECT codigo_muestra FROM recepcion_muestras WHERE id_os = :id_os ORDER BY id ASC");
        $stmtRM1->execute(['id_os' => $idOS1]);
        $muestrasLab1 = $stmtRM1->fetchAll(PDO::FETCH_COLUMN);
        $this->assertEquals($codigosOS1, $muestrasLab1, 'Los códigos en laboratorio deben coincidir exactamente con los declarados');

        // 3. Consultar siguiente consecutivo para Cliente 2 (OS 2)
        $sigConsecutivoCliente2 = $this->opModelo->obtenerSiguienteConsecutivoMuestra($anio, 'MS');
        $this->assertEquals($base + 3, $sigConsecutivoCliente2, 'El siguiente correlativo para el Cliente 2 DEBE ser ' . ($base + 3) . ', sin repetir los números del Cliente 1');

        // 4. Intentar forzar guardado de Cliente 2 con códigos colisionantes (ej. con los del Cliente 1)
        $datosHoja2Colision = [
            'id_os' => $idOS2,
            'id_hoja' => 0,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'numero_registro' => 'REG-OS2-01',
            'nombre_empresa_o_cliente' => 'Cliente Beta',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => $codigosOS1[0], 'descripcion' => 'Cilindro Beta 1', 'ensayo_id' => 1, 'plantilla_id' => 1],
                ['nombre_muestra' => $codigosOS1[1], 'descripcion' => 'Cilindro Beta 2', 'ensayo_id' => 1, 'plantilla_id' => 1]
            ])
        ];
        $idHoja2 = 0;
        $ok2 = $this->opModelo->guardarHojaSolicitud($datosHoja2Colision, $idHoja2);
        $this->assertTrue($ok2);

        // Verificar que el backend auto-reasignó los números evitando absolutamente cualquier duplicado
        $hoja2Guardada = $this->opModelo->obtenerHojaSolicitudPorId($idHoja2);
        $muestrasHoja2 = json_decode($hoja2Guardada['muestras_json'] ?? '[]', true);
        $this->assertCount(2, $muestrasHoja2);
        $this->assertEquals(sprintf("MS-%04d-%s", $base + 3, $anioShort), $muestrasHoja2[0]['nombre_muestra'], 'El primer espécimen de Cliente 2 debe reasignarse evitando duplicados');
        $this->assertEquals(sprintf("MS-%04d-%s", $base + 4, $anioShort), $muestrasHoja2[1]['nombre_muestra'], 'El segundo espécimen de Cliente 2 debe reasignarse evitando duplicados');

        // Siguiente correlativo global debe avanzar a base + 5
        $sigPostCliente2 = $this->opModelo->obtenerSiguienteConsecutivoMuestra($anio, 'MS');
        $this->assertEquals($base + 5, $sigPostCliente2);

        // Limpieza del test
        $this->db->exec("DELETE FROM recepcion_muestras WHERE id_os IN ({$idOS1}, {$idOS2})");
        $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$idHoja1}, {$idHoja2})");
        $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$idHoja1}, {$idHoja2})");
    }
}

