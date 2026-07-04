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
        $sql = "SELECT os.id, os.codigo_os, os.tipo_contrato, os.fecha_emision, os.estado,
                       cot.codigo AS cot_codigo, cot.nombre_proyecto, cli.nombre_razon_social AS cliente_nombre
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
     * Obtiene los productos/ensayos de una O/S y su estado de recepción (código MS y id_lote si existe).
     */
    public function obtenerItemsOS(int $idOS): array {
        $sql = "SELECT cd.id AS id_detalle, cd.descripcion_ensayo, cd.codigo_servicio,
                       (SELECT rm.codigo_muestra 
                        FROM ensayo_edades ee 
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        WHERE ee.id_detalle_cotizacion = cd.id LIMIT 1) AS codigo_muestra,
                       (SELECT lm.id 
                        FROM ensayo_edades ee 
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        WHERE ee.id_detalle_cotizacion = cd.id LIMIT 1) AS id_lote
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
                       cot.codigo AS cot_codigo, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                       cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono
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
            
            $anio = (int)date('Y', strtotime($fechaRecepcion));

            // Calcular correlativo secuencial anual (1 a 1000)
            $stmtSec = $this->db->prepare("SELECT MAX(correlativo_anual) FROM recepcion_muestras WHERE anio = :anio");
            $stmtSec->execute(['anio' => $anio]);
            $correlativo = (int)$stmtSec->fetchColumn() + 1;
            
            // Límite de control
            if ($correlativo > 1000) {
                // Si pasa de 1000 anual por alta demanda, igual lo guardamos pero lo logueamos
                error_log("Alerta LIMS: Correlativo de muestra excede de 1000 en el año $anio");
            }

            $anioShort = date('y', strtotime($fechaRecepcion));
            $codigoMuestra = sprintf("MS-%04d-%02d", $correlativo, $anioShort);
            
            // Si no se provee código de campo o si se pide auto-generado, lo generamos en formato MC-1000-26
            if (empty($codigoCampo)) {
                $codigoCampo = sprintf("MC-%04d-%02d", $correlativo, $anioShort);
            }

            // 1. Insertar Recepción
            $sqlRec = "INSERT INTO recepcion_muestras (id_os, correlativo_anual, anio, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, entregado_por, observaciones, estado)
                       VALUES (:id_os, :correlativo_anual, :anio, :codigo_muestra, :codigo_campo, :fecha_recepcion, :recibido_por, :entregado_por, :observaciones, 'Registrado')";
            $stmtRec = $this->db->prepare($sqlRec);
            $stmtRec->execute([
                'id_os' => $idOS,
                'correlativo_anual' => $correlativo,
                'anio' => $anio,
                'codigo_muestra' => $codigoMuestra,
                'codigo_campo' => $codigoCampo,
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

            // Actualizar estado operativo de la O/S
            $this->db->prepare("UPDATE ordenes_servicio SET estado = 'En Proceso' WHERE id = :id_os")
                     ->execute(['id_os' => $idOS]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
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
                       'ruptura' AS tipo_evento
                FROM ensayo_edades ee
                JOIN lotes_muestras lm ON ee.id_lote = lm.id
                JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                WHERE ee.fecha_programada BETWEEN :inicio AND :fin
                ORDER BY ee.fecha_programada ASC, rm.codigo_muestra ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
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
                       ee.cumple_norma, ee.estado, p.porcentaje_minimo_esperado
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
    public function guardarResultadoRuptura(int $idEnsayo, array $datos): bool {
        try {
            $cargaLbs = (float)$datos['carga_lbs'];
            $areaIn2 = (float)$datos['area_in2'];
            
            // Resistencia PSI = Carga / Área
            $resistenciaPsi = $areaIn2 > 0 ? $cargaLbs / $areaIn2 : 0;
            
            // Resistencia Kg/cm² = PSI * 0.070307
            $resistenciaKg = $resistenciaPsi * 0.070307;

            // Obtener el lote para saber la resistencia de diseño objetivo
            $stmtLote = $this->db->prepare("SELECT lm.id, lm.diseno_resistencia, ee.id_detalle_cotizacion, ee.edad_dias
                                            FROM ensayo_edades ee
                                            JOIN lotes_muestras lm ON ee.id_lote = lm.id
                                            WHERE ee.id = :id");
            $stmtLote->execute(['id' => $idEnsayo]);
            $info = $stmtLote->fetch(PDO::FETCH_ASSOC);
            
            $disenoPsi = 0.0;
            // Parsear diseño numérico (ej: "3000 PSI" -> 3000)
            if (preg_match('/(\d+)\s*(PSI|psi|lb)/', $info['diseno_resistencia'] ?? '', $matches)) {
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
                'edad' => $info['edad_dias']
            ]);
            $pctMinimo = $stmtParam->fetchColumn();
            
            $cumpleNorma = 1;
            if ($pctMinimo !== false && $porcentaje < (float)$pctMinimo) {
                $cumpleNorma = 0; // Levanta alerta visual / No cumple
            }

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
            return $stmt->execute([
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

        } catch (Exception $e) {
            error_log("Error al guardar ruptura LIMS: " . $e->getMessage());
            return false;
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
    public function registrarInforme(int $idLote, string $codigoInforme, int $version, string $codigoCompleto, string $tipoInforme, string $motivoReemplazo, string $rutaPdf): ?int {
        try {
            $sql = "INSERT INTO informes_control (id_lote, codigo_informe, version, codigo_completo, tipo_informe, estado_aprobacion, motivo_reemplazo, ruta_archivo_pdf)
                    VALUES (:id_lote, :codigo_informe, :version, :codigo_completo, :tipo_informe, 'Pendiente', :motivo_reemplazo, :ruta_pdf)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_lote' => $idLote,
                'codigo_informe' => $codigoInforme,
                'version' => $version,
                'codigo_completo' => $codigoCompleto,
                'tipo_informe' => $tipoInforme,
                'motivo_reemplazo' => $motivoReemplazo,
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
}
