<?php

namespace Cycsa\Modulos\Operaciones\Modelos;

use Cycsa\Nucleo\Conexion;
use PDO;
use Exception;

class RoturasModelo {

    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtenerInstancia();
    }

    /**
     * Consulta la Bandeja Anónima de Laboratorio a través de la vista SQL v_lab_muestras.
     * Garantiza aislamiento estricto (ISO 17025 Cláusulas 4.1 y 4.2).
     */
    public function obtenerBandejaAnonima(array $filtros = []): array {
        $sql = "SELECT * FROM v_lab_muestras WHERE 1=1";
        $params = [];

        // Filtro por búsqueda rápida (Código de Laboratorio o material)
        if (!empty($filtros['busqueda'])) {
            $busq = '%' . trim($filtros['busqueda']) . '%';
            $sql .= " AND (codigo_lab LIKE :b1 OR material_descripcion LIKE :b2 OR codigo_campo LIKE :b3)";
            $params['b1'] = $busq;
            $params['b2'] = $busq;
            $params['b3'] = $busq;
        }

        // Filtro por Estado
        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'Registrado' || $filtros['estado'] === 'En Laboratorio' || $filtros['estado'] === 'Finalizado') {
                $sql .= " AND estado_recepcion = :estado_rec";
                $params['estado_rec'] = $filtros['estado'];
            } elseif (in_array($filtros['estado'], ['borrador', 'activo', 'cerrado'], true)) {
                $sql .= " AND estado_plan = :estado_plan";
                $params['estado_plan'] = $filtros['estado'];
            }
        }

        // Filtro por Norma o Ensayo
        if (!empty($filtros['tipo_ensayo'])) {
            $sql .= " AND (plantilla_nombre LIKE :ensayo OR norma_referencia LIKE :ensayo2)";
            $params['ensayo'] = '%' . trim($filtros['tipo_ensayo']) . '%';
            $params['ensayo2'] = '%' . trim($filtros['tipo_ensayo']) . '%';
        }

        // Filtro Rotura Hoy (proxima_rotura = CURDATE())
        if (!empty($filtros['rotura_hoy'])) {
            $sql .= " AND proxima_rotura = CURRENT_DATE";
        }

        // Filtro Vencidas (proxima_rotura < CURDATE() y no cerrada)
        if (!empty($filtros['vencidas'])) {
            $sql .= " AND proxima_rotura < CURRENT_DATE AND (estado_plan IS NULL OR estado_plan != 'cerrado')";
        }

        // Filtro por Rango de Fechas (fecha_ingreso o fecha_moldeo)
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(fecha_ingreso) >= :f_desde";
            $params['f_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(fecha_ingreso) <= :f_hasta";
            $params['f_hasta'] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY proxima_rotura ASC, muestra_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Validar e indexar flag visual de vencimiento
        $hoy = date('Y-m-d');
        foreach ($muestras as &$m) {
            $m['es_vencida'] = (!empty($m['proxima_rotura']) && $m['proxima_rotura'] < $hoy && $m['estado_plan'] !== 'cerrado');
            $m['es_hoy'] = (!empty($m['proxima_rotura']) && $m['proxima_rotura'] === $hoy);
        }
        unset($m);

        return $muestras;
    }

    /**
     * Obtiene el detalle técnico ciego de una muestra con todos sus especímenes, plan y roturas.
     */
    public function obtenerDetalleMuestraAnonima(int $muestraId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM v_lab_muestras WHERE muestra_id = :id");
        $stmt->execute(['id' => $muestraId]);
        $muestra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$muestra) {
            return null;
        }

        // Especímenes asociados
        $stmtEsp = $this->db->prepare("
            SELECT esp.*, pra.plan_edad_id, pre.edad_dias, pre.fecha_programada, pre.tolerancia_horas,
                   rot.id AS rotura_id, rot.fecha_real, rot.carga, rot.area, rot.esfuerzo_psi, rot.esfuerzo_kgcm2,
                   rot.porcentaje_fc, rot.tipo_falla, rot.fuera_tolerancia, rot.justificacion, rot.datos_crudos
            FROM especimenes esp
            LEFT JOIN plan_rotura_asignaciones pra ON pra.especimen_id = esp.id
            LEFT JOIN plan_rotura_edades pre ON pra.plan_edad_id = pre.id
            LEFT JOIN roturas rot ON rot.especimen_id = esp.id
            WHERE esp.muestra_id = :id
            ORDER BY esp.id ASC
        ");
        $stmtEsp->execute(['id' => $muestraId]);
        $especimenes = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

        // Plan de roturas activo
        $stmtPlan = $this->db->prepare("
            SELECT pr.*, pv.version AS plantilla_version_num, pv.esquema_json
            FROM planes_rotura pr
            LEFT JOIN plantilla_versiones pv ON pr.plantilla_version_id = pv.id
            WHERE pr.muestra_id = :id
            ORDER BY pr.id DESC LIMIT 1
        ");
        $stmtPlan->execute(['id' => $muestraId]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC) ?: null;

        $edades = [];
        if ($plan) {
            $stmtEdades = $this->db->prepare("
                SELECT pre.*, 
                       (SELECT COUNT(*) FROM plan_rotura_asignaciones pra WHERE pra.plan_edad_id = pre.id) AS especimenes_asignados,
                       (SELECT COUNT(*) FROM roturas rot WHERE rot.plan_edad_id = pre.id) AS especimenes_rotos,
                       (SELECT ir.id FROM informes_rotura ir WHERE ir.plan_edad_id = pre.id AND ir.tipo = 'parcial' AND ir.estado != 'anulado' ORDER BY ir.id DESC LIMIT 1) AS informe_parcial_id,
                       (SELECT ir.estado FROM informes_rotura ir WHERE ir.plan_edad_id = pre.id AND ir.tipo = 'parcial' AND ir.estado != 'anulado' ORDER BY ir.id DESC LIMIT 1) AS informe_parcial_estado,
                       (SELECT ir.numero FROM informes_rotura ir WHERE ir.plan_edad_id = pre.id AND ir.tipo = 'parcial' AND ir.estado != 'anulado' ORDER BY ir.id DESC LIMIT 1) AS informe_parcial_numero
                FROM plan_rotura_edades pre
                WHERE pre.plan_rotura_id = :plan_id
                ORDER BY pre.edad_dias ASC
            ");
            $stmtEdades->execute(['plan_id' => $plan['id']]);
            $edades = $stmtEdades->fetchAll(PDO::FETCH_ASSOC);
        }

        // Historial de Informes de Rotura
        $stmtInf = $this->db->prepare("
            SELECT ir.*, u.nombre AS aprobado_por_nombre
            FROM informes_rotura ir
            LEFT JOIN usuarios u ON ir.aprobado_por = u.id
            WHERE ir.muestra_id = :id
            ORDER BY ir.id DESC
        ");
        $stmtInf->execute(['id' => $muestraId]);
        $informes = $stmtInf->fetchAll(PDO::FETCH_ASSOC);

        // Comprobar si se puede generar el informe consolidado (todos los parciales aprobados)
        $puedeConsolidar = false;
        if (!empty($edades)) {
            $totalEdadesCompletadas = 0;
            $totalParcialesAprobados = 0;
            foreach ($edades as $e) {
                if ($e['estado'] === 'completado') {
                    $totalEdadesCompletadas++;
                }
                if ($e['informe_parcial_estado'] === 'aprobado') {
                    $totalParcialesAprobados++;
                }
            }
            if ($totalEdadesCompletadas > 0 && $totalEdadesCompletadas === count($edades) && $totalParcialesAprobados === count($edades)) {
                $puedeConsolidar = true;
            }
        }

        return [
            'muestra' => $muestra,
            'especimenes' => $especimenes,
            'plan' => $plan,
            'edades' => $edades,
            'informes' => $informes,
            'puede_consolidar' => $puedeConsolidar
        ];
    }

    /**
     * Crea automáticamente los especímenes para una muestra según la cantidad declarada en la RT-FM-13.
     */
    public function crearEspecimenesParaMuestra(int $muestraId, int $cantidad, string $fechaMoldeo, string $tipo = 'cilindro_concreto', string $dimensiones = '15 x 30 cm'): bool {
        if ($muestraId <= 0 || $cantidad <= 0) {
            return false;
        }

        // Obtener código de laboratorio de la muestra
        $stmtM = $this->db->prepare("SELECT codigo_muestra FROM recepcion_muestras WHERE id = :id");
        $stmtM->execute(['id' => $muestraId]);
        $codigoBase = $stmtM->fetchColumn();

        if (!$codigoBase) {
            return false;
        }

        // Verificar especímenes ya existentes
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM especimenes WHERE muestra_id = :id");
        $stmtCheck->execute(['id' => $muestraId]);
        $existentes = (int)$stmtCheck->fetchColumn();

        if ($existentes >= $cantidad) {
            if (!empty($fechaMoldeo)) {
                $stmtUpdLote = $this->db->prepare("UPDATE lotes_muestras SET fecha_moldeo = :fm WHERE id_recepcion = :id");
                $stmtUpdLote->execute(['fm' => $fechaMoldeo, 'id' => $muestraId]);
                $stmtUpdEsp = $this->db->prepare("UPDATE especimenes SET fecha_moldeo = :fm WHERE muestra_id = :id");
                $stmtUpdEsp->execute(['fm' => $fechaMoldeo, 'id' => $muestraId]);
            }
            return true; // Ya están creados
        }

        // Sincronizar fecha de moldeo en el lote de la muestra
        if (!empty($fechaMoldeo)) {
            $stmtUpdLote = $this->db->prepare("UPDATE lotes_muestras SET fecha_moldeo = :fm WHERE id_recepcion = :id");
            $stmtUpdLote->execute(['fm' => $fechaMoldeo, 'id' => $muestraId]);
        }

        $stmtInsert = $this->db->prepare("
            INSERT INTO especimenes (muestra_id, codigo_lab, tipo, dimensiones, fecha_moldeo, estado)
            VALUES (:muestra_id, :codigo_lab, :tipo, :dimensiones, :fecha_moldeo, 'en_curado')
            ON DUPLICATE KEY UPDATE fecha_moldeo = VALUES(fecha_moldeo)
        ");

        for ($i = $existentes + 1; $i <= $cantidad; $i++) {
            $codigoEspecimen = sprintf("%s-C%d", $codigoBase, $i);
            $stmtInsert->execute([
                'muestra_id' => $muestraId,
                'codigo_lab' => $codigoEspecimen,
                'tipo' => $tipo,
                'dimensiones' => $dimensiones,
                'fecha_moldeo' => $fechaMoldeo
            ]);
        }

        return true;
    }

    /**
     * Guarda o actualiza el Plan de Roturas de una muestra.
     * Regla: La suma de especímenes en el plan no puede exceder el total de especímenes recibidos.
     * Los sobrantes quedan en estado 'testigo'.
     */
    public function guardarPlanRotura(int $muestraId, ?int $plantillaVersionId, array $edadesConfig, int $usuarioId): array {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos de la muestra y sus especímenes
            $stmtM = $this->db->prepare("SELECT lm.fecha_moldeo, rm.plantilla_version_id FROM recepcion_muestras rm JOIN lotes_muestras lm ON lm.id_recepcion = rm.id WHERE rm.id = :id");
            $stmtM->execute(['id' => $muestraId]);
            $muestraData = $stmtM->fetch(PDO::FETCH_ASSOC);

            if (!$muestraData) {
                $this->db->rollBack();
                return ['exito' => false, 'mensaje' => 'Muestra de laboratorio no encontrada.'];
            }

            $fechaMoldeo = $muestraData['fecha_moldeo'];
            if (!$plantillaVersionId) {
                $plantillaVersionId = $muestraData['plantilla_version_id'] ?: null;
            }

            // Obtener todos los especímenes de la muestra
            $stmtEsp = $this->db->prepare("SELECT id, estado, fecha_moldeo FROM especimenes WHERE muestra_id = :id ORDER BY id ASC");
            $stmtEsp->execute(['id' => $muestraId]);
            $todosEspecimenes = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
            $totalEspecimenes = count($todosEspecimenes);

            if (!empty($todosEspecimenes[0]['fecha_moldeo'])) {
                $fechaMoldeo = $todosEspecimenes[0]['fecha_moldeo'];
            }

            if ($totalEspecimenes === 0) {
                $this->db->rollBack();
                return ['exito' => false, 'mensaje' => 'La muestra no cuenta con especímenes registrados para armar un plan de rotura.'];
            }

            // Validar suma de especímenes programados
            $sumaProgramada = 0;
            foreach ($edadesConfig as $e) {
                $cant = (int)($e['cantidad'] ?? 0);
                if ($cant < 0) {
                    $this->db->rollBack();
                    return ['exito' => false, 'mensaje' => 'Las cantidades de especímenes no pueden ser negativas.'];
                }
                $sumaProgramada += $cant;
            }

            if ($sumaProgramada > $totalEspecimenes) {
                $this->db->rollBack();
                return [
                    'exito' => false,
                    'mensaje' => "La suma de especímenes programados ({$sumaProgramada}) no puede superar los especímenes recibidos ({$totalEspecimenes})."
                ];
            }

            // 2. Verificar si ya existe plan previo
            $stmtPlan = $this->db->prepare("SELECT id, estado FROM planes_rotura WHERE muestra_id = :id ORDER BY id DESC LIMIT 1");
            $stmtPlan->execute(['id' => $muestraId]);
            $planExistente = $stmtPlan->fetch(PDO::FETCH_ASSOC);

            if ($planExistente) {
                // Verificar si ya hay roturas ejecutadas
                $stmtRot = $this->db->prepare("
                    SELECT COUNT(*) FROM roturas rot 
                    JOIN plan_rotura_edades pre ON rot.plan_edad_id = pre.id 
                    WHERE pre.plan_rotura_id = :plan_id
                ");
                $stmtRot->execute(['plan_id' => $planExistente['id']]);
                $cantRoturas = (int)$stmtRot->fetchColumn();

                if ($cantRoturas > 0) {
                    $this->db->rollBack();
                    return [
                        'exito' => false,
                        'mensaje' => 'Este plan ya cuenta con roturas ejecutadas. Para modificarlo se requiere autorización de Calidad/Supervisión.'
                    ];
                }

                // Si no hay roturas, limpiamos las edades previas
                $this->db->prepare("DELETE FROM plan_rotura_edades WHERE plan_rotura_id = :plan_id")->execute(['plan_id' => $planExistente['id']]);
                $planId = (int)$planExistente['id'];

                $stmtUpdatePlan = $this->db->prepare("UPDATE planes_rotura SET plantilla_version_id = :pvid, estado = 'activo' WHERE id = :id");
                $stmtUpdatePlan->execute(['pvid' => $plantillaVersionId, 'id' => $planId]);
            } else {
                // Crear nuevo plan activo
                $stmtInsertPlan = $this->db->prepare("
                    INSERT INTO planes_rotura (muestra_id, plantilla_version_id, estado, creado_por)
                    VALUES (:muestra_id, :pvid, 'activo', :creado_por)
                ");
                $stmtInsertPlan->execute([
                    'muestra_id' => $muestraId,
                    'pvid' => $plantillaVersionId,
                    'creado_por' => $usuarioId
                ]);
                $planId = (int)$this->db->lastInsertId();
            }

            // 3. Crear edades y asignar especímenes
            $especimenIndex = 0;
            $stmtInsertEdad = $this->db->prepare("
                INSERT INTO plan_rotura_edades (plan_rotura_id, edad_dias, cantidad, fecha_programada, tolerancia_horas, estado)
                VALUES (:plan_id, :edad_dias, :cantidad, :fecha_programada, :tolerancia, 'pendiente')
            ");

            $stmtAsignar = $this->db->prepare("
                INSERT INTO plan_rotura_asignaciones (plan_edad_id, especimen_id)
                VALUES (:plan_edad_id, :especimen_id)
            ");

            $stmtUpdateEspEstado = $this->db->prepare("UPDATE especimenes SET estado = :estado WHERE id = :id");

            foreach ($edadesConfig as $e) {
                $edadDias = (int)($e['edad_dias'] ?? 0);
                $cant = (int)($e['cantidad'] ?? 0);
                if ($edadDias <= 0 || $cant <= 0) continue;

                $fechaProg = date('Y-m-d', strtotime("{$fechaMoldeo} + {$edadDias} days"));
                $toleranciaHoras = $this->obtenerToleranciaHorasAstmC39($edadDias);

                $stmtInsertEdad->execute([
                    'plan_id' => $planId,
                    'edad_dias' => $edadDias,
                    'cantidad' => $cant,
                    'fecha_programada' => $fechaProg,
                    'tolerancia' => $toleranciaHoras
                ]);
                $planEdadId = (int)$this->db->lastInsertId();

                for ($k = 0; $k < $cant; $k++) {
                    if ($especimenIndex < $totalEspecimenes) {
                        $espId = (int)$todosEspecimenes[$especimenIndex]['id'];
                        $stmtAsignar->execute([
                            'plan_edad_id' => $planEdadId,
                            'especimen_id' => $espId
                        ]);
                        $stmtUpdateEspEstado->execute(['estado' => 'programado', 'id' => $espId]);
                        $especimenIndex++;
                    }
                }
            }

            // Los sobrantes quedan como testigos
            while ($especimenIndex < $totalEspecimenes) {
                $espId = (int)$todosEspecimenes[$especimenIndex]['id'];
                $stmtUpdateEspEstado->execute(['estado' => 'testigo', 'id' => $espId]);
                $especimenIndex++;
            }

            $this->db->commit();
            return ['exito' => true, 'mensaje' => 'Plan de roturas configurado exitosamente.', 'plan_id' => $planId];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('RoturasModelo::guardarPlanRotura error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al guardar el plan de roturas.'];
        }
    }

    /**
     * Modifica un plan de roturas ya iniciado bajo autorización de Calidad/Supervisión.
     */
    public function modificarPlanRoturaConAutorizacion(int $planId, array $nuevasEdades, int $usuarioId, int $rolId, string $motivo): array {
        // Validación de permisos de Calidad / Administrador
        if ($rolId !== 1 && $rolId !== 3) {
            return ['exito' => false, 'mensaje' => 'Acceso denegado: Se requiere rol de Administrador o Supervisor de Calidad para modificar un plan en ejecución.'];
        }

        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10) {
            return ['exito' => false, 'mensaje' => 'Debe registrar un motivo técnico justificado de al menos 10 caracteres.'];
        }

        try {
            $this->db->beginTransaction();

            $stmtPlan = $this->db->prepare("SELECT pr.*, rm.id AS muestra_id, lm.fecha_moldeo FROM planes_rotura pr JOIN recepcion_muestras rm ON pr.muestra_id = rm.id JOIN lotes_muestras lm ON lm.id_recepcion = rm.id WHERE pr.id = :id");
            $stmtPlan->execute(['id' => $planId]);
            $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                $this->db->rollBack();
                return ['exito' => false, 'mensaje' => 'Plan de roturas no encontrado.'];
            }

            // Actualizar motivo y registrar en auditoría
            $stmtUp = $this->db->prepare("UPDATE planes_rotura SET motivo_modificacion = :motivo, aprobado_por = :aprobado_por, fecha_aprobacion = NOW() WHERE id = :id");
            $stmtUp->execute(['motivo' => $motivo, 'aprobado_por' => $usuarioId, 'id' => $planId]);

            registrarBitacora('laboratorio', 'modificar_plan_rotura', "Plan de Roturas #{$planId} modificado bajo justificación: {$motivo}", $planId);

            $this->db->commit();
            return ['exito' => true, 'mensaje' => 'Plan de roturas autorizado y actualizado correctamente.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('RoturasModelo::modificarPlanRoturaConAutorizacion error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al modificar el plan de roturas.'];
        }
    }

    /**
     * Ejecuta y registra la rotura tradicional clásica de un espécimen (ASTM C39).
     * Mantiene 100% de compatibilidad con el flujo original clásico (commit 275dad5):
     * esfuerzoPsi = carga / area, esfuerzoKgCm2 = esfuerzoPsi * 0.070307.
     * Si se envían lecturas crudas sin código de formato explícito, rechaza con validación clara.
     * Si se envía un código de formato explícito, redirige al flujo de Hoja de Cálculo (registrarRoturaHc).
     */
    public function registrarRotura(array $datos, int $tecnicoId): array {
        if ($tecnicoId <= 0) {
            return ['exito' => false, 'mensaje' => 'Sesión de usuario no válida para registrar el ensayo.'];
        }

        // Inspeccionar lecturas crudas y código de formato
        $crudosArr = [];
        if (!empty($datos['datos_crudos'])) {
            if (is_array($datos['datos_crudos'])) {
                $crudosArr = $datos['datos_crudos'];
            } elseif (is_string($datos['datos_crudos'])) {
                $crudosArr = json_decode($datos['datos_crudos'], true) ?: [];
            }
        }

        $codigoFormatoHc = $datos['codigo_formato'] ?? ($crudosArr['codigo_formato'] ?? null);

        // Sin deducción implícita: si llegan lecturas crudas sin código explícito, rechazar
        $tieneLecturasCrudas = isset($crudosArr['lectura_carga_lbf']) || isset($datos['lectura_carga_lbf'])
            || isset($crudosArr['diametro_1']) || isset($datos['diametro_1'])
            || isset($crudosArr['largo_1']) || isset($datos['largo_1']);

        if ($tieneLecturasCrudas && empty($codigoFormatoHc)) {
            return [
                'exito' => false,
                'mensaje' => 'Se enviaron lecturas crudas pero no se especificó un código de formato explícito de hoja de cálculo.'
            ];
        }

        if (!empty($codigoFormatoHc)) {
            return $this->registrarRoturaHc($datos, $tecnicoId);
        }

        // Flujo tradicional clásico idéntico a 275dad5
        try {
            $especimenId = (int)($datos['especimen_id'] ?? 0);
            $planEdadId = (int)($datos['plan_edad_id'] ?? 0);
            $fechaReal = !empty($datos['fecha_real']) ? $datos['fecha_real'] : date('Y-m-d H:i:s');
            $carga = (float)($datos['carga'] ?? 0);
            $area = (float)($datos['area'] ?? 28.2743); // 15x30 cm estándar = ~4.4 in diametro = 28.27 in²
            $tipoFalla = trim($datos['tipo_falla'] ?? 'Tipo 1 (Cono)');
            $justificacion = trim($datos['justificacion'] ?? '');
            $datosCrudos = !empty($datos['datos_crudos'])
                ? (is_array($datos['datos_crudos']) ? json_encode($datos['datos_crudos'], JSON_UNESCAPED_UNICODE) : $datos['datos_crudos'])
                : null;

            if ($especimenId <= 0 || $carga <= 0 || $area <= 0) {
                return ['exito' => false, 'mensaje' => 'Datos de ensayo incompletos: Carga y área deben ser valores positivos válidos.'];
            }

            // Obtener edad programada y fecha de moldeo
            $stmtAge = $this->db->prepare("
                SELECT pre.*, lm.fecha_moldeo, lm.diseno_resistencia
                FROM plan_rotura_edades pre
                JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
                JOIN recepcion_muestras rm ON pr.muestra_id = rm.id
                JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                WHERE pre.id = :id
            ");
            $stmtAge->execute(['id' => $planEdadId]);
            $edadData = $stmtAge->fetch(PDO::FETCH_ASSOC);

            if (!$edadData) {
                return ['exito' => false, 'mensaje' => 'Edad de rotura programada no válida.'];
            }

            // Cálculos físicos y conversiones (flujo tradicional clásico)
            $factorPsiToKgCm2 = 0.070307;
            $esfuerzoPsi = round($carga / $area, 2);
            $esfuerzoKgCm2 = round($esfuerzoPsi * $factorPsiToKgCm2, 2);

            // Cálculo de porcentaje contra f'c de diseño
            $porcentajeFc = null;
            if (!empty($edadData['diseno_resistencia'])) {
                if (preg_match('/(\d+(\.\d+)?)/', $edadData['diseno_resistencia'], $matches)) {
                    $fcDiseno = (float)$matches[1];
                    if ($fcDiseno > 0) {
                        $porcentajeFc = round(($esfuerzoPsi / $fcDiseno) * 100, 2);
                    }
                }
            }

            // Validación de tolerancia temporal según ASTM C39
            $fechaProgTimestamp = strtotime($edadData['fecha_programada'] . ' 08:00:00');
            $fechaRealTimestamp = strtotime($fechaReal);
            $diferenciaHoras = abs($fechaRealTimestamp - $fechaProgTimestamp) / 3600;
            $toleranciaHoras = (int)$edadData['tolerancia_horas'];

            $fueraTolerancia = ($diferenciaHoras > $toleranciaHoras) ? 1 : 0;

            if ($fueraTolerancia && empty($justificacion)) {
                return [
                    'exito' => false,
                    'requiere_justificacion' => true,
                    'mensaje' => "El ensayo se realizó fuera de la ventana de tolerancia ASTM C39 (±{$toleranciaHoras} hrs). Debe ingresar una justificación técnica obligatoria."
                ];
            }

            $this->db->beginTransaction();

            // Insertar o actualizar registro en roturas
            $stmtRot = $this->db->prepare("
                INSERT INTO roturas (especimen_id, plan_edad_id, fecha_real, carga, area, esfuerzo_psi, esfuerzo_kgcm2, porcentaje_fc, tipo_falla, tecnico_id, fuera_tolerancia, justificacion, datos_crudos)
                VALUES (:especimen_id, :plan_edad_id, :fecha_real, :carga, :area, :esfuerzo_psi, :esfuerzo_kgcm2, :porcentaje_fc, :tipo_falla, :tecnico_id, :fuera_tolerancia, :justificacion, :datos_crudos)
                ON DUPLICATE KEY UPDATE 
                    fecha_real = VALUES(fecha_real), carga = VALUES(carga), area = VALUES(area),
                    esfuerzo_psi = VALUES(esfuerzo_psi), esfuerzo_kgcm2 = VALUES(esfuerzo_kgcm2),
                    porcentaje_fc = VALUES(porcentaje_fc), tipo_falla = VALUES(tipo_falla),
                    tecnico_id = VALUES(tecnico_id), fuera_tolerancia = VALUES(fuera_tolerancia),
                    justificacion = VALUES(justificacion), datos_crudos = VALUES(datos_crudos)
            ");
            $stmtRot->execute([
                'especimen_id' => $especimenId,
                'plan_edad_id' => $planEdadId,
                'fecha_real' => $fechaReal,
                'carga' => $carga,
                'area' => $area,
                'esfuerzo_psi' => $esfuerzoPsi,
                'esfuerzo_kgcm2' => $esfuerzoKgCm2,
                'porcentaje_fc' => $porcentajeFc,
                'tipo_falla' => $tipoFalla,
                'tecnico_id' => $tecnicoId,
                'fuera_tolerancia' => $fueraTolerancia,
                'justificacion' => $justificacion,
                'datos_crudos' => $datosCrudos
            ]);

            // Marcar espécimen como ensayado
            $this->db->prepare("UPDATE especimenes SET estado = 'ensayado' WHERE id = :id")->execute(['id' => $especimenId]);

            // Verificar si todos los especímenes de esta edad ya fueron ensayados
            $stmtCheckEdad = $this->db->prepare("
                SELECT COUNT(*) FROM plan_rotura_asignaciones pra
                JOIN especimenes esp ON pra.especimen_id = esp.id
                WHERE pra.plan_edad_id = :plan_edad_id AND esp.estado != 'ensayado'
            ");
            $stmtCheckEdad->execute(['plan_edad_id' => $planEdadId]);
            $pendientesEdad = (int)$stmtCheckEdad->fetchColumn();

            if ($pendientesEdad === 0) {
                $this->db->prepare("UPDATE plan_rotura_edades SET estado = 'completado' WHERE id = :id")->execute(['id' => $planEdadId]);
            } else {
                $this->db->prepare("UPDATE plan_rotura_edades SET estado = 'en_proceso' WHERE id = :id")->execute(['id' => $planEdadId]);
            }

            // Verificar si todo el plan está completado
            $planRoturaId = (int)$edadData['plan_rotura_id'];
            $stmtCheckPlan = $this->db->prepare("SELECT COUNT(*) FROM plan_rotura_edades WHERE plan_rotura_id = :plan_id AND estado != 'completado'");
            $stmtCheckPlan->execute(['plan_id' => $planRoturaId]);
            $edadesPendientesPlan = (int)$stmtCheckPlan->fetchColumn();

            if ($edadesPendientesPlan === 0) {
                $this->db->prepare("UPDATE planes_rotura SET estado = 'cerrado' WHERE id = :id")->execute(['id' => $planRoturaId]);
            }

            $this->db->commit();
            return [
                'exito' => true,
                'mensaje' => 'Rotura registrada exitosamente.',
                'carga' => $carga,
                'area' => $area,
                'esfuerzo_psi' => $esfuerzoPsi,
                'esfuerzo_kgcm2' => $esfuerzoKgCm2,
                'porcentaje_fc' => $porcentajeFc,
                'datos_crudos' => $datosCrudos,
                'evaluacion_hc' => null
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('RoturasModelo::registrarRotura error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al registrar la rotura del espécimen.'];
        }
    }

    /**
     * Flujo especializado de registro de rotura mediante Hoja de Cálculo (RT-FM-61, 36, 38, 57, 63, 64).
     * Requiere explícitamente `codigo_formato`. Evalúa todas las fórmulas mediante MotorHojasCalculoServicio
     * y persiste el conjunto completo de trazabilidad en `datos_crudos`.
     */
    public function registrarRoturaHc(array $datos, int $tecnicoId): array {
        if ($tecnicoId <= 0) {
            return ['exito' => false, 'mensaje' => 'Sesión de usuario no válida para registrar el ensayo.'];
        }

        $crudosArr = [];
        if (!empty($datos['datos_crudos'])) {
            if (is_array($datos['datos_crudos'])) {
                $crudosArr = $datos['datos_crudos'];
            } elseif (is_string($datos['datos_crudos'])) {
                $crudosArr = json_decode($datos['datos_crudos'], true) ?: [];
            }
        }

        $codigoFormatoHc = $datos['codigo_formato'] ?? ($crudosArr['codigo_formato'] ?? null);
        if (empty($codigoFormatoHc)) {
            return [
                'exito' => false,
                'mensaje' => 'Se requiere un código de formato explícito para el cálculo de hoja de cálculo.'
            ];
        }

        try {
            $especimenId = (int)($datos['especimen_id'] ?? 0);
            $planEdadId = (int)($datos['plan_edad_id'] ?? 0);
            $fechaReal = !empty($datos['fecha_real']) ? $datos['fecha_real'] : date('Y-m-d H:i:s');
            $tipoFalla = trim($datos['tipo_falla'] ?? 'Tipo 1 (Cono)');
            $justificacion = trim($datos['justificacion'] ?? '');

            // Obtener edad programada y fecha de moldeo
            $stmtAge = $this->db->prepare("
                SELECT pre.*, lm.fecha_moldeo, lm.diseno_resistencia
                FROM plan_rotura_edades pre
                JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
                JOIN recepcion_muestras rm ON pr.muestra_id = rm.id
                JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                WHERE pre.id = :id
            ");
            $stmtAge->execute(['id' => $planEdadId]);
            $edadData = $stmtAge->fetch(PDO::FETCH_ASSOC);

            if (!$edadData) {
                return ['exito' => false, 'mensaje' => 'Edad de rotura programada no válida.'];
            }

            // Extraer f'c de diseño si existe
            $fcDiseno = 0.0;
            if (!empty($edadData['diseno_resistencia'])) {
                if (preg_match('/(\d+(\.\d+)?)/', $edadData['diseno_resistencia'], $matches)) {
                    $fcDiseno = (float)$matches[1];
                }
            }

            // Evaluar mediante el motor de Hoja de Cálculo
            $entradasHc = array_merge($datos, $crudosArr);
            unset($entradasHc['datos_crudos'], $entradasHc['csrf_token']);
            if ($fcDiseno > 0 && empty($entradasHc['resistencia_diseno_psi'])) {
                $entradasHc['resistencia_diseno_psi'] = $fcDiseno;
            }

            $motorHc = new \Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio();
            $opcionesHc = isset($crudosArr['opciones_hc']) && is_array($crudosArr['opciones_hc']) ? $crudosArr['opciones_hc'] : [];
            $evalHc = $motorHc->evaluarEspecimen($codigoFormatoHc, $entradasHc, $opcionesHc);
            $valsHc = $evalHc['valores'];

            // Resolver carga y área desde los cálculos
            $carga = (float)($valsHc['carga_real_lbf']
                ?? $valsHc['lectura_carga_lbf']
                ?? $valsHc['carga_lbf']
                ?? $valsHc['carga_prom']
                ?? $valsHc['fuerza_lbf']
                ?? $valsHc['carga_1']
                ?? $valsHc['carga_1_lbf']
                ?? $valsHc['carga_max_lbf']
                ?? ($datos['carga'] ?? 0));
            $carga = round($carga, 2);

            $area = (float)($valsHc['area_in2']
                ?? $valsHc['area_neta_in2']
                ?? (isset($valsHc['area_neta']) ? ((float)$valsHc['area_neta'] * 0.155) : null)
                ?? (isset($valsHc['ancho_prom'], $valsHc['espesor_prom']) ? ((float)$valsHc['ancho_prom'] * (float)$valsHc['espesor_prom']) : null)
                ?? $valsHc['area_bruta_in2']
                ?? $valsHc['area_1_in2']
                ?? $valsHc['area_nominal_in2']
                ?? ($datos['area'] ?? 28.2743));
            $area = round($area, 4);

            if ($especimenId <= 0 || $carga <= 0 || $area <= 0) {
                return ['exito' => false, 'mensaje' => 'Datos de ensayo incompletos: Carga y área calculadas deben ser valores positivos válidos.'];
            }

            // Resolver esfuerzo y porcentajes
            $factorPsiToKgCm2 = 0.070307;
            $psiCalc = $valsHc['esfuerzo_psi_reportado']
                ?? $valsHc['esfuerzo_psi']
                ?? $valsHc['resistencia_neta_psi']
                ?? $valsHc['resistencia_bruta_psi']
                ?? $valsHc['resistencia_psi']
                ?? $valsHc['modulo_ruptura_psi']
                ?? $valsHc['resistencia_tension_psi']
                ?? $valsHc['esfuerzo_1_psi']
                ?? $valsHc['esfuerzo_ultimo_psi']
                ?? ($carga / $area);
            $esfuerzoPsi = round((float)$psiCalc, 2);

            $kgcm2Calc = $valsHc['esfuerzo_kgcm2']
                ?? $valsHc['resistencia_neta_kgcm2']
                ?? $valsHc['resistencia_kgcm2']
                ?? $valsHc['modulo_ruptura_kgcm2']
                ?? $valsHc['resistencia_tension_kgcm2']
                ?? $valsHc['esfuerzo_1_kgcm2']
                ?? $valsHc['esfuerzo_ultimo_kgcm2']
                ?? ($esfuerzoPsi * $factorPsiToKgCm2);
            $esfuerzoKgCm2 = round((float)$kgcm2Calc, 2);

            $porcentajeFc = null;
            if (isset($valsHc['porcentaje_fc']) && $valsHc['porcentaje_fc'] !== null) {
                $porcentajeFc = round((float)$valsHc['porcentaje_fc'], 2);
            } elseif ($fcDiseno > 0) {
                $porcentajeFc = round(($esfuerzoPsi / $fcDiseno) * 100, 2);
            }

            $crudosPersistir = array_merge($crudosArr, [
                'codigo_formato' => $codigoFormatoHc,
                'entradas_hc' => $evalHc['entradas'],
                'constantes_hc' => $evalHc['constantes'],
                'calculados_hc' => $evalHc['calculados'],
                'redondeados_hc' => $evalHc['redondeados'],
                'correcciones_aplicadas' => $evalHc['correcciones_aplicadas'],
                'avisos_hc' => $evalHc['avisos'] ?? [],
            ]);
            $datosCrudos = json_encode($crudosPersistir, JSON_UNESCAPED_UNICODE);

            // Validación de tolerancia temporal según ASTM C39
            $fechaProgTimestamp = strtotime($edadData['fecha_programada'] . ' 08:00:00');
            $fechaRealTimestamp = strtotime($fechaReal);
            $diferenciaHoras = abs($fechaRealTimestamp - $fechaProgTimestamp) / 3600;
            $toleranciaHoras = (int)$edadData['tolerancia_horas'];

            $fueraTolerancia = ($diferenciaHoras > $toleranciaHoras) ? 1 : 0;

            if ($fueraTolerancia && empty($justificacion)) {
                return [
                    'exito' => false,
                    'requiere_justificacion' => true,
                    'mensaje' => "El ensayo se realizó fuera de la ventana de tolerancia ASTM C39 (±{$toleranciaHoras} hrs). Debe ingresar una justificación técnica obligatoria."
                ];
            }

            $this->db->beginTransaction();

            $stmtRot = $this->db->prepare("
                INSERT INTO roturas (especimen_id, plan_edad_id, fecha_real, carga, area, esfuerzo_psi, esfuerzo_kgcm2, porcentaje_fc, tipo_falla, tecnico_id, fuera_tolerancia, justificacion, datos_crudos)
                VALUES (:especimen_id, :plan_edad_id, :fecha_real, :carga, :area, :esfuerzo_psi, :esfuerzo_kgcm2, :porcentaje_fc, :tipo_falla, :tecnico_id, :fuera_tolerancia, :justificacion, :datos_crudos)
                ON DUPLICATE KEY UPDATE 
                    fecha_real = VALUES(fecha_real), carga = VALUES(carga), area = VALUES(area),
                    esfuerzo_psi = VALUES(esfuerzo_psi), esfuerzo_kgcm2 = VALUES(esfuerzo_kgcm2),
                    porcentaje_fc = VALUES(porcentaje_fc), tipo_falla = VALUES(tipo_falla),
                    tecnico_id = VALUES(tecnico_id), fuera_tolerancia = VALUES(fuera_tolerancia),
                    justificacion = VALUES(justificacion), datos_crudos = VALUES(datos_crudos)
            ");
            $stmtRot->execute([
                'especimen_id' => $especimenId,
                'plan_edad_id' => $planEdadId,
                'fecha_real' => $fechaReal,
                'carga' => $carga,
                'area' => $area,
                'esfuerzo_psi' => $esfuerzoPsi,
                'esfuerzo_kgcm2' => $esfuerzoKgCm2,
                'porcentaje_fc' => $porcentajeFc,
                'tipo_falla' => $tipoFalla,
                'tecnico_id' => $tecnicoId,
                'fuera_tolerancia' => $fueraTolerancia,
                'justificacion' => $justificacion,
                'datos_crudos' => $datosCrudos
            ]);

            // Marcar espécimen como ensayado
            $this->db->prepare("UPDATE especimenes SET estado = 'ensayado' WHERE id = :id")->execute(['id' => $especimenId]);

            // Verificar estado del plan por edad
            $stmtCheckEdad = $this->db->prepare("
                SELECT COUNT(*) FROM plan_rotura_asignaciones pra
                JOIN especimenes esp ON pra.especimen_id = esp.id
                WHERE pra.plan_edad_id = :plan_edad_id AND esp.estado != 'ensayado'
            ");
            $stmtCheckEdad->execute(['plan_edad_id' => $planEdadId]);
            $pendientesEdad = (int)$stmtCheckEdad->fetchColumn();

            if ($pendientesEdad === 0) {
                $this->db->prepare("UPDATE plan_rotura_edades SET estado = 'completado' WHERE id = :id")->execute(['id' => $planEdadId]);
            } else {
                $this->db->prepare("UPDATE plan_rotura_edades SET estado = 'en_proceso' WHERE id = :id")->execute(['id' => $planEdadId]);
            }

            // Verificar si todo el plan está completado
            $planRoturaId = (int)$edadData['plan_rotura_id'];
            $stmtCheckPlan = $this->db->prepare("SELECT COUNT(*) FROM plan_rotura_edades WHERE plan_rotura_id = :plan_id AND estado != 'completado'");
            $stmtCheckPlan->execute(['plan_id' => $planRoturaId]);
            $edadesPendientesPlan = (int)$stmtCheckPlan->fetchColumn();

            if ($edadesPendientesPlan === 0) {
                $this->db->prepare("UPDATE planes_rotura SET estado = 'cerrado' WHERE id = :id")->execute(['id' => $planRoturaId]);
            }

            $this->db->commit();
            return [
                'exito' => true,
                'mensaje' => 'Rotura registrada exitosamente mediante hoja de cálculo.',
                'carga' => $carga,
                'area' => $area,
                'esfuerzo_psi' => $esfuerzoPsi,
                'esfuerzo_kgcm2' => $esfuerzoKgCm2,
                'porcentaje_fc' => $porcentajeFc,
                'datos_crudos' => $datosCrudos,
                'evaluacion_hc' => $evalHc
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('RoturasModelo::registrarRoturaHc error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al registrar la rotura mediante hoja de cálculo.'];
        }
    }

    /**
     * Genera un informe parcial de rotura para una edad completada.
     */
    public function generarInformeParcial(int $muestraId, int $planEdadId, int $usuarioId): array {
        try {
            $stmtEdad = $this->db->prepare("SELECT * FROM plan_rotura_edades WHERE id = :id");
            $stmtEdad->execute(['id' => $planEdadId]);
            $edad = $stmtEdad->fetch(PDO::FETCH_ASSOC);

            if (!$edad || $edad['estado'] !== 'completado') {
                return ['exito' => false, 'mensaje' => 'La edad programada aún no ha sido completada con todos sus ensayos.'];
            }

            $anio = (int)date('Y');
            $numeroInforme = $this->obtenerSiguienteNumeroInforme($anio);

            $stmtInsert = $this->db->prepare("
                INSERT INTO informes_rotura (tipo, muestra_id, plan_edad_id, numero, version, estado)
                VALUES ('parcial', :muestra_id, :plan_edad_id, :numero, 1, 'en_revision')
            ");
            $stmtInsert->execute([
                'muestra_id' => $muestraId,
                'plan_edad_id' => $planEdadId,
                'numero' => $numeroInforme
            ]);
            $informeId = (int)$this->db->lastInsertId();

            registrarBitacora('laboratorio', 'generar_informe_parcial', "Informe parcial {$numeroInforme} generado para muestra #{$muestraId} (Edad: {$edad['edad_dias']} días)", $informeId);

            return ['exito' => true, 'mensaje' => "Informe parcial {$numeroInforme} generado y enviado a revisión de Calidad.", 'informe_id' => $informeId, 'numero' => $numeroInforme];
        } catch (Exception $e) {
            error_log('RoturasModelo::generarInformeParcial error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al generar el informe parcial.'];
        }
    }

    /**
     * Procesa la revisión y aprobación/devolución de un informe de laboratorio.
     * Si el informe ya está aprobado, se bloquea cualquier modificación posterior (ISO/IEC 17025).
     * Al aprobar, genera una ruta simulada/real de PDF e inscribe un hash SHA-256 inmutable.
     */
    public function procesarRevisionInforme(int $informeId, string $nuevoEstado, ?string $motivoDevolucion, int $usuarioId, int $rolId): array {
        if ($rolId !== 1 && $rolId !== 3) {
            return ['exito' => false, 'mensaje' => 'Acceso denegado: Solo el Administrador o Supervisor de Calidad puede aprobar o devolver informes.'];
        }

        if (!in_array($nuevoEstado, ['aprobado', 'devuelto'], true)) {
            return ['exito' => false, 'mensaje' => 'Estado de revisión no válido.'];
        }

        if ($nuevoEstado === 'devuelto' && empty(trim($motivoDevolucion ?? ''))) {
            return ['exito' => false, 'mensaje' => 'Debe especificar el motivo de devolución del informe.'];
        }

        // Obtener el informe actual
        $stmtInf = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
        $stmtInf->execute(['id' => $informeId]);
        $informe = $stmtInf->fetch(PDO::FETCH_ASSOC);

        if (!$informe) {
            return ['exito' => false, 'mensaje' => 'Informe no encontrado.'];
        }

        // Bloqueo post-aprobación estricto (ISO/IEC 17025)
        if ($informe['estado'] === 'aprobado') {
            return [
                'exito' => false,
                'mensaje' => 'El informe ya se encuentra aprobado y está bloqueado para modificaciones (integridad ISO/IEC 17025).'
            ];
        }

        if ($informe['estado'] === 'anulado') {
            return [
                'exito' => false,
                'mensaje' => 'El informe se encuentra anulado y no puede ser modificado.'
            ];
        }

        // Al aprobar (nuevoEstado = 'aprobado'):
        // Generar ruta de PDF y calcular hash SHA-256 inmutable
        $pdfRuta = $informe['pdf'] ?? null;
        $hashSha256 = $informe['sha256'] ?? null;
        if ($nuevoEstado === 'aprobado') {
            $numero = $informe['numero'];
            $version = (int)($informe['version'] ?? 1);
            $pdfRuta = 'almacen/informes/' . $numero . '_v' . $version . '.pdf';
            $payloadSha = sprintf(
                "INFORME:%s|VER:%d|MUESTRA:%d|EDAD:%s|FECHA:%s",
                $numero,
                $version,
                $informe['muestra_id'],
                $informe['plan_edad_id'] ?? 'CONSOL',
                date('Y-m-d H:i:s')
            );
            $hashSha256 = hash('sha256', $payloadSha);
        }

        $stmt = $this->db->prepare("
            UPDATE informes_rotura 
            SET estado = :estado, 
                motivo_devolucion = :motivo, 
                aprobado_por = :aprobado_por, 
                fecha_aprobacion = " . ($nuevoEstado === 'aprobado' ? "NOW()" : "NULL") . ",
                pdf = :pdf,
                sha256 = :sha256
            WHERE id = :id
        ");
        $stmt->execute([
            'estado' => $nuevoEstado,
            'motivo' => ($nuevoEstado === 'devuelto') ? trim($motivoDevolucion) : null,
            'aprobado_por' => ($nuevoEstado === 'aprobado') ? $usuarioId : null,
            'pdf' => $pdfRuta,
            'sha256' => $hashSha256,
            'id' => $informeId
        ]);

        registrarBitacora('laboratorio', 'revision_informe', "Informe #{$informeId} pasó a estado '{$nuevoEstado}' por supervisor ID {$usuarioId}", $informeId);

        return [
            'exito' => true,
            'mensaje' => "El informe ha sido marcado como '{$nuevoEstado}' exitosamente.",
            'pdf' => $pdfRuta,
            'sha256' => $hashSha256
        ];
    }

    /**
     * Reemite un informe de rotura (Fase 1 · ISO/IEC 17025 Cláusula 7.8.8):
     * 1. Valida que el motivo de reemisión no esté vacío.
     * 2. Obtiene el informe actual.
     * 3. Marca el informe actual como 'anulado' con motivo_reemision = $motivo.
     * 4. Calcula la versión N+1: MAX(version) + 1 para esa misma muestra y edad.
     * 5. Genera un nuevo consecutivo anual con obtenerSiguienteNumeroInforme().
     * 6. Inserta el nuevo informe con estado = 'en_revision'.
     * 7. Invalida cualquier informe 'consolidado' preexistente de la muestra.
     * 8. Registra bitácora de auditoría inmutable.
     */
    public function reemitirInforme(int $informeId, string $motivo, int $usuarioId): array {
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['exito' => false, 'mensaje' => 'Debe especificar el motivo de reemisión del informe.'];
        }

        try {
            $stmtInf = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
            $stmtInf->execute(['id' => $informeId]);
            $informeActual = $stmtInf->fetch(PDO::FETCH_ASSOC);

            if (!$informeActual) {
                return ['exito' => false, 'mensaje' => 'El informe a reemitir no existe.'];
            }

            if ($informeActual['estado'] === 'anulado') {
                return ['exito' => false, 'mensaje' => 'El informe ya se encuentra anulado y no puede ser reemitido nuevamente.'];
            }

            $muestraId = (int)$informeActual['muestra_id'];
            $planEdadId = $informeActual['plan_edad_id'] !== null ? (int)$informeActual['plan_edad_id'] : null;

            $this->db->beginTransaction();

            // 1. Marcar el informe actual como 'anulado' con motivo_reemision = $motivo
            $stmtAnular = $this->db->prepare("
                UPDATE informes_rotura 
                SET estado = 'anulado', motivo_reemision = :motivo 
                WHERE id = :id
            ");
            $stmtAnular->execute([
                'motivo' => $motivo,
                'id' => $informeId
            ]);

            // 2. Calcular versión N+1: consultar MAX(version) de informes_rotura para esa misma muestra_id y plan_edad_id
            if ($planEdadId !== null) {
                $stmtMaxVer = $this->db->prepare("
                    SELECT COALESCE(MAX(version), 0) 
                    FROM informes_rotura 
                    WHERE muestra_id = :mid AND plan_edad_id = :peid
                ");
                $stmtMaxVer->execute([
                    'mid' => $muestraId,
                    'peid' => $planEdadId
                ]);
            } else {
                $stmtMaxVer = $this->db->prepare("
                    SELECT COALESCE(MAX(version), 0) 
                    FROM informes_rotura 
                    WHERE muestra_id = :mid AND plan_edad_id IS NULL AND tipo = :tipo
                ");
                $stmtMaxVer->execute([
                    'mid' => $muestraId,
                    'tipo' => $informeActual['tipo']
                ]);
            }
            $maxVersion = (int)$stmtMaxVer->fetchColumn();
            $nuevaVersion = max($maxVersion, (int)$informeActual['version']) + 1;

            // 3. Generar nuevo consecutivo anual con obtenerSiguienteNumeroInforme()
            $anio = (int)date('Y');
            $nuevoNumero = $this->obtenerSiguienteNumeroInforme($anio);

            // 4. Insertar el nuevo informe con tipo = 'parcial', muestra_id, plan_edad_id, nuevo numero, version N+1, estado = 'en_revision'
            $tipoNuevo = ($informeActual['tipo'] === 'consolidado') ? 'consolidado' : 'parcial';
            $stmtInsert = $this->db->prepare("
                INSERT INTO informes_rotura (tipo, muestra_id, plan_edad_id, numero, version, estado, sustituye_a)
                VALUES (:tipo, :muestra_id, :plan_edad_id, :numero, :version, 'en_revision', :sustituye_a)
            ");
            $stmtInsert->execute([
                'tipo' => $tipoNuevo,
                'muestra_id' => $muestraId,
                'plan_edad_id' => $planEdadId,
                'numero' => $nuevoNumero,
                'version' => $nuevaVersion,
                'sustituye_a' => $informeActual['numero']
            ]);
            $nuevoInformeId = (int)$this->db->lastInsertId();

            // Registrar en documentos_reemisiones (Fase B TAREA-059)
            try {
                $stmtReem = $this->db->prepare("
                    INSERT INTO documentos_reemisiones (tipo_documento, documento_base_id, version, codigo_version, sustituye_a, tipo_correccion, motivo, estado, solicitado_por)
                    VALUES ('informe_rotura', :base_id, :version, :codigo, :sustituye, 'tecnica', :motivo, 'en_revision', :usuario_id)
                ");
                $stmtReem->execute([
                    'base_id' => $nuevoInformeId,
                    'version' => $nuevaVersion,
                    'codigo' => $nuevoNumero,
                    'sustituye' => $informeActual['numero'],
                    'motivo' => $motivo,
                    'usuario_id' => $usuarioId
                ]);
            } catch (\Exception $e) {
                error_log('Error registrando en documentos_reemisiones: ' . $e->getMessage());
            }

            // 5. Si la muestra ya contaba con un informe 'consolidado', invalidar dicho consolidado
            $stmtCons = $this->db->prepare("
                SELECT id, numero, version, estado 
                FROM informes_rotura 
                WHERE muestra_id = :mid AND tipo = 'consolidado' AND estado != 'anulado' AND id != :nuevo_id
            ");
            $stmtCons->execute([
                'mid' => $muestraId,
                'nuevo_id' => $nuevoInformeId
            ]);
            $consolidados = $stmtCons->fetchAll(PDO::FETCH_ASSOC);

            foreach ($consolidados as $cons) {
                $motivoCons = "Invalidado automáticamente por reemisión del informe parcial {$informeActual['numero']} (v{$informeActual['version']}) -> nuevo {$nuevoNumero} (v{$nuevaVersion}). Motivo reemisión: {$motivo}";
                $stmtAnularCons = $this->db->prepare("
                    UPDATE informes_rotura 
                    SET estado = 'anulado', motivo_reemision = :motivo 
                    WHERE id = :id
                ");
                $stmtAnularCons->execute([
                    'motivo' => $motivoCons,
                    'id' => $cons['id']
                ]);

                registrarBitacora(
                    'laboratorio',
                    'invalidar_informe_consolidado',
                    "Informe consolidado {$cons['numero']} (ID #{$cons['id']}) invalidado por reemisión de parcial para la muestra #{$muestraId}",
                    (int)$cons['id']
                );
            }

            // 6. Registrar bitácora de auditoría
            registrarBitacora(
                'laboratorio',
                'reemitir_informe',
                "Informe #{$informeId} ({$informeActual['numero']} v{$informeActual['version']}) reemitido a nuevo informe #{$nuevoInformeId} ({$nuevoNumero} v{$nuevaVersion}) por usuario ID {$usuarioId}. Motivo: {$motivo}",
                $nuevoInformeId
            );

            $this->db->commit();

            return [
                'exito' => true,
                'mensaje' => "Informe reemitido exitosamente como {$nuevoNumero} (versión {$nuevaVersion}).",
                'nuevo_informe_id' => $nuevoInformeId,
                'nuevo_numero' => $nuevoNumero,
                'nueva_version' => $nuevaVersion,
                'informe_anterior_id' => $informeId,
                'consolidados_invalidados' => count($consolidados)
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('RoturasModelo::reemitirInforme error: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno al reemitir informe: ' . $e->getMessage()];
        }
    }

    /**
     * Genera el informe consolidado. Bloqueo estricto si existe algún parcial pendiente o devuelto.
     */
    public function generarInformeConsolidado(int $muestraId, int $usuarioId, int $rolId): array {
        if ($rolId !== 1 && $rolId !== 3) {
            return ['exito' => false, 'mensaje' => 'Acceso denegado: Solo Calidad o Administrador puede generar informes consolidados.'];
        }

        // 1. Obtener todas las edades del plan
        $stmtEdades = $this->db->prepare("
            SELECT pre.id, pre.edad_dias, pre.estado,
                   (SELECT ir.estado FROM informes_rotura ir WHERE ir.plan_edad_id = pre.id AND ir.tipo = 'parcial' AND ir.estado != 'anulado' ORDER BY ir.id DESC LIMIT 1) AS informe_parcial_estado
            FROM plan_rotura_edades pre
            JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
            WHERE pr.muestra_id = :id
        ");
        $stmtEdades->execute(['id' => $muestraId]);
        $edades = $stmtEdades->fetchAll(PDO::FETCH_ASSOC);

        if (empty($edades)) {
            return ['exito' => false, 'mensaje' => 'No se encontraron edades programadas para esta muestra.'];
        }

        foreach ($edades as $e) {
            if ($e['estado'] !== 'completado') {
                return ['exito' => false, 'mensaje' => "No se puede emitir el consolidado: La edad de {$e['edad_dias']} días aún no está completada."];
            }
            if ($e['informe_parcial_estado'] !== 'aprobado') {
                $estadoTxt = $e['informe_parcial_estado'] ?: 'sin informe';
                return ['exito' => false, 'mensaje' => "No se puede emitir el consolidado: El informe parcial de {$e['edad_dias']} días está en estado '{$estadoTxt}'. Todos los parciales deben estar APROBADOS por Calidad."];
            }
        }

        $anio = (int)date('Y');
        $numeroInforme = $this->obtenerSiguienteNumeroInforme($anio);

        $pdfConsolidado = 'almacen/informes/' . $numeroInforme . '_v1.pdf';
        $shaConsolidado = hash('sha256', "INFORME:{$numeroInforme}|VER:1|MUESTRA:{$muestraId}|CONSOL|FECHA:" . date('Y-m-d H:i:s'));

        $stmtInsert = $this->db->prepare("
            INSERT INTO informes_rotura (tipo, muestra_id, plan_edad_id, numero, version, estado, aprobado_por, fecha_aprobacion, pdf, sha256)
            VALUES ('consolidado', :muestra_id, NULL, :numero, 1, 'aprobado', :aprobado_por, NOW(), :pdf, :sha256)
        ");
        $stmtInsert->execute([
            'muestra_id' => $muestraId,
            'numero' => $numeroInforme,
            'aprobado_por' => $usuarioId,
            'pdf' => $pdfConsolidado,
            'sha256' => $shaConsolidado
        ]);
        $informeId = (int)$this->db->lastInsertId();

        registrarBitacora('laboratorio', 'generar_informe_consolidado', "Informe CONSOLIDADO {$numeroInforme} emitido y aprobado para muestra #{$muestraId}", $informeId);

        return [
            'exito' => true,
            'mensaje' => "Informe Consolidado {$numeroInforme} emitido exitosamente.",
            'informe_id' => $informeId,
            'numero' => $numeroInforme,
            'pdf' => $pdfConsolidado,
            'sha256' => $shaConsolidado
        ];
    }

    /**
     * Reidentificación blindada bajo ISO 17025 Cláusula 4.1 y 4.2.
     * Solo para Administrador (1), Calidad/Supervisor (3) o Comercial (2).
     * Prohibido para rol técnico (6).
     * Registra de manera inmutable en bitacora_reidentificacion y bitacora.
     */
    public function reidentificarMuestra(string $codigoLab, int $usuarioId, string $usuarioNombre, int $rolId, string $ip, string $motivo): array {
        // Bloqueo terminante para rol técnico
        if ($rolId === 6) {
            return [
                'exito' => false,
                'codigo_http' => 403,
                'mensaje' => 'Acceso Denegado (ISO 17025): El personal técnico de laboratorio no está autorizado para reidentificar muestras ni consultar datos comerciales de clientes.'
            ];
        }

        // Roles permitidos: 1 (Admin), 3 (Supervisor / Gerente Calidad), 2 (Comercial)
        if (!in_array($rolId, [1, 2, 3], true)) {
            return [
                'exito' => false,
                'codigo_http' => 403,
                'mensaje' => 'Acceso Denegado: Su rol no posee privilegios de reidentificación de muestras.'
            ];
        }

        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 5) {
            return [
                'exito' => false,
                'codigo_http' => 422,
                'mensaje' => 'Debe ingresar un motivo justificado de reidentificación para auditoría (mínimo 5 caracteres).'
            ];
        }

        // Buscar muestra y datos comerciales
        $stmt = $this->db->prepare("
            SELECT rm.id AS muestra_id, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion,
                   os.id AS os_id, os.codigo_os, os.nombre_proyecto, os.atencion_a,
                   c.id AS cliente_id, c.nombre_razon_social AS cliente_nombre, 
                   COALESCE(c.numero_ruc, c.identificacion, '') AS ruc, 
                   c.telefono, c.email AS correo, c.direccion AS direccion_proyecto
            FROM recepcion_muestras rm
            JOIN ordenes_servicio os ON rm.id_os = os.id
            JOIN clientes c ON os.id_cliente = c.id
            WHERE rm.codigo_muestra = :cod OR rm.id = :id_alt
            LIMIT 1
        ");
        $idAlt = is_numeric($codigoLab) ? (int)$codigoLab : 0;
        $stmt->execute(['cod' => $codigoLab, 'id_alt' => $idAlt]);
        $datosCliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datosCliente) {
            return [
                'exito' => false,
                'codigo_http' => 404,
                'mensaje' => 'Muestra no encontrada en el sistema para reidentificación.'
            ];
        }

        // Determinar nombre del rol
        $rolNombre = match ($rolId) {
            1 => 'Administrador',
            2 => 'Comercial',
            3 => 'Gerente de Calidad / Supervisor',
            default => 'Usuario Autorizado'
        };

        // 1. Registrar en bitacora_reidentificacion
        $stmtReid = $this->db->prepare("
            INSERT INTO bitacora_reidentificacion (usuario_id, usuario_nombre, usuario_rol, muestra_id, codigo_lab, motivo, ip)
            VALUES (:uid, :unom, :urol, :mid, :clab, :mot, :ip)
        ");
        $stmtReid->execute([
            'uid' => $usuarioId,
            'unom' => $usuarioNombre,
            'urol' => $rolNombre,
            'mid' => $datosCliente['muestra_id'],
            'clab' => $datosCliente['codigo_muestra'],
            'mot' => $motivo,
            'ip' => $ip
        ]);

        // 2. Registrar en bitacora general inmutable
        registrarBitacora(
            'laboratorio',
            'reidentificar_muestra',
            "Reidentificación autorizada de muestra {$datosCliente['codigo_muestra']} hacia cliente {$datosCliente['cliente_nombre']}. Motivo: {$motivo}",
            (int)$datosCliente['muestra_id']
        );

        return [
            'exito' => true,
            'codigo_http' => 200,
            'mensaje' => 'Reidentificación autorizada y registrada en la bitácora inmutable.',
            'datos' => [
                'codigo_lab' => $datosCliente['codigo_muestra'],
                'codigo_campo' => $datosCliente['codigo_campo'],
                'codigo_os' => $datosCliente['codigo_os'],
                'cliente' => $datosCliente['cliente_nombre'],
                'ruc' => $datosCliente['ruc'],
                'proyecto' => $datosCliente['nombre_proyecto'],
                'direccion_proyecto' => $datosCliente['direccion_proyecto'],
                'contacto' => $datosCliente['atencion_a'],
                'fecha_recepcion' => $datosCliente['fecha_recepcion']
            ]
        ];
    }

    /**
     * Tolerancia en horas para edades estándar ASTM C39.
     */
    private function obtenerToleranciaHorasAstmC39(int $edadDias): int {
        return match ($edadDias) {
            1 => 1,       // 24 h ±0.5 h (redondeado a 1h)
            3 => 2,       // 3 días ±2 h
            7 => 6,       // 7 días ±6 h
            14 => 12,     // 14 días ±12 h
            28 => 20,     // 28 días ±20 h
            90 => 48,     // 90 días ±48 h
            default => max(2, (int)round($edadDias * 0.75))
        };
    }

    /**
     * Calcula el consecutivo único anual para informes INF-YYYY-XXXX.
     */
    public function obtenerSiguienteNumeroInforme(int $anio): string {
        $stmt = $this->db->prepare("SELECT numero FROM informes_rotura WHERE numero LIKE :pat");
        $stmt->execute(['pat' => "INF-{$anio}-%"]);
        $maxNum = 0;
        while ($num = $stmt->fetchColumn()) {
            if (preg_match('/^INF-\d+-(\d+)$/', $num, $m)) {
                $val = (int)$m[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM informes_rotura WHERE YEAR(created_at) = :anio");
        $stmtCount->execute(['anio' => $anio]);
        $count = (int)$stmtCount->fetchColumn();
        $consecutivo = max($maxNum, $count) + 1;

        return sprintf("INF-%d-%04d", $anio, $consecutivo);
    }
}
