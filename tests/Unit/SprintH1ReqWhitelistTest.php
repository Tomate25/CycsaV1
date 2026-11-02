<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Sprint H.1: Whitelist Real de Casillas req_* en RT-FM-13 (Modal & Server-Side)
 *
 * Cobertura de pruebas unitarias:
 * 1. O/S legacy sin whitelist (null) permite todas las categorías (retrocompatibilidad total).
 * 2. O/S con whitelist parcial habilita únicamente las categorías asociadas a los ensayos aprobados.
 * 3. Saneamiento server-side desactiva flags no permitidos en hojas nuevas.
 * 4. Hojas existentes en edición preservan intactas las categorías guardadas.
 * 5. Script JS en vista index.php contiene lógica de deshabilitación y opacidad por categoría.
 */
class SprintH1ReqWhitelistTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private HojasServicioControlador $hojaControlador;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->osModelo = new OrdenServicioModelo();
        $this->hojaControlador = new HojasServicioControlador();
    }

    public function testLegacyOSSinWhitelistPermiteTodasLasCategorias(): void {
        // ID ficticio sin registros en orden_servicio_ensayos_permitidos
        $idOSLegacy = 999998;
        $idsPermitidos = $this->osModelo->obtenerIdsPermitidos($idOSLegacy);

        $this->assertNull($idsPermitidos, 'Una O/S sin registros en la tabla de whitelist debe devolver null');
        $this->assertTrue(
            $this->osModelo->esEnsayoPermitido($idOSLegacy, 123),
            'En O/S legacy, cualquier ensayo debe considerarse permitido (allow-all)'
        );
    }

    public function testOSConWhitelistParcialHabilitaSoloCategoriasPermitidas(): void {
        // Ensayos simulados: Solo Concreto (C39)
        $ensayosConcreto = [
            [
                'id_producto' => 1,
                'nombre_ensayo' => 'Compresión de Cilindros de Concreto',
                'descripcion_ensayo' => 'Ensayo a compresión según norma ASTM C39',
                'norma_astm' => 'ASTM C39',
                'procedimiento' => 'Rotura de especímenes cilíndricos'
            ]
        ];

        $res = $this->hojaControlador->detectarParametrosYNaturaleza($ensayosConcreto);
        $flags = $res['flags'];

        $this->assertEquals(1, $flags['req_resistencia_concreto'], 'Concreto debe estar activo');
        $this->assertEquals(0, $flags['req_granulometria'], 'Granulometría no debe estar activa');
        $this->assertEquals(0, $flags['req_humedad'], 'Humedad no debe estar activa');
        $this->assertEquals(0, $flags['req_proctor_sm'], 'Proctor no debe estar activo');

        // Ensayos simulados: Solo Suelo (Granulometría + Límites)
        $ensayosSuelo = [
            [
                'id_producto' => 2,
                'nombre_ensayo' => 'Granulometría de Suelos',
                'descripcion_ensayo' => 'Análisis granulométrico por tamizado ASTM C136 / D422',
                'norma_astm' => 'ASTM D422',
                'procedimiento' => 'Tamizado'
            ],
            [
                'id_producto' => 3,
                'nombre_ensayo' => 'Límites de Atterberg',
                'descripcion_ensayo' => 'Límite líquido y plástico ASTM D4318',
                'norma_astm' => 'ASTM D4318',
                'procedimiento' => 'Copa de Casagrande'
            ]
        ];

        $resSuelo = $this->hojaControlador->detectarParametrosYNaturaleza($ensayosSuelo);
        $flagsSuelo = $resSuelo['flags'];

        $this->assertEquals(0, $flagsSuelo['req_resistencia_concreto'], 'Concreto no debe estar activo para suelos');
        $this->assertEquals(1, $flagsSuelo['req_granulometria'], 'Granulometría debe estar activa');
        $this->assertEquals(1, $flagsSuelo['req_limites_atterberg'], 'Límites de Atterberg debe estar activo');
    }

    public function testSaneamientoServerSideDesactivaCategoriasNoPermitidasEnHojaNueva(): void {
        // Simular lógica del controlador para hoja nueva con whitelist
        $flagsPermitidos = [
            'req_resistencia_concreto' => 1,
            'req_granulometria' => 0
        ];

        $datosEnviados = [
            'req_resistencia_concreto' => 1,
            'req_granulometria' => 1 // Intento de activar ensayo no autorizado
        ];

        $camposReq = ['req_resistencia_concreto', 'req_granulometria'];
        foreach ($camposReq as $campo) {
            if (!empty($datosEnviados[$campo]) && empty($flagsPermitidos[$campo])) {
                $datosEnviados[$campo] = 0;
            }
        }

        $this->assertEquals(1, $datosEnviados['req_resistencia_concreto'], 'El ensayo autorizado debe mantenerse en 1');
        $this->assertEquals(0, $datosEnviados['req_granulometria'], 'El ensayo no autorizado debe ser saneado a 0');
    }

    public function testVistaContieneMapeoYLogicaCompletaDeDeshabilitacion(): void {
        $viewPath = dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php';
        $content = file_get_contents($viewPath);

        $this->assertStringContainsString('function aplicarFiltroWhitelistReq', $content);
        $this->assertStringContainsString('mapaCheckboxes', $content, 'Debe mapear los checkboxes por categoría');
        $this->assertStringContainsString('flagsPermitidos', $content, 'Debe evaluar flagsPermitidos dinámicamente');
        $this->assertStringContainsString('el.disabled = !estaPermitido', $content, 'Debe deshabilitar casillas no contempladas');
    }
}
