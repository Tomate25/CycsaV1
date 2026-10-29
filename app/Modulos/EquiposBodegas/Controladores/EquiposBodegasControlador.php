<?php

namespace Cycsa\Modulos\EquiposBodegas\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\EquiposBodegas\Modelos\EquiposBodegasModelo;
use InvalidArgumentException;
use Throwable;

/**
 * Bodegas de equipos de laboratorio y traslados trazables (Sprint C.4).
 */
class EquiposBodegasControlador extends ControladorBase {

    protected EquiposBodegasModelo $modelo;

    public function __construct(?EquiposBodegasModelo $modelo = null) {
        $this->modelo = $modelo ?? new EquiposBodegasModelo();
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $filtros = [
            'busqueda' => trim((string)$peticion->obtenerParametro('busqueda', '')),
            'tipo' => (string)$peticion->obtenerParametro('tipo', ''),
            'activo' => (string)$peticion->obtenerParametro('activo', ''),
        ];

        $this->renderizar('EquiposBodegas/Vistas/index', [
            'subnavActiva' => 'bodegas',
            'titulo' => 'Bodegas de equipos - CYCSA',
            'bodegas' => $this->modelo->listarBodegas($filtros),
            'filtros' => $filtros,
            'csrfToken' => $this->asegurarTokenCsrf(),
            'tipos' => EquiposBodegasModelo::etiquetasTipo(),
        ]);
    }

    public function bodega(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)$peticion->obtenerParametro('id', 0);
        $bodega = $this->modelo->obtenerBodega($id);
        if (!$bodega) {
            $_SESSION['flash_error'] = 'La bodega solicitada no existe.';
            $respuesta->redirigir('/Cycsa/publico/equipos-bodegas');
            return;
        }

        $this->renderizar('EquiposBodegas/Vistas/bodega', [
            'subnavActiva' => 'bodegas',
            'titulo' => 'Bodega ' . $bodega['codigo'] . ' - CYCSA',
            'bodega' => $bodega,
            'equipos' => $this->modelo->equiposPorBodega($id),
            'csrfToken' => $this->asegurarTokenCsrf(),
            'estados' => EquiposBodegasModelo::etiquetasEstado(),
        ]);
    }

    public function equipo(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)$peticion->obtenerParametro('id', 0);
        $ubicacion = $this->modelo->ubicacionActual($id);
        if (!$ubicacion) {
            $_SESSION['flash_error'] = 'El equipo no tiene una ubicación registrada.';
            $respuesta->redirigir('/Cycsa/publico/equipos-bodegas');
            return;
        }

        $this->renderizar('EquiposBodegas/Vistas/equipo', [
            'subnavActiva' => 'bodegas',
            'titulo' => 'Equipo ' . $ubicacion['equipo_codigo'] . ' - CYCSA',
            'ubicacion' => $ubicacion,
            'historial' => $this->modelo->historial($id),
            'bodegas' => $this->modelo->listarBodegas(['activo' => 1]),
            'csrfToken' => $this->asegurarTokenCsrf(),
            'estados' => EquiposBodegasModelo::etiquetasEstado(),
        ]);
    }

    public function guardarBodega(Peticion $peticion, Respuesta $respuesta): void {
        $datos = $peticion->obtenerDatos();
        $volver = $this->rutaInterna($datos['volver'] ?? '');

        if (!$this->verificarCsrf($datos)) {
            $this->responderError($peticion, $respuesta, 'Token de seguridad inválido o expirado.', $volver ?: '/Cycsa/publico/equipos-bodegas');
            return;
        }

        try {
            $tipo = (string)($datos['tipo'] ?? 'central');
            $proyectoId = (int)($datos['proyecto_id'] ?? 0);
            if ($tipo === 'proyecto' && $proyectoId > 0) {
                $bodega = $this->modelo->obtenerOCrearBodegaProyecto($proyectoId, [
                    'nombre' => $datos['nombre'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'responsable_id' => $datos['responsable_id'] ?? null,
                ]);
                $bodegaId = (int)$bodega['id'];
                $mensaje = 'Bodega de obra lista: ' . $bodega['codigo'] . '.';
            } else {
                $bodegaId = $this->modelo->crearBodega([
                    'codigo' => $datos['codigo'] ?? '',
                    'nombre' => $datos['nombre'] ?? '',
                    'tipo' => $tipo,
                    'proyecto_id' => $proyectoId > 0 ? $proyectoId : null,
                    'responsable_id' => $datos['responsable_id'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'activo' => $datos['activo'] ?? 1,
                ]);
                $mensaje = 'Bodega registrada.';
            }

            $destino = $volver ?: ('/Cycsa/publico/equipos-bodegas/bodega?id=' . $bodegaId);
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['exito' => true, 'mensaje' => $mensaje, 'bodega_id' => $bodegaId]);
                return;
            }
            $_SESSION['flash_exito'] = $mensaje;
            $respuesta->redirigir($destino);
        } catch (InvalidArgumentException $e) {
            $this->responderError($peticion, $respuesta, $e->getMessage(), $volver ?: '/Cycsa/publico/equipos-bodegas', 422);
        } catch (Throwable $e) {
            error_log('Error al guardar bodega de equipos: ' . $e->getMessage());
            $this->responderError($peticion, $respuesta, 'No se pudo registrar la bodega.', $volver ?: '/Cycsa/publico/equipos-bodegas', 500);
        }
    }

    public function trasladar(Peticion $peticion, Respuesta $respuesta): void {
        $datos = $peticion->obtenerDatos();
        $equipoId = (int)($datos['equipo_id'] ?? 0);
        $volverEquipo = '/Cycsa/publico/equipos-bodegas/equipo?id=' . $equipoId;

        if (!$this->verificarCsrf($datos)) {
            $this->responderError($peticion, $respuesta, 'Token de seguridad inválido o expirado.', $volverEquipo);
            return;
        }

        try {
            $trasladoId = $this->modelo->trasladar($equipoId, (int)($datos['bodega_destino_id'] ?? 0), [
                'usuario_id' => (int)($_SESSION['usuario_id'] ?? 0),
                'motivo' => $datos['motivo'] ?? '',
                'documento_ref' => $datos['documento_ref'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'fecha_traslado' => $datos['fecha_traslado'] ?? null,
            ]);

            $mensaje = 'Traslado registrado (#' . $trasladoId . ').';
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['exito' => true, 'mensaje' => $mensaje, 'traslado_id' => $trasladoId]);
                return;
            }
            $_SESSION['flash_exito'] = $mensaje;
            $respuesta->redirigir($volverEquipo);
        } catch (InvalidArgumentException $e) {
            $this->responderError($peticion, $respuesta, $e->getMessage(), $volverEquipo, 422);
        } catch (Throwable $e) {
            error_log('Error al trasladar equipo: ' . $e->getMessage());
            $this->responderError($peticion, $respuesta, 'No se pudo registrar el traslado.', $volverEquipo, 500);
        }
    }

    private function asegurarTokenCsrf(): string {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    private function verificarCsrf(array $datos): bool {
        $this->asegurarTokenCsrf();
        $token = (string)($datos['csrf_token'] ?? '');
        $sesionToken = (string)($_SESSION['csrf_token'] ?? '');
        return $token !== '' && $sesionToken !== '' && hash_equals($sesionToken, $token);
    }

    private function rutaInterna(mixed $ruta): string {
        $ruta = (string)$ruta;
        if ($ruta === '' || !str_starts_with($ruta, '/Cycsa/publico/')) {
            return '';
        }
        if (str_contains($ruta, "\n") || str_contains($ruta, "\r") || str_contains($ruta, '//')) {
            return '';
        }
        return $ruta;
    }

    private function responderError(Peticion $peticion, Respuesta $respuesta, string $mensaje, string $volver, int $codigo = 403): void {
        if ($peticion->esAjax()) {
            $respuesta->enviarJson(['exito' => false, 'error' => $mensaje], $codigo);
            return;
        }
        $_SESSION['flash_error'] = $mensaje;
        $respuesta->redirigir($volver);
    }
}
