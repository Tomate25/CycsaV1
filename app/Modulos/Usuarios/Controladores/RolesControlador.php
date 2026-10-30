<?php

namespace Cycsa\Modulos\Usuarios\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Usuarios\Modelos\RolModelo;

class RolesControlador extends ControladorBase {
    
    // 🛡️ BARRERA DE SEGURIDAD: Solo Administrador (Rol 1)
    private function verificarSesionAdmin(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        if ($_SESSION['usuario_rol'] != 1) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    // 🔍 INDEX DE ROLES
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $modelo = new RolModelo();
        $roles = $modelo->obtenerTodos();

        $this->renderizar('usuarios/vistas/roles/index', [
            'titulo' => 'Gestión de Roles - Cycsa',
            'roles' => $roles
        ]);
    }

    // ➕ MOSTRAR FORMULARIO CREAR
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('usuarios/vistas/roles/crear', [
            'titulo' => 'Crear Nuevo Rol - Cycsa'
        ]);
    }

    // 💾 PROCESAR CREACIÓN
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new RolModelo();
            
            $nombre = trim($datos['nombre'] ?? '');
            $descripcion = trim($datos['descripcion'] ?? '');
            
            // Validación CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $this->renderizar('usuarios/vistas/roles/crear', ['titulo' => 'Crear Nuevo Rol', 'error' => 'Token CSRF inválido.']); return;
            }
            
            if (empty($nombre)) {
                $this->renderizar('usuarios/vistas/roles/crear', ['titulo' => 'Crear Nuevo Rol', 'error' => 'El nombre del rol es obligatorio.']); return;
            }

            if ($modelo->nombreExiste($nombre)) {
                $this->renderizar('usuarios/vistas/roles/crear', ['titulo' => 'Crear Nuevo Rol', 'error' => 'Este nombre de rol ya está registrado.']); return;
            }

            // Procesar Permisos marcados en el checklist dinámicamente
            $permisosInput = $datos['permisos'] ?? [];
            $permisos = $this->normalizarPermisos($permisosInput);
            $permisosJson = !empty($permisos) ? json_encode($permisos, JSON_UNESCAPED_UNICODE) : null;

            $modelo->guardar($nombre, $descripcion, $permisosJson);
            registrarBitacora('usuarios', 'crear_rol', 'Creado rol: ' . $nombre);
            
            $respuesta->redirigir('/Cycsa/publico/roles');
            return;
        }
    }

    // ✏️ MOSTRAR FORMULARIO EDICIÓN
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $id = $_GET['id'] ?? null;
        if (!$id) { $respuesta->redirigir('/Cycsa/publico/roles'); return; }

        $modelo = new RolModelo();
        $rol = $modelo->obtenerPorId((int)$id);

        if (!$rol) { $respuesta->redirigir('/Cycsa/publico/roles'); return; }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $this->renderizar('usuarios/vistas/roles/editar', [
            'titulo' => 'Editar Rol - Cycsa',
            'rol' => $rol
        ]);
    }

    // ✏️ GUARDAR CAMBIOS DE EDICIÓN
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $id = $_GET['id'] ?? null;
        if (!$id || !$peticion->esPost()) { $respuesta->redirigir('/Cycsa/publico/roles'); return; }

        $datos = $peticion->obtenerDatos();
        $modelo = new RolModelo();
        
        $nombre = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');

        // Validación CSRF
        if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $rol = $modelo->obtenerPorId((int)$id);
            $this->renderizar('usuarios/vistas/roles/editar', ['titulo' => 'Editar Rol', 'error' => 'Token CSRF inválido.', 'rol' => $rol]); return;
        }

        if (empty($nombre)) {
            $rol = $modelo->obtenerPorId((int)$id);
            $this->renderizar('usuarios/vistas/roles/editar', ['titulo' => 'Editar Rol', 'error' => 'El nombre del rol es obligatorio.', 'rol' => $rol]); return;
        }

        if ($modelo->nombreExiste($nombre, (int)$id)) {
            $rol = $modelo->obtenerPorId((int)$id);
            $this->renderizar('usuarios/vistas/roles/editar', ['titulo' => 'Editar Rol', 'error' => 'Este nombre de rol ya está registrado.', 'rol' => $rol]); return;
        }

        // Procesar Permisos dinámicamente
        $permisosInput = $datos['permisos'] ?? [];
        $permisos = $this->normalizarPermisos($permisosInput);
        $permisosJson = !empty($permisos) ? json_encode($permisos, JSON_UNESCAPED_UNICODE) : null;

        $modelo->actualizar((int)$id, $nombre, $descripcion, $permisosJson);
        registrarBitacora('usuarios', 'editar_rol', 'Actualizado rol: ' . $nombre, (int)$id);
        
        $respuesta->redirigir('/Cycsa/publico/roles');
        return;
    }

    // 🗑️ ELIMINAR ROL
    public function eliminar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/roles');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $_SESSION['roles_error'] = 'Token CSRF inválido o faltante.';
            $respuesta->redirigir('/Cycsa/publico/roles');
            return;
        }

        $id = decodificarId($_POST['id'] ?? $_GET['id'] ?? '');
        if ($id) {
            $modelo = new RolModelo();
            $rol = $modelo->obtenerPorId((int)$id);
            
            if ($rol) {
                if ($modelo->eliminar((int)$id)) {
                    registrarBitacora('usuarios', 'eliminar_rol', 'Eliminado rol: ' . $rol['nombre'], (int)$id);
                } else {
                    // Si falla (porque tiene usuarios asociados o es rol crítico)
                    // Podríamos setear un mensaje en la sesión
                    $_SESSION['roles_error'] = 'No se puede eliminar este rol (es crítico o tiene usuarios asignados).';
                }
            }
        }

        $respuesta->redirigir('/Cycsa/publico/roles');
        return;
    }

    /**
     * Normaliza y valida de forma segura los permisos recibidos del formulario.
     */
    private function normalizarPermisos(array $permisosInput): array {
        $permisos = [];
        foreach ($permisosInput as $modulo => $acciones) {
            if (!is_array($acciones)) {
                continue;
            }
            $modSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$modulo);
            if ($modSanitizado === '') {
                continue;
            }
            foreach ($acciones as $accion => $valor) {
                $accSanitizada = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$accion);
                if ($accSanitizada !== '' && !empty($valor)) {
                    $permisos[$modSanitizado][$accSanitizada] = 1;
                }
            }
        }
        return $permisos;
    }
}
