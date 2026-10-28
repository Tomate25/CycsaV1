<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;
use PDO;

class ConfigCuentasTest extends TestCase {

    private PDO $db;
    private ConfigCuentasModelo $modelo;

    private const CLAVES_ESPERADAS = [
        'CAJA_PRINCIPAL'      => '1010101',
        'CAJA_CHICA'          => '1010102',
        'CLIENTES'            => '1010201',
        'ANTICIPO_PROVEEDORES'=> '1010501',
        'IVA_ACREDITABLE'     => '1010601',
        'INVENTARIO_LAB'      => '1010701',
        'PROVEEDORES'         => '2010101',
        'ANTICIPO_CLIENTES'   => '2010303',
        'IR_FUENTE_2'         => '2010502',
        'IVA_POR_PAGAR'       => '2010508',
        'INGRESO_LAB_G'       => '4010106',
        'INGRESO_LAB_E'       => '4010107',
        'COSTO_MAT_LAB'       => '5010308',
        'COSTO_CALIBRACION'   => '5010303',
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 013 haya corrido
        $db = $this->db;
        $ruta013 = dirname(__DIR__, 2) . '/database/migrations/013_puente_contable_config_cuentas.php';
        if (file_exists($ruta013)) {
            ob_start();
            include $ruta013;
            ob_end_clean();
        }

        $this->modelo = new ConfigCuentasModelo($this->db);
    }

    /**
     * TAREA-046: Validar que la migración 013 cree la tabla config_cuentas y siembre las 14 claves primarias.
     */
    public function testMigracion013CreaTablaYSiembraClaves(): void {
        $tablas = $this->db->query("SHOW TABLES LIKE 'config_cuentas'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($tablas, "La tabla 'config_cuentas' debe existir en la base de datos.");

        $stmt = $this->db->query("SELECT clave, codigo_cuenta FROM config_cuentas");
        $filas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach (self::CLAVES_ESPERADAS as $clave => $codigoEsperado) {
            $this->assertArrayHasKey($clave, $filas, "La clave mnemónica '{$clave}' debe estar registrada en config_cuentas.");
            $this->assertSame($codigoEsperado, $filas[$clave], "El código contable de '{$clave}' debe ser {$codigoEsperado}.");
        }
    }

    /**
     * TAREA-046: Validar que obtenerIdPorClave resuelva correctamente el ID de la cuenta contable.
     */
    public function testResolucionIdCuentasContables(): void {
        $idCaja = $this->modelo->obtenerIdPorClave('CAJA_PRINCIPAL');
        $this->assertNotNull($idCaja, "El ID de CAJA_PRINCIPAL no debe ser nulo.");
        $this->assertIsInt($idCaja);
        $this->assertGreaterThan(0, $idCaja);

        $idClientes = $this->modelo->obtenerIdPorClave('CLIENTES');
        $this->assertNotNull($idClientes);
        $this->assertIsInt($idClientes);
        $this->assertGreaterThan(0, $idClientes);

        $idProveedores = $this->modelo->obtenerIdPorClave('PROVEEDORES');
        $this->assertNotNull($idProveedores);
        $this->assertIsInt($idProveedores);
        $this->assertGreaterThan(0, $idProveedores);

        $idIva = $this->modelo->obtenerIdPorClave('IVA_ACREDITABLE');
        $this->assertNotNull($idIva);
        $this->assertIsInt($idIva);

        $idInexistente = $this->modelo->obtenerIdPorClave('CLAVE_INEXISTENTE_XYZ');
        $this->assertNull($idInexistente, "Una clave inexistente debe retornar null.");

        $idVacio = $this->modelo->obtenerIdPorClave('');
        $this->assertNull($idVacio, "Una clave vacía debe retornar null.");
    }

    /**
     * TAREA-046: Validar que obtenerCuentaPorClave devuelva los datos de config enriquecidos con la cuenta.
     */
    public function testObtenerCuentaPorClaveRetornaDetalles(): void {
        $cuenta = $this->modelo->obtenerCuentaPorClave('IVA_POR_PAGAR');
        $this->assertIsArray($cuenta);
        $this->assertSame('IVA_POR_PAGAR', $cuenta['clave']);
        $this->assertSame('2010508', $cuenta['codigo_cuenta']);
        $this->assertSame('IVA 15% por Pagar', $cuenta['descripcion']);
        $this->assertNotNull($cuenta['id_cuenta']);
        $this->assertIsInt($cuenta['id_cuenta']);
        $this->assertArrayHasKey('nombre_cuenta', $cuenta);
        $this->assertNotEmpty($cuenta['nombre_cuenta']);

        $cuentaInexistente = $this->modelo->obtenerCuentaPorClave('CLAVE_INEXISTENTE_ABC');
        $this->assertNull($cuentaInexistente);
    }

    /**
     * TAREA-046: Validar que obtenerCodigoPorClave retorne el código contable en string.
     */
    public function testObtenerCodigoPorClave(): void {
        $codigoCajaChica = $this->modelo->obtenerCodigoPorClave('CAJA_CHICA');
        $this->assertSame('1010102', $codigoCajaChica);

        $codigoClientes = $this->modelo->obtenerCodigoPorClave('CLIENTES');
        $this->assertSame('1010201', $codigoClientes);

        $codigoInexistente = $this->modelo->obtenerCodigoPorClave('INEXISTENTE_123');
        $this->assertNull($codigoInexistente);
    }

    /**
     * TAREA-046: Validar que listarConfiguracion devuelva al menos las 14 claves mnemónicas del catálogo.
     */
    public function testListarConfiguracionContieneTodasLasClaves(): void {
        $lista = $this->modelo->listarConfiguracion();
        $this->assertIsArray($lista);
        $this->assertGreaterThanOrEqual(14, count($lista));

        $clavesListadas = array_column($lista, 'clave');
        foreach (array_keys(self::CLAVES_ESPERADAS) as $clave) {
            $this->assertContains($clave, $clavesListadas, "La lista debe contener la clave {$clave}.");
        }

        foreach ($lista as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('clave', $item);
            $this->assertArrayHasKey('codigo_cuenta', $item);
            $this->assertArrayHasKey('descripcion', $item);
            $this->assertArrayHasKey('modulo', $item);
            $this->assertArrayHasKey('activo', $item);
        }
    }

    /**
     * TAREA-046: Validar que actualizarMapeo actualice id_cuenta y codigo_cuenta persistentemente.
     */
    public function testActualizarMapeoModificaCuentaYPersiste(): void {
        $original = $this->modelo->obtenerCuentaPorClave('CAJA_CHICA');
        $this->assertNotNull($original);
        $originalId = $original['id_cuenta'];
        $originalCodigo = $original['codigo_cuenta'];

        // Obtener un id_cuenta alternativo válido (ej. el de CAJA_PRINCIPAL)
        $idCajaPrincipal = $this->modelo->obtenerIdPorClave('CAJA_PRINCIPAL');
        $this->assertNotNull($idCajaPrincipal);

        try {
            // Actualizar mapeo de CAJA_CHICA hacia la cuenta de CAJA_PRINCIPAL
            $ok = $this->modelo->actualizarMapeo('CAJA_CHICA', $idCajaPrincipal);
            $this->assertTrue($ok, "actualizarMapeo debe retornar true para una clave existente.");

            $actualizado = $this->modelo->obtenerCuentaPorClave('CAJA_CHICA');
            $this->assertSame($idCajaPrincipal, $actualizado['id_cuenta']);
            $this->assertSame('1010101', $actualizado['codigo_cuenta'], "El código contable debe sincronizarse con la cuenta asignada.");

            // Validar que clave inexistente retorne false
            $falso = $this->modelo->actualizarMapeo('CLAVE_INEXISTENTE_XYZ', 9999);
            $this->assertFalse($falso, "actualizarMapeo debe retornar false si la clave no existe.");
        } finally {
            // Restaurar mapeo original
            if ($originalId !== null) {
                $this->modelo->actualizarMapeo('CAJA_CHICA', $originalId);
                // Si el código contable era diferente, reasegurarlo
                $this->db->prepare("UPDATE config_cuentas SET codigo_cuenta = :cod, id_cuenta = :id WHERE clave = 'CAJA_CHICA'")
                    ->execute(['cod' => $originalCodigo, 'id' => $originalId]);
            }
        }
    }

    /**
     * TAREA-046: Validar integración con MigradorServicio para ejecución idempotente.
     */
    public function testMigradorServicioEjecutaMigracionIdempotente(): void {
        $resultados = MigradorServicio::asegurarTablas($this->db);
        $this->assertIsArray($resultados);
        $this->assertArrayNotHasKey('error', $resultados, "MigradorServicio no debe registrar errores.");

        // Verificar que config_cuentas sigue íntegra y disponible
        $idClientes = $this->modelo->obtenerIdPorClave('CLIENTES');
        $this->assertNotNull($idClientes);
    }
}
