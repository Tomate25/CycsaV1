<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Ventas\Controladores\VentasControlador;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use PDO;

/**
 * HOTFIX G.H1: Corrección de doble asiento y fallbacks en facturación de Ventas
 *
 * Cobertura de pruebas unitarias:
 * 1. procesarFacturacion con IVA genera exactamente 1 partida_diario (no duplicada) con 3 líneas balanceadas (DEBE == HABER).
 * 2. procesarFacturacion sin IVA genera exactamente 1 partida_diario con 2 líneas balanceadas.
 * 3. Confirmación estricta de que VentasControlador contiene una única llamada a registrarAsientoContable.
 */
class SprintGH1VentasFacturacionAsientoTest extends TestCase {

    private PDO $db;
    private array $idsLimpieza = [
        'clientes' => [],
        'cotizaciones' => [],
        'ordenes' => [],
        'cxc' => [],
        'partidas' => []
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = 'token_hotfix_gh1_test';
    }

    protected function tearDown(): void {
        // Limpiar en orden inverso de claves foráneas
        foreach ($this->idsLimpieza['partidas'] as $idPartida) {
            $this->db->exec("DELETE FROM partidas_diario_detalles WHERE id_partida = {$idPartida}");
            $this->db->exec("DELETE FROM partidas_diario WHERE id = {$idPartida}");
        }
        foreach ($this->idsLimpieza['cxc'] as $idCxc) {
            $this->db->exec("DELETE FROM cuentas_por_cobrar WHERE id = {$idCxc}");
        }
        foreach ($this->idsLimpieza['ordenes'] as $idOs) {
            $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$idOs}");
        }
        foreach ($this->idsLimpieza['cotizaciones'] as $idCot) {
            $this->db->exec("DELETE FROM cotizaciones WHERE id = {$idCot}");
        }
        foreach ($this->idsLimpieza['clientes'] as $idCli) {
            $this->db->exec("DELETE FROM clientes WHERE id = {$idCli}");
        }
        parent::tearDown();
    }

    public function testFacturacionConIvaCreaUnSoloAsientoTresLineas(): void {
        $rand = random_int(10000, 99999);

        // 1. Crear cliente
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, activo) VALUES (:nom, :ruc, 1)");
        $stmtCli->execute(['nom' => "Cliente Test GH1 Con IVA {$rand}", 'ruc' => "J{$rand}IVA"]);
        $idCli = (int)$this->db->lastInsertId();
        $this->idsLimpieza['clientes'][] = $idCli;

        // 2. Crear cotización con IVA (Subtotal 1000, Impuesto/IVA 150, Total 1150)
        $subtotal = 1000.00;
        $iva = 150.00;
        $total = 1150.00;
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_usuario_creador, subtotal, impuesto, total, estado, version
            ) VALUES (
                :cod, :cli, 1, :sub, :imp, :tot, 'Aprobada por Cliente', 1
            )
        ");
        $stmtCot->execute([
            'cod' => "COT-GH1-{$rand}",
            'cli' => $idCli,
            'sub' => $subtotal,
            'imp' => $iva,
            'tot' => $total
        ]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsLimpieza['cotizaciones'][] = $idCot;

        // 3. Crear O/S
        $osModelo = new OrdenServicioModelo();
        $idOS = $osModelo->crear([
            'codigo_os' => "OS-GH1-{$rand}",
            'id_cotizacion' => $idCot,
            'id_cliente' => $idCli,
            'id_cliente_factura' => $idCli,
            'condicion_comercial' => 'contado',
            'forma_pago' => 'Pago contra entrega',
            'estado' => 'Estado 1: Recepcion'
        ]);
        $this->idsLimpieza['ordenes'][] = $idOS;

        // 4. Ejecutar facturación en efectivo vía VentasControlador
        $facturaNumero = "FAC-GH1-{$rand}";
        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('esPost')->willReturn(true);
        $peticionMock->method('obtenerDatos')->willReturn([
            'csrf_token' => 'token_hotfix_gh1_test',
            'id_os' => $idOS,
            'metodo_pago' => 'efectivo',
            'monto' => $total,
            'fecha' => date('Y-m-d'),
            'factura_numero' => $facturaNumero
        ]);

        $respuestaMock = $this->createMock(Respuesta::class);
        $controlador = new VentasControlador();
        $controlador->procesarFacturacion($peticionMock, $respuestaMock);

        // 5. Verificar que se creó la CxC
        $stmtCxc = $this->db->prepare("SELECT * FROM cuentas_por_cobrar WHERE factura_numero = :fn LIMIT 1");
        $stmtCxc->execute(['fn' => $facturaNumero]);
        $cxc = $stmtCxc->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($cxc, 'La factura debe quedar registrada en cuentas_por_cobrar');
        $idCxc = (int)$cxc['id'];
        $this->idsLimpieza['cxc'][] = $idCxc;

        // 6. VERIFICACIÓN CRÍTICA DEL BUG: Debe existir EXACTAMENTE 1 partida de diario para este origen_id
        $stmtPartidas = $this->db->prepare("SELECT * FROM partidas_diario WHERE origen = 'FACTURACION' AND origen_id = :id");
        $stmtPartidas->execute(['id' => $idCxc]);
        $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(
            1,
            $partidas,
            'CRÍTICO: procesarFacturacion debe registrar EXACTAMENTE UN asiento contable por factura (no doble asiento duplicado)'
        );

        $partidaUnica = $partidas[0];
        $idPartida = (int)$partidaUnica['id'];
        $this->idsLimpieza['partidas'][] = $idPartida;

        // 7. Verificar líneas del asiento (3 líneas: Caja, Ingreso, IVA)
        $stmtDetalles = $this->db->prepare("SELECT * FROM partidas_diario_detalles WHERE id_partida = :id");
        $stmtDetalles->execute(['id' => $idPartida]);
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $detalles, 'Con IVA > 0, el asiento contable debe contener exactamente 3 líneas de detalle');

        $totalDebe = 0.0;
        $totalHaber = 0.0;
        $idCuentaIva = ConfigCuentasModelo::obtenerIdPorClave('IVA_POR_PAGAR') ?: 154;
        $idCuentaIngreso = ConfigCuentasModelo::obtenerIdPorClave('INGRESO_LAB_G') ?: 208;
        $idCuentaCaja = ConfigCuentasModelo::obtenerIdPorClave('CAJA_PRINCIPAL') ?: 4;

        $montoIvaEncontrado = 0.0;
        $montoSubtotalEncontrado = 0.0;
        $montoDebeEncontrado = 0.0;

        foreach ($detalles as $det) {
            $debe = (float)$det['debe'];
            $haber = (float)$det['haber'];
            $totalDebe += $debe;
            $totalHaber += $haber;
            $idC = (int)$det['id_cuenta_contable'];

            if ($idC === $idCuentaCaja) {
                $montoDebeEncontrado += $debe;
            } elseif ($idC === $idCuentaIva) {
                $montoIvaEncontrado += $haber;
            } elseif ($idC === $idCuentaIngreso) {
                $montoSubtotalEncontrado += $haber;
            }
        }

        $this->assertEquals($total, $totalDebe, 'El total al DEBE debe ser igual al total facturado');
        $this->assertEquals($totalDebe, $totalHaber, 'El asiento contable debe cuadrar estrictamente: DEBE == HABER');
        $this->assertEquals($iva, $montoIvaEncontrado, 'El monto asignado a IVA_POR_PAGAR debe corresponder al IVA');
        $this->assertEquals($subtotal, $montoSubtotalEncontrado, 'El monto asignado a Ingresos debe corresponder al subtotal');
    }

    public function testFacturacionSinIvaCreaUnSoloAsientoDosLineas(): void {
        $rand = random_int(10000, 99999);

        // 1. Crear cliente
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, activo) VALUES (:nom, :ruc, 1)");
        $stmtCli->execute(['nom' => "Cliente Test GH1 Sin IVA {$rand}", 'ruc' => "J{$rand}NOIVA"]);
        $idCli = (int)$this->db->lastInsertId();
        $this->idsLimpieza['clientes'][] = $idCli;

        // 2. Crear cotización exenta / sin IVA (Subtotal 800, Impuesto 0, Total 800)
        $subtotal = 800.00;
        $iva = 0.00;
        $total = 800.00;
        $stmtCot = $this->db->prepare("
            INSERT INTO cotizaciones (
                codigo, id_cliente, id_usuario_creador, subtotal, impuesto, total, estado, version
            ) VALUES (
                :cod, :cli, 1, :sub, :imp, :tot, 'Aprobada por Cliente', 1
            )
        ");
        $stmtCot->execute([
            'cod' => "COT-GH1-NOIVA-{$rand}",
            'cli' => $idCli,
            'sub' => $subtotal,
            'imp' => $iva,
            'tot' => $total
        ]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsLimpieza['cotizaciones'][] = $idCot;

        // 3. Crear O/S
        $osModelo = new OrdenServicioModelo();
        $idOS = $osModelo->crear([
            'codigo_os' => "OS-GH1-NOIVA-{$rand}",
            'id_cotizacion' => $idCot,
            'id_cliente' => $idCli,
            'id_cliente_factura' => $idCli,
            'condicion_comercial' => 'contado',
            'forma_pago' => 'Pago contra entrega',
            'estado' => 'Estado 1: Recepcion'
        ]);
        $this->idsLimpieza['ordenes'][] = $idOS;

        // 4. Ejecutar facturación
        $facturaNumero = "FAC-GH1-NOIVA-{$rand}";
        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('esPost')->willReturn(true);
        $peticionMock->method('obtenerDatos')->willReturn([
            'csrf_token' => 'token_hotfix_gh1_test',
            'id_os' => $idOS,
            'metodo_pago' => 'efectivo',
            'monto' => $total,
            'fecha' => date('Y-m-d'),
            'factura_numero' => $facturaNumero
        ]);

        $respuestaMock = $this->createMock(Respuesta::class);
        $controlador = new VentasControlador();
        $controlador->procesarFacturacion($peticionMock, $respuestaMock);

        // 5. Verificar que se creó la CxC
        $stmtCxc = $this->db->prepare("SELECT * FROM cuentas_por_cobrar WHERE factura_numero = :fn LIMIT 1");
        $stmtCxc->execute(['fn' => $facturaNumero]);
        $cxc = $stmtCxc->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($cxc);
        $idCxc = (int)$cxc['id'];
        $this->idsLimpieza['cxc'][] = $idCxc;

        // 6. Verificar EXACTAMENTE 1 partida de diario
        $stmtPartidas = $this->db->prepare("SELECT * FROM partidas_diario WHERE origen = 'FACTURACION' AND origen_id = :id");
        $stmtPartidas->execute(['id' => $idCxc]);
        $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $partidas, 'Sin IVA también debe existir exactamente 1 asiento contable por factura');

        $partidaUnica = $partidas[0];
        $idPartida = (int)$partidaUnica['id'];
        $this->idsLimpieza['partidas'][] = $idPartida;

        // 7. Verificar líneas del asiento (2 líneas: Caja e Ingreso)
        $stmtDetalles = $this->db->prepare("SELECT * FROM partidas_diario_detalles WHERE id_partida = :id");
        $stmtDetalles->execute(['id' => $idPartida]);
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $detalles, 'Sin IVA, el asiento contable debe contener exactamente 2 líneas (DEBE y HABER)');

        $totalDebe = (float)$detalles[0]['debe'] + (float)$detalles[1]['debe'];
        $totalHaber = (float)$detalles[0]['haber'] + (float)$detalles[1]['haber'];
        $this->assertEquals($total, $totalDebe);
        $this->assertEquals($totalDebe, $totalHaber, 'Sin IVA, DEBE == HABER estricto');
    }

    public function testGrepConfirmaUnaSolaLlamadaARegistrarAsientoContableEnVentasControlador(): void {
        $archivo = dirname(__DIR__, 2) . '/app/Modulos/Ventas/Controladores/VentasControlador.php';
        $contenido = file_get_contents($archivo);

        $coincidencias = substr_count($contenido, 'registrarAsientoContable');
        $this->assertSame(
            1,
            $coincidencias,
            'VentasControlador debe contener exactamente UNA llamada a registrarAsientoContable (sin bloque duplicado muerto)'
        );

        $this->assertStringNotContainsString(
            '?: 206',
            $contenido,
            'La cuenta 206 (IVA Acreditable/Activo) nunca debe ser utilizada como fallback de ingresos'
        );
    }
}
