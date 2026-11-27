<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;

class FlujoMultiRtfm13PorProductoLimsTest extends TestCase
{
    private PDO $db;
    private int $idCliente = 0;
    private int $idUsuario = 0;
    private int $idProdA = 0;
    private int $idProdB = 0;
    private int $idCot = 0;
    private int $idDetA = 0;
    private int $idDetB = 0;
    private int $idOs = 0;
    /** @var array<int> */
    private array $hojasCreadas = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $this->db = Conexion::obtenerInstancia();

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1")->fetchColumn();
        $this->idUsuario = (int)$this->db->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();

        // Elegir dos productos que NO sean compactación in situ para poder probar también el ingreso a Laboratorio
        $stmtProds = $this->db->query("
            SELECT p.id
            FROM productos p
            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
            WHERE p.activo = 1
              AND COALESCE(fe.archivo_markdown, '') NOT LIKE '%compactacion%'
              AND COALESCE(fe.archivo_markdown, '') NOT LIKE '%densimetro%'
              AND COALESCE(fe.archivo_markdown, '') NOT LIKE '%cono_de_arena%'
              AND COALESCE(p.nombre_comercial, '') NOT LIKE '%compactaci%'
              AND COALESCE(p.nombre_comercial, '') NOT LIKE '%dens%'
            ORDER BY p.id ASC
            LIMIT 2
        ");
        $prods = $stmtProds->fetchAll(PDO::FETCH_COLUMN);
        $this->idProdA = (int)($prods[0] ?? 0);
        $this->idProdB = (int)($prods[1] ?? ($prods[0] ?? 0));

        if ($this->idCliente <= 0 || $this->idUsuario <= 0 || $this->idProdA <= 0) {
            $this->markTestSkipped('Se requiere cliente, usuario y productos activos en cycsa_test.');
        }

        $suf = substr(uniqid('', true), -6);
        $this->db->prepare("
            INSERT INTO cotizaciones (codigo, id_cliente, id_cliente_factura, id_cliente_informe, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago, estado, subtotal, impuesto, total)
            VALUES (:cod, :cli, :cli2, :cli3, :usr, 'Ing. Multi RT13', 'Proyecto Multi RT-FM-13', 'Managua', 'Contado', 'Aprobada por Cliente', 2000, 300, 2300)
        ")->execute([
            'cod' => 'COT-MRT-' . $suf,
            'cli' => $this->idCliente,
            'cli2' => $this->idCliente,
            'cli3' => $this->idCliente,
            'usr' => $this->idUsuario,
        ]);
        $this->idCot = (int)$this->db->lastInsertId();

        $stmtDet = $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, norma_astm, unidad_medida, cantidad, precio_unitario, subtotal)
            VALUES (:id_cot, :id_prod, :desc, :norma, 'Unidad', 10, 100, 1000)
        ");
        $stmtDet->execute(['id_cot' => $this->idCot, 'id_prod' => $this->idProdA, 'desc' => 'Compresión de cilindros de concreto', 'norma' => 'ASTM C39']);
        $this->idDetA = (int)$this->db->lastInsertId();

        $stmtDet->execute(['id_cot' => $this->idCot, 'id_prod' => $this->idProdB, 'desc' => 'Resistencia a compresión de bloques', 'norma' => 'ASTM C140']);
        $this->idDetB = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, id_cliente_factura, id_cliente_informe, tipo_contrato, es_multimes, duracion_meses, fecha_emision, estado, requiere_muestreo)
            VALUES (:cod, :id_cot, :cli, :cli2, :cli3, 'Marco', 1, 6, CURRENT_DATE, 'Estado 3: Ingreso Directo', 0)
        ")->execute([
            'cod' => 'OS-MRT-' . $suf,
            'id_cot' => $this->idCot,
            'cli' => $this->idCliente,
            'cli2' => $this->idCliente,
            'cli3' => $this->idCliente,
        ]);
        $this->idOs = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        if ($this->idOs > 0) {
            $this->db->prepare("DELETE FROM especimenes WHERE muestra_id IN (SELECT id FROM recepcion_muestras WHERE id_os = ?)")->execute([$this->idOs]);
            $this->db->prepare("DELETE FROM ensayo_edades WHERE id_lote IN (SELECT lm.id FROM lotes_muestras lm JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id WHERE rm.id_os = ?)")->execute([$this->idOs]);
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id_recepcion IN (SELECT id FROM recepcion_muestras WHERE id_os = ?)")->execute([$this->idOs]);
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id_os = ?")->execute([$this->idOs]);
            $this->db->prepare("DELETE FROM hojas_matrices_resultados WHERE id_os = ?")->execute([$this->idOs]);
            foreach ($this->hojasCreadas as $hid) {
                $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = ?")->execute([$hid]);
                $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = ?")->execute([$hid]);
            }
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$this->idOs]);
        }
        if ($this->idCot > 0) {
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = ?")->execute([$this->idCot]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$this->idCot]);
        }
        parent::tearDown();
    }

    /**
     * @param array<int, array{codigo: string, descripcion: string, id_detalle: int, id_producto: int}> $muestras
     */
    private function crearHojaRtfm13ConMuestras(string $numReg, array $muestras): int
    {
        $mJson = [];
        foreach ($muestras as $m) {
            $mJson[] = [
                'nombre_muestra' => $m['codigo'],
                'descripcion' => $m['descripcion'],
                'info_importante' => '',
                'id_detalle_cotizacion' => $m['id_detalle'],
                'ensayo_id' => $m['id_producto'],
                'plantilla_id' => 1,
            ];
        }
        $this->db->prepare("
            INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, id_producto, numero_registro, codigo_documento, nombre_empresa_o_cliente, direccion_proyecto, muestras_json, etapa_flujo, recepcion_completa)
            VALUES (:id_os, NULL, NULL, :num_reg, 'CYCSA-RT-FM-13', 'Cliente Test', 'Managua', :mjson, 'recepcion_borrador', 0)
        ")->execute([
            'id_os' => $this->idOs,
            'num_reg' => $numReg,
            'mjson' => json_encode($mJson, JSON_UNESCAPED_UNICODE),
        ]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->hojasCreadas[] = $idHoja;

        $pltId = (int)$this->db->query("SELECT id FROM plantillas ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($pltId <= 0) {
            $pltId = 1;
        }
        $stmtHsd = $this->db->prepare("
            INSERT INTO hojas_solicitud_detalles (id_hoja_solicitud, id_detalle_cotizacion, orden, codigo_muestra, descripcion_ubicacion, ensayo_id, plantilla_id)
            VALUES (:hid, :id_det, :ord, :cod, :desc, :ens, :plt)
        ");
        foreach ($muestras as $idx => $m) {
            $stmtHsd->execute([
                'hid' => $idHoja,
                'id_det' => $m['id_detalle'],
                'ord' => $idx + 1,
                'cod' => $m['codigo'],
                'desc' => $m['descripcion'],
                'ens' => $m['id_producto'],
                'plt' => $pltId,
            ]);
        }

        asegurarMatricesPorHojaYProductos($this->db, $this->idOs, $idHoja);
        return $idHoja;
    }

    public function testCadaRtfm13CreaRecepcionCapturaMatrizOperacionesYLaboratorioIndependientesPorProducto(): void
    {
        $opModelo = new OperacionModelo();
        $roturasModelo = new RoturasModelo();
        $sufCod = (string)random_int(4000, 8999);

        // 1. Crear RT-FM-13 #1 con 2 muestras para Producto A y 1 muestra para Producto B
        $cod1A1 = "MS-{$sufCod}-26";
        $cod1A2 = "MS-" . ((int)$sufCod + 1) . "-26";
        $cod1B1 = "MS-" . ((int)$sufCod + 2) . "-26";

        $idHoja1 = $this->crearHojaRtfm13ConMuestras('REG-MRT-001', [
            ['codigo' => $cod1A1, 'descripcion' => 'Cilindro 1 Mes 1', 'id_detalle' => $this->idDetA, 'id_producto' => $this->idProdA],
            ['codigo' => $cod1A2, 'descripcion' => 'Cilindro 2 Mes 1', 'id_detalle' => $this->idDetA, 'id_producto' => $this->idProdA],
            ['codigo' => $cod1B1, 'descripcion' => 'Bloque 1 Mes 1', 'id_detalle' => $this->idDetB, 'id_producto' => $this->idProdB],
        ]);

        // Debe haber 2 unidades de trabajo independientes: (Hoja 1 × Prod A) y (Hoja 1 × Prod B)
        $itemsTrasH1 = $opModelo->obtenerItemsOS($this->idOs);
        $this->assertCount(2, $itemsTrasH1);
        $this->assertSame($idHoja1, (int)$itemsTrasH1[0]['id_hoja']);
        $this->assertSame($this->idDetA, (int)$itemsTrasH1[0]['id']);
        $this->assertSame(2, (int)$itemsTrasH1[0]['cantidad']);
        $this->assertSame($idHoja1, (int)$itemsTrasH1[1]['id_hoja']);
        $this->assertSame($this->idDetB, (int)$itemsTrasH1[1]['id']);
        $this->assertSame(1, (int)$itemsTrasH1[1]['cantidad']);

        // Marcar (Hoja 1 × Prod A) como aprobada en hojas_matrices_resultados
        $jsonAprobH1 = json_encode([
            'version_actual' => 1,
            'filas' => [
                ['Código laboratorio' => $cod1A1, 'Nombre muestra' => 'Cilindro 1 Mes 1', 'Carga (lb)' => '90000'],
                ['Código laboratorio' => $cod1A2, 'Nombre muestra' => 'Cilindro 2 Mes 1', 'Carga (lb)' => '91000'],
            ],
            'revision' => ['estado' => 'aprobada', 'usuario_revisor' => 'Supervisor Test'],
        ], JSON_UNESCAPED_UNICODE);
        guardarResultadosJsonMatriz($this->db, $this->idOs, $this->idDetA, $idHoja1, (string)$jsonAprobH1);

        // Ingresar Hoja 1 a Laboratorio -> debe crear recepcion_muestras con id_detalle_cotizacion distinto por producto
        $resLab1 = $opModelo->ingresarMuestrasHojaALaboratorio($this->idOs, $idHoja1, $this->idUsuario);
        $this->assertTrue($resLab1['ok'] ?? false);

        // 2. Crear RT-FM-13 #2 en la MISMA O/S con 2 nuevas muestras también para Producto A
        $cod2A1 = "MS-" . ((int)$sufCod + 3) . "-26";
        $cod2A2 = "MS-" . ((int)$sufCod + 4) . "-26";

        $idHoja2 = $this->crearHojaRtfm13ConMuestras('REG-MRT-002', [
            ['codigo' => $cod2A1, 'descripcion' => 'Cilindro 1 Mes 2', 'id_detalle' => $this->idDetA, 'id_producto' => $this->idProdA],
            ['codigo' => $cod2A2, 'descripcion' => 'Cilindro 2 Mes 2', 'id_detalle' => $this->idDetA, 'id_producto' => $this->idProdA],
        ]);

        // Ahora Operaciones y Captura Técnica deben ver 3 entradas independientes:
        // (Hoja 1 × Prod A aprobada), (Hoja 1 × Prod B pendiente), y (Hoja 2 × Prod A NUEVA pendiente)
        $itemsTrasH2 = $opModelo->obtenerItemsOS($this->idOs);
        $this->assertCount(3, $itemsTrasH2);

        $itemH1A = $itemsTrasH2[0];
        $itemH1B = $itemsTrasH2[1];
        $itemH2A = $itemsTrasH2[2];

        $this->assertSame($idHoja1, (int)$itemH1A['id_hoja']);
        $this->assertSame($this->idDetA, (int)$itemH1A['id']);
        $this->assertSame('aprobada', obtenerEstadoRevisionMatriz($itemH1A['resultados_json'])['estado']);

        $this->assertSame($idHoja1, (int)$itemH1B['id_hoja']);
        $this->assertSame($this->idDetB, (int)$itemH1B['id']);

        $this->assertSame($idHoja2, (int)$itemH2A['id_hoja']);
        $this->assertSame($this->idDetA, (int)$itemH2A['id']);
        $this->assertSame('REG-MRT-002', (string)$itemH2A['hoja_numero_registro']);
        $this->assertSame('pendiente', obtenerEstadoRevisionMatriz($itemH2A['resultados_json'])['estado'], 'La nueva RT-FM-13 #2 debe iniciar pendiente, sin heredar aprobación de RT-FM-13 #1.');

        // 3. Verificar aislamiento de muestras por hoja al abrir cada matriz
        $muestrasH1A = obtenerMuestrasHojaParaDetalle($this->db, $this->idOs, $this->idDetA, ['id' => $idHoja1]);
        $muestrasH2A = obtenerMuestrasHojaParaDetalle($this->db, $this->idOs, $this->idDetA, ['id' => $idHoja2]);
        $this->assertSame([$cod1A1, $cod1A2], array_column($muestrasH1A, 'codigo_muestra'));
        $this->assertSame([$cod2A1, $cod2A2], array_column($muestrasH2A, 'codigo_muestra'));

        // 4. Sincronizar Datos de Recepción en Hoja 2 NO debe alterar Hoja 1
        sincronizarDatosRecepcionEnMatriz($this->db, $idHoja2, [
            strtoupper($cod2A1) => ['Nombre muestra' => 'Viga Piso 2 - M1', 'Fecha de Fabricación' => '2026-11-20'],
            strtoupper($cod2A2) => ['Nombre muestra' => 'Viga Piso 2 - M2', 'Fecha de Fabricación' => '2026-11-20'],
        ]);
        $jsonH1Despues = obtenerResultadosJsonMatriz($this->db, $this->idDetA, $idHoja1);
        $jsonH2Despues = obtenerResultadosJsonMatriz($this->db, $this->idDetA, $idHoja2);

        $this->assertSame('aprobada', obtenerEstadoRevisionMatriz($jsonH1Despues)['estado']);
        $this->assertStringContainsString('Cilindro 1 Mes 1', (string)$jsonH1Despues);
        $this->assertStringNotContainsString('Viga Piso 2', (string)$jsonH1Despues);

        $this->assertStringContainsString('Viga Piso 2 - M1', (string)$jsonH2Despues);
        $this->assertStringNotContainsString('Cilindro 1 Mes 1', (string)$jsonH2Despues);
        $this->assertSame('borrador', obtenerEstadoRevisionMatriz($jsonH2Despues)['estado']);

        // 5. Ingresar Hoja 2 a Laboratorio -> debe crear nueva entrada independiente en bandeja de Laboratorio
        $resLab2 = $opModelo->ingresarMuestrasHojaALaboratorio($this->idOs, $idHoja2, $this->idUsuario);
        $this->assertTrue($resLab2['ok'] ?? false);

        $bandejaLab = $roturasModelo->obtenerBandejaAnonima([]);
        $gruposLab = [];
        foreach ($bandejaLab as $rowLab) {
            $hidL = (int)($rowLab['id_hoja'] ?? 0);
            if ($hidL === $idHoja1 || $hidL === $idHoja2) {
                $claveG = $hidL . ':' . (int)($rowLab['id_detalle_cotizacion'] ?? 0);
                $gruposLab[$claveG][] = $rowLab['codigo_lab'];
            }
        }

        // Exactamente 3 entradas independientes en Laboratorio:
        // Hoja1×ProdA (2 muestras), Hoja1×ProdB (1 muestra) y Hoja2×ProdA (2 muestras)
        $this->assertCount(3, $gruposLab, 'Grupos encontrados en Laboratorio: ' . json_encode($gruposLab) . ' | idHoja1=' . $idHoja1 . ' idHoja2=' . $idHoja2 . ' idDetA=' . $this->idDetA . ' idDetB=' . $this->idDetB);
        $this->assertArrayHasKey($idHoja1 . ':' . $this->idDetA, $gruposLab);
        $this->assertArrayHasKey($idHoja1 . ':' . $this->idDetB, $gruposLab);
        $this->assertArrayHasKey($idHoja2 . ':' . $this->idDetA, $gruposLab);
        $this->assertCount(2, $gruposLab[$idHoja1 . ':' . $this->idDetA]);
        $this->assertCount(1, $gruposLab[$idHoja1 . ':' . $this->idDetB]);
        $this->assertCount(2, $gruposLab[$idHoja2 . ':' . $this->idDetA]);
    }
}
