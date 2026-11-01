<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CapturaMatrizRoundTripTest extends TestCase {

    public function testConcretoRoundTripPreservaCeldasDeMedicionYReplica(): void {
        $filasOriginales = [
            [
                'Código laboratorio' => 'MS-0001-26',
                'Nombre muestra' => 'Muestra 1',
                'Área (in²)' => '28.27',
                'Carga (lb)' => '85000',
                'R. Compresión (lb/in²)' => '3006.7',
                'R. Compresión (kg/cm²)' => '211.4',
                'Fecha de Fabricación' => '2026-10-01',
                'Fecha de Ruptura' => '2026-10-29',
                'Edad (Días)' => '28'
            ],
            [
                'Código laboratorio' => 'MS-0002-26',
                'Nombre muestra' => 'Muestra 2',
                'Área (in²)' => '28.27',
                'Carga (lb)' => '92000',
                'R. Compresión (lb/in²)' => '3254.3',
                'R. Compresión (kg/cm²)' => '228.8',
                'Fecha de Fabricación' => '2026-10-01',
                'Fecha de Ruptura' => '2026-10-29',
                'Edad (Días)' => '28'
            ],
            [
                'Código laboratorio' => 'MS-0001-26-CR',
                'Nombre muestra' => 'Muestra 1 (Réplica CR)',
                '_es_replica' => true,
                '_muestra_origen' => 'MS-0001-26',
                'Área (in²)' => '28.27',
                'Carga (lb)' => '86500',
                'R. Compresión (lb/in²)' => '3059.8',
                'R. Compresión (kg/cm²)' => '215.1',
                'Fecha de Fabricación' => '2026-10-01',
                'Fecha de Ruptura' => '2026-10-29',
                'Edad (Días)' => '28'
            ]
        ];

        $normalizadas = normalizarReplicasMatriz($filasOriginales);
        $this->assertCount(3, $normalizadas);

        $payload = [
            'version_actual' => 1,
            'filas' => $normalizadas,
            'metadatos' => [
                'proyecto' => 'Puente Central',
                'cliente_nombre' => 'Constructora ABC',
                'ensayo_realizado' => 'Resistencia a la compresión de cilindros de concreto'
            ],
            'revision' => ['estado' => 'en_revision']
        ];

        $jsonGuardado = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $recuperado = json_decode($jsonGuardado, true);

        $this->assertIsArray($recuperado['filas']);
        $this->assertCount(3, $recuperado['filas']);

        // Verificar que las celdas de medición round-trip no se limpian
        $fila1 = $recuperado['filas'][0];
        $this->assertSame('85000', $fila1['Carga (lb)']);
        $this->assertSame('28.27', $fila1['Área (in²)']);
        $this->assertSame('3006.7', $fila1['R. Compresión (lb/in²)']);
        $this->assertSame('211.4', $fila1['R. Compresión (kg/cm²)']);

        // Verificar réplica
        $replica = $recuperado['filas'][2];
        $this->assertTrue($replica['_es_replica']);
        $this->assertSame('MS-0001-26', $replica['_muestra_origen']);
        $this->assertSame('86500', $replica['Carga (lb)']);
        $this->assertSame('215.1', $replica['R. Compresión (kg/cm²)']);
    }

    public function testCompactacionRoundTripNoColisionaPvsMaxConPvsSitio(): void {
        $filasCompactacion = [
            [
                'Código laboratorio' => 'MS-0001-26',
                'Nombre muestra' => 'Estación 0+100',
                'P.V.S Max (kg/m³)' => '1950',
                'P.V.S.Sitio (kg/m³)' => '1860',
                'Compactación ((%) (P/P))' => '95.4',
                'Humedad de Sitio (%)' => '12.5'
            ],
            [
                'Código laboratorio' => 'MS-0001-26-CR',
                'Nombre muestra' => 'Estación 0+100 (Réplica CR)',
                '_es_replica' => true,
                '_muestra_origen' => 'MS-0001-26',
                'P.V.S Max (kg/m³)' => '1950',
                'P.V.S.Sitio (kg/m³)' => '1875',
                'Compactación ((%) (P/P))' => '96.2',
                'Humedad de Sitio (%)' => '12.8'
            ]
        ];

        $normalizadas = normalizarReplicasMatriz($filasCompactacion);
        $payload = [
            'version_actual' => 1,
            'filas' => $normalizadas,
            'metadatos' => ['proyecto' => 'Carretera Norte'],
            'revision' => ['estado' => 'en_revision']
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $recuperado = json_decode($json, true);

        $f0 = $recuperado['filas'][0];
        $this->assertSame('1950', $f0['P.V.S Max (kg/m³)']);
        $this->assertSame('1860', $f0['P.V.S.Sitio (kg/m³)']);
        $this->assertNotEquals($f0['P.V.S Max (kg/m³)'], $f0['P.V.S.Sitio (kg/m³)'], 'P.V.S Max y P.V.S Sitio no deben sobreescribirse');
        $this->assertSame('95.4', $f0['Compactación ((%) (P/P))']);

        // Réplica
        $fRep = $recuperado['filas'][1];
        $this->assertSame('1875', $fRep['P.V.S.Sitio (kg/m³)']);
        $this->assertSame('96.2', $fRep['Compactación ((%) (P/P))']);
    }

    public function testVistaCapturaMatrizUsaIdDetalleActivoEnChipCCYForm(): void {
        $vista = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');

        // Debe calcular $idDetalleActivo e inyectarlo en $idDetalle y $detalle['id']
        $this->assertStringContainsString('$idDetalleActivo =', $vista);
        // Debe usar $idDetalle / $detalle['id'] garantizados en el link de control de calidad
        $this->assertStringContainsString('control-calidad/ver?id_detalle=<?= (int)($detalle[\'id\'] ?? ($idDetalle ?? 0)) ?>', $vista);
        // Debe usar $idDetalleActivo en el input oculto
        $this->assertStringContainsString('name="id_detalle" value="<?= $idDetalleActivo ?>"', $vista);
        // No debe contener el dispatchEvent de submit que duplicaba envíos
        $this->assertStringNotContainsString("form.dispatchEvent(new Event('submit'", $vista);
    }
}
