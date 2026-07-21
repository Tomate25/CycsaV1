<?php

namespace Cycsa\Modulos\Autenticacion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Autenticacion\Modelos\UsuarioModelo;

class AutenticacionControlador extends ControladorBase {
    
    public function mostrarLogin(Peticion $peticion, Respuesta $respuesta) {
        // Si ya está logueado, no le mostramos el login, lo mandamos al panel
        if (isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        
        $error = null;
        if (isset($_SESSION['login_error'])) {
            $error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => $error
        ]);
    }

    public function procesarLogin(Peticion $peticion, Respuesta $respuesta) {
        $datos = $peticion->obtenerDatos();
        
        // 🔒 Mayor seguridad en inputs: sanitización y validación
        $email = isset($datos['email']) ? filter_var(trim($datos['email']), FILTER_SANITIZE_EMAIL) : '';
        $password = $datos['password'] ?? '';

        // Validar formato de email y longitud de password
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $this->renderizarSinLayout('autenticacion/vistas/login', [
                'titulo' => 'Iniciar Sesión - Cycsa',
                'error' => 'Por favor, ingresa credenciales válidas.'
            ]);
            return;
        }

        $modeloUsuario = new UsuarioModelo();
        $usuario = $modeloUsuario->buscarPorEmail($email);

        if ($usuario) {
            // 1. Verificar si ya está bloqueado
            if ((int)($usuario['bloqueado'] ?? 0) === 1) {
                registrarBitacora('autenticacion', 'intento_login', 'Intento de login en cuenta bloqueada: ' . $email);
                $this->renderizarSinLayout('autenticacion/vistas/login', [
                    'titulo' => 'Iniciar Sesión - Cycsa',
                    'error' => 'Tu cuenta está bloqueada por exceso de intentos fallidos. Contacta al administrador.'
                ]);
                return;
            }

            // 2. Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                if ($usuario['activo'] == 1) {
                    // Restablecer contador de intentos fallidos a 0
                    $modeloUsuario->restablecerIntentos($usuario['id']);

                    // 🔒 SI EL USUARIO TIENE PENDIENTE CAMBIO OBLIGATORIO DE CONTRASEÑA (DESBLOQUEO POR SUPERVISOR)
                    if ((int)($usuario['debe_cambiar_password'] ?? 0) === 1) {
                        $_SESSION['usuario_id_cambio_obligatorio'] = $usuario['id'];
                        $_SESSION['usuario_nombre_cambio_obligatorio'] = $usuario['nombre'];
                        $respuesta->redirigir('/Cycsa/publico/cambiar-password-obligatorio');
                        return;
                    }

                    // Prevenir Session Fixation regenerando el ID
                    session_regenerate_id(true);
                    
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_rol'] = $usuario['id_rol'];
                    
                    $permisos = null;
                    if (!empty($usuario['permisos'])) {
                        $permisos = json_decode($usuario['permisos'], true);
                    } else {
                        try {
                            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                            $stmtRol = $db->prepare("SELECT permisos FROM roles WHERE id = :id_rol LIMIT 1");
                            $stmtRol->execute(['id_rol' => $usuario['id_rol']]);
                            $rolPermisos = $stmtRol->fetchColumn();
                            if (!empty($rolPermisos)) {
                                $permisos = json_decode($rolPermisos, true);
                            }
                        } catch (\Exception $e) {
                            error_log("Error al obtener permisos del rol: " . $e->getMessage());
                        }
                    }
                    $_SESSION['usuario_permisos'] = $permisos;
                    
                    // Guardar la nueva sesión en la base de datos para invalidar anteriores
                    try {
                        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                        $stmt = $db->prepare("UPDATE usuarios SET session_id = :session_id WHERE id = :id");
                        $stmt->execute([
                            'session_id' => session_id(),
                            'id' => $usuario['id']
                        ]);
                    } catch (\Exception $e) {
                        error_log("Error al guardar session_id: " . $e->getMessage());
                    }

                    registrarBitacora('autenticacion', 'login', 'Inicio de sesión exitoso de ' . $usuario['nombre']);
                    $respuesta->redirigir('/Cycsa/publico/panel');
                } else {
                    registrarBitacora('autenticacion', 'intento_login', 'Intento de login en cuenta inactiva: ' . $email);
                    $this->renderizarSinLayout('autenticacion/vistas/login', [
                        'titulo' => 'Iniciar Sesión - Cycsa',
                        'error' => 'Tu cuenta está inactiva.'
                    ]);
                }
            } else {
                // Contraseña incorrecta: Registrar intento fallido
                $intentos = (int)($usuario['intentos_fallidos'] ?? 0);
                $res = $modeloUsuario->registrarIntentoFallido($usuario['id'], $intentos);
                
                if ($res['bloqueado'] === 1) {
                    registrarBitacora('autenticacion', 'bloqueo_usuario', 'Usuario bloqueado por exceder límite de intentos: ' . $email, $usuario['id']);
                    $errorMsg = 'Tu cuenta ha sido bloqueada debido a 5 intentos fallidos consecutivos. Contacta al administrador.';
                } else {
                    $restantes = 5 - $res['intentos_fallidos'];
                    registrarBitacora('autenticacion', 'intento_fallido', 'Intento fallido de login de ' . $email . ' (Intento ' . $res['intentos_fallidos'] . '/5)');
                    $errorMsg = 'Credenciales incorrectas. Te quedan ' . $restantes . ' intentos antes de bloquear tu cuenta.';
                }

                $this->renderizarSinLayout('autenticacion/vistas/login', [
                    'titulo' => 'Iniciar Sesión - Cycsa',
                    'error' => $errorMsg
                ]);
            }
        } else {
            // Usuario no encontrado
            registrarBitacora('autenticacion', 'intento_login', 'Intento fallido de inicio de sesión con correo inexistente: ' . $email);
            $this->renderizarSinLayout('autenticacion/vistas/login', [
                'titulo' => 'Iniciar Sesión - Cycsa',
                'error' => 'Credenciales incorrectas.'
            ]);
        }
    }

    // 🔑 MOSTRAR FORMULARIO DE CAMBIO DE CONTRASEÑA OBLIGATORIO TRAS DESBLOQUEO
    public function mostrarCambiarPasswordObligatorio(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id_cambio_obligatorio'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            return;
        }

        $error = $_SESSION['cambio_pass_error'] ?? null;
        unset($_SESSION['cambio_pass_error']);

        $this->renderizarSinLayout('autenticacion/vistas/cambiar_password_obligatorio', [
            'titulo' => 'Actualizar Contraseña - Cycsa',
            'error' => $error
        ]);
    }

    // 🔑 PROCESAR EL CAMBIO OBLIGATORIO DE CONTRASEÑA
    public function procesarCambiarPasswordObligatorio(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id_cambio_obligatorio'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            return;
        }

        $idUsuario = (int)$_SESSION['usuario_id_cambio_obligatorio'];
        $datos = $peticion->obtenerDatos();
        $password = $datos['password'] ?? '';
        $confirmPassword = $datos['confirm_password'] ?? '';

        if (empty($password) || strlen($password) < 6) {
            $_SESSION['cambio_pass_error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            $respuesta->redirigir('/Cycsa/publico/cambiar-password-obligatorio');
            return;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['cambio_pass_error'] = 'Las contraseñas no coinciden.';
            $respuesta->redirigir('/Cycsa/publico/cambiar-password-obligatorio');
            return;
        }

        // Hashear de forma segura con password_hash (PASSWORD_DEFAULT)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la BD y quitar la marca de cambio obligatorio
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("UPDATE usuarios SET password = :password, debe_cambiar_password = 0, intentos_fallidos = 0, bloqueado = 0 WHERE id = :id");
        $stmt->execute([
            'password' => $hashedPassword,
            'id' => $idUsuario
        ]);

        // Obtener datos completos del usuario para iniciar sesión directamente
        $stmtUser = $db->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $idUsuario]);
        $usuario = $stmtUser->fetch(\PDO::FETCH_ASSOC);

        // Limpiar variable temporal de cambio obligatorio
        unset($_SESSION['usuario_id_cambio_obligatorio'], $_SESSION['usuario_nombre_cambio_obligatorio']);

        // Iniciar sesión real
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['id_rol'];

        $permisos = null;
        if (!empty($usuario['permisos'])) {
            $permisos = json_decode($usuario['permisos'], true);
        } else {
            try {
                $stmtRol = $db->prepare("SELECT permisos FROM roles WHERE id = :id_rol LIMIT 1");
                $stmtRol->execute(['id_rol' => $usuario['id_rol']]);
                $rolPermisos = $stmtRol->fetchColumn();
                if (!empty($rolPermisos)) {
                    $permisos = json_decode($rolPermisos, true);
                }
            } catch (\Exception $e) {}
        }
        $_SESSION['usuario_permisos'] = $permisos;

        try {
            $stmtSess = $db->prepare("UPDATE usuarios SET session_id = :session_id WHERE id = :id");
            $stmtSess->execute(['session_id' => session_id(), 'id' => $usuario['id']]);
        } catch (\Exception $e) {}

        registrarBitacora('autenticacion', 'cambio_password_obligatorio', 'Actualizada contraseña tras desbloqueo por ' . $usuario['nombre']);
        $_SESSION['exito'] = 'Contraseña actualizada exitosamente. Bienvenido al sistema CYCSA.';
        $respuesta->redirigir('/Cycsa/publico/panel');
    }

    // 🔒 NUEVA FUNCIÓN: Destruir la sesión
    public function cerrarSesion(Peticion $peticion, Respuesta $respuesta) {
        if (isset($_SESSION['usuario_nombre'])) {
            // Limpiar session_id en la base de datos al cerrar sesión
            try {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $stmt = $db->prepare("UPDATE usuarios SET session_id = NULL WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['usuario_id']]);
            } catch (\Exception $e) {
                // Silencioso
            }
            registrarBitacora('autenticacion', 'logout', 'Cierre de sesión de ' . $_SESSION['usuario_nombre']);
        }
        session_destroy();
        $_SESSION = [];
        $respuesta->redirigir('/Cycsa/publico/login');
    }

    public function verificarSesionActiva(Peticion $peticion, Respuesta $respuesta): void {
        $respuesta->enviarJson(['status' => 'ok']);
    }

    public function mostrarRecuperarPassword(Peticion $peticion, Respuesta $respuesta) {
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => 'La recuperación y desbloqueo de cuentas es administrada exclusivamente por el Administrador/Supervisor.'
        ]);
    }

    public function procesarRecuperarPassword(Peticion $peticion, Respuesta $respuesta) {
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => 'La recuperación y desbloqueo de cuentas es administrada exclusivamente por el Administrador/Supervisor.'
        ]);
    }

    public function mostrarRestablecerPassword(Peticion $peticion, Respuesta $respuesta) {
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => 'La recuperación y desbloqueo de cuentas es administrada exclusivamente por el Administrador/Supervisor.'
        ]);
    }

    public function procesarRestablecerPassword(Peticion $peticion, Respuesta $respuesta) {
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => 'La recuperación y desbloqueo de cuentas es administrada exclusivamente por el Administrador/Supervisor.'
        ]);
    }
}