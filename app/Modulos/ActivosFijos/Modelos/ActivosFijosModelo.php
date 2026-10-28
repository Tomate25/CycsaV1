<?php

namespace Cycsa\Modulos\ActivosFijos\Modelos;

use Cycsa\Nucleo\ModeloBase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use PDO;
use Exception;
use Throwable;

/**
 * Modelo de Gestión de Activos Fijos y Depreciación Mensual Automatizada (TAREA-051).
 * Implementa cálculo en línea recta, control de valor en libros y generación de asientos contables en partida doble.
 */
class ActivosFijosModelo extends ModeloBase {

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // =========================================================================
    // 1. CÁLCULOS MATEMÁTICOS Y ENRIQUECIMIENTO DE MÉTRICAS
    // =========================================================================

    /**
     * Calcula la cuota mensual en línea recta.
     * Fórmula: (Valor de Adquisición - Valor Residual) / Vida Útil en Meses
     */
    public function calcularCuotaMensual(float $valorAdquisicion, float $valorResidual, int $vidaUtilMeses): float {
        if ($vidaUtilMeses <= 0) {
            return 0.00;
        }
        $baseDepreciable = max(0.00, $valorAdquisicion - $valorResidual);
        return round($baseDepreciable / $vidaUtilMeses, 2);
    }

    /**
     * Calcula el valor neto en libros.
     * Fórmula: Valor de Adquisición - Depreciación Acumulada
     */
    public function calcularValorEnLibros(float $valorAdquisicion, float $depreciacionAcumulada): float {
        return round(max(0.00, $valorAdquisicion - $depreciacionAcumulada), 2);
    }

    /**
     * Enriquece un registro de activo con métricas calculadas.
     */
    public function calcularMetricas(array $activo): array {
        $valorAdq = (float)($activo['valor_adquisicion'] ?? 0.00);
        $valorRes = (float)($activo['valor_residual'] ?? 0.00);
        $vidaMeses = (int)($activo['vida_util_meses'] ?? 1);
        $depAcum = (float)($activo['depreciacion_acumulada'] ?? 0.00);

        $baseDepreciable = round(max(0.00, $valorAdq - $valorRes), 2);
        $cuotaMensual = $this->calcularCuotaMensual($valorAdq, $valorRes, $vidaMeses);
        $valorEnLibros = $this->calcularValorEnLibros($valorAdq, $depAcum);
        $pendienteDepreciar = round(max(0.00, $valorEnLibros - $valorRes), 2);

        $mesesDepreciados = ($cuotaMensual > 0)
            ? min($vidaMeses, (int)floor($depAcum / $cuotaMensual))
            : 0;
        $mesesRestantes = max(0, $vidaMeses - $mesesDepreciados);
        $porcentajeDepreciado = ($baseDepreciable > 0)
            ? min(100.00, round(($depAcum / $baseDepreciable) * 100, 2))
            : 100.00;

        $activo['base_depreciable'] = $baseDepreciable;
        $activo['cuota_mensual'] = $cuotaMensual;
        $activo['valor_en_libros'] = $valorEnLibros;
        $activo['pendiente_depreciar'] = $pendienteDepreciar;
        $activo['meses_depreciados'] = $mesesDepreciados;
        $activo['meses_restantes'] = $mesesRestantes;
        $activo['porcentaje_depreciado'] = $porcentajeDepreciado;

        return $activo;
    }

    /**
     * Resuelve cuentas contables por defecto según la categoría del activo
     * utilizando config_cuentas o catálogo contable.
     */
    public function resolverCuentasDefecto(string $categoria): array {
        $mapaClaves = [
            'maquinaria_equipo' => [
                'activo' => 'ACTIVOS_MAQUINARIA',
                'dep_acum' => 'DEP_ACUM_MAQUINARIA',
                'gasto' => 'GASTO_DEP_MAQUINARIA',
                'codigo_activo' => '1020601',
                'codigo_dep' => '1020905',
                'codigo_gasto' => '5010306'
            ],
            'edificios' => [
                'activo' => 'ACTIVOS_EDIFICIOS',
                'dep_acum' => 'DEP_ACUM_EDIFICIOS',
                'gasto' => 'GASTO_DEP_EDIFICIOS',
                'codigo_activo' => '1020201',
                'codigo_dep' => '1020901',
                'codigo_gasto' => '6010408'
            ],
            'vehiculos' => [
                'activo' => 'ACTIVOS_VEHICULOS',
                'dep_acum' => 'DEP_ACUM_VEHICULOS',
                'gasto' => 'GASTO_DEP_VEHICULOS',
                'codigo_activo' => '1020501',
                'codigo_dep' => '1020904',
                'codigo_gasto' => '6010207'
            ],
            'muebles_enseres' => [
                'activo' => 'ACTIVOS_MUEBLES',
                'dep_acum' => 'DEP_ACUM_MUEBLES',
                'gasto' => 'GASTO_DEP_MUEBLES',
                'codigo_activo' => '1020301',
                'codigo_dep' => '1020902',
                'codigo_gasto' => '6010404'
            ],
            'equipo_computo' => [
                'activo' => 'ACTIVOS_COMPUTO',
                'dep_acum' => 'DEP_ACUM_COMPUTO',
                'gasto' => 'GASTO_DEP_COMPUTO',
                'codigo_activo' => '1020401',
                'codigo_dep' => '1020903',
                'codigo_gasto' => '6010406'
            ]
        ];

        $def = $mapaClaves[$categoria] ?? $mapaClaves['maquinaria_equipo'];

        $idActivo = ConfigCuentasModelo::obtenerIdPorClave($def['activo'], $this->db);
        if (!$idActivo) {
            $idActivo = $this->buscarCuentaPorCodigo($def['codigo_activo']);
        }

        $idDepAcum = ConfigCuentasModelo::obtenerIdPorClave($def['dep_acum'], $this->db);
        if (!$idDepAcum) {
            $idDepAcum = $this->buscarCuentaPorCodigo($def['codigo_dep']);
        }

        $idGasto = ConfigCuentasModelo::obtenerIdPorClave($def['gasto'], $this->db);
        if (!$idGasto) {
            $idGasto = $this->buscarCuentaPorCodigo($def['codigo_gasto']);
        }

        return [
            'cuenta_activo_id' => $idActivo,
            'cuenta_depreciacion_acum_id' => $idDepAcum,
            'cuenta_gasto_depreciacion_id' => $idGasto
        ];
    }

    private function buscarCuentaPorCodigo(string $codigo): ?int {
        try {
            foreach (['cuentas_contables', 'cuentas'] as $tabla) {
                $check = $this->db->query("SHOW TABLES LIKE '{$tabla}'")->fetch();
                if ($check) {
                    $stmt = $this->db->prepare("SELECT id FROM `{$tabla}` WHERE `codigo` = :c LIMIT 1");
                    $stmt->execute(['c' => $codigo]);
                    $id = $stmt->fetchColumn();
                    if ($id) return (int)$id;
                }
            }
        } catch (Throwable $e) {
            // Ignorar y retornar null
        }
        return null;
    }

    // =========================================================================
    // 2. CRUD DE ACTIVOS FIJOS
    // =========================================================================

    public function crearActivo(array $datos): int {
        $codigo = strtoupper(trim($datos['codigo'] ?? ''));
        $descripcion = trim($datos['descripcion'] ?? '');
        $categoria = trim($datos['categoria'] ?? 'maquinaria_equipo');
        $fechaAdquisicion = trim($datos['fecha_adquisicion'] ?? date('Y-m-d'));
        $valorAdquisicion = (float)($datos['valor_adquisicion'] ?? 0.00);
        $valorResidual = (float)($datos['valor_residual'] ?? 0.00);
        $vidaUtilMeses = (int)($datos['vida_util_meses'] ?? 60);
        $depreciacionAcumulada = (float)($datos['depreciacion_acumulada'] ?? 0.00);
        $estado = trim($datos['estado'] ?? 'activo');

        if ($codigo === '') {
            throw new Exception("El código del activo es obligatorio.");
        }
        if ($descripcion === '') {
            throw new Exception("La descripción del activo es obligatoria.");
        }
        if ($valorAdquisicion <= 0) {
            throw new Exception("El valor de adquisición debe ser mayor a 0.");
        }
        if ($vidaUtilMeses <= 0) {
            throw new Exception("La vida útil debe ser al menos de 1 mes.");
        }
        if ($valorResidual < 0) {
            throw new Exception("El valor residual no puede ser negativo.");
        }
        if ($valorResidual >= $valorAdquisicion) {
            throw new Exception("El valor residual no puede ser mayor o igual al valor de adquisición.");
        }

        // Validar unicidad de código
        $checkCod = $this->db->prepare("SELECT COUNT(*) FROM activos_fijos WHERE codigo = :c");
        $checkCod->execute(['c' => $codigo]);
        if ($checkCod->fetchColumn() > 0) {
            throw new Exception("El código de activo '{$codigo}' ya se encuentra registrado.");
        }

        // Cuentas contables por defecto si no fueron especificadas
        $cuentasDefecto = $this->resolverCuentasDefecto($categoria);
        $cuentaActivoId = !empty($datos['cuenta_activo_id']) ? (int)$datos['cuenta_activo_id'] : $cuentasDefecto['cuenta_activo_id'];
        $cuentaDepAcumId = !empty($datos['cuenta_depreciacion_acum_id']) ? (int)$datos['cuenta_depreciacion_acum_id'] : $cuentasDefecto['cuenta_depreciacion_acum_id'];
        $cuentaGastoId = !empty($datos['cuenta_gasto_depreciacion_id']) ? (int)$datos['cuenta_gasto_depreciacion_id'] : $cuentasDefecto['cuenta_gasto_depreciacion_id'];

        // Ajustar estado si ya se encuentra completamente depreciado
        $baseDepreciable = $valorAdquisicion - $valorResidual;
        if ($depreciacionAcumulada >= $baseDepreciable && $estado === 'activo') {
            $estado = 'depreciado';
        }

        $sql = "INSERT INTO activos_fijos (
            codigo, descripcion, categoria, fecha_adquisicion, valor_adquisicion, valor_residual,
            vida_util_meses, depreciacion_acumulada, cuenta_activo_id, cuenta_depreciacion_acum_id,
            cuenta_gasto_depreciacion_id, estado
        ) VALUES (
            :codigo, :descripcion, :categoria, :fecha_adquisicion, :valor_adquisicion, :valor_residual,
            :vida_util_meses, :depreciacion_acumulada, :cuenta_activo_id, :cuenta_depreciacion_acum_id,
            :cuenta_gasto_depreciacion_id, :estado
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'fecha_adquisicion' => $fechaAdquisicion,
            'valor_adquisicion' => $valorAdquisicion,
            'valor_residual' => $valorResidual,
            'vida_util_meses' => $vidaUtilMeses,
            'depreciacion_acumulada' => $depreciacionAcumulada,
            'cuenta_activo_id' => $cuentaActivoId,
            'cuenta_depreciacion_acum_id' => $cuentaDepAcumId,
            'cuenta_gasto_depreciacion_id' => $cuentaGastoId,
            'estado' => $estado
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function obtenerActivoPorId(int $id): ?array {
        $sql = "SELECT af.*,
                       ca.codigo AS cuenta_activo_codigo, ca.nombre AS cuenta_activo_nombre,
                       cd.codigo AS cuenta_dep_codigo, cd.nombre AS cuenta_dep_nombre,
                       cg.codigo AS cuenta_gasto_codigo, cg.nombre AS cuenta_gasto_nombre
                FROM activos_fijos af
                LEFT JOIN cuentas_contables ca ON af.cuenta_activo_id = ca.id
                LEFT JOIN cuentas_contables cd ON af.cuenta_depreciacion_acum_id = cd.id
                LEFT JOIN cuentas_contables cg ON af.cuenta_gasto_depreciacion_id = cg.id
                WHERE af.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->calcularMetricas($row);
    }

    public function obtenerActivoPorCodigo(string $codigo): ?array {
        $stmt = $this->db->prepare("SELECT id FROM activos_fijos WHERE codigo = :c LIMIT 1");
        $stmt->execute(['c' => trim($codigo)]);
        $id = $stmt->fetchColumn();
        return $id ? $this->obtenerActivoPorId((int)$id) : null;
    }

    public function actualizarActivo(int $id, array $datos): bool {
        $activo = $this->obtenerActivoPorId($id);
        if (!$activo) {
            throw new Exception("El activo fijo ID {$id} no existe.");
        }

        $codigo = strtoupper(trim($datos['codigo'] ?? $activo['codigo']));
        $descripcion = trim($datos['descripcion'] ?? $activo['descripcion']);
        $categoria = trim($datos['categoria'] ?? $activo['categoria']);
        $fechaAdquisicion = trim($datos['fecha_adquisicion'] ?? $activo['fecha_adquisicion']);
        $valorAdquisicion = (float)($datos['valor_adquisicion'] ?? $activo['valor_adquisicion']);
        $valorResidual = (float)($datos['valor_residual'] ?? $activo['valor_residual']);
        $vidaUtilMeses = (int)($datos['vida_util_meses'] ?? $activo['vida_util_meses']);
        $depreciacionAcumulada = (float)($datos['depreciacion_acumulada'] ?? $activo['depreciacion_acumulada']);
        $estado = trim($datos['estado'] ?? $activo['estado']);

        // Si cambió el código, verificar que no colisione con otro
        if ($codigo !== $activo['codigo']) {
            $checkCod = $this->db->prepare("SELECT COUNT(*) FROM activos_fijos WHERE codigo = :c AND id != :id");
            $checkCod->execute(['c' => $codigo, 'id' => $id]);
            if ($checkCod->fetchColumn() > 0) {
                throw new Exception("El código de activo '{$codigo}' ya pertenece a otro activo.");
            }
        }

        $cuentaActivoId = !empty($datos['cuenta_activo_id']) ? (int)$datos['cuenta_activo_id'] : $activo['cuenta_activo_id'];
        $cuentaDepAcumId = !empty($datos['cuenta_depreciacion_acum_id']) ? (int)$datos['cuenta_depreciacion_acum_id'] : $activo['cuenta_depreciacion_acum_id'];
        $cuentaGastoId = !empty($datos['cuenta_gasto_depreciacion_id']) ? (int)$datos['cuenta_gasto_depreciacion_id'] : $activo['cuenta_gasto_depreciacion_id'];

        $sql = "UPDATE activos_fijos SET
            codigo = :codigo,
            descripcion = :descripcion,
            categoria = :categoria,
            fecha_adquisicion = :fecha_adquisicion,
            valor_adquisicion = :valor_adquisicion,
            valor_residual = :valor_residual,
            vida_util_meses = :vida_util_meses,
            depreciacion_acumulada = :depreciacion_acumulada,
            cuenta_activo_id = :cuenta_activo_id,
            cuenta_depreciacion_acum_id = :cuenta_depreciacion_acum_id,
            cuenta_gasto_depreciacion_id = :cuenta_gasto_depreciacion_id,
            estado = :estado
        WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'fecha_adquisicion' => $fechaAdquisicion,
            'valor_adquisicion' => $valorAdquisicion,
            'valor_residual' => $valorResidual,
            'vida_util_meses' => $vidaUtilMeses,
            'depreciacion_acumulada' => $depreciacionAcumulada,
            'cuenta_activo_id' => $cuentaActivoId,
            'cuenta_depreciacion_acum_id' => $cuentaDepAcumId,
            'cuenta_gasto_depreciacion_id' => $cuentaGastoId,
            'estado' => $estado,
            'id' => $id
        ]);
    }

    public function eliminarActivo(int $id): bool {
        // Verificar si tiene histórico de depreciaciones
        $checkDep = $this->db->prepare("SELECT COUNT(*) FROM activos_fijos_depreciaciones WHERE activo_fijo_id = :id");
        $checkDep->execute(['id' => $id]);
        if ($checkDep->fetchColumn() > 0) {
            // No borrar físicamente si ya tiene asientos y depreciaciones procesadas; cambiar a 'de_baja'
            $upd = $this->db->prepare("UPDATE activos_fijos SET estado = 'de_baja' WHERE id = :id");
            return $upd->execute(['id' => $id]);
        }

        $stmt = $this->db->prepare("DELETE FROM activos_fijos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function listarActivos(string $busqueda = '', string $categoria = '', string $estado = ''): array {
        $sql = "SELECT af.*,
                       ca.codigo AS cuenta_activo_codigo, ca.nombre AS cuenta_activo_nombre,
                       cd.codigo AS cuenta_dep_codigo, cd.nombre AS cuenta_dep_nombre,
                       cg.codigo AS cuenta_gasto_codigo, cg.nombre AS cuenta_gasto_nombre
                FROM activos_fijos af
                LEFT JOIN cuentas_contables ca ON af.cuenta_activo_id = ca.id
                LEFT JOIN cuentas_contables cd ON af.cuenta_depreciacion_acum_id = cd.id
                LEFT JOIN cuentas_contables cg ON af.cuenta_gasto_depreciacion_id = cg.id
                WHERE 1=1";

        $params = [];
        if ($busqueda !== '') {
            $sql .= " AND (af.codigo LIKE :q1 OR af.descripcion LIKE :q2)";
            $termino = '%' . trim($busqueda) . '%';
            $params['q1'] = $termino;
            $params['q2'] = $termino;
        }
        if ($categoria !== '') {
            $sql .= " AND af.categoria = :categoria";
            $params['categoria'] = $categoria;
        }
        if ($estado !== '') {
            $sql .= " AND af.estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY af.categoria ASC, af.codigo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($filas as $f) {
            $resultado[] = $this->calcularMetricas($f);
        }

        return $resultado;
    }

    // =========================================================================
    // 3. DEPRECIACIONES Y CONSULTA HISTÓRICA
    // =========================================================================

    public function obtenerDepreciacionesPorActivo(int $activoId): array {
        $sql = "SELECT afd.*, pd.num_partida, pd.concepto AS asiento_concepto
                FROM activos_fijos_depreciaciones afd
                LEFT JOIN partidas_diario pd ON afd.id_asiento = pd.id
                WHERE afd.activo_fijo_id = :id
                ORDER BY afd.periodo DESC, afd.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $activoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerHistoricoDepreciaciones(string $busqueda = '', string $periodo = ''): array {
        $sql = "SELECT afd.*, af.codigo AS activo_codigo, af.descripcion AS activo_descripcion, af.categoria AS activo_categoria,
                       pd.num_partida, pd.concepto AS asiento_concepto
                FROM activos_fijos_depreciaciones afd
                JOIN activos_fijos af ON afd.activo_fijo_id = af.id
                LEFT JOIN partidas_diario pd ON afd.id_asiento = pd.id
                WHERE 1=1";

        $params = [];
        if ($busqueda !== '') {
            $sql .= " AND (af.codigo LIKE :q1 OR af.descripcion LIKE :q2)";
            $termino = '%' . trim($busqueda) . '%';
            $params['q1'] = $termino;
            $params['q2'] = $termino;
        }
        if ($periodo !== '') {
            $sql .= " AND afd.periodo = :periodo";
            $params['periodo'] = $periodo;
        }

        $sql .= " ORDER BY afd.periodo DESC, af.codigo ASC, afd.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Simula la depreciación mensual sin persistir cambios en la base de datos.
     */
    public function simularDepreciacion(string $periodo): array {
        $periodo = trim($periodo);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            throw new Exception("El formato del periodo debe ser YYYY-MM (ej. " . date('Y-m') . ").");
        }

        $sql = "SELECT af.*,
                       ca.codigo AS cuenta_activo_codigo,
                       cd.codigo AS cuenta_dep_codigo,
                       cg.codigo AS cuenta_gasto_codigo
                FROM activos_fijos af
                LEFT JOIN cuentas_contables ca ON af.cuenta_activo_id = ca.id
                LEFT JOIN cuentas_contables cd ON af.cuenta_depreciacion_acum_id = cd.id
                LEFT JOIN cuentas_contables cg ON af.cuenta_gasto_depreciacion_id = cg.id
                WHERE af.estado = 'activo'
                  AND (af.valor_adquisicion - af.depreciacion_acumulada) > af.valor_residual
                  AND af.id NOT IN (
                      SELECT activo_fijo_id FROM activos_fijos_depreciaciones WHERE periodo = :periodo
                  )
                ORDER BY af.categoria ASC, af.codigo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['periodo' => $periodo]);
        $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $simulaciones = [];
        $totalDepreciacion = 0.00;

        foreach ($activos as $a) {
            $valorAdq = (float)$a['valor_adquisicion'];
            $valorRes = (float)$a['valor_residual'];
            $vidaMeses = (int)$a['vida_util_meses'];
            $depAcumActual = (float)$a['depreciacion_acumulada'];

            $cuotaMensual = $this->calcularCuotaMensual($valorAdq, $valorRes, $vidaMeses);
            $valorEnLibrosActual = $this->calcularValorEnLibros($valorAdq, $depAcumActual);
            $pendienteDepreciar = round(max(0.00, $valorEnLibrosActual - $valorRes), 2);

            $montoDepreciacion = min($cuotaMensual, $pendienteDepreciar);
            if ($montoDepreciacion <= 0) {
                continue;
            }

            $nuevaDepAcum = round($depAcumActual + $montoDepreciacion, 2);
            $nuevoValorLibros = round($valorAdq - $nuevaDepAcum, 2);
            $nuevoEstado = ($nuevoValorLibros <= $valorRes) ? 'depreciado' : 'activo';

            $cuentasDefecto = $this->resolverCuentasDefecto($a['categoria']);
            $cuentaGastoId = $a['cuenta_gasto_depreciacion_id'] ?: $cuentasDefecto['cuenta_gasto_depreciacion_id'];
            $cuentaDepAcumId = $a['cuenta_depreciacion_acum_id'] ?: $cuentasDefecto['cuenta_depreciacion_acum_id'];

            $simulaciones[] = [
                'activo_id' => (int)$a['id'],
                'codigo' => $a['codigo'],
                'descripcion' => $a['descripcion'],
                'categoria' => $a['categoria'],
                'valor_adquisicion' => $valorAdq,
                'depreciacion_acumulada_actual' => $depAcumActual,
                'monto_depreciacion' => $montoDepreciacion,
                'depreciacion_acumulada_nueva' => $nuevaDepAcum,
                'valor_en_libros_nuevo' => $nuevoValorLibros,
                'nuevo_estado' => $nuevoEstado,
                'cuenta_gasto_id' => $cuentaGastoId,
                'cuenta_dep_acum_id' => $cuentaDepAcumId
            ];

            $totalDepreciacion += $montoDepreciacion;
        }

        return [
            'periodo' => $periodo,
            'total_activos' => count($simulaciones),
            'total_depreciacion' => round($totalDepreciacion, 2),
            'detalles' => $simulaciones
        ];
    }

    // =========================================================================
    // 4. DEPRECIACIÓN MENSUAL AUTOMATIZADA CON ASIENTO CONTABLE (ACID)
    // =========================================================================

    /**
     * Ejecuta el proceso mensual de depreciación de todos los activos fijos elegibles,
     * actualiza sus saldos, genera los registros históricos y emite el asiento contable balanceado en partida doble.
     *
     * @param string $periodo Periodo en formato YYYY-MM
     * @param int $usuarioId ID del usuario que ejecuta la acción
     * @return array Resultado de la operación
     */
    public function ejecutarDepreciacionMensual(string $periodo, int $usuarioId = 1): array {
        $periodo = trim($periodo);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            throw new Exception("El formato del periodo debe ser YYYY-MM (ej. " . date('Y-m') . ").");
        }

        $transaccionPropia = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $transaccionPropia = true;
            }

            // 1. Obtener activos activos con valor en libros > valor residual y no depreciados aún en este periodo
            $sql = "SELECT * FROM activos_fijos
                    WHERE estado = 'activo'
                      AND (valor_adquisicion - depreciacion_acumulada) > valor_residual
                      AND id NOT IN (
                          SELECT activo_fijo_id FROM activos_fijos_depreciaciones WHERE periodo = :periodo
                      )
                    ORDER BY id ASC FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['periodo' => $periodo]);
            $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($activos)) {
                if ($transaccionPropia && $this->db->inTransaction()) {
                    $this->db->commit();
                }
                return [
                    'exito' => true,
                    'mensaje' => "No existen activos pendientes de depreciación para el periodo {$periodo}.",
                    'periodo' => $periodo,
                    'total_procesados' => 0,
                    'monto_total' => 0.00,
                    'asiento_id' => null,
                    'detalles' => []
                ];
            }

            $fechaProceso = date('Y-m-t', strtotime($periodo . '-01'));
            $detallesProcesados = [];
            $totalMonto = 0.00;

            $debitosPorCuenta = [];
            $creditosPorCuenta = [];

            $stmtInsDep = $this->db->prepare("
                INSERT INTO activos_fijos_depreciaciones (
                    activo_fijo_id, periodo, monto_depreciacion, depreciacion_acumulada_nueva,
                    valor_en_libros, id_asiento, fecha_proceso
                ) VALUES (
                    :activo_fijo_id, :periodo, :monto_depreciacion, :depreciacion_acumulada_nueva,
                    :valor_en_libros, NULL, :fecha_proceso
                )
            ");

            $stmtUpdActivo = $this->db->prepare("
                UPDATE activos_fijos SET
                    depreciacion_acumulada = :dep_acum,
                    estado = :estado
                WHERE id = :id
            ");

            $idsDepreciacionesInsertadas = [];

            foreach ($activos as $a) {
                $activoId = (int)$a['id'];
                $valorAdq = (float)$a['valor_adquisicion'];
                $valorRes = (float)$a['valor_residual'];
                $vidaMeses = (int)$a['vida_util_meses'];
                $depAcumActual = (float)$a['depreciacion_acumulada'];

                $cuotaMensual = $this->calcularCuotaMensual($valorAdq, $valorRes, $vidaMeses);
                $valorEnLibrosActual = $this->calcularValorEnLibros($valorAdq, $depAcumActual);
                $pendienteDepreciar = round(max(0.00, $valorEnLibrosActual - $valorRes), 2);

                $montoDepreciacion = min($cuotaMensual, $pendienteDepreciar);
                if ($montoDepreciacion <= 0) {
                    continue;
                }

                $nuevaDepAcum = round($depAcumActual + $montoDepreciacion, 2);
                $nuevoValorLibros = round($valorAdq - $nuevaDepAcum, 2);
                $nuevoEstado = ($nuevoValorLibros <= $valorRes) ? 'depreciado' : 'activo';

                // Registrar histórico de depreciación
                $stmtInsDep->execute([
                    'activo_fijo_id' => $activoId,
                    'periodo' => $periodo,
                    'monto_depreciacion' => $montoDepreciacion,
                    'depreciacion_acumulada_nueva' => $nuevaDepAcum,
                    'valor_en_libros' => $nuevoValorLibros,
                    'fecha_proceso' => $fechaProceso
                ]);
                $depId = (int)$this->db->lastInsertId();
                $idsDepreciacionesInsertadas[] = $depId;

                // Actualizar activo fijo
                $stmtUpdActivo->execute([
                    'dep_acum' => $nuevaDepAcum,
                    'estado' => $nuevoEstado,
                    'id' => $activoId
                ]);

                // Resolver cuentas para la partida doble
                $cuentasDefecto = $this->resolverCuentasDefecto($a['categoria']);
                $cuentaGastoId = $a['cuenta_gasto_depreciacion_id'] ?: $cuentasDefecto['cuenta_gasto_depreciacion_id'];
                $cuentaDepAcumId = $a['cuenta_depreciacion_acum_id'] ?: $cuentasDefecto['cuenta_depreciacion_acum_id'];

                if (!$cuentaGastoId) {
                    // Fallback contable general a costo/gasto depreciación 5010306 o primer 501/601
                    $cuentaGastoId = $this->buscarCuentaPorCodigo('5010306') ?? 259;
                }
                if (!$cuentaDepAcumId) {
                    // Fallback a depreciación acumulada 1020905 o primer 10209
                    $cuentaDepAcumId = $this->buscarCuentaPorCodigo('1020905') ?? 116;
                }

                $debitosPorCuenta[$cuentaGastoId] = ($debitosPorCuenta[$cuentaGastoId] ?? 0.00) + $montoDepreciacion;
                $creditosPorCuenta[$cuentaDepAcumId] = ($creditosPorCuenta[$cuentaDepAcumId] ?? 0.00) + $montoDepreciacion;

                $totalMonto += $montoDepreciacion;

                $detallesProcesados[] = [
                    'depreciacion_id' => $depId,
                    'activo_id' => $activoId,
                    'codigo' => $a['codigo'],
                    'descripcion' => $a['descripcion'],
                    'categoria' => $a['categoria'],
                    'monto_depreciacion' => $montoDepreciacion,
                    'depreciacion_acumulada_nueva' => $nuevaDepAcum,
                    'valor_en_libros' => $nuevoValorLibros,
                    'nuevo_estado' => $nuevoEstado,
                    'cuenta_gasto_id' => $cuentaGastoId,
                    'cuenta_dep_acum_id' => $cuentaDepAcumId
                ];
            }

            $totalMonto = round($totalMonto, 2);

            // 2. Construir líneas de asiento contable y verificar estricta partida doble
            $asientoLineas = [];
            foreach ($debitosPorCuenta as $cuentaId => $monto) {
                $asientoLineas[] = [
                    'id_cuenta_contable' => (int)$cuentaId,
                    'debe' => round($monto, 2),
                    'haber' => 0.00
                ];
            }
            foreach ($creditosPorCuenta as $cuentaId => $monto) {
                $asientoLineas[] = [
                    'id_cuenta_contable' => (int)$cuentaId,
                    'debe' => 0.00,
                    'haber' => round($monto, 2)
                ];
            }

            $sumDebe = round(array_sum(array_column($asientoLineas, 'debe')), 2);
            $sumHaber = round(array_sum(array_column($asientoLineas, 'haber')), 2);

            if (abs($sumDebe - $sumHaber) > 0.01) {
                throw new Exception("Descuadre contable detectado en depreciación: Debe ($sumDebe) != Haber ($sumHaber).");
            }

            // 3. Registrar el asiento contable utilizando ContabilidadModelo
            $contabilidad = new ContabilidadModelo($this->db);
            $conceptoAsiento = "Depreciación de Activos Fijos - Periodo " . $periodo;
            $asientoId = $contabilidad->registrarAsientoContable(
                $fechaProceso,
                $conceptoAsiento,
                'ACTIVOS_FIJOS',
                null,
                $asientoLineas
            );

            if (!$asientoId) {
                throw new Exception("No se pudo generar el asiento contable para la depreciación del periodo {$periodo}.");
            }

            // 4. Actualizar id_asiento en los registros históricos insertados
            if (!empty($idsDepreciacionesInsertadas)) {
                $placeholders = implode(',', array_fill(0, count($idsDepreciacionesInsertadas), '?'));
                $updAsiento = $this->db->prepare("UPDATE activos_fijos_depreciaciones SET id_asiento = ? WHERE id IN ($placeholders)");
                $updAsiento->execute(array_merge([$asientoId], $idsDepreciacionesInsertadas));
            }

            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return [
                'exito' => true,
                'mensaje' => "Depreciación del periodo {$periodo} procesada exitosamente con asiento #{$asientoId}.",
                'periodo' => $periodo,
                'total_procesados' => count($detallesProcesados),
                'monto_total' => $totalMonto,
                'asiento_id' => $asientoId,
                'detalles' => $detallesProcesados
            ];

        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
