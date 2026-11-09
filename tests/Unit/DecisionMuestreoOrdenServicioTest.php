<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

class DecisionMuestreoOrdenServicioTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;
    private int $idOSPrueba = 0;
    private string $codigoOSPrueba = '';
    private int $idCotizacionPrueba = 0;
    private int $idClientePrueba = 0;
    private int $idDetalleCotizacionPrueba = 0;
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

        // Backup de secuencias_muestras
        try {
            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->secuenciasBackup = [];
        }

        $stmtCli = $this->db->query("SELECT id FROM clientes LIMIT 1");
        $idCli = (int)$stmtCli->fetchColumn();
        if ($idCli <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Unit Test Decision', 'J0310000000001', 'decision@test.com', '22223334')");
            $idCli = (int)$this->db->lastInsertId();
        }
        $this->idClientePrueba = $idCli;

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Tester', 'tester@test.com', '123456', 1, 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $cotCode = 'COT-DEC-' . uniqid();
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:code, :cli, :usr, 'Atn Test', 'Proyecto Test', 'Pago contra entrega', 'Aprobada por Cliente', 1000.00, 1150.00)");
        $stmtCot->execute(['code' => $cotCode, 'cli' => $this->idClientePrueba, 'usr' => $idUser]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:cot, 1, 'Resistencia a Compresion Concreto ASTM C39', 5, 200.00, 1000.00)");
        $stmtDet->execute(['cot' => $this->idCotizacionPrueba]);
        $this->idDetalleCotizacionPrueba = (int)$this->db->lastInsertId();

        $this->codigoOSPrueba = 'OS-DEC-' . uniqid();
        $stmtOS = $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, estado, requiere_muestreo) VALUES (:code, :cot, :cli, CURDATE(), 'Aprobada', NULL)");
        $stmtOS->execute(['code' => $this->codigoOSPrueba, 'cot' => $this->idCotizacionPrueba, 'cli' => $this->idClientePrueba]);
        $this->idOSPrueba = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void {
        foreach ($this->hojasCreadas as $idH) {
            $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id")->execute(['id' => $idH]);
            $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = :id")->execute(['id' => $idH]);
        }
        $this->hojasCreadas = [];
        if ($this->idOSPrueba > 0) {
            $this->db->prepare("DELETE FROM programacion_muestreo WHERE id_orden_servicio = :id")->execute(['id' => $this->idOSPrueba]);
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $this->idOSPrueba]);
        }
        if ($this->idCotizacionPrueba > 0) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $this->idCotizacionPrueba]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $this->idCotizacionPrueba]);
        }

        // Restaurar secuencias_muestras
        try {
            $this->db->exec("DELETE FROM secuencias_muestras");
            if (!empty($this->secuenciasBackup)) {
                $stmtInsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr)");
                foreach ($this->secuenciasBackup as $row) {
                    $stmtInsert->execute(['anio' => $row['anio'], 'tipo' => $row['tipo_muestra'], 'corr' => $row['ultimo_correlativo']]);
                }
            }
        } catch (\Throwable $e) {}
        parent::tearDown();
    }

    public function testMarcarRequiereMuestreoYEstablecerIngresoDirecto(): void {
        // Inicialmente requiere_muestreo es NULL
        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertNull($os['requiere_muestreo']);

        // Marcar que requiere muestreo en campo
        $res = $this->osModelo->marcarRequiereMuestreo($this->idOSPrueba);
        $this->assertTrue($res);

        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(1, (int)$os['requiere_muestreo']);
        $this->assertEquals('Estado 3A: Programacion Muestreo', $os['estado']);

        // Cambiar a ingreso directo (MS)
        $res2 = $this->osModelo->establecerIngresoDirecto($this->idOSPrueba);
        $this->assertTrue($res2);

        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(0, (int)$os['requiere_muestreo']);
        $this->assertEquals('Estado 3: Ingreso Directo', $os['estado']);
    }

    public function testNoRegresarEstadoCuandoYaEstaEnLaboratorio(): void {
        // Simular que la OS ya avanzó a Estado 4
        $this->db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 4: Ingreso Laboratorio' WHERE id = :id")->execute(['id' => $this->idOSPrueba]);

        $this->osModelo->marcarRequiereMuestreo($this->idOSPrueba);
        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(1, (int)$os['requiere_muestreo']);
        $this->assertEquals('Estado 4: Ingreso Laboratorio', $os['estado'], 'No debe regresar de Estado 4 a 3A');

        $this->osModelo->establecerIngresoDirecto($this->idOSPrueba);
        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(0, (int)$os['requiere_muestreo']);
        $this->assertEquals('Estado 4: Ingreso Laboratorio', $os['estado'], 'No debe regresar de Estado 4 a 3');
    }

    public function testGuardarHojaMuestrasMCActualizaRequiereMuestreoA1(): void {
        // Asegurar OS con requiere_muestreo = NULL
        $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = NULL, estado = 'Aprobada' WHERE id = :id")->execute(['id' => $this->idOSPrueba]);

        $anio = date('y');
        $datosHoja = [
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleCotizacionPrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_persona_toma_muestra' => 'Tecnico Campo Test',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MC-0005-{$anio}", 'descripcion' => 'Cilindro 1', 'ensayo_id' => 1]
            ])
        ];

        $idGenerado = 0;
        $ok = $this->opModelo->guardarHojaSolicitud($datosHoja, $idGenerado);
        $this->assertTrue($ok);
        $this->assertGreaterThan(0, $idGenerado);
        $this->hojasCreadas[] = $idGenerado;

        // Verificar que la OS se sincronizó con requiere_muestreo = 1
        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(1, (int)$os['requiere_muestreo']);
        $this->assertEquals('Tecnico Campo Test', $os['tecnico_muestreo']);
    }

    public function testGuardarHojaMuestrasMSActualizaRequiereMuestreoA0(): void {
        // Asegurar OS con requiere_muestreo = NULL
        $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = NULL, estado = 'Aprobada' WHERE id = :id")->execute(['id' => $this->idOSPrueba]);

        $anio = date('y');
        $datosHoja = [
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleCotizacionPrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_persona_toma_muestra' => 'Cliente / Entregada por Cliente',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MS-0005-{$anio}", 'descripcion' => 'Cilindro Ventanilla', 'ensayo_id' => 1]
            ])
        ];

        $idGenerado = 0;
        $ok = $this->opModelo->guardarHojaSolicitud($datosHoja, $idGenerado);
        $this->assertTrue($ok);
        $this->assertGreaterThan(0, $idGenerado);
        $this->hojasCreadas[] = $idGenerado;

        // Verificar que la OS se sincronizó con requiere_muestreo = 0
        $os = $this->osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(0, (int)$os['requiere_muestreo']);
        $this->assertNull($os['tecnico_muestreo']);
    }

    public function testDeterminacionEstadoMuestreoNuncaCaeEnPorDefinirConHoja(): void {
        // Caso A: OS con hoja MS pero sin id_pm
        $anio = date('y');
        $datosHojaMS = [
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleCotizacionPrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_persona_toma_muestra' => 'Cliente',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MS-0006-{$anio}", 'descripcion' => 'Muestra MS', 'ensayo_id' => 1]
            ])
        ];
        $idGen = 0;
        $this->opModelo->guardarHojaSolicitud($datosHojaMS, $idGen);
        $this->hojasCreadas[] = $idGen;

        // Cargar todas las ordenes para simular la vista
        $ordenes = $this->osModelo->obtenerTodas($this->codigoOSPrueba);
        $this->assertNotEmpty($ordenes);
        $osEncontrada = null;
        foreach ($ordenes as $o) {
            if ((int)$o['id'] === $this->idOSPrueba) {
                $osEncontrada = $o;
                break;
            }
        }
        $this->assertNotNull($osEncontrada);

        // Evaluar la lógica exacta de la vista
        $tieneHojasOS = !empty($osEncontrada['id_hoja']) || !empty($osEncontrada['hojas_servicio']);
        $tieneMuestrasMC = false;
        $tieneMuestrasMS = false;
        if (!empty($osEncontrada['muestras_json'])) {
            $mArr = json_decode($osEncontrada['muestras_json'], true) ?: [];
            foreach ($mArr as $m) {
                $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
            }
        }
        if (!empty($osEncontrada['hojas_servicio'])) {
            foreach ($osEncontrada['hojas_servicio'] as $hServ) {
                $mArr = json_decode($hServ['muestras_json'] ?? '[]', true) ?: [];
                foreach ($mArr as $m) {
                    $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                    if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                    if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
                }
            }
        }

        $estadoMuestreo = 'sin_decidir';
        if (!empty($osEncontrada['id_pm'])) {
            if (in_array($osEncontrada['estado_muestreo'], ['Programado', 'En Proceso', 'En Campo'])) {
                $estadoMuestreo = 'en_proceso';
            } else {
                $estadoMuestreo = 'muestreo_campo_finalizado';
            }
        } elseif ($tieneMuestrasMC || $osEncontrada['requiere_muestreo'] === 1 || $osEncontrada['requiere_muestreo'] === '1') {
            if ($tieneHojasOS) {
                $estadoMuestreo = 'muestreo_campo_finalizado';
            } else {
                $estadoMuestreo = 'pendiente_programar';
            }
        } elseif ($tieneMuestrasMS || $osEncontrada['requiere_muestreo'] === 0 || $osEncontrada['requiere_muestreo'] === '0') {
            $estadoMuestreo = 'no_aplica';
        }

        $this->assertEquals('no_aplica', $estadoMuestreo, 'Para una hoja con MS el estado debe ser no_aplica (Ingreso Directo), nunca sin_decidir ni Por Definir');
    }

    public function testDeterminacionEstadoMuestreoConHojaMuestrasMC(): void {
        // Caso B: OS con hoja MC pero sin id_pm formal
        $anio = date('y');
        $datosHojaMC = [
            'id_os' => $this->idOSPrueba,
            'id_detalle_cotizacion' => $this->idDetalleCotizacionPrueba,
            'id_producto' => 1,
            'ensayo_id' => 1,
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'nombre_persona_toma_muestra' => 'Muestreador Obra',
            'identificacion_muestras_json' => json_encode([
                ['nombre_muestra' => "MC-0007-{$anio}", 'descripcion' => 'Muestra MC', 'ensayo_id' => 1]
            ])
        ];
        $idGen = 0;
        $this->opModelo->guardarHojaSolicitud($datosHojaMC, $idGen);
        $this->hojasCreadas[] = $idGen;

        $ordenes = $this->osModelo->obtenerTodas($this->codigoOSPrueba);
        $osEncontrada = null;
        foreach ($ordenes as $o) {
            if ((int)$o['id'] === $this->idOSPrueba) {
                $osEncontrada = $o;
                break;
            }
        }
        $this->assertNotNull($osEncontrada);

        $tieneHojasOS = !empty($osEncontrada['id_hoja']) || !empty($osEncontrada['hojas_servicio']);
        $tieneMuestrasMC = false;
        $tieneMuestrasMS = false;
        if (!empty($osEncontrada['muestras_json'])) {
            $mArr = json_decode($osEncontrada['muestras_json'], true) ?: [];
            foreach ($mArr as $m) {
                $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
            }
        }
        if (!empty($osEncontrada['hojas_servicio'])) {
            foreach ($osEncontrada['hojas_servicio'] as $hServ) {
                $mArr = json_decode($hServ['muestras_json'] ?? '[]', true) ?: [];
                foreach ($mArr as $m) {
                    $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                    if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                    if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
                }
            }
        }

        $estadoMuestreo = 'sin_decidir';
        if (!empty($osEncontrada['id_pm'])) {
            if (in_array($osEncontrada['estado_muestreo'], ['Programado', 'En Proceso', 'En Campo'])) {
                $estadoMuestreo = 'en_proceso';
            } else {
                $estadoMuestreo = 'muestreo_campo_finalizado';
            }
        } elseif ($tieneMuestrasMC || $osEncontrada['requiere_muestreo'] === 1 || $osEncontrada['requiere_muestreo'] === '1') {
            if ($tieneHojasOS) {
                $estadoMuestreo = 'muestreo_campo_finalizado';
            } else {
                $estadoMuestreo = 'pendiente_programar';
            }
        } elseif ($tieneMuestrasMS || $osEncontrada['requiere_muestreo'] === 0 || $osEncontrada['requiere_muestreo'] === '0') {
            $estadoMuestreo = 'no_aplica';
        }

        $this->assertEquals('muestreo_campo_finalizado', $estadoMuestreo, 'Para una hoja con MC el estado debe ser muestreo_campo_finalizado, nunca sin_decidir ni Por Definir');
    }
}
