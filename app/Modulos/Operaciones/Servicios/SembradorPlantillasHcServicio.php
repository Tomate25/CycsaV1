<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Configuracion\Modelos\EquipoCalibracionModelo;
use PDO;

/**
 * Servicio idempotente para sembrar en base de datos el catálogo de variables, unidades,
 * equipos de calibración, correcciones parametrizables y plantillas dinámicas (RT-FM-25 al RT-FM-64)
 * a partir de los JSON canónicos en docs/hojas_calculo/.
 *
 * Garantiza cambios estrictamente aditivos: no altera ni elimina las 21 plantillas legacy RT-FM-22.
 */
class SembradorPlantillasHcServicio {

    private PDO $db;
    private string $rutaBaseHc;
    private EquipoCalibracionModelo $equipoModelo;

    public function __construct(?PDO $db = null, ?string $rutaBaseHc = null) {
        $this->db = $db ?? Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->rutaBaseHc = $rutaBaseHc ?? dirname(__DIR__, 4) . '/docs/hojas_calculo';
        $this->equipoModelo = new EquipoCalibracionModelo($this->db);
    }

    /**
     * Extrae la cantidad de decimales de una cadena de redondeo como "ROUND(x,2)" o un entero.
     */
    public static function extraerDecimalesRedondeo(mixed $redondeo, int $default = 2): int {
        if ($redondeo === null || $redondeo === '') {
            return $default;
        }
        if (is_int($redondeo) || is_numeric($redondeo)) {
            return (int)$redondeo;
        }
        if (is_string($redondeo) && preg_match('/ROUND\s*\(\s*x\s*,\s*(\d+)\s*\)/i', $redondeo, $m)) {
            return (int)$m[1];
        }
        return $default;
    }

    /**
     * Siembra las unidades de medida y el catálogo de variables comunes (variables_comunes.json).
     */
    public function sembrarCatalogoVariablesComunes(): array {
        $rutaVars = $this->rutaBaseHc . '/catalogos/variables_comunes.json';
        if (!file_exists($rutaVars)) {
            return ['unidades_creadas' => 0, 'variables_creadas' => 0];
        }

        $data = json_decode(file_get_contents($rutaVars), true) ?: [];
        $variables = $data['variables'] ?? [];

        $unidadesCreadas = 0;
        $variablesCreadas = 0;

        foreach ($variables as $v) {
            $unidadSimbolo = isset($v['unidad']) ? trim((string)$v['unidad']) : '';
            $unidadId = null;
            if ($unidadSimbolo !== '') {
                $unidadId = $this->asegurarUnidad($unidadSimbolo, $unidadesCreadas);
            }

            if ($this->asegurarVariable(
                $v['codigo'],
                $v['etiqueta'] ?? $v['codigo'],
                $v['tipo'] ?? 'numero',
                $unidadId,
                isset($v['decimales']) ? (int)$v['decimales'] : 2,
                null,
                true
            )) {
                $variablesCreadas++;
            }
        }

        return [
            'unidades_creadas' => $unidadesCreadas,
            'variables_creadas' => $variablesCreadas
        ];
    }

    /**
     * Siembra el catálogo completo de Equipos y Calibración desde catalogos/equipos_calibracion.json.
     */
    public function sembrarEquiposCalibracion(): array {
        $rutaEq = $this->rutaBaseHc . '/catalogos/equipos_calibracion.json';
        if (!file_exists($rutaEq)) {
            return ['equipos' => 0, 'constantes' => 0, 'picnometros' => 0];
        }

        $cat = json_decode(file_get_contents($rutaEq), true) ?: [];
        $equiposCount = 0;
        $constantesCount = 0;
        $picnometrosCount = 0;

        // 1. Sembrar los 12 equipos de catalogos/equipos_calibracion.json -> "equipos"
        foreach ($cat['equipos'] ?? [] as $eq) {
            $codigo = $eq['codigo'];
            $eqId = $this->asegurarEquipo([
                'codigo' => $codigo,
                'nombre' => $eq['descripcion'] ?? $codigo,
                'categoria' => $eq['tipo'] ?? 'general',
                'marca_modelo' => $eq['certificado'] ?? ($eq['fuente'] ?? 'Estándar CYCSA'),
                'norma' => $eq['fuente'] ?? 'ISO/IEC 17025',
                'usado_en' => [$eq['fuente'] ?? ''],
                'metadatos' => $eq
            ]);
            $equiposCount++;

            // Sembrar constantes específicas según el equipo
            if ($codigo === 'PRENSA-01' && !empty($eq['constantes'])) {
                $mapaPrensa = [
                    'prensa_a_e14' => (float)($eq['constantes']['a_e14'] ?? -4.6765),
                    'prensa_b_e8'  => (float)($eq['constantes']['b_e8'] ?? 2.5071),
                    'prensa_c'     => (float)($eq['constantes']['c'] ?? 0.99583),
                    'prensa_d'     => (float)($eq['constantes']['d'] ?? 3.7217),
                ];
                foreach ($mapaPrensa as $clave => $val) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        $clave,
                        $val,
                        null,
                        "PRENSA-01.{$clave}",
                        $clave === 'prensa_d' ? 'lbf' : 'adimensional',
                        "Coeficiente {$clave} del polinomio de calibración de PRENSA-01"
                    );
                    $constantesCount++;
                }
            } elseif ($codigo === 'FACTOR-LD-C39') {
                $mapaLd = [
                    'ld_a' => (float)($eq['constantes']['a'] ?? 0.2133),
                    'ld_b' => (float)($eq['constantes']['b'] ?? -1.04),
                    'ld_c' => (float)($eq['constantes']['c'] ?? 1.7667),
                    'ld_d' => (float)($eq['constantes']['d'] ?? -0.07),
                ];
                foreach ($mapaLd as $clave => $val) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        $clave,
                        $val,
                        null,
                        "FACTOR-LD-C39.{$clave}",
                        'adimensional',
                        "Coeficiente {$clave} del polinomio de esbeltez L/D ASTM C39"
                    );
                    $constantesCount++;
                }
                if (!empty($eq['tabla'])) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        'tabla_ld',
                        1.0,
                        $eq['tabla'],
                        'equipos_calibracion.factor_correccion_ld_astm_c39',
                        'adimensional',
                        'Tabla normativa ASTM C39 de factor de corrección L/D'
                    );
                    $constantesCount++;
                }
            } elseif ($codigo === 'ESCL-01') {
                $posA = $eq['coeficientes_por_posicion']['A'] ?? [];
                $mapaEscl = [
                    'coef_a'             => (float)($posA['a'] ?? -0.0431),
                    'coef_b'             => (float)($posA['b'] ?? 5.8982),
                    'coef_c'             => (float)($posA['c'] ?? -51.093),
                    'coef_d'             => (float)($posA['d'] ?? 319.54),
                    'factor_calibracion' => (float)($eq['factor_calibracion'] ?? 0.976),
                ];
                foreach ($mapaEscl as $clave => $val) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        $clave,
                        $val,
                        $eq['coeficientes_por_posicion'] ?? null,
                        "ESCL-01.{$clave}",
                        'adimensional',
                        "Constante {$clave} del esclerómetro ESCL-01"
                    );
                    $constantesCount++;
                }
            } elseif ($codigo === 'MOLDE-PROCTOR-4IN' && isset($eq['volumen_ml'])) {
                $this->equipoModelo->guardarConstanteEquipo(
                    $eqId,
                    'volumen_molde',
                    (float)$eq['volumen_ml'],
                    null,
                    'MOLDE-PROCTOR-4IN.volumen_molde',
                    'cm³',
                    'Volumen calibrado del molde Proctor de 4 pulgadas'
                );
                $constantesCount++;
            } elseif ($codigo === 'MOLDE-CBR-6IN') {
                if (isset($eq['volumen_cm3'])) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        'volumen_cm3',
                        (float)$eq['volumen_cm3'],
                        null,
                        'MOLDE-CBR-6IN.volumen_cm3',
                        'cm³',
                        'Volumen del molde CBR'
                    );
                    $constantesCount++;
                }
                if (isset($eq['masa_g'])) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        'masa_g',
                        (float)$eq['masa_g'],
                        null,
                        'MOLDE-CBR-6IN.masa_g',
                        'g',
                        'Masa del molde CBR'
                    );
                    $constantesCount++;
                }
            } elseif ($codigo === 'ANILLO-CBR-01') {
                if (isset($eq['constante_lbf_por_div'])) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        'constante_anillo',
                        (float)$eq['constante_lbf_por_div'],
                        null,
                        'ANILLO-CBR-01.constante_anillo',
                        'lbf/div',
                        'Constante del anillo de carga CBR'
                    );
                    $constantesCount++;
                }
                if (isset($eq['area_piston_in2'])) {
                    $this->equipoModelo->guardarConstanteEquipo(
                        $eqId,
                        'area_piston',
                        (float)$eq['area_piston_in2'],
                        null,
                        'ANILLO-CBR-01.area_piston',
                        'in²',
                        'Área del pistón de penetración CBR'
                    );
                    $constantesCount++;
                }
            } elseif ($codigo === 'ARENA-CONO-01' && isset($eq['peso_unitario_kgm3'])) {
                $this->equipoModelo->guardarConstanteEquipo(
                    $eqId,
                    'pu_arena',
                    (float)$eq['peso_unitario_kgm3'],
                    null,
                    'ARENA-CONO-01.pu_arena',
                    'kg/m³',
                    'Peso unitario de la arena calibrada para cono de arena'
                );
                $constantesCount++;
            } elseif ($codigo === 'ANILLO-RA-01' && isset($eq['diametro_m'])) {
                $this->equipoModelo->guardarConstanteEquipo(
                    $eqId,
                    'anillo',
                    (float)$eq['diametro_m'],
                    null,
                    'ANILLO-RA-01.anillo',
                    'm',
                    'Diámetro del anillo para reemplazo de agua'
                );
                $constantesCount++;
            } elseif ($codigo === 'BAL-EQ-6566' && isset($eq['u_combinada_g'])) {
                $this->equipoModelo->guardarConstanteEquipo(
                    $eqId,
                    'u_combinada_g',
                    (float)$eq['u_combinada_g'],
                    $eq,
                    'BAL-EQ-6566.balanza_humedad',
                    'g',
                    'Incertidumbre combinada de balanza de humedad BAL-EQ-6566'
                );
                $constantesCount++;
            }
        }

        // 2. Sembrar Picnómetros (RT-FM-56: PICNO-02 a PICNO-10)
        foreach ($cat['picnometros']['balones'] ?? [] as $balon) {
            $this->equipoModelo->guardarPicnometroCalibrado([
                'identificacion' => $balon['codigo'],
                'volumen_calibrado_cm3' => (float)($balon['volumen_prom_ml'] ?? 500.0),
                'masa_picnometro_vacio_g' => (float)($balon['masa_vacio_prom_g'] ?? 180.0),
                'coef_expansion_termica_vidrio' => 0.000025,
                'fecha_calibracion' => $balon['fecha_evaluacion'] ?? '2018-01-24',
                'tabla_temperatura_masa' => [
                    [
                        'temp_c' => 24.0,
                        'densidad_agua_g_cm3' => 0.9973,
                        'masa_picnometro_mas_agua_g' => (float)($balon['masa_lleno_prom_g'] ?? 680.0),
                        'volumen_prom_ml' => (float)($balon['volumen_prom_ml'] ?? 500.0),
                        'desv_volumen_ml' => (float)($balon['desv_volumen_ml'] ?? 0.1),
                        'hoja' => $balon['hoja'] ?? '',
                        'codigo_lab' => $balon['codigo_lab'] ?? 'B-02'
                    ]
                ]
            ]);
            $picnometrosCount++;
        }

        return [
            'equipos' => $equiposCount,
            'constantes' => $constantesCount,
            'picnometros' => $picnometrosCount
        ];
    }

    /**
     * Siembra las correcciones de fórmulas (incluyendo las marcadas como "dudosa")
     * para todos los formatos o una lista dada de formatos.
     */
    public function sembrarCorreccionesFormatos(?array $codigosFormatos = null): int {
        if ($codigosFormatos === null) {
            $archivos = glob($this->rutaBaseHc . '/formatos/RT-FM-*.json') ?: [];
            $codigosFormatos = array_map(fn($a) => pathinfo($a, PATHINFO_FILENAME), $archivos);
        }

        $total = 0;
        $stmtCheck = $this->db->prepare("
            SELECT id FROM configuracion_correcciones_hc
            WHERE codigo_formato = :fmt AND correccion_id = :cid
            LIMIT 1
        ");
        $stmtIns = $this->db->prepare("
            INSERT INTO configuracion_correcciones_hc
                (codigo_formato, correccion_id, original_excel, corregido, justificacion, es_dudosa, requiere_confirmacion_lab, usar_corregido, confirmado_calidad)
            VALUES
                (:fmt, :cid, :orig, :corr, :just, :dud, :req, 1, 0)
        ");

        foreach ($codigosFormatos as $codigo) {
            $ruta = $this->rutaBaseHc . '/formatos/' . $codigo . '.json';
            if (!file_exists($ruta)) continue;
            $spec = json_decode(file_get_contents($ruta), true) ?: [];

            foreach ($spec['correcciones'] ?? [] as $c) {
                $stmtCheck->execute(['fmt' => $codigo, 'cid' => $c['id']]);
                if (!$stmtCheck->fetchColumn()) {
                    $stmtIns->execute([
                        'fmt' => $codigo,
                        'cid' => $c['id'],
                        'orig' => $c['original_excel'] ?? '',
                        'corr' => $c['corregido'] ?? '',
                        'just' => $c['justificacion'] ?? '',
                        'dud' => !empty($c['dudosa']) ? 1 : 0,
                        'req' => !empty($c['requiere_confirmacion_laboratorio']) ? 1 : 0,
                    ]);
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * Siembra en las tablas plantillas, plantilla_secciones, plantilla_campos y plantilla_versiones
     * una lista de formatos de hojas de cálculo (ej. ['RT-FM-61']).
     *
     * @param array<string> $codigosFormatos
     * @return array<string, int> Mapa [codigo_formato => plantilla_id]
     */
    public function sembrarPlantillasFormatos(array $codigosFormatos): array {
        $usuarioAdminId = (int)$this->db->query("SELECT id FROM usuarios WHERE id_rol = 1 LIMIT 1")->fetchColumn() ?: 1;
        $ensayoDefaultId = (int)$this->db->query("SELECT id FROM productos LIMIT 1")->fetchColumn() ?: 1;
        $unidadesCreadas = 0;
        $resultado = [];

        foreach ($codigosFormatos as $codigo) {
            $codigo = strtoupper(trim($codigo));
            $ruta = $this->rutaBaseHc . '/formatos/' . $codigo . '.json';
            if (!file_exists($ruta)) {
                continue;
            }

            $spec = json_decode(file_get_contents($ruta), true);
            if (!is_array($spec)) {
                continue;
            }

            // Sembrar también sus correcciones
            $this->sembrarCorreccionesFormatos([$codigo]);

            // Buscar producto/ensayo asociado o usar default
            $ensayoId = $this->buscarProductoAsociado($codigo, $spec['nombre'] ?? '', $ensayoDefaultId);
            $normaTxt = $spec['norma']['texto'] ?? 'ASTM';
            $codigoPlantilla = 'HC-CYCSA-' . $codigo;

            // Verificar si ya existe la plantilla
            $stmtPlt = $this->db->prepare("SELECT id FROM plantillas WHERE codigo = :cod LIMIT 1");
            $stmtPlt->execute(['cod' => $codigoPlantilla]);
            $plantillaId = (int)$stmtPlt->fetchColumn();

            if ($plantillaId <= 0) {
                $stmtInsPlt = $this->db->prepare("
                    INSERT INTO plantillas (
                        codigo, nombre, ensayo_id, norma, procedimiento_interno,
                        orientacion, fondo_reporte_id, incluir_logo_cycsa, incluir_sello_acreditacion,
                        sello_acreditacion_url, cliente_id, estado, version_actual, es_por_defecto,
                        creado_por, aprobado_por, fecha_aprobacion, notas_version
                    ) VALUES (
                        :cod, :nom, :eid, :norma, :proc,
                        'horizontal', 'hoja_membretada_horizontal.jpg', 1, 1,
                        'publico/img/sello_acreditacion_default.jpeg', NULL, 'aprobada', 'Rev. 01', 0,
                        :creado, :aprob, NOW(), :notas
                    )
                ");
                $stmtInsPlt->execute([
                    'cod' => $codigoPlantilla,
                    'nom' => 'Hoja de Cálculo ' . $codigo . ' — ' . ($spec['nombre'] ?? $codigo),
                    'eid' => $ensayoId,
                    'norma' => $normaTxt,
                    'proc' => 'CYCSA-' . $codigo,
                    'creado' => $usuarioAdminId,
                    'aprob' => $usuarioAdminId,
                    'notas' => 'Plantilla de Hoja de Cálculo oficial sembrada desde especificación ' . $codigo
                ]);
                $plantillaId = (int)$this->db->lastInsertId();
            }

            // Reconstruir secciones y campos de la plantilla para reflejar íntegramente el JSON
            $this->db->prepare("DELETE FROM plantilla_campos WHERE plantilla_id = :pid")->execute(['pid' => $plantillaId]);
            $this->db->prepare("DELETE FROM plantilla_secciones WHERE plantilla_id = :pid")->execute(['pid' => $plantillaId]);

            $stmtSec = $this->db->prepare("
                INSERT INTO plantilla_secciones (plantilla_id, nombre, orden, tipo_layout)
                VALUES (:pid, :nom, :ord, :layout)
            ");
            $stmtCmp = $this->db->prepare("
                INSERT INTO plantilla_campos (
                    plantilla_id, seccion_id, variable_id, orden, fila, columna, ancho_cols,
                    formula, visible_en_reporte, solo_lectura, texto_ayuda
                ) VALUES (
                    :pid, :sid, :vid, :ord, :fila, :col, :ancho,
                    :formula, :vis, :ro, :ayuda
                )
            ");

            // Mapa de fórmulas por código de variable
            $mapaFormulas = [];
            foreach ($spec['formulas'] ?? [] as $f) {
                $mapaFormulas[$f['codigo']] = $f;
            }

            $ordenSec = 1;

            // Sección 1: Encabezado (si tiene campos)
            if (!empty($spec['encabezado'])) {
                $stmtSec->execute([
                    'pid' => $plantillaId,
                    'nom' => 'Encabezado y Datos Generales',
                    'ord' => $ordenSec++,
                    'layout' => 'formulario'
                ]);
                $secEncId = (int)$this->db->lastInsertId();

                $ordCmp = 1;
                foreach ($spec['encabezado'] as $campo) {
                    $varId = $this->asegurarVariableDesdeCampo($campo, $unidadesCreadas);
                    $fDef = $mapaFormulas[$campo['codigo']] ?? null;
                    $stmtCmp->execute([
                        'pid' => $plantillaId,
                        'sid' => $secEncId,
                        'vid' => $varId,
                        'ord' => $ordCmp,
                        'fila' => (int)ceil($ordCmp / 3),
                        'col' => (($ordCmp - 1) % 3) + 1,
                        'ancho' => 4,
                        'formula' => $fDef['expresion'] ?? null,
                        'vis' => 1,
                        'ro' => ($campo['tipo'] ?? '') === 'calculado' || $fDef !== null ? 1 : 0,
                        'ayuda' => $campo['texto_ayuda'] ?? ($fDef['descripcion'] ?? null)
                    ]);
                    $ordCmp++;
                }
            }

            // Secciones de captura y resultados
            $camposYaIncluidos = [];
            foreach ($spec['encabezado'] ?? [] as $c) {
                $camposYaIncluidos[$c['codigo']] = true;
            }

            foreach ($spec['secciones'] ?? [] as $sec) {
                $layout = in_array($sec['tipo_layout'] ?? '', ['formulario', 'tabla_matriz'], true) ? $sec['tipo_layout'] : 'tabla_matriz';
                $stmtSec->execute([
                    'pid' => $plantillaId,
                    'nom' => $sec['nombre'] ?? ('Sección ' . $ordenSec),
                    'ord' => $ordenSec++,
                    'layout' => $layout
                ]);
                $secId = (int)$this->db->lastInsertId();

                $ordCmp = 1;
                foreach ($sec['campos'] ?? [] as $campo) {
                    $varId = $this->asegurarVariableDesdeCampo($campo, $unidadesCreadas);
                    $fDef = $mapaFormulas[$campo['codigo']] ?? null;
                    $stmtCmp->execute([
                        'pid' => $plantillaId,
                        'sid' => $secId,
                        'vid' => $varId,
                        'ord' => $ordCmp,
                        'fila' => 1,
                        'col' => $ordCmp,
                        'ancho' => 3,
                        'formula' => $fDef['expresion'] ?? null,
                        'vis' => 1,
                        'ro' => ($campo['tipo'] ?? '') === 'calculado' || $fDef !== null ? 1 : 0,
                        'ayuda' => $campo['texto_ayuda'] ?? ($fDef['descripcion'] ?? null)
                    ]);
                    $camposYaIncluidos[$campo['codigo']] = true;
                    $ordCmp++;
                }
            }

            // Asegurar que todas las fórmulas declaradas en spec['formulas'] tengan su variable creada y estén en una sección de Cálculos si no estaban ya
            $formulasFaltantes = [];
            foreach ($spec['formulas'] ?? [] as $f) {
                if (empty($camposYaIncluidos[$f['codigo']])) {
                    $formulasFaltantes[] = $f;
                }
            }

            if (!empty($formulasFaltantes)) {
                $stmtSec->execute([
                    'pid' => $plantillaId,
                    'nom' => 'Variables Calculadas y Conversiones',
                    'ord' => $ordenSec++,
                    'layout' => 'tabla_matriz'
                ]);
                $secCalcId = (int)$this->db->lastInsertId();
                $ordCmp = 1;
                foreach ($formulasFaltantes as $f) {
                    $unidadId = !empty($f['unidad']) ? $this->asegurarUnidad($f['unidad'], $unidadesCreadas) : null;
                    $decimales = self::extraerDecimalesRedondeo($f['redondeo'] ?? null, 2);
                    $this->asegurarVariable(
                        $f['codigo'],
                        $f['descripcion'] ?? $f['codigo'],
                        'calculado',
                        $unidadId,
                        $decimales,
                        null,
                        false
                    );
                    $varId = $this->obtenerIdVariable($f['codigo']);
                    $stmtCmp->execute([
                        'pid' => $plantillaId,
                        'sid' => $secCalcId,
                        'vid' => $varId,
                        'ord' => $ordCmp,
                        'fila' => 1,
                        'col' => $ordCmp,
                        'ancho' => 3,
                        'formula' => $f['expresion'],
                        'vis' => 1,
                        'ro' => 1,
                        'ayuda' => $f['descripcion'] ?? null
                    ]);
                    $ordCmp++;
                }
            }

            // Asegurar versión aprobada en plantilla_versiones con el JSON completo
            $stmtVerCheck = $this->db->prepare("SELECT id FROM plantilla_versiones WHERE plantilla_id = :pid LIMIT 1");
            $stmtVerCheck->execute(['pid' => $plantillaId]);
            $verId = (int)$stmtVerCheck->fetchColumn();

            $esquemaJson = json_encode($spec, JSON_UNESCAPED_UNICODE);
            $legacyJson = json_encode([
                'codigo_formato' => 'CYCSA-' . $codigo,
                'ensayo_titulo' => $spec['nombre'] ?? $codigo,
                'norma' => $normaTxt,
                'alimenta_rt_fm_22' => $spec['formato_informe_rt_fm_22'] ?? null,
                'modulo_destino' => $spec['modulo_destino'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            $hash = hash('sha256', $esquemaJson);

            if ($verId <= 0) {
                $stmtInsVer = $this->db->prepare("
                    INSERT INTO plantilla_versiones (
                        plantilla_id, version, esquema_json, formato_legacy_json, hash_integridad,
                        aprobado_por, fecha_aprobacion, motivo_version
                    ) VALUES (
                        :pid, 'Rev. 01', :esq, :leg, :hash, :aprob, NOW(), :motivo
                    )
                ");
                $stmtInsVer->execute([
                    'pid' => $plantillaId,
                    'esq' => $esquemaJson,
                    'leg' => $legacyJson,
                    'hash' => $hash,
                    'aprob' => $usuarioAdminId,
                    'motivo' => 'Sembrado canónico de Hoja de Cálculo ' . $codigo
                ]);
            } else {
                $stmtUpdVer = $this->db->prepare("
                    UPDATE plantilla_versiones
                    SET esquema_json = :esq, formato_legacy_json = :leg, hash_integridad = :hash
                    WHERE id = :id
                ");
                $stmtUpdVer->execute([
                    'esq' => $esquemaJson,
                    'leg' => $legacyJson,
                    'hash' => $hash,
                    'id' => $verId
                ]);
            }

            $resultado[$codigo] = $plantillaId;
        }

        return $resultado;
    }

    private function asegurarUnidad(string $simbolo, int &$contadorCreadas): int {
        $stmt = $this->db->prepare("SELECT id FROM unidades_medida WHERE simbolo = :sim LIMIT 1");
        $stmt->execute(['sim' => $simbolo]);
        $id = (int)$stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $stmtIns = $this->db->prepare("
            INSERT INTO unidades_medida (nombre, simbolo, magnitud, activo)
            VALUES (:nom, :sim, 'Laboratorio', 1)
        ");
        $stmtIns->execute(['nom' => $simbolo, 'sim' => $simbolo]);
        $contadorCreadas++;
        return (int)$this->db->lastInsertId();
    }

    private function asegurarVariable(
        string $codigo,
        string $etiqueta,
        string $tipo,
        ?int $unidadId,
        int $decimales = 2,
        ?array $opcionesLista = null,
        bool $requerido = false
    ): bool {
        $stmt = $this->db->prepare("SELECT id FROM variables WHERE codigo = :cod LIMIT 1");
        $stmt->execute(['cod' => substr($codigo, 0, 60)]);
        if ($stmt->fetchColumn()) {
            return false;
        }

        $tipoValido = in_array($tipo, ['numero', 'texto', 'fecha', 'lista', 'calculado', 'tabla'], true) ? $tipo : 'numero';
        $opcJson = !empty($opcionesLista) ? json_encode($opcionesLista, JSON_UNESCAPED_UNICODE) : null;

        $stmtIns = $this->db->prepare("
            INSERT INTO variables (codigo, etiqueta, tipo, unidad_id, decimales, opciones_lista, requerido, activo)
            VALUES (:cod, :eti, :tip, :uni, :dec, :opc, :req, 1)
        ");
        $stmtIns->execute([
            'cod' => substr($codigo, 0, 60),
            'eti' => mb_substr($etiqueta, 0, 150),
            'tip' => $tipoValido,
            'uni' => $unidadId,
            'dec' => $decimales,
            'opc' => $opcJson,
            'req' => $requerido ? 1 : 0
        ]);
        return true;
    }

    private function asegurarVariableDesdeCampo(array $campo, int &$unidadesCreadas): int {
        $codigo = $campo['codigo'];
        $unidadId = null;
        if (!empty($campo['unidad'])) {
            $unidadId = $this->asegurarUnidad(trim((string)$campo['unidad']), $unidadesCreadas);
        }

        $this->asegurarVariable(
            $codigo,
            $campo['etiqueta'] ?? $codigo,
            $campo['tipo'] ?? 'numero',
            $unidadId,
            isset($campo['decimales']) && $campo['decimales'] !== null ? (int)$campo['decimales'] : 2,
            isset($campo['opciones_lista']) && is_array($campo['opciones_lista']) ? $campo['opciones_lista'] : null,
            !empty($campo['requerido'])
        );

        return $this->obtenerIdVariable($codigo);
    }

    private function obtenerIdVariable(string $codigo): int {
        $stmt = $this->db->prepare("SELECT id FROM variables WHERE codigo = :cod LIMIT 1");
        $stmt->execute(['cod' => substr($codigo, 0, 60)]);
        return (int)$stmt->fetchColumn();
    }

    private function asegurarEquipo(array $datos): int {
        $stmt = $this->db->prepare("SELECT id FROM equipos_calibracion WHERE codigo = :cod LIMIT 1");
        $stmt->execute(['cod' => $datos['codigo']]);
        $id = (int)$stmt->fetchColumn();

        $usadoJson = json_encode($datos['usado_en'] ?? [], JSON_UNESCAPED_UNICODE);
        $metaJson = json_encode($datos['metadatos'] ?? [], JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            $stmtUpd = $this->db->prepare("
                UPDATE equipos_calibracion
                SET nombre = :nom, categoria = :cat, marca_modelo = :marca, norma = :norma,
                    usado_en_json = :usado, metadatos_json = :meta
                WHERE id = :id
            ");
            $stmtUpd->execute([
                'nom' => $datos['nombre'],
                'cat' => $datos['categoria'],
                'marca' => $datos['marca_modelo'] ?? null,
                'norma' => $datos['norma'] ?? null,
                'usado' => $usadoJson,
                'meta' => $metaJson,
                'id' => $id
            ]);
            return $id;
        }

        $stmtIns = $this->db->prepare("
            INSERT INTO equipos_calibracion
                (codigo, nombre, categoria, marca_modelo, norma, usado_en_json, metadatos_json, vigente_desde, activo)
            VALUES
                (:cod, :nom, :cat, :marca, :norma, :usado, :meta, CURRENT_DATE, 1)
        ");
        $stmtIns->execute([
            'cod' => $datos['codigo'],
            'nom' => $datos['nombre'],
            'cat' => $datos['categoria'],
            'marca' => $datos['marca_modelo'] ?? null,
            'norma' => $datos['norma'] ?? null,
            'usado' => $usadoJson,
            'meta' => $metaJson
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function buscarProductoAsociado(string $codigoHc, string $nombreHc, int $fallbackId): int {
        $mapaKeywords = [
            'RT-FM-61' => 'Cilindros de Concreto',
            'RT-FM-36' => 'Bloques',
            'RT-FM-38' => 'Esclerometr',
            'RT-FM-57' => 'Mortero',
            'RT-FM-63' => 'Núcleos',
            'RT-FM-64' => 'Acero',
            'RT-FM-54' => 'Densímetro',
            'RT-FM-55' => 'Cono y Arena',
            'RT-FM-62' => 'Infiltración',
            'RT-FM-27' => 'Proctor',
            'RT-FM-25' => 'Granulometr',
            'RT-FM-42' => 'Agregado Fino',
            'RT-FM-35' => 'Gravedad Específica',
            'RT-FM-46' => 'CBR',
            'RT-FM-58' => 'Sanidad',
            'RT-FM-59' => 'Abrasión',
        ];

        $kw = $mapaKeywords[$codigoHc] ?? $nombreHc;
        $stmt = $this->db->prepare("SELECT id FROM productos WHERE ensayo_servicio LIKE :kw OR nombre_comercial LIKE :kw2 LIMIT 1");
        $stmt->execute(['kw' => '%' . $kw . '%', 'kw2' => '%' . $kw . '%']);
        $id = (int)$stmt->fetchColumn();
        return $id > 0 ? $id : $fallbackId;
    }
}
