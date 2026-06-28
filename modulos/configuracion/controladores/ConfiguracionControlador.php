<?php

namespace Cycsa\Modulos\Configuracion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;

class ConfiguracionControlador extends ControladorBase {
    
    // 🛡️ Verificar que el usuario sea Administrador
    private function verificarAdmin(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    // 📋 Mostrar panel de configuración
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAdmin($respuesta);
        
        $modelo = new ConfiguracionModelo();
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('configuracion/vistas/index', [
            'titulo' => 'Configuración Comercial - Cycsa',
            'condiciones_pago' => $modelo->obtenerPorTipo('condicion_pago'),
            'tiempos_entrega' => $modelo->obtenerPorTipo('tiempo_entrega'),
            'vigencias_oferta' => $modelo->obtenerPorTipo('vigencia_oferta')
        ]);
    }

    // ➕ Agregar opción vía AJAX
    public function agregarAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            
            // CSRF Check
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }

            $tipo = $datos['tipo'] ?? '';
            $valor = trim($datos['valor'] ?? '');

            if (empty($tipo) || empty($valor)) {
                $respuesta->enviarJson(['error' => 'Tipo y valor son requeridos'], 400);
                return;
            }

            $modelo = new ConfiguracionModelo();
            if ($modelo->guardar($tipo, $valor)) {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $id = $db->lastInsertId();
                registrarBitacora('configuracion', 'crear', "Agregada opción comercial: [{$tipo}] => {$valor}", $id);
                
                $respuesta->enviarJson([
                    'success' => true,
                    'id' => $id,
                    'tipo' => $tipo,
                    'valor' => $valor
                ]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al guardar en la base de datos'], 500);
            }
        }
    }

    // ❌ Eliminar opción vía AJAX
    public function eliminarAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            
            // CSRF Check
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }

            $id = (int)($datos['id'] ?? 0);
            if ($id <= 0) {
                $respuesta->enviarJson(['error' => 'ID inválido'], 400);
                return;
            }

            $modelo = new ConfiguracionModelo();
            $registro = $modelo->obtenerPorId($id);
            if (!$registro) {
                $respuesta->enviarJson(['error' => 'No se encontró la opción de configuración'], 404);
                return;
            }

            if ($modelo->eliminar($id)) {
                registrarBitacora('configuracion', 'eliminar', "Eliminada opción comercial: [{$registro['tipo']}] => {$registro['valor']}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al eliminar de la base de datos'], 500);
            }
        }
    }
}
