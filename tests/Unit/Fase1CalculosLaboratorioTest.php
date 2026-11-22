<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Modulos\Operaciones\Servicios\AgregadorEnsayosServicio;
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;
use Cycsa\Modulos\Operaciones\Servicios\ManejadoresCorreccionHc;
use Cycsa\Modulos\Operaciones\Servicios\MapeadorRtFm22Servicio;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;

class Fase1CalculosLaboratorioTest extends TestCase
{
    private PDO $db;
    private array $cleanupRecepciones = [];
    private array $cleanupOrdenes = [];
    private array $cleanupClientes = [];

    protected function setUp(): void
    {
        $this->db = Conexion::obtenerInstancia();
        ConfigCalculoLabServicio::limpiarCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupRecepciones as $recId) {
            $this->db->prepare("DELETE FROM documentos_reemisiones WHERE tipo_documento = 'informe_rotura' AND documento_base_id IN (SELECT id FROM informes_rotura WHERE muestra_id = ?)")->execute([$recId]);
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = ?)")->execute([$recId]);
            $this->db->prepare("DELETE FROM informes_rotura WHERE muestra_id = ?")->execute([$recId]);
            $this->db->prepare("DELETE FROM plan_rotura_asignaciones WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id = ?)")->execute([$recId]);
            $this->db->prepare("DELETE FROM plan_rotura_edades WHERE plan_rotura_id IN (SELECT id FROM planes_rotura WHERE muestra_id = ?)")->execute([$recId]);
            $this->db->prepare("DELETE FROM planes_rotura WHERE muestra_id = ?")->execute([$recId]);
            $this->db->prepare("DELETE FROM especimenes WHERE muestra_id = ?")->execute([$recId]);
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id_recepcion = ?")->execute([$recId]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id = ?")->execute([$recId]);
        }
        foreach ($this->cleanupOrdenes as $osId) {
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$osId]);
        }
        foreach ($this->cleanupClientes as $clienteId) {
            $this->db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$clienteId]);
        }
        ConfigCalculoLabServicio::limpiarCache();
    }

    private function crearFixtureMuestra(string $fechaMoldeo = '2026-10-01', string $diseno = '3500 PSI'): array
    {
        $suf = bin2hex(random_bytes(4));

        $stmtC = $this->db->prepare("
            INSERT INTO clientes (nombre_razon_social, identificacion, numero_ruc, email, telefono, direccion)
            VALUES (?, ?, ?, ?, '2222-0000', 'Managua')
        ");
        $stmtC->execute(["Cliente Test F1 {$suf}", "J{$suf}", "J{$suf}", "f1_{$suf}@cycsa.local"]);
        $clienteId = (int)$this->db->lastInsertId();
        $this->cleanupClientes[] = $clienteId;

        $stmtOs = $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, nombre_proyecto, atencion_a, estado)
            VALUES (?, 1, ?, CURDATE(), 'Proyecto Fase 1 Lab', 'Ing. Test', 'Estado 4: Ingreso Laboratorio')
        ");
        $stmtOs->execute(["OS-F1-{$suf}", $clienteId]);
        $osId = (int)$this->db->lastInsertId();
        $this->cleanupOrdenes[] = $osId;

        $codigo = "MS-F1-{$suf}-26";
        $stmtRec = $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado)
            VALUES (?, 9988, 2026, 'Laboratorio', ?, 'C-F1-01', NOW(), 1, 'Registrado')
        ");
        $stmtRec->execute([$osId, $codigo]);
        $recId = (int)$this->db->lastInsertId();
        $this->cleanupRecepciones[] = $recId;

        $stmtLote = $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, temperatura_c, procedimiento_muestreo)
            VALUES (?, 'Concreto Fase 1', ?, ?, 4.0, 24.0, 'ASTM C172')
        ");
        $stmtLote->execute([$recId, $diseno, $fechaMoldeo]);

        return [
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'muestra_id' => $recId,
            'codigo_muestra' => $codigo,
        ];
    }

    public function testResistenciaCilindrosFormulaOficialMS15192yDivisorConfigurable(): void
    {
        // Caso real MS-15192-26: 44840 / 12.42 = 3610.3059 -> 3610 psi; 3610 / 14.2029 = 254.17 -> 254 kgf/cm²
        $resOficial = ConfigCalculoLabServicio::calcularResistenciaCilindro(44840.0, 12.42, [
            'divisor_kgcm2' => 14.2029,
            'redondear_psi_10' => true,
            'usar_prensa' => false,
            'usar_factor_ld' => false,
        ]);
        $this->assertSame(3610.0, $resOficial['esfuerzo_psi']);
        $this->assertSame(254.0, $resOficial['esfuerzo_kgcm2']);

        // Divisores alternativos configurables (14.2 y 14.223)
        $res142 = ConfigCalculoLabServicio::calcularResistenciaCilindro(80000.0, 12.42, [
            'divisor_kgcm2' => 14.2,
            'redondear_psi_10' => true,
            'usar_prensa' => false,
            'usar_factor_ld' => false,
        ]);
        // 80000 / 12.42 = 6441.22 -> 6440 psi; 6440 / 14.2 = 453.52 -> 454 kgf/cm²
        $this->assertSame(6440.0, $res142['esfuerzo_psi']);
        $this->assertSame(454.0, $res142['esfuerzo_kgcm2']);

        $res14223 = ConfigCalculoLabServicio::calcularResistenciaCilindro(80000.0, 12.42, [
            'divisor_kgcm2' => 14.223,
            'redondear_psi_10' => true,
            'usar_prensa' => false,
            'usar_factor_ld' => false,
        ]);
        // 6440 / 14.223 = 452.78 -> 453 kgf/cm²
        $this->assertSame(6440.0, $res14223['esfuerzo_psi']);
        $this->assertSame(453.0, $res14223['esfuerzo_kgcm2']);

        // Área desde D1 y D2: π·(D̄/2)² / 6.4516
        $area = ConfigCalculoLabServicio::calcularAreaCilindroIn2(10.10, 10.10);
        $this->assertEqualsWithDelta(12.419, $area, 0.01);

        // Peso volumétrico redondeado a 10
        $pv = ConfigCalculoLabServicio::calcularPesoVolumetricoKgM3(3.820, 12.42, 20.15, true);
        $this->assertSame(2370.0, $pv);

        // Revenimiento pulgadas -> cm redondeado a 0.5 cm
        $this->assertSame(10.0, ConfigCalculoLabServicio::calcularRevenimientoCm(4.0));
        $this->assertSame(11.5, ConfigCalculoLabServicio::calcularRevenimientoCm(4.5));
    }

    public function testRegistrarRoturaDesdeDiametrosPersistePesoVolumetricoEdadYRechazaSinArea(): void
    {
        $fix = $this->crearFixtureMuestra('2026-10-01', '3500 PSI');
        $modelo = new RoturasModelo();

        $modelo->crearEspecimenesParaMuestra($fix['muestra_id'], 2, '2026-10-01');
        $planRes = $modelo->guardarPlanRotura($fix['muestra_id'], null, [
            ['edad_dias' => 7, 'cantidad' => 2],
        ], 1);
        $this->assertTrue($planRes['exito'], $planRes['mensaje'] ?? '');

        $detalle = $modelo->obtenerDetalleMuestraAnonima($fix['muestra_id']);
        $edadId = (int)$detalle['edades'][0]['id'];
        $esp1 = $detalle['especimenes'][0];

        // 1) Debe RECHAZAR cuando no se envía ni D1/D2 ni área (sin fallback 28.2743)
        $resRechazo = $modelo->registrarRotura([
            'muestra_id' => $fix['muestra_id'],
            'especimen_id' => (int)$esp1['id'],
            'plan_edad_id' => $edadId,
            'fecha_real' => '2026-10-08 08:00:00',
            'carga' => 44840,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)',
        ], 1);
        $this->assertFalse($resRechazo['exito']);
        $this->assertStringContainsString('ingrese D1 y D2', $resRechazo['mensaje']);

        // 2) Debe ACEPTAR D1/D2 sin exigir codigo_formato, calcular área/psi/kgcm2/PV/edad y persistirlos
        $resOk = $modelo->registrarRotura([
            'muestra_id' => $fix['muestra_id'],
            'especimen_id' => (int)$esp1['id'],
            'plan_edad_id' => $edadId,
            'fecha_real' => '2026-10-08 08:00:00',
            'carga' => 44840,
            'diametro_1' => 10.10,
            'diametro_2' => 10.10,
            'largo_1' => 20.15,
            'largo_2' => 20.15,
            'masa_kg' => 3.820,
            'tipo_falla' => 'Tipo 1 (Cono bien formado)',
        ], 1);
        $this->assertTrue($resOk['exito'], $resOk['mensaje'] ?? '');
        $this->assertSame(3610.0, (float)$resOk['esfuerzo_psi']);
        $this->assertSame(254.0, (float)$resOk['esfuerzo_kgcm2']);
        $this->assertSame(2370.0, (float)$resOk['peso_volumetrico']);
        $this->assertSame(7, (int)$resOk['edad_calculada']);

        // Verificar persistencia en BD
        $row = $this->db->query("SELECT area, esfuerzo_psi, esfuerzo_kgcm2, peso_volumetrico, edad_calculada FROM roturas WHERE especimen_id = " . (int)$esp1['id'] . " ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqualsWithDelta(12.42, (float)$row['area'], 0.01);
        $this->assertSame(3610.0, (float)$row['esfuerzo_psi']);
        $this->assertSame(254.0, (float)$row['esfuerzo_kgcm2']);
        $this->assertSame(2370.0, (float)$row['peso_volumetrico']);
        $this->assertSame(7, (int)$row['edad_calculada']);
    }

    public function testToleranciasAstmC39DecimalYModificarPlanRecalculaTolerancia(): void
    {
        $this->assertSame(0.5, ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39(1));
        $this->assertSame(2.0, ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39(3));
        $this->assertSame(6.0, ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39(7));
        $this->assertSame(20.0, ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39(28));
        $this->assertSame(48.0, ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39(90));

        $fix = $this->crearFixtureMuestra('2026-10-01');
        $modelo = new RoturasModelo();
        $modelo->crearEspecimenesParaMuestra($fix['muestra_id'], 1, '2026-10-01');
        $planRes = $modelo->guardarPlanRotura($fix['muestra_id'], null, [
            ['edad_dias' => 1, 'cantidad' => 1],
        ], 1);
        $this->assertTrue($planRes['exito'], $planRes['mensaje'] ?? '');

        $planRow = $this->db->query("
            SELECT pre.id, pr.id AS plan_id, pre.tolerancia_horas
            FROM plan_rotura_edades pre
            JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
            WHERE pr.muestra_id = {$fix['muestra_id']}
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqualsWithDelta(0.5, (float)$planRow['tolerancia_horas'], 0.001);

        // Modificar plan a 7 días y confirmar recálculo de tolerancia a 6.0 h
        $modRes = $modelo->modificarPlanRoturaConAutorizacion(
            (int)$planRow['plan_id'],
            [['edad_dias' => 7, 'fecha_programada' => '2026-10-08']],
            1,
            1,
            'Reprogramación solicitada por obra'
        );
        $this->assertTrue($modRes['exito'], $modRes['mensaje'] ?? '');

        $planAfter = $this->db->query("SELECT edad_dias, tolerancia_horas FROM plan_rotura_edades WHERE id = " . (int)$planRow['id'])->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(7, (int)$planAfter['edad_dias']);
        $this->assertEqualsWithDelta(6.0, (float)$planAfter['tolerancia_horas'], 0.001);
    }

    public function testReemisionPreservaBaseYSufijoYRevisionGuardaNombres(): void
    {
        $fix = $this->crearFixtureMuestra('2026-10-01');
        $modelo = new RoturasModelo();

        $suf = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $numBase = "INF-2026-{$suf}";
        $stmt = $this->db->prepare("
            INSERT INTO informes_rotura (muestra_id, tipo, numero, estado, version)
            VALUES (?, 'parcial', ?, 'en_revision', 1)
        ");
        $stmt->execute([$fix['muestra_id'], $numBase]);
        $infId = (int)$this->db->lastInsertId();

        // Revisión / Aprobación con nombres y observaciones pendientes
        $revRes = $modelo->procesarRevisionInforme($infId, 'aprobado', null, 1, 1, [
            'revisado_por' => 1,
            'revisado_nombre' => 'Ing. Revisora CYCSA',
            'aprobado_nombre' => 'Ing. Director Técnico',
            'observaciones_pendientes' => 'Pendiente firma física de cliente',
        ]);
        $this->assertTrue($revRes['exito'], $revRes['mensaje'] ?? '');

        $rowRev = $this->db->query("SELECT estado, revisado_nombre, aprobado_nombre, observaciones_pendientes FROM informes_rotura WHERE id = {$infId}")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('aprobado', $rowRev['estado']);
        $this->assertSame('Ing. Revisora CYCSA', $rowRev['revisado_nombre']);
        $this->assertSame('Ing. Director Técnico', $rowRev['aprobado_nombre']);
        $this->assertSame('Pendiente firma física de cliente', $rowRev['observaciones_pendientes']);

        // Primera reemisión -> sufijo -1
        $reem1 = $modelo->reemitirInforme($infId, 'Corrección tipográfica en proyecto', 1);
        $this->assertTrue($reem1['exito'], $reem1['mensaje'] ?? '');
        $this->assertSame($numBase . '-1', $reem1['nuevo_numero']);
        $this->assertSame(2, (int)$reem1['nueva_version']);

        // Aprobar v2 y reemitir -> sufijo -2 (sin duplicar -1-2)
        $this->db->prepare("UPDATE informes_rotura SET estado = 'aprobado' WHERE id = ?")->execute([(int)$reem1['nuevo_informe_id']]);
        $reem2 = $modelo->reemitirInforme((int)$reem1['nuevo_informe_id'], 'Ajuste de nombre de contratista', 1);
        $this->assertTrue($reem2['exito'], $reem2['mensaje'] ?? '');
        $this->assertSame($numBase . '-2', $reem2['nuevo_numero']);
        $this->assertSame(3, (int)$reem2['nueva_version']);
    }

    public function testSeisMapeadoresYGranulometriaAgregadosYCompactacionYLodoConcreto(): void
    {
        $map = new MapeadorRtFm22Servicio();

        // 1) RT-FM-61
        $m61 = $map->mapearHaciaInforme('RT-FM-61', [
            'especimenes' => [[
                'valores' => [
                    'fecha_moldeo' => '2026-10-01',
                    'fecha_rotura' => '2026-10-08',
                    'edad_dias' => 7,
                    'revenimiento_in' => 4.25,
                    'peso_volumetrico' => 2370,
                    'area_in2' => 12.42,
                    'carga_real_lbf' => 44840,
                    'esfuerzo_psi_reportado' => 3610,
                    'esfuerzo_kgcm2' => 254,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-15192-26', 'resistencia_diseno_psi' => 3500]);
        $fila61 = $m61['resultados_json']['filas'][0];
        $this->assertSame(44840.0, (float)$fila61['Carga (lb)']);
        $this->assertSame(3610.0, (float)$fila61['R. Compresión (lb/in²)']);
        $this->assertSame(254.0, (float)$fila61['R. Compresión (kg/cm²)']);
        $this->assertSame('2026-10-08', $fila61['Fecha de Ensayo']);
        $this->assertSame(7, (int)$fila61['Edad (Días)']);
        $this->assertSame(2370.0, (float)$fila61['Peso Volumétrico (kg/m³)']);

        // 2) RT-FM-36 (Bloques - ÁREA NETA + espesor en cm sin dividir por 10)
        $m36 = $map->mapearHaciaInforme('RT-FM-36', [
            'especimenes' => [[
                'valores' => [
                    'fecha_fabricacion' => '2026-09-20',
                    'largo' => 39.8,
                    'alto' => 19.5,
                    'ancho' => 14.9,
                    'espesor_pared_int' => 2.8,
                    'espesor_pared_ext' => 3.1,
                    'area_bruta' => 593.02, // cm² -> ~91.92 in²
                    'area_neta' => 310.00,  // cm² -> ~48.05 in²
                    'carga_lbf' => 65000,
                    'resistencia_psi' => 1353,
                    'resistencia_kgcm2' => 95,
                    'densidad_seca' => 1950,
                    'absorcion_pct' => 7.4,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-BLQ-01']);
        $porDesc36 = [];
        foreach ($m36['resultados_json']['filas'] as $f) {
            $porDesc36[$f['Descripción']] = $f['Resultado'];
        }
        $this->assertEqualsWithDelta(91.92, (float)$porDesc36['Área bruta.'], 0.05);
        $this->assertEqualsWithDelta(48.05, (float)$porDesc36['Área neta.'], 0.05);
        $this->assertSame(1353.0, (float)$porDesc36['R. Compresión']);
        $this->assertSame(95.0, (float)$porDesc36['R. Compresión.']);
        $this->assertSame(2.8, (float)$porDesc36['Espesor interno.']);
        $this->assertSame(3.1, (float)$porDesc36['Espesor externo.']);
        $this->assertSame(1950.0, (float)$porDesc36['Peso volumétrico']);
        $this->assertSame(7.4, (float)$porDesc36['Absorción']);
        $this->assertSame('2026-09-20', $porDesc36['Fecha de Fabricación']);

        // 3) RT-FM-38 (Vigas)
        $m38 = $map->mapearHaciaInforme('RT-FM-38', [
            'especimenes' => [[
                'valores' => [
                    'fecha_fabricacion' => '2026-09-10',
                    'fecha_ensayo' => '2026-10-08',
                    'edad_dias' => 28,
                    'ancho_prom' => 6.0,
                    'espesor_prom' => 6.0,
                    'luz_apoyos' => 18.0,
                    'peso_volumetrico' => 2350,
                    'carga_lbf' => 7200,
                    'modulo_ruptura_kgcm2' => 42.2,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-VIG-01']);
        $fila38 = $m38['resultados_json']['filas'][0];
        $this->assertSame(6.0, (float)$fila38['Ancho Promedio (in)']);
        $this->assertSame(6.0, (float)$fila38['Espesor Promedio (in)']);
        $this->assertSame(18.0, (float)$fila38['Longitud de Apoyo (in)']);
        $this->assertSame(2350.0, (float)$fila38['Peso volumétrico (kg/m³)']);
        $this->assertSame(42.2, (float)$fila38['Resistencia a la flexión. (kg/cm²)']);

        // 4) RT-FM-57 (Cubos mortero)
        $m57 = $map->mapearHaciaInforme('RT-FM-57', [
            'especimenes' => [[
                'valores' => [
                    'fecha_fabricacion' => '2026-10-01',
                    'fecha_ensayo' => '2026-10-08',
                    'edad_dias' => 7,
                    'area_in2' => 4.0,
                    'peso_volumetrico' => 2120,
                    'carga_lbf' => 16000,
                    'resistencia_psi' => 4000,
                    'resistencia_kgcm2' => 281,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-CUB-01']);
        $fila57 = $m57['resultados_json']['filas'][0];
        $this->assertSame(4000.0, (float)$fila57['R. Compresión (lb/in²)']);
        $this->assertSame(281.0, (float)$fila57['R. Compresión. (kg/cm²)']);
        $this->assertSame(2120.0, (float)$fila57['Peso Volumétrico (kg/m³)']);

        // 5) RT-FM-63 (Adoquines / Núcleos)
        $m63 = $map->mapearHaciaInforme('RT-FM-63', [
            'especimenes' => [[
                'valores' => [
                    'area_neta_in2' => 30.5,
                    'peso_volumetrico' => 2280,
                    'absorcion_pct' => 4.6,
                    'carga_prom' => 185000,
                    'resistencia_psi' => 6066,
                    'resistencia_kgcm2' => 427,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-ADQ-01']);
        $fila63 = $m63['resultados_json']['filas'][0];
        $this->assertSame(6066.0, (float)$fila63['R. Compresión (lb/in²)']);
        $this->assertSame(427.0, (float)$fila63['R. Compresión. (kg/cm²)']);
        $this->assertSame(4.6, (float)$fila63['Absorción ((%)(P/P))']);

        // 6) RT-FM-64 (Acero - Reporte Nuevo con letra sugerida H)
        $m64 = $map->mapearHaciaInforme('RT-FM-64', [
            'especimenes' => [[
                'valores' => [
                    'diametro_mm' => 12.7,
                    'area_mm2' => 126.7,
                    'peso_unitario' => 0.994,
                    'fuerza_max_kn' => 84.5,
                    'fluencia_mpa' => 440.0,
                    'fluencia_psi' => 63817,
                    'resistencia_tension_psi' => 96700,
                    'resistencia_tension_kgcm2' => 6800,
                    'elongacion' => 14.5,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-ACE-01']);
        $this->assertTrue($m64['es_reporte_nuevo']);
        $this->assertSame('H', $m64['letra_rt_fm_22_sugerida']);
        $fila64 = $m64['filas'][0];
        $this->assertSame(12.7, (float)$fila64['Diámetro (mm)']);
        $this->assertSame(63817.0, (float)$fila64['Fluencia (psi)']);
        $this->assertSame(96700.0, (float)$fila64['Resistencia Tensión (psi)']);
        $this->assertSame(14.5, (float)$fila64['Elongación (%)']);

        // 7) RT-FM-55 sin defaults inventados (Capa=1, Espesor=20, PVS=0 eliminados)
        $m55 = $map->mapearHaciaInforme('RT-FM-55', [
            'especimenes' => [[
                'valores' => [
                    'densidad_humeda' => 2050,
                    'humedad' => 11.2,
                    'densidad_seca' => 1844,
                    'compactacion' => 96.5,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-CMP-01']);
        $fila55 = $m55['resultados_json']['filas'][0];
        $this->assertSame('', $fila55['Capa (N°)']);
        $this->assertSame('', $fila55['Espesor (cm)']);
        $this->assertSame('', $fila55['P.V.S Max (kg/m³)']);

        // 8) RT-FM-25 tamiz 3" + modo Agregado Fino (CYCSA-PE-06, sin Atterberg/SUCS, con Módulo de Finura)
        $agregador = new AgregadorEnsayosServicio();
        $valoresGran = [
            'masa_seca' => 500.0,
            'ret_3in' => 0.0,
            'ret_3_8in' => 0.0,
            'ret_no4' => 15.0,
            'ret_no8' => 65.0,
            'ret_no16' => 100.0,
            'ret_no30' => 120.0,
            'ret_no50' => 110.0,
            'ret_no100' => 60.0,
            'ret_no200' => 20.0,
        ];
        $curva = $agregador->calcularCurvaGranulometrica($valoresGran);
        $this->assertArrayHasKey('ret_3in', $curva['tamices']);
        $this->assertGreaterThan(2.0, (float)$curva['modulo_finura']);

        $m25Agr = $map->mapearHaciaInforme('RT-FM-25', [
            'especimenes' => [['valores' => $valoresGran]],
            'agregaciones' => ['curva_granulometrica' => $curva],
        ], ['codigo_muestra' => 'MS-AGR-01', 'tipo_material' => 'Agregado fino']);
        $this->assertSame('granulomnetria_de_agregados.md', $m25Agr['archivo_markdown']);
        $filasAgr = $m25Agr['resultados_json']['filas'];
        $this->assertSame('Tamiz 3"', $filasAgr[0]['Descripción']);
        $this->assertSame('CYCSA-PE-06', $filasAgr[0]['Método de ensayo']);
        $this->assertEqualsWithDelta(100.0, (float)$filasAgr[0]['Resultado'], 0.01);
        $descsAgr = array_column($filasAgr, 'Descripción');
        $this->assertContains('Módulo de Finura', $descsAgr);
        $this->assertNotContains('Clasificación S.U.C.S', $descsAgr);
        $this->assertNotContains('Límite Liquido.', $descsAgr);

        // 9) Formato Lodo Concreto (cm², kg, kg/cm²)
        $mLodo = $map->mapearHaciaInforme('RT-FM-61', [
            'especimenes' => [[
                'valores' => [
                    'diametro_1' => 10.10,
                    'diametro_2' => 10.10,
                    'carga_kg' => 5000,
                    'esfuerzo_kgcm2' => 62.4,
                    'edad_dias' => 28,
                ],
            ]],
        ], ['codigo_muestra' => 'MS-LODO-01', 'archivo_destino' => 'formato_de_lodo_concreto.md']);
        $this->assertSame('formato_de_lodo_concreto.md', $mLodo['archivo_markdown']);
        $filaLodo = $mLodo['resultados_json']['filas'][0];
        $this->assertEqualsWithDelta(80.12, (float)$filaLodo['Área (cm²)'], 0.05);
        $this->assertSame(5000.0, (float)$filaLodo['Carga (kg)']);
        $this->assertSame(62.4, (float)$filaLodo['R. compresión. (kg/cm²)']);
    }

    public function testProctorMaximoMedidoCbrPorGolpesYMartilloSuizoPosiciones(): void
    {
        $agregador = new AgregadorEnsayosServicio();

        // 1) Proctor: default = maximo_medido
        $puntosProctor = [
            ['humedad' => 10.0, 'densidad_seca' => 1750.0],
            ['humedad' => 12.0, 'densidad_seca' => 1890.0],
            ['humedad' => 14.0, 'densidad_seca' => 1920.0],
            ['humedad' => 16.0, 'densidad_seca' => 1810.0],
        ];
        $this->assertSame(1920.0, $agregador->maxCurva($puntosProctor, 'densidad_seca'));
        $this->assertSame(14.0, $agregador->xDeMaxCurva($puntosProctor, 'humedad'));

        // Con regresión parabólica explícita y vértice dentro del rango
        $maxReg = $agregador->maxCurva($puntosProctor, 'densidad_seca', ['usar_regresion' => true]);
        $xReg = $agregador->xDeMaxCurva($puntosProctor, 'humedad', ['usar_regresion' => true]);
        $this->assertGreaterThanOrEqual(1920.0, $maxReg);
        $this->assertGreaterThanOrEqual(10.0, $xReg);
        $this->assertLessThanOrEqual(16.0, $xReg);

        // 2) CBR por golpes (56->100%, 25->95%, 12->90%) + cbr_diseno
        $resumenCbr = $agregador->resumenCbrPorGolpes([
            ['golpes' => 56, 'compactacion' => 100.2, 'cbr' => 45.0],
            ['golpes' => 25, 'compactacion' => 95.1, 'cbr' => 28.5],
            ['golpes' => 12, 'compactacion' => 90.4, 'cbr' => 16.2],
        ]);
        $this->assertSame(45.0, $resumenCbr['cbr_100_56_golpes']);
        $this->assertSame(28.5, $resumenCbr['cbr_95_25_golpes']);
        $this->assertSame(16.2, $resumenCbr['cbr_90_12_golpes']);
        $this->assertNotNull($resumenCbr['cbr_diseno']);

        // 3) Martillo Suizo RT-FM-59: cambio de curva según posición A vs B vs C
        $motor = new MotorHojasCalculoServicio();
        $rebotes = [
            'rebote_1' => 30, 'rebote_2' => 31, 'rebote_3' => 30, 'rebote_4' => 32, 'rebote_5' => 29,
            'rebote_6' => 30, 'rebote_7' => 31, 'rebote_8' => 30, 'rebote_9' => 30, 'rebote_10' => 31,
        ];
        $resA = $motor->evaluarEspecimen('RT-FM-59', array_merge($rebotes, ['posicion' => 'A']));
        $resB = $motor->evaluarEspecimen('RT-FM-59', array_merge($rebotes, ['posicion' => 'B']));
        $resC = $motor->evaluarEspecimen('RT-FM-59', array_merge($rebotes, ['posicion' => 'C']));

        $this->assertNotEquals($resA['valores']['resistencia_psi'], $resB['valores']['resistencia_psi']);
        $this->assertNotEquals($resA['valores']['resistencia_psi'], $resC['valores']['resistencia_psi']);
        $this->assertArrayHasKey('resistencia_psi_redondeada', $resA['valores']);

        // val_FM59_02 advierte cuando >2 lecturas se descartan (conjunto_descartado_c805)
        $valAviso = ManejadoresCorreccionHc::val_FM59_02(['conjunto_descartado_c805' => true], true);
        $this->assertFalse($valAviso['valido']);
        $this->assertNotEmpty($valAviso['aviso']);
    }
}
