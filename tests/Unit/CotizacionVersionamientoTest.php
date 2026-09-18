<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;

class CotizacionVersionamientoTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_id'] = 1;
        $_SESSION['csrf_token'] = 'token-test';
    }

    public function testRenderizadoDesgloseVersionesEnDetalle(): void {
        $cotizacion = [
            'id' => 99,
            'codigo' => 'COT-2026-099',
            'version' => 2,
            'estado' => 'Enviada al Cliente',
            'fecha_creacion' => '2026-09-15 08:00:00',
            'fecha_actualizacion' => '2026-09-18 09:30:00',
            'tipo_moneda' => 1,
            'cliente_nombre' => 'Constructora Continental SA',
            'cliente_ruc' => 'J0310000000000',
            'atencion_a' => 'Ing. Roberto Silva',
            'nombre_proyecto' => 'Puente Desnivel Managua',
            'direccion_proyecto' => 'Managua, Nicaragua',
            'condicion_pago' => '50% anticipo, 50% contra entrega',
            'tiempo_entrega' => '5 días hábiles',
            'vigencia_oferta' => '15 días',
            'subtotal' => 20000.00,
            'descuento' => 0.00,
            'impuesto' => 3000.00,
            'total' => 23000.00,
            'creador_nombre' => 'Asesor Comercial CYCSA',
            'id_usuario_creador' => 1
        ];

        $detalles = [
            [
                'id' => 1,
                'id_producto' => 10,
                'descripcion_ensayo' => 'Compresión de Cilindros de Concreto (3 Cilindros)',
                'codigo_servicio' => 'CYCSA-CONC-01',
                'norma_astm' => 'ASTM C39',
                'cantidad' => 10,
                'precio_unitario' => 2000.00,
                'subtotal' => 20000.00
            ]
        ];

        $versiones = [
            [
                'id' => 501,
                'id_cotizacion' => 99,
                'version' => 1,
                'fecha_creacion' => '2026-09-15 08:00:00',
                'motivo_cambio' => 'Devuelta por cliente: Ajustar cantidad de cilindros a 10',
                'datos_json' => json_encode([
                    'total' => 11500.00,
                    'subtotal' => 10000.00,
                    'impuesto' => 1500.00,
                    'atencion_a' => 'Ing. Roberto Silva',
                    'condicion_pago' => 'Contado',
                    'tiempo_entrega' => '3 días',
                    'vigencia_oferta' => '15 días',
                    'detalles' => [
                        [
                            'descripcion_ensayo' => 'Compresión de Cilindros de Concreto (Versión Original 5 Cilindros)',
                            'codigo_servicio' => 'CYCSA-CONC-01',
                            'cantidad' => 5,
                            'precio_unitario' => 2000.00,
                            'subtotal' => 10000.00
                        ]
                    ]
                ])
            ]
        ];

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/detalle.php';
        $html = ob_get_clean();

        $this->assertNotEmpty($html);
        // Debe mostrar el desglose de versiones
        $this->assertStringContainsString('Control y Desglose de Versiones de la Cotización', $html);
        // Debe identificar la Versión Actual v2
        $this->assertStringContainsString('v2', $html);
        $this->assertStringContainsString('ACTUAL / VIGENTE', $html);
        $this->assertStringContainsString('Imprimir Versión Actual (v2)', $html);
        $this->assertStringContainsString('version=2', $html);
        // Debe desglosar la Versión Histórica v1
        $this->assertStringContainsString('v1', $html);
        $this->assertStringContainsString('HISTÓRICA', $html);
        $this->assertStringContainsString('Imprimir Versión 1', $html);
        $this->assertStringContainsString('version=1', $html);
        $this->assertStringContainsString('Ajustar cantidad de cilindros a 10', $html);
        // Debe mostrar el desglose de ítems de la v1
        $this->assertStringContainsString('Compresión de Cilindros de Concreto (Versión Original 5 Cilindros)', $html);
    }

    public function testGeneracionPdfVersionHistorica(): void {
        $cotizacionBase = [
            'id' => 88,
            'codigo' => 'COT-2026-088',
            'version' => 2,
            'tipo_moneda' => 1,
            'cliente_nombre' => 'Constructora Alfa',
            'cliente_ruc' => 'J0310000000001',
            'atencion_a' => 'Ing. Juan Pérez',
            'nombre_proyecto' => 'Edificio Horizonte',
            'direccion_proyecto' => 'Carretera Masaya',
            'subtotal' => 25000.00,
            'descuento' => 0.00,
            'impuesto' => 3750.00,
            'total' => 28750.00,
            'fecha_creacion' => '2026-09-18'
        ];

        // Simulamos la reconstrucción de la versión 1 histórica para imprimir
        $snapshotV1 = [
            'version' => 1,
            'subtotal' => 15000.00,
            'descuento' => 0.00,
            'impuesto' => 2250.00,
            'total' => 17250.00,
            'detalles' => [
                [
                    'descripcion_ensayo' => 'Ensayo de Suelos Proctor Estándar V1',
                    'cantidad' => 3,
                    'precio_unitario' => 5000.00,
                    'subtotal' => 15000.00
                ]
            ]
        ];

        $cotizacionV1 = array_merge($cotizacionBase, $snapshotV1);
        $detallesV1 = $snapshotV1['detalles'];

        $pdfBytes = generarCotizacionPDF($cotizacionV1, $detallesV1);
        $this->assertNotEmpty($pdfBytes);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }

    public function testCotizacionSinVersionesHistoricasMuestraVersionOriginalInicial(): void {
        $cotizacion = [
            'id' => 77,
            'codigo' => 'COT-2026-077',
            'version' => 1,
            'estado' => 'Borrador',
            'fecha_creacion' => '2026-09-18 10:00:00',
            'fecha_actualizacion' => '2026-09-18 10:00:00',
            'tipo_moneda' => 1,
            'cliente_nombre' => 'Cliente Inicial SA',
            'cliente_ruc' => 'J0310000000002',
            'atencion_a' => 'Lic. Ana Morales',
            'nombre_proyecto' => 'Planta Industrial',
            'direccion_proyecto' => 'León',
            'condicion_pago' => 'Contado',
            'tiempo_entrega' => '2 días',
            'vigencia_oferta' => '10 días',
            'subtotal' => 5000.00,
            'descuento' => 0.00,
            'impuesto' => 750.00,
            'total' => 5750.00,
            'creador_nombre' => 'Asesor Comercial',
            'id_usuario_creador' => 1
        ];

        $detalles = [
            [
                'id' => 1,
                'id_producto' => 5,
                'descripcion_ensayo' => 'Densidad de Campo',
                'cantidad' => 2,
                'precio_unitario' => 2500.00,
                'subtotal' => 5000.00
            ]
        ];

        $versiones = []; // Sin versiones previas

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Cotizaciones/Vistas/detalle.php';
        $html = ob_get_clean();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Control y Desglose de Versiones de la Cotización', $html);
        $this->assertStringContainsString('Imprimir Versión Actual (v1)', $html);
        $this->assertStringContainsString('versión original inicial', $html);
    }

    public function testActualizarCotizacionObservadaCreaNuevaVersionYArchivaAnterior(): void {
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $db->beginTransaction();

        try {
            $modelo = new CotizacionModelo();

            // 1. Obtener cotización existente para simular observación
            $cotOriginal = $modelo->obtenerPorId(5);
            $this->assertNotNull($cotOriginal);

            $idCot = 5;
            $db->prepare("UPDATE cotizaciones SET estado = 'Observada', version = 1, motivo_observacion = 'Corregir precios de ensayo' WHERE id = :id")
               ->execute(['id' => $idCot]);

            $cabecera = [
                'id_cliente' => $cotOriginal['id_cliente'],
                'tipo_moneda' => $cotOriginal['tipo_moneda'],
                'atencion_a' => $cotOriginal['atencion_a'],
                'nombre_proyecto' => $cotOriginal['nombre_proyecto'],
                'direccion_proyecto' => $cotOriginal['direccion_proyecto'],
                'condicion_pago' => $cotOriginal['condicion_pago'],
                'tiempo_entrega' => $cotOriginal['tiempo_entrega'],
                'vigencia_oferta' => $cotOriginal['vigencia_oferta'],
                'configuracion_notas' => $cotOriginal['configuracion_notas'],
                'contactos' => $cotOriginal['contactos'],
                'incluir_anexo_tecnico' => 0,
                'anexo_tecnico' => null,
                'archivo_adjunto' => null,
                'subtotal' => 1500.00,
                'descuento' => 0.00,
                'exonerado' => 0,
                'exoneracion_no' => null,
                'impuesto' => 225.00,
                'total' => 1725.00,
                'fecha_entrega' => null,
                'fecha_seguimiento' => null
            ];

            $detalles = [
                [
                    'id_producto' => 29,
                    'descripcion' => 'Densidad y Humedad In Situ Corregida',
                    'condiciones_muestra' => 'Terreno seco',
                    'procedimiento' => 'CYCSA-PE-25',
                    'unidad_medida' => 'Unidad',
                    'codigo_servicio' => 'CYCSA-RT-FM-22 B',
                    'norma_astm' => 'ASTM D6938-23',
                    'formato_reporte' => 'CYCSA-RT-FM-22 B',
                    'observaciones' => null,
                    'descripcion_adicional' => null,
                    'cantidad' => 1,
                    'precio' => 1500.00,
                    'subtotal' => 1500.00
                ]
            ];

            // 2. Ejecutar actualización
            $res = $modelo->actualizarCotizacionCompleta($idCot, $cabecera, $detalles);
            $this->assertTrue($res, 'actualizarCotizacionCompleta debe retornar true sin pantalla blanca');

            // 3. Verificar cotización activa
            $cotActualizada = $modelo->obtenerPorId($idCot);
            $this->assertSame('En Revision', $cotActualizada['estado']);
            $this->assertEquals(2, $cotActualizada['version']);
            $this->assertNull($cotActualizada['motivo_observacion']);
            $this->assertEquals(1725.00, (float)$cotActualizada['total']);

            // 4. Verificar versión histórica archivada
            $versionHist = $modelo->obtenerVersionHistorica($idCot, 1);
            $this->assertNotNull($versionHist);
            $this->assertStringContainsString('Observada por gerencia', $versionHist['motivo_cambio']);
        } finally {
            $db->rollBack();
        }
    }
}
