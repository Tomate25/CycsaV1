<?php

namespace Cycsa\Modulos\Configuracion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;

class ConfiguracionControlador extends ControladorBase {

    private function verificarAccesoPlantillas(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2], true)) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
        }
    }

    public function plantillasEnsayos(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoPlantillas($respuesta);
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $modelo = new ConfiguracionModelo();
        $this->renderizar('configuracion/vistas/plantillas_ensayos', [
            'titulo' => 'Plantillas de Ensayos - CYCSA',
            'plantillas' => $modelo->obtenerPlantillasEnsayos(),
        ]);
    }

    public function obtenerPlantillaAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoPlantillas($respuesta);
        $id = (int)($_GET['id'] ?? 0);
        $plantilla = $id > 0 ? (new ConfiguracionModelo())->obtenerPlantillaPorId($id) : null;
        if (!$plantilla) {
            $respuesta->enviarJson(['error' => 'Plantilla no encontrada'], 404);
            return;
        }
        $respuesta->enviarJson(['success' => true, 'plantilla' => $plantilla]);
    }

    private function validarTokenPlantilla(array $datos, Respuesta $respuesta): void {
        if (!isset($_SESSION['csrf_token'], $datos['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            $respuesta->enviarJson(['error' => 'Token CSRF inválido'], 400);
        }
    }

    private function normalizarPlantilla(array $entrada, array $actual): array {
        $config = $actual;
        foreach ([
            'codigo_formato' => 100, 'version_documento' => 50,
            'titulo_informe' => 120, 'subtitulo_laboratorio' => 180,
            'ensayo_titulo' => 2000, 'norma' => 255,
            'metodo_muestreo' => 255, 'tipo_muestra' => 255,
            'disclaimer' => 5000, 'firmante_nombre' => 150,
            'firmante_cargo' => 150
        ] as $campo => $limite) {
            if (!isset($entrada[$campo]) || !is_string($entrada[$campo])) {
                throw new \InvalidArgumentException("Falta el campo {$campo}.");
            }
            $valor = trim($entrada[$campo]);
            if (mb_strlen($valor) > $limite) throw new \InvalidArgumentException("El campo {$campo} excede el límite.");
            $config[$campo] = $valor;
        }
        foreach (['codigo_formato', 'version_documento', 'titulo_informe', 'ensayo_titulo', 'firmante_nombre', 'firmante_cargo'] as $obligatorio) {
            if ($config[$obligatorio] === '') throw new \InvalidArgumentException("El campo {$obligatorio} es obligatorio.");
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $config['version_documento'])) {
            throw new \InvalidArgumentException('Versión documental inválida.');
        }
        $columnas = $entrada['columns'] ?? null;
        if (!is_array($columnas) || !array_is_list($columnas) || count($columnas) < 1 || count($columnas) > 60) {
            throw new \InvalidArgumentException('La matriz debe tener entre 1 y 60 columnas.');
        }
        $columnas = array_map(static fn($c) => is_string($c) ? trim($c) : '', $columnas);
        if (in_array('', $columnas, true) || count(array_unique($columnas)) !== count($columnas)) {
            throw new \InvalidArgumentException('Las columnas deben tener nombres únicos y no vacíos.');
        }
        foreach ($columnas as $columna) {
            if (mb_strlen($columna) > 120) throw new \InvalidArgumentException('Nombre de columna demasiado largo.');
        }
        $metodos = $entrada['column_methods'] ?? [];
        if (!is_array($metodos)) throw new \InvalidArgumentException('Métodos de columna inválidos.');
        $config['columns'] = $columnas;
        $config['column_methods'] = [];
        foreach ($columnas as $columna) {
            $metodo = $metodos[$columna] ?? '';
            if (!is_string($metodo) || mb_strlen($metodo) > 120) throw new \InvalidArgumentException('Método de columna inválido.');
            $config['column_methods'][$columna] = trim($metodo);
        }
        $aliases = $entrada['column_aliases'] ?? [];
        if (!is_array($aliases)) throw new \InvalidArgumentException('Alias de columnas inválidos.');
        $config['column_aliases'] = [];
        foreach ($columnas as $columna) {
            $alias = $aliases[$columna] ?? null;
            if ($alias !== null) {
                if (!is_string($alias) || mb_strlen($alias) > 120) throw new \InvalidArgumentException('Alias de columna inválido.');
                $config['column_aliases'][$columna] = trim($alias);
            }
        }
        $notas = $entrada['notas'] ?? [];
        if (!is_array($notas) || !array_is_list($notas) || count($notas) > 30) {
            throw new \InvalidArgumentException('Lista de notas inválida.');
        }
        $config['notas'] = [];
        foreach ($notas as $nota) {
            if (!is_string($nota) || mb_strlen($nota) > 1000) throw new \InvalidArgumentException('Nota demasiado larga.');
            if (trim($nota) !== '') $config['notas'][] = trim($nota);
        }
        return $config;
    }

    public function guardarPlantillaEnsayo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoPlantillas($respuesta);
        $datos = $peticion->obtenerDatos();
        $this->validarTokenPlantilla($datos, $respuesta);
        $id = (int)($datos['id'] ?? 0);
        $modelo = new ConfiguracionModelo();
        $plantilla = $id > 0 ? $modelo->obtenerPlantillaPorId($id) : null;
        if (!$plantilla) {
            $respuesta->enviarJson(['error' => 'Plantilla no encontrada'], 404);
            return;
        }
        try {
            $entrada = json_decode($datos['configuracion_json'] ?? '', true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($entrada)) throw new \InvalidArgumentException('JSON de plantilla inválido.');
            $config = $this->normalizarPlantilla($entrada, $plantilla['configuracion']);
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $db->beginTransaction();
            if (!$modelo->guardarPlantillaEnsayo($id, $config) ||
                !registrarBitacora('configuracion', 'editar_plantilla_ensayo', "Plantilla {$plantilla['nombre']} actualizada a {$config['version_documento']}", $id)) {
                throw new \RuntimeException('No se pudo guardar o auditar la plantilla.');
            }
            $db->commit();
            $respuesta->enviarJson(['success' => true, 'message' => 'Plantilla actualizada.']);
        } catch (\InvalidArgumentException|\JsonException $e) {
            $respuesta->enviarJson(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('Error al guardar plantilla: ' . $e->getMessage());
            $respuesta->enviarJson(['error' => 'No se pudo guardar la plantilla.'], 500);
        }
    }

    public function restablecerPlantillaEnsayo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoPlantillas($respuesta);
        $datos = $peticion->obtenerDatos();
        $this->validarTokenPlantilla($datos, $respuesta);
        $id = (int)($datos['id'] ?? 0);
        $modelo = new ConfiguracionModelo();
        $plantilla = $id > 0 ? $modelo->obtenerPlantillaPorId($id) : null;
        if (!$plantilla) {
            $respuesta->enviarJson(['error' => 'Plantilla no encontrada'], 404);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        try {
            $db->beginTransaction();
            if (!$modelo->restablecerPlantillaOriginal($id) ||
                !registrarBitacora('configuracion', 'restablecer_plantilla_ensayo', "Plantilla {$plantilla['nombre']} restablecida al esquema original", $id)) {
                throw new \RuntimeException('No se pudo restablecer o auditar la plantilla.');
            }
            $db->commit();
            $respuesta->enviarJson(['success' => true, 'message' => 'Plantilla original restablecida.']);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Error al restablecer plantilla: ' . $e->getMessage());
            $respuesta->enviarJson(['error' => 'No se pudo restablecer la plantilla.'], 500);
        }
    }
    
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
