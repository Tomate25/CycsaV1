<?php

namespace Tests\Unit;

use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Servicios\DocumentoRgFm39Servicio;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;

class OrdenServicioRgFm39MsMcTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private int $usuarioId = 0;

    /** @var array<int> */
    private array $osIds = [];
    /** @var array<int> */
    private array $cotIds = [];
    /** @var array<int> */
    private array $detalleIds = [];
    /** @var array<int> */
    private array $clienteIds = [];
    /** @var array<int> */
    private array $usuarioIds = [];
    /** @var array<int> */
    private array $recepcionIds = [];
    /** @var array<int> */
    private array $loteIds = [];
    /** @var array<int> */
    private array $informeIds = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->osModelo = new OrdenServicioModelo();
        $this->asegurarEsquemaMinimo();
        $this->ejecutarMigracion025();
        $this->usuarioId = $this->insertar('usuarios', [
            'id_rol' => 1,
            'nombre' => 'Supervisor C6',
            'email' => 'c6-rgfm39-' . uniqid('', true) . '@test.local',
            'password' => 'x',
            'activo' => 1,
        ]);
    }

    protected function tearDown(): void {
        if (isset($this->db) && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
        if (isset($this->db)) {
            $this->borrarIds('informes_control', 'id', $this->informeIds);
            $this->borrarIds('ensayo_edades', 'id_lote', $this->loteIds);
            $this->borrarIds('lotes_muestras', 'id', $this->loteIds);
            $this->borrarIds('recepcion_muestras', 'id', $this->recepcionIds);
            $this->borrarIds('orden_servicio_ensayos_permitidos', 'id_os', $this->osIds);
            $this->borrarIds('ordenes_servicio', 'id', $this->osIds);
            $this->borrarIds('cotizacion_detalles', 'id', $this->detalleIds);
            $this->borrarIds('cotizaciones', 'id', $this->cotIds);
            $this->borrarIds('clientes', 'id', $this->clienteIds);
            $this->borrarIds('usuarios', 'id', $this->usuarioIds);
        }
        parent::tearDown();
    }

    public function testPdfYHtmlIncluyenMsMcInformeAprobadoYOcultanEnsayoNoAutorizado(): void {
        $marca = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $codigoMs = 'MS-88' . substr($marca, 0, 2) . '-26';
        $codigoMc = 'MC-77' . substr($marca, 2, 2) . '-26';
        $ensayoOk = 'ENSAYO-AUTORIZADO-C6-' . $marca;
        $ensayoNo = 'ENSAYO-BLOQUEADO-C6-' . $marca;
        $cotCodigo = 'COT-C6-' . $marca;
        $infOk = 'CYCSA-INF-C6-OK-' . $marca;
        $infPend = 'CYCSA-INF-C6-PEN-' . $marca;

        $fixture = $this->crearOrden([$ensayoOk, $ensayoNo], $cotCodigo, 9);
        $this->osModelo->guardarEnsayosPermitidos($fixture['os'], [$fixture['detalles'][0]], $this->usuarioId);

        $recepcionMs = $this->insertarRecepcion($fixture['os'], $codigoMs, 'Laboratorio');
        $recepcionMc = $this->insertarRecepcion($fixture['os'], $codigoMc, 'Campo');
        $loteMs = $this->insertarLote($recepcionMs, $fixture['detalles'][0]);
        $loteMc = $this->insertarLote($recepcionMc, $fixture['detalles'][0]);
        $idAprobado = $this->insertarInforme($loteMs, $infOk, 'Aprobado');
        $idPendiente = $this->insertarInforme($loteMc, $infPend, 'Pendiente');

        $os = $this->osModelo->obtenerPorId($fixture['os']);
        $this->assertNotNull($os);
        $this->assertCount(1, $os['ensayos']);
        $this->assertSame($ensayoOk, $os['ensayos'][0]['descripcion_ensayo']);
        $this->assertContains($codigoMs, $os['codigos_muestra']);
        $this->assertContains($codigoMc, $os['codigos_muestra']);

        $html = DocumentoRgFm39Servicio::html($os);
        $pdf = DocumentoRgFm39Servicio::pdf($os);
        $textoPdf = $this->extraerTextoPdf($pdf);

        foreach ([$html, $textoPdf] as $salida) {
            $this->assertStringContainsString($codigoMs, $salida);
            $this->assertStringContainsString($codigoMc, $salida);
            $this->assertStringContainsString($ensayoOk, $salida);
            $this->assertStringNotContainsString($ensayoNo, $salida);
            $this->assertStringContainsString($cotCodigo, $salida);
            $this->assertStringContainsString('Versión 9', $salida);
            $this->assertStringContainsString('Cotización origen', $salida);
            $this->assertStringContainsString($infOk, $salida);
            $this->assertStringContainsString($infPend, $salida);
            $this->assertStringContainsString('Aprobado', $salida);
            $this->assertStringContainsString('Pendiente', $salida);
            $this->assertStringContainsString('(505) 2310-3988', $salida);
            $this->assertStringContainsString('gerencia@cycsanic.com', $salida);
            $this->assertStringContainsString('Lic. MTI No. 6693', $salida);
        }

        $this->assertStringContainsString('/Cycsa/publico/informes/descargar?id=' . $idAprobado, $html);
        $this->assertStringNotContainsString('/Cycsa/publico/informes/descargar?id=' . $idPendiente, $html);
        $this->assertSame(1, substr_count($html, 'Abrir PDF'));
        $this->assertStringContainsString('Abrir PDF', $textoPdf);
    }

    public function testOrdenLegadaSinWhitelistMuestraTodosLosEnsayosYSinRecepcionNoInventaCodigos(): void {
        $marca = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $ensayoA = 'ENSAYO-LEGADO-A-C6-' . $marca;
        $ensayoB = 'ENSAYO-LEGADO-B-C6-' . $marca;
        $fixture = $this->crearOrden([$ensayoA, $ensayoB], 'COT-C6L-' . substr($marca, 0, 4), 3);

        $os = $this->osModelo->obtenerPorId($fixture['os']);
        $this->assertNotNull($os);
        $this->assertCount(2, $os['ensayos']);
        $this->assertSame([], $os['codigos_muestra']);

        $html = DocumentoRgFm39Servicio::html($os);
        $this->assertStringContainsString($ensayoA, $html);
        $this->assertStringContainsString($ensayoB, $html);
        $this->assertStringNotContainsString('MS-', $html);
        $this->assertStringNotContainsString('MC-', $html);
        $this->assertStringContainsString('Sin informes vinculados.', $html);
        $this->assertStringNotContainsString('Abrir PDF', $html);
    }

    public function testRutaYMenuConservanElPdfDeHojasYAgreganElDeLaOrden(): void {
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $index = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php');
        $this->assertNotFalse($rutas);
        $this->assertNotFalse($index);
        $this->assertStringContainsString("'/ordenes-servicio/descargar-pdf'", $rutas);
        $this->assertStringContainsString('hojas-servicio/descargar?id_os=', $index);
        $this->assertStringContainsString('ordenes-servicio/descargar-pdf?id=', $index);
        $this->assertStringContainsString('Descargar PDF Oficial', $index);
    }

    /**
     * @param array<int, string> $nombres
     * @return array{os: int, cotizacion: int, detalles: array<int>}
     */
    private function crearOrden(array $nombres, string $codigoCot, int $version): array {
        $clienteId = $this->insertar('clientes', [
            'nombre_razon_social' => 'Cliente C6 ' . uniqid(),
            'identificacion' => 'J031C6',
            'numero_ruc' => 'J031C6',
            'contacto_nombre' => 'Ing C6',
            'contacto' => 'Ing C6',
            'direccion' => 'León',
            'telefono' => '88881111',
            'email' => 'cliente-c6-' . uniqid('', true) . '@test.local',
            'contacto_correo' => 'cliente-c6@test.local',
            'activo' => 1,
        ]);
        $cotId = $this->insertar('cotizaciones', [
            'codigo' => $codigoCot,
            'id_cliente' => $clienteId,
            'cliente_id' => $clienteId,
            'id_usuario_creador' => $this->usuarioId,
            'id_cliente_factura' => $clienteId,
            'id_cliente_informe' => $clienteId,
            'atencion_a' => 'Ing C6',
            'nombre_proyecto' => 'Proyecto C6',
            'direccion_proyecto' => 'León',
            'condicion_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'estado' => 'Aprobada por Cliente',
            'subtotal' => 100,
            'impuesto' => 0,
            'total' => 100,
            'fecha' => '2026-10-29',
            'folio' => $codigoCot,
            'version' => $version,
        ]);
        $detalles = [];
        foreach ($nombres as $nombre) {
            $detalles[] = $this->insertar('cotizacion_detalles', [
                'id_cotizacion' => $cotId,
                'descripcion_ensayo' => $nombre,
                'cantidad' => 1,
                'precio_unitario' => 10,
                'subtotal' => 10,
                'codigo_servicio' => 'SRV-C6',
                'norma_astm' => 'ASTM C6',
            ]);
        }
        $osId = $this->insertar('ordenes_servicio', [
            'codigo_os' => 'OS-C6-' . uniqid(),
            'codigo' => 'OS-C6-' . uniqid(),
            'id_cotizacion' => $cotId,
            'cotizacion_id' => $cotId,
            'id_cliente' => $clienteId,
            'cliente_id' => $clienteId,
            'id_cliente_factura' => $clienteId,
            'id_cliente_informe' => $clienteId,
            'condicion_comercial' => 'contado',
            'elaborado_por' => 'Supervisor C6',
            'fecha_emision' => '2026-10-29',
            'fecha' => '2026-10-29',
            'atencion_a' => 'Ing C6',
            'nombre_proyecto' => 'Proyecto C6',
            'forma_pago' => 'Contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada',
            'requiere_muestreo' => 0,
        ]);

        return ['os' => $osId, 'cotizacion' => $cotId, 'detalles' => $detalles];
    }

    private function insertarRecepcion(int $idOS, string $codigo, string $tipo): int {
        $id = $this->insertar('recepcion_muestras', [
            'id_os' => $idOS,
            'orden_id' => $idOS,
            'correlativo_anual' => random_int(1000, 9999),
            'anio' => 2026,
            'tipo_muestra' => $tipo,
            'codigo_muestra' => $codigo,
            'codigo_campo' => $codigo,
            'fecha_recepcion' => '2026-10-29 12:00:00',
            'recibido_por' => $this->usuarioId,
            'entregado_por' => 'Cliente C6',
            'estado' => 'Registrado',
            'is_sealed' => 1,
            'is_qa_qc' => 0,
        ]);
        $this->recepcionIds[] = $id;
        return $id;
    }

    private function insertarLote(int $idRecepcion, int $idDetalle): int {
        $loteId = $this->insertar('lotes_muestras', [
            'id_recepcion' => $idRecepcion,
            'nombre_lote' => 'Lote C6 ' . $idRecepcion,
            'fecha_moldeo' => '2026-10-29',
        ]);
        $this->loteIds[] = $loteId;
        $this->insertar('ensayo_edades', [
            'id_lote' => $loteId,
            'id_detalle_cotizacion' => $idDetalle,
            'identificador_especimen' => 'M1',
            'edad_dias' => 0,
            'fecha_programada' => '2026-10-29',
            'estado' => 'Completado',
        ]);
        return $loteId;
    }

    private function insertarInforme(int $idLote, string $codigo, string $estado): int {
        $id = $this->insertar('informes_control', [
            'id_lote' => $idLote,
            'codigo_informe' => $codigo,
            'codigo_completo' => $codigo,
            'version' => 1,
            'tipo_informe' => 'Parcial',
            'estado_aprobacion' => $estado,
            'ruta_archivo_pdf' => 'almacenamiento/informes/' . $codigo . '.pdf',
        ]);
        $this->informeIds[] = $id;
        return $id;
    }

    private function extraerTextoPdf(string $pdfBytes): string {
        $this->assertStringStartsWith('%PDF', $pdfBytes);
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $tempPdf = tempnam($cacheDir, 'os_c6_pdf_');
        $tempScript = tempnam($cacheDir, 'os_c6_py_');
        file_put_contents($tempPdf, $pdfBytes);
        file_put_contents(
            $tempScript,
            "import sys\nimport io\nimport pymupdf\nsys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')\ndoc = pymupdf.open(sys.argv[1])\nfor page in doc:\n    print(page.get_text())\n"
        );
        $output = [];
        $exitCode = 1;
        @exec('python3 ' . escapeshellarg($tempScript) . ' ' . escapeshellarg($tempPdf), $output, $exitCode);
        @unlink($tempPdf);
        @unlink($tempScript);
        $this->assertSame(0, $exitCode, 'No fue posible extraer el texto del PDF.');
        return implode("\n", $output);
    }

    private function ejecutarMigracion025(): void {
        $ruta = dirname(__DIR__, 2) . '/database/migrations/025_orden_servicio_ensayos_permitidos.php';
        require_once $ruta;
        ob_start();
        ejecutarMigracion025($this->db);
        ob_end_clean();
    }

    private function asegurarEsquemaMinimo(): void {
        $this->crearSiNoExiste('usuarios', "CREATE TABLE `usuarios` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_rol` INT(11) NOT NULL DEFAULT 1,
            `nombre` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('clientes', "CREATE TABLE `clientes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre_razon_social` VARCHAR(255) NOT NULL,
            `identificacion` VARCHAR(50) NULL,
            `numero_ruc` VARCHAR(50) NULL,
            `numero_cedula` VARCHAR(50) NULL,
            `contacto_nombre` VARCHAR(150) NULL,
            `contacto` VARCHAR(150) NULL,
            `direccion` TEXT NULL,
            `telefono` VARCHAR(50) NULL,
            `email` VARCHAR(150) NULL,
            `contacto_correo` VARCHAR(150) NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('cotizaciones', "CREATE TABLE `cotizaciones` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo` VARCHAR(50) NOT NULL,
            `folio` VARCHAR(50) NULL,
            `id_cliente` INT(11) NOT NULL,
            `cliente_id` INT(11) NULL,
            `id_usuario_creador` INT(11) NULL,
            `id_cliente_factura` INT(11) NULL,
            `id_cliente_informe` INT(11) NULL,
            `condicion_comercial` VARCHAR(50) NULL,
            `atencion_a` VARCHAR(150) NULL,
            `nombre_proyecto` VARCHAR(200) NULL,
            `direccion_proyecto` TEXT NULL,
            `condicion_pago` VARCHAR(100) NULL,
            `estado` VARCHAR(50) NULL,
            `fecha` DATE NULL,
            `version` INT(11) NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->asegurarColumna('cotizaciones', 'version', '`version` INT(11) NULL DEFAULT 1');
        $this->asegurarColumna('cotizaciones', 'id_cliente_factura', '`id_cliente_factura` INT(11) NULL');
        $this->asegurarColumna('cotizaciones', 'id_cliente_informe', '`id_cliente_informe` INT(11) NULL');
        $this->asegurarColumna('cotizaciones', 'condicion_comercial', '`condicion_comercial` VARCHAR(50) NULL');
        $this->crearSiNoExiste('cotizacion_detalles', "CREATE TABLE `cotizacion_detalles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_cotizacion` INT(11) NOT NULL,
            `id_producto` INT(11) NULL,
            `descripcion_ensayo` TEXT NOT NULL,
            `descripcion_adicional` TEXT NULL,
            `codigo_servicio` VARCHAR(100) NULL,
            `norma_astm` VARCHAR(150) NULL,
            `condiciones_muestra` TEXT NULL,
            `procedimiento` VARCHAR(255) NULL,
            `unidad_medida` VARCHAR(50) NULL,
            `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 1,
            `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `resultados_json` TEXT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->asegurarColumna('cotizacion_detalles', 'resultados_json', '`resultados_json` TEXT NULL');
        $this->crearSiNoExiste('ordenes_servicio', "CREATE TABLE `ordenes_servicio` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo_os` VARCHAR(80) NOT NULL,
            `codigo` VARCHAR(80) NULL,
            `id_cotizacion` INT(11) NOT NULL,
            `cotizacion_id` INT(11) NULL,
            `id_cliente` INT(11) NULL,
            `cliente_id` INT(11) NULL,
            `id_cliente_factura` INT(11) NULL,
            `id_cliente_informe` INT(11) NULL,
            `condicion_comercial` VARCHAR(50) NULL,
            `elaborado_por` VARCHAR(150) NULL,
            `fecha_emision` DATE NOT NULL,
            `fecha` DATE NULL,
            `atencion_a` VARCHAR(150) NULL,
            `nombre_proyecto` VARCHAR(200) NULL,
            `forma_pago` VARCHAR(100) NULL,
            `tipo_contrato` VARCHAR(30) NULL,
            `estado` VARCHAR(100) NULL,
            `motivo_observacion` TEXT NULL,
            `requiere_muestreo` TINYINT(1) NULL,
            `tecnico_muestreo` VARCHAR(150) NULL,
            `vehiculo_muestreo` VARCHAR(100) NULL,
            `fecha_muestreo` DATE NULL,
            `hora_muestreo` TIME NULL,
            `contactos_json` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `codigo_os` (`codigo_os`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'codigo' => '`codigo` VARCHAR(80) NULL',
            'cotizacion_id' => '`cotizacion_id` INT(11) NULL',
            'cliente_id' => '`cliente_id` INT(11) NULL',
            'tecnico_muestreo' => '`tecnico_muestreo` VARCHAR(150) NULL',
            'vehiculo_muestreo' => '`vehiculo_muestreo` VARCHAR(100) NULL',
            'fecha_muestreo' => '`fecha_muestreo` DATE NULL',
            'hora_muestreo' => '`hora_muestreo` TIME NULL',
            'motivo_observacion' => '`motivo_observacion` TEXT NULL',
            'contactos_json' => '`contactos_json` TEXT NULL',
            'id_cliente_factura' => '`id_cliente_factura` INT(11) NULL',
            'id_cliente_informe' => '`id_cliente_informe` INT(11) NULL',
            'condicion_comercial' => '`condicion_comercial` VARCHAR(50) NULL',
            'elaborado_por' => '`elaborado_por` VARCHAR(150) NULL',
            'fecha' => '`fecha` DATE NULL',
            'atencion_a' => '`atencion_a` VARCHAR(150) NULL',
            'nombre_proyecto' => '`nombre_proyecto` VARCHAR(200) NULL',
            'forma_pago' => '`forma_pago` VARCHAR(100) NULL',
            'tipo_contrato' => '`tipo_contrato` VARCHAR(30) NULL',
            'estado' => '`estado` VARCHAR(100) NULL',
            'requiere_muestreo' => '`requiere_muestreo` TINYINT(1) NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('ordenes_servicio', $columna, $definicion);
        }
        $this->crearSiNoExiste('productos', "CREATE TABLE `productos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `formato_id` INT(11) NULL,
            `ensayo_servicio` TEXT NULL,
            `nombre_comercial` VARCHAR(255) NULL,
            `codigo_servicio` VARCHAR(100) NULL,
            `norma_astm` VARCHAR(255) NULL,
            `condiciones_muestra` TEXT NULL,
            `procedimiento_muestreo` VARCHAR(255) NULL,
            `unidad_medida` VARCHAR(50) NULL,
            `codigo_hoja_campo` VARCHAR(100) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->asegurarColumna('productos', 'formato_id', '`formato_id` INT(11) NULL');
        $this->crearSiNoExiste('formatos_ensayos', "CREATE TABLE `formatos_ensayos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(150) NULL,
            `archivo_markdown` VARCHAR(255) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('tecnicos', "CREATE TABLE `tecnicos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(150) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('vehiculos', "CREATE TABLE `vehiculos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `marca` VARCHAR(80) NULL,
            `modelo` VARCHAR(80) NULL,
            `placa` VARCHAR(30) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('programacion_muestreo', "CREATE TABLE `programacion_muestreo` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_orden_servicio` INT(11) NOT NULL,
            `id_tecnico` INT(11) NULL,
            `id_vehiculo` INT(11) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('recepcion_muestras', "CREATE TABLE `recepcion_muestras` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_os` INT(11) NULL,
            `correlativo_anual` INT(11) NULL,
            `anio` INT(11) NULL,
            `tipo_muestra` VARCHAR(30) NULL,
            `codigo_muestra` VARCHAR(50) NULL,
            `codigo_campo` VARCHAR(80) NULL,
            `fecha_recepcion` DATETIME NULL,
            `recibido_por` INT(11) NULL,
            `estado` VARCHAR(40) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('lotes_muestras', "CREATE TABLE `lotes_muestras` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_recepcion` INT(11) NULL,
            `nombre_lote` VARCHAR(150) NULL,
            `fecha_moldeo` DATE NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('ensayo_edades', "CREATE TABLE `ensayo_edades` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_lote` INT(11) NULL,
            `id_detalle_cotizacion` INT(11) NULL,
            `identificador_especimen` VARCHAR(80) NULL,
            `edad_dias` INT(11) NULL,
            `fecha_programada` DATE NULL,
            `estado` VARCHAR(40) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->crearSiNoExiste('informes_control', "CREATE TABLE `informes_control` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_lote` INT(11) NOT NULL,
            `codigo_informe` VARCHAR(80) NOT NULL,
            `version` INT(11) NULL DEFAULT 0,
            `codigo_completo` VARCHAR(80) NOT NULL,
            `tipo_informe` VARCHAR(30) NULL,
            `estado_aprobacion` VARCHAR(30) NULL,
            `ruta_archivo_pdf` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function crearSiNoExiste(string $tabla, string $sql): void {
        $existe = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetch(PDO::FETCH_NUM);
        if (!$existe) {
            $this->db->exec($sql);
        }
    }

    private function asegurarColumna(string $tabla, string $columna, string $definicion): void {
        $meta = $this->metaColumnas($tabla);
        if (!isset($meta[$columna])) {
            $this->db->exec('ALTER TABLE `' . $tabla . '` ADD COLUMN ' . $definicion);
        }
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function insertar(string $tabla, array $datos): int {
        $meta = $this->metaColumnas($tabla);
        $fila = [];
        foreach ($datos as $columna => $valor) {
            if (isset($meta[$columna])) {
                $fila[$columna] = $valor;
            }
        }
        foreach ($meta as $columna => $info) {
            if ($columna === 'id' || array_key_exists($columna, $fila)) {
                continue;
            }
            $extra = strtolower((string)($info['Extra'] ?? ''));
            $nullable = strtoupper((string)($info['Null'] ?? '')) === 'YES';
            $tieneDefault = array_key_exists('Default', $info) && $info['Default'] !== null;
            if (!$nullable && !$tieneDefault && !str_contains($extra, 'auto_increment')) {
                $fila[$columna] = $this->valorPorTipo((string)$info['Type'], $columna);
            }
        }
        $columnas = array_keys($fila);
        $marcas = array_map(static fn(string $columna): string => ':' . $columna, $columnas);
        $sql = 'INSERT INTO `' . $tabla . '` (`' . implode('`,`', $columnas) . '`) VALUES (' . implode(',', $marcas) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($fila);
        $id = (int)$this->db->lastInsertId();
        $this->recordarId($tabla, $id);
        return $id;
    }

    private function recordarId(string $tabla, int $id): void {
        if ($id <= 0) {
            return;
        }
        match ($tabla) {
            'ordenes_servicio' => $this->osIds[] = $id,
            'cotizaciones' => $this->cotIds[] = $id,
            'cotizacion_detalles' => $this->detalleIds[] = $id,
            'clientes' => $this->clienteIds[] = $id,
            'usuarios' => $this->usuarioIds[] = $id,
            default => null,
        };
    }

    /**
     * @param array<int> $ids
     */
    private function borrarIds(string $tabla, string $columna, array $ids): void {
        if ($ids === []) {
            return;
        }
        $existe = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetch(PDO::FETCH_NUM);
        if (!$existe) {
            return;
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` IN ($marcas)");
        $stmt->execute($ids);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function metaColumnas(string $tabla): array {
        $filas = $this->db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $tabla) . '`')->fetchAll(PDO::FETCH_ASSOC);
        $meta = [];
        foreach ($filas as $fila) {
            $meta[$fila['Field']] = $fila;
        }
        return $meta;
    }

    private function valorPorTipo(string $tipo, string $columna): mixed {
        $tipo = strtolower($tipo);
        if (str_starts_with($tipo, 'enum(')) {
            if (preg_match("/enum\\('([^']+)'/i", $tipo, $m)) {
                return $m[1];
            }
            return '';
        }
        if (str_contains($tipo, 'int') || str_contains($tipo, 'decimal') || str_contains($tipo, 'float') || str_contains($tipo, 'double')) {
            return 0;
        }
        if (str_contains($tipo, 'date') && !str_contains($tipo, 'time')) {
            return '2026-10-29';
        }
        if (str_contains($tipo, 'time') || str_contains($tipo, 'timestamp') || str_contains($tipo, 'datetime')) {
            return '2026-10-29 12:00:00';
        }
        if (str_contains($columna, 'email')) {
            return 'c6-' . uniqid('', true) . '@test.local';
        }
        return 'c6';
    }
}
