<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Servicios\DocumentoRgFm39Servicio;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;

class PeriodoMensualCotizacionExtensionHojaTest extends TestCase
{
    private PDO $db;
    private int $idCliente = 0;
    private int $idUsuario = 0;
    private int $idProducto1 = 0;
    private int $idProducto2 = 0;
    /** @var array<int> */
    private array $cotizacionesCreadas = [];
    /** @var array<int> */
    private array $ordenesCreadas = [];
    /** @var array<string> */
    private array $facturasCreadas = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
        $this->db = Conexion::obtenerInstancia();

        $this->idCliente = (int)$this->db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1")->fetchColumn();
        $this->idUsuario = (int)$this->db->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();

        $prods = $this->db->query("SELECT id FROM productos WHERE activo = 1 ORDER BY id ASC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
        $this->idProducto1 = (int)($prods[0] ?? 0);
        $this->idProducto2 = (int)($prods[1] ?? ($prods[0] ?? 0));

        if ($this->idCliente <= 0 || $this->idUsuario <= 0 || $this->idProducto1 <= 0) {
            $this->markTestSkipped('Se requiere al menos un cliente, usuario y producto activo en cycsa_test.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->ordenesCreadas as $idOs) {
            $this->db->prepare("DELETE FROM ordenes_servicio_ampliaciones WHERE id_os = ?")->execute([$idOs]);
            $this->db->prepare("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = ?")->execute([$idOs]);
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = ?")->execute([$idOs]);
        }
        foreach ($this->facturasCreadas as $fac) {
            $this->db->prepare("DELETE FROM cuentas_por_cobrar WHERE factura_numero = ?")->execute([$fac]);
        }
        foreach ($this->cotizacionesCreadas as $idCot) {
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id_cotizacion = ?")->execute([$idCot]);
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = ?")->execute([$idCot]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$idCot]);
        }
        parent::tearDown();
    }

    private function crearCotizacionMensualPrueba(): array
    {
        $cotModelo = new CotizacionModelo();
        $codigo = 'COT-PER-' . substr(uniqid('', true), -6);
        $cabecera = [
            'codigo' => $codigo,
            'id_cliente' => $this->idCliente,
            'id_cliente_factura' => $this->idCliente,
            'id_cliente_informe' => $this->idCliente,
            'condicion_comercial' => 'credito',
            'tipo_moneda' => 1,
            'id_usuario_creador' => $this->idUsuario,
            'id_responsable_interno' => $this->idUsuario,
            'atencion_a' => 'Ing. Prueba Período',
            'direccion_cliente' => 'Managua, Nicaragua',
            'nombre_proyecto' => 'Proyecto Control Mensual Test',
            'direccion_proyecto' => 'Km 12 Carretera Sur',
            'prioridad' => 'Normal',
            'fecha_limite' => null,
            'condicion_pago' => 'Crédito 30 días',
            'tiempo_entrega' => '5 días hábiles',
            'vigencia_oferta' => '15 días calendario',
            'es_mensual' => 1,
            'duracion_meses' => 6,
            'fecha_inicio_periodo' => '2026-12-01',
            'fecha_fin_periodo' => '2027-06-01',
            'periodicidad' => 'Mensual',
            'configuracion_notas' => json_encode(['digital_pdf' => 1]),
            'contactos' => json_encode([['nombre' => 'Ing. Prueba', 'telefono' => '8888-1111', 'correo' => 'prueba@test.com']]),
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 2000.00,
            'descuento' => 0.00,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 300.00,
            'total' => 2300.00,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null,
        ];
        $detalles = [[
            'id_producto' => $this->idProducto1,
            'descripcion' => 'Compresión de cilindros de concreto',
            'procedimiento' => 'ASTM C39',
            'unidad_medida' => 'Unidad',
            'cantidad' => 2,
            'precio' => 1000.00,
            'subtotal' => 2000.00,
        ]];

        $ok = $cotModelo->guardarCotizacionCompleta($cabecera, $detalles);
        $this->assertTrue($ok, 'Debe guardar la cotización mensual correctamente.');

        $stmt = $this->db->prepare("SELECT id FROM cotizaciones WHERE codigo = :cod LIMIT 1");
        $stmt->execute(['cod' => $codigo]);
        $idCot = (int)$stmt->fetchColumn();
        $this->assertGreaterThan(0, $idCot);
        $this->cotizacionesCreadas[] = $idCot;
        $this->facturasCreadas[] = 'FAC-' . $codigo;

        return [$idCot, $codigo];
    }

    public function testCotizacionMensualGuardaPeriodoYSeMuestraEnDocumentoRgFm31(): void
    {
        [$idCot] = $this->crearCotizacionMensualPrueba();
        $cotModelo = new CotizacionModelo();
        $cot = $cotModelo->obtenerPorId($idCot);
        $detalles = $cotModelo->obtenerDetalles($idCot);

        $this->assertSame(1, (int)$cot['es_mensual']);
        $this->assertSame(6, (int)$cot['duracion_meses']);
        $this->assertSame('2026-12-01', $cot['fecha_inicio_periodo']);
        $this->assertSame('2027-06-01', $cot['fecha_fin_periodo']);
        $this->assertSame('Mensual', $cot['periodicidad']);

        $htmlPantalla = DocumentoCotizacionRgFm31Servicio::renderCuerpo($cot, $detalles, false);
        $htmlPdf = DocumentoCotizacionRgFm31Servicio::renderCuerpo($cot, $detalles, true);

        foreach ([$htmlPantalla, $htmlPdf] as $html) {
            $this->assertStringContainsString('Modalidad / Período habilitado', $html);
            $this->assertStringContainsString('Mensual · 6 meses (01/12/2026 — 01/06/2027)', $html);
        }
    }

    public function testAprobacionClienteHeredaPeriodoMensualEnOrdenServicio(): void
    {
        [$idCot] = $this->crearCotizacionMensualPrueba();
        $cotModelo = new CotizacionModelo();

        $ok = $cotModelo->registrarDecisionCliente($idCot, 'Aprobada por Cliente');
        $this->assertTrue($ok, 'La aprobación del cliente debe crear la O/S automáticamente.');

        $stmtOs = $this->db->prepare("SELECT * FROM ordenes_servicio WHERE id_cotizacion = :id LIMIT 1");
        $stmtOs->execute(['id' => $idCot]);
        $osRow = $stmtOs->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($osRow, 'Debe existir una O/S creada para la cotización aprobada.');
        $this->ordenesCreadas[] = (int)$osRow['id'];

        $this->assertSame(1, (int)$osRow['es_multimes']);
        $this->assertSame('Marco', (string)$osRow['tipo_contrato']);
        $this->assertSame(6, (int)$osRow['duracion_meses']);
        $this->assertSame('2026-12-01', (string)$osRow['fecha_inicio']);
        $this->assertSame('2027-06-01', (string)$osRow['fecha_fin']);
        $this->assertSame('Mensual', (string)$osRow['periodicidad']);

        $osModelo = new OrdenServicioModelo();
        $osCompleta = $osModelo->obtenerPorId((int)$osRow['id']);
        $htmlOs = DocumentoRgFm39Servicio::html($osCompleta);
        $this->assertStringContainsString('6 meses', $htmlOs);
        $this->assertStringContainsString('01/12/2026', $htmlOs);
        $this->assertStringContainsString('01/06/2027', $htmlOs);
    }

    public function testAmpliarPeriodoYModificarAgregarProductosDesdeHojaDeServicio(): void
    {
        [$idCot] = $this->crearCotizacionMensualPrueba();
        $cotModelo = new CotizacionModelo();
        $cotModelo->registrarDecisionCliente($idCot, 'Aprobada por Cliente');

        $stmtOs = $this->db->prepare("SELECT id FROM ordenes_servicio WHERE id_cotizacion = :id LIMIT 1");
        $stmtOs->execute(['id' => $idCot]);
        $idOs = (int)$stmtOs->fetchColumn();
        $this->assertGreaterThan(0, $idOs);
        $this->ordenesCreadas[] = $idOs;

        $detallesIni = $cotModelo->obtenerDetalles($idCot);
        $this->assertCount(1, $detallesIni);
        $idDetInicial = (int)$detallesIni[0]['id'];

        // Activar whitelist para verificar que el producto recién agregado se auto-habilite
        $osModelo = new OrdenServicioModelo();
        $osModelo->guardarEnsayosPermitidos($idOs, [$idDetInicial], $this->idUsuario);

        $ctrl = new HojasServicioControlador();
        $res = $ctrl->ejecutarAmpliacionPeriodoYProductos($idOs, [
            'meses_adicionales' => 3,
            'periodicidad' => 'Mensual',
            'motivo' => 'Ampliación fase 2 desde Hoja de Servicio',
            'productos_modificar' => [[
                'id_detalle' => $idDetInicial,
                'descripcion_ensayo' => 'Compresión de cilindros (ampliado)',
                'cantidad' => 5,
            ]],
            'productos_agregar' => [[
                'id_producto' => $this->idProducto2,
                'descripcion_ensayo' => 'Ensayo Nuevo Adicional Test',
                'cantidad' => 4,
                'precio_unitario' => 250.00,
            ]],
        ], $this->idUsuario, 'Supervisor Test');

        $this->assertTrue($res['ok'] ?? false, 'Debe completar la ampliación correctamente: ' . ($res['message'] ?? ''));

        // 1. Verificar período ampliado en O/S y Cotización
        $osDespues = $osModelo->obtenerPorId($idOs);
        $this->assertSame(1, (int)$osDespues['es_multimes']);
        $this->assertSame(9, (int)$osDespues['duracion_meses']);
        $this->assertSame('2027-09-01', (string)$osDespues['fecha_fin']);

        $cotDespues = $cotModelo->obtenerPorId($idCot);
        $this->assertSame(9, (int)$cotDespues['duracion_meses']);
        $this->assertSame('2027-09-01', (string)$cotDespues['fecha_fin_periodo']);

        // 2. Verificar modificación del producto existente y adición del nuevo producto
        $detallesDespues = $cotModelo->obtenerDetalles($idCot);
        $this->assertCount(2, $detallesDespues);
        $this->assertSame('Compresión de cilindros (ampliado)', $detallesDespues[0]['descripcion_ensayo']);
        $this->assertEquals(5.0, (float)$detallesDespues[0]['cantidad']);
        $this->assertSame('Ensayo Nuevo Adicional Test', $detallesDespues[1]['descripcion_ensayo']);
        $this->assertEquals(4.0, (float)$detallesDespues[1]['cantidad']);

        // 3. Verificar totales recalculados: (5 * 1000) + (4 * 250) = 6000; IVA 15% = 900; Total = 6900
        $this->assertEquals(6000.00, (float)$cotDespues['subtotal']);
        $this->assertEquals(900.00, (float)$cotDespues['impuesto']);
        $this->assertEquals(6900.00, (float)$cotDespues['total']);

        // 4. Verificar que el nuevo detalle se habilitó automáticamente en la whitelist de la O/S
        $idsPermitidos = $osModelo->obtenerIdsPermitidos($idOs);
        $idDetNuevo = (int)$detallesDespues[1]['id'];
        $this->assertContains($idDetInicial, $idsPermitidos);
        $this->assertContains($idDetNuevo, $idsPermitidos);

        // 5. Verificar registro en ordenes_servicio_ampliaciones
        $stmtAmp = $this->db->prepare("SELECT * FROM ordenes_servicio_ampliaciones WHERE id_os = :id ORDER BY id DESC LIMIT 1");
        $stmtAmp->execute(['id' => $idOs]);
        $ampRow = $stmtAmp->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($ampRow);
        $this->assertSame(6, (int)$ampRow['duracion_meses_anterior']);
        $this->assertSame(9, (int)$ampRow['duracion_meses_nueva']);
        $this->assertSame('2027-06-01', (string)$ampRow['fecha_fin_anterior']);
        $this->assertSame('2027-09-01', (string)$ampRow['fecha_fin_nueva']);
        $this->assertSame('Ampliación fase 2 desde Hoja de Servicio', (string)$ampRow['motivo']);
    }

    public function testVistasYRutaDePeriodoYAmpliacionPresentes(): void
    {
        $root = dirname(__DIR__, 2);
        $crearCot = (string)file_get_contents($root . '/app/Modulos/Cotizaciones/Vistas/crear.php');
        $editarCot = (string)file_get_contents($root . '/app/Modulos/Cotizaciones/Vistas/editar.php');
        $crearOs = (string)file_get_contents($root . '/app/Modulos/OrdenesServicio/Vistas/crear.php');
        $indexOs = (string)file_get_contents($root . '/app/Modulos/OrdenesServicio/Vistas/index.php');
        $indexHs = (string)file_get_contents($root . '/app/Modulos/HojasServicio/Vistas/index.php');
        $partial = (string)file_get_contents($root . '/app/Modulos/HojasServicio/Vistas/partials/modal_ampliar_periodo_productos.php');
        $rutas = (string)file_get_contents($root . '/rutas/web.php');

        $this->assertStringContainsString('name="es_mensual"', $crearCot);
        $this->assertStringContainsString('name="duracion_meses"', $crearCot);
        $this->assertStringContainsString('name="fecha_inicio_periodo"', $crearCot);
        $this->assertStringContainsString('name="fecha_fin_periodo"', $crearCot);

        $this->assertStringContainsString('name="es_mensual"', $editarCot);
        $this->assertStringContainsString('name="duracion_meses"', $editarCot);

        $this->assertStringContainsString('$cotEsMensualOs', $crearOs);
        $this->assertStringContainsString('abrirModalAmpliarPeriodoProductosHS()', $indexOs);
        $this->assertStringContainsString('abrirModalAmpliarPeriodoProductosHS()', $indexHs);
        $this->assertStringContainsString('modalAmpliarPeriodoProductosHS', $partial);
        $this->assertStringContainsString('/hojas-servicio/ampliar-periodo-productos', $partial);
        $this->assertStringContainsString('/hojas-servicio/ampliar-periodo-productos', $rutas);
    }
}
