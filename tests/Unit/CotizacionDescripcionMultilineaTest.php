<?php

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Throwable;

class CotizacionDescripcionMultilineaTest extends TestCase {

    private function textoLargo(): string {
        $cuerpo = 'C1INICIO ' . str_repeat('C1PALABRA ', 70)
            . "\nC1SEGUNDA " . str_repeat('C1PALABRA ', 40)
            . "\n<script>alert(\"c1xss\")</script> C1FIN";
        $this->assertGreaterThan(1000, strlen($cuerpo));
        return $cuerpo;
    }

    private function cotizacionBase(): array {
        return [
            'id' => 99001,
            'codigo' => 'COT-C1-MULTI',
            'fecha_creacion' => '2026-10-29 10:00:00',
            'tipo_moneda' => 1,
            'subtotal' => 100.00,
            'descuento' => 0.00,
            'impuesto' => 15.00,
            'total' => 115.00,
            'cliente_nombre' => 'Cliente Multilinea SA',
            'cliente_ruc' => 'J031000000099',
            'atencion_a' => 'Ing. Prueba',
            'nombre_proyecto' => 'Proyecto Descripcion Larga',
            'direccion_proyecto' => 'Managua',
            'prioridad' => 'Normal',
            'version' => 1,
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'creador_nombre' => 'Asesor C1',
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => '',
            'archivo_adjunto' => null,
            'configuracion_notas' => json_encode(['digital_pdf' => 1]),
        ];
    }

    private function extraerTextoPdf(string $pdfBytes): string {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $tempPdf = tempnam($cacheDir, 'cot_c1_pdf_');
        $tempScript = tempnam($cacheDir, 'cot_c1_py_');
        file_put_contents($tempPdf, $pdfBytes);
        file_put_contents(
            $tempScript,
            "import sys\nimport io\nimport pymupdf\nsys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')\ndoc = pymupdf.open(sys.argv[1])\nfor page in doc:\n    print(page.get_text())\n"
        );

        $output = [];
        $exitCode = 1;
        $pyCmd = (PHP_OS_FAMILY === 'Windows') ? 'python' : 'python3';
        @exec($pyCmd . ' ' . escapeshellarg($tempScript) . ' ' . escapeshellarg($tempPdf), $output, $exitCode);
        if ($exitCode !== 0) {
            $output = [];
            $fallback = ($pyCmd === 'python') ? 'py' : 'python';
            @exec($fallback . ' ' . escapeshellarg($tempScript) . ' ' . escapeshellarg($tempPdf), $output, $exitCode);
        }
        @unlink($tempPdf);
        @unlink($tempScript);

        $this->assertSame(0, $exitCode, 'No fue posible extraer el texto del PDF.');
        return implode("\n", $output);
    }

    public function testHelperConservaSaltosYEscapaHtml(): void {
        $html = htmlDescripcionCotizacionMultilinea("uno\ndos <script>alert(\"c1xss\")</script>");
        $this->assertMatchesRegularExpression('/uno<br\s*\/?>\s*dos/', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&quot;c1xss&quot;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame('', htmlDescripcionCotizacionMultilinea(null));
        $this->assertSame('', htmlDescripcionCotizacionMultilinea(''));
    }

    public function testFormulariosExponenTextareaVisibleSinMaxlength(): void {
        $archivos = [
            dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/crear.php',
            dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/editar.php',
        ];

        foreach ($archivos as $archivo) {
            $src = file_get_contents($archivo);
            $this->assertNotFalse($src);
            $this->assertSame(3, substr_count($src, '<textarea name="ensayo_descripcion_adicional[]"'), basename($archivo));
            $this->assertStringNotContainsString('type="hidden" name="ensayo_descripcion_adicional[]"', $src);
            $this->assertStringNotContainsString('name="ensayo_descripcion_adicional[]" class="form-control spec-desc-adicional" value=', $src);
            $this->assertDoesNotMatchRegularExpression(
                '/<textarea name="ensayo_descripcion_adicional\[\]"[^>]*maxlength\s*=\s*"\d+"/i',
                $src
            );
            $this->assertGreaterThanOrEqual(3, substr_count($src, 'resize: vertical'));
            $this->assertStringContainsString('name="ensayo_desc[]"', $src);
            $this->assertStringContainsString('list="productos-datalist"', $src);
            $this->assertStringContainsString('type="text" name="ensayo_desc[]"', $src);
        }

        $editar = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/editar.php');
        $this->assertStringContainsString('readonly', $editar);
    }

    public function testDetalleMuestraSaltosYEscapaAmbasDescripciones(): void {
        $cotizacion = $this->cotizacionBase();
        $cotizacion['estado'] = 'Borrador';
        $cotizacion['fecha_actualizacion'] = '2026-10-29 10:00:00';
        $cotizacion['id_usuario_creador'] = 1;
        $ensayo = "Linea uno del ensayo\nLinea dos <script>alert(1)</script>";
        $adicional = "Adicional uno\nAdicional dos <img src=x onerror=alert(1)>";
        $detalles = [[
            'id' => 1,
            'id_producto' => null,
            'descripcion_ensayo' => $ensayo,
            'descripcion_adicional' => $adicional,
            'codigo_servicio' => 'CYC-C1',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
            'unidad_medida' => 'Unidad',
        ]];
        $versiones = [];
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_id'] = 1;

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/detalle.php';
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/Linea uno del ensayo<br\s*\/?>\s*Linea dos/', $html);
        $this->assertMatchesRegularExpression('/Adicional uno<br\s*\/?>\s*Adicional dos/', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function detallePdf(): array {
        $largo = $this->textoLargo();
        $ensayo = "Nombre catalogo corto\ncon salto";
        return [
            $this->cotizacionBase(),
            [[
                'descripcion_ensayo' => $ensayo,
                'descripcion_adicional' => $largo,
                'nombre_comercial' => 'Nombre catalogo corto',
                'condiciones_muestra' => 'Estándar',
                'procedimiento' => 'ASTM D6938',
                'unidad_medida' => 'Punto',
                'codigo_servicio' => 'CYC-C1',
                'cantidad' => 1,
                'precio_unitario' => 100.00,
                'subtotal' => 100.00,
                'observaciones' => '24 h',
            ]],
        ];
    }

    private function assertPdfContieneCuerpoCompleto(string $pdfBytes): void {
        $this->assertStringStartsWith('%PDF', $pdfBytes);
        $texto = $this->extraerTextoPdf($pdfBytes);
        $this->assertStringContainsString('C1INICIO', $texto);
        $this->assertStringContainsString('C1SEGUNDA', $texto);
        $this->assertStringContainsString('C1FIN', $texto);
        $this->assertStringContainsString('c1xss', $texto);
        $this->assertStringContainsString('Nombre catalogo corto', $texto);
        $this->assertStringContainsString('con salto', $texto);
        $this->assertGreaterThanOrEqual(100, substr_count($texto, 'C1PALABRA'));
        $this->assertStringNotContainsString('…', $texto);
    }

    public function testPdfEstandarImprimeDescripcionAdicionalCompleta(): void {
        [$cotizacion, $detalles] = $this->detallePdf();
        $this->assertPdfContieneCuerpoCompleto(generarCotizacionPDF($cotizacion, $detalles));
    }

    public function testPdfCompletoImprimeDescripcionAdicionalCompleta(): void {
        [$cotizacion, $detalles] = $this->detallePdf();
        $this->assertPdfContieneCuerpoCompleto(generarCotizacionCompletaPDF($cotizacion, $detalles));
    }

    private function conectarSiHayBd(): ?PDO {
        try {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo->query('SELECT 1');
            return Conexion::obtenerInstancia();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function testRoundTripPersisteMultilineaMayorAMilCaracteres(): void {
        $db = $this->conectarSiHayBd();
        if (!$db) {
            $this->markTestSkipped('Base de datos no disponible.');
        }

        $tabla = $db->query("SHOW TABLES LIKE 'cotizacion_detalles'")->fetch(PDO::FETCH_NUM);
        if (!$tabla) {
            $this->markTestSkipped('La tabla cotizacion_detalles no existe.');
        }

        ob_start();
        $migrationDb = $db;
        $db = $migrationDb;
        include dirname(__DIR__, 2) . '/database/migrations/024_cotizacion_descripcion_multilinea.php';
        ob_end_clean();

        $cli = $db->query('SELECT id FROM clientes LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $usr = $db->query('SELECT id FROM usuarios LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios para el round-trip.');
        }

        $largo = $this->textoLargo();
        $ensayo = "Ensayo multilinea C1\n" . rtrim(str_repeat('linea ensayo ', 40));
        $this->assertGreaterThan(500, strlen($ensayo));

        $metodo = new ReflectionMethod(CotizacionesControlador::class, 'procesarDetalles');
        $metodo->setAccessible(true);
        $controlador = new CotizacionesControlador();
        $procesados = $metodo->invoke($controlador, [
            'ensayo_desc' => [$ensayo],
            'ensayo_cant' => ['2'],
            'ensayo_precio' => ['50.5'],
            'ensayo_descripcion_adicional' => [$largo],
            'ensayo_id_producto' => [''],
            'ensayo_condiciones' => ["Muestra\ncon salto"],
            'ensayo_unidad' => ['Punto'],
            'ensayo_procedimiento' => ['ASTM D6938'],
        ]);
        $this->assertCount(1, $procesados);
        $this->assertSame($largo, $procesados[0]['descripcion_adicional']);
        $this->assertSame($ensayo, $procesados[0]['descripcion']);
        $this->assertStringContainsString("\n", $procesados[0]['descripcion_adicional']);

        $codigo = 'C1-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $modelo = new CotizacionModelo();
        $cabecera = [
            'codigo' => $codigo,
            'id_cliente' => (int)$cli['id'],
            'id_cliente_factura' => (int)$cli['id'],
            'id_cliente_informe' => (int)$cli['id'],
            'condicion_comercial' => 'contado',
            'tipo_moneda' => 1,
            'id_usuario_creador' => (int)$usr['id'],
            'atencion_a' => 'Ing. C1',
            'nombre_proyecto' => 'Roundtrip multilinea',
            'direccion_proyecto' => 'León',
            'prioridad' => 'Normal',
            'fecha_limite' => null,
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '3 días',
            'vigencia_oferta' => '15 días',
            'configuracion_notas' => null,
            'contactos' => null,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null,
            'subtotal' => 101.00,
            'descuento' => 0,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 15.15,
            'total' => 116.15,
            'fecha_entrega' => null,
            'fecha_seguimiento' => null,
        ];

        $idCot = 0;
        try {
            $ok = $modelo->guardarCotizacionCompleta($cabecera, $procesados);
            $this->assertTrue($ok, 'guardarCotizacionCompleta debe persistir la descripción larga.');

            $stmtId = $db->prepare('SELECT id FROM cotizaciones WHERE codigo = :codigo LIMIT 1');
            $stmtId->execute(['codigo' => $codigo]);
            $idCot = (int)$stmtId->fetchColumn();
            $this->assertGreaterThan(0, $idCot);

            $stmt = $db->prepare('SELECT descripcion_ensayo, descripcion_adicional FROM cotizacion_detalles WHERE id_cotizacion = :id LIMIT 1');
            $stmt->execute(['id' => $idCot]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertNotFalse($fila);
            $this->assertSame($ensayo, $fila['descripcion_ensayo']);
            $this->assertSame($largo, $fila['descripcion_adicional']);
            $this->assertGreaterThan(1000, strlen((string)$fila['descripcion_adicional']));
            $this->assertStringContainsString("\nC1SEGUNDA ", (string)$fila['descripcion_adicional']);
            $this->assertStringContainsString('<script>', (string)$fila['descripcion_adicional']);
        } finally {
            if ($idCot > 0) {
                $db->prepare('DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id')->execute(['id' => $idCot]);
                $db->prepare('DELETE FROM cotizaciones WHERE id = :id')->execute(['id' => $idCot]);
            }
        }
    }
}
