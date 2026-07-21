<?php

namespace Cycsa\Modulos\Operaciones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Exception;

class OperacionModelo extends ModeloBase {

    /**
     * Obtiene cotizaciones aprobadas para poder generar una Orden de Servicio (O/S).
     */
    public function obtenerCotizacionesParaOS(string $busqueda = ''): array {
        $sql = "SELECT cot.id, cot.codigo, cot.nombre_proyecto, cli.nombre_razon_social AS cliente_nombre, 
                       cot.prioridad, cot.fecha_creacion, cot.condicion_pago
                FROM cotizaciones cot
                JOIN clientes cli ON cot.id_cliente = cli.id
                WHERE cot.estado = 'Aprobada por Cliente'
                AND cot.id NOT IN (SELECT id_cotizacion FROM ordenes_servicio WHERE tipo_contrato = 'Puntual')";

        if ($busqueda !== '') {
            $sql .= " AND (cot.codigo LIKE :q1 OR cot.nombre_proyecto LIKE :q2 OR cli.nombre_razon_social LIKE :q3)";
            $stmt = $this->db->prepare($sql);
            $term = '%' . trim($busqueda) . '%';
            $stmt->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una Orden de Servicio (O/S) en base a una cotización, incluyendo programación.
     */
    public function crearOS(int $idCotizacion, string $tipoContrato, ?string $fechaMuestreo = null, ?string $horaMuestreo = null, ?string $tecnicoMuestreo = null, ?string $vehiculoMuestreo = null): string {
        $anio = (int)date('Y');
        
        // Generar código secuencial OS-AÑO-CONSECUTIVO
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ordenes_servicio WHERE YEAR(fecha_emision) = :anio");
        $stmt->execute(['anio' => $anio]);
        $consecutivo = (int)$stmt->fetchColumn() + 1;
        $codigoOS = sprintf("OS-%d-%04d", $anio, $consecutivo);

        $sql = "INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, tipo_contrato, fecha_emision, estado, fecha_muestreo, hora_muestreo, tecnico_muestreo, vehiculo_muestreo) 
                VALUES (:codigo_os, :id_cotizacion, :tipo_contrato, CURRENT_DATE, 'Abierta', :fecha_m, :hora_m, :tecnico_m, :vehiculo_m)";
        $stmtInsert = $this->db->prepare($sql);
        $stmtInsert->execute([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $idCotizacion,
            'tipo_contrato' => $tipoContrato,
            'fecha_m' => !empty($fechaMuestreo) ? $fechaMuestreo : null,
            'hora_m' => !empty($horaMuestreo) ? $horaMuestreo : null,
            'tecnico_m' => !empty($tecnicoMuestreo) ? $tecnicoMuestreo : null,
            'vehiculo_m' => !empty($vehiculoMuestreo) ? $vehiculoMuestreo : null
        ]);

        return $codigoOS;
    }

    /**
     * Obtiene el listado de Órdenes de Servicio (O/S) activas.
     */
    public function obtenerOSActivas(string $busqueda = ''): array {
        $sql = "SELECT os.*,
                       cot.codigo AS cot_codigo, cot.nombre_proyecto, cot.total AS cot_total,
                       cli.id AS cliente_id, cli.nombre_razon_social AS cliente_nombre
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id";

        if ($busqueda !== '') {
            $sql .= " WHERE os.codigo_os LIKE :q1 OR cot.nombre_proyecto LIKE :q2 OR cli.nombre_razon_social LIKE :q3
                      ORDER BY os.id DESC";
            $stmt = $this->db->prepare($sql);
            $term = '%' . trim($busqueda) . '%';
            $stmt->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $sql .= " ORDER BY os.id DESC";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los productos/ensayos de una O/S y su estado de recepción (aislado por O/S y soportando múltiples muestras por servicio).
     */
    public function obtenerItemsOS(int $idOS): array {
        $sql = "SELECT cd.id AS id_detalle, cd.descripcion_ensayo, cd.codigo_servicio, cd.cantidad AS cantidad_facturada,
                       (SELECT COUNT(DISTINCT lm.id)
                        FROM ensayo_edades ee
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        WHERE ee.id_detalle_cotizacion = cd.id AND rm.id_os = os.id) AS total_recibidos,
                       (SELECT rm.codigo_muestra 
                        FROM ensayo_edades ee 
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        WHERE ee.id_detalle_cotizacion = cd.id AND rm.id_os = os.id
                        ORDER BY lm.id DESC LIMIT 1) AS codigo_muestra,
                       (SELECT lm.id 
                        FROM ensayo_edades ee 
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        WHERE ee.id_detalle_cotizacion = cd.id AND rm.id_os = os.id
                        ORDER BY lm.id DESC LIMIT 1) AS id_lote
                FROM cotizacion_detalles cd
                JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                WHERE os.id = :id_os";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_os' => $idOS]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una sola O/S con detalles del cliente y del proyecto.
     */
    public function obtenerOSPorId(int $idOS): ?array {
        $sql = "SELECT os.id, os.codigo_os, os.tipo_contrato, os.fecha_emision, os.estado, os.id_cotizacion,
                       os.tecnico_muestreo, os.vehiculo_muestreo, os.fecha_muestreo, os.hora_muestreo,
                       cot.codigo AS cot_codigo, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                       cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono, cli.email AS cliente_email
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                WHERE os.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idOS]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }
    

    /**
     * Obtiene los detalles de la cotización asociados.
     */
    public function obtenerDetallesCotizacion(int $idCotizacion): array {
        $sql = "SELECT cd.id, cd.descripcion_ensayo, cd.cantidad, p.codigo_servicio, p.norma_astm, p.formato_id 
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                WHERE cd.id_cotizacion = :id_cotizacion";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cotizacion' => $idCotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra el ingreso de muestras y especímenes (Fase Recepción).
     */
    public function registrarRecepcion(array $datos): bool {
        try {
            $this->db->beginTransaction();

            $idOS = (int)$datos['id_os'];
            $codigoCampo = trim($datos['codigo_campo'] ?? '');
            $entregadoPor = trim($datos['entregado_por'] ?? '');
            $observaciones = trim($datos['observaciones'] ?? '');
            $fechaRecepcion = $datos['fecha_recepcion'] ?? date('Y-m-d H:i:s');
            
            $tipoMuestra = in_array($datos['tipo_muestra'] ?? '', ['Campo', 'Laboratorio']) ? $datos['tipo_muestra'] : 'Laboratorio';
            $isQaQc = !empty($datos['is_qa_qc']) ? 1 : 0;
            $idCilindro = trim($datos['id_cilindro'] ?? '');

            $anio = (int)date('Y', strtotime($fechaRecepcion));
            $anioShort = date('y', strtotime($fechaRecepcion));

            // ADQUIRIR CANDADO DE CONCURRENCIA PARA RECEPCION DE MUESTRAS
            $this->db->prepare("SELECT GET_LOCK('lock_recepcion_muestras', 10)")->execute();

            // Calcular correlativo por anio y tipo_muestra usando secuencias_muestras (Reinicio anual el 1° de Enero)
            $stmtSec = $this->db->prepare("SELECT ultimo_correlativo FROM secuencias_muestras WHERE anio = :anio AND tipo_muestra = :tipo");
            $stmtSec->execute(['anio' => $anio, 'tipo' => $tipoMuestra]);
            $lastCorr = $stmtSec->fetchColumn();
            $correlativo = ($lastCorr === false) ? 1 : (int)$lastCorr + 1;

            $stmtUpsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr) ON DUPLICATE KEY UPDATE ultimo_correlativo = :corr2");
            $stmtUpsert->execute(['anio' => $anio, 'tipo' => $tipoMuestra, 'corr' => $correlativo, 'corr2' => $correlativo]);

            // Formato de código consecutivo automático e inmutable por tipo
            $replicaCodigo = null;
            if ($tipoMuestra === 'Campo') {
                $codigoMuestra = sprintf("CAM-%02d-%04d", $anioShort, $correlativo);
            } else {
                $codigoMuestra = sprintf("MS-%04d-%02d", $correlativo, $anioShort);
            }

            if ($isQaQc) {
                $replicaCodigo = "-1";
                $codigoMuestra .= $replicaCodigo;
            }

            // Si no se provee código de campo, lo generamos
            if (empty($codigoCampo)) {
                $codigoCampo = sprintf("MC-%04d-%02d", $correlativo, $anioShort);
            }

            // 1. Insertar Recepción con inmutabilidad y sellado
            $sqlRec = "INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, tipo_muestra, codigo_muestra, id_cilindro, codigo_campo, is_qa_qc, replica_codigo, is_sealed, fecha_recepcion, recibido_por, entregado_por, observaciones, estado)
                       VALUES (:id_os, :correlativo_anual, :anio, :tipo_muestra, :codigo_muestra, :id_cilindro, :codigo_campo, :is_qa_qc, :replica_codigo, 1, :fecha_recepcion, :recibido_por, :entregado_por, :observaciones, 'Registrado')";
            $stmtRec = $this->db->prepare($sqlRec);
            $stmtRec->execute([
                'id_os' => $idOS,
                'correlativo_anual' => $correlativo,
                'anio' => $anio,
                'tipo_muestra' => $tipoMuestra,
                'codigo_muestra' => $codigoMuestra,
                'id_cilindro' => $idCilindro,
                'codigo_campo' => $codigoCampo,
                'is_qa_qc' => $isQaQc,
                'replica_codigo' => $replicaCodigo,
                'fecha_recepcion' => $fechaRecepcion,
                'recibido_por' => $_SESSION['usuario_id'],
                'entregado_por' => $entregadoPor,
                'observaciones' => $observaciones
            ]);

            $idRecepcion = $this->db->lastInsertId();

            // 2. Insertar Lote
            $nombreLote = trim($datos['nombre_lote'] ?? 'Muestra General');
            $disenoResistencia = trim($datos['diseno_resistencia'] ?? '');
            $fechaMoldeo = $datos['fecha_moldeo'] ?? date('Y-m-d');
            $revenimientoIn = !empty($datos['revenimiento_in']) ? (float)$datos['revenimiento_in'] : null;
            $revenimientoCm = !empty($datos['revenimiento_cm']) ? (float)$datos['revenimiento_cm'] : null;
            $temperaturaC = !empty($datos['temperatura_c']) ? (float)$datos['temperatura_c'] : null;
            $procedimiento = trim($datos['procedimiento_muestreo'] ?? 'ASTM C172');

            $sqlLote = "INSERT INTO lotes_muestras (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, revenimiento_cm, temperatura_c, procedimiento_muestreo)
                        VALUES (:id_recepcion, :nombre_lote, :diseno_resistencia, :fecha_moldeo, :revenimiento_in, :revenimiento_cm, :temperatura_c, :procedimiento_muestreo)";
            $stmtLote = $this->db->prepare($sqlLote);
            $stmtLote->execute([
                'id_recepcion' => $idRecepcion,
                'nombre_lote' => $nombreLote,
                'diseno_resistencia' => $disenoResistencia,
                'fecha_moldeo' => $fechaMoldeo,
                'revenimiento_in' => $revenimientoIn,
                'revenimiento_cm' => $revenimientoCm,
                'temperatura_c' => $temperaturaC,
                'procedimiento_muestreo' => $procedimiento
            ]);

            $idLote = $this->db->lastInsertId();

            // 3. Generar Especímenes y Edades Programadas
            $edadesDias = $datos['edades_dias'] ?? [];
            $edadesIdentificadores = $datos['edades_identificadores'] ?? [];
            $idDetalleCotizacion = (int)$datos['id_detalle_cotizacion'];

            if (empty($edadesDias)) {
                // Si es un ensayo sin edades (ej: Proctor), creamos una muestra técnica base con edad 0
                $sqlEdad = "INSERT INTO ensayo_edades (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, estado)
                            VALUES (:id_lote, :id_detalle_cotizacion, 'Muestra', 0, :fecha_programada, 'Completado')";
                $stmtEdad = $this->db->prepare($sqlEdad);
                $stmtEdad->execute([
                    'id_lote' => $idLote,
                    'id_detalle_cotizacion' => $idDetalleCotizacion,
                    'fecha_programada' => $fechaMoldeo
                ]);
            } else {
                for ($i = 0; $i < count($edadesDias); $i++) {
                    $edadDias = (int)$edadesDias[$i];
                    $identificadores = trim($edadesIdentificadores[$i] ?? '');
                    if ($edadDias <= 0 || $identificadores === '') continue;

                    $especimenes = explode(',', $identificadores);
                    foreach ($especimenes as $espName) {
                        $espName = trim($espName);
                        if ($espName === '') continue;

                        $fechaProgramada = date('Y-m-d', strtotime("$fechaMoldeo + $edadDias days"));

                        $sqlEdad = "INSERT INTO ensayo_edades (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, estado)
                                    VALUES (:id_lote, :id_detalle_cotizacion, :identificador_especimen, :edad_dias, :fecha_programada, 'Programado')";
                        $stmtEdad = $this->db->prepare($sqlEdad);
                        $stmtEdad->execute([
                            'id_lote' => $idLote,
                            'id_detalle_cotizacion' => $idDetalleCotizacion,
                            'identificador_especimen' => $espName,
                            'edad_dias' => $edadDias,
                            'fecha_programada' => $fechaProgramada
                        ]);
                    }
                }
            }

            // Obtener todos los ítems de la O/S y verificar si todos han sido recibidos
            $items = $this->obtenerItemsOS($idOS);
            $todosRecibidos = true;
            foreach ($items as $item) {
                $esItemActual = ((int)$item['id_detalle'] === $idDetalleCotizacion);
                if (empty($item['codigo_muestra']) && !$esItemActual) {
                    $todosRecibidos = false;
                    break;
                }
            }

            // Actualizar estado operativo de la O/S
            $nuevoEstadoOS = $todosRecibidos ? 'Estado 5: Solicitud Tecnicos' : 'En Proceso';
            $this->db->prepare("UPDATE ordenes_servicio SET estado = :nuevo_estado WHERE id = :id_os")
                     ->execute(['nuevo_estado' => $nuevoEstadoOS, 'id_os' => $idOS]);

            $this->db->commit();
            try {
                $this->db->prepare("SELECT RELEASE_LOCK('lock_recepcion_muestras')")->execute();
            } catch (Exception $lex) {}
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            try {
                $this->db->prepare("SELECT RELEASE_LOCK('lock_recepcion_muestras')")->execute();
            } catch (Exception $lex) {}
            error_log("Error al registrar recepción LIMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los ensayos programados para el calendario de rupturas.
     */
    public function obtenerEventosCalendario(string $inicio, string $fin): array {
        $sql = "SELECT ee.id AS id_ensayo, ee.edad_dias, ee.identificador_especimen, ee.fecha_programada AS fecha_evento, 
                       ee.estado, lm.nombre_lote, rm.codigo_muestra, rm.codigo_campo,
                       'ruptura' AS tipo_evento, '' AS codigo_os, '' AS hora_muestreo, '' AS tecnico_muestreo, '' AS vehiculo_muestreo
                FROM ensayo_edades ee
                JOIN lotes_muestras lm ON ee.id_lote = lm.id
                JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                WHERE ee.fecha_programada BETWEEN :inicio AND :fin
                
                UNION ALL
                
                SELECT os.id AS id_ensayo, 0 AS edad_dias, '' AS identificador_especimen, os.fecha_muestreo AS fecha_evento,
                       os.estado, '' AS nombre_lote, '' AS codigo_muestra, '' AS codigo_campo,
                       'muestreo' AS tipo_evento, os.codigo_os, os.hora_muestreo, os.tecnico_muestreo, os.vehiculo_muestreo
                FROM ordenes_servicio os
                WHERE os.fecha_muestreo BETWEEN :inicio_m AND :fin_m
                  AND os.fecha_muestreo IS NOT NULL
                
                ORDER BY fecha_evento ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'inicio' => $inicio, 
            'fin' => $fin,
            'inicio_m' => $inicio,
            'fin_m' => $fin
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las recepciones registradas.
     */
    public function obtenerRecepciones(string $busqueda = ''): array {
        $sql = "SELECT lm.id, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, rm.estado,
                       os.codigo_os, lm.nombre_lote, lm.fecha_moldeo
                FROM recepcion_muestras rm
                JOIN ordenes_servicio os ON rm.id_os = os.id
                JOIN lotes_muestras lm ON lm.id_recepcion = rm.id";

        if ($busqueda !== '') {
            $sql .= " WHERE rm.codigo_muestra LIKE :q1 OR rm.codigo_campo LIKE :q2 OR lm.nombre_lote LIKE :q3
                      ORDER BY rm.id DESC";
            $stmt = $this->db->prepare($sql);
            $term = '%' . trim($busqueda) . '%';
            $stmt->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $sql .= " ORDER BY rm.id DESC";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene especímenes programados para un lote específico.
     */
    public function obtenerDetallesLote(int $idLote): array {
        $sql = "SELECT ee.id, ee.identificador_especimen, ee.edad_dias, ee.fecha_programada, ee.fecha_ensaye_real,
                       ee.carga_lbs, ee.area_in2, ee.resistencia_psi, ee.resistencia_kgcm2, ee.porcentaje_diseno,
                       ee.cumple_norma, ee.estado, p.porcentaje_minimo_esperado, cd.descripcion_ensayo AS nombre_ensayo
                FROM ensayo_edades ee
                LEFT JOIN lotes_muestras lm ON ee.id_lote = lm.id
                LEFT JOIN cotizacion_detalles cd ON ee.id_detalle_cotizacion = cd.id
                LEFT JOIN ensayos_parametros p ON cd.id_producto = p.id_producto AND ee.edad_dias = p.edad_evaluada
                WHERE ee.id_lote = :id_lote
                ORDER BY ee.edad_dias ASC, ee.identificador_especimen ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_lote' => $idLote]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda el resultado de una ruptura experimental (Técnico).
     */
    public function guardarResultadoRuptura(int $idEnsayo, array $datos): array {
        try {
            $cargaLbs = (float)$datos['carga_lbs'];
            $areaIn2 = (float)$datos['area_in2'];
            
            // Resistencia PSI = Carga / Área
            $resistenciaPsi = $areaIn2 > 0 ? $cargaLbs / $areaIn2 : 0;
            
            // Resistencia Kg/cm² = PSI * 0.070307
            $resistenciaKg = $resistenciaPsi * 0.070307;

            // Obtener info del ensayo actual (edad, lote, diseño)
            $stmtLote = $this->db->prepare("SELECT lm.id AS id_lote, lm.diseno_resistencia, ee.id_detalle_cotizacion, ee.edad_dias
                                            FROM ensayo_edades ee
                                            JOIN lotes_muestras lm ON ee.id_lote = lm.id
                                            WHERE ee.id = :id");
            $stmtLote->execute(['id' => $idEnsayo]);
            $info = $stmtLote->fetch(PDO::FETCH_ASSOC);
            
            if (!$info) {
                return ['exito' => false, 'mensaje' => 'Ensayo no encontrado.', 'alerta_regresion' => false];
            }

            $edadActual = (int)$info['edad_dias'];
            $idLote = (int)$info['id_lote'];

            $disenoPsi = 0.0;
            // Parsear diseño numérico (ej: "3000 PSI" -> 3000)
            if (preg_match('/(\d+)\s*(PSI|psi|lb)/i', $info['diseno_resistencia'] ?? '', $matches)) {
                $disenoPsi = (float)$matches[1];
            } else {
                $disenoPsi = 3000.0; // Default
            }

            // Calcular porcentaje alcanzado
            $porcentaje = $disenoPsi > 0 ? ($resistenciaPsi / $disenoPsi) * 100 : 0;

            // Verificar contra el parámetro estándar de norma
            $stmtParam = $this->db->prepare("SELECT ep.porcentaje_minimo_esperado 
                                             FROM cotizacion_detalles cd
                                             JOIN ensayos_parametros ep ON cd.id_producto = ep.id_producto
                                             WHERE cd.id = :id_det AND ep.edad_evaluada = :edad");
            $stmtParam->execute([
                'id_det' => $info['id_detalle_cotizacion'],
                'edad' => $edadActual
            ]);
            $pctMinimo = $stmtParam->fetchColumn();
            
            $cumpleNorma = 1;
            if ($pctMinimo !== false && $porcentaje < (float)$pctMinimo) {
                $cumpleNorma = 0; // Levanta alerta visual / No cumple
            }

            // ═══════════════════════════════════════════════════════════════
            // VALIDACIÓN DE REGRESIÓN DE RESISTENCIA (CYCSA-RT-FM-22 A)
            // La resistencia en mayor edad NO debe ser menor que en menor edad.
            // Si ocurre, se permite guardar pero se genera ALERTA para revisión.
            // ═══════════════════════════════════════════════════════════════
            $alertaRegresion = false;
            $mensajeRegresion = '';
            
            $stmtPrevios = $this->db->prepare(
                "SELECT edad_dias, resistencia_psi, identificador_especimen 
                 FROM ensayo_edades 
                 WHERE id_lote = :id_lote 
                   AND estado = 'Completado' 
                   AND edad_dias < :edad_actual 
                   AND resistencia_psi IS NOT NULL
                 ORDER BY edad_dias DESC"
            );
            $stmtPrevios->execute([
                'id_lote' => $idLote,
                'edad_actual' => $edadActual
            ]);
            $previos = $stmtPrevios->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($previos as $prev) {
                if ($resistenciaPsi < (float)$prev['resistencia_psi']) {
                    $alertaRegresion = true;
                    $mensajeRegresion = sprintf(
                        'ALERTA DE REGRESIÓN: El resultado a los %dd (%.0f PSI) es MENOR que el resultado a los %dd (%.0f PSI) del cilindro %s. Este resultado requiere revisión del supervisor.',
                        $edadActual,
                        $resistenciaPsi,
                        $prev['edad_dias'],
                        $prev['resistencia_psi'],
                        $prev['identificador_especimen']
                    );
                    break; // Solo alertar contra la edad inmediata inferior
                }
            }

            // Guardar el resultado (siempre se guarda, pero se marca si hay regresión)
            $sql = "UPDATE ensayo_edades 
                    SET carga_lbs = :carga_lbs,
                        area_in2 = :area_in2,
                        resistencia_psi = :resistencia_psi,
                        resistencia_kgcm2 = :resistencia_kgcm2,
                        porcentaje_diseno = :porcentaje_diseno,
                        cumple_norma = :cumple_norma,
                        fecha_ensaye_real = :fecha_real,
                        estado = 'Completado',
                        usuario_ensayador = :ensayador
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $guardado = $stmt->execute([
                'carga_lbs' => $cargaLbs,
                'area_in2' => $areaIn2,
                'resistencia_psi' => $resistenciaPsi,
                'resistencia_kgcm2' => $resistenciaKg,
                'porcentaje_diseno' => $porcentaje,
                'cumple_norma' => $cumpleNorma,
                'fecha_real' => date('Y-m-d'),
                'ensayador' => $_SESSION['usuario_id'],
                'id' => $idEnsayo
            ]);

            if (!$guardado) {
                return ['exito' => false, 'mensaje' => 'Error al guardar el resultado de ruptura en la base de datos.', 'alerta_regresion' => false];
            }

            // Registrar regresión en bitácora si se detectó
            if ($alertaRegresion) {
                registrarBitacora('operaciones', 'alerta_regresion', $mensajeRegresion);
            }

            $mensaje = 'Resultado de ensaye cargado correctamente.';
            if ($alertaRegresion) {
                $mensaje .= ' ⚠️ ' . $mensajeRegresion;
            }

            return [
                'exito' => true, 
                'mensaje' => $mensaje, 
                'alerta_regresion' => $alertaRegresion,
                'resistencia_psi' => round($resistenciaPsi, 2),
                'resistencia_kgcm2' => round($resistenciaKg, 2),
                'porcentaje' => round($porcentaje, 2),
                'cumple_norma' => $cumpleNorma
            ];

        } catch (Exception $e) {
            error_log("Error al guardar ruptura LIMS: " . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error interno: ' . $e->getMessage(), 'alerta_regresion' => false];
        }
    }

    /**
     * Obtiene el listado de informes de versión generados.
     */
    public function obtenerHistorialInformes(int $idLote): array {
        $sql = "SELECT ic.*, r.nombre AS revisor_nombre, a.nombre AS aprobador_nombre
                FROM informes_control ic
                LEFT JOIN usuarios r ON ic.revisado_por = r.id
                LEFT JOIN usuarios a ON ic.aprobado_por = a.id
                WHERE ic.id_lote = :id_lote
                ORDER BY ic.version DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_lote' => $idLote]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra una nueva versión de informe en la base de datos y guarda el PDF físico.
     */
    public function registrarInforme(int $idLote, string $codigoInforme, int $version, string $codigoCompleto, string $tipoInforme, ?int $edadEvaluada, string $motivoReemplazo, string $rutaPdf, ?string $observacionesSupervisor = null, int $ocultarCumplimiento = 0): ?int {
        try {
            $sql = "INSERT INTO informes_control (id_lote, codigo_informe, version, codigo_completo, tipo_informe, edad_evaluada, estado_aprobacion, motivo_reemplazo, observaciones_supervisor, ocultar_columna_cumplimiento, ruta_archivo_pdf)
                    VALUES (:id_lote, :codigo_informe, :version, :codigo_completo, :tipo_informe, :edad_evaluada, 'Pendiente', :motivo_reemplazo, :obs, :ocultar, :ruta_pdf)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_lote' => $idLote,
                'codigo_informe' => $codigoInforme,
                'version' => $version,
                'codigo_completo' => $codigoCompleto,
                'tipo_informe' => $tipoInforme,
                'edad_evaluada' => $edadEvaluada,
                'motivo_reemplazo' => $motivoReemplazo,
                'obs' => $observacionesSupervisor,
                'ocultar' => $ocultarCumplimiento,
                'ruta_pdf' => $rutaPdf
            ]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error al registrar informe en DB: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cambia el estado de aprobación de un informe (Coordinación / Aprobador).
     */
    public function cambiarEstadoInforme(int $idInforme, string $nuevoEstado, int $idUsuario): bool {
        try {
            $column = '';
            if ($nuevoEstado === 'Revisado') {
                $column = ', revisado_por = :id_user';
            } elseif ($nuevoEstado === 'Aprobado') {
                $column = ', aprobado_por = :id_user';
            }
            
            $sql = "UPDATE informes_control SET estado_aprobacion = :estado $column WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $params = ['estado' => $nuevoEstado, 'id' => $idInforme];
            if ($column !== '') {
                $params['id_user'] = $idUsuario;
            }
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error al cambiar estado del informe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estado de la Orden de Servicio, motivo de observación y/o flag requiere_muestreo.
     */
    public function actualizarEstadoOS(int $idOS, string $estado, ?string $motivo = null, ?int $requiereMuestreo = null): bool {
        try {
            $sql = "UPDATE ordenes_servicio SET estado = :estado";
            $params = ['estado' => $estado, 'id' => $idOS];
            
            if ($motivo !== null) {
                $sql .= ", motivo_observacion = :motivo";
                $params['motivo'] = $motivo;
            }
            if ($requiereMuestreo !== null) {
                $sql .= ", requiere_muestreo = :requiere_muestreo";
                $params['requiere_muestreo'] = $requiereMuestreo;
            }
            
            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en actualizarEstadoOS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra la programación de muestreo en campo.
     */
    public function programarMuestreo(int $idOS, string $fecha, string $hora, string $tecnico, string $vehiculo): bool {
        try {
            $sql = "UPDATE ordenes_servicio 
                    SET fecha_muestreo = :fecha,
                        hora_muestreo = :hora,
                        tecnico_muestreo = :tecnico,
                        vehiculo_muestreo = :vehiculo,
                        estado = 'Estado 3B: Ejecucion Muestreo'
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'fecha' => $fecha,
                'hora' => $hora,
                'tecnico' => $tecnico,
                'vehiculo' => $vehiculo,
                'id' => $idOS
            ]);
        } catch (Exception $e) {
            error_log("Error en programarMuestreo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra la hoja de campo (CYCSA-RT-FM-07) y marca la fecha de inicio del retraso de horas personalizadas (ej. 24, 11, 12h...).
     */
    public function registrarHojaCampo(int $idOS, string $codigo, string $operador, string $notas, int $horasEspera = 24): bool {
        try {
            $sql = "UPDATE ordenes_servicio 
                    SET hoja_campo_codigo = :codigo,
                        hoja_campo_operador = :operador,
                        hoja_campo_notas = :notas,
                        horas_espera_requeridas = :horas,
                        fecha_registro_campo = NOW(),
                        estado = 'Estado 3C: Espera Muestreo'
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'codigo' => $codigo,
                'operador' => $operador,
                'notas' => $notas,
                'horas' => max(0, $horasEspera),
                'id' => $idOS
            ]);
        } catch (Exception $e) {
            error_log("Error en registrarHojaCampo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Permite a la supervisión u operadores omitir/liberar inmediatamente el tiempo de espera.
     */
    public function omitirEsperaMuestreo(int $idOS): bool {
        try {
            $sql = "UPDATE ordenes_servicio SET horas_espera_requeridas = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $idOS]);
        } catch (Exception $e) {
            error_log("Error en omitirEsperaMuestreo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la hoja de solicitud (CYCSA-RT-FM-13) asociada a una O/S si existe.
     */
    public function obtenerHojaSolicitudPorOS(int $idOS): ?array {
        $stmt = $this->db->prepare("SELECT * FROM hojas_solicitud WHERE id_os = :id_os");
        $stmt->execute(['id_os' => $idOS]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Guarda o actualiza los datos de la Hoja de Solicitud de Servicio (CYCSA-RT-FM-13).
     */
    public function guardarHojaSolicitud(array $datos): bool {
        try {
            $idOS = (int)$datos['id_os'];
            $fechaHoraLlegada = !empty($datos['fecha_hora_llegada_laboratorio']) ? $datos['fecha_hora_llegada_laboratorio'] : null;
            $codigoDoc = trim($datos['codigo_documento'] ?? 'CYCSA-RT-FM-13');
            $nombreEmpresa = trim($datos['nombre_empresa_o_cliente'] ?? '');
            $direccionProj = trim($datos['direccion_proyecto'] ?? '');
            $telefono = trim($datos['telefono'] ?? '');
            $email = trim($datos['correo_electronico'] ?? '');
            $personaEntrega = trim($datos['nombre_persona_entrega_muestra'] ?? '');
            
            // Set of checkboxes
            $naturalezaArr = $datos['naturaleza_muestra'] ?? [];
            $naturaleza = !empty($naturalezaArr) ? implode(',', $naturalezaArr) : null;
            
            $procedencia = trim($datos['procedencia_punto_muestreo'] ?? '');
            $personaToma = trim($datos['nombre_persona_toma_muestra'] ?? '');
            $fechaHoraToma = !empty($datos['fecha_hora_toma_muestra']) ? $datos['fecha_hora_toma_muestra'] : null;
            $condicionMuestreoDatos = trim($datos['condicion_muestreo_datos'] ?? 'Muestra tomada y entregada por el cliente');
            
            $muestrasJson = $datos['identificacion_muestras_json'] ?? '[]';
            
            // 3.1 MUESTRA DE CONCRETO, ADOQUINES, BLOQUES
            $reqResistenciaConcreto = (int)($datos['req_resistencia_concreto'] ?? 0);
            $reqResistenciaAdoquin = (int)($datos['req_resistencia_adoquin'] ?? 0);
            $reqResistenciaBloques = (int)($datos['req_resistencia_bloques'] ?? 0);
            $reqOtrosConcreto = trim($datos['req_otros_concreto'] ?? '');
            
            // 3.2 MUESTRAS DE SUELO
            $reqGranulometria = (int)($datos['req_granulometria'] ?? 0);
            $reqLimitesAtterberg = (int)($datos['req_limites_atterberg'] ?? 0);
            $reqHumedad = (int)($datos['req_humedad'] ?? 0);
            $reqResistenciaCorte = (int)($datos['req_resistencia_corte'] ?? 0);
            $reqClasificacionSucsHr = (int)($datos['req_clasificacion_sucs_hr'] ?? 0);
            $reqProctorSm = (int)($datos['req_proctor_sm'] ?? 0);
            $reqInfiltracion = (int)($datos['req_infiltracion'] ?? 0);
            $reqCbr = (int)($datos['req_cbr'] ?? 0);
            $reqDensidad = (int)($datos['req_densidad'] ?? 0);
            $reqOtrosSuelo = trim($datos['req_otros_suelo'] ?? '');
            
            // 3.3 OTROS MATERIALES
            $reqOtrosMateriales = (int)($datos['req_otros_materiales'] ?? 0);
            $descripcionOtrosAnalisis = trim($datos['descripcion_otros_analisis'] ?? '');
            
            // Campos Finales
            $analisisAdicionales = trim($datos['analisis_adicionales'] ?? '');
            $observaciones = trim($datos['observaciones'] ?? '');
            $nombreRecibeCycsa = trim($datos['nombre_recibe_cycsa'] ?? '');
            $firmaRecibeCycsa = (int)($datos['firma_recibe_cycsa'] ?? 0);
            $firmaCliente = (int)($datos['firma_cliente'] ?? 0);

            // Check if exists
            $existing = $this->obtenerHojaSolicitudPorOS($idOS);
            
            $lockAcquired = false;
            if (!$existing) {
                // Adquirir candado para insertar nueva hoja de solicitud
                $this->db->prepare("SELECT GET_LOCK('lock_hojas_solicitud', 10)")->execute();
                $lockAcquired = true;
                
                $anioActual = (int)date('Y');
                $siguienteConsecutivo = $this->obtenerSiguienteConsecutivoMuestra($anioActual);
                
                $muestras = json_decode($muestrasJson, true) ?: [];
                foreach ($muestras as &$m) {
                    $nombre = trim($m['nombre_muestra'] ?? '');
                    if (empty($nombre) || preg_match('/^MC-\d+-\d+$/', $nombre) || strpos($nombre, 'Muestra') === 0) {
                        $m['nombre_muestra'] = 'MC-' . sprintf("%03d", $siguienteConsecutivo) . '-' . $anioActual;
                        $siguienteConsecutivo++;
                    }
                }
                unset($m);
                $muestrasJson = json_encode($muestras);
            }

            if ($existing) {
                $sql = "UPDATE hojas_solicitud SET 
                            fecha_hora_llegada_laboratorio = :f_llegada,
                            codigo_documento = :cod_doc,
                            nombre_empresa_o_cliente = :n_empresa,
                            direccion_proyecto = :dir,
                            telefono = :tel,
                            correo_electronico = :email,
                            nombre_persona_entrega_muestra = :p_entrega,
                            naturaleza_muestra = :naturaleza,
                            procedencia_punto_muestreo = :proc,
                            nombre_persona_toma_muestra = :p_toma,
                            fecha_hora_toma_muestra = :f_toma,
                            condicion_muestreo_datos = :cond_m,
                            muestras_json = :m_json,
                            req_resistencia_concreto = :rc_con,
                            req_resistencia_adoquin = :rc_ado,
                            req_resistencia_bloques = :rc_blo,
                            req_otros_concreto = :rc_ot,
                            req_granulometria = :rg,
                            req_limites_atterberg = :rl,
                            req_humedad = :rh,
                            req_resistencia_corte = :rs,
                            req_clasificacion_sucs_hr = :rc_sucs,
                            req_proctor_sm = :rp,
                            req_infiltracion = :ri,
                            req_cbr = :rcbr,
                            req_densidad = :rd,
                            req_otros_suelo = :rs_ot,
                            req_otros_materiales = :ro_mat,
                            descripcion_otros_analisis = :ro_desc,
                            analisis_adicionales = :anal_ad,
                            observaciones = :obs,
                            nombre_recibe_cycsa = :n_recibe,
                            firma_recibe_cycsa = :f_recibe,
                            firma_cliente = :f_cliente
                        WHERE id_os = :id_os";
            } else {
                $sql = "INSERT INTO hojas_solicitud (
                            id_os, fecha_hora_llegada_laboratorio, codigo_documento, nombre_empresa_o_cliente, direccion_proyecto, telefono, correo_electronico, nombre_persona_entrega_muestra,
                            naturaleza_muestra, procedencia_punto_muestreo, nombre_persona_toma_muestra, fecha_hora_toma_muestra, condicion_muestreo_datos, muestras_json,
                            req_resistencia_concreto, req_resistencia_adoquin, req_resistencia_bloques, req_otros_concreto,
                            req_granulometria, req_limites_atterberg, req_humedad, req_resistencia_corte, req_clasificacion_sucs_hr, req_proctor_sm, req_infiltracion, req_cbr, req_densidad, req_otros_suelo,
                            req_otros_materiales, descripcion_otros_analisis,
                            analisis_adicionales, observaciones, nombre_recibe_cycsa, firma_recibe_cycsa, firma_cliente
                        ) VALUES (
                            :id_os, :f_llegada, :cod_doc, :n_empresa, :dir, :tel, :email, :p_entrega,
                            :naturaleza, :proc, :p_toma, :f_toma, :cond_m, :m_json,
                            :rc_con, :rc_ado, :rc_blo, :rc_ot,
                            :rg, :rl, :rh, :rs, :rc_sucs, :rp, :ri, :rcbr, :rd, :rs_ot,
                            :ro_mat, :ro_desc,
                            :anal_ad, :obs, :n_recibe, :f_recibe, :f_cliente
                        )";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_os' => $idOS,
                'f_llegada' => $fechaHoraLlegada,
                'cod_doc' => $codigoDoc,
                'n_empresa' => $nombreEmpresa,
                'dir' => $direccionProj,
                'tel' => $telefono,
                'email' => $email,
                'p_entrega' => $personaEntrega,
                'naturaleza' => $naturaleza,
                'proc' => $procedencia,
                'p_toma' => $personaToma,
                'f_toma' => $fechaHoraToma,
                'cond_m' => $condicionMuestreoDatos,
                'm_json' => $muestrasJson,
                'rc_con' => $reqResistenciaConcreto,
                'rc_ado' => $reqResistenciaAdoquin,
                'rc_blo' => $reqResistenciaBloques,
                'rc_ot' => $reqOtrosConcreto,
                'rg' => $reqGranulometria,
                'rl' => $reqLimitesAtterberg,
                'rh' => $reqHumedad,
                'rs' => $reqResistenciaCorte,
                'rc_sucs' => $reqClasificacionSucsHr,
                'rp' => $reqProctorSm,
                'ri' => $reqInfiltracion,
                'rcbr' => $reqCbr,
                'rd' => $reqDensidad,
                'rs_ot' => $reqOtrosSuelo,
                'ro_mat' => $reqOtrosMateriales,
                'ro_desc' => $descripcionOtrosAnalisis,
                'anal_ad' => $analisisAdicionales,
                'obs' => $observaciones,
                'n_recibe' => $nombreRecibeCycsa,
                'f_recibe' => $firmaRecibeCycsa,
                'f_cliente' => $firmaCliente
            ]);

            if ($lockAcquired) {
                $this->db->prepare("SELECT RELEASE_LOCK('lock_hojas_solicitud')")->execute();
            }
            return true;
        } catch (Exception $e) {
            if (isset($lockAcquired) && $lockAcquired) {
                try {
                    $this->db->prepare("SELECT RELEASE_LOCK('lock_hojas_solicitud')")->execute();
                } catch (Exception $lex) {}
            }
            error_log("Error en guardarHojaSolicitud: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el siguiente número correlativo para la hoja de solicitud CYCSA-RT-FM-13 de una cotización.
     */
    public function obtenerSiguienteNumeroHojaSolicitud(int $idCotizacion): int {
        $sql = "SELECT COUNT(*) FROM hojas_solicitud hs
                JOIN ordenes_servicio os ON hs.id_os = os.id
                WHERE os.id_cotizacion = :id_cot";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cot' => $idCotizacion]);
        return (int)$stmt->fetchColumn() + 1;
    }

    public function obtenerTecnicosActivos(): array {
        return $this->db->query("SELECT * FROM tecnicos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVehiculosActivos(): array {
        return $this->db->query("SELECT * FROM vehiculos WHERE activo = 1 ORDER BY placa ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el siguiente consecutivo para el nombre de muestra (MC-XXX-AÑO) en un año determinado.
     */
    public function obtenerSiguienteConsecutivoMuestra(int $anio): int {
        $sql = "SELECT muestras_json FROM hojas_solicitud WHERE YEAR(fecha_creacion) = :anio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => $anio]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $maxConsecutivo = 0;
        foreach ($rows as $row) {
            $arr = json_decode($row, true);
            if (is_array($arr)) {
                foreach ($arr as $item) {
                    $nombre = $item['nombre_muestra'] ?? '';
                    if (preg_match('/MC-(\d+)-' . $anio . '/', $nombre, $matches)) {
                        $num = (int)$matches[1];
                        if ($num > $maxConsecutivo) {
                            $maxConsecutivo = $num;
                        }
                    }
                }
            }
        }
        return $maxConsecutivo + 1;
    }
}

