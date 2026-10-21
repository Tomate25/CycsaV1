<?php

namespace Cycsa\Modulos\Configuracion\Modelos;

use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\EvaluadorFormula;
use PDO;
use Exception;
use Throwable;

class PlantillaModelo {

    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Obtiene el listado de plantillas con soporte para filtros por estado, cliente o ensayo.
     */
    public function obtenerPlantillas(array $filtros = []): array {
        $sql = "SELECT p.*,
                       pr.ensayo_servicio,
                       pr.nombre_comercial AS ensayo_nombre_comercial,
                       pr.no_item AS ensayo_item,
                       c.nombre_razon_social AS cliente_nombre,
                       padre.nombre AS plantilla_padre_nombre,
                       u_crea.nombre AS creador_nombre,
                       u_aprob.nombre AS aprobador_nombre,
                       (SELECT COUNT(*) FROM plantilla_versiones pv WHERE pv.plantilla_id = p.id) AS total_versiones
                FROM plantillas p
                LEFT JOIN productos pr ON p.ensayo_id = pr.id
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN plantillas padre ON p.plantilla_padre_id = padre.id
                LEFT JOIN usuarios u_crea ON p.creado_por = u_crea.id
                LEFT JOIN usuarios u_aprob ON p.aprobado_por = u_aprob.id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['ensayo_id'])) {
            $sql .= " AND p.ensayo_id = :ensayo_id";
            $params['ensayo_id'] = (int)$filtros['ensayo_id'];
        }

        if (array_key_exists('cliente_id', $filtros)) {
            if ($filtros['cliente_id'] === null) {
                $sql .= " AND p.cliente_id IS NULL";
            } elseif ($filtros['cliente_id'] > 0) {
                $sql .= " AND p.cliente_id = :cliente_id";
                $params['cliente_id'] = (int)$filtros['cliente_id'];
            }
        }

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (p.nombre LIKE :b OR p.codigo LIKE :b OR p.norma LIKE :b)";
            $params['b'] = '%' . $filtros['buscar'] . '%';
        }

        $sql .= " ORDER BY (p.cliente_id IS NOT NULL) DESC, p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una plantilla completa con sus secciones, campos, variables vinculadas y versiones.
     */
    public function obtenerPlantillaPorId(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   pr.ensayo_servicio,
                   pr.nombre_comercial AS ensayo_nombre_comercial,
                   pr.no_item AS ensayo_item,
                   c.nombre_razon_social AS cliente_nombre,
                   padre.nombre AS plantilla_padre_nombre,
                   u_crea.nombre AS creador_nombre,
                   u_aprob.nombre AS aprobador_nombre
            FROM plantillas p
            LEFT JOIN productos pr ON p.ensayo_id = pr.id
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN plantillas padre ON p.plantilla_padre_id = padre.id
            LEFT JOIN usuarios u_crea ON p.creado_por = u_crea.id
            LEFT JOIN usuarios u_aprob ON p.aprobado_por = u_aprob.id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plantilla) {
            return null;
        }

        // Secciones
        $stmtSec = $this->db->prepare("SELECT * FROM plantilla_secciones WHERE plantilla_id = :pid ORDER BY orden ASC, id ASC");
        $stmtSec->execute(['pid' => $id]);
        $secciones = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

        // Campos por sección
        $stmtCampos = $this->db->prepare("
            SELECT pc.*,
                   v.codigo AS variable_codigo,
                   v.etiqueta AS variable_etiqueta,
                   v.tipo AS variable_tipo,
                   v.decimales AS variable_decimales,
                   v.minimo AS variable_minimo,
                   v.maximo AS variable_maximo,
                   v.opciones_lista AS variable_opciones,
                   um.simbolo AS unidad_simbolo,
                   um.nombre AS unidad_nombre
            FROM plantilla_campos pc
            JOIN variables v ON pc.variable_id = v.id
            LEFT JOIN unidades_medida um ON v.unidad_id = um.id
            WHERE pc.plantilla_id = :pid
            ORDER BY pc.seccion_id ASC, pc.orden ASC, pc.fila ASC, pc.columna ASC
        ");
        $stmtCampos->execute(['pid' => $id]);
        $todosCampos = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);

        $camposPorSeccion = [];
        foreach ($todosCampos as $c) {
            $secId = (int)$c['seccion_id'];
            if (!isset($camposPorSeccion[$secId])) {
                $camposPorSeccion[$secId] = [];
            }
            $camposPorSeccion[$secId][] = $c;
        }

        foreach ($secciones as &$sec) {
            $sec['campos'] = $camposPorSeccion[(int)$sec['id']] ?? [];
        }
        unset($sec);

        $plantilla['secciones'] = $secciones;

        // Historial de versiones
        $stmtVer = $this->db->prepare("
            SELECT pv.id, pv.version, pv.hash_integridad, pv.fecha_aprobacion, pv.motivo_version,
                   u.nombre AS aprobado_por_nombre
            FROM plantilla_versiones pv
            LEFT JOIN usuarios u ON pv.aprobado_por = u.id
            WHERE pv.plantilla_id = :pid
            ORDER BY pv.id DESC
        ");
        $stmtVer->execute(['pid' => $id]);
        $plantilla['versiones'] = $stmtVer->fetchAll(PDO::FETCH_ASSOC);

        return $plantilla;
    }

    /**
     * Obtiene las plantillas disponibles y aprobadas para un ensayo específico.
     * Ubica en primer lugar las variantes específicas del cliente (si existen),
     * luego las plantillas asociadas a este ensayo, y finalmente todas las demás
     * plantillas generales aprobadas para que el laboratorio nunca quede sin opciones.
     */
    public function obtenerPlantillasDisponiblesParaEnsayo(int $ensayoId = 0, ?int $clienteId = null): array {
        $especificas = [];
        if ($ensayoId > 0) {
            $sqlEsp = "SELECT p.id,
                              p.codigo,
                              p.nombre,
                              p.norma,
                              p.version_actual,
                              p.cliente_id,
                              p.es_por_defecto,
                              p.orientacion,
                              p.fondo_reporte_id,
                              p.sello_acreditacion_url,
                              c.nombre_razon_social AS cliente_nombre,
                              pv.id AS version_id,
                              pv.hash_integridad,
                              (CASE WHEN p.cliente_id IS NOT NULL AND p.cliente_id = :cid THEN 1 ELSE 0 END) AS es_variante_cliente,
                              1 AS es_del_ensayo
                       FROM plantillas p
                       LEFT JOIN clientes c ON p.cliente_id = c.id
                       LEFT JOIN plantilla_versiones pv ON (pv.plantilla_id = p.id AND pv.version = p.version_actual)
                       WHERE p.ensayo_id = :eid
                         AND p.estado = 'aprobada'
                         AND (p.cliente_id IS NULL OR p.cliente_id = :cid2)
                       ORDER BY es_variante_cliente DESC, p.es_por_defecto DESC, p.id DESC";

            $stmtEsp = $this->db->prepare($sqlEsp);
            $stmtEsp->execute([
                'eid' => $ensayoId,
                'cid' => (int)($clienteId ?? 0),
                'cid2' => (int)($clienteId ?? 0)
            ]);
            $especificas = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
        }

        // Obtener además todas las demás plantillas aprobadas generales de CYCSA
        $idsExcluir = array_column($especificas, 'id');
        $notInClause = !empty($idsExcluir) ? (" AND p.id NOT IN (" . implode(',', array_map('intval', $idsExcluir)) . ")") : "";

        $sqlGen = "SELECT p.id,
                          p.codigo,
                          p.nombre,
                          p.norma,
                          p.version_actual,
                          p.cliente_id,
                          p.es_por_defecto,
                          p.orientacion,
                          p.fondo_reporte_id,
                          p.sello_acreditacion_url,
                          c.nombre_razon_social AS cliente_nombre,
                          pv.id AS version_id,
                          pv.hash_integridad,
                          0 AS es_variante_cliente,
                          0 AS es_del_ensayo
                   FROM plantillas p
                   LEFT JOIN clientes c ON p.cliente_id = c.id
                   LEFT JOIN plantilla_versiones pv ON (pv.plantilla_id = p.id AND pv.version = p.version_actual)
                   WHERE p.estado = 'aprobada'
                     AND (p.cliente_id IS NULL OR p.cliente_id = :cid)
                     {$notInClause}
                   ORDER BY p.nombre ASC, p.id DESC";

        $stmtGen = $this->db->prepare($sqlGen);
        $stmtGen->execute([
            'cid' => (int)($clienteId ?? 0)
        ]);
        $generales = $stmtGen->fetchAll(PDO::FETCH_ASSOC);

        $todas = array_merge($especificas, $generales);

        foreach ($todas as &$r) {
            $tipoTexto = !empty($r['es_variante_cliente']) ? (' · Variante: ' . ($r['cliente_nombre'] ?? 'Cliente')) : ' · General';
            $r['etiqueta_selector'] = "{$r['codigo']} · {$r['nombre']} · {$r['norma']} · {$r['version_actual']}{$tipoTexto}";
        }
        unset($r);

        return $todas;
    }

    /**
     * Devuelve la plantilla sugerida por prefill:
     * 1. Variante aprobada para el cliente asociada a este ensayo
     * 2. Plantilla recomendada específica para este ensayo
     * 3. Si no, plantilla general marcada como por defecto
     * 4. Si no, la primera plantilla general aprobada disponible
     */
    public function obtenerPlantillaSugerida(int $ensayoId = 0, ?int $clienteId = null): ?array {
        $disponibles = $this->obtenerPlantillasDisponiblesParaEnsayo($ensayoId, $clienteId);
        if (empty($disponibles)) {
            return null;
        }

        // 1. Variante del cliente para este ensayo
        foreach ($disponibles as $d) {
            if (!empty($d['es_variante_cliente']) && !empty($d['es_del_ensayo'])) {
                return $d;
            }
        }

        // 2. Plantilla recomendada específica para este ensayo
        foreach ($disponibles as $d) {
            if (!empty($d['es_del_ensayo'])) {
                return $d;
            }
        }

        // 3. General por defecto
        foreach ($disponibles as $d) {
            if (!empty($d['es_por_defecto'])) {
                return $d;
            }
        }

        // 4. Primera disponible
        return $disponibles[0];
    }

    /**
     * Guarda o actualiza la configuración estructural de una plantilla (cabecera, secciones y campos).
     */
    public function guardarPlantillaEstructural(array $datos, int $usuarioId): int {
        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) {
            $this->db->beginTransaction();
        }
        try {
            $id = (int)($datos['id'] ?? 0);
            $codigo = trim($datos['codigo'] ?? '');
            $nombre = trim($datos['nombre'] ?? '');
            $ensayoId = (int)($datos['ensayo_id'] ?? 0);
            $norma = trim($datos['norma'] ?? '');
            $proc = trim($datos['procedimiento_interno'] ?? '');
            $orientacion = in_array($datos['orientacion'] ?? '', ['vertical', 'horizontal'], true) ? $datos['orientacion'] : 'horizontal';
            $fondo = trim($datos['fondo_reporte_id'] ?? ($orientacion === 'vertical' ? 'hoja_vertical.jpg' : 'hoja_membretada_horizontal.jpg'));
            $incluirLogo = isset($datos['incluir_logo_cycsa']) ? (int)$datos['incluir_logo_cycsa'] : 1;
            $incluirSello = isset($datos['incluir_sello_acreditacion']) ? (int)$datos['incluir_sello_acreditacion'] : 1;
            $selloUrl = trim($datos['sello_acreditacion_url'] ?? 'publico/img/sello_acreditacion_default.jpeg');
            if (empty($selloUrl)) $selloUrl = 'publico/img/sello_acreditacion_default.jpeg';
            $clienteId = !empty($datos['cliente_id']) ? (int)$datos['cliente_id'] : null;
            $padreId = !empty($datos['plantilla_padre_id']) ? (int)$datos['plantilla_padre_id'] : null;
            $estado = in_array($datos['estado'] ?? '', ['borrador', 'en_revision', 'aprobada', 'obsoleta'], true) ? $datos['estado'] : 'borrador';
            $esDefault = !empty($datos['es_por_defecto']) ? 1 : 0;

            if ($id > 0) {
                // Actualizar cabecera
                $stmt = $this->db->prepare("
                    UPDATE plantillas SET
                        codigo = :cod,
                        nombre = :nom,
                        ensayo_id = :eid,
                        norma = :norma,
                        procedimiento_interno = :proc,
                        orientacion = :ori,
                        fondo_reporte_id = :fondo,
                        incluir_logo_cycsa = :logo,
                        incluir_sello_acreditacion = :sello,
                        sello_acreditacion_url = :sello_url,
                        cliente_id = :cid,
                        plantilla_padre_id = :pid,
                        es_por_defecto = :def,
                        estado = :estado
                    WHERE id = :id
                ");
                $stmt->execute([
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'eid' => $ensayoId,
                    'norma' => $norma,
                    'proc' => $proc,
                    'ori' => $orientacion,
                    'fondo' => $fondo,
                    'logo' => $incluirLogo,
                    'sello' => $incluirSello,
                    'sello_url' => $selloUrl,
                    'cid' => $clienteId,
                    'pid' => $padreId,
                    'def' => $esDefault,
                    'estado' => $estado,
                    'id' => $id
                ]);
            } else {
                // Insertar nueva cabecera
                $stmt = $this->db->prepare("
                    INSERT INTO plantillas (
                        codigo, nombre, ensayo_id, norma, procedimiento_interno,
                        orientacion, fondo_reporte_id, incluir_logo_cycsa, incluir_sello_acreditacion, sello_acreditacion_url,
                        cliente_id, plantilla_padre_id, estado, version_actual, es_por_defecto, creado_por
                    ) VALUES (
                        :cod, :nom, :eid, :norma, :proc,
                        :ori, :fondo, :logo, :sello, :sello_url,
                        :cid, :pid, :estado, 'Rev. 01', :def, :creado_por
                    )
                ");
                $stmt->execute([
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'eid' => $ensayoId,
                    'norma' => $norma,
                    'proc' => $proc,
                    'ori' => $orientacion,
                    'fondo' => $fondo,
                    'logo' => $incluirLogo,
                    'sello' => $incluirSello,
                    'sello_url' => $selloUrl,
                    'cid' => $clienteId,
                    'pid' => $padreId,
                    'estado' => $estado,
                    'def' => $esDefault,
                    'creado_por' => $usuarioId
                ]);
                $id = (int)$this->db->lastInsertId();
            }

            // Si es marcada como por defecto para el ensayo, desmarcar otras generales del mismo ensayo
            if ($esDefault && $clienteId === null) {
                $this->db->prepare("UPDATE plantillas SET es_por_defecto = 0 WHERE ensayo_id = :eid AND id != :id AND cliente_id IS NULL")
                         ->execute(['eid' => $ensayoId, 'id' => $id]);
            }

            // Procesar Secciones y Campos si se enviaron en los datos
            if (isset($datos['secciones']) && is_array($datos['secciones'])) {
                // Eliminar campos y secciones anteriores de la plantilla
                $this->db->prepare("DELETE FROM plantilla_campos WHERE plantilla_id = :pid")->execute(['pid' => $id]);
                $this->db->prepare("DELETE FROM plantilla_secciones WHERE plantilla_id = :pid")->execute(['pid' => $id]);

                $stmtSecIns = $this->db->prepare("
                    INSERT INTO plantilla_secciones (plantilla_id, nombre, orden, tipo_layout)
                    VALUES (:pid, :nom, :ord, :layout)
                ");
                $stmtCmpIns = $this->db->prepare("
                    INSERT INTO plantilla_campos (plantilla_id, seccion_id, variable_id, orden, fila, columna, ancho_cols, formula, visible_en_reporte, solo_lectura, texto_ayuda)
                    VALUES (:pid, :sec_id, :var_id, :ord, :fila, :col, :ancho, :formula, :vis, :ro, :ayuda)
                ");

                $secOrden = 1;
                foreach ($datos['secciones'] as $sec) {
                    $secNombre = trim($sec['nombre'] ?? ("Sección " . $secOrden));
                    $secLayout = in_array($sec['tipo_layout'] ?? '', ['formulario', 'tabla_matriz'], true) ? $sec['tipo_layout'] : 'formulario';

                    $stmtSecIns->execute([
                        'pid' => $id,
                        'nom' => $secNombre,
                        'ord' => $secOrden,
                        'layout' => $secLayout
                    ]);
                    $seccionId = (int)$this->db->lastInsertId();

                    if (!empty($sec['campos']) && is_array($sec['campos'])) {
                        $cmpOrden = 1;
                        foreach ($sec['campos'] as $c) {
                            $varId = (int)($c['variable_id'] ?? 0);
                            if ($varId <= 0) continue;

                            $stmtCmpIns->execute([
                                'pid' => $id,
                                'sec_id' => $seccionId,
                                'var_id' => $varId,
                                'ord' => $cmpOrden,
                                'fila' => (int)($c['fila'] ?? 1),
                                'col' => (int)($c['columna'] ?? 1),
                                'ancho' => (int)($c['ancho_cols'] ?? 4),
                                'formula' => !empty($c['formula']) ? trim($c['formula']) : null,
                                'vis' => isset($c['visible_en_reporte']) ? (int)$c['visible_en_reporte'] : 1,
                                'ro' => !empty($c['solo_lectura']) ? 1 : 0,
                                'ayuda' => !empty($c['texto_ayuda']) ? trim($c['texto_ayuda']) : null
                            ]);
                            $cmpOrden++;
                        }
                    }
                    $secOrden++;
                }
            }

            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Clona una plantilla base para crear una variante específica asignada a un cliente.
     */
    public function duplicarComoVariante(int $plantillaId, int $clienteId, int $usuarioId, string $nuevoNombre = ''): int {
        $base = $this->obtenerPlantillaPorId($plantillaId);
        if (!$base) {
            throw new Exception("Plantilla original #{$plantillaId} no encontrada.");
        }

        // Obtener nombre del cliente
        $stC = $this->db->prepare("SELECT nombre_razon_social FROM clientes WHERE id = :cid LIMIT 1");
        $stC->execute(['cid' => $clienteId]);
        $nomCli = $stC->fetchColumn() ?: ('Cliente #' . $clienteId);

        $nombreFinal = !empty($nuevoNombre) ? trim($nuevoNombre) : ($base['nombre'] . ' - Variante ' . $nomCli);
        $codigoSlug = $base['codigo'] . '-VAR-' . $clienteId;

        $datosClon = [
            'id' => 0,
            'codigo' => $codigoSlug,
            'nombre' => $nombreFinal,
            'ensayo_id' => $base['ensayo_id'],
            'norma' => $base['norma'],
            'procedimiento_interno' => $base['procedimiento_interno'],
            'orientacion' => $base['orientacion'],
            'fondo_reporte_id' => $base['fondo_reporte_id'],
            'incluir_logo_cycsa' => $base['incluir_logo_cycsa'],
            'incluir_sello_acreditacion' => $base['incluir_sello_acreditacion'],
            'sello_acreditacion_url' => $base['sello_acreditacion_url'],
            'cliente_id' => $clienteId,
            'plantilla_padre_id' => $plantillaId,
            'estado' => 'borrador',
            'es_por_defecto' => 0,
            'secciones' => []
        ];

        foreach ($base['secciones'] as $sec) {
            $secClon = [
                'nombre' => $sec['nombre'],
                'tipo_layout' => $sec['tipo_layout'],
                'campos' => []
            ];
            foreach ($sec['campos'] as $cmp) {
                $secClon['campos'][] = [
                    'variable_id' => $cmp['variable_id'],
                    'fila' => $cmp['fila'],
                    'columna' => $cmp['columna'],
                    'ancho_cols' => $cmp['ancho_cols'],
                    'formula' => $cmp['formula'],
                    'visible_en_reporte' => $cmp['visible_en_reporte'],
                    'solo_lectura' => $cmp['solo_lectura'],
                    'texto_ayuda' => $cmp['texto_ayuda']
                ];
            }
            $datosClon['secciones'][] = $secClon;
        }

        return $this->guardarPlantillaEstructural($datosClon, $usuarioId);
    }

    /**
     * Aprueba formalmente una plantilla creando una versión inmutable con hash SHA-256.
     */
    public function aprobarPlantilla(int $plantillaId, int $usuarioAprobadorId, string $motivo = ''): int {
        $plantilla = $this->obtenerPlantillaPorId($plantillaId);
        if (!$plantilla) {
            throw new Exception("Plantilla no encontrada.");
        }

        // Calcular el número correlativo de versión
        $numVersiones = count($plantilla['versiones']);
        $proximaRev = sprintf("Rev. %02d", $numVersiones + 1);

        // Generar esquema JSON canónico inmutable
        $esquema = [
            'id' => $plantilla['id'],
            'codigo' => $plantilla['codigo'],
            'nombre' => $plantilla['nombre'],
            'version' => $proximaRev,
            'ensayo_id' => $plantilla['ensayo_id'],
            'norma' => $plantilla['norma'],
            'orientacion' => $plantilla['orientacion'],
            'fondo_reporte_id' => $plantilla['fondo_reporte_id'],
            'incluir_logo_cycsa' => $plantilla['incluir_logo_cycsa'],
            'incluir_sello_acreditacion' => $plantilla['incluir_sello_acreditacion'],
            'sello_acreditacion_url' => $plantilla['sello_acreditacion_url'],
            'cliente_id' => $plantilla['cliente_id'],
            'secciones' => $plantilla['secciones'],
            'aprobado_por' => $usuarioAprobadorId,
            'fecha_aprobacion' => date('Y-m-d H:i:s')
        ];
        $esquemaJson = json_encode($esquema, JSON_UNESCAPED_UNICODE);
        $hashIntegridad = hash('sha256', $esquemaJson);

        // Generar compatibilidad legacy con el formato JSON que consumen reportes antiguos
        $columnsLegacy = [];
        $methodsLegacy = [];
        foreach ($plantilla['secciones'] as $sec) {
            foreach ($sec['campos'] as $c) {
                $eti = $c['variable_etiqueta'];
                $columnsLegacy[] = $eti;
                $methodsLegacy[$eti] = $plantilla['procedimiento_interno'] ?? '';
            }
        }
        $legacyJsonObj = [
            'codigo_formato' => $plantilla['codigo'],
            'ensayo_titulo' => $plantilla['nombre'],
            'norma' => $plantilla['norma'],
            'columns' => $columnsLegacy,
            'column_methods' => $methodsLegacy,
            'version_documento' => $proximaRev,
            'orientacion' => $plantilla['orientacion'],
            'fondo_reporte' => $plantilla['fondo_reporte_id'],
            'sello_acreditacion' => $plantilla['sello_acreditacion_url']
        ];
        $legacyJson = json_encode($legacyJsonObj, JSON_UNESCAPED_UNICODE);

        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) {
            $this->db->beginTransaction();
        }
        try {
            $stmtVer = $this->db->prepare("
                INSERT INTO plantilla_versiones (
                    plantilla_id, version, esquema_json, formato_legacy_json, hash_integridad, aprobado_por, fecha_aprobacion, motivo_version
                ) VALUES (
                    :pid, :ver, :esq, :leg, :hash, :aprob, NOW(), :motivo
                )
            ");
            $stmtVer->execute([
                'pid' => $plantillaId,
                'ver' => $proximaRev,
                'esq' => $esquemaJson,
                'leg' => $legacyJson,
                'hash' => $hashIntegridad,
                'aprob' => $usuarioAprobadorId,
                'motivo' => $motivo ?: 'Aprobación formal por Gerencia de Calidad'
            ]);
            $versionId = (int)$this->db->lastInsertId();

            // Actualizar plantilla a 'aprobada' con la nueva versión
            $stmtUpd = $this->db->prepare("
                UPDATE plantillas SET
                    estado = 'aprobada',
                    version_actual = :ver,
                    aprobado_por = :aprob,
                    fecha_aprobacion = NOW()
                WHERE id = :id
            ");
            $stmtUpd->execute([
                'ver' => $proximaRev,
                'aprob' => $usuarioAprobadorId,
                'id' => $plantillaId
            ]);

            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return $versionId;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cambia el estado de una plantilla (Borrador -> En Revisión -> Aprobada -> Obsoleta).
     */
    public function cambiarEstado(int $plantillaId, string $nuevoEstado, int $usuarioId, string $motivo = ''): bool {
        if (!in_array($nuevoEstado, ['borrador', 'en_revision', 'aprobada', 'obsoleta'], true)) {
            throw new Exception("Estado '{$nuevoEstado}' no válido.");
        }

        if ($nuevoEstado === 'aprobada') {
            $this->aprobarPlantilla($plantillaId, $usuarioId, $motivo);
            return true;
        }

        $stmt = $this->db->prepare("UPDATE plantillas SET estado = :est, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['est' => $nuevoEstado, 'id' => $plantillaId]);
    }

    /**
     * Obtiene el catálogo completo de variables activas agrupadas con sus unidades.
     */
    public function obtenerVariablesYUnidades(): array {
        $sql = "SELECT v.*, um.simbolo AS unidad_simbolo, um.nombre AS unidad_nombre, um.magnitud AS unidad_magnitud
                FROM variables v
                LEFT JOIN unidades_medida um ON v.unidad_id = um.id
                WHERE v.activo = 1
                ORDER BY v.tipo ASC, v.etiqueta ASC";
        $vars = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $unidades = $this->db->query("SELECT * FROM unidades_medida WHERE activo = 1 ORDER BY magnitud ASC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'variables' => $vars,
            'unidades' => $unidades
        ];
    }

    /**
     * Registra una nueva variable en línea desde el Studio.
     */
    public function crearVariable(array $datos): int {
        $codigo = trim($datos['codigo'] ?? '');
        $codigo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($codigo));
        $etiqueta = trim($datos['etiqueta'] ?? '');
        $tipo = in_array($datos['tipo'] ?? '', ['numero', 'texto', 'fecha', 'lista', 'calculado', 'tabla'], true) ? $datos['tipo'] : 'numero';
        $unidadId = !empty($datos['unidad_id']) ? (int)$datos['unidad_id'] : null;
        $decimales = isset($datos['decimales']) ? (int)$datos['decimales'] : 2;
        $min = ($datos['minimo'] !== null && $datos['minimo'] !== '') ? (float)$datos['minimo'] : null;
        $max = ($datos['maximo'] !== null && $datos['maximo'] !== '') ? (float)$datos['maximo'] : null;
        $opciones = !empty($datos['opciones_lista']) ? json_encode($datos['opciones_lista'], JSON_UNESCAPED_UNICODE) : null;
        $req = !empty($datos['requerido']) ? 1 : 0;

        $stmt = $this->db->prepare("
            INSERT INTO variables (codigo, etiqueta, tipo, unidad_id, decimales, minimo, maximo, opciones_lista, requerido, activo)
            VALUES (:cod, :eti, :tip, :uni, :dec, :min, :max, :opc, :req, 1)
        ");
        $stmt->execute([
            'cod' => $codigo,
            'eti' => $etiqueta,
            'tip' => $tipo,
            'uni' => $unidadId,
            'dec' => $decimales,
            'min' => $min,
            'max' => $max,
            'opc' => $opciones,
            'req' => $req
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Registra una nueva unidad de medida en línea.
     */
    public function crearUnidad(array $datos): int {
        $nombre = trim($datos['nombre'] ?? '');
        $simbolo = trim($datos['simbolo'] ?? '');
        $magnitud = trim($datos['magnitud'] ?? 'General');

        $stmt = $this->db->prepare("INSERT INTO unidades_medida (nombre, simbolo, magnitud, activo) VALUES (:nom, :sim, :mag, 1)");
        $stmt->execute(['nom' => $nombre, 'sim' => $simbolo, 'mag' => $magnitud]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Persiste los renglones detallados de muestras/ensayos en la tabla relacional hojas_solicitud_detalles
     * y mantiene sincronizado en segundo plano el campo hojas_solicitud.muestras_json.
     */
    public function guardarDetallesMuestrasHoja(int $idHoja, array $filas, int $usuarioId): bool {
        // Verificar si la hoja ya fue admitida en laboratorio (bloqueada)
        $stmtBloq = $this->db->prepare("SELECT COUNT(*) FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid AND bloqueado_recepcion = 1");
        $stmtBloq->execute(['hid' => $idHoja]);
        $estaBloqueada = ((int)$stmtBloq->fetchColumn() > 0);

        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) {
            $this->db->beginTransaction();
        }
        try {
            $muestrasJsonArray = [];

            // Si no está bloqueada, podemos reconstruir los detalles
            if (!$estaBloqueada) {
                $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid")->execute(['hid' => $idHoja]);

                $stmtIns = $this->db->prepare("
                    INSERT INTO hojas_solicitud_detalles (
                        id_hoja_solicitud, orden, codigo_muestra, descripcion_ubicacion, observacion_edad,
                        ensayo_id, plantilla_id, plantilla_version_id, seleccion_manual, motivo_cambio, bloqueado_recepcion
                    ) VALUES (
                        :hid, :ord, :cod, :desc, :obs,
                        :eid, :pid, :vid, :man, :motivo, 0
                    )
                ");

                $orden = 1;
                foreach ($filas as $f) {
                    $cod = trim($f['codigo_muestra'] ?? ("M-" . $orden));
                    $desc = trim($f['descripcion_ubicacion'] ?? '');
                    $obs = trim($f['observacion_edad'] ?? '');
                    $eid = (int)($f['ensayo_id'] ?? 1);
                    $pid = (int)($f['plantilla_id'] ?? 0);
                    $man = !empty($f['seleccion_manual']) ? 1 : 0;
                    $motivo = !empty($f['motivo_cambio']) ? trim($f['motivo_cambio']) : null;

                    // Si no tiene plantilla asignada, obtener la sugerida por defecto
                    if ($pid <= 0) {
                        $sug = $this->obtenerPlantillaSugerida($eid);
                        if ($sug) {
                            $pid = (int)$sug['id'];
                        }
                    }

                    // Resolver la versión actual aprobada
                    $vid = null;
                    if ($pid > 0) {
                        $stV = $this->db->prepare("SELECT pv.id FROM plantillas p JOIN plantilla_versiones pv ON (p.id = pv.plantilla_id AND p.version_actual = pv.version) WHERE p.id = :pid LIMIT 1");
                        $stV->execute(['pid' => $pid]);
                        $vid = $stV->fetchColumn() ?: null;
                    }

                    $stmtIns->execute([
                        'hid' => $idHoja,
                        'ord' => $orden,
                        'cod' => $cod,
                        'desc' => $desc,
                        'obs' => $obs,
                        'eid' => $eid,
                        'pid' => $pid,
                        'vid' => $vid,
                        'man' => $man,
                        'motivo' => $motivo
                    ]);

                    $muestrasJsonArray[] = [
                        'nombre_muestra' => $cod,
                        'descripcion' => $desc,
                        'info_importante' => $obs,
                        'ensayo_id' => $eid,
                        'plantilla_id' => $pid,
                        'plantilla_version_id' => $vid
                    ];
                    $orden++;
                }
            } else {
                // Solo reconstruir el JSON desde la tabla relacional sin destruirla
                $existentes = $this->obtenerDetallesPorHoja($idHoja);
                foreach ($existentes as $ex) {
                    $muestrasJsonArray[] = [
                        'nombre_muestra' => $ex['codigo_muestra'],
                        'descripcion' => $ex['descripcion_ubicacion'],
                        'info_importante' => $ex['observacion_edad'],
                        'ensayo_id' => $ex['ensayo_id'],
                        'plantilla_id' => $ex['plantilla_id'],
                        'plantilla_version_id' => $ex['plantilla_version_id']
                    ];
                }
            }

            // Sincronizar en segundo plano con hojas_solicitud.muestras_json
            $jsonStr = json_encode($muestrasJsonArray, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE hojas_solicitud SET muestras_json = :mjson WHERE id = :hid")
                     ->execute(['mjson' => $jsonStr, 'hid' => $idHoja]);

            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene los renglones detallados de una Hoja de Solicitud con sus plantillas y versiones.
     */
    public function obtenerDetallesPorHoja(int $idHoja): array {
        $sql = "SELECT hsd.*,
                       pr.ensayo_servicio,
                       pr.nombre_comercial AS ensayo_nombre_comercial,
                       pr.no_item AS ensayo_item,
                       p.codigo AS plantilla_codigo,
                       p.nombre AS plantilla_nombre,
                       p.norma AS plantilla_norma,
                       p.version_actual AS plantilla_version_actual,
                       p.orientacion AS plantilla_orientacion,
                       p.fondo_reporte_id AS plantilla_fondo,
                       p.sello_acreditacion_url,
                       p.cliente_id AS plantilla_cliente_id,
                       pv.version AS version_congelada,
                       pv.hash_integridad
                FROM hojas_solicitud_detalles hsd
                LEFT JOIN productos pr ON hsd.ensayo_id = pr.id
                LEFT JOIN plantillas p ON hsd.plantilla_id = p.id
                LEFT JOIN plantilla_versiones pv ON hsd.plantilla_version_id = pv.id
                WHERE hsd.id_hoja_solicitud = :hid
                ORDER BY hsd.orden ASC, hsd.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hid' => $idHoja]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Congela irreversiblemente las plantillas y versiones de la Hoja de Solicitud
     * al momento en que las muestras son recibidas físicamente en laboratorio.
     */
    public function bloquearYCongelarDetallesRecepcion(int $idHoja, int $usuarioId): bool {
        $detalles = $this->obtenerDetallesPorHoja($idHoja);
        $stmtFreeze = $this->db->prepare("
            UPDATE hojas_solicitud_detalles SET
                plantilla_version_id = :vid,
                bloqueado_recepcion = 1
            WHERE id = :id
        ");

        $stmtGetVersion = $this->db->prepare("
            SELECT pv.id FROM plantillas p
            JOIN plantilla_versiones pv ON (p.id = pv.plantilla_id AND p.version_actual = pv.version)
            WHERE p.id = :pid LIMIT 1
        ");

        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) {
            $this->db->beginTransaction();
        }
        try {
            foreach ($detalles as $d) {
                $vid = $d['plantilla_version_id'];
                if (!$vid && !empty($d['plantilla_id'])) {
                    $stmtGetVersion->execute(['pid' => $d['plantilla_id']]);
                    $vid = $stmtGetVersion->fetchColumn() ?: null;
                }

                $stmtFreeze->execute([
                    'vid' => $vid,
                    'id' => $d['id']
                ]);

                // Sincronizar en recepcion_muestras si ya existe el registro
                $this->db->prepare("UPDATE recepcion_muestras SET plantilla_id = :pid, plantilla_version_id = :vid WHERE codigo_muestra = :cod")
                         ->execute(['pid' => $d['plantilla_id'], 'vid' => $vid, 'cod' => $d['codigo_muestra']]);
            }

            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Permite a Gerencia de Calidad o Administrador cambiar la plantilla de una muestra
     * previamente recibida, requiriendo motivo obligatorio y dejando registro en bitácora.
     */
    public function cambiarPlantillaMuestraConMotivo(int $detalleId, int $nuevaPlantillaId, ?int $nuevaVersionId, string $motivo, int $usuarioId): bool {
        $stmtDet = $this->db->prepare("SELECT * FROM hojas_solicitud_detalles WHERE id = :id LIMIT 1");
        $stmtDet->execute(['id' => $detalleId]);
        $det = $stmtDet->fetch(PDO::FETCH_ASSOC);

        if (!$det) {
            throw new Exception("Detalle de muestra no encontrado.");
        }

        // Si no se pasó versión, resolver la versión actual de la nueva plantilla
        if (!$nuevaVersionId) {
            $stmtV = $this->db->prepare("
                SELECT pv.id FROM plantillas p
                JOIN plantilla_versiones pv ON (p.id = pv.plantilla_id AND p.version_actual = pv.version)
                WHERE p.id = :pid LIMIT 1
            ");
            $stmtV->execute(['pid' => $nuevaPlantillaId]);
            $nuevaVersionId = $stmtV->fetchColumn() ?: null;
        }

        $stmtUpd = $this->db->prepare("
            UPDATE hojas_solicitud_detalles SET
                plantilla_id = :pid,
                plantilla_version_id = :vid,
                seleccion_manual = 1,
                motivo_cambio = :motivo
            WHERE id = :id
        ");
        $stmtUpd->execute([
            'pid' => $nuevaPlantillaId,
            'vid' => $nuevaVersionId,
            'motivo' => $motivo,
            'id' => $detalleId
        ]);

        // Sincronizar en recepcion_muestras
        $this->db->prepare("UPDATE recepcion_muestras SET plantilla_id = :pid, plantilla_version_id = :vid WHERE codigo_muestra = :cod")
                 ->execute(['pid' => $nuevaPlantillaId, 'vid' => $nuevaVersionId, 'cod' => $det['codigo_muestra']]);

        return true;
    }
}
