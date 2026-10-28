<?php

namespace Cycsa\Modulos\Prestamos\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Prestamos\Modelos\PrestamosModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Exception;
use Throwable;

/**
 * Controlador de Préstamos Bancarios (TAREA-052 - Fase 9).
 */
class PrestamosControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/login');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('prestamos', $accion) && !tienePermiso('contabilidad', $accion)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => false,
                    'error' => 'No tiene permisos suficientes para acceder a Préstamos Bancarios.'
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

    /**
     * Bandeja principal de Préstamos Bancarios.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        $modelo = new PrestamosModelo();
        $prestamos = $modelo->listarPrestamos($busqueda, $estado);
        $resumen = $modelo->obtenerResumenDashboard();

        $this->renderizar('Prestamos/Vistas/index', [
            'titulo' => 'Préstamos Bancarios - Amortización y Cuotas - CYCSA',
            'prestamos' => $prestamos,
            'resumen' => $resumen,
            'busqueda' => $busqueda,
            'estadoFiltro' => $estado,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Formulario de Registro y Simulación de Préstamo.
     */
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $csrfToken = $this->asegurarCsrf();
        $modelo = new PrestamosModelo();
        $sugerenciaCodigo = $modelo->generarSiguienteCodigo();

        // Obtener cuentas bancarias activas
        $contabilidad = new ContabilidadModelo();
        $cuentasBancarias = $contabilidad->obtenerCuentasBancarias();

        // Obtener cuentas de pasivo para préstamos
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $tc = $db->query("SHOW TABLES LIKE 'cuentas_contables'")->fetch() ? 'cuentas_contables' : 'cuentas';
        $cuentasPasivo = $db->query("
            SELECT id, codigo, nombre 
            FROM `{$tc}` 
            WHERE codigo LIKE '20104%' AND tipo = 'DETALLE'
            ORDER BY codigo ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderizar('Prestamos/Vistas/crear', [
            'titulo' => 'Registrar Nuevo Préstamo Bancario - CYCSA',
            'sugerenciaCodigo' => $sugerenciaCodigo,
            'cuentasBancarias' => $cuentasBancarias,
            'cuentasPasivo' => $cuentasPasivo,
            'fechaDefecto' => date('Y-m-d'),
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Simulación AJAX de la tabla de amortización cuota nivelada (método francés).
     */
    public function simularAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $monto = (float)($_POST['monto_prestamo'] ?? 0);
        $tasaAnual = (float)($_POST['tasa_interes_anual'] ?? 0);
        $plazo = (int)($_POST['plazo_meses'] ?? 0);
        $fechaDesembolso = trim($_POST['fecha_desembolso'] ?? date('Y-m-d'));

        if ($monto <= 0 || $plazo <= 0) {
            $respuesta->enviarJson([
                'ok' => false,
                'error' => 'El monto y el plazo deben ser mayores a cero.'
            ], 400);
            return;
        }

        $modelo = new PrestamosModelo();
        $cuotas = $modelo->calcularTablaAmortizacion($monto, $tasaAnual, $plazo, $fechaDesembolso);

        $totalCapital = 0.0;
        $totalInteres = 0.0;
        $totalPagar = 0.0;
        $cuotaNiveladaEstimada = !empty($cuotas) ? $cuotas[0]['cuota_total'] : 0.0;

        foreach ($cuotas as $c) {
            $totalCapital += (float)$c['amortizacion_capital'];
            $totalInteres += (float)$c['interes'];
            $totalPagar += (float)$c['cuota_total'];
        }

        $respuesta->enviarJson([
            'ok' => true,
            'monto' => $monto,
            'tasa_anual' => $tasaAnual,
            'plazo_meses' => $plazo,
            'cuota_mensual' => $cuotaNiveladaEstimada,
            'total_capital' => round($totalCapital, 2),
            'total_interes' => round($totalInteres, 2),
            'total_pagar' => round($totalPagar, 2),
            'cuotas' => $cuotas
        ]);
    }

    /**
     * Guarda el préstamo, genera cuotas, transacción bancaria y asiento de desembolso.
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido o sesión expirada.'], 403);
                return;
            }
            $_SESSION['mensaje_error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/prestamos/crear');
            return;
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

        try {
            $modelo = new PrestamosModelo();
            $resultado = $modelo->registrarPrestamo($datos, $usuarioId);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => "Préstamo {$resultado['codigo']} registrado y desembolsado exitosamente.",
                    'prestamo_id' => $resultado['prestamo_id'],
                    'id_asiento' => $resultado['id_asiento'],
                    'cuotas_generadas' => $resultado['cuotas_generadas']
                ]);
                return;
            }

            $_SESSION['mensaje_exito'] = "Préstamo {$resultado['codigo']} registrado con {$resultado['cuotas_generadas']} cuotas.";
            $respuesta->redirigir('/prestamos/detalle?id=' . $resultado['prestamo_id']);
        } catch (Exception $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['mensaje_error'] = $e->getMessage();
            $respuesta->redirigir('/prestamos/crear');
        }
    }

    /**
     * Detalle completo del préstamo con tabla de amortización y modal de pago.
     */
    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $respuesta->redirigir('/prestamos');
            return;
        }

        $modelo = new PrestamosModelo();
        $prestamo = $modelo->obtenerPrestamoPorId($id);

        if (!$prestamo) {
            $_SESSION['mensaje_error'] = "El préstamo solicitado no existe.";
            $respuesta->redirigir('/prestamos');
            return;
        }

        $cuotas = $modelo->obtenerCuotasPorPrestamo($id);

        // Cuentas bancarias para el modal de pago
        $contabilidad = new ContabilidadModelo();
        $cuentasBancarias = $contabilidad->obtenerCuentasBancarias();

        $this->renderizar('Prestamos/Vistas/detalle', [
            'titulo' => "Detalle de Préstamo {$prestamo['codigo']} - CYCSA",
            'prestamo' => $prestamo,
            'cuotas' => $cuotas,
            'cuentasBancarias' => $cuentasBancarias,
            'fechaDefecto' => date('Y-m-d'),
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Procesa el pago de una cuota de préstamo bancario.
     */
    public function pagarCuota(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear');

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido o sesión expirada.'], 403);
                return;
            }
            $_SESSION['mensaje_error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/prestamos');
            return;
        }

        $cuotaId = (int)($datos['cuota_id'] ?? 0);
        $fechaPago = trim($datos['fecha_pago'] ?? date('Y-m-d'));
        $cuentaBancoId = (int)($datos['cuenta_banco_id'] ?? 0);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

        try {
            $modelo = new PrestamosModelo();
            $resultado = $modelo->pagarCuota($cuotaId, $fechaPago, $cuentaBancoId, $usuarioId);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Cuota pagada exitosamente con asiento contable y débito bancario registrados.',
                    'resultado' => $resultado
                ]);
                return;
            }

            $_SESSION['mensaje_exito'] = 'Cuota pagada correctamente.';
            $respuesta->redirigir('/prestamos/detalle?id=' . $resultado['prestamo_id']);
        } catch (Exception $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['mensaje_error'] = $e->getMessage();
            $pid = (int)($datos['prestamo_id'] ?? 0);
            $respuesta->redirigir($pid > 0 ? "/prestamos/detalle?id={$pid}" : '/prestamos');
        }
    }
}
