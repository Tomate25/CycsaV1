<?php

namespace Cycsa\Modulos\Usuarios\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Usuarios\Modelos\UsuarioModelo;

class UsuariosControlador extends ControladorBase {
    
    // 🛡️ REGLA DE ORO: Verificamos sesión y que sea estrictamente Administrador (Rol 1)
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

    // 🔍 INDEX CON BUSCADOR
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $modelo = new UsuarioModelo();
        $busqueda = $_GET['q'] ?? ''; // Capturamos la búsqueda
        
        $bitacora_logs = obtenerBitacoraModulo('usuarios');

        $this->renderizar('usuarios/vistas/index', [
            'titulo' => 'Gestión de Usuarios - Cycsa',
            'usuarios' => $modelo->obtenerTodos($busqueda),
            'busqueda' => $busqueda,
            'bitacora_logs' => $bitacora_logs
        ]);
    }

    // ➕ FORMULARIO CREAR
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $modelo = new UsuarioModelo();
        $this->renderizar('usuarios/vistas/crear', [
            'titulo' => 'Nuevo Usuario - Cycsa',
            'roles' => $modelo->obtenerRoles()
        ]);
    }

    // 💾 PROCESAR CREACIÓN CON VALIDACIONES ESTRICTAS
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new UsuarioModelo();
            
            $email = isset($datos['email']) ? trim($datos['email']) : '';
            $nombre = isset($datos['nombre']) ? trim($datos['nombre']) : '';
            $password = isset($datos['password']) ? $datos['password'] : '';
            $id_rol = isset($datos['id_rol']) ? (int)$datos['id_rol'] : 0;
            
            // Validación CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $this->renderizar('usuarios/vistas/crear', ['titulo' => 'Nuevo Usuario', 'roles' => $modelo->obtenerRoles(), 'error' => 'Token de seguridad inválido. Intenta de nuevo.']); return;
            }
            
            // Validación formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->renderizar('usuarios/vistas/crear', ['titulo' => 'Nuevo Usuario', 'roles' => $modelo->obtenerRoles(), 'error' => 'El correo electrónico no es válido.']); return;
            }
            
            // Validación longitud de contraseña
            if (strlen(trim($password)) < 6) {
                $this->renderizar('usuarios/vistas/crear', ['titulo' => 'Nuevo Usuario', 'roles' => $modelo->obtenerRoles(), 'error' => 'La contraseña debe tener al menos 6 caracteres.']); return;
            }
            
            // Verificación duplicados
            if ($modelo->emailExiste($email)) {
                $this->renderizar('usuarios/vistas/crear', ['titulo' => 'Nuevo Usuario', 'roles' => $modelo->obtenerRoles(), 'error' => 'Este correo electrónico ya está registrado.']); return;
            }
            
            // Procesamiento de permisos
            $permisosJson = null;
            if ($id_rol != 1) {
                $permisosInput = $datos['permisos'] ?? [];
                $permisos = [
                    'clientes' => [
                        'ver' => isset($permisosInput['clientes']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['clientes']['crear_editar']) ? 1 : 0
                    ],
                    'productos' => [
                        'ver' => isset($permisosInput['productos']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['productos']['crear_editar']) ? 1 : 0
                    ],
                    'cotizaciones' => [
                        'ver' => isset($permisosInput['cotizaciones']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['cotizaciones']['crear_editar']) ? 1 : 0,
                        'aprobar' => isset($permisosInput['cotizaciones']['aprobar']) ? 1 : 0
                    ],
                    'inventario' => [
                        'ver' => isset($permisosInput['inventario']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['inventario']['crear_editar']) ? 1 : 0
                    ],
                    'compras' => [
                        'ver' => isset($permisosInput['compras']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['compras']['crear_editar']) ? 1 : 0
                    ],
                    'contabilidad' => [
                        'ver' => isset($permisosInput['contabilidad']['ver']) ? 1 : 0
                    ],
                    'laboratorio' => [
                        'ver' => isset($permisosInput['laboratorio']['ver']) ? 1 : 0,
                        'crear_editar' => isset($permisosInput['laboratorio']['crear_editar']) ? 1 : 0
                    ]
                ];
                $permisosJson = json_encode($permisos);
            }
            
            $modelo->guardarUsuario($nombre, $email, $password, $id_rol, $permisosJson);
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $lastId = $db->lastInsertId();
            registrarBitacora('usuarios', 'crear', 'Creado usuario: ' . $nombre . ' (correo: ' . $email . ')', $lastId);
            $respuesta->redirigir('/Cycsa/publico/usuarios');
            return;
        }
    }

    // ✏️ MOSTRAR FORMULARIO DE EDICIÓN
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $id = $_GET['id'] ?? null;
        if (!$id) { $respuesta->redirigir('/Cycsa/publico/usuarios'); return; }

        $modelo = new UsuarioModelo();
        $usuario = $modelo->obtenerPorId((int)$id);

        if (!$usuario) { $respuesta->redirigir('/Cycsa/publico/usuarios'); return; }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $this->renderizar('usuarios/vistas/editar', [
            'titulo' => 'Editar Usuario - Cycsa',
            'usuario' => $usuario,
            'roles' => $modelo->obtenerRoles()
        ]);
    }

    // ✏️ GUARDAR LOS CAMBIOS DE EDICIÓN
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $id = $_GET['id'] ?? null;
        if (!$id || !$peticion->esPost()) { $respuesta->redirigir('/Cycsa/publico/usuarios'); return; }

        $datos = $peticion->obtenerDatos();
        $modelo = new UsuarioModelo();

        $email = isset($datos['email']) ? trim($datos['email']) : '';
        $nombre = isset($datos['nombre']) ? trim($datos['nombre']) : '';
        $password = isset($datos['password']) ? $datos['password'] : '';
        $id_rol = isset($datos['id_rol']) ? (int)$datos['id_rol'] : 0;
        $activo = isset($datos['activo']) ? (int)$datos['activo'] : 1;

        // Validación CSRF
        if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->renderizar('usuarios/vistas/editar', ['titulo' => 'Editar Usuario', 'error' => 'Token CSRF inválido.', 'usuario' => $datos, 'roles' => $modelo->obtenerRoles()]); return;
        }

        // Validación formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderizar('usuarios/vistas/editar', ['titulo' => 'Editar Usuario', 'error' => 'El correo electrónico no es válido.', 'usuario' => $datos, 'roles' => $modelo->obtenerRoles()]); return;
        }

        // Si digitó una clave nueva, validamos la longitud
        if (!empty(trim($password)) && strlen(trim($password)) < 6) {
            $this->renderizar('usuarios/vistas/editar', ['titulo' => 'Editar Usuario', 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.', 'usuario' => $datos, 'roles' => $modelo->obtenerRoles()]); return;
        }
        
        // Verificación duplicados (excluyendo el usuario actual)
        if ($modelo->emailExiste($email, (int)$id)) {
            $this->renderizar('usuarios/vistas/editar', ['titulo' => 'Editar Usuario', 'error' => 'El correo ya pertenece a otro usuario.', 'usuario' => $datos, 'roles' => $modelo->obtenerRoles()]); return;
        }

        // Procesamiento de permisos
        $permisosJson = null;
        if ($id_rol != 1) {
            $permisosInput = $datos['permisos'] ?? [];
            $permisos = [
                'clientes' => [
                    'ver' => isset($permisosInput['clientes']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['clientes']['crear_editar']) ? 1 : 0
                ],
                'productos' => [
                    'ver' => isset($permisosInput['productos']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['productos']['crear_editar']) ? 1 : 0
                ],
                'cotizaciones' => [
                    'ver' => isset($permisosInput['cotizaciones']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['cotizaciones']['crear_editar']) ? 1 : 0,
                    'aprobar' => isset($permisosInput['cotizaciones']['aprobar']) ? 1 : 0
                ],
                'inventario' => [
                    'ver' => isset($permisosInput['inventario']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['inventario']['crear_editar']) ? 1 : 0
                ],
                'compras' => [
                    'ver' => isset($permisosInput['compras']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['compras']['crear_editar']) ? 1 : 0
                ],
                'contabilidad' => [
                    'ver' => isset($permisosInput['contabilidad']['ver']) ? 1 : 0
                ],
                'laboratorio' => [
                    'ver' => isset($permisosInput['laboratorio']['ver']) ? 1 : 0,
                    'crear_editar' => isset($permisosInput['laboratorio']['crear_editar']) ? 1 : 0
                ]
            ];
            $permisosJson = json_encode($permisos);
        }

        $datosActualizados = [
            'nombre' => $nombre,
            'email' => $email,
            'id_rol' => $id_rol,
            'activo' => $activo,
            'permisos' => $permisosJson
        ];
        if (!empty(trim($password))) {
            $datosActualizados['password'] = $password;
        }

        $modelo->actualizar((int)$id, $datosActualizados);
        registrarBitacora('usuarios', 'editar', 'Actualizado usuario: ' . $nombre . ' (correo: ' . $email . ')', (int)$id);
        $respuesta->redirigir('/Cycsa/publico/usuarios');
        return;
    }

    // 🗑️ DESACTIVAR USUARIO (ELIMINAR ACCESO)
    public function eliminar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesionAdmin($respuesta);
        
        $id = $_GET['id'] ?? null;
        if ($id) {
            $modelo = new UsuarioModelo();
            $usuario = $modelo->obtenerPorId((int)$id);
            $modelo->desactivar((int)$id);
            if ($usuario) {
                registrarBitacora('usuarios', 'desactivar', 'Desactivado/Eliminado usuario: ' . $usuario['nombre'] . ' (correo: ' . $usuario['email'] . ')', (int)$id);
            }
        }
        
        $respuesta->redirigir('/Cycsa/publico/usuarios');
        return;
    }
}