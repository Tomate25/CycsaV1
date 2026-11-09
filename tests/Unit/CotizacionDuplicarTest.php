<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;

class CotizacionDuplicarTest extends TestCase {

    private ?PDO $db = null;

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $this->db = Conexion::obtenerInstancia();
    }

    private function obtenerClienteYUsuario(): array {
        $cli = $this->db->query("SELECT id FROM clientes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $usr = $this->db->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios disponibles en la BD para pruebas.');
        }
        return [(int)$cli['id'], (int)$usr['id']];
    }

    private function obtenerProducto(): array {
        $prod = $this->db->query("SELECT id, nombre_comercial, codigo_servicio, norma_astm, precio FROM productos LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$prod) {
            $this->markTestSkipped('No hay productos disponibles en el catálogo.');
        }
        return $prod;
    }

    /**
     * Valida que generarCodigoUnico() calcula el correlativo basado en el máximo existente.
     */
    public function testGenerarCodigoUnicoUsaMaxConsecutivo(): void {
        $modelo = new CotizacionModelo();
        $codigoGenerado = $modelo->generarCodigoUnico();
        
        $año = date('Y');
        $this->assertMatchesRegularExpression("/^COT-{$año}-\\d{4}$/", $codigoGenerado);

        $stmtMax = $this->db->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) FROM cotizaciones WHERE codigo LIKE :anio");
        $stmtMax->execute(['anio' => "COT-{$año}-%"]);
        $maxActual = (int)$stmtMax->fetchColumn();

        $numeroGenerado = (int)substr($codigoGenerado, -4);
        $this->assertSame($maxActual + 1, $numeroGenerado);
    }

    /**
     * Valida la duplicación integral: datos comerciales, proyecto, notas, opciones seleccionadas,
     * comentarios, anexos y todos los ítems cotizados con sus valores.
     */
    public function testDuplicarCotizacionCopiaTodosLosCamposEItems(): void {
        [$idCliente, $idUsuario] = $this->obtenerClienteYUsuario();
        $prod = $this->obtenerProducto();

        $modelo = new CotizacionModelo();

        // 1. Crear cotización base para la prueba
        $codigoBase = $modelo->generarCodigoUnico();
        $notasConfig = json_encode([
            'incluir_normas' => true,
            'condiciones_especiales' => 'El cliente requiere entrega de informes los viernes',
            'observaciones_comerciales' => 'Descuento autorizado por Gerencia de Operaciones',
            'opcion_transporte' => 'cliente_recoge'
        ]);

        $cabeceraOriginal = [
            'codigo' => $codigoBase,
            'id_cliente' => $idCliente,
            'id_cliente_factura' => $idCliente,
            'id_cliente_informe' => $idCliente,
            'condicion_comercial' => 'credito',
            'tipo_moneda' => 1, // Córdobas
            'id_usuario_creador' => $idUsuario,
            'atencion_a' => 'Ing. Juan Pérez - Director de Obra',
            'nombre_proyecto' => 'Proyecto Residencial Las Colinas Fase 2',
            'direccion_proyecto' => 'Km 10 Carretera a Masaya, 500m al sur',
            'prioridad' => 'Alta',
            'fecha_limite' => null,
            'condicion_pago' => 'Crédito 30 días',
            'tiempo_entrega' => '5 días hábiles tras muestreo',
            'vigencia_oferta' => '30 días calendario',
            'configuracion_notas' => $notasConfig,
            'contactos' => 'Contacto en obra: +505 8888-9999',
            'incluir_anexo_tecnico' => 1,
            'anexo_tecnico' => 'Se requiere ensayar bajo norma ASTM C39 con refrentado de azufre.',
            'archivo_adjunto' => 'adjuntos/especificaciones_obra_colinas.pdf',
            'subtotal' => 2000.00,
            'descuento' => 200.00,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 270.00,
            'total' => 2070.00,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null
        ];

        $detallesOriginales = [
            [
                'id_producto' => (int)$prod['id'],
                'descripcion' => 'Compresión de Cilindros de Concreto 4"x8"',
                'condiciones_muestra' => 'Curadas en agua',
                'procedimiento' => 'ASTM C31 / ASTM C39',
                'unidad_medida' => 'Cilindro',
                'codigo_servicio' => $prod['codigo_servicio'] ?? 'CYCSA-PE-07',
                'norma_astm' => 'ASTM C39/C39M-21',
                'formato_reporte' => 'CYCSA-RT-FM-22 A',
                'observaciones' => 'Edad de rotura a los 7 y 28 días',
                'descripcion_adicional' => 'Probeta cilíndrica estándar',
                'cantidad' => 10.0,
                'precio' => 150.00,
                'subtotal' => 1500.00
            ],
            [
                'id_producto' => (int)$prod['id'],
                'descripcion' => 'Ensayo de Revenimiento (Slump Test) in situ',
                'condiciones_muestra' => 'Concreto fresco en obra',
                'procedimiento' => 'ASTM C143',
                'unidad_medida' => 'Ensayo',
                'codigo_servicio' => 'CYCSA-PE-09',
                'norma_astm' => 'ASTM C143/C143M',
                'formato_reporte' => 'CYCSA-RT-FM-22 D',
                'observaciones' => 'Tolerancia slump 4" +- 1"',
                'descripcion_adicional' => 'Prueba en cono de Abrams',
                'cantidad' => 2.0,
                'precio' => 250.00,
                'subtotal' => 500.00
            ]
        ];

        $guardado = $modelo->guardarCotizacionCompleta($cabeceraOriginal, $detallesOriginales);
        $this->assertTrue($guardado, 'La cotización original de prueba debe guardarse correctamente.');

        // Obtener el ID de la cotización recién creada mediante su código
        $stmtId = $this->db->prepare("SELECT id FROM cotizaciones WHERE codigo = :cod");
        $stmtId->execute(['cod' => $codigoBase]);
        $idOriginal = (int)$stmtId->fetchColumn();
        $this->assertGreaterThan(0, $idOriginal);

        try {
            // 2. Ejecutar la duplicación
            $nuevoId = $modelo->duplicarCotizacion($idOriginal, $idUsuario);
            $this->assertGreaterThan(0, $nuevoId, 'El método duplicarCotizacion debe retornar el ID de la nueva cotización.');
            $this->assertNotSame($idOriginal, $nuevoId, 'La cotización duplicada debe tener un ID diferente al original.');

            // 3. Verificar datos de la cotización duplicada
            $duplicada = $modelo->obtenerPorId($nuevoId);
            $this->assertNotNull($duplicada);

            // A. Verificación del código consecutivo
            $this->assertNotSame($codigoBase, $duplicada['codigo'], 'El código debe ser diferente y nuevo.');
            $this->assertMatchesRegularExpression('/^COT-\d{4}-\d{4}$/', $duplicada['codigo']);
            $numOriginal = (int)substr($codigoBase, -4);
            $numDuplicado = (int)substr($duplicada['codigo'], -4);
            $this->assertGreaterThan($numOriginal, $numDuplicado, 'El consecutivo duplicado debe ser posterior al original.');

            // B. Verificación de estado inicial
            $this->assertSame('Borrador', $duplicada['estado'], 'Toda cotización duplicada debe iniciar en estado Borrador.');
            $this->assertSame(0, (int)$duplicada['version'], 'La versión inicial debe ser 0.');
            $this->assertNull($duplicada['token_seguridad'], 'El token de seguridad debe reiniciarse en el duplicado.');
            $this->assertNull($duplicada['motivo_observacion'], 'No debe heredar motivos de observación pasados.');
            $this->assertNull($duplicada['motivo_rechazo_cliente'], 'No debe heredar motivos de rechazo pasados.');

            // C. Verificación de copia idéntica de metadatos comerciales y de proyecto
            $this->assertSame((int)$idCliente, (int)$duplicada['id_cliente']);
            $this->assertSame($cabeceraOriginal['atencion_a'], $duplicada['atencion_a']);
            $this->assertSame($cabeceraOriginal['nombre_proyecto'], $duplicada['nombre_proyecto']);
            $this->assertSame($cabeceraOriginal['direccion_proyecto'], $duplicada['direccion_proyecto']);
            $this->assertSame($cabeceraOriginal['prioridad'], $duplicada['prioridad']);
            $this->assertSame($cabeceraOriginal['condicion_pago'], $duplicada['condicion_pago']);
            $this->assertSame($cabeceraOriginal['condicion_comercial'], $duplicada['condicion_comercial']);
            $this->assertSame($cabeceraOriginal['tiempo_entrega'], $duplicada['tiempo_entrega']);
            $this->assertSame($cabeceraOriginal['vigencia_oferta'], $duplicada['vigencia_oferta']);
            $this->assertSame($cabeceraOriginal['contactos'], $duplicada['contactos']);
            $this->assertSame((int)$cabeceraOriginal['incluir_anexo_tecnico'], (int)$duplicada['incluir_anexo_tecnico']);
            $this->assertSame($cabeceraOriginal['anexo_tecnico'], $duplicada['anexo_tecnico']);
            $this->assertSame($cabeceraOriginal['archivo_adjunto'], $duplicada['archivo_adjunto']);

            // D. Verificación de notas y configuraciones seleccionadas (JSON idéntico)
            $this->assertSame($notasConfig, $duplicada['configuracion_notas'], 'Las notas y opciones seleccionadas deben ser idénticas.');

            // E. Verificación de valores monetarios
            $this->assertEqualsWithDelta(2000.00, (float)$duplicada['subtotal'], 0.01);
            $this->assertEqualsWithDelta(200.00, (float)$duplicada['descuento'], 0.01);
            $this->assertEqualsWithDelta(270.00, (float)$duplicada['impuesto'], 0.01);
            $this->assertEqualsWithDelta(2070.00, (float)$duplicada['total'], 0.01);

            // F. Verificación de ítems y líneas de ensayo
            $detallesDuplicados = $modelo->obtenerDetalles($nuevoId);
            $this->assertCount(2, $detallesDuplicados, 'Deben haberse duplicado exactamente las 2 líneas de ensayo.');

            $this->assertSame('Compresión de Cilindros de Concreto 4"x8"', $detallesDuplicados[0]['descripcion_ensayo']);
            $this->assertEqualsWithDelta(10.0, (float)$detallesDuplicados[0]['cantidad'], 0.01);
            $this->assertEqualsWithDelta(150.00, (float)$detallesDuplicados[0]['precio_unitario'], 0.01);
            $this->assertEqualsWithDelta(1500.00, (float)$detallesDuplicados[0]['subtotal'], 0.01);
            $this->assertSame('Curadas en agua', $detallesDuplicados[0]['condiciones_muestra']);
            $this->assertSame('ASTM C39/C39M-21', $detallesDuplicados[0]['norma_astm']);
            $this->assertSame('Edad de rotura a los 7 y 28 días', $detallesDuplicados[0]['observaciones']);

            $this->assertSame('Ensayo de Revenimiento (Slump Test) in situ', $detallesDuplicados[1]['descripcion_ensayo']);
            $this->assertEqualsWithDelta(2.0, (float)$detallesDuplicados[1]['cantidad'], 0.01);
            $this->assertEqualsWithDelta(250.00, (float)$detallesDuplicados[1]['precio_unitario'], 0.01);
            $this->assertEqualsWithDelta(500.00, (float)$detallesDuplicados[1]['subtotal'], 0.01);

        } finally {
            // Limpieza de datos de prueba
            if (isset($nuevoId) && $nuevoId > 0) {
                $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $nuevoId]);
                $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $nuevoId]);
            }
            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $idOriginal]);
            $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idOriginal]);
        }
    }

    /**
     * Valida que intentar duplicar una cotización inexistente retorna 0 limpiamente.
     */
    public function testDuplicarCotizacionInexistenteRetornaCero(): void {
        $modelo = new CotizacionModelo();
        $resultado = $modelo->duplicarCotizacion(99999999, 1);
        $this->assertSame(0, $resultado);
    }

    /**
     * Valida que el controlador de duplicar cotización exige permisos y maneja la sesión.
     */
    public function testDuplicarControladorSinPermisosRedirige(): void {
        $_SESSION['usuario_id'] = 2;
        $_SESSION['usuario_rol'] = 3; // Rol que no es SuperAdmin
        $_SESSION['usuario_permisos'] = ['cotizaciones' => ['ver' => true, 'crear_editar' => false]];
        unset($_SESSION['error']);

        $ctrl = new CotizacionesControlador();
        $peticion = new Peticion();
        $respuesta = new Respuesta();

        $_GET['id'] = 1;
        $ctrl->duplicar($peticion, $respuesta);

        $this->assertNotEmpty($_SESSION['error'] ?? '');
        $this->assertStringContainsString('permisos', strtolower($_SESSION['error']));
    }

    /**
     * Valida que el controlador de duplicar cotización con permisos válidos
     * crea la nueva cotización y establece el mensaje de éxito en sesión.
     */
    public function testDuplicarControladorConPermisosDuplicaYRedirige(): void {
        [$idCliente, $idUsuario] = $this->obtenerClienteYUsuario();
        $_SESSION['usuario_id'] = $idUsuario;
        $_SESSION['usuario_rol'] = 1; // Administrador (permiso total)
        $_SESSION['usuario_permisos'] = ['cotizaciones' => ['crear_editar' => true, 'ver' => true]];
        unset($_SESSION['exito']);

        $modelo = new CotizacionModelo();
        $codigoBase = $modelo->generarCodigoUnico();

        $guardado = $modelo->guardarCotizacionCompleta([
            'codigo' => $codigoBase,
            'id_cliente' => $idCliente,
            'id_cliente_factura' => $idCliente,
            'id_cliente_informe' => $idCliente,
            'condicion_comercial' => 'contado',
            'tipo_moneda' => 1,
            'id_usuario_creador' => $idUsuario,
            'atencion_a' => 'Cliente Test',
            'nombre_proyecto' => 'Proyecto Test Duplicar Ctrl',
            'direccion_proyecto' => 'Managua',
            'prioridad' => 'Normal',
            'fecha_limite' => null,
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => 'Inmediato',
            'vigencia_oferta' => '15 días',
            'configuracion_notas' => json_encode(['comentario' => 'Nota original']),
            'contactos' => null,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 100.00,
            'descuento' => 0.00,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 15.00,
            'total' => 115.00,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null
        ], [
            [
                'id_producto' => 1,
                'descripcion' => 'Item de prueba',
                'cantidad' => 1,
                'precio' => 100.00,
                'subtotal' => 100.00
            ]
        ]);
        $this->assertTrue($guardado);

        $stmtId = $this->db->prepare("SELECT id FROM cotizaciones WHERE codigo = :cod");
        $stmtId->execute(['cod' => $codigoBase]);
        $idOriginal = (int)$stmtId->fetchColumn();

        $ctrl = new CotizacionesControlador();
        $peticion = new Peticion();
        $respuesta = new Respuesta();

        $_GET['id'] = codificarId($idOriginal);
        $ctrl->duplicar($peticion, $respuesta);

        $this->assertNotEmpty($_SESSION['exito'] ?? '');
        $this->assertStringContainsString('creada con éxito', $_SESSION['exito']);

        // Buscar la cotización duplicada en la base de datos
        $stmtDup = $this->db->prepare("SELECT id, codigo, estado, version FROM cotizaciones WHERE nombre_proyecto = 'Proyecto Test Duplicar Ctrl' AND id != :id ORDER BY id DESC LIMIT 1");
        $stmtDup->execute(['id' => $idOriginal]);
        $duplicada = $stmtDup->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($duplicada);
        $nuevoId = (int)$duplicada['id'];
        $this->assertGreaterThan(0, $nuevoId);
        $this->assertSame('Borrador', $duplicada['estado']);
        $this->assertSame(0, (int)$duplicada['version']);
        $this->assertNotSame($codigoBase, $duplicada['codigo']);

        // Limpiar registros de prueba
        $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $nuevoId]);
        $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $nuevoId]);
        $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $idOriginal]);
        $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $idOriginal]);
    }
}
