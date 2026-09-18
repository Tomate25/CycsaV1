<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrizPrefijoMuestraTest extends TestCase {

    public function testDeterminarPrefijoMuestraOSMuestraEnCampo(): void {
        // requiere_muestreo como entero 1
        $this->assertEquals('MC', determinarPrefijoMuestraOS(['requiere_muestreo' => 1]));

        // requiere_muestreo como string '1'
        $this->assertEquals('MC', determinarPrefijoMuestraOS(['requiere_muestreo' => '1']));

        // requiere_muestreo como booleano true
        $this->assertEquals('MC', determinarPrefijoMuestraOS(['requiere_muestreo' => true]));

        // Asignación de técnico de muestreo aunque requiere_muestreo sea 0 o nulo
        $this->assertEquals('MC', determinarPrefijoMuestraOS([
            'requiere_muestreo' => 0,
            'tecnico_muestreo' => 'Carlos Mendoza'
        ]));

        // Objeto stdClass
        $osObj = (object)[
            'requiere_muestreo' => 1,
            'tecnico_muestreo' => ''
        ];
        $this->assertEquals('MC', determinarPrefijoMuestraOS($osObj));

        $osObjTecnico = (object)[
            'requiere_muestreo' => 0,
            'tecnico_muestreo' => 'Ing. Juan Pérez'
        ];
        $this->assertEquals('MC', determinarPrefijoMuestraOS($osObjTecnico));
    }

    public function testDeterminarPrefijoMuestraOSMuestraEnSede(): void {
        // Cliente trae la muestra (sin técnicos ni salida a campo)
        $this->assertEquals('MS', determinarPrefijoMuestraOS(['requiere_muestreo' => 0, 'tecnico_muestreo' => '']));
        $this->assertEquals('MS', determinarPrefijoMuestraOS(['requiere_muestreo' => '0']));
        $this->assertEquals('MS', determinarPrefijoMuestraOS(['requiere_muestreo' => false]));

        // Array vacío o nulo
        $this->assertEquals('MS', determinarPrefijoMuestraOS([]));
        $this->assertEquals('MS', determinarPrefijoMuestraOS(null));

        // Objeto sin muestreo
        $osObj = (object)[
            'requiere_muestreo' => 0,
            'tecnico_muestreo' => null
        ];
        $this->assertEquals('MS', determinarPrefijoMuestraOS($osObj));
    }

    public function testGeneracionCodigoInformeConPrefijoMCYMS(): void {
        // Caso MC Consecutivo
        $filasMC = [
            ['Código laboratorio' => 'MC-0001-26'],
            ['Código laboratorio' => 'MC-0002-26'],
            ['Código laboratorio' => 'MC-0003-26']
        ];
        $codigoMC = generarCodigoInformeEnsayo($filasMC, '2026-09-18');
        $this->assertEquals('CYCSA-INF-MC-0001-0003-26', $codigoMC);

        // Caso MS Consecutivo
        $filasMS = [
            ['Código laboratorio' => 'MS-0010-26'],
            ['Código laboratorio' => 'MS-0011-26'],
            ['Código laboratorio' => 'MS-0012-26']
        ];
        $codigoMS = generarCodigoInformeEnsayo($filasMS, '2026-09-18');
        $this->assertEquals('CYCSA-INF-MS-0010-0012-26', $codigoMS);

        // Caso No Consecutivo MC
        $filasDiscontinuas = [
            ['Código laboratorio' => 'MC-0001-26'],
            ['Código laboratorio' => 'MC-0005-26']
        ];
        $codigoDiscontinuo = generarCodigoInformeEnsayo($filasDiscontinuas, '2026-09-18');
        $this->assertEquals('CYCSA-INF-MC-0001, 0005-26', $codigoDiscontinuo);

        // Caso Muestra Única
        $filaUnica = [
            ['Código laboratorio' => 'MC-0008-26']
        ];
        $codigoUnico = generarCodigoInformeEnsayo($filaUnica, '2026-09-18');
        $this->assertEquals('CYCSA-INF-MC-0008-26', $codigoUnico);
    }

    public function testMatrizAprobadaNoPermiteDevolver(): void {
        $payloadAprobada = [
            'filas' => [['Código laboratorio' => 'MC-0001-26', 'Lectura' => '5000']],
            'revision' => [
                'estado' => 'aprobada',
                'usuario_revisor' => 'Supervisor Calidad',
                'fecha_revision' => '2026-09-18 10:00:00'
            ]
        ];

        $estadoInfo = obtenerEstadoRevisionMatriz(json_encode($payloadAprobada));
        $this->assertEquals('aprobada', $estadoInfo['estado']);
        $this->assertFalse($estadoInfo['puede_devolver'], 'Una matriz aprobada no debe tener permitido ser devuelta.');
        $this->assertTrue($estadoInfo['puede_enviar_cliente']);
        $this->assertFalse($estadoInfo['puede_aprobar']);
    }

    public function testVistaCapturaMatrizOcultaBotonDevolverCuandoEstaAprobada(): void {
        $detalle = [
            'id' => 25,
            'cotizacion_id' => 12,
            'codigo_os' => 'OS-2026-012',
            'codigo_documento' => 'CYCSA-RT-FM-22',
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'descripcion_ensayo' => 'Resistencia a la Compresión de Concreto',
            'norma_astm' => 'ASTM C39',
            'requiere_muestreo' => 1,
            'resultados_json' => json_encode([
                'filas' => [['Código laboratorio' => 'MC-0001-26', 'Carga' => '45000']],
                'metadatos' => ['cliente_nombre' => 'Constructora XYZ'],
                'revision' => [
                    'estado' => 'aprobada',
                    'usuario_revisor' => 'Ing. Noel Quintana'
                ]
            ])
        ];

        $listaVersiones = [];
        $versionActual = 1;
        $versionSolicitada = 1;
        $esHistorica = false;
        $versionActivaInfo = ['version' => 1];
        $muestrasSeteadas = [];
        $prefijoMuestraOS = 'MC';
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown']);
        $columnas = $schemaInfo['columns'];
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] => $schemaInfo]);

        // Simular sesión de supervisor
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_rol'] = 1; // Supervisor
        $_SESSION['csrf_token'] = 'token-test';

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $html = ob_get_clean();

        // El botón modal de devolución no debe existir en matriz aprobada
        $this->assertStringNotContainsString('data-bs-target="#modalDevolverMatriz"', $html);
        $this->assertStringNotContainsString('Devolver Matriz (Observación Técnica)', $html);
        // Debe reflejarse el estado aprobada y el prefijo PREFIJO_OFICIAL = "MC"
        $this->assertStringContainsString('PREFIJO_OFICIAL = "MC"', $html);
    }
}
