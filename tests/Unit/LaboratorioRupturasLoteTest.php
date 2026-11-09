<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use PDO;

class LaboratorioRupturasLoteTest extends TestCase {

    private PDO $db;

    protected function setUp(): void {
        $this->db = Conexion::obtenerInstancia();
    }

    /**
     * Verifica que la consulta SQL en OperacionesControlador::imprimirMatrizProducto
     * no contiene parámetros duplicados que causen PDO SQLSTATE[HY093]: Invalid parameter number.
     */
    public function testConsultaMuestrasImprimirMatrizSinErrorHy093(): void {
        // Caso 1: idHojaResolv = 0
        $idHojaResolv = 0;
        $idOsPrueba = 1;

        $sqlFiltroHoja1 = ($idHojaResolv > 0)
            ? " AND (rm.id_hoja = :id_hoja OR (rm.id_hoja IS NULL AND :id_hoja_fb = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = :id_os_fb)))"
            : "";
        $params1 = ['id_os' => $idOsPrueba];
        if ($idHojaResolv > 0) {
            $params1['id_hoja'] = $idHojaResolv;
            $params1['id_hoja_fb'] = $idHojaResolv;
            $params1['id_os_fb'] = $idOsPrueba;
        }

        $stmt1 = $this->db->prepare("
            SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
            FROM recepcion_muestras rm
            LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
            WHERE rm.id_os = :id_os {$sqlFiltroHoja1}
            ORDER BY rm.id ASC
        ");
        $ejecutado1 = $stmt1->execute($params1);
        $this->assertTrue($ejecutado1, "La consulta con idHoja = 0 debe ejecutarse sin SQLSTATE[HY093].");

        // Caso 2: idHojaResolv > 0
        $idHojaResolv2 = 999999;
        $sqlFiltroHoja2 = ($idHojaResolv2 > 0)
            ? " AND (rm.id_hoja = :id_hoja OR (rm.id_hoja IS NULL AND :id_hoja_fb = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = :id_os_fb)))"
            : "";
        $params2 = ['id_os' => $idOsPrueba];
        if ($idHojaResolv2 > 0) {
            $params2['id_hoja'] = $idHojaResolv2;
            $params2['id_hoja_fb'] = $idHojaResolv2;
            $params2['id_os_fb'] = $idOsPrueba;
        }

        $stmt2 = $this->db->prepare("
            SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
            FROM recepcion_muestras rm
            LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
            WHERE rm.id_os = :id_os {$sqlFiltroHoja2}
            ORDER BY rm.id ASC
        ");
        $ejecutado2 = $stmt2->execute($params2);
        $this->assertTrue($ejecutado2, "La consulta con idHoja > 0 debe ejecutarse con parámetros únicos sin SQLSTATE[HY093].");
    }

    /**
     * Verifica que obtenerDatosMuestraDesdeMatriz extrae edad, temperatura y área
     * e incluso deduce la edad a partir de Fecha de Fabricación y Fecha de Ruptura si no hay número directo.
     */
    public function testObtenerDatosMuestraDeduceEdadDesdeFechas(): void {
        $filas = [
            [
                'Código laboratorio' => 'MS-0001-26',
                'Nombre muestra' => 'Cilindro 1',
                'Temperatura (°C)' => '24.5',
                'Área (in²)' => '28.274',
                'Fecha de Fabricación' => '2026-11-01',
                'Fecha de Ruptura' => '2026-11-29',
                'Edad (Días)' => ''
            ],
            [
                'Código laboratorio' => 'MS-0002-26',
                'Nombre muestra' => 'Cilindro 2',
                'Temperatura (°C)' => '25.0',
                'Área (in²)' => '28.274',
                'Edad (Días)' => '7'
            ]
        ];

        $datos1 = obtenerDatosMuestraDesdeMatriz($filas, 'MS-0001-26');
        $this->assertTrue($datos1['encontrada']);
        $this->assertEquals(28, $datos1['edad_dias'], "Debe calcular 28 días entre 2026-11-01 y 2026-11-29.");
        $this->assertEquals(24.5, $datos1['temperatura_c']);
        $this->assertEquals(28.274, $datos1['area']);

        $datos2 = obtenerDatosMuestraDesdeMatriz($filas, 'MS-0002-26');
        $this->assertTrue($datos2['encontrada']);
        $this->assertEquals(7, $datos2['edad_dias']);
        $this->assertEquals(25.0, $datos2['temperatura_c']);
    }

    /**
     * Verifica que sincronizarRoturasConMatriz actualiza en tiempo real
     * la carga, esfuerzos y fecha de ruptura de múltiples muestras en la matriz técnica.
     */
    public function testSincronizarRoturasConMatrizEnLote(): void {
        // Crear un detalle de cotización temporal para prueba
        $stmtInsert = $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal, resultados_json)
            VALUES (1, 1, 'Ensayo Resistencia Concreto ASTM C39', 2, 100, 200, :json)
        ");
        $jsonInicial = json_encode([
            'filas' => [
                [
                    'Código laboratorio' => 'MS-9991-26',
                    'Nombre muestra' => 'Probeta 1',
                    'Área (in²)' => 28.274,
                    'Carga (lb)' => 0,
                    'R. Compresión (lb/in²)' => 0,
                    'R. Compresión (kg/cm²)' => 0,
                    'Fecha de Fabricación' => '2026-11-01',
                    'Fecha de Ruptura' => '',
                    'Edad (Días)' => 28
                ],
                [
                    'Código laboratorio' => 'MS-9992-26',
                    'Nombre muestra' => 'Probeta 2',
                    'Área (in²)' => 28.274,
                    'Carga (lb)' => 0,
                    'R. Compresión (lb/in²)' => 0,
                    'R. Compresión (kg/cm²)' => 0,
                    'Fecha de Fabricación' => '2026-11-01',
                    'Fecha de Ruptura' => '',
                    'Edad (Días)' => 28
                ]
            ]
        ]);
        $stmtInsert->execute(['json' => $jsonInicial]);
        $idDetalleCreado = (int)$this->db->lastInsertId();

        try {
            $roturasEjecutadas = [
                [
                    'codigo_lab' => 'MS-9991-26',
                    'carga' => 70000,
                    'area' => 28.274,
                    'esfuerzo_psi' => 2475.8,
                    'esfuerzo_kgcm2' => 174.1,
                    'fecha_real' => '2026-11-29 10:30:00',
                    'edad_dias' => 28
                ],
                [
                    'codigo_lab' => 'MS-9992-26',
                    'carga' => 72500,
                    'area' => 28.274,
                    'esfuerzo_psi' => 2564.2,
                    'esfuerzo_kgcm2' => 180.3,
                    'fecha_real' => '2026-11-29 10:35:00',
                    'edad_dias' => 28
                ]
            ];

            $actualizadas = sincronizarRoturasConMatriz($this->db, 0, $idDetalleCreado, $roturasEjecutadas);
            $this->assertEquals(2, $actualizadas, "Ambas probetas del lote deben sincronizarse con la matriz técnica.");

            // Verificar contenido actualizado en BD
            $stmtCheck = $this->db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id");
            $stmtCheck->execute(['id' => $idDetalleCreado]);
            $resDec = json_decode((string)$stmtCheck->fetchColumn(), true);

            $this->assertNotEmpty($resDec['filas']);
            $f1 = $resDec['filas'][0];
            $this->assertEquals(70000, (float)$f1['Carga (lb)']);
            $this->assertEquals(2475.8, (float)$f1['R. Compresión (lb/in²)']);
            $this->assertEquals(174.1, (float)$f1['R. Compresión (kg/cm²)']);
            $this->assertEquals('2026-11-29', $f1['Fecha de Ruptura']);

            $f2 = $resDec['filas'][1];
            $this->assertEquals(72500, (float)$f2['Carga (lb)']);
            $this->assertEquals(2564.2, (float)$f2['R. Compresión (lb/in²)']);
            $this->assertEquals(180.3, (float)$f2['R. Compresión (kg/cm²)']);
            $this->assertEquals('2026-11-29', $f2['Fecha de Ruptura']);
        } finally {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id = :id")->execute(['id' => $idDetalleCreado]);
        }
    }

    /**
     * Prueba el ciclo completo de vida de roturas en lote para 2 muestras (MS-XXXX y MS-YYYY):
     * Configuración unificada de plan de roturas, generación de probetas y captura simultánea.
     */
    public function testFlujoCompletoRoturasLoteBatch(): void {
        $roturasModelo = new \Cycsa\Modulos\Operaciones\Modelos\RoturasModelo();
        $idHojaPrueba = 888888;
        $idOsPrueba = (int)$this->db->query("SELECT id FROM ordenes_servicio ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($idOsPrueba <= 0) {
            $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, fecha_emision, estado) VALUES ('OS-TEST-BATCH', 1, '2026-11-01', 'Estado 1: Recepcion')")->execute();
            $idOsPrueba = (int)$this->db->lastInsertId();
        }

        // 1. Crear 2 recepciones de muestra que comparten la misma hoja
        $stmtRm = $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, id_hoja, correlativo_anual, anio, tipo_muestra, codigo_muestra, id_cilindro, codigo_campo, orden_en_lote, recibido_por, estado)
            VALUES (:os, :hoja, :corr, 2026, 'Laboratorio', :cod, :cil, :camp, :ord, 1, 'Registrado')
        ");
        $stmtRm->execute(['os' => $idOsPrueba, 'hoja' => $idHojaPrueba, 'corr' => 991, 'cod' => 'MS-8881-26', 'cil' => 'MS-8881-26', 'camp' => 'MS-8881-26', 'ord' => 1]);
        $mId1 = (int)$this->db->lastInsertId();

        $stmtRm->execute(['os' => $idOsPrueba, 'hoja' => $idHojaPrueba, 'corr' => 992, 'cod' => 'MS-8882-26', 'cil' => 'MS-8882-26', 'camp' => 'MS-8882-26', 'ord' => 2]);
        $mId2 = (int)$this->db->lastInsertId();

        // Crear lotes_muestras
        $stmtLm = $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, fecha_moldeo, procedimiento_muestreo)
            VALUES (:rec, 'Concreto Lote Batch', '2026-11-01', 'ASTM C31')
        ");
        $stmtLm->execute(['rec' => $mId1]);
        $stmtLm->execute(['rec' => $mId2]);

        try {
            // 2. Programar plan de roturas en lote (ambas muestras juntas a 28 días)
            $plan1 = $roturasModelo->guardarPlanRotura($mId1, null, [['edad_dias' => 28, 'cantidad' => 1]], 1);
            $plan2 = $roturasModelo->guardarPlanRotura($mId2, null, [['edad_dias' => 28, 'cantidad' => 1]], 1);

            $this->assertTrue($plan1['exito'], "El plan para la muestra 1 debe configurarse con éxito.");
            $this->assertTrue($plan2['exito'], "El plan para la muestra 2 debe configurarse con éxito.");

            // 3. Verificar que ambas muestras tienen especímenes programados
            $stmtEsp = $this->db->prepare("SELECT id, codigo_lab, estado FROM especimenes WHERE muestra_id = :mid");
            
            $stmtEsp->execute(['mid' => $mId1]);
            $espList1 = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
            $this->assertCount(1, $espList1);
            $this->assertEquals('programado', $espList1[0]['estado']);

            $stmtEsp->execute(['mid' => $mId2]);
            $espList2 = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
            $this->assertCount(1, $espList2);
            $this->assertEquals('programado', $espList2[0]['estado']);

            // Obtener plan_edad_id de cada una
            $stmtPlanEdad = $this->db->prepare("SELECT pre.id FROM plan_rotura_edades pre JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id WHERE pr.muestra_id = :mid");
            $stmtPlanEdad->execute(['mid' => $mId1]);
            $peId1 = (int)$stmtPlanEdad->fetchColumn();

            $stmtPlanEdad->execute(['mid' => $mId2]);
            $peId2 = (int)$stmtPlanEdad->fetchColumn();

            // 4. Registrar rotura simultánea de ambas probetas juntas
            $resRot1 = $roturasModelo->registrarRotura([
                'especimen_id' => (int)$espList1[0]['id'],
                'plan_edad_id' => $peId1,
                'carga' => 68500,
                'area' => 28.2743,
                'fecha_real' => '2026-11-29 09:00:00',
                'tipo_falla' => 'Tipo 1 (Cono)',
                'justificacion' => 'Ensayo en lote programado'
            ], 1);
            $this->assertTrue($resRot1['exito']);
            $this->assertGreaterThan(2000, $resRot1['esfuerzo_psi']);

            $resRot2 = $roturasModelo->registrarRotura([
                'especimen_id' => (int)$espList2[0]['id'],
                'plan_edad_id' => $peId2,
                'carga' => 71000,
                'area' => 28.2743,
                'fecha_real' => '2026-11-29 09:05:00',
                'tipo_falla' => 'Tipo 1 (Cono)',
                'justificacion' => 'Ensayo en lote programado'
            ], 1);
            $this->assertTrue($resRot2['exito']);
            $this->assertGreaterThan(2000, $resRot2['esfuerzo_psi']);

            // 5. Verificar que ambas probetas pasaron a estado ensayado
            $stmtEsp->execute(['mid' => $mId1]);
            $this->assertEquals('ensayado', $stmtEsp->fetchColumn(2));

            $stmtEsp->execute(['mid' => $mId2]);
            $this->assertEquals('ensayado', $stmtEsp->fetchColumn(2));

            // 6. Verificar que ambos planes están cerrados
            $stmtPl = $this->db->prepare("SELECT estado FROM planes_rotura WHERE muestra_id = :mid");
            $stmtPl->execute(['mid' => $mId1]);
            $this->assertEquals('cerrado', $stmtPl->fetchColumn());

            $stmtPl->execute(['mid' => $mId2]);
            $this->assertEquals('cerrado', $stmtPl->fetchColumn());

        } finally {
            // Limpiar registros de prueba
            $this->db->prepare("DELETE FROM roturas WHERE especimen_id IN (SELECT id FROM especimenes WHERE muestra_id IN (:m1, :m2))")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM plan_rotura_asignaciones WHERE plan_edad_id IN (SELECT pre.id FROM plan_rotura_edades pre JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id WHERE pr.muestra_id IN (:m1, :m2))")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM plan_rotura_edades WHERE plan_rotura_id IN (SELECT id FROM planes_rotura WHERE muestra_id IN (:m1, :m2))")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM planes_rotura WHERE muestra_id IN (:m1, :m2)")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM especimenes WHERE muestra_id IN (:m1, :m2)")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id_recepcion IN (:m1, :m2)")->execute(['m1' => $mId1, 'm2' => $mId2]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id IN (:m1, :m2)")->execute(['m1' => $mId1, 'm2' => $mId2]);
        }
    }
}
