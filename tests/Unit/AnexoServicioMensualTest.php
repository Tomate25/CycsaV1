<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio;
use PHPUnit\Framework\TestCase;

class AnexoServicioMensualTest extends TestCase
{
    public function testPlantillaPorDefectoContieneLas6Secciones10CondicionesY20EnsayosOficiales(): void
    {
        $def = AnexoServicioMensualServicio::plantillaPorDefecto();

        $this->assertSame(AnexoServicioMensualServicio::TIPO_ANEXO, $def['tipo']);
        $this->assertStringContainsString('ANEXO TÉCNICO', $def['titulo']);
        $this->assertStringContainsString('laboratorio in situ (30 días)', $def['descripcion_servicio']);
        $this->assertCount(10, $def['condiciones']);
        $this->assertCount(20, $def['ensayos']);
        $this->assertTrue($def['incluir_seguridad_radiologica']);
        $this->assertCount(4, $def['seguridad_radiologica_puntos']);

        $ensayosNombres = array_column($def['ensayos'], 'ensayo');
        $this->assertContains('Humedad en agregados, ASTM C566-25', $ensayosNombres);
        $this->assertContains('Resistencia a Compresión del Concreto – ASTM C39/C39M-26', $ensayosNombres);
        $this->assertContains('Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23', $ensayosNombres);
        $this->assertContains('Resistencia a Compresión de Suelo-Cemento – ASTM D1633-17', $ensayosNombres);
    }

    public function testConstruirDesdePostCodificarDecodificarYRenderHtml(): void
    {
        $post = [
            'anexo_mensual' => [
                'titulo' => 'CYCSA  |  ANEXO TÉCNICO – ALCANCE MENSUAL',
                'descripcion_servicio' => 'Servicio mensual personalizado para Proyecto Norte.',
                'cond_label' => ['Personal:', 'Jornada:'],
                'cond_valor' => ['Dos técnicos tipo B', '63 horas semanales'],
                'ensayos_intro' => 'Lista de ensayos seleccionados:',
                'ensayo_incluido' => [0 => '1', 1 => '0', 2 => '1'],
                'ensayo_categoria' => [0 => 'Concreto/Cilindro', 1 => 'Agregados', 2 => 'Suelo'],
                'ensayo_nombre' => [0 => 'ASTM C39', 1 => 'Ensayo excluido', 2 => 'ASTM D6938-23'],
                'recursos_entrega' => "Informe digital PDF.\nEquipos CYCSA.",
                'responsabilidades_cliente' => 'El cliente suministra vehículo y vigilancia 24/7.',
                'incluir_seguridad_radiologica' => '1',
                'seguridad_radiologica_intro' => 'Densímetro nuclear condiciones:',
                'seguridad_radiologica_puntos_texto' => "1. Punto uno\n2. Punto dos",
                'seguridad_radiologica_cierre' => 'Uso exclusivo en obra.',
            ],
        ];

        $anexo = AnexoServicioMensualServicio::construirDesdePost($post);
        $json = AnexoServicioMensualServicio::codificar($anexo);
        $this->assertTrue(AnexoServicioMensualServicio::esAnexoMensualJson($json));

        $dec = AnexoServicioMensualServicio::decodificar($json);
        $this->assertSame('Servicio mensual personalizado para Proyecto Norte.', $dec['descripcion_servicio']);

        $html = AnexoServicioMensualServicio::renderDesdeCotizacion([
            'es_mensual' => 1,
            'incluir_anexo_tecnico' => 1,
            'anexo_tecnico' => $json,
        ], false);

        $this->assertStringContainsString('ANEXO TÉCNICO – ALCANCE MENSUAL', $html);
        $this->assertStringContainsString('Dos técnicos tipo B', $html);
        $this->assertStringContainsString('ASTM C39', $html);
        $this->assertStringContainsString('ASTM D6938-23', $html);
        $this->assertStringNotContainsString('Ensayo excluido', $html);
        $this->assertStringContainsString('6. Condiciones de seguridad radiológica.', $html);
    }

    public function testRenderDesdeCotizacionSoportaAnexoLegadoYOcultaSiPuntualSinAnexo(): void
    {
        $htmlLegado = AnexoServicioMensualServicio::renderDesdeCotizacion([
            'es_mensual' => 0,
            'incluir_anexo_tecnico' => 1,
            'anexo_tecnico' => '<p>Cláusula especial legada</p>',
        ], false);
        $this->assertSame('<p>Cláusula especial legada</p>', $htmlLegado);

        $htmlVacio = AnexoServicioMensualServicio::renderDesdeCotizacion([
            'es_mensual' => 0,
            'incluir_anexo_tecnico' => 0,
            'anexo_tecnico' => null,
        ], false);
        $this->assertSame('', $htmlVacio);
    }

    public function testGenerarCotizacionPdfConAnexoServicioMensualProducePdfValido(): void
    {
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';

        $jsonAnexo = AnexoServicioMensualServicio::codificar(AnexoServicioMensualServicio::plantillaPorDefecto());
        $cot = [
            'codigo' => 'COT-2026-MENS',
            'version' => 1,
            'fecha_creacion' => '2026-11-27 10:00:00',
            'cliente_nombre' => 'Constructora Mensual S.A.',
            'cliente_ruc' => 'J0310000000001',
            'direccion_cliente' => 'Managua',
            'atencion_a' => 'Ing. Cliente',
            'nombre_proyecto' => 'Carretera Norte',
            'direccion_proyecto' => 'Km 50',
            'condicion_pago' => 'Crédito 30 días',
            'tiempo_entrega' => 'Cortes mensuales',
            'vigencia_oferta' => '15 días calendario',
            'tipo_moneda' => 1,
            'es_mensual' => 1,
            'duracion_meses' => 6,
            'periodicidad' => 'Mensual',
            'fecha_inicio_periodo' => '2026-12-01',
            'fecha_fin_periodo' => '2027-06-01',
            'incluir_anexo_tecnico' => 1,
            'anexo_tecnico' => $jsonAnexo,
            'subtotal' => 150000,
            'descuento' => 0,
            'impuesto' => 22500,
            'total' => 172500,
        ];
        $detalles = [[
            'descripcion_ensayo' => 'Servicio mensual de laboratorio in situ',
            'condiciones_muestra' => 'Según Anexo Técnico – Alcance Mensual',
            'norma_astm' => 'ASTM / AASHTO',
            'unidad_medida' => 'Mes',
            'cantidad' => 1,
            'precio_unitario' => 150000,
            'subtotal' => 150000,
        ]];

        $pdfSinAnexo = generarCotizacionPDF(array_merge($cot, ['incluir_anexo_tecnico' => 0, 'anexo_tecnico' => null]), $detalles);
        $pdfConAnexo = generarCotizacionPDF($cot, $detalles);

        $this->assertStringStartsWith('%PDF-', $pdfSinAnexo);
        $this->assertStringStartsWith('%PDF-', $pdfConAnexo);
        $this->assertGreaterThan(strlen($pdfSinAnexo), strlen($pdfConAnexo), 'El PDF con Anexo Técnico Mensual debe incluir la página adicional del anexo.');
    }
}
