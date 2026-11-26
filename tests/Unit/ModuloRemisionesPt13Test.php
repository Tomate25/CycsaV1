<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cycsa\Modulos\Remisiones\Modelos\RemisionModelo;
use Cycsa\Modulos\Remisiones\Modelos\RevisionPt13Modelo;
use Cycsa\Modulos\Remisiones\Servicios\DocumentoRgFm41Servicio;
use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuloRemisionesPt13Test extends TestCase
{
    private PDO $db;
    private RemisionModelo $remisionModelo;
    private RevisionPt13Modelo $pt13Modelo;

    /** @var array<int> */
    private array $remisionIds = [];
    /** @var array<int> */
    private array $revisionPt13Ids = [];
    /** @var array<int> */
    private array $anuladoIds = [];
    /** @var array<int> */
    private array $osIds = [];
    /** @var array<int> */
    private array $detalleIds = [];
    /** @var array<int> */
    private array $cotizacionIds = [];
    /** @var array<int> */
    private array $clienteIds = [];
    /** @var array<int> */
    private array $usuarioIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->remisionModelo = new RemisionModelo();
        $this->pt13Modelo = new RevisionPt13Modelo();
    }

    protected function tearDown(): void
    {
        if (isset($this->db) && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
        if (isset($this->db)) {
            $this->borrarIds('remisiones_informes_detalle', 'id_remision', $this->remisionIds);
            $this->borrarIds('remisiones_informes', 'id', $this->remisionIds);
            $this->borrarIds('informes_anulados', 'id', $this->anuladoIds);
            $this->borrarIds('informes_revisiones_pt13', 'id', $this->revisionPt13Ids);
            $this->borrarIds('informes_revisiones_pt13', 'id_detalle_cotizacion', $this->detalleIds);
            $this->borrarIds('ordenes_servicio', 'id', $this->osIds);
            $this->borrarIds('cotizacion_detalles', 'id', $this->detalleIds);
            $this->borrarIds('cotizaciones', 'id', $this->cotizacionIds);
            $this->borrarIds('clientes', 'id', $this->clienteIds);
            $this->borrarIds('usuarios', 'id', $this->usuarioIds);
        }
        parent::tearDown();
    }

    public function testGenerarCodigoRemisionFormatoRlabYSecuencial(): void
    {
        $fechaPrueba = '2031-07-15 10:00:00';
        $cod1 = $this->remisionModelo->generarCodigoRemision($fechaPrueba);
        $this->assertMatchesRegularExpression('/^RLAB-07-2031-\d+$/', $cod1);

        $n1 = (int)substr($cod1, strrpos($cod1, '-') + 1);
        $id1 = $this->remisionModelo->crear([
            'codigo_remision' => $cod1,
            'empresa_nombre' => 'Constructora Secuencial S.A.',
            'modalidad_entrega' => 'laboratorio',
            'fecha_entrega' => $fechaPrueba,
            'entregado_por' => 'Recepción CYCSA',
        ], [
            ['codigo_informe' => 'INF-SEQ-01-' . uniqid(), 'descripcion_ensayo' => 'Compresión Cilindros', 'formato_entrega' => 'Digital PDF'],
        ]);
        $this->remisionIds[] = $id1;

        $cod2 = $this->remisionModelo->generarCodigoRemision($fechaPrueba);
        $this->assertSame('RLAB-07-2031-' . ($n1 + 1), $cod2);
    }

    public function testCrudRemisionCompletoYGeneracionPdfRgFm41(): void
    {
        $fixture = $this->crearFixtureOsConDetalle(101);
        $codigoInforme = 'INF-RG41-' . strtoupper(bin2hex(random_bytes(3)));

        $idRem = $this->remisionModelo->crear([
            'id_os' => $fixture['id_os'],
            'id_cliente' => $fixture['id_cliente'],
            'empresa_nombre' => $fixture['cliente_nombre'],
            'modalidad_entrega' => 'domicilio',
            'fecha_entrega' => '2026-11-26 14:30:00',
            'direccion_entrega' => 'Km 85 Carretera León - Chinandega',
            'entregado_por' => 'Ana Recepción',
            'observaciones' => 'Entrega con anexos originales.',
            'usuario_id' => $fixture['analista_id'],
        ], [
            [
                'id_detalle_cotizacion' => $fixture['id_detalle'],
                'codigo_informe' => $codigoInforme,
                'descripcion_ensayo' => 'Resistencia a Compresión ASTM C39',
                'formato_entrega' => 'Ambos',
            ],
        ]);
        $this->remisionIds[] = $idRem;

        // Obtener por ID
        $rem = $this->remisionModelo->obtenerPorId($idRem);
        $this->assertNotNull($rem);
        $this->assertMatchesRegularExpression('/^RLAB-11-2026-\d+$/', (string)$rem['codigo_remision']);
        $this->assertSame('Emitida', $rem['estado']);
        $this->assertSame($fixture['codigo_os'], $rem['codigo_os']);
        $this->assertCount(1, $rem['detalles']);
        $this->assertSame($codigoInforme, $rem['detalles'][0]['codigo_informe']);

        // Obtener por O/S
        $porOs = $this->remisionModelo->obtenerPorOs($fixture['id_os']);
        $this->assertNotEmpty($porOs);
        $this->assertSame($idRem, (int)$porOs[0]['id']);

        // Marcar como Entregada
        $entregada = $this->remisionModelo->marcarEntregada(
            $idRem,
            'Ing. Carlos Méndez',
            '281-150588-0002B',
            '8855-4433',
            '2026-11-26 15:15:00'
        );
        $this->assertTrue($entregada);

        $remEnt = $this->remisionModelo->obtenerPorId($idRem);
        $this->assertSame('Entregada', $remEnt['estado']);
        $this->assertSame('Ing. Carlos Méndez', $remEnt['persona_recibe']);
        $this->assertSame('281-150588-0002B', $remEnt['cedula_recibe']);

        // Verificar HTML y PDF Dompdf oficial CYCSA-RG-FM-41 con logo base64
        $html = DocumentoRgFm41Servicio::html($remEnt);
        $this->assertStringContainsString('CYCSA-RG-FM-41', $html);
        $this->assertStringContainsString((string)$remEnt['codigo_remision'], $html);
        $this->assertStringContainsString($codigoInforme, $html);
        $this->assertStringContainsString('data:image/', $html, 'El documento RG-FM-41 PDF debe incrustar el logo en base64.');

        $pdfBytes = DocumentoRgFm41Servicio::pdf($remEnt);
        $this->assertStringStartsWith('%PDF', $pdfBytes);
        $this->assertGreaterThan(1000, strlen($pdfBytes));

        // Anular remisión
        $anulada = $this->remisionModelo->anular($idRem, 'Reemisión solicitada por cambio de receptor autorizado');
        $this->assertTrue($anulada);
        $remAnul = $this->remisionModelo->obtenerPorId($idRem);
        $this->assertSame('Anulada', $remAnul['estado']);
        $this->assertStringContainsString('cambio de receptor', (string)$remAnul['motivo_anulacion']);

        // KPIs incluyen la estructura esperada
        $kpis = $this->remisionModelo->obtenerResumenKpis();
        foreach (['total', 'emitidas', 'entregadas', 'anuladas', 'revisiones_pt13', 'informes_anulados'] as $claveKpi) {
            $this->assertArrayHasKey($claveKpi, $kpis);
        }
    }

    public function testChecklistRtFm65Tiene25ItemsYFlujoPt13TresEtapasConAntiAutoaprobacion(): void
    {
        // Verificar exactamente 25 ítems del checklist CYCSA-RT-FM-65
        $this->assertCount(25, RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.2.1_a', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.2.1_p', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('NC2_PJ', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.2.2_a', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.2.2_b', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.3.1_a', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.3.1_e', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);
        $this->assertArrayHasKey('7.8.3.2_7.8.5', RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65);

        $analistaId = $this->crearUsuario('Analista Captura PT13');
        $supervisorId = $this->crearUsuario('Supervisor Tecnico PT13');
        $asistenteId = $this->crearUsuario('Asistente Coord PT13');
        $calidadId = $this->crearUsuario('Gerente Calidad PT13');

        $fixture = $this->crearFixtureOsConDetalle($analistaId);
        $idDetalle = $fixture['id_detalle'];

        $checklistCompleto = [];
        foreach (array_keys(RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65) as $k) {
            $checklistCompleto[$k] = 'cumple';
        }

        // 1. Anti-autoaprobación bloquea al analista que capturó resultados en Revisión Técnica
        $bloqueoTec = $this->pt13Modelo->registrarRevisionTecnica(
            $idDetalle,
            $analistaId,
            'Analista Captura PT13',
            $checklistCompleto,
            'Intento de auto-revisión'
        );
        $this->assertFalse($bloqueoTec['exito']);
        $this->assertStringContainsString('PT-13', (string)$bloqueoTec['error']);

        // 2. Anti-autoaprobación bloquea al analista que capturó resultados en Autorización Calidad
        $bloqueoCal = $this->pt13Modelo->autorizarYLiberar(
            $idDetalle,
            $analistaId,
            'Analista Captura PT13',
            [
                'es_unificacion_suplencia' => 1,
                'suplencia_motivo' => 'Intento propio',
                'suplencia_referencia_autorizacion' => 'REF-INVALIDA',
            ]
        );
        $this->assertFalse($bloqueoCal['exito']);
        $this->assertStringContainsString('PT-13', (string)$bloqueoCal['error']);

        // 3. No se puede autorizar sin completar Etapa 1 y Etapa 2 (si no hay suplencia)
        $sinEtapas = $this->pt13Modelo->autorizarYLiberar($idDetalle, $calidadId, 'Gerente Calidad PT13');
        $this->assertFalse($sinEtapas['exito']);

        // 4. Etapa 1: Revisión Técnica por supervisor distinto
        $resTec = $this->pt13Modelo->registrarRevisionTecnica(
            $idDetalle,
            $supervisorId,
            'Supervisor Tecnico PT13',
            $checklistCompleto,
            'Cálculos verificados conforme a ASTM.',
            ['incertidumbre_medicion' => '±1.5% (k=2)']
        );
        $this->assertTrue($resTec['exito']);
        $this->assertSame('En Revision Forma', $resTec['estado_pt13']);
        $this->revisionPt13Ids[] = (int)$resTec['id'];

        // 5. Etapa 2: Revisión de Forma por asistente de coordinación
        $resForma = $this->pt13Modelo->registrarRevisionForma(
            $idDetalle,
            $asistenteId,
            'Asistente Coord PT13',
            $checklistCompleto,
            'Formato, firmas y paginación conformes.'
        );
        $this->assertTrue($resForma['exito']);
        $this->assertSame('Listo Para Calidad', $resForma['estado_pt13']);

        // 6. Etapa 3: Autorización y Liberación por Calidad
        $resCal = $this->pt13Modelo->autorizarYLiberar($idDetalle, $calidadId, 'Gerente Calidad PT13');
        $this->assertTrue($resCal['exito']);
        $this->assertSame('Autorizado', $resCal['estado_pt13']);

        $reg = $this->pt13Modelo->obtenerPorDetalle($idDetalle);
        $this->assertNotNull($reg);
        $this->assertSame('Autorizado', $reg['estado_pt13']);
        $this->assertSame($supervisorId, (int)$reg['revisor_tecnico_id']);
        $this->assertSame($asistenteId, (int)$reg['asistente_coord_id']);
        $this->assertSame($calidadId, (int)$reg['autorizador_calidad_id']);
        $this->assertSame(0, (int)$reg['es_unificacion_suplencia']);
    }

    public function testUnificacionPorSuplenciaExigeMotivoYReferenciaYAutorizaCorrectamente(): void
    {
        $analistaId = $this->crearUsuario('Analista Suplencia');
        $calidadId = $this->crearUsuario('Director Calidad Suplente');
        $fixture = $this->crearFixtureOsConDetalle($analistaId);
        $idDetalle = $fixture['id_detalle'];

        // Sin motivo ni referencia debe fallar
        $fallo1 = $this->pt13Modelo->autorizarYLiberar($idDetalle, $calidadId, 'Director Calidad Suplente', [
            'es_unificacion_suplencia' => 1,
            'suplencia_motivo' => '',
            'suplencia_referencia_autorizacion' => '',
        ]);
        $this->assertFalse($fallo1['exito']);

        // Con motivo pero sin referencia debe fallar
        $fallo2 = $this->pt13Modelo->autorizarYLiberar($idDetalle, $calidadId, 'Director Calidad Suplente', [
            'es_unificacion_suplencia' => 1,
            'suplencia_motivo' => 'Ausencia programada de supervisor',
            'suplencia_referencia_autorizacion' => '',
        ]);
        $this->assertFalse($fallo2['exito']);

        // Con motivo y referencia formal de autorización debe aprobar y registrar trazabilidad completa
        $exito = $this->pt13Modelo->autorizarYLiberar($idDetalle, $calidadId, 'Director Calidad Suplente', [
            'es_unificacion_suplencia' => 1,
            'suplencia_funciones' => 'Revisión Técnica + Revisión de Forma + Autorización de Calidad',
            'suplencia_motivo' => 'Vacaciones del Supervisor de Laboratorio y Asistente en gira de campo',
            'suplencia_referencia_autorizacion' => 'MEMO-GER-2026-114',
        ]);
        $this->assertTrue($exito['exito']);
        $this->assertSame('Autorizado', $exito['estado_pt13']);
        $this->revisionPt13Ids[] = (int)$exito['id'];

        $reg = $this->pt13Modelo->obtenerPorDetalle($idDetalle);
        $this->assertNotNull($reg);
        $this->assertSame(1, (int)$reg['es_unificacion_suplencia']);
        $this->assertSame('MEMO-GER-2026-114', $reg['suplencia_referencia_autorizacion']);
        $this->assertStringContainsString('Vacaciones', (string)$reg['suplencia_motivo']);
    }

    public function testAnularInformeBloqueaReutilizacionPermanenteDeCodigo(): void
    {
        $analistaId = $this->crearUsuario('Analista Anulacion');
        $fixture = $this->crearFixtureOsConDetalle($analistaId);
        $codigo = 'INF-ANUL-' . strtoupper(bin2hex(random_bytes(3)));

        $this->assertFalse($this->pt13Modelo->esCodigoAnulado($codigo));

        // Motivo vacío rechazado
        $vacio = $this->pt13Modelo->anularInforme($codigo, $fixture['id_detalle'], $fixture['id_os'], '', $analistaId, 'Analista Anulacion');
        $this->assertFalse($vacio['exito']);

        // Anulación válida
        $res = $this->pt13Modelo->anularInforme(
            $codigo,
            $fixture['id_detalle'],
            $fixture['id_os'],
            'Error tipográfico en identificación de muestra solicitada por cliente',
            $analistaId,
            'Analista Anulacion'
        );
        $this->assertTrue($res['exito']);
        $this->anuladoIds[] = (int)$res['id'];

        $this->assertTrue($this->pt13Modelo->esCodigoAnulado($codigo));

        // Intento duplicado rechazado
        $dup = $this->pt13Modelo->anularInforme(
            $codigo,
            $fixture['id_detalle'],
            $fixture['id_os'],
            'Segundo intento duplicado',
            $analistaId,
            'Analista Anulacion'
        );
        $this->assertFalse($dup['exito']);

        // Estado PT-13 actualizado a Anulado
        $reg = $this->pt13Modelo->obtenerPorDetalle($fixture['id_detalle']);
        $this->assertNotNull($reg);
        $this->assertSame('Anulado', $reg['estado_pt13']);

        // Bloquea incluir el código anulado en una remisión de entrega
        $this->expectException(RuntimeException::class);
        $this->remisionModelo->crear([
            'empresa_nombre' => $fixture['cliente_nombre'],
            'modalidad_entrega' => 'laboratorio',
        ], [
            ['codigo_informe' => $codigo, 'descripcion_ensayo' => 'Ensayo anulado', 'formato_entrega' => 'Digital PDF'],
        ]);
    }

    private function crearUsuario(string $nombre): int
    {
        $id = $this->insertar('usuarios', [
            'id_rol' => 2,
            'nombre' => $nombre,
            'email' => 'pt13-' . uniqid('', true) . '@test.local',
            'password' => 'x',
            'activo' => 1,
        ]);
        $this->usuarioIds[] = $id;
        return $id;
    }

    private function crearFixtureOsConDetalle(int $usuarioCapturaId): array
    {
        $marca = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $creadorId = $usuarioCapturaId > 0 && in_array($usuarioCapturaId, $this->usuarioIds, true)
            ? $usuarioCapturaId
            : $this->crearUsuario('Usuario Fixture ' . $marca);

        $clienteNombre = 'Cliente PT13 ' . $marca;
        $idCliente = $this->insertar('clientes', [
            'nombre_razon_social' => $clienteNombre,
            'identificacion' => 'J0310000' . substr($marca, 0, 4),
            'telefono' => '8800-1122',
            'direccion' => 'León, Nicaragua',
            'activo' => 1,
        ]);
        $this->clienteIds[] = $idCliente;

        $idCot = $this->insertar('cotizaciones', [
            'codigo' => 'COT-PT13-' . $marca,
            'id_cliente' => $idCliente,
            'id_usuario_creador' => $creadorId,
            'atencion_a' => 'Ing. Prueba ' . $marca,
            'nombre_proyecto' => 'Proyecto Prueba PT13 ' . $marca,
            'direccion_proyecto' => 'León, Nicaragua',
            'condicion_pago' => 'Pago contra entrega',
            'estado' => 'Aprobada por Cliente',
            'subtotal' => 100.00,
            'impuesto' => 15.00,
            'total' => 115.00,
        ]);
        $this->cotizacionIds[] = $idCot;

        $resultadosJson = json_encode([
            'usuario_captura_id' => $usuarioCapturaId,
            'usuario_captura_nombre' => 'Analista ' . $marca,
            'codigo_informe' => 'INF-PT13-' . $marca,
            'resultados' => ['resistencia_psi' => 4250],
        ], JSON_UNESCAPED_UNICODE);

        $idDetalle = $this->insertar('cotizacion_detalles', [
            'id_cotizacion' => $idCot,
            'descripcion_ensayo' => 'Compresión de Cilindros de Concreto ' . $marca,
            'norma_astm' => 'ASTM C39',
            'resultados_json' => $resultadosJson,
            'cantidad' => 1.00,
            'precio_unitario' => 100.00,
            'subtotal' => 100.00,
        ]);
        $this->detalleIds[] = $idDetalle;

        $codigoOs = 'OS-PT13-' . $marca;
        $idOs = $this->insertar('ordenes_servicio', [
            'codigo_os' => $codigoOs,
            'id_cotizacion' => $idCot,
            'cotizacion_version' => 1,
            'id_cliente' => $idCliente,
            'id_cliente_informe' => $idCliente,
            'fecha_emision' => date('Y-m-d'),
            'nombre_proyecto' => 'Proyecto Prueba PT13 ' . $marca,
            'atencion_a' => 'Ing. Prueba ' . $marca,
            'estado' => 'Aprobada',
        ]);
        $this->osIds[] = $idOs;

        return [
            'id_cliente' => $idCliente,
            'cliente_nombre' => $clienteNombre,
            'id_cotizacion' => $idCot,
            'id_detalle' => $idDetalle,
            'id_os' => $idOs,
            'codigo_os' => $codigoOs,
            'analista_id' => $creadorId,
        ];
    }

    private function insertar(string $tabla, array $datos): int
    {
        $cols = array_keys($datos);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tabla,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute($datos);
        return (int)$this->db->lastInsertId();
    }

    private function borrarIds(string $tabla, string $columna, array $ids): void
    {
        $idsLimpios = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if ($idsLimpios === []) {
            return;
        }
        $placeholders = implode(', ', array_fill(0, count($idsLimpios), '?'));
        $stmt = $this->db->prepare("DELETE FROM {$tabla} WHERE {$columna} IN ({$placeholders})");
        $stmt->execute($idsLimpios);
    }
}
