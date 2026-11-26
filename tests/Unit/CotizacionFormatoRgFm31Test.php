<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio;
use PHPUnit\Framework\TestCase;

class CotizacionFormatoRgFm31Test extends TestCase
{
    private function cotizacionMuestra(array $extra = []): array
    {
        return array_merge([
            'id' => 404,
            'codigo' => 'COT-2026-0404',
            'version' => 2,
            'estado' => 'Borrador',
            'fecha_creacion' => '2026-11-26 10:30:00',
            'cliente_nombre' => 'Constructora Horizonte S.A.',
            'cliente_ruc' => 'J0310000999888',
            'direccion_cliente' => 'Km 6.5 Carretera a Masaya, Managua',
            'atencion_a' => 'Ing. María López',
            'nombre_proyecto' => 'Ampliación Nave Industrial Norte',
            'direccion_proyecto' => 'Zona Franca Las Mercedes, Managua',
            'cliente_email' => 'mlopez@horizonte.com.ni',
            'cliente_tel' => '+505 8811-2233',
            'creador_nombre' => 'Ing. Carlos Mendoza',
            'tipo_moneda' => 1,
            'subtotal' => 4500.00,
            'descuento' => 225.00,
            'exonerado' => 0,
            'exoneracion_no' => null,
            'impuesto' => 641.25,
            'total' => 4916.25,
            'condicion_pago' => '50% anticipo, 50% contra entrega',
            'tiempo_entrega' => '5 días hábiles',
            'vigencia_oferta' => '30 días calendario',
            'configuracion_notas' => json_encode([
                'digital_pdf' => 1,
                'no_movilizacion' => 1,
                'entrega_laboratorio' => 1,
                'politica_descuento' => 1,
                'descuento_autorizado_por' => 'Gerencia Administrativa',
            ]),
            'contactos' => json_encode([
                [
                    'nombre' => 'Ing. María López',
                    'telefono' => '+505 8811-2233',
                    'correo' => 'mlopez@horizonte.com.ni',
                ],
                [
                    'nombre' => 'Arq. José Rivera',
                    'telefono' => '+505 8844-5566',
                    'correo' => 'jrivera@horizonte.com.ni',
                ],
            ]),
        ], $extra);
    }

    private function detallesMuestra(): array
    {
        return [
            [
                'descripcion_ensayo' => 'Compresión de Cilindros de Concreto',
                'descripcion_adicional' => "Curado estándar 28 días\nIncluye refrentado",
                'condiciones_muestra' => 'Cilindros 4"x8" debidamente identificados',
                'procedimiento' => 'ASTM C39',
                'norma_astm' => 'ASTM C39/C39M',
                'unidad_medida' => 'Cilindro',
                'cantidad' => 15,
                'precio_unitario' => 300.00,
                'subtotal' => 4500.00,
                'codigo_servicio' => 'CYCSA-PE-07',
            ],
        ];
    }

    public function testRenderCuerpoPantallaYPdfCompartenFormatoOficialRgFm31(): void
    {
        $cot = $this->cotizacionMuestra();
        $dets = $this->detallesMuestra();

        $htmlPantalla = DocumentoCotizacionRgFm31Servicio::renderCuerpo($cot, $dets, false);
        $htmlPdf = DocumentoCotizacionRgFm31Servicio::renderCuerpo($cot, $dets, true);

        foreach (['pantalla' => $htmlPantalla, 'pdf' => $htmlPdf] as $modo => $html) {
            $this->assertStringContainsString('CYCSA-RG-FM-31 V2R1', $html, "Falta código en {$modo}");
            $this->assertStringContainsString('CONSULTORÍA Y CONSTRUCCIÓN S.A', $html, "Falta razón social en {$modo}");
            $this->assertStringContainsString('COTIZACIÓN DE SERVICIOS', $html, "Falta título en {$modo}");
            $this->assertStringContainsString('DATOS DEL CLIENTE', $html, "Falta sección cliente en {$modo}");
            $this->assertStringContainsString('Km 6.5 Carretera a Masaya, Managua', $html, "Falta dirección del cliente en {$modo}");
            $this->assertStringContainsString('Zona Franca Las Mercedes, Managua', $html, "Falta ubicación del proyecto en {$modo}");
            $this->assertStringContainsString('COT-2026-0404 (v2)', $html, "Falta código/versión en {$modo}");
            $this->assertStringContainsString('26/11/2026', $html, "Falta fecha dd/mm/YYYY en {$modo}");
            $this->assertStringContainsString('DETALLE DE SERVICIOS COTIZADOS', $html, "Falta tabla detalle en {$modo}");
            $this->assertStringContainsString('Método de ensayo', $html, "Falta columna Método de ensayo en {$modo}");
            $this->assertStringContainsString('ASTM C39', $html, "Falta procedimiento en {$modo}");
            $this->assertStringContainsString('CONDICIONES COMERCIALES', $html, "Falta condiciones comerciales en {$modo}");
            $this->assertStringContainsString('Tiempo de entrega', $html, "Falta tiempo de entrega en {$modo}");
            $this->assertStringContainsString('Vigencia de la oferta', $html, "Falta vigencia de la oferta en {$modo}");
            $this->assertStringContainsString('NOTAS Y CONDICIONES DE LA COTIZACIÓN', $html, "Falta notas en {$modo}");
            $this->assertStringContainsString('CONTACTOS DEL CLIENTE PARA SEGUIMIENTO', $html, "Falta contactos en {$modo}");
            $this->assertStringContainsString('Arq. José Rivera', $html, "Falta segundo contacto en {$modo}");
            $this->assertStringContainsString('BANPRO:', $html, "Falta BANPRO en {$modo}");
            $this->assertStringContainsString('10010207085164', $html, "Falta cuenta BANPRO en {$modo}");
            $this->assertStringContainsString('357-02445-4', $html, "Falta cuenta BAC en {$modo}");
            $this->assertStringContainsString('550-2000-11', $html, "Falta cuenta LAFISE en {$modo}");
            $this->assertStringContainsString('J0310000073465', $html, "Falta RUC en {$modo}");
            $this->assertStringContainsString('Lic. MTI No. 6693', $html, "Falta licencia MTI en {$modo}");
            $this->assertStringContainsString('www.cycsanic.com', $html, "Falta web institucional en {$modo}");
        }

        // En modo pantalla usa URL pública del logo; en modo PDF incrusta base64
        $this->assertStringContainsString('/Cycsa/publico/img/logo.png', $htmlPantalla);
        $this->assertStringContainsString('data:image/png;base64,', $htmlPdf);

        // Verificar alias renderHtmlCuerpo y estilos
        $this->assertSame($htmlPdf, DocumentoCotizacionRgFm31Servicio::renderHtmlCuerpo($cot, $dets, true));
        $css = DocumentoCotizacionRgFm31Servicio::renderEstilos(true);
        $this->assertStringContainsString('.rgfm31-header-table', $css);
        $this->assertStringContainsString('.rgfm31-banner', $css);
    }

    public function testNormalizarContactosSoportaJsonTextoPlanoYRespaldo(): void
    {
        // 1. JSON estructurado
        $jsonRes = DocumentoCotizacionRgFm31Servicio::normalizarContactos([
            'contactos' => json_encode([
                ['nombre' => 'Ing. Ana Ruiz', 'telefono' => '8800-1122', 'correo' => 'ana@demo.com'],
            ]),
        ]);
        $this->assertCount(1, $jsonRes);
        $this->assertSame('Ing. Ana Ruiz', $jsonRes[0]['nombre']);
        $this->assertSame('8800-1122', $jsonRes[0]['telefono']);
        $this->assertSame('ana@demo.com', $jsonRes[0]['correo']);

        // 2. Texto plano heredado multilínea
        $legacyRes = DocumentoCotizacionRgFm31Servicio::normalizarContactos([
            'contactos' => "Lic. Carlos Fonseca\nTel: +505 8888-9999\nCorreo: cfonseca@obra.ni",
        ]);
        $this->assertCount(1, $legacyRes);
        $this->assertSame('Lic. Carlos Fonseca', $legacyRes[0]['nombre']);
        $this->assertSame('+505 8888-9999', $legacyRes[0]['telefono']);
        $this->assertSame('cfonseca@obra.ni', $legacyRes[0]['correo']);

        // 3. Texto plano delimitado por barra vertical
        $pipeRes = DocumentoCotizacionRgFm31Servicio::normalizarContactos([
            'contactos' => 'Ing. Luis Mora | 8777-6655 | lmora@empresa.com',
        ]);
        $this->assertCount(1, $pipeRes);
        $this->assertSame('Ing. Luis Mora', $pipeRes[0]['nombre']);
        $this->assertSame('8777-6655', $pipeRes[0]['telefono']);
        $this->assertSame('lmora@empresa.com', $pipeRes[0]['correo']);

        // 4. Respaldo automático cuando está vacío
        $fallbackRes = DocumentoCotizacionRgFm31Servicio::normalizarContactos([
            'contactos' => '',
            'atencion_a' => 'Ing. Roberto Díaz',
            'cliente_tel' => '2255-4433',
            'cliente_email' => 'rdiaz@cliente.com',
        ]);
        $this->assertCount(1, $fallbackRes);
        $this->assertSame('Ing. Roberto Díaz', $fallbackRes[0]['nombre']);
        $this->assertSame('2255-4433', $fallbackRes[0]['telefono']);
        $this->assertSame('rdiaz@cliente.com', $fallbackRes[0]['correo']);
    }

    public function testResolverNotasAplicaPorDefectoYPoliticaDescuento(): void
    {
        $notasDefault = DocumentoCotizacionRgFm31Servicio::resolverNotas([
            'configuracion_notas' => null,
            'descuento' => 0,
        ]);
        $this->assertCount(3, $notasDefault);
        $this->assertStringContainsString('.PDF', $notasDefault[0]);
        $this->assertStringContainsString('movilización', $notasDefault[1]);
        $this->assertStringContainsString('Km 83.5', $notasDefault[2]);

        $notasDesc = DocumentoCotizacionRgFm31Servicio::resolverNotas([
            'subtotal' => 10000,
            'descuento' => 1200,
            'configuracion_notas' => json_encode(['politica_descuento' => 1]),
        ]);
        $unidas = implode("\n", $notasDesc);
        $this->assertStringContainsString('Política de Descuento (Inciso 6.7 e)', $unidas);
        $this->assertStringContainsString('Gerencia General', $unidas);
    }

    public function testVistasIncluyenCamposRgFm31YServicioUnificado(): void
    {
        $root = dirname(__DIR__, 2);
        $crear = (string)file_get_contents($root . '/app/Modulos/Cotizaciones/Vistas/crear.php');
        $editar = (string)file_get_contents($root . '/app/Modulos/Cotizaciones/Vistas/editar.php');
        $detalle = (string)file_get_contents($root . '/app/Modulos/Cotizaciones/Vistas/detalle.php');

        foreach (['crear.php' => $crear, 'editar.php' => $editar] as $nombre => $src) {
            $this->assertStringContainsString('name="direccion_cliente"', $src, "{$nombre} debe incluir direccion_cliente");
            $this->assertStringContainsString('name="direccion_proyecto"', $src, "{$nombre} debe incluir direccion_proyecto");
            $this->assertStringContainsString('name="tiempo_entrega"', $src, "{$nombre} debe incluir tiempo_entrega");
            $this->assertStringContainsString('name="vigencia_oferta"', $src, "{$nombre} debe incluir vigencia_oferta");
            $this->assertStringContainsString('name="contacto_nombre[]"', $src, "{$nombre} debe incluir contacto_nombre[]");
            $this->assertStringContainsString('name="contacto_telefono[]"', $src, "{$nombre} debe incluir contacto_telefono[]");
            $this->assertStringContainsString('name="contacto_correo[]"', $src, "{$nombre} debe incluir contacto_correo[]");
        }

        $this->assertStringContainsString('DocumentoCotizacionRgFm31Servicio::renderEstilos', $detalle);
        $this->assertStringContainsString('DocumentoCotizacionRgFm31Servicio::renderCuerpo', $detalle);
    }
}
