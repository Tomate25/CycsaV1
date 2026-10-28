<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;

class ComprasMigracion012Test extends TestCase {

    private PDO $db;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 012 ha sido ejecutada
        ob_start();
        require_once dirname(__DIR__, 2) . '/database/migrations/012_compras_inventario_proveedores.php';
        ob_end_clean();
    }

    public function testMigradorServicioEjecucionIdempotente(): void {
        $resultados = MigradorServicio::asegurarTablas($this->db);
        $this->assertIsArray($resultados);
        $this->assertArrayNotHasKey('error', $resultados, 'MigradorServicio no debe arrojar errores');
    }

    public function testTodasLasTablasExisten(): void {
        $tablasEsperadas = [
            'proveedores',
            'ordenes_compra',
            'ordenes_compra_detalle',
            'recepciones_compra',
            'recepciones_compra_detalle',
            'inventario_insumos',
            'inventario_lotes',
            'inventario_movimientos',
        ];

        foreach ($tablasEsperadas as $tabla) {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$tabla}'");
            $this->assertNotEmpty($stmt->fetchAll(PDO::FETCH_COLUMN), "La tabla '{$tabla}' debe existir en la base de datos.");
        }
    }

    public function testColumnasTablaProveedores(): void {
        $columnasEsperadas = [
            'id',
            'tipo_id',
            'identificacion',
            'razon_social',
            'contacto',
            'telefono',
            'email',
            'banco',
            'cuenta_bancaria',
            'plazo_pago_dias',
            'id_cuenta_contable',
            'cuenta_anticipo_id',
            'categoria',
            'evaluado',
            'fecha_evaluacion',
            'estado',
            'created_at',
            'updated_at',
        ];

        $columnasActuales = $this->obtenerColumnas('proveedores');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'proveedores'.");
        }

        $this->assertStringContainsString('enum', strtolower($columnasActuales['tipo_id']['Type']));
        $this->assertStringContainsString('enum', strtolower($columnasActuales['estado']['Type']));
    }

    public function testColumnasTablaOrdenesCompra(): void {
        $columnasEsperadas = [
            'id',
            'proveedor',
            'fecha',
            'total',
            'estado',
            'created_at',
            'updated_at',
            'proveedor_id',
            'subtotal',
            'iva',
            'aprobado_por',
            'fecha_aprobacion',
        ];

        $columnasActuales = $this->obtenerColumnas('ordenes_compra');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'ordenes_compra'.");
        }
    }

    public function testColumnasTablaOrdenesCompraDetalle(): void {
        $columnasEsperadas = [
            'id',
            'orden_compra_id',
            'insumo_id',
            'descripcion',
            'cantidad',
            'cantidad_recibida',
            'costo_unitario',
            'subtotal',
            'iva',
            'total',
            'cuenta_contable_id',
            'created_at',
            'updated_at',
        ];

        $columnasActuales = $this->obtenerColumnas('ordenes_compra_detalle');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'ordenes_compra_detalle'.");
        }
    }

    public function testColumnasTablaRecepcionesCompra(): void {
        $columnasEsperadas = [
            'id',
            'orden_compra_id',
            'proveedor_id',
            'numero_factura',
            'fecha_recepcion',
            'recibido_por',
            'observaciones',
            'estado',
            'created_at',
            'updated_at',
        ];

        $columnasActuales = $this->obtenerColumnas('recepciones_compra');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'recepciones_compra'.");
        }

        $this->assertStringContainsString('enum', strtolower($columnasActuales['estado']['Type']));
    }

    public function testColumnasTablaRecepcionesCompraDetalle(): void {
        $columnasEsperadas = [
            'id',
            'recepcion_id',
            'orden_compra_detalle_id',
            'insumo_id',
            'cantidad_recibida',
            'costo_unitario',
            'lote',
            'fecha_vencimiento',
            'created_at',
        ];

        $columnasActuales = $this->obtenerColumnas('recepciones_compra_detalle');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'recepciones_compra_detalle'.");
        }
    }

    public function testColumnasTablaInventarioInsumos(): void {
        $columnasEsperadas = [
            'id',
            'codigo',
            'nombre',
            'cantidad',
            'unidad_medida',
            'stock_minimo',
            'created_at',
            'updated_at',
            'cuenta_inventario_id',
        ];

        $columnasActuales = $this->obtenerColumnas('inventario_insumos');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'inventario_insumos'.");
        }
    }

    public function testColumnasTablaInventarioLotes(): void {
        $columnasEsperadas = [
            'id',
            'insumo_id',
            'lote',
            'fecha_vencimiento',
            'costo_unitario',
            'cantidad_inicial',
            'saldo',
            'estado',
            'created_at',
            'updated_at',
        ];

        $columnasActuales = $this->obtenerColumnas('inventario_lotes');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'inventario_lotes'.");
        }

        $this->assertStringContainsString('enum', strtolower($columnasActuales['estado']['Type']));
    }

    public function testColumnasTablaInventarioMovimientos(): void {
        $columnasEsperadas = [
            'id',
            'insumo_id',
            'lote_id',
            'tipo_movimiento',
            'cantidad',
            'costo_unitario',
            'saldo_anterior',
            'saldo_nuevo',
            'referencia_tipo',
            'referencia_id',
            'usuario_id',
            'observaciones',
            'created_at',
        ];

        $columnasActuales = $this->obtenerColumnas('inventario_movimientos');
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $columnasActuales, "La columna '{$col}' debe existir en 'inventario_movimientos'.");
        }

        $this->assertStringContainsString('enum', strtolower($columnasActuales['tipo_movimiento']['Type']));
    }

    public function testIndicesExisten(): void {
        $this->assertIndexExiste('proveedores', 'idx_prov_estado');
        $this->assertIndexExiste('proveedores', 'idx_prov_categoria');
        $this->assertIndexExiste('ordenes_compra', 'idx_oc_proveedor_id');
        $this->assertIndexExiste('ordenes_compra_detalle', 'idx_ocd_orden_compra_id');
        $this->assertIndexExiste('recepciones_compra', 'idx_rc_proveedor_id');
        $this->assertIndexExiste('recepciones_compra_detalle', 'idx_rcd_recepcion_id');
        $this->assertIndexExiste('inventario_insumos', 'idx_inv_cuenta');
        $this->assertIndexExiste('inventario_lotes', 'idx_lotes_insumo_id');
        $this->assertIndexExiste('inventario_movimientos', 'idx_mov_insumo_id');
        $this->assertIndexExiste('inventario_movimientos', 'idx_mov_tipo');
    }

    public function testTablasSonConsultablesYOperablesCrud(): void {
        $identificadorUnico = 'TEST-PRV-' . bin2hex(random_bytes(4));

        // 1. Inserción y consulta en proveedores
        $stmtProv = $this->db->prepare("
            INSERT INTO proveedores (tipo_id, identificacion, razon_social, contacto, telefono, email, banco, cuenta_bancaria, plazo_pago_dias, categoria, estado)
            VALUES ('RUC', :identificacion, 'Proveedor Test S.A.', 'Juan Perez', '2222-3333', 'proveedor@test.com', 'BAC', '9988776655', 45, 'insumos', 'activo')
        ");
        $stmtProv->execute([':identificacion' => $identificadorUnico]);
        $proveedorId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $proveedorId);

        $provConsultado = $this->db->query("SELECT * FROM proveedores WHERE id = {$proveedorId}")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($identificadorUnico, $provConsultado['identificacion']);
        $this->assertEquals('Proveedor Test S.A.', $provConsultado['razon_social']);
        $this->assertEquals(45, (int)$provConsultado['plazo_pago_dias']);

        // 2. Inserción y consulta en inventario_insumos
        $codigoInsumo = 'INS-' . bin2hex(random_bytes(4));
        $stmtIns = $this->db->prepare("
            INSERT INTO inventario_insumos (codigo, nombre, cantidad, unidad_medida, stock_minimo)
            VALUES (:codigo, 'Reactivo Químico Especial', 100.00, 'litro', 20.00)
        ");
        $stmtIns->execute([':codigo' => $codigoInsumo]);
        $insumoId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $insumoId);

        // 3. Inserción y consulta en inventario_lotes
        $stmtLote = $this->db->prepare("
            INSERT INTO inventario_lotes (insumo_id, lote, fecha_vencimiento, costo_unitario, cantidad_inicial, saldo, estado)
            VALUES (:insumo_id, 'LOT-2026-001', '2027-12-31', 15.5000, 100.00, 100.00, 'activo')
        ");
        $stmtLote->execute([':insumo_id' => $insumoId]);
        $loteId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $loteId);

        // 4. Inserción en ordenes_compra y detalle
        $stmtOC = $this->db->prepare("
            INSERT INTO ordenes_compra (proveedor, fecha, total, subtotal, iva, proveedor_id, estado)
            VALUES ('Proveedor Test S.A.', '2026-10-27', 1782.50, 1550.00, 232.50, :proveedor_id, 'SOLICITADA')
        ");
        $stmtOC->execute([':proveedor_id' => $proveedorId]);
        $ocId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $ocId);

        $stmtOCD = $this->db->prepare("
            INSERT INTO ordenes_compra_detalle (orden_compra_id, insumo_id, descripcion, cantidad, costo_unitario, subtotal, iva, total)
            VALUES (:orden_id, :insumo_id, 'Reactivo Químico', 100.00, 15.5000, 1550.00, 232.50, 1782.50)
        ");
        $stmtOCD->execute([':orden_id' => $ocId, ':insumo_id' => $insumoId]);
        $ocdId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $ocdId);

        // 5. Inserción en recepciones_compra y detalle
        $stmtRC = $this->db->prepare("
            INSERT INTO recepciones_compra (orden_compra_id, proveedor_id, numero_factura, fecha_recepcion, recibido_por, estado)
            VALUES (:orden_id, :proveedor_id, 'FACT-00192', '2026-10-27', 1, 'completada')
        ");
        $stmtRC->execute([':orden_id' => $ocId, ':proveedor_id' => $proveedorId]);
        $rcId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $rcId);

        $stmtRCD = $this->db->prepare("
            INSERT INTO recepciones_compra_detalle (recepcion_id, orden_compra_detalle_id, insumo_id, cantidad_recibida, costo_unitario, lote)
            VALUES (:rec_id, :ocd_id, :insumo_id, 100.00, 15.5000, 'LOT-2026-001')
        ");
        $stmtRCD->execute([':rec_id' => $rcId, ':ocd_id' => $ocdId, ':insumo_id' => $insumoId]);
        $rcdId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $rcdId);

        // 6. Inserción en inventario_movimientos
        $stmtMov = $this->db->prepare("
            INSERT INTO inventario_movimientos (insumo_id, lote_id, tipo_movimiento, cantidad, costo_unitario, saldo_anterior, saldo_nuevo, referencia_tipo, referencia_id, observaciones)
            VALUES (:insumo_id, :lote_id, 'entrada_compra', 100.00, 15.5000, 0.00, 100.00, 'recepcion_compra', :rc_id, 'Entrada por factura FACT-00192')
        ");
        $stmtMov->execute([
            ':insumo_id' => $insumoId,
            ':lote_id' => $loteId,
            ':rc_id' => $rcId,
        ]);
        $movId = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $movId);

        // Consulta relacional con JOIN entre las tablas
        $consulta = $this->db->query("
            SELECT oc.id AS oc_id, p.razon_social, ocd.descripcion, r.numero_factura, m.tipo_movimiento, m.cantidad
            FROM ordenes_compra oc
            JOIN proveedores p ON p.id = oc.proveedor_id
            JOIN ordenes_compra_detalle ocd ON ocd.orden_compra_id = oc.id
            JOIN recepciones_compra r ON r.orden_compra_id = oc.id
            JOIN inventario_movimientos m ON m.referencia_id = r.id AND m.referencia_tipo = 'recepcion_compra'
            WHERE oc.id = {$ocId}
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($consulta);
        $this->assertEquals('Proveedor Test S.A.', $consulta['razon_social']);
        $this->assertEquals('FACT-00192', $consulta['numero_factura']);
        $this->assertEquals('entrada_compra', $consulta['tipo_movimiento']);
        $this->assertEquals(100.00, (float)$consulta['cantidad']);

        // Limpieza de datos de prueba
        $this->db->exec("DELETE FROM inventario_movimientos WHERE id = {$movId}");
        $this->db->exec("DELETE FROM recepciones_compra_detalle WHERE id = {$rcdId}");
        $this->db->exec("DELETE FROM recepciones_compra WHERE id = {$rcId}");
        $this->db->exec("DELETE FROM ordenes_compra_detalle WHERE id = {$ocdId}");
        $this->db->exec("DELETE FROM ordenes_compra WHERE id = {$ocId}");
        $this->db->exec("DELETE FROM inventario_lotes WHERE id = {$loteId}");
        $this->db->exec("DELETE FROM inventario_insumos WHERE id = {$insumoId}");
        $this->db->exec("DELETE FROM proveedores WHERE id = {$proveedorId}");
    }

    private function obtenerColumnas(string $tabla): array {
        $stmt = $this->db->query("DESCRIBE `{$tabla}`");
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnas = [];
        foreach ($filas as $fila) {
            $columnas[$fila['Field']] = $fila;
        }
        return $columnas;
    }

    private function assertIndexExiste(string $tabla, string $nombreIndice): void {
        $stmt = $this->db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = '{$nombreIndice}'");
        $this->assertNotEmpty($stmt->fetchAll(), "El índice '{$nombreIndice}' debe existir en la tabla '{$tabla}'.");
    }
}
