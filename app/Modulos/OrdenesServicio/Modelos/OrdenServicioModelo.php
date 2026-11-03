<?php

namespace Cycsa\Modulos\OrdenesServicio\Modelos;

use Cycsa\Nucleo\Conexion;
use PDO;
use Exception;

class OrdenServicioModelo {

    private PDO $db;
    private ?string $ultimoError = null;

    public function obtenerUltimoError(): ?string {
        return $this->ultimoError;
    }

    public function __construct() {
        $this->db = Conexion::obtenerInstancia();
    }

    /**
     * Genera un código correlativo único para la Orden de Servicio
     * Formato: YYYYXXX-ORDEN DE SERVICIO (Ej: 2026001-ORDEN DE SERVICIO)
     */
    public function generarCodigoOS(): string {
        $anio = date('Y');
        $sql = "SELECT COUNT(*) FROM ordenes_servicio WHERE codigo_os LIKE :anio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => $anio . '%']);
        $count = $stmt->fetchColumn() + 1;
        
        $correlativo = str_pad($count, 3, '0', STR_PAD_LEFT);
        return "{$anio}{$correlativo}-ORDEN DE SERVICIO";
    }

    /**
     * Obtiene todas las órdenes de servicio con datos de cotización y cliente
     */
    public function obtenerTodas(string $busqueda = '', string $tab = 'activas', array $filtros = []): array {
        $sql = "SELECT os.*, 
                       cot.codigo AS cotizacion_codigo, 
                       cli.nombre_razon_social AS cliente_nombre,
                       cli_inf.nombre_razon_social AS cliente_informe_nombre,
                       cli_inf.nombre_razon_social AS cliente_informe,
                       cli_fac.nombre_razon_social AS cliente_factura_nombre,
                       cli_fac.nombre_razon_social AS cliente_factura,
                       COALESCE(NULLIF(os.atencion_a, ''), NULLIF(cot.atencion_a, ''), NULLIF(cli.contacto_nombre, ''), NULLIF(cli.contacto, ''), '') AS atencion_a,
                       COALESCE(NULLIF(os.nombre_proyecto, ''), NULLIF(cot.nombre_proyecto, ''), '') AS nombre_proyecto,
                       COALESCE(NULLIF(cot.direccion_proyecto, ''), NULLIF(cli.direccion, ''), '') AS direccion_proyecto,
                       COALESCE(NULLIF(os.forma_pago, ''), NULLIF(cot.condicion_pago, ''), 'Pago contra entrega') AS forma_pago,
                       COALESCE(cli.identificacion, cli.numero_ruc, cli.numero_cedula, '') AS cliente_rfc,
                       COALESCE(NULLIF(cli.direccion, ''), NULLIF(cot.direccion_proyecto, ''), '') AS cliente_direccion,
                       COALESCE(NULLIF(cli.telefono, ''), '') AS cliente_telefono,
                       COALESCE(NULLIF(cli.email, ''), NULLIF(cli.contacto_correo, ''), '') AS cliente_email,
                       pm.id AS id_pm, pm.fecha_ida, pm.fecha_llegada, pm.estado_muestreo, pm.observaciones_campo,
                       pm.lugar_muestreo, pm.cantidad_muestras_est, pm.num_muestreadores, pm.fecha_finalizacion,
                       t.nombre AS tecnico_nombre,
                       v.vehiculo_info,
                       hs.id AS id_hoja, hs.codigo_documento AS hoja_codigo, hs.muestras_json,
                       hs.nombre_persona_toma_muestra, hs.fecha_hora_toma_muestra
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = COALESCE(os.id_cliente_informe, cot.id_cliente_informe, cot.id_cliente)
                LEFT JOIN clientes cli_fac ON cli_fac.id = COALESCE(os.id_cliente_factura, cot.id_cliente_factura, cot.id_cliente)
                LEFT JOIN programacion_muestreo pm ON pm.id_orden_servicio = os.id
                LEFT JOIN tecnicos t ON pm.id_tecnico = t.id
                LEFT JOIN (SELECT id, CONCAT(marca, ' ', modelo, ' (', placa, ')') AS vehiculo_info FROM vehiculos) v ON pm.id_vehiculo = v.id
                LEFT JOIN (
                    SELECT hs1.* FROM hojas_solicitud hs1
                    INNER JOIN (SELECT id_os, MAX(id) AS max_id FROM hojas_solicitud GROUP BY id_os) hs2 ON hs1.id = hs2.max_id
                ) hs ON hs.id_os = os.id";
        
        $where = [];
        $params = [];

        // Filtro por Tab
        $tab = strtolower(trim($tab));
        if ($tab === 'historico' || $tab === 'archivo') {
            $where[] = "os.estado IN ('Finalizado', 'Archivado', 'Cerrado')";
        } elseif ($tab === 'muestreo') {
            $where[] = "(os.estado NOT IN ('Finalizado', 'Archivado', 'Cerrado') OR os.estado IS NULL)";
            $where[] = "(os.requiere_muestreo = 1 OR os.requiere_muestreo = '1') AND (os.tecnico_muestreo IS NULL OR os.tecnico_muestreo = '' OR os.fecha_muestreo IS NULL OR pm.estado_muestreo IN ('Programado', 'En Proceso', 'En Campo') OR pm.id IS NULL)";
        } elseif ($tab === 'todas') {
            // Sin filtro de tab
        } else {
            // Default: 'activas'
            $where[] = "(os.estado NOT IN ('Finalizado', 'Archivado', 'Cerrado') OR os.estado IS NULL)";
        }

        // Filtro por Búsqueda (con paréntesis en los ORs)
        if ($busqueda !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cot.codigo LIKE :q2 OR cli.nombre_razon_social LIKE :q3 OR os.nombre_proyecto LIKE :q4)";
            $term = '%' . trim($busqueda) . '%';
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
        }

        // Filtros adicionales: estado exacto
        if (!empty($filtros['estado'])) {
            $where[] = "os.estado = :f_estado";
            $params['f_estado'] = $filtros['estado'];
        }

        // Filtros adicionales: desde / hasta (fecha_emision)
        if (!empty($filtros['desde'])) {
            $where[] = "os.fecha_emision >= :f_desde";
            $params['f_desde'] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[] = "os.fecha_emision <= :f_hasta";
            $params['f_hasta'] = $filtros['hasta'] . ' 23:59:59';
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY os.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ordenes as &$osItem) {
            $stmtDet = $this->db->prepare("
                SELECT cd.*, p.formato_id, fe.nombre AS formato_nombre, fe.archivo_markdown
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                WHERE cd.id_cotizacion = :id_cot
            ");
            $stmtDet->execute(['id_cot' => $osItem['id_cotizacion']]);
            $osItem['items'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            // Cargar todas las Hojas de Solicitud de la O/S (Soporte Contratos Activos y Múltiples RT-FM-13)
            $stmtHojas = $this->db->prepare("
                SELECT hs.id, hs.id_detalle_cotizacion, hs.id_producto, hs.ensayo_id, hs.codigo_documento, hs.numero_registro,
                       hs.fecha_creacion, hs.fecha_hora_toma_muestra, hs.fecha_hora_llegada_laboratorio,
                       hs.nombre_persona_toma_muestra, hs.nombre_persona_entrega_muestra, hs.muestras_json, hs.procedencia_punto_muestreo,
                       cd.descripcion_ensayo,
                       COALESCE(cd.codigo_servicio, p.codigo_servicio, '') AS codigo_servicio,
                       COALESCE(p.nombre_comercial, '') AS nombre_comercial
                FROM hojas_solicitud hs
                LEFT JOIN cotizacion_detalles cd ON hs.id_detalle_cotizacion = cd.id
                LEFT JOIN productos p ON (hs.id_producto = p.id OR cd.id_producto = p.id)
                WHERE hs.id_os = :id_os 
                ORDER BY hs.id ASC
            ");
            $stmtHojas->execute(['id_os' => $osItem['id']]);
            $hojasList = $stmtHojas->fetchAll(PDO::FETCH_ASSOC);
            foreach ($hojasList as &$hItem) {
                $mArr = json_decode($hItem['muestras_json'] ?? '[]', true) ?: [];
                $hItem['total_muestras'] = count($mArr);
            }
            unset($hItem);

            $osItem['hojas_servicio'] = $hojasList;
            $osItem['total_hojas'] = count($hojasList);
        }
        return $ordenes;
    }

    /**
     * Obtiene una Orden de Servicio por su ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT os.*, 
                       cot.codigo AS cotizacion_codigo, 
                       cot.version AS cotizacion_version,
                       u_cot.nombre AS cotizacion_creador_nombre,
                       COALESCE(NULLIF(os.elaborado_por, ''), u_cot.nombre, 'Personal Autorizado') AS elaborado_por,
                       cli.nombre_razon_social AS cliente_nombre,
                       cli_inf.nombre_razon_social AS cliente_informe_nombre,
                       cli_fac.nombre_razon_social AS cliente_factura_nombre,
                       COALESCE(NULLIF(os.atencion_a, ''), NULLIF(cot.atencion_a, ''), NULLIF(cli.contacto_nombre, ''), NULLIF(cli.contacto, ''), '') AS atencion_a,
                       COALESCE(NULLIF(os.nombre_proyecto, ''), NULLIF(cot.nombre_proyecto, ''), '') AS nombre_proyecto,
                       COALESCE(NULLIF(cot.direccion_proyecto, ''), NULLIF(cli.direccion, ''), '') AS direccion_proyecto,
                       COALESCE(NULLIF(os.forma_pago, ''), NULLIF(cot.condicion_pago, ''), 'Pago contra entrega') AS forma_pago,
                       COALESCE(cli.identificacion, cli.numero_ruc, cli.numero_cedula, '') AS cliente_rfc,
                       COALESCE(NULLIF(cli.direccion, ''), NULLIF(cot.direccion_proyecto, ''), '') AS cliente_direccion,
                       COALESCE(NULLIF(cli.telefono, ''), '') AS cliente_telefono,
                       COALESCE(NULLIF(cli.email, ''), NULLIF(cli.contacto_correo, ''), '') AS cliente_email
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                LEFT JOIN usuarios u_cot ON cot.id_usuario_creador = u_cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = COALESCE(os.id_cliente_informe, cot.id_cliente_informe, cot.id_cliente)
                LEFT JOIN clientes cli_fac ON cli_fac.id = COALESCE(os.id_cliente_factura, cot.id_cliente_factura, cot.id_cliente)
                WHERE os.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $os = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$os) {
            return null;
        }

        // Obtener programación de muestreo si existe
        $sqlPm = "SELECT pm.*, t.nombre AS tecnico_nombre,
                         v.marca, v.modelo, v.placa
                  FROM programacion_muestreo pm
                  LEFT JOIN tecnicos t ON pm.id_tecnico = t.id
                  LEFT JOIN vehiculos v ON pm.id_vehiculo = v.id
                  WHERE pm.id_orden_servicio = :id_os";
        $stmtPm = $this->db->prepare($sqlPm);
        $stmtPm->execute(['id_os' => $id]);
        $os['programacion_muestreo'] = $stmtPm->fetch(PDO::FETCH_ASSOC) ?: null;

        // Obtener detalles de ensayos heredados de la cotización
        $sqlDet = "SELECT cd.*, 
                          COALESCE(cd.descripcion_ensayo, p.ensayo_servicio, p.nombre_comercial, '') AS nombre_ensayo, 
                          COALESCE(cd.codigo_servicio, p.codigo_servicio, '') AS codigo_servicio, 
                          COALESCE(cd.norma_astm, p.norma_astm, '') AS norma_astm,
                          COALESCE(cd.condiciones_muestra, p.condiciones_muestra, '') AS condiciones_muestra,
                          COALESCE(cd.procedimiento, p.procedimiento_muestreo, '') AS procedimiento,
                          COALESCE(cd.unidad_medida, p.unidad_medida, 'Unidad') AS unidad_medida,
                          COALESCE(p.codigo_hoja_campo, '') AS codigo_hoja_campo
                   FROM cotizacion_detalles cd
                   LEFT JOIN productos p ON cd.id_producto = p.id
                   WHERE cd.id_cotizacion = :id_cotizacion";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute(['id_cotizacion' => $os['id_cotizacion']]);
        $ensayos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        $idsPermitidos = $this->obtenerIdsPermitidos((int)$os['id']);
        foreach ($ensayos as &$ensayo) {
            $permitido = $idsPermitidos === null || in_array((int)$ensayo['id'], $idsPermitidos, true);
            $ensayo['permitido'] = $permitido ? 1 : 0;
        }
        unset($ensayo);
        $os['ensayos'] = array_values(array_filter(
            $ensayos,
            static fn(array $ensayo): bool => (int)($ensayo['permitido'] ?? 1) === 1
        ));

        $trazas = $this->obtenerTrazabilidadMuestras((int)$os['id']);
        foreach ($os['ensayos'] as &$ensayo) {
            $idDetalle = (int)($ensayo['id'] ?? 0);
            $ensayo['codigos_laboratorio'] = $trazas['por_detalle'][$idDetalle] ?? [];
        }
        unset($ensayo);
        $os['codigos_muestra'] = $trazas['codigos'];
        $os['informes_vinculados'] = $this->obtenerInformesVinculados((int)$os['id']);

        return $os;
    }

    /**
     * Códigos de laboratorio (MS- y MC-) ya custodiados en recepción para la O/S.
     * La relación con el ensayo, cuando existe, sale de ensayo_edades.
     *
     * @return array{codigos: array<int, string>, por_detalle: array<int, array<int, string>>}
     */
    public function obtenerTrazabilidadMuestras(int $idOS): array {
        $vacio = ['codigos' => [], 'por_detalle' => []];
        if ($idOS <= 0 || !$this->tablaExiste('recepcion_muestras')) {
            return $vacio;
        }

        try {
            if ($this->tablaExiste('lotes_muestras') && $this->tablaExiste('ensayo_edades')) {
                $sql = "SELECT rm.codigo_muestra, ee.id_detalle_cotizacion
                        FROM recepcion_muestras rm
                        LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                        LEFT JOIN ensayo_edades ee ON ee.id_lote = lm.id
                        WHERE rm.id_os = :id_os
                        ORDER BY rm.id ASC, ee.id ASC";
            } else {
                $sql = "SELECT codigo_muestra, NULL AS id_detalle_cotizacion
                        FROM recepcion_muestras
                        WHERE id_os = :id_os
                        ORDER BY id ASC";
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id_os' => $idOS]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('OrdenServicioModelo::obtenerTrazabilidadMuestras: ' . $e->getMessage());
            return $vacio;
        }

        $codigos = [];
        $porDetalle = [];
        foreach ($filas as $fila) {
            $codigo = trim((string)($fila['codigo_muestra'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $codigos[$codigo] = $codigo;
            $idDetalle = (int)($fila['id_detalle_cotizacion'] ?? 0);
            if ($idDetalle > 0) {
                $porDetalle[$idDetalle][$codigo] = $codigo;
            }
        }

        $porDetalleLista = [];
        foreach ($porDetalle as $idDetalle => $lista) {
            $porDetalleLista[$idDetalle] = array_values($lista);
        }

        return [
            'codigos' => array_values($codigos),
            'por_detalle' => $porDetalleLista,
        ];
    }

    /**
     * Informes oficiales ligados a la O/S por la cadena recepción → lote → informe.
     * El enlace de PDF solo se arma cuando el informe está aprobado.
     *
     * @return array<int, array{id: int, codigo: string, estado: string, aprobado: int, url_pdf: string}>
     */
    public function obtenerInformesVinculados(int $idOS): array {
        if ($idOS <= 0) {
            return [];
        }

        $informes = [];
        $informes = array_merge($informes, $this->informesControlDeOS($idOS));
        $informes = array_merge($informes, $this->informesRoturaDeOS($idOS));
        return array_values($informes);
    }

    /**
     * @return array<int, array{id: int, codigo: string, estado: string, aprobado: int, url_pdf: string}>
     */
    private function informesControlDeOS(int $idOS): array {
        if (!$this->tablaExiste('informes_control') || !$this->tablaExiste('lotes_muestras') || !$this->tablaExiste('recepcion_muestras')) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT ic.id, ic.codigo_informe, ic.codigo_completo, ic.estado_aprobacion
                 FROM informes_control ic
                 INNER JOIN lotes_muestras lm ON ic.id_lote = lm.id
                 INNER JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                 WHERE rm.id_os = :id_os
                 ORDER BY ic.id ASC"
            );
            $stmt->execute(['id_os' => $idOS]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('OrdenServicioModelo::informesControlDeOS: ' . $e->getMessage());
            return [];
        }

        $salida = [];
        $vistos = [];
        foreach ($filas as $fila) {
            $id = (int)($fila['id'] ?? 0);
            if ($id <= 0 || isset($vistos[$id])) {
                continue;
            }
            $vistos[$id] = true;
            $codigo = trim((string)($fila['codigo_completo'] ?? ''));
            if ($codigo === '') {
                $codigo = trim((string)($fila['codigo_informe'] ?? ''));
            }
            $estado = trim((string)($fila['estado_aprobacion'] ?? ''));
            $aprobado = $this->estadoInformeAprobado($estado);
            $salida[] = [
                'id' => $id,
                'codigo' => $codigo,
                'estado' => $estado,
                'aprobado' => $aprobado ? 1 : 0,
                'url_pdf' => $aprobado ? '/Cycsa/publico/informes/descargar?id=' . $id : '',
            ];
        }
        return $salida;
    }

    /**
     * @return array<int, array{id: int, codigo: string, estado: string, aprobado: int, url_pdf: string}>
     */
    private function informesRoturaDeOS(int $idOS): array {
        if (!$this->tablaExiste('informes_rotura') || !$this->tablaExiste('recepcion_muestras')) {
            return [];
        }
        $columnas = $this->columnasDe('informes_rotura');
        if (!in_array('muestra_id', $columnas, true) || !in_array('estado', $columnas, true)) {
            return [];
        }
        $colCodigo = in_array('numero', $columnas, true) ? 'numero' : (in_array('codigo', $columnas, true) ? 'codigo' : '');
        if ($colCodigo === '') {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT ir.id, ir.`{$colCodigo}` AS codigo_informe, ir.estado
                 FROM informes_rotura ir
                 INNER JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                 WHERE rm.id_os = :id_os
                 ORDER BY ir.id ASC"
            );
            $stmt->execute(['id_os' => $idOS]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('OrdenServicioModelo::informesRoturaDeOS: ' . $e->getMessage());
            return [];
        }

        $salida = [];
        foreach ($filas as $fila) {
            $id = (int)($fila['id'] ?? 0);
            $codigo = trim((string)($fila['codigo_informe'] ?? ''));
            $estado = trim((string)($fila['estado'] ?? ''));
            if ($id <= 0 || $codigo === '') {
                continue;
            }
            $salida[] = [
                'id' => $id,
                'codigo' => $codigo,
                'estado' => $estado,
                'aprobado' => $this->estadoInformeAprobado($estado) ? 1 : 0,
                'url_pdf' => '',
            ];
        }
        return $salida;
    }

    private function estadoInformeAprobado(string $estado): bool {
        $normalizado = mb_strtolower(trim($estado), 'UTF-8');
        return $normalizado === 'aprobado' || $normalizado === 'approved';
    }

    private function tablaExiste(string $tabla): bool {
        if (!preg_match('/^[a-z0-9_]+$/', $tabla)) {
            return false;
        }
        $fila = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetch(PDO::FETCH_NUM);
        return (bool)$fila;
    }

    /**
     * @return array<int, string>
     */
    private function columnasDe(string $tabla): array {
        if (!$this->tablaExiste($tabla)) {
            return [];
        }
        $columnas = $this->db->query('SHOW COLUMNS FROM `' . $tabla . '`')->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $columnas);
    }

    /**
     * Crea la tabla de whitelist si aún no existe.
     * No debe ejecutarse dentro de una transacción abierta cuando la tabla falta:
     * el DDL de MySQL confirma la transacción de forma implícita.
     */
    public function asegurarTablaEnsayosPermitidos(): void {
        if ($this->tablaEnsayosPermitidosDisponible()) {
            return;
        }
        $ruta = dirname(__DIR__, 4) . '/database/migrations/025_orden_servicio_ensayos_permitidos.php';
        if (!file_exists($ruta)) {
            return;
        }
        require_once $ruta;
        if (!function_exists('ejecutarMigracion025')) {
            return;
        }
        ob_start();
        try {
            ejecutarMigracion025($this->db);
        } finally {
            ob_end_clean();
        }
    }

    public function tablaEnsayosPermitidosDisponible(): bool {
        $fila = $this->db->query("SHOW TABLES LIKE 'orden_servicio_ensayos_permitidos'")->fetch(PDO::FETCH_NUM);
        return (bool)$fila;
    }

    /**
     * Inserta la whitelist fija de la O/S. No borra filas previas ni toca cotizacion_detalles.
     *
     * @param array<int|string> $idsDetalle
     */
    public function guardarEnsayosPermitidos(int $idOS, array $idsDetalle, ?int $creadoPor = null): bool {
        $this->asegurarTablaEnsayosPermitidos();
        if (!$this->tablaEnsayosPermitidosDisponible()) {
            throw new \RuntimeException('No fue posible preparar la whitelist de ensayos de la Orden de Servicio.');
        }

        $ids = $this->normalizarIdsDetalle($idsDetalle);
        if ($ids === []) {
            return false;
        }
        if (!$this->ensayosPertenecenACotizacionDeOS($idOS, $ids)) {
            throw new \InvalidArgumentException('Uno o más ensayos no pertenecen a la cotización de esta Orden de Servicio.');
        }

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle, creado_por, creado_en)
             VALUES (:id_os, :id_detalle, :creado_por, NOW())"
        );
        $creado = ($creadoPor !== null && $creadoPor > 0) ? $creadoPor : null;
        foreach ($ids as $idDetalle) {
            $stmt->execute([
                'id_os' => $idOS,
                'id_detalle' => $idDetalle,
                'creado_por' => $creado,
            ]);
        }
        return true;
    }

    /**
     * IDs de cotizacion_detalles habilitados.
     * null = legado / todos permitidos (la O/S no tiene filas de whitelist).
     *
     * @return array<int>|null
     */
    public function obtenerIdsPermitidos(int $idOS): ?array {
        if ($idOS <= 0 || !$this->tablaEnsayosPermitidosDisponible()) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT id_cotizacion_detalle
             FROM orden_servicio_ensayos_permitidos
             WHERE id_os = :id_os
             ORDER BY id_cotizacion_detalle ASC"
        );
        $stmt->execute(['id_os' => $idOS]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        return $ids === [] ? null : $ids;
    }

    public function esEnsayoPermitido(int $idOS, int $idDetalle): bool {
        $ids = $this->obtenerIdsPermitidos($idOS);
        if ($ids === null) {
            return true;
        }
        return in_array($idDetalle, $ids, true);
    }

    /**
     * @param array<int|string> $idsDetalle
     */
    public function ensayosPertenecenACotizacion(int $idCotizacion, array $idsDetalle): bool {
        $ids = $this->normalizarIdsDetalle($idsDetalle);
        if ($ids === [] || $idCotizacion <= 0) {
            return false;
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM cotizacion_detalles WHERE id_cotizacion = ? AND id IN ($marcas)"
        );
        $stmt->execute(array_merge([$idCotizacion], $ids));
        return (int)$stmt->fetchColumn() === count($ids);
    }

    /**
     * @param array<int|string> $idsDetalle
     * @return array<int>
     */
    private function normalizarIdsDetalle(array $idsDetalle): array {
        $ids = [];
        foreach ($idsDetalle as $idDetalle) {
            $idDetalle = (int)$idDetalle;
            if ($idDetalle > 0) {
                $ids[$idDetalle] = $idDetalle;
            }
        }
        $lista = array_values($ids);
        sort($lista, SORT_NUMERIC);
        return $lista;
    }

    /**
     * @param array<int> $idsDetalle
     */
    private function ensayosPertenecenACotizacionDeOS(int $idOS, array $idsDetalle): bool {
        $stmt = $this->db->prepare("SELECT id_cotizacion FROM ordenes_servicio WHERE id = :id");
        $stmt->execute(['id' => $idOS]);
        $idCotizacion = (int)$stmt->fetchColumn();
        return $this->ensayosPertenecenACotizacion($idCotizacion, $idsDetalle);
    }

    /**
     * Guardar una nueva Orden de Servicio heredando datos de la cotización
     */
    public function crear(array $datos): int {
        $idClienteFactura = !empty($datos['id_cliente_factura']) ? (int)$datos['id_cliente_factura'] : (int)($datos['id_cliente'] ?? 0);
        $idClienteInforme = !empty($datos['id_cliente_informe']) ? (int)$datos['id_cliente_informe'] : (int)($datos['id_cliente'] ?? 0);
        $formaPago = trim($datos['forma_pago'] ?? ($datos['condicion_pago'] ?? ''));
        if ($formaPago === '') {
            $formaPago = (!empty($datos['condicion_comercial']) && $datos['condicion_comercial'] === 'credito') ? 'Crédito' : 'Pago contra entrega';
        }
        $condComercial = !empty($datos['condicion_comercial']) 
            ? trim($datos['condicion_comercial']) 
            : determinarCondicionComercial($formaPago);

        $esMultimes = isset($datos['es_multimes']) && ($datos['es_multimes'] === 1 || $datos['es_multimes'] === '1' || $datos['es_multimes'] === 'on') ? 1 : 0;
        $fechaInicio = !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null;
        $fechaFin = !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null;
        $periodicidad = !empty($datos['periodicidad']) ? trim($datos['periodicidad']) : 'Mensual';

        $sql = "INSERT INTO ordenes_servicio (
                    codigo_os, id_cotizacion, cotizacion_version, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, elaborado_por, fecha_emision,
                    atencion_a, nombre_proyecto, forma_pago, notas_condiciones,
                    contactos_json, requiere_muestreo, tipo_contrato, es_multimes, fecha_inicio, fecha_fin, periodicidad, estado
                ) VALUES (
                    :codigo_os, :id_cotizacion, :cotizacion_version, :id_cliente, :id_cliente_factura, :id_cliente_informe, :condicion_comercial, :elaborado_por, :fecha_emision,
                    :atencion_a, :nombre_proyecto, :forma_pago, :notas_condiciones,
                    :contactos_json, :requiere_muestreo, :tipo_contrato, :es_multimes, :fecha_inicio, :fecha_fin, :periodicidad, :estado
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'codigo_os' => $datos['codigo_os'],
            'id_cotizacion' => $datos['id_cotizacion'],
            'cotizacion_version' => !empty($datos['cotizacion_version']) ? (int)$datos['cotizacion_version'] : 1,
            'id_cliente' => $idClienteFactura,
            'id_cliente_factura' => $idClienteFactura,
            'id_cliente_informe' => $idClienteInforme,
            'condicion_comercial' => $condComercial,
            'elaborado_por' => $datos['elaborado_por'] ?? ($_SESSION['usuario_nombre'] ?? 'Sistema'),
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'atencion_a' => $datos['atencion_a'] ?? '',
            'nombre_proyecto' => $datos['nombre_proyecto'] ?? '',
            'forma_pago' => $formaPago,
            'notas_condiciones' => $datos['notas_condiciones'] ?? '',
            'contactos_json' => is_array($datos['contactos_json'] ?? null) ? json_encode($datos['contactos_json']) : ($datos['contactos_json'] ?? '[]'),
            'requiere_muestreo' => !empty($datos['requiere_muestreo']) ? 1 : 0,
            'tipo_contrato' => $datos['tipo_contrato'] ?? 'Puntual',
            'es_multimes' => $esMultimes,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'periodicidad' => $periodicidad,
            'estado' => $datos['estado'] ?? 'Borrador'
        ]);

        $idInsertado = (int)$this->db->lastInsertId();
        if ($idInsertado <= 0 && !empty($datos['codigo_os'])) {
            $stmtSelect = $this->db->prepare("SELECT id FROM ordenes_servicio WHERE codigo_os = :codigo LIMIT 1");
            $stmtSelect->execute(['codigo' => $datos['codigo_os']]);
            $idInsertado = (int)$stmtSelect->fetchColumn();
        }

        return $idInsertado;
    }

    /**
     * Actualiza los campos editables de una Orden de Servicio
     */
    public function actualizar(int $idOS, array $datos): bool {
        if ($idOS <= 0) {
            $this->ultimoError = 'ID de Orden de Servicio inválido.';
            return false;
        }

        $stmtActual = $this->db->prepare("SELECT id, estado, codigo_os FROM ordenes_servicio WHERE id = :id");
        $stmtActual->execute(['id' => $idOS]);
        $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

        if (!$actual) {
            $this->ultimoError = 'Orden de Servicio no encontrada.';
            return false;
        }

        $estadoActual = $actual['estado'] ?? '';
        if (in_array($estadoActual, ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            $this->ultimoError = 'La Orden de Servicio está cerrada o archivada y no admite modificaciones.';
            return false;
        }

        $idClienteFactura = !empty($datos['id_cliente_factura']) ? (int)$datos['id_cliente_factura'] : null;
        $idClienteInforme = !empty($datos['id_cliente_informe']) ? (int)$datos['id_cliente_informe'] : null;
        $formaPago = trim($datos['forma_pago'] ?? '');
        $condComercial = !empty($datos['condicion_comercial'])
            ? trim($datos['condicion_comercial'])
            : (function_exists('determinarCondicionComercial') ? determinarCondicionComercial($formaPago) : 'contado');

        $esMultimes = isset($datos['es_multimes']) && ($datos['es_multimes'] === 1 || $datos['es_multimes'] === '1' || $datos['es_multimes'] === 'on') ? 1 : 0;
        $fechaInicio = !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null;
        $fechaFin = !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null;
        $periodicidad = !empty($datos['periodicidad']) ? trim($datos['periodicidad']) : 'Mensual';

        // Regla de consistencia: Si el estado actual es 'Aprobada' y se actualiza la O/S, transicionar a 'Estado 2: Revision'
        $nuevoEstado = $estadoActual;
        $estadosAprobados = [
            'Aprobada',
            'Estado 3: Aprobada',
            'Estado 3: Ingreso Directo',
            'Estado 3A: Programacion Muestreo',
            'Estado 3B: Ejecucion Muestreo',
            'Estado 4: Ingreso Laboratorio',
            'Estado 5: Solicitud Tecnicos',
            'Estado 6: Ejecucion Ensayos'
        ];
        if (isset($datos['estado'])) {
            $nuevoEstado = trim($datos['estado']);
        } elseif (in_array($estadoActual, $estadosAprobados, true)) {
            $nuevoEstado = 'Estado 2: Revision';
        }

        $sql = "UPDATE ordenes_servicio SET 
                    atencion_a = :atencion_a,
                    nombre_proyecto = :nombre_proyecto,
                    forma_pago = :forma_pago,
                    condicion_comercial = :condicion_comercial,
                    id_cliente_factura = :id_cliente_factura,
                    id_cliente_informe = :id_cliente_informe,
                    notas_condiciones = :notas_condiciones,
                    contactos_json = :contactos_json,
                    tipo_contrato = :tipo_contrato,
                    es_multimes = :es_multimes,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    periodicidad = :periodicidad,
                    estado = :estado
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $idOS,
            'atencion_a' => $datos['atencion_a'] ?? '',
            'nombre_proyecto' => $datos['nombre_proyecto'] ?? '',
            'forma_pago' => $formaPago,
            'condicion_comercial' => $condComercial,
            'id_cliente_factura' => $idClienteFactura,
            'id_cliente_informe' => $idClienteInforme,
            'notas_condiciones' => $datos['notas_condiciones'] ?? '',
            'contactos_json' => is_array($datos['contactos_json'] ?? null) ? json_encode($datos['contactos_json']) : ($datos['contactos_json'] ?? '[]'),
            'tipo_contrato' => !empty($datos['tipo_contrato']) ? trim($datos['tipo_contrato']) : 'Puntual',
            'es_multimes' => $esMultimes,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'periodicidad' => $periodicidad,
            'estado' => $nuevoEstado
        ]);
    }


    /**
     * Registrar o actualizar la programación de muestreo
     */
    public function guardarProgramacionMuestreo(int $idOS, array $datos): bool {
        $sqlCheck = "SELECT id FROM programacion_muestreo WHERE id_orden_servicio = :id_os";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id_os' => $idOS]);
        $existente = $stmtCheck->fetchColumn();

        $estadoMuestreo = $datos['estado_muestreo'] ?? 'En Campo';
        if (!in_array($estadoMuestreo, ['Programado', 'En Campo', 'Finalizado'])) {
            $estadoMuestreo = 'En Campo';
        }
        $esFinalizado = ($estadoMuestreo === 'Finalizado');

        if ($existente) {
            $sql = "UPDATE programacion_muestreo SET
                        fecha_ida = :fecha_ida,
                        fecha_llegada = :fecha_llegada,
                        id_tecnico = :id_tecnico,
                        id_vehiculo = :id_vehiculo,
                        observaciones_campo = :observaciones_campo,
                        estado_muestreo = :estado_muestreo,
                        fecha_finalizacion = IF(:es_finalizado = 1, NOW(), fecha_finalizacion),
                        lugar_muestreo = :lugar_muestreo,
                        cantidad_muestras_est = :cantidad_muestras_est,
                        num_muestreadores = :num_muestreadores,
                        checklist_json = :checklist_json
                    WHERE id_orden_servicio = :id_os";
        } else {
            $sql = "INSERT INTO programacion_muestreo (
                        id_orden_servicio, fecha_ida, fecha_llegada, id_tecnico, id_vehiculo, 
                        observaciones_campo, estado_muestreo, fecha_finalizacion, lugar_muestreo, cantidad_muestras_est, 
                        num_muestreadores, checklist_json
                    ) VALUES (
                        :id_os, :fecha_ida, :fecha_llegada, :id_tecnico, :id_vehiculo, 
                        :observaciones_campo, :estado_muestreo, IF(:es_finalizado = 1, NOW(), NULL), :lugar_muestreo, :cantidad_muestras_est, 
                        :num_muestreadores, :checklist_json
                    )";
        }

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            'id_os' => $idOS,
            'fecha_ida' => $datos['fecha_ida'],
            'fecha_llegada' => $datos['fecha_llegada'],
            'id_tecnico' => $datos['id_tecnico'],
            'id_vehiculo' => $datos['id_vehiculo'],
            'observaciones_campo' => $datos['observaciones_campo'] ?? null,
            'estado_muestreo' => $estadoMuestreo,
            'es_finalizado' => $esFinalizado ? 1 : 0,
            'lugar_muestreo' => $datos['lugar_muestreo'] ?? null,
            'cantidad_muestras_est' => $datos['cantidad_muestras_est'] ?? null,
            'num_muestreadores' => (int)($datos['num_muestreadores'] ?? 1),
            'checklist_json' => $datos['checklist_json'] ?? null
        ]);

        // Actualizar estado de la Orden de Servicio (manteniendo la aprobación y avanzando en el ciclo)
        $estadoOS = $esFinalizado ? 'Estado 3B: Ejecucion Muestreo' : 'Estado 3A: Programacion Muestreo';
        $stmtState = $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = 1, estado = :estado WHERE id = :id");
        $stmtState->execute([
            'estado' => $estadoOS,
            'id' => $idOS
        ]);

        return $res;
    }

    /**
     * Marcar la orden como que requiere muestreo en campo
     */
    public function marcarRequiereMuestreo(int $idOS): bool {
        $stmt = $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = 1, estado = 'Estado 3A: Programacion Muestreo' WHERE id = :id");
        return $stmt->execute(['id' => $idOS]);
    }

    /**
     * Marcar la orden como ingreso directo (sin muestreo en campo)
     */
    public function establecerIngresoDirecto(int $idOS): bool {
        $stmt = $this->db->prepare("UPDATE ordenes_servicio SET requiere_muestreo = 0, estado = 'Estado 3: Ingreso Directo' WHERE id = :id");
        return $stmt->execute(['id' => $idOS]);
    }

    /**
     * Marcar el muestreo como finalizado (retorno de campo al laboratorio)
     */
    public function finalizarMuestreo(int $idOS): bool {
        // 1. Actualizar estado del muestreo (o insertar registro base si no existía)
        $sqlCheck = "SELECT id FROM programacion_muestreo WHERE id_orden_servicio = :id_os";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id_os' => $idOS]);
        $existente = $stmtCheck->fetchColumn();

        if ($existente) {
            $sqlPm = "UPDATE programacion_muestreo SET 
                        estado_muestreo = 'Finalizado', 
                        fecha_finalizacion = NOW() 
                      WHERE id_orden_servicio = :id_os";
            $stmtPm = $this->db->prepare($sqlPm);
            $stmtPm->execute(['id_os' => $idOS]);
        } else {
            $tecnicos = $this->obtenerTecnicos();
            $vehiculos = $this->obtenerVehiculos();
            $idTec = !empty($tecnicos) ? (int)$tecnicos[0]['id'] : 1;
            $idVeh = !empty($vehiculos) ? (int)$vehiculos[0]['id'] : 1;

            $sqlPm = "INSERT INTO programacion_muestreo (
                        id_orden_servicio, fecha_ida, fecha_llegada, id_tecnico, id_vehiculo, 
                        estado_muestreo, fecha_finalizacion
                      ) VALUES (
                        :id_os, NOW(), NOW(), :id_tecnico, :id_vehiculo, 
                        'Finalizado', NOW()
                      )";
            $stmtPm = $this->db->prepare($sqlPm);
            $stmtPm->execute([
                'id_os' => $idOS,
                'id_tecnico' => $idTec,
                'id_vehiculo' => $idVeh
            ]);
        }

        // 2. Cambiar el estado de la Orden de Servicio a 'Estado 3B: Ejecucion Muestreo' (para registrar Hoja RT-FM-13)
        $sqlOs = "UPDATE ordenes_servicio SET requiere_muestreo = 1, estado = 'Estado 3B: Ejecucion Muestreo' WHERE id = :id_os";
        $stmtOs = $this->db->prepare($sqlOs);
        return $stmtOs->execute(['id_os' => $idOS]);
    }

    /**
     * Obtener lista de técnicos activos
     */
    public function obtenerTecnicos(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM tecnicos WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener lista de vehículos activos
     */
    public function obtenerVehiculos(): array {
        $stmt = $this->db->query("SELECT id, marca, modelo, placa FROM vehiculos WHERE activo = 1 ORDER BY marca ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los conteos en tiempo real para las pestañas de Órdenes de Servicio.
     */
    public function obtenerConteosTabsOSLista(): array {
        $sql = "SELECT 
                    COUNT(DISTINCT CASE WHEN os.estado NOT IN ('Finalizado', 'Archivado', 'Cerrado') OR os.estado IS NULL THEN os.id END) AS activas,
                    COUNT(DISTINCT CASE WHEN (os.estado NOT IN ('Finalizado', 'Archivado', 'Cerrado') OR os.estado IS NULL) 
                                AND (os.requiere_muestreo = 1 OR os.requiere_muestreo = '1') 
                                AND (os.tecnico_muestreo IS NULL OR os.tecnico_muestreo = '' OR os.fecha_muestreo IS NULL OR pm.estado_muestreo IN ('Programado', 'En Proceso', 'En Campo') OR pm.id IS NULL) THEN os.id END) AS muestreo,
                    COUNT(DISTINCT CASE WHEN os.estado IN ('Finalizado', 'Archivado', 'Cerrado') THEN os.id END) AS historico,
                    COUNT(DISTINCT os.id) AS todas
                FROM ordenes_servicio os
                LEFT JOIN programacion_muestreo pm ON pm.id_orden_servicio = os.id";
        try {
            $stmt = $this->db->query($sql);
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            return [
                'activas' => (int)($res['activas'] ?? 0),
                'muestreo' => (int)($res['muestreo'] ?? 0),
                'historico' => (int)($res['historico'] ?? 0),
                'todas' => (int)($res['todas'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['activas' => 0, 'muestreo' => 0, 'historico' => 0, 'todas' => 0];
        }
    }

    /**
     * Determina a qué pestaña corresponde una O/S por su ID para auto-apertura.
     */
    public function determinarTabDeOS(int $idOS): string {
        try {
            $stmt = $this->db->prepare("SELECT estado, requiere_muestreo, tecnico_muestreo, fecha_muestreo FROM ordenes_servicio WHERE id = :id");
            $stmt->execute(['id' => $idOS]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                return 'activas';
            }
            $estado = $row['estado'] ?? '';
            if (in_array($estado, ['Finalizado', 'Archivado', 'Cerrado'], true)) {
                return 'historico';
            }
            if (($row['requiere_muestreo'] == 1) && (empty($row['tecnico_muestreo']) || empty($row['fecha_muestreo']))) {
                return 'muestreo';
            }
            return 'activas';
        } catch (\Throwable $e) {
            return 'activas';
        }
    }
}
