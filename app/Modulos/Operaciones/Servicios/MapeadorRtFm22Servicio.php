<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\Conexion;
use InvalidArgumentException;
use PDO;

/**
 * Servicio de Mapeo Bidireccional entre las 17 Hojas de Cálculo (RT-FM-25 al RT-FM-64)
 * y los 21 formatos de informes oficiales RT-FM-22 (Letras A a G, preservando H intacto),
 * más la generación de estructuras de informe para los 5 formatos nuevos sin equivalente RT-FM-22:
 * RT-FM-46 (CBR), RT-FM-56 (Picnómetros), RT-FM-58 (Sanidad), RT-FM-62 (Infiltración) y RT-FM-64 (Acero).
 */
class MapeadorRtFm22Servicio {

    public const FORMATOS_REPORTE_NUEVO = [
        'RT-FM-46',
        'RT-FM-56',
        'RT-FM-58',
        'RT-FM-62',
        'RT-FM-64',
    ];

    private MotorHojasCalculoServicio $motor;

    /**
     * Mapa canónico de formatos HC -> archivo markdown de formatos_schema.json y letra RT-FM-22.
     */
    private const MAPA_DESTINOS_RT_FM_22 = [
        'RT-FM-61' => [
            'letra' => 'A',
            'nuevo' => false,
            'archivo_markdown' => 'resistencia_de_concreto.md',
            'alternativos' => ['resistencia_de_nucleo_de_concreto.md', 'formato_de_lodo_concreto.md'],
        ],
        'RT-FM-36' => [
            'letra' => 'A',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_resistencia_de_bloques.md',
        ],
        'RT-FM-57' => [
            'letra' => 'A',
            'nuevo' => false,
            'archivo_markdown' => 'resistencia_de_mortero.md',
        ],
        'RT-FM-63' => [
            'letra' => 'A',
            'nuevo' => false,
            'archivo_markdown' => 'resistencia_de_adoquines.md',
        ],
        'RT-FM-54' => [
            'letra' => 'B',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md',
        ],
        'RT-FM-55' => [
            'letra' => 'B',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_compactacion_por_cono_de_arena.md',
        ],
        'RT-FM-27' => [
            'letra' => 'C',
            'nuevo' => false,
            'archivo_markdown' => 'proctor_estandar.md',
        ],
        'RT-FM-59' => [
            'letra' => 'D',
            'nuevo' => false,
            'archivo_markdown' => 'resistencia_de_martillo_suizo.md',
        ],
        'RT-FM-38' => [
            'letra' => 'E',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_resistencia_de_a_la_flexion.md',
        ],
        'RT-FM-25' => [
            'letra' => 'F',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_granulometria_de_suelo.md',
            'alternativos' => ['granulomnetria_de_agregados.md'],
        ],
        'RT-FM-42' => [
            'letra' => 'F',
            'nuevo' => false,
            'archivo_markdown' => 'formato_de_granulometria_de_suelo.md',
        ],
        'RT-FM-35' => [
            'letra' => 'G',
            'nuevo' => false,
            'archivo_markdown' => 'ensayos_varios.md',
        ],
        // 5 formatos sin RT-FM-22 equivalente (generan reporte nuevo propio; con letra sugerida Fase 3)
        'RT-FM-46' => [
            'letra' => null,
            'letra_rt_fm_22_sugerida' => 'H',
            'nuevo' => true,
            'codigo_reporte_nuevo' => 'CYCSA-RT-FM-22-CBR',
            'titulo_reporte' => 'INFORME DE ENSAYO DE RELACIÓN DE SOPORTE CALIFORNIA (CBR)',
        ],
        'RT-FM-56' => [
            'letra' => null,
            'letra_rt_fm_22_sugerida' => null,
            'nuevo' => true,
            'codigo_reporte_nuevo' => 'CYCSA-RT-FM-56-CERT',
            'titulo_reporte' => 'CERTIFICADO INTERNO DE CALIBRACIÓN VOLUMÉTRICA DE PICNÓMETROS',
        ],
        'RT-FM-58' => [
            'letra' => null,
            'letra_rt_fm_22_sugerida' => 'F',
            'nuevo' => true,
            'codigo_reporte_nuevo' => 'CYCSA-RT-FM-22-SAN',
            'titulo_reporte' => 'INFORME DE SANIDAD DE AGREGADOS POR INTEMPERISMO ACELERADO (ASTM C88)',
        ],
        'RT-FM-62' => [
            'letra' => null,
            'letra_rt_fm_22_sugerida' => null,
            'nuevo' => true,
            'codigo_reporte_nuevo' => 'CYCSA-RT-FM-22-INF',
            'titulo_reporte' => 'INFORME DE PRUEBA DE INFILTRACIÓN / PERCOLACIÓN DE SUELO',
        ],
        'RT-FM-64' => [
            'letra' => null,
            'letra_rt_fm_22_sugerida' => 'H',
            'nuevo' => true,
            'codigo_reporte_nuevo' => 'CYCSA-RT-FM-22-ACE',
            'titulo_reporte' => 'INFORME DE ENSAYO DE TENSIÓN EN VARILLAS DE ACERO DE REFUERZO (ASTM A370 / A615)',
        ],
    ];

    public function __construct(?MotorHojasCalculoServicio $motor = null) {
        $this->motor = $motor ?? new MotorHojasCalculoServicio();
    }

    /**
     * Obtiene la definición de destino RT-FM-22 (o reporte nuevo) para cualquiera de los 17 formatos HC.
     */
    public function obtenerDestinoFormato(string $codigoFormato): array {
        $cod = strtoupper(trim($codigoFormato));
        if (!isset(self::MAPA_DESTINOS_RT_FM_22[$cod])) {
            throw new InvalidArgumentException("El formato {$cod} no está registrado en el mapa RT-FM-22.");
        }
        return self::MAPA_DESTINOS_RT_FM_22[$cod];
    }

    /**
     * Valida estrictamente si un código de formato corresponde a uno de los 5 reportes nuevos permitidos.
     */
    public function esFormatoReporteNuevoValido(string $codigoFormato): bool {
        $cod = strtoupper(trim($codigoFormato));
        return in_array($cod, self::FORMATOS_REPORTE_NUEVO, true);
    }

    /**
     * Carga y evalúa los datos reales de base de datos para cualquiera de los 5 formatos de reporte nuevo
     * (RT-FM-46, RT-FM-56, RT-FM-58, RT-FM-62, RT-FM-64).
     * Consulta en orden:
     * 1) `hojas_campo_registros` (por id, muestra_id, id_os o codigo_muestra en encabezado)
     * 2) `roturas` + `especimenes` + `recepcion_muestras` (para RT-FM-64 u otros ensayos con datos_crudos)
     * 3) `equipos_calibracion_picnometros` (para RT-FM-56 certificados volumétricos reales)
     * 4) `cotizacion_detalles` (por id_detalle con resultados_json real)
     *
     * Retorna null si no existe ningún registro real que coincida (nunca imprime datos demo).
     */
    public function construirReporteNuevoDesdeBaseDatos(string $codigoFormato, array $filtros = [], ?PDO $db = null): ?array {
        $cod = strtoupper(trim($codigoFormato));
        if (!$this->esFormatoReporteNuevoValido($cod)) {
            throw new InvalidArgumentException("El formato '{$cod}' no es un formato de reporte nuevo válido.");
        }

        $pdo = $db ?? Conexion::obtenerInstancia();
        $registroId = (int)($filtros['registro_id'] ?? ($filtros['id'] ?? 0));
        $muestraId = (int)($filtros['muestra_id'] ?? 0);
        $idOs = (int)($filtros['id_os'] ?? 0);
        $idDetalle = (int)($filtros['id_detalle'] ?? 0);
        $picnometroId = (int)($filtros['picnometro_id'] ?? 0);
        $numeroPicnometro = (int)($filtros['numero_picnometro'] ?? 0);
        $codigoMuestra = trim((string)($filtros['codigo_muestra'] ?? ''));

        // 1. Buscar en hojas_campo_registros
        $sqlHc = "SELECT hcr.*, rm.codigo_muestra AS codigo_muestra_db
                  FROM hojas_campo_registros hcr
                  LEFT JOIN recepcion_muestras rm ON rm.id = hcr.muestra_id
                  WHERE hcr.codigo_formato = :fmt";
        $paramsHc = ['fmt' => $cod];

        if ($registroId > 0) {
            $sqlHc .= " AND hcr.id = :reg_id";
            $paramsHc['reg_id'] = $registroId;
        }
        if ($muestraId > 0) {
            $sqlHc .= " AND hcr.muestra_id = :mid";
            $paramsHc['mid'] = $muestraId;
        }
        if ($idOs > 0) {
            $sqlHc .= " AND hcr.id_os = :id_os";
            $paramsHc['id_os'] = $idOs;
        }
        if ($codigoMuestra !== '') {
            $sqlHc .= " AND (rm.codigo_muestra = :cod_m OR hcr.datos_encabezado_json LIKE :cod_like)";
            $paramsHc['cod_m'] = $codigoMuestra;
            $paramsHc['cod_like'] = '%' . $codigoMuestra . '%';
        }
        $sqlHc .= " ORDER BY hcr.id DESC LIMIT 1";

        $stmtHc = $pdo->prepare($sqlHc);
        $stmtHc->execute($paramsHc);
        $filaHc = $stmtHc->fetch(PDO::FETCH_ASSOC);

        if ($filaHc) {
            $encabezado = json_decode($filaHc['datos_encabezado_json'] ?? '{}', true) ?: [];
            $puntos = json_decode($filaHc['datos_puntos_json'] ?? '[]', true) ?: [];
            if (!empty($puntos)) {
                $evalConjunto = $this->motor->evaluarConjunto($cod, $puntos, $encabezado);
                $metadatos = array_merge($encabezado, [
                    'registro_id' => (int)$filaHc['id'],
                    'id_os' => (int)$filaHc['id_os'],
                    'muestra_id' => $filaHc['muestra_id'] ? (int)$filaHc['muestra_id'] : null,
                    'codigo_muestra' => $filaHc['codigo_muestra_db'] ?? ($encabezado['codigo_muestra'] ?? ($codigoMuestra ?: ('REG-' . $filaHc['id']))),
                    'fecha_ensayo' => substr((string)($encabezado['fecha_ensayo'] ?? ($filaHc['created_at'] ?? date('Y-m-d'))), 0, 10),
                    'origen_datos' => 'hojas_campo_registros',
                ]);
                return $this->mapearHaciaInforme($cod, $evalConjunto, $metadatos);
            }
        }

        // 2. Buscar en roturas (especialmente para RT-FM-64 Varillas de Acero o ensayos guardados en roturas)
        $sqlRot = "SELECT rot.*, esp.codigo_lab, esp.muestra_id, rm.codigo_muestra, rm.id_os
                   FROM roturas rot
                   JOIN especimenes esp ON rot.especimen_id = esp.id
                   JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                   WHERE rot.datos_crudos IS NOT NULL
                     AND rot.datos_crudos LIKE :fmt_like";
        $paramsRot = ['fmt_like' => '%' . $cod . '%'];

        if ($muestraId > 0) {
            $sqlRot .= " AND esp.muestra_id = :mid";
            $paramsRot['mid'] = $muestraId;
        }
        if ($idOs > 0) {
            $sqlRot .= " AND rm.id_os = :id_os";
            $paramsRot['id_os'] = $idOs;
        }
        if ($codigoMuestra !== '') {
            $sqlRot .= " AND (rm.codigo_muestra = :cod_m OR esp.codigo_lab = :cod_lab)";
            $paramsRot['cod_m'] = $codigoMuestra;
            $paramsRot['cod_lab'] = $codigoMuestra;
        }
        if ($registroId > 0 && $muestraId <= 0 && $idOs <= 0) {
            $sqlRot .= " AND rot.id = :rot_id";
            $paramsRot['rot_id'] = $registroId;
        }
        $sqlRot .= " ORDER BY rot.id ASC";

        $stmtRot = $pdo->prepare($sqlRot);
        $stmtRot->execute($paramsRot);
        $filasRot = $stmtRot->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($filasRot)) {
            $entradasRot = [];
            foreach ($filasRot as $fr) {
                $crudos = json_decode($fr['datos_crudos'] ?? '{}', true) ?: [];
                $ent = $crudos['entradas_hc'] ?? $crudos;
                if (is_array($ent) && !empty($ent)) {
                    $ent['codigo_muestra'] = $ent['codigo_muestra'] ?? ($fr['codigo_lab'] ?? $fr['codigo_muestra']);
                    $entradasRot[] = $ent;
                }
            }
            if (!empty($entradasRot)) {
                $primera = $filasRot[0];
                $evalConjunto = $this->motor->evaluarConjunto($cod, $entradasRot);
                return $this->mapearHaciaInforme($cod, $evalConjunto, [
                    'muestra_id' => (int)$primera['muestra_id'],
                    'id_os' => (int)$primera['id_os'],
                    'codigo_muestra' => $primera['codigo_muestra'] ?? $primera['codigo_lab'],
                    'fecha_ensayo' => substr((string)$primera['fecha_real'], 0, 10),
                    'origen_datos' => 'roturas',
                ]);
            }
        }

        // 3. Para RT-FM-56 (Certificado de Calibración de Picnómetros), consultar equipos_calibracion_picnometros
        if ($cod === 'RT-FM-56') {
            $sqlPic = "SELECT * FROM equipos_calibracion_picnometros WHERE activo = 1";
            $paramsPic = [];
            if ($picnometroId > 0) {
                $sqlPic .= " AND id = :pid";
                $paramsPic['pid'] = $picnometroId;
            } elseif ($registroId > 0) {
                $sqlPic .= " AND id = :pid";
                $paramsPic['pid'] = $registroId;
            }
            if ($numeroPicnometro > 0) {
                $identComp = sprintf('PICNO-%02d', $numeroPicnometro);
                $sqlPic .= " AND (identificacion = :ident1 OR identificacion = :ident2)";
                $paramsPic['ident1'] = $identComp;
                $paramsPic['ident2'] = 'N°' . $numeroPicnometro;
            } elseif ($codigoMuestra !== '') {
                $sqlPic .= " AND identificacion = :ident_m";
                $paramsPic['ident_m'] = $codigoMuestra;
            }
            $sqlPic .= " ORDER BY id ASC LIMIT 1";

            $stmtPic = $pdo->prepare($sqlPic);
            $stmtPic->execute($paramsPic);
            $filaPic = $stmtPic->fetch(PDO::FETCH_ASSOC);

            if ($filaPic) {
                $tablaJson = json_decode($filaPic['tabla_temperatura_json'] ?? '[]', true) ?: [];
                $replicas = $tablaJson['replicas'] ?? [];
                $entradasPic = [];
                foreach ($replicas as $rep) {
                    if (isset($rep['entradas']) && is_array($rep['entradas'])) {
                        $entradasPic[] = $rep['entradas'];
                    } elseif (isset($rep['masa_lleno']) || isset($rep['masa_vacio'])) {
                        $entradasPic[] = $rep;
                    }
                }
                if (empty($entradasPic) && (float)$filaPic['masa_picnometro_vacio_g'] > 0 && (float)$filaPic['volumen_calibrado_cm3'] > 0) {
                    $masaVacio = (float)$filaPic['masa_picnometro_vacio_g'];
                    $volProm = (float)$filaPic['volumen_calibrado_cm3'];
                    $tempCal = 20.0;
                    $densAgua20 = 0.99823;
                    $masaLleno = round($masaVacio + ($volProm * $densAgua20), 4);
                    $entradasPic[] = [
                        'masa_vacio' => $masaVacio,
                        'masa_lleno' => $masaLleno,
                        'temperatura' => $tempCal,
                    ];
                }
                if (!empty($entradasPic)) {
                    $evalConjunto = $this->motor->evaluarConjunto('RT-FM-56', $entradasPic);
                    return $this->mapearHaciaInforme('RT-FM-56', $evalConjunto, [
                        'picnometro_id' => (int)$filaPic['id'],
                        'identificacion' => $filaPic['identificacion'],
                        'codigo_muestra' => $filaPic['identificacion'],
                        'fecha_ensayo' => $filaPic['fecha_calibracion'] ?? date('Y-m-d'),
                        'origen_datos' => 'equipos_calibracion_picnometros',
                    ]);
                }
            }
        }

        // 4. Buscar en cotizacion_detalles si se indicó id_detalle
        if ($idDetalle > 0) {
            $stmtDet = $pdo->prepare("SELECT id, id_cotizacion, resultados_json FROM cotizacion_detalles WHERE id = :id");
            $stmtDet->execute(['id' => $idDetalle]);
            $filaDet = $stmtDet->fetch(PDO::FETCH_ASSOC);
            if ($filaDet && !empty($filaDet['resultados_json'])) {
                $resJson = json_decode($filaDet['resultados_json'], true) ?: [];
                $entradasDet = $resJson['metadatos']['entradas_hc'] ?? ($resJson['entradas_hc'] ?? []);
                if (!empty($entradasDet) && is_array($entradasDet)) {
                    $evalConjunto = $this->motor->evaluarConjunto($cod, $entradasDet);
                    return $this->mapearHaciaInforme($cod, $evalConjunto, [
                        'id_detalle' => (int)$filaDet['id'],
                        'codigo_muestra' => $codigoMuestra ?: ('DET-' . $filaDet['id']),
                        'fecha_ensayo' => $resJson['metadatos']['fecha_ensayo'] ?? date('Y-m-d'),
                        'origen_datos' => 'cotizacion_detalles',
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Transforma el resultado de MotorHojasCalculoServicio::evaluarConjunto() en la estructura
     * exacta de `resultados_json` (`filas` y `metadatos`) esperada por `matriz_print.php` y `formatos_schema.json`
     * para los formatos con letra RT-FM-22 (A..G), o bien en un payload de Reporte Nuevo para (46, 56, 58, 62, 64).
     *
     * @param string $codigoFormato Código HC ('RT-FM-61', 'RT-FM-36', ..., 'RT-FM-64')
     * @param array $evaluacionConjunto Salida de MotorHojasCalculoServicio::evaluarConjunto()
     * @param array $metadatosExtra Metadatos de cabecera (codigo_muestra, nombre_muestra, fecha_fabricacion, etc.)
     * @return array
     */
    public function mapearHaciaInforme(string $codigoFormato, array $evaluacionConjunto, array $metadatosExtra = []): array {
        $cod = strtoupper(trim($codigoFormato));
        $destino = $this->obtenerDestinoFormato($cod);

        if ($destino['nuevo']) {
            return $this->generarEstructuraReporteNuevo($cod, $destino, $evaluacionConjunto, $metadatosExtra);
        }

        $especimenes = $evaluacionConjunto['especimenes'] ?? [];
        $agregaciones = $evaluacionConjunto['agregaciones'] ?? [];
        $global = array_merge($evaluacionConjunto['contexto_global'] ?? [], $metadatosExtra);
        $archivoMarkdown = $destino['archivo_markdown'];

        $archivoDest = strtolower(trim((string)($global['archivo_destino'] ?? '')));
        if ($cod === 'RT-FM-61' && $archivoDest === 'formato_de_lodo_concreto.md') {
            $filas = $this->mapearLodoConcreto($especimenes, $global);
            $archivoMarkdown = 'formato_de_lodo_concreto.md';
        } else {
            if ($cod === 'RT-FM-25') {
                $tipoMatCheck = strtolower(trim((string)($especimenes[0]['valores']['tipo_material'] ?? ($global['tipo_material'] ?? ''))));
                if (str_contains($tipoMatCheck, 'agregado') || $archivoDest === 'granulomnetria_de_agregados.md') {
                    $archivoMarkdown = 'granulomnetria_de_agregados.md';
                }
            }

            $filas = match ($cod) {
                'RT-FM-61' => $this->mapearRtFm61($especimenes, $global),
                'RT-FM-36' => $this->mapearRtFm36($especimenes, $agregaciones, $global),
                'RT-FM-57' => $this->mapearRtFm57($especimenes, $global),
                'RT-FM-63' => $this->mapearRtFm63($especimenes, $global),
                'RT-FM-54' => $this->mapearRtFm54($especimenes, $global),
                'RT-FM-55' => $this->mapearRtFm55($especimenes, $global),
                'RT-FM-27' => $this->mapearRtFm27($agregaciones, $global),
                'RT-FM-59' => $this->mapearRtFm59($especimenes, $global),
                'RT-FM-38' => $this->mapearRtFm38($especimenes, $global),
                'RT-FM-25', 'RT-FM-42' => $this->mapearRtFm25Y42($especimenes, $agregaciones, $global),
                'RT-FM-35' => $this->mapearRtFm35($especimenes, $global),
                default => [],
            };
        }

        return [
            'es_reporte_nuevo' => false,
            'codigo_formato_hc' => $cod,
            'letra_rt_fm_22' => $destino['letra'],
            'archivo_markdown' => $archivoMarkdown,
            'resultados_json' => [
                'version_actual' => 1,
                'filas' => $filas,
                'metadatos' => [
                    'origen_hoja_calculo' => $cod,
                    'letra_rt_fm_22' => $destino['letra'],
                    'agregaciones' => $agregaciones,
                ],
            ],
        ];
    }

    /**
     * Mapeador específico para Lodo Concreto / Suelo-Cemento (`formato_de_lodo_concreto.md` · Fase 3.6)
     * con unidades métricas oficiales (`cm²`, `kg`, `kg/cm²`).
     */
    public function mapearLodoConcreto(array $especimenes, array $global = []): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? $esp;
            $r = $esp['redondeados'] ?? [];

            $areaCm2 = $r['area_cm2']
                ?? (isset($v['area_cm2']) && is_numeric($v['area_cm2']) ? round((float)$v['area_cm2'], 2) : null);
            if ($areaCm2 === null && isset($v['diametro_1'], $v['diametro_2']) && is_numeric($v['diametro_1']) && is_numeric($v['diametro_2']) && (float)$v['diametro_1'] > 0 && (float)$v['diametro_2'] > 0) {
                $dProm = ((float)$v['diametro_1'] + (float)$v['diametro_2']) / 2.0;
                $areaCm2 = round(M_PI * (($dProm / 2.0) ** 2), 2);
            }
            if ($areaCm2 === null && isset($v['area_in2']) && is_numeric($v['area_in2'])) {
                $areaCm2 = round((float)$v['area_in2'] * 6.4516, 2);
            }

            $cargaKg = $r['carga_real_kg']
                ?? $r['carga_kg']
                ?? (isset($v['carga_real_kg']) && is_numeric($v['carga_real_kg'])
                    ? round((float)$v['carga_real_kg'], 0)
                    : (isset($v['carga_kg']) && is_numeric($v['carga_kg']) ? round((float)$v['carga_kg'], 0) : null));
            if ($cargaKg === null) {
                $cargaLb = $v['carga_real_lbf'] ?? $v['lectura_carga_lbf'] ?? $v['carga_lbf'] ?? $v['carga'] ?? 0;
                $cargaKg = is_numeric($cargaLb) && (float)$cargaLb > 0 ? round((float)$cargaLb / 2.20462, 0) : 0;
            }

            $pvRaw = $v['peso_volumetrico'] ?? $v['densidad'] ?? null;
            $pv = ($pvRaw !== null && is_numeric($pvRaw)) ? round((float)$pvRaw / 10.0) * 10.0 : ($r['peso_volumetrico'] ?? $r['densidad'] ?? 0);

            $resKgCm2 = $r['esfuerzo_kgcm2']
                ?? $r['resistencia_kgcm2']
                ?? (isset($v['esfuerzo_kgcm2']) && is_numeric($v['esfuerzo_kgcm2']) ? round((float)$v['esfuerzo_kgcm2'], 1) : null);
            if ($resKgCm2 === null && $areaCm2 > 0 && $cargaKg > 0) {
                $resKgCm2 = round($cargaKg / $areaCm2, 1);
            }

            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['identificacion'] ?? ($global['nombre_muestra'] ?? ('Lodo Concreto #' . ($idx + 1)))),
                'Área (cm²)' => $areaCm2 ?? 0,
                'Carga (kg)' => $cargaKg,
                'Peso Volumétrico (kg/m³)' => $pv,
                'Diseño (kg/cm²)' => $v['diseno_kgcm2'] ?? ($global['diseno_kgcm2'] ?? '-'),
                'R. compresión. (kg/cm²)' => $resKgCm2 ?? 0,
                'Fecha de Fabricación' => (string)($v['fecha_moldeo'] ?? ($v['fecha_fabricacion'] ?? ($global['fecha_moldeo'] ?? ''))),
                'Fecha de Ensayo' => (string)($v['fecha_rotura'] ?? ($v['fecha_ensayo'] ?? ($v['fecha_ruptura'] ?? ($global['fecha_ensayo'] ?? '')))),
                'Edad (Días)' => $r['edad_dias'] ?? $r['edad_calculada'] ?? ($v['edad_dias'] ?? ($v['edad_calculada'] ?? ($global['edad_dias'] ?? ''))),
            ];
        }
        return $filas;
    }

    private function mapearRtFm61(array $especimenes, array $global): array {
        $redondearPv10 = ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_pv_10', true);
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];

            $cargaVal = $r['carga_real_lbf']
                ?? $r['lectura_carga_lbf']
                ?? $r['carga_lbf']
                ?? round((float)($v['carga_real_lbf'] ?? $v['lectura_carga_lbf'] ?? $v['carga_lbf'] ?? 0), 0);

            $psiVal = $r['esfuerzo_psi_reportado']
                ?? $r['esfuerzo_psi']
                ?? round((float)($v['esfuerzo_psi_reportado'] ?? $v['esfuerzo_psi'] ?? 0), 0);

            $kgCm2Val = $r['esfuerzo_kgcm2']
                ?? round((float)($v['esfuerzo_kgcm2'] ?? 0), 0);

            $pvRaw = $v['peso_volumetrico'] ?? $v['densidad'] ?? null;
            if ($pvRaw !== null && is_numeric($pvRaw) && (float)$pvRaw > 0) {
                $pvVal = $redondearPv10 ? (round((float)$pvRaw / 10.0) * 10.0) : round((float)$pvRaw, 0);
            } else {
                $pvVal = $r['peso_volumetrico'] ?? $r['densidad'] ?? 0;
            }

            $revIn = isset($r['revenimiento_in']) && is_numeric($r['revenimiento_in'])
                ? $r['revenimiento_in']
                : (isset($v['revenimiento_in']) && is_numeric($v['revenimiento_in'])
                    ? round((float)$v['revenimiento_in'], 1)
                    : ($global['revenimiento_in'] ?? '-'));

            $revCm = isset($v['revenimiento_cm']) && is_numeric($v['revenimiento_cm'])
                ? round((float)$v['revenimiento_cm'], 1)
                : ($global['revenimiento_cm'] ?? '-');

            $fechaEnsayo = (string)($v['fecha_rotura']
                ?? $v['fecha_ensayo']
                ?? $v['fecha_ruptura']
                ?? $global['fecha_rotura']
                ?? $global['fecha_ensayo']
                ?? $global['fecha_ruptura']
                ?? '');

            $edadVal = $r['edad_dias']
                ?? $r['edad_calculada']
                ?? ($v['edad_dias'] ?? $v['edad_calculada'] ?? ($global['edad_dias'] ?? ''));

            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['identificacion'] ?? ($global['nombre_muestra'] ?? ('Cilindro #' . ($idx + 1)))),
                'Área (in²)' => $r['area_in2'] ?? round((float)($v['area_in2'] ?? 0), 2),
                'Reven. (in)' => $revIn,
                'Reven. (cm)' => $revCm,
                'Temp. (°C)' => $v['temperatura_c'] ?? ($global['temperatura_c'] ?? '-'),
                'Carga (lb)' => $cargaVal,
                'Peso Volumétrico (kg/m³)' => $pvVal,
                'Diseño de concreto (lb/in²)' => $v['resistencia_diseno_psi'] ?? ($global['resistencia_diseno_psi'] ?? '-'),
                'R. Compresión (lb/in²)' => $psiVal,
                'R. Compresión (kg/cm²)' => $kgCm2Val,
                'Fecha de Fabricación' => (string)($v['fecha_moldeo'] ?? ($global['fecha_moldeo'] ?? '')),
                'Fecha de Ensayo' => $fechaEnsayo,
                'Edad (Días)' => $edadVal,
            ];
        }
        return $filas;
    }

    private function mapearRtFm36(array $especimenes, array $agregaciones, array $global): array {
        $v = $especimenes[0]['valores'] ?? [];
        $r = $especimenes[0]['redondeados'] ?? [];

        $areaBrutaIn2 = $r['area_bruta_in2']
            ?? (isset($v['area_bruta_in2']) && is_numeric($v['area_bruta_in2'])
                ? round((float)$v['area_bruta_in2'], 2)
                : (isset($v['area_bruta']) && is_numeric($v['area_bruta']) ? round((float)$v['area_bruta'] / 6.4516, 2) : 0));

        $areaNetaIn2 = $r['area_neta_in2']
            ?? (isset($v['area_neta_in2']) && is_numeric($v['area_neta_in2'])
                ? round((float)$v['area_neta_in2'], 2)
                : (isset($v['area_neta']) && is_numeric($v['area_neta']) ? round((float)$v['area_neta'] / 6.4516, 2) : 0));

        $largoVal = $r['largo_prom']
            ?? (isset($v['largo_prom']) && is_numeric($v['largo_prom']) ? round((float)$v['largo_prom'], 2) : round((float)($v['largo'] ?? 0), 2));
        $altoVal = $r['alto_prom']
            ?? (isset($v['alto_prom']) && is_numeric($v['alto_prom']) ? round((float)$v['alto_prom'], 2) : round((float)($v['alto'] ?? 0), 2));
        $anchoVal = $r['ancho_prom']
            ?? (isset($v['ancho_prom']) && is_numeric($v['ancho_prom']) ? round((float)$v['ancho_prom'], 2) : round((float)($v['ancho'] ?? 0), 2));

        // espesor_pared_int / espesor_pared_ext ya están expresados en cm en RT-FM-36.json (no dividir entre 10)
        $espIntVal = isset($v['espesor_pared_int']) && is_numeric($v['espesor_pared_int'])
            ? round((float)$v['espesor_pared_int'], 2)
            : ($r['espesor_alma_prom_mm'] ?? (isset($v['espesor_alma_prom_mm']) ? round((float)$v['espesor_alma_prom_mm'] / 10, 2) : 0));
        $espExtVal = isset($v['espesor_pared_ext']) && is_numeric($v['espesor_pared_ext'])
            ? round((float)$v['espesor_pared_ext'], 2)
            : ($r['espesor_pared_prom_mm'] ?? (isset($v['espesor_pared_prom_mm']) ? round((float)$v['espesor_pared_prom_mm'] / 10, 2) : 0));

        $densidadVal = $r['densidad_seca']
            ?? (isset($agregaciones['densidad_promedio']) && is_numeric($agregaciones['densidad_promedio'])
                ? round((float)$agregaciones['densidad_promedio'], 0)
                : ($r['densidad'] ?? round((float)($v['densidad_seca'] ?? $v['densidad'] ?? 0), 0)));

        // Fase 1.6: Bloques de concreto reportan resistencia sobre ÁREA NETA (ASTM C140 / NTON 12 008-16)
        $resNetaPsi = $r['resistencia_psi']
            ?? (isset($agregaciones['resistencia_promedio_psi']) && is_numeric($agregaciones['resistencia_promedio_psi'])
                ? round((float)$agregaciones['resistencia_promedio_psi'], 0)
                : round((float)($v['resistencia_psi'] ?? 0), 0));
        $resNetaKgCm2 = $r['resistencia_kgcm2']
            ?? (isset($agregaciones['resistencia_promedio_kgcm2']) && is_numeric($agregaciones['resistencia_promedio_kgcm2'])
                ? round((float)$agregaciones['resistencia_promedio_kgcm2'], 1)
                : round((float)($v['resistencia_kgcm2'] ?? 0), 1));

        $absVal = $r['absorcion_pct']
            ?? (isset($v['absorcion_pct']) && is_numeric($v['absorcion_pct'])
                ? round((float)$v['absorcion_pct'], 2)
                : (isset($agregaciones['absorcion_promedio_pct']) && is_numeric($agregaciones['absorcion_promedio_pct'])
                    ? round((float)$agregaciones['absorcion_promedio_pct'], 2)
                    : (isset($agregaciones['absorcion_promedio_kgm3']) && is_numeric($agregaciones['absorcion_promedio_kgm3'])
                        ? round((float)$agregaciones['absorcion_promedio_kgm3'], 2)
                        : 0)));

        return [
            ['Descripción' => 'Área bruta.', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'in²', 'Resultado' => $areaBrutaIn2, 'Min' => '', 'Max' => ''],
            ['Descripción' => 'Área neta.', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'in²', 'Resultado' => $areaNetaIn2, 'Min' => '50% A.B', 'Max' => ''],
            ['Descripción' => 'Carga', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'lb', 'Resultado' => $r['carga_lbf'] ?? round((float)($v['carga_lbf'] ?? 0), 0), 'Min' => '-', 'Max' => '-'],
            ['Descripción' => 'Largo', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'cm', 'Resultado' => $largoVal, 'Min' => '39', 'Max' => '-'],
            ['Descripción' => 'Alto', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'cm', 'Resultado' => $altoVal, 'Min' => '19', 'Max' => '-'],
            ['Descripción' => 'Ancho', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'cm', 'Resultado' => $anchoVal, 'Min' => '10', 'Max' => '-'],
            ['Descripción' => 'Espesor interno.', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'cm', 'Resultado' => $espIntVal, 'Min' => '2; 2.5; 3', 'Max' => ''],
            ['Descripción' => 'Espesor externo.', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'cm', 'Resultado' => $espExtVal, 'Min' => '2; 2.5; 3', 'Max' => '-'],
            ['Descripción' => 'Peso volumétrico', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'kg/m³', 'Resultado' => $densidadVal, 'Min' => '-', 'Max' => '-'],
            ['Descripción' => 'R. Compresión', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'lb/in²', 'Resultado' => $resNetaPsi, 'Min' => '1714', 'Max' => '-'],
            ['Descripción' => 'R. Compresión.', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => 'kg/cm²', 'Resultado' => $resNetaKgCm2, 'Min' => '120', 'Max' => '-'],
            ['Descripción' => 'Absorción', 'Método de ensayo' => 'CYCSA-PE-32', 'Unidad' => '(%)(P/P)', 'Resultado' => $absVal, 'Min' => '-', 'Max' => '10'],
            ['Descripción' => 'Fecha de Fabricación', 'Método de ensayo' => '', 'Unidad' => '', 'Resultado' => (string)($v['fecha_fabricacion'] ?? $v['fecha_moldeo'] ?? $global['fecha_fabricacion'] ?? $global['fecha_moldeo'] ?? '-'), 'Min' => '-', 'Max' => '-'],
            ['Descripción' => 'Fecha de Ensayo', 'Método de ensayo' => '', 'Unidad' => '', 'Resultado' => (string)($v['fecha_ensayo'] ?? ($global['fecha_ensayo'] ?? date('Y-m-d'))), 'Min' => '-', 'Max' => '-'],
            ['Descripción' => 'Edad', 'Método de ensayo' => '', 'Unidad' => 'Días', 'Resultado' => (string)($v['edad_dias'] ?? ($global['edad_dias'] ?? '-')), 'Min' => '-', 'Max' => '-'],
        ];
    }

    private function mapearRtFm57(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['identificacion'] ?? ($global['nombre_muestra'] ?? ('Cubo Mortero #' . ($idx + 1)))),
                'Área (in²)' => $r['area_in2'] ?? round((float)($v['area_in2'] ?? 4.0), 2),
                'Carga (lb)' => $r['carga_lbf'] ?? round((float)($v['carga_lbf'] ?? 0), 0),
                'Peso Volumétrico (kg/m³)' => $r['peso_volumetrico'] ?? $r['densidad'] ?? round((float)($v['peso_volumetrico'] ?? $v['densidad'] ?? 0), 0),
                'Diseño de Mortero (lb/in²)' => $v['resistencia_diseno_psi'] ?? ($global['resistencia_diseno_psi'] ?? '-'),
                'R. Compresión (lb/in²)' => $r['resistencia_psi'] ?? $r['esfuerzo_psi'] ?? round((float)($v['resistencia_psi'] ?? $v['esfuerzo_psi'] ?? 0), 0),
                'R. Compresión. (kg/cm²)' => $r['resistencia_kgcm2'] ?? $r['esfuerzo_kgcm2'] ?? round((float)($v['resistencia_kgcm2'] ?? $v['esfuerzo_kgcm2'] ?? 0), 1),
                'Fecha de Fabricación' => (string)($v['fecha_fabricacion'] ?? $v['fecha_moldeo'] ?? $global['fecha_fabricacion'] ?? $global['fecha_moldeo'] ?? ''),
                'Fecha de Ensayo' => (string)($v['fecha_ensayo'] ?? $v['fecha_ruptura'] ?? $global['fecha_ensayo'] ?? $global['fecha_ruptura'] ?? ''),
                'Edad (Días)' => $r['edad_dias'] ?? $r['edad_calculada'] ?? ($v['edad_dias'] ?? ($global['edad_dias'] ?? '')),
            ];
        }
        return $filas;
    }

    private function mapearRtFm63(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['identificacion'] ?? ($global['nombre_muestra'] ?? ('Adoquín #' . ($idx + 1)))),
                'Área (in²)' => $r['area_neta_in2'] ?? round((float)($v['area_neta_in2'] ?? 0), 2),
                'Carga (lb)' => $r['carga_prom'] ?? round((float)($v['carga_prom'] ?? ($v['carga_1'] ?? 0)), 0),
                'Peso Volumétrico (kg/m³)' => $r['peso_volumetrico'] ?? $r['densidad'] ?? round((float)($v['peso_volumetrico'] ?? $v['densidad'] ?? 0), 0),
                'Diseño (lb/in²)' => $v['resistencia_diseno_psi'] ?? ($global['resistencia_diseno_psi'] ?? '-'),
                'R. Compresión (lb/in²)' => $r['resistencia_psi'] ?? round((float)($v['resistencia_psi'] ?? 0), 0),
                'R. Compresión. (kg/cm²)' => $r['resistencia_kgcm2'] ?? round((float)($v['resistencia_kgcm2'] ?? ((float)($v['resistencia_psi'] ?? 0) / 14.2)), 0),
                'Absorción ((%)(P/P))' => $r['absorcion_pct'] ?? $r['absorcion'] ?? round((float)($v['absorcion_pct'] ?? $v['absorcion'] ?? 0), 2),
                'Fecha de Fabricación' => (string)($v['fecha_fabricacion'] ?? $v['fecha_moldeo'] ?? $global['fecha_fabricacion'] ?? $global['fecha_moldeo'] ?? '-'),
                'Fecha de Ensayo' => (string)($v['fecha_ensayo'] ?? ($global['fecha_ensayo'] ?? date('Y-m-d'))),
                'Edad (Días)' => $v['edad_dias'] ?? ($global['edad_dias'] ?? '-'),
            ];
        }
        return $filas;
    }

    private function mapearRtFm54(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MC-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['ubicacion'] ?? ($global['nombre_muestra'] ?? ('Punto #' . ($idx + 1)))),
                'Capa (N°)' => $v['capa'] ?? ($global['capa'] ?? ''),
                'Espesor (cm)' => $v['espesor'] ?? ($v['profundidad'] ?? ($global['espesor'] ?? ($global['profundidad'] ?? ''))),
                'Profundidad (cm)' => $v['profundidad'] ?? ($global['profundidad'] ?? ''),
                '**P.V.S Max (kg/m³)' => $v['pvs_maximo'] ?? ($global['pvs_maximo'] ?? ''),
                '**Humedad Óptima (( %)(P/P))' => $v['humedad_optima'] ?? ($global['humedad_optima'] ?? '-'),
                'P.V.S.Sitio (kg/m³)' => $r['densidad_seca'] ?? round((float)($v['densidad_seca'] ?? 0), 0),
                'Humedad sitio ((%) (P/P))' => $r['humedad'] ?? round((float)($v['humedad'] ?? 0), 1),
                'Humedad Sitio (kg/m³)' => $r['densidad_humeda'] ?? round((float)($v['densidad_humeda'] ?? 0), 0),
                'Compactación ((%) (P/P))' => $r['compactacion'] ?? round((float)($v['compactacion'] ?? 0), 1),
                'Fecha de muestreo' => (string)($v['fecha_ensayo'] ?? ($global['fecha_ensayo'] ?? date('Y-m-d'))),
            ];
        }
        return $filas;
    }

    private function mapearRtFm55(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MC-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['procedencia'] ?? ($global['nombre_muestra'] ?? ('Punto #' . ($idx + 1)))),
                'Capa (N°)' => $v['capa'] ?? ($global['capa'] ?? ''),
                'Espesor (cm)' => $v['espesor'] ?? ($v['profundidad'] ?? ($global['espesor'] ?? ($global['profundidad'] ?? ''))),
                'Profundidad (cm)' => $v['profundidad'] ?? ($global['profundidad'] ?? ''),
                'P.V.S Max (kg/m³)' => $v['pvs_maximo'] ?? ($global['pvs_maximo'] ?? ''),
                'Humedad Óptima ((% )(P/P))' => $v['humedad_optima'] ?? ($global['humedad_optima'] ?? '-'),
                'P.V.S.Sitio (kg/m³)' => $r['densidad_seca'] ?? round((float)($v['densidad_seca'] ?? 0), 0),
                'Humedad sitio ((%) (P/P))' => $r['humedad'] ?? round((float)($v['humedad'] ?? 0), 1),
                'Humedad Sitio (kg/m³)' => $r['densidad_humeda'] ?? round((float)($v['densidad_humeda'] ?? 0), 0),
                'Compactación ((%) (P/P))' => $r['compactacion'] ?? round((float)($v['compactacion'] ?? 0), 1),
                'Fecha de muestreo' => (string)($v['fecha_ensayo'] ?? ($global['fecha_ensayo'] ?? date('Y-m-d'))),
            ];
        }
        return $filas;
    }

    private function mapearRtFm27(array $agregaciones, array $global): array {
        return [
            [
                'Código laboratorio' => (string)($global['codigo_muestra'] ?? 'MS-0001-26'),
                'Nombre muestra' => (string)($global['nombre_muestra'] ?? 'Muestra Proctor'),
                'P.Vol.Seco Máximo (kg/m³)' => round((float)($agregaciones['pvs_maximo'] ?? ($global['pvs_maximo'] ?? 0)), 0),
                'Cont. Humedad Óptima ((%)(P/P))' => round((float)($agregaciones['humedad_optima'] ?? ($global['humedad_optima'] ?? 0)), 1),
                'Método Ensayo' => (string)($global['tipo_proctor'] ?? 'Proctor Estandar'),
                'Tipo de Material' => (string)($global['tipo_material'] ?? 'Suelo'),
                'Fecha de Ensayo' => (string)($global['fecha_ensayo'] ?? date('Y-m-d')),
            ],
        ];
    }

    private function mapearRtFm59(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['elemento'] ?? ($global['elemento'] ?? ('Elemento #' . ($idx + 1)))),
                'Diseño de Concreto (lb/in²)' => $v['resistencia_diseno_psi'] ?? ($global['resistencia_diseno_psi'] ?? '-'),
                'Forma de Prueba' => 'Posición ' . ($v['posicion'] ?? ($global['posicion'] ?? 'A')),
                'Promedio de Rebotes (N°)' => $r['rebote_promedio'] ?? round((float)($v['rebote_promedio'] ?? 0), 0),
                'Estimación R. compresión (lb/in²)' => $r['resistencia_psi_redondeada'] ?? $r['resistencia_psi'] ?? round((float)($v['resistencia_psi'] ?? 0), 0),
                'Estimación R. compresión (kg/cm²)' => $r['resistencia_kgcm2'] ?? round((float)($v['resistencia_kgcm2'] ?? 0), 0),
                'Fecha de Fabricación' => (string)($v['fecha_fabricacion'] ?? ($global['fecha_fabricacion'] ?? '-')),
                'Edad (Días)' => $v['edad_dias'] ?? ($global['edad_dias'] ?? '-'),
            ];
        }
        return $filas;
    }

    private function mapearRtFm38(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['identificacion'] ?? ($global['nombre_muestra'] ?? ('Viga #' . ($idx + 1)))),
                'Ancho Promedio (in)' => $r['ancho_prom'] ?? $r['ancho_in'] ?? round((float)($v['ancho_prom'] ?? $v['ancho_in'] ?? 0), 2),
                'Espesor Promedio (in)' => $r['espesor_prom'] ?? $r['peralte_in'] ?? round((float)($v['espesor_prom'] ?? $v['peralte_in'] ?? 0), 2),
                'Longitud de Apoyo (in)' => $r['luz_apoyos'] ?? $r['luz_in'] ?? round((float)($v['luz_apoyos'] ?? $v['luz_in'] ?? 0), 2),
                'Reven. (in)' => $v['revenimiento_in'] ?? ($global['revenimiento_in'] ?? '-'),
                'Reven. (cm)' => $v['revenimiento_cm'] ?? ($global['revenimiento_cm'] ?? '-'),
                'Temp. (°C)' => $v['temperatura_c'] ?? ($global['temperatura_c'] ?? '-'),
                'Carga (lb)' => $r['carga_lbf'] ?? round((float)($v['carga_lbf'] ?? 0), 0),
                'Peso volumétrico (kg/m³)' => $r['peso_volumetrico'] ?? $r['densidad'] ?? round((float)($v['peso_volumetrico'] ?? $v['densidad'] ?? 0), 0),
                'Diseño MR (kg/cm²)' => $v['mr_diseno_kgcm2'] ?? ($global['mr_diseno_kgcm2'] ?? '-'),
                'Resistencia a la flexión. (kg/cm²)' => $r['modulo_ruptura_kgcm2'] ?? $r['mr_kgcm2'] ?? round((float)($v['modulo_ruptura_kgcm2'] ?? $v['mr_kgcm2'] ?? 0), 1),
                'Fecha de Fabricación' => (string)($v['fecha_fabricacion'] ?? $v['fecha_moldeo'] ?? $global['fecha_fabricacion'] ?? $global['fecha_moldeo'] ?? ''),
                'Fecha de Ensayo' => (string)($v['fecha_ensayo'] ?? $v['fecha_ruptura'] ?? $global['fecha_ensayo'] ?? $global['fecha_ruptura'] ?? ''),
                'Edad (Días)' => $r['edad_dias'] ?? $r['edad_calculada'] ?? ($v['edad_dias'] ?? ($global['edad_dias'] ?? '')),
            ];
        }
        return $filas;
    }

    private function mapearRtFm25Y42(array $especimenes, array $agregaciones, array $global): array {
        $v = $especimenes[0]['valores'] ?? [];
        $r = $especimenes[0]['redondeados'] ?? [];
        $curvaObj = $agregaciones['curva_granulometrica'] ?? [];
        $curva = $curvaObj['tamices'] ?? [];

        $tipoMaterial = strtolower(trim((string)($v['tipo_material'] ?? ($global['tipo_material'] ?? ''))));
        $archivoDest = strtolower(trim((string)($global['archivo_destino'] ?? '')));
        $esAgregado = str_contains($tipoMaterial, 'agregado') || $archivoDest === 'granulomnetria_de_agregados.md';

        if ($esAgregado) {
            // Fase 3.7: Tabla de tamices ASTM C33/C136 para agregados (CYCSA-PE-06), sin Atterberg/SUCS, + Módulo de Finura
            $mapaTamicesAgregado = [
                'Tamiz 3"' => $curva['ret_3in']['pct_pasa'] ?? null,
                'Tamiz 2"' => $curva['ret_2in']['pct_pasa'] ?? null,
                'Tamiz 1 1/2"' => $curva['ret_1_1_2in']['pct_pasa'] ?? null,
                'Tamiz 1"' => $curva['ret_1in']['pct_pasa'] ?? null,
                'Tamiz 3/4"' => $curva['ret_3_4in']['pct_pasa'] ?? null,
                'Tamiz 1/2"' => $curva['ret_1_2in']['pct_pasa'] ?? null,
                'Tamiz 3/8"' => $curva['ret_3_8in']['pct_pasa'] ?? null,
                'Tamiz No. 4' => $curva['ret_no4']['pct_pasa'] ?? null,
                'Tamiz No. 8' => $curva['ret_no8']['pct_pasa'] ?? null,
                'Tamiz No. 16' => $curva['ret_no16']['pct_pasa'] ?? null,
                'Tamiz No. 30' => $curva['ret_no30']['pct_pasa'] ?? null,
                'Tamiz No. 50' => $curva['ret_no50']['pct_pasa'] ?? null,
                'Tamiz No. 100' => $curva['ret_no100']['pct_pasa'] ?? null,
                'Tamiz No. 200' => $curva['ret_no200']['pct_pasa'] ?? ($r['pasa_no200'] ?? null),
            ];

            $filas = [];
            foreach ($mapaTamicesAgregado as $desc => $val) {
                $filas[] = [
                    'Descripción' => $desc,
                    'Unidad' => '(%)(P/P)',
                    'Resultado' => $val !== null ? round((float)$val, 1) : '',
                    'Método de ensayo' => 'CYCSA-PE-06',
                    'Min' => '',
                    'Max' => '',
                ];
            }

            $mf = $curvaObj['modulo_finura'] ?? ($v['modulo_finura'] ?? ($global['modulo_finura'] ?? null));
            $filas[] = [
                'Descripción' => 'Módulo de Finura',
                'Unidad' => '',
                'Resultado' => is_numeric($mf) ? round((float)$mf, 2) : '',
                'Método de ensayo' => 'CYCSA-PE-06',
                'Min' => '',
                'Max' => '',
            ];

            return $filas;
        }

        $sucs = $agregaciones['clasificacion_sucs'] ?? ['simbolo' => $global['sucs'] ?? '-', 'descripcion' => ''];

        $ll = $agregaciones['ll'] ?? ($v['ll'] ?? ($global['ll'] ?? 'NP'));
        $lp = $agregaciones['lp'] ?? ($v['lp'] ?? ($global['lp'] ?? 'NP'));
        $ip = $r['ip'] ?? ($v['ip'] ?? ($global['ip'] ?? 'NP'));
        $humedad = $r['humedad'] ?? ($v['humedad'] ?? ($global['humedad'] ?? '-'));

        $mapaTamices = [
            'Tamiz 3"' => $curva['ret_3in']['pct_pasa'] ?? null,
            'Tamiz 2"' => $curva['ret_2in']['pct_pasa'] ?? null,
            'Tamiz 1 1/2"' => $curva['ret_1_1_2in']['pct_pasa'] ?? null,
            'Tamiz 1"' => $curva['ret_1in']['pct_pasa'] ?? null,
            'Tamiz 3/4"' => $curva['ret_3_4in']['pct_pasa'] ?? null,
            'Tamiz 3/8"' => $curva['ret_3_8in']['pct_pasa'] ?? null,
            'Tamiz 4' => $curva['ret_no4']['pct_pasa'] ?? null,
            'Tamiz 10' => $curva['ret_no10']['pct_pasa'] ?? null,
            'Tamiz 20' => $curva['ret_no20']['pct_pasa'] ?? null,
            'Tamiz 40' => $curva['ret_no40']['pct_pasa'] ?? null,
            'Tamiz 60' => $curva['ret_no60']['pct_pasa'] ?? null,
            'Tamiz 100' => $curva['ret_no100']['pct_pasa'] ?? null,
            'Tamiz 140' => $curva['ret_no140']['pct_pasa'] ?? null,
            'Tamiz 200' => $curva['ret_no200']['pct_pasa'] ?? ($r['pasa_no200'] ?? null),
        ];

        $filas = [];
        foreach ($mapaTamices as $desc => $val) {
            $filas[] = [
                'Descripción' => $desc,
                'Unidad' => '(%)(P/P)',
                'Método de ensayo' => 'CYCSA-PE-16',
                'Resultado' => $val !== null ? round((float)$val, 1) : '',
            ];
        }

        $filas[] = ['Descripción' => 'Límite Liquido.', 'Unidad' => '(%)(P/P)', 'Método de ensayo' => 'CYCSA-PE-17', 'Resultado' => is_numeric($ll) ? round((float)$ll, 1) : $ll];
        $filas[] = ['Descripción' => 'I.P', 'Unidad' => '(%)(P/P)', 'Método de ensayo' => 'CYCSA-PE-17', 'Resultado' => is_numeric($ip) ? round((float)$ip, 0) : $ip];
        $filas[] = ['Descripción' => 'Límite plástico.', 'Unidad' => '(%)(P/P)', 'Método de ensayo' => 'CYCSA-PE-17', 'Resultado' => is_numeric($lp) ? round((float)$lp, 1) : $lp];
        $filas[] = ['Descripción' => 'Humedad', 'Unidad' => '(%)(P/P)', 'Método de ensayo' => 'CYCSA-PE-13', 'Resultado' => is_numeric($humedad) ? round((float)$humedad, 2) : $humedad];
        $filas[] = ['Descripción' => 'Clasificación S.U.C.S', 'Unidad' => '', 'Método de ensayo' => 'CYCSA-PE-23', 'Resultado' => trim(($sucs['simbolo'] ?? '') . ' ' . ($sucs['descripcion'] ?? ''))];

        return $filas;
    }

    private function mapearRtFm35(array $especimenes, array $global): array {
        $filas = [];
        foreach ($especimenes as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $r = $esp['redondeados'] ?? [];
            $fraccionLower = strtolower(trim((string)($v['fraccion'] ?? ($global['fraccion'] ?? ''))));
            $esGrueso = ($fraccionLower === 'agregado grueso')
                || (isset($v['g_ge_bulk']) && $v['g_ge_bulk'] !== null && (!isset($v['ge_bulk']) || $v['ge_bulk'] === null));

            $filas[] = [
                'Código laboratorio' => (string)($v['codigo_muestra'] ?? ($global['codigo_muestra'] ?? ('MS-000' . ($idx + 1)))),
                'Nombre muestra' => (string)($v['fraccion'] ?? ($global['nombre_muestra'] ?? ($esGrueso ? 'Agregado Grueso' : 'Agregado Fino'))),
                'Color' => $v['color'] ?? ($global['color'] ?? '-'),
                'PVSS (kg/m³)' => $v['pvss'] ?? ($global['pvss'] ?? '-'),
                'PVSC (kg/m³)' => $v['pvsc'] ?? ($global['pvsc'] ?? '-'),
                'Módulo de finura' => $v['modulo_finura'] ?? ($global['modulo_finura'] ?? '-'),
                'Humedad Natural ((%) (P/P))' => $v['humedad_natural'] ?? ($global['humedad_natural'] ?? '-'),
                'Absorción ((%)(P/P))' => $esGrueso ? ($r['g_absorcion'] ?? round((float)($v['g_absorcion'] ?? 0), 2)) : ($r['absorcion'] ?? round((float)($v['absorcion'] ?? 0), 2)),
                'Gravedad Específica.' => $esGrueso ? ($r['g_ge_bulk'] ?? round((float)($v['g_ge_bulk'] ?? 0), 3)) : ($r['ge_bulk'] ?? round((float)($v['ge_bulk'] ?? 0), 3)),
                'Gravedad Específica (SSS)' => $esGrueso ? ($r['g_ge_sss'] ?? round((float)($v['g_ge_sss'] ?? 0), 3)) : ($r['ge_sss'] ?? round((float)($v['ge_sss'] ?? 0), 3)),
                'Gravedad Específica Aparente' => $esGrueso ? ($r['g_ge_aparente'] ?? round((float)($v['g_ge_aparente'] ?? 0), 3)) : ($r['ge_aparente'] ?? round((float)($v['ge_aparente'] ?? 0), 3)),
            ];
        }
        return $filas;
    }

    /**
     * Genera la estructura de informe para los 5 formatos nuevos que no tenían equivalente RT-FM-22:
     * RT-FM-46 (CBR), RT-FM-56 (Picnómetros), RT-FM-58 (Sanidad), RT-FM-62 (Infiltración) y RT-FM-64 (Acero).
     */
    private function generarEstructuraReporteNuevo(string $cod, array $destino, array $evaluacionConjunto, array $metadatosExtra): array {
        $spec = $this->motor->cargarEspecificacion($cod);
        $especimenes = $evaluacionConjunto['especimenes'] ?? [];
        $agregaciones = $evaluacionConjunto['agregaciones'] ?? [];

        $columnas = [];
        $filasTabla = [];

        switch ($cod) {
            case 'RT-FM-46':
                $columnas = ['Golpes/Capa', 'Densidad Húmeda (kg/m³)', 'Humedad Moldeo (%)', 'Densidad Seca (kg/m³)', 'Compactación (%)', 'Esfuerzo 0.1" (psi)', 'Esfuerzo 0.2" (psi)', 'CBR 0.1" (%)', 'CBR 0.2" (%)', 'CBR Adoptado (%)'];
                foreach ($especimenes as $esp) {
                    $v = $esp['valores'] ?? [];
                    $r = $esp['redondeados'] ?? [];
                    $filasTabla[] = [
                        'Golpes/Capa' => $v['golpes'] ?? '-',
                        'Densidad Húmeda (kg/m³)' => $r['densidad_humeda'] ?? null,
                        'Humedad Moldeo (%)' => $r['humedad_antes'] ?? null,
                        'Densidad Seca (kg/m³)' => $r['densidad_seca'] ?? null,
                        'Compactación (%)' => $r['compactacion'] ?? null,
                        'Esfuerzo 0.1" (psi)' => $r['esfuerzo_01'] ?? null,
                        'Esfuerzo 0.2" (psi)' => $r['esfuerzo_02'] ?? null,
                        'CBR 0.1" (%)' => $r['cbr_01'] ?? null,
                        'CBR 0.2" (%)' => $r['cbr_02'] ?? null,
                        'CBR Adoptado (%)' => $r['cbr'] ?? null,
                    ];
                }
                break;

            case 'RT-FM-56':
                $columnas = ['Réplica', 'Masa Vacío (g)', 'Masa Picnómetro + Agua (g)', 'Temperatura (°C)', 'Densidad Agua (g/mL)', 'Masa Agua (g)', 'Volumen Calibrado (mL)'];
                foreach ($especimenes as $idx => $esp) {
                    $v = $esp['valores'] ?? [];
                    $r = $esp['redondeados'] ?? [];
                    $filasTabla[] = [
                        'Réplica' => $idx + 1,
                        'Masa Vacío (g)' => $v['masa_vacio'] ?? null,
                        'Masa Picnómetro + Agua (g)' => $v['masa_lleno'] ?? null,
                        'Temperatura (°C)' => $v['temperatura'] ?? null,
                        'Densidad Agua (g/mL)' => $r['densidad_agua'] ?? null,
                        'Masa Agua (g)' => isset($v['masa_agua']) && is_numeric($v['masa_agua']) ? round((float)$v['masa_agua'], 2) : null,
                        'Volumen Calibrado (mL)' => $r['volumen'] ?? null,
                    ];
                }
                break;

            case 'RT-FM-58':
                $columnas = ['Fracción', 'Retenido Original (g)', '% Gradación Original', 'Masa Inicial (g)', 'Masa Final (g)', 'Pérdida (g)', '% Pérdida Fracción', '% Pérdida Ponderada'];
                foreach ($especimenes as $esp) {
                    $v = $esp['valores'] ?? [];
                    $r = $esp['redondeados'] ?? [];
                    $filasTabla[] = [
                        'Fracción' => $v['tamano'] ?? '-',
                        'Retenido Original (g)' => $v['retenido_original'] ?? null,
                        '% Gradación Original' => $r['gradacion_pct'] ?? null,
                        'Masa Inicial (g)' => $v['masa_inicial'] ?? null,
                        'Masa Final (g)' => $v['masa_final'] ?? null,
                        'Pérdida (g)' => $v['perdida_masa'] ?? null,
                        '% Pérdida Fracción' => $r['perdida_pct'] ?? null,
                        '% Pérdida Ponderada' => $r['perdida_corregida'] ?? null,
                    ];
                }
                break;

            case 'RT-FM-62':
                $columnas = ['Muestra / Pozo', 'Descenso 20 min (cm)', 'Descenso 25 min (cm)', 'Descenso 30 min (cm)', 'Dif. 20-25 (cm)', 'Dif. 25-30 (cm)', 'Tasa Q (L/m²·día)', 'Tasa G (gal/ft²·día)'];
                foreach ($especimenes as $idx => $esp) {
                    $v = $esp['valores'] ?? [];
                    $r = $esp['redondeados'] ?? [];
                    $filasTabla[] = [
                        'Muestra / Pozo' => $v['codigo_muestra'] ?? ('Pozo #' . ($idx + 1)),
                        'Descenso 20 min (cm)' => $v['descenso_20min'] ?? null,
                        'Descenso 25 min (cm)' => $v['descenso_25min'] ?? null,
                        'Descenso 30 min (cm)' => $v['descenso_30min'] ?? null,
                        'Dif. 20-25 (cm)' => isset($v['dif_25']) && is_numeric($v['dif_25']) ? round((float)$v['dif_25'], 2) : null,
                        'Dif. 25-30 (cm)' => isset($v['dif_30']) && is_numeric($v['dif_30']) ? round((float)$v['dif_30'], 2) : null,
                        'Tasa Q (L/m²·día)' => $r['tasa_lm2_dia'] ?? null,
                        'Tasa G (gal/ft²·día)' => $r['tasa_galft2_dia'] ?? null,
                    ];
                }
                break;

            case 'RT-FM-64':
                $columnas = ['Varilla', 'Diámetro (mm)', 'Área (mm²)', 'Peso Unitario (kg/m)', 'Fuerza Máx. (kN)', 'Fuerza Máx. (lbf)', 'Fluencia (MPa)', 'Fluencia (psi)', 'Resistencia Tensión (psi)', 'Resistencia Tensión (kg/cm²)', 'Elongación (%)'];
                foreach ($especimenes as $idx => $esp) {
                    $v = $esp['valores'] ?? [];
                    $r = $esp['redondeados'] ?? [];
                    $filasTabla[] = [
                        'Varilla' => $v['codigo_muestra'] ?? ('V-' . ($idx + 1)),
                        'Diámetro (mm)' => isset($v['diametro_mm']) && is_numeric($v['diametro_mm']) ? round((float)$v['diametro_mm'], 2) : ($r['diametro_mm'] ?? null),
                        'Área (mm²)' => $r['area_mm2'] ?? (isset($v['area_mm2']) && is_numeric($v['area_mm2']) ? round((float)$v['area_mm2'], 2) : null),
                        'Peso Unitario (kg/m)' => $r['peso_unitario'] ?? (isset($v['peso_unitario']) && is_numeric($v['peso_unitario']) ? round((float)$v['peso_unitario'], 3) : null),
                        'Fuerza Máx. (kN)' => isset($v['fuerza_max_kn']) && is_numeric($v['fuerza_max_kn']) ? round((float)$v['fuerza_max_kn'], 2) : null,
                        'Fuerza Máx. (lbf)' => $r['fuerza_lbf'] ?? (isset($v['fuerza_lbf']) && is_numeric($v['fuerza_lbf']) ? round((float)$v['fuerza_lbf'], 0) : null),
                        'Fluencia (MPa)' => isset($v['fluencia_mpa']) && is_numeric($v['fluencia_mpa']) ? round((float)$v['fluencia_mpa'], 2) : null,
                        'Fluencia (psi)' => $r['fluencia_psi'] ?? (isset($v['fluencia_psi']) && is_numeric($v['fluencia_psi']) ? round((float)$v['fluencia_psi'], 0) : null),
                        'Resistencia Tensión (psi)' => $r['resistencia_tension_psi'] ?? (isset($v['resistencia_tension_psi']) && is_numeric($v['resistencia_tension_psi']) ? round((float)$v['resistencia_tension_psi'], 0) : null),
                        'Resistencia Tensión (kg/cm²)' => $r['resistencia_tension_kgcm2'] ?? (isset($v['resistencia_tension_kgcm2']) && is_numeric($v['resistencia_tension_kgcm2']) ? round((float)$v['resistencia_tension_kgcm2'], 0) : null),
                        'Elongación (%)' => $r['elongacion'] ?? $r['elongacion_excel'] ?? (isset($v['elongacion']) && is_numeric($v['elongacion']) ? round((float)$v['elongacion'], 1) : (isset($v['elongacion_excel']) && is_numeric($v['elongacion_excel']) ? round((float)$v['elongacion_excel'], 1) : null)),
                    ];
                }
                break;
        }

        return [
            'es_reporte_nuevo' => true,
            'codigo_formato_hc' => $cod,
            'letra_rt_fm_22' => null,
            'letra_rt_fm_22_sugerida' => $destino['letra_rt_fm_22_sugerida'] ?? null,
            'codigo_reporte_nuevo' => $destino['codigo_reporte_nuevo'],
            'titulo_reporte' => $destino['titulo_reporte'],
            'norma' => $spec['norma']['texto'] ?? '',
            'columnas' => $columnas,
            'filas' => $filasTabla,
            'agregaciones' => $agregaciones,
            'metadatos' => $metadatosExtra,
        ];
    }
}
