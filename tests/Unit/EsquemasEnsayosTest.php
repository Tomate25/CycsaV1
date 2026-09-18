<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EsquemasEnsayosTest extends TestCase {
    public function testVeintiunFormatosTienenEsquemaYAdmitenResultadosEstructurados(): void {
        $directorio = dirname(__DIR__, 2) . '/database/ensayos';
        $archivos = glob($directorio . '/*.md');
        $esquemas = json_decode(file_get_contents($directorio . '/formatos_schema.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(21, $archivos);
        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $this->assertArrayHasKey($nombre, $esquemas);
            $columnas = $esquemas[$nombre]['columns'] ?? [];
            $this->assertNotEmpty($columnas, $nombre);
            $fila = [$columnas[0] => '12.5'];
            $json = json_encode([
                'filas' => [$fila],
                'metadatos' => ['codigo_formato' => $esquemas[$nombre]['codigo_formato'] ?? ''],
                'revision' => ['estado' => 'aprobada']
            ], JSON_THROW_ON_ERROR);
            $revision = obtenerEstadoRevisionMatriz($json);
            $this->assertSame('aprobada', $revision['estado'], $nombre);
            $this->assertTrue($revision['tiene_resultados'], $nombre);
            $this->assertSame('en_revision', obtenerEstadoRevisionMatriz(json_encode([$fila]))['estado'], $nombre);
        }
    }
}
