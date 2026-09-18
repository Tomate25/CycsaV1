<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrizRevisionAprobacionTest extends TestCase {

    public function testEstadoPendienteCuandoResultadosJsonEstaVacio(): void {
        $resNull = obtenerEstadoRevisionMatriz(null);
        $this->assertEquals('pendiente', $resNull['estado']);
        $this->assertFalse($resNull['puede_enviar_cliente']);
        $this->assertFalse($resNull['tiene_resultados']);
        $this->assertEquals('PENDIENTE MATRIZ', $resNull['estado_label']);

        $resVacio = obtenerEstadoRevisionMatriz('');
        $this->assertEquals('pendiente', $resVacio['estado']);
        $this->assertFalse($resVacio['puede_enviar_cliente']);

        $resArrayVacio = obtenerEstadoRevisionMatriz('[]');
        $this->assertEquals('pendiente', $resArrayVacio['estado']);
        $this->assertFalse($resArrayVacio['puede_enviar_cliente']);
    }

    public function testEstadoEnRevisionPorDefectoAlGuardarResultados(): void {
        $jsonPlano = json_encode([
            ['Código laboratorio' => 'MS-0001-26', 'Lectura' => '4500']
        ]);
        $res = obtenerEstadoRevisionMatriz($jsonPlano);
        $this->assertEquals('en_revision', $res['estado']);
        $this->assertEquals('EN REVISIÓN', $res['estado_label']);
        $this->assertTrue($res['tiene_resultados']);
        $this->assertFalse($res['puede_enviar_cliente']);
        $this->assertTrue($res['puede_aprobar']);
        $this->assertTrue($res['puede_devolver']);
    }

    public function testEstadoDevueltaConObservaciones(): void {
        $payload = [
            'filas' => [
                ['Código laboratorio' => 'MS-0001-26', 'Lectura' => '4500']
            ],
            'metadatos' => [
                'cliente_nombre' => 'Cliente Prueba'
            ],
            'revision' => [
                'estado' => 'devuelta',
                'fecha_envio' => '2026-09-17 10:00:00',
                'usuario_envio' => 'Técnico Lab',
                'fecha_revision' => '2026-09-17 11:30:00',
                'usuario_revisor' => 'Ing. Noel Quintana',
                'motivo_devolucion' => 'Verificar lectura de celda de carga número 3.'
            ]
        ];

        $res = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('devuelta', $res['estado']);
        $this->assertEquals('DEVUELTA', $res['estado_label']);
        $this->assertFalse($res['puede_enviar_cliente']);
        $this->assertEquals('Verificar lectura de celda de carga número 3.', $res['motivo_devolucion']);
        $this->assertEquals('Ing. Noel Quintana', $res['usuario_revisor']);
    }

    public function testEstadoAprobadaHabilitaEnvioCliente(): void {
        $payload = [
            'filas' => [
                ['Código laboratorio' => 'MS-0001-26', 'Lectura' => '5200']
            ],
            'metadatos' => [
                'cliente_nombre' => 'Cliente Aprobado'
            ],
            'revision' => [
                'estado' => 'aprobada',
                'fecha_envio' => '2026-09-17 10:00:00',
                'usuario_envio' => 'Técnico Lab',
                'fecha_revision' => '2026-09-17 12:00:00',
                'usuario_revisor' => 'Supervisor Calidad'
            ]
        ];

        $res = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('aprobada', $res['estado']);
        $this->assertEquals('APROBADA', $res['estado_label']);
        $this->assertTrue($res['puede_enviar_cliente']);
        $this->assertFalse($res['puede_aprobar']);
        $this->assertFalse($res['puede_devolver']);
    }

    public function testCicloCompletoTransicionesConHistorial(): void {
        // 1. Técnico guarda datos iniciales
        $historial = [];
        $historial[] = [
            'accion' => 'enviado_revision',
            'fecha' => '2026-09-17 08:00:00',
            'usuario' => 'Técnico Pedro',
            'nota' => 'Captura inicial de 3 cilindros.'
        ];
        $payload = [
            'filas' => [['Código' => 'C-1', 'Carga' => '40000']],
            'revision' => [
                'estado' => 'en_revision',
                'fecha_envio' => '2026-09-17 08:00:00',
                'usuario_envio' => 'Técnico Pedro',
                'historial' => $historial
            ]
        ];
        $rev1 = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('en_revision', $rev1['estado']);
        $this->assertFalse($rev1['puede_enviar_cliente']);

        // 2. Supervisor revisa y devuelve con observación
        $historial[] = [
            'accion' => 'devuelta',
            'fecha' => '2026-09-17 09:15:00',
            'usuario' => 'Ing. Noel Quintana',
            'motivo' => 'Cálculo de esfuerzo psi incorrecto por error de diámetro.'
        ];
        $payload['revision']['estado'] = 'devuelta';
        $payload['revision']['fecha_revision'] = '2026-09-17 09:15:00';
        $payload['revision']['usuario_revisor'] = 'Ing. Noel Quintana';
        $payload['revision']['motivo_devolucion'] = 'Cálculo de esfuerzo psi incorrecto por error de diámetro.';
        $payload['revision']['historial'] = $historial;

        $rev2 = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('devuelta', $rev2['estado']);
        $this->assertFalse($rev2['puede_enviar_cliente']);
        $this->assertEquals('Cálculo de esfuerzo psi incorrecto por error de diámetro.', $rev2['motivo_devolucion']);

        // 3. Técnico corrige y reenvía
        $historial[] = [
            'accion' => 'corregido_y_reenviado',
            'fecha' => '2026-09-17 10:30:00',
            'usuario' => 'Técnico Pedro',
            'nota' => 'Diámetro corregido a 15.2 cm.'
        ];
        $payload['filas'] = [['Código' => 'C-1', 'Carga' => '42500']];
        $payload['revision']['estado'] = 'en_revision';
        $payload['revision']['fecha_envio'] = '2026-09-17 10:30:00';
        $payload['revision']['historial'] = $historial;

        $rev3 = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('en_revision', $rev3['estado']);
        $this->assertFalse($rev3['puede_enviar_cliente']);

        // 4. Supervisor aprueba satisfactoriamente
        $historial[] = [
            'accion' => 'aprobada',
            'fecha' => '2026-09-17 11:00:00',
            'usuario' => 'Ing. Noel Quintana',
            'nota' => 'Cálculos validados con éxito.'
        ];
        $payload['revision']['estado'] = 'aprobada';
        $payload['revision']['fecha_revision'] = '2026-09-17 11:00:00';
        $payload['revision']['usuario_revisor'] = 'Ing. Noel Quintana';
        $payload['revision']['motivo_devolucion'] = null;
        $payload['revision']['historial'] = $historial;

        $rev4 = obtenerEstadoRevisionMatriz(json_encode($payload));
        $this->assertEquals('aprobada', $rev4['estado']);
        $this->assertTrue($rev4['puede_enviar_cliente']);
        $this->assertCount(4, $rev4['historial']);
        $this->assertEquals('aprobada', $rev4['historial'][3]['accion']);
    }
}
