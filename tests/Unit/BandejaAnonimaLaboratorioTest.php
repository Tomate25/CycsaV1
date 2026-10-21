<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use PDO;

class BandejaAnonimaLaboratorioTest extends TestCase {

    private PDO $db;
    private RoturasModelo $modelo;
    private ?int $clienteTestId = null;
    private ?int $osTestId = null;
    private ?int $recepcionTestId = null;
    private ?int $loteTestId = null;
    private string $codigoLabTest = 'MS-TEST-26';

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->modelo = new RoturasModelo();

        // Crear fixture para pruebas anónimas y de reidentificación
        $stmtC = $this->db->prepare("
            INSERT INTO clientes (nombre_razon_social, identificacion, numero_ruc, email, telefono, direccion)
            VALUES ('Constructora Confidencial S.A.', 'J0310000000001', 'J0310000000001', 'contacto@constructora.com', '2222-3333', 'Km 14 Carretera a Masaya')
        ");
        $stmtC->execute();
        $this->clienteTestId = (int)$this->db->lastInsertId();

        $stmtOS = $this->db->prepare("
            INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, fecha_emision, nombre_proyecto, atencion_a, estado)
            VALUES ('OS-TEST-99', 1, :cid, CURDATE(), 'Puente Paso Real Proyecto Secreto', 'Ing. Residente Confidencial', 'Estado 4: Ingreso Laboratorio')
        ");
        $stmtOS->execute(['cid' => $this->clienteTestId]);
        $this->osTestId = (int)$this->db->lastInsertId();

        $stmtRec = $this->db->prepare("
            INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado)
            VALUES (:os_id, 9999, 2026, 'Laboratorio', :cod, 'C-TEST', NOW(), 1, 'Registrado')
        ");
        $stmtRec->execute(['os_id' => $this->osTestId, 'cod' => $this->codigoLabTest]);
        $this->recepcionTestId = (int)$this->db->lastInsertId();

        $stmtLote = $this->db->prepare("
            INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, temperatura_c, procedimiento_muestreo)
            VALUES (:rec_id, 'Concreto Hidráulico Estructural', '4000 PSI', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 4.5, 27.5, 'ASTM C172 / ISO 17025')
        ");
        $stmtLote->execute(['rec_id' => $this->recepcionTestId]);
        $this->loteTestId = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->loteTestId) {
            $this->db->prepare("DELETE FROM lotes_muestras WHERE id = :id")->execute(['id' => $this->loteTestId]);
        }
        if ($this->recepcionTestId) {
            $this->db->prepare("DELETE FROM recepcion_muestras WHERE id = :id")->execute(['id' => $this->recepcionTestId]);
        }
        if ($this->osTestId) {
            $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $this->osTestId]);
        }
        if ($this->clienteTestId) {
            $this->db->prepare("DELETE FROM clientes WHERE id = :id")->execute(['id' => $this->clienteTestId]);
        }
        $this->db->prepare("DELETE FROM bitacora_reidentificacion WHERE codigo_lab = :cod")->execute(['cod' => $this->codigoLabTest]);
        parent::tearDown();
    }

    /**
     * Prueba de Fuga de Datos (Data Leak Prevention - ISO 17025 Cláusulas 4.1 y 4.2).
     * Comprueba que la vista v_lab_muestras y el modelo anónimo de laboratorio
     * NUNCA devuelvan columnas de cliente, RUC, proyecto, cotización, O/S ni precios.
     */
    public function testVistaLabMuestrasNoContieneColumnasProhibidasDeClientes(): void {
        $stmt = $this->db->query("SHOW COLUMNS FROM v_lab_muestras");
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $columnasProhibidas = [
            'cliente',
            'cliente_id',
            'cliente_nombre',
            'nombre_razon_social',
            'razon_social',
            'identificacion_fiscal',
            'ruc',
            'proyecto',
            'nombre_proyecto',
            'direccion_proyecto',
            'id_cotizacion',
            'cotizacion_codigo',
            'codigo_os',
            'id_os',
            'precio',
            'total',
            'subtotal',
            'atencion_a',
            'tecnico_muestreo',
            'nombre_persona_toma_muestra'
        ];

        foreach ($columnasProhibidas as $col) {
            $this->assertNotContains(
                $col,
                $columnas,
                "VULNERABILIDAD ISO 17025 DETECTADA: La columna prohibida '{$col}' se encuentra presente en la vista anónima 'v_lab_muestras'."
            );
        }
    }

    /**
     * Comprueba que la consulta de la bandeja anónima retorne datos limpios de trazabilidad comercial.
     */
    public function testBandejaAnonimaRetornaCamposTecnicosSinFiltracionComercial(): void {
        $resultados = $this->modelo->obtenerBandejaAnonima(['busqueda' => $this->codigoLabTest]);

        $this->assertNotEmpty($resultados, 'La muestra fixture debe aparecer en la bandeja anónima.');

        $camposProhibidosEnFila = [
            'cliente', 'cliente_nombre', 'razon_social', 'ruc', 'nombre_proyecto', 'direccion_proyecto',
            'precio', 'total', 'cotizacion_codigo', 'codigo_os'
        ];

        foreach ($resultados as $fila) {
            foreach ($camposProhibidosEnFila as $prohibido) {
                $this->assertArrayNotHasKey(
                    $prohibido,
                    $fila,
                    "FUGA DE DATOS: La fila del laboratorio expone el campo comercial '{$prohibido}'."
                );
            }

            // Debe contener los datos técnicos requeridos
            $this->assertArrayHasKey('muestra_id', $fila);
            $this->assertArrayHasKey('codigo_lab', $fila);
            $this->assertArrayHasKey('material_descripcion', $fila);
            $this->assertArrayHasKey('fecha_moldeo', $fila);
            $this->assertArrayHasKey('fecha_ingreso', $fila);
            $this->assertArrayHasKey('proxima_rotura', $fila);
            $this->assertArrayHasKey('es_vencida', $fila);
            $this->assertArrayHasKey('es_hoy', $fila);
        }
    }

    /**
     * Comprueba que un usuario con rol Técnico (Rol 6) NO PUEDA reidentificar una muestra (403 Forbidden).
     */
    public function testRolTecnicoTieneAccesoDenegadoParaReidentificarMuestra(): void {
        $resultado = $this->modelo->reidentificarMuestra(
            $this->codigoLabTest,
            999,
            'Técnico de Laboratorio',
            6, // Rol Técnico
            '127.0.0.1',
            'Intento de consulta por curiosidad'
        );

        $this->assertFalse($resultado['exito']);
        $this->assertEquals(403, $resultado['codigo_http']);
        $this->assertStringContainsString('ISO 17025', $resultado['mensaje']);
    }

    /**
     * Comprueba que la reidentificación exija motivo justificado para auditoría.
     */
    public function testReidentificacionExigeMotivoDeAuditoria(): void {
        $resultado = $this->modelo->reidentificarMuestra(
            $this->codigoLabTest,
            1,
            'Admin',
            1, // Rol Admin
            '127.0.0.1',
            'xyz' // Menor a 5 caracteres
        );

        $this->assertFalse($resultado['exito']);
        $this->assertEquals(422, $resultado['codigo_http']);
    }

    /**
     * Comprueba que la reidentificación autorizada registre en bitacora_reidentificacion y bitacora.
     */
    public function testReidentificacionExitosaQuedaRegistradaEnBitacoraInmutable(): void {
        $motivoAuditoria = 'Auditoría externa anual ISO 17025 Cláusula 4.2';
        $resultado = $this->modelo->reidentificarMuestra(
            $this->codigoLabTest,
            1,
            'Gerente de Calidad',
            3, // Rol Calidad
            '192.168.1.50',
            $motivoAuditoria
        );

        $this->assertTrue($resultado['exito']);
        $this->assertEquals(200, $resultado['codigo_http']);
        $this->assertEquals('Constructora Confidencial S.A.', $resultado['datos']['cliente']);
        $this->assertEquals('Puente Paso Real Proyecto Secreto', $resultado['datos']['proyecto']);

        // Verificar registro en bitacora_reidentificacion
        $stmtCheck = $this->db->prepare("
            SELECT * FROM bitacora_reidentificacion 
            WHERE codigo_lab = :cod AND motivo = :mot 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCheck->execute(['cod' => $this->codigoLabTest, 'mot' => $motivoAuditoria]);
        $registroReid = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($registroReid);
        $this->assertEquals('192.168.1.50', $registroReid['ip']);
        $this->assertEquals('Gerente de Calidad / Supervisor', $registroReid['usuario_rol']);
    }

    /**
     * Comprueba que el Dashboard de Laboratorio priorice el apartado de aprobación
     * y no contenga el tablero kanban de 4 columnas, manteniendo el formato de hoja RT-FM-60.
     */
    public function testDashboardLaboratorioTieneApartadoAprobacionPrimeroYSinTableroKanban(): void {
        $vistaRuta = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/laboratorio_dashboard.php';
        $this->assertFileExists($vistaRuta);

        $contenidoVista = file_get_contents($vistaRuta);

        // Debe tener la pestaña de aprobación como primera opción y por defecto
        $this->assertStringContainsString('tab=aprobacion', $contenidoVista);
        $this->assertStringContainsString('1. Solicitudes por Aprobar', $contenidoVista);
        $this->assertStringContainsString('modalAprobarMuestra', $contenidoVista);
        $this->assertStringContainsString('modalObservarSolicitud', $contenidoVista);

        // NO debe contener el grid del tablero kanban
        $this->assertStringNotContainsString('kanban-board', $contenidoVista);
        $this->assertStringNotContainsString('Tablero Kanban Ciego', $contenidoVista);

        // El formato de la hoja debe estar preservado
        $hojaPrintRuta = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/hoja_solicitud_laboratorio_print.php';
        $this->assertFileExists($hojaPrintRuta);
        $this->assertStringContainsString('imprimir-solicitud', $contenidoVista);
        $this->assertStringContainsString('Hoja RT-FM-60', $contenidoVista);
    }
}

