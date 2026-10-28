<?php

namespace Cycsa\Modulos\Nomina\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Nomina\Modelos\NominaModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Exception;
use Throwable;

/**
 * Controlador de Nómina, Anticipos a Empleados y Provisiones Laborales (TAREA-050).
 */
class NominaControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/login');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('nomina', $accion) && !tienePermiso('contabilidad', $accion) && !tienePermiso('rrhh', $accion)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => false,
                    'error' => 'No tiene permisos suficientes para acceder a Nómina y Recursos Humanos.'
                ], 403);
                return;
            }
            $respuesta->redirigir('/panel');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function asegurarCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function validarCsrfPeticion(array $datos): bool {
        $token = $datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    private function esPeticionAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (!empty($_POST['es_ajax']))
            || (!empty($_GET['es_ajax']));
    }

    // =========================================================================
    // 1. EMPLEADOS Y DEPARTAMENTOS
    // =========================================================================

    public function empleados(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $deptoId = (int)($_GET['departamento_id'] ?? 0);

        $modelo = new NominaModelo();
        $empleados = $modelo->listarEmpleados($busqueda, $estado, $deptoId);
        $departamentos = $modelo->listarDepartamentos(true);

        // Cuentas contables para anticipos o imputación personalizada
        $contabilidad = new ContabilidadModelo();
        $cuentasDetalle = $contabilidad->obtenerCuentasDetalle();

        $this->renderizar('Nomina/Vistas/empleados', [
            'subnavActiva' => 'empleados',
            'titulo' => 'Gestión de Empleados y Salarios - CYCSA',
            'empleados' => $empleados,
            'departamentos' => $departamentos,
            'cuentasDetalle' => $cuentasDetalle,
            'busqueda' => $busqueda,
            'estadoFiltro' => $estado,
            'deptoFiltro' => $deptoId,
            'csrfToken' => $csrfToken
        ]);
    }

    public function guardarEmpleado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            return;
        }

        try {
            $modelo = new NominaModelo();
            $id = (int)($datos['id'] ?? 0);

            if ($id > 0) {
                $modelo->actualizarEmpleado($id, $datos);
                $mensaje = "Empleado actualizado correctamente.";
            } else {
                $id = $modelo->guardarEmpleado($datos);
                $mensaje = "Empleado registrado con éxito.";
            }

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => true, 'mensaje' => $mensaje, 'id' => $id]);
                return;
            }

            $_SESSION['flash_exito'] = $mensaje;
            $respuesta->redirigir('/nomina/empleados');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina/empleados');
        }
    }

    public function cambiarEstadoEmpleado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'editar');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $id = (int)($datos['id'] ?? 0);
            $estado = trim($datos['estado'] ?? 'activo');

            $modelo = new NominaModelo();
            $modelo->cambiarEstadoEmpleado($id, $estado);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => true, 'mensaje' => "Estado del empleado actualizado a '{$estado}'."]);
                return;
            }

            $_SESSION['flash_exito'] = "Estado actualizado.";
            $respuesta->redirigir('/nomina/empleados');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina/empleados');
        }
    }

    // =========================================================================
    // 2. ANTICIPOS A EMPLEADOS
    // =========================================================================

    public function anticipos(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $empleadoId = (int)($_GET['empleado_id'] ?? 0);

        $modelo = new NominaModelo();
        $anticipos = $modelo->listarAnticipos($empleadoId, $estado, $busqueda);
        $empleados = $modelo->listarEmpleados('', 'activo');

        $contabilidad = new ContabilidadModelo();
        $bancos = $contabilidad->obtenerCuentasBancarias();

        $this->renderizar('Nomina/Vistas/anticipos', [
            'subnavActiva' => 'anticipos',
            'titulo' => 'Anticipos y Préstamos a Empleados - CYCSA',
            'anticipos' => $anticipos,
            'empleados' => $empleados,
            'bancos' => $bancos,
            'busqueda' => $busqueda,
            'estadoFiltro' => $estado,
            'empleadoFiltro' => $empleadoId,
            'csrfToken' => $csrfToken
        ]);
    }

    public function guardarAnticipo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $empleadoId = (int)($datos['empleado_id'] ?? 0);
            $monto = (float)($datos['monto'] ?? 0.0);
            $fecha = !empty($datos['fecha']) ? trim($datos['fecha']) : date('Y-m-d');
            $motivo = !empty($datos['motivo']) ? trim($datos['motivo']) : null;
            $idBanco = !empty($datos['id_banco_cuenta']) ? (int)$datos['id_banco_cuenta'] : null;

            $modelo = new NominaModelo();
            $anticipoId = $modelo->registrarAnticipo($empleadoId, $monto, $fecha, $motivo, $idBanco);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Anticipo registrado exitosamente.',
                    'id' => $anticipoId
                ]);
                return;
            }

            $_SESSION['flash_exito'] = "Anticipo registrado exitosamente.";
            $respuesta->redirigir('/nomina/anticipos');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina/anticipos');
        }
    }

    public function anularAnticipo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'editar');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $id = (int)($datos['id'] ?? 0);
            $modelo = new NominaModelo();
            $modelo->anularAnticipo($id);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => true, 'mensaje' => 'Anticipo anulado correctamente.']);
                return;
            }

            $_SESSION['flash_exito'] = "Anticipo anulado.";
            $respuesta->redirigir('/nomina/anticipos');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina/anticipos');
        }
    }

    // =========================================================================
    // 3. NÓMINAS Y PLANILLAS
    // =========================================================================

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        $modelo = new NominaModelo();
        $nominas = $modelo->listarNominas($busqueda, $estado);

        $this->renderizar('Nomina/Vistas/nominas', [
            'subnavActiva' => 'nominas',
            'titulo' => 'Planillas y Nóminas Procesadas - CYCSA',
            'nominas' => $nominas,
            'busqueda' => $busqueda,
            'estadoFiltro' => $estado,
            'csrfToken' => $csrfToken
        ]);
    }

    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $csrfToken = $this->asegurarCsrf();
        $modelo = new NominaModelo();
        $empleados = $modelo->listarEmpleados('', 'activo');

        // Sugerir código de nómina consecutivo (ej. NOM-2026-001)
        $anio = date('Y');
        $sugerenciaCodigo = 'NOM-' . $anio . '-' . str_pad((string)(count($modelo->listarNominas()) + 1), 3, '0', STR_PAD_LEFT);

        $this->renderizar('Nomina/Vistas/crear', [
            'subnavActiva' => 'nominas',
            'titulo' => 'Procesar Nueva Nómina de Sueldos - CYCSA',
            'empleados' => $empleados,
            'sugerenciaCodigo' => $sugerenciaCodigo,
            'fechaInicioDefecto' => date('Y-m-01'),
            'fechaFinDefecto' => date('Y-m-t'),
            'csrfToken' => $csrfToken
        ]);
    }

    public function simularAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        try {
            $modelo = new NominaModelo();
            $empleadosActivos = $modelo->listarEmpleados('', 'activo');
            $desglose = [];

            $totales = [
                'devengado' => 0.0,
                'inss_laboral' => 0.0,
                'ir_laboral' => 0.0,
                'anticipos' => 0.0,
                'deducciones' => 0.0,
                'neto' => 0.0,
                'inss_patronal' => 0.0,
                'inatec' => 0.0,
                'vacaciones' => 0.0,
                'aguinaldo' => 0.0,
                'indemnizacion' => 0.0
            ];

            foreach ($empleadosActivos as $emp) {
                $calc = $modelo->simularCalculoEmpleado(
                    $emp,
                    (float)$emp['salario_base'],
                    0.0,
                    null,
                    0.225
                );
                $desglose[] = $calc;

                $totales['devengado'] += $calc['total_devengado'];
                $totales['inss_laboral'] += $calc['inss_laboral'];
                $totales['ir_laboral'] += $calc['ir_laboral'];
                $totales['anticipos'] += $calc['anticipos_descontados'];
                $totales['deducciones'] += $calc['total_deducciones'];
                $totales['neto'] += $calc['neto_a_pagar'];
                $totales['inss_patronal'] += $calc['inss_patronal'];
                $totales['inatec'] += $calc['inatec_patronal'];
                $totales['vacaciones'] += $calc['vacaciones_provision'];
                $totales['aguinaldo'] += $calc['aguinaldo_provision'];
                $totales['indemnizacion'] += $calc['indemnizacion_provision'];
            }

            $respuesta->enviarJson([
                'ok' => true,
                'empleados' => $desglose,
                'totales' => $totales
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function procesar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $codigo = trim($datos['codigo'] ?? '');
            $periodoInicio = trim($datos['periodo_inicio'] ?? '');
            $periodoFin = trim($datos['periodo_fin'] ?? '');
            $empleadosDataRaw = $datos['empleados'] ?? [];

            // Si se envió en formato JSON string desde AJAX
            if (is_string($empleadosDataRaw)) {
                $empleadosData = json_decode($empleadosDataRaw, true) ?: [];
            } else {
                $empleadosData = (array)$empleadosDataRaw;
            }

            // Si no se detalló lista manual, tomar empleados activos de la base de datos
            $modelo = new NominaModelo();
            if (empty($empleadosData)) {
                $activos = $modelo->listarEmpleados('', 'activo');
                foreach ($activos as $a) {
                    $empleadosData[] = [
                        'empleado_id' => $a['id'],
                        'salario_base' => (float)$a['salario_base'],
                        'horas_extras_monto' => 0.0
                    ];
                }
            }

            $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
            $resultado = $modelo->procesarNomina($codigo, $periodoInicio, $periodoFin, $empleadosData, $usuarioId);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Nómina procesada con éxito y asiento contable generado.',
                    'resultado' => $resultado
                ]);
                return;
            }

            $_SESSION['flash_exito'] = "Nómina '{$codigo}' procesada exitosamente.";
            $respuesta->redirigir('/nomina/detalle?id=' . $resultado['nomina_id']);
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina/crear');
        }
    }

    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $id = (int)($_GET['id'] ?? 0);
        $modelo = new NominaModelo();
        $nomina = $modelo->obtenerNominaPorId($id);

        if (!$nomina) {
            $_SESSION['flash_error'] = "La nómina solicitada no existe.";
            $respuesta->redirigir('/nomina');
            return;
        }

        $csrfToken = $this->asegurarCsrf();
        $this->renderizar('Nomina/Vistas/detalle', [
            'subnavActiva' => 'nominas',
            'titulo' => 'Detalle de Nómina ' . htmlspecialchars($nomina['codigo']) . ' - CYCSA',
            'nomina' => $nomina,
            'csrfToken' => $csrfToken
        ]);
    }

    public function anular(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'editar');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $id = (int)($datos['id'] ?? 0);
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

            $modelo = new NominaModelo();
            $modelo->anularNomina($id, $usuarioId);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => true, 'mensaje' => 'Nómina anulada correctamente y saldos restaurados.']);
                return;
            }

            $_SESSION['flash_exito'] = "Nómina anulada.";
            $respuesta->redirigir('/nomina');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/nomina');
        }
    }
}
