<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use PDO;

class CotizacionPoliticaDescuentoTest extends TestCase {

    private ?PDO $db = null;

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $this->db = Conexion::obtenerInstancia();
    }

    private function extraerTextoPdf(string $pdfBytes): string {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        $tempPdf = tempnam($cacheDir, 'cotiz_desc_pdf_');
        $tempScript = tempnam($cacheDir, 'extraer_desc_pdf_');

        file_put_contents($tempPdf, $pdfBytes);
        file_put_contents(
            $tempScript,
            "import sys\nimport io\nimport pymupdf\nsys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')\ndoc = pymupdf.open(sys.argv[1])\nfor page in doc:\n    print(page.get_text())\n"
        );

        $output = [];
        $exitCode = 1;
        @exec('python ' . escapeshellarg($tempScript) . ' ' . escapeshellarg($tempPdf), $output, $exitCode);
        @unlink($tempPdf);
        @unlink($tempScript);

        $this->assertSame(0, $exitCode, 'No fue posible extraer el texto del PDF para validar su contenido.');
        return preg_replace('/\s+/', ' ', implode(' ', $output));
    }

    private function crearDatosCotizacionPrueba(array $sobreescrituras = []): array {
        return array_merge([
            'id' => 8888,
            'codigo' => 'COT-2026-DESC-TEST',
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'tipo_moneda' => 1,
            'subtotal' => 10000.00,
            'descuento' => 800.00, // 8%
            'impuesto' => 1380.00,
            'total' => 10580.00,
            'cliente_nombre' => 'Constructora Nacional S.A.',
            'cliente_ruc' => 'J0310000073465',
            'nombre_proyecto' => 'Auditoría de Control de Descuentos',
            'direccion_proyecto' => 'Carretera a Masaya Km 12',
            'prioridad' => 'Normal',
            'version' => 1,
            'condicion_pago' => 'Crédito 30 días',
            'tiempo_entrega' => '5 días hábiles',
            'vigencia_oferta' => '30 días',
            'configuracion_notas' => json_encode([
                'digital_pdf' => 1,
                'no_movilizacion' => 1,
                'entrega_laboratorio' => 1,
                'politica_descuento' => 1,
                'descuento_autorizado_por' => 'Gerencia Administrativa'
            ]),
            'contactos' => "Lic. Carlos Fonseca\nTel: +505 8888-9999",
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
            'archivo_adjunto' => null
        ], $sobreescrituras);
    }

    private function crearDetallesPrueba(): array {
        return [
            [
                'descripcion_ensayo' => 'Compresión de Cilindros de Concreto',
                'ensayo_nombre' => 'Resistencia a la Compresión',
                'nombre_comercial' => 'Rompimiento C39',
                'norma_astm' => 'ASTM C39',
                'procedimiento' => 'ASTM C39',
                'unidad_medida' => 'Cilindro',
                'cantidad' => 10,
                'precio_unitario' => 1000.00,
                'subtotal' => 10000.00,
                'condiciones_muestra' => 'Muestras curadas 28 días',
                'descripcion_adicional' => 'Prueba con refrentado de azufre'
            ]
        ];
    }

    /**
     * Valida que generarCotizacionPDF() incluye la nota de Política de Descuento (Inciso 6.7 e)
     * indicando explícitamente el autorizador 'Gerencia Administrativa'.
     */
    public function testGenerarCotizacionPDFIncluyePoliticaDescuentoConGerenciaAdministrativa(): void {
        $cotizacion = $this->crearDatosCotizacionPrueba([
            'configuracion_notas' => json_encode([
                'digital_pdf' => 1,
                'politica_descuento' => 1,
                'descuento_autorizado_por' => 'Gerencia Administrativa'
            ])
        ]);
        $detalles = $this->crearDetallesPrueba();

        $pdfBytes = generarCotizacionPDF($cotizacion, $detalles);
        $this->assertNotEmpty($pdfBytes, 'El PDF generado no debe estar vacío.');

        $texto = $this->extraerTextoPdf($pdfBytes);
        $this->assertStringContainsString('Política de Descuento (Inciso 6.7 e)', $texto);
        $this->assertStringContainsString('Gerencia Administrativa', $texto);
    }

    /**
     * Valida que cuando se elige 'Otro' como autorizador, se imprima el área o cargo personalizado.
     */
    public function testGenerarCotizacionPDFSoportaAutorizadorPersonalizadoOtro(): void {
        $cotizacion = $this->crearDatosCotizacionPrueba([
            'configuracion_notas' => json_encode([
                'digital_pdf' => 1,
                'politica_descuento' => 1,
                'descuento_autorizado_por' => 'Otro',
                'descuento_autorizado_otro' => 'Dirección de Operaciones Especiales'
            ])
        ]);
        $detalles = $this->crearDetallesPrueba();

        $pdfBytes = generarCotizacionPDF($cotizacion, $detalles);
        $texto = $this->extraerTextoPdf($pdfBytes);

        $this->assertStringContainsString('Política de Descuento (Inciso 6.7 e)', $texto);
        $this->assertStringContainsString('Dirección de Operaciones Especiales', $texto);
    }

    /**
     * Valida que la inferencia automática basada en el porcentaje de descuento (política 6.7)
     * asigne correctamente:
     * - > 10% => Gerencia General
     * - 5% a 10% => Gerencia Administrativa
     * - <= 5% => Política Comercial (Automático)
     */
    public function testInferenciaAutomaticaSegunEscalaPolitica67(): void {
        $detalles = $this->crearDetallesPrueba();

        // Caso 1: Descuento 15% (> 10%) sin autorizador explícito
        $cot1 = $this->crearDatosCotizacionPrueba([
            'subtotal' => 10000.00,
            'descuento' => 1500.00,
            'configuracion_notas' => json_encode(['politica_descuento' => 1])
        ]);
        $pdf1 = generarCotizacionPDF($cot1, $detalles);
        $txt1 = $this->extraerTextoPdf($pdf1);
        $this->assertStringContainsString('Gerencia General', $txt1);

        // Caso 2: Descuento 7% (5% - 10%)
        $cot2 = $this->crearDatosCotizacionPrueba([
            'subtotal' => 10000.00,
            'descuento' => 700.00,
            'configuracion_notas' => json_encode(['politica_descuento' => 1])
        ]);
        $pdf2 = generarCotizacionPDF($cot2, $detalles);
        $txt2 = $this->extraerTextoPdf($pdf2);
        $this->assertStringContainsString('Gerencia Administrativa', $txt2);

        // Caso 3: Descuento 4% (<= 5%)
        $cot3 = $this->crearDatosCotizacionPrueba([
            'subtotal' => 10000.00,
            'descuento' => 400.00,
            'configuracion_notas' => json_encode(['politica_descuento' => 1])
        ]);
        $pdf3 = generarCotizacionPDF($cot3, $detalles);
        $txt3 = $this->extraerTextoPdf($pdf3);
        $this->assertStringContainsString('Política Comercial (Automático)', $txt3);
    }

    /**
     * Valida que generarCotizacionCompletaPDF() también incluye la nota y autorizador.
     */
    public function testGenerarCotizacionCompletaPDFIncluyePoliticaDescuento(): void {
        $cotizacion = $this->crearDatosCotizacionPrueba([
            'configuracion_notas' => json_encode([
                'politica_descuento' => 1,
                'descuento_autorizado_por' => 'Presidencia Ejecutiva'
            ])
        ]);
        $detalles = $this->crearDetallesPrueba();

        $pdfBytes = generarCotizacionCompletaPDF($cotizacion, $detalles);
        $texto = $this->extraerTextoPdf($pdfBytes);

        $this->assertStringContainsString('Política de Descuento (Inciso 6.7 e)', $texto);
        $this->assertStringContainsString('Presidencia Ejecutiva', $texto);
    }

    /**
     * Valida que al duplicar una cotización, la configuración de la nota y el autorizador
     * se conservan íntegramente en la nueva cotización clonada.
     */
    public function testDuplicarCotizacionConservaPoliticaDescuento(): void {
        $cli = $this->db->query("SELECT id FROM clientes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $usr = $this->db->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if (!$cli || !$usr) {
            $this->markTestSkipped('No hay clientes o usuarios disponibles para la prueba de clonación.');
        }

        $configEsperada = [
            'digital_pdf' => 1,
            'politica_descuento' => 1,
            'descuento_autorizado_por' => 'Gerencia General'
        ];

        // Insertar cotización base
        $stmt = $this->db->prepare("INSERT INTO cotizaciones (
            codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, tipo_moneda,
            id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, condicion_pago,
            subtotal, descuento, impuesto, total, estado, configuracion_notas
        ) VALUES (
            :codigo, :id_cliente, :id_cliente_factura, :id_cliente_informe, 'contado', 1,
            :id_usuario, 'Contacto Prueba', 'Proyecto Duplicación Descuento', 'León', 'Contado',
            5000.00, 600.00, 660.00, 5060.00, 'Borrador', :configuracion_notas
        )");

        $codigoBase = 'COT-TEST-' . uniqid();
        $stmt->execute([
            'codigo' => $codigoBase,
            'id_cliente' => $cli['id'],
            'id_cliente_factura' => $cli['id'],
            'id_cliente_informe' => $cli['id'],
            'id_usuario' => $usr['id'],
            'configuracion_notas' => json_encode($configEsperada)
        ]);
        $idOrigen = (int)$this->db->lastInsertId();

        $modelo = new CotizacionModelo();
        $nuevoId = $modelo->duplicarCotizacion($idOrigen, (int)$usr['id']);

        $this->assertIsInt($nuevoId);
        $this->assertGreaterThan(0, $nuevoId);

        $clon = $modelo->obtenerPorId($nuevoId);
        $this->assertNotEmpty($clon['configuracion_notas']);

        $configClon = json_decode($clon['configuracion_notas'], true);
        $this->assertSame(1, (int)$configClon['politica_descuento']);
        $this->assertSame('Gerencia General', $configClon['descuento_autorizado_por']);

        // Limpieza de datos de prueba
        $this->db->exec("DELETE FROM cotizaciones WHERE id IN ($idOrigen, $nuevoId)");
    }
}
