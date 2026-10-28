<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Proyectos\Modelos\ProyectosModelo;
use Cycsa\Modulos\Proyectos\Controladores\ProyectosControlador;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;
use PDO;
use InvalidArgumentException;

class ProyectosCostosTest extends TestCase {

    private PDO $db;
    private ProyectosModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 020 esté ejecutada
        $db = $this->db;
        $ruta020 = dirname(__DIR__, 2) . '/database/migrations/020_proyectos_ordenes_obra.php';
        if (file_exists($ruta020)) {
            ob_start();
            include $ruta020;
            ob_end_clean();
        }

        $this->modelo = new ProyectosModelo($this->db);
    }

    /**
     * Valida que la migración 020 haya creado la tabla 'proyectos',
     * las columnas satélites 'proyecto_id' y las claves en config_cuentas.
     */
    public function testMigracion020CreaTablaProyectosYColumnasAditivas(): void {
        // 1. Tabla proyectos
        $checkProy = $this->db->query("SHOW TABLES LIKE 'proyectos'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($checkProy, "La tabla 'proyectos' debe existir.");

        // Columnas esperadas en proyectos
        $columnasProy = $this->db->query("SHOW COLUMNS FROM proyectos")->fetchAll(PDO::FETCH_COLUMN);
        $esperadas = [
            'id', 'codigo', 'nombre', 'cliente_id', 'ubicacion',
            'presupuesto_estimado', 'fecha_inicio', 'fecha_fin',
            'estado', 'cuenta_obra_proceso_id', 'created_at', 'updated_at'
        ];
        foreach ($esperadas as $col) {
            $this->assertContains($col, $columnasProy, "La columna '{$col}' debe existir en proyectos.");
        }

        // 2. Columnas aditivas proyecto_id en tablas relacionadas
        $tablasAditivas = [
            'ordenes_compra',
            'ordenes_compra_detalle',
            'cuentas_por_pagar_detalle',
            'inventario_movimientos',
            'rrhh_nomina_detalles'
        ];
        foreach ($tablasAditivas as $tabla) {
            $cols = $this->db->query("SHOW COLUMNS FROM `{$tabla}` LIKE 'proyecto_id'")->fetchAll();
            $this->assertNotEmpty($cols, "La tabla '{$tabla}' debe contar con la columna 'proyecto_id'.");
        }

        // 3. Sembrado de claves en config_cuentas
        $stmtCfg = $this->db->query("SELECT clave, codigo_cuenta FROM config_cuentas WHERE clave IN ('OBRAS_EN_PROCESO', 'COSTOS_CONSTRUCCION')");
        $claves = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assertArrayHasKey('OBRAS_EN_PROCESO', $claves, "Debe existir la clave 'OBRAS_EN_PROCESO' en config_cuentas.");
        $this->assertArrayHasKey('COSTOS_CONSTRUCCION', $claves, "Debe existir la clave 'COSTOS_CONSTRUCCION' en config_cuentas.");
    }

    /**
     * Valida el CRUD completo de proyectos y reglas de negocio.
     */
    public function testCrudProyecto(): void {
        $codigoUnico = 'PRY-TEST-' . bin2hex(random_bytes(3));
        $nombre = 'Construcción Laboratorio Móvil Zona Sur';

        // 1. Crear
        $proyectoId = $this->modelo->crear([
            'codigo'               => $codigoUnico,
            'nombre'               => $nombre,
            'ubicacion'            => 'Rivas, Km 110',
            'presupuesto_estimado' => 75000.00,
            'fecha_inicio'         => '2026-11-01',
            'fecha_fin'            => '2026-12-31',
            'estado'               => 'planificado',
        ]);
        $this->assertGreaterThan(0, $proyectoId, "El ID del proyecto creado debe ser mayor a 0.");

        // 2. Obtener por ID
        $proyecto = $this->modelo->obtenerPorId($proyectoId);
        $this->assertNotNull($proyecto, "El proyecto recién creado debe ser consultable.");
        $this->assertEquals($codigoUnico, $proyecto['codigo']);
        $this->assertEquals($nombre, $proyecto['nombre']);
        $this->assertEquals(75000.00, $proyecto['presupuesto_estimado']);
        $this->assertEquals('planificado', $proyecto['estado']);

        // 3. Actualizar
        $actualizado = $this->modelo->actualizar($proyectoId, [
            'nombre'               => 'Construcción Laboratorio Móvil Zona Sur - Fase 1',
            'presupuesto_estimado' => 80000.00,
            'estado'               => 'en_ejecucion',
        ]);
        $this->assertTrue($actualizado, "La actualización del proyecto debe retornar true.");

        $proyectoMod = $this->modelo->obtenerPorId($proyectoId);
        $this->assertEquals('Construcción Laboratorio Móvil Zona Sur - Fase 1', $proyectoMod['nombre']);
        $this->assertEquals(80000.00, $proyectoMod['presupuesto_estimado']);
        $this->assertEquals('en_ejecucion', $proyectoMod['estado']);

        // 4. Cambiar estado
        $this->modelo->cambiarEstado($proyectoId, 'completado');
        $proyectoFin = $this->modelo->obtenerPorId($proyectoId);
        $this->assertEquals('completado', $proyectoFin['estado']);

        // 5. Estado inválido arroja excepción
        $this->expectException(InvalidArgumentException::class);
        $this->modelo->cambiarEstado($proyectoId, 'estado_invalido_xyz');
    }

    /**
     * Valida que no se pueda registrar un nombre vacío o un código duplicado.
     */
    public function testValidacionesCreacionProyecto(): void {
        // Nombre vacío
        try {
            $this->modelo->crear([
                'nombre' => '',
            ]);
            $this->fail("Debe lanzar excepción si el nombre está vacío.");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('nombre', $e->getMessage());
        }

        // Código duplicado
        $codigoDup = 'PRY-DUP-' . bin2hex(random_bytes(3));
        $this->modelo->crear([
            'codigo' => $codigoDup,
            'nombre' => 'Proyecto Original',
        ]);

        try {
            $this->modelo->crear([
                'codigo' => $codigoDup,
                'nombre' => 'Proyecto Duplicado',
            ]);
            $this->fail("Debe lanzar excepción si el código ya existe.");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('código', $e->getMessage());
        }
    }

    /**
     * Valida el Cuadrante 1: Costo Compras / Órdenes de Compra (OC).
     */
    public function testImputacionComprasOC(): void {
        $proyectoId = $this->modelo->crear([
            'codigo'               => 'PRY-OC-' . bin2hex(random_bytes(3)),
            'nombre'               => 'Proyecto de Prueba Compras OC',
            'presupuesto_estimado' => 20000.00,
            'fecha_inicio'         => date('Y-m-d'),
        ]);

        // Insertar líneas en ordenes_compra_detalle asociadas al proyecto
        $stmt = $this->db->prepare("
            INSERT INTO ordenes_compra_detalle (
                orden_compra_id, proyecto_id, descripcion, cantidad, costo_unitario, subtotal, iva, total
            ) VALUES (
                1, :pid, 'Cemento Portland Gris Tipo I', 100, 25.00, 2500.00, 375.00, 2875.00
            ), (
                1, :pid2, 'Varilla de Acero 1/2 pulgada', 50, 10.00, 500.00, 75.00, 575.00
            )
        ");
        $stmt->execute(['pid' => $proyectoId, 'pid2' => $proyectoId]);

        $costos = $this->modelo->obtenerCostosConsolidados($proyectoId);

        // Total compras esperado: 2875.00 + 575.00 = 3450.00
        $this->assertEquals(3450.00, $costos['costo_compras'], "El costo de compras debe coincidir con el total de partidas.");
        $this->assertEquals(3450.00, $costos['compras_oc']);
        $this->assertCount(2, $costos['desglose']['compras']);
    }

    /**
     * Valida el Cuadrante 2: Costo Gastos Directos (Cuentas por Pagar CXP).
     */
    public function testImputacionGastosDirectosCXP(): void {
        $proyectoId = $this->modelo->crear([
            'codigo'               => 'PRY-CXP-' . bin2hex(random_bytes(3)),
            'nombre'               => 'Proyecto de Prueba Gastos CXP',
            'presupuesto_estimado' => 30000.00,
            'fecha_inicio'         => date('Y-m-d'),
        ]);

        // Insertar partidas en cuentas_por_pagar_detalle con proyecto_id
        $stmt = $this->db->prepare("
            INSERT INTO cuentas_por_pagar_detalle (
                cxp_id, cuenta_contable_id, proyecto_id, concepto, monto, iva, total
            ) VALUES (
                1, 220, :pid, 'Alquiler de Retroexcavadora CAT 420F', 4000.00, 600.00, 4600.00
            ), (
                1, 220, :pid2, 'Combustible Diésel para Maquinaria', 1200.00, 180.00, 1380.00
            )
        ");
        $stmt->execute(['pid' => $proyectoId, 'pid2' => $proyectoId]);

        $costos = $this->modelo->obtenerCostosConsolidados($proyectoId);

        // Total gastos esperado: 4600.00 + 1380.00 = 5980.00
        $this->assertEquals(5980.00, $costos['costo_gastos'], "El costo de gastos directos debe sumar las líneas de CXP.");
        $this->assertEquals(5980.00, $costos['gastos_directos']);
        $this->assertCount(2, $costos['desglose']['gastos']);
    }

    /**
     * Valida el Cuadrante 3: Costo Insumos de Inventario / Kárdex (SUM(cantidad * costo_unitario)).
     */
    public function testImputacionInsumosKardex(): void {
        $proyectoId = $this->modelo->crear([
            'codigo'               => 'PRY-INV-' . bin2hex(random_bytes(3)),
            'nombre'               => 'Proyecto de Prueba Insumos Kárdex',
            'presupuesto_estimado' => 15000.00,
            'fecha_inicio'         => date('Y-m-d'),
        ]);

        // Insertar consumos en inventario_movimientos con proyecto_id
        $stmt = $this->db->prepare("
            INSERT INTO inventario_movimientos (
                insumo_id, proyecto_id, tipo_movimiento, cantidad, costo_unitario, observaciones
            ) VALUES (
                1, :pid, 'salida_consumo', 20.00, 150.00, 'Tubería PVC 2 pulgada'
            ), (
                1, :pid2, 'consumo_ensayo', 15.00, 80.00, 'Arena sílice graduada'
            )
        ");
        $stmt->execute(['pid' => $proyectoId, 'pid2' => $proyectoId]);

        $costos = $this->modelo->obtenerCostosConsolidados($proyectoId);

        // Esperado: (20 * 150) + (15 * 80) = 3000 + 1200 = 4200.00
        $this->assertEquals(4200.00, $costos['costo_insumos'], "El costo de insumos debe ser SUM(cantidad * costo_unitario).");
        $this->assertEquals(4200.00, $costos['insumos_kardex']);
        $this->assertCount(2, $costos['desglose']['insumos']);
    }

    /**
     * Valida el Cuadrante 4: Costo Mano de Obra (SUM(total_devengado) en rrhh_nomina_detalles).
     */
    public function testImputacionManoDeObraNomina(): void {
        $proyectoId = $this->modelo->crear([
            'codigo'               => 'PRY-NOM-' . bin2hex(random_bytes(3)),
            'nombre'               => 'Proyecto de Prueba Mano de Obra Nómina',
            'presupuesto_estimado' => 25000.00,
            'fecha_inicio'         => date('Y-m-d'),
        ]);

        // Insertar detalles en rrhh_nomina_detalles con proyecto_id
        $stmt = $this->db->prepare("
            INSERT INTO rrhh_nomina_detalles (
                nomina_id, empleado_id, proyecto_id, salario_base, horas_extras_monto, total_devengado,
                total_deducciones, neto_a_pagar
            ) VALUES (
                1, 1, :pid, 6000.00, 1000.00, 7000.00, 800.00, 6200.00
            ), (
                1, 2, :pid2, 5000.00, 500.00, 5500.00, 600.00, 4900.00
            )
        ");
        $stmt->execute(['pid' => $proyectoId, 'pid2' => $proyectoId]);

        $costos = $this->modelo->obtenerCostosConsolidados($proyectoId);

        // Esperado: 7000.00 + 5500.00 = 12500.00
        $this->assertEquals(12500.00, $costos['costo_mano_obra'], "El costo de mano de obra debe ser SUM(total_devengado).");
        $this->assertEquals(12500.00, $costos['mano_obra']);
        $this->assertCount(2, $costos['desglose']['mano_obra']);
    }

    /**
     * Valida la agregación transversal de los 4 cuadrantes,
     * cálculo de costo total acumulado, porcentaje de ejecución y desviación (superávit / déficit).
     */
    public function testConsolidacionTransversalYDesviacionPresupuestaria(): void {
        $presupuestoEstimado = 100000.00;
        $proyectoId = $this->modelo->crear([
            'codigo'               => 'PRY-CONSOL-' . bin2hex(random_bytes(3)),
            'nombre'               => 'Proyecto Integral de Consolidación Transversal',
            'presupuesto_estimado' => $presupuestoEstimado,
            'fecha_inicio'         => '2026-10-01',
            'fecha_fin'            => '2026-11-30',
            'estado'               => 'en_ejecucion',
        ]);

        // 1. Cuadrante Compras: 20,000.00
        $this->db->prepare("
            INSERT INTO ordenes_compra_detalle (orden_compra_id, proyecto_id, descripcion, cantidad, costo_unitario, subtotal, total)
            VALUES (1, :pid, 'Materiales Estructurales', 1, 20000.00, 20000.00, 20000.00)
        ")->execute(['pid' => $proyectoId]);

        // 2. Cuadrante Gastos CXP: 15,000.00
        $this->db->prepare("
            INSERT INTO cuentas_por_pagar_detalle (cxp_id, cuenta_contable_id, proyecto_id, concepto, monto, total)
            VALUES (1, 220, :pid, 'Servicio de Topografía y Ensayos Geotécnicos', 15000.00, 15000.00)
        ")->execute(['pid' => $proyectoId]);

        // 3. Cuadrante Insumos Kárdex: 10,000.00 (200 x 50)
        $this->db->prepare("
            INSERT INTO inventario_movimientos (insumo_id, proyecto_id, tipo_movimiento, cantidad, costo_unitario)
            VALUES (1, :pid, 'salida_consumo', 200.00, 50.00)
        ")->execute(['pid' => $proyectoId]);

        // 4. Cuadrante Mano de Obra: 25,000.00
        $this->db->prepare("
            INSERT INTO rrhh_nomina_detalles (nomina_id, empleado_id, proyecto_id, salario_base, total_devengado, total_deducciones, neto_a_pagar)
            VALUES (1, 1, :pid, 25000.00, 25000.00, 3000.00, 22000.00)
        ")->execute(['pid' => $proyectoId]);

        // Consultar consolidación
        $costos = $this->modelo->obtenerCostosConsolidados($proyectoId);

        // Suma esperada: 20000 + 15000 + 10000 + 25000 = 70,000.00
        $this->assertEquals(20000.00, $costos['compras_oc']);
        $this->assertEquals(15000.00, $costos['gastos_directos']);
        $this->assertEquals(10000.00, $costos['insumos_kardex']);
        $this->assertEquals(25000.00, $costos['mano_obra']);
        $this->assertEquals(70000.00, $costos['costo_total']);

        // Presupuesto y desviación
        $this->assertEquals(100000.00, $costos['presupuesto_estimado']);
        $this->assertEquals(30000.00, $costos['desviacion'], "La desviación inicial debe ser de C$ 30,000 de superávit.");
        $this->assertEquals(70.0, $costos['porcentaje_consumido'], "El porcentaje consumido debe ser exactamente 70%.");
        $this->assertEquals('superavit', $costos['estado_presupuesto']);

        // 5. Escenario de sobrecosto (déficit): añadir gasto extraordinario de C$ 40,000 (total 110,000 > 100,000)
        $this->db->prepare("
            INSERT INTO cuentas_por_pagar_detalle (cxp_id, cuenta_contable_id, proyecto_id, concepto, monto, total)
            VALUES (1, 220, :pid, 'Reparación de Emergencia en Tramo Hundido', 40000.00, 40000.00)
        ")->execute(['pid' => $proyectoId]);

        $costosDeficit = $this->modelo->obtenerCostosConsolidados($proyectoId);
        $this->assertEquals(110000.00, $costosDeficit['costo_total']);
        $this->assertEquals(-10000.00, $costosDeficit['desviacion'], "La desviación debe ser negativa (-10,000.00) indicando déficit.");
        $this->assertEquals(110.0, $costosDeficit['porcentaje_consumido']);
        $this->assertEquals('deficit', $costosDeficit['estado_presupuesto']);

        // Verificar cálculo de avance
        $proyecto = $this->modelo->obtenerPorId($proyectoId);
        $this->assertArrayHasKey('avance', $proyecto);
        $this->assertEquals(110.0, $proyecto['avance']['porcentaje_presupuesto']);
        $this->assertEquals('deficit', $proyecto['avance']['tipo_desviacion']);
    }

    /**
     * Valida que MigradorServicio::asegurarTablas ejecute la FASE 14 de forma idempotente.
     */
    public function testMigradorServicioIdempotenciaFase14(): void {
        $resultados = MigradorServicio::asegurarTablas($this->db);
        $this->assertIsArray($resultados);
        $this->assertArrayNotHasKey('error', $resultados, "MigradorServicio no debe reportar errores al ejecutar migraciones.");
    }

    /**
     * Valida que ProyectosControlador se instancie y exponga sus métodos requeridos.
     */
    public function testControladorProyectos(): void {
        $controlador = new ProyectosControlador($this->modelo);
        $this->assertInstanceOf(ProyectosControlador::class, $controlador);

        $metodosEsperados = ['index', 'crear', 'guardar', 'detalle', 'editar', 'actualizar', 'cambiarEstado', 'apiCostos'];
        foreach ($metodosEsperados as $m) {
            $this->assertTrue(method_exists($controlador, $m), "ProyectosControlador debe contar con el método '{$m}'.");
        }
    }
}
