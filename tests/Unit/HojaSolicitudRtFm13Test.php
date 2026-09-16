<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HojaSolicitudRtFm13Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
    }

    public function testGenerarHojaSolicitudPdfValidoUnaPagina(): void {
        $hoja = [
            'numero_registro' => '23896',
            'codigo_documento' => 'CYCSA-RT-FM-13',
            'fecha_hora_llegada_laboratorio' => '2026-07-22 10:45:00',
            'nombre_empresa_o_cliente' => 'DGuerrero Ingenieros SA',
            'direccion_proyecto' => 'Plaza el sol, 200 m arriba Managua-Nic.',
            'telefono' => '2278-0769',
            'correo_electronico' => 'bosorio@dguerreroings.com',
            'nombre_persona_entrega_muestra' => 'Alden Altamirano',
            'naturaleza_muestra' => 'Suelo',
            'procedencia_punto_muestreo' => 'PROYECTO: OBRA DE RED COLECTORAS DE ALCANTARILLADO SANITARIO DE LA CIUDAD DE LEÓN',
            'nombre_persona_toma_muestra' => 'Alden Altamirano',
            'fecha_hora_toma_muestra' => '2026-07-24 7:00 am-5:00pm',
            'muestras_json' => json_encode([
                [
                    'nombre_muestra' => 'Compactación de Suelo',
                    'descripcion' => '20480',
                    'info_importante' => 'MS-11116,11117-26'
                ]
            ]),
            'req_resistencia_concreto' => 0,
            'req_resistencia_adoquin' => 0,
            'req_resistencia_bloques' => 0,
            'req_otros_concreto' => '',
            'req_granulometria' => 0,
            'req_limites_atterberg' => 0,
            'req_humedad' => 1,
            'req_resistencia_corte' => 0,
            'req_clasificacion_sucs_hr' => 0,
            'req_proctor_sm' => 0,
            'req_infiltracion' => 0,
            'req_cbr' => 0,
            'req_densidad' => 1,
            'req_otros_suelo' => '',
            'req_otros_materiales' => 0,
            'descripcion_otros_analisis' => 'No Aplica',
            'analisis_adicionales' => 'No Aplica',
            'observaciones' => 'NINGUNA',
            'nombre_recibe_cycsa' => 'Paola Cristina Alvarado Rivas',
            'firma_recibe_cycsa' => 1,
            'firma_cliente' => 1
        ];

        $os = [
            'id' => 4,
            'codigo_os' => 'OS-2026-0004'
        ];

        $pdfOutput = generarHojaSolicitudPDF($hoja, $os);

        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF-', $pdfOutput);

        // Validar conteo de páginas mediante parser / regex de objetos /Type /Page
        $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $pdfOutput, $matches);
        $this->assertEquals(1, $pageCount, 'El documento CYCSA-RT-FM-13 debe generarse exactamente en 1 página.');
    }
}
