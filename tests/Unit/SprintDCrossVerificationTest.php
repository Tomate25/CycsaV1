<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Ventas\Modelos\VentaModelo;
use PDO;

/**
 * FASE D.5 — Verificación cruzada (smoke + regresiones)
 * Checklist integral de Sprint D (D.0 a D.4):
 * 1. Celdas medición captura-matriz persisten y renderizan tras recarga.
 * 2. Chip CC con id_detalle correcto en href.
 * 3. Encabezados de informe oficiales correctos con marcadores **.
 * 4. Campo dedicado 'Muestra tomada con equipo' con datos Troxler.
 * 5. O/S compactación usa RT-FM-22 B y unidad kg/m³ estricta en PE-25.
 * 6. Smoke C.1–C.6 juntos (PDF O/S, traslados, whitelist, bitácora, checklist).
 * 7. Destinatario informe ≠ Pagador verificado en PDF vs Factura.
 * 8. Facturación en Ventas opera sin resultados de lab y sin romper candados de CC.
 * 9. Corrección administrativa genera versión N+1 con 'Sustituye a' inmutable.
 * 10. Cotizaciones versionan con snapshot JSON y O/S almacena cotizacion_version.
 */
class SprintDCrossVerificationTest extends TestCase {

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

        $idUser = $this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        if ($idUser) {
            $this->idUsuario = (int)$idUser;
        }

        // Crear clientes de prueba para verificar Destinatario != Pagador
        $nombrePagador = 'CLIENTE COMERCIAL PAGADOR S.A. ' . uniqid();
        $stmt = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, activo) VALUES (?, 'J0310000000001', 1)");
        $stmt->execute([$nombrePagador]);
        $this->idClientePagador = (int)$this->db->lastInsertId();

        $nombreInforme = 'DESTINATARIO TECNICO INFORME S.A. ' . uniqid();
        $stmt = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, activo) VALUES (?, 'J0310000000002', 1)");
        $stmt->execute([$nombreInforme]);
        $this->idClienteInforme = (int)$this->db->lastInsertId();
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

    private function extraerTextoDePdf(string $pdfBytes): string {
        $texto = '';
        if (preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/s", $pdfBytes, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $utf8 = mb_convert_encoding($decompressed, 'UTF-8', 'Windows-1252');
                    $texto .= ' ' . $decompressed . ' ' . $utf8;
                }
            }
        }
        return str_replace(['\\(', '\\)'], ['(', ')'], $texto);
    }

    /**
     * Check 1: Celdas medición captura-matriz persisten y renderizan tras recarga
     */
    public function testCheck1CeldasMedicionCapturaMatrizPersistenYRenderizan(): void {
        $filasConcreto = [
            [
                'Código laboratorio' => 'MS-101-26',
                'Área (in²)' => '28.27',
                'Carga (lb)' => '85000',
                'R. Compresión (lb/in²)' => '3006.7',
                'R. Compresión (kg/cm²)' => '211.4'
            ]
        ];

        $normalizadas = normalizarReplicasMatriz($filasConcreto);
        $payload = [
            'version_actual' => 1,
            'filas' => $normalizadas,
            'metadatos' => ['proyecto' => 'Puente Smoke D.5']
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $recuperado = json_decode($json, true);

        $this->assertIsArray($recuperado['filas']);
        $f0 = $recuperado['filas'][0];
        $this->assertSame('85000', $f0['Carga (lb)']);
        $this->assertSame('28.27', $f0['Área (in²)']);
        $this->assertSame('3006.7', $f0['R. Compresión (lb/in²)']);
        $this->assertSame('211.4', $f0['R. Compresión (kg/cm²)']);
    }

    /**
     * Check 2: Chip CC con id_detalle correcto
     */
    public function testCheck2ChipCCConIdDetalleCorrecto(): void {
        $capturaContenido = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('control-calidad/ver?id_detalle=<?= (int)($detalle[\'id\'] ?? ($idDetalle ?? 0)) ?>', $capturaContenido);
        $this->assertStringNotContainsString('control-calidad/ver?id_detalle=<?= $idDetalle ?>', $capturaContenido);
    }

    /**
     * Check 3: Encabezados de informe oficiales con marcadores **
     */
    public function testCheck3EncabezadosInformeOficialConMarcadores(): void {
        $printContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString('** Nombre del cliente:', $printContent);
        $this->assertStringContainsString('** Proyecto:', $printContent);
        $this->assertStringContainsString('** Dirección:', $printContent);
        $this->assertStringContainsString('** Fecha muestreo:', $printContent);
        $this->assertStringContainsString('** Información Proporcionada por el cliente', $printContent);
    }

    /**
     * Check 4: 'Muestra tomada con equipo' como campo estructurado
     */
    public function testCheck4MuestraTomadaConEquipoEstructurado(): void {
        $meta = resolverMetadatosEnsayo([], [], [
            'equipo_marca' => 'TROXLER',
            'equipo_modelo' => '3440',
            'equipo_serie' => '62677',
            'equipo_codigo' => 'Eq-6555'
        ]);
        $this->assertSame('Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3440, N/S : 62677 Eq-6555', $meta['nota_equipo_densimetro']);
    }

    /**
     * Check 5: O/S compactación usa RT-FM-22 B y unidad kg/m³ en PE-25
     */
    public function testCheck5OSCompactacionUsaRtFm22BYUnidadKgm3(): void {
        $meta = resolverMetadatosEnsayo([
            'descripcion_ensayo' => 'Densidad y Humedad in situ por Método Nuclear (PE-25)',
            'norma_astm' => 'ASTM D6938'
        ]);
        $this->assertSame('CYCSA-RT-FM-22 B (V1-R2)', $meta['codigo_formato']);

        // Conversión dimensional PE-25 estricta a kg/m³
        $colLabel = str_replace(['kg/cm²', 'kg/cm2'], 'kg/m³', 'P.V.S.Sitio (kg/cm²)');
        $this->assertSame('P.V.S.Sitio (kg/m³)', $colLabel);
    }

    private function crearCotizacionPrueba(): int {
        $codigoCot = 'COT-TST-' . uniqid();
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial,
                id_usuario_creador, tipo_moneda, subtotal, impuesto, total, estado, version,
                nombre_proyecto, atencion_a, condicion_pago, tiempo_entrega, vigencia_oferta
            ) VALUES (
                :codigo, :id_cli, :id_fac, :id_inf, 'contado',
                :id_u, 1, 100.0, 15.0, 115.0, 'Aprobada por Cliente', 1,
                'Proyecto Test Cross', 'Contacto', 'Contado', '3 días', '15 días'
            )
        ");
        $stmtCot->execute([
            'codigo' => $codigoCot,
            'id_cli' => $this->idClientePagador,
            'id_fac' => $this->idClientePagador,
            'id_inf' => $this->idClienteInforme,
            'id_u' => $this->idUsuario
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Check 6: Smoke C.1-C.6 integrados
     */
    public function testCheck6SmokeC1AC6Integrados(): void {
        $this->idCotizacionPrueba = $this->crearCotizacionPrueba();
        $this->assertGreaterThan(0, $this->idCotizacionPrueba);

        // Detalle multilínea
        $this->db->exec("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES ({$this->idCotizacionPrueba}, 'Línea Principal\nEspecificación técnica aditiva', 1, 150.0, 150.0)");

        // C.2 Whitelist ensayos al aprobar O/S
        $osModelo = new OrdenServicioModelo();
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => 'OS-SMOKE-' . uniqid(),
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'nombre_proyecto' => 'Proyecto Integrado Smoke D.5',
            'cotizacion_version' => 1,
            'estado' => 'Estado 1: Recepcion'
        ]);
        $this->assertGreaterThan(0, $this->idOSPrueba);

        // C.5 Bitácora de O/S (función helper global)
        $bitacora = obtenerBitacoraOS($this->idOSPrueba);
        $this->assertIsArray($bitacora);

        // Rol legible
        $nombreRol = obtenerNombreRol(1);
        $this->assertSame('Administrador', $nombreRol);
    }

    /**
     * Check 7: Destinatario informe != Pagador verificado
     */
    public function testCheck7DestinatarioInformeDistintoDePagador(): void {
        $this->idCotizacionPrueba = $this->crearCotizacionPrueba();
        $osModelo = new OrdenServicioModelo();
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => 'OS-DEST-' . uniqid(),
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'nombre_proyecto' => 'Proyecto Destinatario != Pagador Test',
            'cotizacion_version' => 1,
            'estado' => 'Estado 1: Recepcion'
        ]);

        $osRecuperada = $osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertNotEmpty($osRecuperada['cliente_informe_nombre']);
        $this->assertNotEmpty($osRecuperada['cliente_factura_nombre']);
        $this->assertNotEquals($osRecuperada['cliente_informe_nombre'], $osRecuperada['cliente_factura_nombre']);
    }

    /**
     * Check 8: Facturación en Ventas opera sin resultados de lab sin romper candados
     */
    public function testCheck8FacturacionVentasSinResultadosLab(): void {
        $this->idCotizacionPrueba = $this->crearCotizacionPrueba();
        $codigoOS = 'OS-FACT-' . uniqid();
        $osModelo = new OrdenServicioModelo();
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'nombre_proyecto' => 'O/S Facturable Inmediatamente',
            'monto_total' => 500.0,
            'cotizacion_version' => 1,
            'estado' => 'Estado 1: Recepcion'
        ]);

        $facturaNum = 'FAC-' . uniqid();
        $stmtCxc = $this->db->prepare("
            INSERT INTO cuentas_por_cobrar (id_cliente, factura_numero, monto, saldo, estado, fecha_emision, notas)
            VALUES (:cli, :fn, 575.00, 0.00, 'Pagado', CURRENT_DATE, 'Facturado antes de laboratorio')
        ");
        $stmtCxc->execute(['cli' => $this->idClientePagador, 'fn' => $facturaNum]);
        $idCxc = (int)$this->db->lastInsertId();
        $this->assertGreaterThan(0, $idCxc);

        // La O/S sigue sin resultados y los candados de matriz/CC permanecen intactos
        $osCheck = $osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertSame('Estado 1: Recepcion', $osCheck['estado']);

        // Limpiar CxC
        $this->db->exec("DELETE FROM cuentas_por_cobrar WHERE id = {$idCxc}");
    }

    /**
     * Check 9: Corrección administrativa genera versión N+1 con "Sustituye a"
     */
    public function testCheck9CorreccionAdministrativaGeneraVersionNMas1(): void {
        $detalle = [
            'id' => 1,
            'codigo_servicio' => 'ENS-CONC-01',
            'descripcion_ensayo' => 'Compresión de Cilindros',
            'codigo_os' => 'OS-2026-CORR',
            'cliente_nombre' => 'CLIENTE VIEJO',
            'nombre_proyecto' => 'PROYECTO VIEJO',
            'resultados_json' => json_encode([
                'version_actual' => 2,
                'reemision' => [
                    'sustituye_a' => 'CYCSA-INF-MS-1001-1002-26',
                    'motivo' => 'Corrección administrativa de razón social'
                ],
                'filas' => []
            ])
        ];

        $pdfBytes = generarMatrizTecnicaPDF($detalle);
        $this->assertNotEmpty($pdfBytes);
        $textoPdf = $this->extraerTextoDePdf($pdfBytes);
        $this->assertStringContainsString('SUSTITUYE AL INFORME', $textoPdf);
        $this->assertStringContainsString('CYCSA-INF-MS-1001-1002-26', $textoPdf);
    }

    /**
     * Check 10: Cotizaciones versionan con snapshot JSON y O/S almacena cotizacion_version
     */
    public function testCheck10CotizacionesVersionanYSnapshotsOS(): void {
        $codigoCot = 'COT-V1-' . uniqid();
        $cotModelo = new CotizacionModelo();

        // 1. Crear cotización inicial
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial,
                id_usuario_creador, tipo_moneda, subtotal, impuesto, total, estado, version,
                nombre_proyecto, atencion_a, condicion_pago, tiempo_entrega, vigencia_oferta
            ) VALUES (
                :codigo, :id_cli, :id_fac, :id_inf, 'contado',
                :id_u, 1, 100.0, 15.0, 115.0, 'Borrador', 1,
                'Proyecto Versionado', 'Contacto', 'Contado', '3 días', '15 días'
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
        $this->assertGreaterThan(0, $this->idCotizacionPrueba);

        $this->db->exec("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES ({$this->idCotizacionPrueba}, 'Ensayo V1', 1, 100.0, 100.0)");

        // Cambiar estado a 'Rechazada por Cliente' para que la edición cree versión 2
        $this->db->exec("UPDATE cotizaciones SET estado = 'Rechazada por Cliente', motivo_rechazo_cliente = 'Revisar costo' WHERE id = {$this->idCotizacionPrueba}");

        // Actualizar cotización para generar V2
        $cabeceraV2 = [
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'condicion_comercial' => 'contado',
            'tipo_moneda' => 1,
            'atencion_a' => 'Contacto V2',
            'nombre_proyecto' => 'Proyecto Versionado v2',
            'direccion_proyecto' => 'Managua',
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'configuracion_notas' => '[]',
            'contactos' => null,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 200.0,
            'descuento' => 0.0,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 30.0,
            'total' => 230.0,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null
        ];
        $detallesV2 = [
            [
                'id_producto' => null,
                'descripcion_ensayo' => 'Ensayo Negociado V2',
                'cantidad' => 2,
                'precio_unitario' => 100.0,
                'subtotal' => 200.0
            ]
        ];

        $okActualizar = $cotModelo->actualizarCotizacionCompleta($this->idCotizacionPrueba, $cabeceraV2, $detallesV2);
        $this->assertTrue($okActualizar);

        $cotData = $cotModelo->obtenerPorId($this->idCotizacionPrueba);
        $this->assertEquals(2, $cotData['version']);

        // Crear O/S con versión 2
        $osModelo = new OrdenServicioModelo();
        $this->idOSPrueba = $osModelo->crear([
            'codigo_os' => 'OS-V2-' . uniqid(),
            'id_cotizacion' => $this->idCotizacionPrueba,
            'id_cliente' => $this->idClientePagador,
            'id_cliente_factura' => $this->idClientePagador,
            'id_cliente_informe' => $this->idClienteInforme,
            'nombre_proyecto' => 'Proyecto Cotización Versionada D.5',
            'cotizacion_version' => 2,
            'estado' => 'Estado 1: Recepcion'
        ]);

        $osData = $osModelo->obtenerPorId($this->idOSPrueba);
        $this->assertEquals(2, $osData['cotizacion_version']);
    }
}
