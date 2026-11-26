<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Remisiones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Throwable;

/**
 * Modelo de Control PT-13 Ver. 5 Rev. 2 (ISO/IEC 17025:2017):
 * - Revisión en 3 etapas (Revisión Técnica -> Revisión de Forma -> Autorización y Liberación de Calidad)
 * - Checklist CYCSA-RT-FM-65 (25 ítems Tabla 1 PT-13)
 * - Blindaje estricto anti-autoaprobación (Sec. 5.1 a)
 * - Registro formal de Unificación por Suplencia (Sec. 5.1 a)
 * - Registro permanente de Informes Anulados y bloqueo de reutilización de códigos (Sec. 5.6 b)
 */
class RevisionPt13Modelo extends ModeloBase
{
    /**
     * 25 ítems oficiales de verificación CYCSA-RT-FM-65 (CYCSA-PT-13 Ver. 5 Rev. 2 Tabla 1 — ISO/IEC 17025:2017).
     */
    public const ITEMS_CHECKLIST_RT_FM_65 = [
        '7.8.2.1_a' => [
            'clausula' => '7.8.2.1 a',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Título claro y adecuado del documento (p. ej., "Informe de Ensayo").',
        ],
        '7.8.2.1_b' => [
            'clausula' => '7.8.2.1 b',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Nombre y dirección oficial del laboratorio emisor (CYCSA).',
        ],
        '7.8.2.1_c' => [
            'clausula' => '7.8.2.1 c',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Lugar en que se realizaron las actividades de laboratorio (sede fija, obra del cliente o instalación temporal).',
        ],
        '7.8.2.1_d' => [
            'clausula' => '7.8.2.1 d',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Identificación única del informe, reconocimiento de todos sus componentes y clara identificación del final ("Página X de Y" y cierre).',
        ],
        '7.8.2.1_e' => [
            'clausula' => '7.8.2.1 e',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Nombre e información de contacto completa del cliente.',
        ],
        '7.8.2.1_f' => [
            'clausula' => '7.8.2.1 f',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Identificación inequívoca del método o norma de ensayo utilizada (ASTM, AASHTO o método interno).',
        ],
        '7.8.2.1_g' => [
            'clausula' => '7.8.2.1 g',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Descripción, identificación inequívoca y, cuando sea necesario, condición física del ítem ensayado.',
        ],
        '7.8.2.1_h' => [
            'clausula' => '7.8.2.1 h',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Fecha de recepción de los ítems de ensayo y fecha del muestreo cuando sea crítica para la validez de resultados.',
        ],
        '7.8.2.1_i' => [
            'clausula' => '7.8.2.1 i',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Fechas de ejecución de las actividades de ensayo en laboratorio o campo.',
        ],
        '7.8.2.1_j' => [
            'clausula' => '7.8.2.1 j',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Fecha de emisión oficial del informe de ensayo.',
        ],
        '7.8.2.1_k' => [
            'clausula' => '7.8.2.1 k',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Referencia al plan y método de muestreo utilizados por el laboratorio u otros organismos cuando sean pertinentes.',
        ],
        '7.8.2.1_l' => [
            'clausula' => '7.8.2.1 l',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Declaración de que los resultados se relacionan únicamente con los ítems sometidos a ensayo.',
        ],
        '7.8.2.1_m' => [
            'clausula' => '7.8.2.1 m',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Resultados presentados de forma exacta, clara, inequívoca y con sus unidades de medida correspondientes.',
        ],
        '7.8.2.1_n' => [
            'clausula' => '7.8.2.1 n',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Registro de adiciones, desviaciones o exclusiones respecto al método normalizado.',
        ],
        '7.8.2.1_o' => [
            'clausula' => '7.8.2.1 o',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Identificación clara de las personas que revisan y autorizan el informe de ensayo.',
        ],
        '7.8.2.1_p' => [
            'clausula' => '7.8.2.1 p',
            'categoria' => 'Requisitos Comunes del Informe',
            'descripcion' => 'Identificación clara cuando los resultados provengan de proveedores externos (laboratorio subcontratado).',
        ],
        'NC2_PJ' => [
            'clausula' => 'NC2 PJ',
            'categoria' => 'Acreditación y Símbolo ONA',
            'descripcion' => 'Uso conforme del símbolo/referencia de acreditación según política vigente (NC2 PJ) y distinción clara de ensayos no acreditados.',
        ],
        '7.8.2.2_a' => [
            'clausula' => '7.8.2.2 (1)',
            'categoria' => 'Responsabilidad e Información del Cliente',
            'descripcion' => 'Confirmación de responsabilidad del laboratorio por toda la información emitida, excepto datos provistos por el cliente.',
        ],
        '7.8.2.2_b' => [
            'clausula' => '7.8.2.2 (2)',
            'categoria' => 'Responsabilidad e Información del Cliente',
            'descripcion' => 'Identificación expresa de datos suministrados por el cliente y descargo de responsabilidad cuando puedan afectar la validez.',
        ],
        '7.8.3.1_a' => [
            'clausula' => '7.8.3.1 a',
            'categoria' => 'Requisitos Específicos de Ensayo',
            'descripcion' => 'Información sobre condiciones específicas del ensayo (condiciones ambientales, curado, preparación de muestra).',
        ],
        '7.8.3.1_b' => [
            'clausula' => '7.8.3.1 b',
            'categoria' => 'Requisitos Específicos de Ensayo',
            'descripcion' => 'Declaración de conformidad con especificaciones/requisitos y regla de decisión cuando sea pertinente.',
        ],
        '7.8.3.1_c' => [
            'clausula' => '7.8.3.1 c',
            'categoria' => 'Requisitos Específicos de Ensayo',
            'descripcion' => 'Incertidumbre de medición en la misma unidad del mensurando o término relativo cuando sea aplicable o solicitada.',
        ],
        '7.8.3.1_d' => [
            'clausula' => '7.8.3.1 d',
            'categoria' => 'Requisitos Específicos de Ensayo',
            'descripcion' => 'Opiniones e interpretaciones claramente identificadas, autorizadas y basadas estrictamente en los resultados obtenidos.',
        ],
        '7.8.3.1_e' => [
            'clausula' => '7.8.3.1 e',
            'categoria' => 'Requisitos Específicos de Ensayo',
            'descripcion' => 'Información adicional requerida por métodos específicos, autoridades regulatorias, clientes o grupos de clientes.',
        ],
        '7.8.3.2_7.8.5' => [
            'clausula' => '7.8.3.2 / 7.8.5',
            'categoria' => 'Información de Muestreo',
            'descripcion' => 'Datos completos de muestreo (fecha, identificación única, ubicación/croquis, plan/método y condiciones ambientales) cuando CYCSA realiza el muestreo.',
        ],
    ];

    /**
     * Lista los ensayos/matrices con resultados capturados (o con control PT-13 iniciado),
     * junto con su estado PT-13, Orden de Servicio y cliente.
     */
    public function obtenerListaRevisiones(string $busqueda = ''): array
    {
        $sql = "SELECT cd.id AS id_detalle_cotizacion,
                       cd.id_cotizacion,
                       cd.descripcion_ensayo,
                       cd.norma_astm,
                       cd.formato_reporte,
                       cd.resultados_json,
                       os.id AS id_os,
                       os.codigo_os,
                       os.nombre_proyecto,
                       COALESCE(cl.nombre_razon_social, '') AS cliente_nombre,
                       pt.id AS id_revision,
                       pt.codigo_informe,
                       pt.revisor_tecnico_id,
                       pt.revisor_tecnico_nombre,
                       pt.fecha_rev_tecnica,
                       pt.obs_rev_tecnica,
                       pt.asistente_coord_id,
                       pt.asistente_coord_nombre,
                       pt.fecha_rev_forma,
                       pt.obs_rev_forma,
                       pt.autorizador_calidad_id,
                       pt.autorizador_calidad_nombre,
                       pt.fecha_autorizacion,
                       pt.es_unificacion_suplencia,
                       pt.suplencia_funciones,
                       pt.suplencia_motivo,
                       pt.suplencia_referencia_autorizacion,
                       pt.checklist_json,
                       pt.laboratorio_externo_nombre,
                       pt.es_laboratorio_externo,
                       pt.adiciones_desviaciones,
                       pt.opiniones_interpretaciones,
                       pt.incertidumbre_medicion,
                       pt.solicitud_simplificacion_ref,
                       COALESCE(pt.estado_pt13, 'Pendiente') AS estado_pt13,
                       pt.updated_at AS pt_updated_at
                FROM cotizacion_detalles cd
                INNER JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion
                LEFT JOIN clientes cl ON cl.id = COALESCE(os.id_cliente_informe, os.id_cliente)
                LEFT JOIN informes_revisiones_pt13 pt ON pt.id_detalle_cotizacion = cd.id
                WHERE ((cd.resultados_json IS NOT NULL AND TRIM(cd.resultados_json) != '') OR pt.id IS NOT NULL)";

        $params = [];
        $q = trim($busqueda);
        if ($q !== '') {
            $termino = '%' . $q . '%';
            $sql .= " AND (os.codigo_os LIKE :q1
                           OR cl.nombre_razon_social LIKE :q2
                           OR cd.descripcion_ensayo LIKE :q3
                           OR pt.codigo_informe LIKE :q4
                           OR COALESCE(pt.estado_pt13, 'Pendiente') LIKE :q5)";
            $params = [
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino,
                'q4' => $termino,
                'q5' => $termino,
            ];
        }

        $sql .= " ORDER BY COALESCE(pt.updated_at, os.created_at) DESC, cd.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $metaCaptura = $this->extraerMetadatosCaptura($row['resultados_json'] ?? null);
            $row['usuario_captura_id'] = $metaCaptura['usuario_captura_id'];
            $row['usuario_captura_nombre'] = $metaCaptura['usuario_captura_nombre'];
            $row['codigo_informe_resuelto'] = trim((string)(
                $row['codigo_informe']
                ?: $metaCaptura['codigo_informe']
                ?: ('INF-' . ($row['codigo_os'] ?: 'OS') . '-' . $row['id_detalle_cotizacion'])
            ));

            $checklist = [];
            if (!empty($row['checklist_json'])) {
                $decChecklist = json_decode((string)$row['checklist_json'], true);
                if (is_array($decChecklist)) {
                    $checklist = $decChecklist;
                }
            }
            $row['checklist'] = $checklist;
            $cumplidos = 0;
            foreach (array_keys(self::ITEMS_CHECKLIST_RT_FM_65) as $clave) {
                $val = $checklist[$clave] ?? null;
                if ($val === true || $val === 1 || $val === '1' || $val === 'cumple' || $val === 'na') {
                    $cumplidos++;
                }
            }
            $row['checklist_completados'] = $cumplidos;
            $row['checklist_total'] = count(self::ITEMS_CHECKLIST_RT_FM_65);
        }
        unset($row);

        return $rows;
    }

    /**
     * Obtiene el registro PT-13 de un detalle específico de cotización.
     */
    public function obtenerPorDetalle(int $idDetalle): ?array
    {
        if ($idDetalle <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT pt.*,
                    cd.descripcion_ensayo,
                    cd.norma_astm,
                    cd.resultados_json,
                    os.codigo_os,
                    os.nombre_proyecto
             FROM informes_revisiones_pt13 pt
             LEFT JOIN cotizacion_detalles cd ON cd.id = pt.id_detalle_cotizacion
             LEFT JOIN ordenes_servicio os ON os.id = pt.id_os
             WHERE pt.id_detalle_cotizacion = :id_detalle
             LIMIT 1"
        );
        $stmt->execute(['id_detalle' => $idDetalle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['checklist'] = !empty($row['checklist_json'])
            ? (json_decode((string)$row['checklist_json'], true) ?: [])
            : [];
        $meta = $this->extraerMetadatosCaptura($row['resultados_json'] ?? null);
        $row['usuario_captura_id'] = $meta['usuario_captura_id'];

        return $row;
    }

    /**
     * Etapa 1 PT-13: Revisión Técnica (Supervisor / Jefe de Laboratorio).
     * Aplica blindaje anti-autoaprobación (Sec. 5.1 a) contra el analista que capturó resultados.
     */
    public function registrarRevisionTecnica(
        int $idDetalle,
        int $usuarioId,
        string $usuarioNombre,
        array $checklist,
        string $obs,
        array $extras = []
    ): array {
        $contexto = $this->obtenerContextoDetalle($idDetalle);
        if (!$contexto) {
            return ['exito' => false, 'error' => 'El detalle de ensayo especificado no existe.'];
        }

        $actual = $this->obtenerPorDetalle($idDetalle);
        if ($actual && ($actual['estado_pt13'] ?? '') === 'Anulado') {
            return ['exito' => false, 'error' => 'Este informe se encuentra anulado (PT-13 Sec. 5.6 b) y no admite modificaciones.'];
        }

        // Blindaje PT-13 Sec. 5.1 a: el analista que capturó los resultados no puede revisar ni autorizar
        $usuarioCapturaId = (int)$contexto['usuario_captura_id'];
        if ($usuarioCapturaId > 0 && $usuarioCapturaId === $usuarioId) {
            return [
                'exito' => false,
                'error' => 'Bloqueado por PT-13 Sec. 5.1 a (ISO/IEC 17025:2017): el analista que capturó los resultados no puede ejecutar la revisión técnica ni autorizar su propio informe.',
            ];
        }

        $codigoInforme = trim((string)($extras['codigo_informe'] ?? ($actual['codigo_informe'] ?? $contexto['codigo_informe'])));
        if ($codigoInforme !== '' && $this->esCodigoAnulado($codigoInforme)) {
            return [
                'exito' => false,
                'error' => "El código de informe '{$codigoInforme}' está anulado permanentemente (PT-13 Sec. 5.6 b) y no puede reutilizarse.",
            ];
        }

        $checklistPrevio = is_array($actual['checklist'] ?? null) ? $actual['checklist'] : [];
        $checklistFinal = $this->normalizarChecklist(array_merge($checklistPrevio, $checklist));

        $marcarObservado = !empty($extras['marcar_observado']);
        $tieneForma = !empty($actual['fecha_rev_forma']);
        $nuevoEstado = $marcarObservado
            ? 'Observado'
            : ($tieneForma ? 'Listo Para Calidad' : 'En Revision Forma');

        $esLabExterno = !empty($extras['es_laboratorio_externo']) ? 1 : (int)($actual['es_laboratorio_externo'] ?? 0);
        $labExternoNombre = array_key_exists('laboratorio_externo_nombre', $extras)
            ? $this->nullSiVacio((string)$extras['laboratorio_externo_nombre'])
            : ($actual['laboratorio_externo_nombre'] ?? null);
        $adiciones = array_key_exists('adiciones_desviaciones', $extras)
            ? $this->nullSiVacio((string)$extras['adiciones_desviaciones'])
            : ($actual['adiciones_desviaciones'] ?? null);
        $opiniones = array_key_exists('opiniones_interpretaciones', $extras)
            ? $this->nullSiVacio((string)$extras['opiniones_interpretaciones'])
            : ($actual['opiniones_interpretaciones'] ?? null);
        $incertidumbre = array_key_exists('incertidumbre_medicion', $extras)
            ? $this->nullSiVacio((string)$extras['incertidumbre_medicion'])
            : ($actual['incertidumbre_medicion'] ?? null);
        $simplificacion = array_key_exists('solicitud_simplificacion_ref', $extras)
            ? $this->nullSiVacio((string)$extras['solicitud_simplificacion_ref'])
            : ($actual['solicitud_simplificacion_ref'] ?? null);

        $ahora = date('Y-m-d H:i:s');
        $checklistJson = json_encode($checklistFinal, JSON_UNESCAPED_UNICODE);

        if ($actual) {
            $stmt = $this->db->prepare(
                "UPDATE informes_revisiones_pt13
                 SET id_os = COALESCE(id_os, :id_os),
                     codigo_informe = :codigo_informe,
                     revisor_tecnico_id = :rev_id,
                     revisor_tecnico_nombre = :rev_nombre,
                     fecha_rev_tecnica = :fecha_rev,
                     obs_rev_tecnica = :obs,
                     checklist_json = :checklist_json,
                     es_laboratorio_externo = :es_lab_ext,
                     laboratorio_externo_nombre = :lab_ext_nombre,
                     adiciones_desviaciones = :adiciones,
                     opiniones_interpretaciones = :opiniones,
                     incertidumbre_medicion = :incertidumbre,
                     solicitud_simplificacion_ref = :simplificacion,
                     estado_pt13 = :estado
                 WHERE id_detalle_cotizacion = :id_detalle"
            );
            $stmt->execute([
                'id_os' => $contexto['id_os'],
                'codigo_informe' => $this->nullSiVacio($codigoInforme),
                'rev_id' => $usuarioId,
                'rev_nombre' => trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Revisor Técnico',
                'fecha_rev' => $ahora,
                'obs' => $this->nullSiVacio($obs),
                'checklist_json' => $checklistJson,
                'es_lab_ext' => $esLabExterno,
                'lab_ext_nombre' => $labExternoNombre,
                'adiciones' => $adiciones,
                'opiniones' => $opiniones,
                'incertidumbre' => $incertidumbre,
                'simplificacion' => $simplificacion,
                'estado' => $nuevoEstado,
                'id_detalle' => $idDetalle,
            ]);
            $idReg = (int)$actual['id'];
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO informes_revisiones_pt13 (
                    id_detalle_cotizacion, id_os, codigo_informe,
                    revisor_tecnico_id, revisor_tecnico_nombre, fecha_rev_tecnica, obs_rev_tecnica,
                    checklist_json, es_laboratorio_externo, laboratorio_externo_nombre,
                    adiciones_desviaciones, opiniones_interpretaciones, incertidumbre_medicion,
                    solicitud_simplificacion_ref, estado_pt13
                 ) VALUES (
                    :id_detalle, :id_os, :codigo_informe,
                    :rev_id, :rev_nombre, :fecha_rev, :obs,
                    :checklist_json, :es_lab_ext, :lab_ext_nombre,
                    :adiciones, :opiniones, :incertidumbre,
                    :simplificacion, :estado
                 )"
            );
            $stmt->execute([
                'id_detalle' => $idDetalle,
                'id_os' => $contexto['id_os'],
                'codigo_informe' => $this->nullSiVacio($codigoInforme),
                'rev_id' => $usuarioId,
                'rev_nombre' => trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Revisor Técnico',
                'fecha_rev' => $ahora,
                'obs' => $this->nullSiVacio($obs),
                'checklist_json' => $checklistJson,
                'es_lab_ext' => $esLabExterno,
                'lab_ext_nombre' => $labExternoNombre,
                'adiciones' => $adiciones,
                'opiniones' => $opiniones,
                'incertidumbre' => $incertidumbre,
                'simplificacion' => $simplificacion,
                'estado' => $nuevoEstado,
            ]);
            $idReg = (int)$this->db->lastInsertId();
        }

        return [
            'exito' => true,
            'id' => $idReg,
            'estado_pt13' => $nuevoEstado,
            'fecha_rev_tecnica' => $ahora,
        ];
    }

    /**
     * Etapa 2 PT-13: Revisión de Forma (Asistente de Coordinación / Control Documental).
     */
    public function registrarRevisionForma(
        int $idDetalle,
        int $usuarioId,
        string $usuarioNombre,
        array $checklist,
        string $obs,
        bool $marcarObservado = false
    ): array {
        $contexto = $this->obtenerContextoDetalle($idDetalle);
        if (!$contexto) {
            return ['exito' => false, 'error' => 'El detalle de ensayo especificado no existe.'];
        }

        $actual = $this->obtenerPorDetalle($idDetalle);
        if ($actual && ($actual['estado_pt13'] ?? '') === 'Anulado') {
            return ['exito' => false, 'error' => 'Este informe se encuentra anulado (PT-13 Sec. 5.6 b) y no admite modificaciones.'];
        }

        $checklistPrevio = is_array($actual['checklist'] ?? null) ? $actual['checklist'] : [];
        $checklistFinal = $this->normalizarChecklist(array_merge($checklistPrevio, $checklist));

        $tieneTecnica = !empty($actual['fecha_rev_tecnica']);
        $nuevoEstado = $marcarObservado
            ? 'Observado'
            : ($tieneTecnica ? 'Listo Para Calidad' : 'En Revision Tecnica');

        $ahora = date('Y-m-d H:i:s');
        $checklistJson = json_encode($checklistFinal, JSON_UNESCAPED_UNICODE);

        if ($actual) {
            $stmt = $this->db->prepare(
                "UPDATE informes_revisiones_pt13
                 SET id_os = COALESCE(id_os, :id_os),
                     asistente_coord_id = :asist_id,
                     asistente_coord_nombre = :asist_nombre,
                     fecha_rev_forma = :fecha_forma,
                     obs_rev_forma = :obs,
                     checklist_json = :checklist_json,
                     estado_pt13 = :estado
                 WHERE id_detalle_cotizacion = :id_detalle"
            );
            $stmt->execute([
                'id_os' => $contexto['id_os'],
                'asist_id' => $usuarioId,
                'asist_nombre' => trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Asistente Coordinación',
                'fecha_forma' => $ahora,
                'obs' => $this->nullSiVacio($obs),
                'checklist_json' => $checklistJson,
                'estado' => $nuevoEstado,
                'id_detalle' => $idDetalle,
            ]);
            $idReg = (int)$actual['id'];
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO informes_revisiones_pt13 (
                    id_detalle_cotizacion, id_os, codigo_informe,
                    asistente_coord_id, asistente_coord_nombre, fecha_rev_forma, obs_rev_forma,
                    checklist_json, estado_pt13
                 ) VALUES (
                    :id_detalle, :id_os, :codigo_informe,
                    :asist_id, :asist_nombre, :fecha_forma, :obs,
                    :checklist_json, :estado
                 )"
            );
            $stmt->execute([
                'id_detalle' => $idDetalle,
                'id_os' => $contexto['id_os'],
                'codigo_informe' => $this->nullSiVacio($contexto['codigo_informe']),
                'asist_id' => $usuarioId,
                'asist_nombre' => trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Asistente Coordinación',
                'fecha_forma' => $ahora,
                'obs' => $this->nullSiVacio($obs),
                'checklist_json' => $checklistJson,
                'estado' => $nuevoEstado,
            ]);
            $idReg = (int)$this->db->lastInsertId();
        }

        return [
            'exito' => true,
            'id' => $idReg,
            'estado_pt13' => $nuevoEstado,
            'fecha_rev_forma' => $ahora,
        ];
    }

    /**
     * Etapa 3 PT-13: Autorización y Liberación (Gerencia / Calidad).
     * Reglas obligatorias:
     * 1. Anti-autoaprobación estricta (PT-13 Sec. 5.1 a): el analista que capturó datos NO puede autorizar.
     * 2. Requiere fecha_rev_tecnica Y fecha_rev_forma completadas, SALVO Unificación por Suplencia
     *    con suplencia_motivo y suplencia_referencia_autorizacion documentados.
     */
    public function autorizarYLiberar(
        int $idDetalle,
        int $usuarioId,
        string $usuarioNombre,
        array $opciones = []
    ): array {
        $contexto = $this->obtenerContextoDetalle($idDetalle);
        if (!$contexto) {
            return ['exito' => false, 'error' => 'El detalle de ensayo especificado no existe.'];
        }

        $actual = $this->obtenerPorDetalle($idDetalle);
        if ($actual && ($actual['estado_pt13'] ?? '') === 'Anulado') {
            return ['exito' => false, 'error' => 'No es posible autorizar un informe que ha sido anulado (PT-13 Sec. 5.6 b).'];
        }

        // 1. Anti-autoaprobación estricta (PT-13 Sec. 5.1 a)
        $usuarioCapturaId = (int)$contexto['usuario_captura_id'];
        if ($usuarioCapturaId > 0 && $usuarioCapturaId === $usuarioId) {
            return [
                'exito' => false,
                'error' => 'Bloqueado por PT-13 Sec. 5.1 a (ISO/IEC 17025:2017): el analista que realizó la captura de resultados no puede autorizar ni liberar su propio informe.',
            ];
        }

        $codigoInforme = trim((string)($opciones['codigo_informe'] ?? ($actual['codigo_informe'] ?? $contexto['codigo_informe'])));
        if ($codigoInforme !== '' && $this->esCodigoAnulado($codigoInforme)) {
            return [
                'exito' => false,
                'error' => "El código de informe '{$codigoInforme}' fue anulado previamente y no puede reutilizarse ni liberarse (PT-13 Sec. 5.6 b).",
            ];
        }

        // 2. Verificar 3 etapas o Unificación por Suplencia debidamente autorizada
        $tieneRevTecnica = !empty($actual['fecha_rev_tecnica']);
        $tieneRevForma = !empty($actual['fecha_rev_forma']);
        $esSuplencia = !empty($opciones['es_unificacion_suplencia']);
        $suplenciaFunciones = trim((string)($opciones['suplencia_funciones'] ?? ''));
        $suplenciaMotivo = trim((string)($opciones['suplencia_motivo'] ?? ''));
        $suplenciaRef = trim((string)($opciones['suplencia_referencia_autorizacion'] ?? ''));

        if (!($tieneRevTecnica && $tieneRevForma)) {
            if (!$esSuplencia) {
                return [
                    'exito' => false,
                    'error' => 'Faltan etapas previas de revisión PT-13 (Revisión Técnica y Revisión de Forma). Complete ambas etapas o registre una Unificación por Suplencia autorizada (PT-13 Sec. 5.1 a).',
                ];
            }

            if ($suplenciaMotivo === '' || $suplenciaRef === '') {
                return [
                    'exito' => false,
                    'error' => 'La Unificación por Suplencia (PT-13 Sec. 5.1 a) requiere documentar obligatoriamente el motivo y la referencia de autorización formal.',
                ];
            }

            if ($suplenciaFunciones === '') {
                $funcionesAsumidas = [];
                if (!$tieneRevTecnica) {
                    $funcionesAsumidas[] = 'Revisión Técnica';
                }
                if (!$tieneRevForma) {
                    $funcionesAsumidas[] = 'Revisión de Forma';
                }
                $funcionesAsumidas[] = 'Autorización y Liberación de Calidad';
                $suplenciaFunciones = implode(' + ', $funcionesAsumidas);
            }
        }

        $checklistPrevio = is_array($actual['checklist'] ?? null) ? $actual['checklist'] : [];
        $checklistNuevo = isset($opciones['checklist']) && is_array($opciones['checklist']) ? $opciones['checklist'] : [];
        $checklistFinal = $this->normalizarChecklist(array_merge($checklistPrevio, $checklistNuevo));
        $checklistJson = json_encode($checklistFinal, JSON_UNESCAPED_UNICODE);

        $ahora = date('Y-m-d H:i:s');
        $nombreAutorizador = trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Autorizador Calidad';

        $esLabExterno = array_key_exists('es_laboratorio_externo', $opciones)
            ? (!empty($opciones['es_laboratorio_externo']) ? 1 : 0)
            : (int)($actual['es_laboratorio_externo'] ?? 0);
        $labExternoNombre = array_key_exists('laboratorio_externo_nombre', $opciones)
            ? $this->nullSiVacio((string)$opciones['laboratorio_externo_nombre'])
            : ($actual['laboratorio_externo_nombre'] ?? null);
        $adiciones = array_key_exists('adiciones_desviaciones', $opciones)
            ? $this->nullSiVacio((string)$opciones['adiciones_desviaciones'])
            : ($actual['adiciones_desviaciones'] ?? null);
        $opiniones = array_key_exists('opiniones_interpretaciones', $opciones)
            ? $this->nullSiVacio((string)$opciones['opiniones_interpretaciones'])
            : ($actual['opiniones_interpretaciones'] ?? null);
        $incertidumbre = array_key_exists('incertidumbre_medicion', $opciones)
            ? $this->nullSiVacio((string)$opciones['incertidumbre_medicion'])
            : ($actual['incertidumbre_medicion'] ?? null);
        $simplificacion = array_key_exists('solicitud_simplificacion_ref', $opciones)
            ? $this->nullSiVacio((string)$opciones['solicitud_simplificacion_ref'])
            : ($actual['solicitud_simplificacion_ref'] ?? null);

        if ($actual) {
            $stmt = $this->db->prepare(
                "UPDATE informes_revisiones_pt13
                 SET id_os = COALESCE(id_os, :id_os),
                     codigo_informe = :codigo_informe,
                     autorizador_calidad_id = :aut_id,
                     autorizador_calidad_nombre = :aut_nombre,
                     fecha_autorizacion = :fecha_aut,
                     es_unificacion_suplencia = :es_sup,
                     suplencia_funciones = :sup_func,
                     suplencia_motivo = :sup_motivo,
                     suplencia_referencia_autorizacion = :sup_ref,
                     checklist_json = :checklist_json,
                     es_laboratorio_externo = :es_lab_ext,
                     laboratorio_externo_nombre = :lab_ext_nombre,
                     adiciones_desviaciones = :adiciones,
                     opiniones_interpretaciones = :opiniones,
                     incertidumbre_medicion = :incertidumbre,
                     solicitud_simplificacion_ref = :simplificacion,
                     estado_pt13 = 'Autorizado'
                 WHERE id_detalle_cotizacion = :id_detalle"
            );
            $stmt->execute([
                'id_os' => $contexto['id_os'],
                'codigo_informe' => $this->nullSiVacio($codigoInforme),
                'aut_id' => $usuarioId,
                'aut_nombre' => $nombreAutorizador,
                'fecha_aut' => $ahora,
                'es_sup' => $esSuplencia ? 1 : 0,
                'sup_func' => $esSuplencia ? $this->nullSiVacio($suplenciaFunciones) : null,
                'sup_motivo' => $esSuplencia ? $this->nullSiVacio($suplenciaMotivo) : null,
                'sup_ref' => $esSuplencia ? $this->nullSiVacio($suplenciaRef) : null,
                'checklist_json' => $checklistJson,
                'es_lab_ext' => $esLabExterno,
                'lab_ext_nombre' => $labExternoNombre,
                'adiciones' => $adiciones,
                'opiniones' => $opiniones,
                'incertidumbre' => $incertidumbre,
                'simplificacion' => $simplificacion,
                'id_detalle' => $idDetalle,
            ]);
            $idReg = (int)$actual['id'];
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO informes_revisiones_pt13 (
                    id_detalle_cotizacion, id_os, codigo_informe,
                    autorizador_calidad_id, autorizador_calidad_nombre, fecha_autorizacion,
                    es_unificacion_suplencia, suplencia_funciones, suplencia_motivo, suplencia_referencia_autorizacion,
                    checklist_json, es_laboratorio_externo, laboratorio_externo_nombre,
                    adiciones_desviaciones, opiniones_interpretaciones, incertidumbre_medicion,
                    solicitud_simplificacion_ref, estado_pt13
                 ) VALUES (
                    :id_detalle, :id_os, :codigo_informe,
                    :aut_id, :aut_nombre, :fecha_aut,
                    :es_sup, :sup_func, :sup_motivo, :sup_ref,
                    :checklist_json, :es_lab_ext, :lab_ext_nombre,
                    :adiciones, :opiniones, :incertidumbre,
                    :simplificacion, 'Autorizado'
                 )"
            );
            $stmt->execute([
                'id_detalle' => $idDetalle,
                'id_os' => $contexto['id_os'],
                'codigo_informe' => $this->nullSiVacio($codigoInforme),
                'aut_id' => $usuarioId,
                'aut_nombre' => $nombreAutorizador,
                'fecha_aut' => $ahora,
                'es_sup' => $esSuplencia ? 1 : 0,
                'sup_func' => $esSuplencia ? $this->nullSiVacio($suplenciaFunciones) : null,
                'sup_motivo' => $esSuplencia ? $this->nullSiVacio($suplenciaMotivo) : null,
                'sup_ref' => $esSuplencia ? $this->nullSiVacio($suplenciaRef) : null,
                'checklist_json' => $checklistJson,
                'es_lab_ext' => $esLabExterno,
                'lab_ext_nombre' => $labExternoNombre,
                'adiciones' => $adiciones,
                'opiniones' => $opiniones,
                'incertidumbre' => $incertidumbre,
                'simplificacion' => $simplificacion,
            ]);
            $idReg = (int)$this->db->lastInsertId();
        }

        return [
            'exito' => true,
            'id' => $idReg,
            'estado_pt13' => 'Autorizado',
            'es_unificacion_suplencia' => $esSuplencia ? 1 : 0,
            'fecha_autorizacion' => $ahora,
        ];
    }

    /**
     * Registra la anulación permanente de un informe y bloquea la reutilización de su código (PT-13 Sec. 5.6 b).
     */
    public function anularInforme(
        string $codigoInforme,
        ?int $idDetalle,
        ?int $idOs,
        string $motivo,
        int $usuarioId,
        string $usuarioNombre
    ): array {
        $codigo = trim($codigoInforme);
        $motivoLimpio = trim($motivo);

        if ($codigo === '') {
            return ['exito' => false, 'error' => 'Debe especificar el código del informe a anular.'];
        }
        if ($motivoLimpio === '') {
            return ['exito' => false, 'error' => 'Debe registrar el motivo técnico o documental de la anulación.'];
        }
        if ($this->esCodigoAnulado($codigo)) {
            return [
                'exito' => false,
                'error' => "El código de informe '{$codigo}' ya fue anulado previamente y permanece bloqueado (PT-13 Sec. 5.6 b).",
            ];
        }

        $iniciadaAqui = !$this->db->inTransaction();
        if ($iniciadaAqui) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO informes_anulados (
                    codigo_informe, id_detalle_cotizacion, id_os, motivo, usuario_id, usuario_nombre
                 ) VALUES (
                    :codigo_informe, :id_detalle, :id_os, :motivo, :usuario_id, :usuario_nombre
                 )"
            );
            $stmt->execute([
                'codigo_informe' => $codigo,
                'id_detalle' => ($idDetalle !== null && $idDetalle > 0) ? $idDetalle : null,
                'id_os' => ($idOs !== null && $idOs > 0) ? $idOs : null,
                'motivo' => $motivoLimpio,
                'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
                'usuario_nombre' => trim($usuarioNombre) !== '' ? trim($usuarioNombre) : 'Usuario Sistema',
            ]);
            $idAnulado = (int)$this->db->lastInsertId();

            // Marcar el registro PT-13 como Anulado si existe o si viene idDetalle
            if ($idDetalle !== null && $idDetalle > 0) {
                $actual = $this->obtenerPorDetalle($idDetalle);
                if ($actual) {
                    $stmtUpd = $this->db->prepare(
                        "UPDATE informes_revisiones_pt13
                         SET estado_pt13 = 'Anulado',
                             codigo_informe = COALESCE(NULLIF(codigo_informe, ''), :codigo)
                         WHERE id_detalle_cotizacion = :id_detalle"
                    );
                    $stmtUpd->execute([
                        'codigo' => $codigo,
                        'id_detalle' => $idDetalle,
                    ]);
                } else {
                    $ctx = $this->obtenerContextoDetalle($idDetalle);
                    if ($ctx) {
                        $stmtIns = $this->db->prepare(
                            "INSERT INTO informes_revisiones_pt13 (
                                id_detalle_cotizacion, id_os, codigo_informe, estado_pt13
                             ) VALUES (:id_detalle, :id_os, :codigo, 'Anulado')"
                        );
                        $stmtIns->execute([
                            'id_detalle' => $idDetalle,
                            'id_os' => ($idOs !== null && $idOs > 0) ? $idOs : $ctx['id_os'],
                            'codigo' => $codigo,
                        ]);
                    }
                }
            } else {
                $stmtByCode = $this->db->prepare(
                    "UPDATE informes_revisiones_pt13
                     SET estado_pt13 = 'Anulado'
                     WHERE codigo_informe = :codigo"
                );
                $stmtByCode->execute(['codigo' => $codigo]);
            }

            if ($iniciadaAqui) {
                $this->db->commit();
            }

            return [
                'exito' => true,
                'id' => $idAnulado,
                'codigo_informe' => $codigo,
            ];
        } catch (Throwable $e) {
            if ($iniciadaAqui && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['exito' => false, 'error' => 'Error al registrar la anulación del informe: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica si un código de informe está registrado como anulado (bloqueo permanente PT-13 Sec. 5.6 b).
     */
    public function esCodigoAnulado(string $codigoInforme): bool
    {
        $codigo = trim($codigoInforme);
        if ($codigo === '') {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM informes_anulados WHERE LOWER(TRIM(codigo_informe)) = LOWER(:codigo)"
        );
        $stmt->execute(['codigo' => $codigo]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Lista todos los informes anulados registrados con trazabilidad de O/S y responsable.
     */
    public function obtenerInformesAnulados(): array
    {
        $sql = "SELECT ia.*,
                       os.codigo_os,
                       os.nombre_proyecto,
                       cd.descripcion_ensayo
                FROM informes_anulados ia
                LEFT JOIN ordenes_servicio os ON os.id = ia.id_os
                LEFT JOIN cotizacion_detalles cd ON cd.id = ia.id_detalle_cotizacion
                ORDER BY ia.id DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene contexto de cotizacion_detalles + ordenes_servicio + usuario_captura_id.
     */
    private function obtenerContextoDetalle(int $idDetalle): ?array
    {
        if ($idDetalle <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT cd.id,
                    cd.id_cotizacion,
                    cd.descripcion_ensayo,
                    cd.resultados_json,
                    os.id AS id_os,
                    os.codigo_os
             FROM cotizacion_detalles cd
             LEFT JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion
             WHERE cd.id = :id_detalle
             ORDER BY os.id DESC
             LIMIT 1"
        );
        $stmt->execute(['id_detalle' => $idDetalle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $meta = $this->extraerMetadatosCaptura($row['resultados_json'] ?? null);
        return [
            'id_detalle' => (int)$row['id'],
            'id_os' => !empty($row['id_os']) ? (int)$row['id_os'] : null,
            'codigo_os' => (string)($row['codigo_os'] ?? ''),
            'codigo_informe' => $meta['codigo_informe'] !== ''
                ? $meta['codigo_informe']
                : ('INF-' . (!empty($row['codigo_os']) ? $row['codigo_os'] : 'OS') . '-' . (int)$row['id']),
            'usuario_captura_id' => $meta['usuario_captura_id'],
            'usuario_captura_nombre' => $meta['usuario_captura_nombre'],
        ];
    }

    /**
     * Extrae usuario_captura_id y código de informe desde resultados_json.
     */
    private function extraerMetadatosCaptura(?string $resultadosJson): array
    {
        $meta = [
            'usuario_captura_id' => 0,
            'usuario_captura_nombre' => '',
            'codigo_informe' => '',
        ];

        if ($resultadosJson === null || trim($resultadosJson) === '') {
            return $meta;
        }

        $dec = json_decode($resultadosJson, true);
        if (!is_array($dec)) {
            return $meta;
        }

        $meta['usuario_captura_id'] = (int)($dec['usuario_captura_id'] ?? ($dec['revision']['usuario_envio_id'] ?? 0));
        $meta['usuario_captura_nombre'] = trim((string)($dec['usuario_captura_nombre'] ?? ($dec['revision']['usuario_envio_nombre'] ?? '')));
        $meta['codigo_informe'] = trim((string)($dec['codigo_informe'] ?? ($dec['numero_informe'] ?? ($dec['datos_generales']['codigo_informe'] ?? ''))));

        return $meta;
    }

    /**
     * Normaliza las claves del checklist CYCSA-RT-FM-65 permitiendo solo los 25 ítems oficiales.
     */
    private function normalizarChecklist(array $entrada): array
    {
        $resultado = [];
        foreach (array_keys(self::ITEMS_CHECKLIST_RT_FM_65) as $clave) {
            if (!array_key_exists($clave, $entrada)) {
                continue;
            }
            $valor = $entrada[$clave];
            if (is_bool($valor)) {
                $resultado[$clave] = $valor ? 'cumple' : 'no_cumple';
            } else {
                $str = strtolower(trim((string)$valor));
                if (in_array($str, ['1', 'true', 'si', 'sí', 'cumple', 'on'], true)) {
                    $resultado[$clave] = 'cumple';
                } elseif (in_array($str, ['na', 'n/a', 'no_aplica'], true)) {
                    $resultado[$clave] = 'na';
                } elseif (in_array($str, ['0', 'false', 'no', 'no_cumple'], true)) {
                    $resultado[$clave] = 'no_cumple';
                }
            }
        }
        return $resultado;
    }

    private function nullSiVacio(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $limpio = trim($valor);
        return $limpio === '' ? null : $limpio;
    }
}
