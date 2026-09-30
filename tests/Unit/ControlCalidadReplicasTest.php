<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ControlCalidadReplicasTest extends TestCase {
    public function testNormalizaSufijoRelacionYEvitaDuplicados(): void {
        $filas = [
            ['Código laboratorio' => 'MC-0001-26', 'Carga (lb)' => '100'],
            ['Código laboratorio' => 'MC-0002-26', 'Carga (lb)' => '200'],
            ['Código laboratorio' => 'codigo-manipulado', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Carga (lb)' => '102'],
            ['Código laboratorio' => 'MC-0001-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Carga (lb)' => '999'],
            ['Código laboratorio' => 'MC-9999-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-9999-26'],
        ];

        $normalizadas = normalizarReplicasMatriz($filas);

        $this->assertCount(3, $normalizadas);
        $this->assertSame('MC-0001-26-CR', $normalizadas[2]['Código laboratorio']);
        $this->assertTrue($normalizadas[2]['_es_replica']);
        $this->assertSame('MC-0001-26', $normalizadas[2]['_muestra_origen']);
        $this->assertSame('102', $normalizadas[2]['Carga (lb)']);
    }

    public function testAgrupaOriginalReplicaYCalculaDiferenciasNumericas(): void {
        $pares = obtenerParesControlCalidad([
            ['Código laboratorio' => 'MS-0042-26', 'Carga (lb)' => '100', 'Observación' => 'Original'],
            ['Código laboratorio' => 'MS-0042-26-CR', '_es_replica' => true, '_muestra_origen' => 'MS-0042-26', 'Carga (lb)' => '105', 'Observación' => 'Réplica'],
        ]);

        $this->assertCount(1, $pares);
        $this->assertSame('MS-0042-26', $pares[0]['codigo_original']);
        $this->assertSame('MS-0042-26-CR', $pares[0]['codigo_replica']);
        $this->assertSame(5.0, $pares[0]['diferencias']['Carga (lb)']['diferencia']);
        $this->assertSame(5.0, $pares[0]['diferencias']['Carga (lb)']['diferencia_porcentual']);
        $this->assertArrayNotHasKey('Observación', $pares[0]['diferencias']);
    }

    public function testReplicaEsExcluidaDeImpresionPdfYEnvioAlCliente(): void {
        $filasPublicas = filtrarFilasPublicasMatriz([
            ['Código laboratorio' => 'MC-0001-26', 'Resultado' => '98.5'],
            ['Código laboratorio' => 'MC-0001-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Resultado' => '99.1'],
            ['Código laboratorio' => 'MC-0002-26', 'Resultado' => '101.0'],
            ['Código laboratorio' => 'MC-0002-26-CR', 'Resultado' => '100.8'],
        ]);

        $this->assertCount(2, $filasPublicas);
        $this->assertSame(['MC-0001-26', 'MC-0002-26'], array_column($filasPublicas, 'Código laboratorio'));

        $vistaImpresion = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $helpers = file_get_contents(dirname(__DIR__, 2) . '/app/Helpers/funciones.php');
        $controlador = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/OperacionesControlador.php');
        $this->assertStringContainsString('filtrarFilasPublicasMatriz($resultados)', $vistaImpresion);
        $this->assertGreaterThanOrEqual(2, substr_count($helpers, 'filtrarFilasPublicasMatriz($resultados)'));
        $this->assertStringContainsString('filtrarFilasPublicasMatriz(is_array($resultados)', $controlador);
    }

    public function testVistaMatrizIncluyeSelectorYPersistenciaDeRelacion(): void {
        $vista = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $controlador = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/OperacionesControlador.php');
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');

        $this->assertStringContainsString('replica-selector', $vista);
        $this->assertStringContainsString('sincronizarReplicasSeleccionadas', $vista);
        $this->assertStringContainsString('_muestra_origen', $vista);
        $this->assertStringContainsString('normalizarReplicasMatriz', $controlador);
        $this->assertStringContainsString("'/control-calidad'", $rutas);
        $this->assertStringContainsString("'/control-calidad/evaluar-replica'", $rutas);
    }

    public function testVistaControlCalidadRenderizaComparacionInterna(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['csrf_token'] = 'csrf-prueba';
        $comparaciones = [[
            'id' => 10,
            'codigo_os' => 'OS-2026-001',
            'cliente_nombre' => 'Cliente Prueba',
            'nombre_proyecto' => 'Proyecto Interno',
            'descripcion_ensayo' => 'Compresión',
            'norma_astm' => 'ASTM C39',
            'codigo_original' => 'MC-0001-26',
            'codigo_replica' => 'MC-0001-26-CR',
            'diferencias' => [
                'Carga (lb)' => ['original' => 100.0, 'replica' => 102.0, 'diferencia' => 2.0, 'diferencia_porcentual' => 2.0],
            ],
            'evaluacion' => ['estado' => 'pendiente', 'observaciones' => '', 'usuario' => '', 'fecha' => ''],
        ]];

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/control_calidad_replicas.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('MC-0001-26-CR', $html);
        $this->assertStringContainsString('Carga (lb)', $html);
        $this->assertStringContainsString('Guardar evaluación', $html);
        $this->assertStringContainsString('csrf-prueba', $html);
    }

    public function testVistaControlCalidadRenderizaInformeCompletoConMatrizYMetadatos(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['csrf_token'] = 'csrf-token-123';
        $informes = [[
            'detalle' => [
                'id' => 45,
                'codigo_os' => 'OS-2026-0099',
                'descripcion_ensayo' => 'Compresión de Cilindros ASTM C39',
                'norma_astm' => 'ASTM C39'
            ],
            'schemaInfo' => [],
            'metaOficial' => [
                'cliente_nombre' => 'CEMEX Nicaragua S.A.',
                'proyecto' => 'Planta San Rafael',
                'cliente_direccion' => 'Carretera Norte Km 11',
                'ubicacion' => 'Punto A-10',
                'fecha_muestreo' => '2026-09-29',
                'fecha_ingreso' => '2026-09-29',
                'fecha_ejecucion' => '2026-09-30',
                'fecha_emision' => '2026-09-30',
                'tipo_muestra' => 'Cilindros de Concreto 6x12',
                'procedimiento_muestreo' => 'CYCSA-PE-05',
                'muestra_tomada_por' => 'Personal CYCSA',
                'metodo_muestreo' => 'ASTM C39',
                'codigo_formato' => 'CYCSA-RT-FM-22 A',
                'ensayo_realizado' => 'Compresión de Cilindros de Concreto'
            ],
            'codigoInformeConsecutivo' => 'CYCSA-INF-MC-0001-0002-26',
            'columnas' => ['Código laboratorio', 'Nombre muestra', 'Carga (lb)', 'R. Compresión (lb/in²)'],
            'filas' => [
                ['Código laboratorio' => 'MC-0001-26', 'Nombre muestra' => 'Cilindro 1', 'Carga (lb)' => '95000', 'R. Compresión (lb/in²)' => '3360'],
                ['Código laboratorio' => 'MC-0001-26-CR', 'Nombre muestra' => 'Cilindro 1 (Réplica CR)', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Carga (lb)' => '95800', 'R. Compresión (lb/in²)' => '3388'],
                ['Código laboratorio' => 'MC-0002-26', 'Nombre muestra' => 'Cilindro 2', 'Carga (lb)' => '98000', 'R. Compresión (lb/in²)' => '3466'],
            ],
            'pares' => [[
                'id' => 45,
                'codigo_original' => 'MC-0001-26',
                'codigo_replica' => 'MC-0001-26-CR',
                'diferencias' => [
                    'Carga (lb)' => ['original' => 95000.0, 'replica' => 95800.0, 'diferencia' => 800.0, 'diferencia_porcentual' => 0.84],
                ],
                'evaluacion' => ['estado' => 'conforme', 'observaciones' => 'Dentro de tolerancia de repetibilidad ASTM', 'usuario' => 'Ing. Supervisor', 'fecha' => '2026-09-30 13:00:00']
            ]]
        ]];

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/control_calidad_replicas.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('CEMEX Nicaragua S.A.', $html);
        $this->assertStringContainsString('Planta San Rafael', $html);
        $this->assertStringContainsString('CYCSA-INF-MC-0001-0002-26', $html);
        $this->assertStringContainsString('MC-0001-26-CR', $html);
        $this->assertStringContainsString('MC-0002-26', $html);
        $this->assertStringContainsString('RÉPLICA', $html);
        $this->assertStringContainsString('OS-2026-0099', $html);
        $this->assertStringContainsString('CYCSA-RT-FM-22 A', $html);
        $this->assertStringContainsString('tablaMaestraControles', $html);
        $this->assertStringContainsString('row-control-45', $html);
        $this->assertStringContainsString('detail-row-45', $html);
        $this->assertStringContainsString('toggleControlRow(45)', $html);
        $this->assertStringContainsString('filtroTextoControles', $html);
    }

    public function testConsultaControlCalidadEjecutaSinErroresDeColumna(): void {
        try {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->query("
                SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm, cd.resultados_json, cd.formato_reporte,
                       cd.procedimiento, cd.condiciones_muestra,
                       p.formato_id, p.nombre_comercial, p.ensayo_servicio, p.tipo_muestra AS prod_tipo_muestra, p.procedimiento_muestreo AS prod_procedimiento, p.norma_astm AS prod_norma_astm,
                       fe.archivo_markdown, fe.nombre AS formato_nombre, fe.codigo_formato AS codigo_documento, fe.procedimientos AS formato_procedimiento,
                       os.id AS id_os, os.codigo_os, os.created_at AS os_created_at, os.fecha_muestreo, os.fecha_emision AS os_fecha_emision,
                       cot.nombre_proyecto, cot.direccion_proyecto, cot.id_cliente, cot.atencion_a,
                       cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON p.id = cd.id_producto
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                JOIN cotizaciones cot ON cot.id = cd.id_cotizacion
                JOIN ordenes_servicio os ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cli.id = cot.id_cliente
                WHERE cd.resultados_json LIKE '%-CR%'
                ORDER BY cd.id DESC
                LIMIT 5
            ");
            $this->assertNotNull($stmt);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->assertIsArray($rows);
        } catch (\PDOException $e) {
            $this->fail("La consulta SQL de controlCalidadReplicas falló con error: " . $e->getMessage());
        }
    }
}
