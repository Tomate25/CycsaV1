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

        $tabActual = $_GET['tab'] ?? 'comercial';
        if (!in_array($tabActual, ['comercial', 'logistica'])) {
            $tabActual = 'comercial';
        }

        $bitacora_logs = obtenerBitacoraModulo('configuracion');

        $this->renderizar('configuracion/vistas/index', [
            'titulo' => 'Configuración - Cycsa',
            'tabActual' => $tabActual,
            'condiciones_pago' => $modelo->obtenerPorTipo('condicion_pago'),
            'tiempos_entrega' => $modelo->obtenerPorTipo('tiempo_entrega'),
            'vigencias_oferta' => $modelo->obtenerPorTipo('vigencia_oferta'),
            'tecnicos' => $modelo->obtenerTecnicos(),
            'vehiculos' => $modelo->obtenerVehiculos(),
            'bitacora_logs' => $bitacora_logs
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

    public function agregarTecnicoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }
            $nombre = trim($datos['nombre'] ?? '');
            if (empty($nombre)) {
                $respuesta->enviarJson(['error' => 'El nombre es requerido'], 400);
                return;
            }
            $modelo = new ConfiguracionModelo();
            if ($modelo->agregarTecnico($nombre)) {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $id = $db->lastInsertId();
                registrarBitacora('configuracion', 'crear', "Agregado técnico de muestreo: {$nombre}", $id);
                $respuesta->enviarJson(['success' => true, 'id' => $id, 'nombre' => $nombre]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al guardar en base de datos'], 500);
            }
        }
    }

    public function eliminarTecnicoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
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
            if ($modelo->eliminarTecnico($id)) {
                registrarBitacora('configuracion', 'eliminar', "Eliminado técnico de muestreo ID: {$id}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al eliminar de la base de datos'], 500);
            }
        }
    }

    public function agregarVehiculoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }
            $placa = trim($datos['placa'] ?? '');
            $marca = trim($datos['marca'] ?? '');
            $modeloCar = trim($datos['modelo'] ?? '');
            if (empty($placa)) {
                $respuesta->enviarJson(['error' => 'La placa es requerida'], 400);
                return;
            }
            $modelo = new ConfiguracionModelo();
            if ($modelo->agregarVehiculo($placa, $marca, $modeloCar)) {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $id = $db->lastInsertId();
                $placaFormated = strtoupper($placa);
                registrarBitacora('configuracion', 'crear', "Agregado vehículo de muestreo Placa: {$placaFormated}", $id);
                $respuesta->enviarJson(['success' => true, 'id' => $id, 'placa' => $placaFormated, 'marca' => $marca, 'modelo' => $modeloCar]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al guardar en base de datos. Placa repetida.'], 500);
            }
        }
    }

    public function eliminarVehiculoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
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
            if ($modelo->eliminarVehiculo($id)) {
                registrarBitacora('configuracion', 'eliminar', "Eliminado vehículo de muestreo ID: {$id}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al eliminar de la base de datos'], 500);
            }
        }
    }

    public function actualizarAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }
            $id = (int)($datos['id'] ?? 0);
            $valor = trim($datos['valor'] ?? '');
            if ($id <= 0 || empty($valor)) {
                $respuesta->enviarJson(['error' => 'ID y valor son requeridos'], 400);
                return;
            }
            $modelo = new ConfiguracionModelo();
            $registro = $modelo->obtenerPorId($id);
            if (!$registro) {
                $respuesta->enviarJson(['error' => 'No se encontró la opción de configuración'], 404);
                return;
            }
            if ($modelo->actualizar($id, $valor)) {
                registrarBitacora('configuracion', 'editar', "Actualizada opción comercial ID {$id}: [{$registro['tipo']}] => {$valor}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al actualizar en base de datos'], 500);
            }
        }
    }

    public function actualizarTecnicoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }
            $id = (int)($datos['id'] ?? 0);
            $nombre = trim($datos['nombre'] ?? '');
            if ($id <= 0 || empty($nombre)) {
                $respuesta->enviarJson(['error' => 'ID y nombre son requeridos'], 400);
                return;
            }
            $modelo = new ConfiguracionModelo();
            if ($modelo->actualizarTecnico($id, $nombre)) {
                registrarBitacora('configuracion', 'editar', "Actualizado técnico de muestreo ID {$id}: {$nombre}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al actualizar en base de datos'], 500);
            }
        }
    }

    public function actualizarVehiculoAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 1) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
                return;
            }
            $id = (int)($datos['id'] ?? 0);
            $placa = trim($datos['placa'] ?? '');
            $marca = trim($datos['marca'] ?? '');
            $modeloCar = trim($datos['modelo'] ?? '');
            if ($id <= 0 || empty($placa)) {
                $respuesta->enviarJson(['error' => 'ID y placa son requeridos'], 400);
                return;
            }
            $modelo = new ConfiguracionModelo();
            if ($modelo->actualizarVehiculo($id, $placa, $marca, $modeloCar)) {
                registrarBitacora('configuracion', 'editar', "Actualizado vehículo ID {$id}: Placa: {$placa}", $id);
                $respuesta->enviarJson(['success' => true]);
            } else {
                $respuesta->enviarJson(['error' => 'Error al actualizar en base de datos'], 500);
            }
        }
    }
}
