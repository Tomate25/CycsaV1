<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ControlCalidadReplicasTest extends TestCase {
    public function testNormalizaSufijoRelacionYEvitaDuplicados(): void {
        $filas = [
            ['Código laboratorio' => 'MC-0001-26', 'Carga (lb)' => '100'],
            ['Código laboratorio' => 'MC-0002-26', 'Carga (lb)' => '200'],
            ['Código laboratorio' => 'codigo-manipulado', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Carga (lb)' => '102'],
            ['Código laboratorio' => 'MC-0001-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Carga (lb)' => '999'],
            ['Código laboratorio' => 'MC-9999-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-9999-26'],
        ];

        $normalizadas = normalizarReplicasMatriz($filas);

        $this->assertCount(3, $normalizadas);
        $this->assertSame('MC-0001-26-CR', $normalizadas[2]['Código laboratorio']);
        $this->assertTrue($normalizadas[2]['_es_replica']);
        $this->assertSame('MC-0001-26', $normalizadas[2]['_muestra_origen']);
        $this->assertSame('102', $normalizadas[2]['Carga (lb)']);
    }

    public function testAgrupaOriginalReplicaYCalculaDiferenciasNumericas(): void {
        $pares = obtenerParesControlCalidad([
            ['Código laboratorio' => 'MS-0042-26', 'Carga (lb)' => '100', 'Observación' => 'Original'],
            ['Código laboratorio' => 'MS-0042-26-CR', '_es_replica' => true, '_muestra_origen' => 'MS-0042-26', 'Carga (lb)' => '105', 'Observación' => 'Réplica'],
        ]);

        $this->assertCount(1, $pares);
        $this->assertSame('MS-0042-26', $pares[0]['codigo_original']);
        $this->assertSame('MS-0042-26-CR', $pares[0]['codigo_replica']);
        $this->assertSame(5.0, $pares[0]['diferencias']['Carga (lb)']['diferencia']);
        $this->assertSame(5.0, $pares[0]['diferencias']['Carga (lb)']['diferencia_porcentual']);
        $this->assertArrayNotHasKey('Observación', $pares[0]['diferencias']);
    }

    public function testReplicaEsExcluidaDeImpresionPdfYEnvioAlCliente(): void {
        $filasPublicas = filtrarFilasPublicasMatriz([
            ['Código laboratorio' => 'MC-0001-26', 'Resultado' => '98.5'],
            ['Código laboratorio' => 'MC-0001-26-CR', '_es_replica' => true, '_muestra_origen' => 'MC-0001-26', 'Resultado' => '99.1'],
            ['Código laboratorio' => 'MC-0002-26', 'Resultado' => '101.0'],
            ['Código laboratorio' => 'MC-0002-26-CR', 'Resultado' => '100.8'],
        ]);

        $this->assertCount(2, $filasPublicas);
        $this->assertSame(['MC-0001-26', 'MC-0002-26'], array_column($filasPublicas, 'Código laboratorio'));

        $vistaImpresion = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/matriz_print.php');
        $helpers = file_get_contents(dirname(__DIR__, 2) . '/app/Helpers/funciones.php');
        $controlador = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/OperacionesControlador.php');
        $this->assertStringContainsString('filtrarFilasPublicasMatriz($resultados)', $vistaImpresion);
        $this->assertGreaterThanOrEqual(2, substr_count($helpers, 'filtrarFilasPublicasMatriz($resultados)'));
        $this->assertStringContainsString('filtrarFilasPublicasMatriz(is_array($resultados)', $controlador);
    }

    public function testVistaMatrizIncluyeSelectorYPersistenciaDeRelacion(): void {
        $vista = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $controlador = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/OperacionesControlador.php');
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');

        $this->assertStringContainsString('replica-selector', $vista);
        $this->assertStringContainsString('sincronizarReplicasSeleccionadas', $vista);
        $this->assertStringContainsString('_muestra_origen', $vista);
        $this->assertStringContainsString('normalizarReplicasMatriz', $controlador);
        $this->assertStringContainsString("'/control-calidad'", $rutas);
        $this->assertStringContainsString("'/control-calidad/evaluar-replica'", $rutas);
    }
}
