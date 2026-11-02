<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SprintEReportesUXVerifyTest extends TestCase {

    /**
     * Requisito #7: Encabezados normativos ASTM / AASHTO en metadatos y matriz_print
     */
    public function testRequisito7EncabezadosNormativosAstmAashto(): void {
        // Caso A: con norma ASTM explícita en detalle
        $meta1 = resolverMetadatosEnsayo([
            'norma_astm' => 'ASTM C39 / C39M - 21',
            'descripcion_ensayo' => 'Resistencia a la compresión de cilindros de concreto'
        ]);
        $this->assertSame('ASTM C39 / C39M - 21', $meta1['norma_tecnica']);
        $this->assertSame('ASTM C39 / C39M - 21', $meta1['metodo_muestreo']);

        // Caso B: con norma en schemaInfo
        $meta2 = resolverMetadatosEnsayo([], [
            'norma' => 'AASHTO T 310 / ASTM D6938',
            'metodo_muestreo' => 'AASHTO T 310'
        ]);
        $this->assertSame('AASHTO T 310 / ASTM D6938', $meta2['norma_tecnica']);
        $this->assertSame('AASHTO T 310', $meta2['metodo_muestreo']);

        // Caso C: verificar presencia en la vista matriz_print.php
        $printContent = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString('Norma técnica: <?= htmlspecialchars($normaOficial) ?>', $printContent);
    }

    /**
     * Requisito #8: Nota 1 oficial con metadatos estructurados Troxler
     */
    public function testRequisito8Nota1EstructuradaTroxler(): void {
        $meta = resolverMetadatosEnsayo([], [], [
            'equipo_marca' => 'TROXLER',
            'equipo_modelo' => '3440',
            'equipo_serie' => '62677',
            'equipo_codigo' => 'Eq-6555'
        ]);

        $this->assertSame('Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3440, N/S : 62677 Eq-6555', $meta['nota_equipo_densimetro']);
        $this->assertSame('TROXLER', $meta['equipo_marca']);
        $this->assertSame('3440', $meta['equipo_modelo']);
        $this->assertSame('62677', $meta['equipo_serie']);
        $this->assertSame('Eq-6555', $meta['equipo_codigo']);
    }

    /**
     * Requisito #8 (Aditivo): Campo dedicado 'muestra_tomada_con_equipo' directo
     */
    public function testCampoDedicadoMuestraTomadaConEquipoDirecto(): void {
        // Texto libre que no empieza con Nota 1
        $metaCustom = resolverMetadatosEnsayo([], [], [
            'muestra_tomada_con_equipo' => 'HUMBOLDT, Modelo: HS-5001EZ, N/S: 4589 Eq-1020'
        ]);
        $this->assertSame('Nota 1: Muestra tomada con equipo HUMBOLDT, Modelo: HS-5001EZ, N/S: 4589 Eq-1020', $metaCustom['nota_equipo_densimetro']);
        $this->assertSame('HUMBOLDT, Modelo: HS-5001EZ, N/S: 4589 Eq-1020', $metaCustom['muestra_tomada_con_equipo']);

        // Texto que ya contiene "Nota 1: Muestra tomada con equipo"
        $metaCompleto = resolverMetadatosEnsayo([], [], [
            'muestra_tomada_con_equipo' => 'Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3430, N/S : 55214 Eq-4410'
        ]);
        $this->assertSame('Nota 1: Muestra tomada con equipo TROXLER, Modelo: 3430, N/S : 55214 Eq-4410', $metaCompleto['nota_equipo_densimetro']);
    }

    /**
     * Requisito #8: Formulario de captura_matriz contiene inputs dedicados de equipo
     */
    public function testCapturaMatrizContieneApartadoDedicadoEquipo(): void {
        $captura = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $this->assertStringContainsString('id="meta_equipo_marca"', $captura);
        $this->assertStringContainsString('id="meta_equipo_modelo"', $captura);
        $this->assertStringContainsString('id="meta_equipo_serie"', $captura);
        $this->assertStringContainsString('id="meta_equipo_codigo"', $captura);
        $this->assertStringContainsString('Muestra tomada con equipo (Nota 1 Oficial de Ensayo / Densímetro Nuclear)', $captura);
    }

    /**
     * Requisito #8: Matriz print inyecta Nota 1 en compactación / RT-FM-22 B
     */
    public function testMatrizPrintInyectaNota1ParaCompactacion(): void {
        $print = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $this->assertStringContainsString("stripos(\$notaExistente, 'Nota 1') !== false || stripos(\$notaExistente, 'TROXLER') !== false", $print);
        $this->assertStringContainsString('$metadatos[\'nota_equipo_densimetro\']', $print);
    }
}
