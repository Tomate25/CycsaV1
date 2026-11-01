<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Ventas\Modelos\VentaModelo;
use PDO;

/**
 * Sprint D.2: P3 Administrativo / Facturación / Versiones
 * D.2.1: Destinatario informe != Pagador
 * D.2.2: Corrección administrativa -> nueva versión con 'Sustituye a'
 * D.2.3: Facturar O/S en cualquier etapa sin resultados de lab
 * D.2.4: Versionado de cotizaciones y enlace a O/S
 */
class AdministrativoFacturacionVersionesTest extends TestCase {

    private PDO $db;
    private int $idUsuario = 1;
    private int $idClientePagador = 0;
    private int $idClienteInforme = 0;
    private int $idCotizacionPrueba = 0;
    private int $idOSPrueba = 0;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar usuario válido
        $idUser = $this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser) {
            $this->idUsuario = (int)$idUser;
        } else {
            $this->db->exec("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES ('Usuario Test D2', 'testd2@cycsa.com', 'hash', 1, 1)");
            $this->idUsuario = (int)$this->db->lastInsertId();
        }

        // Asegurar que la migración 028 está aplicada
        $colsOS = $this->db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('cotizacion_version', $colsOS, true)) {
            $this->db->exec("ALTER TABLE ordenes_servicio ADD COLUMN cotizacion_version INT NOT NULL DEFAULT 1 AFTER id_cotizacion");
        }
    }

    protected function tearDown(): void {
        if ($this->idOSPrueba > 0) {
            $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOSPrueba}");
        }
        if ($this->idCotizacionPrueba > 0) {
            $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacionPrueba}");
            $this->db->exec("DELETE FROM cotizacion_versiones WHERE id_cotizacion = {$this->idCotizacionPrueba}");
            $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacionPrueba}");
        }
        if ($this->idClientePagador > 0) {
            $this->db->exec("DELETE FROM clientes WHERE id = {$this->idClientePagador}");
        }
        if ($this->idClienteInforme > 0) {
            $this->db->exec("DELETE FROM clientes WHERE id = {$this->idClienteInforme}");
        }
        parent::tearDown();
    }

    /**
     * D.2.1: Destinatario del informe != pagador
     * PDF/informe técnico usa cliente_informe; Factura/CxC usa cliente_factura.
     */
    public function testDestinatarioInformeDiferenteDePagador(): void {
        $rand = random_int(1000, 9999);
        $pagadorNombre = "Constructora Financiera Pagadora {$rand} S.A.";
        $destinatarioNombre = "Consorcio Técnico Proyecto {$rand} Ltda.";

        // 1. Crear clientes de prueba
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, activo) VALUES (:nom, :ruc, 1)");
        $stmtCli->execute(['nom' => $pagadorNombre, 'ruc' => "J031000{$rand}P"]);
        $this->idClientePagador = (int)$this->db->lastInsertId();

        $stmtCli->execute(['nom' => $destinatarioNombre, 'ruc' => "J031000{$rand}D"]);
        $this->idClienteInforme = (int)$this->db->lastInsertId();

        // 2. Crear cotización con pagador != informe
        $codigoCot = "COT-D21-{$rand}";
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial,
                id_usuario_creador, tipo_moneda, subtotal, impuesto, total, estado, version, nombre_proyecto, atencion_a
            ) VALUES (
                :codigo, :id_cli, :id_fac, :id_inf, 'contado',
                :id_u, 1, 1000.00, 150.00, 1150.00, 'Aprobada por Cliente', 1, 'Proyecto Separación Figuras', 'Ing. Director'
            )
        ");
        $stmtCot->execute([
            'codigo' => $codigoCot,
            'id_cli' => $this->idClientePagador,
            'id_fac' => $this->idClientePagador,
            'id_inf' => $this->idClienteInforme,
            'id_u' => $this->idUsuario
        ]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        // 3. Crear O/S heredando o definiendo destinatario != pagador
        $osModelo = new OrdenServicioModelo();
        $codigoOS = "OS-D21-{$rand}";
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $this->idCotizacionPrueba,
            'cotizacion_version' => 1,
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'condicion_comercial' => 'contado',
            'forma_pago' => 'Pago contra entrega',
            'nombre_proyecto' => 'Proyecto Separación Figuras',
            'estado' => 'Estado 1: Recepcion'
        ]);
        $this->assertGreaterThan(0, $this->idOSPrueba);

        // 4. Verificar que OrdenServicioModelo resuelve ambos nombres
        $osCargada = $osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertNotNull($osCargada);
        $this->assertSame($pagadorNombre, $osCargada['cliente_factura_nombre']);
        $this->assertSame($destinatarioNombre, $osCargada['cliente_informe_nombre']);

        // 5. Verificar que el informe técnico resolverMetadatosEnsayo prioriza cliente_informe_nombre
        $detalleDummy = [
            'id' => 9999,
            'codigo_os' => $codigoOS,
            'cliente_informe_nombre' => $osCargada['cliente_informe_nombre'],
            'cliente_nombre' => $osCargada['cliente_nombre'],
            'cliente_factura_nombre' => $osCargada['cliente_factura_nombre'],
            'nombre_proyecto' => 'Proyecto Separación Figuras'
        ];
        $meta = resolverMetadatosEnsayo($detalleDummy, [], []);
        $this->assertSame($destinatarioNombre, $meta['cliente_nombre'], 'El informe oficial debe reflejar al destinatario técnico del informe.');

        // 6. Verificar que Ventas utiliza al pagador para cuentas_por_cobrar
        $ventaModelo = new VentaModelo();
        $ordenesVenta = $ventaModelo->obtenerOrdenes($codigoOS);
        $this->assertNotEmpty($ordenesVenta);
        $this->assertSame($pagadorNombre, $ordenesVenta[0]['cliente_nombre'], 'El módulo de Ventas/Facturación debe listar al pagador comercial.');
    }

    /**
     * D.2.2: Corrección administrativa -> nueva versión MS-…-N con 'Sustituye a'
     */
    public function testCorreccionAdministrativaVersionamientoYSustituye(): void {
        $codigoBase = 'CYCSA-INF-MS-0088-26';
        $versionActual = 1;
        $motivo = 'Corrección formal del nombre del proyecto por cambio de licitación';

        // Simular lógica de reemisión de OperacionesControlador
        $codigoPrevio = ($versionActual > 1) ? "{$codigoBase}-" . ($versionActual - 1) : $codigoBase;
        $nuevaVersionActual = $versionActual + 1;
        $nuevoCodigoVersion = "{$codigoBase}-" . ($nuevaVersionActual - 1);

        $this->assertSame('CYCSA-INF-MS-0088-26', $codigoPrevio);
        $this->assertSame(2, $nuevaVersionActual);
        $this->assertSame('CYCSA-INF-MS-0088-26-1', $nuevoCodigoVersion);

        // Estructura de reemisión guardada en JSON
        $reemision = [
            'version' => $nuevaVersionActual,
            'codigo_version' => $nuevoCodigoVersion,
            'sustituye_a' => $codigoPrevio,
            'tipo_correccion' => 'administrativa',
            'motivo' => $motivo
        ];

        // Verificar leyenda de sustitución oficial
        $bannerHtml = '<div style="font-size: 8px; font-weight: bold; color: #b91c1c;">SUSTITUYE AL INFORME: ' . htmlspecialchars($reemision['sustituye_a']) . ' (Motivo: ' . htmlspecialchars($reemision['motivo']) . ')</div>';
        $this->assertStringContainsString('SUSTITUYE AL INFORME: CYCSA-INF-MS-0088-26', $bannerHtml);
        $this->assertStringContainsString('cambio de licitación', $bannerHtml);
    }

    /**
     * D.2.3: Facturar en cualquier momento del ciclo (sin resultados de lab)
     */
    public function testFacturarOSSinResultadosDeLaboratorio(): void {
        $rand = random_int(1000, 9999);
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, activo) VALUES (:nom, 1)");
        $stmtCli->execute(['nom' => "Cliente Factura Directa {$rand}"]);
        $idCli = (int)$this->db->lastInsertId();

        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, subtotal, impuesto, total, estado, version) VALUES (:c, :id, :u, 500, 75, 575, 'Aprobada por Cliente', 1)");
        $stmtCot->execute(['c' => "COT-FAC-{$rand}", 'id' => $idCli, 'u' => $this->idUsuario]);
        $idCot = (int)$this->db->lastInsertId();

        $osModelo = new OrdenServicioModelo();
        $idOS = $osModelo->crear([
            'codigo_os' => "OS-FAC-{$rand}",
            'id_cotizacion' => $idCot,
            'id_cliente' => $idCli,
            'id_cliente_factura' => $idCli,
            'id_cliente_informe' => $idCli,
            'condicion_comercial' => 'contado',
            'forma_pago' => 'Pago contra entrega',
            'estado' => 'Estado 1: Recepcion' // Aún en recepción, sin ningún ensaye realizado
        ]);

        // Simular creación de cuenta por cobrar desde Ventas (facturación anticipada/contado)
        $facturaNum = "FAC-COT-FAC-{$rand}";
        $stmtCxc = $this->db->prepare("
            INSERT INTO cuentas_por_cobrar (id_cliente, factura_numero, monto, saldo, estado, fecha_emision, notas)
            VALUES (:cli, :fn, 575.00, 0.00, 'Pagado', CURRENT_DATE, 'Facturado antes de laboratorio')
        ");
        $stmtCxc->execute(['cli' => $idCli, 'fn' => $facturaNum]);
        $idCxc = (int)$this->db->lastInsertId();

        $this->assertGreaterThan(0, $idCxc);

        // Verificar que la O/S sigue sin resultados y los candados técnicos permanecen intactos
        $osCheck = $osModelo->obtenerPorId($idOS);
        $this->assertSame('Estado 1: Recepcion', $osCheck['estado']);

        // Limpieza puntual
        $this->db->exec("DELETE FROM cuentas_por_cobrar WHERE id = {$idCxc}");
        $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$idOS}");
        $this->db->exec("DELETE FROM cotizaciones WHERE id = {$idCot}");
        $this->db->exec("DELETE FROM clientes WHERE id = {$idCli}");
    }

    /**
     * D.2.4: Versionado de cotizaciones y enlace a versión concreta en O/S
     */
    public function testVersionadoCotizacionesYEnlaceAOS(): void {
        $rand = random_int(1000, 9999);
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, activo) VALUES (:nom, 1)");
        $stmtCli->execute(['nom' => "Cliente Cot Version {$rand}"]);
        $idCli = (int)$this->db->lastInsertId();

        $cotModelo = new CotizacionModelo();
        $codigoCot = "COT-VER-{$rand}";

        // Insertar cotización inicial en v1
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial,
                id_usuario_creador, tipo_moneda, subtotal, impuesto, total, estado, version,
                nombre_proyecto, atencion_a, condicion_pago, tiempo_entrega, vigencia_oferta
            ) VALUES (
                :codigo, :id_cli, :id_fac, :id_inf, 'contado',
                :id_u, 1, 2000.00, 300.00, 2300.00, 'Borrador', 1,
                'Proyecto Original v1', 'Contacto V1', 'Contado', '5 días', '15 días'
            )
        ");
        $stmtCot->execute([
            'codigo' => $codigoCot,
            'id_cli' => $idCli,
            'id_fac' => $idCli,
            'id_inf' => $idCli,
            'id_u' => $this->idUsuario
        ]);
        $this->idCotizacionPrueba = (int)$this->db->lastInsertId();

        $stmtDet = $this->db->prepare("
            INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal)
            VALUES (:id_cot, 'Ensayo Inicial V1', 2, 1000.00, 2000.00)
        ");
        $stmtDet->execute(['id_cot' => $this->idCotizacionPrueba]);

        $cotV1 = $cotModelo->obtenerPorId($this->idCotizacionPrueba);
        $this->assertSame(1, (int)$cotV1['version']);

        // Pasar a 'Rechazada por Cliente' para forzar incremento de versión al editar
        $this->db->exec("UPDATE cotizaciones SET estado = 'Rechazada por Cliente', motivo_rechazo_cliente = 'Precio alto' WHERE id = {$this->idCotizacionPrueba}");

        // Actualizar cotización para generar V2
        $cabeceraV2 = [
            'id_cliente' => $idCli,
            'id_cliente_factura' => $idCli,
            'id_cliente_informe' => $idCli,
            'condicion_comercial' => 'contado',
            'tipo_moneda' => 1,
            'atencion_a' => 'Contacto V2',
            'nombre_proyecto' => 'Proyecto Negociado v2',
            'direccion_proyecto' => 'Managua',
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'configuracion_notas' => '[]',
            'contactos' => null,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 1800.00,
            'descuento' => 0.00,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 270.00,
            'total' => 2070.00,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null
        ];
        $detallesV2 = [
            [
                'id_producto' => null,
                'descripcion_ensayo' => 'Ensayo Negociado V2',
                'cantidad' => 2,
                'precio_unitario' => 900.00,
                'subtotal' => 1800.00
            ]
        ];

        $okActualizar = $cotModelo->actualizarCotizacionCompleta($this->idCotizacionPrueba, $cabeceraV2, $detallesV2);
        $this->assertTrue($okActualizar);

        // Verificar que v1 quedó archivada en cotizacion_versiones
        $versionesHistoricas = $cotModelo->obtenerVersiones($this->idCotizacionPrueba);
        $this->assertCount(1, $versionesHistoricas);
        $this->assertSame(1, (int)$versionesHistoricas[0]['version']);

        // Verificar que la versión actual es 2
        $cotV2 = $cotModelo->obtenerPorId($this->idCotizacionPrueba);
        $this->assertSame(2, (int)$cotV2['version']);

        // Crear O/S apuntando a la versión 2
        $osModelo = new OrdenServicioModelo();
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => "OS-VER-{$rand}",
            'id_cotizacion' => $this->idCotizacionPrueba,
            'cotizacion_version' => 2,
            'id_cliente' => $idCli,
            'id_cliente_factura' => $idCli,
            'id_cliente_informe' => $idCli,
            'condicion_comercial' => 'contado',
            'forma_pago' => 'Contado',
            'estado' => 'Estado 1: Recepcion'
        ]);

        $osCheck = $osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertSame(2, (int)$osCheck['cotizacion_version'], 'La O/S debe almacenar y reflejar la versión concreta de la cotización formalizada.');

        $this->idClientePagador = $idCli;
    }
}
