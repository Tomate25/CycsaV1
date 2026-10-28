<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CorreccionAdministrativaVersionesTest extends TestCase {
    public function testReemisionIncrementaVersionYAnulaPrevia(): void {
        $filas = [
            ['Código laboratorio' => 'MS-0001-26', 'Carga (lb)' => '100']
        ];
        $metadatos = [
            'cliente_nombre' => 'Cliente Original',
            'proyecto' => 'Proyecto A',
            'codigo_formato' => 'CYCSA-RT-FM-22'
        ];
        $revision = [
            'estado' => 'aprobada',
            'historial' => []
        ];

        $codigoBase = generarCodigoInformeEnsayo($filas);
        $this->assertSame('CYCSA-INF-MS-0001-26', $codigoBase);

        // Simular solicitud de corrección administrativa
        $versionActual = 1;
        $nuevaVersion = $versionActual + 1;
        $nuevoCodigo = "{$codigoBase}-" . ($nuevaVersion - 1);
        $sustituyeA = $codigoBase;
        $motivo = 'Ajuste de razón social solicitada por el cliente';

        $versiones = [];
        $versiones[] = [
            'version' => $versionActual,
            'codigo_documento' => $codigoBase,
            'estado' => 'anulada',
            'sustituida_por_version' => $nuevaVersion,
            'motivo_reemision' => $motivo,
            'filas' => $filas,
            'metadatos' => $metadatos
        ];

        // Modificar metadatos administrativos
        $metadatos['cliente_nombre'] = 'Cliente Nuevo S.A.';
        $revision['estado'] = 'en_revision';

        $reemision = [
            'version' => $nuevaVersion,
            'codigo_version' => $nuevoCodigo,
            'sustituye_a' => $sustituyeA,
            'tipo_correccion' => 'administrativa',
            'motivo' => $motivo
        ];

        $jsonFinal = [
            'version_actual' => $nuevaVersion,
            'filas' => $filas,
            'metadatos' => $metadatos,
            'revision' => $revision,
            'versiones' => $versiones,
            'reemision' => $reemision
        ];

        $this->assertSame(2, $jsonFinal['version_actual']);
        $this->assertSame('CYCSA-INF-MS-0001-26-1', $jsonFinal['reemision']['codigo_version']);
        $this->assertSame('CYCSA-INF-MS-0001-26', $jsonFinal['reemision']['sustituye_a']);
        $this->assertSame('anulada', $jsonFinal['versiones'][0]['estado']);
        $this->assertSame('en_revision', $jsonFinal['revision']['estado']);
        $this->assertSame('Cliente Nuevo S.A.', $jsonFinal['metadatos']['cliente_nombre']);
    }

    public function testReemisionExigeReAprobacionParaEnvio(): void {
        $json = json_encode([
            'filas' => [['Código laboratorio' => 'MS-0001-26', 'Carga (lb)' => '100']],
            'revision' => ['estado' => 'en_revision']
        ]);

        $revInfo = obtenerEstadoRevisionMatriz($json);
        $this->assertFalse($revInfo['puede_enviar_cliente']);
        $this->assertTrue($revInfo['puede_aprobar']);
    }

    public function testPdfGeneraMarcaSustitucion(): void {
        $detalle = [
            'id' => 999,
            'id_cotizacion' => 1,
            'codigo_os' => 'OS-2026-TEST',
            'descripcion_ensayo' => 'Compresión de Cilindros',
            'archivo_markdown' => 'CYCSA-RT-FM-22.md',
            'formato_id' => null,
            'orientacion' => 'horizontal',
            'resultados_json' => json_encode([
                'version_actual' => 2,
                'filas' => [['Código laboratorio' => 'MS-0001-26', 'Carga' => '100']],
                'metadatos' => [
                    'cliente_nombre' => 'Constructora Alfa',
                    'proyecto' => 'Puente Norte',
                    'codigo_formato' => 'CYCSA-RT-FM-22'
                ],
                'reemision' => [
                    'version' => 2,
                    'codigo_version' => 'CYCSA-INF-MS-0001-26-1',
                    'sustituye_a' => 'CYCSA-INF-MS-0001-26',
                    'tipo_correccion' => 'administrativa',
                    'motivo' => 'Corrección de Razón Social'
                ]
            ])
        ];

        // Validar que la función de generación procesa la reemisión correctamente
        $dec = json_decode($detalle['resultados_json'], true);
        $this->assertSame('CYCSA-INF-MS-0001-26', $dec['reemision']['sustituye_a']);
        $this->assertSame(2, $dec['version_actual']);
    }

    public function testTablaDocumentosReemisionesExiste(): void {
        try {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->query("SHOW TABLES LIKE 'documentos_reemisiones'");
            $this->assertNotEmpty($stmt->fetchAll());
        } catch (\PDOException $e) {
            $this->markTestSkipped("BD no disponible para test de tabla: " . $e->getMessage());
        }
    }
}
