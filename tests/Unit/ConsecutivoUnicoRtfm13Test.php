<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;

require_once dirname(__DIR__, 2) . '/database/migrations/033_consecutivo_unico_rtfm13.php';

class ConsecutivoUnicoRtfm13Test extends TestCase {

    private PDO $db;
    private OperacionModelo $opModelo;
    private OrdenServicioModelo $osModelo;

    private int $idCliente = 0;
    private int $idCotizacion = 0;
    private int $idOS1 = 0;
    private int $idOS2 = 0;
    private int $idProd = 0;
    private array $detalleIds = [];
    private array $hojasCreadas = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($this->db);

        $this->opModelo = new OperacionModelo();
        $this->osModelo = new OrdenServicioModelo();

        $idUser = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser <= 0) {
            $this->db->exec("INSERT INTO usuarios (id_rol, nombre, email, password, activo) VALUES (1, 'Tester Cons', 'cons@test.local', 'hash', 1)");
            $idUser = (int)$this->db->lastInsertId();
        }

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($this->idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Consecutivo', 'J0310000008888', 'cons@test.local', '22225555')");
            $this->idCliente = (int)$this->db->lastInsertId();
        }

        $this->idProd = (int)$this->db->query("SELECT id FROM productos LIMIT 1")->fetchColumn();

        $codCot = 'COT-CS-' . substr(uniqid(), -8);
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:cod, :cli, :usr, 'Ing. Obra', 'Proyecto Consecutivo', 'Contado', 'Aprobada por Cliente', 1000, 1150)");
        $stmtCot->execute(['cod' => $codCot, 'cli' => $this->idCliente, 'usr' => $idUser]);
        $this->idCotizacion = (int)$this->db->lastInsertId();

        for ($i = 1; $i <= 4; $i++) {
            $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, :desc, 'SRV-CS', 'ASTM', 1, 250, 250)");
            $stmtDet->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProd, 'desc' => "Ensayo #$i"]);
            $this->detalleIds[] = (int)$this->db->lastInsertId();
        }

        $this->idOS1 = $this->osModelo->crear([
            'codigo_os' => 'OS-CS1-' . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Obra',
            'nombre_proyecto' => 'Proyecto Consecutivo 1',
            'forma_pago' => 'Contado',
            'estado' => 'Aprobada',
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS1}");

        $this->idOS2 = $this->osModelo->crear([
            'codigo_os' => 'OS-CS2-' . uniqid(),
            'id_cotizacion' => $this->idCotizacion,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'atencion_a' => 'Ing. Obra',
            'nombre_proyecto' => 'Proyecto Consecutivo 2',
            'forma_pago' => 'Contado',
            'estado' => 'Aprobada',
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS2}");
    }

    protected function tearDown(): void {
        if (!empty($this->hojasCreadas)) {
            $in = implode(',', array_map('intval', $this->hojasCreadas));
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$in})");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$in})");
            } catch (\Throwable $e) {}
        }
        foreach ([$this->idOS1, $this->idOS2] as $osId) {
            if ($osId > 0) {
                try {
                    $this->db->exec("DELETE FROM hojas_solicitud WHERE id_os = {$osId}");
                    $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$osId}");
                } catch (\Throwable $e) {}
            }
        }
        if ($this->idCotizacion > 0) {
            try {
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacion}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacion}");
            } catch (\Throwable $e) {}
        }
        parent::tearDown();
    }

    public function testConsecutivoUnicoGlobalSecuencialHastaInfinitoEntreOrdenes(): void {
        $asignados = [];
        $osPorDetalle = [$this->idOS1, $this->idOS1, $this->idOS2, $this->idOS2];

        foreach ($this->detalleIds as $idx => $idDet) {
            $idH = 0;
            // Simular incluso intento de mandar formato compuesto heredado en el 2do registro
            $numEntrada = ($idx === 1) ? '00001-02' : '';
            $ok = $this->opModelo->guardarHojaSolicitud([
                'id_os' => $osPorDetalle[$idx],
                'id_hoja' => 0,
                'id_detalle_cotizacion' => $idDet,
                'id_producto' => $this->idProd,
                'ensayo_id' => $this->idProd,
                'numero_registro' => $numEntrada,
                'nombre_empresa_o_cliente' => 'Cliente Consecutivo',
                'identificacion_muestras_json' => '[]',
            ], $idH);
            $this->assertTrue($ok);
            $this->hojasCreadas[] = $idH;

            $hoja = $this->opModelo->obtenerHojaSolicitudPorId($idH);
            $asignados[] = $hoja['numero_registro'];
        }

        // Debe ser consecutivo único 01, 02, 03, 04 (sin 00001-02 ni 00002-02)
        $this->assertSame(['01', '02', '03', '04'], $asignados);

        // Verificar salto correcto a 10, 99, 100 y 1000+ ("hasta infinito")
        $this->db->prepare("UPDATE hojas_solicitud SET numero_registro = '99' WHERE id = :id")
                 ->execute(['id' => $this->hojasCreadas[3]]);
        $this->assertSame('100', $this->opModelo->obtenerSiguienteNumeroRegistroGlobal());

        $this->db->prepare("UPDATE hojas_solicitud SET numero_registro = '1000' WHERE id = :id")
                 ->execute(['id' => $this->hojasCreadas[3]]);
        $this->assertSame('1001', $this->opModelo->obtenerSiguienteNumeroRegistroGlobal());
    }

    public function testMigracion033ComponeRegistrosHeredadosYEsIdempotente(): void {
        $valoresHeredados = ['00001', '00001-02', '02', '02-02'];
        $osPorDetalle = [$this->idOS1, $this->idOS1, $this->idOS2, $this->idOS2];

        foreach ($this->detalleIds as $idx => $idDet) {
            $stmt = $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, ensayo_id, numero_registro, fecha_creacion) VALUES (:os, :det, :prod, :ens, :num, NOW())");
            $stmt->execute([
                'os' => $osPorDetalle[$idx],
                'det' => $idDet,
                'prod' => $this->idProd,
                'ens' => $this->idProd,
                'num' => $valoresHeredados[$idx],
            ]);
            $this->hojasCreadas[] = (int)$this->db->lastInsertId();
        }

        ejecutarMigracion033($this->db);

        $in = implode(',', array_map('intval', $this->hojasCreadas));
        $finales = $this->db->query("SELECT numero_registro FROM hojas_solicitud WHERE id IN ({$in}) ORDER BY id ASC")
                            ->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(['01', '02', '03', '04'], $finales);

        // Segunda ejecución idempotente debe mantener 01, 02, 03, 04
        ejecutarMigracion033($this->db);
        $finales2 = $this->db->query("SELECT numero_registro FROM hojas_solicitud WHERE id IN ({$in}) ORDER BY id ASC")
                             ->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['01', '02', '03', '04'], $finales2);
    }
}
