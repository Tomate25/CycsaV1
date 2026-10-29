<?php

namespace Cycsa\Modulos\Proyectos\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Proyectos\Modelos\ProyectosModelo;
use Throwable;
use InvalidArgumentException;

class ProyectosControlador extends ControladorBase {

    protected ProyectosModelo $modelo;

    public function __construct(?ProyectosModelo $modelo = null) {
        $this->modelo = $modelo ?? new ProyectosModelo();
    }

    private function asegurarTokenCsrf(): string {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verificarCsrf(array $datos): bool {
        $token = $datos['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        $sesionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($token) && !empty($sesionToken) && hash_equals($sesionToken, (string)$token);
    }

    /**
     * Listado principal de proyectos y órdenes de obra.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $filtros = [
            'busqueda'   => $peticion->obtenerParametro('busqueda', ''),
            'estado'     => $peticion->obtenerParametro('estado', ''),
            'cliente_id' => $peticion->obtenerParametro('cliente_id', ''),
        ];

        $proyectos = $this->modelo->listar($filtros);
        $estadisticas = $this->modelo->obtenerEstadisticasGenerales();
        $clientes = $this->modelo->listarClientesCatalogo();

        $this->renderizar('Proyectos/Vistas/index', [
            'subnavActiva' => 'proyectos',
            'titulo'       => 'Gestión de Proyectos y Órdenes de Obra - CYCSA',
            'proyectos'    => $proyectos,
            'estadisticas' => $estadisticas,
            'clientes'     => $clientes,
            'filtros'      => $filtros,
        ]);
    }

    /**
     * Formulario para crear un nuevo proyecto.
     */
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $csrfToken = $this->asegurarTokenCsrf();
        $sugerenciaCodigo = $this->modelo->generarSiguienteCodigo();
        $clientes = $this->modelo->listarClientesCatalogo();
        $cuentasObras = $this->modelo->listarCuentasObrasCatalogo();
        $cuentaDefault = $this->modelo->obtenerCuentaObraDefault();

        $this->renderizar('Proyectos/Vistas/crear', [
            'subnavActiva'     => 'proyectos',
            'titulo'           => 'Registrar Nuevo Proyecto / Orden de Obra - CYCSA',
            'sugerenciaCodigo' => $sugerenciaCodigo,
            'clientes'         => $clientes,
            'cuentasObras'     => $cuentasObras,
            'cuentaDefault'    => $cuentaDefault,
            'csrfToken'        => $csrfToken,
            'proyecto'         => null,
        ]);
    }

    /**
     * Procesa la creación de un nuevo proyecto (POST).
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => false, 'error' => 'Token de seguridad inválido o expirado.'], 403);
                return;
            }
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            $respuesta->redirigir('/Cycsa/publico/proyectos/crear');
            return;
        }

        try {
            $proyectoId = $this->modelo->crear([
                'codigo'                 => $datos['codigo'] ?? '',
                'nombre'                 => $datos['nombre'] ?? '',
                'cliente_id'             => $datos['cliente_id'] ?? null,
                'ubicacion'              => $datos['ubicacion'] ?? null,
                'presupuesto_estimado'   => $datos['presupuesto_estimado'] ?? 0.00,
                'fecha_inicio'           => $datos['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin'              => $datos['fecha_fin'] ?? null,
                'estado'                 => $datos['estado'] ?? 'planificado',
                'cuenta_obra_proceso_id' => $datos['cuenta_obra_proceso_id'] ?? null,
            ]);

            if ($peticion->esAjax()) {
                $respuesta->json([
                    'exito'       => true,
                    'mensaje'     => 'Proyecto creado exitosamente.',
                    'proyecto_id' => $proyectoId,
                ]);
                return;
            }

            $_SESSION['flash_exito'] = "Proyecto registrado exitosamente con ID #{$proyectoId}.";
            $respuesta->redirigir("/Cycsa/publico/proyectos/detalle?id={$proyectoId}");
        } catch (InvalidArgumentException $ie) {
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => false, 'error' => $ie->getMessage()], 422);
                return;
            }
            $_SESSION['flash_error'] = $ie->getMessage();
            $respuesta->redirigir('/Cycsa/publico/proyectos/crear');
        } catch (Throwable $e) {
            error_log("Error al crear proyecto: " . $e->getMessage());
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => false, 'error' => 'Error interno al registrar el proyecto.'], 500);
                return;
            }
            $_SESSION['flash_error'] = "Error al registrar el proyecto: " . $e->getMessage();
            $respuesta->redirigir('/Cycsa/publico/proyectos/crear');
        }
    }

    /**
     * Vista de detalle del proyecto con las tarjetas de los 4 cuadrantes de costos.
     */
    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)$peticion->obtenerParametro('id', 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Identificador de proyecto no válido.';
            $respuesta->redirigir('/Cycsa/publico/proyectos');
            return;
        }

        $proyecto = $this->modelo->obtenerPorId($id, true);
        if (!$proyecto) {
            $_SESSION['flash_error'] = 'El proyecto solicitado no existe.';
            $respuesta->redirigir('/Cycsa/publico/proyectos');
            return;
        }

        $csrfToken = $this->asegurarTokenCsrf();
        $equiposEnObra = [];
        $bodegaObra = null;
        try {
            $modeloBodegas = new \Cycsa\Modulos\EquiposBodegas\Modelos\EquiposBodegasModelo();
            $bodegaObra = $modeloBodegas->buscarBodegaPorProyecto($id);
            $equiposEnObra = $modeloBodegas->equiposPorProyecto($id);
        } catch (Throwable $e) {
            error_log('Detalle de proyecto, equipos en obra: ' . $e->getMessage());
        }

        $this->renderizar('Proyectos/Vistas/detalle', [
            'subnavActiva' => 'proyectos',
            'titulo'    => "Detalle Proyecto {$proyecto['codigo']} - CYCSA",
            'proyecto'  => $proyecto,
            'costos'    => $proyecto['costos'],
            'avance'    => $proyecto['avance'],
            'csrfToken' => $csrfToken,
            'equiposEnObra' => $equiposEnObra,
            'bodegaObra' => $bodegaObra,
        ]);
    }

    /**
     * Formulario de edición de un proyecto.
     */
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)$peticion->obtenerParametro('id', 0);
        $proyecto = $this->modelo->obtenerPorId($id, false);

        if (!$proyecto) {
            $_SESSION['flash_error'] = 'Proyecto no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/proyectos');
            return;
        }

        $csrfToken = $this->asegurarTokenCsrf();
        $clientes = $this->modelo->listarClientesCatalogo();
        $cuentasObras = $this->modelo->listarCuentasObrasCatalogo();

        $this->renderizar('Proyectos/Vistas/crear', [
            'subnavActiva'     => 'proyectos',
            'titulo'           => "Editar Proyecto {$proyecto['codigo']} - CYCSA",
            'sugerenciaCodigo' => $proyecto['codigo'],
            'clientes'         => $clientes,
            'cuentasObras'     => $cuentasObras,
            'cuentaDefault'    => $proyecto['cuenta_obra_proceso_id'],
            'csrfToken'        => $csrfToken,
            'proyecto'         => $proyecto,
        ]);
    }

    /**
     * Procesa la actualización de un proyecto (POST).
     */
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $datos = $peticion->obtenerDatos();
        $id = (int)($datos['id'] ?? 0);

        if ($id <= 0 || !$this->verificarCsrf($datos)) {
            $_SESSION['flash_error'] = 'Solicitud o token inválido.';
            $respuesta->redirigir('/Cycsa/publico/proyectos');
            return;
        }

        try {
            $this->modelo->actualizar($id, [
                'nombre'                 => $datos['nombre'] ?? null,
                'cliente_id'             => $datos['cliente_id'] ?? null,
                'ubicacion'              => $datos['ubicacion'] ?? null,
                'presupuesto_estimado'   => $datos['presupuesto_estimado'] ?? null,
                'fecha_inicio'           => $datos['fecha_inicio'] ?? null,
                'fecha_fin'              => $datos['fecha_fin'] ?? null,
                'estado'                 => $datos['estado'] ?? null,
                'cuenta_obra_proceso_id' => $datos['cuenta_obra_proceso_id'] ?? null,
            ]);

            $_SESSION['flash_exito'] = 'Proyecto actualizado exitosamente.';
            $respuesta->redirigir("/Cycsa/publico/proyectos/detalle?id={$id}");
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Error al actualizar proyecto: ' . $e->getMessage();
            $respuesta->redirigir("/Cycsa/publico/proyectos/editar?id={$id}");
        }
    }

    /**
     * Cambia el estado de un proyecto vía POST.
     */
    public function cambiarEstado(Peticion $peticion, Respuesta $respuesta): void {
        $datos = $peticion->obtenerDatos();
        $id = (int)($datos['id'] ?? 0);
        $nuevoEstado = trim($datos['estado'] ?? '');

        if ($id <= 0 || empty($nuevoEstado)) {
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => false, 'error' => 'Datos incompletos.'], 400);
                return;
            }
            $respuesta->redirigir('/Cycsa/publico/proyectos');
            return;
        }

        try {
            $this->modelo->cambiarEstado($id, $nuevoEstado);
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => true, 'mensaje' => 'Estado actualizado.']);
                return;
            }
            $_SESSION['flash_exito'] = "Estado de proyecto actualizado a '{$nuevoEstado}'.";
            $respuesta->redirigir("/Cycsa/publico/proyectos/detalle?id={$id}");
        } catch (Throwable $t) {
            if ($peticion->esAjax()) {
                $respuesta->json(['exito' => false, 'error' => $t->getMessage()], 422);
                return;
            }
            $_SESSION['flash_error'] = $t->getMessage();
            $respuesta->redirigir("/Cycsa/publico/proyectos/detalle?id={$id}");
        }
    }

    /**
     * Endpoint API para consultar costos consolidados en formato JSON.
     */
    public function apiCostos(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)$peticion->obtenerParametro('id', 0);
        if ($id <= 0) {
            $respuesta->json(['exito' => false, 'error' => 'ID de proyecto no provisto.'], 400);
            return;
        }

        $costos = $this->modelo->obtenerCostosConsolidados($id, true);
        $respuesta->json(['exito' => true, 'costos' => $costos]);
    }
}
